/**
 * =============================================================================
 *  PayrollPro — Automated Demo Recording Script (Employee Portal)
 * =============================================================================
 *
 *  Records the employee self-service side of PayrollPro:
 *
 *    1. Login as an employee
 *    2. Portal Dashboard (today's attendance, clock in/out, pending leaves)
 *    3. My QR (personal QR code for attendance scanning)
 *    4. Attendance History
 *    5. Payroll History (payslips)
 *    6. Tax Info (PPh 21)
 *    7. Leaves (open the request form)
 *    8. Return to Portal Dashboard → fade-out ending
 *
 *  Usage:
 *    node scripts/demo-employee.js
 *
 *  Output:
 *    output/video-employee/*.webm
 *    output/timeline-employee.json
 * =============================================================================
 */

import { chromium } from 'playwright';
import { mkdir, writeFile } from 'fs/promises';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const ROOT = join(__dirname, '..');
const OUTPUT_DIR = join(ROOT, 'output');

// ─── Configuration ──────────────────────────────────────────────────────────

const APP_URL = process.env.APP_URL || 'http://127.0.0.1:8000';
const EMPLOYEE_EMAIL = process.env.DEMO_EMPLOYEE_EMAIL || 'ahmad.fauzi.1@project-kp.test';
const EMPLOYEE_PASSWORD = process.env.DEMO_EMPLOYEE_PASSWORD || 'password';

const VIEWPORT = { width: 1920, height: 1080 };
const RECORDING_DIR = join(OUTPUT_DIR, 'video-employee');
const TIMELINE_FILE = join(OUTPUT_DIR, 'timeline-employee.json');

const MOUSE_STEPS = 24;
const MOUSE_DELAY_MS = 6;

const PAUSE_SHORT = 400;
const PAUSE_MEDIUM = 800;
const PAUSE_LONG = 1500;
const PAUSE_HIGHLIGHT = 2500;
const SCENE_PAUSE = 1200;

// ─── Scene Timeline ─────────────────────────────────────────────────────────

const TIMELINE = { scenes: [], markers: [] };
let sceneStartTime = 0;
let currentScene = '';
let SESSION_START = Date.now();

function markScene(name) {
  const now = Date.now();
  if (currentScene) {
    TIMELINE.scenes.push({ name: currentScene, start: sceneStartTime, end: now });
  }
  currentScene = name;
  sceneStartTime = now;
  TIMELINE.markers.push({ name, time: now - SESSION_START });
}

// Minimum on-screen time per scene ≈ Brian narration duration + ~1.5 s.
const SCENE_MIN_SECONDS = {
  'opening': 12,
  'portal-dashboard': 17,
  'my-qr': 12,
  'portal-attendance': 13,
  'portal-payroll': 15,
  'portal-tax': 12,
  'portal-leaves': 15,
  'ending': 13,
};

async function holdSceneMinimum(page) {
  const min = (SCENE_MIN_SECONDS[currentScene] || 0) * 1000;
  const elapsed = Date.now() - sceneStartTime;
  if (elapsed < min) {
    await page.waitForTimeout(min - elapsed);
  }
}

// ─── Cursor & Movement Helpers (same behavior as the admin demo) ────────────

async function getCursorPos(page) {
  const pos = await page.evaluate(() => {
    const el = document.getElementById('demo-cursor');
    if (!el) return null;
    const x = parseInt(el.style.left);
    const y = parseInt(el.style.top);
    if (isNaN(x) || isNaN(y)) return null;
    return { x, y };
  });
  return pos ?? { x: VIEWPORT.width / 2, y: VIEWPORT.height / 2 };
}

async function naturalMouseMove(page, x1, y1, x2, y2, { steps = MOUSE_STEPS, delay = MOUSE_DELAY_MS } = {}) {
  if (isNaN(x1) || isNaN(y1) || isNaN(x2) || isNaN(y2)) return;
  const cp1x = x1 + (x2 - x1) * (0.2 + Math.random() * 0.15);
  const cp1y = y1;
  const cp2x = x1 + (x2 - x1) * (0.7 + Math.random() * 0.15);
  const cp2y = y2;

  for (let i = 0; i <= steps; i++) {
    const t = i / steps;
    const ease = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
    const px = Math.round(
      (1 - ease) ** 3 * x1 + 3 * (1 - ease) ** 2 * ease * cp1x +
      3 * (1 - ease) * ease ** 2 * cp2x + ease ** 3 * x2
    );
    const py = Math.round(
      (1 - ease) ** 3 * y1 + 3 * (1 - ease) ** 2 * ease * cp1y +
      3 * (1 - ease) * ease ** 2 * cp2y + ease ** 3 * y2
    );
    if (isNaN(px) || isNaN(py)) continue;
    await page.mouse.move(px, py);
    if (Math.random() < 0.3) {
      const jx = px + (Math.random() - 0.5) * 2;
      const jy = py + (Math.random() - 0.5) * 2;
      if (!isNaN(jx) && !isNaN(jy)) await page.mouse.move(jx, jy);
    }
  }
}

async function smoothScroll(page, targetY, { duration = 800, steps = 30 } = {}) {
  const currentY = await page.evaluate(() => window.scrollY);
  const distance = targetY - currentY;
  if (Math.abs(distance) < 5) return;
  for (let i = 1; i <= steps; i++) {
    const t = i / steps;
    const ease = 1 - Math.pow(1 - t, 3);
    await page.evaluate((y) => window.scrollTo({ top: y, behavior: 'instant' }), currentY + distance * ease);
    await page.waitForTimeout(duration / steps);
  }
}

const CURSOR_STYLES = `
  #demo-cursor {
    position: fixed;
    pointer-events: none;
    z-index: 9999999;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: rgba(99, 102, 241, 0.85);
    border: 2px solid rgba(255, 255, 255, 0.9);
    box-shadow: 0 0 12px rgba(99, 102, 241, 0.4), 0 2px 6px rgba(0, 0, 0, 0.15);
    transform: translate(-50%, -50%);
    transition: width 0.2s ease, height 0.2s ease, background 0.2s ease;
    will-change: transform;
  }
  #demo-cursor.clicking {
    width: 20px;
    height: 20px;
    background: rgba(99, 102, 241, 1);
    box-shadow: 0 0 20px rgba(99, 102, 241, 0.6);
  }
  html, body, * { cursor: none !important; }
`;

async function injectCursor(page) {
  await page.evaluate((styles) => {
    const cursor = document.createElement('div');
    cursor.id = 'demo-cursor';
    document.body.appendChild(cursor);
    const style = document.createElement('style');
    style.textContent = styles;
    document.head.appendChild(style);
    document.addEventListener('mousemove', (e) => {
      const el = document.getElementById('demo-cursor');
      if (el) {
        el.style.left = e.clientX + 'px';
        el.style.top = e.clientY + 'px';
      }
    });
  }, CURSOR_STYLES);
}

async function cursorClick(page) {
  await page.evaluate(() => document.getElementById('demo-cursor')?.classList.add('clicking'));
  await page.waitForTimeout(60);
  await page.evaluate(() => document.getElementById('demo-cursor')?.classList.remove('clicking'));
}

async function pause(page, ms) {
  if (ms > 600) {
    const pos = await getCursorPos(page);
    await page.mouse.move(pos.x + (Math.random() - 0.5) * 4, pos.y + (Math.random() - 0.5) * 4);
  }
  await page.waitForTimeout(ms);
}

async function navigateTo(page, url, sceneName) {
  await holdSceneMinimum(page);
  markScene(sceneName);
  console.log(`  → Scene: ${sceneName}`);
  await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForTimeout(500);
  await injectCursor(page).catch(() => {});
  await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
  await page.waitForTimeout(SCENE_PAUSE);
}

async function highlightCards(page, selector, { max = 4, holdMs = PAUSE_HIGHLIGHT - 500 } = {}) {
  const cards = await page.$$(selector);
  for (let i = 0; i < Math.min(cards.length, max); i++) {
    const box = await cards[i].boundingBox();
    if (box && box.y < VIEWPORT.height - 80) {
      const pos = await getCursorPos(page);
      await naturalMouseMove(page, pos.x, pos.y, box.x + box.width / 2, box.y + Math.min(box.height / 2, 60));
      await pause(page, holdMs);
    }
  }
}

function setupConsoleMonitoring(page) {
  const errors = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') errors.push({ type: 'console', text: msg.text() });
  });
  page.on('pageerror', (err) => errors.push({ type: 'pageerror', text: err.message }));
  page.on('requestfailed', (req) => errors.push({ type: 'network', text: req.url() }));
  return errors;
}

// ═════════════════════════════════════════════════════════════════════════════
//  MAIN DEMO SEQUENCE — EMPLOYEE PORTAL
// ═════════════════════════════════════════════════════════════════════════════

async function runDemo(browser) {
  const context = await browser.newContext({
    viewport: VIEWPORT,
    deviceScaleFactor: 1,
    recordVideo: { dir: RECORDING_DIR, size: VIEWPORT },
    locale: 'id-ID',
    timezoneId: 'Asia/Jakarta',
    colorScheme: 'dark',
  });

  await context.addInitScript(() => {
    try {
      localStorage.setItem('darkMode', 'true');
      document.documentElement.classList.add('dark');
    } catch (e) {}
  });

  const page = await context.newPage();
  page.setDefaultTimeout(15000);
  const consoleErrors = setupConsoleMonitoring(page);
  await injectCursor(page);

  SESSION_START = Date.now();
  sceneStartTime = SESSION_START;

  console.log('\n  ════════════════════════════════════════════');
  console.log('   PayrollPro — Employee Demo Recording Started');
  console.log('  ════════════════════════════════════════════\n');

  try {
    // ─── SCENE 1: Opening — Login as employee ─────────────────────────────
    markScene('opening');
    console.log('  → Scene: opening (login)');
    await page.goto(`${APP_URL}/login`, { waitUntil: 'networkidle', timeout: 30000 });
    await injectCursor(page).catch(() => {});
    await page.waitForTimeout(1500);

    const emailInput = await page.$('input[type="email"]');
    if (emailInput) {
      const box = await emailInput.boundingBox();
      if (box) {
        await naturalMouseMove(page, 960, 300, box.x + 50, box.y + box.height / 2);
        await pause(page, 200);
        await cursorClick(page);
        await emailInput.click();
        await emailInput.fill('');
        for (const char of EMPLOYEE_EMAIL) {
          await page.keyboard.type(char);
          await page.waitForTimeout(25 + Math.random() * 25);
        }
      }
    }
    await pause(page, PAUSE_SHORT);

    const passInput = await page.$('input[type="password"]');
    if (passInput) {
      const box = await passInput.boundingBox();
      if (box) {
        const pos = await getCursorPos(page);
        await naturalMouseMove(page, pos.x, pos.y, box.x + 50, box.y + box.height / 2);
        await pause(page, 150);
        await cursorClick(page);
        await passInput.click();
        await passInput.fill('');
        for (const char of EMPLOYEE_PASSWORD) {
          await page.keyboard.type(char);
          await page.waitForTimeout(20 + Math.random() * 25);
        }
      }
    }
    await pause(page, PAUSE_MEDIUM);

    const loginBtn = await page.$('button[type="submit"]');
    if (loginBtn) {
      const box = await loginBtn.boundingBox();
      if (box) {
        const pos = await getCursorPos(page);
        await naturalMouseMove(page, pos.x, pos.y, box.x + box.width / 2, box.y + box.height / 2, { steps: 18 });
        await pause(page, 250);
        await cursorClick(page);
        await loginBtn.click();
      }
    }
    // Wait for the login POST to complete and land on the dashboard —
    // navigating away earlier would cancel the in-flight login request.
    await page.waitForURL('**/dashboard', { timeout: 20000 });
    await page.waitForTimeout(1500);

    // ─── SCENE 2: Portal Dashboard ────────────────────────────────────────
    await navigateTo(page, `${APP_URL}/portal/dashboard`, 'portal-dashboard');
    await highlightCards(page, '[class*="StatCard"], .stat-card, [class*="glass-card"]', { max: 4, holdMs: 1800 });
    await smoothScroll(page, 300, { duration: 1200 });
    await pause(page, PAUSE_LONG);
    await smoothScroll(page, 0, { duration: 900 });

    // ─── SCENE 3: My QR ───────────────────────────────────────────────────
    await navigateTo(page, `${APP_URL}/my-qr`, 'my-qr');
    // Point at the QR code area
    const qrEl = await page.$('canvas, svg[class*="qr"], img[src*="qr"], [class*="qr"]');
    if (qrEl) {
      const box = await qrEl.boundingBox();
      if (box) {
        const pos = await getCursorPos(page);
        await naturalMouseMove(page, pos.x, pos.y, box.x + box.width / 2, box.y + box.height / 2);
        await pause(page, PAUSE_HIGHLIGHT);
      }
    }
    await pause(page, PAUSE_LONG);

    // ─── SCENE 4: Attendance History ──────────────────────────────────────
    await navigateTo(page, `${APP_URL}/portal/attendance`, 'portal-attendance');
    await smoothScroll(page, 200, { duration: 1000 });
    await highlightCards(page, 'table tbody tr', { max: 3, holdMs: 800 });
    await pause(page, PAUSE_MEDIUM);

    // ─── SCENE 5: Payroll History ─────────────────────────────────────────
    await navigateTo(page, `${APP_URL}/portal/payroll`, 'portal-payroll');
    await smoothScroll(page, 200, { duration: 1000 });
    await highlightCards(page, 'table tbody tr, [class*="glass-card"]', { max: 3, holdMs: 1000 });
    await pause(page, PAUSE_MEDIUM);

    // ─── SCENE 6: Tax Info ────────────────────────────────────────────────
    await navigateTo(page, `${APP_URL}/portal/tax`, 'portal-tax');
    await highlightCards(page, '[class*="StatCard"], [class*="glass-card"]', { max: 3, holdMs: 1500 });
    await smoothScroll(page, 250, { duration: 1000 });
    await pause(page, PAUSE_LONG);

    // ─── SCENE 7: Leaves — open the request form ──────────────────────────
    await navigateTo(page, `${APP_URL}/portal/leaves`, 'portal-leaves');

    const ajukanBtn = await page.$('button:has-text("Ajukan Cuti")');
    if (ajukanBtn) {
      const box = await ajukanBtn.boundingBox();
      if (box) {
        const pos = await getCursorPos(page);
        await naturalMouseMove(page, pos.x, pos.y, box.x + box.width / 2, box.y + box.height / 2);
        await pause(page, 300);
        await cursorClick(page);
        await ajukanBtn.click();
        await page.waitForTimeout(800);
      }
    }
    // Point at the form fields (do not submit — view only)
    await highlightCards(page, 'select, input[type="date"], textarea', { max: 3, holdMs: 1000 });
    await smoothScroll(page, 300, { duration: 1000 });
    await pause(page, PAUSE_LONG);

    // ─── SCENE 8: Ending — back to portal dashboard ───────────────────────
    await navigateTo(page, `${APP_URL}/portal/dashboard`, 'ending');
    await pause(page, PAUSE_LONG);
    await naturalMouseMove(page, 100, VIEWPORT.height / 2, VIEWPORT.width - 100, VIEWPORT.height / 2 - 100, { steps: 40, delay: 10 });
    await pause(page, 1000);

    // Premium fade-out with logo (same as admin demo)
    await page.evaluate(() => {
      const style = document.createElement('style');
      style.textContent = `
        @keyframes demo-fade-in-overlay {
          0% { opacity: 0; } 60% { opacity: 0; } 100% { opacity: 1; }
        }
        @keyframes demo-logo-rise {
          0% { opacity: 0; transform: translateY(20px) scale(0.95); }
          60% { opacity: 0; transform: translateY(20px) scale(0.95); }
          100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        #demo-ending-overlay {
          position: fixed; inset: 0; z-index: 9999998;
          display: flex; flex-direction: column; align-items: center; justify-content: center;
          background: rgba(3, 7, 18, 0.97);
          backdrop-filter: blur(8px);
          animation: demo-fade-in-overlay 3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
          pointer-events: none;
        }
        #demo-ending-logo {
          animation: demo-logo-rise 3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
          max-width: 320px; height: auto;
        }
        #demo-ending-text {
          margin-top: 20px;
          font-family: 'Plus Jakarta Sans', sans-serif;
          font-size: 14px; color: #6b7280; letter-spacing: 0.05em;
          animation: demo-logo-rise 3.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
          opacity: 0;
        }
      `;
      document.head.appendChild(style);
      const overlay = document.createElement('div');
      overlay.id = 'demo-ending-overlay';
      const existingLogo = document.querySelector('img[src*="logo"], img[alt*="logo"], img[alt*="Payroll"]');
      if (existingLogo) {
        const logoClone = existingLogo.cloneNode(true);
        logoClone.id = 'demo-ending-logo';
        overlay.appendChild(logoClone);
      } else {
        const logoDiv = document.createElement('div');
        logoDiv.id = 'demo-ending-logo';
        logoDiv.style.cssText = 'font-size: 42px; font-weight: 800; background: linear-gradient(135deg, #6366f1, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;';
        logoDiv.textContent = 'PayrollPro';
        overlay.appendChild(logoDiv);
      }
      const text = document.createElement('p');
      text.id = 'demo-ending-text';
      text.textContent = 'Terima kasih telah menonton demonstrasi ini';
      overlay.appendChild(text);
      document.body.appendChild(overlay);
    }).catch(() => {});

    await page.waitForTimeout(9000);
    await holdSceneMinimum(page);
    markScene('end');

    if (consoleErrors.length > 0) {
      console.warn(`\n  ⚠ ${consoleErrors.length} console errors detected`);
      consoleErrors.slice(0, 5).forEach(e => console.warn(`     • ${e.text?.slice(0, 120)}`));
    } else {
      console.log('\n  ✓ No console errors detected');
    }

    const timeline = {
      sessionStart: SESSION_START,
      duration: Date.now() - SESSION_START,
      scenes: TIMELINE.scenes,
      markers: TIMELINE.markers,
      consoleErrors: consoleErrors.length > 0 ? consoleErrors.slice(0, 20) : undefined,
    };
    await writeFile(TIMELINE_FILE, JSON.stringify(timeline, null, 2));
    console.log(`\n  ✓ Timeline saved to output/timeline-employee.json`);
    console.log(`  ✓ Demo recorded! Duration: ${(timeline.duration / 1000).toFixed(1)}s\n`);
  } catch (err) {
    console.error('\n  ✗ Demo recording error:', err.message);
    try {
      await writeFile(TIMELINE_FILE, JSON.stringify({
        sessionStart: SESSION_START,
        duration: Date.now() - SESSION_START,
        scenes: TIMELINE.scenes,
        markers: TIMELINE.markers,
        error: err.message,
      }, null, 2));
    } catch {}
    throw err;
  } finally {
    await context.close();
  }
}

async function main() {
  console.log('\n  ┌─────────────────────────────────────────────┐');
  console.log('  │   PayrollPro — Employee Portal Demo          │');
  console.log('  └─────────────────────────────────────────────┘\n');
  console.log(`  Target: ${APP_URL}`);
  console.log(`  Login:  ${EMPLOYEE_EMAIL}`);
  console.log(`  Output: ${RECORDING_DIR}\n`);

  await mkdir(RECORDING_DIR, { recursive: true });

  const browser = await chromium.launch({
    headless: false,
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-web-security',
      '--disable-features=IsolateOrigins,site-per-process',
      '--window-size=1920,1080',
      '--window-position=0,0',
    ],
  });

  try {
    await runDemo(browser);
  } catch (err) {
    console.error('\n  ✗ Fatal error:', err.message);
    process.exitCode = 1;
  } finally {
    await browser.close();
    console.log('\n  ✓ Browser closed.\n');
  }
}

main();
