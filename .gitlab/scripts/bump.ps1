# Bumps VERSION and rolls CHANGELOG.md's "## master" section into it (build/bump_version.py), commits that to a
# release-v<version> branch and opens a merge request into the current branch. Merging it releases the version.
# Based on tippr's .gitlab/scripts/bump.ps1.
#
# Needs CI_PUSH_TOKEN: a project access token (Maintainer, scopes api + write_repository).
param(
    [Parameter(Mandatory)][string]$BumpType,
    [string]$PreId = 'rc'
)

$ErrorActionPreference = 'Stop'

function Invoke-Git {
    git @args
    if ($LASTEXITCODE -ne 0) { throw "git $($args -join ' ') failed (exit $LASTEXITCODE)" }
}

if (-not $env:CI_PUSH_TOKEN) { throw 'CI_PUSH_TOKEN is not set. Add a project access token with api + write_repository scopes.' }
$baseBranch = $env:CI_COMMIT_BRANCH
if (-not $baseBranch) { throw 'Run the bump from a branch pipeline.' }
$api = "$env:CI_API_V4_URL/projects/$env:CI_PROJECT_ID"
$tokenHeaders = @{ 'PRIVATE-TOKEN' = $env:CI_PUSH_TOKEN }
$pushUrl = ($env:CI_PROJECT_URL -replace '^https://', "https://oauth2:$($env:CI_PUSH_TOKEN)@") + '.git'
$gitUser = @('-c', "user.name=$env:CI_PROJECT_NAME CI", '-c', "user.email=noreply@$env:CI_SERVER_HOST")

# bump_version.py writes version=<new> to $GITHUB_OUTPUT when it's set
$output = Join-Path ([IO.Path]::GetTempPath()) "bump-$env:CI_JOB_ID.txt"
$env:GITHUB_OUTPUT = $output
python build/bump_version.py $BumpType $PreId
if ($LASTEXITCODE -ne 0) { throw 'bump_version.py failed' }
# @(): a one-line file reads as a string, and -match on a string gives True, not the line
$version = (@(Get-Content $output) -match '^version=' | Select-Object -Last 1) -replace '^version=', ''
Remove-Item $output
if ($version -notmatch '^\d+\.\d+\.\d+(-[0-9A-Za-z.]+)?$') { throw "bump_version.py gave '$version', not a version" }
$branch = "release-v$version"
if (git ls-remote --heads origin "refs/heads/$branch") {
    throw "$branch already exists on the server. Merge or delete it first."
}

Invoke-Git checkout -B $branch
Invoke-Git add VERSION CHANGELOG.md
Invoke-Git @gitUser commit -m "Release v$version"

git push $pushUrl "HEAD:refs/heads/$branch" -o ci.skip
if ($LASTEXITCODE -ne 0) { throw "git push of $branch failed (exit $LASTEXITCODE)" }   # not Invoke-Git: its error would echo the token URL

$body = @{
    source_branch = $branch
    target_branch = $baseBranch
    title         = "Release v$version"
    description   = "Automated version bump to ``v$version``.`n`nReview the ``CHANGELOG.md`` entries before merging. " +
                    "Once merged, the pipeline on $baseBranch builds and boot-tests the Proxmox template " +
                    "and publishes the GitLab release."
    labels        = 'release'
} | ConvertTo-Json
$mr = Invoke-RestMethod -Method Post -Uri "$api/merge_requests" -Headers $tokenHeaders -ContentType 'application/json; charset=utf-8' `
    -Body ([Text.Encoding]::UTF8.GetBytes($body))
Write-Host "Created merge request: $($mr.web_url)"
