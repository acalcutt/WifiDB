<?php
/*
shared_files.inc.php: the files every node needs, kept in an S3-compatible bucket (Site Settings, files_s3_*), for
several nodes sharing one database. With it, the bucket is where uploads are kept: one is put there before it's
queued (and the node it came in on lets its own copy go), whichever node's importer takes it fetches it and removes
that working copy afterwards, and deleting an import moves it to deleted/ in the bucket.

In the bucket: PREFIX/uploads/FILE_NAME (the name it has in import/up), and PREFIX/deleted/ as import/up/deleted/.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/
require_once dirname(__FILE__).'/s3.inc.php';

class SharedFiles
{
	private $s3;
	private $prefix;

	/** The bucket Site Settings names, or null when none is set up (each node keeps its own files). */
	public static function FromSettings($conn, array $config)
	{
		require_once dirname(__FILE__).'/site_settings.inc.php';
		$s = new SiteSettings($conn, $config);
		if($s->Get('files_s3_endpoint') === '') { return null; }
		$prefix = trim((string)$s->Get('files_s3_prefix'), '/');
		if($prefix === '') { $prefix = (string)parse_url($config['hosturl'] ?? '', PHP_URL_HOST); }
		return new SharedFiles(new S3Client($s->Get('files_s3_endpoint'), $s->Get('files_s3_region'), $s->Get('files_s3_bucket'),
			$s->Get('files_s3_key_id'), $s->Get('files_s3_secret')), $prefix !== '' ? $prefix : 'wifidb');
	}

	public function __construct(S3Client $s3, $prefix)
	{
		$this->s3 = $s3;
		$this->prefix = trim($prefix, '/');
	}

	public function UploadKey($name) { return $this->prefix.'/uploads/'.basename($name); }

	/** Puts an upload in the bucket. Throws if it can't, so the caller can refuse the upload rather than queue it unreachable. */
	public function PutUpload($name, $path)
	{
		$this->s3->PutFile($this->UploadKey($name), $path, array('sha256' => hash_file('sha256', $path)));
	}

	/**
	 * Fetches an upload this node doesn't have to $path (by way of $path.part, so a half-fetched file is never taken for
	 * the upload). @return bool false when the bucket hasn't got it either
	 */
	public function FetchUpload($name, $path)
	{
		$key = $this->UploadKey($name);
		$head = $this->s3->Head($key);
		if(!$head) { return false; }
		$part = $path.'.part';
		$this->s3->GetFile($key, $part);
		if(isset($head['meta']['sha256']) && hash_file('sha256', $part) !== $head['meta']['sha256'])
		{
			unlink($part);
			throw new S3Exception("The copy of $name fetched from S3 doesn't match the one put there");
		}
		rename($part, $path);
		chmod($path, 0600);
		return true;
	}

	public function DeleteUpload($name)
	{
		$this->s3->Delete($this->UploadKey($name));
	}

	/** PREFIX/FOLDER/NAME: where admind moves a deleted import's upload (deleted/ by default), beside uploads/. */
	public function FolderKey($folder, $name)
	{
		$folder = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$folder);
		return $this->prefix.'/'.($folder !== '' ? $folder : 'deleted').'/'.basename($name);
	}

	/**
	 * Moves an upload from uploads/ to FOLDER/ in the bucket, with a note of what it was (NAME without its extension,
	 * .txt) beside it, as import/up/deleted/ has without the bucket. @return bool false when the bucket hasn't got it
	 */
	public function MoveUpload($name, $folder, $note)
	{
		$from = $this->UploadKey($name);
		if(!$this->s3->Head($from)) { return false; }
		$this->s3->Copy($from, $this->FolderKey($folder, $name));
		$this->s3->PutString($this->FolderKey($folder, pathinfo(basename($name), PATHINFO_FILENAME).'.txt'), $note);
		$this->s3->Delete($from);
		return true;
	}
}
