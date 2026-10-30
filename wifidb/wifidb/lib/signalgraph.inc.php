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
