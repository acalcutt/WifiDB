<?php
/*
searchconditions.inc.php, the AP search's WHERE clause, shared by export::SearchArray() (the search page, its map and
its exports) and api::Search() (api/search.php).
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class WifidbApSearch
{
	/** how a field matches: equals is the one an index can use; contains and ends need a leading wildcard */
	const OPS = array('equals', 'contains', 'starts', 'ends');

	/** the search's fields (as the request names them) => wifi_ap's columns */
	const FIELDS = array('ssid' => 'SSID', 'mac' => 'BSSID', 'radio' => 'RADTYPE', 'chan' => 'CHAN', 'auth' => 'AUTH', 'encry' => 'ENCR');

	/** @return array field => its match, from the request's ssid_op, mac_op, ...; $default for one missing or not in OPS */
	public static function Ops(array $request, $default = 'equals')
	{
		$ops = array();
		foreach(array_keys(self::FIELDS) as $field)
		{
			$op = isset($request[$field.'_op']) ? (string)$request[$field.'_op'] : '';
			$ops[$field] = in_array($op, self::OPS, true) ? $op : $default;
		}
		return $ops;
	}

	/** @return string the ops as query parameters ("&ssid_op=equals&..."), for links that repeat the search */
	public static function OpsQuery(array $ops)
	{
		$out = '';
		foreach(array_keys(self::FIELDS) as $field)
		{
			if(isset($ops[$field]) && in_array($ops[$field], self::OPS, true)) { $out .= '&'.$field.'_op='.$ops[$field]; }
		}
		return $out;
	}

	/**
	 * The search's conditions on wifi_ap. A blank field is left out rather than matched with LIKE '%%', which still has
	 * to be read for every row; a value with a % in it is a LIKE pattern as typed, whatever its op.
	 * @param string $service mysql, sqlsrv or pgsql
	 * @param string $table what the query calls wifi_ap ("wap", "wifi_ap")
	 * @param array $values field => value
	 * @param array $ops field => op, as Ops() gives them
	 * @return array ['where' => [conditions], 'params' => [values to bind, in order]]
	 */
	public static function Conditions($service, $table, array $values, array $ops)
	{
		$where = array();
		$params = array();
		foreach(self::FIELDS as $field => $column)
		{
			$value = trim((string)($values[$field] ?? ''));
			if($value === '') { continue; }
			$col = ($service === 'pgsql') ? $table.'."'.$column.'"' : $table.'.'.$column;
			if($column === 'CHAN')
			{
				# an integer column: compared as text, so a value that isn't a number finds nothing rather than failing
				if($service === 'pgsql') { $col .= '::text'; }
				elseif($service === 'sqlsrv') { $col = 'CAST('.$col.' AS varchar(11))'; }
			}
			$op = $ops[$field] ?? 'equals';
			if(strpos($value, '%') !== false) { $where[] = "$col LIKE ?"; $params[] = $value; }
			elseif($op === 'contains') { $where[] = "$col LIKE ?"; $params[] = '%'.$value.'%'; }
			elseif($op === 'starts') { $where[] = "$col LIKE ?"; $params[] = $value.'%'; }
			elseif($op === 'ends') { $where[] = "$col LIKE ?"; $params[] = '%'.$value; }
			else { $where[] = "$col = ?"; $params[] = $value; }
		}
		return array('where' => $where, 'params' => $params);
	}
}
