#!/usr/bin/php
<?php
/*
GenerateBoardersFromKML.php, WiFiDB Import Daemon
Copyright (C) 2015 Phil Ferland.
Used to generate the boarders data from a KML file.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "cli");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit  = "2015-03-02";
echo "
##########################################################
##       Start Gathering of Boarder data from KML       ##
##########################################################
";
$dbcore->verbose = 1;

$dir = $GLOBALS['wifidb_install']."/import/up/";
if ($dh = opendir($dir))
{
    while(($file = readdir($dh)) !== false)
    {
        $exp = explode(".", $file);
        $c = count($exp)-1;
        $ext = $exp[$c];
        if(strtolower($ext) != "vs1")
        {
            continue;
        }
        $file_cont = file($dir.$file);

        #echo $file_cont[1]."\r\n";

        $exp_line = explode(":", $file_cont[1]);

        if(!@$exp_line[1])
        {
            echo $file."\r\n";
            echo $file_cont[1]."\r\n";
            // movie file;
            $source = $dir.$file;
            $dest = "/var/www/1/".$file;
            if(copy($source, $dest))
            {unlink($source);}
            else{echo "failed to move\r\n";}
            continue;
        }

        $line_exp = explode(" ", trim($exp_line[1]));
        $file_part = $line_exp[0];
        if($file_part == "RanInt")
        {
            //move file;
            echo $file."\r\n";
            echo $file_cont[1]."\r\n";
            $source = $dir.$file;
            $dest = "/var/www/1/".$file;
            if(copy($source, $dest))
            {unlink($source);}
            else{echo "failed to move\r\n";}
        }
    }
    closedir($dh);
}
?>
