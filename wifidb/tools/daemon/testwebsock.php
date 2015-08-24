#!/usr/bin/php
<?php
/*
importd.php, WiFiDB Import Daemon
Copyright (C) 2015 Andrew Calcutt, Phil Ferland.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
ini_set('display_errors', 1);//***DEV USE ONLY***
#ini_set("screen.enabled", TRUE);//***DEV USE ONLY***
error_reporting(E_ALL);# || E_STRICT);//***DEV USE ONLY***
#error_reporting(E_STRICT);# || E_STRICT);//***DEV USE ONLY***
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "API");

require_once('./lib/websockets.php');

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}

require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->lastedit		=	"2015-08-18";
$dbcore->daemon_name	=	"WebSocket";
$dbcore->createPIDFile();

$arguments = $dbcore->parseArgs($argv);

class echoServer extends WebSocketServer {
  //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.
  public function __construct(&$dbcore, $addr, $port, $bufferLength = 2048)
  {
      parent::__construct($addr, $port, $bufferLength);
      $this->sql = $dbcore->sql;
      $this->dbcore = $dbcore;
  }

  protected function process ($user, $message)
  {
      var_dump($message);
      switch(strtolower($message))
      {
          case "import_waiting":
              $return = $this->FetchImportWaitingData();
              break;
          case "import_active":
              $return = $this->FetchImportActiveData();
              break;
          case "daemon_stats":
              $return = $this->FetchDaemonStats();
              break;
          case "daemon_schedule":
              $return = $this->FetchDaemonSchedule();
              break;
          default:
              $return = "bad_switch_selected";
              break;
      }
      #echo "---------------------------------------------------------------------------------------------------\r\n";
//creating object of SimpleXMLElement
      $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><Scheduling></Scheduling>");
//function call to convert array to xml
      #var_dump($return);
      $this->array_to_xml($return, $xml);
      #echo $xml->asXML();
      #echo "---------------------------------------------------------------------------------------------------\r\n";
      $this->send($user, $xml->asXML());
  }

    function array_to_xml($array, &$xml_user_info) {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)){
                    $subnode = $xml_user_info->addChild("$key");
                    $this->array_to_xml($value, $subnode);
                }else{
                    $subnode = $xml_user_info->addChild("item$key");
                    $this->array_to_xml($value, $subnode);
                }
            }else {
                $xml_user_info->addChild("$key",htmlspecialchars("$value"));
            }
        }
    }


    protected function FetchDaemonSchedule()
    {
        $result = $this->sql->conn->query("SELECT `schedule`.`nodename`, `schedule`.`daemon`, `interval`, `status`, `nextrun` FROM `wifi`.`schedule`;");
        $fetch_waiting = $result->fetchAll(2);
        if(empty($fetch_waiting))
        {
            $fetch_waiting = array("notice"=>"no_daemon_schedule_data");
        }
        return array("daemon_schedule"=>$fetch_waiting);
    }

    protected function FetchImportWaitingData($limit = 10)
    {
        $result = $this->sql->conn->query("SELECT `id`, `file`, `user`, `title`, `size`, `date`, `hash` FROM wifi.files_tmp LIMIT $limit;");
        $fetch_waiting = $result->fetchAll(2);
        if(empty($fetch_waiting))
        {
            $fetch_waiting = array("notice"=>"no_importing_data");
        }
        return array("import_waiting"=>$fetch_waiting);
    }

    protected function FetchImportActiveData()
    {
        $result = $this->sql->conn->query("SELECT `id`,  `tmp_id`, `file`, `user`, `title`, `size`, `date`, `hash`, `tot`, `ap` FROM wifi.files_importing;");
        $fetch_active = $result->fetchAll(2);
        if(empty($fetch_active))
        {
            $fetch_active = array("notice"=>"no_importing_data");
        }

        return array("import_active"=>$fetch_active);
    }

    protected function FetchDaemonStats()
    {
        $daemon_sql = "SELECT `nodename`, `pidfile`, `pid`, `pidtime`, `pidmem`, `pidcmd`, `date` FROM `wifi`.`daemon_pid_stats`";
        $result = $this->sql->conn->prepare($daemon_sql);
        $result->bindParam(1, $this->dbcore->node_name, PDO::PARAM_STR);
        $result->execute();
        $fetch = $result->fetch(2);
        if(empty($fetch))
        {
            $fetch = array("notice"=>"no_daemon_stats_data");
        }
        return array("daemon_stats"=>$fetch);
    }

    protected function connected ($user) {
        // Do nothing: This is just an echo server, there's no need to track the user.
        // However, if we did care about the users, we would probably have a cookie to
        // parse at this step, would be looking them up in permanent storage, etc.
    }

    protected function closed ($user) {
        // Do nothing: This is where cleanup would go, in case the user had any sort of
        // open files or other objects associated with them.  This runs after the socket
        // has been closed, so there is no need to clean up the socket itself here.
}
}

$echo = new echoServer($dbcore, "172.16.1.77","9000");

try {
  $echo->run();
}
catch (Exception $e) {
  $echo->stdout($e->getMessage());
}
