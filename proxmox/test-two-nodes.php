<?php
// Run inside a node by test-two-nodes.sh, as www-data, with the node's own settings:
//   php test-two-nodes.php set-shared ENDPOINT BUCKET KEY_ID SECRET   save Shared files in S3 in Site Settings
//   php test-two-nodes.php secret                                      print the S3 secret as this node reads it
//   php test-two-nodes.php has-upload NAME                             1 if the bucket has that upload, else 0
require '/var/www/wifidb/lib/config.inc.php';
require_once '/var/www/wifidb/lib/helpers.inc.php';
require_once '/var/www/wifidb/lib/site_settings.inc.php';
require_once '/var/www/wifidb/lib/shared_files.inc.php';
$pdo = wifidb_pdo_from_config($config, 10);
switch($argv[1] ?? '')
{
	case 'set-shared':
		(new SiteSettings($pdo, $config))->Save(array('files_s3_endpoint' => $argv[2], 'files_s3_region' => 'garage', 'files_s3_bucket' => $argv[3],
			'files_s3_prefix' => 'twonodes', 'files_s3_key_id' => $argv[4], 'files_s3_secret' => $argv[5]), 'test');
		break;
	case 'secret':
		echo (new SiteSettings($pdo, $config))->Get('files_s3_secret');
		break;
	case 'has-upload':
		$s = new SiteSettings($pdo, $config);
		$s3 = new S3Client($s->Get('files_s3_endpoint'), 'garage', $s->Get('files_s3_bucket'), $s->Get('files_s3_key_id'), $s->Get('files_s3_secret'));
		echo $s3->Head(SharedFiles::FromSettings($pdo, $config)->UploadKey($argv[2])) ? 1 : 0;
		break;
	default:
		fwrite(STDERR, "usage: php test-two-nodes.php set-shared|secret|has-upload ...\n");
		exit(2);
}
