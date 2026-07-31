/**
 * =============================================================================
 *  PayrollPro — Background Music Generator ("Corporate Tech" theme)
 * =============================================================================
 *
 *  Synthesizes a modern, upbeat-but-soft corporate technology track suitable
 *  for SaaS product demos (think Stripe/Linear launch videos): a warm pad,
 *  a gentle plucked arpeggio, soft sub-bass, and a subtle rhythmic pulse.
 *  No vocals, no harsh transients — designed to sit under narration at 20%.
 *
 *  Structure (100 BPM, 4/4):
 *    - Chord loop: Am7 → Fmaj7 → Cmaj7 → Gsus2  (8 beats each, 32-beat loop)
 *    - Pad: detuned sine layers, slow attack
 *    - Pluck: triangle-ish arpeggio on 8th notes with decay envelope
 *    - Bass: soft sine following chord roots, pulsing on beats
 *    - Pulse: filtered noise tick on off-beats (very quiet, adds motion)
 *
 *  Usage:
 *    node scripts/generate-bgm.js
 *
 *  Output:
 *    assets/bgm.wav  (stereo, 44.1 kHz, 16-bit, ~77 s seamless loop)
 * =============================================================================
 */

import { writeFileSync, mkdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const ASSETS_DIR = join(__dirname, '..', 'assets');
const OUTPUT = join(ASSETS_DIR, 'bgm.wav');

const SAMPLE_RATE = 44100;
const BPM = 100;
const BEAT = 60 / BPM;                 // 0.6 s
const BEATS_PER_CHORD = 8;
const LOOPS = 4;                       // 4 × 4 chords × 8 beats = 128 beats ≈ 77 s

const NOTE = (n) => 440 * Math.pow(2, (n - 69) / 12); // MIDI → Hz

// Chords: [root(bass), pad notes..., arp notes...]
const CHORDS = [
  { bass: 33, pad: [57, 60, 64, 67], arp: [57, 60, 64, 67, 72] }, // Am7
  { bass: 29, pad: [53, 57, 60, 64], arp: [53, 57, 60, 64, 69] }, // Fmaj7
  { bass: 36, pad: [55, 60, 64, 71], arp: [48, 55, 60, 64, 71] }, // Cmaj7
  { bass: 31, pad: [55, 57, 62, 67], arp: [43, 55, 62, 67, 74] }, // Gsus2
];

const chordDur = BEATS_PER_CHORD * BEAT;
const totalSeconds = LOOPS * CHORDS.length * chordDur;
const totalSamples = Math.floor(totalSeconds * SAMPLE_RATE);

console.log('\n  Generating "Corporate Tech" BGM...');
console.log(`  ${BPM} BPM, ${CHORDS.length}-chord loop × ${LOOPS}, ${totalSeconds.toFixed(0)}s total\n`);

const left = new Float64Array(totalSamples);
const right = new Float64Array(totalSamples);

// Deterministic pseudo-random (no Math.random → reproducible output)
let seed = 42;
function rand() {
  seed = (seed * 1103515245 + 12345) & 0x7fffffff;
  return seed / 0x7fffffff;
}
// Pre-generate noise table for the pulse ticks
const noiseTable = new Float64Array(SAMPLE_RATE);
for (let i = 0; i < noiseTable.length; i++) noiseTable[i] = rand() * 2 - 1;

const smooth = (g) => g * g * (3 - 2 * g);

for (let i = 0; i < totalSamples; i++) {
  const t = i / SAMPLE_RATE;
  const beatPos = t / BEAT;                       // global beat position
  const chordIdx = Math.floor(beatPos / BEATS_PER_CHORD) % CHORDS.length;
  const tInChord = (beatPos % BEATS_PER_CHORD) * BEAT;
  const chord = CHORDS[chordIdx];

  // Chord crossfade envelope (1.2 s edges)
  const fadeEdge = 1.2;
  const attack = Math.min(1, tInChord / fadeEdge);
  const release = Math.min(1, (chordDur - tInChord) / fadeEdge);
  const chordEnv = smooth(Math.min(attack, release));

  let sL = 0, sR = 0;

  // ── Pad: warm detuned sines, slow breathing LFO ───────────────────────
  const lfo = 0.8 + 0.2 * Math.sin(2 * Math.PI * t / 11);
  for (let n = 0; n < chord.pad.length; n++) {
    const f = NOTE(chord.pad[n]);
    const a = Math.sin(2 * Math.PI * (f - 0.6) * t);
    const b = Math.sin(2 * Math.PI * (f + 0.6) * t);
    const pan = n % 2 === 0 ? 0.38 : 0.62;
    const amp = 0.085 * chordEnv * lfo;
    sL += amp * (a * (1 - pan) + b * pan);
    sR += amp * (a * pan + b * (1 - pan));
  }

  // ── Bass: soft sine on the root, gently pulsing per beat ──────────────
  const beatFrac = beatPos % 1;
  const bassEnv = Math.exp(-beatFrac * 1.6) * 0.5 + 0.5;   // subtle pump
  const bassF = NOTE(chord.bass);
  const bass = Math.sin(2 * Math.PI * bassF * t) * 0.16 * bassEnv * chordEnv;
  sL += bass;
  sR += bass;

  // ── Pluck arpeggio: 8th notes cycling up the chord ────────────────────
  const eighthPos = beatPos * 2;                            // 8th-note index
  const arpIdx = Math.floor(eighthPos) % chord.arp.length;
  const tInEighth = (eighthPos % 1) * (BEAT / 2);
  const pluckEnv = Math.exp(-tInEighth * 9);                // fast decay
  const pf = NOTE(chord.arp[arpIdx] + 12);
  // Triangle-ish: fundamental + odd harmonic, softened
  const pluck = (Math.sin(2 * Math.PI * pf * t) + 0.15 * Math.sin(2 * Math.PI * pf * 3 * t))
    * 0.075 * pluckEnv * chordEnv;
  // Alternate pluck panning by index for stereo motion
  const ppan = 0.3 + 0.4 * (arpIdx / (chord.arp.length - 1));
  sL += pluck * (1 - ppan);
  sR += pluck * ppan;

  // ── Pulse: soft filtered-noise tick on off-beats (the "tech" texture) ──
  const offbeatFrac = (beatPos + 0.5) % 1;
  if (offbeatFrac < 0.05) {
    const tickEnv = Math.exp(-offbeatFrac * 90);
    const nz = noiseTable[i % noiseTable.length] * 0.028 * tickEnv;
    sL += nz;
    sR += nz;
  }

  left[i] = sL;
  right[i] = sR;
}

// Gentle lowpass (~2.8 kHz) to keep everything soft under narration
const alpha = 1 - Math.exp(-2 * Math.PI * 2800 / SAMPLE_RATE);
let stL = 0, stR = 0;
for (let i = 0; i < totalSamples; i++) {
  stL += alpha * (left[i] - stL);
  stR += alpha * (right[i] - stR);
  left[i] = stL;
  right[i] = stR;
}

// Loop-edge fades (0.5 s) so -stream_loop restarts are click-free
const edge = Math.floor(0.5 * SAMPLE_RATE);
for (let i = 0; i < edge; i++) {
  const g = smooth(i / edge);
  left[i] *= g; right[i] *= g;
  left[totalSamples - 1 - i] *= g; right[totalSamples - 1 - i] *= g;
}

// Normalize to a comfortable peak; render mixes this at 20%
let peak = 0;
for (let i = 0; i < totalSamples; i++) {
  peak = Math.max(peak, Math.abs(left[i]), Math.abs(right[i]));
}
const norm = peak > 0 ? 0.7 / peak : 1;

// Write 16-bit stereo PCM WAV
const dataBytes = totalSamples * 2 * 2;
const buf = Buffer.alloc(44 + dataBytes);
buf.write('RIFF', 0);
buf.writeUInt32LE(36 + dataBytes, 4);
buf.write('WAVE', 8);
buf.write('fmt ', 12);
buf.writeUInt32LE(16, 16);
buf.writeUInt16LE(1, 20);
buf.writeUInt16LE(2, 22);
buf.writeUInt32LE(SAMPLE_RATE, 24);
buf.writeUInt32LE(SAMPLE_RATE * 4, 28);
buf.writeUInt16LE(4, 32);
buf.writeUInt16LE(16, 34);
buf.write('data', 36);
buf.writeUInt32LE(dataBytes, 40);

for (let i = 0; i < totalSamples; i++) {
  const l = Math.max(-1, Math.min(1, left[i] * norm));
  const r = Math.max(-1, Math.min(1, right[i] * norm));
  buf.writeInt16LE(Math.round(l * 32767), 44 + i * 4);
  buf.writeInt16LE(Math.round(r * 32767), 46 + i * 4);
}

mkdirSync(ASSETS_DIR, { recursive: true });
writeFileSync(OUTPUT, buf);
console.log(`  ✓ BGM written to ${OUTPUT}`);
console.log(`  Size: ${(buf.length / (1024 * 1024)).toFixed(1)} MB\n`);
