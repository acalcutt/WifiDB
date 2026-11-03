<?php
// robots.txt, ads.txt and the sitemaps (lib/site_files.inc.php), which the .htaccess beside this sends here. Like
// /health/ready it doesn't load the whole application: crawlers ask for these often, and they need only Site Settings
// and the database.
$lib = __DIR__ . '/lib';
require $lib . '/helpers.inc.php';
require $lib . '/config.inc.php';
require_once $lib . '/site_settings.inc.php';
require_once $lib . '/site_files.inc.php';

// which one (the .htaccess's ?file=), and where it was asked for: the address the browser used, as a rewrite keeps it
$path = (string)parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH);
$file = (isset($_GET['file']) && is_string($_GET['file'])) ? $_GET['file'] : '';

try { $conn = wifidb_pdo_from_config($config, 5); }
catch(Throwable $e) { $conn = null; }   # the settings come from config.inc.php then, and the sitemaps list only the pages

$site = $config['hosturl'] . $config['root'] . '/';
// these files are where they were asked for: the top of the host, or the site's own folder
$origin = preg_replace('#^(https?://[^/]+).*$#i', '$1', $site);
$folder = rtrim(str_replace('\\', '/', dirname($path)), '/') . '/';
$files = new SiteFiles($conn, $config['srvc'], new SiteSettings($conn, $config), $site, $origin . $folder);

$answer = $files->Serve($file);
header('Cache-Control: public, max-age=3600');
if($answer === null)
{
	http_response_code(404);
	header('Content-Type: text/plain; charset=utf-8');
	echo "Not found\n";
	exit;
}
header('Content-Type: ' . $answer[0]);
echo $answer[1];
