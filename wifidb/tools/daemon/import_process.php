#!/usr/bin/php
<?php
/*
import_process.php, WiFiDB Import Daemon
Copyright (C) 2015 Andrew Calcutt, based on imp_expd.php by Phil Ferland.
This script is made to do imports and be run off of the importd.php daemon.
It is not possible to run this script on its own, it needs to be controled and scheduled by the importd.php daemon.
This is to prevent the import processes from colliding and thinking that an import is already in, when it is really not.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "import");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit			=	"2015-03-21";
$dbcore->daemon_name	=	"Import";

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
	echo "Usage: import_process.php [args...]
  -f		(null)			Force daemon to run without being scheduled.
  -h		(null)			Show this screen.
  -i        (integer)       The ID Number for the Import to be well... Imported...
  -l		(null)			Show License Information.
  -t		(integer)		Identify the Import Daemon with a Thread ID. Used to track what thread was importing what file in the bab files table.
  -v		(null)			Run Verbosely (SHOW EVERYTHING!)
  -version	(null)			Version Info.

* = Not working yet.
";
	exit();
}

if(@$arguments['version'])
{
	$dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2 Random Intervals");
	exit();
}

if(@$arguments['l'])
{
	$dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2 Random Intervals
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
Copyright (C) 2015 Andrew Calcutt, Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
");
	exit();
}

if(@$arguments['v'])
{
	$dbcore->verbose = 1;
}else
{
	$dbcore->verbose = 0;
}

if(@$arguments['i'])
{
	$dbcore->ImportID = (int)$arguments['i'];
}else
{
	$dbcore->ImportID = 0;
}

if(@$arguments['f'])
{
	$dbcore->ForceDaemonRun = 1;
}else
{
	$dbcore->ForceDaemonRun = 0;
}

if(@$arguments['t'])
{
	$dbcore->thread_id = (int)$arguments['t'];
}else
{
	$dbcore->thread_id = 1;
}

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
	mkdir($dbcore->pid_file_loc);
}
$dbcore->pid_file = $dbcore->pid_file_loc.'import_process_'.$dbcore->This_is_me.'.pid';

if(!file_exists($dbcore->pid_file_loc))
{
	if(!mkdir($dbcore->pid_file_loc))
	{
		throw new ErrorException("Could not make WiFiDB PID folder. ($dbcore->pid_file_loc)");
	}
}
if(file_put_contents($dbcore->pid_file, $dbcore->This_is_me) === FALSE)
{
	die("Could not write pid file ($dbcore->pid_file), that's not good... >:[");
}

$dbcore->verbosed("Have written the PID file at ".$dbcore->pid_file." (".$dbcore->This_is_me.")");

$dbcore->verbosed("
WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
 - {$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
PID File: [ $dbcore->pid_file ]
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level);


trigger_error("Starting Import on Proc: ".$dbcore->thread_id, E_USER_NOTICE);

unlink($dbcore->pid_file);
exit($dbcore->thread_id);