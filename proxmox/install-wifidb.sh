#!/bin/bash
# Installs WiFiDB into a running Ubuntu container (build-template.sh runs this inside it).
#
# Expects the source tree unpacked at /usr/local/src/wifidb (a `git archive` of the repository) and these files
# beside this script: wifidb-firstboot, wifidb-firstboot.service, wifidb-monitord.service, wifidb-websocketd.service,
# wifidb.cron and wifidb-apache.conf.
#
# Layout:
#   /var/www/wifidb              the web site            (from wifidb/wifidb)
#   /opt/wifidb/tools            daemons and cron jobs   (from wifidb/tools)
#   /opt/wifidb/sql              blank_db.sql and blank_db.sqlsrv
#   /srv/wifidb-data             everything that must survive an upgrade (made at first boot): uploads, generated
#                                files, logs and the two config files. Mount a second disk here.
#
# SQL Server support needs Microsoft's ODBC driver and the pdo_sqlsrv extension. WIFIDB_SQLSRV controls it:
#   auto (default)  install it if it can be, otherwise carry on with MariaDB only and say so
#   yes             fail the build if it can't be installed
#   no              skip it
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
here=$(cd "$(dirname "$0")" && pwd)
src=/usr/local/src/wifidb
sqlsrv=${WIFIDB_SQLSRV:-auto}

log() { echo "[install-wifidb] $*"; }

log "installing packages"
apt-get update -q
apt-get install -y -q --no-install-recommends \
    apache2 php-fpm php-cli php-gd php-zip php-bcmath php-mbstring php-curl php-xml php-sqlite3 php-apcu php-mysql \
    mariadb-client unzip rsync tar gzip
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

log "installing the application"
install -d /var/www/wifidb /opt/wifidb/tools /opt/wifidb/sql
rsync -a --delete "$src/wifidb/wifidb/" /var/www/wifidb/
rsync -a --delete "$src/wifidb/tools/" /opt/wifidb/tools/
install -m 0644 "$src/wifidb/blank_db.sql" "$src/wifidb/blank_db.sqlsrv" /opt/wifidb/sql/
# the config files are made at first boot; the working copy's examples stay in the tree
rm -f /var/www/wifidb/lib/config.inc.php /opt/wifidb/tools/daemon.config.inc.php
# the data folders live on the data disk (see wifidb-firstboot), so the copies in the code are just placeholders
chmod +x /opt/wifidb/tools/cron/* 2>/dev/null || true
chown -R root:root /var/www/wifidb /opt/wifidb

log "web server"
install -m 0644 "$here/wifidb-apache.conf" /etc/apache2/sites-available/wifidb.conf
a2enmod -q rewrite headers remoteip proxy proxy_fcgi proxy_http proxy_wstunnel setenvif expires >/dev/null
a2enconf -q "php$phpver-fpm" >/dev/null
install -m 0644 "$here/wifidb-slow-pool.conf" /etc/apache2/wifidb-slow-pool.conf
install -m 0644 "$here/wifidb-slow-pool-fpm.conf" "/etc/php/$phpver/fpm/pool.d/wdb-slow.conf"
# The interactive pool, sized as wifidb.net's is: 50 workers, requests cut off at 30s (PHP's own limit 110s). The
# heavy endpoints have the wdb-slow pool for longer ones.
cat > "/etc/php/$phpver/fpm/pool.d/www.conf" <<POOL
[www]
user = www-data
group = www-data
listen = /run/php/php$phpver-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 8
pm.max_spare_servers = 20
pm.max_requests = 500
request_terminate_timeout = 30s
php_admin_value[max_execution_time] = 110
POOL
install -d /var/www/wifidb-health
echo ok > /var/www/wifidb-health/index.html
a2dissite -q 000-default >/dev/null 2>&1 || true
a2ensite -q wifidb >/dev/null

# Apache timeouts and slow-client protection, as on wifidb.net
a2enmod -q deflate >/dev/null
cat > /etc/apache2/conf-available/wifidb-tuning.conf <<CONF
Timeout 300
KeepAlive On
KeepAliveTimeout 5
RequestReadTimeout header=20-40,minrate=500 body=10,minrate=500
<IfModule mpm_event_module>
    StartServers             4
    MinSpareThreads          50
    MaxSpareThreads          250
    ThreadsPerChild          25
    ServerLimit              24
    MaxRequestWorkers        600
    MaxConnectionsPerChild   0
</IfModule>
CONF
a2enconf -q wifidb-tuning >/dev/null

# PHP, as wifidb.net runs it. Web (FPM): uploads up to 100M, 2 GB per request, no php.ini time limit (each pool sets
# its own), pcntl switched off. Command line (the daemons and cron jobs): no memory limit.
cat > "/etc/php/$phpver/fpm/conf.d/90-wifidb.ini" <<INI
upload_max_filesize = 100M
post_max_size = 100M
max_file_uploads = 20
max_execution_time = 0
max_input_time = 120
memory_limit = 2048M
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors = Off
log_errors = On
expose_php = Off
date.timezone = UTC
disable_functions = pcntl_alarm,pcntl_fork,pcntl_waitpid,pcntl_wait,pcntl_wifexited,pcntl_wifstopped,pcntl_wifsignaled,pcntl_wifcontinued,pcntl_wexitstatus,pcntl_wtermsig,pcntl_wstopsig,pcntl_signal,pcntl_signal_get_handler,pcntl_signal_dispatch,pcntl_get_last_error,pcntl_strerror,pcntl_sigprocmask,pcntl_sigwaitinfo,pcntl_sigtimedwait,pcntl_exec,pcntl_getpriority,pcntl_setpriority,pcntl_async_signals,pcntl_unshare
apc.shm_size = 128M
INI
cat > "/etc/php/$phpver/cli/conf.d/90-wifidb.ini" <<INI
memory_limit = -1
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
date.timezone = UTC
INI

# the connection backlog wifidb.net uses (applies if the container may set it; harmless if not)
echo 'net.core.somaxconn = 4096' > /etc/sysctl.d/90-wifidb.conf

log "services and cron jobs"
install -m 0644 "$here/wifidb-monitord.service" /etc/systemd/system/wifidb-monitord.service
install -m 0644 "$here/wifidb-websocketd.service" /etc/systemd/system/wifidb-websocketd.service
install -m 0644 "$here/wifidb.cron" /etc/cron.d/wifidb
echo 'd /run/wifidb 0775 www-data www-data -' > /etc/tmpfiles.d/wifidb.conf
install -m 0755 "$here/wifidb-firstboot" /usr/local/sbin/wifidb-firstboot
install -m 0644 "$here/wifidb-firstboot.service" /etc/systemd/system/wifidb-firstboot.service
systemctl daemon-reload

log "done"
