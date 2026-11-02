#!/bin/bash
# Installs WiFiDB's code and its system files (Apache, PHP-FPM, cron, the services and the updater) from a release's
# files. install-wifidb.sh runs it when the template is built, and wifidb-update runs it from an update package
# (with the old code already moved aside), so both install the same way. Packages are install-wifidb.sh's job.
#
# usage: install-files.sh WEB TOOLS SQL
#   WEB    the site, with Composer's vendor/ already in it   -> /var/www/wifidb
#   TOOLS  the daemons and cron jobs                         -> /opt/wifidb/tools
#   SQL    blank_db.sql, blank_db.sqlsrv, blank_db.pgsql and migrations/   -> /opt/wifidb/sql
# The system files are taken from beside this script.
set -euo pipefail
[ $# -eq 3 ] || { echo "usage: $0 WEB TOOLS SQL" >&2; exit 2; }
web=$(readlink -f "$1"); tools=$(readlink -f "$2"); sql=$(readlink -f "$3")
here=$(cd "$(dirname "$0")" && pwd)
test -f "$web/vendor/autoload.php" || { echo "$web has no vendor/autoload.php (Composer's packages)" >&2; exit 1; }
phpver=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')

log() { echo "[install-files] $*"; }

log "installing the application"
install -d /var/www/wifidb /opt/wifidb/tools /opt/wifidb/sql
rsync -a --delete "$web/" /var/www/wifidb/
rsync -a --delete "$tools/" /opt/wifidb/tools/
rsync -a --delete "$sql/" /opt/wifidb/sql/
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
install -m 0644 "$here/wifidb-health-pool-fpm.conf" "/etc/php/$phpver/fpm/pool.d/wdb-health.conf"
install -m 0644 "$here/wifidb-backup-pool-fpm.conf" "/etc/php/$phpver/fpm/pool.d/wdb-backup.conf"
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

log "services, cron jobs and the updater"
install -m 0644 "$here/wifidb-monitord.service" /etc/systemd/system/wifidb-monitord.service
install -m 0644 "$here/wifidb-websocketd.service" /etc/systemd/system/wifidb-websocketd.service
install -m 0644 "$here/wifidb.cron" /etc/cron.d/wifidb
echo 'd /run/wifidb 0775 www-data www-data -' > /etc/tmpfiles.d/wifidb.conf
install -m 0755 "$here/wifidb-firstboot" /usr/local/sbin/wifidb-firstboot
install -m 0755 "$here/wifidb-dkim" /usr/local/sbin/wifidb-dkim
install -m 0644 "$here/wifidb-firstboot.service" /etc/systemd/system/wifidb-firstboot.service
# the updater (admin/servers.php asks; this does it, as root). wifidb-firstboot switches its timer on.
install -m 0755 "$here/wifidb-update" /usr/local/sbin/wifidb-update
install -m 0644 "$here/wifidb-update.service" /etc/systemd/system/wifidb-update.service
install -m 0644 "$here/wifidb-update.timer" /etc/systemd/system/wifidb-update.timer
install -d -m 0700 /var/lib/wifidb/updates
systemctl daemon-reload

log "done"
