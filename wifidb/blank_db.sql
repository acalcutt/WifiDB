SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8;

--
-- Database: `wifi`
--
drop database IF EXISTS wifi;
create database wifi;
use `wifi`;
-- --------------------------------------------------------

--
-- Table structure for table `annunc`
--

CREATE TABLE IF NOT EXISTS `annunc` (
  `id` int NOT NULL AUTO_INCREMENT,
  `set` tinyint NOT NULL DEFAULT '0',
  `auth` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Annon Coward',
  `title` varchar(120) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Blank',
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `body` text COLLATE utf8_unicode_ci NOT NULL,
  `comments` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;


-- --------------------------------------------------------

--
-- Table structure for table `boundaries`
--

CREATE TABLE IF NOT EXISTS `boundaries` (
  `id` int  NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `polygon` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;


-- --------------------------------------------------------

--
-- Table structure for table `DB_stats`
--

CREATE TABLE IF NOT EXISTS `DB_stats` (
  `id` int  NOT NULL AUTO_INCREMENT,
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
  `num_users` int NOT NULL,
  `aps_totals` int NOT NULL,
  `gps_totals` int NOT NULL,
  `top_ssids` blob NOT NULL,
  `geos` blob NOT NULL,
  PRIMARY KEY (`id`, `timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE IF NOT EXISTS `files` (
  `id` int NOT NULL AUTO_INCREMENT,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `node_name` varchar(16) COLLATE utf8_unicode_ci,
  `user` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `converted` tinyint NOT NULL DEFAULT 0,
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `size` varchar(14) COLLATE utf8_unicode_ci NOT NULL,
  `aps` int NOT NULL,
  `gps` int NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `completed` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`, `file`),
  KEY `files` (`file`, `node_name`, `user`, `title`, `date`, `size`, `aps`, `gps`, `hash`, `completed`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `files_bad`
--

CREATE TABLE IF NOT EXISTS `files_bad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `converted` tinyint NOT NULL,
  `thread_id` int  NOT NULL DEFAULT 0,
  `node_name` varchar(255) NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `error_msg` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`, `hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

-- --
-- Table structure for table `files_importing`
--

CREATE TABLE IF NOT EXISTS `files_importing` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tmp_id` int  DEFAULT 0,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `converted` tinyint NOT NULL DEFAULT 0,
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `importing` tinyint NOT NULL,
  `ap` VARCHAR(64) COLLATE utf8_unicode_ci NOT NULL,
  `tot` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`, `hash`),
  KEY `files_importing` ( `tmp_id`, `file`, `user`, `title`, `size`, `date`, `hash`, `importing`, `ap`, `tot` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;
-- --------------------------------------------------------

--
-- Table structure for table `files_tmp`
--

CREATE TABLE IF NOT EXISTS `files_tmp` (
  `id` int NOT NULL AUTO_INCREMENT,
  `file` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `converted` tinyint NOT NULL DEFAULT 0,
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`, `hash`),
  KEY `files_tmp` ( `file`, `user`, `title`, `size`, `date` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;
-- --------------------------------------------------------

--
-- Table structure for table `geonames`
--

CREATE TABLE IF NOT EXISTS `geonames` (
  `id` int  NOT NULL AUTO_INCREMENT,
  `geonameid` int  NOT NULL,
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
  PRIMARY KEY (`geonameid`),
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_admin1`
--

CREATE TABLE IF NOT EXISTS `geonames_admin1` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin1` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `asciiname` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `geonameid` int  NOT NULL,
  PRIMARY KEY (`id`, `admin1`),
  KEY `geonames_admin1` ( `admin1`, `name`, `asciiname`, `geonameid` )
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_admin2`
--

CREATE TABLE IF NOT EXISTS `geonames_admin2` (
  `id` int  NOT NULL AUTO_INCREMENT,
  `admin2` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `asciiname` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `geonameid` int  NOT NULL,
  PRIMARY KEY (`id`, `admin2`),
  KEY `geonames_admin1` ( `admin2`, `name`, `asciiname`, `geonameid` )
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_country_names`
--

CREATE TABLE IF NOT EXISTS `geonames_country_names` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  KEY `id` (`id`),
  KEY `geonames_country_name` (`geonamesid`, `Country`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `live_aps`
--

CREATE TABLE IF NOT EXISTS `live_aps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ssid` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `mac` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `auth` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `encry` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `sectype` int NOT NULL,
  `radio` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
  `chan` int  NOT NULL,
  `user_id` int  NOT NULL,
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `BTx` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `OTx` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `NT` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `FA` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `LA` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lat` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N 0.0000',
  `long` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E 0.0000',
  PRIMARY KEY (`id`, `ap_hash`),
  KEY `live_aps` (`ssid`, `mac`, `auth`, `encry`, `sectype`, `radio`, `chan`, `user_id`, `ap_hash`, `lat`, `long`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

-- --
-- Table structure for table `live_gps`
--

CREATE TABLE IF NOT EXISTS `live_gps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lat` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `long` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `sats` int NOT NULL,
  `hdp` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `alt` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `geo` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `kmh` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `mph` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `track` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `time` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `live_gps` (`lat`, `long`, `sats`, `date`, `time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `live_signals`
--

CREATE TABLE IF NOT EXISTS `live_signals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `signal` int NOT NULL,
  `rssi` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `gps_id` int NOT NULL,
  `time_stamp` int  NOT NULL,
  KEY `id` (`id`),
  KEY `ap_hash` (`ap_hash`, `signal`, `rssi`, `gps_id`, `time_stamp` )
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `live_titles`
--

CREATE TABLE IF NOT EXISTS `live_titles` (
  `id` int  NOT NULL AUTO_INCREMENT,
  `username` int  NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `notes` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  KEY `id` (`id`, `username`, `title`, `notes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `live_users`
--

CREATE TABLE IF NOT EXISTS `live_users` (
  `id` int  NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `live_users` (`id`, `username`, `session_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE IF NOT EXISTS `log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text COLLATE utf8_unicode_ci NOT NULL,
  `level` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `prefix` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE IF NOT EXISTS `manufactures`
(
  id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  manuf VARCHAR(255) NOT NULL,
  address VARCHAR(9) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `manuf` (`manuf`, `address`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `daemon_state` int NOT NULL,
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `node_name` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `settings` (`daemon_state`, `version`, `node_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_imports`
--

CREATE TABLE IF NOT EXISTS `user_imports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `points` text COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `aps` int NOT NULL,
  `gps` int NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `file_id` int  DEFAULT NULL,
  `converted` tinyint NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_imports` (`username`, `points`, `notes`, `title`, `date`, `aps`, `gps`, `hash`, `file_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_info`
--

CREATE TABLE IF NOT EXISTS `user_info` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `help` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `uid` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `disabled` tinyint NOT NULL DEFAULT '0',
  `validated` tinyint NOT NULL DEFAULT '0',
  `locked` tinyint NOT NULL DEFAULT '0',
  `login_fails` int NOT NULL DEFAULT '0',
  `permissions` varchar(4) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0001',
  `last_login` datetime NOT NULL,
  `email` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `mail_updates` tinyint NOT NULL DEFAULT '1',
  `schedule` tinyint NOT NULL DEFAULT '1',
  `imports` int NOT NULL DEFAULT '1',
  `kmz` tinyint NOT NULL DEFAULT '1',
  `new_users` tinyint NOT NULL DEFAULT '1',
  `statistics` tinyint NOT NULL DEFAULT '1',
  `announcements` tinyint NOT NULL DEFAULT '1',
  `announce_comment` tinyint NOT NULL DEFAULT '1',
  `geonamed` tinyint NOT NULL DEFAULT '1',
  `pub_geocache` tinyint NOT NULL DEFAULT '1',
  `h_email` tinyint NOT NULL DEFAULT '1',
  `join_date` datetime NOT NULL,
  `friends` text COLLATE utf8_unicode_ci NOT NULL,
  `foes` text COLLATE utf8_unicode_ci NOT NULL,
  `website` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `Vis_ver` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `apikey` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_info_login` (`username`, `password`, `help`, `uid`, `disabled`, `validated`, `locked`, `login_fails`, `permissions` )
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_login_hashes`
--

CREATE TABLE IF NOT EXISTS `user_login_hashes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `utime` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login_hashes` (`username`, `hash`, `utime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_stats`
--

CREATE TABLE IF NOT EXISTS `user_stats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `newest` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `largest` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_stats` (`newest`, `largest`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_validate`
--

CREATE TABLE IF NOT EXISTS `user_validate` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `code` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`, `username`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

-- --
-- Table structure for table `wifi_gps`
--

CREATE TABLE IF NOT EXISTS `wifi_gps` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lat` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `long` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `sats` int NOT NULL,
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `wifi_pointers`
--

CREATE TABLE IF NOT EXISTS `wifi_pointers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ssid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mac` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `chan` int DEFAULT NULL,
  `sectype` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `radio` varchar(13) COLLATE utf8_unicode_ci NOT NULL,
  `auth` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `encry` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `geonames_id` int  NOT NULL,
  `admin1_id` int  NOT NULL,
  `admin2_id` int  NOT NULL,
  `lat` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N 0.0000',
  `long` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E 0.0000',
  `active` tinyint NOT NULL DEFAULT '0',
  `BTx` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `OTx` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `NT` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Ad-Hoc',
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Unknown',
  `LA` datetime NOT NULL,
  `FA` datetime NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNKOWN',
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `signal_high` int  NOT NULL,
  `rssi_high` int  NOT NULL,
  `alt` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `manuf` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `wifi_signals`
--

CREATE TABLE IF NOT EXISTS `wifi_signals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `signal` int NOT NULL,
  `rssi` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `gps_id` int NOT NULL,
  `file_id` int  NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'WiFiDB',
  `time_stamp` DATETIME,
  PRIMARY KEY (`id`),
  KEY `ap_hash` (`ap_hash`,`signal`,`gps_id`,`username`,`time_stamp`),
  KEY `FK_GPS` (`gps_id`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `daemon_pid_stats`
--

CREATE TABLE IF NOT EXISTS `daemon_pid_stats` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nodename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pidfile` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pidtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pidmem` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pidcmd` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE IF NOT EXISTS `schedule` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nodename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `daemon` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enabled` tinyint NOT NULL,
  `interval` int NOT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nextrun` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

--
-- Insert Initial Data
--

INSERT INTO `settings` (`id`, `daemon_state`, `version`, `apswithgps`, `node_name`) VALUES ('1', 1, '0.30 b1 Alpha', 0, '1');

INSERT INTO `schedule` (`id`, `nodename`, `daemon`, `enabled`, `interval`, `status`, `nextrun`) VALUES
(1, '1', 'Import', 1, 10, 'Waiting', CURRENT_TIMESTAMP+900),
(4, '1', 'Export', 1, 30, 'Waiting', CURRENT_TIMESTAMP+900);