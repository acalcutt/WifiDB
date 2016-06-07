-- MySQL dump 10.14  Distrib 10.0.0-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: wifi
-- ------------------------------------------------------
-- Server version	10.0.0-MariaDB-mariadb1~squeeze-log

DROP DATABASE IF EXISTS `wifi`;
Create DATABASE `wifi`;
Use `wifi`;
--  --
-- Table structure for table `DB_stats`
--

CREATE TABLE `DB_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` int(11) NOT NULL,
  `total_aps` int(11) NOT NULL,
  `wep_aps` int(11) NOT NULL,
  `open_aps` int(11) NOT NULL,
  `secure_aps` int(11) NOT NULL,
  `num_users` int(11) NOT NULL,
  `gps_totals` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dbstats` (`timestamp`,`total_aps`,`wep_aps`,`open_aps`,`secure_aps`,`num_users`,`gps_totals`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `annunc`
--

CREATE TABLE `annunc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `set` tinyint(4) NOT NULL DEFAULT '0',
  `auth` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Annon Coward',
  `title` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Blank',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `body` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `comments` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`title`),
  KEY `annunc` (`set`,`auth`,`title`,`date`,`body`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `boundaries`
--

CREATE TABLE `boundaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `polygon` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `boundaries` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `daemon_pid_stats`
--

CREATE TABLE `daemon_pid_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nodename` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `pidfile` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `pid` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `pidtime` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `pidmem` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `pidcmd` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `nodename` (`nodename`),
  KEY `pidfile` (`pidfile`),
  KEY `pid` (`pid`),
  KEY `pidtime` (`pidtime`),
  KEY `pidmem` (`pidmem`),
  KEY `pidcmd` (`pidcmd`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


--
-- Table structure for table `files`
--

CREATE TABLE `federation_servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `FriendlyName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ServerAddress`varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `APIVersion` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `SharedKey` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `SharedKeyRemote` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `node_name` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `converted` tinyint(4) NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `size` varchar(14) COLLATE utf8_unicode_ci NOT NULL,
  `aps` int(11) NOT NULL,
  `gps` int(11) NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `completed` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`file`),
  KEY `file` (`file`),
  KEY `node_name` (`node_name`),
  KEY `user` (`user`),
  KEY `title` (`title`),
  KEY `date` (`date`),
  KEY `size` (`size`),
  KEY `aps` (`aps`,`gps`,`hash`,`completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `files_bad`
--

CREATE TABLE `files_bad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `converted` tinyint(4) NOT NULL,
  `thread_id` int(11) NOT NULL DEFAULT '0',
  `node_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `error_msg` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `files_importing`
--

CREATE TABLE `files_importing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tmp_id` int(11) DEFAULT '0',
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `converted` tinyint(4) NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `importing` tinyint(4) NOT NULL,
  `ap` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `tot` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`hash`),
  KEY `tmp_id` (`tmp_id`),
  KEY `file` (`file`),
  KEY `user` (`user`),
  KEY `title` (`title`),
  KEY `size` (`size`),
  KEY `date` (`date`),
  KEY `hash` (`hash`),
  KEY `importing` (`importing`),
  KEY `ap` (`ap`),
  KEY `tot` (`tot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `files_tmp`
--

CREATE TABLE `files_tmp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `converted` tinyint(4) NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`hash`),
  KEY `file` (`file`),
  KEY `user` (`user`),
  KEY `title` (`title`),
  KEY `size` (`size`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `geonames`
--

CREATE TABLE `geonames` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `geonameid` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `asciiname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alternatenames` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `latitude` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `longitude` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `feature class` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `feature code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `country code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cc2` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `admin1 code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `admin2 code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `admin3 code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `admin4 code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `population` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `elevation` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `gtopo30` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `timezone` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `mod_date` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`,`geonameid`),
  KEY `id` (`id`),
  KEY `name` (`name`),
  KEY `asciiname` (`asciiname`),
  KEY `alternatenames` (`alternatenames`),
  KEY `latitude` (`latitude`),
  KEY `longitude` (`longitude`),
  KEY `feature class` (`feature class`),
  KEY `feature code` (`feature code`),
  KEY `country code` (`country code`),
  KEY `admin1 code` (`admin1 code`),
  KEY `admin2 code` (`admin2 code`),
  KEY `admin3 code` (`admin3 code`),
  KEY `admin4 code` (`admin4 code`),
  KEY `population` (`population`),
  KEY `elevation` (`elevation`),
  KEY `timezone` (`timezone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `geonames_admin1`
--

CREATE TABLE `geonames_admin1` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin1` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `asciiname` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `geonameid` int(11) NOT NULL,
  PRIMARY KEY (`id`,`admin1`),
  KEY `admin1` (`admin1`),
  KEY `name` (`name`),
  KEY `asciiname` (`asciiname`),
  KEY `geonameid` (`geonameid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `geonames_admin2`
--

CREATE TABLE `geonames_admin2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin2` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `asciiname` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `geonameid` int(11) NOT NULL,
  PRIMARY KEY (`id`,`admin2`),
  KEY `geonames_admin1` (`admin2`,`name`,`asciiname`,`geonameid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `geonames_country_names`
--

CREATE TABLE `geonames_country_names` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ISO` varchar(255) COLLATE utf8_bin NOT NULL,
  `ISO3` varchar(255) COLLATE utf8_bin NOT NULL,
  `ISO-Numeric` varchar(255) COLLATE utf8_bin NOT NULL,
  `fips` varchar(255) COLLATE utf8_bin NOT NULL,
  `Country` varchar(255) COLLATE utf8_bin NOT NULL,
  `Capital` varchar(255) COLLATE utf8_bin NOT NULL,
  `Area` varchar(255) COLLATE utf8_bin NOT NULL,
  `Population` varchar(255) COLLATE utf8_bin NOT NULL,
  `Continent` varchar(255) COLLATE utf8_bin NOT NULL,
  `tld` varchar(255) COLLATE utf8_bin NOT NULL,
  `CurrencyCode` varchar(255) COLLATE utf8_bin NOT NULL,
  `CurrencyName` varchar(255) COLLATE utf8_bin NOT NULL,
  `Phone` varchar(255) COLLATE utf8_bin NOT NULL,
  `Postal Code Format` varchar(255) COLLATE utf8_bin NOT NULL,
  `Postal Code Regex` varchar(255) COLLATE utf8_bin NOT NULL,
  `Languages` varchar(255) COLLATE utf8_bin NOT NULL,
  `geonamesid` varchar(255) COLLATE utf8_bin NOT NULL,
  `neighbors` varchar(255) COLLATE utf8_bin NOT NULL,
  `EquivalentFipsCode` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `geonamesid` (`geonamesid`),
  KEY `Country` (`Country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Table structure for table `live_aps`
--

CREATE TABLE `live_aps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ssid` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `mac` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `auth` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `encry` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `sectype` int(11) NOT NULL,
  `radio` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
  `chan` int(11) NOT NULL,
  `session_id` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `BTx` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `OTx` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `NT` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `Label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `FA` int(11) DEFAULT NULL,
  `LA` int(11) DEFAULT NULL,
  `lat` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N 0.0000',
  `long` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E 0.0000',
  PRIMARY KEY (`id`,`ap_hash`),
  KEY `ssid` (`ssid`),
  KEY `mac` (`mac`),
  KEY `auth` (`auth`),
  KEY `encry` (`encry`),
  KEY `sectype` (`sectype`),
  KEY `radio` (`radio`),
  KEY `chan` (`chan`),
  KEY `session_id` (`session_id`),
  KEY `ap_hash` (`ap_hash`),
  KEY `lat` (`lat`),
  KEY `long` (`long`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `live_gps`
--

CREATE TABLE `live_gps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ap_id` int(11) NOT NULL,
  `lat` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `long` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `sats` int(11) NOT NULL,
  `hdp` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `alt` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `geo` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `kmh` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `mph` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `track` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lat` (`lat`),
  KEY `long` (`long`),
  KEY `sats` (`sats`),
  KEY `time` (`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `live_signals`
--

CREATE TABLE `live_signals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ap_id` int(11) NOT NULL,
  `signal` int(11) NOT NULL,
  `rssi` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `gps_id` int(11) NOT NULL,
  `time_stamp` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ap_hash` (`ap_id`),
  KEY `signal` (`signal`),
  KEY `rssi` (`rssi`),
  KEY `gps_id` (`gps_id`),
  KEY `time_stamp` (`time_stamp`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `live_titles`
--

CREATE TABLE `live_titles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `notes` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `completed` TINYINT DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `title` (`title`),
  KEY `notes` (`notes`),
  KEY `completed` (`completed`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `live_users`
--

CREATE TABLE `live_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`,`session_id`),
  KEY `username` (`username`,`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `level` int(11) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `prefix` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `manufactures`
--

CREATE TABLE `manufactures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `manuf` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `address` varchar(9) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `manuf` (`manuf`),
  KEY `address` (`address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nodename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `daemon` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enabled` tinyint(4) NOT NULL,
  `interval` int(11) NOT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nextrun` int(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `nodename` (`nodename`),
  KEY `daemon` (`daemon`),
  KEY `enabled` (`enabled`),
  KEY `interval` (`interval`),
  KEY `status` (`status`),
  KEY `nextrun` (`nextrun`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `daemon_state` int(11) NOT NULL,
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `apswithgps` int(11) NOT NULL,
  `node_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `daemon_state` (`daemon_state`),
  KEY `version` (`version`),
  KEY `node_name` (`node_name`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `user_imports`
--

CREATE TABLE `user_imports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `points` text COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `aps` int(11) NOT NULL,
  `gps` int(11) NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  `converted` tinyint(4) NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `title` (`title`),
  KEY `date` (`date`),
  KEY `aps` (`aps`),
  KEY `gps` (`gps`),
  KEY `hash` (`hash`),
  KEY `file_id` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `user_info`
--

CREATE TABLE `user_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `help` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `uid` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `disabled` tinyint(4) NOT NULL DEFAULT '0',
  `validated` tinyint(4) NOT NULL DEFAULT '0',
  `locked` tinyint(4) NOT NULL DEFAULT '0',
  `login_fails` int(11) NOT NULL DEFAULT '0',
  `permissions` varchar(4) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0001',
  `last_login` datetime NOT NULL,
  `email` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `mail_updates` tinyint(4) NOT NULL DEFAULT '1',
  `schedule` tinyint(4) NOT NULL DEFAULT '1',
  `imports` int(11) NOT NULL DEFAULT '1',
  `kmz` tinyint(4) NOT NULL DEFAULT '1',
  `new_users` tinyint(4) NOT NULL DEFAULT '1',
  `statistics` tinyint(4) NOT NULL DEFAULT '1',
  `announcements` tinyint(4) NOT NULL DEFAULT '1',
  `announce_comment` tinyint(4) NOT NULL DEFAULT '1',
  `geonamed` tinyint(4) NOT NULL DEFAULT '1',
  `pub_geocache` tinyint(4) NOT NULL DEFAULT '1',
  `h_email` tinyint(4) NOT NULL DEFAULT '1',
  `join_date` datetime NOT NULL,
  `friends` text COLLATE utf8_unicode_ci NOT NULL,
  `foes` text COLLATE utf8_unicode_ci NOT NULL,
  `website` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `Vis_ver` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `apikey` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `username` (`username`),
  KEY `password` (`password`),
  KEY `help` (`help`),
  KEY `uid` (`uid`),
  KEY `disabled` (`disabled`),
  KEY `validated` (`validated`),
  KEY `locked` (`locked`),
  KEY `login_fails` (`login_fails`),
  KEY `permissions` (`permissions`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `user_login_hashes`
--

CREATE TABLE `user_login_hashes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `utime` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `hash` (`hash`),
  KEY `utime` (`utime`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `user_stats`
--

CREATE TABLE `user_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `newest` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `largest` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `newest` (`newest`),
  KEY `largest` (`largest`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `user_validate`
--

CREATE TABLE `user_validate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `code` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `code` (`code`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `wifi_gps`
--

CREATE TABLE `wifi_gps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lat` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `long` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `sats` int(11) NOT NULL,
  `hdp` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alt` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `geo` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `kmh` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `mph` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `track` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `time` varchar(8) COLLATE utf8_unicode_ci NOT NULL,
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `GPS_INDEX` (`lat`,`sats`,`alt`,`date`,`time`),
  KEY `ap_hash` (`ap_hash`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `wifi_pointers`
--

CREATE TABLE `wifi_pointers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ssid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mac` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `chan` int(11) DEFAULT NULL,
  `sectype` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `radio` varchar(13) COLLATE utf8_unicode_ci NOT NULL,
  `auth` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `encry` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `geonames_id` int(11) NOT NULL,
  `admin1_id` int(11) NOT NULL,
  `admin2_id` int(11) NOT NULL,
  `lat` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N 0.0000',
  `long` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E 0.0000',
  `active` tinyint(4) NOT NULL DEFAULT '0',
  `BTx` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `OTx` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `NT` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Ad-Hoc',
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Unknown',
  `LA` datetime NOT NULL,
  `FA` datetime NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNKOWN',
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `signal_high` int(11) NOT NULL,
  `rssi_high` int(11) NOT NULL,
  `alt` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `manuf` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`),
  KEY `ssid` (`ssid`),
  KEY `mac` (`mac`),
  KEY `chan` (`chan`),
  KEY `sectype` (`sectype`),
  KEY `radio` (`radio`),
  KEY `auth` (`auth`),
  KEY `encry` (`encry`),
  KEY `geonames_id` (`geonames_id`),
  KEY `admin1_id` (`admin1_id`),
  KEY `admin2_id` (`admin2_id`),
  KEY `lat` (`lat`),
  KEY `long` (`long`),
  KEY `active` (`active`),
  KEY `username` (`username`),
  KEY `ap_hash` (`ap_hash`),
  KEY `alt` (`alt`),
  KEY `manuf` (`manuf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `wifi_signals`
--

CREATE TABLE `wifi_signals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `signal` int(11) NOT NULL,
  `rssi` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `gps_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'WiFiDB',
  `time_stamp` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ap_hash` (`ap_hash`),
  KEY `signal` (`signal`),
  KEY `gps_id` (`gps_id`),
  KEY `username` (`username`),
  KEY `time_stamp` (`time_stamp`),
  KEY `gps_id_2` (`gps_id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Dump completed on 2015-08-30 17:12:56
--
-- Insert Initial Data
--

INSERT INTO `settings` (`id`, `daemon_state`, `version`, `apswithgps`, `node_name`) VALUES ('1', 1, '0.30 build 2', 0, '1');

INSERT INTO `schedule` (`id`, `nodename`, `daemon`, `enabled`, `interval`, `status`, `nextrun`) VALUES
  (1, '1', 'Import', 1, 10, 'Waiting', CURRENT_TIMESTAMP+900),
  (2, '1', 'Export', 1, 30, 'Waiting', CURRENT_TIMESTAMP+1000),
  (3, '1', 'Geonames', 1, 30, 'Waiting', CURRENT_TIMESTAMP+1200);