/* Event'Light — comportements du site. Aucun module externe, tout dégrade proprement sans JavaScript. */
(function () {
  "use strict";

  var doc = document;
  var root = doc.documentElement;
  var calme = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function $(sel, ctx) {
    return (ctx || doc).querySelector(sel);
  }
  function $$(sel, ctx) {
    return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel));
  }
  // Mémoire du navigateur. Quand elle est refusée (navigation privée, page servie dans un cadre),
  // on se rabat sur window.name, qui survit aux changements de page dans le même onglet.
  // On n'y touche que s'il est vide ou déjà à nous.
  var secours = { el: 1 };
  var nomLibre = false;
  try {
    if (!window.name) nomLibre = true;
    else {
      var deja = JSON.parse(window.name);
      if (deja && deja.el === 1) {
        secours = deja;
        nomLibre = true;
      }
    }
  } catch (e) {}
  function coffre(session) {
    return session ? window.sessionStorage : window.localStorage;
  }
  function lire(cle, session) {
    try {
      var v = coffre(session).getItem(cle);
      if (v !== null) return v;
    } catch (e) {}
    return Object.prototype.hasOwnProperty.call(secours, cle) ? secours[cle] : null;
  }
  function ecrire(cle, valeur, session) {
    try {
      if (valeur === null) coffre(session).removeItem(cle);
      else coffre(session).setItem(cle, valeur);
      if (!Object.prototype.hasOwnProperty.call(secours, cle)) return;
    } catch (e) {}
    if (valeur === null) delete secours[cle];
    else secours[cle] = valeur;
    try {
      if (nomLibre) window.name = JSON.stringify(secours);
    } catch (e) {}
  }

  // Sur le site, les liens sont absolus ("/devis/"). Dans un aperçu servi depuis un sous-dossier,
  // la page indique son préfixe dans data-base et les liens deviennent relatifs.
  var BASE = root.getAttribute("data-base");
  function lien(chemin) {
    if (BASE === null) return chemin;
    var p = chemin.replace(/^\//, "");
    var suite = "";
    var i = p.search(/[?#]/);
    if (i >= 0) {
      suite = p.slice(i);
      p = p.slice(0, i);
    }
    return BASE + p + (p === "" || /\/$/.test(p) ? "index.html" : "") + suite;
  }

  // Les choix faits avant d'arriver sur le devis (type, pack, pistes) voyagent dans l'adresse,
  // et en double dans la session pour les contextes où l'adresse perd ses paramètres.
  doc.addEventListener("click", function (e) {
    var a = e.target.closest ? e.target.closest('a[href*="devis/"]') : null;
    if (!a) return;
    var q = a.getAttribute("href").split("?")[1];
    ecrire("el-devis", q ? q.split("#")[0] : null, true);
  });

  /* ------------------------------------------------------------ la salle : allumée ou éteinte */

  function salleEteinte() {
    var t = root.getAttribute("data-theme");
    if (t) return t === "dark";
    return window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
  }
  function majSalle() {
    var eteinte = salleEteinte();
    $$("[data-salle]").forEach(function (b) {
      b.textContent = eteinte ? "Rallumer la salle" : "Éteindre la salle";
      b.setAttribute("aria-pressed", eteinte ? "true" : "false");
      b.setAttribute("title", eteinte ? "Passer au thème clair" : "Passer au thème sombre");
    });
  }
  $$("[data-salle]").forEach(function (b) {
    b.addEventListener("click", function () {
      var suivant = salleEteinte() ? "light" : "dark";
      root.setAttribute("data-theme", suivant);
      ecrire("el-salle", suivant);
      majSalle();
    });
  });
  // En temps normal l'en-tête de page a déjà appliqué le choix du visiteur, avant le premier affichage.
  // Ici, on rattrape les contextes où ce n'est pas le cas (mémoire de secours, aperçu).
  var choixSalle = lire("el-salle");
  if (!root.getAttribute("data-theme") && (choixSalle === "dark" || choixSalle === "light")) {
    root.setAttribute("data-theme", choixSalle);
  }
  majSalle();
  if (window.matchMedia) {
    var mq = window.matchMedia("(prefers-color-scheme: dark)");
    if (mq.addEventListener) mq.addEventListener("change", majSalle);
  }
  // L'hôte d'un aperçu peut changer le thème de son côté : on suit.
  if (window.MutationObserver) {
    new MutationObserver(majSalle).observe(root, { attributes: true, attributeFilter: ["data-theme"] });
  }

  /* ------------------------------------------------------------ menu mobile */

  var burger = $("[data-burger]");
  var nav = $("#nav");
  if (burger && nav) {
    burger.addEventListener("click", function () {
      var ouvert = nav.classList.toggle("is-open");
      burger.setAttribute("aria-expanded", ouvert ? "true" : "false");
      burger.textContent = ouvert ? "Fermer" : "Menu";
    });
    doc.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && nav.classList.contains("is-open")) {
        nav.classList.remove("is-open");
        burger.setAttribute("aria-expanded", "false");
        burger.textContent = "Menu";
        burger.focus();
      }
    });
  }

  /* ------------------------------------------------------------ message bref */

  var toast = $("#toast");
  var toastTimer;
  function annoncer(html) {
    if (!toast) return;
    toast.innerHTML = html;
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () {
      toast.hidden = true;
    }, 4200);
  }

  /* ------------------------------------------------------------ ouverture : changer l'image projetée */

  (function () {
    var stage = $("[data-hero]");
    if (!stage) return;
    var shots = $$(".hero-shot", stage);
    var boutons = $$("[data-scene-btn]", stage);
    var cap = $("[data-hero-cap]", stage);
    var occupe = false;

    function montrer(id) {
      shots.forEach(function (img) {
        var on = img.getAttribute("data-scene") === id;
        if (on && img.loading === "lazy") img.loading = "eager";
        img.classList.toggle("is-on", on);
      });
      boutons.forEach(function (b) {
        var on = b.getAttribute("data-scene-btn") === id;
        b.setAttribute("aria-pressed", on ? "true" : "false");
        if (on && cap) {
          cap.textContent = b.getAttribute("data-legende");
          cap.setAttribute("href", b.getAttribute("data-url"));
        }
      });
    }

    boutons.forEach(function (b) {
      b.addEventListener("click", function () {
        var id = b.getAttribute("data-scene-btn");
        if (occupe || b.getAttribute("aria-pressed") === "true") return;
        if (calme) return montrer(id);
        occupe = true;
        stage.classList.remove("is-opening");
        stage.classList.add("is-closing");
        setTimeout(function () {
          montrer(id);
          stage.classList.remove("is-closing");
          stage.classList.add("is-opening");
          setTimeout(function () {
            stage.classList.remove("is-opening");
            occupe = false;
          }, 600);
        }, 190);
      });
    });
  })();

  /* ------------------------------------------------------------ composeur */

  (function () {
    var bloc = $("[data-composer]");
    var source = $("#composer-data");
    if (!bloc || !source) return;
    var data;
    try {
      data = JSON.parse(source.textContent);
    } catch (e) {
      return;
    }

    var COUCHES = {
      lumiere: ["totems-2", "totems-4", "brouillard", "archi"],
      son: ["son", "ondes", "micro", "pupitre", "animateur", "ceremonie"],
      effets: ["etincelles", "fumee", "geysers"],
      mapping: ["mapping"],
    };
    var DEFAUT = { lumiere: ["totems-4", "brouillard"], son: ["son"], effets: ["etincelles"], mapping: ["mapping"] };

    var types = {};
    data.eventTypes.forEach(function (t) {
      types[t.id] = t;
    });
    var formules = {};
    data.formules.forEach(function (f) {
      formules[f.slug] = f;
    });
    var libelles = {};
    data.tracks.forEach(function (t) {
      libelles[t.id] = t.label.toLowerCase();
    });

    var titre = $("[data-composer-title]", bloc);
    var packEl = $("[data-composer-pack]", bloc);
    var hint = $("[data-composer-hint]", bloc);
    var options = $("[data-composer-addons]", bloc);
    var lienDevis = $("[data-composer-devis]", bloc);
    var lienSecond = $("[data-composer-secondary]", bloc);
    var plan = $("[data-composer-plan] svg", bloc);
    var radios = $$('input[name="composer-type"]', bloc);
    var cases = $$("input[data-piste]", bloc);

    function typeChoisi() {
      var r = radios.filter(function (el) {
        return el.checked;
      })[0];
      return types[(r && r.value) || "mariage"] || data.eventTypes[0];
    }
    function pistes() {
      return cases
        .filter(function (el) {
          return el.checked;
        })
        .map(function (el) {
          return el.value;
        });
    }
    function packConseille(formule, type) {
      if (!formule) return null;
      var i;
      for (i = 0; i < formule.packs.length; i++) if (formule.packs[i].nom === type.recommendedPack) return formule.packs[i];
      for (i = 0; i < formule.packs.length; i++) if (formule.packs[i].featured) return formule.packs[i];
      return formule.packs[0];
    }
    function contient(pack, option) {
      if (!pack) return false;
      return pack.items.some(function (item) {
        var t = String(item).toLowerCase();
        if (option.id === "animateur") return t.indexOf("animateur") !== -1;
        if (option.id === "effets") return /étincelle|fumée|geyser|confetti/.test(t);
        if (option.id === "ceremonie") return t.indexOf("cérémonie") !== -1;
        if (option.id === "technicien") return t.indexOf("montage") !== -1;
        return t.indexOf(String(option.label).toLowerCase()) !== -1;
      });
    }
    function optionsUtiles(type, actives, pack) {
      return data.addons.filter(function (o) {
        if (o.eventTypes && o.eventTypes.length && o.eventTypes.indexOf(type.id) === -1) return false;
        if (o.alwaysSuggest) return true;
        var touche = (o.tracks || []).some(function (t) {
          return actives.indexOf(t) !== -1;
        });
        if (!touche) return false;
        return o.horsPack ? true : !contient(pack, o);
      });
    }
    function dessiner(actives, pack) {
      if (!plan) return;
      var voulues = {};
      actives.forEach(function (piste) {
        var duPack = pack
          ? pack.couches.filter(function (c) {
              return COUCHES[piste].indexOf(c) !== -1;
            })
          : [];
        (duPack.length ? duPack : DEFAUT[piste]).forEach(function (c) {
          voulues[c] = true;
        });
      });
      $$("[data-couche]", plan).forEach(function (g) {
        var on = Boolean(voulues[g.getAttribute("data-couche")]);
        if (on) g.removeAttribute("hidden");
        else g.setAttribute("hidden", "");
      });
    }

    function maj() {
      var type = typeChoisi();
      var actives = pistes();
      var formule = type.formuleSlug ? formules[type.formuleSlug] : null;
      var pack = packConseille(formule, type);

      titre.textContent = formule ? "Formule " + formule.titre : type.label;
      if (pack) packEl.textContent = pack.nom + ", à partir de " + pack.prix.replace(" €", " €");
      else if (type.id === "location") packEl.textContent = "Location à la journée, tarifs du catalogue";
      else packEl.textContent = "Prestation sur devis";
      hint.textContent = type.blurb || "";

      options.innerHTML = "";
      optionsUtiles(type, actives, pack)
        .slice(0, 4)
        .forEach(function (o) {
          var li = doc.createElement("li");
          li.textContent = o.label + "\u00a0: " + (o.pricing.charAt(0).toLowerCase() + o.pricing.slice(1)).replace(/ €/g, "\u00a0€");
          options.appendChild(li);
        });

      dessiner(actives, pack);

      if (formule) {
        lienSecond.href = lien("/nos-formules/" + formule.slug + "/");
        lienSecond.textContent = "Voir la formule";
      } else if (type.id === "location") {
        lienSecond.href = lien("/location/");
        lienSecond.textContent = "Ouvrir le catalogue";
      } else {
        lienSecond.href = lien("/video-mapping/");
        lienSecond.textContent = "Voir le vidéo mapping";
      }

      var params = ["type=" + encodeURIComponent(type.devisType || type.label)];
      if (actives.length) params.push("pistes=" + actives.join(","));
      if (pack) params.push("pack=" + encodeURIComponent(pack.nom));
      lienDevis.href = lien("/devis/?" + params.join("&"));
    }

    radios.forEach(function (el) {
      el.addEventListener("change", function () {
        var defaut = typeChoisi().defaultTracks || [];
        cases.forEach(function (c) {
          c.checked = defaut.indexOf(c.value) !== -1;
        });
        maj();
      });
    });
    cases.forEach(function (el) {
      el.addEventListener("change", maj);
    });
    maj();
  })();

  /* ------------------------------------------------------------ sélection de matériel (location) */

  var selection = (function () {
    var CLE = "el-selection";
    function tout() {
      try {
        var v = JSON.parse(lire(CLE) || "[]");
        return Array.isArray(v) ? v : [];
      } catch (e) {
        return [];
      }
    }
    function garder(liste) {
      ecrire(CLE, JSON.stringify(liste));
      majCompteur();
      doc.dispatchEvent(new CustomEvent("selection"));
    }
    function quantite(slug) {
      var l = tout().filter(function (x) {
        return x.slug === slug;
      })[0];
      return l ? l.q : 0;
    }
    function regler(item, q) {
      var liste = tout().filter(function (x) {
        return x.slug !== item.slug;
      });
      if (q > 0) liste.push({ slug: item.slug, nom: item.nom, prix: item.prix, q: Math.min(q, 99) });
      garder(liste);
    }
    function vider() {
      garder([]);
    }
    function nombre() {
      return tout().reduce(function (n, x) {
        return n + x.q;
      }, 0);
    }
    function majCompteur() {
      var n = nombre();
      $$("[data-selection-link]").forEach(function (a) {
        a.hidden = n === 0;
      });
      $$("[data-selection-count]").forEach(function (s) {
        s.textContent = String(n);
      });
    }
    majCompteur();
    return { tout: tout, quantite: quantite, regler: regler, vider: vider, nombre: nombre };
  })();

  function prixNombre(prix) {
    var m = String(prix || "").replace(",", ".").match(/[\d.]+/);
    return m ? Number(m[0]) : 0;
  }
  function euros(n) {
    return (Math.round(n * 100) / 100).toString().replace(".", ",") + " €";
  }

  function majBoutonsAjout() {
    $$("[data-add]").forEach(function (b) {
      var q = selection.quantite(b.getAttribute("data-add"));
      b.classList.toggle("is-in", q > 0);
      b.textContent = q > 0 ? (b.getAttribute("data-label-in") || "Ajouté") + " (" + q + ")" : b.getAttribute("data-label") || "Ajouter";
    });
  }
  $$("[data-add]").forEach(function (b) {
    b.setAttribute("data-label", b.textContent.trim());
    b.addEventListener("click", function () {
      var item = { slug: b.getAttribute("data-add"), nom: b.getAttribute("data-nom"), prix: b.getAttribute("data-prix") };
      selection.regler(item, selection.quantite(item.slug) + 1);
      majBoutonsAjout();
      annoncer(item.nom + " ajouté à votre sélection. <a href=\"" + lien("/devis/#selection") + "\">Voir la sélection</a>");
    });
  });
  majBoutonsAjout();

  /* ------------------------------------------------------------ devis : sélection et pré-remplissage */

  (function () {
    var form = $("[data-devis]");
    if (!form) return;
    var zone = $("[data-selection]", form);
    var champ = $("#devis-materiel", form);
    var typeSel = $("#devis-type", form);
    var message = $("#devis-message", form);

    var typeImpose = false;
    function rendre() {
      if (!zone) return;
      var liste = selection.tout();
      zone.hidden = liste.length === 0;
      var ul = $("ul", zone);
      ul.innerHTML = "";
      var total = 0;
      var lignes = [];
      liste.forEach(function (x) {
        var unit = prixNombre(x.prix);
        total += unit * x.q;
        lignes.push(x.q + " x " + x.nom + " (" + x.prix + ")");
        var li = doc.createElement("li");
        li.innerHTML =
          "<span></span>" +
          '<span class="qty"><button type="button" data-q="-1" aria-label="Retirer un">−</button><output></output><button type="button" data-q="1" aria-label="Ajouter un">+</button></span>' +
          "<span></span>" +
          '<button type="button" class="link-btn" data-q="0">Retirer</button>';
        li.children[0].textContent = x.nom;
        $("output", li).textContent = String(x.q);
        li.children[2].textContent = euros(unit * x.q) + " / jour";
        $$("button", li).forEach(function (btn) {
          btn.addEventListener("click", function () {
            var d = Number(btn.getAttribute("data-q"));
            selection.regler(x, d === 0 ? 0 : x.q + d);
            rendre();
          });
        });
        ul.appendChild(li);
      });
      var t = $("[data-selection-total]", zone);
      if (t) t.textContent = euros(total) + " par jour";
      if (champ) champ.value = lignes.join("\n");
      if (liste.length && typeSel && !typeSel.dataset.touche && !typeImpose) {
        typeSel.value = "Location de matériel";
      }
    }
    var vider = $("[data-selection-vider]", form);
    if (vider) {
      vider.addEventListener("click", function () {
        selection.vider();
        rendre();
      });
    }
    if (typeSel) {
      typeSel.addEventListener("change", function () {
        typeSel.dataset.touche = "1";
      });
    }
    rendre();

    // Pré-remplissage depuis le composeur ou une page formule.
    var requete = window.location.search;
    if (!requete) requete = lire("el-devis", true) || "";
    ecrire("el-devis", null, true);
    var params = new URLSearchParams(requete);
    var type = params.get("type");
    var pack = params.get("pack");
    var pistesTxt = (params.get("pistes") || "")
      .split(",")
      .filter(Boolean)
      .map(function (p) {
        return { lumiere: "lumière", son: "son", effets: "effets", mapping: "mapping" }[p] || p;
      })
      .join(", ");
    if (type && typeSel) {
      $$("option", typeSel).forEach(function (o) {
        if (o.value === type) {
          typeSel.value = o.value;
          typeImpose = true;
        }
      });
    }
    if (message && !message.value && (pack || pistesTxt)) {
      var lignes = [];
      if (pack) lignes.push("Pack envisagé : " + pack + ".");
      if (pistesTxt) lignes.push("Ce qu'il me faut : " + pistesTxt + ".");
      message.value = lignes.join("\n") + "\n";
    }

    // Dans un aperçu sans serveur, le formulaire n'a nulle part où partir : on le dit.
    if (root.hasAttribute("data-apercu")) {
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        annoncer("Aperçu : sur le site en ligne, la demande part par Netlify Forms.");
      });
    }
  })();

  // La sélection n'est vidée qu'une fois la demande partie, sur la page de confirmation :
  // si l'envoi échoue, le visiteur retrouve son matériel en revenant au formulaire.
  if ($("[data-demande-envoyee]")) selection.vider();

  /* ------------------------------------------------------------ réalisations : filtres */

  (function () {
    var filtres = $$("[data-filtre]");
    if (!filtres.length) return;
    var cartes = $$("[data-type]");
    filtres.forEach(function (b) {
      b.addEventListener("click", function () {
        var v = b.getAttribute("data-filtre");
        filtres.forEach(function (x) {
          x.setAttribute("aria-pressed", x === b ? "true" : "false");
        });
        cartes.forEach(function (c) {
          c.hidden = v !== "tout" && c.getAttribute("data-type") !== v;
        });
      });
    });
  })();

  /* ------------------------------------------------------------ vidéo : chargée au clic */

  $$("[data-video]").forEach(function (bloc) {
    var bouton = $(".video-play", bloc);
    if (!bouton || root.hasAttribute("data-apercu")) return;
    bouton.addEventListener("click", function (e) {
      e.preventDefault();
      var id = bloc.getAttribute("data-video");
      var frame = doc.createElement("iframe");
      frame.src = "https://www.youtube-nocookie.com/embed/" + encodeURIComponent(id) + "?autoplay=1&rel=0";
      frame.title = bloc.getAttribute("data-titre") || "Vidéo";
      frame.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture";
      frame.allowFullscreen = true;
      bloc.appendChild(frame);
      bouton.remove();
    });
  });

  /* ------------------------------------------------------------ galerie plein écran */

  (function () {
    var liens = $$("[data-zoom]");
    var boite = $("#lightbox");
    if (!liens.length || !boite || !boite.showModal) return;
    var img = $("img", boite);
    var compte = $("[data-zoom-compte]", boite);
    var i = 0;
    function aller(n) {
      i = (n + liens.length) % liens.length;
      var a = liens[i];
      img.src = a.getAttribute("href");
      img.alt = a.getAttribute("data-alt") || "";
      if (compte) compte.textContent = i + 1 + " / " + liens.length;
    }
    liens.forEach(function (a, n) {
      a.addEventListener("click", function (e) {
        e.preventDefault();
        aller(n);
        boite.showModal();
      });
    });
    $$("[data-zoom-pas]", boite).forEach(function (b) {
      b.addEventListener("click", function () {
        aller(i + Number(b.getAttribute("data-zoom-pas")));
      });
    });
    $("[data-zoom-fermer]", boite).addEventListener("click", function () {
      boite.close();
    });
    boite.addEventListener("keydown", function (e) {
      if (e.key === "ArrowRight") aller(i + 1);
      if (e.key === "ArrowLeft") aller(i - 1);
    });
  })();

  /* ------------------------------------------------------------ fiche produit : vignettes */

  (function () {
    var grande = $("[data-photo]");
    var vignettes = $$("[data-vignette]");
    if (!grande || !vignettes.length) return;
    vignettes.forEach(function (b) {
      b.addEventListener("click", function () {
        var img = $("img", grande);
        img.src = b.getAttribute("data-vignette");
        img.removeAttribute("srcset");
        img.setAttribute("data-fond", b.getAttribute("data-fond") || "clair");
        vignettes.forEach(function (x) {
          x.setAttribute("aria-pressed", x === b ? "true" : "false");
        });
      });
    });
  })();
})();
