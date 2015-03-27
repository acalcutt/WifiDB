#!/usr/bin/php
<?php
/*
importd.php, WiFiDB Import Daemon
Copyright (C) 2015 Andrew Calcutt, based on ImportThreading.php by Phil Ferland.
This script is made to do imports and be run as a cron job.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
$NumberOfThreads = 20;

for ($i = 1; $i <= $NumberOfThreads; ++$i)
{
	$pid = pcntl_fork();

	if (!$pid)
	{
		exec("php ../daemon/importd.php -f");
		exit($i);
	}
}

while (pcntl_waitpid(0, $status) != -1)
{
	$status = pcntl_wexitstatus($status);
	echo "Child $status completed\n";
}
