#!/usr/bin/env python3
"""Lädt alle internen Seiten von https://haiger.feg.de als HTML herunter.

Aufruf:
    python3 tools/seiten_laden.py rohdaten/

Legt je Seite eine HTML-Datei sowie _pages.json (Adresse → Datei) an.
Die Dateien sind die Eingabe für tools/joomla_zu_wxr.py.
"""

import json
import os
import re
import sys
import time
import urllib.request

BASIS = 'https://haiger.feg.de'
AUSLASSEN = re.compile(
    r'(format=feed|\.(jpg|png|pdf|gif|css|js|ico|svg|webmanifest|mp3|zip|docx?)$'
    r'|^/(images|media|templates|t3-assets|plugins|components|modules|libraries)/)',
    re.I,
)


def main():
    ziel = sys.argv[1]
    os.makedirs(ziel, exist_ok=True)
    offen, gesehen, seiten = ['/'], set(), {}

    while offen and len(gesehen) < 250:
        pfad = offen.pop(0)
        if pfad in gesehen:
            continue
        gesehen.add(pfad)
        try:
            anfrage = urllib.request.Request(BASIS + pfad, headers={'User-Agent': 'Mozilla/5.0'})
            text = urllib.request.urlopen(anfrage, timeout=30).read().decode('utf-8', 'replace')
        except Exception as fehler:  # noqa: BLE001 – einzelne Fehler überspringen
            print('Fehler:', pfad, fehler, file=sys.stderr)
            continue

        datei = (pfad.strip('/').replace('/', '__') or 'index') + '.html'
        with open(os.path.join(ziel, datei), 'w', encoding='utf-8') as f:
            f.write(text)
        seiten[pfad] = datei

        for link in re.findall(r'href="(/[^"#?]*|https://haiger\.feg\.de/[^"#?]*)"', text):
            link = link.replace(BASIS, '') or '/'
            if link.startswith('//') or AUSLASSEN.search(link):
                continue
            if link not in gesehen and link not in offen:
                offen.append(link)
        time.sleep(0.2)

    with open(os.path.join(ziel, '_pages.json'), 'w', encoding='utf-8') as f:
        json.dump(seiten, f, indent=1)
    print(len(seiten), 'Seiten gespeichert')


if __name__ == '__main__':
    main()
