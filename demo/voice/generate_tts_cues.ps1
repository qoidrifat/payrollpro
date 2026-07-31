# Batch-synthesizes narration cues with the Windows OneCore Indonesian voice.
# Reads a JSON file of cues ([{ "index": 1, "text": "..." }, ...]) and writes
# one WAV per cue into the output directory (cue_001.wav, cue_002.wav, ...).
#
# Usage:
#   powershell -ExecutionPolicy Bypass -File generate_tts_cues.ps1 `
#     -CuesFile cues.json -OutDir out\cues -Rate 1.3

param(
    [Parameter(Mandatory = $true)][string]$CuesFile,
    [Parameter(Mandatory = $true)][string]$OutDir,
    [double]$Rate = 1.3
)

$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.Runtime.WindowsRuntime

$null = [Windows.Media.SpeechSynthesis.SpeechSynthesizer, Windows.Media.SpeechSynthesis, ContentType = WindowsRuntime]
$null = [Windows.Storage.Streams.DataReader, Windows.Storage.Streams, ContentType = WindowsRuntime]

$asTaskGeneric = ([System.WindowsRuntimeSystemExtensions].GetMethods() | Where-Object {
    $_.Name -eq 'AsTask' -and $_.GetParameters().Count -eq 1 -and
    $_.GetParameters()[0].ParameterType.Name -eq 'IAsyncOperation`1'
})[0]

function Await($WinRtTask, $ResultType) {
    $asTask = $asTaskGeneric.MakeGenericMethod($ResultType)
    $netTask = $asTask.Invoke($null, @($WinRtTask))
    $netTask.Wait(-1) | Out-Null
    $netTask.Result
}

$synth = New-Object Windows.Media.SpeechSynthesis.SpeechSynthesizer

$indonesian = [Windows.Media.SpeechSynthesis.SpeechSynthesizer]::AllVoices |
    Where-Object { $_.Language -like 'id*' } | Select-Object -First 1

if ($indonesian) {
    $synth.Voice = $indonesian
    Write-Output "Voice: $($indonesian.DisplayName) [$($indonesian.Language)]"
} else {
    Write-Output "Voice: $($synth.Voice.DisplayName) (no Indonesian voice found)"
}

$synth.Options.SpeakingRate = $Rate

if (-not (Test-Path $OutDir)) { New-Item -ItemType Directory -Path $OutDir -Force | Out-Null }

$cues = Get-Content -Path $CuesFile -Raw -Encoding UTF8 | ConvertFrom-Json

foreach ($cue in $cues) {
    $stream = Await ($synth.SynthesizeTextToStreamAsync($cue.text)) ([Windows.Media.SpeechSynthesis.SpeechSynthesisStream])
    $size = $stream.Size
    $reader = New-Object Windows.Storage.Streams.DataReader($stream.GetInputStreamAt(0))
    Await ($reader.LoadAsync($size)) ([UInt32]) | Out-Null
    $bytes = New-Object byte[] $size
    $reader.ReadBytes($bytes)

    $outFile = Join-Path $OutDir ("cue_{0:d3}.wav" -f [int]$cue.index)
    [System.IO.File]::WriteAllBytes($outFile, $bytes)
    Write-Output ("cue {0}: {1} bytes" -f $cue.index, $size)
}

Write-Output "Done: $($cues.Count) cues"
