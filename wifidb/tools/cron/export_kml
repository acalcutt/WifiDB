#!/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/opt/unison

cd ${0%/*}
if ps -ef | grep -v grep | grep exportd.php ; then
	echo Export Daemon Already Running! Exiting!
else
	echo Starting Export Daemon...
		timestamp=$(date +%s)
        php ../daemon/exportd.php -v > ../log/export/exportd_${timestamp}.log &
fi

echo "Cleaning up old logs..."
find ../log/export/ -mtime +7 -type f -delete

echo "Done. Exiting."
exit 0