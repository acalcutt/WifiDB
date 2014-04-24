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

ou should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/
// Show all error's with strict santex
//***DEV USE ONLY*** TODO: remove dev stuff
#ini_set('display_errors', 1);//***DEV USE ONLY***
#error_reporting(E_ALL);//***DEV USE ONLY***
#ini_set("screen.enabled", TRUE);//***DEV USE ONLY***
//***DEV USE ONLY***

date_default_timezone_set('UTC'); //setting the time zone to GMT(Zulu) for internal keeping, displays will soon be customizable for the users time zone

set_exception_handler('exception_handler');


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
$dsn = $config['srvc'].':host='.$config['host'];
$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);

$conn = new PDO($dsn, $config['db_user'], $config['db_pwd'], $options);

$sql = "SELECT `size` FROM `wifi`.`settings` WHERE `table` = 'version'";
$res = $conn->query($sql);
$fetch = $res->fetch(2);

unset($res);
unset($conn);
if($fetch['size'] != '0.30 b1')
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
unset($fetch);
unset($gen_cwd);
unset($cwd);
unset($sql);

if(strtolower(SWITCH_SCREEN) != "cli")
{
    if(strtolower(SWITCH_EXTRAS) != "api")
    {
        if( (!@isset($_COOKIE['wifidb_client_check']) || !@$_COOKIE['wifidb_client_timezone']))
        {
            create_base_cookies($config['hosturl'].$config['root'].'/');
            exit();
        }
    }
}


/*
 * Class autoloader
 */
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
        include_once $GLOBALS['config']['wifidb_install'].'smarty/sysplugins/'.strtolower($class).'.php';
        return 1;
    }else
    {
        throw new errorexception("Could not load class `{$class}`");
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
                    $dbcore = new export($config, $daemon_config);
                break;
                ####
                case "import":
                    $dbcore = new import($config, $daemon_config, new stdClass() );
                ####
                case "daemon":
                    $dbcore = new daemon($config, $daemon_config);
                    $dbcore->convert = new convert($config, $daemon_config, $dbcore);
                    $dbcore->export = new export($config, $daemon_config);
                    $dbcore->import = new import($config, $daemon_config, $dbcore->export, $dbcore->convert);
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
                break;

                case "export":
                    $dbcore = new frontend($config);
                    __autoload('convert');
                    $dbcore->convert = new convert($config, $daemon_config, $dbcore);
                    __autoload('export');
                    $dbcore->export = new export($config, $dbcore->convert);
                break;

                case "graph":
                    $dbcore = new frontend($config);
                    __autoload('graphs');
                    $dbcore->graphs = new graphs($dbcore->PATH, $dbcore->URL_PATH);
                break;

                default:
                    $dbcore = new frontend($config);
                break;
            }
            $dbcore->cli = 0;
            break;
        ################
        Default:
            die("Unknown Switch Set.");
            break;
    }
    #done setting up WiFiDB, weather it be the daemon or the web interface, or just plain failing.
}
catch (Exception $e) {
    throw new ErrorException($e);
}

function exception_handler($err)
{
    $trace = array( 'Error' =>strval($err->getCode()), 'Message'=>str_replace("\n", "</br>\r\n", $err->getMessage()),'Code'=>strval($err->getCode()), 'File'=>$err->getFile(), 'Line'=>strval($err->getLine()));
    switch(strtolower(SWITCH_SCREEN))
    {
        case "html":
            define('WWW_DIR', $_SERVER['DOCUMENT_ROOT']."/wifidb/");
            define('SMARTY_DIR', $_SERVER['DOCUMENT_ROOT']."/wifidb/smarty/");
            $smarty = new Smarty();
            $smarty->setTemplateDir( WWW_DIR.'smarty/templates/wifidb/' );
            $smarty->setCompileDir( WWW_DIR.'smarty/templates_c/' );
            $smarty->setCacheDir( WWW_DIR.'smarty/cache/' );
            $smarty->setConfigDir( WWW_DIR.'/smarty/configs/');
            $smarty->smarty->assign('wifidb_error_mesg', $trace);
            $smarty->display("error.tpl");
            break;

        case "cli":
            var_dump($err);
            break;

        default:
            echo "Unknown screen switch, here is a raw dump of the error...\r\n".var_export($trace, 1);
            break;
    }
}

function CLIErrorHandlingFunction($message, $type=E_USER_NOTICE) {
    $backtrace = debug_backtrace();
    foreach($backtrace as $entry) {
        if ($entry['function'] == __FUNCTION__) {
            trigger_error($entry['file'] . '#' . $entry['line'] . ' ' . $message, $type);
            return true;
        }
    }
    return false;
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
    $PATH       = ";path=".$root;
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
?>