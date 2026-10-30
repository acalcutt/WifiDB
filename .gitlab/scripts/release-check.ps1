# Reads VERSION and checks whether a GitLab release already exists for it. Writes release.env (a dotenv report)
# that the build (.gitlab/scripts/build.sh) and publish-release.ps1 read:
#   RELEASE_VERSION, RELEASE_TAG, RELEASE_PRERELEASE, RELEASE_BUILD, RELEASE_PUBLISH
# With RELEASE_DRY_RUN=true the version gets a -dev.<commit> suffix and nothing is published.

$ErrorActionPreference = 'Stop'

$version = (Get-Content VERSION -Raw).Trim()
if (-not $version) { throw 'VERSION is empty' }

$published = $false
try {
    Invoke-RestMethod -Uri "$env:CI_API_V4_URL/projects/$env:CI_PROJECT_ID/releases/$([uri]::EscapeDataString("v$version"))" `
        -Headers @{ 'JOB-TOKEN' = $env:CI_JOB_TOKEN } | Out-Null
    $published = $true
}
catch {
    if ([int]$_.Exception.Response.StatusCode -ne 404) { throw }
}

if ($env:RELEASE_DRY_RUN -eq 'true') {
    $version = "$version-dev.$env:CI_COMMIT_SHORT_SHA"
    $build = $true; $publish = $false
}
elseif ($published) { $build = $false; $publish = $false }
else { $build = $true; $publish = $true }

@(
    "RELEASE_VERSION=$version"
    "RELEASE_TAG=v$version"
    "RELEASE_PRERELEASE=$(([string]($version -match '-')).ToLower())"
    "RELEASE_BUILD=$(([string]$build).ToLower())"
    "RELEASE_PUBLISH=$(([string]$publish).ToLower())"
) | Set-Content release.env -Encoding ascii

Write-Host "Version $version (already released: $published); build: $build, publish: $publish"
