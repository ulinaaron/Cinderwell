#!/usr/bin/env node
import { readFile, mkdir, writeFile } from 'node:fs/promises';
import { resolve, join } from 'node:path';
import { chromium } from 'playwright';

const names = [
  'aster-peak-demo', 'cedar-ledger-demo', 'field-frost-demo',
  'goodgood-parcel-demo', 'northline-concrete-demo', 'sundrift-fruit-co',
  'velaform-building-systems', 'my-shiny-website',
];
const config = resolve(process.argv[2] || 'demo-sites.json');
const sites = JSON.parse(await readFile(config, 'utf8'));
if (!Array.isArray(sites) || sites.length !== names.length ||
    names.some((name) => sites.filter((site) => site.name === name).length !== 1)) {
  throw new Error(`Config must contain exactly these sites: ${names.join(', ')}`);
}
for (const site of sites) {
  const url = new URL(site.url);
  if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) {
    throw new Error(`Invalid URL for ${site.name}`);
  }
}

const artifacts = resolve('.artifacts/blast-radius');
await mkdir(artifacts, { recursive: true });
const browser = await chromium.launch();
const results = [];
try {
  for (const site of sites) {
    for (const width of [1440, 390]) {
      const context = await browser.newContext({ viewport: { width, height: 900 }, deviceScaleFactor: 1 });
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', (error) => errors.push(error.message));
      const row = { name: site.name, width, url: site.url, failures: [] };
      try {
        const response = await page.goto(site.url, { waitUntil: 'domcontentloaded', timeout: 30000 });
        if (!response || response.status() >= 400) row.failures.push(`HTTP ${response?.status() ?? 'no response'}`);
        await page.locator('body').waitFor({ state: 'visible', timeout: 10000 });
        const state = await page.evaluate(() => ({
          text: document.body.innerText.trim().length,
          overflow: document.documentElement.scrollWidth - window.innerWidth,
          title: document.title.trim(),
        }));
        if (!state.text) row.failures.push('Empty body text');
        if (!state.title) row.failures.push('Empty page title');
        if (state.overflow > 2) row.failures.push(`Horizontal overflow: ${state.overflow}px`);
        await page.screenshot({ path: join(artifacts, `${site.name}-${width}.png`), fullPage: true });
      } catch (error) { row.failures.push(error.message); }
      row.failures.push(...errors.map((message) => `Page error: ${message}`));
      results.push(row);
      console.log(`${row.failures.length ? 'FAIL' : 'PASS'} ${site.name} ${width}px ${row.failures.join(' | ')}`);
      await context.close();
    }
  }
} finally { await browser.close(); }
await writeFile(join(artifacts, 'results.json'), `${JSON.stringify(results, null, 2)}\n`);
console.log(`Screenshots and results: ${artifacts}`);
if (results.some((row) => row.failures.length)) process.exitCode = 1;
