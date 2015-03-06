<?php

/**
 * Description of wdbcli
 *
 * @author pferland
 */
class wdbcli extends dbcore
{
    function __construct($config, $daemon_config)
    {
        parent::__construct($config);
        $this->pid_file =   "";
        $this->cli      =   1;
        if($daemon_config['colors_setting'] == 0 or PHP_OS == "WINNT")
        {
            $this->colors = array(
                            "LIGHTGRAY"	=> "",
                            "BLUE"	=> "",
                            "GREEN"	=> "",
                            "RED"	=> "",
                            "YELLOW"	=> ""
                            );
        }else
        {
            $this->colors = array(
                            "LIGHTGRAY"	=> "\033[0;37m",
                            "BLUE"	=> "\033[0;34m",
                            "GREEN"	=> "\033[0;32m",
                            "RED"	=> "\033[0;31m",
                            "YELLOW"	=> "\033[1;33m"
                            );
        }
    }


}
