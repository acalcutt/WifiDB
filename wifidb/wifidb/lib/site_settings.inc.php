<?php
/*
site_settings.inc.php, the settings an administrator changes from the site (admin/settings.php) instead of in
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
	/** name => [type ('bool' or 'secret'), label, help, default when config.inc.php doesn't say] */
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

	/** Where the value comes from: 'saved' (this page), 'config' (config.inc.php) or 'default'. */
	public function Source($name)
	{
		if(!isset(self::SETTINGS[$name])) { throw new InvalidArgumentException("No setting named $name"); }
		$rows = $this->Rows();
		if(array_key_exists($name, $rows)) { return 'saved'; }
		return array_key_exists($name, $this->config) ? 'config' : 'default';
	}

	/** @return bool|string a bool setting as a bool; a secret as plain text ('' if none, or if it can't be read) */
	public function Get($name)
	{
		$source = $this->Source($name);
		list($type, , , $default) = self::SETTINGS[$name];
		if($source === 'saved')
		{
			$raw = (string)$this->rows[$name];
			if($type === 'bool') { return $raw === '1'; }
			$plain = Totp::Unprotect($raw, $this->key);
			return $plain === false ? '' : $plain;
		}
		$value = ($source === 'config') ? $this->config[$name] : $default;
		return $type === 'bool' ? (bool)$value : (string)$value;
	}

	/** For the settings page: each setting with its value (never a secret's, only whether one is set) and its source. */
	public function Describe()
	{
		$out = array();
		foreach(self::SETTINGS as $name => $def)
		{
			list($type, $label, $help) = $def;
			$value = $this->Get($name);
			$out[] = array(
				'name'   => $name,
				'type'   => $type,
				'label'  => $label,
				'help'   => $help,
				'value'  => $type === 'bool' ? $value : null,
				'is_set' => $type === 'secret' ? $value !== '' : null,
				'source' => $this->Source($name),
			);
		}
		return $out;
	}

	/**
	 * Saves what changed. $values: name => true/false for a bool; for a secret, a new value, '' to clear it, or null
	 * (or left out) to keep it. Names it doesn't know are ignored.
	 * @return array the names that changed
	 */
	public function Save($values, $username)
	{
		$changed = array();
		foreach(self::SETTINGS as $name => $def)
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
				if($new === $this->Get($name) && $this->Source($name) === 'saved') { continue; }
				$stored = $new === '' ? '' : Totp::Protect($new, $this->key);
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
