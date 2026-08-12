[CmdletBinding()]
param(
  [Parameter(Mandatory)][string]$Package,
  [Parameter(Mandatory)][string]$ExpectedSha256,
  [Parameter(Mandatory)][string]$ExpectedBuild,
  [string]$ReportPath = ''
)
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$resolved = (Resolve-Path -LiteralPath $Package).Path
$failures = [Collections.Generic.List[string]]::new()
$hash = (Get-FileHash -Algorithm SHA256 -LiteralPath $resolved).Hash.ToLowerInvariant()
if ($hash -ne $ExpectedSha256.ToLowerInvariant()) { $failures.Add('package-sha256-mismatch') }
$identityJson=& (Join-Path $PSScriptRoot 'resolve-framework-package-identity.ps1') -Package $resolved -NoExitFailure
$identityResolution=$identityJson|ConvertFrom-Json
if($identityResolution.result-ne'passed'){foreach($failure in @($identityResolution.failures)){$failures.Add("package-identity:$failure")}}elseif($identityResolution.canonical_identity-ne$ExpectedBuild){$failures.Add("canonical-package-identity-mismatch:resolved=$($identityResolution.canonical_identity):expected=$ExpectedBuild")}
$zip = $null
$parseRoot = Join-Path ([IO.Path]::GetTempPath()) ('mdf-package-parse-'+[guid]::NewGuid().ToString('n'))
try {
  $zip = [IO.Compression.ZipFile]::OpenRead($resolved)
  $names = [Collections.Generic.HashSet[string]]::new([StringComparer]::Ordinal)
  foreach ($entry in $zip.Entries) {
    $name = $entry.FullName
    if ([string]::IsNullOrWhiteSpace($name) -or $name.Contains('\') -or $name.StartsWith('/') -or $name -match '(^|/)\.\.(/|$)' -or [IO.Path]::IsPathRooted($name)) { $failures.Add("unsafe-zip-entry:$name"); continue }
    if (-not $names.Add($name)) { $failures.Add("duplicate-zip-entry:$name") }
  }
  foreach ($required in @('framework.json','package-inventory.json','package-manifest.md','scripts/validate-installed-framework.ps1','scripts/update-product-framework.ps1','templates/AGENTS.md','integrity/baseline-v0.3.8-manifest.json')) {
    if (-not $names.Contains($required)) { $failures.Add("missing-package-entry:$required") }
  }
  $identityEntry = $zip.GetEntry('framework.json')
  if ($identityEntry) {
    $reader = [IO.StreamReader]::new($identityEntry.Open())
    try { $identity = $reader.ReadToEnd() | ConvertFrom-Json } finally { $reader.Dispose() }
    if ($identity.version -ne $ExpectedBuild -or $identity.package_build -ne $ExpectedBuild) { $failures.Add('internal-package-identity-mismatch') }
  }
  if($identityEntry){$reader=[IO.StreamReader]::new($identityEntry.Open());try{$metadata=$reader.ReadToEnd()|ConvertFrom-Json}finally{$reader.Dispose()};foreach($field in @('entry_point','builder_instruction_source','repository_operations_schema','workflow_state_schema','adoption_plan_schema','consumer_validator','builder_updater')){$reference=[string]$metadata.$field;if(-not$reference-or-not$zip.GetEntry($reference)){$failures.Add("package-reference-missing:${field}:$reference")}}}
  $inventoryEntry=$zip.GetEntry('package-inventory.json')
  if($inventoryEntry){$reader=[IO.StreamReader]::new($inventoryEntry.Open());try{$inventory=$reader.ReadToEnd()|ConvertFrom-Json}finally{$reader.Dispose()};if($inventory.distribution_type-ne'complete-distribution'-or$inventory.candidate_identity-ne$ExpectedBuild-or$inventory.self_identity_rule-ne'all-zip-file-members-except-package-inventory-itself'){$failures.Add('internal-package-inventory-identity-mismatch')};$listed=@($inventory.members.path|Sort-Object);$actual=@($names|Where-Object{$_-ne'package-inventory.json'-and-not$_.EndsWith('/')}|Sort-Object);if(@(Compare-Object $listed $actual).Count){$failures.Add('package-inventory-member-set-mismatch')};foreach($member in @($inventory.members)){$entry=$zip.GetEntry($member.path);if(-not$entry){$failures.Add("inventory-member-missing:$($member.path)");continue};$stream=$entry.Open();try{$sha=[Security.Cryptography.SHA256]::Create();try{$actual=([BitConverter]::ToString($sha.ComputeHash($stream))).Replace('-','').ToLowerInvariant()}finally{$sha.Dispose()}}finally{$stream.Dispose()};if($actual-ne$member.sha256){$failures.Add("inventory-member-hash-mismatch:$($member.path)")}}}
  New-Item -ItemType Directory -Force -Path $parseRoot|Out-Null
  foreach($entry in @($zip.Entries|Where-Object{$_.FullName.EndsWith('.ps1',[StringComparison]::OrdinalIgnoreCase)})){
    $target=Join-Path $parseRoot $entry.FullName;$parent=Split-Path -Parent $target;if($parent){New-Item -ItemType Directory -Force -Path $parent|Out-Null}
    $source=$entry.Open();$destination=[IO.File]::Open($target,[IO.FileMode]::CreateNew);try{$source.CopyTo($destination)}finally{$destination.Dispose();$source.Dispose()}
    $tokens=$null;$parseErrors=$null;[Management.Automation.Language.Parser]::ParseFile($target,[ref]$tokens,[ref]$parseErrors)|Out-Null
    foreach($parseError in @($parseErrors)){$failures.Add("packaged-powershell-parse-error:$($entry.FullName):$($parseError.Message)")}
  }
} catch { $failures.Add("invalid-zip:$($_.Exception.Message)") } finally { if ($zip) { $zip.Dispose() };if(Test-Path -LiteralPath $parseRoot){Remove-Item -Recurse -Force -LiteralPath $parseRoot} }
$result = [ordered]@{schema_version=2;result=$(if($failures.Count){'failed'}else{'passed'});package=(Split-Path -Leaf $resolved);sha256=$hash;expected_build=$ExpectedBuild;resolved_canonical_identity=$identityResolution.canonical_identity;identity_kind=$identityResolution.identity_kind;release_target=$identityResolution.release_target;source_provenance_identity=$identityResolution.source_provenance_identity;canonical_authority_files=@($identityResolution.canonical_authority_files);failures=@($failures)}
if ($ReportPath) { $parent=Split-Path -Parent $ReportPath;if($parent){New-Item -ItemType Directory -Force $parent|Out-Null};$result|ConvertTo-Json -Depth 6|Set-Content -Encoding utf8 $ReportPath }
$result|ConvertTo-Json -Depth 6
if ($failures.Count) { exit 1 }
