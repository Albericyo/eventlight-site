module.exports = function (eleventyConfig) {
  eleventyConfig.addPassthroughCopy("src/styles.css");
  eleventyConfig.addPassthroughCopy("src/player.js");
  eleventyConfig.addPassthroughCopy("src/robots.txt");
  eleventyConfig.addPassthroughCopy({ "src/_redirects": "_redirects" });
  eleventyConfig.addPassthroughCopy("src/images");

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

  return {
    dir: {
      input: "src",
      output: "_site",
      includes: "_includes",
      data: "_data",
    },
  };
};
