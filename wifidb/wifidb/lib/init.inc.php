<?php
require(dirname(__FILE__).'/config.inc.php');
require(dirname(__FILE__).'/../smarty/libs/Autoloader.php');
Smarty_Autoloader::register();
/*
Init.inc.php, Initialization script for WiFiDB both CLI and HTTP
Copyright (C) 2016 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/
// Show all error's with strict santex
//***DEV USE ONLY*** TODO: remove dev stuff
#ini_set('display_errors', 1);//***DEV USE ONLY***
#ini_set("screen.enabled", TRUE);//***DEV USE ONLY***
#error_reporting(E_ALL);# || E_STRICT);//***DEV USE ONLY***
//***DEV USE ONLY***
date_default_timezone_set('UTC'); //setting the time zone to GMT(Zulu) for internal keeping, displays will soon be customizable for the users time zone
if(!function_exists('WiFiDBexception_handler')) {
	function WiFiDBexception_handler($err)
	{
		require(dirname(__FILE__).'/config.inc.php');
		$trace = array('Error' => strval($err->getCode()), 'Message' => str_replace("\n", "</br>\r\n", $err->getMessage()), 'Code' => strval($err->getCode()), 'File' => $err->getFile(), 'Line' => strval($err->getLine()));
		switch (strtolower(SWITCH_SCREEN)) {
			case "html":
				$WWW_DIR = $config['wifidb_install'];
				$URL_PATH = $config['hosturl'].$config['root'].'/';
				if (isset($_COOKIE['wifidb_theme']) && $_COOKIE['wifidb_theme'] != '') {$theme = $_COOKIE['wifidb_theme'];}else{$theme = $config['default_theme'];}
				
				$smarty = new Smarty();
				$smarty->template_dir = $config['wifidb_install'].'themes/'.$theme.'/templates/';
				$smarty->compile_dir  = $config['wifidb_install'].'smarty/templates_c/'.$theme.'/';
				$smarty->config_dir   = $config['wifidb_install'].'smarty/configs/'.$theme.'/';
				$smarty->cache_dir    = $config['wifidb_install'].'smarty/cache/'.$theme.'/';
				$smarty->assign('themeurl', $URL_PATH .'themes/'.$theme.'/');
				$smarty->assign('wifidb_error_mesg', $trace);
				$smarty->display("error.tpl");
				break;

			case "cli":
				var_dump($err);
				break;

			default:
				echo "Unknown screen switch, here is a raw dump of the error...\r\n" . var_export($trace, 1);
				break;
		}
	}
}
set_exception_handler('WiFiDBexception_handler');

if(strtolower(SWITCH_SCREEN) == "cli")
{
	if(!file_exists($config['wifidb_tools'].'daemon.config.inc.php'))
	{
		$error_msg = 'There was no config file found. You will need to install WiFiDB first. Please go to /[WiFiDB ROOT]/install/ (The install page) to do that.';
		throw new ErrorException($error_msg);
	}
	require $config['wifidb_tools'].'daemon.config.inc.php';
}
$dsn = $config['srvc'].':dbname='.$config['db'].';host='.$config['host'].';charset='.$config['charset'];
$options = array(
	PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$config['charset'].' COLLATE '.$config['collate'],
);

$conn = new PDO($dsn, $config['db_user'], $config['db_pwd'], $options);
$sql = "SELECT `version` FROM `settings` LIMIT 1";
$res = $conn->query($sql);
$fetch = $res->fetch(2);

unset($res);
unset($conn);
if($fetch['version'] != '0.40')
{
	$cwd = getcwd().'/';
	$gen_cwd = $_SERVER['DOCUMENT_ROOT'].$config['root'].'/install/upgrade/';
	if($cwd != $gen_cwd)
	{
		throw new ErrorException('The database is not in the 0.40 format, you will need to do an upgrade first.<br>
				If this database is older than Version 0.30 I would do a Full Fresh Install, After making a backup of all your data.
				Please go '.$config['hosturl'].$config['root'].'/install/ to do that, or you can run the new command line upgrader in the tools folder');
	}
}
unset($fetch);
unset($gen_cwd);
unset($cwd);
unset($sql);

if( (strtolower(SWITCH_SCREEN) === "html") && ( strtolower(SWITCH_EXTRAS) !== "api") && ( strtolower(SWITCH_EXTRAS) !== "apiv2")  )
{
    if ((!@isset($_COOKIE['wifidb_client_check']) || !@$_COOKIE['wifidb_client_timezone'])) {
        create_base_cookies($config['hosturl'] . $config['root']);
        exit();
    }
}

/*
 * Class autoloader
 */
function autoload_function($class) {    
	if(file_exists($GLOBALS['config']['wifidb_install'].'lib/'.$class.'.inc.php'))
	{
		include_once $GLOBALS['config']['wifidb_install'].'lib/'.$class.'.inc.php';
		return 1;
	}elseif(file_exists($GLOBALS['config']['wifidb_tools'].'daemon/lib/'.$class.'.inc.php'))
	{
		include_once $GLOBALS['config']['wifidb_tools'].'daemon/lib/'.$class.'.inc.php';
		return 1;
	}elseif(file_exists($GLOBALS['config']['wifidb_install'].'lib/'.$class.'.php'))
	{
		include_once $GLOBALS['config']['wifidb_install'].'lib/'.$class.'.php';
		return 1;
	}else
	{
		throw new errorexception("Could not load class `{$class}`");
	}
}
spl_autoload_register('autoload_function'); 

try
{
	switch(strtolower(SWITCH_SCREEN))
	{
		case "cli":
			switch(strtolower(SWITCH_EXTRAS))
			{
				####
				case "export":
					$dbcore = new daemon($config, $daemon_config);
					$dbcore->convert = new convert($config);
					$dbcore->Zip = new Zip;
					$dbcore->createKML = new createKML($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 5, $dbcore->convert);
					$dbcore->createGeoJSON = new createGeoJSON($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 5, $dbcore->convert);
					$dbcore->export = new export($config, $dbcore->createKML, $dbcore->createGeoJSON, $dbcore->convert, $dbcore->Zip);
				break;
				####
				case "import":
					$dbcore = new daemon($config, $daemon_config);
					$dbcore->convert = new convert($config);
					$dbcore->import = new import($config, $dbcore->convert, $dbcore->verbose );
				####
				case "daemon":
					$dbcore = new daemon($config, $daemon_config);
					$dbcore->convert = new convert($config);
					$dbcore->Zip = new Zip;
					$dbcore->createKML = new createKML($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 5, $dbcore->convert);
					$dbcore->createGeoJSON = new createGeoJSON($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 5, $dbcore->convert);
					$dbcore->export = new export($config, $dbcore->createKML, $dbcore->createGeoJSON, $dbcore->convert, $dbcore->Zip);
					$dbcore->import = new import($config, $dbcore->convert, $dbcore->verbose );
				break;
				####
				case "cli":
					$dbcore = new wdbcli($config, $daemon_config);
				break;
				####
				case "api":
					$dbcore = new api($config);
					break;
				####
				case "frontend_prep":
					$dbcore = new frontend($config);
					break;
				####
				default:
					die("bad cli extras switch.");
					break;
			}
			$dbcore->cli = 1;
			break;

		################
		case "html":
			switch(strtolower(SWITCH_EXTRAS))
			{
				case "api":
					$dbcore = new api($config);
					$dbcore->convert = new convert($config);
					$dbcore->Zip = new Zip;
					$dbcore->createKML = new createKML($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 5, $dbcore->convert);
					$dbcore->createGeoJSON = new createGeoJSON($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 5, $dbcore->convert);
					$dbcore->export = new export($config, $dbcore->createKML, $dbcore->createGeoJSON, $dbcore->convert, $dbcore->Zip);
				break;

                case "apiv2":
                    $dbcore = new apiv2($config, $SQL);
                    $dbcore->convert = new convert($config, $SQL);
                    $dbcore->Zip = new Zip;
                    $dbcore->createKML = new createKML($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 2, $dbcore->convert);
					$dbcore->createGeoJSON = new createGeoJSON($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 2, $dbcore->convert);
                    $dbcore->export = new export($config, $dbcore->createKML, $dbcore->createGeoJSON, $dbcore->convert, $dbcore->Zip, NULL, $SQL);
				break;

				case "export":
					$dbcore = new frontend($config);
					$dbcore->convert = new convert($config);
					$dbcore->Zip = new Zip;
					$dbcore->createKML = new createKML($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 5, $dbcore->convert);
					$dbcore->createGeoJSON = new createGeoJSON($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 5, $dbcore->convert);
					$dbcore->export = new export($config, $dbcore->createKML, $dbcore->createGeoJSON, $dbcore->convert, $dbcore->Zip);
				break;

				case "graph":
					$dbcore = new frontend($config);
					$dbcore->graphs = new graphs($dbcore->PATH, $dbcore->URL_PATH);
				break;

				case "cp":
					$dbcore = new frontend($config);
				break;

				default:
					$dbcore = new frontend($config);
				break;
			}
			$dbcore->cli = 0;
			break;
		################
		Default:
			die("Unknown Switch Set. gurgle...cough...dead...");
			break;
	}
	#done setting up WiFiDB, whether it be the daemon or the web interface, or just plain failing in a spectacular fashion...
}
catch (Exception $e) {
	throw new ErrorException($e);
}


if(!function_exists('CLIErrorHandlingFunction'))
{
	function CLIErrorHandlingFunction($message, $type = E_USER_NOTICE)
	{
		$backtrace = debug_backtrace();
		foreach ($backtrace as $entry) {
			if ($entry['function'] == __FUNCTION__) {
				trigger_error($entry['file'] . '#' . $entry['line'] . ' ' . $message, $type);
				return true;
			}
		}
		return false;
	}
}

function create_base_cookies($URL_PATH)
{
	$ssl_flag = parse_url($URL_PATH, PHP_URL_SCHEME);
	if($ssl_flag == "https")
	{
		$ssl = ";secure";
	}else
	{
		$ssl = "";
	}
	$domain = ";domain=".$_SERVER['HTTP_HOST'];
	$folder = parse_url($URL_PATH, PHP_URL_PATH);
	$c = strlen($folder);
	if($folder[$c-1] == "/" && $c > 1)
	{
		$root = substr($folder, 0, -1);
	}else
	{
		$root = $folder;
	}
	$PATH	   = ";path=".$root;
	$ultimate_path = $_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING'];
	?>
<script type="text/javascript">
	function checkTimeZone()
	{
		var expiredays = 86400;
		var rightNow = new Date();
        var exdate=new Date();
		var date1 = new Date(rightNow.getFullYear(), 0, 1, 0, 0, 0, 0);
		var date2 = new Date(rightNow.getFullYear(), 6, 1, 0, 0, 0, 0);
		var temp = date1.toGMTString();
		var date3 = new Date(temp.substring(0, temp.lastIndexOf(" ")-1));
		var temp = date2.toGMTString();
		var date4 = new Date(temp.substring(0, temp.lastIndexOf(" ")-1));
		var hoursDiffStdTime = (date1 - date3) / (1000 * 60 * 60);
		var hoursDiffDaylightTime = (date2 - date4) / (1000 * 60 * 60);
		if (hoursDiffDaylightTime == hoursDiffStdTime)
		{
			exdate.setDate(exdate.getDate()+expiredays);
			document.cookie="wifidb_client_dst=0" + ((expiredays==null) ? "" : "<?php echo $domain.$PATH.$ssl; ?>;expires=" +exdate.toUTCString());

			exdate.setDate(exdate.getDate()+expiredays);
			document.cookie="wifidb_client_check=1" + ((expiredays==null) ? "" : "<?php echo $domain.$PATH.$ssl; ?>;expires=" +exdate.toUTCString());

			exdate.setDate(exdate.getDate()+expiredays);
			document.cookie="wifidb_client_timezone=" + hoursDiffStdTime +((expiredays==null) ? "" : "<?php echo $domain.$PATH.$ssl; ?>;expires=" +exdate.toUTCString());
		}
		else
		{
			exdate.setDate(exdate.getDate()+expiredays);
			document.cookie="wifidb_client_dst=1" + ((expiredays==null) ? "" : "<?php echo $domain.$PATH.$ssl; ?>;expires=" +exdate.toUTCString());

			exdate.setDate(exdate.getDate()+expiredays);
			document.cookie="wifidb_client_check=1" + ((expiredays==null) ? "" : "<?php echo $domain.$PATH.$ssl; ?>;expires=" +exdate.toUTCString());

			exdate.setDate(exdate.getDate()+expiredays);
			document.cookie="wifidb_client_timezone=" + hoursDiffStdTime + ((expiredays==null) ? "" : "<?php echo $domain.$PATH.$ssl; ?>;expires=" +exdate.toUTCString());
		}
		location.href = '<?php echo $ultimate_path; ?>';
	}
	</script>
	<body onload = "checkTimeZone();"> </body>
	<?php
	exit();
}
