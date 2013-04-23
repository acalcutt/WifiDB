<?php
class api_server extends wdbcli
{
    function __construct($argv)
    {
        global $screen_output;
        $screen_output = "CLI";

        $this->ver = '2.0.0';
        $this->author = 'Phil Ferland';
        $this->last = '2011-03-21';
        $args = parseArgs($argv);

        if(@$args['wconfig']!=""){$cli_w_config = $args['wconfig'];}
	    else{$cli_w_config = "config.inc.xml";}

        if(@$args['config']!=""){$cli_config = $args['config'];}
	    else{$cli_config = "config.inc.xml";}

        if(!$config_xml = @file_get_contents($cli_config))
        {
            die("Failed to read the config file: $cli_config\r\n".$this->help());
        }else
        {
            echo "Loaded Config File... ".$cli_config."\r\n";
        }
        $xml = xml2ary($config_xml);
        $this->settings['wifidb_install'] = $xml['config']['_c']['wifidb_install']['_v'];
        $this->settings['pid_file'] = $xml['config']['_c']['pid_file_loc']['_v']."api_d.pid";
        $this->settings['log_file'] = $xml['config']['_c']['daemon_log_folder']['_v']."api_d.log";
	$this->settings['max_clients'] = $xml['config']['_c']['max_clients']['_v'];
	$this->client = array();

	if(@$argv['port'])
	{
	    $this->settings['port'] = $args['port'];
	}else
	{
	    $this->settings['port'] = $xml['config']['_c']['api_port']['_v'];
	}
	if(@$args['ipaddress'])
	{
	    $this->settings['address'] = $args['ipaddress'];
	}else
	{
	    $this->settings['address'] = getIPs(0);
	}

        #var_dump($this->settings['address']);

        if(!$config_w_xml = @file_get_contents($this->settings['wifidb_install'].'lib/'.$cli_w_config))
        {
            die("Failed to read the web config file: ".$this->settings['wifidb_install']."lib/$cli_w_config\r\n".$this->help());
        }else
        {
            echo "Loaded Web Config File... ".$this->settings['wifidb_install'].'lib/'.$cli_w_config."\r\n";
        }

        $xml1 = xml2ary($config_w_xml);

        #die(var_dump($xml1));

        $this->settings['db']           =   $xml1['config']['_c']['db']['_v'];
        $this->settings['db_st']	=   $xml1['config']['_c']['db_st']['_v'];
        $this->settings['wtable']	=   $xml1['config']['_c']['wtable']['_v'];
        $this->settings['users_t']	=   $xml1['config']['_c']['users_t']['_v'];
        $this->settings['gps_ext']	=   $xml1['config']['_c']['gps_ext']['_v'];
        $this->settings['files']	=   $xml1['config']['_c']['files']['_v'];
        $this->settings['files_tmp']    =   $xml1['config']['_c']['files_tmp']['_v'];
        $this->settings['login_t']      =   $xml1['config']['_c']['user_logins_table']['_v'];
        $this->settings['seed']         =   $xml1['config']['_c']['login_seed']['_v'];
        $this->settings['config_fails'] =   $xml1['config']['_c']['config_fails']['_v'];

        $this->settings['sqlhost']      =   $xml1['config']['_c']['host']['_v'];
        $this->settings['sqluser']      =   $xml1['config']['_c']['db_user']['_v'];
        $this->settings['sqlpwd']       =   $xml1['config']['_c']['db_pwd']['_v'];
        $this->settings['sqldb']        =   $xml1['config']['_c']['db']['_v'];
        date_default_timezone_set($xml1['config']['_c']['timezn']['_v']);

        $db_lib = $this->settings['wifidb_install']."lib/database.inc.php";
        require $db_lib;
	echo "Loaded Database Library... ".$db_lib."\r\n\r\n";

	$this->sock = socket_create(AF_INET, SOCK_STREAM, 0);
	// Bind the socket to an address/port
	$this->bind();
	// Start listening for connections
	if(socket_listen($this->sock))
	{
	    echo "Listening on: ".$this->settings['address'].":".$this->settings['port']."\r\n";
	}
	$this->conn = new mysqli($this->settings['sqlhost'], $this->settings['sqluser'], $this->settings['sqlpwd'], $this->settings['sqldb']);
    }

    function bind()
    {
	echo "Trying to bind to socket.";
	while(!@socket_bind($this->sock, $this->settings['address'], $this->settings['port']))
	{
	    echo ".";
	    sleep(1);
	}
	echo "\r\n";
    }

    function main()
    {
        while (true)
        {
            // Setup clients listen socket for reading
            #
            $read[0] = $this->sock;
            for ($i = 0; $i < $this->settings['max_clients']; $i++)
            {
            if (@$this->client[$i]->sock != null)
            {
                        $read[$i + 1] = $this->client[$i]->sock;
                        #echo ".";
                    }
            }
            // Set up a blocking call to socket_select()
            $ready = @socket_select($read, $w, $e, 0);
            /* if a new connection is being made, add it to the client array */
            if (in_array($this->sock, $read))
            {
            for ($i = 0; $i < $this->settings['max_clients']; $i++)
            {
                if (@is_null($this->client[$i]->sock))
                        {
                $this->client[$i] = new api_client();
                            $this->client[$i]->sock = socket_accept($this->sock);

                            echo time().": Accepted socket.\r\n";
                break;
                }elseif ($i == $this->settings['max_clients'] - 1)
                {
                echo time().": Too many clients: $this->settings['max_clients']\r\n";
                }
            }
            if ($ready > 0)
            #   echo $ready."\r\n";
                continue;
            } // end if in_array
            // If a client is trying to write - handle it now
            for ($i = 0; $i < $this->settings['max_clients']; $i++) // for each client
            {
                if(in_array(@$this->client[$i]->sock , $read))
                {
                    $this->client[$i]->input = trim(@socket_read($this->client[$i]->sock , 1024));
                    if(@$this->client[$i]->input == NULL){continue;}
                    var_dump($this->client[$i]->input);
                    #var_dump($this->client[$i]['input']);
                    if(@$this->client[$i]->input == null) {
                        // Zero length string meaning disconnected
                        unset($this->client[$i]);
                    }
                    if (@$this->client->input == 'EXIT') {
                        // requested disconnect
                        socket_close($this->client[$i]->sock);
                        echo time().": Closed connection to: ".$this->client[$i]->sock."\r\n";
                        unset($this->client[$i]);
                    }
                    $this->client[$i]->input_exp = @explode("|", $this->client[$i]->input);
                    switch (@strtoupper($this->client[$i]->input_exp[0]))
                    {
                        case "IP":
                            // strip white spaces and write back to user
                            #echo $this->client[$i]['input_exp'][1]."\r\nGet me your IP...";
                            socket_write($this->client[$i]->sock, "GET_U_IP|", strlen("GET_U_IP|"));
                        break;

                        case "IPADDR":
                            $this->client[$i]->ip_addr = $this->client[$i]->input_exp[1];
                            echo time().": Logged IP: ".$this->client[$i]->ip_addr."\r\n";
                            $messg = "OK|Logged IP...";
                            socket_write($this->client[$i]->sock, $messg, strlen($messg));
                        break;

                        case "LOCATE":
                            $locate = $this->locate($this->client[$i]->input_exp[1]);
                            switch($locate[0])
                            {
                                case 1:
                                    $messg = "LOCATE|".$locate[1];
                                    socket_write($this->client[$i]->sock, $messg, strlen($messg));
                                    echo time().": Locate AP GPS command successful (".$this->client[$i]->ip_addr.").\r\n";
                                break;
                                case 0:
                                    $messg = "LOCATE|Empty";
                                    socket_write($this->client[$i]->sock, $messg, strlen($messg));
                                    echo time().": Locate AP GPS command failed, no data in database (".$this->client[$i]->ip_addr.").\r\n";
                                break;
                                case -1:
                                    $messg = "LOCATE|Error";
                                    socket_write($this->client[$i]->sock, $messg, strlen($messg));
                                    echo time().": Locate AP GPS command failed (".$this->client[$i]->ip_addr.").\r\n".$messg;
                                break;
                            }
                        break;

                        case "LOGIN":
                            $this->client[$i]->wdb_user = mysql_real_escape_string($this->client[$i]->input_exp[1]);
                            var_dump($this->client[$i]->input_exp[1]);
                            echo time().": User attemting login ".$this->client[$i]->wdb_user."@(".$this->client[$i]->ip_addr.")\r\n";
                            #ask for password
                            $messg = "PWD|";
                            socket_write($this->client[$i]->sock, $messg, strlen($messg));
                            echo time().": Asked ".$this->client[$i]->input_exp[1]." for their password.\r\n";
                        break;
                        case "PWD":
                            echo time().": ".$this->client[$i]->wdb_user." sent password.\r\n";
                            $this->client[$i]->sql = "select
                                                     `id`,`username`,`API_KEY`,`login_fails`,`locked`,`validated`
                                                     from `".$this->settings['db']."`.`".$this->settings['login_t']."`
                                                     WHERE `username` LIKE '".$this->client[$i]->wdb_user."'";
                            $this->client[$i]->result = $this->conn->query($this->client[$i]->sql) or die($this->conn->error);
                            $this->client[$i]->pwd_return = $this->client[$i]->result->fetch_array(1);
                            if($this->client[$i]->pwd_return['login_fails'] == $this->settings['config_fails'] or $this->client[$i]->pwd_return['locked'] == 1)
                            {
                                echo time().": ".$this->client[$i]->wdb_user." is locked\r\n";
                                $messg = "EXIT|Your account has been locked for too many bad login attpemts...";
                                socket_write($this->client[$i]->sock, $messg, strlen($messg));
                                socket_close($this->client[$i]->sock);
                                unset($this->client[$i]);
                                break;
                            }
                            if($this->client[$i]->pwd_return['validated'] == 1)
                            {
                                echo time().": ".$this->client[$i]->wdb_user." has not validated yet.\r\n";
                                $messg = "EXIT|User is not validated yet...";
                                socket_write($this->client[$i]->sock, $messg, strlen($messg));
                                socket_close($this->client[$i]->sock);
                                unset($this->client[$i]);
                                break;
                            }

                            $this->client[$i]->id = $this->client[$i]->pwd_return['id'];
                            $db_pass = sha1($this->client[$i]->pwd_return['API_KEY']);
                            $this->client[$i]->fails = $this->client[$i]->pwd_return['login_fails'];
                            $username_db = $this->client[$i]->pwd_return['username'];

                           #$pass_seed = md5($input[1].$server_settings['seed']);

                            if($db_pass === $this->client[$i]->input_exp[1])
                            {
                                $this->client[$i]->sql = "SELECT `last_active` FROM `".$this->settings['db']."`.`".$this->settings['login_t']."` WHERE `id` = '".$this->client[$i]->id."' LIMIT 1";
                                $this->result = $this->conn->query($this->client[$i]->sql);
                                $this->client[$i]->last_return = $this->result->fetch_array(1);
                                $this->client[$i]->lastlogin = $this->client[$i]->last_return['last_active'];
                                $date = date("Y-m-d H:i:s");
                                if($this->client[$i]->lastlogin == "0000-00-00 00:00:00")
                                {
                                    $this->client[$i]->lastlogin = $date;
                                }
                                $this->client[$i]->sql = "UPDATE `".$this->settings['db']."`.`".$this->settings['login_t']."`
                                                          SET `login_fails` = '0',
                                                              `last_active` = '".$this->client[$i]->lastlogin."',
                                                              `last_login` = '$date'
                                                               WHERE `id` = '".$this->client[$i]->id."' LIMIT 1";
                                echo $this->client[$i]->sql."\r\n";
                                if($this->conn->query($this->client[$i]->sql))
                                {
                                    echo time().": ".$this->client[$i]->wdb_user." logged in successfully.\r\n";
                                    $session = sha1($db_pass.rand(0000,9999));
                                    $messg = "LOK|".$session;
                                    $this->client[$i]->keys = $session;
                                    socket_write($this->client[$i]->sock, $messg, strlen($messg));
                                }else
                                {
                                    echo time().": Failed to update ".$this->client[$i]->wdb_user." user row.\r\n";
                                }
                            }else
                            {
                                if($username_db != '')
                                {
                                    $this->client[$i]->fails++;
                                    $this->client[$i]->to_go = $this->settings['config_fails'] - $this->client[$i]->fails;
                    #               echo $fails.' - '.$GLOBALS['config_fails'];
                                    if($this->client[$i]->fails >= $this->settings['config_fails'])
                                    {
                                        $this->client[$i]->sql = "UPDATE `".$this->settings['db']."`.`".$this->settings['login_t']."` SET `locked` = '1' WHERE `id` = '".$this->client[$i]->id."' LIMIT 1";
                                        $this->conn->query($this->client[$i]->sql);
                                        echo time().": ".$this->client[$i]->wdb_user." has been locked.\r\n";
                                        $messg = "EXIT|Your account has been locked for too many bad login attpemts...";
                                        socket_write($this->client[$i]->sock, $messg, strlen($messg));
                                        socket_close($this->client[$i]->sock);
                                        unset($this->client[$i]);
                                    }else
                                    {
                                        $this->client[$i]->sql = "UPDATE `".$this->settings['db']."`.`".$this->settings['login_t']."` SET `login_fails` = '".$this->client[$i]->fails."' WHERE `id` = '".$this->client[$i]->id."' LIMIT 1";
                                        $this->conn->query($this->client[$i]->sql);
                                        echo time().": ".$this->client[$i]->wdb_user." failed to login, To Go: ".$this->client[$i]->to_go."\r\n";
                                        $messg = "FAIL|".$this->client[$i]->to_go;
                                        socket_write($this->client[$i]->sock, $messg, strlen($messg));
                                    }
                                }else
                                {
                                    echo time().": Username was empty.. :-/\r\n";
                                    $messg = "BADU|The Username or password was bad, try again...";
                                    socket_write($this->client[$i]->sock, $messg, strlen($messg));
                                }
                            }
                        break;
                    }
                }
            }
            usleep(1);
        }
    }

    function help()
    {
        echo "

	WIFIDB API SERVER, Copyright (C) $this->last $this->author
WIFIDB API SERVER comes with ABSOLUTELY NO WARRANTY.  This is free software,
and you are welcome to redistribute it under certain conditions. For more details
Visit the FSF website at http://www.gnu.org/licenses/gpl-2.0.html

Version: $this->ver
Last Edit: $this->last
Author: $this->author

Commands:
    -c,--config	    Location of the config file.
    -h,--help	    This Help message.
";
    }
}

###################################
function getIPs($withV6 = true)
{
    if(PHP_OS == "WINNT")
    {
        $ipconfig = `ipconfig`;
        preg_match_all('/IP Address. . . . . . . . . . . . : ?([^ ]+)/', $ipconfig, $ips);
        if(!$withV6)
        {return trim($ips[1][0]);}
        else
        {return trim($ips[1][1]);}
    }else
    {
        preg_match_all('/inet'.($withV6 ? '6?' : '').' addr: ?([^ ]+)/', `ifconfig`, $ips);
	return trim($ips[1][count($ips[1])-1]);
    }
}
#-------------------------------------------------------------------------------------#
#----------------------------------  Parse Arg values  -------------------------------#
#-------------------------------------------------------------------------------------#
function parseArgs($argv){
    array_shift($argv);
    $out = array();
    foreach ($argv as $arg)
    {
        if (substr($arg,0,2) == '--'){
            $eqPos = strpos($arg,'=');
            if ($eqPos === false){
                $key = substr($arg,2);
                $out[$key] = isset($out[$key]) ? $out[$key] : true;
            } else {
                $key = substr($arg,2,$eqPos-2);
                $out[$key] = substr($arg,$eqPos+1);
            }
        } else if (substr($arg,0,1) == '-'){
            if (substr($arg,2,1) == '='){
                $key = substr($arg,1,1);
                $out[$key] = substr($arg,3);
            } else {
                $chars = str_split(substr($arg,1));
                foreach ($chars as $char){
                    $key = $char;
                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
                }
            }
        } else {
            $out[] = $arg;
        }
    }
    return $out;
}
