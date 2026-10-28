# FeG Haiger – Website für WordPress 7.1

Dieses Repository enthält die Umsetzung der bisherigen Joomla-Website
[haiger.feg.de](https://haiger.feg.de/) für **WordPress 7.1.4**:

| Ordner | Inhalt |
| --- | --- |
| `wp-content/themes/feg-haiger/` | Block-Theme im Design der bisherigen Seite (Logo, Menü, Seitenbanner, Seitenleisten, Slideshow, Fußbereich) |
| `wp-content/plugins/feg-haiger-funktionen/` | Terminvorschau aus den Google-Kalendern, Kontaktformular, Weiterleitungen alter Adressen, Einrichtungshilfe |
| `import/feg-haiger-inhalte.xml` | Alle Inhalte als WordPress-Importdatei: 52 Seiten, 1 Beitrag, 89 Bilder/PDFs |
| `tools/` | Skripte, mit denen die Importdatei aus der alten Website erzeugt wurde |

## Installation

1. **WordPress 7.1.4 installieren** (am besten direkt im Hauptverzeichnis der Domain, z. B. `https://haiger.feg.de/`,
   damit die bisherigen Adressen wie `/angebote/kinder/jungschar/` erhalten bleiben).
2. **Theme und Plugin hochladen:** die Ordner `wp-content/themes/feg-haiger` und
   `wp-content/plugins/feg-haiger-funktionen` in die gleichnamigen Ordner der WordPress-Installation kopieren
   (oder jeweils als ZIP über *Design → Themes → Theme hochladen* bzw. *Plugins → Plugin hochladen*).
3. **Aktivieren:** *Design → Themes → „FeG Haiger“* und *Plugins → „FeG Haiger – Funktionen“*.
4. **Inhalte importieren:** *Werkzeuge → Daten importieren → WordPress → Jetzt installieren → Importer ausführen*,
   dann `import/feg-haiger-inhalte.xml` hochladen. Im nächsten Schritt einen Autor zuordnen und
   **„Dateianhänge herunterladen und importieren“ anhaken** – die Bilder werden dann direkt von der
   alten Website übernommen (dazu muss `haiger.feg.de` während des Imports noch erreichbar sein).
5. Nach dem Import richtet das Plugin automatisch ein:
   * „Startseite“ als statische Startseite,
   * „Datenschutzerklärung“ als Datenschutzseite,
   * sprechende Permalinks (`/%postname%/`), sofern noch „Einfach“ oder die Datums-Voreinstellung aktiv ist.

   Falls nötig, lässt sich das unter *Einstellungen → FeG Haiger → „Website einrichten“* wiederholen.
6. Die von WordPress angelegte „Beispiel-Seite“ und den Entwurf „Datenschutzerklärung“ (Slug `privacy-policy`)
   löschen.
7. Optional: unter *Design → Editor → Website-Informationen* ein Website-Icon setzen. Ein eigenes Logo kann
   über den Block „Website-Logo“ im Kopfbereich hinterlegt werden; ohne Logo wird das bisherige FeG-Logo angezeigt.

## Was wo gepflegt wird

* **Seiten** (*Seiten*): Jede Seite besteht aus einem **Banner** (Cover-Block mit Bereichsbild und -name),
  einer **Hauptspalte** mit Überschrift und Text und – wo es sie bisher gab – einer **grauen Seitenleiste**
  (WANN / WO / FÜR WEN / INFOS, Bilder). Für neue Seiten gibt es im Block-Einfüger unter
  *Vorlagen → FeG Haiger* die Vorlage „Seite mit Banner und Seitenleiste“.
* **Veranstaltungen / Aktuelles** (*Beiträge*, Kategorie „Veranstaltungen“): erscheinen automatisch auf der Seite
  *Aktuell*. Das Beitragsbild wird in der Liste angezeigt.
* **Startseite:** Slideshow und Terminvorschau sind Teil der Vorlage (*Design → Editor → Vorlagen → Startseite*);
  die drei Kacheln darunter werden auf der Seite „Startseite“ bearbeitet.
* **Menü:** *Design → Editor → Navigation*.
* **Kopf- und Fußbereich:** *Design → Editor → Vorlagenteile*.

## Funktionen des Plugins

### Terminvorschau `[feg_termine]`

Zeigt die nächsten Termine aus den öffentlichen Google-Kalendern der Gemeinde als farbige Kacheln (wie bisher
„Veranstaltungshinweise“ auf der Startseite). Wiederholungstermine, Ausnahmen und abgesagte Termine werden
berücksichtigt; die Kalender werden eine Stunde zwischengespeichert.

```
[feg_termine]                                   Kacheln, 12 Termine
[feg_termine anzahl="30" ansicht="liste"]       Liste nach Tagen
[feg_termine kalender="Kinder & Jugend"]        nur bestimmte Kalender
```

Kalender, Farben und Zeitraum: *Einstellungen → FeG Haiger*. Voreingestellt sind die fünf öffentlichen
Kalender (Gottesdienste, Besondere Termine, Kinder & Jugend, Senioren, Treffpunkt Bibel).

### Kontaktformular `[feg_kontaktformular]`

Ersetzt das bisherige Formular-Modul. Pflichtfelder Name, E-Mail, Nachricht und Zustimmung zur
Datenschutzerklärung; Spam-Schutz ohne externe Dienste (unsichtbares Feld, Mindest-Ausfülldauer,
Sendebegrenzung). Empfänger: *Einstellungen → FeG Haiger* (voreingestellt `kontakt@haiger.feg.de`).

> Damit E-Mails zuverlässig ankommen, empfiehlt sich ein SMTP-Plugin (z. B. „WP Mail SMTP“) mit dem
> Postfach der Gemeinde.

### Weitere Funktionen

* Alte Beitragsadressen wie `/aktuelles/141-ladies-time-mit-elena-schulte` werden per 301 auf den neuen
  Beitrag umgeleitet.
* E-Mail-Adressen in Inhalten werden – wie bisher in Joomla – vor Spam-Robotern verschleiert.

## Datenschutz-Hinweise

* Das Theme lädt **keine externen Schriften** (keine Google Fonts) – es nutzt Systemschriften.
* Eingebettet bleibt auf der Seite *Terminübersicht* der Google-Kalender (wie bisher). Die Datenschutzerklärung
  sollte nach dem Umstieg auf WordPress geprüft und angepasst werden (z. B. Abschnitte zu Joomla,
  Cookies, Kontaktformular).

## Importdatei neu erzeugen

Falls sich Inhalte auf der alten Website noch ändern, kann die Importdatei neu erzeugt werden
(Python 3 mit `beautifulsoup4`):

```bash
python3 tools/seiten_laden.py rohdaten/
```

```bash
python3 tools/joomla_zu_wxr.py rohdaten/ import/feg-haiger-inhalte.xml
```

## Getestet

Mit WordPress 7.1.4 (PHP 8.3): Installation, Import aller Inhalte inkl. Bilder, Darstellung aller Seiten,
Terminvorschau mit den Live-Kalendern und Kontaktformular.
