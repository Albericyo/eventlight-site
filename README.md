# Event'Light — nouveau site (sans Google Sites)

Site statique généré avec **Eleventy (11ty)**. Gratuit à 100% : hébergement, HTTPS, formulaire.

## Ce qui a déjà été fait pour toi

- 29 pages générées automatiquement (accueil, 4 formules, catalogue, 17 fiches produits, devis, contact, portfolio, mentions légales)
- Chaque page a un `<title>`, une meta description et un schema.org (`LocalBusiness` + `Product` sur les fiches produits)
- Un `robots.txt` et un `sitemap.xml` propres et sous ton contrôle
- Les 4 pages "formules" pointent automatiquement vers le matériel adapté (mariage → produits taggés `mariage`, etc.) — c'est piloté par `src/_data/produits.json`, tu n'as qu'à éditer ce fichier pour ajouter/modifier du matériel, pas besoin de toucher au HTML
- Un formulaire de devis prêt pour Netlify Forms (gratuit)
- Un fichier `_redirects` avec les redirections 301 des anciennes URLs Google Sites vers les nouvelles (à compléter si tu identifies d'autres URLs indexées)

## Étape par étape pour mettre en ligne (gratuit)

### 1. Créer un compte GitHub (si tu n'en as pas) et pousser le projet
```bash
cd eventlight-site
git init
git add .
git commit -m "Nouveau site Event'Light"
git branch -M main
git remote add origin https://github.com/TON_USER/eventlight-site.git
git push -u origin main
```
(Crée d'abord le repo vide sur github.com, sans README ni .gitignore)

### 2. Déployer sur Netlify (gratuit, formulaire inclus)
1. Va sur https://app.netlify.com → "Add new site" → "Import an existing project"
2. Connecte ton compte GitHub, choisis le repo `eventlight-site`
3. Build command : `npm run build` — Publish directory : `_site`
4. Clique "Deploy" — ton site est en ligne en ~1 minute sur une URL du type `xxx.netlify.app`

### 3. Brancher ton domaine eventlight.net
1. Dans Netlify : Site settings → Domain management → Add custom domain → `eventlight.net`
2. Netlify te donne des enregistrements DNS (A record + CNAME) à ajouter chez ton registrar actuel (là où tu as acheté eventlight.net)
3. Le HTTPS se configure automatiquement une fois le DNS propagé (quelques heures max)

### 4. Vérifier le formulaire de devis
Une fois déployé, va dans Netlify → Forms : le formulaire "devis" doit apparaître automatiquement (grâce à `data-netlify="true"` dans `src/devis/index.njk`). Tu peux configurer une notification email dans les settings du formulaire.

### 5. Search Console (gratuit, à faire dès la mise en ligne)
1. https://search.google.com/search-console → ajouter `eventlight.net`
2. Soumettre `https://www.eventlight.net/sitemap.xml`
3. Demander l'indexation manuelle des pages clés (accueil, /location/, les 4 formules)

## Pour continuer à développer en local
```bash
npm install
npm run serve   # prévisualisation sur http://localhost:8080
npm run build   # génère le site dans _site/
```

## À compléter toi-même
- `src/images/` : ajouter ton logo et tes photos (compressées en WebP idéalement)
- `src/portfolio/index.njk` : intégrer tes réalisations
- `src/mentions-legales/index.njk` : recopier l'intégralité de tes CGV actuelles
- `src/_data/produits.json` : compléter avec les articles manquants de ton catalogue actuel
- Numéro de téléphone dans le schema.org (`src/_includes/layout.njk`) si tu veux l'afficher
