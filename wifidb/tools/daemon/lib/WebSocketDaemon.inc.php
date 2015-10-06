<?php

/**
 * Created by PhpStorm.
 * User: ferph02
 * Date: 9/27/2015
 * Time: 4:17 PM
 */
class WebSocketDaemon extends WebSocketServer {
    //protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.
    public function __construct(&$dbcore, $addr, $port, $bufferLength = 2048)
    {
        parent::__construct($addr, $port, $bufferLength);
        $this->sql = $dbcore->sql;
        $this->dbcore = $dbcore;
    }

    protected function process ($user, $message)
    {
        #echo "---------------------------------------------------------------------------------------------------\r\n";
        #var_dump("Message: ".$message);

        switch($user->headers['get'])
        {
            case "/wifidb/api/Scheduling";
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
                        $return = "bad_message_sent";
                        break;
                }
                $type = "<Scheduling></Scheduling>";
                break;
            case "/wifidb/api/LiveAPs":
                $message_part = explode("|", $message);
                switch(strtolower($message_part[0]))
                {
                    case "list":
                        $return = $this->FetchLiveAPs((int)$message_part[1]);
                        break;

                    case "map":
                        $return = $this->GenerateMapData((int)$message_part[1]);
                        break;

                    default:
                        $return = "bad_message_sent";
                        break;

                }
                $type = "<LiveAPs></LiveAPs>";
                break;
            default:
                $return = "Unknown WebSocket Path: ".$user['headers']['get'];
                break;
        }
        echo ".";
        //creating object of SimpleXMLElement
        $xml = new SimpleXMLElement('<?xml version="1.0"?>'.$type);
        //function call to convert array to xml
        #var_dump($return);
        $this->array_to_xml($return, $xml);
        #echo $xml->asXML();
        #var_dump("emalloc: ". ((memory_get_usage()/1024)/1024) ."Mb" );
        #echo "------------------\r\n";
        #var_dump("Full Memory: ".((memory_get_usage(1)/1024)/1024) ."Mb" );
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

    public function FetchLiveAPs($limit = 0)
    {
        $sql = "SELECT `a`.`id`, `ssid`, `mac`, `auth`, `encry`, `radio`, `chan`, `FA`, `LA`, `lat`, `long`, `b`.`title`, `c`.`username`
                FROM `live_aps` AS a
                LEFT JOIN `live_users` AS `c` ON `a`.`session_id` = `c`.`session_id`
                LEFT JOIN `live_titles` AS `b` ON `b`.`id` = `c`.`title_id`
                ORDER BY `b`.`id`, `a`.`id` ASC";
        #var_dump($sql);
        $res = $this->sql->conn->query($sql);
        $this->sql->checkError($res, __LINE__, __FILE__);
        $fetch = $res->fetchAll(2);
        #var_dump($fetch);
        return array("LiveList"=>$fetch);
    }

    public function GenerateMapData($id)
    {

    }

    protected function FetchDaemonSchedule()
    {
        $result = $this->sql->conn->query("SELECT `nodename`, `daemon`, `interval`, `status`, `nextrun` FROM `schedule`;");
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
        $daemon_sql = "SELECT `nodename`, `pidfile`, `pid`, `pidtime`, `pidmem`, `pidcmd`, `date` FROM `daemon_pid_stats`";
        $result = $this->sql->conn->prepare($daemon_sql);
        $result->bindParam(1, $this->dbcore->node_name, PDO::PARAM_STR);
        $result->execute();
        $fetch = $result->fetch(2);
        if(empty($fetch))
        {
            $ret = array("notice"=>"no_daemon_stats_data");
        }else
        {
            $get_stats = $this->dbcore->getdaemonstats($fetch['pidfile']);
            $ret = array('nodename'=>$fetch['nodename'],
                'pidfile'=>$fetch['pidfile'],
                'pid'=>$fetch['pid'],
                'time'=>$get_stats['time'],
                'mem'=>$get_stats['mem'],
                'cmd'=>$get_stats['cmd'],
                'date'=>$fetch['date'],
                'color'=>$get_stats['color']);
            #var_dump($ret);
        }
        return array("daemon_stats"=>$ret);
    }

    protected function connected ($user) {
        // Do nothing: This is just an echo server, there's no need to track the user.
        // However, if we did care about the users, we would probably have a cookie to
        // parse at this step, would be looking them up in permanent storage, etc.
    }

    protected function closed ($user) {
        var_dump("pseudo close connection for :".$user->id);
        unset($user);
    }
}
