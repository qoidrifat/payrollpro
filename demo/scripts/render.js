/**
 * =============================================================================
 *  PayrollPro — Demo Render Pipeline
 * =============================================================================
 *
 *  Post-processes the raw Playwright recording into a polished demo video:
 *
 *    1. Compress/interpolate raw video
 *    2. Add voice-over narration (if available)
 *    3. Add background music (if available)
 *    4. Burn subtitles
 *    5. Add intro/outro fade effects
 *    6. Output final 1920×1080 60fps H.264 MP4
 *
 *  Usage:
 *    node scripts/render.js
 *
 *  Required:
 *    - FFmpeg installed and available in PATH
 *    - Raw video from Playwright in output/video/
 *    - (Optional) voice/narration.mp3
 *    - (Optional) assets/bgm.mp3
 *
 *  Output:
 *    output/PayrollPro_Demo.mp4
 * =============================================================================
 */

import { readdir, mkdir, stat, readFile, writeFile } from 'fs/promises';
import { existsSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { spawn, execSync, execFileSync } from 'child_process';
import { homedir } from 'os';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const ROOT = join(__dirname, '..');
const OUTPUT_DIR = join(ROOT, 'output');

// Profile: 'admin' (default) or 'employee' — selects input video dir,
// narration file, subtitle, and final output name.
const PROFILE = process.env.DEMO_PROFILE || (process.argv.includes('--employee') ? 'employee' : 'admin');
const IS_EMPLOYEE = PROFILE === 'employee';

const VIDEO_DIR = join(OUTPUT_DIR, IS_EMPLOYEE ? 'video-employee' : 'video');
const VOICE_DIR = join(ROOT, 'voice');
const ASSETS_DIR = join(ROOT, 'assets');
const SUBTITLE_DIR = join(ROOT, 'subtitle');

const NARRATION_BASE = IS_EMPLOYEE ? 'narration-employee' : 'narration';
const SUBTITLE_NAME = IS_EMPLOYEE ? 'PayrollPro_Employee_Demo.srt' : 'PayrollPro_Demo.srt';
const OUTPUT_VIDEO = join(OUTPUT_DIR, IS_EMPLOYEE ? 'PayrollPro_Employee_Demo.mp4' : 'PayrollPro_Demo.mp4');
const TEMP_DIR = join(OUTPUT_DIR, '.temp');

// ─── Configuration ──────────────────────────────────────────────────────────

const CONFIG = {
  resolution: '1920:1080',
  fps: 60,
  codec: 'h264',
  videoBitrate: '20M',
  audioBitrate: '192k',
};

// ─── Utilities ──────────────────────────────────────────────────────────────

// Resolve an ffmpeg binary: PATH first, then the winget (Gyan.FFmpeg) install
// location, which is not visible to shells opened before the install.
function resolveFfmpeg() {
  try {
    execSync('ffmpeg -version', { stdio: 'ignore' });
    return 'ffmpeg';
  } catch {}
  const wingetRoot = join(homedir(), 'AppData', 'Local', 'Microsoft', 'WinGet', 'Packages');
  if (existsSync(wingetRoot)) {
    try {
      const found = execFileSync('powershell', [
        '-NoProfile', '-Command',
        "Get-ChildItem -Path $env:LOCALAPPDATA\\Microsoft\\WinGet\\Packages -Recurse -Filter ffmpeg.exe -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName",
      ], { encoding: 'utf-8' }).trim();
      if (found && existsSync(found)) return found;
    } catch {}
  }
  return null;
}

const FFMPEG_BIN = resolveFfmpeg();
const FFPROBE_BIN = FFMPEG_BIN === 'ffmpeg'
  ? 'ffprobe'
  : (FFMPEG_BIN ? join(dirname(FFMPEG_BIN), 'ffprobe.exe') : null);

function probeDuration(file) {
  try {
    const out = execFileSync(FFPROBE_BIN, [
      '-v', 'error',
      '-show_entries', 'format=duration',
      '-of', 'default=noprint_wrappers=1:nokey=1',
      file,
    ], { encoding: 'utf-8' }).trim();
    const dur = parseFloat(out);
    return isNaN(dur) ? null : dur;
  } catch {
    return null;
  }
}

function ffmpeg(...args) {
  return new Promise((resolve, reject) => {
    const proc = spawn(FFMPEG_BIN, args, { stdio: ['ignore', 'pipe', 'pipe'] });
    let stderr = '';
    proc.stderr.on('data', (d) => { stderr += d.toString(); });
    proc.on('close', (code) => {
      if (code === 0) resolve();
      else reject(new Error(`FFmpeg exited with code ${code}\n${stderr.slice(-500)}`));
    });
    proc.on('error', reject);
  });
}

async function getVideoFile() {
  try {
    const list = await readdir(VIDEO_DIR);
    return (
      list.find(f => f.endsWith('.webm')) ||
      list.find(f => f.endsWith('.mp4')) ||
      list[0]
    );
  } catch {
    return null;
  }
}

async function filePathOrNull(dir, filename) {
  const path = join(dir, filename);
  return existsSync(path) ? path : null;
}

// ─── Steps ──────────────────────────────────────────────────────────────────

async function ensureDirs() {
  await mkdir(TEMP_DIR, { recursive: true });
  await mkdir(OUTPUT_DIR, { recursive: true });
}

async function detectInputs() {
  const videoFile = await getVideoFile();
  if (!videoFile) {
    console.error('  ✗ No video file found in output/video/');
    console.error('  Please run "npm run record" first.');
    process.exit(1);
  }

  const rawVideo = join(VIDEO_DIR, videoFile);
  const narrationMp3 = await filePathOrNull(VOICE_DIR, `${NARRATION_BASE}.mp3`);
  const narrationWav = await filePathOrNull(VOICE_DIR, `${NARRATION_BASE}.wav`);
  const narrationRaw = IS_EMPLOYEE ? null : await filePathOrNull(OUTPUT_DIR, 'narration_raw.wav');
  const bgm = (await filePathOrNull(ASSETS_DIR, 'bgm.mp3')) || (await filePathOrNull(ASSETS_DIR, 'bgm.wav'));
  const subtitle = await filePathOrNull(SUBTITLE_DIR, SUBTITLE_NAME);

  const narration = narrationMp3 || narrationWav || narrationRaw;

  console.log(`  Input video:      ${rawVideo}`);
  console.log(`  Narration:        ${narration || '(not found — will render without voice-over)'}`);
  console.log(`  Background music: ${bgm || '(not found — will render without BGM)'}`);
  console.log(`  Subtitles:        ${subtitle || '(not found — will render without subs)'}`);

  return { rawVideo, narration, bgm, subtitle };
}

async function buildFFmpegCommand(inputs) {
  const { rawVideo, narration, bgm, subtitle } = inputs;
  const filterComplexParts = [];
  const mapArgs = [];
  const inputArgs = [];

  const videoDuration = probeDuration(rawVideo);
  if (videoDuration) {
    console.log(`  Video duration:   ${videoDuration.toFixed(1)}s`);
  }

  // Input: raw video
  inputArgs.push('-i', rawVideo);

  let nextInputIdx = 1; // 0 = raw video

  // Input: narration — padded with silence so BGM keeps playing after
  // the voice-over ends, until the video itself ends.
  if (narration) {
    inputArgs.push('-i', narration);
    filterComplexParts.push(`[${nextInputIdx}:a]volume=1.0,apad[narration_audio]`);
    nextInputIdx++;
  }

  // Input: BGM (looped, 20% volume, gentle fade-out at the end)
  if (bgm) {
    inputArgs.push('-stream_loop', '-1', '-i', bgm);
    const bgmFade = videoDuration
      ? `,afade=t=out:st=${Math.max(0, videoDuration - 3).toFixed(2)}:d=3`
      : '';
    filterComplexParts.push(`[${nextInputIdx}:a]volume=0.2${bgmFade}[bgm_audio]`);
    nextInputIdx++;
  }

  // Build audio mixing
  const audioInputLabels = [];
  if (narration) audioInputLabels.push('[narration_audio]');
  if (bgm) audioInputLabels.push('[bgm_audio]');

  // ── Audio Mapping ────────────────────────────────────────────────────
  if (audioInputLabels.length > 0) {
    const mixInputStr = audioInputLabels.join('');
    filterComplexParts.push(`${mixInputStr}amix=inputs=${audioInputLabels.length}:duration=longest:dropout_transition=2,alimiter=limit=0.95[mixed_audio]`);
    mapArgs.push('-map', '[mixed_audio]');
  } else {
    // No audio inputs — keep original video audio (if any)
    mapArgs.push('-map', '0:a?');
  }

  // ── Video Mapping ────────────────────────────────────────────────────
  // Intro fade-in and outro fade-out for a premium feel.
  const videoFilters = ['fade=t=in:st=0:d=0.8'];
  if (videoDuration) {
    videoFilters.push(`fade=t=out:st=${Math.max(0, videoDuration - 1.2).toFixed(2)}:d=1.2`);
  }
  if (subtitle) {
    // Escape the subtitle path for the FFmpeg filter parser: backslashes →
    // forward slashes, then quote the filename and escape the drive colon.
    const subPath = subtitle.replace(/\\/g, '/').replace(/:/g, '\\:');
    videoFilters.push(`subtitles=filename='${subPath}':force_style='FontName=Segoe UI,FontSize=17,PrimaryColour=&HF5F5F5&,OutlineColour=&H80000000&,BorderStyle=1,Outline=1,Shadow=0,MarginV=28'`);
  }
  filterComplexParts.push(`[0:v]${videoFilters.join(',')}[video_output]`);
  mapArgs.unshift('-map', '[video_output]');

  // Build the full filter_complex argument
  let filterComplexArg = null;
  if (filterComplexParts.length > 0) {
    filterComplexArg = filterComplexParts.join(';');
  }

  // Build full command args
  const cmdArgs = [
    ...inputArgs,
    ...(filterComplexArg ? ['-filter_complex', filterComplexArg] : []),
    ...mapArgs,
    '-c:v', 'libx264',
    '-preset', 'medium',
    '-crf', '18',
    '-r', String(CONFIG.fps),
    '-s', CONFIG.resolution,
    '-pix_fmt', 'yuv420p',
    '-c:a', 'aac',
    '-b:a', CONFIG.audioBitrate,
    '-ar', '48000',
    '-movflags', '+faststart',
    // Trim to video length — the looped BGM and padded narration are infinite.
    ...(videoDuration ? ['-t', videoDuration.toFixed(2)] : ['-shortest']),
    '-y',
    OUTPUT_VIDEO,
  ];

  return cmdArgs;
}

// ─── Main ───────────────────────────────────────────────────────────────────

async function main() {
  console.log('\n  ┌─────────────────────────────────────────────┐');
  console.log('  │       PayrollPro — Demo Render Pipeline     │');
  console.log('  └─────────────────────────────────────────────┘\n');
  console.log(`  Profile: ${PROFILE}`);

  await ensureDirs();

  if (!FFMPEG_BIN) {
    console.error('  ✗ FFmpeg not found in PATH or WinGet packages.');
    console.error('  Install it with: winget install Gyan.FFmpeg');
    process.exit(1);
  }
  console.log(`  FFmpeg: ${FFMPEG_BIN}\n`);

  const inputs = await detectInputs();

  const cmdArgs = await buildFFmpegCommand(inputs);

  console.log('\n  Rendering final video...\n');

  try {
    await ffmpeg(...cmdArgs);
    console.log(`\n  ✓ Final video rendered!`);
    console.log(`  → ${OUTPUT_VIDEO}\n`);

    // Get file size
    try {
      const fileStats = await stat(OUTPUT_VIDEO);
      const sizeMB = (fileStats.size / (1024 * 1024)).toFixed(1);
      console.log(`  File size: ${sizeMB} MB`);
    } catch {}

    // Clean up temp
    try {
      const { rm } = await import('fs/promises');
      await rm(TEMP_DIR, { recursive: true, force: true });
    } catch {}
  } catch (err) {
    console.error('\n  ✗ Render failed:', err.message);
    console.error('\n  Tips:');
    console.error('  - Make sure FFmpeg is installed and in your PATH');
    console.error('  - On Windows: Download from https://ffmpeg.org/download.html');
    console.error('  - On macOS: brew install ffmpeg');
    console.error('  - On Linux: sudo apt install ffmpeg');
    process.exit(1);
  }
}

main();
