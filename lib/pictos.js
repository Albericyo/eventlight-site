/**
 * Pictogrammes au trait, dessinés avec la même épaisseur que le logo.
 * Servent de visuel aux catégories et aux produits sans photo.
 */
const n = (v) => (Math.round(v * 10) / 10).toString();

function rayons(x, y, angles, longueurs) {
  return angles
    .map((deg, i) => {
      const a = ((deg - 90) * Math.PI) / 180;
      return `<path d="M${x} ${y}L${n(x + longueurs[i] * Math.cos(a))} ${n(y + longueurs[i] * Math.sin(a))}"/>`;
    })
    .join("");
}

const portique =
  `<rect class="pf-plein" x="14" y="24" width="92" height="12"/>` +
  `<path d="M14 36L25.5 24L37 36L48.5 24L60 36L71.5 24L83 36L94.5 24L106 36"/>` +
  `<path d="M22 36V108M98 36V108M22 108L10 112M22 108L34 112M98 108L86 112M98 108L110 112M22 84L13 108M22 84L31 108M98 84L89 108M98 84L107 108"/>`;

const DESSINS = {
  // catégories
  "projecteurs-robotises":
    `<rect class="pf-plein" x="40" y="96" width="40" height="12"/><path d="M44 96V60M76 96V60"/>` +
    `<polygon class="pf-lum" points="60,56 106.6,39.7 91.2,5.9"/>` +
    `<rect class="pf-plein" x="46.2" y="43.5" width="27.5" height="25" transform="rotate(-35 60 56)"/>`,
  "projecteurs-statiques":
    `<path d="M26 58V98H94V58M18 98H102"/><circle class="pf-plein" cx="60" cy="58" r="32"/>` +
    `<circle cx="60" cy="58" r="5.5"/>` +
    [0, 60, 120, 180, 240, 300]
      .map((d) => `<circle cx="${n(60 + 17 * Math.cos((d * Math.PI) / 180))}" cy="${n(58 + 17 * Math.sin((d * Math.PI) / 180))}" r="5.5"/>`)
      .join(""),
  son:
    `<rect class="pf-plein" x="36" y="12" width="48" height="72"/><circle cx="60" cy="58" r="17"/><circle cx="60" cy="27" r="7"/>` +
    `<path d="M60 84V108M44 108H76"/>`,
  effets:
    `<g class="pf-jets">${rayons(60, 98, [-27, -18, -9, 0, 9, 18, 27], [56, 70, 80, 86, 80, 70, 56])}</g>` +
    `<rect class="pf-plein" x="50" y="98" width="20" height="10"/><path d="M36 108H84"/>`,
  structures: portique,
  // produits sans photo
  "pack-structure-f24200": portique,
  "global-truss-4-points":
    `<rect class="pf-plein" x="8" y="44" width="104" height="32"/>` +
    `<path d="M8 76L21 44L34 76L47 44L60 76L73 44L86 76L99 44L112 76"/><path d="M4 40V80M116 40V80"/>`,
  "totem-4-points":
    `<rect class="pf-plein" x="48" y="24" width="24" height="80"/>` +
    `<path d="M48 104L72 88L48 72L72 56L48 40L72 24"/>` +
    `<rect class="pf-plein" x="34" y="104" width="52" height="6"/><rect class="pf-plein" x="42" y="18" width="36" height="6"/>`,
  "plugger-monotube":
    `<rect class="pf-plein" x="40" y="104" width="40" height="6"/>` +
    `<rect class="pf-plein" x="56" y="50" width="8" height="54"/><path d="M60 50V14M55 14H65"/>` +
    `<rect class="pf-plein" x="53" y="46" width="14" height="8"/>`,
};

function picto(nom, titre) {
  const corps = DESSINS[nom];
  if (!corps) return "";
  const t = titre ? `<title>${String(titre).replace(/&/g, "&amp;").replace(/</g, "&lt;")}</title>` : "";
  const aria = titre ? 'role="img"' : 'aria-hidden="true" focusable="false"';
  return `<svg class="pf picto" viewBox="0 0 120 120" ${aria}>${t}<g fill="none" stroke="currentColor" stroke-linejoin="miter">${corps}</g></svg>`;
}

module.exports = { picto, a: (nom) => Boolean(DESSINS[nom]) };
