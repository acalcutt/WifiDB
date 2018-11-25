<?php

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "import");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

/* stage and data reference arrays */
$one = [];
$two = [];
$data = [];

$pool = new Pool(8);
$pool->start();

/* construct stage one */
while (count($one) < $dbcore->NumberOfThreads) {
    $staging = new StagingData();
    /* maintain reference counts by storing return value in normal array in local scope */
    $one[] = $pool->submit(new StageOne($staging, $config, $daemon_config));
    /* maintain reference counts */
    $data[] = $staging;
}
$NumberOfImports = $dbcore->GetWaitingImportRowCount();
/* construct stage two */
while(1)
{
    /* find completed StageOne objects */
    foreach ($one as $id => $job) {
        /* if done is set, the data from this StageOne can be used */
        if ($job->done) {
            /* use each element of data to create new tasks for StageTwo */
            foreach ($job->data as $chunk) {

                /* submit stage two */
                $two[] = $pool->submit(new StageTwo($chunk));
            }
            /* no longer required */
            unset($one[$id]);

			$NumberOfImports = $dbcore->GetWaitingImportRowCount();
            if($NumberOfImports > 0)
            {
				$staging = new StagingData();
				$one[] = $pool->submit(new StageOne($staging, $config, $daemon_config));
				$data[] = $staging;
				sleep(1);
			}
        }
    }
    echo "Done checking jobs for now... sleep for 5 seconds.\n";
    sleep(5);
	$NumberOfImports = $dbcore->GetWaitingImportRowCount();
}

/* all tasks stacked, the pool can be shutdown */
$pool->shutdown();