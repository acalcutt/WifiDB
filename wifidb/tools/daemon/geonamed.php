<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit  = "2014-10-18";

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
    echo "Usage: geonamed.php [args...]
  -v               Run Verbosely (SHOW EVERYTHING!)
  -i               Version Info.
  -d               Run continuously without stop (as a daemon)
  -h               Show this screen.
  -l               Show License Information.
  
* = Not working yet.
";
    exit();
}

if(@$arguments['i'])
{
    $dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
Geoname Daemon 3.0, {$lastedit}, GPLv2 Random Intervals");
    exit();
}

if(@$arguments['l'])
{
    $dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
Geoname Daemon 3.0, {$lastedit}, GPLv2 Random Intervals

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

ou should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
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

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
    mkdir($dbcore->pid_file_loc);
}
$dbcore->pid_file = $dbcore->pid_file_loc.'geonamed.pid';

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

$dbcore->verbosed("
WiFiDB 'Geoname Daemon'
Version: 3.0.0
- Daemon Start: 2010-06-23
- Last Daemon File Edit: 2014-10-15
( /tools/daemon/geonamed.php )
- By: Phillip Ferland ( pferland@randomintervals.com ), Andrew Calcutt (acalcutt@vistumbler.net)
- http://www.randomintervals.com/wifidb/
");


$dbcore->verbosed("Have written the PID file at ".$dbcore->pid_file." (".$dbcore->This_is_me.")");

if($dbcore->time_interval_to_check < '30'){$dbcore->time_interval_to_check = '30';} //its really pointless to check more then 5 min at a time, becuse if it is
$finished = 0;
//Main loop
while(1)
{
    $dbcore->verbosed("Start Gather of WiFiDB GeoNames");
    
    if(is_null($dbcore->sql))
    {
        $dbcore->sql = new SQL($config);
    }

    $sql = "SELECT `id`,`lat`,`long`,`ap_hash` FROM `wifi`.`wifi_pointers` WHERE `geonames_id` = '' AND `lat` != '0.0000' ORDER BY `id` ASC";
    echo $sql."\r\n";
    $result = $dbcore->sql->conn->query($sql);
    $dbcore->verbosed("Gathered Wtable data");
    echo "Rows that need updating: ".$result->rowCount()."\r\n";
    sleep(4);
    while($ap = $result->fetch(1))
    {
        $dbcore->verbosed($ap['id']." - ".$ap['ap_hash']);
        $lat = round($dbcore->convert->dm2dd($ap['lat']), 1);
        $long = round($dbcore->convert->dm2dd($ap['long']), 1);
        $dbcore->verbosed("Lat - Long: ".$lat." [----] ".$long);
        $sql = "SELECT `geonameid`, `country code`, `admin1 code`, `admin2 code` FROM `wifi`.`geonames` WHERE `latitude` LIKE '$lat%' AND `longitude` LIKE '$long%' LIMIT 1";
        $dbcore->verbosed("Query Geonames Table to see if there is a location in an area that is equal to the geocord rounded to the first decimal.", 3);
        $geo_res = $dbcore->sql->conn->query($sql);
        $geo_array = $geo_res->fetch(PDO::FETCH_ASSOC);
        if(!$geo_array['geonameid'])
        {continue;}
        
        $dbcore->verbosed("Geoname ID: ".$geo_array['geonameid']);
        $admin1_array = array('id'=>'');
        $admin2_array = array('id'=>'');
        if($geo_array['admin1 code'])
        {
            $dbcore->verbosed("Admin1 Code is Numeric, need to query the admin1 table for more information.");
            $admin1 = $geo_array['country code'].".".$geo_array['admin1 code'];
            
            $sql = "SELECT `id` FROM `wifi`.`geonames_admin1` WHERE `admin1`='$admin1'";
            $admin1_res = $dbcore->sql->conn->query($sql);
            $admin1_array = $admin1_res->fetch(PDO::FETCH_ASSOC);
        }
        if(is_numeric($geo_array['admin2 code']))
        {
            $dbcore->verbosed("Admin2 Code is Numeric, need to query the admin2 table for more information.");
            $admin2 = $geo_array['country code'].".".$geo_array['admin1 code'].".".$geo_array['admin2 code'];
            $sql = "SELECT `id` FROM `wifi`.`geonames_admin2` WHERE `admin2`='$admin2'";
            $admin2_res = $dbcore->sql->conn->query($sql);
            $admin2_array = $admin2_res->fetch(PDO::FETCH_ASSOC);
        }

        $sql = "UPDATE `wifi`.`wifi_pointers` SET `geonames_id` = '{$geo_array['geonameid']}', `admin1_id` = '{$admin1_array['id']}', `admin2_id` = '{$admin2_array['id']}' WHERE `ap_hash` = '{$ap['ap_hash']}'";
        if($dbcore->sql->conn->query($sql))
        {
            $dbcore->verbosed("Updated AP's Geolocation  [{$ap['id']}] ({$ap['ap_hash']})" , 2);
        }else
        {
            $dbcore->verbosed("Failed to update AP's Geolocation [{$ap['id']}] ({$ap['ap_hash']})", -1);
            var_dump($dbcore->sql->conn->errorInfo());
        }
    }

    if(@$arguments['d']){
        ##### Set next run time
        $wait_interval=86400;
        $nextrun = date("Y-m-d H:i:s", (time()+$wait_interval));
        $dbcore->verbosed("Next check in T+ ".$wait_interval."s at ".$nextrun);
        $dbcore->sql = NULL;
        sleep($wait_interval);
    }else{
        break;
    }
}
?>