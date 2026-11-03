-- admin/servers.php: each node's report (tools/daemon/server_status.php, each minute: version, load, memory, disks,
-- services) and its updater's (wifidb-update: the releases it sees, and how an update is going); and the update an
-- administrator asked for, which every node's updater does in turn.

CREATE TABLE `server_status` (
  `node_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reported_at` datetime DEFAULT NULL,
  `info` mediumtext COLLATE utf8mb4_unicode_ci,
  `releases` mediumtext COLLATE utf8mb4_unicode_ci,
  `releases_at` datetime DEFAULT NULL,
  `update_request` int(11) DEFAULT NULL,
  `update_version` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `update_state` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `update_message` text COLLATE utf8mb4_unicode_ci,
  `update_at` datetime DEFAULT NULL,
  PRIMARY KEY (`node_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `server_update_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `backup` smallint(6) NOT NULL DEFAULT 0,
  `backup_taken` smallint(6) NOT NULL DEFAULT 0,
  `requested_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_at` datetime DEFAULT NULL,
  `cancelled` smallint(6) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
