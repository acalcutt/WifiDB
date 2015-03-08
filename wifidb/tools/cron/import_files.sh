#!/bin/bash

cd /opt/wifidb/tools/daemon/
if ps -ef | grep -v grep | grep importd.php ; then
	echo "Import Daemon Already Running! Exiting!"
else
	echo "Starting Import Daemon..."
		timestamp=$(date +%s)
        php importd.php -v > /opt/wifidb/tools/log/import/importd_${timestamp}.log &
fi

echo "Cleaning up old logs..."
find /opt/wifidb/tools/log/import/ -mtime +7 -type f -delete

echo "Done. Exiting."
exit 0