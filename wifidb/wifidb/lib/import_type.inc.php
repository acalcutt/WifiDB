<?php
/*
import_type.inc.php, which importer an uploaded file is for, from its first bytes and then its name: the upload page's
default (lib/js/wifidb/import_type.js has the same rules), an upload sent without a type, and api/v2/import.php.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class ImportType
{
	/** the upload page's types (the importer's, tools/daemon/lib/daemon.inc.php) => what the page calls them */
	const TYPES = array(
		'vistumbler'   => 'Vistumbler VS1/VSZ/CSV/MDB',
		'ns1'          => 'NetStumbler NS1',
		'wardrive'     => 'Wardrive DB/DB3',
		'wiglewificsv' => 'WigleWifi CSV',
		'swardriving'  => 'SWardriving CSV',
		'kismet'       => 'Kismet netxml',
	);

	/** how many of a file's first bytes Guess() looks at */
	const HEAD_BYTES = 4096;

	/** extension => type, for a file whose contents don't say */
	const EXTENSIONS = array(
		'vs1' => 'vistumbler', 'vsz' => 'vistumbler', 'vscz' => 'vistumbler', 'txt' => 'vistumbler', 'csv' => 'vistumbler',
		'mdb' => 'vistumbler', 'ns1' => 'ns1', 'db' => 'wardrive', 'db3' => 'wardrive', 'gz' => 'wiglewificsv',
		'wiglecsv' => 'wiglewificsv', 'wiglewificsv' => 'wiglewificsv', 'netxml' => 'kismet',
	);

	/**
	 * @param string $name the file's name, as uploaded
	 * @param string $head its first bytes (HEAD_BYTES is plenty)
	 * @return string|null a key of TYPES, or null when neither says
	 */
	public static function Guess($name, $head)
	{
		$head = (string)$head;
		# what the file is, whatever it's called
		if(strncmp($head, "SQLite format 3\0", 16) === 0) { return 'wardrive'; }
		if(strncmp($head, 'NetS', 4) === 0) { return 'ns1'; }
		if(strncmp($head, "\x1f\x8b", 2) === 0) { return 'wiglewificsv'; }   # only WiGLE's exports come gzipped
		if(strncmp($head, "PK\x03\x04", 4) === 0) { return 'vistumbler'; }  # a VSZ
		if(preg_match('/^.{4}Standard (Jet|ACE) DB/s', $head)) { return 'vistumbler'; }  # a Vistumbler MDB
		$text = ltrim(preg_replace('/^\xEF\xBB\xBF/', '', $head));
		if(stripos($text, 'WigleWifi') === 0) { return 'wiglewificsv'; }
		if(stripos($text, 'BSSID,SSID,AuthMode,channel,frequency') === 0) { return 'swardriving'; }
		if(stripos($text, 'SSID,BSSID,MANUFACTURER') === 0) { return 'vistumbler'; }   # Vistumbler's detailed CSV
		if(strpos($text, '<?xml') === 0 || strpos($text, '<detection-run') === 0)
		{
			if(stripos($text, 'detection-run') !== false || stripos($text, 'kismet') !== false) { return 'kismet'; }
		}
		if(stripos($text, '# Vistumbler') === 0) { return 'vistumbler'; }
		# then what it's called
		$ext = strtolower(pathinfo((string)$name, PATHINFO_EXTENSION));
		return self::EXTENSIONS[$ext] ?? null;
	}

	/** Guess() for a file on disk */
	public static function GuessFile($name, $path)
	{
		$head = @file_get_contents($path, false, null, 0, self::HEAD_BYTES);
		return self::Guess($name, $head === false ? '' : $head);
	}
}
