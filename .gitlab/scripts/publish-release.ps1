# Uploads the files in release-files/ to the generic package registry and creates the GitLab release, with the
# matching CHANGELOG.md section and download notes as its description. Files are attached with permanent links:
# <project>/-/releases/<tag>/downloads/<file>. Based on handbrake-lxc's publish-release.ps1.
param(
    [string]$PackageName = 'wifidb',
    [string]$ReleaseName = 'WifiDB',
    [string]$FilesDir = 'release-files'
)

$ErrorActionPreference = 'Stop'

if ($env:RELEASE_PUBLISH -ne 'true') {
    Write-Host "Not publishing $env:RELEASE_TAG (dry run, or already released)."
    exit 0
}

$version = $env:RELEASE_VERSION
$tag = $env:RELEASE_TAG
$api = "$env:CI_API_V4_URL/projects/$env:CI_PROJECT_ID"
$jobHeaders = @{ 'JOB-TOKEN' = $env:CI_JOB_TOKEN }

$files = @(Get-ChildItem $FilesDir -File -ErrorAction SilentlyContinue)
if (-not $files) { throw "No release files found in $FilesDir" }
$template = ($files | Where-Object Name -like '*-proxmox-amd64.tar.zst' | Select-Object -First 1).Name

# Release notes: the lines under "## <version>" up to the next "## " heading
$notes = @()
$inSection = $false
foreach ($line in Get-Content CHANGELOG.md -Encoding UTF8) {
    if ($line -eq "## $version") { $inSection = $true; continue }
    if ($inSection -and $line -match '^## ') { break }
    if ($inSection) { $notes += $line }
}
$notes += @(
    ''
    '## Downloads'
    ''
    "- **Proxmox:** ``$template`` is an LXC template (Ubuntu 26.04, PHP, Apache, the services and the cron jobs) with no"
    '  database of its own: it uses your MySQL/MariaDB or SQL Server. See docs/proxmox.md.'
    '- **Anywhere else:** the source is the repository at this tag; README.md has the install steps.'
)
$description = ($notes -join "`n").Trim()
Write-Host "--- Release notes ---`n$description"

$links = @()
foreach ($file in $files) {
    $url = "$api/packages/generic/$PackageName/$version/$([uri]::EscapeDataString($file.Name))"
    Write-Host "Uploading $($file.Name)"
    Invoke-RestMethod -Method Put -Uri $url -Headers $jobHeaders -InFile $file.FullName | Out-Null
    $links += @{ name = $file.Name; url = $url; direct_asset_path = "/$($file.Name)"; link_type = 'package' }
}

$release = @{
    name        = if ($env:RELEASE_PRERELEASE -eq 'true') { "$ReleaseName $tag (pre-release)" } else { "$ReleaseName $tag" }
    tag_name    = $tag
    ref         = $env:CI_COMMIT_SHA
    description = $description
    assets      = @{ links = $links }
} | ConvertTo-Json -Depth 5
Invoke-RestMethod -Method Post -Uri "$api/releases" -Headers $jobHeaders -ContentType 'application/json; charset=utf-8' `
    -Body ([Text.Encoding]::UTF8.GetBytes($release)) | Out-Null
Write-Host "Created release ${tag}: $env:CI_PROJECT_URL/-/releases/$([uri]::EscapeDataString($tag))"
