/**
 * Assemble le thème WordPress et en fait un fichier .zip prêt à installer.
 *
 *   npm run wordpress
 *
 * Le code du thème est dans wordpress/eventlight/. Ce script y ajoute ce qu'il partage avec le
 * site Eleventy (feuille de style, script, polices, logo), prépare le contenu à importer
 * (textes de src/_data/, images de src/img/), puis écrit deux archives dans dist/ :
 *
 *   eventlight-theme.zip        le thème complet, images comprises
 *   eventlight-theme-leger.zip  le même sans les images : l'import les télécharge depuis GitHub.
 *                               Utile si l'hébergeur refuse les gros fichiers.
 */
const fs = require("fs");
const path = require("path");
const zlib = require("zlib");
const { execSync } = require("child_process");

const ROOT = path.resolve(__dirname, "..");
const SRC = path.join(ROOT, "src");
const THEME = path.join(ROOT, "wordpress", "eventlight");
const DIST = path.join(ROOT, "dist");
const DEPOT = "Albericyo/eventlight-site";

const lire = (nom) => JSON.parse(fs.readFileSync(path.join(SRC, "_data", nom), "utf8"));

function vider(dir) {
  fs.rmSync(dir, { recursive: true, force: true });
  fs.mkdirSync(dir, { recursive: true });
}

function copier(src, dest) {
  const stat = fs.statSync(src);
  if (stat.isDirectory()) {
    fs.mkdirSync(dest, { recursive: true });
    for (const f of fs.readdirSync(src)) {
      if (f === ".DS_Store") continue;
      copier(path.join(src, f), path.join(dest, f));
    }
  } else {
    fs.mkdirSync(path.dirname(dest), { recursive: true });
    fs.copyFileSync(src, dest);
  }
}

// ------------------------------------------------------------------ fichiers partagés

function fichiersPartages() {
  vider(path.join(THEME, "assets"));
  for (const d of ["css", "js", "fonts", "logo"]) copier(path.join(SRC, "assets", d), path.join(THEME, "assets", d));
  vider(path.join(THEME, "logo"));
  copier(path.join(SRC, "_includes", "logo"), path.join(THEME, "logo"));

  const business = lire("business.json");
  vider(path.join(THEME, "data"));
  fs.writeFileSync(
    path.join(THEME, "data", "composeur.json"),
    JSON.stringify({ eventTypes: business.eventTypes, tracks: business.tracks, addons: business.addons }, null, 1) + "\n"
  );
}

// ------------------------------------------------------------------ contenu à importer

/** Texte d'un fragment HTML simple : entités courantes décodées, balises retirées. */
const texteBrut = (html) =>
  html
    .replace(/<[^>]+>/g, "")
    .replace(/&amp;/g, "&")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">")
    .replace(/\s+/g, " ")
    .trim();

/** Les conditions générales, écrites en blocs de l'éditeur WordPress (titres, paragraphes, listes). */
function cgvEnBlocs() {
  const njk = fs.readFileSync(path.join(SRC, "mentions-legales", "index.njk"), "utf8");
  const debut = njk.indexOf('<div class="split-body prose">');
  if (debut < 0) throw new Error("mentions-legales : bloc .prose introuvable");
  const corps = njk.slice(debut);
  const blocs = [];
  const re = /<(h2|h3|p|ul)([^>]*)>([\s\S]*?)<\/\1>/g;
  let m;
  while ((m = re.exec(corps))) {
    const [, balise, attrs, contenu] = m;
    if (balise === "h2" || balise === "h3") {
      const id = (attrs.match(/id="([^"]+)"/) || [])[1];
      const niveau = balise === "h3" ? 3 : 2;
      const options = {};
      if (niveau !== 2) options.level = niveau;
      if (id) options.anchor = id;
      const json = Object.keys(options).length ? " " + JSON.stringify(options) : "";
      blocs.push(
        `<!-- wp:heading${json} -->\n<${balise} class="wp-block-heading"${id ? ` id="${id}"` : ""}>${contenu.trim()}</${balise}>\n<!-- /wp:heading -->`
      );
    } else if (balise === "p") {
      blocs.push(`<!-- wp:paragraph -->\n<p>${contenu.trim()}</p>\n<!-- /wp:paragraph -->`);
    } else {
      const items = [...contenu.matchAll(/<li>([\s\S]*?)<\/li>/g)].map(
        (li) => `<!-- wp:list-item -->\n<li>${li[1].trim()}</li>\n<!-- /wp:list-item -->`
      );
      blocs.push(`<!-- wp:list -->\n<ul class="wp-block-list">${items.join("\n\n")}</ul>\n<!-- /wp:list -->`);
    }
  }
  if (blocs.length < 20) throw new Error("mentions-legales : conversion incomplète (" + blocs.length + " blocs)");
  return blocs.join("\n\n");
}

/** Titre, description et introduction d'une page, lus dans son gabarit Eleventy. */
function page(fichier) {
  const njk = fs.readFileSync(path.join(SRC, fichier), "utf8");
  const champ = (nom) => {
    const m = njk.match(new RegExp("^" + nom + ':\\s*"(.*)"\\s*$', "m"));
    return m ? m[1] : "";
  };
  const h1 = njk.match(/<h1>([\s\S]*?)<\/h1>/);
  const lede = njk.match(/<p class="lede">([\s\S]*?)<\/p>/);
  return {
    titre: h1 ? texteBrut(h1[1]) : "",
    intro: lede && !lede[1].includes("{{") ? texteBrut(lede[1]) : "",
    seo_titre: champ("title"),
    seo_description: champ("description"),
  };
}

const paragraphe = (texte) => (texte ? `<!-- wp:paragraph -->\n<p>${texte}</p>\n<!-- /wp:paragraph -->` : "");

function contenu() {
  const formules = lire("formules.json");
  const produits = lire("produits.json");
  const portfolio = lire("portfolio.json");
  const videos = lire("videos.json");
  const media = lire("media.json");
  const accueil = lire("accueil.json");

  const images = (kind, slug) =>
    ((media[kind] || {})[slug] || []).map((e) => ({
      cle: e.base.replace(/^\/img\//, ""),
      largeurs: e.largeurs,
      w: e.w,
      h: e.h,
      fond: e.fond,
    }));

  const pages = [];
  const ajouter = (slug, fichier, plus = {}) => {
    const p = page(fichier);
    pages.push({ slug, parent: "", titre: p.titre, contenu: paragraphe(p.intro), seo_titre: p.seo_titre, seo_description: p.seo_description, noindex: false, ...plus });
  };
  ajouter("devis", "devis/index.njk");
  ajouter("merci", "devis/merci.njk", { parent: "devis", contenu: "", noindex: true });
  ajouter("contact", "contact/index.njk");
  ajouter("video-mapping", "video-mapping/index.njk");
  ajouter("mentions-legales", "mentions-legales/index.njk", { contenu: cgvEnBlocs() });
  ajouter("charte", "charte/index.njk", { contenu: "", noindex: true });

  // Le dernier commit qui a touché aux images : c'est lui que l'import léger télécharge.
  let commit = "";
  try {
    commit = execSync("git log -1 --format=%H -- src/img", { cwd: ROOT, encoding: "utf8" }).trim();
  } catch (e) {}

  return {
    version: 1,
    images: {
      depot: DEPOT,
      versions: [commit, "refonte-da", "main"].filter(Boolean),
      dossier: "src/img",
    },
    types: [...new Set(portfolio.map((p) => p.type))],
    categories: lire("categories.json"),
    formules: formules.pages.map((f) => ({
      slug: f.slug,
      titre: f.titre,
      accroche: f.accroche,
      h1: f.h1,
      description: f.description,
      seo_titre: f.seoTitle,
      seo_description: f.seoDescription,
      devis_type: f.devisType,
      types: f.typesPortfolio || [],
      options: f.optionsHorsPack || [],
      packs: f.packs.map((p) => ({ nom: p.nom, prix: p.prix, featured: Boolean(p.featured), items: p.items })),
    })),
    produits: produits.map((p) => {
      const stock = (p.details || []).map((d) => d.match(/^(\d+) en stock/)).find(Boolean);
      return {
        slug: p.slug,
        nom: p.nom,
        categorie: p.categorie,
        prix: p.prix,
        description: p.description,
        details: p.details || [],
        usages: p.usages || [],
        youtube: videos[p.slug] || "",
        stock: stock ? Number(stock[1]) : null,
        images: images("location", p.slug),
      };
    }),
    realisations: portfolio.map((p) => ({
      slug: p.slug,
      titre: p.titre,
      sous_titre: p.sousTitre || "",
      annee: p.annee || "",
      type: p.type || "",
      production: p.production || "",
      credits: p.credits || [],
      logiciel: p.logiciel || "",
      technologie: p.technologie || "",
      texte: p.projet || p.resume || "",
      youtube: videos[p.slug] || "",
      couverture: p.couverture || 0,
      images: images("portfolio", p.slug),
    })),
    questions: lire("faq.json"),
    accueil: {
      scenes: accueil.scenes.map((s) => ({ label: s.label, realisation: s.projet, image: s.image, cadrage: s.position, legende: s.legende })),
      realisations: accueil.realisations,
      mapping_vedette: "pignan",
    },
    pages,
  };
}

function preparerImport() {
  vider(path.join(THEME, "import"));
  const data = contenu();
  fs.writeFileSync(path.join(THEME, "import", "contenu.json"), JSON.stringify(data, null, 1) + "\n");
  copier(path.join(SRC, "img"), path.join(THEME, "import", "img"));
  return data;
}

// ------------------------------------------------------------------ archive .zip

const TABLE_CRC = (() => {
  const t = new Uint32Array(256);
  for (let n = 0; n < 256; n++) {
    let c = n;
    for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
    t[n] = c >>> 0;
  }
  return t;
})();
function crc32(buf) {
  let c = 0xffffffff;
  for (let i = 0; i < buf.length; i++) c = TABLE_CRC[(c ^ buf[i]) & 0xff] ^ (c >>> 8);
  return (c ^ 0xffffffff) >>> 0;
}

/** Écrit une archive .zip. `entrees` : [{ nom, fichier }] ; les dossiers sont déduits des noms. */
function ecrireZip(cible, entrees) {
  const maintenant = new Date();
  const heure = (maintenant.getHours() << 11) | (maintenant.getMinutes() << 5) | (maintenant.getSeconds() >> 1);
  const jour = ((maintenant.getFullYear() - 1980) << 9) | ((maintenant.getMonth() + 1) << 5) | maintenant.getDate();

  const dossiers = new Set();
  for (const e of entrees) {
    const morceaux = e.nom.split("/");
    for (let i = 1; i < morceaux.length; i++) dossiers.add(morceaux.slice(0, i).join("/") + "/");
  }
  const tout = [...[...dossiers].sort().map((nom) => ({ nom, dossier: true })), ...entrees];

  const sortie = fs.openSync(cible, "w");
  const centre = [];
  let position = 0;
  const ecrire = (buf) => {
    fs.writeSync(sortie, buf);
    position += buf.length;
  };

  for (const e of tout) {
    const nom = Buffer.from(e.nom, "utf8");
    const brut = e.dossier ? Buffer.alloc(0) : fs.readFileSync(e.fichier);
    const dejaCompresse = /\.(webp|png|jpe?g|woff2|zip)$/i.test(e.nom);
    const methode = e.dossier || dejaCompresse || brut.length === 0 ? 0 : 8;
    const donnees = methode === 8 ? zlib.deflateRawSync(brut, { level: 9 }) : brut;
    const crc = crc32(brut);
    const debut = position;

    const local = Buffer.alloc(30);
    local.writeUInt32LE(0x04034b50, 0);
    local.writeUInt16LE(20, 4);
    local.writeUInt16LE(0x0800, 6);
    local.writeUInt16LE(methode, 8);
    local.writeUInt16LE(heure, 10);
    local.writeUInt16LE(jour, 12);
    local.writeUInt32LE(crc, 14);
    local.writeUInt32LE(donnees.length, 18);
    local.writeUInt32LE(brut.length, 22);
    local.writeUInt16LE(nom.length, 26);
    local.writeUInt16LE(0, 28);
    ecrire(local);
    ecrire(nom);
    ecrire(donnees);

    const c = Buffer.alloc(46);
    c.writeUInt32LE(0x02014b50, 0);
    c.writeUInt16LE(0x031e, 4);
    c.writeUInt16LE(20, 6);
    c.writeUInt16LE(0x0800, 8);
    c.writeUInt16LE(methode, 10);
    c.writeUInt16LE(heure, 12);
    c.writeUInt16LE(jour, 14);
    c.writeUInt32LE(crc, 16);
    c.writeUInt32LE(donnees.length, 20);
    c.writeUInt32LE(brut.length, 24);
    c.writeUInt16LE(nom.length, 28);
    c.writeUInt16LE(0, 30);
    c.writeUInt16LE(0, 32);
    c.writeUInt16LE(0, 34);
    c.writeUInt16LE(0, 36);
    c.writeUInt32LE((e.dossier ? ((0o40755 << 16) | 0x10) : (0o100644 << 16)) >>> 0, 38);
    c.writeUInt32LE(debut, 42);
    centre.push(Buffer.concat([c, nom]));
  }

  const debutCentre = position;
  for (const c of centre) ecrire(c);
  const fin = Buffer.alloc(22);
  fin.writeUInt32LE(0x06054b50, 0);
  fin.writeUInt16LE(0, 4);
  fin.writeUInt16LE(0, 6);
  fin.writeUInt16LE(tout.length, 8);
  fin.writeUInt16LE(tout.length, 10);
  fin.writeUInt32LE(position - debutCentre, 12);
  fin.writeUInt32LE(debutCentre, 16);
  fin.writeUInt16LE(0, 20);
  ecrire(fin);
  fs.closeSync(sortie);
}

function lister(dir, prefixe, garder) {
  const sortie = [];
  for (const f of fs.readdirSync(dir).sort()) {
    if (f === ".DS_Store") continue;
    const chemin = path.join(dir, f);
    const nom = prefixe + "/" + f;
    if (fs.statSync(chemin).isDirectory()) sortie.push(...lister(chemin, nom, garder));
    else if (garder(nom)) sortie.push({ nom, fichier: chemin });
  }
  return sortie;
}

const taille = (f) => (fs.statSync(f).size / 1048576).toFixed(1).replace(".", ",") + " Mo";

// ------------------------------------------------------------------ assemblage

function main() {
  if (!fs.existsSync(path.join(THEME, "style.css"))) throw new Error("wordpress/eventlight/style.css introuvable");
  fichiersPartages();
  const data = preparerImport();

  if (process.argv.includes("--sans-zip")) {
    console.log("Thème assemblé dans wordpress/eventlight/ (sans archive).");
    return;
  }

  fs.mkdirSync(DIST, { recursive: true });
  const complet = path.join(DIST, "eventlight-theme.zip");
  const leger = path.join(DIST, "eventlight-theme-leger.zip");
  ecrireZip(complet, lister(THEME, "eventlight", () => true));
  ecrireZip(leger, lister(THEME, "eventlight", (nom) => !nom.startsWith("eventlight/import/img/")));

  const nbImages = data.produits.concat(data.realisations).reduce((n, x) => n + x.images.length, 0);
  console.log(
    `${data.formules.length} formules, ${data.produits.length} produits, ${data.realisations.length} réalisations, ${nbImages} images, ${data.pages.length} pages.`
  );
  console.log(`dist/eventlight-theme.zip        ${taille(complet)}`);
  console.log(`dist/eventlight-theme-leger.zip  ${taille(leger)}`);
}

main();
