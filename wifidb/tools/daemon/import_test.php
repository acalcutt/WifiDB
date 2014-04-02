<?php
error_reporting(E_ALL|E_STRICT);

global $screen_output;
$screen_output = "CLI";

if(!(require('config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";
echo "
#####################################
##       File Number ONE (1)       ##
#####################################
";
$dbcore->verbose = 1;
$dbcore->import_vs1( '/var/www/wifidb/import/up/atlanta_hotel.VS1', 'pferland', 'no notes', 'Test 1', date("Y-m-d H:i:s"));

/*
echo "
*************************************
**       File Number TWO (2)       **
*************************************
";

$dbcore->import_vs1( '/var/www/wifidb/import/up/atlanta_hotel_2.VS1', 'pferland', 'no notes', 'Test 2', date("Y-m-d H:i:s"));



echo "
*************************************
**       File Number TWO (2)       **
*************************************
";

$dbcore->import_vs1( '/var/www/wifidb/import/up/atlanta_hotel_3.VS1', 'pferland', 'no notes', 'Large File Test', date("Y-m-d H:i:s"));
*/

?>
