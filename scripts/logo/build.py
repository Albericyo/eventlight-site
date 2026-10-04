#!/usr/bin/env python3
"""
Génère tous les fichiers du logo Event'Light à partir de la géométrie maîtresse.

    python3 scripts/logo/build.py        (dépendance : pip install shapely)

Sorties :
  src/assets/logo/*.svg      fichiers autonomes (noir, à télécharger ou à envoyer à un imprimeur)
  src/_includes/logo/*.svg   versions à inclure dans les gabarits (couleur héritée : currentColor)
  src/_data/logo.json        cotes utiles aux gabarits (proportions du signe)
"""
import json
import os
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(HERE, "..", ".."))
sys.path.insert(0, HERE)
import logo_geom as L  # noqa: E402
from shapely.geometry import Polygon  # noqa: E402

# Trois graisses. La valeur est l'épaisseur du trait pour une hauteur de capitale de 294.
GRAISSES = {"fin": 8.0, "courant": 16.0, "fort": 30.0}

OUT_FILES = os.path.join(ROOT, "src", "assets", "logo")
OUT_INC = os.path.join(ROOT, "src", "_includes", "logo")
OUT_DATA = os.path.join(ROOT, "src", "_data", "logo.json")


def word(S, which, ox, oy):
    """Mot EVENT ou LIGHT, coin haut-gauche hors-tout placé en (ox, oy)."""
    if which == "event":
        g = L.letters_event(S, top=0.0)
        x0 = 95.5 - S / 2
        d = "".join(L.poly_to_path(g[n], ox - x0, oy) for n in ("E1", "V", "E2", "N", "T1"))
        width = (1413.75 + 89.75) - x0
    else:
        g = L.letters_light(S, top=0.0)
        x0 = 136.5 - S / 2
        d = "".join(L.poly_to_path(g[n], ox - x0, oy) for n in ("L", "I"))
        d += L.curves_to_path(L.g_curves(S, top=0.0), ox - x0, oy)
        d += "".join(L.poly_to_path(g[n], ox - x0, oy) for n in ("H", "T2"))
        width = (1373.0 + 89.75) - x0
    return d, width


def mark(S, left, axis_y, base_out):
    """Signe : bord gauche hors-tout en `left`, axe horizontal en axis_y, base hors-tout base_out."""
    g = L.mark_geometry(S, cx=0.0, axis_y=axis_y, base_out=base_out)
    shift = left + S / 2 - g["rect"][0]
    polys, g = L.mark_polys(S, cx=shift, axis_y=axis_y, base_out=base_out)
    glow = Polygon([g["apex"], g["top"], g["bottom"]])
    return {
        "boite": L.poly_to_path(polys["rect"]),
        "faisceau": L.poly_to_path(polys["beam"]),
        "lueur": L.poly_to_path(glow),
        "right": g["top"][0] + S / 2,
        "geom": g,
    }


def empile(S):
    """Logo principal : EVENT / signe / LIGHT, composition d'origine recentrée."""
    cap = L.CAP
    base = L.LI_TOP - L.EV_BOT            # 964 : la base du faisceau relie les deux mots
    m = mark(S, 0.0, cap + base / 2, base)
    width = m["right"]
    ev_w = (1413.75 + 89.75) - (95.5 - S / 2)
    li_w = (1373.0 + 89.75) - (136.5 - S / 2)
    e, _ = word(S, "event", (width - ev_w) / 2, 0.0)
    l, _ = word(S, "light", (width - li_w) / 2, cap + base)
    return {"w": width, "h": cap * 2 + base, "mot": e + l, **m}


def ligne(S, ratio=1.5, gap=0.62):
    """Logo en ligne : EVENT [signe] LIGHT. Le signe fait `ratio` fois la hauteur de capitale."""
    cap = L.CAP
    base = cap * ratio
    y0 = (base - cap) / 2
    e, we = word(S, "event", 0.0, y0)
    g = cap * gap
    m = mark(S, we + g, base / 2, base)
    l, wl = word(S, "light", m["right"] + g, y0)
    return {"w": m["right"] + g + wl, "h": base, "mot": e + l, **m}


def signe(S, base=L.LI_TOP - L.EV_BOT):
    """Signe seul."""
    m = mark(S, 0.0, base / 2, base)
    return {"w": m["right"], "h": base, "mot": "", **m}


def svg(parts, standalone, title=None, color="#000"):
    w, h = parts["w"], parts["h"]
    vb = "0 0 %s %s" % (L.fmt(w), L.fmt(h))
    body = ""
    if not standalone:
        body += '<path class="el-lueur" d="%s"/>' % parts["lueur"]
    if parts["mot"]:
        body += '<path class="el-mot" d="%s"/>' % parts["mot"]
    body += '<path class="el-boite" d="%s"/>' % parts["boite"]
    body += '<path class="el-faisceau" d="%s"/>' % parts["faisceau"]
    if standalone:
        body = body.replace(' class="el-mot"', "").replace(' class="el-boite"', "").replace(' class="el-faisceau"', "")
        t = "<title>%s</title>" % title if title else ""
        return ('<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s" width="%s" height="%s" fill="%s" fill-rule="evenodd">%s%s</svg>\n'
                % (vb, L.fmt(w), L.fmt(h), color, t, body))
    return '<svg class="el-logo" viewBox="%s" fill="currentColor" fill-rule="evenodd" aria-hidden="true" focusable="false">%s</svg>' % (vb, body)


def write(path, text):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w", encoding="utf-8") as f:
        f.write(text)
    return os.path.getsize(path)


def main():
    made = []
    for nom, S in GRAISSES.items():
        for forme, fn in (("logo", empile), ("ligne", ligne), ("signe", signe)):
            p = fn(S)
            label = {"logo": "Event'Light", "ligne": "Event'Light", "signe": "Event'Light, signe"}[forme]
            for teinte, color in (("noir", "#000"), ("blanc", "#fff")):
                f = os.path.join(OUT_FILES, "eventlight-%s-%s-%s.svg" % (forme, nom, teinte))
                made.append((f, write(f, svg(p, True, label, color))))
            f = os.path.join(OUT_INC, "%s-%s.svg" % (forme, nom))
            made.append((f, write(f, svg(p, False))))

    # Signe en trait vif : l'épaisseur se règle en CSS et ne change pas avec la taille.
    trait = ('<svg class="el-signe" viewBox="0 0 343 216" fill="none" stroke="currentColor" aria-hidden="true" focusable="false">'
             '<polygon class="el-lueur" points="55,108 343,0 343,216" stroke="none"/>'
             '<rect class="el-boite" x="0" y="58" width="110" height="100" vector-effect="non-scaling-stroke"/>'
             '<polygon class="el-faisceau" points="55,108 343,0 343,216" stroke-linejoin="miter" stroke-miterlimit="4" vector-effect="non-scaling-stroke"/>'
             '</svg>')
    made.append((os.path.join(OUT_INC, "signe-trait.svg"), write(os.path.join(OUT_INC, "signe-trait.svg"), trait)))

    g = L.mark_geometry(8.0)
    data = {
        "graisses": GRAISSES,
        "signe": {"largeur": 343, "hauteur": 216, "boite": [0, 58, 110, 100], "pointe": [55, 108], "pente": "3:8", "ouverture": 41.1},
        "empile": {"largeur": round(empile(8.0)["w"], 2), "hauteur": round(empile(8.0)["h"], 2)},
        "ligne": {"largeur": round(ligne(8.0)["w"], 2), "hauteur": round(ligne(8.0)["h"], 2)},
        "unite": round(g["s"], 4),
    }
    write(OUT_DATA, json.dumps(data, ensure_ascii=False, indent=2) + "\n")
    for f, size in made:
        print("%6d  %s" % (size, os.path.relpath(f, ROOT)))
    print("       src/_data/logo.json")


if __name__ == "__main__":
    main()
