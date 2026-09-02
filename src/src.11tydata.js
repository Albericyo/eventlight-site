module.exports = {
  date: "2026-09-02",
  eleventyComputed: {
    canonical: (data) => {
      if (!data.site || !data.page) return "";
      return data.site.url + data.page.url;
    }
  }
};
