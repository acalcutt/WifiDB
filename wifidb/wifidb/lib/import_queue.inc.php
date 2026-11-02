<?php
/*
import_queue.inc.php: puts an upload on the import queue (files_tmp), for the upload page and both versions of the
API, so all three do it the same way: turned away if the same file is already waiting, importing or imported; put
in the shared files bucket first when there is one (lib/shared_files.inc.php), which is then where it's kept; and
queued with the name of the node it came in on, so that without the bucket only that node's importer takes it.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/
require_once dirname(__FILE__).'/shared_files.inc.php';

class ImportQueue
{
	/** This node's name: config.inc.php's wifidb_nodename, else the daemons' (daemon.config.inc.php), else ''. */
	public static function NodeName(array $config)
	{
		if(!empty($config['wifidb_nodename'])) { return (string)$config['wifidb_nodename']; }
		$file = ($config['wifidb_tools'] ?? '').'daemon.config.inc.php';
		if(($config['wifidb_tools'] ?? '') === '' || !is_readable($file)) { return ''; }
		$daemon = (function ($file) { include $file; return isset($daemon_config) ? $daemon_config : null; })($file);
		return is_array($daemon) && isset($daemon['wifidb_nodename']) ? (string)$daemon['wifidb_nodename'] : '';
	}

	/**
	 * Queues the upload at $path (already in import/up as $f['file_name']).
	 * @param array $f file_name, file_orig, file_date, file_user, otherusers, notes, title, size, hash, type
	 * @param callable|null $log function ($line), for what went wrong
	 * @return array [result, detail]: ['queued', import id], ['waiting'|'imported', ''] (the same file is already
	 *   queued or imported), ['unstored', why] (the shared files bucket couldn't take it) or ['failed', why]. Unless it
	 *   was queued, the copy at $path is removed again; with the bucket, it is removed once queued too.
	 */
	public static function Add(PDO $conn, array $config, array $f, $path, $log = null)
	{
		$say = function ($line) use ($log) { if($log) { call_user_func($log, $line); } };
		$hash = (string)$f['hash'];
		$found = $conn->prepare("SELECT (SELECT COUNT(*) FROM files_tmp WHERE hash = ?) + (SELECT COUNT(*) FROM files_importing WHERE hash = ?) AS waiting, (SELECT COUNT(*) FROM files WHERE hash = ?) AS imported");
		$found->execute(array($hash, $hash, $hash));
		$found = $found->fetch(PDO::FETCH_ASSOC);
		if((int)$found['waiting'] > 0 || (int)$found['imported'] > 0)
		{
			@unlink($path);
			return array((int)$found['waiting'] > 0 ? 'waiting' : 'imported', '');
		}

		// with the bucket (several nodes on one database) the upload goes there before it's queued, so whichever node's
		// importer takes it can fetch it; if the bucket can't take it, it isn't queued at all
		$shared = null;
		try
		{
			$shared = SharedFiles::FromSettings($conn, $config);
			if($shared) { $shared->PutUpload($f['file_name'], $path); }
		}
		catch(Throwable $e)
		{
			@unlink($path);
			$say("Could not put the upload ".$f['file_name']." in the shared files bucket: ".$e->getMessage());
			return array('unstored', $e->getMessage());
		}

		$node = self::NodeName($config);
		try
		{
			$prep = $conn->prepare("INSERT INTO files_tmp (file_name, file_orig, file_date, file_user, otherusers, notes, title, size, hash, type, node_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$prep->execute(array($f['file_name'], $f['file_orig'], $f['file_date'], $f['file_user'], $f['otherusers'], $f['notes'], $f['title'],
				$f['size'], $hash, $f['type'], $node !== '' ? $node : null));
			$id = $conn->lastInsertId();
		}
		catch(PDOException $e)
		{
			$say("Could not queue the upload ".$f['file_name'].": ".$e->getMessage());
			@unlink($path);
			if($shared)
			{
				try { $shared->DeleteUpload($f['file_name']); } catch(Throwable $ignored) { /* nothing queued points at it */ }
			}
			return array('failed', $e->getMessage());
		}
		// with the bucket, it's where uploads are kept: this node's copy goes (the importer fetches it)
		if($shared) { @unlink($path); }
		return array('queued', $id);
	}
}
