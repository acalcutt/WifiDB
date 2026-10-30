#!/bin/bash
# Installs Microsoft's ODBC Driver 18 for SQL Server and the sqlsrv / pdo_sqlsrv PHP extensions (built with PECL).
# Run by install-wifidb.sh in its own process, so a failed step really stops it (set -e is ignored inside a function
# that is run as an `if` condition).
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

list=/etc/apt/sources.list.d/mssql-release.list
key=/usr/share/keyrings/microsoft-prod.gpg
release=$(lsb_release -rs)
phpver=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')

# Microsoft signs newer repositories with a second key (microsoft-2025.asc); trust both.
{
    curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor
    curl -fsSL https://packages.microsoft.com/keys/microsoft-2025.asc | gpg --dearmor || true
} > "$key"

# This Ubuntu's own repository first; a brand new release may not have the driver yet, and then the previous LTS's
# packages are used (they're plain binaries that run on the newer system).
found=
for repo in "$release" 24.04; do
    curl -fsSL "https://packages.microsoft.com/config/ubuntu/$repo/prod.list" -o "$list" || continue
    if ! grep -q signed-by "$list"; then
        if grep -q '^deb \[' "$list"; then
            sed -i "s#^deb \\[#deb [signed-by=$key #" "$list"
        else
            sed -i "s#^deb #deb [signed-by=$key] #" "$list"
        fi
    fi
    if apt-get update -q; then
        # (no `| grep -q` here: with pipefail it can fail a pipeline that matched)
        candidate=$(apt-cache policy msodbcsql18 | awk '/Candidate:/ {print $2}')
        if [ -n "${candidate:-}" ] && [ "$candidate" != "(none)" ]; then
            echo "using Microsoft's Ubuntu $repo repository (msodbcsql18 $candidate)"
            found=$repo
            break
        fi
    fi
    echo "Microsoft's Ubuntu $repo repository has no usable msodbcsql18"
done
[ -n "$found" ] || { echo "no Microsoft repository provides msodbcsql18"; exit 1; }

ACCEPT_EULA=Y apt-get install -y -q msodbcsql18 mssql-tools18 unixodbc
apt-get install -y -q --no-install-recommends php-dev php-pear build-essential unixodbc-dev
pecl channel-update pecl.php.net >/dev/null 2>&1 || true
printf '\n' | pecl install sqlsrv
printf '\n' | pecl install pdo_sqlsrv
for ext in sqlsrv pdo_sqlsrv; do
    echo "extension=$ext.so" > "/etc/php/$phpver/mods-available/$ext.ini"
    phpenmod -v "$phpver" "$ext"
done
apt-get purge -y -q php-dev build-essential unixodbc-dev
apt-get autoremove -y -q

# it has to load, with the ODBC libraries that are left
php -m | grep -x pdo_sqlsrv >/dev/null
php -m | grep -x sqlsrv >/dev/null
/opt/mssql-tools18/bin/sqlcmd -? >/dev/null
echo "pdo_sqlsrv and the ODBC driver are installed"
