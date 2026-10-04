/**
 * Plan de feu : le dessin au trait d'une installation, composé à partir de « couches ».
 *
 * Chaque couche correspond à un élément d'un pack (sono, totems, étincelles…).
 * - `couchesDepuisItems(items)` lit les lignes d'un pack (formules.json) et en déduit les couches.
 * - `planDeFeu(couches, options)` renvoie le SVG. Avec `options.complet`, toutes les couches
 *   sont dessinées et masquées par défaut : le composeur de la page d'accueil les allume en CSS.
 *
 * Le projecteur de chaque totem reprend le signe du logo : un boîtier 11:10 et un faisceau
 * de pente 3:8.
 */

const W = 720;
const SOL = 336;
const BAS = 346;
/** Hauteur à laquelle commence chaque couche : le cadre du dessin s'ajuste à la plus haute. */
const SOMMET = {
  ceremonie: 22, mapping: 44, archi: 88, brouillard: 108, "totems-4": 164, "totems-2": 164,
  son: 176, ondes: 176, geysers: 184, etincelles: 210, animateur: 230, micro: 238, pupitre: 246, fumee: 304,
};
const PENTE = 3 / 8;

/** Libellé d'un item de pack -> couche du dessin. L'ordre compte : première règle qui matche. */
const REGLES = [
  [/(\d+)\s+totems?/i, (m) => `totems-${m[1] === "2" ? 2 : 4}`],
  [/c[ée]r[ée]monie/i, () => "ceremonie"],
  [/syst[èe]me son|sono/i, () => "son"],
  [/brouillard/i, () => "brouillard"],
  [/[ée]tincelles/i, () => "etincelles"],
  [/fum[ée]e lourde/i, () => "fumee"],
  [/geysers?/i, () => "geysers"],
  [/animateur/i, () => "animateur"],
  [/pupitre/i, () => "pupitre"],
  [/micro/i, () => "micro"],
  [/[ée]clairage architectural/i, () => "archi"],
  [/musiques? d'ambiance/i, () => "ondes"],
  [/mapping/i, () => "mapping"],
];

const LIBELLES = {
  son: "Sono",
  ondes: "Musique d'ambiance",
  "totems-2": "2 totems et leurs projecteurs",
  "totems-4": "4 totems et leurs projecteurs",
  brouillard: "Brouillard",
  etincelles: "Étincelles froides",
  fumee: "Fumée lourde",
  geysers: "Geysers",
  animateur: "Animateur",
  micro: "Micro sans fil",
  pupitre: "Pupitre",
  ceremonie: "Kit cérémonie",
  archi: "Éclairage architectural",
  mapping: "Vidéo mapping",
};

function couchesDepuisItems(items) {
  const couches = [];
  for (const item of items || []) {
    for (const [re, fn] of REGLES) {
      const m = String(item).match(re);
      if (m) {
        const c = fn(m);
        if (!couches.includes(c)) couches.push(c);
        break;
      }
    }
  }
  return couches;
}

const n = (v) => {
  const s = (Math.round(v * 10) / 10).toString();
  return s === "-0" ? "0" : s;
};
const pts = (list) => list.map(([x, y]) => `${n(x)},${n(y)}`).join(" ");

/** Coupe un polygone au niveau du sol (on ne garde que ce qui est au-dessus). */
function auDessusDuSol(poly) {
  const out = [];
  for (let i = 0; i < poly.length; i++) {
    const a = poly[i];
    const b = poly[(i + 1) % poly.length];
    const aIn = a[1] <= SOL;
    const bIn = b[1] <= SOL;
    if (aIn) out.push(a);
    if (aIn !== bIn) {
      const t = (SOL - a[1]) / (b[1] - a[1]);
      out.push([a[0] + t * (b[0] - a[0]), SOL]);
    }
  }
  return out;
}

/** Faisceau : pointe en (x, y), direction en degrés (0 = vers la droite, 90 = vers le bas). */
function faisceau(x, y, angle, longueur) {
  const a = (angle * Math.PI) / 180;
  const ex = x + longueur * Math.cos(a);
  const ey = y + longueur * Math.sin(a);
  const d = longueur * PENTE;
  const px = -Math.sin(a) * d;
  const py = Math.cos(a) * d;
  return `<polygon class="pf-lum" points="${pts(auDessusDuSol([[x, y], [ex + px, ey + py], [ex - px, ey - py]]))}"/>`;
}

function totem(x, angle) {
  return {
    faisceau: faisceau(x, 182, angle, 250),
    corps:
      `<rect class="pf-plein" x="${n(x - 8)}" y="196" width="16" height="135"/>` +
      `<rect x="${n(x - 18)}" y="331" width="36" height="5"/>` +
      `<path d="M${n(x - 13)} 196H${n(x + 13)}"/>` +
      `<rect class="pf-plein" x="${n(x - 11)}" y="172" width="22" height="20"/>`,
  };
}

function enceinte(x, sens) {
  const o = x + sens * 19;
  return {
    corps:
      `<rect class="pf-plein" x="${n(x - 30)}" y="288" width="60" height="48"/>` +
      `<circle cx="${n(x)}" cy="312" r="15"/>` +
      `<path d="M${n(x)} 288V240"/>` +
      `<rect class="pf-plein" x="${n(x - 19)}" y="184" width="38" height="56"/>` +
      `<circle cx="${n(x)}" cy="220" r="11"/><circle cx="${n(x)}" cy="198" r="4"/>`,
    ondes: [16, 30, 44]
      .map((r) => {
        const y0 = 212 - r * 0.64;
        const y1 = 212 + r * 0.64;
        const dx = sens * r * 0.77;
        return `<path d="M${n(o + dx)} ${n(y0)}A${r} ${r} 0 0 ${sens > 0 ? 1 : 0} ${n(o + dx)} ${n(y1)}"/>`;
      })
      .join(""),
  };
}

function fontaine(x) {
  const angles = [-22, -16.5, -11, -5.5, 0, 5.5, 11, 16.5, 22];
  const longueurs = [54, 72, 88, 100, 108, 100, 88, 72, 54];
  const rayons = angles
    .map((deg, i) => {
      const a = ((deg - 90) * Math.PI) / 180;
      return `<path d="M${n(x)} 326L${n(x + longueurs[i] * Math.cos(a))} ${n(326 + longueurs[i] * Math.sin(a))}"/>`;
    })
    .join("");
  return `<g class="pf-jets">${rayons}</g><rect class="pf-plein" x="${n(x - 8)}" y="326" width="16" height="10"/>`;
}

function geyser(x) {
  return (
    `<path class="pf-lum" d="M${n(x - 4)} 324L${n(x - 13)} 204a13 13 0 0 1 26 0L${n(x + 4)} 324Z"/>` +
    `<rect class="pf-plein" x="${n(x - 7)}" y="324" width="14" height="12"/>`
  );
}

function projecteurSol(x) {
  return {
    faisceau: `<polygon class="pf-lum" points="${pts([[x, 330], [x - 30, 96], [x + 30, 96]])}"/>`,
    corps: `<rect class="pf-plein" x="${n(x - 7)}" y="328" width="14" height="8"/>`,
  };
}

/** Brume : deux lignes ondulées en travers de la scène. */
function brume(y, x0, x1, pas) {
  let d = `M${x0} ${y}`;
  for (let x = x0, i = 0; x < x1; x += pas, i++) d += `q${pas / 2} ${i % 2 ? 7 : -7} ${pas} 0`;
  return `<path d="${d}"/>`;
}

/** Dessins de chaque couche, du fond vers l'avant. */
function dessins() {
  const t4 = [totem(150, 24), totem(240, 50), totem(480, 130), totem(570, 156)];
  const t2 = [totem(200, 34), totem(520, 146)];
  const eg = enceinte(66, 1);
  const ed = enceinte(654, -1);
  const sols = [150, 255, 360, 465, 570].map(projecteurSol);
  const bosses = Array.from({ length: 10 }, () => "a21 13 0 0 1 42 0").join("");

  return [
    ["archi", `<path d="M112 96H608"/>${sols.map((s) => s.faisceau).join("")}${sols.map((s) => s.corps).join("")}`],
    ["mapping",
      `<path class="pf-lum" d="M236 246V96L360 52L484 96V246Z"/>` +
      [258, 312, 382, 436].map((x) => `<rect x="${x}" y="116" width="26" height="34"/><rect x="${x}" y="176" width="26" height="40"/>`).join("") +
      `<path d="M236 96H484M300 246V96M420 246V96"/>`],
    ["brouillard", `<g class="pf-brume">${brume(136, 162, 558, 44)}${brume(160, 118, 602, 44)}</g>`],
    ["totems-4", t4.map((t) => t.faisceau).join("") + t4.map((t) => t.corps).join("")],
    ["totems-2", t2.map((t) => t.faisceau).join("") + t2.map((t) => t.corps).join("")],
    ["son", eg.corps + ed.corps],
    ["ondes", `<g class="pf-ondes">${eg.ondes}${ed.ondes}</g>`],
    ["ceremonie",
      `<path d="M24 100H132M52 100V56a26 26 0 0 1 52 0V100"/>` +
      `<rect class="pf-plein" x="30" y="70" width="12" height="18"/><path d="M36 88V100"/>` +
      `<rect class="pf-plein" x="114" y="70" width="12" height="18"/><path d="M120 88V100"/>` +
      `<circle cx="78" cy="82" r="3.5"/><path d="M78 85.5V100"/>`],
    ["etincelles", fontaine(195) + fontaine(525)],
    ["geysers", geyser(122) + geyser(598)],
    ["pupitre", `<path class="pf-plein" d="M336 268L342 254H378L384 268Z"/><rect class="pf-plein" x="354" y="268" width="12" height="68"/>`],
    ["animateur",
      `<circle class="pf-plein" cx="360" cy="250" r="12"/><path d="M346 249a14 14 0 0 1 28 0"/>` +
      `<path class="pf-plein" d="M334 300V290q0 -24 26 -24t26 24V300Z"/>` +
      `<rect class="pf-plein" x="332" y="288" width="56" height="12"/>` +
      `<rect class="pf-plein" x="306" y="300" width="108" height="36"/>`],
    ["micro", `<path d="M440 336V264"/><rect class="pf-plein" x="435.5" y="246" width="9" height="18" rx="4.5"/>`],
    ["fumee", `<path class="pf-plein" d="M150 336V326${bosses}V336Z"/>`],
  ];
}

/**
 * @param {string[]} couches  couches à dessiner (voir LIBELLES)
 * @param {{complet?: boolean, titre?: string, id?: string}} options
 */
function planDeFeu(couches, options = {}) {
  const actives = new Set(couches || []);
  const complet = Boolean(options.complet);
  const corps = dessins()
    .filter(([nom]) => complet || actives.has(nom))
    .map(([nom, svg]) => `<g data-couche="${nom}"${complet && !actives.has(nom) ? " hidden" : ""}>${svg}</g>`)
    .join("");
  const titre = options.titre
    ? `<title>${String(options.titre).replace(/&/g, "&amp;").replace(/</g, "&lt;")}</title>`
    : "";
  const aria = options.titre ? 'role="img"' : 'aria-hidden="true" focusable="false"';
  const haut = complet ? 14 : Math.max(14, Math.min(...[...actives].map((c) => SOMMET[c] || 176), 246) - 8);
  return (
    `<svg class="pf" viewBox="0 ${haut} ${W} ${BAS - haut}" ${aria}>${titre}` +
    `<g fill="none" stroke="currentColor" stroke-linejoin="miter">${corps}<path d="M24 ${SOL}H696"/></g></svg>`
  );
}

module.exports = { planDeFeu, couchesDepuisItems, LIBELLES };
