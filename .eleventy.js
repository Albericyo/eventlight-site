const fs = require("fs");
const path = require("path");

function mediaFiles(kind, slug) {
  if (!slug) return [];
  const dir = path.join("src/images", kind, slug);
  if (!fs.existsSync(dir)) return [];
  return fs
    .readdirSync(dir)
    .filter((f) => /\.(jpe?g|png|webp|gif)$/i.test(f))
    .sort()
    .map((f) => `/images/${kind}/${slug}/${f}`);
}

module.exports = function (eleventyConfig) {
  eleventyConfig.addPassthroughCopy("src/styles.css");
  eleventyConfig.addPassthroughCopy("src/player.js");
  eleventyConfig.addPassthroughCopy("src/robots.txt");
  eleventyConfig.addPassthroughCopy({ "src/_redirects": "_redirects" });
  eleventyConfig.addPassthroughCopy("src/images");

  eleventyConfig.addFilter("youtubeId", (slug) => {
    try {
      const man = JSON.parse(fs.readFileSync(path.join("src/images/manifest.json"), "utf8"));
      return (man.youtube && man.youtube[slug]) || "";
    } catch (e) {
      return "";
    }
  });
  eleventyConfig.addFilter("cover", (slug, kind) => mediaFiles(kind, slug)[0] || "");
  eleventyConfig.addFilter("gallery", (slug, kind) => mediaFiles(kind, slug));
  eleventyConfig.addFilter("firstOfCat", (produits, catName) =>
    (produits || []).find((p) => p.categorie === catName) || null
  );

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

  eleventyConfig.addFilter("frameClass", function (slug) {
    const map = {
      mariage: "g-red",
      anniversaire: "g-violet",
      "comite-entreprise": "g-blue",
      "manifestation-sportive": "g-green",
    };
    return map[slug] || "g-amber";
  });

  eleventyConfig.addFilter("folioFrame", function (index) {
    const frames = ["g-blue", "g-red", "g-green", "g-violet", "g-amber"];
    return frames[index % frames.length];
  });

  eleventyConfig.addFilter("prixSchema", (value) => {
    if (!value) return "";
    const match = String(value).replace(",", ".").match(/[\d.]+/);
    return match ? match[0] : "";
  });

  eleventyConfig.addFilter("jsonLd", (value) => JSON.stringify(value));

  eleventyConfig.addFilter("encodeURIComponent", (value) =>
    encodeURIComponent(String(value ?? ""))
  );

  eleventyConfig.addFilter("xmlEsc", (value) =>
    String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
  );

  eleventyConfig.addFilter("categorieSlug", (nom, categories) => {
    const found = (categories || []).find((c) => c.nom === nom);
    return found ? found.slug : "";
  });

  eleventyConfig.addFilter("relatedProduits", (produits, current) => {
    if (!current || !produits) return [];
    return produits
      .filter((p) => p.slug !== current.slug && p.categorie === current.categorie)
      .slice(0, 3);
  });

  eleventyConfig.addFilter("sitemapPriority", (url) => {
    if (url === "/") return "1.0";
    if (url === "/location/" || url === "/nos-formules/" || url === "/devis/") return "0.9";
    if (url.startsWith("/nos-formules/") || url.startsWith("/location/")) return "0.8";
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
