/**
 * Capture a mobile-width (responsive) screenshot of PayrollPro for docs/images/.
 * Requires the app running at the BASE URL and seeded admin account.
 *
 * Usage: node scripts/capture-mobile.mjs
 */
import { chromium } from 'playwright';
import { join } from 'path';

const BASE = 'http://127.0.0.1:8000';
const OUT = join(process.cwd(), 'docs', 'images');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

const browser = await chromium.launch();
const ctx = await browser.newContext({
  viewport: { width: 390, height: 844 },
  deviceScaleFactor: 2,
  isMobile: true,
  hasTouch: true,
  colorScheme: 'light',
});
const page = await ctx.newPage();

await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 45000 });
await page.waitForSelector('#email', { state: 'visible', timeout: 30000 });
await page.fill('#email', 'admin@project-kp.test');
await page.fill('#password', 'password');
await page.click('button[type=submit]');
await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 25000 });
await page.waitForLoadState('networkidle');
await sleep(2500);

await page.goto(`${BASE}/dashboard`, { waitUntil: 'domcontentloaded', timeout: 45000 });
await page.waitForLoadState('networkidle');
await sleep(3000);
await page.screenshot({ path: join(OUT, 'mobile-dashboard.png'), fullPage: false });
console.log('OK mobile-dashboard (390x844 @2x)');

await browser.close();
