<?php
/*
nodelock.inc.php: a lock held in the database, so work only one node should do at a time (building tiles and GeoJSON,
the nightly backup) is done by whichever node takes it first, however many nodes share the database.

The lock belongs to the connection that took it and goes when that connection closes, so a node that stops doesn't
keep it. SQL Server's sp_getapplock, MariaDB's GET_LOCK and PostgreSQL's advisory locks; SQLite (the unit tests) is
one process, so there the lock is always free. tools/daemon/lock_run.php runs a command under one.
*/
class NodeLock
{
	# GET_LOCK takes names of up to 64 characters (sp_getapplock up to 255); a longer one is hashed
	public static function Key($name)
	{
		$key = 'wifidb:'.$name;
		return strlen($key) > 64 ? 'wifidb:'.sha1($name) : $key;
	}

	# true when this connection now holds the lock, false when someone else does (it doesn't wait)
	public static function Take(PDO $conn, $name)
	{
		$key = self::Key($name);
		switch($conn->getAttribute(PDO::ATTR_DRIVER_NAME))
		{
			case 'mysql':
				return self::One($conn, "SELECT GET_LOCK(?, 0)", $key) === 1;
			case 'pgsql':
				return self::One($conn, "SELECT CASE WHEN pg_try_advisory_lock(hashtext(?)) THEN 1 ELSE 0 END", $key) === 1;
			case 'sqlsrv':
				# 0 granted, 1 granted after waiting; below 0 not granted
				return self::One($conn, "SET NOCOUNT ON; DECLARE @r int; EXEC @r = sp_getapplock @Resource = ?, @LockMode = 'Exclusive', @LockOwner = 'Session', @LockTimeout = 0; SELECT @r", $key) >= 0;
			default:
				return true;
		}
	}

	public static function Release(PDO $conn, $name)
	{
		$key = self::Key($name);
		switch($conn->getAttribute(PDO::ATTR_DRIVER_NAME))
		{
			case 'mysql':
				self::One($conn, "SELECT RELEASE_LOCK(?)", $key);
				break;
			case 'pgsql':
				self::One($conn, "SELECT CASE WHEN pg_advisory_unlock(hashtext(?)) THEN 1 ELSE 0 END", $key);
				break;
			case 'sqlsrv':
				self::One($conn, "SET NOCOUNT ON; DECLARE @r int; EXEC @r = sp_releaseapplock @Resource = ?, @LockOwner = 'Session'; SELECT @r", $key);
				break;
		}
	}

	private static function One(PDO $conn, $sql, $key)
	{
		$prep = $conn->prepare($sql);
		$prep->execute(array($key));
		# SQL Server can put an empty result ahead of the SELECT's
		do
		{
			if($prep->columnCount() > 0) { return (int)$prep->fetchColumn(); }
		}
		while($prep->nextRowset());
		return 0;
	}
}
