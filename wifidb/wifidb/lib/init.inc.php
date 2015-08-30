<?php
/*
Init.inc.php, Initialization script for WiFiDB both CLI and HTTP
Copyright (C) 2013 Phil Ferland

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

/*
 * Class autoloader
 *
 */
if(!function_exists('__autoload'))
{
    function __autoload($class)
    {
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
        }elseif(file_exists($GLOBALS['config']['wifidb_install'].'smarty/'.$class.'.class.php'))
        {
            include_once $GLOBALS['config']['wifidb_install'].'smarty/'.$class.'.class.php';
            return 1;
        }elseif(file_exists($GLOBALS['config']['wifidb_install'].'smarty/sysplugins/'.strtolower($class).'.php'))
        {
            include_once $GLOBALS['config']['wifidb_install'] . 'smarty/sysplugins/' . strtolower($class) . '.php';
            return 1;
        }else
        {
            require_once $class . '.php';
            #throw new errorexception("Could not load class `{$class}`");
        }
    }
}

date_default_timezone_set('UTC'); //setting the time zone to GMT(Zulu) for internal keeping, displays will soon be customizable for the users time zone
#set_exception_handler('WiFiDBexception_handler');

if(strtolower(SWITCH_SCREEN) == "cli")
{
	if(!file_exists('../config.inc.php'))
	{
		$error_msg = 'There was no config file found. You will need to install WiFiDB first. Please go to /[WiFiDB ROOT]/install/ (The install page) to do that.';
		throw new ErrorException($error_msg);
	}
	require '../config.inc.php';
	require $daemon_config['wifidb_install'].'/lib/config.inc.php';
}else
{
	require 'config.inc.php';
}
#if(WDB_DEBUG)
if(1)
{
    /*-----------------------*/
    // Show all error's with strict santex
    //***DEV USE ONLY enable with debug flag in config***
    ini_set('display_errors', 1);//***DEV USE ONLY***
    ini_set("screen.enabled", TRUE);//***DEV USE ONLY***
    error_reporting(E_ALL);# || E_STRICT);//***DEV USE ONLY***
    /*-----------------------*/
}

$SQL = new SQL($config);
$query = "SELECT `version` FROM `wifi`.`settings` LIMIT 1";
$res = $SQL->conn->query($query);
$fetch = $res->fetch(2);

if($fetch['version'] != '0.30 b1 Alpha')
{
	$cwd = getcwd().'/';
	$gen_cwd = $_SERVER['DOCUMENT_ROOT'].$config['root'].'/install/upgrade/';
	if($cwd != $gen_cwd)
	{
		throw new ErrorException('The database is still in an old format, you will need to do an upgrade first.<br>
				If this database is older than Version 0.20 I would do a Full Fresh Install, After making a backup of all your data.
				Please go '.$config['hosturl'].$config['root'].'/install/ to do that, or you can run the new command line upgrader in the tools folder');
	}
}

if(strtolower(SWITCH_SCREEN) === "html") {
    if ((!@isset($_COOKIE['wifidb_client_check']) || !@$_COOKIE['wifidb_client_timezone'])) {
        create_base_cookies($config['hosturl'] . $config['root']);
        exit();
    }
}

try
{
	switch(strtolower(SWITCH_SCREEN))
	{
		case "cli":
			switch(strtolower(SWITCH_EXTRAS))
			{
				####
				case "export":
					$dbcore = new daemon($config, $daemon_config, $SQL);
					$dbcore->convert = new convert($config);
					$dbcore->Zip = new Zip;
					$dbcore->createKML = new createKML($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 2, $dbcore->convert);
					$dbcore->export = new export($config, $dbcore->createKML, $dbcore->convert, $dbcore->Zip, $SQL);
				break;
				####
				case "import":
					$dbcore = new daemon($config, $daemon_config, $SQL);
					$dbcore->convert = new convert($config);
					$dbcore->import = new import($config, $dbcore->convert, $dbcore->verbose, $SQL);
				####
				case "daemon":
					$dbcore = new daemon($config, $daemon_config, $SQL);
					$dbcore->convert = new convert($config);
					$dbcore->Zip = new Zip;
					$dbcore->createKML = new createKML($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 2, $dbcore->convert);
					$dbcore->export = new export($config, $dbcore->createKML, $dbcore->convert, $dbcore->Zip, $SQL);
					$dbcore->import = new import($config, $dbcore->convert, $dbcore->verbose, $SQL);
				break;
				####
				case "cli":
					$dbcore = new wdbcli($config, $daemon_config, $SQL);
				break;
				####
				case "api":
					$dbcore = new api($config, $SQL);
					break;
				####
				case "frontend_prep":
					$dbcore = new frontend($config, $SQL);
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
            $dbcore = new frontend($config, $SQL);
			switch(strtolower(SWITCH_EXTRAS))
			{
				case "export":
					__autoload("createKML");
					__autoload("convert");
					__autoload("export");
					__autoload("Zip");
					$dbcore->convert = new convert($config);
					$dbcore->Zip = new Zip;
					$dbcore->createKML = new createKML($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 2, $dbcore->convert);
					$dbcore->export = new export($config, $dbcore->createKML, $dbcore->convert, $dbcore->Zip, $SQL);
				    break;
				case "graph":
					__autoload("graphs");
					$dbcore->graphs = new graphs($dbcore->PATH, $dbcore->URL_PATH);
				    break;
				case "cp":
				    break;
				default:
				    break;
			}
            $dbcore->cli = 0;
            if($dbcore->sec->privs > 1000)
            {
                $dbcore->smarty->assign('admin_login_link', ' <-> <a href="/wifidb/cp/admin/">Admin Control Panel</a>');
            }
			break;
		################

        case "api":
            $dbcore = new api($config, $SQL);
            switch(SWITCH_EXTRAS)
            {
                case "announce":
                    break;
                case "atomrss":
                    break;
                case "export";
                    __autoload("createKML");
                    __autoload("convert");
                    __autoload("export");
                    __autoload("api");
                    __autoload("Zip");
                    $dbcore = new api($config, $SQL);
                    $dbcore->convert = new convert($config);
                    $dbcore->Zip = new Zip;
                    $dbcore->createKML = new createKML($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 2, $dbcore->convert);
                    $dbcore->export = new export($config, $dbcore->createKML, $dbcore->convert, $dbcore->Zip, $SQL);
                    break;
                case "geonames":
                    break;
                case "import":
                    break;
                case "latest":
                    __autoload("createKML");
                    __autoload("convert");
                    __autoload("export");
                    __autoload("api");
                    __autoload("Zip");
                    $dbcore->convert = new convert($config);
                    $dbcore->Zip = new Zip;
                    $dbcore->createKML = new createKML($dbcore->URL_PATH, $dbcore->kml_out, $dbcore->daemon_out, 2, $dbcore->convert);
                    $dbcore->export = new export($config, $dbcore->createKML, $dbcore->convert, $dbcore->Zip);
                    break;
                case "live":
                    break;
                case "locate":
                    break;
                case "search":
                    break;
                default:
                    throw new ErrorException("SWITCH_EXTRAS does not have an additive. eg api:export");
                    break;
            }
            break;
		Default:
            throw new ErrorException("Unknown SWITCH_SCREEN Set. gurgle...cough...dead... *checks pulse*");
			break;
	}
	#done setting up WiFiDB, whether it be the daemon or the web interface, or just plain failing in a spectacular fashion...
}
catch (Exception $e) {
	var_dump($e);
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
	$domain = ";domain=".parse_url($URL_PATH, PHP_URL_HOST);
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
	if($_SERVER['SCRIPT_NAME'] != "/wifidb/login.php")
	{
		$ultimate_path = parse_url($URL_PATH, PHP_URL_HOST).$_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING'];
	}else
	{
		$ultimate_path = $URL_PATH;
	}

	?>
<script type="text/javascript">
	function checkTimeZone()
	{
		var expiredays = 86400;
		var rightNow = new Date();
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
			var exdate=new Date();
			exdate.setDate(exdate.getDate()+expiredays);
			document.cookie="wifidb_client_dst=" +escape("0")+((expiredays==null) ? "" : "<?php echo $domain.$PATH.$ssl; ?>;expires=" +exdate.toUTCString());

			var exdate=new Date();
			exdate.setDate(exdate.getDate()+expiredays);
			document.cookie="wifidb_client_check=" +escape("1")+((expiredays==null) ? "" : "<?php echo $domain.$PATH.$ssl; ?>;expires=" +exdate.toUTCString());

			var exdate=new Date();
			exdate.setDate(exdate.getDate()+expiredays);
			document.cookie="wifidb_client_timezone=" +escape(hoursDiffStdTime)+((expiredays==null) ? "" : "<?php echo $domain.$PATH.$ssl; ?>;expires=" +exdate.toUTCString());
		}
		else
		{
			var exdate=new Date();
			exdate.setDate(exdate.getDate()+expiredays);
			document.cookie="wifidb_client_dst" + "=" +escape("1")+((expiredays==null) ? "" : "<?php echo $domain.$PATH.$ssl; ?>;expires=" +exdate.toUTCString());

			var exdate=new Date();
			exdate.setDate(exdate.getDate()+expiredays);
			document.cookie="wifidb_client_check" + "=" +escape("1")+((expiredays==null) ? "" : "<?php echo $domain.$PATH.$ssl; ?>;expires=" +exdate.toUTCString());

			var exdate=new Date();
			exdate.setDate(exdate.getDate()+expiredays);
			document.cookie="wifidb_client_timezone=" +escape(hoursDiffStdTime)+((expiredays==null) ? "" : "<?php echo $domain.$PATH.$ssl; ?>;expires=" +exdate.toUTCString());
		}
		location.href = '<?php echo $ssl_flag.'://'.$ultimate_path; ?>';
	}
	</script>
	<body onload = "checkTimeZone();"> </body>
	<?php
	exit();
}





if(!function_exists('WiFiDBexception_handler')) {
    function WiFiDBexception_handler($err)
    {
        $trace = array('Error' => strval($err->getCode()), 'Message' => str_replace("\n", "</br>\r\n", $err->getMessage()), 'Code' => strval($err->getCode()), 'File' => $err->getFile(), 'Line' => strval($err->getLine()));
        switch (strtolower(SWITCH_SCREEN)) {
            case "html":
                define('WWW_DIR', $_SERVER['DOCUMENT_ROOT'] . "/wifidb/");
                define('SMARTY_DIR', $_SERVER['DOCUMENT_ROOT'] . "/wifidb/smarty/");
                $smarty = new Smarty();
                $smarty->setTemplateDir(WWW_DIR . 'smarty/templates/wifidb/');
                $smarty->setCompileDir(WWW_DIR . 'smarty/templates_c/');
                $smarty->setCacheDir(WWW_DIR . 'smarty/cache/');
                $smarty->setConfigDir(WWW_DIR . '/smarty/configs/');
                $smarty->smarty->assign('wifidb_error_mesg', $trace);
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
