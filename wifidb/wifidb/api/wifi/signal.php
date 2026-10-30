<?php
// signal.png and signal.svg: the signal history graph of one access point, straight from an address, so it works in an
// <img> tag, a forum post or a link. It takes Vistumbler's own parameters (SSID, Mac, Manuf, Auth, Encry, radio, Chn, Lat,
// Long, BTx, OTx, FA, LU, NT, Label, Sig), plus:
//   type=bar|line   text=, bg=, line=   colors as rrr:ggg:bbb, or "rand"
//   format=png|svg  (signal.png and signal.svg set it)
// Nothing is stored: the same address gives the same picture, so it is cached.
require dirname(__DIR__, 2) . '/lib/signalgraph.inc.php';

$format = (isset($_GET['format']) && $_GET['format'] === 'svg') ? 'svg' : 'png';

$spec = SignalGraph::FromQuery($_GET);

if($format === 'png' && !SignalGraph::GdAvailable())
{
	// no GD on this server: the SVG is the same picture
	$format = 'svg';
}

header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=3600');
header('Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'');

if($format === 'svg')
{
	header('Content-Type: image/svg+xml; charset=utf-8');
	echo SignalGraph::Svg($spec);
	exit;
}

header('Content-Type: image/png');
echo SignalGraph::Png($spec);
