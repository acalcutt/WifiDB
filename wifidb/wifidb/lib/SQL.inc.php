<?php
class SQL
{
    public $conn;
	function __construct($config)
	{
		$this->host			  = $config['host'];
		$this->service		  = $config['srvc'];
        $this->driver         = $config['driver'];
        $this->database       = $config['db'];
        switch($this->driver)
        {
            case "PDO":
                if($this->service === "mysql")
                {
                    $options = array(
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                        PDO::ATTR_PERSISTENT => FALSE,
                        PDO::ERRMODE_EXCEPTION => TRUE
                    );
                }
                else
                {
                    $options = array(
                        PDO::ATTR_PERSISTENT => FALSE,
                        PDO::ERRMODE_EXCEPTION => TRUE
                    );
                }
                $dsn    = $this->service.':host='.$this->host;
                /** @var PDO */
                $this->conn = new PDO($dsn, $config['db_user'], $config['db_pwd'], $options);
                $this->conn->query("USE `$this->database`");
                break;

            case "mysqli":
                /** @var mysqli */
                $this->conn = new mysqli($this->host, $config['db_user'], $config['db_pwd'], $this->database);
                $this->conn->query("SET NAMES 'utf8'");
                $this->conn->query("USE `$this->database`");
                break;

            default:
                    throw new ErrorException("SQL Driver not selected.");
                break;
        }
        unset($config);
	}

	function checkError($ExecuteResults, $line, $file)
	{
        if($ExecuteResults === NULL)
        {
            throw new ErrorException("checkError called without setting ExecuteResults variable.");
        }
		if($ExecuteResults === FALSE)
		{
            throw new ErrorException("There was an error running the SQL statement: ".var_export($this->conn->errorInfo() ,1)."\r\nLine: $line\r\nFile: $file");
            return 1;
		}else
		{
            return 0;
		}
	}
}
