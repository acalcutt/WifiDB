<?php
/*
traffic.php, the site's own traffic (lib/traffic.inc.php): page views and unique visitors by hour, day and month, the
average for each day of the week, this month's countries and pages, and what people did (sign-ups, sign-ins, uploads,
imports). Counted by the site on every node and added up in the database each minute (tools/cron/traffic), so with
several nodes it's the whole site. Needs admin mode.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

global $switches;
$switches = array('screen'=>"HTML",'extras'=>'');
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "cp");

include('../lib/init.inc.php');
$dbcore->sec->RequireAdminMode('admin/traffic.php');
require_once '../lib/traffic.inc.php';

$conn = $dbcore->sql->conn;
$now = time();
$error = '';
$data = array('hours' => array(), 'days' => array(), 'months' => array());
$weekdays = $countries = $pages = $events = array();
try
{
	$data['hours'] = Traffic::History($conn, 'site', '', 'hour', 48, $now);
	$data['days'] = Traffic::History($conn, 'site', '', 'day', 60, $now);
	$data['months'] = Traffic::History($conn, 'site', '', 'month', 24, $now);
	$month = Traffic::PeriodStart($now - $now % 3600, 'month');
	$countries = Traffic::Top($conn, 'country', 'month', $month, 60);
	$pages = Traffic::Top($conn, 'page', 'month', $month, 40);

	# each day of the week's average over the last 60 days, today's left out (it isn't over)
	$sum = array_fill(0, 7, array(0, 0, 0));
	foreach(array_slice($data['days'], 1) as $day)
	{
		$dow = (int)gmdate('w', $day[0]);
		$sum[$dow][0] += $day[1];
		$sum[$dow][1] += $day[2];
		$sum[$dow][2]++;
	}
	foreach(array(1, 2, 3, 4, 5, 6, 0) as $dow)
	{
		$n = max(1, $sum[$dow][2]);
		$weekdays[] = array('day' => gmdate('l', strtotime("Sunday +$dow days")), 'visitors' => round($sum[$dow][0] / $n), 'pageviews' => round($sum[$dow][1] / $n));
	}

	$spans = array(1 => Traffic::EventTotals($conn, 1, $now), 7 => Traffic::EventTotals($conn, 7, $now), 30 => Traffic::EventTotals($conn, 30, $now));
	foreach(Traffic::EVENTS as $name => $label) { $events[] = array('label' => $label, 'day' => $spans[1][$name], 'week' => $spans[7][$name], 'month' => $spans[30][$name]); }
}
catch(Throwable $e)
{
	$dbcore->sec->logd("Traffic page: ".$e->getMessage(), "error");
	$error = 'The traffic counts could not be read. If you have not run the database migrations yet, run tools/utilites/migrate.php.';
}

# the charts' data, oldest first, for the page's script
$chart = array();
foreach(array('hours' => 'D H:00', 'days' => 'M j', 'months' => 'M Y') as $span => $format)
{
	$chart[$span] = array('labels' => array(), 'visitors' => array(), 'pageviews' => array());
	foreach(array_reverse($data[$span]) as $row)
	{
		$chart[$span]['labels'][] = gmdate($format, $row[0]);
		$chart[$span]['visitors'][] = $row[1];
		$chart[$span]['pageviews'][] = $row[2];
	}
}
$table = function ($rows, $format) {
	return array_map(function ($r) use ($format) { return array('period' => gmdate($format, $r[0]), 'visitors' => $r[1], 'pageviews' => $r[2]); }, $rows);
};

$dbcore->smarty->assign('wifidb_page_label', 'Traffic');
$dbcore->smarty->assign('traffic_error', $error);
$dbcore->smarty->assign('traffic_chart_json', json_encode($chart, JSON_HEX_TAG | JSON_HEX_AMP));
$dbcore->smarty->assign('traffic_days', $table($data['days'], 'D Y-m-d'));
$dbcore->smarty->assign('traffic_months', $table($data['months'], 'F Y'));
$dbcore->smarty->assign('traffic_weekdays', $weekdays);
$dbcore->smarty->assign('traffic_countries', $countries);
$dbcore->smarty->assign('traffic_pages', $pages);
$dbcore->smarty->assign('traffic_events', $events);
$dbcore->smarty->assign('traffic_month_label', gmdate('F Y', $now));
$dbcore->smarty->display('admin_traffic.tpl');
