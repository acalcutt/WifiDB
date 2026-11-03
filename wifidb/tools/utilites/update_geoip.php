<?php
/*
update_geoip.php, fetches a new copy of the country database (lib/visitor_country.inc.php) when the one in
config's geoip_country_db is missing or over a month old. tools/cron/update_geoip runs it each day; each node keeps its
own copy. If the fetch fails the copy there stays, and still works.

  php update_geoip.php           fetch one if it's due
  php update_geoip.php --force   fetch one now
*/
if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php\r\n");}
$web = rtrim($daemon_config['wifidb_install'], '/');
require $web.'/lib/config.inc.php';
require_once $web.'/vendor/autoload.php';
require_once $web.'/lib/visitor_country.inc.php';

$db = isset($config['geoip_country_db']) ? (string)$config['geoip_country_db'] : '';
if($db === '')
{
	echo "geoip_country_db isn't set in config.inc.php: nothing to update\r\n";
	exit(0);
}
try
{
	echo VisitorCountry::Update($db, isset($config['geoip_country_source']) ? $config['geoip_country_source'] : '', null, null, in_array('--force', $argv, true))."\r\n";
}
catch(Throwable $e)
{
	fwrite(STDERR, $e->getMessage().(is_file($db) ? " (the copy there, from ".gmdate('Y-m-d', filemtime($db)).", is still used)" : '')."\r\n");
	exit(1);
}
