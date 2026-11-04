<?php
/*
position.inc.php, where an access point (or a cell tower or Bluetooth device) most likely is, from all its readings
rather than the single strongest one.

Each reading is where the scanner was and how strongly it heard the device. The estimate is the weighted centre of the
strongest readings: those within WINDOW_DB of the strongest, at most MAX_READINGS of them. A reading counts for more
the stronger it was (10^(dBm/20): 6 dB stronger is twice the weight) and the better its fix was (accuracy in metres,
else the HDOP; a fix worse than MAX_FIX_METERS is only used when there is nothing better). The accuracy given back is
how far, in metres, the readings used are from that centre (weighted, root mean square), plus how good their fixes
were: a rough radius the device is likely within.

ApPosition::Estimate() does the arithmetic on readings already in hand; ApPosition::Update() reads them from the
database and stores the result in wifi_ap's (or cell_id's) est_lat, est_lon, est_alt, est_accuracy and est_points.
Latitude and longitude are kept as the GPS tables keep them, NMEA degrees and minutes (DDMM.MMMM).

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class ApPosition
{
	const WINDOW_DB = 15;         // readings this much weaker than the strongest still count
	const MAX_READINGS = 50;      // at most this many of the strongest
	const MAX_FIX_METERS = 100;   // fixes worse than this are left out when there are better ones
	const EARTH_METERS = 6371000;

	private $conn;
	private $service;

	/** @param PDO $conn @param string $service mysql, sqlsrv or pgsql */
	public function __construct($conn, $service)
	{
		$this->conn = $conn;
		$this->service = $service;
	}

	// ── The arithmetic ──────────────────────────────────────────────────────────────────────────────

	/**
	 * @param array $readings each: lat, lon (decimal degrees), dbm (signal strength; null if unknown), and optionally
	 *                        accuracy (metres), hdop, alt
	 * @return array|null lat, lon (decimal degrees), alt (or null), accuracy (metres, or null for one reading with no
	 *                    accuracy of its own), points (readings used); null with no usable reading
	 */
	public static function Estimate(array $readings)
	{
		$usable = array();
		foreach($readings as $r)
		{
			if(!isset($r['lat'], $r['lon'])) { continue; }
			$lat = (float)$r['lat'];
			$lon = (float)$r['lon'];
			if(($lat == 0.0 && $lon == 0.0) || abs($lat) > 90 || abs($lon) > 180) { continue; }
			$r['lat'] = $lat;
			$r['lon'] = $lon;
			$r['fix'] = self::FixMeters($r);
			$usable[] = $r;
		}
		if(!$usable) { return null; }

		// good fixes, when there are any
		$good = array_values(array_filter($usable, function ($r) { return $r['fix'] === null || $r['fix'] <= self::MAX_FIX_METERS; }));
		if($good) { $usable = $good; }

		// the strongest, and those within WINDOW_DB of it (readings without a strength count as the weakest)
		$strongest = null;
		foreach($usable as $r) { if(isset($r['dbm']) && ($strongest === null || $r['dbm'] > $strongest)) { $strongest = (float)$r['dbm']; } }
		usort($usable, function ($a, $b) {
			$sa = isset($a['dbm']) ? (float)$a['dbm'] : -1000.0;
			$sb = isset($b['dbm']) ? (float)$b['dbm'] : -1000.0;
			return $sb <=> $sa;
		});
		$used = array();
		foreach($usable as $r)
		{
			if($strongest !== null && (!isset($r['dbm']) || (float)$r['dbm'] < $strongest - self::WINDOW_DB)) { continue; }
			$used[] = $r;
			if(count($used) >= self::MAX_READINGS) { break; }
		}
		if(!$used) { $used = array($usable[0]); }

		// weights: signal (amplitude), then the fix's quality
		$weights = array();
		foreach($used as $i => $r)
		{
			$w = ($strongest !== null && isset($r['dbm'])) ? pow(10, ((float)$r['dbm'] - $strongest) / 20) : 1.0;
			$weights[$i] = $w / (1 + (($r['fix'] !== null) ? $r['fix'] : 10) / 10);
		}
		$total = array_sum($weights);

		// the centre: a weighted mean of unit vectors, so it works across the 180th meridian and near the poles
		$x = $y = $z = 0.0;
		$alt = 0.0; $altWeight = 0.0;
		foreach($used as $i => $r)
		{
			$la = deg2rad($r['lat']); $lo = deg2rad($r['lon']); $w = $weights[$i] / $total;
			$x += $w * cos($la) * cos($lo);
			$y += $w * cos($la) * sin($lo);
			$z += $w * sin($la);
			if(isset($r['alt']) && $r['alt'] !== '' && $r['alt'] !== null) { $alt += $w * (float)$r['alt']; $altWeight += $w; }
		}
		$lat = rad2deg(atan2($z, sqrt($x * $x + $y * $y)));
		$lon = rad2deg(atan2($y, $x));

		// how far the readings are from it, and how good their fixes were
		$spread = 0.0; $fix = 0.0; $fixWeight = 0.0;
		foreach($used as $i => $r)
		{
			$w = $weights[$i] / $total;
			$d = self::Meters($lat, $lon, $r['lat'], $r['lon']);
			$spread += $w * $d * $d;
			if($r['fix'] !== null) { $fix += $w * $r['fix']; $fixWeight += $w; }
		}
		$accuracy = sqrt($spread) + ($fixWeight > 0 ? $fix / $fixWeight : 0.0);
		if(count($used) === 1 && $fixWeight == 0) { $accuracy = null; }

		return array(
			'lat' => $lat,
			'lon' => $lon,
			'alt' => $altWeight > 0 ? $alt / $altWeight : null,
			'accuracy' => $accuracy === null ? null : round($accuracy, 2),
			'points' => count($used),
		);
	}

	/** How good a fix was, in metres: its own accuracy, else roughly 5 m per unit of HDOP; null if neither is known */
	private static function FixMeters($r)
	{
		if(isset($r['accuracy']) && (float)$r['accuracy'] > 0) { return (float)$r['accuracy']; }
		if(isset($r['hdop']) && (float)$r['hdop'] > 0) { return (float)$r['hdop'] * 5; }
		return null;
	}

	/** Great-circle distance in metres */
	public static function Meters($lat1, $lon1, $lat2, $lon2)
	{
		$p1 = deg2rad($lat1); $p2 = deg2rad($lat2);
		$a = pow(sin(($p2 - $p1) / 2), 2) + cos($p1) * cos($p2) * pow(sin(deg2rad($lon2 - $lon1) / 2), 2);
		return 2 * self::EARTH_METERS * atan2(sqrt($a), sqrt(1 - $a));
	}

	/** NMEA degrees and minutes (DDMM.MMMM, as wifi_gps keeps them) to decimal degrees */
	public static function DmToDd($dm)
	{
		$dm = (float)$dm;
		$sign = $dm < 0 ? -1 : 1;
		$dm = abs($dm);
		$degrees = floor($dm / 100);
		return $sign * ($degrees + ($dm - $degrees * 100) / 60);
	}

	/** Decimal degrees to NMEA degrees and minutes, four places as stored */
	public static function DdToDm($dd)
	{
		$sign = $dd < 0 ? '-' : '';
		$dd = abs((float)$dd);
		$degrees = floor($dd);
		$minutes = round(($dd - $degrees) * 60, 4);
		if($minutes >= 60) { $degrees += 1; $minutes -= 60; }
		return $sign.number_format($degrees * 100 + $minutes, 4, '.', '');
	}

	// ── From and to the database ────────────────────────────────────────────────────────────────────

	private function Q($name) { return $this->service === 'pgsql' ? '"'.$name.'"' : $name; }

	/**
	 * The readings with a fix for one access point ($cell false) or cell tower / Bluetooth device ($cell true), in the
	 * form Estimate() takes. A reading's strength is its RSSI in dBm; an access point's reading with no RSSI uses its
	 * signal percentage, turned into dBm the way Vistumbler does.
	 */
	public function Readings($id, $cell = false)
	{
		if($cell)
		{
			$sql = "SELECT g.".$this->Q('Lat')." AS lat, g.".$this->Q('Lon')." AS lon, g.".$this->Q('Alt')." AS alt, g.".$this->Q('AccuracyMeters')." AS accuracy,"
				." g.".$this->Q('HorDilPitch')." AS hdop, h.rssi AS rssi, NULL AS sig"
				." FROM cell_hist h INNER JOIN wifi_gps g ON g.".$this->Q('GPS_ID')." = h.gps_id WHERE h.cell_id = ?";
		}
		else
		{
			$sql = "SELECT g.".$this->Q('Lat')." AS lat, g.".$this->Q('Lon')." AS lon, g.".$this->Q('Alt')." AS alt, g.".$this->Q('AccuracyMeters')." AS accuracy,"
				." g.".$this->Q('HorDilPitch')." AS hdop, h.".$this->Q('RSSI')." AS rssi, h.".$this->Q('Sig')." AS sig"
				." FROM wifi_hist h INNER JOIN wifi_gps g ON g.".$this->Q('GPS_ID')." = h.".$this->Q('GPS_ID')." WHERE h.".$this->Q('AP_ID')." = ?";
		}
		$prep = $this->conn->prepare($sql);
		$prep->execute(array($id));
		$out = array();
		while($row = $prep->fetch(PDO::FETCH_ASSOC))
		{
			$row = array_change_key_case($row, CASE_LOWER);
			$lat = (float)$row['lat']; $lon = (float)$row['lon'];
			if($lat == 0.0 && $lon == 0.0) { continue; }
			$dbm = null;
			if($row['rssi'] !== null && $row['rssi'] !== '' && (int)$row['rssi'] < 0) { $dbm = (int)$row['rssi']; }
			elseif($row['sig'] !== null && $row['sig'] !== '' && (int)$row['sig'] > 0) { $dbm = self::SigToDbm((int)$row['sig']); }
			$out[] = array(
				'lat' => self::DmToDd($lat), 'lon' => self::DmToDd($lon), 'dbm' => $dbm,
				'alt' => $row['alt'], 'accuracy' => $row['accuracy'], 'hdop' => $row['hdop'],
			);
		}
		return $out;
	}

	/** Vistumbler's signal percentage to dBm: convert.inc.php's Sig2dBm, with config.inc.php's default -30 and -85 dBm */
	public static function SigToDbm($sig, $max = -30, $dissociation = -85)
	{
		return (int)round(((($max - $dissociation) * (float)$sig) - (20 * $max) + (100 * $dissociation)) / 80);
	}

	/**
	 * The access points and cell towers / Bluetooth devices an import has readings for: what to work out again after
	 * its readings are taken away (a reset or a deleted import), with UpdateAll().
	 * @return array ap => ids, cell => ids
	 */
	public function Touched($file_id)
	{
		$ap = $this->conn->prepare("SELECT DISTINCT ".$this->Q('AP_ID')." FROM wifi_hist WHERE ".$this->Q('File_ID')." = ?");
		$ap->execute(array($file_id));
		$cell = $this->conn->prepare("SELECT DISTINCT cell_id FROM cell_hist WHERE file_id = ?");
		$cell->execute(array($file_id));
		return array('ap' => array_map('intval', $ap->fetchAll(PDO::FETCH_COLUMN)), 'cell' => array_map('intval', $cell->fetchAll(PDO::FETCH_COLUMN)));
	}

	/** Update() for each of Touched()'s ids (one that has since been deleted changes nothing). @return int how many */
	public function UpdateAll(array $touched)
	{
		$n = 0;
		foreach(array('ap' => false, 'cell' => true) as $kind => $cell)
		{
			foreach(isset($touched[$kind]) ? $touched[$kind] : array() as $id) { $this->Update($id, $cell); $n++; }
		}
		return $n;
	}

	/**
	 * Works out one device's position and stores it. With no reading that has a fix, the columns are emptied.
	 * @return array|null what was stored (lat and lon in DDMM.MMMM), or null if there was nothing to go on
	 */
	public function Update($id, $cell = false)
	{
		$est = self::Estimate($this->Readings($id, $cell));
		$row = $est === null ? array('lat' => null, 'lon' => null, 'alt' => null, 'accuracy' => null, 'points' => null) : array(
			'lat' => self::DdToDm($est['lat']),
			'lon' => self::DdToDm($est['lon']),
			'alt' => $est['alt'] === null ? null : round($est['alt'], 2),
			'accuracy' => $est['accuracy'],
			'points' => $est['points'],
		);
		$table = $cell ? 'cell_id' : 'wifi_ap';
		$key = $cell ? 'cell_id' : $this->Q('AP_ID');
		// (MariaDB would otherwise stamp ModDate: the position is worked out, not a change to the access point)
		$keep = ($this->service === 'mysql') ? ', ModDate = ModDate' : '';
		$prep = $this->conn->prepare("UPDATE $table SET est_lat = ?, est_lon = ?, est_alt = ?, est_accuracy = ?, est_points = ?$keep WHERE $key = ?");
		$prep->execute(array($row['lat'], $row['lon'], $row['alt'], $row['accuracy'], $row['points'], $id));
		return $est === null ? null : $row;
	}
}
