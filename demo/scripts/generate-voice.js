/**
 * PayrollPro Voice-over Generator
 * Converts the Indonesian narration script to WAV audio using Windows SAPI.
 */

import { readFileSync, existsSync, mkdirSync, writeFileSync, unlinkSync, copyFileSync, statSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { execSync, execFileSync } from 'child_process';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
const ROOT = join(__dirname, '..');
const VOICE_DIR = join(ROOT, 'voice');
const OUTPUT_DIR = join(ROOT, 'output');
const NARRATION_FILE = join(VOICE_DIR, 'narration.txt');
const OUTPUT_WAV = join(OUTPUT_DIR, 'narration_raw.wav');

const TTS_RATE = -1;

function extractSpeechText(filePath) {
  if (!existsSync(filePath)) {
    console.error('  x Narration file not found:', filePath);
    process.exit(1);
  }

  const lines = readFileSync(filePath, 'utf-8').split('\n');
  const speechLines = [];
  let inSceneMarker = false;

  for (const rawLine of lines) {
    const line = rawLine.trim();
    if (/^[=─═]+$/.test(line) || /^$/.test(line)) {
      inSceneMarker = false;
      continue;
    }
    if (/^SCENE/i.test(line)) {
      inSceneMarker = true;
      continue;
    }
    if (inSceneMarker) {
      inSceneMarker = false;
      continue;
    }
    speechLines.push(line);
  }

  return speechLines.join(' ');
}

function generateWithOneCore(text, outputPath) {
  // WinRT OneCore voices include "Microsoft Andika - Indonesian", which the
  // legacy SAPI System.Speech API cannot see. Prefer this path.
  const textFile = join(OUTPUT_DIR, '_tts_text.txt');
  writeFileSync(textFile, text, 'utf-8');

  const scriptPath = join(VOICE_DIR, 'generate_tts_onecore.ps1');
  console.log('  Generating TTS audio via OneCore (Indonesian voice)...');
  const result = execFileSync('powershell.exe', [
    '-ExecutionPolicy', 'Bypass',
    '-File', scriptPath,
    '-TextFile', textFile,
    '-OutFile', outputPath,
  ], { timeout: 300000, encoding: 'utf-8', maxBuffer: 10 * 1024 * 1024 });

  try { unlinkSync(textFile); } catch {}
  return result;
}

function generateWithPowerShell(text, outputPath, rate) {
  const wavPath = outputPath.replace(/\\/g, '\\\\');
  const escapedText = text.replace(/'/g, "''");

  const psScript = [
    '$text = ' + "'" + escapedText + "'",
    'Add-Type -AssemblyName System.Speech',
    '$s = New-Object System.Speech.Synthesis.SpeechSynthesizer',
    '$s.Rate = ' + rate,
    '$s.Volume = 100',
    '$id = $s.GetInstalledVoices() | Where-Object { $_.VoiceInfo.Culture.Name -eq "id-ID" } | Select-Object -First 1',
    'if ($id) { $s.SelectVoice($id.VoiceInfo.Name); Write-Output "Voice: $($id.VoiceInfo.Name)" }',
    'else { Write-Output "Voice: $($s.Voice.Name) (no Indonesian voice)" }',
    "$s.SetOutputToWaveFile('" + wavPath + "')",
    '$s.Speak($text)',
    '$s.Dispose()',
    'Write-Output "Done"',
  ].join('\n');

  const psFile = join(OUTPUT_DIR, '_tts_temp.ps1');
  writeFileSync(psFile, psScript, 'utf-8');

  console.log('  Generating TTS audio via PowerShell...');
  const result = execSync(
    'powershell.exe -ExecutionPolicy Bypass -File "' + psFile + '"',
    { timeout: 120000, encoding: 'utf-8', maxBuffer: 10 * 1024 * 1024 }
  );

  try { unlinkSync(psFile); } catch {}
  return result;
}

async function main() {
  console.log('\n  ---------------------------------------------');
  console.log('     PayrollPro Voice-over Generator');
  console.log('  ---------------------------------------------\n');

  if (!existsSync(OUTPUT_DIR)) mkdirSync(OUTPUT_DIR, { recursive: true });

  const speechText = extractSpeechText(NARRATION_FILE);
  console.log('  Narration:', NARRATION_FILE);
  console.log('  Text length:', speechText.length, 'characters\n');

  try {
    let output;
    try {
      output = generateWithOneCore(speechText, OUTPUT_WAV);
    } catch (oneCoreErr) {
      console.warn('  ! OneCore TTS failed, falling back to SAPI:', oneCoreErr.message.split('\n')[0]);
      output = generateWithPowerShell(speechText, OUTPUT_WAV, TTS_RATE);
    }
    console.log(output);
  } catch (err) {
    console.error('  x TTS generation failed:', err.message);
    process.exit(1);
  }

  if (existsSync(OUTPUT_WAV)) {
    const stats = statSync(OUTPUT_WAV);
    const sizeMB = (stats.size / (1024 * 1024)).toFixed(1);
    console.log('\n  Audio generated successfully!');
    console.log('  ->', OUTPUT_WAV);
    console.log('  Size:', sizeMB, 'MB\n');

    const voiceCopy = join(VOICE_DIR, 'narration.wav');
    copyFileSync(OUTPUT_WAV, voiceCopy);
    console.log('  Copied to', voiceCopy, '\n');
  } else {
    console.error('  x Output file not found:', OUTPUT_WAV);
    process.exit(1);
  }
}

main();
