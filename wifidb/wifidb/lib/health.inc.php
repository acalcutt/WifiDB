<?php
/*
health.inc.php, the readiness check behind /health/ready: can this node reach its database, and can it write where
uploads and generated files go? Standalone: it is given a way to connect and the folders to try, and needs no config.

This is separate from /health on purpose. /health is a static file Apache answers by itself, so it keeps answering when
every PHP worker is busy with a long export; this one runs PHP and the database, so it can only be trusted if it can't
be starved (its own tiny PHP-FPM pool) or left hanging (short timeouts). Use /health for a load balancer's check and
/health/ready for dashboards, alerts, or a tolerant check (e.g. HAProxy "fall 5 rise 2").

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

/**
 * @param callable $connect returns a PDO, or throws
 * @param string[] $writableDirs folders a file must be writable in
 * @return array ok (bool), problems (string[]: short reasons, no paths or versions), ms (how long it took)
 */
function wifidb_readiness($connect, $writableDirs)
{
	$start = microtime(true);
	$problems = array();

	try
	{
		$pdo = call_user_func($connect);
		$row = $pdo->query('SELECT 1')->fetch(PDO::FETCH_NUM);
		if(!$row || (int)$row[0] !== 1) { $problems[] = 'database gave a wrong answer'; }
	}
	catch(Throwable $e)
	{
		$problems[] = 'database unreachable';
	}

	foreach($writableDirs as $name => $dir)
	{
		$file = rtrim($dir, '/\\') . '/.ready-' . getmypid() . '-' . bin2hex(random_bytes(3));
		if(!is_dir($dir)) { $problems[] = $name . ' missing'; continue; }
		if(@file_put_contents($file, 'x') === false) { $problems[] = $name . ' not writable'; continue; }
		@unlink($file);
	}

	return array('ok' => !$problems, 'problems' => $problems, 'ms' => (int)round((microtime(true) - $start) * 1000));
}
