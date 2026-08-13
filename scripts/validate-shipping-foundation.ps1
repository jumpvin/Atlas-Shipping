[CmdletBinding()]
param([string]$RepositoryRoot = '', [switch]$NoExitFailure)
$ErrorActionPreference = 'Stop'
if (-not $RepositoryRoot) { $RepositoryRoot = Split-Path -Parent $PSScriptRoot }
$RepositoryRoot = (Resolve-Path $RepositoryRoot).Path
$fail = [Collections.Generic.List[string]]::new()
function Need([string]$Path,[string]$Pattern,[string]$Code) { $text=Get-Content -Raw (Join-Path $RepositoryRoot $Path); if($text-notmatch$Pattern){$fail.Add($Code)} }
foreach($path in @('atlas-shipping/includes/migrations/005-shipping-domain.php','atlas-shipping/includes/shipping/class-models.php','atlas-shipping/includes/shipping/class-repositories.php','atlas-shipping/includes/shipping/class-service.php')){if(-not(Test-Path(Join-Path $RepositoryRoot $path))){$fail.Add("missing:$path")}}
Need 'atlas-shipping/atlas-shipping.php' "ATLAS_SHIPPING_VERSION', '0\.1\.8'" 'version-mismatch'
Need 'atlas-shipping/atlas-shipping.php' "ATLAS_SHIPPING_SCHEMA_VERSION', '0\.1\.4'" 'schema-mismatch'
Need 'atlas-shipping/includes/class-migrator.php' '005_shipping_domain' 'migration-unregistered'
$migration=Get-Content -Raw (Join-Path $RepositoryRoot 'atlas-shipping/includes/migrations/005-shipping-domain.php')
foreach($table in @('requests','stops','items','snapshots')){if($migration-notmatch"atlas_shipping_$table"){$fail.Add("table-missing:$table")}}
foreach($index in @('UNIQUE KEY public_id','UNIQUE KEY request_sequence','UNIQUE KEY request_snapshot')){if($migration-notmatch[regex]::Escape($index)){$fail.Add("index-missing:$index")}}
$models=Get-Content -Raw (Join-Path $RepositoryRoot 'atlas-shipping/includes/shipping/class-models.php')
foreach($status in @('draft','submitted','sent_to_shipper','options_received','scheduled','in_transit','carrier_reported_delivered','delivery_issue','delivery_verified','complete','cancelled')){if($models-notmatch"'$status'"){$fail.Add("status-missing:$status")}}
$repos=Get-Content -Raw (Join-Path $RepositoryRoot 'atlas-shipping/includes/shipping/class-repositories.php')
if($repos-notmatch'archived_at IS NULL'){$fail.Add('soft-delete-filter-missing')};if($repos-notmatch'ORDER BY sequence_no ASC'){$fail.Add('deterministic-order-missing')}
$service=Get-Content -Raw (Join-Path $RepositoryRoot 'atlas-shipping/includes/shipping/class-service.php')
if($service-notmatch'content_hash'){$fail.Add('snapshot-hash-missing')}
foreach($contract in @('START TRANSACTION','ROLLBACK','request_created','request_updated','request_archived','stop_created','item_created','snapshot_created','WP_Error')){if($service-notmatch[regex]::Escape($contract)){$fail.Add("service-contract-missing:$contract")}}
foreach($reviewContract in @('atlas_request_parent_invalid','atlas_actor_identity_invalid','atlas_stop_datetime_invalid','atlas_stop_window_reversed','actor_identity_id','next_number_for_update')){if($service-notmatch[regex]::Escape($reviewContract)){$fail.Add("review-contract-missing:$reviewContract")}}
if($service-notmatch'for\(\$attempt=0;\$attempt<3;\$attempt\+\+\)'){$fail.Add('snapshot-bounded-retry-missing')}
if($repos-notmatch'FOR UPDATE'){$fail.Add('snapshot-allocation-lock-missing')}
$changed=git -c safe.directory='C:/Users/govin/WebstormProjects/Atlas-Shipping/.builder-worktree' -C $RepositoryRoot diff --name-only 23ad4f5799a2e1826987c59b029991b8d58313a8 -- 'atlas-shipping/includes/migrations/001-initial-foundation.php' 'atlas-shipping/includes/migrations/002-identities.php' 'atlas-shipping/includes/migrations/003-magic-tokens.php' 'atlas-shipping/includes/migrations/004-sessions.php'
if($changed){$fail.Add('historical-migration-modified')}
Need 'atlas-shipping/includes/class-activator.php' 'strpos\( \$page->post_content, ''\[atlas_shipping_app' 'activation-shortcode-validation-invalid'
$result=[ordered]@{schema_version=1;result=$(if($fail.Count){'failed'}else{'passed'});checks='static-isolated';runtime_database='not-run';failures=@($fail)}
$result|ConvertTo-Json -Depth 8
if($fail.Count-and-not$NoExitFailure){exit 1}
