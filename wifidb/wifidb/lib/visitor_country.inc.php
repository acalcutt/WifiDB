<?php
/*
visitor_country.inc.php, the country a visitor is in, from their address: for asking consent before analytics and ads
only where the law requires it (lib/cookie_consent.inc.php).

Looked up on this server, in a copy of DB-IP's free country database (IP to Country Lite, CC BY 4.0: https://db-ip.com)
kept in the file config's geoip_country_db names; nothing about the visitor is sent anywhere. tools/cron/update_geoip
fetches a new copy each month and only puts it in place once it opens, so if the source is down, moves or goes away the
copy there keeps working. geoip_country_source points the fetch somewhere else (any MMDB country database: DB-IP's,
MaxMind's GeoLite2 or IPinfo's; the Proxmox template ships a copy too).

A CDN in front that says the country (Cloudflare's CF-IPCountry) is taken at its word. The address is the visitor's own
behind the proxy too (mod_remoteip, as the Proxmox template sets it up). No database, or an address it doesn't know (a
private one): the country isn't known, and whoever asks treats that as needing consent.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class VisitorCountry
{
	const SOURCE = 'https://download.db-ip.com/free/dbip-country-lite-%s.mmdb.gz';   # %s: the month, 2026-11
	const MAX_AGE_DAYS = 32;    # older than this, the next update fetches a new one
	const MIN_BYTES = 1048576;  # a whole database is a few MB; less is an error page or a broken download

	/**
	 * @param array $server $_SERVER
	 * @param string $dbPath the country database (config's geoip_country_db)
	 * @return string|null the two-letter country code, or null when it can't be told
	 */
	public static function Lookup(array $server, $dbPath)
	{
		if(isset($server['HTTP_CF_IPCOUNTRY']) && is_string($server['HTTP_CF_IPCOUNTRY']))
		{
			$code = strtoupper(trim($server['HTTP_CF_IPCOUNTRY']));
			if(preg_match('/^[A-Z]{2}$/', $code) && $code !== 'XX' && $code !== 'T1') { return $code; }   # XX unknown, T1 Tor
		}
		$ip = isset($server['REMOTE_ADDR']) ? (string)$server['REMOTE_ADDR'] : '';
		if($ip === '' || !is_string($dbPath) || $dbPath === '' || !is_file($dbPath) || !class_exists('MaxMind\Db\Reader')) { return null; }
		try
		{
			$reader = new MaxMind\Db\Reader($dbPath);
			$found = $reader->get($ip);
			$reader->close();
		}
		catch(Throwable $e)
		{
			return null;
		}
		return self::CodeIn($found);
	}

	/** The country code in a database's answer: country.iso_code (DB-IP's and MaxMind's) or country_code (IPinfo's). */
	public static function CodeIn($found)
	{
		$code = null;
		if(is_array($found) && isset($found['country']['iso_code'])) { $code = $found['country']['iso_code']; }
		elseif(is_array($found) && isset($found['country_code'])) { $code = $found['country_code']; }
		$code = is_string($code) ? strtoupper($code) : '';
		return preg_match('/^[A-Z]{2}$/', $code) ? $code : null;
	}

	/**
	 * Fetches a new database when the one at $dbPath is missing or older than MAX_AGE_DAYS, checks it opens and knows a
	 * country, and only then puts it in place, in one step: a fetch that fails leaves the one there as it was.
	 * @param string $source where from (config's geoip_country_source; SOURCE when empty): %s in it is the month, and
	 *        then last month's is tried too, early in a month before the new one is out; .gz is unpacked
	 * @param callable|null $fetch function($url, $file) that saves $url to $file and returns true (tests)
	 * @return string what was done
	 * @throws RuntimeException when no database could be fetched
	 */
	public static function Update($dbPath, $source = '', $now = null, $fetch = null, $force = false)
	{
		$now = $now === null ? time() : $now;
		if(!$force && is_file($dbPath) && filemtime($dbPath) > $now - self::MAX_AGE_DAYS * 86400) { return "up to date"; }
		$dir = dirname($dbPath);
		if(!is_dir($dir) && !@mkdir($dir, 0755, true)) { throw new RuntimeException("Can't make $dir"); }
		$source = (is_string($source) && $source !== '') ? $source : self::SOURCE;
		$fetch = $fetch ?: array(__CLASS__, 'Download');
		$urls = (strpos($source, '%s') === false) ? array($source)
			: array(sprintf($source, gmdate('Y-m', $now)), sprintf($source, gmdate('Y-m', strtotime(gmdate('Y-m-01', $now).' -1 month'))));
		$tried = array();
		foreach($urls as $url)
		{
			$gz = $dbPath.'.download';
			$part = $dbPath.'.part';
			@unlink($gz);
			@unlink($part);
			try
			{
				if(!call_user_func($fetch, $url, $gz)) { $tried[] = "$url: not fetched"; continue; }
				if(preg_match('/\.gz($|\?)/', $url)) { self::Gunzip($gz, $part); }
				elseif(!rename($gz, $part)) { throw new RuntimeException("can't move it"); }
				if(filesize($part) < self::MIN_BYTES) { throw new RuntimeException("too small to be the database"); }
				$reader = new MaxMind\Db\Reader($part);   # throws if it isn't one
				$type = $reader->metadata()->databaseType;
				$known = self::CodeIn($reader->get('8.8.8.8'));   # a database that can't place Google's resolver isn't a country database
				$reader->close();
				if($known === null) { throw new RuntimeException("it doesn't say countries ($type)"); }
				if(!rename($part, $dbPath)) { throw new RuntimeException("can't put it at $dbPath"); }
				return "fetched $url ($type)";
			}
			catch(Throwable $e)
			{
				$tried[] = "$url: ".$e->getMessage();
			}
			finally
			{
				@unlink($gz);
				@unlink($part);
			}
		}
		throw new RuntimeException("No country database could be fetched: ".implode('; ', $tried));
	}

	private static function Gunzip($from, $to)
	{
		$in = gzopen($from, 'rb');
		$out = fopen($to, 'wb');
		if(!$in || !$out) { throw new RuntimeException("can't unpack it"); }
		while(!gzeof($in))
		{
			$chunk = gzread($in, 1048576);
			if($chunk === false) { throw new RuntimeException("can't unpack it"); }
			fwrite($out, $chunk);
		}
		gzclose($in);
		fclose($out);
	}

	/** @return bool whether $url was saved to $file */
	public static function Download($url, $file)
	{
		$out = fopen($file, 'wb');
		$ch = curl_init($url);
		curl_setopt_array($ch, array(CURLOPT_FILE => $out, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3, CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT => 600, CURLOPT_FAILONERROR => true, CURLOPT_USERAGENT => 'WifiDB'));
		$ok = curl_exec($ch);
		fclose($out);
		return $ok !== false;
	}
}
