-- phpMyAdmin SQL Dump
-- version 4.5.3.1
-- http://www.phpmyadmin.net
--
-- Host: 172.16.1.111
-- Generation Time: Aug 06, 2018 at 01:41 AM
-- Server version: 10.3.8-MariaDB-1:10.3.8+maria~stretch-log
-- PHP Version: 5.6.30-0+deb8u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wifi`
--

-- --------------------------------------------------------

--
-- Table structure for table `annunc`
--

CREATE TABLE `annunc` (
  `id` int(11) NOT NULL,
  `set` tinyint(1) NOT NULL DEFAULT 0,
  `auth` varchar(32) DEFAULT 'Annon Coward',
  `title` varchar(120) DEFAULT 'Blank',
  `date` timestamp NOT NULL DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Table structure for table `boundaries`
--

CREATE TABLE `boundaries` (
  `id` int(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `polygon` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daemon_pid_stats`
--

CREATE TABLE `daemon_pid_stats` (
  `id` int(11) NOT NULL,
  `nodename` varchar(255) DEFAULT NULL,
  `pidfile` varchar(255) DEFAULT NULL,
  `pid` varchar(255) DEFAULT NULL,
  `pidtime` varchar(255) DEFAULT NULL,
  `pidmem` varchar(255) DEFAULT NULL,
  `pidcmd` varchar(255) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Table structure for table `DB_stats`
--

CREATE TABLE `DB_stats` (
  `id` int(255) NOT NULL,
  `timestamp` varchar(60) DEFAULT NULL,
  `graph_min` varchar(255) DEFAULT NULL,
  `graph_max` varchar(255) DEFAULT NULL,
  `graph_avg` varchar(255) DEFAULT NULL,
  `graph_num` varchar(255) DEFAULT NULL,
  `graph_total` varchar(255) DEFAULT NULL,
  `kmz_min` varchar(255) DEFAULT NULL,
  `kmz_max` varchar(255) DEFAULT NULL,
  `kmz_avg` varchar(255) DEFAULT NULL,
  `kmz_num` varchar(255) DEFAULT NULL,
  `kmz_total` varchar(255) DEFAULT NULL,
  `file_min` varchar(255) DEFAULT NULL,
  `file_max` varchar(255) DEFAULT NULL,
  `file_avg` varchar(255) DEFAULT NULL,
  `file_num` varchar(255) DEFAULT NULL,
  `file_up_totals` varchar(255) DEFAULT NULL,
  `gpx_size` varchar(255) DEFAULT NULL,
  `gpx_num` varchar(255) DEFAULT NULL,
  `gpx_min` varchar(255) DEFAULT NULL,
  `gpx_max` varchar(255) DEFAULT NULL,
  `gpx_avg` varchar(255) DEFAULT NULL,
  `daemon_size` varchar(255) DEFAULT NULL,
  `daemon_num` varchar(255) DEFAULT NULL,
  `daemon_min` varchar(255) DEFAULT NULL,
  `daemon_max` varchar(255) DEFAULT NULL,
  `daemon_avg` varchar(255) DEFAULT NULL,
  `total_aps` varchar(255) DEFAULT NULL,
  `wep_aps` varchar(255) DEFAULT NULL,
  `open_aps` varchar(255) DEFAULT NULL,
  `secure_aps` varchar(255) DEFAULT NULL,
  `nuap` varchar(255) DEFAULT NULL,
  `num_priv_geo` varchar(255) DEFAULT NULL,
  `num_pub_geo` varchar(255) DEFAULT NULL,
  `user` blob NOT NULL,
  `ap_gps_totals` blob NOT NULL,
  `top_ssids` blob NOT NULL,
  `geos` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `node_name` varchar(255) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT 0,
  `prev_ext` varchar(4) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Table structure for table `files_bad`
--

CREATE TABLE `files_bad` (
  `id` int(11) NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `user` varchar(32) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `size` varchar(12) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Table structure for table `files_importing`
--

CREATE TABLE `files_importing` (
  `id` int(255) NOT NULL,
  `tmp_id` int(255) DEFAULT 0,
  `file` varchar(255) DEFAULT NULL,
  `user` varchar(32) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `size` varchar(12) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Table structure for table `files_tmp`
--

CREATE TABLE `files_tmp` (
  `id` int(11) NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `user` varchar(32) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `size` varchar(12) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Table structure for table `geonames`
--

CREATE TABLE `geonames` (
  `id` int(255) NOT NULL,
  `geonameid` int(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `asciiname` varchar(255) DEFAULT NULL,
  `alternatenames` varchar(255) DEFAULT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `feature_class` varchar(255) DEFAULT NULL,
  `feature_code` varchar(255) DEFAULT NULL,
  `country_code` varchar(255) DEFAULT NULL,
  `cc2` varchar(255) DEFAULT NULL,
  `admin1_code` varchar(255) DEFAULT NULL,
  `admin2_code` varchar(255) DEFAULT NULL,
  `admin3_code` varchar(255) DEFAULT NULL,
  `admin4_code` varchar(255) DEFAULT NULL,
  `population` varchar(255) DEFAULT NULL,
  `elevation` varchar(255) DEFAULT NULL,
  `gtopo30` varchar(255) DEFAULT NULL,
  `timezone` varchar(255) DEFAULT NULL,
  `mod_date` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_admin1`
--

CREATE TABLE `geonames_admin1` (
  `id` int(11) NOT NULL,
  `admin1` varchar(64) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `asciiname` varchar(200) DEFAULT NULL,
  `geonameid` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_admin2`
--

CREATE TABLE `geonames_admin2` (
  `id` int(255) NOT NULL,
  `admin2` varchar(64) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `asciiname` varchar(200) DEFAULT NULL,
  `geonameid` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_country_names`
--

CREATE TABLE `geonames_country_names` (
  `id` int(255) NOT NULL,
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
  `EquivalentFipsCode` varchar(255) COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `live_aps`
--

CREATE TABLE `live_aps` (
  `id` int(255) NOT NULL,
  `ssid` varchar(32) DEFAULT NULL,
  `mac` varchar(64) DEFAULT NULL,
  `auth` varchar(64) DEFAULT NULL,
  `encry` varchar(64) DEFAULT NULL,
  `sectype` int(1) NOT NULL,
  `radio` varchar(7) DEFAULT NULL,
  `chan` int(255) NOT NULL,
  `sig` text DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `ap_hash` varchar(255) DEFAULT NULL,
  `BTx` varchar(255) DEFAULT NULL,
  `OTx` varchar(255) DEFAULT NULL,
  `NT` varchar(255) DEFAULT NULL,
  `Label` varchar(255) DEFAULT NULL,
  `FA` varchar(255) DEFAULT NULL,
  `LA` varchar(255) DEFAULT NULL,
  `lat` varchar(255) DEFAULT 'N 0.0000',
  `long` varchar(255) DEFAULT 'E 0.0000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_gps`
--

CREATE TABLE `live_gps` (
  `id` int(255) NOT NULL,
  `lat` varchar(255) DEFAULT NULL,
  `long` varchar(255) DEFAULT NULL,
  `sats` int(25) NOT NULL,
  `hdp` varchar(255) DEFAULT NULL,
  `alt` varchar(255) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  `kmh` varchar(255) DEFAULT NULL,
  `mph` varchar(255) DEFAULT NULL,
  `track` varchar(255) DEFAULT NULL,
  `date` varchar(255) DEFAULT NULL,
  `time` varchar(255) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_signals`
--

CREATE TABLE `live_signals` (
  `id` int(11) NOT NULL,
  `ap_hash` varchar(255) DEFAULT NULL,
  `signal` int(11) NOT NULL,
  `rssi` varchar(255) DEFAULT '0',
  `gps_id` int(11) NOT NULL,
  `time_stamp` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_titles`
--

CREATE TABLE `live_titles` (
  `id` int(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_users`
--

CREATE TABLE `live_users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `title_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `level` varchar(255) DEFAULT NULL,
  `timestamp` varchar(32) DEFAULT NULL,
  `prefix` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manufactures`
--

CREATE TABLE `manufactures` (
  `id` int(11) NOT NULL,
  `manuf` varchar(255) DEFAULT NULL,
  `address` varchar(9) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `nodename` varchar(255) DEFAULT NULL,
  `daemon` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL,
  `interval` int(11) NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  `nextrun` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `nodename`, `daemon`, `enabled`, `interval`, `status`, `nextrun`) VALUES
(1, '1', 'Import', 1, 5, 'Waiting', '2018-08-06 09:44:02'),
(2, '1', 'Export', 1, 30, 'Running', '2018-08-06 10:00:01'),
(3, '1', 'Geoname', 0, 30, 'Waiting', '2017-01-08 04:11:01');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(255) NOT NULL,
  `daemon_state` int(2) NOT NULL,
  `version` varchar(255) DEFAULT NULL,
  `apswithgps` int(255) NOT NULL,
  `node_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `daemon_state`, `version`, `apswithgps`, `node_name`) VALUES
(1, 1, '0.30 b1 Alpha', 0, '1');

-- --------------------------------------------------------

--
-- Table structure for table `share_waypoints`
--

CREATE TABLE `share_waypoints` (
  `id` int(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `gcid` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cat` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `diff` double(3,2) NOT NULL,
  `terain` double(3,2) NOT NULL,
  `lat` varchar(255) DEFAULT NULL,
  `long` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `c_date` datetime NOT NULL,
  `u_date` datetime NOT NULL,
  `pvt_id` int(255) NOT NULL,
  `shared_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_imports`
--

CREATE TABLE `user_imports` (
  `id` int(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `points` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `date` varchar(255) DEFAULT NULL,
  `aps` int(11) DEFAULT NULL,
  `gps` int(11) DEFAULT NULL,
  `NewAPPercent` int(11) NOT NULL DEFAULT 0,
  `hash` varchar(255) DEFAULT NULL,
  `file_id` int(255) DEFAULT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT 0,
  `prev_ext` varchar(4) DEFAULT NULL,
  `GPSBOX_NORTH` varchar(255) DEFAULT NULL,
  `GPSBOX_SOUTH` varchar(255) DEFAULT NULL,
  `GPSBOX_EAST` varchar(255) DEFAULT NULL,
  `GPSBOX_WEST` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_info`
--

CREATE TABLE `user_info` (
  `id` int(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `help` varchar(255) DEFAULT NULL,
  `uid` varchar(255) DEFAULT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT 0,
  `validated` tinyint(1) NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `login_fails` int(255) NOT NULL DEFAULT 0,
  `permissions` varchar(4) DEFAULT '0001',
  `last_login` datetime NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `mail_updates` tinyint(1) NOT NULL DEFAULT 1,
  `schedule` tinyint(1) NOT NULL DEFAULT 1,
  `imports` tinyint(1) NOT NULL DEFAULT 1,
  `kmz` tinyint(1) NOT NULL DEFAULT 1,
  `new_users` tinyint(1) NOT NULL DEFAULT 1,
  `statistics` tinyint(1) NOT NULL DEFAULT 1,
  `announcements` tinyint(1) NOT NULL DEFAULT 1,
  `announce_comment` tinyint(1) NOT NULL DEFAULT 1,
  `geonamed` tinyint(1) NOT NULL DEFAULT 1,
  `pub_geocache` tinyint(1) NOT NULL DEFAULT 1,
  `h_email` tinyint(1) NOT NULL DEFAULT 1,
  `join_date` datetime NOT NULL,
  `friends` text DEFAULT NULL,
  `foes` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `Vis_ver` varchar(255) DEFAULT NULL,
  `apikey` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_login_hashes`
--

CREATE TABLE `user_login_hashes` (
  `id` int(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `utime` int(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_stats`
--

CREATE TABLE `user_stats` (
  `id` int(255) NOT NULL,
  `newest` varchar(255) DEFAULT NULL,
  `largest` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_validate`
--

CREATE TABLE `user_validate` (
  `id` int(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `code` varchar(64) DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp
) ;

-- --------------------------------------------------------

--
-- Table structure for table `user_waypoints`
--

CREATE TABLE `user_waypoints` (
  `id` int(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `shared_by` varchar(255) DEFAULT NULL,
  `gcid` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cat` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `diff` double(3,2) NOT NULL,
  `terain` double(3,2) NOT NULL,
  `lat` varchar(255) DEFAULT NULL,
  `long` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `share` tinyint(1) NOT NULL,
  `share_id` int(255) NOT NULL,
  `c_date` datetime NOT NULL,
  `u_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wifi_gps`
--

CREATE TABLE `wifi_gps` (
  `id` int(255) NOT NULL,
  `lat` varchar(25) DEFAULT NULL,
  `long` varchar(25) DEFAULT NULL,
  `sats` int(2) NOT NULL DEFAULT 0,
  `hdp` varchar(255) DEFAULT NULL,
  `alt` varchar(255) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  `kmh` varchar(255) DEFAULT NULL,
  `mph` varchar(255) DEFAULT NULL,
  `track` varchar(255) DEFAULT NULL,
  `date` varchar(10) DEFAULT NULL,
  `time` varchar(12) DEFAULT NULL,
  `ap_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wifi_pointers`
--

CREATE TABLE `wifi_pointers` (
  `id` int(255) NOT NULL,
  `ssid` varchar(32) DEFAULT NULL,
  `mac` varchar(25) DEFAULT NULL,
  `chan` int(255) DEFAULT NULL,
  `sectype` varchar(1) DEFAULT NULL,
  `radio` varchar(13) DEFAULT NULL,
  `auth` varchar(25) DEFAULT NULL,
  `encry` varchar(25) DEFAULT NULL,
  `geonames_id` int(255) DEFAULT NULL,
  `admin1_id` int(255) DEFAULT NULL,
  `admin2_id` int(255) DEFAULT NULL,
  `country_code` varchar(3) DEFAULT NULL,
  `lat` varchar(32) DEFAULT 'N 0.0000',
  `long` varchar(32) DEFAULT 'E 0.0000',
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `BTx` varchar(255) DEFAULT '0',
  `OTx` varchar(255) DEFAULT '0',
  `NT` varchar(255) DEFAULT 'Ad-Hoc',
  `label` varchar(255) DEFAULT 'Unknown',
  `LA` datetime DEFAULT NULL,
  `FA` datetime DEFAULT NULL,
  `username` varchar(255) DEFAULT 'UNKOWN',
  `ap_hash` varchar(255) DEFAULT NULL,
  `signal_high` int(255) DEFAULT NULL,
  `rssi_high` int(255) DEFAULT NULL,
  `alt` varchar(10) DEFAULT NULL,
  `manuf` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wifi_signals`
--

CREATE TABLE `wifi_signals` (
  `id` int(11) NOT NULL,
  `ap_hash` varchar(255) DEFAULT NULL,
  `signal` int(11) NOT NULL,
  `rssi` varchar(255) DEFAULT '0',
  `gps_id` int(11) NOT NULL,
  `file_id` int(255) NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'WiFiDB',
  `time_stamp` datetime(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--
--
-- Indexes for table `annunc``
--
ALTER TABLE `annunc`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);
  
--
-- Indexes for table `boundaries`
--
ALTER TABLE `boundaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `daemon_pid_stats``
--
ALTER TABLE `daemon_pid_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `DB_stats`
--
ALTER TABLE `DB_stats`
  ADD UNIQUE KEY `timestamp` (`timestamp`),
  ADD KEY `id` (`id`);
  
--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);
  
--
-- Indexes for table `files_bad`
--
ALTER TABLE `files_bad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);
  
--
-- Indexes for table `files_importing``
--
ALTER TABLE `files_importing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);
  
--
-- Indexes for table `files_tmp`
--
ALTER TABLE `files_tmp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `geonames`
--
ALTER TABLE `geonames`
  ADD PRIMARY KEY (`geonameid`),
  ADD KEY `id` (`id`),
  ADD KEY `name` (`name`),
  ADD KEY `asciiname` (`asciiname`),
  ADD KEY `alternatenames` (`alternatenames`),
  ADD KEY `latitude` (`latitude`),
  ADD KEY `longitude` (`longitude`),
  ADD KEY `feature_class` (`feature_class`),
  ADD KEY `feature_code` (`feature_code`),
  ADD KEY `country_code` (`country_code`),
  ADD KEY `admin1_code` (`admin1_code`),
  ADD KEY `admin2_code` (`admin2_code`),
  ADD KEY `admin3_code` (`admin3_code`),
  ADD KEY `admin4_code` (`admin4_code`),
  ADD KEY `population` (`population`),
  ADD KEY `elevation` (`elevation`),
  ADD KEY `timezone` (`timezone`);

--
-- Indexes for table `geonames_admin1`
--
ALTER TABLE `geonames_admin1`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin1` (`admin1`);

--
-- Indexes for table `geonames_admin2`
--
ALTER TABLE `geonames_admin2`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admin2` (`admin2`);

--
-- Indexes for table `geonames_country_names`
--
ALTER TABLE `geonames_country_names`
  ADD KEY `id` (`id`),
  ADD KEY `geonamesid` (`geonamesid`);

--
-- Indexes for table `live_aps`
--
ALTER TABLE `live_aps`
  ADD PRIMARY KEY (`ap_hash`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `live_gps`
--
ALTER TABLE `live_gps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `live_signals`
--
ALTER TABLE `live_signals`
  ADD KEY `id` (`id`),
  ADD KEY `ap_hash` (`ap_hash`,`signal`,`gps_id`,`time_stamp`),
  ADD KEY `FK_ap_hash_gps` (`gps_id`);

--
-- Indexes for table `live_titles`
--
ALTER TABLE `live_titles`
  ADD KEY `id` (`id`);

--
-- Indexes for table `live_users`
--
ALTER TABLE `live_users`
  ADD PRIMARY KEY (`id`,`session_id`),
  ADD KEY `username` (`username`,`session_id`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `manufactures`
--
ALTER TABLE `manufactures`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `share_waypoints`
--
ALTER TABLE `share_waypoints`
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `user_imports`
--
ALTER TABLE `user_imports`
  ADD KEY `id` (`id`),
  ADD KEY `id_2` (`id`);

--
-- Indexes for table `user_info`
--
ALTER TABLE `user_info`
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `uid` (`uid`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `user_login_hashes`
--
ALTER TABLE `user_login_hashes`
  ADD KEY `id` (`id`);

--
-- Indexes for table `user_stats`
--
ALTER TABLE `user_stats`
  ADD UNIQUE KEY `id` (`id`);
  
--
-- Indexes for table `user_validate``
--
ALTER TABLE `user_validate`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `user_waypoints`
--
ALTER TABLE `user_waypoints`
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `gcid` (`gcid`);

--
-- Indexes for table `wifi_gps`
--
ALTER TABLE `wifi_gps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `GPS_INDEX` (`lat`,`sats`,`alt`,`date`,`time`),
  ADD KEY `ap_hash` (`ap_hash`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `wifi_pointers`
--
ALTER TABLE `wifi_pointers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ssid` (`ssid`),
  ADD KEY `mac` (`mac`),
  ADD KEY `chan` (`chan`),
  ADD KEY `sectype` (`sectype`),
  ADD KEY `radio` (`radio`),
  ADD KEY `auth` (`auth`),
  ADD KEY `encry` (`encry`),
  ADD KEY `geonames_id` (`geonames_id`),
  ADD KEY `admin1_id` (`admin1_id`),
  ADD KEY `admin2_id` (`admin2_id`),
  ADD KEY `lat` (`lat`),
  ADD KEY `long` (`long`),
  ADD KEY `active` (`active`),
  ADD KEY `username` (`username`),
  ADD KEY `ap_hash` (`ap_hash`),
  ADD KEY `alt` (`alt`),
  ADD KEY `manuf` (`manuf`);

--
-- Indexes for table `wifi_signals`
--
ALTER TABLE `wifi_signals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ap_hash` (`ap_hash`,`signal`,`gps_id`,`username`,`time_stamp`),
  ADD KEY `FK_GPS` (`gps_id`),
  ADD KEY `id` (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `annunc`
--
ALTER TABLE `annunc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `boundaries`
--
ALTER TABLE `boundaries`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `daemon_pid_stats`
--
ALTER TABLE `daemon_pid_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `DB_stats`
--
ALTER TABLE `DB_stats`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `files_bad`
--
ALTER TABLE `files_bad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `files_importing`
--
ALTER TABLE `files_importing`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `files_tmp`
--
ALTER TABLE `files_tmp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `geonames`
--
ALTER TABLE `geonames`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `geonames_admin1`
--
ALTER TABLE `geonames_admin1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `geonames_admin2`
--
ALTER TABLE `geonames_admin2`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `geonames_country_names`
--
ALTER TABLE `geonames_country_names`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `live_aps`
--
ALTER TABLE `live_aps`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `live_gps`
--
ALTER TABLE `live_gps`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `live_signals`
--
ALTER TABLE `live_signals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `live_titles`
--
ALTER TABLE `live_titles`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `live_users`
--
ALTER TABLE `live_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `manufactures`
--
ALTER TABLE `manufactures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `share_waypoints`
--
ALTER TABLE `share_waypoints`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_imports`
--
ALTER TABLE `user_imports`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_info`
--
ALTER TABLE `user_info`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_login_hashes`
--
ALTER TABLE `user_login_hashes`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_stats`
--
ALTER TABLE `user_stats`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_validate`
--
ALTER TABLE `user_validate`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_waypoints`
--
ALTER TABLE `user_waypoints`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `wifi_gps`
--
ALTER TABLE `wifi_gps`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `wifi_pointers`
--
ALTER TABLE `wifi_pointers`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `wifi_signals`
--
ALTER TABLE `wifi_signals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `live_aps`
--
ALTER TABLE `live_aps`
  ADD CONSTRAINT `FK_ap_hash` FOREIGN KEY (`ap_hash`) REFERENCES `live_signals` (`ap_hash`),
  ADD CONSTRAINT `FK_ap_hash_sig` FOREIGN KEY (`ap_hash`) REFERENCES `live_signals` (`ap_hash`);

--
-- Constraints for table `live_signals`
--
ALTER TABLE `live_signals`
  ADD CONSTRAINT `FK_ap_hash_gps` FOREIGN KEY (`gps_id`) REFERENCES `live_gps` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
