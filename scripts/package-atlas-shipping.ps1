[CmdletBinding()]
param([string]$RepositoryRoot = '')
$ErrorActionPreference = 'Stop'
if (-not $RepositoryRoot) { $RepositoryRoot = Split-Path -Parent $PSScriptRoot }
$RepositoryRoot = (Resolve-Path $RepositoryRoot).Path
$source = Join-Path $RepositoryRoot 'atlas-shipping'
$destination = Join-Path $RepositoryRoot 'build/dev/atlas-shipping-0.2.1.zip'
if (-not (Test-Path (Join-Path $source 'atlas-shipping.php'))) { throw 'Plugin entry file missing.' }
New-Item -ItemType Directory -Force (Split-Path -Parent $destination) | Out-Null
Add-Type -AssemblyName System.IO.Compression
if (Test-Path -LiteralPath $destination) { [IO.File]::Delete($destination) }
$stream = [IO.File]::Open($destination, [IO.FileMode]::CreateNew)
$archive = [IO.Compression.ZipArchive]::new($stream, [IO.Compression.ZipArchiveMode]::Create)
try {
    Get-ChildItem -LiteralPath $source -Recurse -File | ForEach-Object {
        $relative = $_.FullName.Substring($source.Length + 1).Replace('\', '/')
        $entry = $archive.CreateEntry('atlas-shipping/' + $relative, [IO.Compression.CompressionLevel]::Optimal)
        $input = [IO.File]::OpenRead($_.FullName)
        $output = $entry.Open()
        try { $input.CopyTo($output) } finally { $output.Dispose(); $input.Dispose() }
    }
} finally { $archive.Dispose(); $stream.Dispose() }
Write-Output $destination
