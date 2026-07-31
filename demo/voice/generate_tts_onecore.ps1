# Generates Indonesian narration WAV using the Windows OneCore voice
# (Microsoft Andika - Indonesian) via the WinRT SpeechSynthesizer API,
# which sees voices the legacy SAPI System.Speech API cannot.
#
# Usage: powershell -ExecutionPolicy Bypass -File generate_tts_onecore.ps1 -TextFile <txt> -OutFile <wav>

param(
    [Parameter(Mandatory = $true)][string]$TextFile,
    [Parameter(Mandatory = $true)][string]$OutFile,
    [double]$Rate = 1.3
)

$ErrorActionPreference = 'Stop'

Add-Type -AssemblyName System.Runtime.WindowsRuntime

$null = [Windows.Media.SpeechSynthesis.SpeechSynthesizer, Windows.Media.SpeechSynthesis, ContentType = WindowsRuntime]
$null = [Windows.Storage.Streams.DataReader, Windows.Storage.Streams, ContentType = WindowsRuntime]

# Helper to await WinRT IAsyncOperation from PowerShell
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

$text = Get-Content -Path $TextFile -Raw -Encoding UTF8

$stream = Await ($synth.SynthesizeTextToStreamAsync($text)) ([Windows.Media.SpeechSynthesis.SpeechSynthesisStream])

$size = $stream.Size
$reader = New-Object Windows.Storage.Streams.DataReader($stream.GetInputStreamAt(0))
Await ($reader.LoadAsync($size)) ([UInt32]) | Out-Null
$bytes = New-Object byte[] $size
$reader.ReadBytes($bytes)

[System.IO.File]::WriteAllBytes($OutFile, $bytes)
Write-Output "Done: $OutFile ($([math]::Round($size / 1MB, 1)) MB)"
