# Generate TTS audio for PayrollPro demo narration
# Uses Windows built-in Speech API (SAPI)

Add-Type -AssemblyName System.Speech

$synthesizer = New-Object System.Speech.Synthesis.SpeechSynthesizer

Write-Output "=== Available Voices ==="
$synthesizer.GetInstalledVoices() | ForEach-Object {
    $voice = $_.VoiceInfo
    Write-Output ("  " + $voice.Name + " [" + $voice.Culture.Name + "] " + $voice.Gender + " " + $voice.Age)
}

# Try to find and set an Indonesian voice
$indonesianVoice = $synthesizer.GetInstalledVoices() | Where-Object {
    $_.VoiceInfo.Culture.Name -eq 'id-ID'
} | Select-Object -First 1

if ($indonesianVoice) {
    $synthesizer.SelectVoice($indonesianVoice.VoiceInfo.Name)
    Write-Output ""
    Write-Output "=== Selected Indonesian Voice ==="
    Write-Output ("  " + $indonesianVoice.VoiceInfo.Name)
} else {
    Write-Output ""
    Write-Output "=== No Indonesian voice found, using default ==="
}

# Configure output file
$outputDir = Join-Path $PSScriptRoot ".." "output"
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
}

# Read the narration text - only the content lines, skip the scene markers
$narrationFile = Join-Path $PSScriptRoot "narration.txt"
$fullText = Get-Content $narrationFile -Raw

# Extract just the spoken text (skip lines with === markers and SCENE markers)
$lines = Get-Content $narrationFile
$speechText = @()
$inScene = $false
foreach ($line in $lines) {
    $trimmed = $line.Trim()
    if ($trimmed -match '^═+$' -or $trimmed -match '^SCENE') {
        $inScene = $true
        continue
    }
    if ($inScene -and $trimmed -eq '') {
        $inScene = $false
        continue
    }
    if (-not $inScene -and $trimmed -ne '') {
        $speechText += $trimmed
    }
}

$speechText = $speechText -join "`n`n"

Write-Output ""
Write-Output "=== Generating Speech ==="
Write-Output ("  Text length: " + $speechText.Length + " characters")

# Set voice rate and volume
$synthesizer.Rate = -1   # Slightly slower for professional tone
$synthesizer.Volume = 100

# Generate WAV file
$wavPath = Join-Path $outputDir "narration_raw.wav"
$synthesizer.SetOutputToWaveFile($wavPath)
$synthesizer.Speak($speechText)

# Clean up
$synthesizer.Dispose()

Write-Output ""
Write-Output "=== Done ==="
Write-Output ("  Output: " + $wavPath)

# Get file info
$fileInfo = Get-Item $wavPath
Write-Output ("  Size: " + ("{0:N1}" -f ($fileInfo.Length / 1MB)) + " MB")
