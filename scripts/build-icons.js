/**
 * Fabrique les icônes et l'image de partage à partir du signe.
 *
 *   npm run icons
 *
 * Sorties (src/assets/logo/) : favicon.svg, favicon-48.png, apple-touch-icon.png,
 * icon-192.png, icon-512.png, og-default.jpg.
 */
const fs = require("fs");
const path = require("path");
const sharp = require("sharp");

const ROOT = path.resolve(__dirname, "..");
const OUT = path.join(ROOT, "src", "assets", "logo");
const MUR = "#d4d7d9";

// Le signe sur sa grille : boîtier 110 x 100, faisceau de pente 3:8.
const signe = (trait, couleur, lueur) =>
  `<g fill="none" stroke="${couleur}" stroke-width="${trait}" stroke-linejoin="miter" stroke-miterlimit="4">` +
  (lueur ? `<polygon points="55,108 343,0 343,216" fill="${lueur}" stroke="none"/>` : "") +
  `<rect x="0" y="58" width="110" height="100"/><polygon points="55,108 343,0 343,216"/></g>`;

// Favicon : le trait suit le thème de l'onglet.
const favicon =
  `<svg xmlns="http://www.w3.org/2000/svg" viewBox="-26 -89.5 395 395">` +
  `<style>g{stroke:#000}@media (prefers-color-scheme:dark){g{stroke:#fff}}</style>` +
  signe(26, "#000", null) +
  `</svg>\n`;

const tuile = (taille, marge, trait) => {
  const cote = 343 + marge * 2;
  const dy = (cote - 216) / 2;
  return Buffer.from(
    `<svg xmlns="http://www.w3.org/2000/svg" width="${taille}" height="${taille}" viewBox="${-marge} ${-dy} ${cote} ${cote}">` +
      `<rect x="${-marge}" y="${-dy}" width="${cote}" height="${cote}" fill="${MUR}"/>` +
      signe(trait, "#000", "#ffffff") +
      `</svg>`
  );
};

async function partage() {
  // 1200 x 630 : le signe en grand, une photo dans le faisceau, le logo en ligne dessous.
  const W = 1200;
  const H = 630;
  const k = 2.6; // échelle du signe
  const ox = 90;
  const oy = 34;
  const photo = path.join(ROOT, "src", "images", "portfolio", "arbre-de-noel-pompier-2023", "03.jpg");
  const bw = Math.round(288 * k);
  const bh = Math.round(216 * k);
  const masque = Buffer.from(
    `<svg xmlns="http://www.w3.org/2000/svg" width="${bw}" height="${bh}"><polygon points="0,${bh / 2} ${bw},0 ${bw},${bh}" fill="#fff"/></svg>`
  );
  const dansLeFaisceau = await sharp(photo)
    .resize(bw, bh, { fit: "cover", position: "centre" })
    .composite([{ input: masque, blend: "dest-in" }])
    .png()
    .toBuffer();

  const ligne = fs
    .readFileSync(path.join(OUT, "eventlight-ligne-fort-noir.svg"), "utf8")
    .replace(/<svg[^>]*>/, "")
    .replace("</svg>", "")
    .replace(/<title>.*?<\/title>/, "");
  const calque = Buffer.from(
    `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">` +
      `<g transform="translate(${ox} ${oy}) scale(${k})">${signe(1.5 / k * 2, "#000", null)}</g>` +
      `<g transform="translate(${ox} ${H - 62}) scale(${(0.115).toFixed(4)})" fill="#000" fill-rule="evenodd">${ligne}</g>` +
      `</svg>`
  );
  await sharp({ create: { width: W, height: H, channels: 3, background: MUR } })
    .composite([
      { input: dansLeFaisceau, left: Math.round(ox + 55 * k), top: oy },
      { input: calque, left: 0, top: 0 },
    ])
    .jpeg({ quality: 86, mozjpeg: true })
    .toFile(path.join(OUT, "og-default.jpg"));
}

async function main() {
  fs.mkdirSync(OUT, { recursive: true });
  fs.writeFileSync(path.join(OUT, "favicon.svg"), favicon);
  await sharp(tuile(48, 46, 30)).png().toFile(path.join(OUT, "favicon-48.png"));
  await sharp(tuile(180, 70, 22)).png().toFile(path.join(OUT, "apple-touch-icon.png"));
  await sharp(tuile(192, 70, 22)).png().toFile(path.join(OUT, "icon-192.png"));
  await sharp(tuile(512, 70, 20)).png().toFile(path.join(OUT, "icon-512.png"));
  await partage();
  console.log("icônes et image de partage écrites dans src/assets/logo/");
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
