<?php
global $switches;
$switches = array('screen'=>"CLI",'extras'=>'export');

if(!(require('daemon/config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}

if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($daemon_config['wifidb_install']."/lib/init.inc.php");

$daemon_sql = "SELECT * FROM `wifi`.`files_tmp` where `file` LIKE '%.csv' ORDER BY `date` DESC";
$result = $dbcore->sql->conn->query($daemon_sql);
if($result)//Check to see if I can successfully look at the file_tmp folder
{
    while($files_a = $result->fetch(2))
    {
        echo $dbcore->convert_logic($files_a['file'], $files_a['id'])."\r\n";
    }
}
?>