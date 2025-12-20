<?php
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "cli");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->verbose = 1;
$dbcore->named = 1;
$username = $argv[1];
$password = $argv[2];

echo "Test login:\r\n";
var_dump($dbcore->sec->Login($username, $password));
var_dump($dbcore->sec->username);
var_dump($dbcore->sec->pass_hash);
