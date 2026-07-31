/**
 * Capture professional 1920×1080 screenshots of PayrollPro for docs/images/.
 * Requires the app to be running and seeded (admin@project-kp.test / password).
 *
 * Usage: node _archive/capture-screenshots.mjs
 */
import { chromium } from 'playwright';
import { mkdirSync } from 'fs';
import { join } from 'path';

const BASE = 'http://127.0.0.1:8000';
const OUT = join(process.cwd(), 'docs', 'images');
mkdirSync(OUT, { recursive: true });

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function login(page, email) {
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  // Wait for the Vue/Inertia app to hydrate the form.
  await page.waitForSelector('#email', { state: 'visible', timeout: 30000 });
  await page.fill('#email', email);
  await page.fill('#password', 'password');
  await page.click('button[type=submit]');
  await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 25000 });
  await page.waitForLoadState('networkidle');
  await sleep(2500);
}

async function shot(page, urlPath, name, wait = 2500) {
  await page.goto(`${BASE}${urlPath}`, { waitUntil: 'domcontentloaded', timeout: 45000 });
  await page.waitForLoadState('networkidle');
  await sleep(wait);
  const file = join(OUT, `${name}.png`);
  await page.screenshot({ path: file, fullPage: false });
  console.log(`  OK ${name.padEnd(22)}  <- ${urlPath}`);
}

const browser = await chromium.launch();
const ctx = await browser.newContext({
  viewport: { width: 1920, height: 1080 },
  deviceScaleFactor: 1,
});
const page = await ctx.newPage();
page.on('console', (msg) => {
  if (msg.type() === 'error') console.log(`  [console.error] ${msg.text().slice(0, 120)}`);
});

console.log('[ADMIN]');
await shot(page, '/login', 'login', 2000);
await login(page, 'admin@project-kp.test');
await shot(page, '/dashboard', 'dashboard', 3000);
await shot(page, '/employees', 'employees', 2500);
await shot(page, '/employees/1', 'employee-detail', 2500);
await shot(page, '/attendances', 'attendance', 2500);
await shot(page, '/payroll', 'payroll', 2500);
await shot(page, '/payroll/1', 'payroll-detail', 2500);
await shot(page, '/reports/payroll', 'reports', 3000);
await shot(page, '/settings', 'settings', 2500);
await shot(page, '/my-qr', 'my-qr', 3500);

console.log('[EMPLOYEE]');
await ctx.clearCookies();
await login(page, 'ahmad.fauzi.1@project-kp.test');
await shot(page, '/dashboard', 'portal-dashboard', 3000);
await shot(page, '/portal/attendance', 'portal-attendance', 2500);
await shot(page, '/portal/payroll', 'portal-payroll', 2500);
await shot(page, '/portal/tax', 'portal-tax', 2500);

console.log('[DARK MODE]');
await ctx.clearCookies();
await ctx.addInitScript(() => localStorage.setItem('darkMode', 'true'));
await login(page, 'admin@project-kp.test');
await shot(page, '/dashboard', 'dashboard-dark', 3000);

await browser.close();
console.log('Done. Screenshots in docs/images/');
