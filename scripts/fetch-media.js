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
  "arbre-de-noel-pompier-2023": null,
  "anniversaire-guinguette": null,
  "eurolite-led-pix-144-rgb-bar": "4qs5noXoh4Y",
  "eurolite-parled-sls-7-hcl-floor": "rtu1T7JhGF0",
  "evo-spark-600": "VedgKHWGKBs",
  "evolite-moving-beam1r": "URbd9yZEM6U",
  "fogspray-3000-rgb": "FHYWTfmiKF4",
  "jb-challenger-bsw": "-O-XPsAcURg",
  "mac-mah-lyre-wash-740z": "1RzpEb85y5M",
};

function chromePath() {
  for (const c of ["/usr/bin/chromium-browser", "/usr/bin/chromium", "/usr/bin/google-chrome"]) {
    if (fs.existsSync(c)) return c;
  }
  throw new Error("no chrome");
}

function extOf(buf) {
  if (buf[0] === 0xff && buf[1] === 0xd8) return "jpg";
  if (buf[0] === 0x89 && buf[1] === 0x50) return "png";
  if (buf[8] === 0x57 && buf[9] === 0x45) return "webp";
  return "bin";
}

function hash(buf) {
  return crypto.createHash("sha1").update(buf).digest("hex");
}

function normalize(url) {
  return url.replace(/=w\d+$/, "=w1280");
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

async function fetchInPage(page, url) {
  return page.evaluate(async (u) => {
    try {
      const res = await fetch(u);
      if (!res.ok) return { ok: false, status: res.status };
      const buf = await res.arrayBuffer();
      const bytes = new Uint8Array(buf);
      let bin = "";
      const chunk = 0x8000;
      for (let i = 0; i < bytes.length; i += chunk) {
        bin += String.fromCharCode.apply(null, bytes.subarray(i, i + chunk));
      }
      return { ok: true, b64: btoa(bin) };
    } catch (e) {
      return { ok: false, error: String(e) };
    }
  }, url);
}

function downloadYt(slug, kind, id) {
  if (!id) return null;
  const destDir = path.join(OUT, kind, slug);
  fs.mkdirSync(destDir, { recursive: true });
  const dest = path.join(destDir, "yt.jpg");
  for (const quality of ["maxresdefault", "sddefault", "hqdefault"]) {
    try {
      execFileSync("curl", ["-fsSL", "-o", dest, `https://i.ytimg.com/vi/${id}/${quality}.jpg`], {
        timeout: 20000,
      });
      const st = fs.statSync(dest);
      if (st.size > 2000) return "/images/" + kind + "/" + slug + "/yt.jpg";
    } catch (_) {}
  }
  return null;
}

async function main() {
  const puppeteer = require("puppeteer-core");
  const report = JSON.parse(fs.readFileSync(REPORT, "utf8"));
  const browser = await puppeteer.launch({
    executablePath: chromePath(),
    headless: "new",
    args: ["--no-sandbox", "--disable-gpu", "--disable-dev-shm-usage"],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1400, height: 1000 });
  await page.setUserAgent(
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
  );

  const seen = new Set();
  const manifest = { portfolio: {}, location: {}, youtube: {} };

  for (const entry of report.pages) {
    const kind = kindOf(entry.page);
    const slug = slugOf(entry.page);
    if (!kind || !slug || entry.http !== 200) continue;
    const destDir = path.join(OUT, kind, slug);
    fs.mkdirSync(destDir, { recursive: true });

    console.log("VISIT", entry.page, (entry.photos || []).length);
    try {
      await page.goto(entry.url, { waitUntil: "domcontentloaded", timeout: 40000 });
      await new Promise((r) => setTimeout(r, 800));
    } catch (err) {
      console.error("  nav fail", err.message);
    }

    const saved = [];
    let n = 0;
    for (const raw of entry.photos || []) {
      if (shouldSkipUrl(raw)) continue;
      const url = normalize(raw);
      const got = await fetchInPage(page, url);
      if (!got.ok || !got.b64) {
        console.log("  skip fetch", got.status || got.error || "empty");
        continue;
      }
      const buf = Buffer.from(got.b64, "base64");
      if (buf.length === LOGO_SIZE || buf.length < 8000) continue;
      const h = hash(buf);
      if (seen.has(h)) continue;
      seen.add(h);
      n += 1;
      const name = String(n).padStart(2, "0") + "." + extOf(buf);
      fs.writeFileSync(path.join(destDir, name), buf);
      saved.push("/images/" + kind + "/" + slug + "/" + name);
      console.log("  photo", name, buf.length);
    }

    const ytId = (entry.youtube_ids && entry.youtube_ids[0]) || YT[slug];
    if (ytId) {
      manifest.youtube[slug] = ytId;
      const ytPath = downloadYt(slug, kind, ytId);
      if (ytPath && !saved.includes(ytPath)) saved.push(ytPath);
    }

    manifest[kind][slug] = saved;
  }

  // Listing covers for items without a detail page
  const listing = report.pages.find((p) => p.page === "portfolio");
  if (listing) {
    const extras = [
      { slug: "soiree-halloween-2025", idx: 1 },
      { slug: "mariage-2025-09-20", idx: 2 },
    ];
    try {
      await page.goto(listing.url, { waitUntil: "domcontentloaded", timeout: 40000 });
      await new Promise((r) => setTimeout(r, 800));
    } catch (_) {}
    for (const extra of extras) {
      const raw = listing.photos[extra.idx];
      if (!raw) continue;
      const destDir = path.join(OUT, "portfolio", extra.slug);
      fs.mkdirSync(destDir, { recursive: true });
      const got = await fetchInPage(page, normalize(raw));
      if (!got.ok || !got.b64) continue;
      const buf = Buffer.from(got.b64, "base64");
      if (buf.length === LOGO_SIZE || buf.length < 8000) continue;
      const name = "01." + extOf(buf);
      fs.writeFileSync(path.join(destDir, name), buf);
      manifest.portfolio[extra.slug] = ["/images/portfolio/" + extra.slug + "/" + name];
      console.log("  listing", extra.slug, buf.length);
    }
  }

  // Location listing leftovers: structures without dedicated pages
  const locList = report.pages.find((p) => p.page === "location");
  const structureSlugs = [
    "pack-structure-f24200",
    "global-truss-4-points",
    "totem-4-points",
    "plugger-monotube",
  ];
  if (locList) {
    try {
      await page.goto(locList.url, { waitUntil: "domcontentloaded", timeout: 40000 });
      await new Promise((r) => setTimeout(r, 800));
    } catch (_) {}
    const productThumbs = (locList.photos || []).slice(1);
    const already = Object.values(manifest.location).flat();
    let offset = 13;
    for (let i = 0; i < structureSlugs.length; i++) {
      const slug = structureSlugs[i];
      const raw = productThumbs[offset + i];
      if (!raw) continue;
      const destDir = path.join(OUT, "location", slug);
      fs.mkdirSync(destDir, { recursive: true });
      const got = await fetchInPage(page, normalize(raw));
      if (!got.ok || !got.b64) continue;
      const buf = Buffer.from(got.b64, "base64");
      if (buf.length === LOGO_SIZE || buf.length < 8000) continue;
      const name = "01." + extOf(buf);
      fs.writeFileSync(path.join(destDir, name), buf);
      manifest.location[slug] = ["/images/location/" + slug + "/" + name];
      console.log("  structure", slug, buf.length);
    }
    void already;
  }

  await browser.close();

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
            manifest[kind][slug] = manifest[kind][slug].filter(
              (p) => !p.endsWith("/" + file)
            );
          }
        }
      }
    }
  }

  fs.writeFileSync(path.join(OUT, "manifest.json"), JSON.stringify(manifest, null, 2));
  console.log("DONE keys", Object.keys(manifest.portfolio).length, Object.keys(manifest.location).length);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
