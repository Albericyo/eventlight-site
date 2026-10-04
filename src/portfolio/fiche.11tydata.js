module.exports = {
  eleventyComputed: {
    title: (data) => {
      const p = data.projet;
      if (!p) return data.title;
      return `${p.titre} - ${p.annee} | Portfolio Event'Light`;
    },
    description: (data) => {
      const p = data.projet;
      if (!p) return data.description;
      return p.projet || p.resume || `${p.titre} - réalisation Event'Light.`;
    },
    breadcrumbs: (data) => {
      if (!data.projet) return [];
      return [
        { name: "Réalisations", url: "/portfolio/" },
        { name: String(data.projet.titre).replace(/^Mapping(?: en 48h)? - /, "").replace(/ - /g, ", ") }
      ];
    }
  }
};
