"""
Event'Light — géométrie maîtresse du logo, reconstruite d'après le PNG 1600 px d'origine.

Repère : plan de travail 1600 x 1600, y vers le bas. Les lettres gardent leur hauteur
hors-tout (le trait épaissit vers l'intérieur) ; le signe (rectangle + faisceau) est tracé
sur son axe (le trait épaissit de part et d'autre).

Règles relevées sur l'original puis régularisées :
  - rectangle 11:10, pointe du faisceau au centre du rectangle ;
  - pente du faisceau 3:8 (ouverture 41,1°), base verticale ;
  - la base du faisceau va de la ligne de pied de EVENT à la ligne de tête de LIGHT ;
  - coupes des lettres : 24 (barres décalées de E, T, L), 14 (barres du 2e E),
    17,5 (fûts du H), réserve de 15 autour des diagonales (V, N), ouverture du G de ±60 ;
  - le G est une superellipse (exposant 2,11) : courbes de Bézier de coefficient 0,587.
"""
import math
from shapely.geometry import LineString, box, LinearRing
from shapely.ops import unary_union

CAP = 294.0                 # hauteur de capitale hors-tout
EV_TOP, EV_BOT = 12.0, 306.0
LI_TOP, LI_BOT = 1270.0, 1564.0
AXIS_Y = (EV_BOT + LI_TOP) / 2.0      # 788 : axe du faisceau
CX = 797.5                  # axe vertical de la composition

BEAM_SLOPE = 3.0 / 8.0
GAP_BAR = 24.0     # barre détachée du fût (E, T, L)
GAP_E2 = 14.0      # barres haute et basse du second E
CUT_H = 17.5       # fûts raccourcis du H
GAP_DIAG = 15.0    # réserve autour des diagonales (V, N)
G_OPEN = 60.0      # demi-ouverture du G
OVERSHOOT = 2.75   # dépassement optique du G
KAPPA = 0.587      # tension des courbes du G (0,5523 = ellipse)


def fmt(v):
    s = ("%.2f" % v).rstrip("0").rstrip(".")
    return "0" if s in ("-0", "") else s


# ------------------------------------------------------------------ signe

def mark_geometry(stroke, cx=CX, axis_y=AXIS_Y, base_out=LI_TOP - EV_BOT):
    """Axes du signe. `base_out` = hauteur hors-tout de la base du faisceau."""
    cosphi = 1.0 / math.sqrt(1 + BEAM_SLOPE ** 2)
    corner_out = (stroke / 2.0) * BEAM_SLOPE + (stroke / 2.0) / cosphi
    half_base = base_out / 2.0 - corner_out
    s = half_base / 54.0
    rect_w, rect_h, length = 55 * s, 50 * s, 144 * s
    total = rect_w / 2.0 + length
    left = cx - total / 2.0
    apex_x = left + rect_w / 2.0
    return {
        "s": s,
        "rect": (left, axis_y - rect_h / 2.0, left + rect_w, axis_y + rect_h / 2.0),
        "apex": (apex_x, axis_y),
        "top": (apex_x + length, axis_y - half_base),
        "bottom": (apex_x + length, axis_y + half_base),
    }


def ring(points, stroke):
    return LinearRing(points).buffer(stroke / 2.0, join_style="mitre", mitre_limit=10)


def mark_polys(stroke, **kw):
    g = mark_geometry(stroke, **kw)
    x0, y0, x1, y1 = g["rect"]
    rect = ring([(x0, y0), (x1, y0), (x1, y1), (x0, y1)], stroke)
    tri = ring([g["apex"], g["top"], g["bottom"]], stroke)
    return {"rect": rect, "beam": tri}, g


# ------------------------------------------------------------------ lettres

def seg(p, q, stroke):
    return LineString([p, q]).buffer(stroke / 2.0, cap_style="flat", join_style="mitre", mitre_limit=10)


def poly(points, stroke):
    return LineString(points).buffer(stroke / 2.0, cap_style="flat", join_style="mitre", mitre_limit=10)


def extended(p, q, d=80.0):
    dx, dy = q[0] - p[0], q[1] - p[1]
    n = math.hypot(dx, dy)
    ux, uy = dx / n, dy / n
    return LineString([(p[0] - ux * d, p[1] - uy * d), (q[0] + ux * d, q[1] + uy * d)])


def letters_event(stroke, dx=0.0, top=EV_TOP):
    S = stroke
    bot = top + CAP
    yT, yB, yM = top + S / 2, bot - S / 2, (top + bot) / 2
    out = {}

    # E (1) : fût + barres haute et basse liées, barre médiane détachée
    x = 95.5 + dx
    e1 = poly([(x + 139.5, yT), (x, yT), (x, yB), (x + 139.5, yB)], S)
    e1_mid = seg((x + S / 2 + GAP_BAR, yM), (x + 126.75, yM), S)
    out["E1"] = unary_union([e1, e1_mid])

    # V : jambe gauche entière, jambe droite arrêtée avant la pointe
    vx = 468.25 + dx
    band = box(vx - 400, top, vx + 400, bot)
    left_axis = extended((vx - 111.75, top), (vx, bot))
    right_axis = extended((vx + 111.75, top), (vx, bot))
    left = left_axis.buffer(S / 2, cap_style="flat").intersection(band)
    right = right_axis.buffer(S / 2, cap_style="flat").intersection(band)
    right = right.difference(left_axis.buffer(S / 2 + GAP_DIAG, cap_style="flat"))
    if right.geom_type == "MultiPolygon":
        right = min(right.geoms, key=lambda p: p.bounds[1])
    out["V"] = unary_union([left, right])

    # E (2) : fût + barre médiane liée, barres haute et basse détachées
    x = 719.25 + dx
    stem = seg((x, top), (x, bot), S)
    mid = seg((x, yM), (x + 126.75, yM), S)
    tb = seg((x + S / 2 + GAP_E2, yT), (x + 139.5, yT), S)
    bb = seg((x + S / 2 + GAP_E2, yB), (x + 139.5, yB), S)
    out["E2"] = unary_union([stem, mid, tb, bb])

    # N : diagonale entière (d'axe de fût à axe de fût), fûts arrêtés avant elle
    xl, xr = 1002.5 + dx, 1193.0 + dx
    cell = box(xl - S / 2, top, xr + S / 2, bot)
    diag_axis = extended((xl, top), (xr, bot))
    diag = diag_axis.buffer(S / 2, cap_style="flat").intersection(cell)
    reserve = diag_axis.buffer(S / 2 + GAP_DIAG, cap_style="flat")
    lstem = seg((xl, top), (xl, bot), S).difference(reserve)
    rstem = seg((xr, top), (xr, bot), S).difference(reserve)
    if lstem.geom_type == "MultiPolygon":
        lstem = max(lstem.geoms, key=lambda g_: g_.bounds[3])
    if rstem.geom_type == "MultiPolygon":
        rstem = min(rstem.geoms, key=lambda g_: g_.bounds[1])
    out["N"] = unary_union([diag, lstem, rstem])

    # T : barre droite liée au fût (coin d'équerre), barre gauche détachée
    x = 1413.75 + dx
    half = 89.75
    t_main = unary_union([seg((x, top), (x, bot), S), seg((x - S / 2, yT), (x + half, yT), S)])
    t_left = seg((x - half, yT), (x - S / 2 - GAP_BAR, yT), S)
    out["T1"] = unary_union([t_main, t_left])
    return out


def letters_light(stroke, dx=0.0, top=LI_TOP):
    S = stroke
    bot = top + CAP
    yT, yB, yM = top + S / 2, bot - S / 2, (top + bot) / 2
    out = {}

    # L : fût arrêté, pied détaché posé sur la ligne de pied
    x = 136.5 + dx
    l_stem = seg((x, top), (x, bot - S - GAP_BAR), S)
    l_foot = seg((x - S / 2, yB), (x + 116.75, yB), S)
    out["L"] = unary_union([l_stem, l_foot])

    # I
    x = 384.5 + dx
    out["I"] = seg((x, top), (x, bot), S)

    # H : fût gauche arrêté en bas, fût droit arrêté en haut, traverse entière
    xl, xr = 960.25 + dx, 1151.75 + dx
    ybar = yM - 4.0
    h_l = seg((xl, top), (xl, bot - CUT_H), S)
    h_r = seg((xr, top + CUT_H), (xr, bot), S)
    h_bar = seg((xl - S / 2, ybar), (xr + S / 2, ybar), S)
    out["H"] = unary_union([h_l, h_r, h_bar])

    # T : barre entière, fût détaché
    x = 1373.0 + dx
    half = 89.75
    t_bar = seg((x - half, yT), (x + half, yT), S)
    t_stem = seg((x, top + S + GAP_BAR), (x, bot), S)
    out["T2"] = unary_union([t_bar, t_stem])
    return out


# ------------------------------------------------------------------ G (courbes)

def _quad(cx, cy, a, b, q):
    """Quart de superellipse en Bézier cubique. q : 0 = haut->droite, 1 = droite->bas,
    2 = bas->gauche, 3 = gauche->haut (sens horaire à l'écran)."""
    k = KAPPA
    T, R, B, Lf = (cx, cy - b), (cx + a, cy), (cx, cy + b), (cx - a, cy)
    if q == 0:
        return [T, (cx + k * a, cy - b), (cx + a, cy - k * b), R]
    if q == 1:
        return [R, (cx + a, cy + k * b), (cx + k * a, cy + b), B]
    if q == 2:
        return [B, (cx - k * a, cy + b), (cx - a, cy + k * b), Lf]
    return [Lf, (cx - a, cy - k * b), (cx - k * a, cy - b), T]


def _bez(p, t):
    mt = 1 - t
    return tuple(mt ** 3 * p[0][i] + 3 * mt * mt * t * p[1][i] + 3 * mt * t * t * p[2][i] + t ** 3 * p[3][i] for i in (0, 1))


def _split(p, t):
    """de Casteljau : renvoie (première partie, seconde partie)."""
    def lerp(a, b):
        return (a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t)
    p01, p12, p23 = lerp(p[0], p[1]), lerp(p[1], p[2]), lerp(p[2], p[3])
    p012, p123 = lerp(p01, p12), lerp(p12, p23)
    m = lerp(p012, p123)
    return [p[0], p01, p012, m], [m, p123, p23, p[3]]


def _t_at_y(p, y):
    lo, hi = 0.0, 1.0
    inc = p[3][1] > p[0][1]
    for _ in range(60):
        mid = (lo + hi) / 2
        v = _bez(p, mid)[1]
        if (v < y) == inc:
            lo = mid
        else:
            hi = mid
    return (lo + hi) / 2


def g_params(stroke, dx=0.0, top=LI_TOP):
    S = stroke
    cx, cy = 673.3 + dx, top + CAP / 2
    bO = CAP / 2 + OVERSHOOT          # 149,75
    aO = 140.4                        # demi-largeur hors-tout relevée
    return cx, cy, aO, bO, aO - S, bO - S


def g_curves(stroke, dx=0.0, top=LI_TOP):
    """Contour du G : liste de sous-tracés, chacun = liste de segments ('L', pt) ou ('C', c1, c2, pt)."""
    S = stroke
    cx, cy, aO, bO, aI, bI = g_params(S, dx, top)
    yu, yl = cy - G_OPEN, cy + G_OPEN

    def arc(a, b):
        # du terminal haut (à droite) au terminal bas en passant par le haut, la gauche, le bas
        q0 = _quad(cx, cy, a, b, 0)      # haut -> droite
        q1 = _quad(cx, cy, a, b, 1)      # droite -> bas
        q2 = _quad(cx, cy, a, b, 2)
        q3 = _quad(cx, cy, a, b, 3)
        t0 = _t_at_y(q0, yu)
        upper, _ = _split(q0, t0)        # haut -> terminal haut
        t1 = _t_at_y(q1, yl)
        _, lower = _split(q1, t1)        # terminal bas -> bas
        return upper, lower, q2, q3

    uo, lo, q2o, q3o = arc(aO, bO)
    ui, li, q2i, q3i = arc(aI, bI)
    rev = lambda c: [c[3], c[2], c[1], c[0]]
    path = [("M", uo[3])]
    # extérieur, sens anti-horaire : terminal haut -> haut -> gauche -> bas -> terminal bas
    for c in (rev(uo), rev(q3o), rev(q2o), rev(lo)):
        path.append(("C", c[1], c[2], c[3]))
    path.append(("L", li[0]))
    # intérieur, sens horaire : terminal bas -> bas -> gauche -> haut -> terminal haut
    for c in (li, q2i, q3i, ui):
        path.append(("C", c[1], c[2], c[3]))
    path.append(("Z",))
    bx0, bx1 = cx - 6.5, cx + aO - 0.75
    bar = [("M", (bx0, cy - S / 2)), ("L", (bx1, cy - S / 2)), ("L", (bx1, cy + S / 2)), ("L", (bx0, cy + S / 2)), ("Z",)]
    return [path, bar]


def curves_to_path(subpaths, ox=0.0, oy=0.0, k=1.0):
    d = []
    f = lambda p: "%s %s" % (fmt((p[0] + ox) * k), fmt((p[1] + oy) * k))
    for sp in subpaths:
        for cmd in sp:
            if cmd[0] == "M":
                d.append("M" + f(cmd[1]))
            elif cmd[0] == "L":
                d.append("L" + f(cmd[1]))
            elif cmd[0] == "C":
                d.append("C" + f(cmd[1]) + " " + f(cmd[2]) + " " + f(cmd[3]))
            else:
                d.append("Z")
    return "".join(d)


def g_polygon(stroke, dx=0.0, top=LI_TOP, n=48):
    """G échantillonné en polygones (contrôles par superposition)."""
    from shapely.geometry import Polygon
    polys = []
    for sp in g_curves(stroke, dx, top):
        pts, cur = [], None
        for cmd in sp:
            if cmd[0] in ("M", "L"):
                cur = cmd[1]
                pts.append(cur)
            elif cmd[0] == "C":
                c = [cur, cmd[1], cmd[2], cmd[3]]
                pts.extend(_bez(c, i / n) for i in range(1, n + 1))
                cur = cmd[3]
        polys.append(Polygon(pts))
    return unary_union(polys)


# ------------------------------------------------------------------ sorties SVG

def simplify_collinear(pts, eps=1e-6):
    out = []
    n = len(pts)
    for i in range(n):
        a, b, c = pts[i - 1], pts[i], pts[(i + 1) % n]
        cross = (b[0] - a[0]) * (c[1] - b[1]) - (b[1] - a[1]) * (c[0] - b[0])
        if abs(cross) > eps:
            out.append(b)
    return out


def poly_to_path(geom, ox=0.0, oy=0.0, k=1.0):
    """Polygone(s) shapely -> donnée de tracé SVG (segments droits)."""
    if geom is None or geom.is_empty:
        return ""
    geoms = list(geom.geoms) if geom.geom_type in ("MultiPolygon", "GeometryCollection") else [geom]
    geoms = sorted([g_ for g_ in geoms if g_.geom_type == "Polygon"], key=lambda g_: (round(g_.bounds[0], 1), round(g_.bounds[1], 1)))
    d = []
    for g_ in geoms:
        for r in [g_.exterior] + list(g_.interiors):
            pts = simplify_collinear(list(r.coords)[:-1])
            d.append("M" + "L".join("%s %s" % (fmt((x + ox) * k), fmt((y + oy) * k)) for x, y in pts) + "Z")
    return "".join(d)
