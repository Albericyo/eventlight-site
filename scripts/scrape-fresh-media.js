#!/usr/bin/env node
/**
 * Re-scrape with DOM-ordered content images; skip site chrome/logo.
 */
const fs = require("fs");
const path = require("path");
const crypto = require("crypto");
const { execFileSync } = require("child_process");
const puppeteer = require("puppeteer-core");

const ROOT = path.resolve(__dirname, "..");
const OUT = path.join(ROOT, "src", "images");

const SKIP_SIZES = new Set([279530, 376966, 181041]); // logos / chrome
const YT_FALLBACK = {
  pignan: "rQ2P5zFz1Do",
  "pba-2024": "qa9DIcC56KU",
  "saint-jean": "fPIcQxnWaJE",
  "adj-2021": "q7wRCDqIqtg",
  "gala-ffeo": "U9n7ctUEh30",
  "gare-saint-paul": "6vckeSXc_8Y",
  "le-pavillon-bleu": "KdpNwDgyiNs",
  "adj-2020": "q0p5miY9ufM",
  "la-maison-de-la-motte": "VYDlCmQyEbc",
  "mapping-halloween-2018": "HagrZQk-Jbc",
  "eurolite-led-pix-144-rgb-bar": "4qs5noXoh4Y",
  "eurolite-parled-sls-7-hcl-floor": "rtu1T7JhGF0",
  "evo-spark-600": "VedgKHWGKBs",
  "evolite-moving-beam1r": "URbd9yZEM6U",
  "fogspray-3000-rgb": "FHYWTfmiKF4",
  "jb-challenger-bsw": "-O-XPsAcURg",
  "mac-mah-lyre-wash-740z": "1RzpEb85y5M",
};

function loadSlugs() {
  const portfolio = JSON.parse(fs.readFileSync(path.join(ROOT, "src/_data/portfolio.json"), "utf8"));
  const produits = JSON.parse(fs.readFileSync(path.join(ROOT, "src/_data/produits.json"), "utf8"));
  return {
    portfolio: portfolio.map((p) => p.slug),
    location: produits.map((p) => p.slug),
  };
}

function chromePath() {
  for (const c of ["/usr/bin/chromium-browser", "/usr/bin/chromium", "/snap/bin/chromium"]) {
    if (fs.existsSync(c)) return c;
  }
  throw new Error("no chrome");
}

function extOf(buf, ct) {
  if (buf[0] === 0xff && buf[1] === 0xd8) return "jpg";
  if (buf[0] === 0x89 && buf[1] === 0x50) return "png";
  if (buf.length > 12 && buf.toString("ascii", 8, 12) === "WEBP") return "webp";
  if (ct && ct.includes("jpeg")) return "jpg";
  if (ct && ct.includes("png")) return "png";
  return "bin";
}

function sha1(buf) {
  return crypto.createHash("sha1").update(buf).digest("hex");
}

function canonUrl(url) {
  return String(url).split("?")[0].replace(/=w\d+$/, "").replace(/=s\d+[^/]*$/, "");
}

function clearDir(dir) {
  if (!fs.existsSync(dir)) return;
  for (const f of fs.readdirSync(dir)) {
    if (/\.(jpe?g|png|webp|gif|bin)$/i.test(f)) fs.unlinkSync(path.join(dir, f));
  }
}

function downloadYt(slug, kind, id) {
  if (!id) return null;
  const destDir = path.join(OUT, kind, slug);
  fs.mkdirSync(destDir, { recursive: true });
  const dest = path.join(destDir, "yt.jpg");
  for (const q of ["maxresdefault", "sddefault", "hqdefault"]) {
    try {
      execFileSync("curl", ["-fsSL", "-o", dest, `https://i.ytimg.com/vi/${id}/${q}.jpg`], {
        stdio: "ignore",
      });
      if (fs.existsSync(dest) && fs.statSync(dest).size > 2000) return "yt.jpg";
    } catch (_) {}
  }
  return null;
}

function isChromeBuf(buf, w, h) {
  if (!buf || buf.length < 5000) return true;
  if (SKIP_SIZES.has(buf.length)) return true;
  // large square logo-like PNGs
  if (w === 1600 && h === 1600 && buf[0] === 0x89) return true;
  return false;
}

async function scrapePage(browser, pagePath) {
  const url = `https://www.eventlight.net/${pagePath}`;
  const page = await browser.newPage();
  await page.setUserAgent(
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
  );
  await page.setViewport({ width: 1400, height: 1100 });

  const byCanon = new Map();

  page.on("response", async (res) => {
    try {
      const u = res.url();
      if (!/googleusercontent\.com\/sitesv\//.test(u)) return;
      if (res.status() !== 200) return;
      const ct = (res.headers()["content-type"] || "").toLowerCase();
      if (!ct.startsWith("image/")) return;
      const buf = await res.buffer();
      if (buf.length < 5000) return;
      const key = canonUrl(u);
      const prev = byCanon.get(key);
      if (!prev || buf.length > prev.buf.length) {
        byCanon.set(key, { buf, ct, url: u });
      }
    } catch (_) {}
  });

  let status = 0;
  let ytId = "";
  let ordered = [];
  try {
    const resp = await page.goto(url, { waitUntil: "domcontentloaded", timeout: 60000 });
    status = resp ? resp.status() : 0;
    if (status >= 400) {
      await page.close();
      return { page: pagePath, status, images: [], ytId: "" };
    }

    await page.evaluate(async () => {
      const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
      for (let y = 0; y < Math.max(document.body.scrollHeight, 2400); y += 320) {
        window.scrollTo(0, y);
        await sleep(160);
      }
      window.scrollTo(0, document.body.scrollHeight);
      await sleep(800);
      window.scrollTo(0, 0);
      await sleep(400);
    });
    await new Promise((r) => setTimeout(r, 2000));

    const meta = await page.evaluate(() => {
      const imgs = [];
      const seen = new Set();
      for (const img of document.querySelectorAll("img")) {
        const src = img.currentSrc || img.src || "";
        if (!src.includes("googleusercontent.com/sitesv/")) continue;
        const key = src.split("?")[0].replace(/=w\d+$/, "");
        if (seen.has(key)) continue;
        seen.add(key);
        imgs.push({
          src,
          key,
          w: img.naturalWidth || 0,
          h: img.naturalHeight || 0,
        });
      }
      let ytId = "";
      const iframe = document.querySelector("iframe[src*='youtube.com/embed/']");
      if (iframe) {
        const m = iframe.src.match(/embed\/([^?&/]+)/);
        if (m) ytId = m[1];
      }
      return { imgs, ytId };
    });

    ytId = meta.ytId;
    ordered = meta.imgs;
  } catch (e) {
    await page.close();
    return { page: pagePath, status: 0, error: String(e), images: [], ytId: "" };
  }

  await page.close();

  const images = [];
  const usedHash = new Set();
  for (const im of ordered) {
    const hit = byCanon.get(im.key) || byCanon.get(canonUrl(im.src));
    if (!hit) continue;
    if (isChromeBuf(hit.buf, im.w, im.h)) continue;
    // skip tiny icons unless nothing else
    if (im.w > 0 && im.h > 0 && im.w < 280 && im.h < 280) continue;
    const h = sha1(hit.buf);
    if (usedHash.has(h)) continue;
    usedHash.add(h);
    images.push({ ...hit, w: im.w, h: im.h, hash: h });
  }

  // Fallback: any captured non-chrome buffers not already included
  if (images.length === 0) {
    for (const hit of byCanon.values()) {
      if (isChromeBuf(hit.buf, 0, 0)) continue;
      const h = sha1(hit.buf);
      if (usedHash.has(h)) continue;
      usedHash.add(h);
      images.push({ ...hit, hash: h });
    }
    images.sort((a, b) => b.buf.length - a.buf.length);
  }

  return { page: pagePath, status, ytId, images };
}

function saveGallery(kind, slug, images) {
  const dir = path.join(OUT, kind, slug);
  fs.mkdirSync(dir, { recursive: true });
  // Preserve mariage restore if re-scrape empty
  const prev = fs.existsSync(dir)
    ? fs.readdirSync(dir).filter((f) => /\.(jpe?g|png|webp)$/i.test(f))
    : [];
  clearDir(dir);
  const saved = [];
  let i = 1;
  for (const item of images) {
    const ext = extOf(item.buf, item.ct);
    if (ext === "bin") continue;
    const name = String(i).padStart(2, "0") + "." + ext;
    fs.writeFileSync(path.join(dir, name), item.buf);
    saved.push(name);
    i++;
  }
  if (saved.length === 0 && prev.length && (slug === "mariage-2025-09-20" || slug === "soiree-halloween-2025")) {
    // shouldn't happen - mariage restored separately
  }
  return saved;
}

async function main() {
  const { portfolio, location } = loadSlugs();
  // Keep mariage cover
  const mariageKeep = path.join(OUT, "portfolio/mariage-2025-09-20/01.jpg");
  let mariageBuf = null;
  if (fs.existsSync(mariageKeep)) mariageBuf = fs.readFileSync(mariageKeep);

  const pages = [
    ...portfolio.map((s) => `portfolio/${s}`),
    ...location.map((s) => `location/${s}`),
  ];

  console.log(`Pages: ${pages.length}`);
  const browser = await puppeteer.launch({
    executablePath: chromePath(),
    headless: "new",
    args: ["--no-sandbox", "--disable-setuid-sandbox", "--disable-dev-shm-usage"],
  });

  const hashCount = new Map();
  const results = [];

  for (const pagePath of pages) {
    process.stdout.write(`→ ${pagePath} ... `);
    const result = await scrapePage(browser, pagePath);
    console.log(`HTTP ${result.status} imgs=${result.images.length} yt=${result.ytId || "-"}`);
    for (const im of result.images) {
      hashCount.set(im.hash, (hashCount.get(im.hash) || 0) + 1);
    }
    results.push(result);
  }

  // Hashes seen on 3+ pages = site chrome
  const chromeHashes = new Set(
    [...hashCount.entries()].filter(([, n]) => n >= 3).map(([h]) => h)
  );
  console.log("chrome hashes to strip:", chromeHashes.size);

  const youtube = {};
  for (const result of results) {
    const [kind, slug] = result.page.split("/");
    if (!kind || !slug) continue;
    let imgs = result.images.filter((im) => !chromeHashes.has(im.hash));
    // Prefer larger content first for cover quality among DOM order already set -
    // but promote best product shot: first image with min side >= 400 if current first is awkward
    if (imgs.length > 1) {
      const firstOk = imgs[0].w >= 400 || imgs[0].h >= 400 || (!imgs[0].w && imgs[0].buf.length > 40000);
      if (!firstOk) {
        const better = imgs.findIndex((im) => im.w >= 400 || im.h >= 400 || im.buf.length > 80000);
        if (better > 0) {
          const [b] = imgs.splice(better, 1);
          imgs.unshift(b);
        }
      }
    }

    const saved = saveGallery(kind, slug, imgs);
    const yt = result.ytId || YT_FALLBACK[slug] || "";
    if (yt) youtube[slug] = yt;
    if (saved.length === 0 && yt) downloadYt(slug, kind, yt);
    console.log(`  saved ${slug}: ${saved.length}`);
  }

  if (mariageBuf) {
    const dir = path.join(OUT, "portfolio/mariage-2025-09-20");
    fs.mkdirSync(dir, { recursive: true });
    if (!fs.existsSync(path.join(dir, "01.jpg"))) {
      fs.writeFileSync(path.join(dir, "01.jpg"), mariageBuf);
    }
  }

  const manifest = { youtube, portfolio: {}, location: {} };
  for (const kind of ["portfolio", "location"]) {
    const base = path.join(OUT, kind);
    if (!fs.existsSync(base)) continue;
    for (const slug of fs.readdirSync(base)) {
      const d = path.join(base, slug);
      if (!fs.statSync(d).isDirectory()) continue;
      manifest[kind][slug] = fs
        .readdirSync(d)
        .filter((f) => /\.(jpe?g|png|webp|gif)$/i.test(f))
        .sort();
    }
  }
  fs.writeFileSync(path.join(OUT, "manifest.json"), JSON.stringify(manifest, null, 2));
  console.log("Done.");
  await browser.close();
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
