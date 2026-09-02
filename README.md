# Event'Light — site

Site statique généré avec **Eleventy (11ty)**, à partir du contenu du site Google Sites [eventlight.net](https://www.eventlight.net/).

## Contenu repris du site actuel

- Accueil (« Créateurs de rêves »), nouveautés étincelles / geysers CO2
- Formules : mariage (550 / 799 / 999 €), anniversaire (499 / 699 €), CE (250 €), sport (199 / 399 €)
- Catalogue location (17 références) et fiches produits
- Portfolio (14 réalisations, y compris mappings Pignan, Saint-Jean, Palais des Beaux-Arts)
- Contact Albéric Delabie et Antoine Capel
- CGV intégrales, SIRET, siège 53 rue du Général Friant
- Redirections 301 depuis les URLs Google Sites

Les photos hébergées par Google Sites n'ont pas pu être téléchargées (blocage 403). Dépose tes visuels dans `src/images/` pour les intégrer.

## Développer en local

```bash
npm install
npm run serve
npm run build
```

Prévisualisation : http://localhost:8080

## Mise en ligne (Netlify)

1. Importer le repo GitHub `eventlight-site`
2. Build : `npm run build` — Publish : `_site`
3. Brancher `eventlight.net` dans Domain management
