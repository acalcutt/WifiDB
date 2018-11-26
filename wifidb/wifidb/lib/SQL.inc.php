<?php
class SQL
{
	function __construct($config)
	{
		$this->host			  = $config['host'];
		$this->service		   = $config['srvc'];
		$this->database       = $config['db'];
		$this->charset       = $config['charset'];
		$this->collate       = $config['collate'];
		
		$dsn = $this->service.':dbname='.$this->database.';host='.$this->host.';charset='.$this->charset;
		$options = array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$this->charset.' COLLATE '.$this->collate,
			PDO::ATTR_PERSISTENT => TRUE,
		);
		$this->conn = new PDO($dsn, $config['db_user'], $config['db_pwd'], $options);
	}

	function checkError($line=0, $file="")
	{
		$err = $this->conn->errorCode();
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
