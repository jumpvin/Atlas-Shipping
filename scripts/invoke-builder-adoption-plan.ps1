[CmdletBinding()]
param(
  [Parameter(Mandatory)][string]$ProductRoot,
  [Parameter(Mandatory)][string]$PlanPath,
  [Parameter(Mandatory)][string]$PlanSha256,
  [string]$ReportPath = '',
  [string]$Remote = 'origin',
  [switch]$AllowLocalTestRemote
)
$ErrorActionPreference = 'Stop'
$ProductRoot = (Resolve-Path $ProductRoot).Path
$PlanPath = (Resolve-Path $PlanPath).Path
if($ReportPath){if(-not[IO.Path]::IsPathRooted($ReportPath)){throw'report-path-must-be-external-and-absolute'};$reportFull=[IO.Path]::GetFullPath($ReportPath);if($reportFull.StartsWith($ProductRoot+[IO.Path]::DirectorySeparatorChar,[StringComparison]::OrdinalIgnoreCase)){throw'report-path-inside-product-repository-forbidden'}}
function Git([string[]]$Arguments) { $prior=$ErrorActionPreference;$ErrorActionPreference='Continue';try{$value=& git.exe -C $ProductRoot @Arguments 2>&1;$exit=$LASTEXITCODE}finally{$ErrorActionPreference=$prior};if($exit-ne0){throw($value-join"`n")};($value-join"`n").Trim() }
function HashText([string]$Text){$hash=[Security.Cryptography.SHA256]::Create();try{([BitConverter]::ToString($hash.ComputeHash([Text.Encoding]::UTF8.GetBytes($Text)))).Replace('-','').ToLowerInvariant()}finally{$hash.Dispose()}}
function PlanHash($Plan){$lines=@("source=$($Plan.source.package_sha256)","target=$($Plan.target.framework)","repository=$($Plan.repository_authority.repository)","branch=$($Plan.repository_authority.authorized_branch)","start=$($Plan.repository_authority.starting_commit)","semantic=$($Plan.semantic_outcome_sha256)")+@($Plan.managed_writes|ForEach-Object{"write=$($_.path)|$($_.encoding)|$($_.content_sha256)"}|Sort-Object)+@($Plan.managed_removals|ForEach-Object{"remove=$_"}|Sort-Object)+@($Plan.preserved_paths|ForEach-Object{"preserve=$_"}|Sort-Object)+@($Plan.extensions|ForEach-Object{"extension=$($_.identity)|$($_.version)|$($_.sha256)|$($_.migration_decision)"}|Sort-Object)+@("next=$($Plan.lifecycle.next_owner)|$($Plan.lifecycle.next_command)");HashText($lines-join"`n")}
function WriteJson($Value,[string]$Path) { $parent=Split-Path -Parent $Path; if($parent){New-Item -ItemType Directory -Force $parent|Out-Null}; [IO.File]::WriteAllText($Path,(($Value|ConvertTo-Json -Depth 20)-replace"`r`n","`n"),[Text.UTF8Encoding]::new($false)) }
function PlanBytes($Write) { if($Write.encoding -eq 'base64'){[Convert]::FromBase64String([string]$Write.content)}elseif($Write.encoding -eq 'utf-8'){[Text.Encoding]::UTF8.GetBytes([string]$Write.content)}else{throw "unsupported-plan-encoding:$($Write.encoding)"} }
function SafePath([string]$Path) { if([IO.Path]::IsPathRooted($Path)-or$Path -match '(^|/)\.\.(/|$)'){throw "unsafe-managed-path:$Path"}; $full=[IO.Path]::GetFullPath((Join-Path $ProductRoot $Path)); if(-not $full.StartsWith($ProductRoot+[IO.Path]::DirectorySeparatorChar,[StringComparison]::OrdinalIgnoreCase)){throw "unsafe-managed-path:$Path"}; $full }
function RequiredOperations($Plan) { $write=@($Plan.managed_writes|Where-Object path -eq 'repository.operations.json'); if($write.Count -ne 1){throw 'required-operation-authority-missing'}; $ops=[Text.Encoding]::UTF8.GetString((PlanBytes $write[0]))|ConvertFrom-Json; foreach($property in $ops.operations.PSObject.Properties){$op=$property.Value;if($op.applicability -eq 'required'){$command=[string]$op.command;if($command -notmatch '^\./scripts/[A-Za-z0-9._-]+\.ps1$'){throw "required-operation-unsafe:$($property.Name)"};$relative=$command.Substring(2);$planned=@($Plan.managed_writes.path)-contains$relative;if(-not$planned-and-not(Test-Path(SafePath $relative))){throw "required-operation-missing:$($property.Name):$relative"}}};$ops }
function BlobHash([string]$Spec){$start=[Diagnostics.ProcessStartInfo]::new('git.exe');$start.WorkingDirectory=$ProductRoot;$start.UseShellExecute=$false;$start.RedirectStandardOutput=$true;$start.RedirectStandardError=$true;$start.Arguments='cat-file blob "'+$Spec.Replace('"','\"')+'"';$process=[Diagnostics.Process]::Start($start);$hash=[Security.Cryptography.SHA256]::Create();try{$value=([BitConverter]::ToString($hash.ComputeHash($process.StandardOutput.BaseStream))).Replace('-','').ToLowerInvariant();$process.WaitForExit();if($process.ExitCode-ne0){throw "committed-blob-missing:$Spec"};$value}finally{$hash.Dispose();$process.Dispose()}}
function VerifyCommittedPlan($Plan,[string]$Commit){foreach($write in @($Plan.managed_writes)){if((BlobHash "$Commit`:$($write.path)")-ne$write.content_sha256){throw"retry-managed-write-mismatch:$($write.path)"}};foreach($remove in @($Plan.managed_removals)){& git.exe -C $ProductRoot cat-file -e "$Commit`:$remove" 2>$null;if($LASTEXITCODE-eq0){throw"retry-managed-removal-present:$remove"}};$changed=@((Git @('diff','--name-only',$Plan.repository_authority.starting_commit,$Commit))-split"`r?`n"|Where-Object{$_});$allowed=@($Plan.managed_writes.path)+@($Plan.managed_removals);$outside=@($changed|Where-Object{$_-notin$allowed});if($outside){throw"retry-mutation-boundary:$($outside-join',')"}}

$plan=Get-Content -Raw $PlanPath|ConvertFrom-Json
if($plan.plan_sha256 -ne $PlanSha256-or(PlanHash $plan)-ne$PlanSha256){throw 'adoption-plan-identity-mismatch'}
$branch=Git @('branch','--show-current');if($branch-ne$plan.repository_authority.authorized_branch){throw "authorized-branch-mismatch:$branch"}
$origin=Git @('remote','get-url',$Remote);if(-not$AllowLocalTestRemote-and$origin-notmatch[regex]::Escape($plan.repository_authority.repository)){throw 'repository-remote-mismatch'}
if(Git @('status','--porcelain')){throw 'builder-product-tree-not-clean'}
Git @('fetch','--prune',$Remote)|Out-Null
$head=Git @('rev-parse','HEAD');$remoteHead=Git @('rev-parse',"$Remote/$branch");$message="Update Framework to $($plan.target.framework) [$($PlanSha256.Substring(0,12))]"
if($head-ne$remoteHead){$parent=Git @('rev-parse','HEAD^');$subject=Git @('show','-s','--format=%s','HEAD');if($remoteHead-eq$plan.repository_authority.starting_commit-and$parent-eq$remoteHead-and$subject-eq$message-and-not(Git @('status','--porcelain'))){VerifyCommittedPlan $plan $head;Git @('push',$Remote,"HEAD:$branch")|Out-Null;Git @('fetch','--prune',$Remote)|Out-Null;$published=Git @('rev-parse',"$Remote/$branch");if($published-ne$head){throw'retry-publication-verification-failed'};$retry=[ordered]@{schema_version=3;result='passed';adapter='builder-local-git';plan_sha256=$PlanSha256;publication_commit=$head;publication_status='verified';retry='push-only';changed_paths=@();next_owner=$plan.lifecycle.next_owner;next_command=$plan.lifecycle.next_command};if($ReportPath){WriteJson $retry $ReportPath};$retry|ConvertTo-Json -Depth 10;return}}
if($head-ne$remoteHead-or$head-ne$plan.repository_authority.starting_commit){throw "repository-not-synchronized:local=$head;remote=$remoteHead;plan=$($plan.repository_authority.starting_commit)"}
$ops=RequiredOperations $plan
$newPaths=[Collections.Generic.List[string]]::new()
try {
  foreach($write in @($plan.managed_writes)){$target=SafePath $write.path;if(-not(Test-Path -LiteralPath $target)){$newPaths.Add($target)};$parent=Split-Path -Parent $target;if($parent){New-Item -ItemType Directory -Force $parent|Out-Null};[IO.File]::WriteAllBytes($target,(PlanBytes $write))}
  foreach($remove in @($plan.managed_removals)){$target=SafePath $remove;if(Test-Path -LiteralPath $target){Remove-Item -LiteralPath $target -Recurse -Force}}
  foreach($property in $ops.operations.PSObject.Properties){$op=$property.Value;if($op.applicability-eq'required'){$script=SafePath ([string]$op.command).Substring(2);& powershell.exe -NoProfile -ExecutionPolicy Bypass -File $script;if($LASTEXITCODE-ne0){throw "required-operation-failed:$($property.Name)"}}}
  $validation=& (Join-Path $PSScriptRoot 'validate-framework-adoption.ps1') -RepositoryRoot $ProductRoot -PlanPath $PlanPath -NoExitFailure|ConvertFrom-Json
  if($validation.result-ne'passed'){throw "installed-framework-validation-failed:$($validation.failures-join',')"}
  Git @('add','-A')|Out-Null;$changed=@((Git @('diff','--cached','--name-only'))-split"`r?`n"|Where-Object{$_})
  $allowed=@($plan.managed_writes.path)+@($plan.managed_removals);$outside=@($changed|Where-Object{$_-notin$allowed});if($outside){throw "builder-plan-mutation-boundary:$($outside-join',')"}
  Git @('-c','user.name=MDF Builder','-c','user.email=builder@mdf.invalid','commit','-m',$message)|Out-Null
  $commit=Git @('rev-parse','HEAD');Git @('push',$Remote,"HEAD:$branch")|Out-Null;Git @('fetch','--prune',$Remote)|Out-Null
  $published=Git @('rev-parse',"$Remote/$branch");$dirty=Git @('status','--porcelain');if($commit-ne$published-or$dirty){throw "final-publication-verification-failed:local=$commit;remote=$published;dirty=$dirty"}
  $result=[ordered]@{schema_version=3;result='passed';adapter='builder-local-git';plan_sha256=$PlanSha256;publication_commit=$commit;publication_status='verified';changed_paths=$changed;next_owner=$plan.lifecycle.next_owner;next_command=$plan.lifecycle.next_command}
  if($ReportPath){WriteJson $result $ReportPath};$result|ConvertTo-Json -Depth 20
} catch {
  if((Git @('rev-parse','HEAD'))-eq$head){Git @('restore','--staged','--worktree','.')|Out-Null;foreach($path in $newPaths){if(Test-Path -LiteralPath $path){Remove-Item -LiteralPath $path -Recurse -Force}}}
  throw
}
