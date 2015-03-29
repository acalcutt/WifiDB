#!/usr/bin/php
<?php
/*
ImportThreading.php, WiFiDB Import Daemon
Copyright (C) 2015 by Phil Ferland.
This script is made to do imports and be run as a cron job.
It will run the number of import scripts as you set $NumberOfThreads to.
I did not see an imporvement in import time after 20 threads.
20 Threads imported 430,000 APs and their signal and GPS data in just over 1 hour and 45 minutes
This was with 8 vCPU's and 16GB of RAM.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/

/*
 * DANGER, Will Robinson!!
 * DANGER, Will Robinson!!
 * DANGER, Will Robinson!!
 * This Process WILL use a shit load of RAM.
 * On my Dev server with 8 vCPU's and 16GB of RAM, 20 import processes after 340,000 APs was using 8GB of RAM.
 * Just a reminder Don't say I didn't warn you that this script will most likely bring your server to its knees and beg for mercy.
 */
$NumberOfThreads = 20;


for ($i = 1; $i <= $NumberOfThreads; ++$i)
{
	sleep(2);
	$pid = pcntl_fork();

	if (!$pid)
	{
		exec("php ../daemon/importd.php -f -t=$i", $output);
		file_put_contents($dbcore->log_path."Import/".$pid."_".$i."_ConsoleOutput.log" , $output);
		exit($i);
	}
}

while (pcntl_waitpid(0, $status) != -1)
{
	$status = pcntl_wexitstatus($status);
	echo "Child $status completed\n";
}
