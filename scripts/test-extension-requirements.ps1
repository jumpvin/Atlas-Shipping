[CmdletBinding()]param([Parameter(Mandatory)]$Requirements,[Parameter(Mandatory)][string]$RepositoryRoot)
$ErrorActionPreference='Stop'
function ReadLock([string]$path){
  $text=Get-Content -Raw $path
  if($text.TrimStart().StartsWith('{')){return $text|ConvertFrom-Json}
  $values=@{};$section=''
  foreach($line in $text-split"`r?`n"){
    if($line-match'^([a-z0-9-]+):\s*$'){$section=$Matches[1];continue}
    if($line-match'^(\s+)([a-z0-9-]+):\s*"?([^"#]+?)"?\s*$'){$values["$section.$($Matches[2])"]=$Matches[3].Trim();continue}
    if($line-match'^([a-z0-9-]+):\s*"?([^"#]+?)"?\s*$'){$section='';$values[$Matches[1]]=$Matches[2].Trim()}
  }
  [pscustomobject]@{extension_id=$values['extension-slug'];extension_name=$values['extension-name'];extension_version=$values['extension-version'];package_type=$values['package-type'];source_package=[pscustomobject]@{exact_filename=$values['source-package.exact-filename'];repository_path=$values['source-package.repository-path'];sha256=$values['source-package.sha256']}}
}
$results=@();foreach($r in @($Requirements)){
  $path=Join-Path $RepositoryRoot $r.lock_path;if(-not(Test-Path -LiteralPath $path)){throw "Required extension lock absent: $($r.lock_path)"}
  $hash=(Get-FileHash -Algorithm SHA256 $path).Hash.ToLowerInvariant();if($r.sha256-and$hash-ne$r.sha256){throw "Extension lock hash mismatch: $($r.lock_path)"}
  $lock=ReadLock $path;if($r.extension_id-and$lock.extension_id-ne$r.extension_id){throw "Extension identity mismatch: $($r.lock_path)"};if($r.extension_version-and$lock.extension_version-ne$r.extension_version){throw "Extension version mismatch: $($r.lock_path)"};if(-not$lock.extension_id-or-not$lock.extension_version){throw "Extension lock authority is incomplete: $($r.lock_path)"}
  $source=$lock.source_package;if(-not$source-or-not$source.exact_filename-or-not$source.repository_path-or$source.sha256-notmatch'^[a-f0-9]{64}$'){throw "Extension source-package authority is incomplete: $($r.lock_path)"}
  if([IO.Path]::IsPathRooted($source.repository_path)-or$source.repository_path-match'(^|[\\/])\.\.([\\/]|$)'){throw "Unsafe extension source-package path: $($r.lock_path)"}
  if([IO.Path]::GetFileName($source.repository_path)-ne$source.exact_filename){throw "Extension source-package filename/path mismatch: $($r.lock_path)"}
  $packagePath=Join-Path $RepositoryRoot $source.repository_path;if(-not(Test-Path -LiteralPath $packagePath)){throw "Extension source package absent: $($source.repository_path)"}
  $packageHash=(Get-FileHash -Algorithm SHA256 $packagePath).Hash.ToLowerInvariant();if($packageHash-ne$source.sha256){throw "Extension source package hash mismatch: $($source.repository_path)"}
  $results+=[pscustomobject]@{lock_path=$r.lock_path;extension_id=$lock.extension_id;extension_version=$lock.extension_version;sha256=$hash;source_package_path=$source.repository_path;source_package_sha256=$packageHash;result='passed'}
};,$results
