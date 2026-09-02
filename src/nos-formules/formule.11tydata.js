module.exports = {
  eleventyComputed: {
    title: (data) => (data.formule && data.formule.seoTitle) || data.title,
    description: (data) => (data.formule && data.formule.seoDescription) || data.description,
    breadcrumbs: (data) => {
      if (!data.formule) return [];
      return [
        { name: "Nos formules", url: "/nos-formules/" },
        { name: data.formule.titre }
      ];
    }
  }
};
