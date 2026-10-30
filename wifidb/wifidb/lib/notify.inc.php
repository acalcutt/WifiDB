<?php
/*
notify.inc.php, in-app notifications: what happened (an import finished, a new member, a failure) shown in the control
panel, so the site does not have to rely on e-mail. E-mail stays for what a member cannot read without signing in
(validation, password reset) and for whoever also asked for it in their e-mail preferences (wdbmail::mail_users).

Each kind of notification has a type below. It says who is told (the person it is about, the administrators, or
everyone who opted in) and whether it is on or off until the member chooses (notification_prefs). Administrator alerts
are not optional.

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class Notifier
{
	/** type => [label, audience, default for a member who has not chosen, whether a member may turn it off] */
	const TYPES = array(
		'import_started'  => array('Your import started',       'owner',  1, true),
		'import_finished' => array('Your import finished',      'owner',  1, true),
		'import_failed'   => array('Your import failed',        'owner',  1, true),
		'export'          => array('New map data exports',      'all',    0, true),
		'new_user'        => array('A new member joined',       'admins', 1, true),
		'admin_alert'     => array('Errors and warnings',       'admins', 1, false),
	);

	private $conn;
	private $clock;

	/**
	 * @param PDO $conn
	 * @param callable|null $clock returns a unix time (for tests)
	 */
	public function __construct($conn, $clock = null)
	{
		$this->conn = $conn;
		$this->clock = $clock;
	}

	private function Now() { return gmdate('Y-m-d H:i:s', $this->clock ? call_user_func($this->clock) : time()); }
	private function IsSqlsrv() { return $this->conn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlsrv'; }

	private function Run($sql, $params = array())
	{
		$stmt = $this->conn->prepare($sql);
		$stmt->execute($params);
		return $stmt;
	}

	/** Only a site address or a web address may become a link; anything else (javascript:, data:) is dropped. */
	public static function CleanLink($link)
	{
		$link = trim((string)$link);
		if($link === '' || strlen($link) > 500) { return ''; }
		if($link[0] === '/' && substr($link, 0, 2) !== '//') { return $link; }
		return preg_match('#^https?://[^\s<>"\']+$#i', $link) ? $link : '';
	}

	/**
	 * Tells members about something. $users names them when the notification is about particular people (the importer);
	 * otherwise the type decides: administrators, or everyone not opted out (or, for a type that is off by default,
	 * everyone opted in).
	 * @return int how many members were told
	 */
	public function Notify($type, $title, $body = '', $link = '', $users = null)
	{
		if(!isset(self::TYPES[$type])) { throw new InvalidArgumentException("Unknown notification type $type"); }
		list(, $audience, $default, $optional) = self::TYPES[$type];

		if($users === null)
		{
			$users = $this->Audience($type, $audience, $default);
		}
		else
		{
			$users = $this->Interested(array_values(array_unique(array_map('strval', (array)$users))), $type, $default, $optional);
		}

		$title = mb_substr(trim((string)$title), 0, 255);
		$body = (string)$body;
		$link = self::CleanLink($link);
		$now = $this->Now();
		foreach($users as $user)
		{
			$this->Run("INSERT INTO notifications (username, type, title, body, link, created) VALUES (?, ?, ?, ?, ?, ?)",
				array($user, $type, $title, $body, $link, $now));
		}
		return count($users);
	}

	private function Enabled()
	{
		return "disabled = '0' AND validated = '0'";
	}

	private function Audience($type, $audience, $default)
	{
		$where = $this->Enabled();
		if($audience === 'admins') { $where .= " AND admin = '1'"; }
		$everyone = array();
		foreach($this->Run("SELECT username FROM user_info WHERE $where")->fetchAll(PDO::FETCH_COLUMN) as $user)
		{
			$everyone[] = (string)$user;
		}
		return $this->Interested($everyone, $type, $default, self::TYPES[$type][3]);
	}

	/** Of these members, the ones who want this type: their own choice if they made one, otherwise the default. */
	private function Interested($users, $type, $default, $optional)
	{
		if(!$optional || !$users) { return $users; }
		$chosen = array();
		foreach($this->Run("SELECT username, in_app FROM notification_prefs WHERE type = ?", array($type))->fetchAll(PDO::FETCH_ASSOC) as $row)
		{
			$chosen[(string)$row['username']] = (int)$row['in_app'];
		}
		return array_values(array_filter($users, function ($user) use ($chosen, $default) {
			return (isset($chosen[$user]) ? $chosen[$user] : $default) === 1;
		}));
	}

	/** @return array [type => [label, on]] for the preferences page; types a member cannot switch are left out */
	public function Prefs($username)
	{
		$chosen = array();
		foreach($this->Run("SELECT type, in_app FROM notification_prefs WHERE username = ?", array($username))->fetchAll(PDO::FETCH_ASSOC) as $row)
		{
			$chosen[$row['type']] = (int)$row['in_app'];
		}
		$out = array();
		foreach(self::TYPES as $type => $info)
		{
			if(!$info[3]) { continue; }
			$out[$type] = array('label' => $info[0], 'on' => isset($chosen[$type]) ? $chosen[$type] === 1 : $info[2] === 1);
		}
		return $out;
	}

	public function SetPref($username, $type, $on)
	{
		if(!isset(self::TYPES[$type]) || !self::TYPES[$type][3]) { return false; }
		$this->Run("DELETE FROM notification_prefs WHERE username = ? AND type = ?", array($username, $type));
		$this->Run("INSERT INTO notification_prefs (username, type, in_app) VALUES (?, ?, ?)", array($username, $type, $on ? 1 : 0));
		return true;
	}

	public function UnreadCount($username)
	{
		return (int)$this->Run("SELECT COUNT(*) FROM notifications WHERE username = ? AND read_at IS NULL", array($username))->fetchColumn();
	}

	/** @return array newest first */
	public function Inbox($username, $limit = 25, $offset = 0)
	{
		$limit = max(1, min(200, (int)$limit));
		$offset = max(0, (int)$offset);
		$page = $this->IsSqlsrv() ? "OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY" : "LIMIT $limit OFFSET $offset";
		return $this->Run("SELECT id, type, title, body, link, created, read_at FROM notifications WHERE username = ? ORDER BY created DESC, id DESC $page",
			array($username))->fetchAll(PDO::FETCH_ASSOC);
	}

	public function Total($username)
	{
		return (int)$this->Run("SELECT COUNT(*) FROM notifications WHERE username = ?", array($username))->fetchColumn();
	}

	/** Always scoped to the member, so one member cannot touch another's. */
	public function MarkRead($username, $id)
	{
		return $this->Run("UPDATE notifications SET read_at = ? WHERE id = ? AND username = ? AND read_at IS NULL", array($this->Now(), (int)$id, $username))->rowCount() > 0;
	}

	public function MarkAllRead($username)
	{
		return $this->Run("UPDATE notifications SET read_at = ? WHERE username = ? AND read_at IS NULL", array($this->Now(), $username))->rowCount();
	}

	public function Delete($username, $id)
	{
		return $this->Run("DELETE FROM notifications WHERE id = ? AND username = ?", array((int)$id, $username))->rowCount() > 0;
	}

	/** Removes read notifications older than $days, and any older than twice that. @return int removed */
	public function Prune($days = 90)
	{
		$days = max(1, (int)$days);
		$now = $this->clock ? call_user_func($this->clock) : time();
		$read = gmdate('Y-m-d H:i:s', $now - $days * 86400);
		$any = gmdate('Y-m-d H:i:s', $now - 2 * $days * 86400);
		return $this->Run("DELETE FROM notifications WHERE (read_at IS NOT NULL AND created < ?) OR created < ?", array($read, $any))->rowCount();
	}
}
