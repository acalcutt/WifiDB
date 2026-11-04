<?php
/*
geonames.inc.php, the GeoNames places nearest a point: an access point's "places nearby" (export::GeoNamesArray()) and
api/geonames.php (api::GeoNames()). Every kind of place is kept (towns, but also the hills, lakes and buildings that
say where something is).
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class GeoNamesNearby
{
	/**
	 * How far to look, in degrees of latitude either side of the point: the first box with anything in it is used, so a
	 * city answers from its own few kilometres and an empty desert still finds the nearest place within a few hundred.
	 */
	const BOXES = array(0.1, 0.5, 2.0);

	/**
	 * @param PDO $conn
	 * @param float $lat decimal degrees
	 * @param float $lon decimal degrees
	 * @return array the places, nearest first, $offset on and at most $limit of them: id, asciiname, country_code,
	 *   admin1_code, admin2_code, timezone, latitude, longitude, miles and kilometers
	 */
	public static function Nearest(PDO $conn, $lat, $lon, $limit = 10, $offset = 0)
	{
		$lat = (float)$lat; $lon = (float)$lon;
		$prep = $conn->prepare("SELECT id, asciiname, country_code, admin1_code, admin2_code, timezone, latitude, longitude, lat, lon FROM geonames"
			." WHERE lat BETWEEN ? AND ? AND lon BETWEEN ? AND ?");
		$rows = array();
		foreach(self::BOXES as $d)
		{
			# a degree of longitude shrinks towards the poles, so the box is as wide as it is tall on the ground
			$dlon = min(180.0, $d / max(0.01, cos(deg2rad($lat))));
			# as plain decimals: SQL Server won't read 1.4E-17 as a decimal
			$prep->execute(array_map(function ($v) { return sprintf('%.6F', $v); }, array($lat - $d, $lat + $d, $lon - $dlon, $lon + $dlon)));
			$rows = $prep->fetchAll(PDO::FETCH_ASSOC);
			if($rows) { break; }
		}
		foreach($rows as &$row)
		{
			$row = array_change_key_case($row, CASE_LOWER);
			$km = self::Kilometers($lat, $lon, (float)$row['lat'], (float)$row['lon']);
			$row['kilometers'] = $km;
			$row['miles'] = $km / 1.609344;
			unset($row['lat'], $row['lon']);
		}
		unset($row);
		usort($rows, function ($a, $b) { return $a['kilometers'] <=> $b['kilometers']; });
		return array_slice($rows, (int)$offset, (int)$limit);
	}

	/** the great-circle distance between two points, in kilometres */
	public static function Kilometers($lat1, $lon1, $lat2, $lon2)
	{
		$a = sin(deg2rad($lat2 - $lat1) / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($lon2 - $lon1) / 2) ** 2;
		return 6371.0 * 2 * atan2(sqrt($a), sqrt(1 - $a));
	}
}
