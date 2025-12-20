#!/usr/bin/php
<?php
/*
SpawnImportDaemons.php, WiFiDB Import Daemon Spawner
Copyright (C) 2015 Andrew Calcutt, Phil Ferland.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "import");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit			=	"2015-10-12";
$dbcore->daemon_name	=	"Import";

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
    echo "Usage: SpawnImportDaemons.php [args...]
  -n        (int)           Number of Import Daemons to spawn.
  -f		(null)			Force daemon to run without being scheduled.
  -o		(null)			Run a loop through the files waiting table, and end once done. ( Will override the -d argument. )
  -d		(null)			Run the Import script as a Daemon. ( Will override the -i argument. )
  -v		(null)			Run Verbosely (SHOW EVERYTHING!)
  -l		(null)			Show License Information.
  -h		(null)			Show this screen.
  --version	(null)			Version Info.

* = Not working yet.
";
    exit(-1);
}
if(@$arguments['n'])
{
    $dbcore->Spawn = (int)$arguments['n'];
}else
{
    exit("You need to specify the number to import daemons to spawn other wise there is no point to this script..");
}
if(@$arguments['f'])
{
    $dbcore->ForceDaemonRun = "-f";
}else
{
    $dbcore->ForceDaemonRun = "";
}
if(@$arguments['o'])
{
    $dbcore->RunOnceThrough = "-o";
}else
{
    $dbcore->RunOnceThrough = "";
}
if(@$arguments['d'])
{
    $dbcore->daemonize = "-d";
}else
{
    $dbcore->daemonize = "";
}
if(@$arguments['v'])
{
    $dbcore->verbose = "-v";
}else
{
    $dbcore->verbose = "";
}
if(@$arguments['l'])
{
    echo "WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2 Random Intervals
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
Copyright (C) 2015 Andrew Calcutt, Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
";
    exit(-3);
}
if(@$arguments['version'])
{
    echo "WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2 Random Intervals\n";
    exit(-2);
}
$threadNumber = 0;
$options = $dbcore->ForceDaemonRun." ".$dbcore->daemonize." ".$dbcore->verbose." ".$dbcore->RunOnceThrough;
var_dump($options);
var_dump($dbcore->Spawn);
for($i = 1; $i <= $dbcore->Spawn; $i++)
{
    if($i !== 1) sleep(2);
    $threadNumber++;
    var_dump($threadNumber);
    $cmd = "nohup php /wifidb/code/tools/daemon/importd.php -t=$i $options > /wifidb/logs/wifidb/importd_$i.log  2>&1 &";
    #var_dump($cmd);
    exec($cmd);
}
