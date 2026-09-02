/**
 * Download Event'Light photos from the live Google Sites pages
 * by fetching them in Chromium (curl gets 403 on lh3.googleusercontent.com).
 */
const fs = require("fs");
const path = require("path");
const { execSync } = require("child_process");

const ROOT = path.resolve(__dirname, "..");
const OUT = path.join(ROOT, "src", "images");
const REPORT = path.join(ROOT, "tmp-el-images", "report.json");

const SHARED_BANNER = [
  "AG8ngQXbtWcQD",
  "AG8ngQWqjQGNUB",
  "AG8ngQV1kISC7",
];

const PAGES = [
  { kind: "portfolio", slug: "pignan", url: "https://www.eventlight.net/portfolio/pignan" },
  { kind: "portfolio", slug: "pba-2024", url: "https://www.eventlight.net/portfolio/pba-2024" },
  { kind: "portfolio", slug: "saint-jean", url: "https://www.eventlight.net/portfolio/saint-jean" },
  { kind: "portfolio", slug: "arbre-de-noel-pompier-2023", url: "https://www.eventlight.net/portfolio/arbre-de-noel-pompier-2023" },
  { kind: "portfolio", slug: "gare-saint-paul", url: "https://www.eventlight.net/portfolio/gare-saint-paul" },
  { kind: "portfolio", slug: "anniversaire-guinguette", url: "https://www.eventlight.net/portfolio/anniversaire-guinguette" },
  { kind: "portfolio", slug: "adj-2021", url: "https://www.eventlight.net/portfolio/adj-2021" },
  { kind: "portfolio", slug: "gala-ffeo", url: "https://www.eventlight.net/portfolio/gala-ffeo" },
  { kind: "portfolio", slug: "le-pavillon-bleu", url: "https://www.eventlight.net/portfolio/le-pavillon-bleu" },
  { kind: "portfolio", slug: "adj-2020", url: "https://www.eventlight.net/portfolio/adj-2020" },
  { kind: "portfolio", slug: "la-maison-de-la-motte", url: "https://www.eventlight.net/portfolio/la-maison-de-la-motte" },
  { kind: "portfolio", slug: "mapping-halloween-2018", url: "https://www.eventlight.net/portfolio/mapping-halloween-2018" },
  { kind: "location", slug: "jb-challenger-bsw", url: "https://www.eventlight.net/location/jb-challenger-bsw" },
  { kind: "location", slug: "evolite-moving-beam1r", url: "https://www.eventlight.net/location/evolite-moving-beam1r" },
  { kind: "location", slug: "mac-mah-lyre-wash-740z", url: "https://www.eventlight.net/location/mac-mah-lyre-wash-740z" },
  { kind: "location", slug: "eurolite-parled-sls-7-hcl-floor", url: "https://www.eventlight.net/location/eurolite-parled-sls-7-hcl-floor" },
  { kind: "location", slug: "boomtone-ez-box-4x15w", url: "https://www.eventlight.net/location/boomtone-ez-box-4x15w" },
  { kind: "location", slug: "eurolite-led-pix-144-rgb-bar", url: "https://www.eventlight.net/location/eurolite-led-pix-144-rgb-bar" },
  { kind: "location", slug: "elokance-800c", url: "https://www.eventlight.net/location/elokance-800c" },
  { kind: "location", slug: "fbt-x-lite-110a", url: "https://www.eventlight.net/location/fbt-x-lite-110a" },
  { kind: "location", slug: "fbt-x-sub-115sa", url: "https://www.eventlight.net/location/fbt-x-sub-115sa" },
  { kind: "location", slug: "mac-mah-micro-sans-fil", url: "https://www.eventlight.net/location/mac-mah-micro-sans-fil" },
  { kind: "location", slug: "evolite-hazebox", url: "https://www.eventlight.net/location/evolite-hazebox" },
  { kind: "location", slug: "fogspray-3000-rgb", url: "https://www.eventlight.net/location/fogspray-3000-rgb" },
  { kind: "location", slug: "evo-spark-600", url: "https://www.eventlight.net/location/evo-spark-600" },
];

function findChrome() {
  const candidates = [
    process.env.CHROME_PATH,
    "/usr/bin/chromium-browser",
    "/usr/bin/chromium",
    "/usr/bin/google-chrome",
  ].filter(Boolean);
  for (const c of candidates) {
    if (fs.existsSync(c)) return c;
  }
  throw new Error("Chromium not found");
}

function normalizeUrl(url) {
  return url.replace(/=w\d+$/, "=w1280");
}

function isShared(url) {
  return SHARED_BANNER.some((token) => url.includes(token));
}

function extFromBuffer(buf) {
  if (buf[0] === 0xff && buf[1] === 0xd8) return "jpg";
  if (buf[0] === 0x89 && buf[1] === 0x50) return "png";
  if (buf[0] === 0x47 && buf[1] === 0x49) return "gif";
  if (buf[0] === 0x52 && buf[1] === 0x49) return "webp";
  return "jpg";
}

async function main() {
  const puppeteer = require("puppeteer-core");
  const chrome = findChrome();
  const browser = await puppeteer.launch({
    executablePath: chrome,
    headless: "new",
    args: ["--no-sandbox", "--disable-gpu", "--disable-dev-shm-usage"],
  });

  const manifest = { portfolio: {}, location: {} };
  const report = fs.existsSync(REPORT) ? JSON.parse(fs.readFileSync(REPORT, "utf8")) : { pages: [] };

  const page = await browser.newPage();
  await page.setViewport({ width: 1280, height: 900 });
  await page.setUserAgent(
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
  );

  for (const item of PAGES) {
    const destDir = path.join(OUT, item.kind, item.slug);
    fs.mkdirSync(destDir, { recursive: true });
    console.log("PAGE", item.slug);
    try {
      await page.goto(item.url, { waitUntil: "networkidle2", timeout: 45000 });
      await new Promise((r) => setTimeout(r, 1500));
      const urls = await page.evaluate(() => {
        const found = new Set();
        document.querySelectorAll("img").forEach((img) => {
          const src = img.currentSrc || img.src || "";
          if (src.includes("googleusercontent.com/sitesv/")) found.add(src);
        });
        return Array.from(found);
      });
      const unique = urls.map(normalizeUrl).filter((u) => !isShared(u));
      const saved = [];
      let i = 0;
      for (const url of unique) {
        const b64 = await page.evaluate(async (u) => {
          const res = await fetch(u);
          if (!res.ok) return null;
          const buf = await res.arrayBuffer();
          const bytes = new Uint8Array(buf);
          let bin = "";
          for (let j = 0; j < bytes.length; j++) bin += String.fromCharCode(bytes[j]);
          return btoa(bin);
        }, url);
        if (!b64) continue;
        const buf = Buffer.from(b64, "base64");
        if (buf.length < 4000) continue;
        i += 1;
        const ext = extFromBuffer(buf);
        const name = String(i).padStart(2, "0") + "." + ext;
        fs.writeFileSync(path.join(destDir, name), buf);
        saved.push("/images/" + item.kind + "/" + item.slug + "/" + name);
        console.log("  saved", name, buf.length);
      }
      manifest[item.kind][item.slug] = saved;
    } catch (err) {
      console.error("  FAIL", item.slug, err.message);
      manifest[item.kind][item.slug] = manifest[item.kind][item.slug] || [];
    }
  }

  // Listing covers for Halloween 2025 / Mariage 2025 (no detail pages)
  try {
    await page.goto("https://www.eventlight.net/portfolio", { waitUntil: "networkidle2", timeout: 45000 });
    await new Promise((r) => setTimeout(r, 1500));
    const listing = await page.evaluate(() => {
      return Array.from(document.querySelectorAll("img"))
        .map((img) => img.currentSrc || img.src)
        .filter((src) => src && src.includes("googleusercontent.com/sitesv/"));
    });
    const extras = [
      { slug: "soiree-halloween-2025", index: 1 },
      { slug: "mariage-2025-09-20", index: 2 },
    ];
    for (const extra of extras) {
      const url = listing[extra.index];
      if (!url) continue;
      const destDir = path.join(OUT, "portfolio", extra.slug);
      fs.mkdirSync(destDir, { recursive: true });
      const b64 = await page.evaluate(async (u) => {
        const res = await fetch(u);
        if (!res.ok) return null;
        const buf = await res.arrayBuffer();
        const bytes = new Uint8Array(buf);
        let bin = "";
        for (let j = 0; j < bytes.length; j++) bin += String.fromCharCode(bytes[j]);
        return btoa(bin);
      }, normalizeUrl(url));
      if (!b64) continue;
      const buf = Buffer.from(b64, "base64");
      const name = "01." + extFromBuffer(buf);
      fs.writeFileSync(path.join(destDir, name), buf);
      manifest.portfolio[extra.slug] = ["/images/portfolio/" + extra.slug + "/" + name];
      console.log("  listing", extra.slug, buf.length);
    }
  } catch (err) {
    console.error("listing fail", err.message);
  }

  // YouTube thumbs as fallback covers
  const ytBySlug = {};
  for (const p of report.pages || []) {
    const slug = (p.slug || p.key || "").replace(/^(portfolio|location)\//, "");
    if (p.youtube_ids && p.youtube_ids[0] && slug) {
      ytBySlug[slug] = p.youtube_ids[0];
    }
  }
  const extraYt = {
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
  Object.assign(ytBySlug, extraYt);

  for (const [slug, id] of Object.entries(ytBySlug)) {
    const kind = manifest.location[slug] ? "location" : "portfolio";
    const destDir = path.join(OUT, kind, slug);
    fs.mkdirSync(destDir, { recursive: true });
    const files = manifest[kind][slug] || [];
    if (files.length) {
      manifest.youtube = manifest.youtube || {};
      manifest.youtube[slug] = id;
      continue;
    }
    const thumb = "https://i.ytimg.com/vi/" + id + "/hqdefault.jpg";
    try {
      execSync(`curl -fsSL -o "${path.join(destDir, "yt.jpg")}" "${thumb}"`, { stdio: "ignore" });
      manifest[kind][slug] = ["/images/" + kind + "/" + slug + "/yt.jpg"];
      console.log("  yt thumb", slug);
    } catch (_) {}
    manifest.youtube = manifest.youtube || {};
    manifest.youtube[slug] = id;
  }

  await browser.close();
  fs.writeFileSync(path.join(OUT, "manifest.json"), JSON.stringify(manifest, null, 2));
  console.log("DONE", JSON.stringify(manifest, null, 2));
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
