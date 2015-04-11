<?php

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "import");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

/* stage and data reference arrays */
$one = [];
$two = [];
$data = [];

$pool = new Pool(8);
$pool->start();

/* construct stage one */
while (count($one) < 10) {
    $staging = new StagingData();
    /* maintain reference counts by storing return value in normal array in local scope */
    $one[] = $pool->submit(new StageOne($staging));
    /* maintain reference counts */
    $data[] = $staging;
}
$importnumber = 100;
/* construct stage two */
while($importnumber > 0)
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
        }
    }
	$importnumber--;
    /* in the real world, it is unecessary to keep polling the array */
    /* you probably have some work you want to do ... do it :) */
    #if (count($one)) {
        /* everyone likes sleep ... */
    #    usleep(1000000);
    #}

}

/* all tasks stacked, the pool can be shutdown */
$pool->shutdown();