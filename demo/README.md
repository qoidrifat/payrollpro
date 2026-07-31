# PayrollPro — Automated Demo Recording System

> **Create a premium SaaS product demonstration video for PayrollPro with one command.**

A fully automated demo recording system that uses **Playwright** to navigate PayrollPro's UI (in **dark mode**) with natural mouse movements, smooth scrolling, and cinematic timing — then renders a polished MP4 with a scene-synchronized Indonesian voice-over, ambient background music, and burned-in subtitles via **FFmpeg**.

---

## 📋 Prerequisites

| Tool        | Required | Install Instructions                                                       |
|-------------|----------|-----------------------------------------------------------------------------|
| **Node.js** | ✅ Yes   | `node -v` should be ≥ 18. Download from [nodejs.org](https://nodejs.org)   |
| **FFmpeg**  | ✅ Yes   | `winget install Gyan.FFmpeg` (auto-detected even without PATH restart)     |
| **Browser** | ✅ Yes   | Chromium (installed automatically by Playwright)                            |
| **TTS voice** | ✅ Yes | **ElevenLabs Multilingual v2, voice Mizan** (most natural — set `ELEVENLABS_API_KEY` in `demo/.env`, free 10k chars/month) → falls back to **Microsoft Edge neural TTS** (`id-ID-ArdiNeural`, free, needs internet) → falls back to offline Windows OneCore voice |

The render and narration scripts locate FFmpeg automatically: first from `PATH`, then from the WinGet package directory — no shell restart needed after installing.

---

## 🚀 Quick Start

### 1. Install Dependencies

```bash
cd demo
npm install
npx playwright install chromium
```

### 2. Make Sure the App Is Running

```bash
php artisan serve   # → http://127.0.0.1:8000 (default target)
```

### 3. Run the Demo!

```bash
npm run demo            # Admin/HR demo  → output/PayrollPro_Demo.mp4
npm run demo:employee   # Employee demo  → output/PayrollPro_Employee_Demo.mp4
```

This single command runs the full pipeline:

1. 🎬 **`record`** — Launch Chromium (dark mode forced), navigate every module with natural mouse movement, record 1920×1080 video, write `output/timeline.json` with real scene timestamps
2. 🗣️ **`narration`** — Synthesize one Indonesian TTS cue per scene, align each cue to its scene's actual start time, apply a broadcast VO chain (high-pass, presence EQ, compression, limiter), mix into `voice/narration.wav`, and regenerate `subtitle/PayrollPro_Demo.srt` with exact matching timings. Voice engine priority: **ElevenLabs (Mizan)** → **Edge neural (Ardi)** → offline Windows voice
3. 🎵 **`bgm`** — Synthesize a "Corporate Tech" backing track into `assets/bgm.wav` (100 BPM, warm pad + plucked arpeggio + soft pulse — no vocals, seamless loop)
4. 🎞️ **`render`** — FFmpeg merge: narration at 100%, BGM at 20% with end fade-out, burned subtitles, intro/outro video fades → `output/PayrollPro_Demo.mp4`

Because narration and subtitles are generated **from the actual recording timeline**, they stay perfectly in sync even if a recording run is slower or faster than the last one.

---

## 🎬 Demo Flow

### Admin/HR demo (`npm run demo`, ~3 min)

| #  | Scene               | Description                                          |
|----|---------------------|------------------------------------------------------|
| 1  | **Opening**         | PayrollPro landing page — sets the context           |
| 2  | **Login**           | Auto-fill credentials with human-like typing         |
| 3  | **Dashboard**       | Stat cards, quick actions, live attendance preview   |
| 4  | **Employees**       | Employee list and detailed profile view              |
| 5  | **Attendance**      | Attendance records with status badges                |
| 6  | **Payroll**         | Payroll list and detailed calculation breakdown      |
| 7  | **Payslip**         | Deductions modal with subtle zoom                    |
| 8  | **Reports**         | Payroll reports, chart hover, export interaction     |
| 9  | **Settings**        | Company profile, payroll config, roles & permissions |
| 10 | **Ending**          | Return to dashboard, logo fade-out                   |

Each scene has a **minimum on-screen duration** (`SCENE_MIN_SECONDS` in `scripts/demo.js`) sized to fit its narration cue, so the voice-over never spills into the next scene.

### Employee portal demo (`npm run demo:employee`, ~2 min)

| # | Scene                 | Description                                            |
|---|-----------------------|--------------------------------------------------------|
| 1 | **Opening**           | Login as an employee (self-service account)            |
| 2 | **Portal Dashboard**  | Today's attendance, clock in/out, recent payslips      |
| 3 | **My QR**             | Personal QR code for attendance scanning               |
| 4 | **Attendance History**| Personal attendance records with status                |
| 5 | **Payroll History**   | Payslips with BPJS/PPh21 breakdown, PDF download       |
| 6 | **Tax Info**          | Transparent PPh 21 calculation and tax status          |
| 7 | **Leaves**            | Leave request form and approval status tracking        |
| 8 | **Ending**            | Back to portal dashboard, logo fade-out                |

Employee login defaults to `ahmad.fauzi.1@project-kp.test` / `password` (from `EmployeeUserSeeder`); override with `DEMO_EMPLOYEE_EMAIL` / `DEMO_EMPLOYEE_PASSWORD`. Narration lives in `EMPLOYEE_NARRATION` (`scripts/build-narration.js`), scene timings in `SCENE_MIN_SECONDS` (`scripts/demo-employee.js`).

---

## 🎯 Key Features

### Natural Mouse Movement
- Bezier-curve interpolation with ease-in/ease-out
- Human-like hesitation before clicks, click ripple animation
- Idle micro-movements during pauses
- Visible premium cursor dot in video (indigo glow)

### Dark Mode
- Forced via `colorScheme: 'dark'` + an init script that sets `localStorage.darkMode = 'true'` before any page script runs, matching the app's own dark-mode bootstrapping in `resources/js/app.js`

### Scene-Synchronized Narration
- One TTS cue per scene, generated from `SCENE_NARRATION` in `scripts/build-narration.js`
- **Primary voice: Mizan** ("Deep, Soothing and Calm") via ElevenLabs `eleven_multilingual_v2` — the most human-sounding option; needs `ELEVENLABS_API_KEY` (free tier: 10,000 chars/month, our narration is ~2,000 chars)
- Automatic fallbacks: Edge neural `id-ID-ArdiNeural` (free, no key) → Windows OneCore (offline)
- Cue start times come from `output/timeline.json` (real recording timestamps)
- Broadcast VO processing: high-pass at 85 Hz, +1.5 dB presence at 3.2 kHz, 2.5:1 compression, peak limiter
- SRT subtitles are derived from the same cue timings — always in sync

### ElevenLabs setup (recommended)
```bash
cp .env.example .env
# then paste your key into demo/.env:
# ELEVENLABS_API_KEY=xi-...
npm run narration && npm run render
```
The key comes from [elevenlabs.io](https://elevenlabs.io) → Profile → API Keys (free account works). `demo/.env` is git-ignored. To use a different premade voice, set `ELEVENLABS_VOICE_ID` in `.env`.

### Video Output
- **Resolution:** 1920 × 1080 (16:9)
- **Frame Rate:** 60 FPS
- **Codec:** H.264 (libx264, CRF 18)
- **Audio:** AAC 192 kbps, 48 kHz stereo
- **Format:** MP4 with fast-start (web-optimized)
- Intro fade-in, outro fade-out, BGM fade at the end

---

## 📂 Project Structure

```
demo/
├── package.json                  # Dependencies & scripts
├── README.md                     # ← You are here
├── scripts/
│   ├── demo.js                   # Playwright automation (recording, dark mode)
│   ├── build-narration.js        # Scene-synced TTS + SRT builder
│   ├── generate-voice.js         # Legacy: single-pass TTS of narration.txt
│   ├── generate-bgm.js           # Ambient BGM synthesizer
│   └── render.js                 # FFmpeg post-processing
├── voice/
│   ├── narration.txt             # Full narration script (reference)
│   ├── narration.wav             # Mixed, scene-aligned voice track (generated)
│   ├── generate_tts_onecore.ps1  # WinRT OneCore TTS (Indonesian voice)
│   └── generate_tts_cues.ps1     # Per-scene cue synthesis
├── subtitle/
│   └── PayrollPro_Demo.srt       # Timed subtitles (generated, Indonesian)
├── assets/
│   └── bgm.wav                   # Background music (generated)
└── output/
    ├── video/                    # Raw Playwright recording (.webm)
    ├── timeline.json             # Scene timestamps from the recording
    └── PayrollPro_Demo.mp4       # ← Final rendered video
```

---

## ⚙️ Configuration

| Variable        | Default                 | Description              |
|-----------------|-------------------------|--------------------------|
| `APP_URL`       | http://127.0.0.1:8000   | Application base URL     |
| `DEMO_EMAIL`    | admin@project-kp.test   | Admin login email        |
| `DEMO_PASSWORD` | password                | Admin login password     |

Example:
```bash
APP_URL=http://localhost:8080 npm run record
```

### Changing the narration
Edit `SCENE_NARRATION` in `scripts/build-narration.js`, then re-run:
```bash
npm run narration && npm run render
```
If a scene's narration gets longer, raise its entry in `SCENE_MIN_SECONDS` (`scripts/demo.js`) and re-record.

### Using your own music
Replace `assets/bgm.wav` (or add `assets/bgm.mp3`) with any royalty-free corporate/ambient track. The render script mixes it at 20% volume automatically.

### Using a premium TTS voice
Generate audio for the script in `voice/narration.txt` with ElevenLabs / Google TTS / Azure (`id-ID` voices) and save it as `voice/narration.mp3` — the render script prefers it over the generated WAV. Note: per-scene sync only applies to the built-in pipeline.

---

## 🛠️ Individual Steps

```bash
npm run record      # Record only
npm run narration   # Rebuild scene-synced narration + SRT from last recording
npm run bgm         # Regenerate background music
npm run render      # Render final MP4 from existing inputs
```

---

## 🧪 Validation Checklist

After rendering, verify:

- ✅ No console errors (`npm run record` prints a summary; also saved in `timeline.json`)
- ✅ Video is 1920×1080 @ 60 FPS H.264, audio AAC 48 kHz — check with `ffprobe output/PayrollPro_Demo.mp4`
- ✅ Subtitles match the voice-over (both generated from the same cue timings)
- ✅ Dark mode active on every page
- ✅ Narration ends before the video does (the narration builder warns otherwise)

---

## 🩺 Troubleshooting

| Symptom | Fix |
|---------|-----|
| `FFmpeg not found` | `winget install Gyan.FFmpeg`, then re-run — no restart needed |
| English voice instead of Indonesian | Neural TTS handles Indonesian automatically; for the offline fallback, install the Indonesian language pack so *Microsoft Andika* is available |
| `Neural TTS unavailable` warning | No internet connection — the script falls back to the offline Windows voice. Re-run `npm run narration` once online for the natural neural voice |
| Demo can't log in | Ensure the database is seeded: `php artisan db:seed` (admin@project-kp.test / password) |
| Narration overlaps scenes | Re-record — `SCENE_MIN_SECONDS` holds each scene long enough for its cue |
| Blank/white pages in video | Make sure `npm run build` has been run in the project root so Vite assets exist |
