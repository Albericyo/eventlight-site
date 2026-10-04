const now = new Date();

module.exports = {
  date: now.toISOString().slice(0, 10),
  annee: now.getFullYear(),
  // Suffixe ajouté aux feuilles de style et scripts pour renouveler le cache à chaque mise en ligne.
  version: now.toISOString().replace(/\D/g, "").slice(0, 12),
};
