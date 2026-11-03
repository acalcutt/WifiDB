#!/bin/bash
# Installs pmtiles-swarm (https://github.com/TechIdiots-LLC/pmtiles-swarm) into the template the way its own Proxmox
# template does: Node from NodeSource, Ubuntu's python3-libtorrent, and the npm release in its service account's home.
# install-wifidb.sh runs this. It stays off until wifidb-firstboot turns it on (WIFIDB_SWARM=1 in firstboot.env), which
# also writes its configuration and its unit (docs/pmtiles-swarm.md).
#
# usage: install-swarm.sh PACKAGE     (what npm installs: pmtiles-swarm@<version>, or a tarball from `npm pack`)
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
package=$1
NODE_MAJOR=24
HOME_DIR=/var/lib/pmtiles-swarm

log() { echo "[install-swarm] $*"; }

log "Node $NODE_MAJOR and python3-libtorrent"
apt-get install -y -q --no-install-recommends ca-certificates curl gnupg python3 python3-libtorrent
install -d /etc/apt/keyrings
curl -fsSL --retry 3 https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key |
    gpg --dearmor --batch --yes -o /etc/apt/keyrings/nodesource.gpg
echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" \
    > /etc/apt/sources.list.d/nodesource.list
apt-get update -q
apt-get install -y -q --no-install-recommends nodejs
node --version | grep -q "^v$NODE_MAJOR\." || { log "NodeSource didn't install Node $NODE_MAJOR"; exit 1; }
python3 -c 'import libtorrent; print("[install-swarm] libtorrent", libtorrent.__version__)'

log "pmtiles-swarm ($package)"
# Its own account, as pmtiles-swarm's docs/running-as-a-service.md makes it: the package goes in its home. It shares
# only the archive folder with the site (through this group), so it can't read the site's settings.
getent group pmtiles-swarm >/dev/null || groupadd --system pmtiles-swarm
id pmtiles-swarm >/dev/null 2>&1 || useradd --system --gid pmtiles-swarm --home-dir "$HOME_DIR" --create-home \
    --shell /usr/sbin/nologin --comment "pmtiles-swarm service" pmtiles-swarm
# Retried: straight after a release, the registry can take a minute to serve it everywhere
for attempt in 1 2 3 4 5 6; do
    if env npm_config_cache=/tmp/npm-cache npm_config_update_notifier=false \
        npm install --prefix "$HOME_DIR" --omit=dev --no-audit --no-fund "$package"; then break; fi
    [ "$attempt" -lt 6 ] || { log "npm couldn't install $package"; exit 1; }
    log "npm install failed; trying again in 20 s"
    sleep 20
done
rm -rf /tmp/npm-cache /root/.npm
chown -R pmtiles-swarm:pmtiles-swarm "$HOME_DIR"
# WebTorrent's WebRTC binary is downloaded by node-datachannel's install script: check it landed
(cd "$HOME_DIR" && node -e "import('node-datachannel').then(() => console.log('[install-swarm] webrtc ok'))")
log "pmtiles-swarm $(node -p "require('$HOME_DIR/node_modules/pmtiles-swarm/package.json').version") is installed"
