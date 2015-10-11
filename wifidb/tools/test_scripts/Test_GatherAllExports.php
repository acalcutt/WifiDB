<?php
/**
 * Created by JetBrains PhpStorm.
 * User: sysferland
 * Date: 5/10/13
 * Time: 3:41 PM
 * To change this template use File | Settings | File Templates.
 */

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "export");


require('../config.inc.php');
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );

$dbcore->export->named = 0;
$dbcore->export->GatherAllExports();

$dbcore->export->named = 1;
$dbcore->export->GatherAllExports();