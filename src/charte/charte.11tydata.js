// La charte est interne : elle n'est publiée qu'en local, avec `npm run charte`.
module.exports = {
  eleventyComputed: {
    permalink: () => (process.env.CHARTE ? "/charte/" : false),
  },
};
