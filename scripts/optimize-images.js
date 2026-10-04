/**
 * Prépare les images du site.
 *
 *   npm run images
 *
 * Lit les originaux de src/images/{portfolio,location}/<slug>/, écrit des WebP
 * redimensionnés dans src/img/ et tient à jour src/_data/media.json (dimensions,
 * tailles disponibles, type de fond). Seules les images nouvelles ou modifiées
 * sont recalculées. Les originaux ne sont pas publiés.
 */
const fs = require("fs");
const path = require("path");
const sharp = require("sharp");

const ROOT = path.resolve(__dirname, "..");
const SRC = path.join(ROOT, "src", "images");
const OUT = path.join(ROOT, "src", "img");
const DATA = path.join(ROOT, "src", "_data", "media.json");

const PROFILS = {
  portfolio: { largeurs: [480, 960, 1600], qualite: 80 },
  location: { largeurs: [360, 720], qualite: 84 },
};

const estImage = (f) => /\.(jpe?g|png|webp)$/i.test(f);

/** Fond clair, sombre ou quelconque : moyenne des pixels du pourtour. */
async function typeDeFond(file) {
  const n = 24;
  const { data, info } = await sharp(file)
    .resize(n, n, { fit: "fill" })
    .ensureAlpha()
    .raw()
    .toBuffer({ resolveWithObject: true });
  let lum = 0;
  let alpha = 0;
  let count = 0;
  for (let y = 0; y < n; y++) {
    for (let x = 0; x < n; x++) {
      if (x > 0 && x < n - 1 && y > 0 && y < n - 1) continue;
      const i = (y * n + x) * info.channels;
      const a = data[i + 3] / 255;
      lum += (0.2126 * data[i] + 0.7152 * data[i + 1] + 0.0722 * data[i + 2]) / 255;
      alpha += a;
      count++;
    }
  }
  lum /= count;
  alpha /= count;
  if (alpha < 0.5) return "transparent";
  if (lum > 0.86) return "clair";
  if (lum < 0.16) return "sombre";
  return "image";
}

async function traiter(kind, slug, file, profil) {
  const src = path.join(SRC, kind, slug, file);
  const nom = path.parse(file).name;
  const meta = await sharp(src).metadata();
  const tournee = [5, 6, 7, 8].includes(meta.orientation);
  const w = tournee ? meta.height : meta.width;
  const h = tournee ? meta.width : meta.height;

  let largeurs = profil.largeurs.filter((l) => l <= w);
  if (!largeurs.length || (w < profil.largeurs[profil.largeurs.length - 1] && !largeurs.includes(w) && w > largeurs[largeurs.length - 1] * 1.2)) {
    largeurs.push(w);
  }
  largeurs = [...new Set(largeurs)].sort((a, b) => a - b);

  const dir = path.join(OUT, kind, slug);
  fs.mkdirSync(dir, { recursive: true });
  const srcTime = fs.statSync(src).mtimeMs;
  let ecrits = 0;
  for (const l of largeurs) {
    const out = path.join(dir, `${nom}-${l}.webp`);
    if (fs.existsSync(out) && fs.statSync(out).mtimeMs >= srcTime) continue;
    await sharp(src)
      .rotate()
      .resize({ width: l, withoutEnlargement: true })
      .webp({ quality: profil.qualite, effort: 5 })
      .toFile(out);
    ecrits++;
  }
  return {
    entree: {
      base: `/img/${kind}/${slug}/${nom}`,
      largeurs,
      w,
      h,
      fond: await typeDeFond(src),
    },
    ecrits,
  };
}

async function main() {
  const media = {};
  let total = 0;
  let ecrits = 0;
  for (const [kind, profil] of Object.entries(PROFILS)) {
    media[kind] = {};
    const base = path.join(SRC, kind);
    if (!fs.existsSync(base)) continue;
    for (const slug of fs.readdirSync(base).sort()) {
      const dir = path.join(base, slug);
      if (!fs.statSync(dir).isDirectory()) continue;
      const files = fs.readdirSync(dir).filter(estImage).sort();
      const liste = [];
      for (const file of files) {
        const r = await traiter(kind, slug, file, profil);
        liste.push(r.entree);
        ecrits += r.ecrits;
        total++;
      }
      if (liste.length) media[kind][slug] = liste;
    }
  }

  // Nettoyage : retirer les dérivés dont l'original a disparu.
  const attendus = new Set();
  for (const kind of Object.keys(media)) {
    for (const liste of Object.values(media[kind])) {
      for (const e of liste) for (const l of e.largeurs) attendus.add(path.join(ROOT, "src", `${e.base}-${l}.webp`));
    }
  }
  let supprimes = 0;
  const parcourir = (dir) => {
    if (!fs.existsSync(dir)) return;
    for (const f of fs.readdirSync(dir)) {
      const p = path.join(dir, f);
      if (fs.statSync(p).isDirectory()) parcourir(p);
      else if (p.endsWith(".webp") && !attendus.has(p)) {
        fs.unlinkSync(p);
        supprimes++;
      }
    }
  };
  for (const kind of Object.keys(PROFILS)) parcourir(path.join(OUT, kind));

  fs.writeFileSync(DATA, JSON.stringify(media, null, 2) + "\n");
  console.log(`${total} images suivies, ${ecrits} fichiers écrits, ${supprimes} supprimés → src/img + src/_data/media.json`);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
