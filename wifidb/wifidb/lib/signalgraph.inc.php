<?php
/*
signalgraph.inc.php, the signal history graph of one access point, as SVG (needs nothing) or PNG (needs PHP's GD).

The address Vistumbler opens (api/wifi/) carries the access point's data, so the same parameters build the graph here:
SSID, Mac, Manuf, Auth, Encry, radio, Chn, Lat, Long, BTx, OTx, FA, LU, NT, Label and Sig (whole numbers 0-100, joined
by dashes, oldest first). Everything is untrusted: text is cleaned and limited, colors are checked, the number of
points is capped, and all text is escaped before it goes into SVG.

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class SignalGraph
{
	const MAX_POINTS = 3000;
	const MAX_TEXT = 64;
	const MIN_WIDTH = 900;
	const HEIGHT = 480;
	const STEP = 6;        // pixels between samples
	const LEFT = 20;       // where the first sample sits
	const BASE = 460;      // y of signal 0
	const SCALE = 4;       // pixels per signal point (100 = 400px)

	/** Vistumbler's parameter name => the key in the spec */
	const FIELDS = array(
		'SSID' => 'ssid', 'Mac' => 'mac', 'Manuf' => 'man', 'Auth' => 'auth', 'Encry' => 'encry', 'radio' => 'radio',
		'Chn' => 'chan', 'Lat' => 'lat', 'Long' => 'long', 'BTx' => 'btx', 'OTx' => 'otx', 'FA' => 'fa', 'LU' => 'lu',
		'NT' => 'nt', 'Label' => 'label',
	);

	// ── Cleaning what comes in ───────────────────────────────────────────────────────────────────────

	/** A short single-line label: control characters out, length limited */
	public static function CleanText($v, $max = self::MAX_TEXT)
	{
		$v = preg_replace('/[\x00-\x1f\x7f]+/', ' ', is_scalar($v) ? (string)$v : '');
		return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max);
	}

	/** @return int[] [r, g, b] from "rrr:ggg:bbb" (each clamped to 255), or $default; "rand" gives null (pick one) */
	public static function ParseColor($v, array $default)
	{
		$v = is_scalar($v) ? (string)$v : '';
		if($v === 'rand') { return null; }
		if(!preg_match('/^(\d{1,3}):(\d{1,3}):(\d{1,3})$/', $v, $m)) { return $default; }
		return array(min(255, (int)$m[1]), min(255, (int)$m[2]), min(255, (int)$m[3]));
	}

	/** @return int[] whole numbers 0-100 from "50-60-70" (anything else dropped), at most $max; [0] if there are none */
	public static function ParseSignals($v, $max = self::MAX_POINTS)
	{
		$out = array();
		foreach(explode('-', substr(is_scalar($v) ? (string)$v : '', 0, $max * 4)) as $part)
		{
			if($part === '' || !ctype_digit($part)) { continue; }
			$out[] = min(100, (int)$part);
			if(count($out) >= $max) { break; }
		}
		return $out ? $out : array(0);
	}

	/**
	 * Builds a spec from Vistumbler's parameters (and these options: type bar|line, text/bg/line colors "rrr:ggg:bbb"
	 * or "rand", and a title override).
	 */
	public static function FromQuery(array $q)
	{
		$spec = array();
		foreach(self::FIELDS as $param => $key)
		{
			$spec[$key] = self::CleanText(isset($q[$param]) ? $q[$param] : (isset($q[strtolower($param)]) ? $q[strtolower($param)] : ''));
		}
		$spec['sig'] = self::ParseSignals(isset($q['Sig']) ? $q['Sig'] : (isset($q['sig']) ? $q['sig'] : ''));
		$spec['type'] = (isset($q['type']) && $q['type'] === 'line') ? 'line' : 'bar';
		$seed = $spec['mac'] . '|' . $spec['ssid'];
		$spec['text'] = self::ParseColor(isset($q['text']) ? $q['text'] : '', array(255, 255, 255));
		$spec['line'] = self::ParseColor(isset($q['line']) ? $q['line'] : '', array(255, 0, 0));
		$spec['bg'] = self::ParseColor(isset($q['bg']) ? $q['bg'] : '', array(0, 0, 0));
		foreach(array('text', 'line', 'bg') as $part)
		{
			if($spec[$part] === null) { $spec[$part] = self::RandomColor($seed . $part); }
		}
		if($spec['ssid'] === '' || trim($spec['ssid']) === '') { $spec['ssid'] = 'UNNAMED'; }
		return $spec;
	}

	/** A "random" color that is the same every time for the same graph, so the image can be cached */
	public static function RandomColor($seed)
	{
		$h = md5($seed, true);
		return array(25 + ord($h[0]) % 196, 25 + ord($h[1]) % 196, 25 + ord($h[2]) % 196);
	}

	// ── From the database ────────────────────────────────────────────────────────────────────────────

	const DB_MAX_ROWS = 50000;       // readings read for one graph, the most recent
	const DEFAULT_WIDTH = 3000;      // pixels: about what Vistumbler's own links reached
	const MAX_WIDTH = 20000;

	/** A date or date-time as "Y-m-d H:i:s", or null if it isn't one. A date alone is midnight, or with $endOfDay the last second of that day (for a "to"). */
	public static function ParseDate($v, $endOfDay = false)
	{
		if(!is_scalar($v) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/', trim((string)$v), $m)) { return null; }
		if(!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) { return null; }
		$dateOnly = !isset($m[4]);
		$h = isset($m[4]) ? (int)$m[4] : ($endOfDay ? 23 : 0); $i = isset($m[5]) ? (int)$m[5] : ($endOfDay && $dateOnly ? 59 : 0); $s = isset($m[6]) ? (int)$m[6] : ($endOfDay && $dateOnly ? 59 : 0);
		if($h > 23 || $i > 59 || $s > 59) { return null; }
		return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[1], $m[2], $m[3], $h, $i, $s);
	}

	/**
	 * Shrinks a long run of readings to at most $max points, keeping the strongest reading in each stretch, so a
	 * brief peak is still there afterwards.
	 */
	public static function Fit(array $sig, $max)
	{
		$n = count($sig);
		$max = max(1, (int)$max);
		if($n <= $max) { return $sig; }
		$out = array();
		for($i = 0; $i < $max; $i++)
		{
			$from = (int)floor($i * $n / $max);
			$to = max($from + 1, (int)floor(($i + 1) * $n / $max));
			$out[] = max(array_slice($sig, $from, $to - $from));
		}
		return $out;
	}

	/**
	 * The access point's own data and its recorded signal history (the same rows the details pages show), as the fields
	 * of a spec: the readings oldest first, like Vistumbler's addresses.
	 *
	 * @param PDO $conn
	 * @param string $dialect mysql or sqlsrv: how a row limit is spelled
	 * @param array $opts what to include: file (a file's ID: only that import's readings, as on the details page),
	 *                    days (only the last N days of what there is, counted back from the newest reading, so an old
	 *                    access point still graphs), from and to (dates, "2024-01-31" or "2024-01-31 12:00:00"; inclusive),
	 *                    width (the widest picture wanted, in pixels; more readings than fit are merged, strongest kept)
	 * @return array|null null if there is no such access point, or none of its readings are in what was asked for
	 */
	public static function DataForAp($conn, $dialect, $apId, array $opts = array())
	{
		if(!is_scalar($apId) || !ctype_digit((string)$apId) || strlen((string)$apId) > 18) { return null; }
		$id = (int)$apId;

		// mixed-case names are quoted on PostgreSQL, which would fold them to lower case; the others match them either way
		$q = function ($name) use ($dialect) { return $dialect === 'pgsql' ? '"' . $name . '"' : $name; };
		$cols = 'wap.' . $q('BSSID') . ', wap.' . $q('SSID') . ', wap.' . $q('CHAN') . ', wap.' . $q('AUTH') . ', wap.' . $q('ENCR') . ', wap.' . $q('RADTYPE')
			. ', wap.' . $q('NETTYPE') . ', wap.' . $q('BTX') . ', wap.' . $q('OTX') . ', wap.fa, wap.la, g.' . $q('Lat') . ', g.' . $q('Lon') . ' ';
		$sql = ($dialect === 'sqlsrv') ? 'SELECT TOP 1 ' . $cols : 'SELECT ' . $cols;
		$sql .= 'FROM wifi_ap AS wap LEFT JOIN wifi_gps AS g ON g.' . $q('GPS_ID') . ' = wap.' . $q('HighGps_ID') . ' WHERE wap.' . $q('AP_ID') . ' = ?';
		if($dialect !== 'sqlsrv') { $sql .= ' LIMIT 1'; }
		$prep = $conn->prepare($sql);
		$prep->execute(array($id));
		$ap = $prep->fetch(PDO::FETCH_ASSOC);
		if(!$ap) { return null; }

		// which readings
		$where = $q('AP_ID') . ' = ?';
		$params = array($id);
		$file = (isset($opts['file']) && is_scalar($opts['file']) && ctype_digit((string)$opts['file']) && strlen((string)$opts['file']) <= 18) ? (int)$opts['file'] : null;
		if($file !== null) { $where .= ' AND ' . $q('File_ID') . ' = ?'; $params[] = $file; }
		$date = $q('Hist_Date');

		$from = isset($opts['from']) ? self::ParseDate($opts['from']) : null;
		$to = isset($opts['to']) ? self::ParseDate($opts['to'], true) : null;
		$days = (isset($opts['days']) && is_scalar($opts['days']) && ctype_digit((string)$opts['days']) && strlen((string)$opts['days']) <= 5 && (int)$opts['days'] > 0) ? (int)$opts['days'] : null;
		if($days !== null)
		{
			$prep = $conn->prepare('SELECT MAX(' . $date . ') FROM wifi_hist WHERE ' . $where);
			$prep->execute($params);
			$newest = $prep->fetchColumn();
			$stamp = $newest ? strtotime(substr($newest, 0, 19) . ' UTC') : false;
			if($stamp !== false)
			{
				$since = gmdate('Y-m-d H:i:s', $stamp - $days * 86400);
				if($from === null || $since > $from) { $from = $since; }
			}
		}
		if($from !== null) { $where .= ' AND ' . $date . ' >= ?'; $params[] = $from; }
		if($to !== null) { $where .= ' AND ' . $date . ' <= ?'; $params[] = $to; }

		$order = ' ORDER BY ' . $date . ' DESC, ' . $q('Hist_ID') . ' DESC';
		$sql = ($dialect === 'sqlsrv')
			? 'SELECT TOP ' . self::DB_MAX_ROWS . ' ' . $q('Sig') . ', ' . $date . ' FROM wifi_hist WHERE ' . $where . $order
			: 'SELECT ' . $q('Sig') . ', ' . $date . ' FROM wifi_hist WHERE ' . $where . $order . ' LIMIT ' . self::DB_MAX_ROWS;
		$prep = $conn->prepare($sql);
		$prep->execute($params);
		$rows = array_reverse($prep->fetchAll(PDO::FETCH_NUM));
		$filtered = ($file !== null || $from !== null || $to !== null);
		if(!$rows && $filtered) { return null; }

		$signals = array();
		foreach($rows as $row) { $signals[] = max(0, min(100, (int)$row[0])); }
		$width = (isset($opts['width']) && is_scalar($opts['width']) && ctype_digit((string)$opts['width']) && strlen((string)$opts['width']) <= 6)
			? max(400, min(self::MAX_WIDTH, (int)$opts['width'])) : self::DEFAULT_WIDTH;
		$signals = self::Fit($signals, (int)floor(($width - 2 * self::LEFT) / self::STEP));

		$text = function ($v) { return self::CleanText($v === null ? '' : $v); };
		// what is drawn: the window's own first and last readings when only part of the history was asked for
		$first = $ap['fa']; $last = $ap['la'];
		if($filtered || $days !== null) { $first = $rows ? $rows[0][1] : $first; $last = $rows ? $rows[count($rows) - 1][1] : $last; }
		return array(
			'ssid' => ($text($ap['SSID']) === '' ? 'UNNAMED' : $text($ap['SSID'])), 'mac' => $text($ap['BSSID']), 'man' => '',
			'auth' => $text($ap['AUTH']), 'encry' => $text($ap['ENCR']), 'radio' => $text($ap['RADTYPE']), 'chan' => $text($ap['CHAN']),
			'lat' => $text($ap['Lat']), 'long' => $text($ap['Lon']), 'btx' => $text($ap['BTX']), 'otx' => $text($ap['OTX']),
			'fa' => $text($first), 'lu' => $text($last), 'nt' => $text($ap['NETTYPE']),
			'label' => $file !== null ? 'File ' . $file : '',
			'sig' => $signals ? $signals : array(0),
		);
	}

	// ── Layout ───────────────────────────────────────────────────────────────────────────────────────

	/** The three lines of text at the top of the picture */
	public static function Header(array $s)
	{
		return array(
			'SSID: ' . $s['ssid'] . '   Channel: ' . $s['chan'] . '   Radio: ' . $s['radio'] . '   Network: ' . $s['nt'] . '   OTx: ' . $s['otx'],
			'Mac: ' . $s['mac'] . '   Auth: ' . $s['auth'] . ' ' . $s['encry'] . '   BTx: ' . $s['btx'] . '   Lat: ' . $s['lat'] . '   Long: ' . $s['long'],
			'Manuf: ' . $s['man'] . '   Label: ' . $s['label'] . '   First: ' . $s['fa'] . '   Last: ' . $s['lu'],
		);
	}

	public static function Width(array $s)
	{
		$wide = self::LEFT * 2 + count($s['sig']) * self::STEP;
		foreach(self::Header($s) as $line) { $wide = max($wide, 21 + 8 * mb_strlen($line, 'UTF-8') + 20); }
		return (int)max(self::MIN_WIDTH, min(25000, $wide));
	}

	private static function Y($signal) { return self::BASE - $signal * self::SCALE; }

	// ── SVG ──────────────────────────────────────────────────────────────────────────────────────────

	public static function Svg(array $s)
	{
		$w = self::Width($s);
		$h = self::HEIGHT;
		$rgb = function ($c) { return 'rgb(' . $c[0] . ',' . $c[1] . ',' . $c[2] . ')'; };
		$grid = ($s['bg'] === array(0, 0, 0)) ? 'rgb(255,255,255)' : 'rgb(0,0,0)';
		$text = $rgb($s['text']);
		$line = $rgb($s['line']);
		$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8'); };

		$svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="' . $e('Signal history of ' . $s['ssid']) . '">' . "\n";
		$svg .= '<title>' . $e('Signal history of ' . $s['ssid']) . '</title>' . "\n";
		$svg .= '<rect width="100%" height="100%" fill="' . $rgb($s['bg']) . '"/>' . "\n";

		// grid: a line every 20px (5 signal points)
		$svg .= '<g stroke="' . $grid . '" stroke-width="1" stroke-opacity="0.35" shape-rendering="crispEdges">';
		for($y = self::BASE; $y >= 60; $y -= 20) { $svg .= '<line x1="0" y1="' . $y . '" x2="' . $w . '" y2="' . $y . '"/>'; }
		for($x = self::LEFT; $x < $w; $x += 20) { $svg .= '<line x1="' . $x . '" y1="60" x2="' . $x . '" y2="' . self::BASE . '"/>'; }
		$svg .= "</g>\n";

		$svg .= '<g fill="' . $text . '" font-family="monospace" font-size="13">';
		foreach(self::Header($s) as $i => $row) { $svg .= '<text x="21" y="' . (15 + $i * 20) . '">' . $e($row) . '</text>'; }
		for($v = 0; $v <= 100; $v += 5) { $svg .= '<text x="2" y="' . (self::Y($v) + 4) . '" font-size="11">' . $v . '</text>'; }
		$svg .= "</g>\n";

		$n = count($s['sig']);
		if($s['type'] === 'line')
		{
			$points = array();
			foreach($s['sig'] as $i => $v) { $points[] = (self::LEFT + $i * self::STEP) . ',' . self::Y($v); }
			$svg .= '<polyline fill="none" stroke="' . $line . '" stroke-width="2" stroke-linejoin="round" points="' . implode(' ', $points) . '"/>' . "\n";
		}
		else
		{
			$svg .= '<g fill="' . $line . '" shape-rendering="crispEdges">';
			foreach($s['sig'] as $i => $v)
			{
				if($v > 0) { $svg .= '<rect x="' . (self::LEFT + $i * self::STEP) . '" y="' . self::Y($v) . '" width="' . (self::STEP - 1) . '" height="' . ($v * self::SCALE) . '"/>'; }
			}
			$svg .= "</g>\n";
		}
		return $svg . '</svg>' . "\n";
	}

	// ── PNG (GD) ─────────────────────────────────────────────────────────────────────────────────────

	public static function GdAvailable() { return function_exists('imagecreatetruecolor') && function_exists('imagepng'); }

	/** @return string the PNG file's bytes */
	public static function Png(array $s)
	{
		if(!self::GdAvailable()) { throw new RuntimeException('PHP GD is not installed'); }
		$w = self::Width($s);
		$h = self::HEIGHT;
		$img = imagecreatetruecolor($w, $h);
		$bg = imagecolorallocate($img, $s['bg'][0], $s['bg'][1], $s['bg'][2]);
		$grid = ($s['bg'] === array(0, 0, 0)) ? imagecolorallocate($img, 90, 90, 90) : imagecolorallocate($img, 170, 170, 170);
		$text = imagecolorallocate($img, $s['text'][0], $s['text'][1], $s['text'][2]);
		$col = imagecolorallocate($img, $s['line'][0], $s['line'][1], $s['line'][2]);
		imagefill($img, 0, 0, $bg);

		for($y = self::BASE; $y >= 60; $y -= 20) { imageline($img, 0, $y, $w, $y, $grid); }
		for($x = self::LEFT; $x < $w; $x += 20) { imageline($img, $x, 60, $x, self::BASE, $grid); }
		foreach(self::Header($s) as $i => $row) { imagestring($img, 4, 21, 3 + $i * 20, self::Latin1($row), $text); }
		for($v = 0; $v <= 100; $v += 5) { imagestring($img, 2, 2, self::Y($v) - 6, (string)$v, $text); }

		$prevX = null; $prevY = null;
		foreach($s['sig'] as $i => $v)
		{
			$x = self::LEFT + $i * self::STEP;
			$y = self::Y($v);
			if($s['type'] === 'line')
			{
				if($prevX !== null) { imageline($img, $prevX, $prevY, $x, $y, $col); imageline($img, $prevX, $prevY - 1, $x, $y - 1, $col); }
				$prevX = $x; $prevY = $y;
			}
			elseif($v > 0)
			{
				imagefilledrectangle($img, $x, $y, $x + self::STEP - 2, self::BASE, $col);
			}
		}
		ob_start();
		imagepng($img);
		$png = ob_get_clean();
		unset($img);
		return $png;
	}

	/** GD's built-in fonts only know Latin-1; anything else shows as a question mark */
	private static function Latin1($v)
	{
		$out = function_exists('iconv') ? @iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $v) : false;
		return $out === false ? preg_replace('/[^\x20-\x7e]/', '?', $v) : $out;
	}
}
