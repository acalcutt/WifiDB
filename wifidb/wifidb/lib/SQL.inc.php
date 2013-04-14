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
        $this->conn->query("SET NAMES 'utf8'");
    }
    
    function checkError()
    {
        $err = $this->conn->errorCode();
        #var_dump($err);
        if($err === "00000")
        {
            #dbcore::verbosed("There was no error running the SQL");
            #dbcore::logd("There was no error running the SQL");
            return 0;
        }else
        {
            @dbcore::verbosed("There was an error running the SQL");
            @dbcore::logd("There was an error running the SQL");
            return $this->conn->errorInfo();
        }
        
    }
}
?>
