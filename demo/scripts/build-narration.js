/**
 * =============================================================================
 *  PayrollPro — Scene-Synchronized Narration Builder
 * =============================================================================
 *
 *  Reads output/timeline.json (produced by scripts/demo.js), synthesizes one
 *  Indonesian TTS cue per scene, aligns each cue to its scene's real start
 *  time in the recording, then:
 *
 *    1. Mixes all cues into a single voice/narration.wav (silence between)
 *    2. Writes subtitle/PayrollPro_Demo.srt with timings that match exactly
 *
 *  This guarantees voice-over and subtitles stay in sync with what is on
 *  screen, regardless of how long each recording run actually takes.
 *
 *  Usage:  node scripts/build-narration.js
 * =============================================================================
 */

import { readFileSync, writeFileSync, existsSync, mkdirSync, rmSync, readdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { execFileSync } from 'child_process';
import { homedir } from 'os';
import { MsEdgeTTS, OUTPUT_FORMAT } from 'msedge-tts';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ROOT = join(__dirname, '..');
const OUTPUT_DIR = join(ROOT, 'output');
const VOICE_DIR = join(ROOT, 'voice');
const SUBTITLE_DIR = join(ROOT, 'subtitle');

// Profile: 'admin' (default) or 'employee' — selects timeline, narration
// script, and output file names. Set via DEMO_PROFILE=employee or --employee.
const PROFILE = process.env.DEMO_PROFILE || (process.argv.includes('--employee') ? 'employee' : 'admin');
const IS_EMPLOYEE = PROFILE === 'employee';

const CUES_DIR = join(OUTPUT_DIR, IS_EMPLOYEE ? '.cues-employee' : '.cues');
const TIMELINE_FILE = join(OUTPUT_DIR, IS_EMPLOYEE ? 'timeline-employee.json' : 'timeline.json');
const OUTPUT_WAV = join(VOICE_DIR, IS_EMPLOYEE ? 'narration-employee.wav' : 'narration.wav');
const OUTPUT_SRT = join(SUBTITLE_DIR, IS_EMPLOYEE ? 'PayrollPro_Employee_Demo.srt' : 'PayrollPro_Demo.srt');

const TTS_RATE = 1.3;

// ─── ElevenLabs TTS (primary — most human-sounding) ─────────────────────────
// Model: eleven_multilingual_v2 (free tier, 1 credit/char).
// Default voice: Adam — deep, firm premade voice (free-tier API compatible).
// NOTE: featured "Indonesian" voices on elevenlabs.io (Mizan, Ahmad, ...) are
// Voice Library community voices — the API rejects them for free accounts
// ("You need to be on the creator tier or above to use this voice").
// Premade voices work on the free tier and Multilingual v2 speaks fluent
// Indonesian with any of them.
// Requires ELEVENLABS_API_KEY in the environment or in demo/.env; skipped when absent.

// Load demo/.env (KEY=value lines) so users can store the API key in a file.
function loadDotEnv() {
  const envFile = join(ROOT, '.env');
  if (!existsSync(envFile)) return;
  for (const line of readFileSync(envFile, 'utf-8').split('\n')) {
    const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*"?([^"\r\n]*)"?\s*$/);
    if (m && !process.env[m[1]]) process.env[m[1]] = m[2];
  }
}
loadDotEnv();

const ELEVENLABS_API_KEY = process.env.ELEVENLABS_API_KEY || '';
const ELEVENLABS_VOICE_ID = process.env.ELEVENLABS_VOICE_ID || 'pNInz6obpgDQGcFmaJgB'; // Adam (premade)
const ELEVENLABS_MODEL = process.env.ELEVENLABS_MODEL || 'eleven_multilingual_v2';

async function synthesizeElevenLabsCue(text, outFile) {
  const res = await fetch(
    `https://api.elevenlabs.io/v1/text-to-speech/${ELEVENLABS_VOICE_ID}?output_format=mp3_44100_128`,
    {
      method: 'POST',
      headers: {
        'xi-api-key': ELEVENLABS_API_KEY,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        text,
        model_id: ELEVENLABS_MODEL,
        voice_settings: {
          stability: 0.55,        // steady, professional delivery
          similarity_boost: 0.75,
          style: 0.2,             // slight expressiveness, not theatrical
          use_speaker_boost: true,
        },
      }),
    }
  );
  if (!res.ok) {
    const body = await res.text().catch(() => '');
    throw new Error(`ElevenLabs HTTP ${res.status}: ${body.slice(0, 200)}`);
  }
  const buf = Buffer.from(await res.arrayBuffer());
  if (buf.length < 1000) throw new Error(`ElevenLabs returned ${buf.length} bytes`);
  writeFileSync(outFile, buf);
}

async function synthesizeElevenLabsCues(cues, outDir) {
  console.log(`  Voice ID: ${ELEVENLABS_VOICE_ID} (ElevenLabs ${ELEVENLABS_MODEL})`);
  // Cue cache: skip the API call when the same text was already synthesized
  // with the same voice+model (saves free-tier credits on re-records).
  const cacheMetaFile = join(outDir, 'cache.json');
  let cache = {};
  try { cache = JSON.parse(readFileSync(cacheMetaFile, 'utf-8')); } catch {}

  for (const cue of cues) {
    const outFile = join(outDir, `cue_${String(cue.index).padStart(3, '0')}.mp3`);
    const cacheKey = `${ELEVENLABS_VOICE_ID}|${ELEVENLABS_MODEL}|${cue.text}`;
    if (cache[String(cue.index)] === cacheKey && existsSync(outFile)) {
      process.stdout.write(`  cue ${cue.index}/${cues.length} (cached)\r`);
      continue;
    }
    let lastErr;
    for (let attempt = 1; attempt <= 3; attempt++) {
      try {
        await synthesizeElevenLabsCue(cue.text, outFile);
        lastErr = null;
        break;
      } catch (err) {
        lastErr = err;
        // Free-tier voice/plan errors won't fix themselves — fail fast
        if (/HTTP 4\d\d/.test(err.message)) throw err;
        console.warn(`    retry ${attempt}/3 for cue ${cue.index}: ${err.message}`);
        await new Promise(r => setTimeout(r, 2000 * attempt));
      }
    }
    if (lastErr) throw lastErr;
    cache[String(cue.index)] = cacheKey;
    writeFileSync(cacheMetaFile, JSON.stringify(cache, null, 2), 'utf-8');
    process.stdout.write(`  cue ${cue.index}/${cues.length} ✓\r`);
  }
  process.stdout.write('\n');
}

// ─── Neural TTS (Microsoft Edge) ────────────────────────────────────────────
// id-ID-ArdiNeural is a natural-sounding Indonesian male neural voice —
// far more professional than the robotic Windows OneCore/SAPI voices.
// Fallback voice: id-ID-GadisNeural (female).

const NEURAL_VOICE = process.env.TTS_VOICE || 'id-ID-ArdiNeural';
// Slightly slower than default reads as calm and confident for business VO.
const NEURAL_RATE = process.env.TTS_NEURAL_RATE || '-4%';
const NEURAL_PITCH = process.env.TTS_NEURAL_PITCH || '-2Hz';

async function synthesizeNeuralCue(text, outFile) {
  const tts = new MsEdgeTTS();
  await tts.setMetadata(NEURAL_VOICE, OUTPUT_FORMAT.AUDIO_24KHZ_96KBITRATE_MONO_MP3);
  const { audioStream } = await tts.toStream(text, { rate: NEURAL_RATE, pitch: NEURAL_PITCH });
  const chunks = [];
  await new Promise((resolve, reject) => {
    audioStream.on('data', (c) => chunks.push(c));
    audioStream.on('end', resolve);
    audioStream.on('error', reject);
  });
  const buf = Buffer.concat(chunks);
  if (buf.length < 1000) throw new Error(`Neural TTS returned ${buf.length} bytes for cue`);
  writeFileSync(outFile, buf);
  tts.close();
}

async function synthesizeNeuralCues(cues, outDir) {
  console.log(`  Voice: ${NEURAL_VOICE} (Edge neural, rate ${NEURAL_RATE})`);
  for (const cue of cues) {
    const outFile = join(outDir, `cue_${String(cue.index).padStart(3, '0')}.mp3`);
    // Retry transient network failures up to 3 times
    let lastErr;
    for (let attempt = 1; attempt <= 3; attempt++) {
      try {
        await synthesizeNeuralCue(cue.text, outFile);
        lastErr = null;
        break;
      } catch (err) {
        lastErr = err;
        console.warn(`    retry ${attempt}/3 for cue ${cue.index}: ${err.message}`);
        await new Promise(r => setTimeout(r, 1500 * attempt));
      }
    }
    if (lastErr) throw lastErr;
    process.stdout.write(`  cue ${cue.index}/${cues.length} ✓\r`);
  }
  process.stdout.write('\n');
}

// ─── Narration per scene (formal Indonesian) ───────────────────────────────
// Keys must match the markScene() names in scripts/demo.js.

const ADMIN_NARRATION = {
  'opening': 'Selamat datang di PayrollPro, sebuah sistem penggajian modern yang dirancang untuk membantu perusahaan mengelola proses payroll secara lebih cepat, akurat, dan efisien.',
  'login': 'Mari kita mulai. Silakan masuk menggunakan akun Anda untuk mengakses seluruh fungsi manajemen penggajian dalam satu platform yang aman.',
  'dashboard': 'Ini adalah halaman Dasbor. Di sini Anda dapat melihat ringkasan operasional perusahaan secara langsung: total karyawan aktif, kehadiran hari ini, total penggajian bulan berjalan, serta aktivitas terbaru, semuanya tersaji dalam satu tampilan yang informatif.',
  'employees-list': 'Selanjutnya, modul Manajemen Karyawan. Seluruh data karyawan terpusat dalam satu tabel yang rapi, lengkap dengan nomor induk, jabatan, departemen, dan status kepegawaian.',
  'employee-detail': 'Detail setiap karyawan mencakup data pribadi, riwayat kepegawaian, gaji pokok, hingga informasi bank dan nomor BPJS.',
  'attendance': 'Modul Absensi mencatat kehadiran karyawan secara lengkap, mulai dari jam masuk, jam pulang, keterlambatan, cuti, hingga ketidakhadiran. Data ini terintegrasi langsung dengan perhitungan penggajian.',
  'payroll-list': 'Sekarang kita masuk ke modul Penggajian. Anda dapat membuat dan mengelola proses penggajian untuk setiap periode dengan mudah.',
  'payroll-detail': 'PayrollPro menghitung seluruh komponen secara otomatis: gaji pokok, tunjangan, bonus, dan lembur, hingga potongan BPJS Kesehatan, BPJS Ketenagakerjaan, serta PPh dua puluh satu dengan skema tarif efektif rata-rata terbaru.',
  'payslip': 'Slip gaji dihasilkan secara otomatis dalam format PDF profesional, transparan, dan siap dibagikan kepada seluruh karyawan.',
  'reports-payroll': 'Modul Laporan menyajikan data penggajian dan kehadiran dalam format yang informatif. Laporan dapat difilter berdasarkan periode, dan diekspor untuk kebutuhan manajemen maupun akuntansi.',
  'settings': 'Halaman Pengaturan memberikan kendali penuh atas konfigurasi sistem: profil perusahaan, konfigurasi penggajian, tarif BPJS, hingga peran dan izin akses.',
  'ending': 'Dengan antarmuka yang modern, perhitungan yang akurat, dan fitur yang lengkap, PayrollPro adalah solusi penggajian yang tepat untuk perusahaan Anda. Terima kasih telah menyaksikan demonstrasi ini.',
};

// Keys must match the markScene() names in scripts/demo-employee.js.
const EMPLOYEE_NARRATION = {
  'opening': 'Selain untuk tim HR, PayrollPro juga menyediakan portal mandiri bagi setiap karyawan. Mari kita lihat pengalaman PayrollPro dari sisi karyawan, dimulai dengan masuk menggunakan akun pribadi.',
  'portal-dashboard': 'Ini adalah Dasbor Karyawan. Setiap karyawan dapat melihat status absensi hari ini, jam masuk dan jam pulang, serta jumlah pengajuan cuti yang sedang diproses, semuanya dalam satu tampilan yang sederhana dan mudah dipahami.',
  'my-qr': 'Untuk absensi, setiap karyawan memiliki kode QR pribadi. Cukup pindai kode ini di perangkat absensi kantor untuk mencatat jam masuk dan jam pulang secara real-time.',
  'portal-attendance': 'Pada halaman Riwayat Absensi, karyawan dapat memantau catatan kehadirannya sendiri, lengkap dengan status hadir, terlambat, cuti, maupun tidak hadir.',
  'portal-payroll': 'Halaman Riwayat Gaji menampilkan seluruh slip gaji yang pernah diterima. Karyawan dapat melihat rincian gaji pokok, tunjangan, dan potongan, serta mengunduh slip gaji dalam format PDF kapan saja.',
  'portal-tax': 'Informasi pajak juga tersedia secara transparan. Karyawan dapat melihat rincian perhitungan PPh dua puluh satu beserta status pajaknya tanpa perlu bertanya kepada tim HR.',
  'portal-leaves': 'Untuk pengajuan cuti, karyawan cukup mengisi formulir singkat: jenis cuti, tanggal mulai, tanggal selesai, dan alasannya. Status persetujuan dapat dipantau langsung dari halaman yang sama.',
  'ending': 'Dengan portal mandiri ini, karyawan memperoleh transparansi penuh atas data kehadiran, gaji, dan pajaknya, sementara tim HR terbebas dari pertanyaan yang berulang. Terima kasih telah menyaksikan demonstrasi ini.',
};

const SCENE_NARRATION = IS_EMPLOYEE ? EMPLOYEE_NARRATION : ADMIN_NARRATION;

// ─── FFmpeg / FFprobe resolution ────────────────────────────────────────────

function resolveBin(name) {
  try {
    execFileSync(name, ['-version'], { stdio: 'ignore' });
    return name;
  } catch {}
  try {
    const found = execFileSync('powershell', [
      '-NoProfile', '-Command',
      `Get-ChildItem -Path $env:LOCALAPPDATA\\Microsoft\\WinGet\\Packages -Recurse -Filter ${name}.exe -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName`,
    ], { encoding: 'utf-8' }).trim();
    if (found && existsSync(found)) return found;
  } catch {}
  return null;
}

const FFMPEG = resolveBin('ffmpeg');
const FFPROBE = resolveBin('ffprobe');

function probeDuration(file) {
  const out = execFileSync(FFPROBE, [
    '-v', 'error',
    '-show_entries', 'format=duration',
    '-of', 'default=noprint_wrappers=1:nokey=1',
    file,
  ], { encoding: 'utf-8' }).trim();
  return parseFloat(out);
}

// ─── SRT helpers ────────────────────────────────────────────────────────────

function srtTime(seconds) {
  const ms = Math.max(0, Math.round(seconds * 1000));
  const h = String(Math.floor(ms / 3600000)).padStart(2, '0');
  const m = String(Math.floor((ms % 3600000) / 60000)).padStart(2, '0');
  const s = String(Math.floor((ms % 60000) / 1000)).padStart(2, '0');
  const mss = String(ms % 1000).padStart(3, '0');
  return `${h}:${m}:${s},${mss}`;
}

// Split a cue's text into subtitle chunks (~90 chars max), distributing the
// cue's real audio duration proportionally to chunk length.
function splitIntoSubtitles(text, start, duration) {
  const words = text.split(/\s+/);
  const chunks = [];
  let current = '';
  for (const w of words) {
    if ((current + ' ' + w).trim().length > 90 && current) {
      chunks.push(current.trim());
      current = w;
    } else {
      current = (current + ' ' + w).trim();
    }
  }
  if (current) chunks.push(current.trim());

  const totalChars = chunks.reduce((a, c) => a + c.length, 0);
  const entries = [];
  let t = start;
  for (const chunk of chunks) {
    const d = duration * (chunk.length / totalChars);
    entries.push({ start: t, end: t + d, text: chunk });
    t += d;
  }
  return entries;
}

// ─── Main ───────────────────────────────────────────────────────────────────

async function main() {
  console.log('\n  ┌─────────────────────────────────────────────┐');
  console.log('  │   PayrollPro — Narration Sync Builder       │');
  console.log('  └─────────────────────────────────────────────┘\n');
  console.log(`  Profile: ${PROFILE}`);

  if (!FFMPEG || !FFPROBE) {
    console.error('  ✗ FFmpeg/FFprobe not found. Install with: winget install Gyan.FFmpeg');
    process.exit(1);
  }
  if (!existsSync(TIMELINE_FILE)) {
    console.error(`  ✗ ${TIMELINE_FILE} not found. Run the ${IS_EMPLOYEE ? '"npm run record:employee"' : '"npm run record"'} step first.`);
    process.exit(1);
  }

  const timeline = JSON.parse(readFileSync(TIMELINE_FILE, 'utf-8'));
  const videoDuration = timeline.duration / 1000;
  console.log(`  Recording duration: ${videoDuration.toFixed(1)}s`);

  // Build the cue list from scene markers that have narration
  const cues = [];
  let idx = 1;
  for (const scene of timeline.scenes) {
    const text = SCENE_NARRATION[scene.name];
    if (!text) continue;
    cues.push({
      index: idx++,
      name: scene.name,
      startSec: (scene.start - timeline.sessionStart) / 1000,
      text,
    });
  }
  console.log(`  Scenes with narration: ${cues.length}\n`);

  // 1) Synthesize each cue as its own audio file.
  //    Primary: ElevenLabs Multilingual v2, voice Mizan (most human-sounding).
  //    Secondary: Microsoft Edge neural TTS (free, no key needed).
  //    Last resort (offline): Windows OneCore voice via PowerShell.
  // Keep CUES_DIR intact — ElevenLabs cues are cached there keyed by text,
  // so unchanged narration doesn't re-spend free-tier credits.
  mkdirSync(CUES_DIR, { recursive: true });

  let cueExt = 'mp3';
  let synthesized = false;

  if (ELEVENLABS_API_KEY) {
    try {
      console.log('  Synthesizing cues with ElevenLabs...');
      await synthesizeElevenLabsCues(cues, CUES_DIR);
      synthesized = true;
    } catch (err) {
      console.warn(`  ! ElevenLabs failed (${err.message.split('\n')[0]})`);
      console.warn('  Falling back to Edge neural TTS...');
    }
  } else {
    console.log('  ELEVENLABS_API_KEY not set — using Edge neural TTS.');
    console.log('  (Set the key to use the more natural Mizan voice.)');
  }

  if (!synthesized) {
    try {
      console.log('  Synthesizing neural TTS cues (Microsoft Edge)...');
      await synthesizeNeuralCues(cues, CUES_DIR);
      synthesized = true;
    } catch (err) {
      console.warn(`  ! Neural TTS unavailable (${err.message.split('\n')[0]})`);
      console.warn('  Falling back to Windows OneCore voice...');
    }
  }

  if (!synthesized) {
    cueExt = 'wav';
    const cuesJson = join(CUES_DIR, 'cues.json');
    writeFileSync(cuesJson, JSON.stringify(cues.map(c => ({ index: c.index, text: c.text })), null, 2), 'utf-8');
    const ttsOut = execFileSync('powershell.exe', [
      '-ExecutionPolicy', 'Bypass',
      '-File', join(VOICE_DIR, 'generate_tts_cues.ps1'),
      '-CuesFile', cuesJson,
      '-OutDir', CUES_DIR,
      '-Rate', String(TTS_RATE),
    ], { encoding: 'utf-8', timeout: 600000, maxBuffer: 10 * 1024 * 1024 });
    console.log('  ' + ttsOut.trim().split('\n')[0]);
  }

  // 2) Measure each cue and resolve overlaps: a cue may not start before the
  //    previous one ends (+0.4s breathing gap).
  let prevEnd = 0;
  for (const cue of cues) {
    cue.file = join(CUES_DIR, `cue_${String(cue.index).padStart(3, '0')}.${cueExt}`);
    cue.duration = probeDuration(cue.file);
    cue.actualStart = Math.max(cue.startSec + 0.3, prevEnd + 0.4);
    prevEnd = cue.actualStart + cue.duration;
    const drift = cue.actualStart - cue.startSec;
    console.log(`  ${cue.name.padEnd(18)} scene@${cue.startSec.toFixed(1).padStart(6)}s  voice@${cue.actualStart.toFixed(1).padStart(6)}s  (${cue.duration.toFixed(1)}s${drift > 1 ? `, +${drift.toFixed(1)}s drift` : ''})`);
  }

  if (prevEnd > videoDuration) {
    console.warn(`\n  ⚠ Narration ends at ${prevEnd.toFixed(1)}s but video is ${videoDuration.toFixed(1)}s.`);
    console.warn('    Consider longer scene pauses or shorter narration for the last scenes.');
  }

  // 3) Mix all cues into one narration track with adelay per cue, then apply
  //    a light broadcast chain: high-pass (rumble), presence lift, gentle
  //    compression, and peak normalization for a polished VO sound.
  console.log('\n  Mixing narration track...');
  const inputArgs = [];
  const filterParts = [];
  cues.forEach((cue, i) => {
    inputArgs.push('-i', cue.file);
    const delayMs = Math.round(cue.actualStart * 1000);
    filterParts.push(`[${i}:a]aresample=48000,adelay=${delayMs}|${delayMs}[a${i}]`);
  });
  const mixInputs = cues.map((_, i) => `[a${i}]`).join('');
  const voChain = [
    `amix=inputs=${cues.length}:normalize=0`,
    'highpass=f=85',
    'equalizer=f=3200:t=q:w=1.2:g=1.5',
    'acompressor=threshold=-20dB:ratio=2.5:attack=8:release=180:makeup=3dB',
    'alimiter=limit=0.92',
    `apad=whole_dur=${Math.max(videoDuration, prevEnd).toFixed(2)}`,
  ].join(',');
  filterParts.push(`${mixInputs}${voChain}[out]`);

  execFileSync(FFMPEG, [
    ...inputArgs,
    '-filter_complex', filterParts.join(';'),
    '-map', '[out]',
    '-ar', '48000',
    '-ac', '2',
    '-y', OUTPUT_WAV,
  ], { stdio: ['ignore', 'ignore', 'pipe'] });
  console.log(`  ✓ ${OUTPUT_WAV}`);

  // 4) Generate the SRT from real cue timings
  const entries = [];
  for (const cue of cues) {
    entries.push(...splitIntoSubtitles(cue.text, cue.actualStart, cue.duration));
  }
  const srt = entries
    .map((e, i) => `${i + 1}\n${srtTime(e.start)} --> ${srtTime(e.end)}\n${e.text}\n`)
    .join('\n');
  mkdirSync(SUBTITLE_DIR, { recursive: true });
  writeFileSync(OUTPUT_SRT, srt, 'utf-8');
  console.log(`  ✓ ${OUTPUT_SRT} (${entries.length} entries)`);

  console.log('\n  ✓ Narration and subtitles are synchronized to the recording.\n');
}

main().catch((err) => {
  console.error('  ✗', err.message);
  process.exit(1);
});
