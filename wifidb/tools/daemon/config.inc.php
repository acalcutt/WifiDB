<?php
#last edited -> 2011-Nov-22
global $daemon_config;
$daemon_config = array(

    'dim' => DIRECTORY_SEPARATOR,

    //Defaults for unclaimed imports
    'default_user'	=> 'WiFiDB',
    'default_title'	=> 'Recovery',
    'default_notes'	=> 'WiFiDB Recovery run by an administrator.',

    //path to the folder that wifidb is installed in default is /var/www/wifidb/ , because I use Debian. fuck windows
    'wifidb_install'        =>	'/var/www/wifidb/',
    'console_line_limit'    =>	3000,
    'console_trim_log'      =>	1,
    'pid_file_loc'          =>	'/var/run/',
    'daemon_log_folder'     =>	'/opt/wifidb/tools/log/',

    //IF you are running windows you need to define the install path to the PHP binary, this is so the daemon can restart itself every once and a while.
    'php_install'	=>	'C:\\program files\\php5\\',

    //In seconds: 1800 = 30 min interval
    //# Sleep for the Import/Export Daemon
    'time_interval_to_check'	=>	1800,
    //# Database Statistics Daemon sleep, really should be at once a day (86400 seconds) if you have a very large database.
    'DBSTATS_time_interval_to_check' => 86400,

    //The level that you want the log file to write, off (0), Errors only (1), Detailed Errors [when available] (2). That is all for now.
    'log_level'	=>	0,

    //0, no out put STUF; 1, let me see the world.
    'verbose'	=>	1,

    //if you want the CLI output to be color coded 1 => ON, 0 => OFF
    //if you ware running windows, this is disabled for you, so even if you turn it on, its not going to work :-p
    'colors_setting'	=>	1,

    //Default colors for the CLI
    //Allowed colors:
            //LIGHTGRAY, BLUE, GREEN, RED, YELLOW
    'BAD_CLI_COLOR'	=>	'RED',
    'GOOD_CLI_COLOR'	=>	'GREEN',
    'OTHER_CLI_COLOR'	=>	'YELLOW',

    //Debug functions turned on, may also include dropping tables and re-createing them
    //so only turn on if you really know what you are doing
    'debug' => 0
);
?>