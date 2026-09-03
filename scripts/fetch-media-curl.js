#!/usr/bin/env node
/**
 * Download Event'Light Google Sites photos via curl (no CORS).
 * Uses tmp-el-images/report.json page → photos mapping.
 */
const fs = require("fs");
const path = require("path");
const crypto = require("crypto");
const { execFileSync } = require("child_process");

const ROOT = path.resolve(__dirname, "..");
const OUT = path.join(ROOT, "src", "images");
const REPORT = path.join(ROOT, "tmp-el-images", "report.json");
const LOGO_SIZE = 279530;

const SKIP_TOKENS = [
  "AG8ngQXbtWcQD",
  "AG8ngQWqjQGNUB",
  "AG8ngQV1kISC7",
  "AG8ngQX5LpXVXu",
];

const YT = {
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

function extOf(buf) {
  if (buf[0] === 0xff && buf[1] === 0xd8) return "jpg";
  if (buf[0] === 0x89 && buf[1] === 0x50) return "png";
  if (buf.length > 12 && buf[8] === 0x57 && buf[9] === 0x45) return "webp";
  return "bin";
}

function hash(buf) {
  return crypto.createHash("sha1").update(buf).digest("hex");
}

function normalize(url) {
  return String(url).replace(/=w\d+$/, "=w1280");
}

function shouldSkipUrl(url) {
  return SKIP_TOKENS.some((t) => url.includes(t));
}

function kindOf(page) {
  if (page.startsWith("portfolio/") || page === "portfolio") return "portfolio";
  if (page.startsWith("location/") || page === "location") return "location";
  return null;
}

function slugOf(page) {
  if (page === "portfolio" || page === "location" || page === "home") return null;
  return page.split("/").pop();
}

function curlDownload(url, dest) {
  try {
    execFileSync(
      "curl",
      [
        "-fsSL",
        "-A",
        "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36",
        "-o",
        dest,
        url,
      ],
      { timeout: 45000, stdio: ["ignore", "ignore", "pipe"] }
    );
    return fs.existsSync(dest) && fs.statSync(dest).size > 0;
  } catch (_) {
    return false;
  }
}

function downloadYt(slug, kind, id) {
  if (!id) return null;
  const destDir = path.join(OUT, kind, slug);
  fs.mkdirSync(destDir, { recursive: true });
  const dest = path.join(destDir, "yt.jpg");
  for (const quality of ["maxresdefault", "sddefault", "hqdefault"]) {
    if (curlDownload(`https://i.ytimg.com/vi/${id}/${quality}.jpg`, dest)) {
      if (fs.statSync(dest).size > 2000) return `/images/${kind}/${slug}/yt.jpg`;
    }
  }
  return null;
}

function savePhoto(buf, destDir, n) {
  const name = String(n).padStart(2, "0") + "." + extOf(buf);
  fs.writeFileSync(path.join(destDir, name), buf);
  return name;
}

function main() {
  const report = JSON.parse(fs.readFileSync(REPORT, "utf8"));
  const seen = new Set();
  const manifest = { portfolio: {}, location: {}, youtube: {} };
  const tmp = path.join(ROOT, "tmp-el-images", "_dl.bin");

  for (const entry of report.pages) {
    const kind = kindOf(entry.page);
    const slug = slugOf(entry.page);
    if (!kind || !slug) continue;
    if (entry.http !== 200 && !(entry.photos || []).length) continue;

    const destDir = path.join(OUT, kind, slug);
    fs.mkdirSync(destDir, { recursive: true });
    // keep existing yt.jpg; wipe numbered photos to refresh
    for (const f of fs.readdirSync(destDir)) {
      if (/^\d{2}\./.test(f)) fs.unlinkSync(path.join(destDir, f));
    }

    console.log("PAGE", entry.page, (entry.photos || []).length);
    const saved = [];
    let n = 0;

    for (const raw of entry.photos || []) {
      if (shouldSkipUrl(raw)) continue;
      const url = normalize(raw);
      if (!curlDownload(url, tmp)) {
        console.log("  fail", url.slice(0, 80));
        continue;
      }
      const buf = fs.readFileSync(tmp);
      if (buf.length === LOGO_SIZE || buf.length < 8000) continue;
      const h = hash(buf);
      if (seen.has(h)) continue;
      seen.add(h);
      n += 1;
      const name = savePhoto(buf, destDir, n);
      saved.push(`/images/${kind}/${slug}/${name}`);
      console.log("  ok", name, buf.length);
    }

    const ytId = (entry.youtube_ids && entry.youtube_ids[0]) || YT[slug];
    if (ytId) {
      manifest.youtube[slug] = ytId;
      const ytPath = downloadYt(slug, kind, ytId);
      if (ytPath && !saved.includes(ytPath)) saved.push(ytPath);
    }

    // Prefer numbered photos first, yt as fallback cover
    saved.sort((a, b) => {
      const an = /\/\d{2}\./.test(a) ? 0 : 1;
      const bn = /\/\d{2}\./.test(b) ? 0 : 1;
      return an - bn || a.localeCompare(b);
    });
    manifest[kind][slug] = saved;
  }

  // Listing covers for portfolio items without detail photos
  const listing = report.pages.find((p) => p.page === "portfolio");
  if (listing) {
    const extras = [
      { slug: "soiree-halloween-2025", idx: 1 },
      { slug: "mariage-2025-09-20", idx: 2 },
    ];
    for (const extra of extras) {
      const raw = (listing.photos || [])[extra.idx];
      if (!raw || shouldSkipUrl(raw)) continue;
      const destDir = path.join(OUT, "portfolio", extra.slug);
      fs.mkdirSync(destDir, { recursive: true });
      if (!curlDownload(normalize(raw), tmp)) continue;
      const buf = fs.readFileSync(tmp);
      if (buf.length === LOGO_SIZE || buf.length < 8000) continue;
      const name = "01." + extOf(buf);
      fs.writeFileSync(path.join(destDir, name), buf);
      const list = [`/images/portfolio/${extra.slug}/${name}`];
      if (manifest.portfolio[extra.slug]) {
        for (const p of manifest.portfolio[extra.slug]) {
          if (!list.includes(p)) list.push(p);
        }
      }
      manifest.portfolio[extra.slug] = list;
      console.log("LISTING", extra.slug, buf.length);
    }
  }

  // Structures from location listing (no dedicated Google Sites pages)
  const locList = report.pages.find((p) => p.page === "location");
  const structureSlugs = [
    "pack-structure-f24200",
    "global-truss-4-points",
    "totem-4-points",
    "plugger-monotube",
  ];
  if (locList) {
    const productThumbs = (locList.photos || []).slice(1);
    let offset = 13;
    for (let i = 0; i < structureSlugs.length; i++) {
      const slug = structureSlugs[i];
      const raw = productThumbs[offset + i];
      if (!raw || shouldSkipUrl(raw)) continue;
      const destDir = path.join(OUT, "location", slug);
      fs.mkdirSync(destDir, { recursive: true });
      if (!curlDownload(normalize(raw), tmp)) continue;
      const buf = fs.readFileSync(tmp);
      if (buf.length === LOGO_SIZE || buf.length < 8000) continue;
      const name = "01." + extOf(buf);
      fs.writeFileSync(path.join(destDir, name), buf);
      manifest.location[slug] = [`/images/location/${slug}/${name}`];
      console.log("STRUCTURE", slug, buf.length);
    }
  }

  if (fs.existsSync(tmp)) fs.unlinkSync(tmp);

  // Drop leftover logo clones
  for (const kind of ["portfolio", "location"]) {
    const dir = path.join(OUT, kind);
    if (!fs.existsSync(dir)) continue;
    for (const slug of fs.readdirSync(dir)) {
      const folder = path.join(dir, slug);
      if (!fs.statSync(folder).isDirectory()) continue;
      for (const file of fs.readdirSync(folder)) {
        const full = path.join(folder, file);
        if (fs.statSync(full).size === LOGO_SIZE) {
          fs.unlinkSync(full);
          if (manifest[kind][slug]) {
            manifest[kind][slug] = manifest[kind][slug].filter((p) => !p.endsWith("/" + file));
          }
        }
      }
    }
  }

  fs.writeFileSync(path.join(OUT, "manifest.json"), JSON.stringify(manifest, null, 2));
  console.log(
    "DONE portfolio",
    Object.keys(manifest.portfolio).length,
    "location",
    Object.keys(manifest.location).length,
    "photos",
    Object.values(manifest.portfolio).concat(Object.values(manifest.location)).flat().length
  );
}

main();
