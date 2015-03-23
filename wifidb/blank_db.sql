SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "-05:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `wifi`
--

-- --------------------------------------------------------

--
-- Table structure for table `annunc`
--

CREATE TABLE IF NOT EXISTS `annunc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `set` tinyint(1) NOT NULL DEFAULT '0',
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
  `id` int(255) NOT NULL AUTO_INCREMENT,
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
  `id` int(255) NOT NULL AUTO_INCREMENT,
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
  `geos` blob NOT NULL,
  UNIQUE KEY `timestamp` (`timestamp`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE IF NOT EXISTS `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `size` varchar(14) COLLATE utf8_unicode_ci DEFAULT NULL,
  `aps` int(11) NOT NULL,
  `gps` int(11) NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_row` int(11) NOT NULL,
  `user` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`user_row`),
  UNIQUE KEY `file` (`file`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `files_tmp`
--

CREATE TABLE IF NOT EXISTS `files_tmp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  `user` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `importing` tinyint(1) NOT NULL,
  `ap` text COLLATE utf8_unicode_ci NOT NULL,
  `tot` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `row` int(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `geonames`
--

CREATE TABLE IF NOT EXISTS `geonames` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `geonameid` int(255) NOT NULL,
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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin1` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `asciiname` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `geonameid` int(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin1` (`admin1`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_admin2`
--

CREATE TABLE IF NOT EXISTS `geonames_admin2` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `admin2` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `asciiname` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `geonameid` int(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin2` (`admin2`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_country_names`
--

CREATE TABLE IF NOT EXISTS `geonames_country_names` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
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
  KEY `geonamesid` (`geonamesid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `live_aps`
--

CREATE TABLE IF NOT EXISTS `live_aps` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `ssid` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `mac` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `auth` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `encry` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `sectype` int(1) NOT NULL,
  `radio` varchar(7) COLLATE utf8_unicode_ci NOT NULL,
  `chan` int(255) NOT NULL,
  `sig` text COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `BTx` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `OTx` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `NT` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Label` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `FA` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `LA` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lat` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N 0.0000',
  `long` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E 0.0000',
  PRIMARY KEY (`ap_hash`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `live_gps`
--

CREATE TABLE IF NOT EXISTS `live_gps` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
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
  `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `live_signals`
--

CREATE TABLE IF NOT EXISTS `live_signals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `signal` int(11) NOT NULL,
  `rssi` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `gps_id` int(11) NOT NULL,
  `time_stamp` int(255) NOT NULL,
  KEY `id` (`id`),
  KEY `ap_hash` (`ap_hash`,`signal`,`gps_id`,`time_stamp`),
  KEY `FK_ap_hash_gps` (`gps_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `live_titles`
--

CREATE TABLE IF NOT EXISTS `live_titles` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `notes` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(255) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `daemon_state` int(2) NOT NULL,
  `version` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `apswithgps` INT(255) NOT NULL,
  `node_name` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `daemon_state`, `version`, `apswithgps`, `node_name`) VALUES ('1', 1, '0.30 b1 Alpha', 0, '1');

-- --------------------------------------------------------

--
-- Table structure for table `share_waypoints`
--

CREATE TABLE IF NOT EXISTS `share_waypoints` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
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
  `shared_by` varchar(255) CHARACTER SET utf8 NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_imports`
--

CREATE TABLE IF NOT EXISTS `user_imports` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `points` text COLLATE utf8_unicode_ci NOT NULL,
  `notes` text COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `aps` int(11) NOT NULL,
  `gps` int(11) NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `file_id` int(255) DEFAULT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8_unicode_ci NOT NULL,
  KEY `id` (`id`),
  KEY `id_2` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_info`
--

CREATE TABLE IF NOT EXISTS `user_info` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `help` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `uid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  `validated` tinyint(1) NOT NULL DEFAULT '0',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `login_fails` int(255) NOT NULL DEFAULT '0',
  `permissions` varchar(4) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0001',
  `last_login` datetime NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `mail_updates` tinyint(1) NOT NULL DEFAULT '1',
  `schedule` tinyint(1) NOT NULL DEFAULT '1',
  `imports` tinyint(1) NOT NULL DEFAULT '1',
  `kmz` tinyint(1) NOT NULL DEFAULT '1',
  `new_users` tinyint(1) NOT NULL DEFAULT '1',
  `statistics` tinyint(1) NOT NULL DEFAULT '1',
  `announcements` tinyint(1) NOT NULL DEFAULT '1',
  `announce_comment` tinyint(1) NOT NULL DEFAULT '1',
  `geonamed` tinyint(1) NOT NULL DEFAULT '1',
  `pub_geocache` tinyint(1) NOT NULL DEFAULT '1',
  `h_email` tinyint(1) NOT NULL DEFAULT '1',
  `join_date` datetime NOT NULL,
  `friends` text COLLATE utf8_unicode_ci NOT NULL,
  `foes` text COLLATE utf8_unicode_ci NOT NULL,
  `website` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `Vis_ver` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `apikey` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `uid` (`uid`),
  UNIQUE KEY `email` (`email`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_login_hashes`
--

CREATE TABLE IF NOT EXISTS `user_login_hashes` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `utime` int(64) NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_stats`
--

CREATE TABLE IF NOT EXISTS `user_stats` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `newest` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `largest` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_validate`
--

CREATE TABLE IF NOT EXISTS `user_validate` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `code` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `username` (`username`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_waypoints`
--

CREATE TABLE IF NOT EXISTS `user_waypoints` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
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
  `u_date` datetime NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `gcid` (`gcid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

-- --------------------------------------------------------

--
-- Table structure for table `wifi_gps`
--

CREATE TABLE IF NOT EXISTS `wifi_gps` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `lat` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `long` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `sats` int(2) NOT NULL,
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
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `ssid` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `mac` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `chan` int(255) DEFAULT NULL,
  `sectype` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `radio` varchar(13) COLLATE utf8_unicode_ci NOT NULL,
  `auth` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `encry` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  `geonames_id` int(255) NOT NULL,
  `admin1_id` int(255) NOT NULL,
  `admin2_id` int(255) NOT NULL,
  `lat` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N 0.0000',
  `long` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'E 0.0000',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `BTx` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `OTx` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `NT` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Ad-Hoc',
  `label` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Unknown',
  `LA` datetime NOT NULL,
  `FA` datetime NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNKOWN',
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `signal_high` int(255) NOT NULL,
  `rssi_high` int(255) NOT NULL,
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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ap_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `signal` int(11) NOT NULL,
  `rssi` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `gps_id` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci DEFAULT 'WiFiDB',
  `time_stamp` int(255) NOT NULL,
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
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nodename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pidfile` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pidtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pidmem` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pidcmd` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

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

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE IF NOT EXISTS `schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nodename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `daemon` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `interval` int(11) NOT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `nextrun` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=0 ;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `nodename`, `daemon`, `enabled`, `interval`, `status`, `nextrun`) VALUES
(1, '1', 'Import', 1, 10, 'Waiting', '0000-00-00 00:00:00'),
(4, '1', 'Export', 1, 30, 'Waiting', '0000-00-00 00:00:00');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
