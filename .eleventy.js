const fs = require("fs");
const path = require("path");
const { planDeFeu, couchesDepuisItems, LIBELLES } = require("./lib/plan-de-feu.js");
const pictos = require("./lib/pictos.js");

/** Lit un fichier de src/_data à chaque appel (léger, et à jour en mode --serve). */
function donnees(nom) {
  try {
    return JSON.parse(fs.readFileSync(path.join("src", "_data", nom), "utf8"));
  } catch (e) {
    return nom === "portfolio.json" ? [] : {};
  }
}

const esc = (v) =>
  String(v ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");

/** Images d'un projet ou d'un produit (voir scripts/optimize-images.js). */
function mediaDe(kind, slug) {
  const media = donnees("media.json");
  return (media[kind] && media[kind][slug]) || [];
}

module.exports = function (eleventyConfig) {
  eleventyConfig.addPassthroughCopy({ "src/assets": "assets" });
  eleventyConfig.addPassthroughCopy({ "src/img": "img" });
  eleventyConfig.addPassthroughCopy("src/robots.txt");
  eleventyConfig.addPassthroughCopy({ "src/_redirects": "_redirects" });
  eleventyConfig.addWatchTarget("./lib/");

  // ---------------------------------------------------------------- images

  eleventyConfig.addFilter("media", (slug, kind) => mediaDe(kind, slug));

  /** Photo de couverture d'un produit : de préférence un détourage sur fond clair. */
  eleventyConfig.addFilter("coverProduit", (slug) => {
    const liste = mediaDe("location", slug);
    return liste.find((m) => m.fond === "clair" || m.fond === "transparent") || liste[0] || null;
  });

  /** Image de couverture. Un projet peut choisir la sienne : "couverture": 5 (rang dans son dossier). */
  eleventyConfig.addFilter("cover", (slug, kind) => {
    const liste = mediaDe(kind, slug);
    if (kind === "portfolio") {
      const projet = (donnees("portfolio.json") || []).find((p) => p.slug === slug);
      if (projet && projet.couverture && liste[projet.couverture - 1]) return liste[projet.couverture - 1];
    }
    return liste[0] || null;
  });

  eleventyConfig.addFilter("avecFond", (liste, fond) => (liste || []).filter((m) => m.fond === fond));

  /**
   * <img> adaptatif.
   *   {% img entree, "texte alternatif", "(min-width: 60em) 50vw, 100vw", "lazy", "classe" %}
   */
  eleventyConfig.addShortcode("img", (entree, alt, sizes, loading, classe) => {
    if (!entree) return "";
    const largeurs = entree.largeurs || [];
    const src = (l) => `${entree.base}-${l}.webp`;
    const defaut = largeurs.filter((l) => l <= 960).pop() || largeurs[0];
    const srcset = largeurs.map((l) => `${src(l)} ${l}w`).join(", ");
    const attrs = [
      `src="${src(defaut)}"`,
      largeurs.length > 1 ? `srcset="${srcset}"` : "",
      largeurs.length > 1 ? `sizes="${esc(sizes || "100vw")}"` : "",
      `width="${entree.w}"`,
      `height="${entree.h}"`,
      `alt="${esc(alt)}"`,
      loading === "eager" ? 'fetchpriority="high"' : 'loading="lazy"',
      'decoding="async"',
      classe ? `class="${esc(classe)}"` : "",
      `data-fond="${entree.fond}"`,
    ];
    return `<img ${attrs.filter(Boolean).join(" ")}>`;
  });

  eleventyConfig.addFilter("youtubeId", (slug) => donnees("videos.json")[slug] || "");

  // ---------------------------------------------------------------- dessins

  eleventyConfig.addFilter("couches", (items) => couchesDepuisItems(items));
  eleventyConfig.addFilter("coucheLabel", (c) => LIBELLES[c] || c);
  eleventyConfig.addShortcode("plan", (couches, titre, complet) =>
    planDeFeu(couches, { titre, complet: Boolean(complet) })
  );
  eleventyConfig.addShortcode("picto", (nom, titre) => pictos.picto(nom, titre));
  eleventyConfig.addFilter("aPicto", (nom) => pictos.a(nom));

  // ---------------------------------------------------------------- textes et prix

  /** "40,00 €/j (la paire)" -> { montant: "40", unite: "€/j", note: "la paire" } */
  eleventyConfig.addFilter("prixDetail", (value) => {
    const s = String(value || "");
    const m = s.match(/([\d\s]+)(?:,(\d+))?\s*€(\/j)?\s*(?:\((.+)\))?/);
    if (!m) return { montant: s, unite: "", note: "" };
    const cents = m[2] && m[2] !== "00" ? "," + m[2] : "";
    return { montant: m[1].trim() + cents, unite: m[3] ? "€/j" : "€", note: m[4] || "" };
  });

  eleventyConfig.addFilter("prixNombre", (value) => {
    const m = String(value || "").replace(",", ".").match(/[\d.]+/);
    return m ? Number(m[0]) : 0;
  });

  eleventyConfig.addFilter("prixSchema", (value) => {
    if (!value) return "";
    const match = String(value).replace(",", ".").match(/[\d.]+/);
    return match ? match[0] : "";
  });

  /** Prix le plus bas d'une liste de produits, pour « dès 9 €/j ». */
  eleventyConfig.addFilter("prixMin", (produits) => {
    const n = (produits || [])
      .map((p) => Number(String(p.prix).replace(",", ".").match(/[\d.]+/)?.[0] || 0))
      .filter(Boolean);
    return n.length ? Math.min(...n) : 0;
  });

  eleventyConfig.addFilter("deCategorie", (produits, nom) =>
    (produits || []).filter((p) => p.categorie === nom)
  );

  eleventyConfig.addFilter("deType", (projets, type) =>
    (projets || []).filter((p) => p.type === type)
  );

  eleventyConfig.addFilter("types", (projets) => [...new Set((projets || []).map((p) => p.type))]);

  eleventyConfig.addFilter("avecUsage", (produits, usage) =>
    (produits || []).filter((p) => (p.usages || []).includes(usage))
  );

  eleventyConfig.addFilter("parSlug", (liste, slug) => (liste || []).find((x) => x.slug === slug) || null);

  /** Projet précédent / suivant dans le portfolio. */
  eleventyConfig.addFilter("voisin", (liste, slug, pas) => {
    const i = (liste || []).findIndex((x) => x.slug === slug);
    if (i < 0) return null;
    return liste[(i + pas + liste.length) % liste.length];
  });

  /** Coupe "Rôle : Nom" en deux pour les fiches techniques. */
  eleventyConfig.addFilter("credit", (ligne) => {
    const i = String(ligne).indexOf(" : ");
    return i < 0 ? { role: "", nom: ligne } : { role: ligne.slice(0, i), nom: ligne.slice(i + 3) };
  });

  eleventyConfig.addFilter("slug", (value) =>
    String(value || "")
      .normalize("NFD")
      .replace(/[̀-ͯ]/g, "")
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-|-$/g, "")
  );

  /** Typographie française : espaces insécables avant : ; ! ? » € % et après «. */
  const typo = (value) =>
    String(value ?? "")
      .replace(/ ([;!?])/g, "\u202f$1")
      .replace(/ ([:»])/g, "\u00a0$1")
      .replace(/« /g, "«\u00a0")
      .replace(/(\d) (€|%)/g, "$1\u00a0$2");
  eleventyConfig.addFilter("typo", typo);

  // La même règle, appliquée à tout le texte des pages (hors balises, scripts et styles).
  eleventyConfig.addTransform("typographie", function (content, outputPath) {
    const sortie = outputPath || (this && this.outputPath) || "";
    if (!String(sortie).endsWith(".html")) return content;
    return content.replace(
      /(<script[\s\S]*?<\/script>|<style[\s\S]*?<\/style>|<textarea[\s\S]*?<\/textarea>|<[^>]+>)|([^<]+)/g,
      (m, balise, texte) => (balise ? balise : typo(texte))
    );
  });

  /** "Mapping - Pignan Millénaire" -> "Pignan Millénaire" : le type est déjà affiché en étiquette. */
  eleventyConfig.addFilter("titreCourt", (titre) =>
    String(titre || "")
      .replace(/^Mapping(?: en 48h)? - /, "")
      .replace(/ - /g, ", ")
  );
  eleventyConfig.addFilter("virgules", (texte) => String(texte || "").replace(/ - /g, ", "));

  eleventyConfig.addFilter("usageLabel", function (slug) {
    const map = {
      mariage: "Mariage",
      anniversaire: "Anniversaire",
      "comite-entreprise": "Comité d'entreprise",
      "manifestation-sportive": "Manifestation sportive",
    };
    return map[slug] || slug;
  });

  eleventyConfig.addFilter("startsWith", function (value, prefix) {
    return typeof value === "string" && value.startsWith(prefix);
  });

  eleventyConfig.addFilter("jsonLd", (value) => JSON.stringify(value));

  eleventyConfig.addFilter("encodeURIComponent", (value) => encodeURIComponent(String(value ?? "")));

  eleventyConfig.addFilter("xmlEsc", esc);

  eleventyConfig.addFilter("categorieSlug", (nom, categories) => {
    const found = (categories || []).find((c) => c.nom === nom);
    return found ? found.slug : "";
  });

  eleventyConfig.addFilter("relatedProduits", (produits, current) => {
    if (!current || !produits) return [];
    return produits.filter((p) => p.slug !== current.slug && p.categorie === current.categorie).slice(0, 3);
  });

  eleventyConfig.addFilter("sitemapPriority", (url) => {
    if (url === "/") return "1.0";
    if (url === "/location/" || url === "/nos-formules/" || url === "/devis/") return "0.9";
    if (url.startsWith("/nos-formules/") || url.startsWith("/location/")) return "0.8";
    if (url === "/video-mapping/") return "0.8";
    if (url.startsWith("/portfolio/")) return "0.6";
    if (url === "/contact/") return "0.7";
    return "0.4";
  });

  return {
    dir: {
      input: "src",
      output: "_site",
      includes: "_includes",
      data: "_data",
    },
  };
};
