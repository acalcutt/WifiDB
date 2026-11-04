<?php
/*
notify.inc.php, notifications: what happened (an import finished, a new member, a failure), as an alert in the member's
Inbox and, for whoever chose it, by e-mail (wdbmail::notify sends what Deliver() says). E-mail is also what a member
cannot read without signing in (validation, password reset), which doesn't go through here.

Each kind of notification has a type below. It says who is told (the person it is about, the administrators, or
everyone who opted in), whether the Inbox alert is on or off until the member chooses, and whether it can also be
e-mailed, which is off until they choose (notification_prefs: in_app, email). Administrator and security alerts can't be
switched off in the Inbox.

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class Notifier
{
	/**
	 * type => [label, audience, Inbox default for a member who has not chosen, whether a member may turn the Inbox alert
	 * off, whether it can be e-mailed (sent through wdbmail, which mails it; e-mail is off until chosen)]
	 */
	const TYPES = array(
		'import_started'  => array('Your import started',       'owner',  1, true,  true),
		'import_finished' => array('Your import finished',      'owner',  1, true,  true),
		'import_failed'   => array('Your import failed',        'owner',  1, true,  true),
		'export'          => array('New map data exports',      'all',    0, true,  true),
		'new_user'        => array('A new member joined',       'admins', 1, true,  true),
		'admin_alert'     => array('Errors and warnings',       'admins', 1, false, true),
		'security'        => array('Sign-in and security changes', 'owner',  1, false, false),
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
		return count($this->Deliver($type, $title, $body, $link, $users)['inbox']);
	}

	/**
	 * Notify(), and who also wants it by e-mail (for the caller to send: wdbmail::notify). The same members are asked
	 * either way: the person it is about, or the type's audience.
	 * @return array inbox => usernames given the alert, email => usernames who chose e-mail for this type
	 */
	public function Deliver($type, $title, $body = '', $link = '', $users = null)
	{
		if(!isset(self::TYPES[$type])) { throw new InvalidArgumentException("Unknown notification type $type"); }
		list(, $audience, $default, $optional, $emailable) = self::TYPES[$type];

		$candidates = ($users === null) ? $this->Audience($audience) : array_values(array_unique(array_map('strval', (array)$users)));
		$inbox = $optional ? $this->Wanting($candidates, $type, 'in_app', $default) : $candidates;
		$email = $emailable ? $this->Wanting($candidates, $type, 'email', 0) : array();

		$title = mb_substr(trim((string)$title), 0, 255);
		$body = (string)$body;
		$link = self::CleanLink($link);
		$now = $this->Now();
		foreach($inbox as $user)
		{
			$this->Run("INSERT INTO notifications (username, type, title, body, link, created) VALUES (?, ?, ?, ?, ?, ?)",
				array($user, $type, $title, $body, $link, $now));
		}
		return array('inbox' => $inbox, 'email' => $email);
	}

	private function Enabled()
	{
		return "disabled = '0' AND validated = '0'";
	}

	/** Every enabled member, or the enabled administrators */
	private function Audience($audience)
	{
		$where = $this->Enabled();
		if($audience === 'admins') { $where .= " AND admin = '1'"; }
		$everyone = array();
		foreach($this->Run("SELECT username FROM user_info WHERE $where")->fetchAll(PDO::FETCH_COLUMN) as $user)
		{
			$everyone[] = (string)$user;
		}
		return $everyone;
	}

	/** Of these members, the ones who want this type ($column: in_app or email): their own choice if they made one, otherwise the default. */
	private function Wanting($users, $type, $column, $default)
	{
		if(!$users) { return $users; }
		$chosen = array();
		foreach($this->Run("SELECT username, $column FROM notification_prefs WHERE type = ?", array($type))->fetchAll(PDO::FETCH_ASSOC) as $row)
		{
			$chosen[(string)$row['username']] = (int)$row[$column];
		}
		return array_values(array_filter($users, function ($user) use ($chosen, $default) {
			return (isset($chosen[$user]) ? $chosen[$user] : $default) === 1;
		}));
	}

	/**
	 * For the preferences page: each type the member can choose something about.
	 * @return array [type => [label, on (Inbox), inbox_optional, email, emailable]]
	 */
	public function Prefs($username)
	{
		$chosen = array();
		foreach($this->Run("SELECT type, in_app, email FROM notification_prefs WHERE username = ?", array($username))->fetchAll(PDO::FETCH_ASSOC) as $row)
		{
			$chosen[$row['type']] = $row;
		}
		$out = array();
		foreach(self::TYPES as $type => $info)
		{
			if(!$info[3] && !$info[4]) { continue; }
			$out[$type] = array(
				'label' => $info[0],
				'on' => !$info[3] || (isset($chosen[$type]) ? (int)$chosen[$type]['in_app'] === 1 : $info[2] === 1),
				'inbox_optional' => $info[3],
				'email' => $info[4] && isset($chosen[$type]) && (int)$chosen[$type]['email'] === 1,
				'emailable' => $info[4],
			);
		}
		return $out;
	}

	/** The Inbox alert for a type, on or off. @return bool false for a type that can't be switched off */
	public function SetPref($username, $type, $on)
	{
		if(!isset(self::TYPES[$type]) || !self::TYPES[$type][3]) { return false; }
		$this->Save($username, $type, $on, null);
		return true;
	}

	/** E-mail for a type, on or off. @return bool false for a type that is never e-mailed */
	public function SetEmail($username, $type, $on)
	{
		if(!isset(self::TYPES[$type]) || !self::TYPES[$type][4]) { return false; }
		$this->Save($username, $type, null, $on);
		return true;
	}

	/** One row per member and type; null keeps that choice as it was (or the default). */
	private function Save($username, $type, $inApp, $email)
	{
		$row = $this->Run("SELECT in_app, email FROM notification_prefs WHERE username = ? AND type = ?", array($username, $type))->fetch(PDO::FETCH_ASSOC);
		$inApp = $inApp !== null ? ($inApp ? 1 : 0) : ($row ? (int)$row['in_app'] : self::TYPES[$type][2]);
		$email = $email !== null ? ($email ? 1 : 0) : ($row ? (int)$row['email'] : 0);
		$this->Run("DELETE FROM notification_prefs WHERE username = ? AND type = ?", array($username, $type));
		$this->Run("INSERT INTO notification_prefs (username, type, in_app, email) VALUES (?, ?, ?, ?)", array($username, $type, $inApp, $email));
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
