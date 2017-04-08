
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
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
  `id` int(255) NOT NULL,
  `timestamp` varchar(60) CHARACTER SET utf8 NOT NULL,
  `graph_min` varchar(255) CHARACTER SET utf8 NOT NULL,
  `graph_max` varchar(255) CHARACTER SET utf8 NOT NULL,
  `graph_avg` varchar(255) CHARACTER SET utf8 NOT NULL,
  `graph_num` varchar(255) CHARACTER SET utf8 NOT NULL,
  `graph_total` varchar(255) CHARACTER SET utf8 NOT NULL,
  `kmz_min` varchar(255) CHARACTER SET utf8 NOT NULL,
  `kmz_max` varchar(255) CHARACTER SET utf8 NOT NULL,
  `kmz_avg` varchar(255) CHARACTER SET utf8 NOT NULL,
  `kmz_num` varchar(255) CHARACTER SET utf8 NOT NULL,
  `kmz_total` varchar(255) CHARACTER SET utf8 NOT NULL,
  `file_min` varchar(255) CHARACTER SET utf8 NOT NULL,
  `file_max` varchar(255) CHARACTER SET utf8 NOT NULL,
  `file_avg` varchar(255) CHARACTER SET utf8 NOT NULL,
  `file_num` varchar(255) CHARACTER SET utf8 NOT NULL,
  `file_up_totals` varchar(255) CHARACTER SET utf8 NOT NULL,
  `gpx_size` varchar(255) CHARACTER SET utf8 NOT NULL,
  `gpx_num` varchar(255) CHARACTER SET utf8 NOT NULL,
  `gpx_min` varchar(255) CHARACTER SET utf8 NOT NULL,
  `gpx_max` varchar(255) CHARACTER SET utf8 NOT NULL,
  `gpx_avg` varchar(255) CHARACTER SET utf8 NOT NULL,
  `daemon_size` varchar(255) CHARACTER SET utf8 NOT NULL,
  `daemon_num` varchar(255) CHARACTER SET utf8 NOT NULL,
  `daemon_min` varchar(255) CHARACTER SET utf8 NOT NULL,
  `daemon_max` varchar(255) CHARACTER SET utf8 NOT NULL,
  `daemon_avg` varchar(255) CHARACTER SET utf8 NOT NULL,
  `total_aps` varchar(255) CHARACTER SET utf8 NOT NULL,
  `wep_aps` varchar(255) CHARACTER SET utf8 NOT NULL,
  `open_aps` varchar(255) CHARACTER SET utf8 NOT NULL,
  `secure_aps` varchar(255) CHARACTER SET utf8 NOT NULL,
  `nuap` varchar(255) CHARACTER SET utf8 NOT NULL,
  `num_priv_geo` varchar(255) CHARACTER SET utf8 NOT NULL,
  `num_pub_geo` varchar(255) CHARACTER SET utf8 NOT NULL,
  `user` blob NOT NULL,
  `ap_gps_totals` blob NOT NULL,
  `top_ssids` blob NOT NULL,
  `geos` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `annunc`
--

CREATE TABLE `annunc` (
  `id` int(11) NOT NULL,
  `set` tinyint(1) NOT NULL DEFAULT '0',
  `auth` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Annon Coward',
  `title` varchar(120) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Blank',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `body` text COLLATE utf8_unicode_ci NOT NULL,
  `comments` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `boundaries`
--

CREATE TABLE `boundaries` (
  `id` int(255) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `polygon` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daemon_pid_stats`
--

CREATE TABLE `daemon_pid_stats` (
  `id` int(11) NOT NULL,
  `nodename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pidfile` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pidtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pidmem` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pidcmd` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Table structure for table `federation_servers`
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
  `id` int(11) NOT NULL,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `node_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `user` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `size` varchar(14) COLLATE utf8_unicode_ci NOT NULL,
  `aps` int(11) NOT NULL,
  `gps` int(11) NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files_bad`
--

CREATE TABLE `files_bad` (
  `id` int(11) NOT NULL,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `converted` tinyint(1) NOT NULL,
  `thread_id` int(255) NOT NULL DEFAULT '0',
  `node_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `error_msg` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files_importing`
--

CREATE TABLE `files_importing` (
  `id` int(255) NOT NULL,
  `tmp_id` int(255) DEFAULT '0',
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `importing` tinyint(1) NOT NULL,
  `ap` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `tot` varchar(128) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files_tmp`
--

CREATE TABLE `files_tmp` (
  `id` int(11) NOT NULL,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geonames`
--

CREATE TABLE `geonames` (
  `id` int(255) NOT NULL,
  `geonameid` int(255) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `asciiname` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alternatenames` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `latitude` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `longitude` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `feature_class` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `feature_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `country_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cc2` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `admin1_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `admin2_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `admin3_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `admin4_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `population` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `elevation` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `gtopo30` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `timezone` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `mod_date` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_admin1`
--

CREATE TABLE `geonames_admin1` (
  `id` int(11) NOT NULL,
  `admin1` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `asciiname` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `geonameid` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_admin2`
--

CREATE TABLE `geonames_admin2` (
  `id` int(255) NOT NULL,
  `admin2` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `asciiname` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
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
  `ssid` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `mac` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `auth` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `encry` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `sectype` int(11) NOT NULL,
  `radio` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
  `chan` int(11) NOT NULL,
  `session_id` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `BTx` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `OTx` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `NT` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `FA` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `LA` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lat` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N 0.0000',
  `long` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E 0.0000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_gps`
--

CREATE TABLE `live_gps` (
  `id` int(255) NOT NULL,
  `lat` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `long` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `sats` int(25) NOT NULL,
  `hdp` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alt` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `geo` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `kmh` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `mph` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `track` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `time` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_signals`
--

CREATE TABLE `live_signals` (
  `id` int(11) NOT NULL,
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `signal` int(11) NOT NULL,
  `rssi` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `gps_id` int(11) NOT NULL,
  `time_stamp` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_titles`
--

CREATE TABLE `live_titles` (
  `id` int(255) NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `notes` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_users`
--

CREATE TABLE `live_users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

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
=======
CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `level` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `prefix` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manufactures`
--

CREATE TABLE `manufactures` (
  `id` int(11) NOT NULL,
  `manuf` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `address` varchar(9) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `nodename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `daemon` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `interval` int(11) NOT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nextrun` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(255) NOT NULL,
  `daemon_state` int(2) NOT NULL,
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `apswithgps` int(255) NOT NULL,
  `node_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

CREATE TABLE `share_waypoints` (
  `id` int(255) NOT NULL,
  `author` varchar(255) CHARACTER SET utf8 NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `gcid` varchar(255) CHARACTER SET utf8 NOT NULL,
  `notes` text CHARACTER SET utf8 NOT NULL,
  `cat` varchar(255) CHARACTER SET utf8 NOT NULL,
  `type` varchar(255) CHARACTER SET utf8 NOT NULL,
  `diff` double(3,2) NOT NULL,
  `terain` double(3,2) NOT NULL,
  `lat` varchar(255) CHARACTER SET utf8 NOT NULL,
  `long` varchar(255) CHARACTER SET utf8 NOT NULL,
  `link` varchar(255) CHARACTER SET utf8 NOT NULL,
  `c_date` datetime NOT NULL,
  `u_date` datetime NOT NULL,
  `pvt_id` int(255) NOT NULL,
  `shared_by` varchar(255) CHARACTER SET utf8 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_imports`
--

CREATE TABLE `user_imports` (
  `id` int(255) NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `points` text COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `aps` int(11) NOT NULL,
  `gps` int(11) NOT NULL,
  `NewAPPercent` int(255) NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  `converted` tinyint(4) NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `GPSBOX_NORTH` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `GPSBOX_SOUTH` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `GPSBOX_EAST` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `GPSBOX_WEST` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_info`
--

CREATE TABLE `user_info` (
  `id` int(255) NOT NULL,
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
  `website` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Vis_ver` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `apikey` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_login_hashes`
--

CREATE TABLE `user_login_hashes` (
  `id` int(255) NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `utime` int(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_stats`
--

CREATE TABLE `user_stats` (
  `id` int(255) NOT NULL,
  `newest` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `largest` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_validate`
--

CREATE TABLE `user_validate` (
  `id` int(255) NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `code` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_waypoints`
--

CREATE TABLE `user_waypoints` (
  `id` int(255) NOT NULL,
  `author` varchar(255) CHARACTER SET utf8 NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `shared_by` varchar(255) CHARACTER SET utf8 NOT NULL,
  `gcid` varchar(255) CHARACTER SET utf8 NOT NULL,
  `notes` text CHARACTER SET utf8 NOT NULL,
  `cat` varchar(255) CHARACTER SET utf8 NOT NULL,
  `type` varchar(255) CHARACTER SET utf8 NOT NULL,
  `diff` double(3,2) NOT NULL,
  `terain` double(3,2) NOT NULL,
  `lat` varchar(255) CHARACTER SET utf8 NOT NULL,
  `long` varchar(255) CHARACTER SET utf8 NOT NULL,
  `link` varchar(255) CHARACTER SET utf8 NOT NULL,
  `share` tinyint(1) NOT NULL,
  `share_id` int(255) NOT NULL,
  `c_date` datetime NOT NULL,
  `u_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wandering_aps`
--

CREATE TABLE wifi.wandering_aps
(
  id INT PRIMARY KEY AUTO_INCREMENT,
  aphash VARCHAR(255),
  wander_rating int(255),
  lat_nw VARCHAR(255),
  long_nw VARCHAR(255),
  lat_ne VARCHAR(255),
  long_ne VARCHAR(255),
  lat_se VARCHAR(255),
  long_se VARCHAR(255),
  lat_sw VARCHAR(255),
  long_sw VARCHAR(255)
);

--
-- Table structure for table `wifi_gps`
--

CREATE TABLE `wifi_gps` (
  `id` int(255) NOT NULL,
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
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wifi_pointers`
--

CREATE TABLE `wifi_pointers` (
  `id` int(255) NOT NULL,
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
  `wander_rating` int(255),
  `signal_high` int(11) NOT NULL,
  `rssi_high` int(11) NOT NULL,
  `alt` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `manuf` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wifi_signals`
--

CREATE TABLE `wifi_signals` (
  `id` int(11) NOT NULL,
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `signal` int(11) NOT NULL,
  `rssi` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `gps_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'WiFiDB',
  `time_stamp` datetime(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `annunc`
--
ALTER TABLE `annunc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title` (`title`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `boundaries`
--
ALTER TABLE `boundaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `daemon_pid_stats`
--
ALTER TABLE `daemon_pid_stats`
  ADD PRIMARY KEY (`id`);

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
  ADD UNIQUE KEY `file` (`file`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `files_bad`
--
ALTER TABLE `files_bad`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hash` (`hash`);

--
-- Indexes for table `files_importing`
--
ALTER TABLE `files_importing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hash` (`hash`);

--
-- Indexes for table `files_tmp`
--
ALTER TABLE `files_tmp`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hash` (`hash`);

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
  ADD KEY `feature class` (`feature class`),
  ADD KEY `feature code` (`feature code`),
  ADD KEY `country code` (`country code`),
  ADD KEY `admin1 code` (`admin1 code`),
  ADD KEY `admin2 code` (`admin2 code`),
  ADD KEY `admin3 code` (`admin3 code`),
  ADD KEY `admin4 code` (`admin4 code`),
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
>>>>>>> ac-wifidb-prod

-- Dump completed on 2015-08-30 17:12:56
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
-- Indexes for table `user_validate`
--
ALTER TABLE `user_validate`
  ADD UNIQUE KEY `username` (`username`),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8922;
--
-- AUTO_INCREMENT for table `DB_stats`
--
ALTER TABLE `DB_stats`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10389;
--
-- AUTO_INCREMENT for table `files_bad`
--
ALTER TABLE `files_bad`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=964;
--
-- AUTO_INCREMENT for table `files_importing`
--
ALTER TABLE `files_importing`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=414;
--
-- AUTO_INCREMENT for table `files_tmp`
--
ALTER TABLE `files_tmp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3315;
--
-- AUTO_INCREMENT for table `geonames`
--
ALTER TABLE `geonames`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19818540;
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
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=259071;
--
-- AUTO_INCREMENT for table `live_gps`
--
ALTER TABLE `live_gps`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=713553;
--
-- AUTO_INCREMENT for table `live_signals`
--
ALTER TABLE `live_signals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1423890;
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `share_waypoints`
--
ALTER TABLE `share_waypoints`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_imports`
--
ALTER TABLE `user_imports`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10401;
--
-- AUTO_INCREMENT for table `user_info`
--
ALTER TABLE `user_info`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267;
--
-- AUTO_INCREMENT for table `user_login_hashes`
--
ALTER TABLE `user_login_hashes`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;
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
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17184204;
--
-- AUTO_INCREMENT for table `wifi_pointers`
--
ALTER TABLE `wifi_pointers`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3199425;
--
-- AUTO_INCREMENT for table `wifi_signals`
--
ALTER TABLE `wifi_signals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95514750;
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
