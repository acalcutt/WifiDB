<?php
/*
releases.inc.php, WifiDB's published releases, for updating a server (proxmox/wifidb-update; admin/servers.php shows
what it found). Read from GitLab (the primary: gitlab.techidiots.net) or GitHub, as the root-owned
/etc/wifidb/updates.conf says. Only releases with an update package (WifiDB-v<version>-update.tar.gz) and SHA256SUMS
count: those are what an update installs (proxmox/make-update-package.sh, docs/updates.md).
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/
require_once dirname(__FILE__).'/servers.inc.php';   # WifidbVersion

class ReleaseException extends RuntimeException {}

class WifidbReleases
{
	const PACKAGE_RE = '/^WifiDB-v(.+)-update\.tar\.gz$/';
	const SUMS = 'SHA256SUMS';
	const TIMEOUT = 30;

	/** @var array source (gitlab|github), gitlab_url, gitlab_project, github_repo, token */
	private $conf;
	/** @var callable function ($url, array $headers): array(status, body) — swapped in tests */
	public $get;

	public function __construct(array $conf)
	{
		$this->conf = array_change_key_case($conf, CASE_LOWER) + array('source' => 'gitlab', 'gitlab_url' => '', 'gitlab_project' => '',
			'github_repo' => '', 'token' => '');
		$this->get = array($this, 'HttpGet');
	}

	/** The API address of the release list. */
	public function ListUrl()
	{
		if($this->conf['source'] === 'github')
		{
			if(!preg_match('#^[\w.-]+/[\w.-]+$#', $this->conf['github_repo'])) { throw new ReleaseException("Set the GitHub repository (owner/name) in /etc/wifidb/updates.conf (WIFIDB_UPDATE_GITHUB_REPO)"); }
			return 'https://api.github.com/repos/'.$this->conf['github_repo'].'/releases?per_page=50';
		}
		if($this->conf['source'] !== 'gitlab') { throw new ReleaseException("Unknown update source '".$this->conf['source']."': gitlab or github"); }
		if(!preg_match('#^https?://[^/]+#', $this->conf['gitlab_url']) || $this->conf['gitlab_project'] === '')
		{
			throw new ReleaseException("Set the GitLab address and project in /etc/wifidb/updates.conf");
		}
		return rtrim($this->conf['gitlab_url'], '/').'/api/v4/projects/'.rawurlencode($this->conf['gitlab_project']).'/releases?per_page=50';
	}

	/** The headers that carry the token, if there is one (a private project). */
	public function AuthHeaders()
	{
		if($this->conf['token'] === '') { return array(); }
		return $this->conf['source'] === 'github' ? array('Authorization: Bearer '.$this->conf['token']) : array('PRIVATE-TOKEN: '.$this->conf['token']);
	}

	/** @return array the releases with an update package, newest first: version, tag, prerelease, date, package, sums (URLs) */
	public function Fetch()
	{
		list($status, $body) = call_user_func($this->get, $this->ListUrl(), array_merge(array('Accept: application/json'), $this->AuthHeaders()));
		if($status !== 200) { throw new ReleaseException("The release list answered HTTP $status"); }
		$data = json_decode($body, true);
		if(!is_array($data)) { throw new ReleaseException("The release list isn't JSON"); }
		return $this->conf['source'] === 'github' ? self::ParseGitHub($data) : self::ParseGitLab($data);
	}

	/** GitLab's /projects/:id/releases: the files are asset links (permanent /-/releases/<tag>/downloads/<file> ones). */
	public static function ParseGitLab(array $data)
	{
		$out = array();
		foreach($data as $r)
		{
			if(!is_array($r) || !empty($r['upcoming_release'])) { continue; }
			$files = array();
			foreach(($r['assets']['links'] ?? array()) as $link)
			{
				$url = !empty($link['direct_asset_url']) ? $link['direct_asset_url'] : ($link['url'] ?? '');
				if(isset($link['name']) && $url !== '') { $files[$link['name']] = $url; }
			}
			$out[] = self::Release($r['tag_name'] ?? '', $files, $r['released_at'] ?? ($r['created_at'] ?? ''), null);
		}
		return self::Usable($out);
	}

	/** GitHub's /repos/:owner/:repo/releases: the files are assets with download URLs; drafts don't count. */
	public static function ParseGitHub(array $data)
	{
		$out = array();
		foreach($data as $r)
		{
			if(!is_array($r) || !empty($r['draft'])) { continue; }
			$files = array();
			foreach(($r['assets'] ?? array()) as $asset)
			{
				if(isset($asset['name'], $asset['browser_download_url'])) { $files[$asset['name']] = $asset['browser_download_url']; }
			}
			$out[] = self::Release($r['tag_name'] ?? '', $files, $r['published_at'] ?? '', !empty($r['prerelease']));
		}
		return self::Usable($out);
	}

	private static function Release($tag, array $files, $date, $prerelease)
	{
		$version = ltrim((string)$tag, 'v');
		$package = '';
		foreach($files as $name => $url)
		{
			if(preg_match(self::PACKAGE_RE, $name, $m) && $m[1] === $version) { $package = $url; }
		}
		return array('version' => $version, 'tag' => (string)$tag, 'date' => (string)$date,
			'prerelease' => $prerelease === null ? WifidbVersion::IsPrerelease($version) : $prerelease,
			'package' => $package, 'package_name' => 'WifiDB-v'.$version.'-update.tar.gz', 'sums' => $files[self::SUMS] ?? '');
	}

	/** Releases with a version, a package and checksums, newest first. */
	private static function Usable(array $releases)
	{
		$out = array_values(array_filter($releases, function ($r) { return WifidbVersion::Valid($r['version']) && $r['package'] !== '' && $r['sums'] !== ''; }));
		usort($out, function ($a, $b) { return WifidbVersion::Compare($b['version'], $a['version']); });
		return $out;
	}

	/** @return array|null the release of $version in $releases */
	public static function Find(array $releases, $version)
	{
		foreach($releases as $r) { if(WifidbVersion::Compare($r['version'], $version) === 0) { return $r; } }
		return null;
	}

	/** @return array|null the newest release, pre-releases only if asked */
	public static function Newest(array $releases, $prereleases = false)
	{
		foreach($releases as $r) { if($prereleases || empty($r['prerelease'])) { return $r; } }
		return null;
	}

	/** SHA256SUMS ("<hash>  <name>" or "<hash> *<name>" lines) as name => hash. */
	public static function ParseSums($text)
	{
		$out = array();
		foreach(preg_split('/\r?\n/', (string)$text) as $line)
		{
			if(preg_match('/^([0-9a-fA-F]{64})\s+\*?(\S.*)$/', trim($line), $m)) { $out[basename(trim($m[2]))] = strtolower($m[1]); }
		}
		return $out;
	}

	/**
	 * Downloads $release's package to $dir and checks it against its SHA256SUMS.
	 * @return string the package's path
	 */
	public function Download(array $release, $dir)
	{
		list($status, $sums) = call_user_func($this->get, $release['sums'], $this->AuthHeaders());
		if($status !== 200) { throw new ReleaseException("SHA256SUMS answered HTTP $status"); }
		$want = self::ParseSums($sums)[$release['package_name']] ?? '';
		if($want === '') { throw new ReleaseException("SHA256SUMS has no line for ".$release['package_name']); }
		$path = rtrim($dir, '/').'/'.$release['package_name'];
		list($status, $body) = call_user_func($this->get, $release['package'], $this->AuthHeaders(), $path);
		if($status !== 200) { @unlink($path); throw new ReleaseException("The update package answered HTTP $status"); }
		if(!is_file($path)) { file_put_contents($path, $body); }
		$got = hash_file('sha256', $path);
		if(!hash_equals($want, $got)) { unlink($path); throw new ReleaseException("The update package's SHA-256 ($got) isn't the one in SHA256SUMS ($want)"); }
		return $path;
	}

	/**
	 * GET $url, into $file if given. Redirects are followed here rather than by curl, so the token goes only to the
	 * host it's for (GitLab hands a package's download to its object storage). @return array status, body ('' to $file)
	 */
	public function HttpGet($url, array $headers, $file = null)
	{
		$host = parse_url($url, PHP_URL_HOST);
		for($hop = 0; $hop <= 5; $hop++)
		{
			if(!preg_match('#^https?://#i', $url)) { throw new ReleaseException("Not a web address: $url"); }
			$send = parse_url($url, PHP_URL_HOST) === $host ? $headers : array_values(array_filter($headers, function ($h) {
				return !preg_match('/^(PRIVATE-TOKEN|Authorization):/i', $h); }));
			$ch = curl_init($url);
			$location = '';
			curl_setopt_array($ch, array(CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
				CURLOPT_TIMEOUT => $file ? 3600 : self::TIMEOUT, CURLOPT_HTTPHEADER => array_merge($send, array('User-Agent: wifidb-update')),
				CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$location) {
					if(stripos($line, 'Location:') === 0) { $location = trim(substr($line, 9)); }
					return strlen($line);
				}));
			# the body goes to the file only once it's the final answer, not a redirect's
			$out = null;
			$body = '';
			curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$out, &$body, $file) {
				$status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
				if($file && $status === 200)
				{
					if(!$out) { $out = fopen($file, 'wb'); }
					return fwrite($out, $data);
				}
				$body .= $data;
				return strlen($data);
			});
			$ok = curl_exec($ch);
			$status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
			$error = curl_error($ch);
			if($out) { fclose($out); }
			if($ok === false) { throw new ReleaseException("Couldn't reach $url: $error"); }
			if($status >= 300 && $status < 400 && $location !== '')
			{
				# a relative Location is on the same host
				$url = preg_match('#^https?://#i', $location) ? $location : preg_replace('#^(https?://[^/]+).*$#i', '$1', $url).'/'.ltrim($location, '/');
				continue;
			}
			return array($status, $file && $status === 200 ? '' : $body);
		}
		throw new ReleaseException("Too many redirects from $url");
	}
}
