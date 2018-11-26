-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: 172.16.1.111
-- Generation Time: Nov 26, 2018 at 11:34 AM
-- Server version: 10.3.9-MariaDB-1:10.3.9+maria~stretch-log
-- PHP Version: 7.2.12-1+0~20181112102304.11+stretch~1.gbp55f215

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dev_wifi`
--

-- --------------------------------------------------------

--
-- Table structure for table `annunc`
--

CREATE TABLE `annunc` (
  `id` int(11) NOT NULL,
  `set` tinyint(1) NOT NULL DEFAULT 0,
  `auth` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Annon Coward',
  `title` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Blank',
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `boundaries`
--

CREATE TABLE `boundaries` (
  `id` int(255) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `polygon` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daemon_pid_stats`
--

CREATE TABLE `daemon_pid_stats` (
  `id` int(11) NOT NULL,
  `nodename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pidfile` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pidtime` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pidmem` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pidcmd` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` bigint(20) NOT NULL,
  `file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `node_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT 0,
  `prev_ext` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `size` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aps` int(11) NOT NULL DEFAULT 0,
  `gps` int(11) NOT NULL DEFAULT 0,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `NewAPPercent` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files_bad`
--

CREATE TABLE `files_bad` (
  `id` bigint(20) NOT NULL,
  `file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `converted` tinyint(1) NOT NULL,
  `thread_id` int(255) NOT NULL DEFAULT 0,
  `node_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_msg` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files_importing`
--

CREATE TABLE `files_importing` (
  `id` bigint(20) NOT NULL,
  `tmp_id` int(255) DEFAULT 0,
  `file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT 0,
  `prev_ext` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `importing` tinyint(1) NOT NULL DEFAULT 0,
  `ap` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tot` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files_tmp`
--

CREATE TABLE `files_tmp` (
  `id` bigint(20) NOT NULL,
  `file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT 0,
  `prev_ext` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geonames`
--

CREATE TABLE `geonames` (
  `id` bigint(20) NOT NULL,
  `geonameid` bigint(20) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asciiname` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alternatenames` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `longitude` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `feature_class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `feature_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cc2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin1_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin2_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin3_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin4_code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `population` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `elevation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gtopo30` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timezone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mod_date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_admin1`
--

CREATE TABLE `geonames_admin1` (
  `id` bigint(20) NOT NULL,
  `admin1` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asciiname` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `geonameid` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_admin2`
--

CREATE TABLE `geonames_admin2` (
  `id` bigint(20) NOT NULL,
  `admin2` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asciiname` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `geonameid` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `geonames_country_names`
--

CREATE TABLE `geonames_country_names` (
  `id` bigint(20) NOT NULL,
  `ISO` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ISO3` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ISO-Numeric` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fips` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Capital` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Population` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Continent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tld` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CurrencyCode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CurrencyName` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Postal Code Format` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Postal Code Regex` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Languages` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `geonamesid` bigint(20) DEFAULT NULL,
  `neighbors` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `EquivalentFipsCode` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_aps`
--

CREATE TABLE `live_aps` (
  `id` int(255) NOT NULL,
  `ssid` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mac` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `encry` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sectype` int(1) NOT NULL,
  `radio` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chan` int(255) NOT NULL,
  `sig` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ap_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `BTx` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `OTx` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `NT` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `FA` datetime DEFAULT NULL,
  `LA` datetime DEFAULT NULL,
  `lat` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'N 0.0000',
  `long` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'E 0.0000'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_gps`
--

CREATE TABLE `live_gps` (
  `id` int(255) NOT NULL,
  `lat` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `long` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sats` int(25) NOT NULL,
  `hdp` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `geo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kmh` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mph` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `track` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_signals`
--

CREATE TABLE `live_signals` (
  `id` int(11) NOT NULL,
  `ap_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signal` int(11) NOT NULL,
  `rssi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `gps_id` int(11) NOT NULL,
  `time_stamp` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_titles`
--

CREATE TABLE `live_titles` (
  `id` int(255) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_users`
--

CREATE TABLE `live_users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `id` int(11) NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manufacturers`
--

CREATE TABLE `manufacturers` (
  `id` int(11) NOT NULL,
  `BSSID` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Manufacturer` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `id` int(11) NOT NULL,
  `nodename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `daemon` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `interval` int(11) NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nextrun` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `nodename`, `daemon`, `enabled`, `interval`, `status`, `nextrun`) VALUES
(1, 'dev', 'Import', 1, 0, 'wait', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(255) NOT NULL,
  `daemon_state` int(2) NOT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apswithgps` int(255) NOT NULL,
  `node_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `daemon_state`, `version`, `apswithgps`, `node_name`) VALUES
(1, 1, '0.40', 0, 'dev');

-- --------------------------------------------------------

--
-- Table structure for table `share_waypoints`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_imports`
--

CREATE TABLE `user_imports` (
  `id` int(255) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `points` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aps` int(11) DEFAULT NULL,
  `gps` int(11) DEFAULT NULL,
  `NewAPPercent` int(11) NOT NULL DEFAULT 0,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_id` int(255) DEFAULT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT 0,
  `prev_ext` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `GPSBOX_NORTH` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `GPSBOX_SOUTH` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `GPSBOX_EAST` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `GPSBOX_WEST` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_info`
--

CREATE TABLE `user_info` (
  `id` int(255) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `help` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disabled` tinyint(1) NOT NULL DEFAULT 0,
  `validated` tinyint(1) NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `login_fails` int(255) NOT NULL DEFAULT 0,
  `permissions` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0001',
  `last_login` datetime DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `join_date` datetime DEFAULT NULL,
  `friends` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Vis_ver` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apikey` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_login_hashes`
--

CREATE TABLE `user_login_hashes` (
  `id` int(255) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `utime` int(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_stats`
--

CREATE TABLE `user_stats` (
  `id` int(255) NOT NULL,
  `newest` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `largest` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_validate`
--

CREATE TABLE `user_validate` (
  `id` int(255) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wifi_ap`
--

CREATE TABLE `wifi_ap` (
  `AP_ID` bigint(20) NOT NULL,
  `BSSID` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SSID` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CHAN` int(11) DEFAULT NULL,
  `AUTH` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ENCR` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SECTYPE` int(11) DEFAULT NULL,
  `RADTYPE` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NETTYPE` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `BTX` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `OTX` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `HighGps_ID` bigint(20) DEFAULT NULL,
  `FirstHist_ID` bigint(20) DEFAULT NULL,
  `LastHist_ID` bigint(20) DEFAULT NULL,
  `HighSig_ID` bigint(20) DEFAULT NULL,
  `HighRSSI_ID` bigint(20) DEFAULT NULL,
  `File_ID` bigint(20) DEFAULT NULL,
  `geonames_id` bigint(20) DEFAULT NULL,
  `admin1_id` bigint(20) DEFAULT NULL,
  `admin2_id` bigint(20) DEFAULT NULL,
  `country_code` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ap_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ModDate` datetime(3) NOT NULL DEFAULT current_timestamp(3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wifi_gps`
--

CREATE TABLE `wifi_gps` (
  `GPS_ID` bigint(20) NOT NULL,
  `File_ID` bigint(20) DEFAULT NULL,
  `File_GPS_ID` bigint(20) DEFAULT NULL,
  `Lat` decimal(9,4) DEFAULT NULL,
  `Lon` decimal(9,4) DEFAULT NULL,
  `NumOfSats` int(11) DEFAULT NULL,
  `HorDilPitch` decimal(10,2) DEFAULT NULL,
  `Alt` decimal(7,2) DEFAULT NULL,
  `Geo` decimal(10,2) DEFAULT NULL,
  `MPH` decimal(6,2) DEFAULT NULL,
  `KPH` decimal(6,2) DEFAULT NULL,
  `TrackAngle` decimal(5,2) DEFAULT NULL,
  `GPS_Date` datetime(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wifi_hist`
--

CREATE TABLE `wifi_hist` (
  `Hist_ID` bigint(20) NOT NULL,
  `AP_ID` bigint(20) DEFAULT NULL,
  `GPS_ID` bigint(20) DEFAULT NULL,
  `File_ID` bigint(20) DEFAULT NULL,
  `Sig` int(11) DEFAULT NULL,
  `RSSI` int(11) DEFAULT NULL,
  `New` tinyint(1) DEFAULT 0,
  `Hist_Date` datetime(3) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for table `manufacturers`
--
ALTER TABLE `manufacturers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `BSSID` (`BSSID`);

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
-- Indexes for table `wifi_ap`
--
ALTER TABLE `wifi_ap`
  ADD PRIMARY KEY (`AP_ID`),
  ADD KEY `HighGps_ID` (`HighGps_ID`),
  ADD KEY `FirstHist_ID` (`FirstHist_ID`),
  ADD KEY `LastHist_ID` (`LastHist_ID`),
  ADD KEY `ap_hash` (`ap_hash`),
  ADD KEY `File_ID` (`File_ID`),
  ADD KEY `HighSig_ID` (`HighSig_ID`),
  ADD KEY `HighRSSI_ID` (`HighRSSI_ID`);

--
-- Indexes for table `wifi_gps`
--
ALTER TABLE `wifi_gps`
  ADD PRIMARY KEY (`GPS_ID`),
  ADD KEY `File_GPS_ID` (`File_GPS_ID`),
  ADD KEY `File_ID` (`File_ID`);

--
-- Indexes for table `wifi_hist`
--
ALTER TABLE `wifi_hist`
  ADD PRIMARY KEY (`Hist_ID`),
  ADD KEY `AP_ID` (`AP_ID`),
  ADD KEY `GPS_ID` (`GPS_ID`),
  ADD KEY `File_ID` (`File_ID`),
  ADD KEY `Hist_Date` (`Hist_Date`);

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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `files_bad`
--
ALTER TABLE `files_bad`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `files_importing`
--
ALTER TABLE `files_importing`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `files_tmp`
--
ALTER TABLE `files_tmp`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `geonames`
--
ALTER TABLE `geonames`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `geonames_admin1`
--
ALTER TABLE `geonames_admin1`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `geonames_admin2`
--
ALTER TABLE `geonames_admin2`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `geonames_country_names`
--
ALTER TABLE `geonames_country_names`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `manufacturers`
--
ALTER TABLE `manufacturers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- AUTO_INCREMENT for table `wifi_ap`
--
ALTER TABLE `wifi_ap`
  MODIFY `AP_ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wifi_gps`
--
ALTER TABLE `wifi_gps`
  MODIFY `GPS_ID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wifi_hist`
--
ALTER TABLE `wifi_hist`
  MODIFY `Hist_ID` bigint(20) NOT NULL AUTO_INCREMENT;

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

--
-- Constraints for table `wifi_ap`
--
ALTER TABLE `wifi_ap`
  ADD CONSTRAINT `wifi_ap_ibfk_1` FOREIGN KEY (`File_ID`) REFERENCES `files` (`id`),
  ADD CONSTRAINT `wifi_ap_ibfk_2` FOREIGN KEY (`HighGps_ID`) REFERENCES `wifi_gps` (`GPS_ID`),
  ADD CONSTRAINT `wifi_ap_ibfk_3` FOREIGN KEY (`FirstHist_ID`) REFERENCES `wifi_hist` (`Hist_ID`),
  ADD CONSTRAINT `wifi_ap_ibfk_4` FOREIGN KEY (`LastHist_ID`) REFERENCES `wifi_hist` (`Hist_ID`),
  ADD CONSTRAINT `wifi_ap_ibfk_5` FOREIGN KEY (`HighSig_ID`) REFERENCES `wifi_hist` (`Hist_ID`),
  ADD CONSTRAINT `wifi_ap_ibfk_6` FOREIGN KEY (`HighRSSI_ID`) REFERENCES `wifi_hist` (`Hist_ID`);

--
-- Constraints for table `wifi_gps`
--
ALTER TABLE `wifi_gps`
  ADD CONSTRAINT `wifi_gps_ibfk_1` FOREIGN KEY (`File_ID`) REFERENCES `files` (`id`);

--
-- Constraints for table `wifi_hist`
--
ALTER TABLE `wifi_hist`
  ADD CONSTRAINT `wifi_hist_ibfk_1` FOREIGN KEY (`AP_ID`) REFERENCES `wifi_ap` (`AP_ID`),
  ADD CONSTRAINT `wifi_hist_ibfk_2` FOREIGN KEY (`GPS_ID`) REFERENCES `wifi_gps` (`GPS_ID`),
  ADD CONSTRAINT `wifi_hist_ibfk_3` FOREIGN KEY (`File_ID`) REFERENCES `files` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
