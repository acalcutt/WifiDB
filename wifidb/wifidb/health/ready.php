<?php
// /health/ready: 200 "ok" when this node can reach its database and write to its data folders, otherwise 503 with
// short reasons. See lib/health.inc.php. Deliberately does not load the whole application (no Smarty, no sessions):
// it needs to stay fast and keep working when the rest of the site is struggling.
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

$lib = dirname(__DIR__) . '/lib';
if(!is_file($lib . '/config.inc.php')) { http_response_code(503); echo "not ready: no config\n"; exit; }
require $lib . '/helpers.inc.php';
require $lib . '/health.inc.php';
require $lib . '/config.inc.php';

$root = rtrim($config['wifidb_install'], '/');
$result = wifidb_readiness(
	function() use ($config) { return wifidb_pdo_from_config($config, 2); },
	array(
		'uploads'   => $root . '/import/up',
		'generated' => $root . '/out',
	)
);

if($result['ok']) { echo "ok\n"; exit; }
http_response_code(503);
echo 'not ready: ' . implode(', ', $result['problems']) . "\n";
