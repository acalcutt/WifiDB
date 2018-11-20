<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

if(!(require('/etc/wifidb/daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

#Fix date and time in the wifi_gps tables

$sql = "SELECT * FROM `wifi_gps` ORDER BY `date` ASC";
$result = $dbcore->sql->conn->query($sql);
$dbcore->verbosed("Fixing wifi_gps date/time stamps");
while($array = $result->fetch(1))
{
	$id = $array['id'];
	
	$datestamp = $array['date'];
	$new_datestamp = "";
	$secs = explode('-' , $datestamp);
	$num_secs = count($secs);
	//echo "secs:".$num_secs."\r\n";
	if ($num_secs == 3)
	{
		$sec1_size = strlen($secs[0]);
		if ($sec1_size == 4)
		{
			//format is correct
			$new_datestamp = $datestamp;
		}
		else
		{
			//format is incorrect...reformat
			$new_datestamp = $secs[2]."-".$secs[0]."-".$secs[1];
		}
	}
	else
	{
		$new_datestamp = "0000-00-00";
	}
	
	if($new_datestamp != $datestamp)
	{
		echo "wifi_gps:".$id." - ".$datestamp."-->".$new_datestamp."\r\n";
		$sql = "UPDATE `wifi_gps` SET `date`=? WHERE `id`=?";
		$prepnr = $dbcore->sql->conn->prepare($sql);
		$prepnr->bindParam(1, $new_datestamp, PDO::PARAM_STR);
		$prepnr->bindParam(2, $id, PDO::PARAM_INT);
		$prepnr->execute();
	}
	
	$timestamp = $array['time'];
	$secs = explode(':' , $timestamp);
	$num_secs = count($secs);
	//echo "secs:".$num_secs."\r\n";
	if ($num_secs == 3)
	{
		//format is correct
		$new_timestamp = $timestamp;
	}
	else
	{
		$new_timestamp = "00:00:00";
	}

	
	if($new_timestamp != $timestamp)
	{
		echo "wifi_gps:".$id." - ".$timestamp."-->".$new_timestamp."\r\n";
		$sql = "UPDATE `wifi_gps` SET `time`=? WHERE `id`=?";
		$prepnr = $dbcore->sql->conn->prepare($sql);
		$prepnr->bindParam(1, $new_timestamp, PDO::PARAM_STR);
		$prepnr->bindParam(2, $id, PDO::PARAM_INT);
		$prepnr->execute();
	}	
}

#Fix date and time in the wifi_pointers table

$sql = "SELECT * FROM `wifi_pointers`";
$result = $dbcore->sql->conn->query($sql);
$dbcore->verbosed("Fixing wifi_pointers date/time stamps");
while($array = $result->fetch(1))
{
	$id = $array['id'];
	
	#Fix First Active Date
	$fastamp = $array['FA'];
	$secs = explode(' ' , $fastamp);
	$num_secs = count($secs);
	if ($num_secs == 2)
	{
		$datestamp = $secs[0];
		$secs2 = explode('-' , $datestamp);
		$num_secs2 = count($secs2);
		//echo "secs2:".$num_secs2."\r\n";
		if ($num_secs2 == 3)
		{
			$sec1_size = strlen($secs2[0]);
			if ($sec1_size == 4)
			{
				//format is correct
				$new_datestamp = $datestamp;
			}
			else
			{
				//format is incorrect...reformat
				$new_datestamp = $secs2[2]."-".$secs2[0]."-".$secs2[1];
			}
		}
		else
		{
			$new_datestamp = "0000-00-00";
		}		
		
		$timestamp = $secs[1];
		$secs2 = explode(':' , $timestamp);
		$num_secs2 = count($secs2);
		//echo "secs2:".$num_secs2."\r\n";
		if ($num_secs2 == 3)
		{
			//format is correct
			$new_timestamp = $timestamp;
		}
		else
		{
			$new_timestamp = "00:00:00";
		}
		$new_fastamp = $new_datestamp." ".$new_timestamp;
	}
	else
	{
		$new_fastamp = "0000-00-00 00:00:00";
	}
	
	if($new_fastamp != $fastamp)
	{
		echo "wifi_pointer:".$id." - ".$fastamp."-->".$new_fastamp."\r\n";
		$sql = "UPDATE `wifi_pointers` SET `FA`=? WHERE `id`=?";
		$prepnr = $dbcore->sql->conn->prepare($sql);
		$prepnr->bindParam(1, $new_fastamp, PDO::PARAM_STR);
		$prepnr->bindParam(2, $id, PDO::PARAM_INT);
		$prepnr->execute();
	}	
		
	#Fix Last Active Date
	$lastamp = $array['LA'];
	$secs = explode(' ' , $lastamp);
	$num_secs = count($secs);
	if ($num_secs == 2)
	{
		$datestamp = $secs[0];
		$secs2 = explode('-' , $datestamp);
		$num_secs2 = count($secs2);
		//echo "secs2:".$num_secs2."\r\n";
		if ($num_secs2 == 3)
		{
			$sec1_size = strlen($secs2[0]);
			if ($sec1_size == 4)
			{
				//format is correct
				$new_datestamp = $datestamp;
			}
			else
			{
				//format is incorrect...reformat
				$new_datestamp = $secs2[2]."-".$secs2[0]."-".$secs2[1];
			}
		}
		else
		{
			$new_datestamp = "0000-00-00";
		}		
		
		$timestamp = $secs[1];
		$secs2 = explode(':' , $timestamp);
		$num_secs2 = count($secs2);
		//echo "secs2:".$num_secs2."\r\n";
		if ($num_secs2 == 3)
		{
			//format is correct
			$new_timestamp = $timestamp;
		}
		else
		{
			$new_timestamp = "00:00:00";
		}
		$new_lastamp = $new_datestamp." ".$new_timestamp;
	}
	else
	{
		$new_lastamp = "0000-00-00 00:00:00";
	}
	
	if($new_lastamp != $lastamp)
	{
		echo "wifi_pointer:".$id." - ".$lastamp."-->".$new_lastamp."\r\n";
		$sql = "UPDATE `wifi_pointers` SET `LA`=? WHERE `id`=?";
		$prepnr = $dbcore->sql->conn->prepare($sql);
		$prepnr->bindParam(1, $new_lastamp, PDO::PARAM_STR);
		$prepnr->bindParam(2, $id, PDO::PARAM_INT);
		$prepnr->execute();
	}
}

#When Fisrt Active is blank and Last Active has a value, set First Active to Last Active
$dbcore->verbosed("Fixing wifi_pointers blank FA");
$sql = "UPDATE `wifi_pointers` SET FA=LA WHERE FA='0000-00-00 00:00:00' and LA <> '0000-00-00 00:00:00'";
$fix1 = $dbcore->sql->conn->query($sql);

$dbcore->verbosed("Fixing wifi_pointers blank LA");
#When Last Active is blank and First Active has a value, set Last Active to First Active
$sql = "UPDATE `wifi_pointers` SET LA=FA WHERE LA='0000-00-00 00:00:00' and FA <> '0000-00-00 00:00:00'";
$fix2 = $dbcore->sql->conn->query($sql);
?>