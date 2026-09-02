(function () {
  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var root = document.documentElement;
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

  if (!reduce) {
    window.addEventListener("mousemove", function (e) {
      root.style.setProperty("--mx", e.clientX + "px");
      root.style.setProperty("--my", e.clientY + "px");
      root.style.setProperty("--hue", (e.clientX / window.innerWidth) * 260 - 30 + "deg");
      root.style.setProperty("--shift", ((e.clientX / window.innerWidth) - 0.5) * 8 + "px");
    });
  }

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

  window.toggleTrack = function (layer, active, checkbox) {
    var el = document.querySelector('.layer2[data-layer="' + layer + '"]');
    if (el) el.classList.toggle("on", active);
    if (checkbox) {
      var row = checkbox.closest(".track-row");
      if (row) row.classList.toggle("on", active);
    }
  };

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
  };

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
