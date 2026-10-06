# Event'Light, le site

Site statique généré avec **Eleventy (11ty)**, publié sur Netlify. Le contenu vient des fichiers JSON de `src/_data/` : changer un prix, ajouter un produit ou une réalisation ne demande pas de toucher aux gabarits.

## L'identité en trois lignes

- **Un mur gris aluminium, de la lumière blanche, un trait noir.** Ce qui compte est éclairé (blanc), le reste est au trait sur le mur. La couleur vient des photos.
- **Tout part du signe** : un boîtier 11:10, un faisceau de pente 3:8 (41°). Il sert de héros, d'interrupteur, de puce de bouton, et de projecteur dans les plans de feu.
- **Une seule épaisseur de trait à l'écran** (1,5 px), une seule famille de caractères (Sofia Sans, servie par le site).

La charte complète est un document interne : elle n'est pas publiée. Pour la lire, lance `npm run charte` (page `/charte/` en local) ; sur WordPress, elle est dans le menu Event'Light > Charte graphique (administrateurs uniquement) : construction du signe, logos, couleurs, typographie, règles d'usage.

## Développer

```bash
npm install
npm run serve     # http://localhost:8080
npm run build     # sortie dans _site/
```

| Commande | Rôle |
| --- | --- |
| `npm run images` | Optimise les photos de `src/images/` vers `src/img/` (WebP, plusieurs largeurs) et met à jour `src/_data/media.json`. À relancer après chaque ajout de photo. |
| `npm run icons` | Refait le favicon, les icônes et l'image de partage (`src/assets/logo/`). |
| `npm run logo` | Régénère tous les SVG du logo depuis la géométrie (`scripts/logo/`, Python 3 + `pip install shapely`), puis les icônes. |
| `npm run wordpress` | Assemble le thème WordPress et écrit `dist/eventlight-theme.zip`. Voir « Version WordPress » plus bas. |

## Où est quoi

```
src/
  _data/
    site.json         coordonnées, SIRET, réseaux
    formules.json     formules, packs, prix, options
    produits.json     catalogue de location
    categories.json   catégories du catalogue (textes et SEO)
    portfolio.json    réalisations
    videos.json       identifiants YouTube, par slug de projet ou de produit
    accueil.json      images du héros, réalisations mises en avant, étapes
    business.json     types d'événement et options du composeur
    faq.json          questions fréquentes (reprises dans les données structurées)
    media.json        généré par `npm run images`, ne pas éditer
    logo.json         généré par `npm run logo`
  _includes/
    layout.njk        squelette de page, balises SEO
    partials/         en-tête, pied de page
    macros.njk        fiche produit, réalisation, rangée de formule, bandeau d'appel
    logo/             SVG du logo à inclure dans les gabarits
  assets/
    css/site.css      toute la feuille de style (jetons en tête de fichier)
    js/site.js        thème, menu, héros, composeur, sélection de matériel, galerie
    fonts/            Sofia Sans (licence OFL jointe)
    logo/             fichiers du logo, favicon, image de partage
  images/             photos originales (non publiées) ; brand/ garde les fichiers d'origine du logo
  img/                photos optimisées (publiées)
lib/
  plan-de-feu.js      dessine le schéma d'un pack à partir de ses lignes
  pictos.js           pictogrammes au trait (catégories, produits sans photo)
scripts/              optimisation des images, icônes, géométrie du logo, assemblage du thème WordPress
wordpress/eventlight/ le thème WordPress (gabarits PHP, administration)
```

## Gestes courants

**Changer un prix ou le contenu d'un pack** : `src/_data/formules.json`. Le schéma du pack se redessine tout seul d'après ses lignes (« 4 totems », « Fumée lourde », « Animateur »…). Les correspondances sont en tête de `lib/plan-de-feu.js`.

**Ajouter un produit à la location** : une entrée dans `src/_data/produits.json`, ses photos dans `src/images/location/<slug>/` (01.jpg, 02.jpg…), puis `npm run images`. Sans photo, la fiche affiche le pictogramme de sa catégorie.

**Ajouter une réalisation** : une entrée dans `src/_data/portfolio.json`, ses photos dans `src/images/portfolio/<slug>/`, puis `npm run images`. Pour une vidéo, ajouter `"<slug>": "<id YouTube>"` dans `videos.json`. La première photo sert de couverture, sauf si l'entrée précise `"couverture": 3`.

**Changer les images du héros** : `src/_data/accueil.json` (projet, rang de l'image, cadrage, légende).

**Changer une couleur ou une taille** : les jetons en tête de `src/assets/css/site.css`. Le thème sombre (« salle éteinte ») redéfinit les mêmes jetons.

## Version WordPress

Le même site existe en thème WordPress, dans `wordpress/eventlight/`. Les pages sont identiques à celles d'Eleventy : même feuille de style, même script, mêmes dessins. Ce qui change, c'est que le contenu se modifie dans l'administration, et que WordPress ajoute ce qu'un site statique ne sait pas faire : recevoir les demandes de devis, suivre le stock, tenir un planning.

```bash
npm run wordpress   # écrit dist/eventlight-theme.zip et dist/eventlight-theme-leger.zip
```

Le script copie dans le thème ce qu'il partage avec Eleventy (`src/assets/`, les SVG du logo), prépare le contenu à importer à partir de `src/_data/` et de `src/img/`, puis fait l'archive. Ces copies ne sont pas suivies par git : seul le code du thème l'est.

- `eventlight-theme.zip` contient les photos. C'est celui à installer.
- `eventlight-theme-leger.zip` ne les contient pas : l'import les télécharge depuis ce dépôt. À utiliser si l'hébergeur refuse les gros fichiers.

L'installation et l'usage au quotidien sont décrits dans `wordpress/eventlight/LISEZ-MOI.txt`, livré avec le thème.

| Dans le thème | Rôle |
| --- | --- |
| `*.php` à la racine | Un gabarit par type de page, portés de `src/**/*.njk`. |
| `inc/plan-de-feu.php`, `inc/pictos.php`, `inc/textes.php` | Ports PHP de `lib/` et des filtres de `.eleventy.js`. Leur sortie est identique, au caractère près. |
| `inc/types.php`, `inc/donnees.php` | Formules, produits, réalisations et questions : types de contenu, adresses, lecture. |
| `inc/planning.php`, `inc/devis.php` | Demandes de devis, réservations, stock, disponibilités, agenda. |
| `inc/admin/` | Fiches, réglages, calendrier, import du contenu. |

Une fois le site passé sur WordPress, le contenu vit dans WordPress : les fichiers de `src/_data/` ne servent plus qu'au premier import.

## Mise en ligne (Netlify)

1. Build : `npm run build`, dossier publié : `_site`.
2. Le formulaire de devis passe par Netlify Forms (`name="devis"`), puis redirige vers `/devis/merci/`.
3. Les redirections depuis les anciennes adresses Google Sites sont dans `src/_redirects`.

Les vidéos YouTube ne se chargent qu'au clic (aucun traceur au chargement des pages), les polices sont servies par le site, la carte est un simple lien.
