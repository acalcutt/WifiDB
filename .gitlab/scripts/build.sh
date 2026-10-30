#!/bin/bash
# Builds the Proxmox template and boot-tests it (proxmox/build-template.sh, proxmox/test-template.sh). Runs in a
# docker:27 job with the dind service on the Linux runner (registered --privileged); the repository is the checkout.
#
# usage: .gitlab/scripts/build.sh OUTDIR VERSION
#
# VERSION names the file: the release's (0.41.0), or a CI build's (0.40.0+ci12.abc1234).
set -euo pipefail
[ $# -eq 2 ] || { echo "usage: $0 OUTDIR VERSION" >&2; exit 2; }
out=$(readlink -m "$1")
version=$2
repo=$(cd "$(dirname "$0")/../.." && pwd)

mkdir -p "$out"
cd "$repo"
src=$(mktemp -d)/wifidb-src.tar.gz
git archive --format=tar.gz -o "$src" HEAD wifidb composer.json composer.lock

# the template's name keeps the version's "+" out: v0.40.0-ci12-abc1234
if [[ $version =~ ^(.*)\+ci([0-9]+)\.(.*)$ ]]; then
    label=v${BASH_REMATCH[1]}-ci${BASH_REMATCH[2]}-${BASH_REMATCH[3]}
else
    label=v$version
fi
template=$out/WifiDB-$label-proxmox-amd64.tar.zst
bash proxmox/build-template.sh "$src" "$template"
bash proxmox/test-template.sh "$template"
ls -la "$out"
