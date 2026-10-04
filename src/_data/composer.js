/**
 * Données du composeur de la page d'accueil : types d'événement, pistes, options,
 * et pour chaque pack les « couches » à allumer sur le plan de feu.
 */
const fs = require("fs");
const path = require("path");
const { couchesDepuisItems } = require("../../lib/plan-de-feu.js");

const lire = (nom) => JSON.parse(fs.readFileSync(path.join(__dirname, nom), "utf8"));

module.exports = () => {
  const formules = lire("formules.json");
  const business = lire("business.json");
  const site = lire("site.json");
  return {
    eventTypes: business.eventTypes,
    tracks: business.tracks,
    addons: business.addons,
    pricingNote: business.pricingNote,
    tvaNote: site.tva,
    formules: formules.pages.map((f) => ({
      slug: f.slug,
      titre: f.titre,
      packs: f.packs.map((p) => ({
        nom: p.nom,
        prix: p.prix,
        featured: Boolean(p.featured),
        items: p.items,
        couches: couchesDepuisItems(p.items),
      })),
    })),
  };
};
