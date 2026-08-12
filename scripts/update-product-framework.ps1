[CmdletBinding()]
param(
  [Parameter(Mandatory)][string]$ProductRoot,
  [string]$FrameworkPackage,
  [string]$FrameworkSha256,
  [string[]]$ExtensionPackages=@(),
  [string[]]$ExtensionSha256=@(),
  [string]$PlanPath,
  [string]$PlanSha256,
  [string]$ReportPath = '',
  [string]$Remote = 'origin',
  [switch]$AllowLocalTestRemote
)
$ErrorActionPreference = 'Stop'
if(-not $PlanPath){if(-not $FrameworkPackage-or-not $FrameworkSha256){throw 'FrameworkPackage and FrameworkSha256 are required.'};$PlanPath=Join-Path ([IO.Path]::GetTempPath()) ('mdf-plan-'+[guid]::NewGuid().ToString('n')+'.json');$prepared=& (Join-Path $PSScriptRoot 'new-local-adoption-plan.ps1') -ProductRoot $ProductRoot -FrameworkPackage $FrameworkPackage -FrameworkSha256 $FrameworkSha256 -ExtensionPackages $ExtensionPackages -ExtensionSha256 $ExtensionSha256 -PlanPath $PlanPath -Remote $Remote -AllowLocalTestRemote:$AllowLocalTestRemote|ConvertFrom-Json;$PlanSha256=$prepared.plan_sha256}
if(-not$PlanSha256){throw'PlanSha256 is required with PlanPath.'}
& (Join-Path $PSScriptRoot 'invoke-builder-adoption-plan.ps1') -ProductRoot $ProductRoot -PlanPath $PlanPath -PlanSha256 $PlanSha256 -ReportPath $ReportPath -Remote $Remote -AllowLocalTestRemote:$AllowLocalTestRemote
exit $LASTEXITCODE
