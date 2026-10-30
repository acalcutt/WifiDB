<?php
// signal.png and signal.svg: the signal history graph of one access point, straight from an address, so it works in an
// <img> tag, a forum post or a link. Two ways to say which access point:
//   ?ap=123    an access point in this database: its details and its recorded signal history (public, like the details pages)
//   Vistumbler's own parameters: SSID, Mac, Manuf, Auth, Encry, radio, Chn, Lat, Long, BTx, OTx, FA, LU, NT, Label, Sig
//              (Sig: whole numbers 0-100 joined by dashes, oldest first)
// plus, for either:
//   type=bar|line   text=, bg=, line=   colors as rrr:ggg:bbb, or "rand"
//   format=png|svg  (signal.png and signal.svg set it)
// Nothing is stored. A graph from Vistumbler's parameters is always the same picture for the same address; one from
// ?ap= follows the database, so it is cached for a few minutes.
$lib = dirname(__DIR__, 2) . '/lib';
require $lib . '/helpers.inc.php';
require $lib . '/signalgraph.inc.php';

$format = (isset($_GET['format']) && $_GET['format'] === 'svg') ? 'svg' : 'png';
$spec = SignalGraph::FromQuery($_GET);
$maxAge = 3600;

if(isset($_GET['ap']))
{
	$maxAge = 300;
	$data = null;
	try
	{
		if(!is_file($lib . '/config.inc.php')) { throw new RuntimeException('no config'); }
		require $lib . '/config.inc.php';
		$data = SignalGraph::DataForAp(wifidb_pdo_from_config($config, 3), $config['srvc'], $_GET['ap']);
	}
	catch(Throwable $e)
	{
		http_response_code(503);
		header('Content-Type: text/plain; charset=utf-8');
		echo "The signal history is not available right now.\n";
		exit;
	}
	if($data === null)
	{
		http_response_code(404);
		header('Content-Type: text/plain; charset=utf-8');
		echo "No such access point.\n";
		exit;
	}
	$spec = array_replace($spec, $data);
}

if($format === 'png' && !SignalGraph::GdAvailable())
{
	// no GD on this server: the SVG is the same picture
	$format = 'svg';
}

header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=' . $maxAge);
header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'');

if($format === 'svg')
{
	header('Content-Type: image/svg+xml; charset=utf-8');
	echo SignalGraph::Svg($spec);
	exit;
}

header('Content-Type: image/png');
echo SignalGraph::Png($spec);
