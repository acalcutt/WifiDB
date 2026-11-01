<?php
/*
test-s3.php, run by test-s3.sh against a real S3 service (a one-node Garage): lib/s3.inc.php puts, lists, reads,
resumes and deletes, and a wrong secret is refused.
  php test-s3.php LIB_DIR    Environment: S3_ENDPOINT, S3_BUCKET, S3_KEY_ID, S3_SECRET, S3_REGION
*/
require $argv[1].'/s3.inc.php';
$failed = 0;
function check($what, $ok, $detail = '') { global $failed; echo ($ok ? "  ok   " : "  FAIL ").$what.($ok || $detail === '' ? '' : "\n       ".$detail)."\n"; if(!$ok) { $failed = 1; } }
$s3 = function ($secret = null) { return new S3Client(getenv('S3_ENDPOINT'), getenv('S3_REGION'), getenv('S3_BUCKET'), getenv('S3_KEY_ID'), $secret ?? getenv('S3_SECRET')); };
$c = $s3();
$c->partSize = 5 * 1048576;
$tmp = sys_get_temp_dir();

try
{
	check("the bucket answers", $c->Check());
	file_put_contents("$tmp/small", "hello\x00\xff");
	$c->PutFile('backups/site one/small.bin', "$tmp/small", array('sha256' => hash_file('sha256', "$tmp/small")));
	$big = "$tmp/big";
	$fh = fopen($big, 'wb'); for($i = 0; $i < 13; $i++) { fwrite($fh, random_bytes(1048576)); } fclose($fh);
	# in 5 MB parts here (64 MB normally): three of them, the last one short
	$c->singlePutMax = 1048576;
	$said = array();
	$c->log = function ($line) use (&$said) { $said[] = $line; };
	$c->PutFile('backups/site one/big.bin', $big, array('sha256' => hash_file('sha256', $big)));
	check("a big file goes up in parts", in_array('  sent 3 of 3 parts', $said, true), json_encode($said));
	$list = $c->ListObjects('backups/site one/');
	check("both are listed, with their sizes", array_map(function ($o) { return array($o[0], $o[1]); }, $list) === array(array('backups/site one/big.bin', 13 * 1048576), array('backups/site one/small.bin', 7)), json_encode($list));
	$head = $c->Head('backups/site one/small.bin');
	check("its metadata comes back", ($head['meta']['sha256'] ?? '') === hash_file('sha256', "$tmp/small"), json_encode($head));
	check("a missing object is null, not an error", $c->Head('backups/nothing') === null);

	# download, cut off part way, then carry on
	$dest = "$tmp/got";
	$fh = fopen($big, 'rb'); file_put_contents($dest, fread($fh, 3 * 1048576 + 17)); fclose($fh);
	$c->GetFile('backups/site one/big.bin', $dest);
	check("a download carries on from where it stopped and comes out whole", hash_file('sha256', $dest) === hash_file('sha256', $big));
	@unlink($dest);
	$c->GetFile('backups/site one/small.bin', $dest);
	check("binary bytes come back as they went", file_get_contents($dest) === "hello\x00\xff");

	try { $s3('wrong')->ListObjects(); check("a wrong secret is refused", false); }
	catch(S3Exception $e) { check("a wrong secret is refused", true); echo "       (".$e->getMessage().")\n"; }

	$c->Delete('backups/site one/small.bin');
	$c->Delete('backups/site one/big.bin');
	check("deleted objects are gone", $c->ListObjects('backups/') === array());
}
catch(Throwable $e)
{
	check("the S3 test stopped", false, $e->getMessage());
}
exit($failed);
