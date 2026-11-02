<?php
/*
site_files.inc.php, the files search engines and ad networks read at the top of a site, made from its own address and
Site Settings instead of kept as files written for wifidb.net (site_files.php serves them):

  robots.txt   what crawlers may visit, and where the sitemap is. With "Keep search engines out": nothing.
  ads.txt      who may sell the site's ad space: Site Settings' text, else the AdSense line for its publisher ID, else none.
  sitemap.xml  an index of the sitemaps: the main pages (sitemap-pages.xml), each member with a finished import
               (sitemap-members-1.xml, ...) and each finished import (sitemap-imports-1.xml, ...), at most 50,000 to a
               file as the sitemaps protocol allows. Access points' own pages aren't listed: there are millions, and
               each names a network and its MAC address.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class SiteFiles
{
	const PER_SITEMAP = 50000;
	const ADSENSE_TAG_ID = 'f08c47fec0942fa0';   # Google's certification ID, in the ads.txt line AdSense asks for
	/** The main pages, from the site's root. */
	const PAGES = array('', 'opt/map.php?func=wifidbmap&labeled=0&sig_label=none', 'stats.php', 'opt/userstats.php?func=allusers',
		'opt/search.php', 'opt/scheduling.php?func=done', 'opt/live.php', 'contact.php', 'privacy.php', 'terms.php', 'cookies.php');
	/** Kept from crawlers: members' and administrators' pages, signing in, uploads, exports and the API (heavy, or nothing to index). */
	const DISALLOW = array('cp/', 'admin/', 'lib/', 'api/', 'import/', 'login.php', 'logins.php', 'all.php', 'opt/export.php',
		'opt/admin_action.php');

	private $conn;
	private $service;
	private $settings;
	private $site;
	private $sitePath;
	private $base;
	private $per;

	/**
	 * @param PDO|null $conn the database (null: the sitemaps of members and imports are empty)
	 * @param string $service mysql, sqlsrv or pgsql
	 * @param SiteSettings $settings
	 * @param string $siteUrl where the pages are, ending in / (URL_PATH): https://wifidb.net/ or https://example.com/wifidb/
	 * @param string $baseUrl where these files are served, ending in /: the top of the host, unless the site's own folder is
	 */
	public function __construct($conn, $service, $settings, $siteUrl, $baseUrl, $per = self::PER_SITEMAP)
	{
		$this->conn = $conn;
		$this->service = $service;
		$this->settings = $settings;
		$this->site = rtrim($siteUrl, '/').'/';
		$path = parse_url($this->site, PHP_URL_PATH);
		$this->sitePath = is_string($path) && $path !== '' ? $path : '/';
		$this->base = rtrim($baseUrl, '/').'/';
		$this->per = max(1, (int)$per);
	}

	/** @return array|null (content type, body) for $file, or null when there's no such file here */
	public function Serve($file)
	{
		$file = strtolower((string)$file);
		if($file === 'robots.txt') { return array('text/plain; charset=utf-8', $this->Robots()); }
		if($file === 'ads.txt')
		{
			$ads = $this->AdsTxt();
			return $ads === null ? null : array('text/plain; charset=utf-8', $ads);
		}
		if($this->settings->Get('search_noindex')) { return null; }   # no sitemaps for a site search engines are kept out of
		if($file === 'sitemap.xml') { return array('application/xml; charset=utf-8', $this->Index()); }
		if(preg_match('/^sitemap-(pages|members|imports)(?:-([1-9][0-9]{0,5}))?\.xml$/', $file, $m))
		{
			$body = $this->Sitemap($m[1], isset($m[2]) ? (int)$m[2] : 1);
			return $body === null ? null : array('application/xml; charset=utf-8', $body);
		}
		return null;
	}

	public function Robots()
	{
		if($this->settings->Get('search_noindex')) { return "# This site asks search engines not to index it (Site Settings)\nUser-agent: *\nDisallow: /\n"; }
		$out = "User-agent: *\nCrawl-delay: 10\n";
		foreach(self::DISALLOW as $path) { $out .= "Disallow: ".$this->sitePath.$path."\n"; }
		return $out."\nSitemap: ".$this->base."sitemap.xml\n";
	}

	/** @return string|null */
	public function AdsTxt()
	{
		$text = trim(str_replace("\r", '', (string)$this->settings->Get('ads_txt')));
		if($text !== '') { return $text."\n"; }
		$client = (string)$this->settings->Get('adsense_client');
		if(preg_match('/^ca-(pub-\d+)$/', $client, $m)) { return "google.com, ".$m[1].", DIRECT, ".self::ADSENSE_TAG_ID."\n"; }
		return null;
	}

	public function Index()
	{
		$maps = array('pages');
		foreach(array('members', 'imports') as $kind)
		{
			$pieces = (int)ceil($this->Count($kind) / $this->per);
			for($n = 1; $n <= $pieces; $n++) { $maps[] = "$kind-$n"; }
		}
		$out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
		foreach($maps as $map) { $out .= '<sitemap><loc>'.self::Xml($this->base."sitemap-$map.xml").'</loc></sitemap>'."\n"; }
		return $out."</sitemapindex>\n";
	}

	/** @return string|null one sitemap, or null past the last piece */
	public function Sitemap($kind, $piece = 1)
	{
		$urls = array();
		if($kind === 'pages')
		{
			if($piece !== 1) { return null; }
			foreach(self::PAGES as $page) { $urls[] = array($this->site.$page, null); }
		}
		else
		{
			if($piece > max(1, (int)ceil($this->Count($kind) / $this->per))) { return null; }
			foreach($this->Rows($kind, ($piece - 1) * $this->per) as $row)
			{
				$url = $kind === 'members' ? $this->site.'opt/userstats.php?func=alluserlists&user='.rawurlencode($row[0])
					: $this->site.'opt/userstats.php?func=useraplist&row='.(int)$row[0];
				$urls[] = array($url, $row[1]);
			}
		}
		$out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
		foreach($urls as $u)
		{
			$lastmod = (is_string($u[1]) && preg_match('/^\d{4}-\d{2}-\d{2}/', $u[1], $d)) ? '<lastmod>'.$d[0].'</lastmod>' : '';
			$out .= '<url><loc>'.self::Xml($u[0]).'</loc>'.$lastmod.'</url>'."\n";
		}
		return $out."</urlset>\n";
	}

	private static function Xml($text) { return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8'); }

	const MEMBERS = "FROM files WHERE completed = 1 AND file_user IS NOT NULL AND file_user <> ''";

	private function Count($kind)
	{
		if(!$this->conn) { return 0; }
		$sql = $kind === 'members' ? "SELECT COUNT(DISTINCT file_user) ".self::MEMBERS : "SELECT COUNT(*) FROM files WHERE completed = 1";
		try { return (int)$this->conn->query($sql)->fetchColumn(); }
		catch(Throwable $e) { return 0; }
	}

	/** @return array of [member's name or import's id, its newest finished import's date] */
	private function Rows($kind, $offset)
	{
		if(!$this->conn) { return array(); }
		$sql = $kind === 'members' ? "SELECT file_user, MAX(file_date) ".self::MEMBERS." GROUP BY file_user ORDER BY file_user"
			: "SELECT id, file_date FROM files WHERE completed = 1 ORDER BY id";
		$sql .= $this->service === 'sqlsrv' ? " OFFSET ".(int)$offset." ROWS FETCH NEXT ".$this->per." ROWS ONLY"
			: " LIMIT ".$this->per." OFFSET ".(int)$offset;
		try { return $this->conn->query($sql)->fetchAll(PDO::FETCH_NUM); }
		catch(Throwable $e) { return array(); }
	}
}
