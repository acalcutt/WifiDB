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
		else if($this->service == "pgsql")
		{
			// connect_timeout mirrors the sqlsrv LoginTimeout below: bound how long
			// a connect can block when the server is unreachable instead of hanging
			// on the OS TCP timeout and piling up PHP-FPM workers.
			// client_encoding is hard-coded to UTF8 rather than taken from
			// $config['charset'] -- that key holds the MySQL name ('utf8mb4'),
			// which Postgres does not recognise.
			$dsn = $this->service.':host='.$this->host.';port='.$this->port.';dbname='.$this->database.";options='--client_encoding=UTF8';connect_timeout=15";
			$this->conn = new PDO($dsn, $config['db_user'], $config['db_pwd']);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
			// LoginTimeout: bound how long a connect can block when SQL Server is
			// unreachable, instead of hanging on the OS TCP timeout (tens of
			// seconds) and piling up PHP-FPM workers + Apache threads until the
			// box exhausts MaxRequestWorkers. 15s is short enough to keep pile-up
			// in check but long enough to survive a busy-but-alive server's slow
			// pre-login/TLS handshake -- 3s was too aggressive and produced
			// intermittent SQLSTATE 08001 "error during handshakes before login".
			$dsn = $this->service.':Server='.$this->host.';Database='.$this->database.';TrustServerCertificate=true;LoginTimeout=15';
			$this->conn = new PDO($dsn, $config['db_user'], $config['db_pwd']);
			$this->conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
	}

	/**
	 * Quote an identifier for the active driver.
	 *
	 * Only pgsql needs this. MySQL and SQL Server match column names
	 * case-insensitively, so "WHERE File_ID = ?" finds the File_ID column either
	 * way; Postgres folds the unquoted name to "file_id" and errors out.
	 * Returning the name untouched for the other two keeps their query text
	 * byte-identical to what is running today, so a single shared query string
	 * can serve all three drivers.
	 *
	 * Use it for one-off references in otherwise portable statements, and for
	 * caller-supplied identifiers such as a sort column. A query that differs
	 * from the MySQL/MSSQL form by more than a few identifiers should get its
	 * own pgsql branch instead -- that stays easier to read.
	 */
	function ident($name)
	{
		if($this->service == "pgsql")
		{
			return '"'.str_replace('"', '""', $name).'"';
		}
		return $name;
	}

	/** Back-compat alias for ident(). */
	function sortIdent($name)
	{
		return $this->ident($name);
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
		else if($this->service == "pgsql")
		{
			// 40P01 = deadlock_detected, 40001 = serialization_failure. Both are
			// transient and safe to retry. Postgres reports SQLSTATE as a string
			// ('40P01' is not numeric), so compare as strings rather than using the
			// numeric driver codes the mysql/sqlsrv branches key off.
			return (
				$e instanceof PDOException &&
				$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) == 'pgsql' &&
				($e->errorInfo[0] === '40P01' || $e->errorInfo[0] === '40001')
			);
		}
		return false;
	}
	
	public function isPDOException(PDO $pdo, $e): bool
	{
		if (isset($pdo) && $this->isDeadlock($pdo, $e)) 
		{
			//echo "Deadlock!\r\n";
			sleep(rand (1, 5));
			//echo "Retry!\r\n";
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
