<?php
class SQL
{
	function __construct($config)
	{
		$this->host			  = $config['host'];
		$this->service		   = $config['srvc'];
        $this->driver           = $config['driver'];
        $this->database         = $config['db'];
        /** @var PDO */
        switch($this->driver)
        {
            case "PDO":
                if($this->service === "mysql")
                {
                    $options = array(
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                        PDO::ATTR_PERSISTENT => FALSE,
                    );
                }
                else
                {
                    $options = array(
                        PDO::ATTR_PERSISTENT => FALSE,
                    );
                }
                $dsn    = $this->service.':host='.$this->host;
                $this->conn = new PDO($dsn, $config['db_user'], $config['db_pwd'], $options);
                break;

            case "mysqli":
                $this->conn = new mysqli($this->host, $config['db_user'], $config['db_pwd'], $this->database);
                $this->conn->query("SET NAMES 'utf8'");

                break;

            default:

                break;
        }
	}

	function checkError($line=0, $file="")
	{
		$err = $this->conn->errorCode();
        #var_dump($err);
		if($err === "00000")
		{
			return 0;
		}else
		{
			throw new ErrorException("There was an error running the SQL statement: ".var_export($this->conn->errorInfo() ,1)."\r\nLine: $line\r\nFile: $file");
			return 1;
		}
	}
}
