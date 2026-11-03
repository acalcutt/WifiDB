<?php
/*
out_sync.inc.php, keeps the generated files (out/: the tile archives, the GeoJSON, the KML exports) the same on every
node through the shared files bucket (lib/shared_files.inc.php), for several nodes on one database. Whichever node
built a file pushes it; the others pull it; each still serves out/ from its own disk, so Apache's headers, byte
ranges and all work as before, and a node keeps serving its copy if the bucket is out of reach.
(tools/cron/out_sync runs it every few minutes; docs/proxmox.md.)

Each run compares three things for every file: what's on disk, what's in the bucket (PREFIX/out/<path>), and what
they both were after the last run (a state file of this node's). A file changed here since then is pushed, one changed
in the bucket is pulled (into a .part first, then renamed, so a reader never sees half a file), and one gone from one
side since then is removed from the other. A hard link (the stable name of a tile archive, linked to its dated file)
or a symbolic link (the KML exports') is stored as an empty object saying what it links to, and made again on the way
down, so a big archive is in the bucket once. Files still being written (.building, .tmp, .part) and dot files are
left out.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/
require_once dirname(__FILE__).'/s3.inc.php';

class OutSync
{
	/** The folders under out/ the cron builders make. The tile trees (tiles/, tiles-mlt/) aren't here: each node fills
	 * its own from the database as tiles are asked for, and the signal graphs are drawn per request. */
	const DIRS = array('pmtiles', 'pmtiles-mlt', 'geojson', 'daemon');
	const SKIP = '#(\.building|\.tmp|\.part)$|(^|/)\.#';

	private $s3;
	private $prefix;
	private $root;
	private $dirs;
	private $stateFile;
	/** @var callable|null function ($line) */
	public $log;

	/**
	 * @param string $prefix the site's folder in the bucket (objects go under PREFIX/out/)
	 * @param string $root out/ on this node
	 * @param string $stateFile where this node keeps what both sides were after the last run (not under out/: it's served)
	 */
	public function __construct(S3Client $s3, $prefix, $root, $stateFile, array $dirs = self::DIRS)
	{
		$this->s3 = $s3;
		$this->prefix = trim($prefix, '/').'/out/';
		$this->root = rtrim($root, '/').'/';
		$this->stateFile = $stateFile;
		$this->dirs = $dirs;
	}

	private function Say($line) { if($this->log) { call_user_func($this->log, $line); } }

	/** What's on disk: path => [sig, link] where sig changes whenever the file does, and link is '' or 'hard:<path>' / 'sym:<target>'. */
	public function Local()
	{
		$out = array();
		$inodes = array();
		foreach($this->dirs as $dir)
		{
			if(!is_dir($this->root.$dir)) { continue; }
			$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root.$dir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME));
			$paths = array();
			foreach($it as $path) { $paths[] = $path; }
			sort($paths, SORT_STRING);   # the first name of a hard-linked file, by name, is the one uploaded
			foreach($paths as $path)
			{
				$rel = substr($path, strlen($this->root));
				if(preg_match(self::SKIP, $rel)) { continue; }
				if(is_link($path))
				{
					$target = readlink($path);
					$out[$rel] = array('sig' => 'sym:'.$target, 'link' => 'sym:'.$target);
					continue;
				}
				$st = @stat($path);
				if(!$st || !is_file($path)) { continue; }
				$key = $st['dev'].':'.$st['ino'];
				if($st['nlink'] > 1 && isset($inodes[$key]))
				{
					$out[$rel] = array('sig' => 'hard:'.$inodes[$key].':'.$st['size'].':'.$st['mtime'], 'link' => 'hard:'.$inodes[$key]);
					continue;
				}
				if($st['nlink'] > 1) { $inodes[$key] = $rel; }
				$out[$rel] = array('sig' => 'file:'.$st['size'].':'.$st['mtime'], 'link' => '');
			}
		}
		return $out;
	}

	/** What's in the bucket: path => Unix time it was written. */
	public function Remote()
	{
		$out = array();
		foreach($this->s3->ListObjects($this->prefix) as $o)
		{
			$rel = substr($o[0], strlen($this->prefix));
			$top = explode('/', $rel)[0];
			if($rel === '' || !in_array($top, $this->dirs, true) || preg_match(self::SKIP, $rel)) { continue; }
			$out[$rel] = (int)strtotime($o[2]);
		}
		return $out;
	}

	/** @return array what it did: pushed, pulled, removed_remote, removed_here, and errors */
	public function Run()
	{
		$state = is_file($this->stateFile) ? (json_decode((string)file_get_contents($this->stateFile), true) ?: array()) : array();
		$local = $this->Local();
		$remote = $this->Remote();
		$did = array('pushed' => 0, 'pulled' => 0, 'removed_remote' => 0, 'removed_here' => 0, 'errors' => array());
		$paths = array_unique(array_merge(array_keys($local), array_keys($remote), array_keys($state)));
		// plain files before links, so a link is made after what it links to
		usort($paths, function ($a, $b) use ($local, $state) {
			$la = ($local[$a]['link'] ?? $state[$a]['link'] ?? '') !== ''; $lb = ($local[$b]['link'] ?? $state[$b]['link'] ?? '') !== '';
			return $la === $lb ? strcmp($a, $b) : ($la ? 1 : -1);
		});
		$pulls = array();
		foreach($paths as $rel)
		{
			$L = $local[$rel] ?? null;
			$R = $remote[$rel] ?? null;
			$S = $state[$rel] ?? null;
			$hereChanged = $L && (!$S || $L['sig'] !== $S['sig']);
			$thereChanged = $R !== null && (!$S || $R !== (int)$S['remote']);
			try
			{
				if($L && $hereChanged && (!$thereChanged || $R === null))
				{
					$state[$rel] = array('sig' => $L['sig'], 'link' => $L['link'], 'remote' => $this->Push($rel, $L));
					$did['pushed']++;
				}
				elseif($R !== null && $thereChanged)
				{
					$pulls[] = $rel;   # after the loop: a link's target has to be here first
				}
				elseif(!$L && $S && $R !== null && !$thereChanged)
				{
					$this->s3->Delete($this->prefix.$rel);
					unset($state[$rel]);
					$did['removed_remote']++;
				}
				elseif($L && $S && $R === null && !$hereChanged)
				{
					@unlink($this->root.$rel);
					unset($state[$rel]);
					$did['removed_here']++;
				}
				elseif(!$L && $R === null) { unset($state[$rel]); }
			}
			catch(Throwable $e)
			{
				$did['errors'][] = "$rel: ".$e->getMessage();
			}
		}
		foreach($pulls as $rel)
		{
			try
			{
				$state[$rel] = $this->Pull($rel, $remote[$rel]);
				$did['pulled']++;
			}
			catch(Throwable $e)
			{
				$did['errors'][] = "$rel: ".$e->getMessage();
			}
		}
		file_put_contents($this->stateFile.'.new', json_encode($state));
		rename($this->stateFile.'.new', $this->stateFile);
		return $did;
	}

	/** @return int when the bucket says it was written */
	private function Push($rel, array $L)
	{
		$key = $this->prefix.$rel;
		if($L['link'] !== '')
		{
			$this->Say("push $rel (".$L['link'].")");
			$this->s3->PutString($key, '', array('link' => $L['link']));
		}
		else
		{
			$this->Say("push $rel");
			$this->s3->PutFile($key, $this->root.$rel);
		}
		$head = $this->s3->Head($key);
		return $head ? (int)$head['modified'] : time();
	}

	/** @return array the state entry for it */
	private function Pull($rel, $modified)
	{
		$key = $this->prefix.$rel;
		$path = $this->root.$rel;
		$head = $this->s3->Head($key);
		if(!$head) { throw new S3Exception("it went from the bucket meanwhile"); }
		if(!is_dir(dirname($path))) { mkdir(dirname($path), 0775, true); }
		$link = (string)($head['meta']['link'] ?? '');
		$part = $path.'.part';
		@unlink($part);
		if(strpos($link, 'sym:') === 0)
		{
			$this->Say("pull $rel ($link)");
			symlink(substr($link, 4), $part);
		}
		elseif(strpos($link, 'hard:') === 0)
		{
			$this->Say("pull $rel ($link)");
			$target = $this->root.substr($link, 5);
			if(!is_file($target)) { throw new S3Exception("what it links to, ".substr($link, 5).", isn't here"); }
			link($target, $part);
		}
		else
		{
			$this->Say("pull $rel");
			$this->s3->GetFile($key, $part);
		}
		rename($part, $path);
		$st = lstat($path);
		$sig = $link === '' ? 'file:'.$st['size'].':'.$st['mtime'] : (strpos($link, 'sym:') === 0 ? $link : $link.':'.$st['size'].':'.$st['mtime']);
		return array('sig' => $sig, 'link' => $link, 'remote' => (int)$modified);
	}
}
