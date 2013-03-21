<?php
class SQL
{
    function __construct($config)
    {
        $this->host              = $config['host'];
        $this->service           = $config['srvc'];
        $dsn                     = $this->service.':host='.$this->host;
        if($this->service === "mysql")
        {
            $options = array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                PDO::ATTR_PERSISTENT => TRUE,
            );
        }
        else
        {
            $options = array(
                PDO::ATTR_PERSISTENT => TRUE,
            );
        }
        $this->conn              = new PDO($dsn, $config['db_user'], $config['db_pwd'], $options);
        $this->users_t           = $config['users_t'];
        $this->settings_tb       = $config['settings_tb'];
        $this->pointers_table	 = $config['pointers_table'];
        $this->signals_table	 = $config['signals_table'];
        $this->gps_table	 = $config['gps_table'];
        $this->user_logins_table = $config['user_logins_table'];
        $this->DB_stats		 = $config['DB_stats'];
        $this->validate_table	 = $config['validate_table'];
        $this->share_cache       = $config['share_cache'];
        $this->files		 = $config['files'];
        $this->files_tmp	 = $config['files_tmp'];
        $this->annunc		 = $config['annunc'];
        $this->annunc_comm       = $config['annunc_comm'];
        $this->gps_ext		 = $config['gps_ext'];
        $this->sep               = $config['sep'];
        $this->conn->query("SET NAMES 'utf8'");
    }
    
    function checkError()
    {
        $err = $this->conn->errorCode();
        if($err !== "00000")
        {
            dbcore::verbosed("There was an error running the SQL");
            dbcore::logd("There was an error running the SQL");
        }
    }
}
?>
