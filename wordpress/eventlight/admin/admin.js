/* Event'Light : écrans de l'administration (photos, lignes de matériel, disponibilités, import). */
(function ($) {
  "use strict";

  /* ------------------------------------------------------------ photos de la médiathèque */

  function majPhotos($bloc) {
    var ids = $bloc
      .find(".el-photos-liste li")
      .map(function () {
        return $(this).data("id");
      })
      .get();
    $bloc.find('input[type="hidden"]').val(ids.join(","));
  }

  $(".el-photos").each(function () {
    var $bloc = $(this);
    var multiple = String($bloc.data("multiple")) === "1";
    var $liste = $bloc.find(".el-photos-liste");
    var cadre = null;

    if (multiple && $.fn.sortable) {
      $liste.sortable({
        items: "li",
        tolerance: "pointer",
        update: function () {
          majPhotos($bloc);
        }
      });
    }

    $bloc.on("click", ".el-photos-ajouter", function () {
      if (!window.wp || !wp.media) return;
      if (!cadre) {
        cadre = wp.media({
          title: multiple ? "Choisir des photos" : "Choisir une image",
          button: { text: multiple ? "Ajouter ces photos" : "Choisir cette image" },
          library: { type: "image" },
          multiple: multiple
        });
        cadre.on("select", function () {
          var choix = cadre.state().get("selection").toJSON();
          if (!multiple) $liste.empty();
          choix.forEach(function (a) {
            if ($liste.find('li[data-id="' + a.id + '"]').length) return;
            var tailles = a.sizes || {};
            var adresse = (tailles.medium || tailles.thumbnail || tailles.full || {}).url || a.url;
            $('<li><img alt=""><button type="button" class="el-photos-retirer" aria-label="Retirer cette photo">&times;</button></li>')
              .attr("data-id", a.id)
              .find("img")
              .attr("src", adresse)
              .end()
              .appendTo($liste);
          });
          majPhotos($bloc);
        });
      }
      cadre.open();
    });

    $bloc.on("click", ".el-photos-retirer", function () {
      $(this).closest("li").remove();
      majPhotos($bloc);
    });
  });

  /* ------------------------------------------------------------ lignes de matériel */

  function ajouterLigne($bloc, produit, quantite) {
    var rang = Number($bloc.attr("data-suivant")) || 0;
    $bloc.attr("data-suivant", rang + 1);
    var $ligne = $($bloc.find(".el-ligne-modele").html().replace(/__i__/g, String(rang)));
    if (produit) $ligne.find("select").val(String(produit));
    if (quantite) $ligne.find('input[type="number"]').val(quantite);
    $bloc.find("tbody").append($ligne);
    return $ligne;
  }

  $(document).on("click", ".el-ligne-ajouter", function () {
    ajouterLigne($(this).closest(".el-lignes")).find("select").trigger("focus");
  });
  $(document).on("click", ".el-ligne-retirer", function () {
    var $bloc = $(this).closest(".el-lignes");
    $(this).closest("tr").remove();
    $bloc.trigger("el:lignes");
  });

  /* ------------------------------------------------------------ fiche d'un dossier : disponibilités */

  var $materiel = $("#el-dossier-materiel");
  if ($materiel.length && window.elAdmin) {
    var $lignes = $materiel.find(".el-lignes");
    var $periode = $materiel.find(".el-dispo-periode");
    var $debut = $("#el-debut");
    var $fin = $("#el-fin");
    var dispos = null;
    var minuterie;

    var afficher = function () {
      $lignes.find("tr.el-ligne").each(function () {
        var $l = $(this);
        var produit = $l.find("select").val();
        var q = Number($l.find('input[type="number"]').val()) || 0;
        var $cellule = $l.find(".el-ligne-dispo").removeClass("el-manque el-tendu").text("");
        if (!dispos || !produit || produit === "0") return;
        var d = dispos[produit];
        if (!d) {
          $cellule.text("Stock non suivi");
          return;
        }
        var reste = d.dispo;
        var texte = Math.max(reste, 0) + " sur " + d.stock + (reste === 1 ? " disponible" : " disponibles");
        if (q > reste) {
          texte += ", il en manque " + (q - Math.max(reste, 0));
          $cellule.addClass("el-manque");
        } else if (d.attente > 0 && q + d.attente > reste) {
          $cellule.addClass("el-tendu");
        }
        if (d.attente > 0) texte += " (" + d.attente + (d.attente === 1 ? " demandé" : " demandés") + " ailleurs, en attente)";
        $cellule.text(texte);
      });
    };

    var charger = function () {
      if (!$debut.val()) {
        dispos = null;
        $periode.text("Indiquez les dates du dossier pour voir ce qui est disponible.");
        afficher();
        return;
      }
      $.post(elAdmin.ajax, {
        action: "el_dispo",
        jeton: elAdmin.jeton,
        debut: $debut.val(),
        fin: $fin.val(),
        sauf: $periode.data("sauf")
      }).done(function (r) {
        if (!r || !r.success) return;
        dispos = r.data.dispos || null;
        if (r.data.periode) $periode.text("Disponibilités " + r.data.periode + ", sans compter ce dossier.");
        afficher();
      });
    };

    $debut.add($fin).on("change", function () {
      clearTimeout(minuterie);
      minuterie = setTimeout(charger, 250);
    });
    $lignes.on("change input", "select, input", afficher).on("el:lignes", afficher);
    charger();

    // Reprendre le matériel d'un pack.
    $materiel.on("click", ".el-pack-ajouter", function () {
      var packs;
      try {
        packs = JSON.parse($("#el-packs-materiel").text());
      } catch (e) {
        return;
      }
      var pack = packs[Number($("#el-pack-choix").val())];
      if (!pack) return;
      pack.lignes.forEach(function (l) {
        var $existante = $lignes.find("tr.el-ligne").filter(function () {
          return $(this).find("select").val() === String(l.produit);
        });
        if ($existante.length) {
          var $q = $existante.first().find('input[type="number"]');
          $q.val((Number($q.val()) || 0) + l.q);
        } else {
          ajouterLigne($lignes, l.produit, l.q);
        }
      });
      afficher();
    });
  }

  /* ------------------------------------------------------------ import du contenu */

  var $lancer = $("#el-import-lancer");
  if ($lancer.length && window.elAdmin) {
    var $suivi = $(".el-import-suivi");
    var $barre = $("#el-import-barre");
    var $etat = $("#el-import-etat");
    var $erreurs = $("#el-import-erreurs");
    var $notes = $("#el-import-notes");
    var essais = 0;

    var avancer = function (rang) {
      $.post(elAdmin.ajax, {
        action: "el_import",
        jeton: elAdmin.jeton,
        rang: rang,
        permaliens: $("#el-import-permaliens").is(":checked") ? 1 : 0
      })
        .done(function (r) {
          if (!r || !r.success) {
            $etat.text((r && r.data && r.data.message) || "L'import s'est arrêté. Rechargez la page et relancez-le : il reprendra où il en était.");
            $lancer.prop("disabled", false);
            return;
          }
          essais = 0;
          var d = r.data;
          (d.erreurs || []).forEach(function (e) {
            $("<li>").text(e).appendTo($erreurs);
          });
          (d.notes || []).forEach(function (n) {
            $("<li>").text(n).appendTo($notes);
          });
          $barre.val(Math.round((d.rang / d.total) * 100));
          if (d.fini) {
            $etat.text($erreurs.children().length ? "Import terminé, sauf pour les éléments ci-dessous." : "Import terminé.");
            $(".el-import-fin").prop("hidden", false);
            return;
          }
          $etat.text("Import en cours : étape " + d.rang + " sur " + d.total + ". Gardez cette page ouverte.");
          avancer(d.rang);
        })
        .fail(function () {
          // Une coupure passagère : on réessaie la même étape, trois fois au plus.
          essais += 1;
          if (essais <= 3) {
            $etat.text("Le serveur n'a pas répondu, nouvel essai…");
            setTimeout(function () {
              avancer(rang);
            }, 2500);
          } else {
            $etat.text("Le serveur ne répond plus. Rechargez la page et relancez l'import : il reprendra où il en était.");
            $lancer.prop("disabled", false);
          }
        });
    };

    $lancer.on("click", function () {
      $lancer.prop("disabled", true);
      $suivi.prop("hidden", false);
      $erreurs.empty();
      $notes.empty();
      $etat.text("Import en cours…");
      avancer(0);
    });
  }
})(jQuery);
