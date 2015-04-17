<?php

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "import");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require "lib/MainPool.inc.php";
require $daemon_config['wifidb_install']."lib/config.inc.php";
require_once $daemon_config['wifidb_install']."lib/SQL.inc.php";

$sql = new SQL($config);

/* stage and data reference arrays */
$one = [];
$two = [];
$data = [];

$pool = new MainPool(8);
$pool->start();

/* construct stage one */
echo "Spawning ".$daemon_config['NumberOfThreads']." Threads.\nThere is a 3 second wait between Thread starts.\n";
while (count($one) < 2) { #$daemon_config['NumberOfThreads']) {
    $staging = new StagingData();
    /* maintain reference counts by storing return value in normal array in local scope */
    $one[] = $pool->submit(new StageOne($staging, $config, $daemon_config));
    /* maintain reference counts */
    $data[] = $staging;
#    sleep(1);
}
$NumberOfImports = GetWaitingImportRowCount($sql);
#var_dump(count($one));

/* construct stage two */
while(1)
{
    /* find completed StageOne objects */
    foreach ($one as $id => $job)
    {
    	#var_dump($job);
        /* if done is set, the data from this StageOne can be used */
        if ($job->done) {
            /* use each element of data to create new tasks for StageTwo */
            foreach ($job->data as $chunk) {

                /* submit stage two */
                $two[] = $pool->submit(new StageTwo($chunk));
            }
            /* no longer required */
            unset($one[$id]);

			$NumberOfImports = GetWaitingImportRowCount($sql);
            echo "Number of Imports Left: $NumberOfImports\n";
            /*
            if($NumberOfImports > 0)
            {
				$staging = new StagingData();
				$one[] = $pool->submit(new StageOne($staging, $config, $daemon_config));
				$data[] = $staging;
				sleep(1);
			}
            */
        }
    }
    echo "Done checking jobs for now... sleep for 5 seconds.\n";
    sleep(5);
	$NumberOfImports = GetWaitingImportRowCount($sql);
}

/* all tasks stacked, the pool can be shutdown */
$pool->shutdown();




function GetWaitingImportRowCount($sql)
{
	$result = $sql->conn->query("SELECT count(id) FROM `wifi`.`files_tmp`");
	$fetch = $result->fetch();
	return $fetch[0];
}




class StageOne extends Stackable {
    protected $done;
    /**
    * Construct StageOne with suitable storage for data
    * @param StagingData $data
 	* @param array $config
 	* @param array $daemon_config
    */
    public function __construct(StagingData $data, $config, $daemon_config) {
        $this->data = $data;
        $this->i = 0;
		$this->config = $config;
		$this->daemon_config = $daemon_config;

    }

	private function GetNextImportID()
	{
		echo "Running GetNextImportID()\n";
		require_once $this->daemon_config['wifidb_install']."/lib/SQL.inc.php";
		$sql = new SQL($this->config);
		$daemon_sql = "INSERT INTO `wifi`.`files_importing` (`file`, `user`, `title`, `notes`, `size`, `date`, `hash`, `tmp_id`) SELECT `file`, `user`, `title`, `notes`, `size`, `date`, `hash`, `id` FROM `wifi`.`files_tmp` WHERE importing = 0 ORDER BY `id` ASC LIMIT 1;";
		$result = $sql->conn->prepare($daemon_sql);
		$result->execute();
		$sql->checkError(__LINE__, __FILE__);
		$LastInsert = $sql->conn->lastInsertID();
		#var_dump($LastInsert);
		$sql = NULL;
		return $LastInsert;
	}

    public function run()
    {
        echo "Running run()\n";
        /* Run an Import */
		$import_id = $this->GetNextImportID();
        system("php /opt/wifidb/tools/daemon/importd.php -f -i $import_id &> /opt/wifidb/tools/logs/{$import_id}_".date("Y-m-d_h:i:s").".log", $ret);
        var_dump("------------------------------------------------------------------------------------------------\n\n\nProcess Done, setting Return value: $ret --- And done variable\n\n\n\n");
        $this->data[] = $ret;
        $this->done = true;
    }
}


class StageTwo extends Stackable {
    /**
    * Construct StageTwo from a part of StageOne data
    * @param int $data
    */
    public function __construct($data) {
        $this->data = $data;
    }

    public function run(){
        printf(
            "Thread %lu had result of: %d\n",
            $this->worker->getThreadId(), $this->data);
    }
}

class StagingData extends Stackable {
    public function run() {}
}
