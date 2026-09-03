(function () {
  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var body = document.body;

  var clock = document.getElementById("clock");
  function tick() {
    var now = new Date();
    var hh = String(now.getHours()).padStart(2, "0");
    var mm = String(now.getMinutes()).padStart(2, "0");
    var ss = String(now.getSeconds()).padStart(2, "0");
    var stamp = hh + ":" + mm + ":" + ss;
    if (clock) {
      clock.textContent = stamp;
      clock.setAttribute("datetime", now.toISOString());
    }
  }
  tick();
  setInterval(tick, 1000);

  var fill = document.getElementById("t-fill");
  var head = document.getElementById("t-head");
  var bar = document.getElementById("t-bar");
  var elapsed = document.getElementById("t-elapsed");
  var totalEl = document.getElementById("t-total");

  function fmt(sec) {
    var m = Math.floor(sec / 60);
    var s = String(Math.floor(sec % 60)).padStart(2, "0");
    return m + ":" + s;
  }

  function pageProgress() {
    var doc = document.documentElement;
    var max = doc.scrollHeight - window.innerHeight;
    var pct = max <= 0 ? 0 : Math.min(1, Math.max(0, window.scrollY / max));
    if (fill) fill.style.width = pct * 100 + "%";
    if (head) head.style.left = pct * 100 + "%";
    if (bar) bar.setAttribute("aria-valuenow", String(Math.round(pct * 100)));
    if (elapsed) elapsed.textContent = fmt(pct * 60);
    if (totalEl) totalEl.textContent = "1:00";
    return pct;
  }
  pageProgress();
  window.addEventListener("scroll", pageProgress, { passive: true });
  window.addEventListener("resize", pageProgress);

  if (bar) {
    bar.addEventListener("click", function (e) {
      var rect = bar.getBoundingClientRect();
      var pct = Math.min(1, Math.max(0, (e.clientX - rect.left) / rect.width));
      var max = document.documentElement.scrollHeight - window.innerHeight;
      window.scrollTo({ top: pct * max, behavior: reduce ? "auto" : "smooth" });
    });
  }

  var chapters = Array.prototype.slice.call(document.querySelectorAll(".timeline .chapter"));
  function goChapter(offset) {
    var current = chapters.findIndex(function (el) { return el.classList.contains("active"); });
    if (current < 0) current = 0;
    var next = chapters[current + offset];
    if (next) window.location.href = next.getAttribute("href");
  }
  var prev = document.getElementById("btn-prev");
  var next = document.getElementById("btn-next");
  if (prev) prev.addEventListener("click", function () { goChapter(-1); });
  if (next) next.addEventListener("click", function () { goChapter(1); });

  function setPlaying(on) {
    body.classList.toggle("is-playing", on);
    var btn = document.getElementById("btn-play");
    if (btn) btn.setAttribute("aria-label", on ? "Mettre la session en pause" : "Lancer la session");
  }
  var play = document.getElementById("btn-play");
  if (play) {
    play.addEventListener("click", function () {
      setPlaying(!body.classList.contains("is-playing"));
    });
  }

  var heroPlay = document.getElementById("hero-play");
  if (heroPlay) {
    heroPlay.addEventListener("click", function () {
      setPlaying(true);
      var target = document.getElementById("composer");
      if (target) target.scrollIntoView({ behavior: reduce ? "auto" : "smooth", block: "start" });
    });
  }

  function initComposer() {
    var root = document.querySelector("[data-composer]");
    if (!root) return;

    var dataEl = document.getElementById("composer-data");
    var data = { eventTypes: [], tracks: [], addons: [], formules: [] };
    if (dataEl) {
      try {
        data = JSON.parse(dataEl.textContent);
      } catch (e) {}
    }

    var eventTypes = data.eventTypes || [];
    var addons = data.addons || [];
    var formules = data.formules || [];

    var typeById = {};
    eventTypes.forEach(function (t) {
      typeById[t.id] = t;
    });

    var formuleBySlug = {};
    formules.forEach(function (f) {
      formuleBySlug[f.slug] = f;
    });

    var TRACK_LABELS = {};
    (data.tracks || []).forEach(function (t) {
      TRACK_LABELS[t.id] = t.label.toLowerCase();
    });
    if (!TRACK_LABELS.lumiere) {
      TRACK_LABELS = {
        lumiere: "lumière",
        mapping: "mapping",
        son: "son",
        effets: "effets",
      };
    }

    var titleEl = root.querySelector("[data-composer-title]");
    var packEl = root.querySelector("[data-composer-pack]");
    var pistesEl = root.querySelector("[data-composer-pistes]");
    var addonsEl = root.querySelector("[data-composer-addons]");
    var hintEl = root.querySelector("[data-composer-hint]");
    var devisLink = root.querySelector("[data-composer-devis]");
    var secondaryLink = root.querySelector("[data-composer-secondary]");
    var typeInputs = root.querySelectorAll('input[name="composer-type"]');
    var trackInputs = root.querySelectorAll('input[name="composer-track"]');

    var applyingDefaults = false;

    function selectedType() {
      var checked = root.querySelector('input[name="composer-type"]:checked');
      var id = (checked && checked.value) || "mariage";
      return typeById[id] || eventTypes[0] || null;
    }

    function activeTracks() {
      return Array.prototype.slice
        .call(trackInputs)
        .filter(function (el) {
          return el.checked;
        })
        .map(function (el) {
          return el.value;
        });
    }

    function applyDefaults(keys) {
      applyingDefaults = true;
      Array.prototype.forEach.call(trackInputs, function (el) {
        el.checked = keys.indexOf(el.value) !== -1;
        var row = el.closest(".track-row");
        if (row) row.classList.toggle("on", el.checked);
      });
      applyingDefaults = false;
    }

    function syncTrackRows() {
      Array.prototype.forEach.call(trackInputs, function (el) {
        var row = el.closest(".track-row");
        if (row) row.classList.toggle("on", el.checked);
      });
    }

    function findRecommendedPack(formule, type) {
      if (!formule || !formule.packs || !formule.packs.length) return null;
      var name = type && type.recommendedPack;
      if (name) {
        for (var i = 0; i < formule.packs.length; i++) {
          if (formule.packs[i].nom === name) return formule.packs[i];
        }
      }
      for (var j = 0; j < formule.packs.length; j++) {
        if (formule.packs[j].featured) return formule.packs[j];
      }
      return formule.packs[0];
    }

    function packIncludesAddon(pack, addon) {
      if (!pack || !pack.items || !addon) return false;
      var needle = (addon.label || "").toLowerCase();
      var id = addon.id || "";
      return pack.items.some(function (item) {
        var t = String(item).toLowerCase();
        if (id === "animateur" && t.indexOf("animateur") !== -1) return true;
        if (id === "effets" && (t.indexOf("étincelle") !== -1 || t.indexOf("fumée") !== -1 || t.indexOf("geyser") !== -1 || t.indexOf("brouillard") !== -1)) return true;
        if (id === "ceremonie" && t.indexOf("cérémonie") !== -1) return true;
        if (id === "technicien" && t.indexOf("montage") !== -1) return true;
        return needle && t.indexOf(needle) !== -1;
      });
    }

    function suggestedAddons(type, tracks, pack) {
      var typeId = type ? type.id : "";
      return addons.filter(function (addon) {
        if (addon.eventTypes && addon.eventTypes.length && addon.eventTypes.indexOf(typeId) === -1) {
          return false;
        }
        if (addon.alwaysSuggest) return true;
        var addonTracks = addon.tracks || [];
        if (!addonTracks.length) return false;
        var hit = addonTracks.some(function (t) {
          return tracks.indexOf(t) !== -1;
        });
        if (!hit) return false;
        if (addon.horsPack) return true;
        return !packIncludesAddon(pack, addon);
      });
    }

    function renderAddons(list) {
      if (!addonsEl) return;
      addonsEl.innerHTML = "";
      if (!list.length) return;
      var intro = document.createElement("li");
      intro.className = "composer-addons-label";
      intro.textContent = "Options à discuter";
      addonsEl.appendChild(intro);
      list.slice(0, 4).forEach(function (addon) {
        var li = document.createElement("li");
        li.textContent = addon.label + " — " + addon.pricing;
        addonsEl.appendChild(li);
      });
    }

    function update() {
      var type = selectedType();
      if (!type) return;
      var tracks = activeTracks();
      var pisteLabels = tracks.map(function (t) {
        return TRACK_LABELS[t] || t;
      });
      var pisteText =
        pisteLabels.length > 0
          ? "Pistes : " + pisteLabels.join(", ")
          : "Aucune piste sélectionnée";

      var formule = type.formuleSlug ? formuleBySlug[type.formuleSlug] : null;
      var pack = findRecommendedPack(formule, type);

      if (titleEl) {
        titleEl.textContent = formule ? "Formule " + formule.titre : type.label;
      }
      if (packEl) {
        if (pack) {
          packEl.textContent = pack.nom + " — à partir de " + pack.prix;
        } else if (type.id === "location") {
          packEl.textContent = "Location à la journée — tarifs catalogue";
        } else {
          packEl.textContent = "Prestation sur devis personnalisé";
        }
      }
      if (pistesEl) pistesEl.textContent = pisteText;
      if (hintEl) hintEl.textContent = type.blurb || "";

      renderAddons(suggestedAddons(type, tracks, pack));
      syncTrackRows();

      if (secondaryLink) {
        if (formule) {
          secondaryLink.href = "/nos-formules/" + formule.slug + "/";
          secondaryLink.textContent = "Voir la formule";
          secondaryLink.hidden = false;
        } else if (type.locationUrl) {
          secondaryLink.href = type.locationUrl;
          secondaryLink.textContent = "Ouvrir le catalogue";
          secondaryLink.hidden = false;
        } else if (type.id === "mapping") {
          secondaryLink.href = "/portfolio/";
          secondaryLink.textContent = "Voir le portfolio";
          secondaryLink.hidden = false;
        } else {
          secondaryLink.hidden = true;
        }
      }

      if (devisLink) {
        var params = new URLSearchParams();
        params.set("type", type.devisType || type.label);
        if (tracks.length) params.set("pistes", tracks.join(","));
        devisLink.href = "/devis/?" + params.toString();
      }
    }

    Array.prototype.forEach.call(typeInputs, function (el) {
      el.addEventListener("change", function () {
        var type = selectedType();
        applyDefaults((type && type.defaultTracks) || []);
        update();
      });
    });

    Array.prototype.forEach.call(trackInputs, function (el) {
      el.addEventListener("change", function () {
        if (applyingDefaults) return;
        update();
      });
    });

    var initial = selectedType();
    if (initial && initial.defaultTracks) applyDefaults(initial.defaultTracks);
    update();
  }

  var TYPE_ALIASES = {
    mariage: "Mariage",
    anniversaire: "Anniversaire",
    "comite-entreprise": "Comité d'entreprise",
    ce: "Comité d'entreprise",
    "manifestation-sportive": "Manifestation sportive",
    sport: "Manifestation sportive",
    location: "Location de matériel",
    mapping: "Vidéo mapping",
  };

  function initDevisPrefill() {
    var typeSelect = document.getElementById("devis-type");
    var message = document.getElementById("devis-message");
    if (!typeSelect && !message) return;

    var params = new URLSearchParams(window.location.search);
    var type = params.get("type");
    var pistes = params.get("pistes");

    if (type && typeSelect) {
      var resolved = TYPE_ALIASES[type] || type;
      var match = Array.prototype.find.call(typeSelect.options, function (opt) {
        return opt.value === resolved || opt.value === type || opt.textContent === resolved || opt.textContent === type;
      });
      if (match) typeSelect.value = match.value;
      type = match ? match.value : resolved;
    }

    if (message && (type || pistes) && !message.value) {
      var pisteList = (pistes || "")
        .split(",")
        .map(function (s) {
          return s.trim();
        })
        .filter(Boolean);
      var pisteLabels = {
        lumiere: "lumière",
        mapping: "mapping",
        son: "son",
        effets: "effets",
      };
      var pisteText = pisteList
        .map(function (p) {
          return pisteLabels[p] || p;
        })
        .join(", ");
      var lines = ["Projet composé sur le site Event'Light."];
      if (type) lines.push("Type d'événement : " + type + ".");
      if (pisteText) lines.push("Pistes souhaitées : " + pisteText + ".");
      lines.push("Merci de me confirmer disponibilité, jauge et lieu.");
      message.value = lines.join("\n");
    }
  }

  initComposer();
  initDevisPrefill();

  function scrub(e, frame) {
    var rect = frame.getBoundingClientRect();
    var pct = Math.min(1, Math.max(0, (e.clientX - rect.left) / rect.width));
    var playhead = frame.querySelector(".playhead");
    var time = frame.querySelector(".time");
    if (playhead) playhead.style.left = pct * 100 + "%";
    frame.style.backgroundPosition = pct * 100 + "% 50%";
    var duration = parseInt(frame.getAttribute("data-duration"), 10) || 0;
    var current = Math.round(pct * duration);
    if (time) {
      var mm = Math.floor(current / 60);
      var ss = String(current % 60).padStart(2, "0");
      time.textContent = mm + ":" + ss;
    }
  }

  function resetScrub(frame) {
    var playhead = frame.querySelector(".playhead");
    var time = frame.querySelector(".time");
    if (playhead) playhead.style.left = "0%";
    frame.style.backgroundPosition = "0% 50%";
    if (time) time.textContent = "0:00";
  }

  window.scrub = scrub;
  window.resetScrub = resetScrub;

  document.querySelectorAll(".frame[data-duration]").forEach(function (frame) {
    frame.addEventListener("mousemove", function (e) { scrub(e, frame); });
    frame.addEventListener("mouseleave", function () { resetScrub(frame); });
  });
})();
