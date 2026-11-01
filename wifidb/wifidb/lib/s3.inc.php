<?php
/*
s3.inc.php, a small client for S3 and the services that speak its API (Garage, MinIO, Ceph, AWS): put a file (in parts
when it's big), get one (carrying on from where an earlier try stopped), list, look at and delete. Requests are signed
with AWS Signature Version 4 and use path-style addresses (https://host/bucket/key), which is what Garage and MinIO
expect. Written for the backups (lib/backups.inc.php); nothing in it is about backups, so uploads and exports shared
between nodes can use it too.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class S3Exception extends Exception {}

class S3Client
{
	const PART_SIZE = 67108864;      // 64 MB pieces for big files; more when a file would need over 9000 of them
	const SINGLE_PUT_MAX = 67108864; // up to this, one PUT
	const MAX_PARTS = 9000;          // S3 allows 10000

	private $endpoint;
	private $region;
	private $bucket;
	private $keyId;
	private $secret;
	public $insecure = false;        // don't check the TLS certificate (a test server)
	public $partSize = self::PART_SIZE;
	public $singlePutMax = self::SINGLE_PUT_MAX;
	public $log;                     // function ($line)
	public $clock;                   // function (): Unix time (tests)

	public function __construct($endpoint, $region, $bucket, $keyId, $secret)
	{
		$this->endpoint = rtrim((string)$endpoint, '/');
		$this->region = (string)$region !== '' ? (string)$region : 'us-east-1';
		$this->bucket = (string)$bucket;
		$this->keyId = (string)$keyId;
		$this->secret = (string)$secret;
		if(!preg_match('#^https?://[^/]+$#i', $this->endpoint)) { throw new S3Exception("The S3 address should look like https://s3.example.com (no path)"); }
		if($this->bucket === '' || $this->keyId === '' || $this->secret === '') { throw new S3Exception("The S3 bucket, key ID and secret are all needed"); }
	}

	private function Say($line) { if($this->log) { call_user_func($this->log, $line); } }

	// ── Signing ──────────────────────────────────────────────────────────────────────────────────────

	public static function EncodeKey($key)
	{
		return implode('/', array_map('rawurlencode', explode('/', $key)));
	}

	/**
	 * The headers for a signed request (Authorization, x-amz-date, x-amz-content-sha256 and the signed extras).
	 * @param array $extra more headers to sign, name => value
	 */
	public function SignedHeaders($method, $key, array $query, $payloadHash, array $extra = array())
	{
		$now = $this->clock ? call_user_func($this->clock) : time();
		$amzDate = gmdate('Ymd\THis\Z', $now);
		$date = gmdate('Ymd', $now);
		$host = parse_url($this->endpoint, PHP_URL_HOST);
		$port = parse_url($this->endpoint, PHP_URL_PORT);
		if($port) { $host .= ':'.$port; }
		$path = '/'.rawurlencode($this->bucket).($key !== '' ? '/'.self::EncodeKey($key) : ($method === 'GET' ? '/' : ''));
		ksort($query, SORT_STRING);
		$q = array();
		foreach($query as $k => $v) { $q[] = rawurlencode($k).'='.rawurlencode((string)$v); }

		$headers = array('host' => $host, 'x-amz-content-sha256' => $payloadHash, 'x-amz-date' => $amzDate);
		foreach($extra as $k => $v) { $headers[strtolower($k)] = trim((string)$v); }
		ksort($headers, SORT_STRING);
		$canonicalHeaders = '';
		foreach($headers as $k => $v) { $canonicalHeaders .= $k.':'.$v."\n"; }
		$signed = implode(';', array_keys($headers));
		$canonical = $method."\n".$path."\n".implode('&', $q)."\n".$canonicalHeaders."\n".$signed."\n".$payloadHash;
		$scope = "$date/{$this->region}/s3/aws4_request";
		$toSign = "AWS4-HMAC-SHA256\n$amzDate\n$scope\n".hash('sha256', $canonical);
		$k = hash_hmac('sha256', $date, 'AWS4'.$this->secret, true);
		$k = hash_hmac('sha256', $this->region, $k, true);
		$k = hash_hmac('sha256', 's3', $k, true);
		$k = hash_hmac('sha256', 'aws4_request', $k, true);
		$signature = hash_hmac('sha256', $toSign, $k);
		$out = array('Authorization' => "AWS4-HMAC-SHA256 Credential={$this->keyId}/$scope, SignedHeaders=$signed, Signature=$signature");
		foreach($headers as $k => $v) { if($k !== 'host') { $out[$k] = $v; } }
		return array($path.($q ? '?'.implode('&', $q) : ''), $out);
	}

	// ── Requests ─────────────────────────────────────────────────────────────────────────────────────

	/**
	 * @param string|array|null $body a string, or array(file path, offset, length) sent from the file
	 * @param resource|null $sink the response body goes here instead of being returned
	 * @return array status, headers (lower-case names), body
	 */
	private function Request($method, $key, array $query = array(), $body = null, array $extra = array(), $sink = null, array $unsigned = array())
	{
		if(is_array($body))
		{
			list($file, $offset, $length) = $body;
			$fh = fopen($file, 'rb');
			fseek($fh, $offset);
			$ctx = hash_init('sha256');
			$left = $length;
			while($left > 0) { $chunk = fread($fh, (int)min(8388608, $left)); if($chunk === '' || $chunk === false) { break; } hash_update($ctx, $chunk); $left -= strlen($chunk); }
			$hash = hash_final($ctx);
			fseek($fh, $offset);
		}
		else { $hash = hash('sha256', (string)$body); }
		list($uri, $headers) = $this->SignedHeaders($method, $key, $query, $hash, $extra);
		$ch = curl_init($this->endpoint.$uri);
		$lines = array();
		foreach($headers + $unsigned as $k => $v) { $lines[] = "$k: $v"; }
		$lines[] = 'Expect:';
		$got = array();
		curl_setopt_array($ch, array(
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => $lines,
			CURLOPT_CONNECTTIMEOUT => 20,
			CURLOPT_TIMEOUT => 3600,
			CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$got) {
				$p = strpos($line, ':');
				if($p !== false) { $got[strtolower(trim(substr($line, 0, $p)))] = trim(substr($line, $p + 1)); }
				return strlen($line);
			},
		));
		if($this->insecure) { curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); }
		if($method === 'HEAD') { curl_setopt($ch, CURLOPT_NOBODY, true); }
		if(is_array($body))
		{
			curl_setopt($ch, CURLOPT_UPLOAD, true);
			curl_setopt($ch, CURLOPT_INFILE, $fh);
			curl_setopt($ch, CURLOPT_INFILESIZE, $length);
		}
		elseif($body !== null) { curl_setopt($ch, CURLOPT_POSTFIELDS, $body); }
		# an error is read into memory even when the body would go to a file
		$errorBody = '';
		if($sink)
		{
			curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use ($sink, &$errorBody) {
				$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if($status >= 300) { $errorBody .= $data; return strlen($data); }
				return fwrite($sink, $data);
			});
		}
		else { curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); }
		$out = curl_exec($ch);
		$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		if(isset($fh)) { fclose($fh); }
		if($out === false) { throw new S3Exception("Couldn't reach ".$this->endpoint.": $error"); }
		$text = $sink ? $errorBody : (string)$out;
		if($status >= 300 && $status !== 404)
		{
			$code = preg_match('#<Code>([^<]*)</Code>#', $text, $m) ? $m[1] : '';
			$message = preg_match('#<Message>([^<]*)</Message>#', $text, $m) ? $m[1] : '';
			throw new S3Exception("S3 said $status".($code !== '' ? " $code" : '').($message !== '' ? ": $message" : '')." ($method ".($key !== '' ? $key : $this->bucket).")");
		}
		return array($status, $got, $text);
	}

	// ── Operations ───────────────────────────────────────────────────────────────────────────────────

	/** Does the bucket answer, with these keys? Throws if not. */
	public function Check()
	{
		list($status) = $this->Request('GET', '', array('list-type' => '2', 'max-keys' => '1'));
		if($status === 404) { throw new S3Exception("There's no bucket named ".$this->bucket); }
		return true;
	}

	/** @return array of [key, size, modified] under $prefix, oldest key name first */
	public function ListObjects($prefix = '')
	{
		$out = array();
		$token = null;
		do
		{
			$q = array('list-type' => '2', 'prefix' => $prefix);
			if($token !== null) { $q['continuation-token'] = $token; }
			list($status, , $xml) = $this->Request('GET', '', $q);
			if($status === 404) { throw new S3Exception("There's no bucket named ".$this->bucket); }
			$doc = @simplexml_load_string($xml);
			if(!$doc) { throw new S3Exception("S3 sent a list this can't read"); }
			foreach($doc->Contents as $c) { $out[] = array((string)$c->Key, (int)(string)$c->Size, (string)$c->LastModified); }
			$token = ((string)$doc->IsTruncated === 'true') ? (string)$doc->NextContinuationToken : null;
		}
		while($token);
		usort($out, function ($a, $b) { return strcmp($a[0], $b[0]); });
		return $out;
	}

	/** @return array|null size and metadata (x-amz-meta-*, without the prefix), or null if there's no such object */
	public function Head($key)
	{
		list($status, $headers) = $this->Request('HEAD', $key);
		if($status === 404) { return null; }
		$meta = array();
		foreach($headers as $k => $v) { if(strpos($k, 'x-amz-meta-') === 0) { $meta[substr($k, 11)] = $v; } }
		return array('size' => (int)($headers['content-length'] ?? 0), 'meta' => $meta);
	}

	public function Delete($key)
	{
		$this->Request('DELETE', $key);
	}

	/** Uploads a file; one over singlePutMax goes in parts. @param array $meta stored as x-amz-meta-* */
	public function PutFile($key, $file, array $meta = array())
	{
		$size = filesize($file);
		$metaHeaders = array();
		foreach($meta as $k => $v) { $metaHeaders['x-amz-meta-'.strtolower($k)] = $v; }
		if($size <= $this->singlePutMax)
		{
			$this->Request('PUT', $key, array(), array($file, 0, $size), $metaHeaders);
			return;
		}
		$part = max($this->partSize, (int)ceil($size / self::MAX_PARTS / 1048576) * 1048576);
		list(, , $xml) = $this->Request('POST', $key, array('uploads' => ''), '', $metaHeaders);
		if(!preg_match('#<UploadId>([^<]+)</UploadId>#', $xml, $m)) { throw new S3Exception("S3 didn't start the upload of $key"); }
		$uploadId = $m[1];
		try
		{
			$etags = array();
			$parts = (int)ceil($size / $part);
			for($n = 1; $n <= $parts; $n++)
			{
				$offset = ($n - 1) * $part;
				list(, $headers) = $this->Request('PUT', $key, array('partNumber' => (string)$n, 'uploadId' => $uploadId), array($file, $offset, min($part, $size - $offset)));
				if(empty($headers['etag'])) { throw new S3Exception("S3 didn't confirm part $n of $key"); }
				$etags[$n] = $headers['etag'];
				if($n % 10 === 0 || $n === $parts) { $this->Say("  sent ".$n." of ".$parts." parts"); }
			}
			$done = '<CompleteMultipartUpload>';
			foreach($etags as $n => $etag) { $done .= '<Part><PartNumber>'.$n.'</PartNumber><ETag>'.htmlspecialchars($etag, ENT_XML1).'</ETag></Part>'; }
			$done .= '</CompleteMultipartUpload>';
			list(, , $answer) = $this->Request('POST', $key, array('uploadId' => $uploadId), $done);
			if(strpos($answer, '<Error>') !== false) { throw new S3Exception("S3 couldn't put $key together: ".strip_tags($answer)); }
		}
		catch(Throwable $e)
		{
			try { $this->Request('DELETE', $key, array('uploadId' => $uploadId)); } catch(Throwable $ignored) { /* it expires anyway */ }
			throw $e;
		}
	}

	/**
	 * Downloads an object to $dest, carrying on from the end of $dest if an earlier try left part of it.
	 * @return int its size
	 */
	public function GetFile($key, $dest)
	{
		$head = $this->Head($key);
		if(!$head) { throw new S3Exception("There's no $key in the bucket"); }
		$size = $head['size'];
		clearstatcache(true, $dest);
		$have = is_file($dest) ? filesize($dest) : 0;
		if($have > $size) { $have = 0; file_put_contents($dest, ''); }
		$out = fopen($dest, $have ? 'ab' : 'wb');
		while($have < $size)
		{
			$end = min($size, $have + $this->partSize) - 1;
			try { list($status) = $this->Request('GET', $key, array(), null, array(), $out, array('Range' => "bytes=$have-$end")); }
			catch(Throwable $e) { ftruncate($out, $have); fclose($out); throw $e; }
			fflush($out);
			clearstatcache(true, $dest);
			if(filesize($dest) !== $end + 1) { ftruncate($out, $have); fclose($out); throw new S3Exception("The download of $key stopped at $have bytes (HTTP $status)"); }
			$have = $end + 1;
		}
		fclose($out);
		return $size;
	}
}
