<?php
/*
site_settings.inc.php, the settings an administrator changes from the site (admin/settings.php, logins.php, mail.php) instead of in
config.inc.php. config.inc.php still gives each one its starting value, under the same name; once an administrator saves
it, the row in site_settings wins. Secrets (an API key) are encrypted with two_factor_key, like the authenticator secrets,
and are never shown again, only whether one is set.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/
require_once dirname(__FILE__).'/totp.inc.php';

class SiteSettings
{
	/**
	 * name => [type ('bool', 'text', 'lines' (text of several lines), 'secret' or 'choice'), label, help, default when
	 * config.inc.php doesn't say, and for a choice its options: value => label]
	 */
	const SETTINGS = array(
		'signups_open'      => array('bool', 'Sign-ups are open',
			'Anyone can create an account. Off: the sign-up form says sign-ups are closed; members already signed up are not affected.', 1),
		'email_validation'  => array('bool', 'New accounts confirm their e-mail',
			'A new member has to click the link in an e-mail before they can sign in. Needs working mail (tools/utilites/check_mail.php).', 1),
		'cleantalk_enabled' => array('bool', 'Check sign-ups and messages with CleanTalk',
			'Sends the e-mail address, IP address and name of each new sign-up, and private messages from members without a track record, to CleanTalk (cleantalk.org), and refuses what it calls spam. If CleanTalk cannot be reached, they go through.', 0),
		'cleantalk_key'     => array('secret', 'CleanTalk access key',
			'From your CleanTalk dashboard. Saving checks it with CleanTalk.', ''),
		'backup_daily'      => array('bool', 'Make a backup every night',
			'At about 03:30 (tools/cron/backups daily). The newest daily ones are kept (backup_keep in config.inc.php, 7 unless set); backups made by hand are never removed. A big database makes big backups: check the backups folder has room.', 1),
		'backup_s3_endpoint' => array('text', 'Copy backups to S3: address',
			'An S3-compatible service (Garage, MinIO, AWS), such as https://s3.example.com, without the bucket. Each nightly backup is copied there (the newest 30 are kept); others can be copied from the Backups page. Empty: no copies.', ''),
		'backup_s3_region'  => array('text', 'S3 region',
			'Garage: the s3_region in its garage.toml (usually garage). AWS: the bucket\'s region.', 'garage'),
		'backup_s3_bucket'  => array('text', 'S3 bucket', 'Keep it private: backups hold e-mail addresses and password hashes.', ''),
		'backup_s3_prefix'  => array('text', 'S3 folder',
			'The folder in the bucket, so several sites can share one. Empty: this site\'s domain.', ''),
		'backup_s3_key_id'  => array('text', 'S3 key ID', 'A key that can only reach this bucket.', ''),
		'backup_s3_secret'  => array('secret', 'S3 secret key', 'Saving checks the bucket with these keys.', ''),
		'files_s3_endpoint' => array('text', 'Shared files in S3: address',
			'An S3-compatible service (Garage, MinIO, AWS), without the bucket, for the files every node needs: each upload is put there before it is queued, and a node importing an upload it doesn\'t have fetches it. For several nodes on one database. Empty: uploads stay on the disk of the node they came in on.', ''),
		'files_s3_region'   => array('text', 'Shared files S3 region',
			'Garage: the s3_region in its garage.toml (usually garage). AWS: the bucket\'s region.', 'garage'),
		'files_s3_bucket'   => array('text', 'Shared files S3 bucket',
			'Not the backups\' bucket. Keep it private: uploads are members\' own files.', ''),
		'files_s3_prefix'   => array('text', 'Shared files S3 folder',
			'The folder in the bucket, so several sites can share one. Empty: this site\'s domain.', ''),
		'files_s3_key_id'   => array('text', 'Shared files S3 key ID', 'A key that can only reach this bucket.', ''),
		'files_s3_secret'   => array('secret', 'Shared files S3 secret key', 'Saving checks the bucket with these keys.', ''),
		'analytics_id'      => array('text', 'Google Analytics measurement ID',
			'G-XXXXXXXXXX, or paste the whole tag Google gives you. Empty: no analytics.', ''),
		'adsense_client'    => array('text', 'AdSense publisher ID',
			'ca-pub-… (or pub-…), from AdSense, Account, Account information. Empty: no ads.', ''),
		'adsense_slot'      => array('text', 'AdSense ad unit',
			'The data-ad-slot number of the display ad unit shown at the foot of each page (or paste its code).', ''),
		'adsense_slot_menu' => array('text', 'AdSense ad unit under the menu',
			'The mobile theme shows a second ad under its menu: that ad unit\'s number (or its code). Empty: none there.', ''),
		'ads_txt'           => array('lines', 'ads.txt',
			'Served at /ads.txt, for ad networks to check who may sell the ad space. Empty: the AdSense line for the publisher ID above (none without one).', ''),
		'site_description'  => array('text', 'Site description',
			"What search engines and shared links show under the site's name.",
			'Vistumbler WiFiDB is a global open-source wireless network database. Upload, map, and analyze WiFi access points.'),
		'search_noindex'    => array('bool', 'Keep search engines out',
			'For a test or staging copy: every page asks search engines not to index it, robots.txt turns crawlers away, and there is no sitemap.', 0),
		'google_site_verification' => array('text', 'Google Search Console verification',
			'The code in the HTML tag Search Console gives to prove the site is yours (or paste the whole tag).', ''),
		'bing_site_verification'   => array('text', 'Bing Webmaster Tools verification',
			'The code in the msvalidate.01 tag Bing gives (or paste the whole tag).', ''),
		'geonames_refresh'  => array('choice', 'Refresh the GeoNames places',
			"Downloads GeoNames' files again and brings the places nearby up to date (tools/cron/geonames, checked each night; one node does it). A refresh of every place downloads about 400 MB and takes a while, but the places stay in use throughout. Off: only when someone runs tools/utilites/load_geonames.php.",
			'off', array('off' => 'Off', 'monthly' => 'Every month', 'quarterly' => 'Every 3 months')),
		'geonames_set'      => array('choice', 'Which GeoNames places',
			"What a refresh loads: every place GeoNames has (towns, landmarks, hills, lakes; about 12 million), or only towns with at least so many people.",
			'allCountries', array('allCountries' => 'Every place', 'cities500' => 'Towns of 500 or more', 'cities1000' => 'Towns of 1,000 or more',
				'cities5000' => 'Towns of 5,000 or more', 'cities15000' => 'Towns of 15,000 or more')),
		'map_dem_url'       => array('text', 'Map elevation tiles',
			"Where the mobile theme's map gets the elevation it draws contour lines from: the address of elevation tiles (PNG or WebP) with {z}, {x} and {y} in it, or of their TileJSON (such as a pmtiles-swarm tiles.json, #torrent= and all), which says where the tiles are and how they're encoded. The contours read the tiles over HTTPS, not from the swarm. Empty: no contour lines.",
			'https://tiles.wifidb.net/data/jaxa_terrarium/{z}/{x}/{y}.png'),
		'map_dem_encoding'  => array('choice', 'Map elevation tiles: encoding',
			"How the elevation is stored in the tiles' colours. As the TileJSON says: its encoding, or Terrarium for a tile address or a TileJSON that doesn't say.",
			'auto', array('auto' => 'As the TileJSON says', 'terrarium' => 'Terrarium', 'mapbox' => 'Terrain-RGB (Mapbox)')),
		'map_dem_maxzoom'   => array('text', 'Map elevation tiles: highest zoom',
			"The most detailed zoom of the elevation tiles the contour lines read (closer in, they're drawn from that one): 0 to 22. Higher draws finer lines but downloads more. Empty: the TileJSON's maxzoom, or 11 for a tile address.",
			''),
		'analytics_consent' => array('choice', "Visitors' consent to analytics and ad cookies",
			"Ask where it's required: only visitors in the EU and the rest of the European Economic Area, the UK and Switzerland see the cookie banner (by the country of their address, looked up on this server; anyone whose country can't be told is asked too); everyone else isn't asked. Ask every visitor: everyone sees the banner. Don't ask: nobody does, which is only allowed where consent isn't required. Until a visitor allows them, Google is told not to use cookies for analytics or personalized ads.",
			'region', array('region' => "Ask where it's required", 'ask' => 'Ask every visitor', 'off' => "Don't ask")),
	);

	/**
	 * A value as an administrator might paste it, made into what's kept: an ID found in the tag Google gives, for
	 * example. @return array (the value, '' when it's fine or else what's wrong with it)
	 */
	public static function Clean($name, $value)
	{
		$value = trim((string)$value);
		if($value === '') { return array('', ''); }
		switch($name)
		{
			case 'google_site_verification':
			case 'bing_site_verification':
				if(preg_match('/content\s*=\s*["\']([^"\']*)["\']/i', $value, $m)) { $value = trim($m[1]); }
				return preg_match('/^[A-Za-z0-9_\-]{8,128}$/', $value) ? array($value, '')
					: array($value, "That isn't a site verification code (letters, numbers, - and _).");
			case 'map_dem_url':
				# a tile address has all three of {z}, {x} and {y}; a TileJSON's has none
				$parts = (strpos($value, '{z}') !== false) + (strpos($value, '{x}') !== false) + (strpos($value, '{y}') !== false);
				return preg_match('#^https?://[^\s"\'<>\\\\]+$#i', $value) && ($parts === 0 || $parts === 3)
					? array($value, '') : array($value, 'The elevation tiles need a web address: of the tiles, with {z}, {x} and {y} in it, or of their TileJSON.');
			case 'map_dem_maxzoom':
				return preg_match('/^\d{1,2}$/', $value) && (int)$value <= 22 ? array((string)(int)$value, '')
					: array($value, 'The highest zoom is a number from 0 to 22.');
			case 'site_description':
				$value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
				return mb_strlen($value) <= 300 ? array($value, '') : array($value, 'The site description can be at most 300 characters.');
			case 'ads_txt':
				$value = trim(str_replace("\r", '', $value));
				if(strlen($value) > 20000) { return array($value, 'ads.txt can be at most 20,000 characters.'); }
				return preg_match('/[\x00-\x08\x0b-\x1f\x7f]/', $value) ? array($value, 'ads.txt has characters it can\'t.') : array($value, '');
			case 'analytics_id':
				return preg_match('/\b(G-[A-Z0-9]{4,20})\b/i', $value, $m) ? array(strtoupper($m[1]), '')
					: array($value, "That isn't a Google Analytics measurement ID (G- and letters and numbers).");
			case 'adsense_client':
				return preg_match('/\b(?:ca-)?pub-(\d{10,20})\b/', $value, $m) ? array('ca-pub-'.$m[1], '')
					: array($value, "That isn't an AdSense publisher ID (ca-pub- and a number).");
			case 'adsense_slot':
			case 'adsense_slot_menu':
				if(preg_match('/data-ad-slot\s*=\s*["\']?(\d{5,20})/', $value, $m) || preg_match('/^(\d{5,20})$/', $value, $m)) { return array($m[1], ''); }
				return array($value, "That isn't an AdSense ad unit number.");
			case 'smtp_host':
			case 'signup_smtp_host':
				return preg_match('/^[^\s"\'<>]+$/', $value) ? array($value, '') : array($value, "That isn't a mail server's name.");
			case 'smtp_port':
			case 'signup_smtp_port':
				return preg_match('/^\d{1,5}$/', $value) && (int)$value >= 1 && (int)$value <= 65535 ? array((string)(int)$value, '')
					: array($value, "$value isn't a port number.");
			case 'smtp_from':
			case 'smtp_replyto':
			case 'signup_smtp_from':
			case 'signup_smtp_replyto':
			case 'DKIM_identity':
				return filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? array($value, '') : array($value, "$value isn't an e-mail address.");
			case 'smtp_from_name':
				return preg_match('/[\x00-\x1f\x7f]/', $value) ? array($value, 'The From name has characters it can\'t.') : array($value, '');
			case 'DKIM_domain':
				$value = strtolower(rtrim($value, '.'));
				return preg_match('/^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z0-9-]{2,}$/', $value) ? array($value, '') : array($value, "$value isn't a domain.");
			case 'DKIM_selector':
				return preg_match('/^[A-Za-z0-9]([A-Za-z0-9._-]*[A-Za-z0-9])?$/', $value) ? array($value, '')
					: array($value, 'A selector is letters, numbers, dots and dashes.');
		}
		return array($value, '');
	}

	/**
	 * Sending mail (tools/daemon/lib/wdbmail.inc.php), saved from admin/mail.php instead. The names are config.inc.php's,
	 * so a site that set them there starts from those. A sign-up account setting left empty is the main account's.
	 */
	const MAIL_SETTINGS = array(
		'smtp_host'      => array('text', 'Mail server', 'Such as smtp.gmail.com.', ''),
		'smtp_port'      => array('text', 'Port', '587 with STARTTLS, 465 with SSL/TLS.', '587'),
		'smtp_secure'    => array('choice', 'Encryption', '', 'tls',
			array('tls' => 'STARTTLS (usually port 587)', 'ssl' => 'SSL/TLS (usually port 465)', '' => 'None (a server on your own network)')),
		'smtp_auth'      => array('bool', 'Sign in to the mail server',
			'Off only for a server that takes this one\'s mail without a password.', 1),
		'smtp_authtype'  => array('choice', 'How to sign in', '', '',
			array('' => 'With the password: the best way the server offers', 'LOGIN' => 'With the password: LOGIN',
				'PLAIN' => 'With the password: PLAIN', 'CRAM-MD5' => 'With the password: CRAM-MD5',
				'XOAUTH2' => 'With a Google account (OAuth), connected below: Gmail and Google Workspace')),
		'smtp_user'      => array('text', 'Username', '', ''),
		'smtp_pass'      => array('secret', 'Password',
			'Gmail and Google Workspace need an app password (Google account, Security, App passwords), not the account\'s own.', ''),
		'smtp_from'      => array('text', 'From address',
			'Gmail sends only as the account\'s own address or one of its aliases.', ''),
		'smtp_from_name' => array('text', 'From name', 'Shown with the address, such as WiFiDB. Empty: the address alone.', ''),
		'smtp_replyto'   => array('text', 'Reply-To address', 'Empty: replies go to the From address.', ''),
		'wifidb_email_updates' => array('bool', 'Send notification mail',
			'Members who asked for it in their preferences get mail about new members, imports and exports, and administrators about imports that failed. The notifications on the site itself are made either way.', 1),
		'signup_smtp_host'   => array('text', 'Sign-up account\'s mail server', '', ''),
		'signup_smtp_port'    => array('text', 'Sign-up account\'s port', '', ''),
		'signup_smtp_secure'  => array('choice', 'Sign-up account\'s encryption', '', '',
			array('' => 'The main account\'s', 'tls' => 'STARTTLS', 'ssl' => 'SSL/TLS', 'none' => 'None')),
		'signup_smtp_user'    => array('text', 'Sign-up account\'s username', '', ''),
		'signup_smtp_pass'    => array('secret', 'Sign-up account\'s password', '', ''),
		'signup_smtp_from'    => array('text', 'Sign-up account\'s From address', '', ''),
		'signup_smtp_replyto' => array('text', 'Sign-up account\'s Reply-To address', '', ''),
		'smtp_oauth_client_id' => array('text', 'Google OAuth client ID',
			'From the Google Cloud project that the mail accounts connect through (a Web application client).', ''),
		'smtp_oauth_secret'    => array('secret', 'Google OAuth client secret', '', ''),
		'DKIM_domain'    => array('text', 'Signing domain',
			'The domain mail is signed for: the part after @ in the From address, or a parent of it. Empty: mail is sent unsigned.', ''),
		'DKIM_selector'  => array('text', 'Selector',
			'A name for the key, so a domain can have several, and a new key can be published before the old one is retired.', 'wifidb'),
		'DKIM_identity'  => array('text', 'Signing identity', 'Normally the From address. Empty: the domain.', ''),
		'DKIM_private_string' => array('secret', 'Signing key',
			'Kept in the database, so every server signs with it. Empty: the key file DKIM_private names in config.inc.php, if any.', ''),
	);

	/** The Google accounts connected on admin/mail.php (lib/mail_oauth.inc.php): saved by connecting one, not typed in */
	const MAIL_TOKENS = array(
		'smtp_oauth_token'          => array('secret', 'Google account connected for mail', '', ''),
		'smtp_oauth_account'        => array('text', 'Google account connected for mail: its address', '', ''),
		'signup_smtp_oauth_token'   => array('secret', 'Google account connected for sign-up mail', '', ''),
		'signup_smtp_oauth_account' => array('text', 'Google account connected for sign-up mail: its address', '', ''),
	);

	/**
	 * config.inc.php's mail settings, with what was saved on admin/mail.php over them (for wdbmail::Mailer). A sign-up
	 * account setting saved empty is taken out, so it is the main account's, as one left out of config.inc.php is.
	 * email_validation (Site Settings) comes too, for the mail check.
	 */
	public function MailConfig(array $config)
	{
		foreach(self::MAIL_SETTINGS + self::MAIL_TOKENS + array('email_validation' => self::SETTINGS['email_validation']) as $name => $def)
		{
			if($this->Source($name) !== 'saved') { continue; }
			$value = $this->Get($name);
			if($value === '' && strpos($name, 'signup_') === 0) { unset($config[$name]); continue; }
			if($name === 'smtp_port' || $name === 'signup_smtp_port') { $value = (int)$value; }
			if($name === 'signup_smtp_secure' && $value === 'none') { $value = ''; }
			$config[$name] = $value;
		}
		return $config;
	}

	/** Signing in with another site's account (lib/linked_logins.inc.php), saved from admin/logins.php instead. */
	const LOGIN_SETTINGS = array(
		'login_google_enabled'     => array('bool', 'Sign in with Google', '', 0),
		'login_google_client_id'   => array('text', 'Google client ID', '', ''),
		'login_google_secret'      => array('secret', 'Google client secret', '', ''),
		'login_github_enabled'     => array('bool', 'Sign in with GitHub', '', 0),
		'login_github_client_id'   => array('text', 'GitHub client ID', '', ''),
		'login_github_secret'      => array('secret', 'GitHub client secret', '', ''),
		'login_discord_enabled'    => array('bool', 'Sign in with Discord', '', 0),
		'login_discord_client_id'  => array('text', 'Discord client ID', '', ''),
		'login_discord_secret'     => array('secret', 'Discord client secret', '', ''),
		'login_facebook_enabled'   => array('bool', 'Sign in with Facebook', '', 0),
		'login_facebook_client_id' => array('text', 'Facebook app ID', '', ''),
		'login_facebook_secret'    => array('secret', 'Facebook app secret', '', ''),
	);

	private $conn;
	private $config;
	private $key;
	private $clock;
	private $rows = null;

	/**
	 * @param PDO $conn
	 * @param array $config config.inc.php: the starting values, and two_factor_key for the secrets
	 * @param callable|null $clock returns the time as a Unix timestamp (for tests)
	 */
	public function __construct($conn, $config = array(), $clock = null)
	{
		$this->conn = $conn;
		$this->config = is_array($config) ? $config : array();
		$this->key = isset($this->config['two_factor_key']) ? (string)$this->config['two_factor_key'] : '';
		$this->clock = $clock;
	}

	/** Saved rows, read once. A database that hasn't been migrated yet has none, and everything comes from config. */
	private function Rows()
	{
		if($this->rows === null)
		{
			$this->rows = array();
			try
			{
				foreach($this->conn->query("SELECT name, value FROM site_settings")->fetchAll(PDO::FETCH_ASSOC) as $row)
				{
					$this->rows[$row['name']] = $row['value'];
				}
			}
			catch(Throwable $e)
			{
				$this->rows = array();
			}
		}
		return $this->rows;
	}

	/** @return array [type, label, help, default] */
	public static function Definition($name)
	{
		if(isset(self::SETTINGS[$name])) { return self::SETTINGS[$name]; }
		if(isset(self::LOGIN_SETTINGS[$name])) { return self::LOGIN_SETTINGS[$name]; }
		if(isset(self::MAIL_SETTINGS[$name])) { return self::MAIL_SETTINGS[$name]; }
		if(isset(self::MAIL_TOKENS[$name])) { return self::MAIL_TOKENS[$name]; }
		throw new InvalidArgumentException("No setting named $name");
	}

	/** Where the value comes from: 'saved' (this page), 'config' (config.inc.php) or 'default'. */
	public function Source($name)
	{
		self::Definition($name);
		$rows = $this->Rows();
		if(array_key_exists($name, $rows)) { return 'saved'; }
		return array_key_exists($name, $this->config) ? 'config' : 'default';
	}

	/** @return bool|string a bool setting as a bool; text and a secret as plain text ('' if a secret can't be read) */
	public function Get($name)
	{
		$source = $this->Source($name);
		$def = self::Definition($name);
		list($type, , , $default) = $def;
		if($source === 'saved')
		{
			$raw = (string)$this->rows[$name];
			if($type === 'bool') { return $raw === '1'; }
			if($type === 'choice') { return isset($def[4][$raw]) ? $raw : (string)$default; }
			if($type === 'text' || $type === 'lines') { return $raw; }
			$plain = Totp::Unprotect($raw, $this->key);
			return $plain === false ? '' : $plain;
		}
		$value = ($source === 'config') ? $this->config[$name] : $default;
		if($type === 'choice') { return isset($def[4][(string)$value]) ? (string)$value : (string)$default; }
		return $type === 'bool' ? (bool)$value : (string)$value;
	}

	/**
	 * For the settings page: each setting with its value (never a secret's, only whether one is set) and its source.
	 * @param array|null $settings the definitions to describe; the Site Settings page's (SETTINGS) unless given
	 */
	public function Describe($settings = null)
	{
		$out = array();
		foreach($settings === null ? self::SETTINGS : $settings as $name => $def)
		{
			list($type, $label, $help) = $def;
			$value = $this->Get($name);
			$out[] = array(
				'name'   => $name,
				'type'   => $type,
				'label'  => $label,
				'help'   => $help,
				'value'  => $type === 'secret' ? null : $value,
				'is_set' => $type === 'secret' ? $value !== '' : null,
				'source' => $this->Source($name),
				'options' => isset($def[4]) ? $def[4] : null,
			);
		}
		return $out;
	}

	/**
	 * Saves what changed. $values: name => true/false for a bool; a string for text; for a secret, a new value, '' to clear it, or null
	 * (or left out) to keep it. Names it doesn't know are ignored.
	 * @return array the names that changed
	 */
	public function Save($values, $username)
	{
		$changed = array();
		foreach(self::SETTINGS + self::LOGIN_SETTINGS + self::MAIL_SETTINGS + self::MAIL_TOKENS as $name => $def)
		{
			if(!array_key_exists($name, $values) || $values[$name] === null) { continue; }
			$type = $def[0];
			if($type === 'bool')
			{
				$new = (bool)$values[$name];
				if($this->Source($name) === 'saved' && $this->Get($name) === $new) { continue; }
				$stored = $new ? '1' : '0';
			}
			else
			{
				$new = trim((string)$values[$name]);
				if($type === 'choice' && !isset($def[4][$new])) { continue; }   # not one of its options
				$plain = ($type !== 'secret');
				if($new === $this->Get($name) && ($plain || $this->Source($name) === 'saved')) { continue; }
				$stored = ($new === '' || $plain) ? $new : Totp::Protect($new, $this->key);
			}
			$this->Store($name, $stored, $username);
			$changed[] = $name;
		}
		$this->rows = null;
		return $changed;
	}

	private function Store($name, $stored, $username)
	{
		$now = gmdate('Y-m-d H:i:s', $this->clock ? call_user_func($this->clock) : time());
		$exists = $this->conn->prepare("SELECT COUNT(*) FROM site_settings WHERE name = ?");
		$exists->execute(array($name));
		if((int)$exists->fetchColumn() > 0)
		{
			$prep = $this->conn->prepare("UPDATE site_settings SET value = ?, updated_by = ?, updated_at = ? WHERE name = ?");
			$prep->execute(array($stored, $username, $now, $name));
		}
		else
		{
			$prep = $this->conn->prepare("INSERT INTO site_settings (name, value, updated_by, updated_at) VALUES (?, ?, ?, ?)");
			$prep->execute(array($name, $stored, $username, $now));
		}
	}
}
