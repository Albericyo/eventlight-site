module.exports = {
  eleventyComputed: {
    title: (data) => {
      const p = data.produit;
      if (!p) return data.title;
      return `Location ${p.nom} Amiens - ${p.prix} | Event'Light`;
    },
    description: (data) => {
      const p = data.produit;
      if (!p) return data.description;
      return `${p.description} Location à Amiens, livraison dès 25 €.`;
    },
    breadcrumbs: (data) => {
      const p = data.produit;
      if (!p) return [];
      const cat = (data.categories || []).find((c) => c.nom === p.categorie);
      return [
        { name: "Location", url: "/location/" },
        cat ? { name: cat.nom, url: `/location/${cat.slug}/` } : { name: p.categorie, url: "/location/" },
        { name: p.nom }
      ];
    }
  }
};
