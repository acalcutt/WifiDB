<?php
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "export");
error_reporting("E_ALL");

require('../config.inc.php');
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );

$daemon_sql = "SELECT * FROM `wifi`.`files_tmp` where `file` LIKE '%.csv' ORDER BY `date` DESC";
$result = $dbcore->sql->conn->query($daemon_sql);
if($result)//Check to see if I can successfully look at the file_tmp folder
{
    while($files_a = $result->fetch(2))
    {
        echo $dbcore->convert->main($files_a['file'])."\r\n";
    }
}