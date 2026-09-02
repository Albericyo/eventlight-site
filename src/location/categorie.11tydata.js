module.exports = {
  eleventyComputed: {
    title: (data) => (data.categorie && data.categorie.seoTitle) || data.title,
    description: (data) => (data.categorie && data.categorie.seoDescription) || data.description,
    breadcrumbs: (data) => {
      if (!data.categorie) return [];
      return [
        { name: "Location", url: "/location/" },
        { name: data.categorie.nom }
      ];
    }
  }
};
