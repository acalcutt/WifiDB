#!/bin/bash
# Installs WiFiDB into a running Ubuntu container (build-template.sh runs this inside it).
#
# Expects the source tree unpacked at /usr/local/src/wifidb (a `git archive` of the repository) and these files
# beside this script: install-files.sh (the code and the system files, also used by updates), install-swarm.sh, wifidb-firstboot,
# wifidb-firstboot.service, wifidb-monitord.service, wifidb-websocketd.service, wifidb-update and its units,
# wifidb.cron and wifidb-apache.conf.
#
# Layout:
#   /var/www/wifidb              the web site            (from wifidb/wifidb)
#   /opt/wifidb/tools            daemons and cron jobs   (from wifidb/tools)
#   /opt/wifidb/sql              blank_db.sql, blank_db.sqlsrv, blank_db.pgsql and migrations/ (applied by tools/utilites/migrate.php)
#   /srv/wifidb-data             everything that must survive an upgrade (made at first boot): uploads, generated
#                                files, logs and the two config files. Mount a second disk here.
#
# SQL Server support needs Microsoft's ODBC driver and the pdo_sqlsrv extension. WIFIDB_SQLSRV controls it:
#   auto (default)  install it if it can be, otherwise carry on with MariaDB only and say so
#   yes             fail the build if it can't be installed
#   no              skip it
#
# pmtiles-swarm (install-swarm.sh), which shares the map's tile archives between the nodes and serves tiles from them,
# is installed from npm, off until firstboot.env turns it on. WIFIDB_SWARM_PACKAGE is what npm installs: the release
# below by default, another version, a tarball from `npm pack`, or none to leave it out.
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
here=$(cd "$(dirname "$0")" && pwd)
src=/usr/local/src/wifidb
sqlsrv=${WIFIDB_SQLSRV:-auto}
swarm=${WIFIDB_SWARM_PACKAGE:-pmtiles-swarm@0.107.2}

log() { echo "[install-wifidb] $*"; }

log "installing packages"
# mdbtools: mdb-export reads the Access database Vistumbler saves (.mdb uploads)
apt-get update -q
apt-get install -y -q --no-install-recommends \
    apache2 php-fpm php-cli php-gd php-zip php-bcmath php-mbstring php-curl php-xml php-sqlite3 php-apcu php-mysql php-pgsql \
    mariadb-client postgresql-client unzip rsync tar gzip mdbtools
phpver=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
log "PHP $phpver"

if [ "$sqlsrv" != "no" ]; then
    log "SQL Server support"
    if bash "$here/install-sqlsrv.sh" > /var/log/wifidb-sqlsrv-install.log 2>&1; then
        tail -3 /var/log/wifidb-sqlsrv-install.log
        log "pdo_sqlsrv is installed"
    elif [ "$sqlsrv" = "yes" ]; then
        tail -40 /var/log/wifidb-sqlsrv-install.log
        log "WIFIDB_SQLSRV=yes and the SQL Server driver could not be installed"
        exit 1
    else
        tail -25 /var/log/wifidb-sqlsrv-install.log
        log "WARNING: the SQL Server driver could not be installed; this template supports MariaDB only"
        # leave a clean system behind: no half-installed extension that PHP warns about on every start
        phpdismod -v "$phpver" sqlsrv pdo_sqlsrv >/dev/null 2>&1 || true
        rm -f "/etc/php/$phpver/mods-available/sqlsrv.ini" "/etc/php/$phpver/mods-available/pdo_sqlsrv.ini"
        rm -f /etc/apt/sources.list.d/mssql-release.list
        apt-get purge -y -q php-dev build-essential unixodbc-dev >/dev/null 2>&1 || true
    fi
fi

if [ "$swarm" != none ]; then
    log "pmtiles-swarm"
    if bash "$here/install-swarm.sh" "$swarm" > /var/log/wifidb-swarm-install.log 2>&1; then
        tail -1 /var/log/wifidb-swarm-install.log
    else
        tail -40 /var/log/wifidb-swarm-install.log
        log "pmtiles-swarm could not be installed (WIFIDB_SWARM_PACKAGE=none builds without it)"
        exit 1
    fi
fi

log "installing Smarty with Composer (the version pinned in composer.json and composer.lock)"
apt-get install -y -q --no-install-recommends composer
(cd "$src" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --no-interaction --no-progress --optimize-autoloader --classmap-authoritative)
test -f "$src/wifidb/wifidb/vendor/autoload.php"
apt-get purge -y -q composer >/dev/null && apt-get autoremove -y -q >/dev/null

# the code and the system files: the same script installs an update package (wifidb-update)
sqlsrc=$(mktemp -d)
install -m 0644 "$src/wifidb/blank_db.sql" "$src/wifidb/blank_db.sqlsrv" "$src/wifidb/blank_db.pgsql" "$sqlsrc/"
cp -a "$src/wifidb/sql/migrations" "$sqlsrc/"
bash "$here/install-files.sh" "$src/wifidb/wifidb" "$src/wifidb/tools" "$sqlsrc"
rm -rf "$sqlsrc"

# A copy of the country database the cookie banner asks by, so a new container has one before it can fetch its own:
# wifidb-firstboot puts it on the data disk, and tools/cron/update_geoip keeps that one up to date
install -d /opt/wifidb/geoip
if php -r 'require "/var/www/wifidb/vendor/autoload.php"; require "/var/www/wifidb/lib/visitor_country.inc.php";
           echo "[install-wifidb] country database: ", VisitorCountry::Update("/opt/wifidb/geoip/dbip-country-lite.mmdb"), "\n";'; then
    chmod 0644 /opt/wifidb/geoip/dbip-country-lite.mmdb
else
    log "WARNING: the country database could not be fetched; until tools/cron/update_geoip gets one, everyone is asked about cookies"
fi

log "done"
