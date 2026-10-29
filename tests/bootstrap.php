<?php
// Tests run without a config file or a database, so only standalone code is loaded here.
define('WIFIDB_LIB', dirname(__DIR__) . '/wifidb/wifidb/lib');
define('WIFIDB_WEB', dirname(__DIR__) . '/wifidb/wifidb');

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once WIFIDB_LIB . '/helpers.inc.php';
