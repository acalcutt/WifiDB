<?php
class SQL
{
	function __construct($config)
	{
		$this->host			  = $config['host'];
		$this->port			  = $config['port'];
		$this->service		   = $config['srvc'];
		$this->driver		   = $config['driver'];
		$this->database       = $config['db'];
		$this->charset       = $config['charset'];
		$this->collate       = $config['collate'];
		
		if($this->service == "mysql")
		{
			$dsn = $this->service.':dbname='.$this->database.';host='.$this->host.';charset='.$this->charset;
			$options = array(
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$this->charset.' COLLATE '.$this->collate,
				PDO::ATTR_PERSISTENT => TRUE,
			);
			$this->conn = new PDO($dsn, $config['db_user'], $config['db_pwd'], $options);
		}
		else if($this->service == "sqlsrv" && $this->driver == "dblib")
		{
			$dsn = $this->driver.":host=".$this->host.":".$this->port.";dbname=".$this->database;
			$this->conn = new PDO($dsn, $config['db_user'], $config['db_pwd']);
			$this->conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		else if($this->service == "sqlsrv")
		{
			$dsn = $this->service.':Server='.$this->host.';Database='.$this->database;
			$this->conn = new PDO($dsn, $config['db_user'], $config['db_pwd']);
			$this->conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
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
	
	function isDeadlock(PDO $pdo, $e): bool
	{
		if($this->service == "mysql")
		{
			return (
				$e instanceof PDOException &&
				$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql' &&
				$e->errorInfo[0] == 40001 &&
				$e->errorInfo[1] == 1213
			);
		}
		else if($this->service == "sqlsrv")
		{
			return (
				$e instanceof PDOException &&
				$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlsrv' &&
				$e->errorInfo[0] == 40001 &&
				$e->errorInfo[1] == 1205
			);
		}
	}
	
	public function isPDOException(PDO $pdo, $e): bool
	{
		if (isset($pdo) && $this->isDeadlock($pdo, $e)) 
		{
			echo "Deadlock!\r\n";
			sleep(rand (1, 5));
			echo "Retry!\r\n";
			$retry = true;
		}
		else 
		{
			$retry = false;
			if (isset($pdo) && $pdo->inTransaction()) {
				$pdo->rollBack();
			}
			$errorMsg = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)." ".$e->errorInfo[0]." ".$e->errorInfo[1]."\r\n".$e->getMessage();
			echo "$errorMsg\r\n";die;
		}
		return ($retry);
	}
}
