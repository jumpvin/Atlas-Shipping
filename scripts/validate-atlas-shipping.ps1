[CmdletBinding()]
param([string]$RepositoryRoot = '', [switch]$NoExitFailure)
$ErrorActionPreference = 'Stop'
if (-not $RepositoryRoot) { $RepositoryRoot = Split-Path -Parent $PSScriptRoot }
$RepositoryRoot = (Resolve-Path $RepositoryRoot).Path
$failures = [Collections.Generic.List[string]]::new()
$entry = Join-Path $RepositoryRoot 'atlas-shipping/atlas-shipping.php'
if (-not (Test-Path $entry)) { $failures.Add('plugin-entry-missing') }
else {
  $text = Get-Content -Raw $entry
  if ($text -notmatch '(?m)^ \* Version: 0\.1\.8\s*$') { $failures.Add('plugin-header-version-mismatch') }
  if ($text -notmatch "ATLAS_SHIPPING_VERSION', '0\.1\.8'") { $failures.Add('plugin-version-constant-mismatch') }
  if ($text -notmatch "ATLAS_SHIPPING_SCHEMA_VERSION', '0\.1\.4'") { $failures.Add('schema-version-mismatch') }
}
$required = @('AGENTS.md','framework.json','framework.lock','workflow-state.json','repository.operations.json','docs/extensions/wordpress-plugin-suite-profile.lock','docs/current-state-inventory.md')
foreach ($path in $required) { if (-not (Test-Path (Join-Path $RepositoryRoot $path))) { $failures.Add("required-path-missing:$path") } }
$zipPath = Join-Path $RepositoryRoot 'build/dev/atlas-shipping-0.1.8.zip'
if (-not (Test-Path $zipPath)) { $failures.Add('development-package-missing') }
else {
  Add-Type -AssemblyName System.IO.Compression.FileSystem
  $zip = [IO.Compression.ZipFile]::OpenRead($zipPath)
  try {
    if (@($zip.Entries | Where-Object { $_.FullName.Contains('\') }).Count) { $failures.Add('package-entry-backslash-invalid') }
    $files = @($zip.Entries | Where-Object { -not $_.FullName.EndsWith('/') } | ForEach-Object { $_.FullName.Replace('\','/') })
    $roots = @($files | ForEach-Object { ($_ -split '/')[0] } | Select-Object -Unique)
    if ($roots.Count -ne 1 -or $roots[0] -ne 'atlas-shipping') { $failures.Add('package-top-level-invalid') }
    if ('atlas-shipping/atlas-shipping.php' -notin $files) { $failures.Add('package-entry-missing') }
    if (@($files | Where-Object { $_ -match '(^|/)(\.git|framework\.lock|workflow-state\.json|repository\.operations\.json|build)(/|$)' }).Count) { $failures.Add('repository-metadata-leaked') }
  } finally { $zip.Dispose() }
}
$result = [ordered]@{schema_version=1;result=$(if($failures.Count){'failed'}else{'passed'});product_build='0.1.8';schema_version_product='0.1.4';artifact='build/dev/atlas-shipping-0.1.8.zip';failures=@($failures)}
$result | ConvertTo-Json -Depth 8
if ($failures.Count -and -not $NoExitFailure) { exit 1 }
