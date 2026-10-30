<?php

// ── Input handling for the graph pages (anything from the address bar or a form is untrusted) ──────────────────────
if(!function_exists('h')) {
	/** A value for HTML text or an attribute */
	function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
if(!function_exists('wifi_clean_text')) {
	/** A short, single-line label: control characters out, length limited (the picture grows with its text) */
	function wifi_clean_text($v, $max = 64)
	{
		$v = preg_replace('/[\x00-\x1f\x7f]+/', ' ', (string)$v);
		return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max);
	}
}
if(!function_exists('wifi_color_spec')) {
	/** "rrr:ggg:bbb" with each part 0-255, or "rand"; anything else gives $default */
	function wifi_color_spec($v, $default)
	{
		$v = (string)$v;
		if($v === 'rand') { return 'rand'; }
		if(!preg_match('/^(\d{1,3}):(\d{1,3}):(\d{1,3})$/', $v, $m)) { $v = $default; preg_match('/^(\d{1,3}):(\d{1,3}):(\d{1,3})$/', $v, $m); }
		return sprintf('%03d:%03d:%03d', min(255, (int)$m[1]), min(255, (int)$m[2]), min(255, (int)$m[3]));
	}
}
if(!function_exists('wifi_sig_spec')) {
	/** "50-60-70": whole numbers 0-100 joined by dashes, at most $max of them */
	function wifi_sig_spec($v, $max = 3000)
	{
		$out = array();
		foreach(explode('-', substr((string)$v, 0, $max * 4)) as $part)
		{
			if($part === '' || !ctype_digit($part)) { continue; }
			$out[] = min(100, (int)$part);
			if(count($out) >= $max) { break; }
		}
		return $out ? implode('-', $out) : '0';
	}
}
$ver=array(
			"lastedit"	=>"2010-June-13",
			"wifi"		=>"v2.0.5",
		   );
if(!function_exists('wifi_show_graph')) {
	/**
	 * Shows the graph the form (genline.php) asked for, as an image the browser fetches from signal.php (the same
	 * picture the signal.png and signal.svg addresses give). Nothing is written to disk.
	 */
	function wifi_show_graph($type)
	{
		$map = array('SSID' => 'ssid', 'Mac' => 'mac', 'Manuf' => 'man', 'Auth' => 'auth', 'Encry' => 'encry', 'radio' => 'radio',
			'Chn' => 'chan', 'Lat' => 'lat', 'Long' => 'long', 'BTx' => 'btx', 'OTx' => 'otx', 'FA' => 'FA', 'LU' => 'LU',
			'NT' => 'NT', 'Label' => 'label', 'Sig' => 'sig', 'text' => 'text', 'bg' => 'bgc', 'line' => 'linec');
		$q = array('type' => $type);
		foreach($map as $param => $field)
		{
			$q[$param] = (isset($_POST[$field]) && is_scalar($_POST[$field])) ? (string)$_POST[$field] : '';
		}
		$url = 'signal.php?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
		$title = isset($_POST['ssid']) && is_scalar($_POST['ssid']) ? wifi_clean_text($_POST['ssid']) : '';
		echo '<h1>' . h($title === '' ? 'UNNAMED' : $title) . '</h1><br>';
		echo '<img src="' . h($url) . '" alt="Signal history graph"><br />';
		echo 'Save or share this picture: <a href="' . h($url) . '">graph</a> (add <code>&amp;format=svg</code> for an SVG)<br />';
	}
}

?>