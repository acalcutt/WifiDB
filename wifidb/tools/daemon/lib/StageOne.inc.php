<?php
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
        define("SWITCH_SCREEN", "CLI");
		define("SWITCH_EXTRAS", "import");

		require $daemon_config['wifidb_install']."/lib/init.inc.php";
		$this->dbcore = $dbcore;
    }


    public function run()
    {
        /* Run an Import */
		$import_id = $this->dbcore->GetNextImportID();
        system("php /opt/wifidb/tools/daemon/importd.php -f -i $import_id", $ret);
        $this->data[] = $ret;
        $this->done = true;
    }
}

