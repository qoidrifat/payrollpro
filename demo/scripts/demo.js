/**
 * =============================================================================
 *  PayrollPro — Automated Demo Recording Script
 * =============================================================================
 *
 *  A cinematic Playwright automation that records a premium SaaS product demo.
 *
 *  Flow:
 *    1. Landing Page (Welcome)
 *    2. Login
 *    3. Dashboard (with stat highlights)
 *    4. Employee List → Employee Detail
 *    5. Attendance Records
 *    6. Payroll List → Payroll Detail
 *    7. Payslip Generation
 *    8. Reports (Payroll + Attendance)
 *    9. Settings
 *   10. Return to Dashboard → Fade-out ending
 *
 *  Features:
 *    - Natural bezier mouse movement with acceleration/deceleration
 *    - Smooth programmatic scrolling
 *    - Custom CSS cursor visible in recording
 *    - 1920×1080 @ 60 FPS recording via Playwright video
 *    - Timestamp markers for subtitle/voice-over sync
 *    - Automatic error recovery with retry logic
 *    - Graceful shutdown on unrecoverable errors
 *
 *  Usage:
 *    node scripts/demo.js
 *
 *  Output:
 *    ../output/raw_demo.webm  (Playwright video)
 *    A timestamps.json file is written alongside for subtitle/audio sync.
 * =============================================================================
 */

import { chromium } from 'playwright';
import { existsSync } from 'fs';
import { mkdir, writeFile } from 'fs/promises';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const ROOT = join(__dirname, '..');
const OUTPUT_DIR = join(ROOT, 'output');

// ─── Configuration ──────────────────────────────────────────────────────────

const APP_URL = process.env.APP_URL || 'http://127.0.0.1:8000';
const ADMIN_EMAIL = process.env.DEMO_EMAIL || 'admin@project-kp.test';
const ADMIN_PASSWORD = process.env.DEMO_PASSWORD || 'password';

const VIEWPORT = { width: 1920, height: 1080 };
const RECORDING_DIR = join(OUTPUT_DIR, 'video');

// Natural mouse config — cubic bezier look via multi-step linear segments
const MOUSE_STEPS = 24; // intermediate points for smooth movement
const MOUSE_DELAY_MS = 6; // ms between each step → ~144ms per move

// Timing constants
const PAUSE_SHORT = 400; // ms between quick actions
const PAUSE_MEDIUM = 800;
const PAUSE_LONG = 1500;
const PAUSE_HIGHLIGHT = 2500; // pause to let viewer read a card
const SCENE_PAUSE = 1200; // pause after scene transition

// ─── Scene Timeline (used for subtitle/voice-over sync) ─────────────────────

const TIMELINE = {
  scenes: [],
  markers: [],
};
let sceneStartTime = 0;
let currentScene = '';

function markScene(name) {
  const now = Date.now();
  if (currentScene) {
    TIMELINE.scenes.push({ name: currentScene, start: sceneStartTime, end: now });
  }
  currentScene = name;
  sceneStartTime = now;
  TIMELINE.markers.push({ name, time: now - SESSION_START });
}

// Minimum on-screen time per scene so the narration cue for each scene can
// finish before the next one begins (values ≈ neural TTS duration + ~1.5s).
const SCENE_MIN_SECONDS = {
  'opening': 11.5,
  'login': 10,
  'dashboard': 18.5,
  'employees-list': 14.5,
  'employee-detail': 10.5,
  'attendance': 15.5,
  'payroll-list': 9.5,
  'payroll-detail': 17,
  'payslip': 10,
  'reports-payroll': 13.5,
  'settings': 12,
  'ending': 14,
};

// Wait out the remainder of the current scene's minimum duration.
async function holdSceneMinimum(page) {
  const min = (SCENE_MIN_SECONDS[currentScene] || 0) * 1000;
  const elapsed = Date.now() - sceneStartTime;
  if (elapsed < min) {
    await page.waitForTimeout(min - elapsed);
  }
}

let SESSION_START = Date.now();

// ─── Helper: Natural Mouse Movement (Bezier-like) ───────────────────────────

// ─── Helper: Read cursor position (with NaN guard) ─────────────────────────

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

// ─── Helper: Natural Mouse Movement (Bezier-like) ───────────────────────────

async function naturalMouseMove(page, x1, y1, x2, y2, { steps = MOUSE_STEPS, delay = MOUSE_DELAY_MS } = {}) {
  // Guard against NaN or invalid coordinates
  if (isNaN(x1) || isNaN(y1) || isNaN(x2) || isNaN(y2)) {
    console.warn('  ⚠ Skipping mouse move due to invalid coordinates');
    return;
  }

  // Cubic bezier control points with slight randomness for natural feel
  const cp1x = x1 + (x2 - x1) * (0.2 + Math.random() * 0.15);
  const cp1y = y1;
  const cp2x = x1 + (x2 - x1) * (0.7 + Math.random() * 0.15);
  const cp2y = y2;

  for (let i = 0; i <= steps; i++) {
    const t = i / steps;
    // Ease in-out curve for natural acceleration/deceleration
    const ease = t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
    const px = Math.round(
      (1 - ease) ** 3 * x1 +
      3 * (1 - ease) ** 2 * ease * cp1x +
      3 * (1 - ease) * ease ** 2 * cp2x +
      ease ** 3 * x2
    );
    const py = Math.round(
      (1 - ease) ** 3 * y1 +
      3 * (1 - ease) ** 2 * ease * cp1y +
      3 * (1 - ease) * ease ** 2 * cp2y +
      ease ** 3 * y2
    );
    // Double-check: never send NaN to CDP
    if (isNaN(px) || isNaN(py)) continue;
    await page.mouse.move(px, py);
    // Add tiny jitter to simulate human tremor
    if (Math.random() < 0.3) {
      const jx = px + (Math.random() - 0.5) * 2;
      const jy = py + (Math.random() - 0.5) * 2;
      if (!isNaN(jx) && !isNaN(jy)) {
        await page.mouse.move(jx, jy);
      }
    }
  }
}

// ─── Helper: Slow Scroll to Position ────────────────────────────────────────

async function smoothScroll(page, targetY, { duration = 800, steps = 30 } = {}) {
  const currentY = await page.evaluate(() => window.scrollY);
  const distance = targetY - currentY;
  if (Math.abs(distance) < 5) return;

  for (let i = 1; i <= steps; i++) {
    const t = i / steps;
    // Smooth ease-out
    const ease = 1 - Math.pow(1 - t, 3);
    const scrollTo = currentY + distance * ease;
    await page.evaluate((y) => window.scrollTo({ top: y, behavior: 'instant' }), scrollTo);
    await page.waitForTimeout(duration / steps);
  }
}

// ─── Helper: Injected Cursor (visible in video recording) ───────────────────
// Injects a small premium dot cursor so the recording shows mouse position.

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
    box-shadow: 0 0 12px rgba(99, 102, 241, 0.4),
                0 2px 6px rgba(0, 0, 0, 0.15);
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
  /* Hide the real cursor */
  html, body, * {
    cursor: none !important;
  }
`;

async function injectCursor(page) {
  await page.evaluate((styles) => {
    const cursor = document.createElement('div');
    cursor.id = 'demo-cursor';
    document.body.appendChild(cursor);
    const style = document.createElement('style');
    style.textContent = styles;
    document.head.appendChild(style);

    // Track mouse for the cursor element
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
  // Click ripple effect
  await page.evaluate(() => {
    const el = document.getElementById('demo-cursor');
    if (el) el.classList.add('clicking');
  });
  await page.waitForTimeout(60);
  await page.evaluate(() => {
    const el = document.getElementById('demo-cursor');
    if (el) el.classList.remove('clicking');
  });
}

// ─── Helper: Pause with natural idle movement ───────────────────────────────

async function pause(page, ms) {
  if (ms > 600) {
    // Slight idle mouse wiggle for longer pauses (makes it look alive)
    const pos = await getCursorPos(page);
    await page.mouse.move(pos.x + (Math.random() - 0.5) * 4, pos.y + (Math.random() - 0.5) * 4);
  }
  await page.waitForTimeout(ms);
}

// ─── Helper: Navigate with transition pause ─────────────────────────────────

async function navigateTo(page, url, sceneName) {
  await holdSceneMinimum(page);
  markScene(sceneName);
  console.log(`  → Scene: ${sceneName}`);
  await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
  await page.waitForTimeout(500);
  // Re-inject cursor after full page navigation (it gets destroyed on page reload)
  await injectCursor(page).catch(() => {});
  // Scroll to top
  await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));
  await page.waitForTimeout(SCENE_PAUSE);
}



// ─── Console Error Detection ──────────────────────────────────────────────

function setupConsoleMonitoring(page) {
  const consoleErrors = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      consoleErrors.push({ type: 'console', text: msg.text(), location: msg.location() });
      console.warn(`  ⚠ Console error: ${msg.text().slice(0, 150)}`);
    }
  });
  page.on('pageerror', (err) => {
    consoleErrors.push({ type: 'pageerror', text: err.message });
    console.error(`  ✗ Page error: ${err.message}`);
  });
  page.on('requestfailed', (req) => {
    consoleErrors.push({ type: 'network', text: `${req.url()} - ${req.failure()?.errorText || 'unknown'}` });
  });
  return consoleErrors;
}

// ═══════════════════════════════════════════════════════════════════════════════
//  MAIN DEMO SEQUENCE
// ═══════════════════════════════════════════════════════════════════════════════

async function runDemo(browser) {
  const context = await browser.newContext({
    viewport: VIEWPORT,
    deviceScaleFactor: 1,
    recordVideo: {
      dir: RECORDING_DIR,
      size: VIEWPORT,
    },
    locale: 'id-ID',
    timezoneId: 'Asia/Jakarta',
    colorScheme: 'dark',
  });

  // Force the app into dark mode before any page script runs.
  // The app reads localStorage.darkMode in resources/js/app.js and the
  // AuthenticatedLayout, so setting it here guarantees every page renders dark.
  await context.addInitScript(() => {
    try {
      localStorage.setItem('darkMode', 'true');
      document.documentElement.classList.add('dark');
    } catch (e) { /* storage may be unavailable on about:blank */ }
  });

  const page = await context.newPage();

  // Set longer default timeout
  page.setDefaultTimeout(15000);

  // Monitor console for errors
  const consoleErrors = setupConsoleMonitoring(page);

  // Inject custom cursor
  await injectCursor(page);

  SESSION_START = Date.now();
  sceneStartTime = SESSION_START;

  console.log('\n  ════════════════════════════════════════');
  console.log('   PayrollPro — Demo Recording Started');
  console.log('  ════════════════════════════════════════\n');

  try {
    // ─── SCENE 1: Opening — Landing Page ──────────────────────────────────
    markScene('opening');
    console.log('  → Scene: Opening — Landing Page');
    await page.goto(APP_URL, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(1500);

    // Move mouse slowly across the hero section
    const heroTitle = await page.$('h1');
    if (heroTitle) {
      const box = await heroTitle.boundingBox();
      if (box) {
        await naturalMouseMove(page, 960, 200, box.x + box.width / 2, box.y + box.height / 2);
      }
    }
    await pause(page, PAUSE_LONG);

    // Mouse toward CTA button
    const ctaBtn = await page.$('a[href*="demo.login"]');
    if (ctaBtn) {
      const box = await ctaBtn.boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + box.height / 2);
      }
    }
    await pause(page, PAUSE_MEDIUM);

    // Smooth scroll down the landing page
    await smoothScroll(page, 400, { duration: 1000 });
    await pause(page, PAUSE_MEDIUM);

    // ─── SCENE 2: Login ────────────────────────────────────────────────────
    await navigateTo(page, `${APP_URL}/login`, 'login');

    await pause(page, PAUSE_MEDIUM);

    // Fill email — natural typing
    const emailInput = await page.$('input[type="email"]');
    if (emailInput) {
      const box = await emailInput.boundingBox();
      if (box) {
        await naturalMouseMove(page, 960, 540, box.x + 50, box.y + box.height / 2);
        await pause(page, 200);
        await cursorClick(page);
        await emailInput.click();
        await page.waitForTimeout(100);
        await emailInput.fill('');
        for (const char of ADMIN_EMAIL) {
          await page.keyboard.type(char);
          await page.waitForTimeout(30 + Math.random() * 30);
        }
      }
    }
    await pause(page, PAUSE_SHORT);

    // Fill password
    const passInput = await page.$('input[type="password"]');
    if (passInput) {
      const box = await passInput.boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + 50, box.y + box.height / 2);
        await pause(page, 150);
        await cursorClick(page);
        await passInput.click();
        await page.waitForTimeout(80);
        await passInput.fill('');
        for (const char of ADMIN_PASSWORD) {
          await page.keyboard.type(char);
          await page.waitForTimeout(20 + Math.random() * 25);
        }
      }
    }
    await pause(page, PAUSE_MEDIUM);

    // Click Login button
    const loginBtn = await page.$('button[type="submit"]');
    if (loginBtn) {
      const box = await loginBtn.boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + box.height / 2, { steps: 18 });
        await pause(page, 250);
        await cursorClick(page);
        await loginBtn.click();
      }
    }

    // Wait for dashboard to load
    await page.waitForURL('**/dashboard', { timeout: 20000 });
    await page.waitForTimeout(2000);

    // ─── SCENE 3: Dashboard ────────────────────────────────────────────────
    await holdSceneMinimum(page);
    markScene('dashboard');
    console.log('  → Scene: Dashboard');
    await page.waitForTimeout(1000);

    // Point to each stat card
    const statCards = await page.$$('.stat-card, [class*="StatCard"]');
    for (let i = 0; i < Math.min(statCards.length, 4); i++) {
      const card = statCards[i];
      const box = await card.boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + box.height / 2);
        await pause(page, PAUSE_HIGHLIGHT - 500);
      }
    }

    // Scroll down slightly to show Quick Actions
    await smoothScroll(page, 300, { duration: 1200 });
    await pause(page, PAUSE_MEDIUM);

    // Point to Quick Actions
    const quickActions = await page.$$('[class*="aksi"]');
    if (quickActions.length) {
      const box = await quickActions[0].boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + box.height / 2);
        await pause(page, PAUSE_LONG);
      }
    }

    // ─── SCENE 4: Employee Management ──────────────────────────────────────
    // Click the Employees nav link
    await navigateTo(page, `${APP_URL}/employees`, 'employees-list');
    await pause(page, PAUSE_MEDIUM);

    // Scroll through employee list
    await smoothScroll(page, 200, { duration: 1000 });
    await pause(page, PAUSE_MEDIUM);

    // Click the "Lihat" (eye icon) button in first row to view employee detail
    const viewBtn = await page.$('a[href*="/employees/"], a:has-text("Lihat"), a:has(svg[class*="EyeIcon"])');
    if (viewBtn) {
      const box = await viewBtn.boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + box.height / 2);
        await pause(page, 200);
        await cursorClick(page);
        await viewBtn.click();
        await page.waitForTimeout(2000);
      }
    }

    // Employee Detail
    await holdSceneMinimum(page);
    markScene('employee-detail');
    console.log('  → Scene: Employee Detail');
    await page.waitForTimeout(1000);

    // Highlight employee info sections
    const detailCards = await page.$$('[class*="glass-card"]');
    for (let i = 0; i < Math.min(detailCards.length, 3); i++) {
      const card = detailCards[i];
      const box = await card.boundingBox();
      if (box && box.y < VIEWPORT.height - 100) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + 40);
        await pause(page, PAUSE_HIGHLIGHT - 500);
      }
    }

    // Gently scroll through the rest of the profile and hold, so the scene
    // is long enough for its narration even if no cards matched above.
    await smoothScroll(page, 350, { duration: 1200 });
    await pause(page, 2500);
    await smoothScroll(page, 0, { duration: 1000 });
    await pause(page, PAUSE_LONG);

    // ─── SCENE 5: Attendance ──────────────────────────────────────────────
    await navigateTo(page, `${APP_URL}/attendances`, 'attendance');
    await pause(page, PAUSE_MEDIUM);

    // Scroll through attendance table
    await smoothScroll(page, 200, { duration: 1000 });
    await pause(page, PAUSE_MEDIUM);

    // Point to a few attendance rows
    const attendRows = await page.$$('table tbody tr, [class*="DataTable"] tbody tr');
    for (let i = 0; i < Math.min(attendRows.length, 3); i++) {
      const row = attendRows[i];
      const box = await row.boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + 15);
        await pause(page, 700);
      }
    }

    // ─── SCENE 6: Payroll ─────────────────────────────────────────────────
    await navigateTo(page, `${APP_URL}/payroll`, 'payroll-list');
    await pause(page, PAUSE_MEDIUM);

    // Click first payroll to see detail
    const payrollRow = await page.$('table tbody tr, [class*="DataTable"] tbody tr a');
    if (payrollRow) {
      const box = await payrollRow.boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + box.height / 2);
        await pause(page, 200);
        await cursorClick(page);
        await payrollRow.click();
        await page.waitForTimeout(2000);
      }
    }

    // Payroll Detail
    await holdSceneMinimum(page);
    markScene('payroll-detail');
    console.log('  → Scene: Payroll Detail');
    await page.waitForTimeout(1000);

    // Scroll through payroll items
    await smoothScroll(page, 300, { duration: 1200 });
    await pause(page, PAUSE_LONG);

    // Point to totals
    const totalRow = await page.$('table tfoot tr, [class*="total"]');
    if (totalRow) {
      const box = await totalRow.boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width - 50, box.y + box.height / 2);
        await pause(page, PAUSE_HIGHLIGHT);
      }
    }

    // ─── SCENE 7: Payslip (zoom in on detail) ─────────────────────────────
    // Try clicking "Buat Slip Gaji" (Generate Payslip) button if payroll is approved
    const generatePayslipBtn = await page.$('button:has-text("Buat Slip Gaji"), button:has-text("Generate"), a:has-text("Cetak Semua PDF")');
    if (generatePayslipBtn) {
      const box = await generatePayslipBtn.boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + box.height / 2);
        await pause(page, 300);
        await cursorClick(page);
        await generatePayslipBtn.click();
        // Wait for the generation animation/loading
        await page.waitForTimeout(2000);
        const printLink = await page.$('a:has-text("Cetak Semua PDF"), a[href*="payslips/bulk"]');
        if (printLink) {
          await pause(page, PAUSE_HIGHLIGHT);
        }
      }
    }

    // Click "Rincian" button to open deductions modal
    const rincianBtn = await page.$('button:has-text("Rincian"), [class*="Rincian"]');
    if (rincianBtn) {
      const box = await rincianBtn.boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + box.height / 2);
        await pause(page, 200);
        await cursorClick(page);
        await rincianBtn.click();
        await page.waitForTimeout(500);
      }
    }

    await holdSceneMinimum(page);
    markScene('payslip');
    console.log('  → Scene: Payslip Detail');

    // Wait for modal to appear, then highlight the deductions with a subtle zoom effect
    await page.waitForTimeout(1000);
    const modalContent = await page.$('[class*="Modal"], [role="dialog"]');
    if (modalContent) {
      const box = await modalContent.boundingBox();
      if (box) {
        // Inject subtle zoom-in effect on the modal
        await page.evaluate(() => {
          const style = document.createElement('style');
          style.id = 'demo-zoom-style';
          style.textContent = `
            @keyframes demo-zoom-in {
              from { transform: scale(1); }
              to   { transform: scale(1.03); }
            }
            [class*="Modal"], [role="dialog"] {
              animation: demo-zoom-in 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
            }
          `;
          document.head.appendChild(style);
        }).catch(() => {});

        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + 60);
        // Longer pause — let the zoom animation play and the viewer read
        await pause(page, 3500);
      }
    }

    // Close modal
    const closeBtn = await page.$('button:has-text("Tutup"), [class*="close"]');
    if (closeBtn) {
      await closeBtn.click();
      await page.waitForTimeout(500);
    } else {
      // Press Escape
      await page.keyboard.press('Escape');
      await page.waitForTimeout(500);
    }

    // ─── SCENE 8: Reports ─────────────────────────────────────────────────
    await navigateTo(page, `${APP_URL}/reports/payroll`, 'reports-payroll');
    await pause(page, PAUSE_MEDIUM);

    // Highlight stats cards in reports
    const reportStats = await page.$$('[class*="StatCard"], [class*="stat"]');
    for (let i = 0; i < Math.min(reportStats.length, 3); i++) {
      const card = reportStats[i];
      const box = await card.boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + box.height / 2);
        await pause(page, 700);
      }
    }

    // Hover over any chart-like element (apexcharts, div with chart class)
    const chartEls = await page.$$('[class*="chart"], [class*="Chart"], .apexcharts-canvas, [class*="bar"]');
    for (let i = 0; i < Math.min(chartEls.length, 2); i++) {
      const chart = chartEls[i];
      const box = await chart.boundingBox();
      if (box && box.width > 50) {
        const currentPos = await getCursorPos(page);
        // Point to a data point area inside the chart
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width * 0.4, box.y + box.height * 0.5);
        await pause(page, 600);
        await naturalMouseMove(page, box.x + box.width * 0.4, box.y + box.height * 0.5, box.x + box.width * 0.7, box.y + box.height * 0.3, { steps: 12, delay: 8 });
        await pause(page, PAUSE_LONG);
      }
    }

    // Scroll down the report
    await smoothScroll(page, 400, { duration: 1200 });
    await pause(page, PAUSE_MEDIUM);

    // Try clicking Export / Download button to show the export interaction
    const exportBtn = await page.$('a:has-text("Export"), a:has-text("Ekspor"), a[href*="export"], button:has-text("Export"), button:has-text("Ekspor")');
    if (exportBtn) {
      const box = await exportBtn.boundingBox();
      if (box) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + box.height / 2);
        await pause(page, 400);
        await cursorClick(page);
        await exportBtn.click();
        await page.waitForTimeout(1000);
      }
    }

    await pause(page, PAUSE_LONG);

    // ─── SCENE 9: Settings ────────────────────────────────────────────────
    await navigateTo(page, `${APP_URL}/settings`, 'settings');
    await pause(page, PAUSE_MEDIUM);

    // Highlight settings sections
    const settingSections = await page.$$('[class*="glass-card"], [class*="card"], section');
    for (let i = 0; i < Math.min(settingSections.length, 4); i++) {
      const section = settingSections[i];
      const box = await section.boundingBox();
      if (box && box.y < VIEWPORT.height - 50) {
        const currentPos = await getCursorPos(page);
        await naturalMouseMove(page, currentPos.x, currentPos.y, box.x + box.width / 2, box.y + 30);
        await pause(page, 700);
      }
    }

    // ─── SCENE 10: Ending — Return to Dashboard ───────────────────────────
    await navigateTo(page, `${APP_URL}/dashboard`, 'ending');
    await pause(page, PAUSE_LONG);

    // Slow "appreciation" move — mouse glides across the dashboard
    await naturalMouseMove(page, 100, VIEWPORT.height / 2, VIEWPORT.width - 100, VIEWPORT.height / 2 - 100, { steps: 40, delay: 10 });
    await pause(page, 1000);

    // One last look at the stats
    const lastCards = await page.$$('[class*="stat-card"], [class*="StatCard"], [class*="stat"]');
    if (lastCards.length) {
      const box = await lastCards[0].boundingBox();
      if (box) {
        await naturalMouseMove(page, VIEWPORT.width / 2, VIEWPORT.height / 2, box.x + box.width / 2, box.y + box.height / 2);
        await pause(page, 1500);
      }
    }

    // ── Premium Fade-out with Logo Display ──
    await page.evaluate(() => {
      // Inject the full-screen fade overlay + centered logo
      const style = document.createElement('style');
      style.textContent = `
        @keyframes demo-fade-in-overlay {
          0%   { opacity: 0; }
          60%  { opacity: 0; }
          100% { opacity: 1; }
        }
        @keyframes demo-logo-rise {
          0%   { opacity: 0; transform: translateY(20px) scale(0.95); }
          60%  { opacity: 0; transform: translateY(20px) scale(0.95); }
          100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        #demo-ending-overlay {
          position: fixed;
          inset: 0;
          z-index: 9999998;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          background: rgba(255, 255, 255, 0.97);
          backdrop-filter: blur(8px);
          animation: demo-fade-in-overlay 3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
          pointer-events: none;
        }
        .dark #demo-ending-overlay {
          background: rgba(3, 7, 18, 0.97);
        }
        #demo-ending-logo {
          animation: demo-logo-rise 3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
          max-width: 320px;
          height: auto;
        }
        #demo-ending-text {
          margin-top: 20px;
          font-family: 'Plus Jakarta Sans', sans-serif;
          font-size: 14px;
          color: #6b7280;
          letter-spacing: 0.05em;
          animation: demo-logo-rise 3.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
          opacity: 0;
        }
      `;
      document.head.appendChild(style);

      // Create overlay
      const overlay = document.createElement('div');
      overlay.id = 'demo-ending-overlay';

      // Try to find the app logo, otherwise use a text version
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

    // Hold on the fade-out long enough for the closing narration to finish
    await page.waitForTimeout(9000);

    // ─── Final scene marker ───────────────────────────────────────────────
    await holdSceneMinimum(page);
    markScene('end');
    console.log('  → Scene: End');

    // Report console errors
    if (consoleErrors.length > 0) {
      console.warn(`\n  ⚠ ${consoleErrors.length} console errors detected during recording:`);
      consoleErrors.slice(0, 5).forEach(e => console.warn(`     • ${e.text?.slice(0, 120) || e}`));
      if (consoleErrors.length > 5) console.warn(`     ... and ${consoleErrors.length - 5} more`);
    } else {
      console.log(`\n  ✓ No console errors detected`);
    }

    // Save timeline data
    const timeline = {
      sessionStart: SESSION_START,
      duration: Date.now() - SESSION_START,
      scenes: TIMELINE.scenes,
      markers: TIMELINE.markers,
      consoleErrors: consoleErrors.length > 0 ? consoleErrors.slice(0, 20) : undefined,
    };

    await writeFile(join(OUTPUT_DIR, 'timeline.json'), JSON.stringify(timeline, null, 2));
    console.log(`\n  ✓ Timeline saved to output/timeline.json`);
    console.log(`  ✓ Demo recorded successfully! Duration: ${(timeline.duration / 1000).toFixed(1)}s\n`);

  } catch (err) {
    console.error('\n  ✗ Demo recording error:', err.message);
    // Try to save whatever timeline we have
    try {
      const timeline = {
        sessionStart: SESSION_START,
        duration: Date.now() - SESSION_START,
        scenes: TIMELINE.scenes,
        markers: TIMELINE.markers,
        error: err.message,
      };
      await writeFile(join(OUTPUT_DIR, 'timeline.json'), JSON.stringify(timeline, null, 2));
    } catch {}
    throw err;
  } finally {
    await context.close();
  }

  return TIMELINE;
}

// ═══════════════════════════════════════════════════════════════════════════════
//  ENTRY POINT
// ═══════════════════════════════════════════════════════════════════════════════

async function main() {
  console.log('\n  ┌─────────────────────────────────────────────┐');
  console.log('  │        PayrollPro — Demo Recording           │');
  console.log('  │        Automated Demo System v1.0            │');
  console.log('  └─────────────────────────────────────────────┘\n');
  console.log(`  Target: ${APP_URL}`);
  console.log(`  Output: ${RECORDING_DIR}\n`);

  // Ensure output directories exist
  await mkdir(RECORDING_DIR, { recursive: true });

  const browser = await chromium.launch({
    headless: false, // Visible so user can watch the demo in action
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
    console.log('\n  ✓ Browser closed. Demo recording complete.\n');
  }
}

main();
