<?php
/*
live_session.inc.php, the live API's version 2 sessions (api/live.php). Vistumbler starts one with the member's
username and API key and a title and notes; each live access point it sends with the session's ID is that member's,
whatever username comes with it; and when it finishes the session, what was sent becomes an import of the member's,
as a VS1 file on the import queue, so it goes into WiFiDB as an upload would. A session ID WiFiDB didn't hand out is
version 1's, made up by the client, and works as it always has.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class LiveSession
{
	/** what live.php?LiveVersion= answers: 2 has sessions (Vistumbler's _StartWifiDBAutoUpload) */
	const VERSION = 2;

	/** who a session without an account is (Vistumbler's name for a member who left the username empty) */
	const ANONYMOUS = 'AnonCoward';

	private $conn;
	private $service;

	public function __construct(PDO $conn, $service)
	{
		$this->conn = $conn;
		$this->service = $service;
	}

	/** a column name for this database: live_gps.long and live_signals.signal are reserved words in MariaDB */
	private function Q($name)
	{
		if($this->service === 'mysql') { return '`'.$name.'`'; }
		if($this->service === 'sqlsrv') { return '['.$name.']'; }
		return '"'.$name.'"';
	}

	/** Starts a session for $username (an account's, already checked, or ANONYMOUS). @return string its ID */
	public function Start($username, $title, $notes)
	{
		$session_id = bin2hex(random_bytes(32));
		$title = mb_substr(trim((string)$title) !== '' ? trim((string)$title) : gmdate('Y-m-d H:i:s'), 0, 255);
		$notes = mb_substr((string)$notes, 0, 255);
		$this->conn->prepare("INSERT INTO live_titles (username, title, notes, session_id, started_at) VALUES (?, ?, ?, ?, ?)")
			->execute(array($username, $title, $notes, $session_id, gmdate('Y-m-d H:i:s')));
		$title_id = (int)$this->conn->lastInsertId();
		$this->conn->prepare("INSERT INTO live_users (username, session_id, title_id) VALUES (?, ?, ?)")
			->execute(array($username, $session_id, $title_id));
		return $session_id;
	}

	/** @return array|null a version 2 session: username, title_id, title, notes, started_at, completed_at */
	public function Find($session_id)
	{
		if(!preg_match('/^[0-9a-f]{64}$/', (string)$session_id)) { return null; }
		$prep = $this->conn->prepare("SELECT u.username, u.title_id, t.title, t.notes, t.started_at, t.completed_at FROM live_users u"
			." JOIN live_titles t ON t.id = u.title_id WHERE u.session_id = ? AND t.started_at IS NOT NULL");
		$prep->execute(array($session_id));
		$row = $prep->fetch(PDO::FETCH_ASSOC);
		return $row ? array_change_key_case($row, CASE_LOWER) : null;
	}

	public function Finished($title_id)
	{
		$this->conn->prepare("UPDATE live_titles SET completed_at = ? WHERE id = ?")->execute(array(gmdate('Y-m-d H:i:s'), (int)$title_id));
	}

	/**
	 * What a session sent, as a Vistumbler VS1 (Detailed Export Version 4.0) for the importer: its GPS points, and each
	 * access point with its readings at them.
	 * @return array [the file's text, how many access points]
	 */
	public function Vs1($session_id, $created_by = 'WiFiDB live API')
	{
		$clean = function ($v) { return str_replace(array('|', "\r", "\n"), '', (string)$v); };
		$out = "# Vistumbler VS1 - Detailed Export Version 4.0\r\n# Created By: ".$clean($created_by)."\r\n"
			."# GpsID|Latitude|Longitude|NumOfSatalites|HorizontalDilutionOfPrecision|Altitude(m)|HeightOfGeoidAboveWGS84Ellipsoid(m)|Speed(km/h)|Speed(MPH)|TrackAngle(Deg)|Date(UTC y-m-d)|Time(UTC h:m:s.ms)\r\n";

		# the GPS points, numbered from 1 in the order they came
		$gps = array();
		$prep = $this->conn->prepare("SELECT id, lat, ".$this->Q('long')." AS lon, sats, hdp, alt, geo, kmh, mph, track, hist_date FROM live_gps WHERE session_id = ? ORDER BY id");
		$prep->execute(array($session_id));
		foreach($prep->fetchAll(PDO::FETCH_ASSOC) as $g)
		{
			$g = array_change_key_case($g, CASE_LOWER);
			$gps[(string)$g['id']] = $n = count($gps) + 1;
			$when = preg_split('/[ T]/', substr((string)$g['hist_date'], 0, 23), 2);
			$out .= implode('|', array($n, $clean($g['lat']), $clean($g['lon']), (int)$g['sats'], $clean($g['hdp']), $clean($g['alt']), $clean($g['geo']),
				$clean($g['kmh']), $clean($g['mph']), $clean($g['track']), $when[0], $when[1] ?? '00:00:00.000'))."\r\n";
		}

		$out .= "# SSID|BSSID|MANUFACTURER|Authentication|Encryption|Security Type|Radio Type|Channel|Basic Transfer Rates|Other Transfer Rates|High Signal|High RSSI|Network Type|Label|GID,SIGNAL,RSSI\r\n";
		$prep = $this->conn->prepare("SELECT ssid, mac, auth, encry, sectype, radio, chan, ".$this->Q('BTx')." AS btx, ".$this->Q('OTx')." AS otx, "
			.$this->Q('NT')." AS nt, ".$this->Q('Label')." AS label, sig FROM live_aps WHERE session_id = ? ORDER BY id");
		$prep->execute(array($session_id));
		$aps = array_map(function ($a) { return array_change_key_case($a, CASE_LOWER); }, $prep->fetchAll(PDO::FETCH_ASSOC));

		# each access point's readings: its sig is "signal id|gps id~signal id|gps id..."
		$readings = array();
		foreach($aps as $i => $a)
		{
			foreach(explode('~', (string)$a['sig']) as $pair)
			{
				$p = explode('|', $pair);
				if(count($p) === 2 && ctype_digit($p[0]) && isset($gps[$p[1]])) { $readings[$i][] = array($p[0], $gps[$p[1]]); }
			}
		}
		$signals = array();
		$ids = array();
		foreach($readings as $list) { foreach($list as $r) { $ids[] = (int)$r[0]; } }
		foreach(array_chunk(array_unique($ids), 500) as $chunk)
		{
			$prep = $this->conn->prepare("SELECT id, ".$this->Q('signal')." AS sig, rssi FROM live_signals WHERE id IN (".implode(',', array_fill(0, count($chunk), '?')).")");
			$prep->execute($chunk);
			foreach($prep->fetchAll(PDO::FETCH_ASSOC) as $s) { $s = array_change_key_case($s, CASE_LOWER); $signals[(string)$s['id']] = $s; }
		}

		$count = 0;
		foreach($aps as $i => $a)
		{
			$list = array();
			$high = null;
			$high_rssi = null;
			foreach($readings[$i] ?? array() as $r)
			{
				if(!isset($signals[$r[0]])) { continue; }
				$sig = (int)$signals[$r[0]]['sig'];
				$rssi = (int)$signals[$r[0]]['rssi'];
				$list[] = $r[1].','.$sig.','.$rssi;
				$high = max($high ?? $sig, $sig);
				$high_rssi = max($high_rssi ?? $rssi, $rssi);
			}
			if(!$list) { continue; }
			$out .= implode('|', array($clean($a['ssid']), $clean($a['mac']), '', $clean($a['auth']), $clean($a['encry']), (int)$a['sectype'], $clean($a['radio']),
				(int)$a['chan'], $clean($a['btx']), $clean($a['otx']), $high, $high_rssi, $clean($a['nt']), $clean($a['label']), implode('\\', $list)))."\r\n";
			$count++;
		}
		return array($out, $count);
	}
}
