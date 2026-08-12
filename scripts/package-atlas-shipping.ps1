[CmdletBinding()]
param([string]$RepositoryRoot = '')
$ErrorActionPreference = 'Stop'
if (-not $RepositoryRoot) { $RepositoryRoot = Split-Path -Parent $PSScriptRoot }
$RepositoryRoot = (Resolve-Path $RepositoryRoot).Path
$source = Join-Path $RepositoryRoot 'atlas-shipping'
$destination = Join-Path $RepositoryRoot 'build/dev/atlas-shipping-0.1.5.zip'
if (-not (Test-Path (Join-Path $source 'atlas-shipping.php'))) { throw 'Plugin entry file missing.' }
New-Item -ItemType Directory -Force (Split-Path -Parent $destination) | Out-Null
Compress-Archive -LiteralPath $source -DestinationPath $destination -Force
Write-Output $destination
