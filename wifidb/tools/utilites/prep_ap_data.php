<?php
/**
 * Created by Phil Ferland
 * Date: 4/21/13
 * Time: 5:33 PM
 * To change this template use File | Settings | File Templates.
 */
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "frontend_prep");
define("DEBUG", 1);

require( '../daemon/config.inc.php' );
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );
require( $daemon_config['wifidb_install']."/lib/config.inc.php" );

$sql = "SELECT `id`, `ssid`, `ap_hash` FROM `wifi`.`wifi_pointers`";
$query = $dbcore->sql->conn->query($sql);
$all = $query->fetchAll(2);
foreach($all as $ap)
{
    echo "Preping for AP: \r\n\tID:{$ap['id']}\r\n\tSSID: {$ap['ssid']}\r\n\tAP Hash: {$ap['ap_hash']}\r\n";
    $dbcore->APFetch($ap['id']);
    echo "Done with that one...\r\n";
}