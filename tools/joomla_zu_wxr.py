#!/usr/bin/env python3
"""Wandelt die gespeicherten Seiten der bisherigen Joomla-Website in eine
WordPress-Importdatei (WXR) mit Gutenberg-Blöcken um.

Aufruf:
    python3 tools/seiten_laden.py rohdaten/            # Seiten herunterladen
    python3 tools/joomla_zu_wxr.py rohdaten/ import/feg-haiger-inhalte.xml

Ergebnis: Seiten (mit Seitenhierarchie wie die alten Adressen), Beiträge,
Mediendateien (werden beim Import von https://haiger.feg.de geladen).
"""

import html
import json
import os
import re
import sys
from datetime import datetime, timezone
from xml.sax.saxutils import escape

from bs4 import BeautifulSoup, NavigableString, Tag

ALTE_DOMAIN = 'https://haiger.feg.de'
MEDIEN_DIR = None  # optional: lokale Kopie von /images zum Prüfen auf fehlende Dateien
INLINE_TAGS = {'a', 'strong', 'b', 'em', 'i', 'br', 'span', 'sup', 'sub', 'small', 'u', 'code', 'img', 'font', 's'}
FARBEN = {'#0063ae': 'primary', '#ff0000': None, '#cc0000': 'accent-red'}


# ---------------------------------------------------------------------------
# Hilfsfunktionen
# ---------------------------------------------------------------------------

def j(attrs):
    """Block-Attribute als JSON (wie Gutenberg: ohne Leerzeichen, Unicode)."""
    return json.dumps(attrs, ensure_ascii=False, separators=(',', ':')).replace('--', '\\u002d\\u002d')


def block(name, attrs, inner):
    a = (' ' + j(attrs)) if attrs else ''
    return f'<!-- wp:{name}{a} -->\n{inner}\n<!-- /wp:{name} -->'


def leer(text):
    return not text.replace('\xa0', ' ').strip()


def abs_url(url):
    if not url:
        return url
    url = url.strip()
    if url.startswith('/images/') or url.startswith('images/'):
        return ALTE_DOMAIN + '/' + url.lstrip('/')
    if url.startswith(ALTE_DOMAIN + '/images/'):
        return url
    return url


def link_url(url):
    """Interne Links als wurzelrelative Pfade mit abschließendem Schrägstrich."""
    if not url:
        return url
    url = url.strip()
    if url.startswith(ALTE_DOMAIN):
        url = url[len(ALTE_DOMAIN):] or '/'
    if url.startswith('/images/') or url.startswith('images/'):
        return abs_url(url)
    if url.startswith('/') and not url.startswith('//'):
        pfad, _, rest = url.partition('#')
        pfad, _, query = pfad.partition('?')
        if pfad == '/home':
            pfad = '/'
        m = re.match(r'^/aktuelles/\d+-([a-z0-9-]+)$', pfad)
        if m:
            pfad = '/' + m.group(1)
        if not re.search(r'\.[a-z0-9]{2,4}$', pfad) and not pfad.endswith('/'):
            pfad += '/'
        return pfad + ('?' + query if query else '') + ('#' + rest if rest else '')
    return url


def style_dict(tag):
    st = {}
    for teil in (tag.get('style') or '').split(';'):
        if ':' in teil:
            k, v = teil.split(':', 1)
            st[k.strip().lower()] = v.strip().lower()
    return st


# ---------------------------------------------------------------------------
# E-Mail-Verschleierung von Joomla auflösen
# ---------------------------------------------------------------------------

def emails_entschleiern(seite_html):
    """Ersetzt Joomlas <span id="cloak…">…</span><script>…</script> durch den echten mailto-Link."""
    def literale(ausdruck):
        return html.unescape(''.join(re.findall(r"'((?:[^'\\]|\\.)*)'", ausdruck)))

    def ersetzen(m):
        cid, skript = m.group(1), m.group(2)
        kennung = cid[len('cloak'):]
        teil1 = re.search(r'var addy%s = (.*?);\s*\n' % kennung, skript, re.S)
        teil2 = re.search(r'addy%s = addy%s \+ (.*?);\s*\n' % (kennung, kennung), skript, re.S)
        if not teil1:
            return ''
        adresse = literale(teil1.group(1)) + (literale(teil2.group(1)) if teil2 else '')
        text = re.search(r'var addy_text%s = (.*?);document' % kennung, skript, re.S)
        text = literale(text.group(1)) if text else adresse
        attrs = re.search(r"\\'( [^>]*?)>'\s*\+", skript)
        klassen = ' class="btn btn-primary btn-xs"' if attrs and 'btn' in attrs.group(1) else ''
        ziel = ' target="_blank"' if attrs and '_blank' in attrs.group(1) else ''
        return f'<a href="mailto:{adresse}"{ziel}{klassen}>{text}</a>'

    return re.sub(r'<span id="(cloak[0-9a-f]+)">.*?</span>\s*<script[^>]*>(.*?)</script>', ersetzen, seite_html, flags=re.S)


# ---------------------------------------------------------------------------
# Inline-HTML bereinigen
# ---------------------------------------------------------------------------

def inline_html(knoten, bilder_raus=True, aussen=True):
    """Gibt den bereinigten Inline-Inhalt eines Elements zurück."""
    out = []
    for kind in knoten.children:
        if isinstance(kind, NavigableString):
            if kind.__class__.__name__ in ('Comment', 'Doctype', 'Declaration'):
                continue
            out.append(escape(str(kind)).replace('\xa0', '&nbsp;'))
            continue
        if not isinstance(kind, Tag):
            continue
        name = kind.name
        if name in ('script', 'style'):
            continue
        if name == 'img':
            if not bilder_raus:
                out.append(f'<img src="{escape(abs_url(kind.get("src")))}" alt="{escape(kind.get("alt") or "", {chr(34): "&quot;"})}" style="height:{kind.get("height") or 25}px;width:auto"/>')
            continue
        if name == 'br':
            out.append('<br>')
            continue
        innen = inline_html(kind, bilder_raus, aussen=False)
        if name in ('strong', 'b'):
            out.append(f'<strong>{innen}</strong>' if not leer(innen) else innen)
        elif name in ('em', 'i'):
            if 'fa' in (kind.get('class') or []) or 'fas' in (kind.get('class') or []):
                continue
            out.append(f'<em>{innen}</em>' if not leer(innen) else innen)
        elif name in ('sup', 'sub', 'code', 's'):
            out.append(f'<{name}>{innen}</{name}>')
        elif name == 'a':
            href = kind.get('href')
            if not href:
                out.append(innen)
                continue
            attrs = f' href="{escape(link_url(href), {chr(34): "&quot;"})}"'
            klassen = kind.get('class') or []
            if 'btn' in klassen:
                attrs += ' class="feg-btn-klein"'
            if kind.get('target') == '_blank' or href.lower().endswith('.pdf'):
                attrs += ' target="_blank" rel="noreferrer noopener"'
            out.append(f'<a{attrs}>{innen}</a>')
        elif name in ('span', 'font'):
            farbe = style_dict(kind).get('color') or (kind.get('color') or '').lower()
            if farbe and not leer(innen):
                slug = FARBEN.get(farbe)
                if slug:
                    out.append(f'<mark style="background-color:rgba(0, 0, 0, 0)" class="has-inline-color has-{slug}-color">{innen}</mark>')
                else:
                    out.append(f'<mark style="background-color:rgba(0, 0, 0, 0);color:{farbe}" class="has-inline-color">{innen}</mark>')
            else:
                out.append(innen)
        else:
            out.append(innen)
    text = ''.join(out)
    if not aussen:
        return text
    text = re.sub(r'(<br>\s*)+$', '', text.strip())
    text = re.sub(r'^(\s*<br>)+', '', text)
    text = re.sub(r'(&nbsp;\s*)+$', '', text).strip()
    return text


# ---------------------------------------------------------------------------
# Blöcke
# ---------------------------------------------------------------------------

def absatz(inhalt, ausrichtung=None, klasse=None):
    attrs, klassen = {}, []
    if ausrichtung in ('center', 'right'):
        attrs['align'] = ausrichtung
        klassen.append(f'has-text-align-{ausrichtung}')
    if klasse:
        attrs['className'] = klasse
        klassen.append(klasse)
    k = f' class="{" ".join(klassen)}"' if klassen else ''
    return block('paragraph', attrs, f'<p{k}>{inhalt}</p>')


def ueberschrift(text, ebene, ausrichtung=None):
    attrs, klassen = {}, ['wp-block-heading']
    if ebene != 2:
        attrs['level'] = ebene
    if ausrichtung == 'center':
        attrs['textAlign'] = 'center'
        klassen.append('has-text-align-center')
    return block('heading', attrs, f'<h{ebene} class="{" ".join(klassen)}">{text}</h{ebene}>')


def vorhanden(url):
    if not MEDIEN_DIR or not url.startswith(ALTE_DOMAIN + '/images/'):
        return True
    from urllib.parse import unquote
    return os.path.exists(os.path.join(MEDIEN_DIR, unquote(url[len(ALTE_DOMAIN) + 1:])))


def bild(img, link=None, seitenleiste=False, beschriftung=None, ausrichtung=None):
    src = abs_url(img.get('src'))
    if not vorhanden(src):
        print('Bild fehlt auf der alten Website, wird ausgelassen:', src, file=sys.stderr)
        return ''
    alt = img.get('alt') or ''
    st = style_dict(img)
    attrs = {}
    klassen = ['wp-block-image']
    float_ = st.get('float') or ausrichtung
    if float_ in ('left', 'right'):
        attrs['align'] = float_
        klassen.append('align' + float_)
    elif 'margin-left' in st and st.get('margin-left') == 'auto':
        attrs['align'] = 'center'
        klassen.append('aligncenter')
    attrs['sizeSlug'] = 'large'
    klassen.append('size-large')
    breite = None
    if not seitenleiste and st.get('width') != '100%' and img.get('width') not in (None, '100%'):
        try:
            b = int(re.sub(r'\D', '', img.get('width')))
            if b < 700:
                breite = b
        except ValueError:
            breite = None
    if st.get('max-width', '').endswith('px'):
        breite = int(re.sub(r'\D', '', st['max-width']))
    if breite:
        attrs['width'] = f'{breite}px'
        klassen.append('is-resized')
    if 'img-thumbnail' in (img.get('class') or []):
        attrs['className'] = 'is-style-feg-rahmen'
        klassen.append('is-style-feg-rahmen')

    style = f' style="width:{breite}px"' if breite else ''
    img_html = f'<img src="{escape(src)}" alt="{escape(alt, {chr(34): "&quot;"})}"{style}/>'
    if link:
        ziel = link.get('href')
        if ziel and abs_url(ziel) == src:
            attrs['lightbox'] = {'enabled': True}
            inner = img_html
        else:
            attrs['linkDestination'] = 'custom'
            t = ' target="_blank" rel="noreferrer noopener"' if link.get('target') == '_blank' else ''
            if t:
                attrs['linkTarget'] = '_blank'
            inner = f'<a href="{escape(link_url(ziel))}"{t}>{img_html}</a>'
    else:
        inner = img_html
    if beschriftung:
        inner += f'<figcaption class="wp-element-caption">{escape(beschriftung)}</figcaption>'
    # Attributreihenfolge wie Gutenberg (lightbox/width/sizeSlug/linkDestination/align/className)
    reihen = ['lightbox', 'width', 'sizeSlug', 'linkDestination', 'linkTarget', 'align', 'className']
    attrs = {k: attrs[k] for k in reihen if k in attrs}
    return block('image', attrs, f'<figure class="{" ".join(klassen)}">{inner}</figure>')


def buttons(links):
    teile = []
    for a in links:
        t = ' target="_blank" rel="noreferrer noopener"' if a.get('target') == '_blank' else ''
        text = inline_html(a)
        klein = 'btn-xs' in (a.get('class') or [])
        attrs = {'className': 'is-style-feg-klein'} if klein else {}
        k = ' is-style-feg-klein' if klein else ''
        teile.append(block('button', attrs, f'<div class="wp-block-button{k}"><a class="wp-block-button__link wp-element-button" href="{escape(link_url(a.get("href")))}"{t}>{text}</a></div>'))
    return block('buttons', {}, '<div class="wp-block-buttons">' + '\n'.join(teile) + '</div>')


def liste(tag):
    eintraege = []
    for li in tag.find_all('li', recursive=False):
        unter = [c for c in li.children if isinstance(c, Tag) and c.name in ('ul', 'ol')]
        for u in unter:
            u.extract()
        innen = inline_html(li)
        sub = ''.join(liste(u) for u in unter)
        eintraege.append(block('list-item', {}, f'<li>{innen}{sub}</li>'))
    geordnet = tag.name == 'ol'
    attrs = {'ordered': True} if geordnet else {}
    typ = ''
    if geordnet and style_dict(tag).get('list-style-type') == 'lower-alpha':
        attrs['type'] = 'a'
        typ = ' type="a"'
    t = 'ol' if geordnet else 'ul'
    return block('list', attrs, f'<{t} class="wp-block-list"{typ}>' + '\n'.join(eintraege) + f'</{t}>')


def tabelle(tag):
    zeilen = []
    for tr in tag.find_all('tr'):
        zellen = ''.join(f'<td>{inline_html(td)}</td>' for td in tr.find_all(['td', 'th'], recursive=False))
        zeilen.append(f'<tr>{zellen}</tr>')
    return block('table', {'className': 'feg-tabelle'}, f'<figure class="wp-block-table feg-tabelle"><table><tbody>{"".join(zeilen)}</tbody></table></figure>')


def bildraster(paare):
    """Mehrere verlinkte Bilder nebeneinander (z.B. Übersicht „Kinder“)."""
    spalten = []
    for img, a in paare:
        titel = (img.get('title') or '').split('::')
        beschriftung = ' – '.join(t.strip() for t in titel if t.strip()) if titel and titel[0] else None
        spalten.append(block('column', {}, '<div class="wp-block-column">' + bild(img, a, seitenleiste=True, beschriftung=beschriftung) + '</div>'))
    return block('columns', {'className': 'feg-bildraster'}, '<div class="wp-block-columns feg-bildraster">' + '\n'.join(spalten) + '</div>')


def konvertieren(knoten, seitenleiste=False):
    """Konvertiert die Kinder eines Containers in eine Liste von Blöcken."""
    bloecke = []
    inline_puffer = []

    def puffer_leeren():
        if inline_puffer:
            dummy = BeautifulSoup('<p></p>', 'html.parser').p
            for k in inline_puffer:
                dummy.append(k)
            inhalt = inline_html(dummy)
            if not leer(re.sub(r'<br>|&nbsp;', '', inhalt)):
                bloecke.append(absatz(inhalt))
            inline_puffer.clear()

    for kind in list(knoten.children):
        if isinstance(kind, NavigableString):
            if kind.__class__.__name__ == 'Comment':
                continue
            if not leer(str(kind)):
                inline_puffer.append(NavigableString(str(kind)))
            continue
        if not isinstance(kind, Tag):
            continue
        name = kind.name
        if name in INLINE_TAGS and not (name == 'a' and kind.find('img')) and name != 'img':
            inline_puffer.append(kind)
            continue
        puffer_leeren()

        if name in ('script', 'style', 'meta', 'form', 'input', 'label', 'fieldset', 'textarea', 'button', 'legend', 'header'):
            continue
        if name in ('p', 'img', 'a'):
            bloecke.extend(absatz_mit_bildern(kind, seitenleiste))
        elif name in ('h1', 'h2', 'h3', 'h4', 'h5', 'h6'):
            text = inline_html(kind)
            if not leer(text):
                ebene = max(2, int(name[1]))
                bloecke.append(ueberschrift(text, ebene, style_dict(kind).get('text-align')))
        elif name in ('ul', 'ol'):
            bloecke.append(liste(kind))
        elif name == 'blockquote':
            innen = konvertieren(kind) or [absatz(inline_html(kind))]
            bloecke.append(block('quote', {}, '<blockquote class="wp-block-quote">' + '\n'.join(innen) + '</blockquote>'))
        elif name == 'table':
            bloecke.append(tabelle(kind))
        elif name == 'hr':
            bloecke.append(block('separator', {}, '<hr class="wp-block-separator has-alpha-channel-opacity"/>'))
        elif name == 'iframe':
            src = kind.get('src', '')
            src = src.split('&quot;')[0].split('"')[0]
            bloecke.append(block('html', {}, f'<div class="feg-kalender"><iframe src="{escape(html.unescape(src))}" title="{escape(kind.get("title") or "Eingebetteter Inhalt")}" loading="lazy"></iframe></div>'))
        else:
            bloecke.extend(konvertieren(kind, seitenleiste))
    puffer_leeren()
    return [b for b in bloecke if b]


def absatz_mit_bildern(p, seitenleiste):
    bloecke = []
    if p.name == 'img':
        return [bild(p, None, seitenleiste)]
    if p.name == 'a':
        img = p.find('img')
        return [bild(img, p, seitenleiste)] if img else []

    imgs = p.find_all('img')
    st = style_dict(p)
    ausrichtung = st.get('text-align')

    if imgs:
        paare = []
        for img in imgs:
            a = img.find_parent('a')
            if a is not None and p not in a.parents:
                a = None
            paare.append((img, a))
        # Bilder aus dem Absatz entfernen (samt umschließendem Link, wenn dieser nur das Bild enthält)
        for img, a in paare:
            if a is not None and leer(a.get_text()):
                a.extract()
            else:
                img.extract()
        rest = inline_html(p)
        nur_bilder = leer(re.sub(r'<br>|&nbsp;|<[^>]+>', '', rest))
        if nur_bilder and len(paare) > 1 and not seitenleiste and all(i.get('title') for i, _ in paare):
            return [bildraster(paare)]
        for img, a in paare:
            if img.get('src', '').endswith('home.png'):
                continue
            mittig = ausrichtung == 'center' and not style_dict(img).get('float')
            bloecke.append(bild(img, a, seitenleiste, ausrichtung='center' if mittig and not seitenleiste else None))
        if not nur_bilder:
            bloecke.append(absatz(rest, ausrichtung))
        return bloecke

    # Absatz, der nur aus Button-Links besteht → Buttons-Block
    links = p.find_all('a', recursive=False)
    ohne_links = ''.join(str(c) for c in p.children if not (isinstance(c, Tag) and c.name == 'a'))
    if links and all('btn' in (a.get('class') or []) for a in links) and leer(BeautifulSoup(ohne_links, 'html.parser').get_text()):
        return [buttons(links)]

    inhalt = inline_html(p)
    if leer(re.sub(r'<br>|&nbsp;', '', inhalt)):
        return []
    klasse = 'feg-einrueckung' if st.get('padding-left') == '30px' else None
    return [absatz(inhalt, ausrichtung, klasse)]


# ---------------------------------------------------------------------------
# Seitenaufbau
# ---------------------------------------------------------------------------

def banner_block(bild_url, titel):
    url = abs_url(bild_url)
    attrs = {
        'url': url, 'dimRatio': 0, 'isUserOverlayColor': True, 'minHeight': 200, 'isDark': False,
        'className': 'feg-banner',
        'style': {'spacing': {'margin': {'top': 'var:preset|spacing|20'}}},
        'layout': {'type': 'constrained'},
    }
    h = block('heading', {'textAlign': 'center', 'textColor': 'white', 'fontSize': 'xx-large'},
              f'<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color has-xx-large-font-size">{escape(titel)}</h2>')
    return block('cover', attrs,
                 '<div class="wp-block-cover is-light feg-banner" style="margin-top:var(--wp--preset--spacing--20);min-height:200px">'
                 '<span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span>'
                 f'<img class="wp-block-cover__image-background" alt="" src="{escape(url)}" data-object-fit="cover"/>'
                 f'<div class="wp-block-cover__inner-container">\n{h}\n</div></div>')


def seitentitel(titel):
    return block('heading', {'level': 1}, f'<h1 class="wp-block-heading">{titel}</h1>')


def kontaktformular_bloecke():
    return [ueberschrift('Kontaktformular', 3), block('shortcode', {}, '[feg_kontaktformular]')]


def layout(banner, titel, haupt, seite):
    """Banner + (Hauptspalte | Seitenleiste)."""
    teile = []
    if banner:
        teile.append(banner)
    inhalt = [seitentitel(titel)] + haupt
    if seite:
        haupt_spalte = block('column', {'width': '66.66%'},
                             '<div class="wp-block-column" style="flex-basis:66.66%">\n' + '\n\n'.join(inhalt) + '\n</div>')
        leiste = block('column', {
            'width': '33.33%', 'className': 'feg-sidebar', 'backgroundColor': 'light',
            'style': {'spacing': {'padding': {'top': '14px', 'bottom': '14px', 'left': '14px', 'right': '14px'}}},
        }, '<div class="wp-block-column feg-sidebar has-light-background-color has-background" '
           'style="padding-top:14px;padding-right:14px;padding-bottom:14px;padding-left:14px;flex-basis:33.33%">\n'
           + '\n\n'.join(seite) + '\n</div>')
        teile.append(block('columns', {
            'style': {'spacing': {'margin': {'top': 'var:preset|spacing|40'}, 'blockGap': {'left': 'var:preset|spacing|40'}}},
        }, '<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--40)">\n' + haupt_spalte + '\n\n' + leiste + '\n</div>'))
    else:
        teile.append(block('group', {
            'style': {'spacing': {'margin': {'top': 'var:preset|spacing|40'}}}, 'layout': {'type': 'constrained'},
        }, '<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--40)">\n' + '\n\n'.join(inhalt) + '\n</div>'))
    return '\n\n'.join(teile)


def seite_lesen(pfad, roh_html):
    roh_html = emails_entschleiern(roh_html)
    soup = BeautifulSoup(roh_html, 'html.parser')
    titel_tag = soup.find('title')
    menue_titel = html.unescape(titel_tag.get_text()).split(' - FeG Haiger')[0].strip() if titel_tag else pfad

    banner = None
    bh = soup.select_one('.bg-seiten-header')
    if bh:
        m = re.search(r"url\('?([^')]+)", bh.get('style', ''))
        h2 = bh.find('h2')
        banner = (m.group(1) if m else None, h2.get_text(strip=True) if h2 else '')

    inhalt = soup.select_one('#t3-content')
    artikel_titel = None
    haupt = None
    if inhalt:
        t = inhalt.select_one('.article-title') or inhalt.select_one('.page-subtitle')
        if t:
            artikel_titel = ' '.join(t.get_text(' ', strip=True).split())
        haupt = inhalt.select_one('.article-content') or inhalt.select_one('.item-page') or inhalt

    module = []
    for mod in soup.select('.t3-sidebar-right .t3-module'):
        titel = mod.select_one('.module-title')
        if mod.select_one('.dj-easy-contact-wrapper'):
            module.append(('kontakt', None))
            continue
        ct = mod.select_one('.module-ct') or mod
        module.append(('inhalt', ct, titel.get_text(strip=True) if titel else None))

    return {
        'pfad': pfad, 'menue_titel': menue_titel, 'titel': artikel_titel or menue_titel,
        'banner': banner, 'haupt': haupt, 'module': module, 'soup': soup,
    }


def seitenleiste_bloecke(module):
    bloecke = []
    for m in module:
        if m[0] == 'kontakt':
            bloecke.extend(kontaktformular_bloecke())
        else:
            if m[2]:
                bloecke.append(ueberschrift(m[2], 3))
            bloecke.extend(konvertieren(m[1], seitenleiste=True))
    return bloecke


# ---------------------------------------------------------------------------
# Sonderseiten
# ---------------------------------------------------------------------------

def startseite(info):
    """Die drei Teaser-Kacheln der bisherigen Startseite."""
    spalten = []
    for art in info['soup'].select('.blog-featured .item'):
        intro = art.select_one('.article-intro')
        mehr = art.select_one('.readmore a')
        bl = konvertieren(intro)
        if mehr:
            bl.append(block('buttons', {'layout': {'type': 'flex', 'justifyContent': 'right'}},
                            '<div class="wp-block-buttons">' + block('button', {'className': 'is-style-outline'},
                            f'<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="{escape(link_url(mehr.get("href")))}">Weiterlesen …</a></div>') + '</div>'))
        spalten.append(block('column', {}, '<div class="wp-block-column">\n' + '\n\n'.join(bl) + '\n</div>'))
    return block('columns', {'className': 'feg-teaser'},
                 '<div class="wp-block-columns feg-teaser">\n' + '\n\n'.join(spalten) + '\n</div>')


def aktuelles_seite(info):
    intro = konvertieren(info['soup'].select_one('.category-desc'))
    query = (
        '<!-- wp:query {"queryId":2,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->\n'
        '<div class="wp-block-query"><!-- wp:post-template -->\n'
        '<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|30"},"margin":{"bottom":"var:preset|spacing|30"}},"border":{"bottom":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"constrained"}} -->\n'
        '<div class="wp-block-group" style="border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;margin-bottom:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">'
        '<!-- wp:post-title {"level":2,"isLink":true} /-->\n'
        '<!-- wp:columns -->\n<div class="wp-block-columns"><!-- wp:column {"width":"45%"} -->\n<div class="wp-block-column" style="flex-basis:45%"><!-- wp:post-featured-image {"isLink":true,"className":"is-style-feg-rahmen"} /--></div>\n<!-- /wp:column -->\n\n'
        '<!-- wp:column {"width":"55%"} -->\n<div class="wp-block-column" style="flex-basis:55%"><!-- wp:post-excerpt {"moreText":"Weiterlesen …","excerptLength":45} /--></div>\n<!-- /wp:column --></div>\n<!-- /wp:columns --></div>\n<!-- /wp:group -->\n'
        '<!-- /wp:post-template -->\n\n'
        '<!-- wp:query-no-results -->\n<!-- wp:paragraph -->\n<p>Zurzeit sind keine besonderen Veranstaltungen angekündigt.</p>\n<!-- /wp:paragraph -->\n<!-- /wp:query-no-results --></div>\n'
        '<!-- /wp:query -->'
    )
    return layout(banner_block(*info['banner']), 'Besondere Veranstaltungen', intro + [query], None)


def suche_seite(info):
    haupt = [b for b in konvertieren(info['haupt']) if 'Suchen' not in b]
    haupt.append('<!-- wp:search {"label":"Suche","showLabel":false,"placeholder":"Suchbegriff …","buttonText":"Suchen"} /-->')
    return layout(banner_block(*info['banner']), info['titel'], haupt, seitenleiste_bloecke(info['module']))


# ---------------------------------------------------------------------------
# WXR
# ---------------------------------------------------------------------------

def cdata(text):
    return '<![CDATA[' + text.replace(']]>', ']]]]><![CDATA[>') + ']]>'


class Export:
    def __init__(self):
        self.naechste_id = 100
        self.items = []
        self.anhaenge = {}

    def neue_id(self):
        self.naechste_id += 1
        return self.naechste_id

    def anhang(self, url, eltern=0):
        if url in self.anhaenge:
            return self.anhaenge[url]
        aid = self.neue_id()
        name = os.path.basename(url)
        titel = os.path.splitext(name)[0].replace('-', ' ').replace('_', ' ')
        # Der Importer überspringt Anhänge mit gleichem Titel – bei gleichem Dateinamen Ordner ergänzen.
        if any(i['typ'] == 'attachment' and i['titel'] == titel for i in self.items):
            titel += ' (' + os.path.basename(os.path.dirname(url)) + ')'
        self.anhaenge[url] = aid
        self.items.append(dict(typ='attachment', id=aid, titel=titel, slug='bild-' + re.sub(r'[^a-z0-9]+', '-', titel.lower()).strip('-'),
                               inhalt='', eltern=eltern, url=url, pfad=url.replace(ALTE_DOMAIN + '/images/', '')))
        return aid

    def eintrag(self, typ, titel, slug, inhalt, eltern=0, reihenfolge=0, meta=None, auszug='', kategorie=None, datum=None):
        pid = self.neue_id()
        self.items.append(dict(typ=typ, id=pid, titel=titel, slug=slug, inhalt=inhalt, eltern=eltern,
                               reihenfolge=reihenfolge, meta=meta or {}, auszug=auszug, kategorie=kategorie, datum=datum))
        return pid

    def xml(self):
        jetzt = datetime.now(timezone.utc)
        out = ['<?xml version="1.0" encoding="UTF-8" ?>',
               '<rss version="2.0" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" '
               'xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" '
               'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/">',
               '<channel>',
               '<title>FeG Haiger</title>', f'<link>{ALTE_DOMAIN}</link>',
               '<description>Die Freie evangelische Gemeinde in Haiger</description>',
               f'<pubDate>{jetzt.strftime("%a, %d %b %Y %H:%M:%S +0000")}</pubDate>',
               '<language>de-DE</language>', '<wp:wxr_version>1.2</wp:wxr_version>',
               f'<wp:base_site_url>{ALTE_DOMAIN}</wp:base_site_url>', f'<wp:base_blog_url>{ALTE_DOMAIN}</wp:base_blog_url>',
               '<wp:author><wp:author_id>1</wp:author_id><wp:author_login>' + cdata('feg-redaktion') + '</wp:author_login>'
               '<wp:author_email>' + cdata('kontakt@haiger.feg.de') + '</wp:author_email><wp:author_display_name>'
               + cdata('FeG Haiger') + '</wp:author_display_name><wp:author_first_name>' + cdata('') +
               '</wp:author_first_name><wp:author_last_name>' + cdata('') + '</wp:author_last_name></wp:author>',
               '<wp:category><wp:term_id>2</wp:term_id><wp:category_nicename>' + cdata('veranstaltungen') +
               '</wp:category_nicename><wp:category_parent>' + cdata('') + '</wp:category_parent><wp:cat_name>' +
               cdata('Veranstaltungen') + '</wp:cat_name></wp:category>',
               '<generator>joomla_zu_wxr.py</generator>']
        # Seiten und Beiträge vor den Mediendateien, damit deren Adressen Vorrang haben.
        for it in sorted(self.items, key=lambda i: i['typ'] == 'attachment'):
            datum = it.get('datum') or jetzt
            d_lokal = datum.strftime('%Y-%m-%d %H:%M:%S')
            out.append('<item>')
            out.append(f'<title>{cdata(it["titel"])}</title>')
            out.append(f'<link>{ALTE_DOMAIN}/</link>')
            out.append(f'<pubDate>{datum.strftime("%a, %d %b %Y %H:%M:%S +0000")}</pubDate>')
            out.append('<dc:creator>' + cdata('feg-redaktion') + '</dc:creator>')
            guid = it['url'] if it['typ'] == 'attachment' else f'{ALTE_DOMAIN}/?p={it["id"]}'
            out.append(f'<guid isPermaLink="false">{escape(guid)}</guid>')
            out.append('<description></description>')
            out.append(f'<content:encoded>{cdata(it["inhalt"])}</content:encoded>')
            out.append(f'<excerpt:encoded>{cdata(it.get("auszug") or "")}</excerpt:encoded>')
            out.append(f'<wp:post_id>{it["id"]}</wp:post_id>')
            out.append(f'<wp:post_date>{cdata(d_lokal)}</wp:post_date>')
            out.append(f'<wp:post_date_gmt>{cdata(d_lokal)}</wp:post_date_gmt>')
            out.append('<wp:comment_status>' + cdata('closed') + '</wp:comment_status>')
            out.append('<wp:ping_status>' + cdata('closed') + '</wp:ping_status>')
            out.append(f'<wp:post_name>{cdata(it["slug"])}</wp:post_name>')
            out.append('<wp:status>' + cdata('inherit' if it['typ'] == 'attachment' else 'publish') + '</wp:status>')
            out.append(f'<wp:post_parent>{it["eltern"]}</wp:post_parent>')
            out.append(f'<wp:menu_order>{it.get("reihenfolge", 0)}</wp:menu_order>')
            out.append(f'<wp:post_type>{cdata(it["typ"])}</wp:post_type>')
            out.append('<wp:post_password>' + cdata('') + '</wp:post_password>')
            out.append('<wp:is_sticky>0</wp:is_sticky>')
            if it['typ'] == 'attachment':
                out.append(f'<wp:attachment_url>{cdata(it["url"])}</wp:attachment_url>')
            if it.get('kategorie'):
                out.append(f'<category domain="category" nicename="{it["kategorie"][0]}">{cdata(it["kategorie"][1])}</category>')
            for k, v in (it.get('meta') or {}).items():
                out.append(f'<wp:postmeta><wp:meta_key>{cdata(k)}</wp:meta_key><wp:meta_value>{cdata(str(v))}</wp:meta_value></wp:postmeta>')
            out.append('</item>')
        out.append('</channel>')
        out.append('</rss>')
        return '\n'.join(out) + '\n'


# ---------------------------------------------------------------------------
# Hauptprogramm
# ---------------------------------------------------------------------------

# Reihenfolge entspricht dem bisherigen Menü.
REIHENFOLGE = [
    '/', '/home/kontakt', '/home/anfahrt', '/home/gemeindezentrum', '/home/glaskunst-im-gemeindezentrum',
    '/home/suche', '/home/impressum', '/home/datenschutzerklaerung',
    '/aktuelles', '/aktuelles/veranstaltungen', '/medien',
    '/angebote', '/angebote/gottesdienst', '/angebote/gottesdienst/unser-sonntagmorgen',
    '/angebote/gottesdienst/kindergottesdienst', '/angebote/gottesdienst/kleinkindbetreuung',
    '/angebote/kinder', '/angebote/kinder/krabbelmaeuse', '/angebote/kinder/spielekiste',
    '/angebote/kinder/jungschar', '/angebote/kinder/zeltlager', '/angebote/junge-generation',
    '/angebote/biblischer-unterricht', '/angebote/jugend', '/angebote/kreis-junger-erwachsener',
    '/angebote/hauskreise', '/angebote/treffpunkt-bibel', '/angebote/seniorenkreis', '/angebote/gebetstreffen',
    '/gemeinde', '/gemeinde/was-heisst-feg', '/gemeinde/unsere-vision', '/gemeinde/struktur',
    '/gemeinde/pastor-und-leitung', '/gemeinde/kairos-projekt', '/gemeinde/finanzierung',
    '/gemeinde/vernetzung', '/gemeinde/bund-feg',
] + [f'/home/glaskunst-im-gemeindezentrum/{t}-{n}' for t in ('at', 'nt') for n in range(1, 8)]


def bilder_registrieren(export, inhalt):
    for url in re.findall(r'(https://haiger\.feg\.de/images/[^"\'\s<>)]+)', inhalt):
        export.anhang(html.unescape(url))


def main():
    global MEDIEN_DIR
    roh, ziel = sys.argv[1], sys.argv[2]
    if len(sys.argv) > 3:
        MEDIEN_DIR = sys.argv[3]
    seiten = json.load(open(os.path.join(roh, '_pages.json'), encoding='utf-8'))
    export = Export()
    ids = {}

    for reihenfolge, pfad in enumerate(REIHENFOLGE):
        if pfad not in seiten:
            print('fehlt:', pfad, file=sys.stderr)
            continue
        info = seite_lesen(pfad, open(os.path.join(roh, seiten[pfad]), encoding='utf-8').read())

        if pfad == '/':
            inhalt, titel, slug, eltern_pfad = startseite(info), 'Startseite', 'home', None
        elif pfad == '/aktuelles':
            inhalt, titel, slug, eltern_pfad = aktuelles_seite(info), 'Aktuell', 'aktuelles', None
        elif pfad == '/home/suche':
            inhalt, titel, slug, eltern_pfad = suche_seite(info), 'Suche', 'suche', '/'
        else:
            haupt = konvertieren(info['haupt'])
            leiste = seitenleiste_bloecke(info['module'])
            banner = banner_block(*info['banner']) if info['banner'] and info['banner'][0] else None
            inhalt = layout(banner, info['titel'], haupt, leiste)
            titel = info['menue_titel']
            teile = pfad.strip('/').split('/')
            slug = teile[-1]
            eltern_pfad = '/' + '/'.join(teile[:-1]) if len(teile) > 1 else None
            if eltern_pfad == '/home':
                eltern_pfad = '/'

        bilder_registrieren(export, inhalt)
        eltern = ids.get(eltern_pfad, 0) if eltern_pfad else 0
        meta = {'_wp_page_template': ''}
        ids[pfad] = export.eintrag('page', titel, slug, inhalt, eltern, reihenfolge, meta)

    # Beitrag(e) aus „Aktuelles“
    for pfad, datei in seiten.items():
        m = re.match(r'^/aktuelles/\d+-([a-z0-9-]+)$', pfad)
        if not m:
            continue
        info = seite_lesen(pfad, open(os.path.join(roh, datei), encoding='utf-8').read())
        haupt = info['haupt']
        bild_tag = haupt.find('img')
        thumb = None
        if bild_tag is not None:
            thumb = export.anhang(abs_url(bild_tag.get('src')))
        inhalt = '\n\n'.join(konvertieren(haupt))
        bilder_registrieren(export, inhalt)
        export.eintrag('post', info['titel'], m.group(1), inhalt, meta={'_thumbnail_id': thumb} if thumb else {},
                       kategorie=('veranstaltungen', 'Veranstaltungen'),
                       datum=datetime(2026, 10, 1, 12, 0, tzinfo=timezone.utc))

    # Dokumente (PDF) als Mediendateien, damit sie mit umziehen.
    for it in list(export.items):
        for url in re.findall(r'(https://haiger\.feg\.de/images/[^"]+\.pdf)', it.get('inhalt', '')):
            export.anhang(url)

    with open(ziel, 'w', encoding='utf-8') as f:
        f.write(export.xml())
    n = {}
    for it in export.items:
        n[it['typ']] = n.get(it['typ'], 0) + 1
    print('geschrieben:', ziel, n)


if __name__ == '__main__':
    main()
