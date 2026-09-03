#!/usr/bin/env node
/**
 * Map portfolio listing cards → cover images (for 404 detail pages).
 * Also try alternate location URLs for structure products.
 */
const fs = require("fs");
const path = require("path");
const crypto = require("crypto");
const puppeteer = require("puppeteer-core");

const ROOT = path.resolve(__dirname, "..");
const OUT = path.join(ROOT, "src", "images");

function chromePath() {
  for (const c of ["/usr/bin/chromium-browser", "/usr/bin/chromium", "/snap/bin/chromium"]) {
    if (fs.existsSync(c)) return c;
  }
  throw new Error("no chrome");
}

function extOf(buf) {
  if (buf[0] === 0xff && buf[1] === 0xd8) return "jpg";
  if (buf[0] === 0x89 && buf[1] === 0x50) return "png";
  return "bin";
}

function sha1(buf) {
  return crypto.createHash("sha1").update(buf).digest("hex");
}

async function main() {
  const browser = await puppeteer.launch({
    executablePath: chromePath(),
    headless: "new",
    args: ["--no-sandbox", "--disable-setuid-sandbox", "--disable-dev-shm-usage"],
  });

  const page = await browser.newPage();
  await page.setViewport({ width: 1400, height: 1200 });
  await page.setUserAgent(
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
  );

  const buffers = new Map();
  page.on("response", async (res) => {
    try {
      const u = res.url();
      if (!/googleusercontent\.com\/sitesv\//.test(u) || res.status() !== 200) return;
      const ct = (res.headers()["content-type"] || "").toLowerCase();
      if (!ct.startsWith("image/")) return;
      const buf = await res.buffer();
      if (buf.length < 8000 || buf.length === 279530) return;
      buffers.set(u.split("?")[0].replace(/=w\d+$/, ""), buf);
      buffers.set(u, buf);
    } catch (_) {}
  });

  await page.goto("https://www.eventlight.net/portfolio", {
    waitUntil: "domcontentloaded",
    timeout: 60000,
  });
  await page.evaluate(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += 300) {
      window.scrollTo(0, y);
      await new Promise((r) => setTimeout(r, 150));
    }
  });
  await new Promise((r) => setTimeout(r, 3000));

  const cards = await page.evaluate(() => {
    const out = [];
    const anchors = [...document.querySelectorAll("a[href*='/portfolio/']")];
    for (const a of anchors) {
      const href = a.getAttribute("href") || "";
      const m = href.match(/\/portfolio\/([^/?#]+)/);
      if (!m) continue;
      const slug = m[1];
      if (slug === "portfolio") continue;
      const img = a.querySelector("img");
      const src = img ? img.currentSrc || img.src : "";
      const text = (a.innerText || "").replace(/\s+/g, " ").trim().slice(0, 80);
      out.push({ slug, src, text });
    }
    return out;
  });

  console.log("cards", cards.length);
  const bySlug = {};
  for (const c of cards) {
    if (!bySlug[c.slug]) bySlug[c.slug] = c;
    console.log(c.slug, c.src ? "img" : "noimg", c.text);
  }

  const need = ["mariage-2025-09-20", "soiree-halloween-2025"];
  for (const slug of need) {
    const card = bySlug[slug];
    const dir = path.join(OUT, "portfolio", slug);
    fs.mkdirSync(dir, { recursive: true });
    const existing = fs.readdirSync(dir).filter((f) => /\.(jpe?g|png|webp)$/i.test(f));
    if (existing.length) {
      console.log(slug, "already has", existing);
      continue;
    }
    if (!card || !card.src) {
      console.log(slug, "no card image on listing");
      continue;
    }
    const key = card.src.split("?")[0].replace(/=w\d+$/, "");
    let buf = buffers.get(card.src) || buffers.get(key);
    if (!buf) {
      // try fuzzy match
      for (const [u, b] of buffers) {
        if (u.includes(key.slice(-40)) || key.includes(u.slice(-40))) {
          buf = b;
          break;
        }
      }
    }
    if (!buf) {
      // fetch via new page navigation (works in browser)
      const p2 = await browser.newPage();
      const res = await p2.goto(card.src, { waitUntil: "networkidle2", timeout: 30000 });
      buf = await res.buffer();
      await p2.close();
    }
    if (!buf || buf.length < 5000) {
      console.log(slug, "failed to get buffer");
      continue;
    }
    const ext = extOf(buf);
    const name = `01.${ext}`;
    fs.writeFileSync(path.join(dir, name), buf);
    console.log(slug, "saved", name, buf.length, sha1(buf).slice(0, 8));
  }

  // Location listing: map structure products if present
  await page.goto("https://www.eventlight.net/location", {
    waitUntil: "domcontentloaded",
    timeout: 60000,
  });
  await page.evaluate(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += 300) {
      window.scrollTo(0, y);
      await new Promise((r) => setTimeout(r, 120));
    }
  });
  await new Promise((r) => setTimeout(r, 2500));

  const locCards = await page.evaluate(() => {
    return [...document.querySelectorAll("a[href*='/location/']")].map((a) => {
      const href = a.getAttribute("href") || "";
      const m = href.match(/\/location\/([^/?#]+)/);
      const img = a.querySelector("img");
      return {
        slug: m ? m[1] : "",
        src: img ? img.currentSrc || img.src : "",
        text: (a.innerText || "").replace(/\s+/g, " ").trim().slice(0, 60),
      };
    }).filter((c) => c.slug);
  });
  console.log("\nlocation cards:");
  const seen = new Set();
  for (const c of locCards) {
    if (seen.has(c.slug)) continue;
    seen.add(c.slug);
    console.log(c.slug, c.src ? "img" : "-", c.text);
  }

  // Rebuild manifest youtube + inventory
  const manPath = path.join(OUT, "manifest.json");
  const man = JSON.parse(fs.readFileSync(manPath, "utf8"));
  for (const kind of ["portfolio", "location"]) {
    man[kind] = {};
    const base = path.join(OUT, kind);
    for (const slug of fs.readdirSync(base)) {
      const d = path.join(base, slug);
      if (!fs.statSync(d).isDirectory()) continue;
      man[kind][slug] = fs
        .readdirSync(d)
        .filter((f) => /\.(jpe?g|png|webp|gif)$/i.test(f))
        .sort();
    }
  }
  fs.writeFileSync(manPath, JSON.stringify(man, null, 2));

  await browser.close();
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
