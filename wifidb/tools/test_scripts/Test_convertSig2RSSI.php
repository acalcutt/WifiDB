<?php
/**
 * Created by JetBrains PhpStorm.
 * User: sysferland
 * Date: 5/9/13
 * Time: 4:16 PM
 * To change this template use File | Settings | File Templates.
 */

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "import");


require('../daemon/config.inc.php');
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );


var_dump($dbcore->import->convertdBm2Sig(-35));
var_dump($dbcore->import->convertSig2dBm(75));

