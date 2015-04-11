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

	private function GetNextImportID()
	{
		$daemon_sql = "INSERT INTO wifi.files_importing (`file`, `user`, `title`, `notes`, `size`, `date`, `hash`, `tmp_id`) SELECT `file`, `user`, `title`, `notes`, `size`, `date`, `hash`, `id` FROM `wifi`.`files_tmp` WHERE importing = 0 ORDER BY `id` ASC LIMIT 1;";
		$result = $this->dbcore->sql->conn->query($daemon_sql);
		$fetch = $result->fetch(2);
		return $fetch['id'];
	}

    public function run() {
        /* Run an Import */
		$import_id = $this->GetNextImportID();
        system("php /opt/wifidb/tools/daemon/importd.php -f -i $import_id");
        #while (@$this->i++ < 100) {
        #    $this->data[] = mt_rand(
        #        20, 1000);
        #}
        $this->done = true;
    }
}

