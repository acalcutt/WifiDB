<?php

/**
 * Description of wdbcli
 *
 * @author pferland
 */
class wdbcli extends dbcore
{
    function __construct($config, $daemon_config, &$SQL)
    {
        parent::__construct($config, $SQL);
        if(strtolower(SWITCH_SCREEN) == "cli")
        {
            $this->pid_file =   "";
            $this->node_name = $daemon_config['wifidb_nodename'];
            $this->log_path	= $daemon_config['daemon_log_folder'];
            $this->cli      =   1;
            if($daemon_config['colors_setting'] == 0 or PHP_OS == "WINNT")
            {
                $this->colors = array(
                    "LIGHTGRAY"	=> "",
                    "BLUE"	    => "",
                    "GREEN"	    => "",
                    "RED"	    => "",
                    "YELLOW"	=> ""
                );
            }else
            {
                $this->colors = array(
                    "LIGHTGRAY"	=> "\033[0;37m",
                    "BLUE"	    => "\033[0;34m",
                    "GREEN"	    => "\033[0;32m",
                    "RED"	    => "\033[0;31m",
                    "YELLOW"	=> "\033[1;33m"
                );
            }
        }
    }


    public function createPIDFile()
    {
        $this->pid_file;
    }

    /**
     * @param $argv
     * @return array
     */
    function parseArgs($argv)
    {
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
}