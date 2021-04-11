-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: 172.16.1.111:3307
-- Generation Time: Mar 17, 2019 at 12:48 AM
-- Server version: 10.3.9-MariaDB-1:10.3.9+maria~stretch-log
-- PHP Version: 7.2.13-1+0~20181207100540.13+stretch~1.gbpf57305

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `prod_wifi`
--

-- --------------------------------------------------------

--
-- Table structure for table `annunc`
--

CREATE TABLE `annunc` (
  `id` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
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
-- Table structure for table `cell_carriers`
--

CREATE TABLE `cell_carriers` (
  `carrier_id` bigint(20) NOT NULL,
  `mcc` int(3) DEFAULT NULL,
  `mcc_int` int(4) DEFAULT NULL,
  `mnc` varchar(3) DEFAULT NULL,
  `mnc_int` int(4) DEFAULT NULL,
  `iso` varchar(3) DEFAULT NULL,
  `country` varchar(33) DEFAULT NULL,
  `country_code` varchar(4) DEFAULT NULL,
  `network` varchar(72) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `cell_carriers`
--

INSERT INTO `cell_carriers` (`carrier_id`, `mcc`, `mcc_int`, `mnc`, `mnc_int`, `iso`, `country`, `country_code`, `network`) VALUES
(1, 289, 649, '88', 2191, 'ge', 'Abkhazia', '7', 'A-Mobile'),
(2, 289, 649, '68', 1679, 'ge', 'Abkhazia', '7', 'A-Mobile'),
(3, 289, 649, '67', 1663, 'ge', 'Abkhazia', '7', 'Aquafon'),
(4, 412, 1042, '88', 2191, 'af', 'Afghanistan', '93', 'Afghan Telecom Corp. (AT)'),
(5, 412, 1042, '80', 2063, 'af', 'Afghanistan', '93', 'Afghan Telecom Corp. (AT)'),
(6, 412, 1042, '01', 31, 'af', 'Afghanistan', '93', 'Afghan Wireless/AWCC'),
(7, 412, 1042, '40', 1039, 'af', 'Afghanistan', '93', 'Areeba/MTN'),
(8, 412, 1042, '30', 783, 'af', 'Afghanistan', '93', 'Etisalat'),
(9, 412, 1042, '50', 1295, 'af', 'Afghanistan', '93', 'Etisalat'),
(10, 412, 1042, '20', 527, 'af', 'Afghanistan', '93', 'Roshan/TDCA'),
(11, 412, 1042, '03', 63, 'af', 'Afghanistan', '93', 'WaselTelecom (WT)'),
(12, 276, 630, '01', 31, 'al', 'Albania', '355', 'AMC/Cosmote'),
(13, 276, 630, '03', 63, 'al', 'Albania', '355', 'Eagle Mobile'),
(14, 276, 630, '04', 79, 'al', 'Albania', '355', 'PLUS Communication Sh.a'),
(15, 276, 630, '02', 47, 'al', 'Albania', '355', 'Vodafone'),
(16, 603, 1539, '01', 31, 'dz', 'Algeria', '213', 'ATM Mobils'),
(17, 603, 1539, '02', 47, 'dz', 'Algeria', '213', 'Orascom / DJEZZY'),
(18, 603, 1539, '03', 63, 'dz', 'Algeria', '213', 'Oreedo/Wataniya / Nedjma'),
(19, 544, 1348, '11', 287, 'as', 'American Samoa', '684', 'Blue Sky Communications'),
(20, 213, 531, '03', 63, 'ad', 'Andorra', '376', 'Mobiland'),
(21, 631, 1585, '04', 79, 'ao', 'Angola', '244', 'MoviCel'),
(22, 631, 1585, '02', 47, 'ao', 'Angola', '244', 'Unitel'),
(23, 365, 869, '840', 2112, 'ai', 'Anguilla', '1264', 'Cable and Wireless'),
(24, 365, 869, '010', 16, 'ai', 'Anguilla', '1264', 'Digicell / Wireless Vent. Ltd'),
(25, 344, 836, '030', 48, 'ag', 'Antigua and Barbuda', '1268', 'APUA PCS'),
(26, 344, 836, '920', 2336, 'ag', 'Antigua and Barbuda', '1268', 'C & W'),
(27, 344, 836, '930', 2352, 'ag', 'Antigua and Barbuda', '1268', 'DigiCel/Cing. Wireless'),
(28, 722, 1826, '310', 784, 'ar', 'Argentina Republic', '54', 'Claro/ CTI/AMX'),
(29, 722, 1826, '330', 816, 'ar', 'Argentina Republic', '54', 'Claro/ CTI/AMX'),
(30, 722, 1826, '320', 800, 'ar', 'Argentina Republic', '54', 'Claro/ CTI/AMX'),
(31, 722, 1826, '010', 16, 'ar', 'Argentina Republic', '54', 'Compania De Radiocomunicaciones Moviles SA'),
(32, 722, 1826, '070', 112, 'ar', 'Argentina Republic', '54', 'Movistar/Telefonica'),
(33, 722, 1826, '020', 32, 'ar', 'Argentina Republic', '54', 'Nextel'),
(34, 722, 1826, '341', 833, 'ar', 'Argentina Republic', '54', 'Telecom Personal S.A.'),
(35, 722, 1826, '340', 832, 'ar', 'Argentina Republic', '54', 'Telecom Personal S.A.'),
(36, 283, 643, '01', 31, 'am', 'Armenia', '374', 'ArmenTel/Beeline'),
(37, 283, 643, '04', 79, 'am', 'Armenia', '374', 'Karabakh Telecom'),
(38, 283, 643, '10', 271, 'am', 'Armenia', '374', 'Orange'),
(39, 283, 643, '05', 95, 'am', 'Armenia', '374', 'Vivacell'),
(40, 363, 867, '02', 47, 'aw', 'Aruba', '297', 'Digicel'),
(41, 363, 867, '20', 527, 'aw', 'Aruba', '297', 'Digicel'),
(42, 363, 867, '01', 31, 'aw', 'Aruba', '297', 'Setar GSM'),
(43, 505, 1285, '14', 335, 'au', 'Australia', '61', 'AAPT Ltd.'),
(44, 505, 1285, '24', 591, 'au', 'Australia', '61', 'Advanced Comm Tech Pty.'),
(45, 505, 1285, '09', 159, 'au', 'Australia', '61', 'Airnet Commercial Australia Ltd..'),
(46, 505, 1285, '04', 79, 'au', 'Australia', '61', 'Department of Defense'),
(47, 505, 1285, '26', 623, 'au', 'Australia', '61', 'Dialogue Communications Pty Ltd'),
(48, 505, 1285, '12', 303, 'au', 'Australia', '61', 'H3G Ltd.'),
(49, 505, 1285, '06', 111, 'au', 'Australia', '61', 'H3G Ltd.'),
(50, 505, 1285, '88', 2191, 'au', 'Australia', '61', 'Localstar Holding Pty. Ltd'),
(51, 505, 1285, '19', 415, 'au', 'Australia', '61', 'Lycamobile Pty Ltd'),
(52, 505, 1285, '08', 143, 'au', 'Australia', '61', 'Railcorp/Vodafone'),
(53, 505, 1285, '99', 2463, 'au', 'Australia', '61', 'Railcorp/Vodafone'),
(54, 505, 1285, '13', 319, 'au', 'Australia', '61', 'Railcorp/Vodafone'),
(55, 505, 1285, '90', 2319, 'au', 'Australia', '61', 'Singtel Optus'),
(56, 505, 1285, '02', 47, 'au', 'Australia', '61', 'Singtel Optus'),
(57, 505, 1285, '71', 1823, 'au', 'Australia', '61', 'Telstra Corp. Ltd.'),
(58, 505, 1285, '01', 31, 'au', 'Australia', '61', 'Telstra Corp. Ltd.'),
(59, 505, 1285, '11', 287, 'au', 'Australia', '61', 'Telstra Corp. Ltd.'),
(60, 505, 1285, '72', 1839, 'au', 'Australia', '61', 'Telstra Corp. Ltd.'),
(61, 505, 1285, '05', 95, 'au', 'Australia', '61', 'The Ozitel Network Pty.'),
(62, 505, 1285, '16', 367, 'au', 'Australia', '61', 'Victorian Rail Track Corp. (VicTrack)'),
(63, 505, 1285, '07', 127, 'au', 'Australia', '61', 'Vodafone'),
(64, 505, 1285, '03', 63, 'au', 'Australia', '61', 'Vodafone'),
(65, 232, 562, '11', 287, 'at', 'Austria', '43', 'A1 MobilKom'),
(66, 232, 562, '02', 47, 'at', 'Austria', '43', 'A1 MobilKom'),
(67, 232, 562, '09', 159, 'at', 'Austria', '43', 'A1 MobilKom'),
(68, 232, 562, '01', 31, 'at', 'Austria', '43', 'A1 MobilKom'),
(69, 232, 562, '15', 351, 'at', 'Austria', '43', 'T-Mobile/Telering'),
(70, 232, 562, '10', 271, 'at', 'Austria', '43', 'H3G'),
(71, 232, 562, '14', 335, 'at', 'Austria', '43', 'H3G'),
(72, 232, 562, '06', 111, 'at', 'Austria', '43', '3/Orange/One Connect'),
(73, 232, 562, '12', 303, 'at', 'Austria', '43', '3/Orange/One Connect'),
(74, 232, 562, '05', 95, 'at', 'Austria', '43', '3/Orange/One Connect'),
(75, 232, 562, '17', 383, 'at', 'Austria', '43', 'Spusu/Mass Response'),
(76, 232, 562, '07', 127, 'at', 'Austria', '43', 'T-Mobile/Telering'),
(77, 232, 562, '04', 79, 'at', 'Austria', '43', 'T-Mobile/Telering'),
(78, 232, 562, '03', 63, 'at', 'Austria', '43', 'T-Mobile/Telering'),
(79, 232, 562, '19', 415, 'at', 'Austria', '43', 'Tele2'),
(80, 232, 562, '08', 143, 'at', 'Austria', '43', 'A1 MobilKom'),
(81, 232, 562, '13', 319, 'at', 'Austria', '43', 'UPC Austria'),
(82, 400, 1024, '01', 31, 'az', 'Azerbaijan', '994', 'Azercell Telekom B.M.'),
(83, 400, 1024, '04', 79, 'az', 'Azerbaijan', '994', 'Azerfon.'),
(84, 400, 1024, '03', 63, 'az', 'Azerbaijan', '994', 'Caspian American Telecommunications LLC (CATEL)'),
(85, 400, 1024, '02', 47, 'az', 'Azerbaijan', '994', 'J.V. Bakcell GSM 2000'),
(86, 364, 868, '390', 912, 'bs', 'Bahamas', '1242', 'Bahamas Telco. Comp.'),
(87, 364, 868, '30', 783, 'bs', 'Bahamas', '1242', 'Bahamas Telco. Comp.'),
(88, 364, 868, '39', 927, 'bs', 'Bahamas', '1242', 'Bahamas Telco. Comp.'),
(89, 364, 868, '03', 63, 'bs', 'Bahamas', '1242', 'Smart Communications'),
(90, 426, 1062, '01', 31, 'bh', 'Bahrain', '973', 'Batelco'),
(91, 426, 1062, '02', 47, 'bh', 'Bahrain', '973', 'ZAIN/Vodafone'),
(92, 426, 1062, '04', 79, 'bh', 'Bahrain', '973', 'VIVA'),
(93, 470, 1136, '02', 47, 'bd', 'Bangladesh', '880', 'Robi/Aktel'),
(94, 470, 1136, '05', 95, 'bd', 'Bangladesh', '880', 'Citycell'),
(95, 470, 1136, '06', 111, 'bd', 'Bangladesh', '880', 'Citycell'),
(96, 470, 1136, '01', 31, 'bd', 'Bangladesh', '880', 'GrameenPhone'),
(97, 470, 1136, '03', 63, 'bd', 'Bangladesh', '880', 'Orascom/Banglalink'),
(98, 470, 1136, '04', 79, 'bd', 'Bangladesh', '880', 'TeleTalk'),
(99, 470, 1136, '07', 127, 'bd', 'Bangladesh', '880', 'Airtel/Warid'),
(100, 342, 834, '600', 1536, 'bb', 'Barbados', '1246', 'LIME'),
(101, 342, 834, '810', 2064, 'bb', 'Barbados', '1246', 'Cingular Wireless'),
(102, 342, 834, '750', 1872, 'bb', 'Barbados', '1246', 'Digicel'),
(103, 342, 834, '050', 80, 'bb', 'Barbados', '1246', 'Digicel'),
(104, 342, 834, '820', 2080, 'bb', 'Barbados', '1246', 'Sunbeach'),
(105, 257, 599, '03', 63, 'by', 'Belarus', '375', 'BelCel JV'),
(106, 257, 599, '04', 79, 'by', 'Belarus', '375', 'BeST'),
(107, 257, 599, '01', 31, 'by', 'Belarus', '375', 'Mobile Digital Communications'),
(108, 257, 599, '02', 47, 'by', 'Belarus', '375', 'MTS'),
(109, 206, 518, '20', 527, 'be', 'Belgium', '32', 'Base/KPN'),
(110, 206, 518, '01', 31, 'be', 'Belgium', '32', 'Belgacom/Proximus'),
(111, 206, 518, '06', 111, 'be', 'Belgium', '32', 'Lycamobile Belgium'),
(112, 206, 518, '10', 271, 'be', 'Belgium', '32', 'Mobistar/Orange'),
(113, 206, 518, '02', 47, 'be', 'Belgium', '32', 'SNCT/NMBS'),
(114, 206, 518, '05', 95, 'be', 'Belgium', '32', 'Telenet BidCo NV'),
(115, 702, 1794, '67', 1663, 'bz', 'Belize', '501', 'DigiCell'),
(116, 702, 1794, '68', 1679, 'bz', 'Belize', '501', 'International Telco (INTELCO)'),
(117, 616, 1558, '04', 79, 'bj', 'Benin', '229', 'Bell Benin/BBCOM'),
(118, 616, 1558, '02', 47, 'bj', 'Benin', '229', 'Etisalat/MOOV'),
(119, 616, 1558, '05', 95, 'bj', 'Benin', '229', 'GloMobile'),
(120, 616, 1558, '01', 31, 'bj', 'Benin', '229', 'Libercom'),
(121, 616, 1558, '03', 63, 'bj', 'Benin', '229', 'MTN/Spacetel'),
(122, 350, 848, '000', 0, 'bm', 'Bermuda', '1441', 'Bermuda Digital Communications Ltd (BDC)'),
(123, 350, 848, '99', 2463, 'bm', 'Bermuda', '1441', 'CellOne Ltd'),
(124, 350, 848, '10', 271, 'bm', 'Bermuda', '1441', 'DigiCel / Cingular'),
(125, 350, 848, '02', 47, 'bm', 'Bermuda', '1441', 'M3 Wireless Ltd'),
(126, 350, 848, '01', 31, 'bm', 'Bermuda', '1441', 'Telecommunications (Bermuda & West Indies) Ltd (Digicel Bermuda)'),
(127, 402, 1026, '11', 287, 'bt', 'Bhutan', '975', 'B-Mobile'),
(128, 402, 1026, '17', 383, 'bt', 'Bhutan', '975', 'Bhutan Telecom Ltd (BTL)'),
(129, 402, 1026, '77', 1919, 'bt', 'Bhutan', '975', 'TashiCell'),
(130, 736, 1846, '02', 47, 'bo', 'Bolivia', '591', 'Entel Pcs'),
(131, 736, 1846, '01', 31, 'bo', 'Bolivia', '591', 'Viva/Nuevatel'),
(132, 736, 1846, '03', 63, 'bo', 'Bolivia', '591', 'Tigo'),
(133, 218, 536, '90', 2319, 'ba', 'Bosnia & Herzegov.', '387', 'BH Mobile'),
(134, 218, 536, '03', 63, 'ba', 'Bosnia & Herzegov.', '387', 'Eronet Mobile'),
(135, 218, 536, '05', 95, 'ba', 'Bosnia & Herzegov.', '387', 'M-Tel'),
(136, 652, 1618, '04', 79, 'bw', 'Botswana', '267', 'BeMOBILE'),
(137, 652, 1618, '01', 31, 'bw', 'Botswana', '267', 'Mascom Wireless (Pty) Ltd.'),
(138, 652, 1618, '02', 47, 'bw', 'Botswana', '267', 'Orange'),
(139, 724, 1828, '12', 303, 'br', 'Brazil', '55', 'Claro/Albra/America Movil'),
(140, 724, 1828, '38', 911, 'br', 'Brazil', '55', 'Claro/Albra/America Movil'),
(141, 724, 1828, '05', 95, 'br', 'Brazil', '55', 'Claro/Albra/America Movil'),
(142, 724, 1828, '01', 31, 'br', 'Brazil', '55', 'Vivo S.A./Telemig'),
(143, 724, 1828, '33', 831, 'br', 'Brazil', '55', 'CTBC Celular SA (CTBC)'),
(144, 724, 1828, '32', 815, 'br', 'Brazil', '55', 'CTBC Celular SA (CTBC)'),
(145, 724, 1828, '34', 847, 'br', 'Brazil', '55', 'CTBC Celular SA (CTBC)'),
(146, 724, 1828, '08', 143, 'br', 'Brazil', '55', 'TIM'),
(147, 724, 1828, '39', 927, 'br', 'Brazil', '55', 'Nextel (Telet)'),
(148, 724, 1828, '00', 15, 'br', 'Brazil', '55', 'Nextel (Telet)'),
(149, 724, 1828, '30', 783, 'br', 'Brazil', '55', 'Oi (TNL PCS / Oi)'),
(150, 724, 1828, '31', 799, 'br', 'Brazil', '55', 'Oi (TNL PCS / Oi)'),
(151, 724, 1828, '24', 591, 'br', 'Brazil', '55', 'Amazonia Celular S/A'),
(152, 724, 1828, '16', 367, 'br', 'Brazil', '55', 'Brazil Telcom'),
(153, 724, 1828, '54', 1359, 'br', 'Brazil', '55', 'PORTO SEGURO TELECOMUNICACOES'),
(154, 724, 1828, '15', 351, 'br', 'Brazil', '55', 'Sercontel Cel'),
(155, 724, 1828, '07', 127, 'br', 'Brazil', '55', 'CTBC/Triangulo'),
(156, 724, 1828, '19', 415, 'br', 'Brazil', '55', 'Vivo S.A./Telemig'),
(157, 724, 1828, '03', 63, 'br', 'Brazil', '55', 'TIM'),
(158, 724, 1828, '02', 47, 'br', 'Brazil', '55', 'TIM'),
(159, 724, 1828, '04', 79, 'br', 'Brazil', '55', 'TIM'),
(160, 724, 1828, '37', 895, 'br', 'Brazil', '55', 'Unicel do Brasil Telecomunicacoes Ltda'),
(161, 724, 1828, '23', 575, 'br', 'Brazil', '55', 'Vivo S.A./Telemig'),
(162, 724, 1828, '11', 287, 'br', 'Brazil', '55', 'Vivo S.A./Telemig'),
(163, 724, 1828, '10', 271, 'br', 'Brazil', '55', 'Vivo S.A./Telemig'),
(164, 724, 1828, '06', 111, 'br', 'Brazil', '55', 'Vivo S.A./Telemig'),
(165, 348, 840, '570', 1392, 'vg', 'British Virgin Islands', '284', 'Caribbean Cellular'),
(166, 348, 840, '770', 1904, 'vg', 'British Virgin Islands', '284', 'Digicel'),
(167, 348, 840, '170', 368, 'vg', 'British Virgin Islands', '284', 'LIME'),
(168, 528, 1320, '02', 47, 'bn', 'Brunei Darussalam', '673', 'b-mobile'),
(169, 528, 1320, '11', 287, 'bn', 'Brunei Darussalam', '673', 'Datastream (DTSCom)'),
(170, 528, 1320, '01', 31, 'bn', 'Brunei Darussalam', '673', 'Telekom Brunei Bhd (TelBru)'),
(171, 284, 644, '06', 111, 'bg', 'Bulgaria', '359', 'BTC Mobile EOOD (vivatel)'),
(172, 284, 644, '03', 63, 'bg', 'Bulgaria', '359', 'BTC Mobile EOOD (vivatel)'),
(173, 284, 644, '05', 95, 'bg', 'Bulgaria', '359', 'Telenor/Cosmo/Globul'),
(174, 284, 644, '01', 31, 'bg', 'Bulgaria', '359', 'MobilTel AD'),
(175, 613, 1555, '03', 63, 'bf', 'Burkina Faso', '226', 'TeleCel'),
(176, 613, 1555, '01', 31, 'bf', 'Burkina Faso', '226', 'TeleMob-OnaTel'),
(177, 613, 1555, '02', 47, 'bf', 'Burkina Faso', '226', 'Airtel/ZAIN/CelTel'),
(178, 642, 1602, '02', 47, 'bi', 'Burundi', '257', 'Africel / Safaris'),
(179, 642, 1602, '08', 143, 'bi', 'Burundi', '257', 'Lumitel/Viettel'),
(180, 642, 1602, '03', 63, 'bi', 'Burundi', '257', 'Onatel / Telecel'),
(181, 642, 1602, '07', 127, 'bi', 'Burundi', '257', 'Smart Mobile / LACELL'),
(182, 642, 1602, '82', 2095, 'bi', 'Burundi', '257', 'Spacetel / Econet / Leo'),
(183, 642, 1602, '01', 31, 'bi', 'Burundi', '257', 'Spacetel / Econet / Leo'),
(184, 456, 1110, '04', 79, 'kh', 'Cambodia', '855', 'Cambodia Advance Communications Co. Ltd (CADCOMMS)'),
(185, 456, 1110, '02', 47, 'kh', 'Cambodia', '855', 'Smart Mobile'),
(186, 456, 1110, '08', 143, 'kh', 'Cambodia', '855', 'Metfone'),
(187, 456, 1110, '18', 399, 'kh', 'Cambodia', '855', 'MFone/Camshin/Cellcard'),
(188, 456, 1110, '01', 31, 'kh', 'Cambodia', '855', 'Mobitel/Cam GSM'),
(189, 456, 1110, '03', 63, 'kh', 'Cambodia', '855', 'QB/Cambodia Adv. Comms.'),
(190, 456, 1110, '06', 111, 'kh', 'Cambodia', '855', 'Smart Mobile'),
(191, 456, 1110, '05', 95, 'kh', 'Cambodia', '855', 'Smart Mobile'),
(192, 456, 1110, '09', 159, 'kh', 'Cambodia', '855', 'Sotelco/Beeline'),
(193, 624, 1572, '01', 31, 'cm', 'Cameroon', '237', 'MTN'),
(194, 624, 1572, '04', 79, 'cm', 'Cameroon', '237', 'Nextel'),
(195, 624, 1572, '02', 47, 'cm', 'Cameroon', '237', 'Orange'),
(196, 302, 770, '652', 1618, 'ca', 'Canada', '1', 'BC Tel Mobility'),
(197, 302, 770, '630', 1584, 'ca', 'Canada', '1', 'Bell Aliant'),
(198, 302, 770, '651', 1617, 'ca', 'Canada', '1', 'Bell Mobility'),
(199, 302, 770, '610', 1552, 'ca', 'Canada', '1', 'Bell Mobility'),
(200, 302, 770, '670', 1648, 'ca', 'Canada', '1', 'CityWest Mobility'),
(201, 302, 770, '360', 864, 'ca', 'Canada', '1', 'Clearnet'),
(202, 302, 770, '361', 865, 'ca', 'Canada', '1', 'Clearnet'),
(203, 302, 770, '380', 896, 'ca', 'Canada', '1', 'DMTS Mobility'),
(204, 302, 770, '710', 1808, 'ca', 'Canada', '1', 'Globalstar Canada'),
(205, 302, 770, '640', 1600, 'ca', 'Canada', '1', 'Latitude Wireless'),
(206, 302, 770, '370', 880, 'ca', 'Canada', '1', 'FIDO (Rogers AT&T/ Microcell)'),
(207, 302, 770, '320', 800, 'ca', 'Canada', '1', 'mobilicity'),
(208, 302, 770, '702', 1794, 'ca', 'Canada', '1', 'MT&T Mobility'),
(209, 302, 770, '655', 1621, 'ca', 'Canada', '1', 'MTS Mobility'),
(210, 302, 770, '660', 1632, 'ca', 'Canada', '1', 'MTS Mobility'),
(211, 302, 770, '701', 1793, 'ca', 'Canada', '1', 'NB Tel Mobility'),
(212, 302, 770, '703', 1795, 'ca', 'Canada', '1', 'New Tel Mobility'),
(213, 302, 770, '760', 1888, 'ca', 'Canada', '1', 'Public Mobile'),
(214, 302, 770, '657', 1623, 'ca', 'Canada', '1', 'Quebectel Mobility'),
(215, 302, 770, '720', 1824, 'ca', 'Canada', '1', 'Rogers AT&T Wireless'),
(216, 302, 770, '654', 1620, 'ca', 'Canada', '1', 'Sask Tel Mobility'),
(217, 302, 770, '680', 1664, 'ca', 'Canada', '1', 'Sask Tel Mobility'),
(218, 302, 770, '780', 1920, 'ca', 'Canada', '1', 'Sask Tel Mobility'),
(219, 302, 770, '656', 1622, 'ca', 'Canada', '1', 'Tbay Mobility'),
(220, 302, 770, '220', 544, 'ca', 'Canada', '1', 'Telus Mobility'),
(221, 302, 770, '653', 1619, 'ca', 'Canada', '1', 'Telus Mobility'),
(222, 302, 770, '500', 1280, 'ca', 'Canada', '1', 'Videotron'),
(223, 302, 770, '490', 1168, 'ca', 'Canada', '1', 'WIND'),
(224, 625, 1573, '01', 31, 'cv', 'Cape Verde', '238', 'CV Movel'),
(225, 625, 1573, '02', 47, 'cv', 'Cape Verde', '238', 'T+ Telecom'),
(226, 346, 838, '050', 80, 'ky', 'Cayman Islands', '1345', 'Digicel Cayman Ltd'),
(227, 346, 838, '006', 6, 'ky', 'Cayman Islands', '1345', 'Digicel Ltd.'),
(228, 346, 838, '140', 320, 'ky', 'Cayman Islands', '1345', 'LIME / Cable & Wirel.'),
(229, 623, 1571, '01', 31, 'cf', 'Central African Rep.', '236', 'Centrafr. Telecom+'),
(230, 623, 1571, '04', 79, 'cf', 'Central African Rep.', '236', 'Nationlink'),
(231, 623, 1571, '03', 63, 'cf', 'Central African Rep.', '236', 'Orange/Celca'),
(232, 623, 1571, '02', 47, 'cf', 'Central African Rep.', '236', 'Telecel Centraf.'),
(233, 622, 1570, '04', 79, 'td', 'Chad', '235', 'Salam/Sotel'),
(234, 622, 1570, '02', 47, 'td', 'Chad', '235', 'Tchad Mobile'),
(235, 622, 1570, '03', 63, 'td', 'Chad', '235', 'Tigo/Milicom/Tchad Mobile'),
(236, 622, 1570, '01', 31, 'td', 'Chad', '235', 'Airtel/ZAIN/Celtel'),
(237, 730, 1840, '06', 111, 'cl', 'Chile', '56', 'Blue Two Chile SA'),
(238, 730, 1840, '11', 287, 'cl', 'Chile', '56', 'Celupago SA'),
(239, 730, 1840, '15', 351, 'cl', 'Chile', '56', 'Cibeles Telecom SA'),
(240, 730, 1840, '03', 63, 'cl', 'Chile', '56', 'Claro'),
(241, 730, 1840, '10', 271, 'cl', 'Chile', '56', 'Entel Telefonia'),
(242, 730, 1840, '01', 31, 'cl', 'Chile', '56', 'Entel Telefonia Mov'),
(243, 730, 1840, '14', 335, 'cl', 'Chile', '56', 'Netline Telefonica Movil Ltda'),
(244, 730, 1840, '05', 95, 'cl', 'Chile', '56', 'Nextel SA'),
(245, 730, 1840, '04', 79, 'cl', 'Chile', '56', 'Nextel SA'),
(246, 730, 1840, '09', 159, 'cl', 'Chile', '56', 'Nextel SA'),
(247, 730, 1840, '19', 415, 'cl', 'Chile', '56', 'Sociedad Falabella Movil SPA'),
(248, 730, 1840, '02', 47, 'cl', 'Chile', '56', 'TELEFONICA'),
(249, 730, 1840, '07', 127, 'cl', 'Chile', '56', 'TELEFONICA'),
(250, 730, 1840, '12', 303, 'cl', 'Chile', '56', 'Telestar Movil SA'),
(251, 730, 1840, '00', 15, 'cl', 'Chile', '56', 'TESAM SA'),
(252, 730, 1840, '13', 319, 'cl', 'Chile', '56', 'Tribe Mobile SPA'),
(253, 730, 1840, '08', 143, 'cl', 'Chile', '56', 'VTR Banda Ancha SA'),
(254, 460, 1120, '07', 127, 'cn', 'China', '86', 'China Mobile GSM'),
(255, 460, 1120, '00', 15, 'cn', 'China', '86', 'China Mobile GSM'),
(256, 460, 1120, '02', 47, 'cn', 'China', '86', 'China Mobile GSM'),
(257, 460, 1120, '04', 79, 'cn', 'China', '86', 'China Space Mobile Satellite Telecommunications Co. Ltd (China Spacecom)'),
(258, 460, 1120, '03', 63, 'cn', 'China', '86', 'China Telecom'),
(259, 460, 1120, '05', 95, 'cn', 'China', '86', 'China Telecom'),
(260, 460, 1120, '06', 111, 'cn', 'China', '86', 'China Unicom'),
(261, 460, 1120, '01', 31, 'cn', 'China', '86', 'China Unicom'),
(262, 732, 1842, '130', 304, 'co', 'Colombia', '57', 'Avantel SAS'),
(263, 732, 1842, '102', 258, 'co', 'Colombia', '57', 'Movistar'),
(264, 732, 1842, '103', 259, 'co', 'Colombia', '57', 'TIGO/Colombia Movil'),
(265, 732, 1842, '001', 1, 'co', 'Colombia', '57', 'TIGO/Colombia Movil'),
(266, 732, 1842, '101', 257, 'co', 'Colombia', '57', 'Comcel S.A. Occel S.A./Celcaribe'),
(267, 732, 1842, '002', 2, 'co', 'Colombia', '57', 'Edatel S.A.'),
(268, 732, 1842, '187', 391, 'co', 'Colombia', '57', 'eTb'),
(269, 732, 1842, '123', 291, 'co', 'Colombia', '57', 'Movistar'),
(270, 732, 1842, '111', 273, 'co', 'Colombia', '57', 'TIGO/Colombia Movil'),
(271, 732, 1842, '142', 322, 'co', 'Colombia', '57', 'UNE EPM Telecomunicaciones SA ESP'),
(272, 732, 1842, '020', 32, 'co', 'Colombia', '57', 'UNE EPM Telecomunicaciones SA ESP'),
(273, 732, 1842, '154', 340, 'co', 'Colombia', '57', 'Virgin Mobile Colombia SAS'),
(274, 654, 1620, '01', 31, 'km', 'Comoros', '269', 'HURI - SNPT'),
(275, 630, 1584, '90', 2319, 'cd', 'Congo Dem. Rep.', '243', 'Africell'),
(276, 630, 1584, '86', 2159, 'cd', 'Congo Dem. Rep.', '243', 'Orange RDC sarl'),
(277, 630, 1584, '05', 95, 'cd', 'Congo Dem. Rep.', '243', 'SuperCell'),
(278, 630, 1584, '89', 2207, 'cd', 'Congo Dem. Rep.', '243', 'TIGO/Oasis'),
(279, 630, 1584, '01', 31, 'cd', 'Congo Dem. Rep.', '243', 'Vodacom'),
(280, 630, 1584, '88', 2191, 'cd', 'Congo Dem. Rep.', '243', 'Yozma Timeturns sprl (YTT)'),
(281, 630, 1584, '02', 47, 'cd', 'Congo Dem. Rep.', '243', 'Airtel/ZAIN'),
(282, 629, 1577, '01', 31, 'cg', 'Congo Republic', '242', 'Airtel SA'),
(283, 629, 1577, '02', 47, 'cg', 'Congo Republic', '242', 'Azur SA (ETC)'),
(284, 629, 1577, '10', 271, 'cg', 'Congo Republic', '242', 'MTN/Libertis'),
(285, 629, 1577, '07', 127, 'cg', 'Congo Republic', '242', 'Warid'),
(286, 548, 1352, '01', 31, 'ck', 'Cook Islands', '682', 'Telecom Cook Islands'),
(287, 712, 1810, '03', 63, 'cr', 'Costa Rica', '506', 'Claro'),
(288, 712, 1810, '02', 47, 'cr', 'Costa Rica', '506', 'ICE'),
(289, 712, 1810, '01', 31, 'cr', 'Costa Rica', '506', 'ICE'),
(290, 712, 1810, '04', 79, 'cr', 'Costa Rica', '506', 'Movistar'),
(291, 712, 1810, '20', 527, 'cr', 'Costa Rica', '506', 'Virtualis'),
(292, 219, 537, '01', 31, 'hr', 'Croatia', '385', 'T-Mobile/Cronet'),
(293, 219, 537, '02', 47, 'hr', 'Croatia', '385', 'Tele2'),
(294, 219, 537, '10', 271, 'hr', 'Croatia', '385', 'VIPnet d.o.o.'),
(295, 368, 872, '01', 31, 'cu', 'Cuba', '53', 'C-COM'),
(296, 362, 866, '95', 2399, 'cw', 'Curacao', '599', 'EOCG Wireless NV'),
(297, 362, 866, '69', 1695, 'cw', 'Curacao', '599', 'Polycom N.V./ Digicel'),
(298, 280, 640, '10', 271, 'cy', 'Cyprus', '357', 'MTN/Areeba'),
(299, 280, 640, '20', 527, 'cy', 'Cyprus', '357', 'PrimeTel PLC'),
(300, 280, 640, '01', 31, 'cy', 'Cyprus', '357', 'Vodafone/CyTa'),
(301, 230, 560, '08', 143, 'cz', 'Czech Rep.', '420', 'Compatel s.r.o.'),
(302, 230, 560, '02', 47, 'cz', 'Czech Rep.', '420', 'O2'),
(303, 230, 560, '01', 31, 'cz', 'Czech Rep.', '420', 'T-Mobile / RadioMobil'),
(304, 230, 560, '05', 95, 'cz', 'Czech Rep.', '420', 'Travel Telekommunikation s.r.o.'),
(305, 230, 560, '04', 79, 'cz', 'Czech Rep.', '420', 'Ufone'),
(306, 230, 560, '03', 63, 'cz', 'Czech Rep.', '420', 'Vodafone'),
(307, 230, 560, '99', 2463, 'cz', 'Czech Rep.', '420', 'Vodafone'),
(308, 238, 568, '05', 95, 'dk', 'Denmark', '45', 'ApS KBUS'),
(309, 238, 568, '23', 575, 'dk', 'Denmark', '45', 'Banedanmark'),
(310, 238, 568, '28', 655, 'dk', 'Denmark', '45', 'CoolTEL ApS'),
(311, 238, 568, '06', 111, 'dk', 'Denmark', '45', 'H3G'),
(312, 238, 568, '12', 303, 'dk', 'Denmark', '45', 'Lycamobile Ltd'),
(313, 238, 568, '03', 63, 'dk', 'Denmark', '45', 'Mach Connectivity ApS'),
(314, 238, 568, '07', 127, 'dk', 'Denmark', '45', 'Mundio Mobile'),
(315, 238, 568, '04', 79, 'dk', 'Denmark', '45', 'NextGen Mobile Ltd (CardBoardFish)'),
(316, 238, 568, '10', 271, 'dk', 'Denmark', '45', 'TDC Denmark'),
(317, 238, 568, '01', 31, 'dk', 'Denmark', '45', 'TDC Denmark'),
(318, 238, 568, '02', 47, 'dk', 'Denmark', '45', 'Telenor/Sonofon'),
(319, 238, 568, '77', 1919, 'dk', 'Denmark', '45', 'Telenor/Sonofon'),
(320, 238, 568, '20', 527, 'dk', 'Denmark', '45', 'Telia'),
(321, 238, 568, '30', 783, 'dk', 'Denmark', '45', 'Telia'),
(322, 638, 1592, '01', 31, 'dj', 'Djibouti', '253', 'Djibouti Telecom SA (Evatis)'),
(323, 366, 870, '110', 272, 'dm', 'Dominica', '1767', 'C & W'),
(324, 366, 870, '020', 32, 'dm', 'Dominica', '1767', 'Cingular Wireless/Digicel'),
(325, 366, 870, '050', 80, 'dm', 'Dominica', '1767', 'Wireless Ventures (Dominica) Ltd (Digicel Dominica)'),
(326, 370, 880, '02', 47, 'do', 'Dominican Republic', '1809', 'Claro'),
(327, 370, 880, '01', 31, 'do', 'Dominican Republic', '1809', 'Orange'),
(328, 370, 880, '03', 63, 'do', 'Dominican Republic', '1809', 'TRIcom'),
(329, 370, 880, '04', 79, 'do', 'Dominican Republic', '1809', 'Trilogy Dominicana S. A.'),
(330, 740, 1856, '02', 47, 'ec', 'Ecuador', '593', 'Alegro/Telcsa'),
(331, 740, 1856, '00', 15, 'ec', 'Ecuador', '593', 'MOVISTAR/OteCel'),
(332, 740, 1856, '01', 31, 'ec', 'Ecuador', '593', 'Claro/Porta'),
(333, 602, 1538, '01', 31, 'eg', 'Egypt', '20', 'Orange/Mobinil'),
(334, 602, 1538, '03', 63, 'eg', 'Egypt', '20', 'ETISALAT'),
(335, 602, 1538, '02', 47, 'eg', 'Egypt', '20', 'Vodafone/Mirsfone'),
(336, 706, 1798, '01', 31, 'sv', 'El Salvador', '503', 'CLARO/CTE'),
(337, 706, 1798, '02', 47, 'sv', 'El Salvador', '503', 'Digicel'),
(338, 706, 1798, '05', 95, 'sv', 'El Salvador', '503', 'INTELFON SA de CV'),
(339, 706, 1798, '04', 79, 'sv', 'El Salvador', '503', 'Telefonica'),
(340, 706, 1798, '03', 63, 'sv', 'El Salvador', '503', 'Telemovil'),
(341, 627, 1575, '03', 63, 'gq', 'Equatorial Guinea', '240', 'HiTs-GE'),
(342, 627, 1575, '01', 31, 'gq', 'Equatorial Guinea', '240', 'ORANGE/GETESA'),
(343, 657, 1623, '01', 31, 'er', 'Eritrea', '291', 'Eritel'),
(344, 248, 584, '01', 31, 'ee', 'Estonia', '372', 'EMT GSM'),
(345, 248, 584, '02', 47, 'ee', 'Estonia', '372', 'Radiolinja Eesti'),
(346, 248, 584, '03', 63, 'ee', 'Estonia', '372', 'Tele2 Eesti AS'),
(347, 248, 584, '04', 79, 'ee', 'Estonia', '372', 'Top Connect OU'),
(348, 636, 1590, '01', 31, 'et', 'Ethiopia', '251', 'ETH/MTN'),
(349, 750, 1872, '001', 1, 'fk', 'Falkland Islands (Malvinas)', '500', 'Cable and Wireless South Atlantic Ltd (Falkland Islands'),
(350, 288, 648, '03', 63, 'fo', 'Faroe Islands', '298', 'Edge Mobile Sp/F'),
(351, 288, 648, '01', 31, 'fo', 'Faroe Islands', '298', 'Faroese Telecom'),
(352, 288, 648, '02', 47, 'fo', 'Faroe Islands', '298', 'Kall GSM'),
(353, 542, 1346, '02', 47, 'fj', 'Fiji', '679', 'DigiCell'),
(354, 542, 1346, '01', 31, 'fj', 'Fiji', '679', 'Vodafone'),
(355, 244, 580, '14', 335, 'fi', 'Finland', '358', 'Alands'),
(356, 244, 580, '26', 623, 'fi', 'Finland', '358', 'Compatel Ltd'),
(357, 244, 580, '03', 63, 'fi', 'Finland', '358', 'DNA/Finnet'),
(358, 244, 580, '13', 319, 'fi', 'Finland', '358', 'DNA/Finnet'),
(359, 244, 580, '12', 303, 'fi', 'Finland', '358', 'DNA/Finnet'),
(360, 244, 580, '04', 79, 'fi', 'Finland', '358', 'DNA/Finnet'),
(361, 244, 580, '21', 543, 'fi', 'Finland', '358', 'Elisa/Saunalahti'),
(362, 244, 580, '05', 95, 'fi', 'Finland', '358', 'Elisa/Saunalahti'),
(363, 244, 580, '82', 2095, 'fi', 'Finland', '358', 'ID-Mobile'),
(364, 244, 580, '11', 287, 'fi', 'Finland', '358', 'Mundio Mobile (Finland) Ltd'),
(365, 244, 580, '09', 159, 'fi', 'Finland', '358', 'Nokia Oyj'),
(366, 244, 580, '10', 271, 'fi', 'Finland', '358', 'TDC Oy Finland'),
(367, 244, 580, '91', 2335, 'fi', 'Finland', '358', 'TeliaSonera'),
(368, 208, 520, '27', 639, 'fr', 'France', '33', 'AFONE SA'),
(369, 208, 520, '92', 2351, 'fr', 'France', '33', 'Association Plate-forme Telecom'),
(370, 208, 520, '28', 655, 'fr', 'France', '33', 'Astrium'),
(371, 208, 520, '88', 2191, 'fr', 'France', '33', 'Bouygues Telecom'),
(372, 208, 520, '21', 543, 'fr', 'France', '33', 'Bouygues Telecom'),
(373, 208, 520, '20', 527, 'fr', 'France', '33', 'Bouygues Telecom'),
(374, 208, 520, '14', 335, 'fr', 'France', '33', 'Lliad/FREE Mobile'),
(375, 208, 520, '05', 95, 'fr', 'France', '33', 'GlobalStar'),
(376, 208, 520, '07', 127, 'fr', 'France', '33', 'GlobalStar'),
(377, 208, 520, '06', 111, 'fr', 'France', '33', 'GlobalStar'),
(378, 208, 520, '29', 671, 'fr', 'France', '33', 'Orange'),
(379, 208, 520, '17', 383, 'fr', 'France', '33', 'Legos - Local Exchange Global Operation Services SA'),
(380, 208, 520, '16', 367, 'fr', 'France', '33', 'Lliad/FREE Mobile'),
(381, 208, 520, '15', 351, 'fr', 'France', '33', 'Lliad/FREE Mobile'),
(382, 208, 520, '25', 607, 'fr', 'France', '33', 'Lycamobile SARL'),
(383, 208, 520, '24', 591, 'fr', 'France', '33', 'MobiquiThings'),
(384, 208, 520, '03', 63, 'fr', 'France', '33', 'MobiquiThings'),
(385, 208, 520, '31', 799, 'fr', 'France', '33', 'Mundio Mobile (France) Ltd'),
(386, 208, 520, '26', 623, 'fr', 'France', '33', 'NRJ'),
(387, 208, 520, '89', 2207, 'fr', 'France', '33', 'Virgin Mobile/Omer'),
(388, 208, 520, '23', 575, 'fr', 'France', '33', 'Virgin Mobile/Omer'),
(389, 208, 520, '91', 2335, 'fr', 'France', '33', 'Orange'),
(390, 208, 520, '02', 47, 'fr', 'France', '33', 'Orange'),
(391, 208, 520, '01', 31, 'fr', 'France', '33', 'Orange'),
(392, 208, 520, '13', 319, 'fr', 'France', '33', 'S.F.R.'),
(393, 208, 520, '11', 287, 'fr', 'France', '33', 'S.F.R.'),
(394, 208, 520, '10', 271, 'fr', 'France', '33', 'S.F.R.'),
(395, 208, 520, '09', 159, 'fr', 'France', '33', 'S.F.R.'),
(396, 208, 520, '04', 79, 'fr', 'France', '33', 'SISTEER'),
(397, 208, 520, '00', 15, 'fr', 'France', '33', 'Tel/Tel'),
(398, 208, 520, '22', 559, 'fr', 'France', '33', 'Transatel SA'),
(399, 340, 832, '20', 527, 'fg', 'French Guiana', '594', 'Bouygues/DigiCel'),
(400, 340, 832, '01', 31, 'fg', 'French Guiana', '594', 'Orange Caribe'),
(401, 340, 832, '02', 47, 'fg', 'French Guiana', '594', 'Outremer Telecom'),
(402, 340, 832, '03', 63, 'fg', 'French Guiana', '594', 'TelCell GSM'),
(403, 340, 832, '11', 287, 'fg', 'French Guiana', '594', 'TelCell GSM'),
(404, 547, 1351, '15', 351, 'pf', 'French Polynesia', '689', 'Pacific Mobile Telecom (PMT)'),
(405, 547, 1351, '20', 527, 'pf', 'French Polynesia', '689', 'Vini/Tikiphone'),
(406, 628, 1576, '04', 79, 'ga', 'Gabon', '241', 'Azur/Usan S.A.'),
(407, 628, 1576, '01', 31, 'ga', 'Gabon', '241', 'Libertis S.A.'),
(408, 628, 1576, '02', 47, 'ga', 'Gabon', '241', 'MOOV/Telecel'),
(409, 628, 1576, '03', 63, 'ga', 'Gabon', '241', 'Airtel/ZAIN/Celtel Gabon S.A.'),
(410, 607, 1543, '02', 47, 'gm', 'Gambia', '220', 'Africel'),
(411, 607, 1543, '03', 63, 'gm', 'Gambia', '220', 'Comium'),
(412, 607, 1543, '01', 31, 'gm', 'Gambia', '220', 'Gamcel'),
(413, 607, 1543, '04', 79, 'gm', 'Gambia', '220', 'Q-Cell'),
(414, 282, 642, '01', 31, 'ge', 'Georgia', '995', 'Geocell Ltd.'),
(415, 282, 642, '03', 63, 'ge', 'Georgia', '995', 'Iberiatel Ltd.'),
(416, 282, 642, '02', 47, 'ge', 'Georgia', '995', 'Magti GSM Ltd.'),
(417, 282, 642, '04', 79, 'ge', 'Georgia', '995', 'MobiTel/Beeline'),
(418, 282, 642, '05', 95, 'ge', 'Georgia', '995', 'Silknet'),
(419, 262, 610, '17', 383, 'de', 'Germany', '49', 'E-Plus'),
(420, 262, 610, '10', 271, 'de', 'Germany', '49', 'DB Netz AG'),
(421, 262, 610, 'n/a', 271, 'de', 'Germany', '49', 'Debitel'),
(422, 262, 610, '05', 95, 'de', 'Germany', '49', 'E-Plus'),
(423, 262, 610, '77', 1919, 'de', 'Germany', '49', 'E-Plus'),
(424, 262, 610, '03', 63, 'de', 'Germany', '49', 'E-Plus'),
(425, 262, 610, '12', 303, 'de', 'Germany', '49', 'E-Plus'),
(426, 262, 610, '20', 527, 'de', 'Germany', '49', 'E-Plus'),
(427, 262, 610, '14', 335, 'de', 'Germany', '49', 'Group 3G UMTS'),
(428, 262, 610, '43', 1087, 'de', 'Germany', '49', 'Lycamobile'),
(429, 262, 610, '13', 319, 'de', 'Germany', '49', 'Mobilcom'),
(430, 262, 610, '11', 287, 'de', 'Germany', '49', 'O2'),
(431, 262, 610, '08', 143, 'de', 'Germany', '49', 'O2'),
(432, 262, 610, '07', 127, 'de', 'Germany', '49', 'O2'),
(433, 262, 610, 'n/a', 127, 'de', 'Germany', '49', 'Talkline'),
(434, 262, 610, '06', 111, 'de', 'Germany', '49', 'T-mobile/Telekom'),
(435, 262, 610, '01', 31, 'de', 'Germany', '49', 'T-mobile/Telekom'),
(436, 262, 610, '16', 367, 'de', 'Germany', '49', 'Telogic/ViStream'),
(437, 262, 610, '09', 159, 'de', 'Germany', '49', 'Vodafone D2'),
(438, 262, 610, '04', 79, 'de', 'Germany', '49', 'Vodafone D2'),
(439, 262, 610, '02', 47, 'de', 'Germany', '49', 'Vodafone D2'),
(440, 262, 610, '42', 1071, 'de', 'Germany', '49', 'Vodafone D2'),
(441, 620, 1568, '04', 79, 'gh', 'Ghana', '233', 'Expresso Ghana Ltd'),
(442, 620, 1568, '07', 127, 'gh', 'Ghana', '233', 'GloMobile'),
(443, 620, 1568, '03', 63, 'gh', 'Ghana', '233', 'Milicom/Tigo'),
(444, 620, 1568, '01', 31, 'gh', 'Ghana', '233', 'MTN'),
(445, 620, 1568, '02', 47, 'gh', 'Ghana', '233', 'Vodafone'),
(446, 620, 1568, '06', 111, 'gh', 'Ghana', '233', 'Airtel/ZAIN'),
(447, 266, 614, '06', 111, 'gi', 'Gibraltar', '350', 'CTS Mobile'),
(448, 266, 614, '09', 159, 'gi', 'Gibraltar', '350', 'eazi telecom'),
(449, 266, 614, '01', 31, 'gi', 'Gibraltar', '350', 'Gibtel GSM'),
(450, 202, 514, '07', 127, 'gr', 'Greece', '30', 'AMD Telecom SA'),
(451, 202, 514, '02', 47, 'gr', 'Greece', '30', 'Cosmote'),
(452, 202, 514, '01', 31, 'gr', 'Greece', '30', 'Cosmote'),
(453, 202, 514, '14', 335, 'gr', 'Greece', '30', 'CyTa Mobile'),
(454, 202, 514, '04', 79, 'gr', 'Greece', '30', 'Organismos Sidirodromon Ellados (OSE)'),
(455, 202, 514, '03', 63, 'gr', 'Greece', '30', 'OTE Hellenic Telecommunications Organization SA'),
(456, 202, 514, '10', 271, 'gr', 'Greece', '30', 'Tim/Wind'),
(457, 202, 514, '09', 159, 'gr', 'Greece', '30', 'Tim/Wind'),
(458, 202, 514, '05', 95, 'gr', 'Greece', '30', 'Vodafone'),
(459, 290, 656, '01', 31, 'gl', 'Greenland', '299', 'Tele Greenland'),
(460, 352, 850, '110', 272, 'gd', 'Grenada', '1473', 'Cable & Wireless'),
(461, 352, 850, '030', 48, 'gd', 'Grenada', '1473', 'Digicel'),
(462, 352, 850, '050', 80, 'gd', 'Grenada', '1473', 'Digicel'),
(463, 340, 832, '08', 143, 'gp', 'Guadeloupe', '590', 'Dauphin Telecom SU (Guadeloupe Telecom)'),
(464, 340, 832, '10', 271, 'gp', 'Guadeloupe', '590', ''),
(465, 310, 784, '370', 880, 'gu', 'Guam', '1671', 'Docomo'),
(466, 310, 784, '470', 1136, 'gu', 'Guam', '1671', 'Docomo'),
(467, 310, 784, '140', 320, 'gu', 'Guam', '1671', 'GTA Wireless'),
(468, 310, 784, '033', 51, 'gu', 'Guam', '1671', 'Guam Teleph. Auth.'),
(469, 310, 784, '032', 50, 'gu', 'Guam', '1671', 'IT&E OverSeas'),
(470, 311, 785, '250', 592, 'gu', 'Guam', '1671', 'Wave Runner LLC'),
(471, 704, 1796, '01', 31, 'gt', 'Guatemala', '502', 'Claro'),
(472, 704, 1796, '03', 63, 'gt', 'Guatemala', '502', 'Telefonica'),
(473, 704, 1796, '02', 47, 'gt', 'Guatemala', '502', 'TIGO/COMCEL'),
(474, 611, 1553, '04', 79, 'gn', 'Guinea', '224', 'MTN/Areeba'),
(475, 611, 1553, '05', 95, 'gn', 'Guinea', '224', 'Celcom'),
(476, 611, 1553, '03', 63, 'gn', 'Guinea', '224', 'Intercel'),
(477, 611, 1553, '01', 31, 'gn', 'Guinea', '224', 'Orange/Sonatel/Spacetel'),
(478, 611, 1553, '02', 47, 'gn', 'Guinea', '224', 'SotelGui'),
(479, 632, 1586, '01', 31, 'gw', 'Guinea-Bissau', '245', 'GuineTel'),
(480, 632, 1586, '03', 63, 'gw', 'Guinea-Bissau', '245', 'Orange'),
(481, 632, 1586, '02', 47, 'gw', 'Guinea-Bissau', '245', 'SpaceTel'),
(482, 738, 1848, '02', 47, 'gy', 'Guyana', '592', 'Cellink Plus'),
(483, 738, 1848, '01', 31, 'gy', 'Guyana', '592', 'DigiCel'),
(484, 372, 882, '01', 31, 'ht', 'Haiti', '509', 'Comcel'),
(485, 372, 882, '02', 47, 'ht', 'Haiti', '509', 'Digicel'),
(486, 372, 882, '03', 63, 'ht', 'Haiti', '509', 'National Telecom SA (NatCom)'),
(487, 708, 1800, '040', 64, 'hn', 'Honduras', '504', 'Digicel'),
(488, 708, 1800, '030', 48, 'hn', 'Honduras', '504', 'HonduTel'),
(489, 708, 1800, '001', 1, 'hn', 'Honduras', '504', 'SERCOM/CLARO'),
(490, 708, 1800, '002', 2, 'hn', 'Honduras', '504', 'Telefonica/CELTEL'),
(491, 454, 1108, '12', 303, 'hk', 'Hongkong China', '852', 'China Mobile/Peoples'),
(492, 454, 1108, '28', 655, 'hk', 'Hongkong China', '852', 'China Mobile/Peoples'),
(493, 454, 1108, '13', 319, 'hk', 'Hongkong China', '852', 'China Mobile/Peoples'),
(494, 454, 1108, '09', 159, 'hk', 'Hongkong China', '852', 'China Motion'),
(495, 454, 1108, '07', 127, 'hk', 'Hongkong China', '852', 'China Unicom Ltd'),
(496, 454, 1108, '11', 287, 'hk', 'Hongkong China', '852', 'China-HongKong Telecom Ltd (CHKTL)'),
(497, 454, 1108, '01', 31, 'hk', 'Hongkong China', '852', 'Citic Telecom Ltd.'),
(498, 454, 1108, '02', 47, 'hk', 'Hongkong China', '852', 'CSL Ltd.'),
(499, 454, 1108, '00', 15, 'hk', 'Hongkong China', '852', 'CSL Ltd.'),
(500, 454, 1108, '18', 399, 'hk', 'Hongkong China', '852', 'CSL Ltd.'),
(501, 454, 1108, '10', 271, 'hk', 'Hongkong China', '852', 'CSL/New World PCS Ltd.'),
(502, 454, 1108, '05', 95, 'hk', 'Hongkong China', '852', 'H3G/Hutchinson'),
(503, 454, 1108, '04', 79, 'hk', 'Hongkong China', '852', 'H3G/Hutchinson'),
(504, 454, 1108, '03', 63, 'hk', 'Hongkong China', '852', 'H3G/Hutchinson'),
(505, 454, 1108, '14', 335, 'hk', 'Hongkong China', '852', 'H3G/Hutchinson'),
(506, 454, 1108, '20', 527, 'hk', 'Hongkong China', '852', 'HKT/PCCW'),
(507, 454, 1108, '29', 671, 'hk', 'Hongkong China', '852', 'HKT/PCCW'),
(508, 454, 1108, '19', 415, 'hk', 'Hongkong China', '852', 'HKT/PCCW'),
(509, 454, 1108, '16', 367, 'hk', 'Hongkong China', '852', 'HKT/PCCW'),
(510, 454, 1108, '47', 1151, 'hk', 'Hongkong China', '852', 'shared by private TETRA systems'),
(511, 454, 1108, '40', 1039, 'hk', 'Hongkong China', '852', 'shared by private TETRA systems'),
(512, 454, 1108, '08', 143, 'hk', 'Hongkong China', '852', 'Truephone'),
(513, 454, 1108, '17', 383, 'hk', 'Hongkong China', '852', 'Vodafone/SmarTone'),
(514, 454, 1108, '15', 351, 'hk', 'Hongkong China', '852', 'Vodafone/SmarTone'),
(515, 454, 1108, '06', 111, 'hk', 'Hongkong China', '852', 'Vodafone/SmarTone'),
(516, 216, 534, '01', 31, 'hu', 'Hungary', '36', 'Pannon/Telenor'),
(517, 216, 534, '30', 783, 'hu', 'Hungary', '36', 'T-mobile/Magyar'),
(518, 216, 534, '71', 1823, 'hu', 'Hungary', '36', 'UPC Magyarorszag Kft.'),
(519, 216, 534, '70', 1807, 'hu', 'Hungary', '36', 'Vodafone'),
(520, 274, 628, '09', 159, 'is', 'Iceland', '354', 'Amitelo'),
(521, 274, 628, '07', 127, 'is', 'Iceland', '354', 'IceCell'),
(522, 274, 628, '08', 143, 'is', 'Iceland', '354', 'Siminn'),
(523, 274, 628, '01', 31, 'is', 'Iceland', '354', 'Siminn'),
(524, 274, 628, '11', 287, 'is', 'Iceland', '354', 'NOVA'),
(525, 274, 628, '04', 79, 'is', 'Iceland', '354', 'VIKING/IMC'),
(526, 274, 628, '05', 95, 'is', 'Iceland', '354', 'Vodafone/Tal hf'),
(527, 274, 628, '03', 63, 'is', 'Iceland', '354', 'Vodafone/Tal hf'),
(528, 274, 628, '02', 47, 'is', 'Iceland', '354', 'Vodafone/Tal hf'),
(529, 404, 1028, '17', 383, 'in', 'India', '91', 'Aircel'),
(530, 404, 1028, '42', 1071, 'in', 'India', '91', 'Aircel'),
(531, 404, 1028, '33', 831, 'in', 'India', '91', 'Aircel'),
(532, 404, 1028, '29', 671, 'in', 'India', '91', 'Aircel'),
(533, 404, 1028, '28', 655, 'in', 'India', '91', 'Aircel'),
(534, 404, 1028, '25', 607, 'in', 'India', '91', 'Aircel'),
(535, 404, 1028, '01', 31, 'in', 'India', '91', 'Aircel Digilink India'),
(536, 404, 1028, '15', 351, 'in', 'India', '91', 'Aircel Digilink India'),
(537, 404, 1028, '60', 1551, 'in', 'India', '91', 'Aircel Digilink India'),
(538, 405, 1029, '53', 1343, 'in', 'India', '91', 'AirTel'),
(539, 404, 1028, '86', 2159, 'in', 'India', '91', 'Barakhamba Sales & Serv.'),
(540, 404, 1028, '13', 319, 'in', 'India', '91', 'Barakhamba Sales & Serv.'),
(541, 404, 1028, '58', 1423, 'in', 'India', '91', 'BSNL'),
(542, 404, 1028, '81', 2079, 'in', 'India', '91', 'BSNL'),
(543, 404, 1028, '74', 1871, 'in', 'India', '91', 'BSNL'),
(544, 404, 1028, '38', 911, 'in', 'India', '91', 'BSNL'),
(545, 404, 1028, '57', 1407, 'in', 'India', '91', 'BSNL'),
(546, 404, 1028, '80', 2063, 'in', 'India', '91', 'BSNL'),
(547, 404, 1028, '73', 1855, 'in', 'India', '91', 'BSNL'),
(548, 404, 1028, '34', 847, 'in', 'India', '91', 'BSNL'),
(549, 404, 1028, '66', 1647, 'in', 'India', '91', 'BSNL'),
(550, 404, 1028, '55', 1375, 'in', 'India', '91', 'BSNL'),
(551, 404, 1028, '72', 1839, 'in', 'India', '91', 'BSNL'),
(552, 404, 1028, '77', 1919, 'in', 'India', '91', 'BSNL'),
(553, 404, 1028, '64', 1615, 'in', 'India', '91', 'BSNL'),
(554, 404, 1028, '54', 1359, 'in', 'India', '91', 'BSNL'),
(555, 404, 1028, '71', 1823, 'in', 'India', '91', 'BSNL'),
(556, 404, 1028, '76', 1903, 'in', 'India', '91', 'BSNL'),
(557, 404, 1028, '62', 1583, 'in', 'India', '91', 'BSNL'),
(558, 404, 1028, '53', 1343, 'in', 'India', '91', 'BSNL'),
(559, 404, 1028, '59', 1439, 'in', 'India', '91', 'BSNL'),
(560, 404, 1028, '75', 1887, 'in', 'India', '91', 'BSNL'),
(561, 404, 1028, '51', 1311, 'in', 'India', '91', 'BSNL'),
(562, 404, 1028, '10', 271, 'in', 'India', '91', 'Bharti Airtel Limited (Delhi)'),
(563, 404, 1028, '045', 69, 'in', 'India', '91', 'Bharti Airtel Limited (Karnataka) (India)'),
(564, 404, 1028, '79', 1951, 'in', 'India', '91', 'CellOne A&N'),
(565, 404, 1028, '89', 2207, 'in', 'India', '91', 'Escorts Telecom Ltd.'),
(566, 404, 1028, '88', 2191, 'in', 'India', '91', 'Escorts Telecom Ltd.'),
(567, 404, 1028, '87', 2175, 'in', 'India', '91', 'Escorts Telecom Ltd.'),
(568, 404, 1028, '82', 2095, 'in', 'India', '91', 'Escorts Telecom Ltd.'),
(569, 404, 1028, '12', 303, 'in', 'India', '91', 'Escotel Mobile Communications'),
(570, 404, 1028, '19', 415, 'in', 'India', '91', 'Escotel Mobile Communications'),
(571, 404, 1028, '56', 1391, 'in', 'India', '91', 'Escotel Mobile Communications'),
(572, 405, 1029, '05', 95, 'in', 'India', '91', 'Fascel Limited'),
(573, 404, 1028, '05', 95, 'in', 'India', '91', 'Fascel'),
(574, 404, 1028, '70', 1807, 'in', 'India', '91', 'Hexacom India'),
(575, 404, 1028, '16', 367, 'in', 'India', '91', 'Hexcom India'),
(576, 404, 1028, '04', 79, 'in', 'India', '91', 'Idea Cellular Ltd.'),
(577, 404, 1028, '24', 591, 'in', 'India', '91', 'Idea Cellular Ltd.'),
(578, 404, 1028, '22', 559, 'in', 'India', '91', 'Idea Cellular Ltd.'),
(579, 404, 1028, '78', 1935, 'in', 'India', '91', 'Idea Cellular Ltd.'),
(580, 404, 1028, '07', 127, 'in', 'India', '91', 'Idea Cellular Ltd.'),
(581, 404, 1028, '69', 1695, 'in', 'India', '91', 'Mahanagar Telephone Nigam'),
(582, 404, 1028, '68', 1679, 'in', 'India', '91', 'Mahanagar Telephone Nigam'),
(583, 404, 1028, '83', 2111, 'in', 'India', '91', 'Reliable Internet Services'),
(584, 404, 1028, '36', 879, 'in', 'India', '91', 'Reliance Telecom Private'),
(585, 404, 1028, '52', 1327, 'in', 'India', '91', 'Reliance Telecom Private'),
(586, 404, 1028, '50', 1295, 'in', 'India', '91', 'Reliance Telecom Private'),
(587, 404, 1028, '67', 1663, 'in', 'India', '91', 'Reliance Telecom Private'),
(588, 404, 1028, '18', 399, 'in', 'India', '91', 'Reliance Telecom Private'),
(589, 404, 1028, '85', 2143, 'in', 'India', '91', 'Reliance Telecom Private'),
(590, 404, 1028, '09', 159, 'in', 'India', '91', 'Reliance Telecom Private'),
(591, 404, 1028, '41', 1055, 'in', 'India', '91', 'RPG Cellular'),
(592, 404, 1028, '14', 335, 'in', 'India', '91', 'Spice'),
(593, 404, 1028, '44', 1103, 'in', 'India', '91', 'Spice'),
(594, 404, 1028, '11', 287, 'in', 'India', '91', 'Sterling Cellular Ltd.'),
(595, 405, 1029, '034', 52, 'in', 'India', '91', 'TATA / Karnataka'),
(596, 404, 1028, '30', 783, 'in', 'India', '91', 'Usha Martin Telecom'),
(597, 510, 1296, '08', 143, 'id', 'Indonesia', '62', 'Axis/Natrindo'),
(598, 510, 1296, '99', 2463, 'id', 'Indonesia', '62', 'Esia (PT Bakrie Telecom) (CDMA)'),
(599, 510, 1296, '07', 127, 'id', 'Indonesia', '62', 'Flexi (PT Telkom) (CDMA)'),
(600, 510, 1296, '89', 2207, 'id', 'Indonesia', '62', 'H3G CP'),
(601, 510, 1296, '21', 543, 'id', 'Indonesia', '62', 'Indosat/Satelindo/M3'),
(602, 510, 1296, '01', 31, 'id', 'Indonesia', '62', 'Indosat/Satelindo/M3'),
(603, 510, 1296, '00', 15, 'id', 'Indonesia', '62', 'PT Pasifik Satelit Nusantara (PSN)'),
(604, 510, 1296, '27', 639, 'id', 'Indonesia', '62', 'PT Sampoerna Telekomunikasi Indonesia (STI)'),
(605, 510, 1296, '28', 655, 'id', 'Indonesia', '62', 'PT Smartfren Telecom Tbk'),
(606, 510, 1296, '09', 159, 'id', 'Indonesia', '62', 'PT Smartfren Telecom Tbk'),
(607, 510, 1296, '11', 287, 'id', 'Indonesia', '62', 'PT. Excelcom'),
(608, 510, 1296, '10', 271, 'id', 'Indonesia', '62', 'Telkomsel'),
(609, 901, 2305, '13', 319, 'n/a', 'International Networks', '882', 'Antarctica'),
(610, 432, 1074, '19', 415, 'ir', 'Iran', '98', 'Mobile Telecommunications Company of Esfahan JV-PJS (MTCE)'),
(611, 432, 1074, '70', 1807, 'ir', 'Iran', '98', 'MTCE'),
(612, 432, 1074, '35', 863, 'ir', 'Iran', '98', 'MTN/IranCell'),
(613, 432, 1074, '20', 527, 'ir', 'Iran', '98', 'Rightel'),
(614, 432, 1074, '32', 815, 'ir', 'Iran', '98', 'Taliya'),
(615, 432, 1074, '11', 287, 'ir', 'Iran', '98', 'MCI/TCI'),
(616, 432, 1074, '14', 335, 'ir', 'Iran', '98', 'TKC/KFZO'),
(617, 418, 1048, '05', 95, 'iq', 'Iraq', '964', 'Asia Cell'),
(618, 418, 1048, '92', 2351, 'iq', 'Iraq', '964', 'Itisaluna and Kalemat'),
(619, 418, 1048, '82', 2095, 'iq', 'Iraq', '964', 'Korek'),
(620, 418, 1048, '40', 1039, 'iq', 'Iraq', '964', 'Korek'),
(621, 418, 1048, '45', 1119, 'iq', 'Iraq', '964', 'Mobitel (Iraq-Kurdistan) and Moutiny'),
(622, 418, 1048, '20', 527, 'iq', 'Iraq', '964', 'ZAIN/Atheer/Orascom'),
(623, 418, 1048, '30', 783, 'iq', 'Iraq', '964', 'Orascom Telecom'),
(624, 418, 1048, '08', 143, 'iq', 'Iraq', '964', 'Sanatel'),
(625, 272, 626, '04', 79, 'ie', 'Ireland', '353', 'Access Telecom Ltd.'),
(626, 272, 626, '09', 159, 'ie', 'Ireland', '353', 'Clever Communications Ltd'),
(627, 272, 626, '07', 127, 'ie', 'Ireland', '353', 'eircom Ltd'),
(628, 272, 626, '05', 95, 'ie', 'Ireland', '353', 'Three/H3G'),
(629, 272, 626, '11', 287, 'ie', 'Ireland', '353', 'Tesco Mobile/Liffey Telecom'),
(630, 272, 626, '13', 319, 'ie', 'Ireland', '353', 'Lycamobile'),
(631, 272, 626, '03', 63, 'ie', 'Ireland', '353', 'Meteor Mobile Ltd.'),
(632, 272, 626, '02', 47, 'ie', 'Ireland', '353', 'Three/O2/Digifone'),
(633, 272, 626, '01', 31, 'ie', 'Ireland', '353', 'Vodafone Eircell'),
(634, 425, 1061, '14', 335, 'il', 'Israel', '972', 'Alon Cellular Ltd'),
(635, 425, 1061, '02', 47, 'il', 'Israel', '972', 'Cellcom ltd.'),
(636, 425, 1061, '08', 143, 'il', 'Israel', '972', 'Golan Telekom'),
(637, 425, 1061, '15', 351, 'il', 'Israel', '972', 'Home Cellular Ltd'),
(638, 425, 1061, '07', 127, 'il', 'Israel', '972', 'Hot Mobile/Mirs'),
(639, 425, 1061, '77', 1919, 'il', 'Israel', '972', 'Hot Mobile/Mirs'),
(640, 425, 1061, '01', 31, 'il', 'Israel', '972', 'Orange/Partner Co. Ltd.'),
(641, 425, 1061, '12', 303, 'il', 'Israel', '972', 'Pelephone'),
(642, 425, 1061, '03', 63, 'il', 'Israel', '972', 'Pelephone'),
(643, 425, 1061, '16', 367, 'il', 'Israel', '972', 'Rami Levy Hashikma Marketing Communications Ltd'),
(644, 425, 1061, '19', 415, 'il', 'Israel', '972', 'Telzar/AZI'),
(645, 222, 546, '34', 847, 'it', 'Italy', '39', 'BT Italia SpA'),
(646, 222, 546, '02', 47, 'it', 'Italy', '39', 'Elsacom'),
(647, 222, 546, '08', 143, 'it', 'Italy', '39', 'Fastweb SpA'),
(648, 222, 546, '00', 15, 'it', 'Italy', '39', 'Fix Line'),
(649, 222, 546, '99', 2463, 'it', 'Italy', '39', 'Hi3G'),
(650, 222, 546, '77', 1919, 'it', 'Italy', '39', 'IPSE 2000'),
(651, 222, 546, '35', 863, 'it', 'Italy', '39', 'Lycamobile Srl'),
(652, 222, 546, '07', 127, 'it', 'Italy', '39', 'Noverca Italia Srl'),
(653, 222, 546, '33', 831, 'it', 'Italy', '39', 'PosteMobile SpA'),
(654, 222, 546, '00', 15, 'it', 'Italy', '39', 'Premium Number(s)'),
(655, 222, 546, '30', 783, 'it', 'Italy', '39', 'RFI Rete Ferroviaria Italiana SpA'),
(656, 222, 546, '48', 1167, 'it', 'Italy', '39', 'Telecom Italia Mobile SpA'),
(657, 222, 546, '43', 1087, 'it', 'Italy', '39', 'Telecom Italia Mobile SpA'),
(658, 222, 546, '01', 31, 'it', 'Italy', '39', 'TIM'),
(659, 222, 546, '10', 271, 'it', 'Italy', '39', 'Vodafone'),
(660, 222, 546, '06', 111, 'it', 'Italy', '39', 'Vodafone'),
(661, 222, 546, '00', 15, 'it', 'Italy', '39', 'VOIP Line'),
(662, 222, 546, '44', 1103, 'it', 'Italy', '39', 'WIND (Blu) -'),
(663, 222, 546, '88', 2191, 'it', 'Italy', '39', 'WIND (Blu) -'),
(664, 612, 1554, '07', 127, 'ci', 'Ivory Coast', '225', 'Aircomm SA'),
(665, 612, 1554, '02', 47, 'ci', 'Ivory Coast', '225', 'Atlantik Tel./Moov'),
(666, 612, 1554, '04', 79, 'ci', 'Ivory Coast', '225', 'Comium'),
(667, 612, 1554, '01', 31, 'ci', 'Ivory Coast', '225', 'Comstar'),
(668, 612, 1554, '05', 95, 'ci', 'Ivory Coast', '225', 'MTN'),
(669, 612, 1554, '03', 63, 'ci', 'Ivory Coast', '225', 'Orange'),
(670, 612, 1554, '06', 111, 'ci', 'Ivory Coast', '225', 'OriCell'),
(671, 338, 824, '110', 272, 'jm', 'Jamaica', '1876', 'Cable & Wireless'),
(672, 338, 824, '020', 32, 'jm', 'Jamaica', '1876', 'Cable & Wireless'),
(673, 338, 824, '180', 384, 'jm', 'Jamaica', '1876', 'Cable & Wireless'),
(674, 338, 824, '050', 80, 'jm', 'Jamaica', '1876', 'DIGICEL/Mossel'),
(675, 440, 1088, '00', 15, 'jp', 'Japan', '81', 'Y-Mobile'),
(676, 440, 1088, '74', 1871, 'jp', 'Japan', '81', 'KDDI Corporation'),
(677, 440, 1088, '70', 1807, 'jp', 'Japan', '81', 'KDDI Corporation'),
(678, 440, 1088, '89', 2207, 'jp', 'Japan', '81', 'KDDI Corporation'),
(679, 440, 1088, '51', 1311, 'jp', 'Japan', '81', 'KDDI Corporation'),
(680, 440, 1088, '75', 1887, 'jp', 'Japan', '81', 'KDDI Corporation'),
(681, 440, 1088, '56', 1391, 'jp', 'Japan', '81', 'KDDI Corporation'),
(682, 441, 1089, '70', 1807, 'jp', 'Japan', '81', 'KDDI Corporation'),
(683, 440, 1088, '52', 1327, 'jp', 'Japan', '81', 'KDDI Corporation'),
(684, 440, 1088, '76', 1903, 'jp', 'Japan', '81', 'KDDI Corporation'),
(685, 440, 1088, '71', 1823, 'jp', 'Japan', '81', 'KDDI Corporation'),
(686, 440, 1088, '53', 1343, 'jp', 'Japan', '81', 'KDDI Corporation'),
(687, 440, 1088, '77', 1919, 'jp', 'Japan', '81', 'KDDI Corporation'),
(688, 440, 1088, '08', 143, 'jp', 'Japan', '81', 'KDDI Corporation'),
(689, 440, 1088, '72', 1839, 'jp', 'Japan', '81', 'KDDI Corporation'),
(690, 440, 1088, '54', 1359, 'jp', 'Japan', '81', 'KDDI Corporation'),
(691, 440, 1088, '79', 1951, 'jp', 'Japan', '81', 'KDDI Corporation'),
(692, 440, 1088, '07', 127, 'jp', 'Japan', '81', 'KDDI Corporation'),
(693, 440, 1088, '73', 1855, 'jp', 'Japan', '81', 'KDDI Corporation'),
(694, 440, 1088, '55', 1375, 'jp', 'Japan', '81', 'KDDI Corporation'),
(695, 440, 1088, '88', 2191, 'jp', 'Japan', '81', 'KDDI Corporation'),
(696, 440, 1088, '50', 1295, 'jp', 'Japan', '81', 'KDDI Corporation'),
(697, 441, 1089, '41', 1055, 'jp', 'Japan', '81', 'NTT Docomo'),
(698, 440, 1088, '67', 1663, 'jp', 'Japan', '81', 'NTT Docomo'),
(699, 440, 1088, '14', 335, 'jp', 'Japan', '81', 'NTT Docomo'),
(700, 441, 1089, '94', 2383, 'jp', 'Japan', '81', 'NTT Docomo'),
(701, 440, 1088, '10', 271, 'jp', 'Japan', '81', 'NTT Docomo'),
(702, 440, 1088, '62', 1583, 'jp', 'Japan', '81', 'NTT Docomo'),
(703, 440, 1088, '39', 927, 'jp', 'Japan', '81', 'NTT Docomo'),
(704, 440, 1088, '30', 783, 'jp', 'Japan', '81', 'NTT Docomo'),
(705, 440, 1088, '24', 591, 'jp', 'Japan', '81', 'NTT Docomo'),
(706, 441, 1089, '45', 1119, 'jp', 'Japan', '81', 'NTT Docomo'),
(707, 441, 1089, '42', 1071, 'jp', 'Japan', '81', 'NTT Docomo'),
(708, 440, 1088, '68', 1679, 'jp', 'Japan', '81', 'NTT Docomo'),
(709, 440, 1088, '15', 351, 'jp', 'Japan', '81', 'NTT Docomo'),
(710, 441, 1089, '98', 2447, 'jp', 'Japan', '81', 'NTT Docomo'),
(711, 440, 1088, '11', 287, 'jp', 'Japan', '81', 'NTT Docomo'),
(712, 440, 1088, '63', 1599, 'jp', 'Japan', '81', 'NTT Docomo'),
(713, 440, 1088, '38', 911, 'jp', 'Japan', '81', 'NTT Docomo'),
(714, 440, 1088, '26', 623, 'jp', 'Japan', '81', 'NTT Docomo'),
(715, 440, 1088, '13', 319, 'jp', 'Japan', '81', 'NTT Docomo'),
(716, 440, 1088, '23', 575, 'jp', 'Japan', '81', 'NTT Docomo'),
(717, 440, 1088, '21', 543, 'jp', 'Japan', '81', 'NTT Docomo'),
(718, 441, 1089, '44', 1103, 'jp', 'Japan', '81', 'NTT Docomo'),
(719, 440, 1088, '34', 847, 'jp', 'Japan', '81', 'NTT Docomo'),
(720, 440, 1088, '69', 1695, 'jp', 'Japan', '81', 'NTT Docomo'),
(721, 440, 1088, '16', 367, 'jp', 'Japan', '81', 'NTT Docomo'),
(722, 441, 1089, '99', 2463, 'jp', 'Japan', '81', 'NTT Docomo'),
(723, 440, 1088, '64', 1615, 'jp', 'Japan', '81', 'NTT Docomo'),
(724, 440, 1088, '37', 895, 'jp', 'Japan', '81', 'NTT Docomo'),
(725, 440, 1088, '25', 607, 'jp', 'Japan', '81', 'NTT Docomo'),
(726, 440, 1088, '02', 47, 'jp', 'Japan', '81', 'NTT Docomo'),
(727, 440, 1088, '22', 559, 'jp', 'Japan', '81', 'NTT Docomo');
INSERT INTO `cell_carriers` (`carrier_id`, `mcc`, `mcc_int`, `mnc`, `mnc_int`, `iso`, `country`, `country_code`, `network`) VALUES
(728, 441, 1089, '43', 1087, 'jp', 'Japan', '81', 'NTT Docomo'),
(729, 440, 1088, '27', 639, 'jp', 'Japan', '81', 'NTT Docomo'),
(730, 440, 1088, '31', 799, 'jp', 'Japan', '81', 'NTT Docomo'),
(731, 440, 1088, '87', 2175, 'jp', 'Japan', '81', 'NTT Docomo'),
(732, 440, 1088, '17', 383, 'jp', 'Japan', '81', 'NTT Docomo'),
(733, 440, 1088, '65', 1631, 'jp', 'Japan', '81', 'NTT Docomo'),
(734, 440, 1088, '36', 879, 'jp', 'Japan', '81', 'NTT Docomo'),
(735, 441, 1089, '92', 2351, 'jp', 'Japan', '81', 'NTT Docomo'),
(736, 440, 1088, '03', 63, 'jp', 'Japan', '81', 'NTT Docomo'),
(737, 440, 1088, '12', 303, 'jp', 'Japan', '81', 'NTT Docomo'),
(738, 440, 1088, '58', 1423, 'jp', 'Japan', '81', 'NTT Docomo'),
(739, 440, 1088, '28', 655, 'jp', 'Japan', '81', 'NTT Docomo'),
(740, 440, 1088, '32', 815, 'jp', 'Japan', '81', 'NTT Docomo'),
(741, 440, 1088, '61', 1567, 'jp', 'Japan', '81', 'NTT Docomo'),
(742, 440, 1088, '18', 399, 'jp', 'Japan', '81', 'NTT Docomo'),
(743, 441, 1089, '91', 2335, 'jp', 'Japan', '81', 'NTT Docomo'),
(744, 441, 1089, '40', 1039, 'jp', 'Japan', '81', 'NTT Docomo'),
(745, 440, 1088, '66', 1647, 'jp', 'Japan', '81', 'NTT Docomo'),
(746, 440, 1088, '35', 863, 'jp', 'Japan', '81', 'NTT Docomo'),
(747, 440, 1088, '01', 31, 'jp', 'Japan', '81', 'NTT Docomo'),
(748, 441, 1089, '93', 2367, 'jp', 'Japan', '81', 'NTT Docomo'),
(749, 440, 1088, '09', 159, 'jp', 'Japan', '81', 'NTT Docomo'),
(750, 440, 1088, '49', 1183, 'jp', 'Japan', '81', 'NTT Docomo'),
(751, 440, 1088, '29', 671, 'jp', 'Japan', '81', 'NTT Docomo'),
(752, 440, 1088, '33', 831, 'jp', 'Japan', '81', 'NTT Docomo'),
(753, 440, 1088, '60', 1551, 'jp', 'Japan', '81', 'NTT Docomo'),
(754, 440, 1088, '19', 415, 'jp', 'Japan', '81', 'NTT Docomo'),
(755, 441, 1089, '90', 2319, 'jp', 'Japan', '81', 'NTT Docomo'),
(756, 440, 1088, '99', 2463, 'jp', 'Japan', '81', 'NTT Docomo'),
(757, 440, 1088, '78', 1935, 'jp', 'Japan', '81', 'Okinawa Cellular Telephone'),
(758, 440, 1088, '04', 79, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(759, 441, 1089, '62', 1583, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(760, 440, 1088, '45', 1119, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(761, 440, 1088, '20', 527, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(762, 440, 1088, '96', 2415, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(763, 440, 1088, '40', 1039, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(764, 441, 1089, '63', 1599, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(765, 440, 1088, '47', 1151, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(766, 440, 1088, '95', 2399, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(767, 440, 1088, '41', 1055, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(768, 441, 1089, '64', 1615, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(769, 440, 1088, '46', 1135, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(770, 440, 1088, '97', 2431, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(771, 440, 1088, '42', 1071, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(772, 440, 1088, '90', 2319, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(773, 441, 1089, '65', 1631, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(774, 440, 1088, '92', 2351, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(775, 440, 1088, '98', 2447, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(776, 440, 1088, '43', 1087, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(777, 440, 1088, '93', 2367, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(778, 440, 1088, '48', 1167, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(779, 440, 1088, '06', 111, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(780, 441, 1089, '61', 1567, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(781, 440, 1088, '44', 1103, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(782, 440, 1088, '94', 2383, 'jp', 'Japan', '81', 'SoftBank Mobile Corp'),
(783, 440, 1088, '85', 2143, 'jp', 'Japan', '81', 'KDDI Corporation'),
(784, 440, 1088, '83', 2111, 'jp', 'Japan', '81', 'KDDI Corporation'),
(785, 440, 1088, '80', 2063, 'jp', 'Japan', '81', 'KDDI Corporation'),
(786, 440, 1088, '86', 2159, 'jp', 'Japan', '81', 'KDDI Corporation'),
(787, 440, 1088, '81', 2079, 'jp', 'Japan', '81', 'KDDI Corporation'),
(788, 440, 1088, '84', 2127, 'jp', 'Japan', '81', 'KDDI Corporation'),
(789, 440, 1088, '82', 2095, 'jp', 'Japan', '81', 'KDDI Corporation'),
(790, 416, 1046, '77', 1919, 'jo', 'Jordan', '962', 'Orange/Petra'),
(791, 416, 1046, '03', 63, 'jo', 'Jordan', '962', 'Umniah Mobile Co.'),
(792, 416, 1046, '02', 47, 'jo', 'Jordan', '962', 'Xpress'),
(793, 416, 1046, '01', 31, 'jo', 'Jordan', '962', 'ZAIN /J.M.T.S'),
(794, 401, 1025, '01', 31, 'kz', 'Kazakhstan', '7', 'Beeline/KaR-Tel LLP'),
(795, 401, 1025, '07', 127, 'kz', 'Kazakhstan', '7', 'Dalacom/Altel'),
(796, 401, 1025, '02', 47, 'kz', 'Kazakhstan', '7', 'K-Cell'),
(797, 401, 1025, '77', 1919, 'kz', 'Kazakhstan', '7', 'Tele2/NEO/MTS'),
(798, 639, 1593, '05', 95, 'ke', 'Kenya', '254', 'Econet Wireless'),
(799, 639, 1593, '07', 127, 'ke', 'Kenya', '254', 'Orange'),
(800, 639, 1593, '02', 47, 'ke', 'Kenya', '254', 'Safaricom Ltd.'),
(801, 639, 1593, '03', 63, 'ke', 'Kenya', '254', 'Airtel/Zain/Celtel Ltd.'),
(802, 545, 1349, '09', 159, 'ki', 'Kiribati', '686', 'Kiribati Frigate'),
(803, 467, 1127, '193', 403, 'kp', 'Korea N. Dem. People\'s Rep.', '850', 'Sun Net'),
(804, 450, 1104, '08', 143, 'kr', 'Korea S Republic of', '82', 'KT Freetel Co. Ltd.'),
(805, 450, 1104, '02', 47, 'kr', 'Korea S Republic of', '82', 'KT Freetel Co. Ltd.'),
(806, 450, 1104, '04', 79, 'kr', 'Korea S Republic of', '82', 'KT Freetel Co. Ltd.'),
(807, 450, 1104, '06', 111, 'kr', 'Korea S Republic of', '82', 'LG Telecom'),
(808, 450, 1104, '03', 63, 'kr', 'Korea S Republic of', '82', 'SK Telecom'),
(809, 450, 1104, '05', 95, 'kr', 'Korea S Republic of', '82', 'SK Telecom Co. Ltd'),
(810, 419, 1049, '04', 79, 'kw', 'Kuwait', '965', 'Viva'),
(811, 419, 1049, '03', 63, 'kw', 'Kuwait', '965', 'Wataniya'),
(812, 419, 1049, '02', 47, 'kw', 'Kuwait', '965', 'Zain'),
(813, 437, 1079, '03', 63, 'kg', 'Kyrgyzstan', '996', 'AkTel LLC'),
(814, 437, 1079, '01', 31, 'kg', 'Kyrgyzstan', '996', 'Beeline/Bitel'),
(815, 437, 1079, '05', 95, 'kg', 'Kyrgyzstan', '996', 'MEGACOM'),
(816, 437, 1079, '09', 159, 'kg', 'Kyrgyzstan', '996', 'O!/NUR Telecom'),
(817, 457, 1111, '02', 47, 'la', 'Laos P.D.R.', '856', 'ETL Mobile'),
(818, 457, 1111, '01', 31, 'la', 'Laos P.D.R.', '856', 'Lao Tel'),
(819, 457, 1111, '08', 143, 'la', 'Laos P.D.R.', '856', 'Beeline/Tigo/Millicom'),
(820, 457, 1111, '03', 63, 'la', 'Laos P.D.R.', '856', 'UNITEL/LAT'),
(821, 247, 583, '05', 95, 'lv', 'Latvia', '371', 'Bite'),
(822, 247, 583, '01', 31, 'lv', 'Latvia', '371', 'Latvian Mobile Phone'),
(823, 247, 583, '09', 159, 'lv', 'Latvia', '371', 'SIA Camel Mobile'),
(824, 247, 583, '08', 143, 'lv', 'Latvia', '371', 'SIA IZZI'),
(825, 247, 583, '07', 127, 'lv', 'Latvia', '371', 'SIA Master Telecom'),
(826, 247, 583, '06', 111, 'lv', 'Latvia', '371', 'SIA Rigatta'),
(827, 247, 583, '02', 47, 'lv', 'Latvia', '371', 'Tele2'),
(828, 247, 583, '03', 63, 'lv', 'Latvia', '371', 'TRIATEL/Telekom Baltija'),
(829, 415, 1045, '35', 863, 'lb', 'Lebanon', '961', 'Cellis'),
(830, 415, 1045, '33', 831, 'lb', 'Lebanon', '961', 'Cellis'),
(831, 415, 1045, '32', 815, 'lb', 'Lebanon', '961', 'Cellis'),
(832, 415, 1045, '34', 847, 'lb', 'Lebanon', '961', 'FTML Cellis'),
(833, 415, 1045, '39', 927, 'lb', 'Lebanon', '961', 'MIC2/LibanCell/MTC'),
(834, 415, 1045, '38', 911, 'lb', 'Lebanon', '961', 'MIC2/LibanCell/MTC'),
(835, 415, 1045, '37', 895, 'lb', 'Lebanon', '961', 'MIC2/LibanCell/MTC'),
(836, 415, 1045, '01', 31, 'lb', 'Lebanon', '961', 'MIC1 (Alfa)'),
(837, 415, 1045, '03', 63, 'lb', 'Lebanon', '961', 'MIC2/LibanCell/MTC'),
(838, 415, 1045, '36', 879, 'lb', 'Lebanon', '961', 'MIC2/LibanCell/MTC'),
(839, 651, 1617, '02', 47, 'ls', 'Lesotho', '266', 'Econet/Ezi-cel'),
(840, 651, 1617, '01', 31, 'ls', 'Lesotho', '266', 'Vodacom Lesotho'),
(841, 618, 1560, '07', 127, 'lr', 'Liberia', '231', 'CELLCOM'),
(842, 618, 1560, '04', 79, 'lr', 'Liberia', '231', 'Comium BVI'),
(843, 618, 1560, '02', 47, 'lr', 'Liberia', '231', 'Libercell'),
(844, 618, 1560, '20', 527, 'lr', 'Liberia', '231', 'LibTelco'),
(845, 618, 1560, '01', 31, 'lr', 'Liberia', '231', 'Lonestar'),
(846, 606, 1542, '02', 47, 'ly', 'Libya', '218', 'Al-Madar'),
(847, 606, 1542, '01', 31, 'ly', 'Libya', '218', 'Al-Madar'),
(848, 606, 1542, '06', 111, 'ly', 'Libya', '218', 'Hatef'),
(849, 606, 1542, '00', 15, 'ly', 'Libya', '218', 'Libyana'),
(850, 606, 1542, '03', 63, 'ly', 'Libya', '218', 'Libyana'),
(851, 295, 661, '06', 111, 'li', 'Liechtenstein', '423', 'CUBIC (Liechtenstein'),
(852, 295, 661, '07', 127, 'li', 'Liechtenstein', '423', 'First Mobile AG'),
(853, 295, 661, '02', 47, 'li', 'Liechtenstein', '423', 'Orange'),
(854, 295, 661, '01', 31, 'li', 'Liechtenstein', '423', 'Swisscom FL AG'),
(855, 295, 661, '77', 1919, 'li', 'Liechtenstein', '423', 'Alpmobile/Tele2'),
(856, 295, 661, '05', 95, 'li', 'Liechtenstein', '423', 'Telecom FL1 AG'),
(857, 246, 582, '02', 47, 'lt', 'Lithuania', '370', 'Bite'),
(858, 246, 582, '01', 31, 'lt', 'Lithuania', '370', 'Omnitel'),
(859, 246, 582, '03', 63, 'lt', 'Lithuania', '370', 'Tele2'),
(860, 270, 624, '77', 1919, 'lu', 'Luxembourg', '352', 'Millicom Tango GSM'),
(861, 270, 624, '01', 31, 'lu', 'Luxembourg', '352', 'P+T/Post LUXGSM'),
(862, 270, 624, '99', 2463, 'lu', 'Luxembourg', '352', 'Orange/VOXmobile S.A.'),
(863, 455, 1109, '04', 79, 'mo', 'Macao China', '853', 'C.T.M. TELEMOVEL+'),
(864, 455, 1109, '01', 31, 'mo', 'Macao China', '853', 'C.T.M. TELEMOVEL+'),
(865, 455, 1109, '02', 47, 'mo', 'Macao China', '853', 'China Telecom'),
(866, 455, 1109, '05', 95, 'mo', 'Macao China', '853', 'Hutchison Telephone Co. Ltd'),
(867, 455, 1109, '03', 63, 'mo', 'Macao China', '853', 'Hutchison Telephone Co. Ltd'),
(868, 455, 1109, '06', 111, 'mo', 'Macao China', '853', 'Smartone Mobile'),
(869, 455, 1109, '00', 15, 'mo', 'Macao China', '853', 'Smartone Mobile'),
(870, 294, 660, '75', 1887, 'mk', 'Macedonia', '389', 'ONE/Cosmofone'),
(871, 294, 660, '02', 47, 'mk', 'Macedonia', '389', 'ONE/Cosmofone'),
(872, 294, 660, '01', 31, 'mk', 'Macedonia', '389', 'T-Mobile/Mobimak'),
(873, 294, 660, '03', 63, 'mk', 'Macedonia', '389', 'VIP Mobile'),
(874, 646, 1606, '01', 31, 'mg', 'Madagascar', '261', 'Airtel/MADACOM'),
(875, 646, 1606, '02', 47, 'mg', 'Madagascar', '261', 'Orange/Soci'),
(876, 646, 1606, '03', 63, 'mg', 'Madagascar', '261', 'Sacel'),
(877, 646, 1606, '04', 79, 'mg', 'Madagascar', '261', 'Telma'),
(878, 650, 1616, '01', 31, 'mw', 'Malawi', '265', 'TNM/Telekom Network Ltd.'),
(879, 650, 1616, '10', 271, 'mw', 'Malawi', '265', 'Airtel/Zain/Celtel ltd.'),
(880, 502, 1282, '01', 31, 'my', 'Malaysia', '60', 'Art900'),
(881, 502, 1282, '151', 337, 'my', 'Malaysia', '60', 'Baraka Telecom Sdn Bhd'),
(882, 502, 1282, '19', 415, 'my', 'Malaysia', '60', 'CelCom'),
(883, 502, 1282, '13', 319, 'my', 'Malaysia', '60', 'CelCom'),
(884, 502, 1282, '198', 408, 'my', 'Malaysia', '60', 'CelCom'),
(885, 502, 1282, '10', 271, 'my', 'Malaysia', '60', 'Digi Telecommunications'),
(886, 502, 1282, '16', 367, 'my', 'Malaysia', '60', 'Digi Telecommunications'),
(887, 502, 1282, '20', 527, 'my', 'Malaysia', '60', 'Electcoms Wireless Sdn Bhd'),
(888, 502, 1282, '12', 303, 'my', 'Malaysia', '60', 'Maxis'),
(889, 502, 1282, '17', 383, 'my', 'Malaysia', '60', 'Maxis'),
(890, 502, 1282, '11', 287, 'my', 'Malaysia', '60', 'MTX Utara'),
(891, 502, 1282, '153', 339, 'my', 'Malaysia', '60', 'Webe/Packet One Networks (Malaysia) Sdn Bhd'),
(892, 502, 1282, '155', 341, 'my', 'Malaysia', '60', 'Samata Communications Sdn Bhd'),
(893, 502, 1282, '154', 340, 'my', 'Malaysia', '60', 'Tron/Talk Focus Sdn Bhd'),
(894, 502, 1282, '18', 399, 'my', 'Malaysia', '60', 'U Mobile'),
(895, 502, 1282, '195', 405, 'my', 'Malaysia', '60', 'XOX Com Sdn Bhd'),
(896, 502, 1282, '152', 338, 'my', 'Malaysia', '60', 'YES'),
(897, 472, 1138, '01', 31, 'mv', 'Maldives', '960', 'Dhiraagu/C&W'),
(898, 472, 1138, '02', 47, 'mv', 'Maldives', '960', 'Ooredo/Wataniya'),
(899, 610, 1552, '01', 31, 'ml', 'Mali', '223', 'Malitel'),
(900, 610, 1552, '02', 47, 'ml', 'Mali', '223', 'Orange/IKATEL'),
(901, 278, 632, '21', 543, 'mt', 'Malta', '356', 'GO Mobile'),
(902, 278, 632, '77', 1919, 'mt', 'Malta', '356', 'Melita'),
(903, 278, 632, '01', 31, 'mt', 'Malta', '356', 'Vodafone'),
(904, 340, 832, '12', 303, 'mq', 'Martinique (French Department of)', '596', 'UTS Caraibe'),
(905, 609, 1545, '02', 47, 'mr', 'Mauritania', '222', 'Chinguitel SA'),
(906, 609, 1545, '01', 31, 'mr', 'Mauritania', '222', 'Mattel'),
(907, 609, 1545, '10', 271, 'mr', 'Mauritania', '222', 'Mauritel'),
(908, 617, 1559, '10', 271, 'mu', 'Mauritius', '230', 'Emtel Ltd'),
(909, 617, 1559, '03', 63, 'mu', 'Mauritius', '230', 'Mahanagar Telephone'),
(910, 617, 1559, '02', 47, 'mu', 'Mauritius', '230', 'Mahanagar Telephone'),
(911, 617, 1559, '01', 31, 'mu', 'Mauritius', '230', 'Orange/Cellplus'),
(912, 334, 820, '04', 79, 'mx', 'Mexico', '52', 'AT&T/IUSACell'),
(913, 334, 820, '50', 1295, 'mx', 'Mexico', '52', 'AT&T/IUSACell'),
(914, 334, 820, '050', 80, 'mx', 'Mexico', '52', 'AT&T/IUSACell'),
(915, 334, 820, '040', 64, 'mx', 'Mexico', '52', 'AT&T/IUSACell'),
(916, 334, 820, '03', 63, 'mx', 'Mexico', '52', 'Movistar/Pegaso'),
(917, 334, 820, '030', 48, 'mx', 'Mexico', '52', 'Movistar/Pegaso'),
(918, 334, 820, '010', 16, 'mx', 'Mexico', '52', 'NEXTEL'),
(919, 334, 820, '09', 159, 'mx', 'Mexico', '52', 'NEXTEL'),
(920, 334, 820, '01', 31, 'mx', 'Mexico', '52', 'NEXTEL'),
(921, 334, 820, '090', 144, 'mx', 'Mexico', '52', 'NEXTEL'),
(922, 334, 820, '080', 128, 'mx', 'Mexico', '52', 'Operadora Unefon SA de CV'),
(923, 334, 820, '070', 112, 'mx', 'Mexico', '52', 'Operadora Unefon SA de CV'),
(924, 334, 820, '060', 96, 'mx', 'Mexico', '52', 'SAI PCS'),
(925, 334, 820, '020', 32, 'mx', 'Mexico', '52', 'TelCel/America Movil'),
(926, 334, 820, '02', 47, 'mx', 'Mexico', '52', 'TelCel/America Movil'),
(927, 550, 1360, '01', 31, 'fm', 'Micronesia', '691', 'FSM Telecom'),
(928, 259, 601, '04', 79, 'md', 'Moldova', '373', 'Eventis Mobile'),
(929, 259, 601, '03', 63, 'md', 'Moldova', '373', 'IDC/Unite'),
(930, 259, 601, '99', 2463, 'md', 'Moldova', '373', 'IDC/Unite'),
(931, 259, 601, '05', 95, 'md', 'Moldova', '373', 'IDC/Unite'),
(932, 259, 601, '02', 47, 'md', 'Moldova', '373', 'Moldcell'),
(933, 259, 601, '01', 31, 'md', 'Moldova', '373', 'Orange/Voxtel'),
(934, 212, 530, '10', 271, 'mc', 'Monaco', '377', 'Monaco Telecom'),
(935, 212, 530, '01', 31, 'mc', 'Monaco', '377', 'Monaco Telecom'),
(936, 428, 1064, '98', 2447, 'mn', 'Mongolia', '976', 'G-Mobile Corporation Ltd'),
(937, 428, 1064, '99', 2463, 'mn', 'Mongolia', '976', 'Mobicom'),
(938, 428, 1064, '00', 15, 'mn', 'Mongolia', '976', 'Skytel Co. Ltd'),
(939, 428, 1064, '91', 2335, 'mn', 'Mongolia', '976', 'Skytel Co. Ltd'),
(940, 428, 1064, '88', 2191, 'mn', 'Mongolia', '976', 'Unitel'),
(941, 297, 663, '02', 47, 'me', 'Montenegro', '382', 'Monet/T-mobile'),
(942, 297, 663, '03', 63, 'me', 'Montenegro', '382', 'Mtel'),
(943, 297, 663, '01', 31, 'me', 'Montenegro', '382', 'Telenor/Promonte GSM'),
(944, 354, 852, '860', 2144, 'ms', 'Montserrat', '1664', 'Cable & Wireless'),
(945, 604, 1540, '01', 31, 'ma', 'Morocco', '212', 'IAM/Itissallat'),
(946, 604, 1540, '02', 47, 'ma', 'Morocco', '212', 'INWI/WANA'),
(947, 604, 1540, '00', 15, 'ma', 'Morocco', '212', 'Medi Telecom'),
(948, 643, 1603, '01', 31, 'mz', 'Mozambique', '258', 'mCel'),
(949, 643, 1603, '03', 63, 'mz', 'Mozambique', '258', 'Movitel'),
(950, 643, 1603, '04', 79, 'mz', 'Mozambique', '258', 'Vodacom'),
(951, 414, 1044, '01', 31, 'mm', 'Myanmar (Burma)', '95', 'Myanmar Post & Teleco.'),
(952, 414, 1044, '05', 95, 'mm', 'Myanmar (Burma)', '95', 'Oreedoo'),
(953, 414, 1044, '06', 111, 'mm', 'Myanmar (Burma)', '95', 'Telenor'),
(954, 649, 1609, '03', 63, 'na', 'Namibia', '264', 'Leo / Orascom'),
(955, 649, 1609, '01', 31, 'na', 'Namibia', '264', 'MTC'),
(956, 649, 1609, '02', 47, 'na', 'Namibia', '264', 'Switch/Nam. Telec.'),
(957, 429, 1065, '02', 47, 'np', 'Nepal', '977', 'Ncell'),
(958, 429, 1065, '01', 31, 'np', 'Nepal', '977', 'NT Mobile / Namaste'),
(959, 429, 1065, '04', 79, 'np', 'Nepal', '977', 'Smart Cell'),
(960, 204, 516, '14', 335, 'nl', 'Netherlands', '31', '6GMOBILE BV'),
(961, 204, 516, '23', 575, 'nl', 'Netherlands', '31', 'Aspider Solutions'),
(962, 204, 516, '05', 95, 'nl', 'Netherlands', '31', 'Elephant Talk Communications Premium Rate Services Netherlands BV'),
(963, 204, 516, '17', 383, 'nl', 'Netherlands', '31', 'Intercity Mobile Communications BV'),
(964, 204, 516, '10', 271, 'nl', 'Netherlands', '31', 'KPN Telecom B.V.'),
(965, 204, 516, '08', 143, 'nl', 'Netherlands', '31', 'KPN Telecom B.V.'),
(966, 204, 516, '69', 1695, 'nl', 'Netherlands', '31', 'KPN Telecom B.V.'),
(967, 204, 516, '12', 303, 'nl', 'Netherlands', '31', 'KPN/Telfort'),
(968, 204, 516, '28', 655, 'nl', 'Netherlands', '31', 'Lancelot BV'),
(969, 204, 516, '09', 159, 'nl', 'Netherlands', '31', 'Lycamobile Ltd'),
(970, 204, 516, '06', 111, 'nl', 'Netherlands', '31', 'Mundio/Vectone Mobile'),
(971, 204, 516, '21', 543, 'nl', 'Netherlands', '31', 'NS Railinfrabeheer B.V.'),
(972, 204, 516, '24', 591, 'nl', 'Netherlands', '31', 'Private Mobility Nederland BV'),
(973, 204, 516, '98', 2447, 'nl', 'Netherlands', '31', 'T-Mobile B.V.'),
(974, 204, 516, '16', 367, 'nl', 'Netherlands', '31', 'T-Mobile B.V.'),
(975, 204, 516, '20', 527, 'nl', 'Netherlands', '31', 'T-mobile/former Orange'),
(976, 204, 516, '02', 47, 'nl', 'Netherlands', '31', 'Tele2'),
(977, 204, 516, '07', 127, 'nl', 'Netherlands', '31', 'Teleena Holding BV'),
(978, 204, 516, '68', 1679, 'nl', 'Netherlands', '31', 'Unify Mobile'),
(979, 204, 516, '18', 399, 'nl', 'Netherlands', '31', 'UPC Nederland BV'),
(980, 204, 516, '04', 79, 'nl', 'Netherlands', '31', 'Vodafone Libertel'),
(981, 204, 516, '03', 63, 'nl', 'Netherlands', '31', 'Voiceworks Mobile BV'),
(982, 204, 516, '15', 351, 'nl', 'Netherlands', '31', 'Ziggo BV'),
(983, 362, 866, '630', 1584, 'an', 'Netherlands Antilles', '599', 'Cingular Wireless'),
(984, 362, 866, '51', 1311, 'an', 'Netherlands Antilles', '599', 'TELCELL GSM'),
(985, 362, 866, '91', 2335, 'an', 'Netherlands Antilles', '599', 'SETEL GSM'),
(986, 362, 866, '951', 2385, 'an', 'Netherlands Antilles', '599', 'UTS Wireless'),
(987, 546, 1350, '01', 31, 'nc', 'New Caledonia', '687', 'OPT Mobilis'),
(988, 530, 1328, '28', 655, 'nz', 'New Zealand', '64', '2degrees'),
(989, 530, 1328, '05', 95, 'nz', 'New Zealand', '64', 'Spark/NZ Telecom'),
(990, 530, 1328, '02', 47, 'nz', 'New Zealand', '64', 'Spark/NZ Telecom'),
(991, 530, 1328, '04', 79, 'nz', 'New Zealand', '64', 'Telstra'),
(992, 530, 1328, '24', 591, 'nz', 'New Zealand', '64', 'Two Degrees Mobile Ltd'),
(993, 530, 1328, '01', 31, 'nz', 'New Zealand', '64', 'Vodafone'),
(994, 530, 1328, '03', 63, 'nz', 'New Zealand', '64', 'Walker Wireless Ltd.'),
(995, 710, 1808, '21', 543, 'ni', 'Nicaragua', '505', 'Empresa Nicaraguense de Telecomunicaciones SA (ENITEL)'),
(996, 710, 1808, '30', 783, 'ni', 'Nicaragua', '505', 'Movistar'),
(997, 710, 1808, '73', 1855, 'ni', 'Nicaragua', '505', 'Claro'),
(998, 614, 1556, '03', 63, 'ne', 'Niger', '227', 'MOOV/TeleCel'),
(999, 614, 1556, '04', 79, 'ne', 'Niger', '227', 'Orange/Sahelc.'),
(1000, 614, 1556, '01', 31, 'ne', 'Niger', '227', 'Orange/Sahelc.'),
(1001, 614, 1556, '02', 47, 'ne', 'Niger', '227', 'Airtel/Zain/CelTel'),
(1002, 621, 1569, '20', 527, 'ng', 'Nigeria', '234', 'Airtel/ZAIN/Econet'),
(1003, 621, 1569, '60', 1551, 'ng', 'Nigeria', '234', 'ETISALAT'),
(1004, 621, 1569, '50', 1295, 'ng', 'Nigeria', '234', 'Glo Mobile'),
(1005, 621, 1569, '40', 1039, 'ng', 'Nigeria', '234', 'M-Tel/Nigeria Telecom. Ltd.'),
(1006, 621, 1569, '30', 783, 'ng', 'Nigeria', '234', 'MTN'),
(1007, 621, 1569, '99', 2463, 'ng', 'Nigeria', '234', 'Starcomms'),
(1008, 621, 1569, '25', 607, 'ng', 'Nigeria', '234', 'Visafone'),
(1009, 621, 1569, '01', 31, 'ng', 'Nigeria', '234', 'Visafone'),
(1010, 555, 1365, '01', 31, 'nu', 'Niue', '683', 'Niue Telecom'),
(1011, 242, 578, '09', 159, 'no', 'Norway', '47', 'Com4 AS'),
(1012, 242, 578, '14', 335, 'no', 'Norway', '47', 'ICE Nordisk Mobiltelefon AS'),
(1013, 242, 578, '21', 543, 'no', 'Norway', '47', 'Jernbaneverket (GSM-R)'),
(1014, 242, 578, '20', 527, 'no', 'Norway', '47', 'Jernbaneverket (GSM-R)'),
(1015, 242, 578, '23', 575, 'no', 'Norway', '47', 'Lycamobile Ltd'),
(1016, 242, 578, '02', 47, 'no', 'Norway', '47', 'Netcom'),
(1017, 242, 578, '05', 95, 'no', 'Norway', '47', 'Network Norway AS'),
(1018, 242, 578, '22', 559, 'no', 'Norway', '47', 'Network Norway AS'),
(1019, 242, 578, '06', 111, 'no', 'Norway', '47', 'ICE Nordisk Mobiltelefon AS'),
(1020, 242, 578, '08', 143, 'no', 'Norway', '47', 'TDC Mobil A/S'),
(1021, 242, 578, '04', 79, 'no', 'Norway', '47', 'Tele2'),
(1022, 242, 578, '12', 303, 'no', 'Norway', '47', 'Telenor'),
(1023, 242, 578, '01', 31, 'no', 'Norway', '47', 'Telenor'),
(1024, 242, 578, '03', 63, 'no', 'Norway', '47', 'Teletopia'),
(1025, 242, 578, '017', 23, 'no', 'Norway', '47', 'Ventelo AS'),
(1026, 242, 578, '07', 127, 'no', 'Norway', '47', 'Ventelo AS'),
(1027, 422, 1058, '03', 63, 'om', 'Oman', '968', 'Nawras'),
(1028, 422, 1058, '02', 47, 'om', 'Oman', '968', 'Oman Mobile/GTO'),
(1029, 410, 1040, '08', 143, 'pk', 'Pakistan', '92', 'Instaphone'),
(1030, 410, 1040, '01', 31, 'pk', 'Pakistan', '92', 'Mobilink'),
(1031, 410, 1040, '06', 111, 'pk', 'Pakistan', '92', 'Telenor'),
(1032, 410, 1040, '03', 63, 'pk', 'Pakistan', '92', 'UFONE/PAKTel'),
(1033, 410, 1040, '07', 127, 'pk', 'Pakistan', '92', 'Warid Telecom'),
(1034, 410, 1040, '04', 79, 'pk', 'Pakistan', '92', 'ZONG/CMPak'),
(1035, 552, 1362, '80', 2063, 'pw', 'Palau (Republic of)', '680', 'Palau Mobile Corp. (PMC) (Palau'),
(1036, 552, 1362, '01', 31, 'pw', 'Palau (Republic of)', '680', 'Palau National Communications Corp. (PNCC) (Palau'),
(1037, 425, 1061, '05', 95, 'ps', 'Palestinian Territory', '970', 'Jawwal'),
(1038, 425, 1061, '06', 111, 'ps', 'Palestinian Territory', '970', 'Wataniya Mobile'),
(1039, 714, 1812, '01', 31, 'pa', 'Panama', '507', 'Cable & W./Mas Movil'),
(1040, 714, 1812, '03', 63, 'pa', 'Panama', '507', 'Claro'),
(1041, 714, 1812, '04', 79, 'pa', 'Panama', '507', 'Digicel'),
(1042, 714, 1812, '020', 32, 'pa', 'Panama', '507', 'Movistar'),
(1043, 714, 1812, '02', 47, 'pa', 'Panama', '507', 'Movistar'),
(1044, 537, 1335, '03', 63, 'pg', 'Papua New Guinea', '675', 'Digicel'),
(1045, 537, 1335, '02', 47, 'pg', 'Papua New Guinea', '675', 'GreenCom PNG Ltd'),
(1046, 537, 1335, '01', 31, 'pg', 'Papua New Guinea', '675', 'Pacific Mobile'),
(1047, 744, 1860, '02', 47, 'py', 'Paraguay', '595', 'Claro/Hutchison'),
(1048, 744, 1860, '03', 63, 'py', 'Paraguay', '595', 'Compa'),
(1049, 744, 1860, '01', 31, 'py', 'Paraguay', '595', 'Hola/VOX'),
(1050, 744, 1860, '05', 95, 'py', 'Paraguay', '595', 'TIM/Nucleo/Personal'),
(1051, 744, 1860, '04', 79, 'py', 'Paraguay', '595', 'Tigo/Telecel'),
(1052, 716, 1814, '20', 527, 'pe', 'Peru', '51', 'Claro /Amer.Mov./TIM'),
(1053, 716, 1814, '10', 271, 'pe', 'Peru', '51', 'Claro /Amer.Mov./TIM'),
(1054, 716, 1814, '02', 47, 'pe', 'Peru', '51', 'GlobalStar'),
(1055, 716, 1814, '01', 31, 'pe', 'Peru', '51', 'GlobalStar'),
(1056, 716, 1814, '06', 111, 'pe', 'Peru', '51', 'Movistar'),
(1057, 716, 1814, '17', 383, 'pe', 'Peru', '51', 'Nextel'),
(1058, 716, 1814, '07', 127, 'pe', 'Peru', '51', 'Nextel'),
(1059, 716, 1814, '15', 351, 'pe', 'Peru', '51', 'Viettel Mobile'),
(1060, 515, 1301, '00', 15, 'ph', 'Philippines', '63', 'Fix Line'),
(1061, 515, 1301, '01', 31, 'ph', 'Philippines', '63', 'Globe Telecom'),
(1062, 515, 1301, '02', 47, 'ph', 'Philippines', '63', 'Globe Telecom'),
(1063, 515, 1301, '88', 2191, 'ph', 'Philippines', '63', 'Next Mobile'),
(1064, 515, 1301, '18', 399, 'ph', 'Philippines', '63', 'RED Mobile/Cure'),
(1065, 515, 1301, '03', 63, 'ph', 'Philippines', '63', 'Smart'),
(1066, 515, 1301, '05', 95, 'ph', 'Philippines', '63', 'SUN/Digitel'),
(1067, 260, 608, '17', 383, 'pl', 'Poland', '48', 'Aero2 SP.'),
(1068, 260, 608, '18', 399, 'pl', 'Poland', '48', 'AMD Telecom.'),
(1069, 260, 608, '38', 911, 'pl', 'Poland', '48', 'CallFreedom Sp. z o.o.'),
(1070, 260, 608, '12', 303, 'pl', 'Poland', '48', 'Cyfrowy POLSAT S.A.'),
(1071, 260, 608, '08', 143, 'pl', 'Poland', '48', 'e-Telko'),
(1072, 260, 608, '09', 159, 'pl', 'Poland', '48', 'Lycamobile'),
(1073, 260, 608, '16', 367, 'pl', 'Poland', '48', 'Mobyland'),
(1074, 260, 608, '36', 879, 'pl', 'Poland', '48', 'Mundio Mobile Sp. z o.o.'),
(1075, 260, 608, '07', 127, 'pl', 'Poland', '48', 'Play/P4'),
(1076, 260, 608, '11', 287, 'pl', 'Poland', '48', 'NORDISK Polska'),
(1077, 260, 608, '05', 95, 'pl', 'Poland', '48', 'Orange/IDEA/Centertel'),
(1078, 260, 608, '03', 63, 'pl', 'Poland', '48', 'Orange/IDEA/Centertel'),
(1079, 260, 608, '35', 863, 'pl', 'Poland', '48', 'PKP Polskie Linie Kolejowe S.A.'),
(1080, 260, 608, '98', 2447, 'pl', 'Poland', '48', 'Play/P4'),
(1081, 260, 608, '06', 111, 'pl', 'Poland', '48', 'Play/P4'),
(1082, 260, 608, '01', 31, 'pl', 'Poland', '48', 'Polkomtel/Plus'),
(1083, 260, 608, '13', 319, 'pl', 'Poland', '48', 'Sferia'),
(1084, 260, 608, '10', 271, 'pl', 'Poland', '48', 'Sferia'),
(1085, 260, 608, '14', 335, 'pl', 'Poland', '48', 'Sferia'),
(1086, 260, 608, '02', 47, 'pl', 'Poland', '48', 'T-Mobile/ERA'),
(1087, 260, 608, '34', 847, 'pl', 'Poland', '48', 'T-Mobile/ERA'),
(1088, 260, 608, '15', 351, 'pl', 'Poland', '48', 'Tele2'),
(1089, 260, 608, '04', 79, 'pl', 'Poland', '48', 'Tele2'),
(1090, 268, 616, '04', 79, 'pt', 'Portugal', '351', 'Lycamobile'),
(1091, 268, 616, '03', 63, 'pt', 'Portugal', '351', 'NOS/Optimus'),
(1092, 268, 616, '07', 127, 'pt', 'Portugal', '351', 'NOS/Optimus'),
(1093, 268, 616, '06', 111, 'pt', 'Portugal', '351', 'MEO/TMN'),
(1094, 268, 616, '01', 31, 'pt', 'Portugal', '351', 'Vodafone'),
(1095, 330, 816, '11', 287, 'pr', 'Puerto Rico', '', 'Puerto Rico Telephone Company Inc. (PRTC)'),
(1096, 330, 816, '110', 272, 'pr', 'Puerto Rico', '', 'Puerto Rico Telephone Company Inc. (PRTC)'),
(1097, 427, 1063, '01', 31, 'qa', 'Qatar', '974', 'Ooredoo/Qtel'),
(1098, 427, 1063, '02', 47, 'qa', 'Qatar', '974', 'Vodafone'),
(1099, 647, 1607, '00', 15, 're', 'Reunion', '262', 'Orange'),
(1100, 647, 1607, '02', 47, 're', 'Reunion', '262', 'Outremer Telecom'),
(1101, 647, 1607, '10', 271, 're', 'Reunion', '262', 'SFR'),
(1102, 226, 550, '03', 63, 'ro', 'Romania', '40', 'Cosmote'),
(1103, 226, 550, '11', 287, 'ro', 'Romania', '40', 'Enigma Systems'),
(1104, 226, 550, '16', 367, 'ro', 'Romania', '40', 'Lycamobile'),
(1105, 226, 550, '10', 271, 'ro', 'Romania', '40', 'Orange'),
(1106, 226, 550, '05', 95, 'ro', 'Romania', '40', 'RCS&RDS Digi Mobile'),
(1107, 226, 550, '02', 47, 'ro', 'Romania', '40', 'Romtelecom SA'),
(1108, 226, 550, '06', 111, 'ro', 'Romania', '40', 'Telemobil/Zapp'),
(1109, 226, 550, '01', 31, 'ro', 'Romania', '40', 'Vodafone'),
(1110, 226, 550, '04', 79, 'ro', 'Romania', '40', 'Telemobil/Zapp'),
(1111, 250, 592, '12', 303, 'ru', 'Russian Federation', '79', 'Baykal Westcom'),
(1112, 250, 592, '28', 655, 'ru', 'Russian Federation', '79', 'BeeLine/VimpelCom'),
(1113, 250, 592, '10', 271, 'ru', 'Russian Federation', '79', 'DTC/Don Telecom'),
(1114, 250, 592, '13', 319, 'ru', 'Russian Federation', '79', 'Kuban GSM'),
(1115, 250, 592, '35', 863, 'ru', 'Russian Federation', '79', 'MOTIV/LLC Ekaterinburg-2000'),
(1116, 250, 592, '02', 47, 'ru', 'Russian Federation', '79', 'Megafon'),
(1117, 250, 592, '01', 31, 'ru', 'Russian Federation', '79', 'MTS'),
(1118, 250, 592, '03', 63, 'ru', 'Russian Federation', '79', 'NCC'),
(1119, 250, 592, '16', 367, 'ru', 'Russian Federation', '79', 'NTC'),
(1120, 250, 592, '19', 415, 'ru', 'Russian Federation', '79', 'OJSC Altaysvyaz'),
(1121, 250, 592, '11', 287, 'ru', 'Russian Federation', '79', 'Orensot'),
(1122, 250, 592, '92', 2351, 'ru', 'Russian Federation', '79', 'Printelefone'),
(1123, 250, 592, '04', 79, 'ru', 'Russian Federation', '79', 'Sibchallenge'),
(1124, 250, 592, '44', 1103, 'ru', 'Russian Federation', '79', 'StavTelesot'),
(1125, 250, 592, '20', 527, 'ru', 'Russian Federation', '79', 'Tele2/ECC/Volgogr.'),
(1126, 250, 592, '93', 2367, 'ru', 'Russian Federation', '79', 'Telecom XXL'),
(1127, 250, 592, '39', 927, 'ru', 'Russian Federation', '79', 'UralTel'),
(1128, 250, 592, '17', 383, 'ru', 'Russian Federation', '79', 'UralTel'),
(1129, 250, 592, '99', 2463, 'ru', 'Russian Federation', '79', 'BeeLine/VimpelCom'),
(1130, 250, 592, '05', 95, 'ru', 'Russian Federation', '79', 'Yenisey Telecom'),
(1131, 250, 592, '15', 351, 'ru', 'Russian Federation', '79', 'ZAO SMARTS'),
(1132, 250, 592, '07', 127, 'ru', 'Russian Federation', '79', 'ZAO SMARTS'),
(1133, 635, 1589, '14', 335, 'rw', 'Rwanda', '250', 'Airtel'),
(1134, 635, 1589, '10', 271, 'rw', 'Rwanda', '250', 'MTN/Rwandacell'),
(1135, 635, 1589, '13', 319, 'rw', 'Rwanda', '250', 'TIGO'),
(1136, 356, 854, '110', 272, 'kn', 'Saint Kitts and Nevis', '1869', 'Cable & Wireless'),
(1137, 356, 854, '50', 1295, 'kn', 'Saint Kitts and Nevis', '1869', 'Digicel'),
(1138, 356, 854, '70', 1807, 'kn', 'Saint Kitts and Nevis', '1869', 'UTS Cariglobe'),
(1139, 358, 856, '110', 272, 'lc', 'Saint Lucia', '1758', 'Cable & Wireless'),
(1140, 358, 856, '30', 783, 'lc', 'Saint Lucia', '1758', 'Cingular Wireless'),
(1141, 358, 856, '50', 1295, 'lc', 'Saint Lucia', '1758', 'Digicel (St Lucia) Limited'),
(1142, 549, 1353, '27', 639, 'ws', 'Samoa', '685', 'Samoatel Mobile'),
(1143, 549, 1353, '01', 31, 'ws', 'Samoa', '685', 'Telecom Samoa Cellular Ltd.'),
(1144, 292, 658, '01', 31, 'sm', 'San Marino', '378', 'Prima Telecom'),
(1145, 626, 1574, '01', 31, 'st', 'Sao Tome & Principe', '239', 'CSTmovel'),
(1146, 901, 2305, '14', 335, 'n/a', 'Satellite Networks', '870', 'AeroMobile'),
(1147, 901, 2305, '11', 287, 'n/a', 'Satellite Networks', '870', 'InMarSAT'),
(1148, 901, 2305, '12', 303, 'n/a', 'Satellite Networks', '870', 'Maritime Communications Partner AS'),
(1149, 901, 2305, '05', 95, 'n/a', 'Satellite Networks', '870', 'Thuraya Satellite'),
(1150, 420, 1056, '07', 127, 'sa', 'Saudi Arabia', '966', 'Zain'),
(1151, 420, 1056, '03', 63, 'sa', 'Saudi Arabia', '966', 'Etihad/Etisalat/Mobily'),
(1152, 420, 1056, '06', 111, 'sa', 'Saudi Arabia', '966', 'Lebara Mobile'),
(1153, 420, 1056, '01', 31, 'sa', 'Saudi Arabia', '966', 'STC/Al Jawal'),
(1154, 420, 1056, '05', 95, 'sa', 'Saudi Arabia', '966', 'Virgin Mobile'),
(1155, 420, 1056, '04', 79, 'sa', 'Saudi Arabia', '966', 'Zain'),
(1156, 608, 1544, '03', 63, 'sn', 'Senegal', '221', 'Expresso/Sudatel'),
(1157, 608, 1544, '01', 31, 'sn', 'Senegal', '221', 'Orange/Sonatel'),
(1158, 608, 1544, '02', 47, 'sn', 'Senegal', '221', 'TIGO/Sentel GSM'),
(1159, 220, 544, '03', 63, 'rs', 'Serbia', '381', 'MTS/Telekom Srbija'),
(1160, 220, 544, '02', 47, 'rs', 'Serbia', '381', 'Telenor/Mobtel'),
(1161, 220, 544, '01', 31, 'rs', 'Serbia', '381', 'Telenor/Mobtel'),
(1162, 220, 544, '05', 95, 'rs', 'Serbia', '381', 'VIP Mobile'),
(1163, 633, 1587, '10', 271, 'sc', 'Seychelles', '248', 'Airtel'),
(1164, 633, 1587, '01', 31, 'sc', 'Seychelles', '248', 'C&W'),
(1165, 633, 1587, '02', 47, 'sc', 'Seychelles', '248', 'Smartcom'),
(1166, 619, 1561, '03', 63, 'sl', 'Sierra Leone', '232', 'Africel'),
(1167, 619, 1561, '01', 31, 'sl', 'Sierra Leone', '232', 'Airtel/Zain/Celtel'),
(1168, 619, 1561, '04', 79, 'sl', 'Sierra Leone', '232', 'Comium'),
(1169, 619, 1561, '05', 95, 'sl', 'Sierra Leone', '232', 'Africel'),
(1170, 619, 1561, '02', 47, 'sl', 'Sierra Leone', '232', 'Tigo/Millicom'),
(1171, 619, 1561, '25', 607, 'sl', 'Sierra Leone', '232', 'Mobitel'),
(1172, 525, 1317, '12', 303, 'sg', 'Singapore', '65', 'GRID Communications Pte Ltd'),
(1173, 525, 1317, '03', 63, 'sg', 'Singapore', '65', 'MobileOne Ltd'),
(1174, 525, 1317, '02', 47, 'sg', 'Singapore', '65', 'Singtel'),
(1175, 525, 1317, '01', 31, 'sg', 'Singapore', '65', 'Singtel'),
(1176, 525, 1317, '07', 127, 'sg', 'Singapore', '65', 'Singtel'),
(1177, 525, 1317, '06', 111, 'sg', 'Singapore', '65', 'Starhub'),
(1178, 525, 1317, '05', 95, 'sg', 'Singapore', '65', 'Starhub'),
(1179, 231, 561, '03', 63, 'sk', 'Slovakia', '421', '4Ka'),
(1180, 231, 561, '06', 111, 'sk', 'Slovakia', '421', 'O2'),
(1181, 231, 561, '05', 95, 'sk', 'Slovakia', '421', 'Orange'),
(1182, 231, 561, '01', 31, 'sk', 'Slovakia', '421', 'Orange'),
(1183, 231, 561, '15', 351, 'sk', 'Slovakia', '421', 'Orange'),
(1184, 231, 561, '02', 47, 'sk', 'Slovakia', '421', 'T-Mobile'),
(1185, 231, 561, '04', 79, 'sk', 'Slovakia', '421', 'T-Mobile'),
(1186, 231, 561, '99', 2463, 'sk', 'Slovakia', '421', 'Zeleznice Slovenskej republiky (ZSR)'),
(1187, 293, 659, '41', 1055, 'si', 'Slovenia', '386', 'Mobitel'),
(1188, 293, 659, '40', 1039, 'si', 'Slovenia', '386', 'SI.Mobil'),
(1189, 293, 659, '10', 271, 'si', 'Slovenia', '386', 'Slovenske zeleznice d.o.o.'),
(1190, 293, 659, '64', 1615, 'si', 'Slovenia', '386', 'T-2 d.o.o.'),
(1191, 293, 659, '70', 1807, 'si', 'Slovenia', '386', 'Telemach/TusMobil/VEGA'),
(1192, 540, 1344, '02', 47, 'sb', 'Solomon Islands', '677', 'bemobile'),
(1193, 540, 1344, '10', 271, 'sb', 'Solomon Islands', '677', 'BREEZE'),
(1194, 540, 1344, '01', 31, 'sb', 'Solomon Islands', '677', 'BREEZE'),
(1195, 637, 1591, '30', 783, 'so', 'Somalia', '252', 'Golis'),
(1196, 637, 1591, '19', 415, 'so', 'Somalia', '252', 'HorTel'),
(1197, 637, 1591, '60', 1551, 'so', 'Somalia', '252', 'Nationlink'),
(1198, 637, 1591, '10', 271, 'so', 'Somalia', '252', 'Nationlink'),
(1199, 637, 1591, '04', 79, 'so', 'Somalia', '252', 'Somafone'),
(1200, 637, 1591, '82', 2095, 'so', 'Somalia', '252', 'Somtel'),
(1201, 637, 1591, '71', 1823, 'so', 'Somalia', '252', 'Somtel'),
(1202, 637, 1591, '01', 31, 'so', 'Somalia', '252', 'Telesom'),
(1203, 655, 1621, '02', 47, 'za', 'South Africa', '27', '8.ta'),
(1204, 655, 1621, '21', 543, 'za', 'South Africa', '27', 'Cape Town Metropolitan'),
(1205, 655, 1621, '07', 127, 'za', 'South Africa', '27', 'Cell C'),
(1206, 655, 1621, '10', 271, 'za', 'South Africa', '27', 'MTN'),
(1207, 655, 1621, '12', 303, 'za', 'South Africa', '27', 'MTN'),
(1208, 655, 1621, '06', 111, 'za', 'South Africa', '27', 'Sentech'),
(1209, 655, 1621, '01', 31, 'za', 'South Africa', '27', 'Vodacom'),
(1210, 655, 1621, '19', 415, 'za', 'South Africa', '27', 'Wireless Business Solutions (Pty) Ltd'),
(1211, 659, 1625, '03', 63, 'ss', 'South Sudan (Republic of)', '', 'Gemtel Ltd (South Sudan'),
(1212, 659, 1625, '02', 47, 'ss', 'South Sudan (Republic of)', '', 'MTN South Sudan (South Sudan'),
(1213, 659, 1625, '04', 79, 'ss', 'South Sudan (Republic of)', '', 'Network of The World Ltd (NOW) (South Sudan'),
(1214, 659, 1625, '06', 111, 'ss', 'South Sudan (Republic of)', '', 'Zain South Sudan (South Sudan'),
(1215, 214, 532, '23', 575, 'es', 'Spain', '34', 'Lycamobile SL'),
(1216, 214, 532, '22', 559, 'es', 'Spain', '34', 'Digi Spain Telecom SL'),
(1217, 214, 532, '15', 351, 'es', 'Spain', '34', 'BT Espana  SAU'),
(1218, 214, 532, '18', 399, 'es', 'Spain', '34', 'Cableuropa SAU (ONO)'),
(1219, 214, 532, '08', 143, 'es', 'Spain', '34', 'Euskaltel SA'),
(1220, 214, 532, '20', 527, 'es', 'Spain', '34', 'fonYou Wireless SL'),
(1221, 214, 532, '32', 815, 'es', 'Spain', '34', 'ION Mobile'),
(1222, 214, 532, '21', 543, 'es', 'Spain', '34', 'Jazz Telecom SAU'),
(1223, 214, 532, '26', 623, 'es', 'Spain', '34', 'Lleida'),
(1224, 214, 532, '25', 607, 'es', 'Spain', '34', 'Lycamobile SL'),
(1225, 214, 532, '07', 127, 'es', 'Spain', '34', 'Movistar'),
(1226, 214, 532, '05', 95, 'es', 'Spain', '34', 'Movistar'),
(1227, 214, 532, '11', 287, 'es', 'Spain', '34', 'Orange'),
(1228, 214, 532, '09', 159, 'es', 'Spain', '34', 'Orange'),
(1229, 214, 532, '03', 63, 'es', 'Spain', '34', 'Orange'),
(1230, 214, 532, '17', 383, 'es', 'Spain', '34', 'R Cable y Telec. Galicia SA'),
(1231, 214, 532, '19', 415, 'es', 'Spain', '34', 'Simyo/KPN'),
(1232, 214, 532, '16', 367, 'es', 'Spain', '34', 'Telecable de Asturias SA'),
(1233, 214, 532, '27', 639, 'es', 'Spain', '34', 'Truphone'),
(1234, 214, 532, '01', 31, 'es', 'Spain', '34', 'Vodafone'),
(1235, 214, 532, '06', 111, 'es', 'Spain', '34', 'Vodafone Enabler Espana SL'),
(1236, 214, 532, '04', 79, 'es', 'Spain', '34', 'Yoigo'),
(1237, 413, 1043, '05', 95, 'lk', 'Sri Lanka', '94', 'Airtel'),
(1238, 413, 1043, '03', 63, 'lk', 'Sri Lanka', '94', 'Etisalat/Tigo'),
(1239, 413, 1043, '08', 143, 'lk', 'Sri Lanka', '94', 'H3G Hutchison'),
(1240, 413, 1043, '01', 31, 'lk', 'Sri Lanka', '94', 'Mobitel Ltd.'),
(1241, 413, 1043, '02', 47, 'lk', 'Sri Lanka', '94', 'MTN/Dialog'),
(1242, 308, 776, '01', 31, 'pm', 'St. Pierre & Miquelon', '508', 'Ameris'),
(1243, 360, 864, '110', 272, 'vc', 'St. Vincent & Gren.', '1784', 'C & W'),
(1244, 360, 864, '10', 271, 'vc', 'St. Vincent & Gren.', '1784', 'Cingular'),
(1245, 360, 864, '100', 256, 'vc', 'St. Vincent & Gren.', '1784', 'Cingular'),
(1246, 360, 864, '050', 80, 'vc', 'St. Vincent & Gren.', '1784', 'Digicel'),
(1247, 360, 864, '70', 1807, 'vc', 'St. Vincent & Gren.', '1784', 'Digicel'),
(1248, 634, 1588, '00', 15, 'sd', 'Sudan', '249', 'Canar Telecom'),
(1249, 634, 1588, '02', 47, 'sd', 'Sudan', '249', 'MTN'),
(1250, 634, 1588, '22', 559, 'sd', 'Sudan', '249', 'MTN'),
(1251, 634, 1588, '15', 351, 'sd', 'Sudan', '249', 'Sudani One'),
(1252, 634, 1588, '07', 127, 'sd', 'Sudan', '249', 'Sudani One'),
(1253, 634, 1588, '08', 143, 'sd', 'Sudan', '249', 'Vivacell'),
(1254, 634, 1588, '05', 95, 'sd', 'Sudan', '249', 'Vivacell'),
(1255, 634, 1588, '06', 111, 'sd', 'Sudan', '249', 'ZAIN/Mobitel'),
(1256, 634, 1588, '01', 31, 'sd', 'Sudan', '249', 'ZAIN/Mobitel'),
(1257, 746, 1862, '03', 63, 'sr', 'Suriname', '597', 'Digicel'),
(1258, 746, 1862, '01', 31, 'sr', 'Suriname', '597', 'Telesur'),
(1259, 746, 1862, '02', 47, 'sr', 'Suriname', '597', 'Telecommunicatiebedrijf Suriname (TELESUR)'),
(1260, 746, 1862, '04', 79, 'sr', 'Suriname', '597', 'UNIQA'),
(1261, 653, 1619, '10', 271, 'sz', 'Swaziland', '268', 'Swazi MTN'),
(1262, 653, 1619, '01', 31, 'sz', 'Swaziland', '268', 'SwaziTelecom'),
(1263, 240, 576, '35', 863, 'se', 'Sweden', '46', '42 Telecom AB'),
(1264, 240, 576, '16', 367, 'se', 'Sweden', '46', '42 Telecom AB'),
(1265, 240, 576, '26', 623, 'se', 'Sweden', '46', 'Beepsend'),
(1266, 240, 576, '30', 783, 'se', 'Sweden', '46', 'NextGen Mobile Ltd (CardBoardFish)'),
(1267, 240, 576, '28', 655, 'se', 'Sweden', '46', 'CoolTEL Aps'),
(1268, 240, 576, '25', 607, 'se', 'Sweden', '46', 'Digitel Mobile Srl'),
(1269, 240, 576, '22', 559, 'se', 'Sweden', '46', 'Eu Tel AB'),
(1270, 240, 576, '27', 639, 'se', 'Sweden', '46', 'Fogg Mobile AB'),
(1271, 240, 576, '18', 399, 'se', 'Sweden', '46', 'Generic Mobile Systems Sweden AB'),
(1272, 240, 576, '17', 383, 'se', 'Sweden', '46', 'Gotalandsnatet AB'),
(1273, 240, 576, '04', 79, 'se', 'Sweden', '46', 'H3G Access AB'),
(1274, 240, 576, '02', 47, 'se', 'Sweden', '46', 'H3G Access AB'),
(1275, 240, 576, '36', 879, 'se', 'Sweden', '46', 'ID Mobile'),
(1276, 240, 576, '23', 575, 'se', 'Sweden', '46', 'Infobip Ltd.'),
(1277, 240, 576, '11', 287, 'se', 'Sweden', '46', 'Lindholmen Science Park AB'),
(1278, 240, 576, '12', 303, 'se', 'Sweden', '46', 'Lycamobile Ltd'),
(1279, 240, 576, '29', 671, 'se', 'Sweden', '46', 'Mercury International Carrier Services'),
(1280, 240, 576, '19', 415, 'se', 'Sweden', '46', 'Mundio Mobile (Sweden) Ltd'),
(1281, 240, 576, '10', 271, 'se', 'Sweden', '46', 'Spring Mobil AB'),
(1282, 240, 576, '05', 95, 'se', 'Sweden', '46', 'Svenska UMTS-N'),
(1283, 240, 576, '14', 335, 'se', 'Sweden', '46', 'TDC Sverige AB'),
(1284, 240, 576, '07', 127, 'se', 'Sweden', '46', 'Tele2 Sverige AB'),
(1285, 240, 576, '08', 143, 'se', 'Sweden', '46', 'Telenor (Vodafone)'),
(1286, 240, 576, '06', 111, 'se', 'Sweden', '46', 'Telenor (Vodafone)'),
(1287, 240, 576, '24', 591, 'se', 'Sweden', '46', 'Telenor (Vodafone)'),
(1288, 240, 576, '01', 31, 'se', 'Sweden', '46', 'Telia Mobile'),
(1289, 240, 576, '13', 319, 'se', 'Sweden', '46', 'Ventelo Sverige AB'),
(1290, 240, 576, '20', 527, 'se', 'Sweden', '46', 'Wireless Maingate AB'),
(1291, 240, 576, '15', 351, 'se', 'Sweden', '46', 'Wireless Maingate Nordic AB'),
(1292, 228, 552, '51', 1311, 'ch', 'Switzerland', '41', 'BebbiCell AG'),
(1293, 228, 552, '05', 95, 'ch', 'Switzerland', '41', 'Comfone AG'),
(1294, 228, 552, '09', 159, 'ch', 'Switzerland', '41', 'Comfone AG'),
(1295, 228, 552, '07', 127, 'ch', 'Switzerland', '41', 'TDC Sunrise'),
(1296, 228, 552, '54', 1359, 'ch', 'Switzerland', '41', 'Lycamobile AG'),
(1297, 228, 552, '52', 1327, 'ch', 'Switzerland', '41', 'Mundio Mobile AG'),
(1298, 228, 552, '03', 63, 'ch', 'Switzerland', '41', 'Salt/Orange'),
(1299, 228, 552, '01', 31, 'ch', 'Switzerland', '41', 'Swisscom'),
(1300, 228, 552, '02', 47, 'ch', 'Switzerland', '41', 'TDC Sunrise'),
(1301, 228, 552, '12', 303, 'ch', 'Switzerland', '41', 'TDC Sunrise'),
(1302, 228, 552, '08', 143, 'ch', 'Switzerland', '41', 'TDC Sunrise'),
(1303, 228, 552, '53', 1343, 'ch', 'Switzerland', '41', 'upc cablecom GmbH'),
(1304, 417, 1047, '02', 47, 'sy', 'Syrian Arab Republic', '963', 'MTN/Spacetel'),
(1305, 417, 1047, '09', 159, 'sy', 'Syrian Arab Republic', '963', 'Syriatel Holdings'),
(1306, 417, 1047, '01', 31, 'sy', 'Syrian Arab Republic', '963', 'Syriatel Holdings'),
(1307, 466, 1126, '68', 1679, 'tw', 'Taiwan', '886', 'ACeS Taiwan - ACeS Taiwan Telecommunications Co Ltd'),
(1308, 466, 1126, '05', 95, 'tw', 'Taiwan', '886', 'Asia Pacific Telecom Co. Ltd (APT)'),
(1309, 466, 1126, '11', 287, 'tw', 'Taiwan', '886', 'Chunghwa Telecom LDM'),
(1310, 466, 1126, '92', 2351, 'tw', 'Taiwan', '886', 'Chunghwa Telecom LDM'),
(1311, 466, 1126, '01', 31, 'tw', 'Taiwan', '886', 'Far EasTone'),
(1312, 466, 1126, '02', 47, 'tw', 'Taiwan', '886', 'Far EasTone'),
(1313, 466, 1126, '07', 127, 'tw', 'Taiwan', '886', 'Far EasTone'),
(1314, 466, 1126, '06', 111, 'tw', 'Taiwan', '886', 'Far EasTone'),
(1315, 466, 1126, '03', 63, 'tw', 'Taiwan', '886', 'Far EasTone'),
(1316, 466, 1126, '10', 271, 'tw', 'Taiwan', '886', 'Global Mobile Corp.'),
(1317, 466, 1126, '56', 1391, 'tw', 'Taiwan', '886', 'International Telecom Co. Ltd (FITEL)'),
(1318, 466, 1126, '88', 2191, 'tw', 'Taiwan', '886', 'KG Telecom'),
(1319, 466, 1126, '99', 2463, 'tw', 'Taiwan', '886', 'TransAsia'),
(1320, 466, 1126, '97', 2431, 'tw', 'Taiwan', '886', 'Taiwan Cellular'),
(1321, 466, 1126, '93', 2367, 'tw', 'Taiwan', '886', 'Mobitai'),
(1322, 466, 1126, '89', 2207, 'tw', 'Taiwan', '886', 'T-Star/VIBO'),
(1323, 466, 1126, '09', 159, 'tw', 'Taiwan', '886', 'VMAX Telecom Co. Ltd'),
(1324, 436, 1078, '04', 79, 'tk', 'Tajikistan', '992', 'Babilon-M'),
(1325, 436, 1078, '05', 95, 'tk', 'Tajikistan', '992', 'Bee Line'),
(1326, 436, 1078, '02', 47, 'tk', 'Tajikistan', '992', 'CJSC Indigo Tajikistan'),
(1327, 436, 1078, '12', 303, 'tk', 'Tajikistan', '992', 'Tcell/JC Somoncom'),
(1328, 436, 1078, '03', 63, 'tk', 'Tajikistan', '992', 'MLT/TT mobile'),
(1329, 436, 1078, '01', 31, 'tk', 'Tajikistan', '992', 'Tcell/JC Somoncom'),
(1330, 640, 1600, '08', 143, 'tz', 'Tanzania', '255', 'Benson Informatics Ltd'),
(1331, 640, 1600, '06', 111, 'tz', 'Tanzania', '255', 'Dovetel (T) Ltd'),
(1332, 640, 1600, '09', 159, 'tz', 'Tanzania', '255', 'Halotel/Viettel Ltd'),
(1333, 640, 1600, '11', 287, 'tz', 'Tanzania', '255', 'Smile Communications Tanzania Ltd'),
(1334, 640, 1600, '07', 127, 'tz', 'Tanzania', '255', 'Tanzania Telecommunications Company Ltd (TTCL)'),
(1335, 640, 1600, '02', 47, 'tz', 'Tanzania', '255', 'TIGO/MIC'),
(1336, 640, 1600, '01', 31, 'tz', 'Tanzania', '255', 'Tri Telecomm. Ltd.'),
(1337, 640, 1600, '04', 79, 'tz', 'Tanzania', '255', 'Vodacom Ltd'),
(1338, 640, 1600, '05', 95, 'tz', 'Tanzania', '255', 'Airtel/ZAIN/Celtel'),
(1339, 640, 1600, '03', 63, 'tz', 'Tanzania', '255', 'Zantel/Zanzibar Telecom'),
(1340, 520, 1312, '20', 527, 'th', 'Thailand', '66', 'ACeS Thailand - ACeS Regional Services Co Ltd'),
(1341, 520, 1312, '15', 351, 'th', 'Thailand', '66', 'ACT Mobile'),
(1342, 520, 1312, '03', 63, 'th', 'Thailand', '66', 'Advanced Wireless Networks/AWN'),
(1343, 520, 1312, '01', 31, 'th', 'Thailand', '66', 'AIS/Advanced Info Service'),
(1344, 520, 1312, '23', 575, 'th', 'Thailand', '66', 'Digital Phone Co.'),
(1345, 520, 1312, '00', 15, 'th', 'Thailand', '66', 'Hutch/CAT CDMA'),
(1346, 520, 1312, '18', 399, 'th', 'Thailand', '66', 'Total Access (DTAC)'),
(1347, 520, 1312, '05', 95, 'th', 'Thailand', '66', 'Total Access (DTAC)'),
(1348, 520, 1312, '04', 79, 'th', 'Thailand', '66', 'True Move/Orange'),
(1349, 520, 1312, '99', 2463, 'th', 'Thailand', '66', 'True Move/Orange'),
(1350, 514, 1300, '01', 31, 'tp', 'Timor-Leste', '670', 'Telin/ Telkomcel'),
(1351, 514, 1300, '02', 47, 'tp', 'Timor-Leste', '670', 'Timor Telecom'),
(1352, 615, 1557, '02', 47, 'tg', 'Togo', '228', 'Telecel/MOOV'),
(1353, 615, 1557, '03', 63, 'tg', 'Togo', '228', 'Telecel/MOOV'),
(1354, 615, 1557, '01', 31, 'tg', 'Togo', '228', 'Togo Telecom/TogoCELL'),
(1355, 539, 1337, '43', 1087, 'to', 'Tonga', '676', 'Shoreline Communication'),
(1356, 539, 1337, '01', 31, 'to', 'Tonga', '676', 'Tonga Communications'),
(1357, 374, 884, '12', 303, 'tt', 'Trinidad and Tobago', '1868', 'Bmobile/TSTT'),
(1358, 374, 884, '120', 288, 'tt', 'Trinidad and Tobago', '1868', 'Bmobile/TSTT'),
(1359, 374, 884, '130', 304, 'tt', 'Trinidad and Tobago', '1868', 'Digicel'),
(1360, 374, 884, '140', 320, 'tt', 'Trinidad and Tobago', '1868', 'LaqTel Ltd.'),
(1361, 605, 1541, '01', 31, 'tn', 'Tunisia', '216', 'Orange'),
(1362, 605, 1541, '03', 63, 'tn', 'Tunisia', '216', 'Oreedo/Orascom'),
(1363, 605, 1541, '02', 47, 'tn', 'Tunisia', '216', 'TuniCell/Tunisia Telecom'),
(1364, 605, 1541, '06', 111, 'tn', 'Tunisia', '216', 'TuniCell/Tunisia Telecom'),
(1365, 286, 646, '04', 79, 'tr', 'Turkey', '90', 'AVEA/Aria'),
(1366, 286, 646, '03', 63, 'tr', 'Turkey', '90', 'AVEA/Aria'),
(1367, 286, 646, '01', 31, 'tr', 'Turkey', '90', 'Turkcell'),
(1368, 286, 646, '02', 47, 'tr', 'Turkey', '90', 'Vodafone-Telsim'),
(1369, 438, 1080, '01', 31, 'tm', 'Turkmenistan', '993', 'MTS/Barash Communication'),
(1370, 438, 1080, '02', 47, 'tm', 'Turkmenistan', '993', 'Altyn Asyr/TM-Cell'),
(1371, 376, 886, '350', 848, 'tc', 'Turks and Caicos Islands', '', 'Cable & Wireless (TCI) Ltd'),
(1372, 376, 886, '050', 80, 'tc', 'Turks and Caicos Islands', '', 'Digicel TCI Ltd'),
(1373, 376, 886, '352', 850, 'tc', 'Turks and Caicos Islands', '', 'IslandCom Communications Ltd.'),
(1374, 553, 1363, '01', 31, 'tv', 'Tuvalu', '', 'Tuvalu Telecommunication Corporation (TTC)'),
(1375, 641, 1601, '01', 31, 'ug', 'Uganda', '256', 'Airtel/Celtel'),
(1376, 641, 1601, '66', 1647, 'ug', 'Uganda', '256', 'i-Tel Ltd'),
(1377, 641, 1601, '30', 783, 'ug', 'Uganda', '256', 'K2 Telecom Ltd'),
(1378, 641, 1601, '10', 271, 'ug', 'Uganda', '256', 'MTN Ltd.'),
(1379, 641, 1601, '14', 335, 'ug', 'Uganda', '256', 'Orange'),
(1380, 641, 1601, '33', 831, 'ug', 'Uganda', '256', 'Smile Communications Uganda Ltd'),
(1381, 641, 1601, '18', 399, 'ug', 'Uganda', '256', 'Suretelecom Uganda Ltd'),
(1382, 641, 1601, '11', 287, 'ug', 'Uganda', '256', 'Uganda Telecom Ltd.'),
(1383, 641, 1601, '22', 559, 'ug', 'Uganda', '256', 'Airtel/Warid'),
(1384, 255, 597, '06', 111, 'ua', 'Ukraine', '380', 'Astelit/LIFE'),
(1385, 255, 597, '05', 95, 'ua', 'Ukraine', '380', 'Golden Telecom'),
(1386, 255, 597, '39', 927, 'ua', 'Ukraine', '380', 'Golden Telecom'),
(1387, 255, 597, '04', 79, 'ua', 'Ukraine', '380', 'Intertelecom Ltd (IT)'),
(1388, 255, 597, '67', 1663, 'ua', 'Ukraine', '380', 'KyivStar'),
(1389, 255, 597, '03', 63, 'ua', 'Ukraine', '380', 'KyivStar'),
(1390, 255, 597, '21', 543, 'ua', 'Ukraine', '380', 'Telesystems Of Ukraine CJSC (TSU)'),
(1391, 255, 597, '07', 127, 'ua', 'Ukraine', '380', 'TriMob LLC'),
(1392, 255, 597, '50', 1295, 'ua', 'Ukraine', '380', 'UMC/MTS'),
(1393, 255, 597, '02', 47, 'ua', 'Ukraine', '380', 'Beeline'),
(1394, 255, 597, '01', 31, 'ua', 'Ukraine', '380', 'UMC/MTS'),
(1395, 255, 597, '68', 1679, 'ua', 'Ukraine', '380', 'Beeline'),
(1396, 424, 1060, '03', 63, 'ae', 'United Arab Emirates', '971', 'DU'),
(1397, 424, 1060, '02', 47, 'ae', 'United Arab Emirates', '971', 'Etisalat'),
(1398, 431, 1073, '02', 47, 'ae', 'United Arab Emirates', '971', 'Etisalat'),
(1399, 430, 1072, '02', 47, 'ae', 'United Arab Emirates', '971', 'Etisalat'),
(1400, 234, 564, '03', 63, 'gb', 'United Kingdom', '44', 'Airtel/Vodafone'),
(1401, 234, 564, '77', 1919, 'gb', 'United Kingdom', '44', 'BT Group'),
(1402, 234, 564, '76', 1903, 'gb', 'United Kingdom', '44', 'BT Group'),
(1403, 234, 564, '07', 127, 'gb', 'United Kingdom', '44', 'Cable and Wireless'),
(1404, 234, 564, '92', 2351, 'gb', 'United Kingdom', '44', 'Cable and Wireless'),
(1405, 234, 564, '36', 879, 'gb', 'United Kingdom', '44', 'Cable and Wireless Isle of Man'),
(1406, 234, 564, '18', 399, 'gb', 'United Kingdom', '44', 'Cloud9/wire9 Tel.'),
(1407, 235, 565, '02', 47, 'gb', 'United Kingdom', '44', 'Everyth. Ev.wh.'),
(1408, 234, 564, '17', 383, 'gb', 'United Kingdom', '44', 'FlexTel'),
(1409, 234, 564, '55', 1375, 'gb', 'United Kingdom', '44', 'Guernsey Telecoms'),
(1410, 234, 564, '14', 335, 'gb', 'United Kingdom', '44', 'HaySystems'),
(1411, 234, 564, '94', 2383, 'gb', 'United Kingdom', '44', 'H3G Hutchinson'),
(1412, 234, 564, '20', 527, 'gb', 'United Kingdom', '44', 'H3G Hutchinson'),
(1413, 234, 564, '75', 1887, 'gb', 'United Kingdom', '44', 'Inquam Telecom Ltd'),
(1414, 234, 564, '50', 1295, 'gb', 'United Kingdom', '44', 'Jersey Telecom'),
(1415, 234, 564, '35', 863, 'gb', 'United Kingdom', '44', 'JSC Ingenicum'),
(1416, 234, 564, '26', 623, 'gb', 'United Kingdom', '44', 'Lycamobile'),
(1417, 234, 564, '58', 1423, 'gb', 'United Kingdom', '44', 'Manx Telecom'),
(1418, 234, 564, '01', 31, 'gb', 'United Kingdom', '44', 'Mapesbury C. Ltd'),
(1419, 234, 564, '28', 655, 'gb', 'United Kingdom', '44', 'Marthon Telecom'),
(1420, 234, 564, '10', 271, 'gb', 'United Kingdom', '44', 'O2 Ltd.'),
(1421, 234, 564, '02', 47, 'gb', 'United Kingdom', '44', 'O2 Ltd.'),
(1422, 234, 564, '11', 287, 'gb', 'United Kingdom', '44', 'O2 Ltd.'),
(1423, 234, 564, '08', 143, 'gb', 'United Kingdom', '44', 'OnePhone'),
(1424, 234, 564, '16', 367, 'gb', 'United Kingdom', '44', 'Opal Telecom'),
(1425, 234, 564, '34', 847, 'gb', 'United Kingdom', '44', 'Everyth. Ev.wh./Orange'),
(1426, 234, 564, '33', 831, 'gb', 'United Kingdom', '44', 'Everyth. Ev.wh./Orange'),
(1427, 234, 564, '19', 415, 'gb', 'United Kingdom', '44', 'PMN/Teleware'),
(1428, 234, 564, '12', 303, 'gb', 'United Kingdom', '44', 'Railtrack Plc'),
(1429, 234, 564, '22', 559, 'gb', 'United Kingdom', '44', 'Routotelecom'),
(1430, 234, 564, '57', 1407, 'gb', 'United Kingdom', '44', 'Sky UK Limited'),
(1431, 234, 564, '24', 591, 'gb', 'United Kingdom', '44', 'Stour Marine'),
(1432, 234, 564, '37', 895, 'gb', 'United Kingdom', '44', 'Synectiv Ltd.'),
(1433, 234, 564, '31', 799, 'gb', 'United Kingdom', '44', 'Everyth. Ev.wh./T-Mobile'),
(1434, 234, 564, '30', 783, 'gb', 'United Kingdom', '44', 'Everyth. Ev.wh./T-Mobile'),
(1435, 234, 564, '32', 815, 'gb', 'United Kingdom', '44', 'Everyth. Ev.wh./T-Mobile'),
(1436, 234, 564, '27', 639, 'gb', 'United Kingdom', '44', 'Vodafone');
INSERT INTO `cell_carriers` (`carrier_id`, `mcc`, `mcc_int`, `mnc`, `mnc_int`, `iso`, `country`, `country_code`, `network`) VALUES
(1437, 234, 564, '09', 159, 'gb', 'United Kingdom', '44', 'Tismi'),
(1438, 234, 564, '25', 607, 'gb', 'United Kingdom', '44', 'Truphone'),
(1439, 234, 564, '51', 1311, 'gb', 'United Kingdom', '44', 'Jersey Telecom'),
(1440, 234, 564, '23', 575, 'gb', 'United Kingdom', '44', 'Vectofone Mobile Wifi'),
(1441, 234, 564, '15', 351, 'gb', 'United Kingdom', '44', 'Vodafone'),
(1442, 234, 564, '91', 2335, 'gb', 'United Kingdom', '44', 'Vodafone'),
(1443, 234, 564, '78', 1935, 'gb', 'United Kingdom', '44', 'Wave Telecom Ltd'),
(1444, 310, 784, '050', 80, 'us', 'United States', '1', ''),
(1445, 310, 784, '880', 2176, 'us', 'United States', '1', ''),
(1446, 310, 784, '850', 2128, 'us', 'United States', '1', 'Aeris Comm. Inc.'),
(1447, 310, 784, '640', 1600, 'us', 'United States', '1', ''),
(1448, 310, 784, '510', 1296, 'us', 'United States', '1', 'Airtel Wireless LLC'),
(1449, 310, 784, '190', 400, 'us', 'United States', '1', 'Unknown'),
(1450, 312, 786, '090', 144, 'us', 'United States', '1', 'Allied Wireless Communications Corporation'),
(1451, 311, 785, '130', 304, 'us', 'United States', '1', ''),
(1452, 310, 784, '710', 1808, 'us', 'United States', '1', 'Arctic Slope Telephone Association Cooperative Inc.'),
(1453, 310, 784, '150', 336, 'us', 'United States', '1', 'AT&T Wireless Inc.'),
(1454, 310, 784, '680', 1664, 'us', 'United States', '1', 'AT&T Wireless Inc.'),
(1455, 310, 784, '070', 112, 'us', 'United States', '1', 'AT&T Wireless Inc.'),
(1456, 310, 784, '560', 1376, 'us', 'United States', '1', 'AT&T Wireless Inc.'),
(1457, 310, 784, '410', 1040, 'us', 'United States', '1', 'AT&T Wireless Inc.'),
(1458, 310, 784, '380', 896, 'us', 'United States', '1', 'AT&T Wireless Inc.'),
(1459, 310, 784, '170', 368, 'us', 'United States', '1', 'AT&T Wireless Inc.'),
(1460, 310, 784, '980', 2432, 'us', 'United States', '1', 'AT&T Wireless Inc.'),
(1461, 311, 785, '810', 2064, 'us', 'United States', '1', 'Bluegrass Wireless LLC'),
(1462, 311, 785, '800', 2048, 'us', 'United States', '1', 'Bluegrass Wireless LLC'),
(1463, 311, 785, '440', 1088, 'us', 'United States', '1', 'Bluegrass Wireless LLC'),
(1464, 310, 784, '900', 2304, 'us', 'United States', '1', 'Cable & Communications Corp.'),
(1465, 311, 785, '590', 1424, 'us', 'United States', '1', 'California RSA No. 3 Limited Partnership'),
(1466, 311, 785, '500', 1280, 'us', 'United States', '1', 'Cambridge Telephone Company Inc.'),
(1467, 310, 784, '830', 2096, 'us', 'United States', '1', 'Caprock Cellular Ltd.'),
(1468, 311, 785, '487', 1159, 'us', 'United States', '1', 'Verizon Wireless'),
(1469, 310, 784, '590', 1424, 'us', 'United States', '1', 'Verizon Wireless'),
(1470, 311, 785, '282', 642, 'us', 'United States', '1', 'Verizon Wireless'),
(1471, 311, 785, '271', 625, 'us', 'United States', '1', 'Verizon Wireless'),
(1472, 311, 785, '287', 647, 'us', 'United States', '1', 'Verizon Wireless'),
(1473, 311, 785, '481', 1153, 'us', 'United States', '1', 'Verizon Wireless'),
(1474, 311, 785, '276', 630, 'us', 'United States', '1', 'Verizon Wireless'),
(1475, 311, 785, '486', 1158, 'us', 'United States', '1', 'Verizon Wireless'),
(1476, 310, 784, '013', 19, 'us', 'United States', '1', 'Verizon Wireless'),
(1477, 311, 785, '281', 641, 'us', 'United States', '1', 'Verizon Wireless'),
(1478, 311, 785, '270', 624, 'us', 'United States', '1', 'Verizon Wireless'),
(1479, 311, 785, '286', 646, 'us', 'United States', '1', 'Verizon Wireless'),
(1480, 311, 785, '480', 1152, 'us', 'United States', '1', 'Verizon Wireless'),
(1481, 311, 785, '275', 629, 'us', 'United States', '1', 'Verizon Wireless'),
(1482, 311, 785, '485', 1157, 'us', 'United States', '1', 'Verizon Wireless'),
(1483, 310, 784, '012', 18, 'us', 'United States', '1', 'Verizon Wireless'),
(1484, 311, 785, '280', 640, 'us', 'United States', '1', 'Verizon Wireless'),
(1485, 311, 785, '110', 272, 'us', 'United States', '1', 'Verizon Wireless'),
(1486, 311, 785, '285', 645, 'us', 'United States', '1', 'Verizon Wireless'),
(1487, 311, 785, '390', 912, 'us', 'United States', '1', 'Verizon Wireless'),
(1488, 311, 785, '274', 628, 'us', 'United States', '1', 'Verizon Wireless'),
(1489, 311, 785, '484', 1156, 'us', 'United States', '1', 'Verizon Wireless'),
(1490, 310, 784, '010', 16, 'us', 'United States', '1', 'Verizon Wireless'),
(1491, 311, 785, '279', 633, 'us', 'United States', '1', 'Verizon Wireless'),
(1492, 311, 785, '489', 1161, 'us', 'United States', '1', 'Verizon Wireless'),
(1493, 310, 784, '910', 2320, 'us', 'United States', '1', 'Verizon Wireless'),
(1494, 311, 785, '284', 644, 'us', 'United States', '1', 'Verizon Wireless'),
(1495, 311, 785, '289', 649, 'us', 'United States', '1', 'Verizon Wireless'),
(1496, 311, 785, '273', 627, 'us', 'United States', '1', 'Verizon Wireless'),
(1497, 311, 785, '483', 1155, 'us', 'United States', '1', 'Verizon Wireless'),
(1498, 310, 784, '004', 4, 'us', 'United States', '1', 'Verizon Wireless'),
(1499, 311, 785, '278', 632, 'us', 'United States', '1', 'Verizon Wireless'),
(1500, 311, 785, '488', 1160, 'us', 'United States', '1', 'Verizon Wireless'),
(1501, 310, 784, '890', 2192, 'us', 'United States', '1', 'Verizon Wireless'),
(1502, 311, 785, '283', 643, 'us', 'United States', '1', 'Verizon Wireless'),
(1503, 311, 785, '272', 626, 'us', 'United States', '1', 'Verizon Wireless'),
(1504, 311, 785, '288', 648, 'us', 'United States', '1', 'Verizon Wireless'),
(1505, 311, 785, '482', 1154, 'us', 'United States', '1', 'Verizon Wireless'),
(1506, 311, 785, '277', 631, 'us', 'United States', '1', 'Verizon Wireless'),
(1507, 312, 786, '280', 640, 'us', 'United States', '1', 'Cellular Network Partnership LLC'),
(1508, 312, 786, '270', 624, 'us', 'United States', '1', 'Cellular Network Partnership LLC'),
(1509, 310, 784, '360', 864, 'us', 'United States', '1', 'Cellular Network Partnership LLC'),
(1510, 311, 785, '190', 400, 'us', 'United States', '1', ''),
(1511, 310, 784, '030', 48, 'us', 'United States', '1', ''),
(1512, 311, 785, '120', 288, 'us', 'United States', '1', 'Choice Phone LLC'),
(1513, 310, 784, '480', 1152, 'us', 'United States', '1', 'Choice Phone LLC'),
(1514, 310, 784, '630', 1584, 'us', 'United States', '1', ''),
(1515, 310, 784, '420', 1056, 'us', 'United States', '1', 'Cincinnati Bell Wireless LLC'),
(1516, 310, 784, '180', 384, 'us', 'United States', '1', 'Cingular Wireless'),
(1517, 310, 784, '620', 1568, 'us', 'United States', '1', 'Coleman County Telco /Trans TX'),
(1518, 311, 785, '040', 64, 'us', 'United States', '1', ''),
(1519, 310, 784, '06', 111, 'us', 'United States', '1', 'Consolidated Telcom'),
(1520, 310, 784, '60', 1551, 'us', 'United States', '1', 'Consolidated Telcom'),
(1521, 310, 784, '26', 623, 'us', 'United States', '1', ''),
(1522, 312, 786, '380', 896, 'us', 'United States', '1', ''),
(1523, 310, 784, '930', 2352, 'us', 'United States', '1', ''),
(1524, 311, 785, '240', 576, 'us', 'United States', '1', ''),
(1525, 310, 784, '080', 128, 'us', 'United States', '1', ''),
(1526, 310, 784, '700', 1792, 'us', 'United States', '1', 'Cross Valliant Cellular Partnership'),
(1527, 311, 785, '140', 320, 'us', 'United States', '1', 'Cross Wireless Telephone Co.'),
(1528, 312, 786, '030', 48, 'us', 'United States', '1', 'Cross Wireless Telephone Co.'),
(1529, 311, 785, '520', 1312, 'us', 'United States', '1', ''),
(1530, 312, 786, '040', 64, 'us', 'United States', '1', 'Custer Telephone Cooperative Inc.'),
(1531, 310, 784, '440', 1088, 'us', 'United States', '1', 'Dobson Cellular Systems'),
(1532, 310, 784, '990', 2448, 'us', 'United States', '1', 'E.N.M.R. Telephone Coop.'),
(1533, 312, 786, '120', 288, 'us', 'United States', '1', 'East Kentucky Network LLC'),
(1534, 310, 784, '750', 1872, 'us', 'United States', '1', 'East Kentucky Network LLC'),
(1535, 312, 786, '130', 304, 'us', 'United States', '1', 'East Kentucky Network LLC'),
(1536, 310, 784, '090', 144, 'us', 'United States', '1', 'Edge Wireless LLC'),
(1537, 310, 784, '610', 1552, 'us', 'United States', '1', 'Elkhart TelCo. / Epic Touch Co.'),
(1538, 311, 785, '210', 528, 'us', 'United States', '1', ''),
(1539, 311, 785, '311', 785, 'us', 'United States', '1', 'Farmers'),
(1540, 311, 785, '460', 1120, 'us', 'United States', '1', 'Fisher Wireless Services Inc.'),
(1541, 311, 785, '370', 880, 'us', 'United States', '1', 'GCI Communication Corp.'),
(1542, 310, 784, '430', 1072, 'us', 'United States', '1', 'GCI Communication Corp.'),
(1543, 310, 784, '920', 2336, 'us', 'United States', '1', 'Get Mobile Inc.'),
(1544, 310, 784, '970', 2416, 'us', 'United States', '1', ''),
(1545, 311, 785, '340', 832, 'us', 'United States', '1', 'Illinois Valley Cellular RSA 2 Partnership'),
(1546, 311, 785, '030', 48, 'us', 'United States', '1', ''),
(1547, 312, 786, '170', 368, 'us', 'United States', '1', 'Iowa RSA No. 2 Limited Partnership'),
(1548, 311, 785, '410', 1040, 'us', 'United States', '1', 'Iowa RSA No. 2 Limited Partnership'),
(1549, 310, 784, '770', 1904, 'us', 'United States', '1', 'Iowa Wireless Services LLC'),
(1550, 310, 784, '650', 1616, 'us', 'United States', '1', 'Jasper'),
(1551, 310, 784, '870', 2160, 'us', 'United States', '1', 'Kaplan Telephone Company Inc.'),
(1552, 312, 786, '180', 384, 'us', 'United States', '1', 'Keystone Wireless LLC'),
(1553, 310, 784, '690', 1680, 'us', 'United States', '1', 'Keystone Wireless LLC'),
(1554, 311, 785, '310', 784, 'us', 'United States', '1', 'Lamar County Cellular'),
(1555, 310, 784, '016', 22, 'us', 'United States', '1', 'Leap Wireless International Inc.'),
(1556, 311, 785, '090', 144, 'us', 'United States', '1', ''),
(1557, 310, 784, '040', 64, 'us', 'United States', '1', 'Matanuska Tel. Assn. Inc.'),
(1558, 310, 784, '780', 1920, 'us', 'United States', '1', 'Message Express Co. / Airlink PCS'),
(1559, 311, 785, '660', 1632, 'us', 'United States', '1', ''),
(1560, 311, 785, '330', 816, 'us', 'United States', '1', 'Michigan Wireless LLC'),
(1561, 311, 785, '000', 0, 'us', 'United States', '1', ''),
(1562, 310, 784, '400', 1024, 'us', 'United States', '1', 'Minnesota South. Wirel. Co. / Hickory'),
(1563, 312, 786, '220', 544, 'us', 'United States', '1', 'Missouri RSA No 5 Partnership'),
(1564, 312, 786, '010', 16, 'us', 'United States', '1', 'Missouri RSA No 5 Partnership'),
(1565, 311, 785, '920', 2336, 'us', 'United States', '1', 'Missouri RSA No 5 Partnership'),
(1566, 311, 785, '020', 32, 'us', 'United States', '1', 'Missouri RSA No 5 Partnership'),
(1567, 311, 785, '010', 16, 'us', 'United States', '1', 'Missouri RSA No 5 Partnership'),
(1568, 310, 784, '350', 848, 'us', 'United States', '1', 'Mohave Cellular LP'),
(1569, 310, 784, '570', 1392, 'us', 'United States', '1', 'MTPCS LLC'),
(1570, 310, 784, '290', 656, 'us', 'United States', '1', 'NEP Cellcorp Inc.'),
(1571, 310, 784, '34', 847, 'us', 'United States', '1', 'Nevada Wireless LLC'),
(1572, 311, 785, '380', 896, 'us', 'United States', '1', ''),
(1573, 310, 784, '600', 1536, 'us', 'United States', '1', 'New-Cell Inc.'),
(1574, 311, 785, '100', 256, 'us', 'United States', '1', ''),
(1575, 311, 785, '300', 768, 'us', 'United States', '1', 'Nexus Communications Inc.'),
(1576, 310, 784, '130', 304, 'us', 'United States', '1', 'North Carolina RSA 3 Cellular Tel. Co.'),
(1577, 312, 786, '230', 560, 'us', 'United States', '1', 'North Dakota Network Company'),
(1578, 311, 785, '610', 1552, 'us', 'United States', '1', 'North Dakota Network Company'),
(1579, 310, 784, '450', 1104, 'us', 'United States', '1', 'Northeast Colorado Cellular Inc.'),
(1580, 311, 785, '710', 1808, 'us', 'United States', '1', 'Northeast Wireless Networks LLC'),
(1581, 310, 784, '670', 1648, 'us', 'United States', '1', 'Northstar'),
(1582, 310, 784, '011', 17, 'us', 'United States', '1', 'Northstar'),
(1583, 311, 785, '420', 1056, 'us', 'United States', '1', 'Northwest Missouri Cellular Limited Partnership'),
(1584, 310, 784, '540', 1344, 'us', 'United States', '1', ''),
(1585, 310, 784, '760', 1888, 'us', 'United States', '1', 'Panhandle Telephone Cooperative Inc.'),
(1586, 310, 784, '580', 1408, 'us', 'United States', '1', 'PCS ONE'),
(1587, 311, 785, '170', 368, 'us', 'United States', '1', 'PetroCom'),
(1588, 311, 785, '670', 1648, 'us', 'United States', '1', 'Pine Belt Cellular Inc.'),
(1589, 311, 785, '080', 128, 'us', 'United States', '1', ''),
(1590, 310, 784, '790', 1936, 'us', 'United States', '1', ''),
(1591, 310, 784, '100', 256, 'us', 'United States', '1', 'Plateau Telecommunications Inc.'),
(1592, 310, 784, '940', 2368, 'us', 'United States', '1', 'Poka Lambro Telco Ltd.'),
(1593, 311, 785, '730', 1840, 'us', 'United States', '1', ''),
(1594, 311, 785, '540', 1344, 'us', 'United States', '1', ''),
(1595, 310, 784, '500', 1280, 'us', 'United States', '1', 'Public Service Cellular Inc.'),
(1596, 311, 785, '430', 1072, 'us', 'United States', '1', 'RSA 1 Limited Partnership'),
(1597, 312, 786, '160', 352, 'us', 'United States', '1', 'RSA 1 Limited Partnership'),
(1598, 311, 785, '350', 848, 'us', 'United States', '1', 'Sagebrush Cellular Inc.'),
(1599, 311, 785, '910', 2320, 'us', 'United States', '1', ''),
(1600, 310, 784, '46', 1135, 'us', 'United States', '1', 'SIMMETRY'),
(1601, 311, 785, '260', 608, 'us', 'United States', '1', 'SLO Cellular Inc / Cellular One of San Luis'),
(1602, 310, 784, '320', 800, 'us', 'United States', '1', 'Smith Bagley Inc.'),
(1603, 310, 784, '15', 351, 'us', 'United States', '1', 'Unknown'),
(1604, 316, 790, '011', 17, 'us', 'United States', '1', 'Southern Communications Services Inc.'),
(1605, 312, 786, '530', 1328, 'us', 'United States', '1', 'Sprint Spectrum'),
(1606, 311, 785, '870', 2160, 'us', 'United States', '1', 'Sprint Spectrum'),
(1607, 311, 785, '490', 1168, 'us', 'United States', '1', 'Sprint Spectrum'),
(1608, 310, 784, '120', 288, 'us', 'United States', '1', 'Sprint Spectrum'),
(1609, 316, 790, '010', 16, 'us', 'United States', '1', 'Sprint Spectrum'),
(1610, 312, 786, '190', 400, 'us', 'United States', '1', 'Sprint Spectrum'),
(1611, 311, 785, '880', 2176, 'us', 'United States', '1', 'Sprint Spectrum'),
(1612, 310, 784, '210', 528, 'us', 'United States', '1', 'T-Mobile'),
(1613, 310, 784, '260', 608, 'us', 'United States', '1', 'T-Mobile'),
(1614, 310, 784, '200', 512, 'us', 'United States', '1', 'T-Mobile'),
(1615, 310, 784, '250', 592, 'us', 'United States', '1', 'T-Mobile'),
(1616, 310, 784, '160', 352, 'us', 'United States', '1', 'T-Mobile'),
(1617, 310, 784, '240', 576, 'us', 'United States', '1', 'T-Mobile'),
(1618, 310, 784, '660', 1632, 'us', 'United States', '1', 'T-Mobile'),
(1619, 310, 784, '230', 560, 'us', 'United States', '1', 'T-Mobile'),
(1620, 310, 784, '31', 799, 'us', 'United States', '1', 'T-Mobile'),
(1621, 310, 784, '220', 544, 'us', 'United States', '1', 'T-Mobile'),
(1622, 310, 784, '270', 624, 'us', 'United States', '1', 'T-Mobile'),
(1623, 310, 784, '800', 2048, 'us', 'United States', '1', 'T-Mobile'),
(1624, 310, 784, '300', 768, 'us', 'United States', '1', 'T-Mobile'),
(1625, 310, 784, '280', 640, 'us', 'United States', '1', 'T-Mobile'),
(1626, 310, 784, '330', 816, 'us', 'United States', '1', 'T-Mobile'),
(1627, 310, 784, '310', 784, 'us', 'United States', '1', 'T-Mobile'),
(1628, 311, 785, '740', 1856, 'us', 'United States', '1', ''),
(1629, 310, 784, '740', 1856, 'us', 'United States', '1', 'Telemetrix Inc.'),
(1630, 310, 784, '14', 335, 'us', 'United States', '1', 'Testing'),
(1631, 310, 784, '950', 2384, 'us', 'United States', '1', 'Unknown'),
(1632, 310, 784, '860', 2144, 'us', 'United States', '1', 'Texas RSA 15B2 Limited Partnership'),
(1633, 311, 785, '830', 2096, 'us', 'United States', '1', 'Thumb Cellular Limited Partnership'),
(1634, 311, 785, '050', 80, 'us', 'United States', '1', 'Thumb Cellular Limited Partnership'),
(1635, 310, 784, '460', 1120, 'us', 'United States', '1', 'TMP Corporation'),
(1636, 310, 784, '490', 1168, 'us', 'United States', '1', 'Triton PCS'),
(1637, 310, 784, '960', 2400, 'us', 'United States', '1', 'Uintah Basin Electronics Telecommunications Inc.'),
(1638, 312, 786, '290', 656, 'us', 'United States', '1', 'Uintah Basin Electronics Telecommunications Inc.'),
(1639, 311, 785, '860', 2144, 'us', 'United States', '1', 'Uintah Basin Electronics Telecommunications Inc.'),
(1640, 310, 784, '020', 32, 'us', 'United States', '1', 'Union Telephone Co.'),
(1641, 311, 785, '220', 544, 'us', 'United States', '1', 'United States Cellular Corp.'),
(1642, 310, 784, '730', 1840, 'us', 'United States', '1', 'United States Cellular Corp.'),
(1643, 311, 785, '650', 1616, 'us', 'United States', '1', 'United Wireless Communications Inc.'),
(1644, 310, 784, '38', 911, 'us', 'United States', '1', 'USA 3650 AT&T'),
(1645, 310, 784, '520', 1312, 'us', 'United States', '1', 'VeriSign'),
(1646, 310, 784, '003', 3, 'us', 'United States', '1', 'Unknown'),
(1647, 310, 784, '23', 575, 'us', 'United States', '1', 'Unknown'),
(1648, 310, 784, '24', 591, 'us', 'United States', '1', 'Unknown'),
(1649, 310, 784, '25', 607, 'us', 'United States', '1', 'Unknown'),
(1650, 310, 784, '530', 1328, 'us', 'United States', '1', 'West Virginia Wireless'),
(1651, 310, 784, '26', 623, 'us', 'United States', '1', 'Unknown'),
(1652, 310, 784, '340', 832, 'us', 'United States', '1', 'Westlink Communications LLC'),
(1653, 311, 785, '150', 336, 'us', 'United States', '1', ''),
(1654, 311, 785, '070', 112, 'us', 'United States', '1', 'Wisconsin RSA #7 Limited Partnership'),
(1655, 310, 784, '390', 912, 'us', 'United States', '1', 'Yorkville Telephone Cooperative'),
(1656, 748, 1864, '03', 63, 'uy', 'Uruguay', '598', 'Ancel/Antel'),
(1657, 748, 1864, '01', 31, 'uy', 'Uruguay', '598', 'Ancel/Antel'),
(1658, 748, 1864, '10', 271, 'uy', 'Uruguay', '598', 'Claro/AM Wireless'),
(1659, 748, 1864, '07', 127, 'uy', 'Uruguay', '598', 'MOVISTAR'),
(1660, 434, 1076, '04', 79, 'uz', 'Uzbekistan', '998', 'Bee Line/Unitel'),
(1661, 434, 1076, '01', 31, 'uz', 'Uzbekistan', '998', 'Buztel'),
(1662, 434, 1076, '07', 127, 'uz', 'Uzbekistan', '998', 'MTS/Uzdunrobita'),
(1663, 434, 1076, '05', 95, 'uz', 'Uzbekistan', '998', 'Ucell/Coscom'),
(1664, 434, 1076, '02', 47, 'uz', 'Uzbekistan', '998', 'Uzmacom'),
(1665, 541, 1345, '05', 95, 'vu', 'Vanuatu', '678', 'DigiCel'),
(1666, 541, 1345, '01', 31, 'vu', 'Vanuatu', '678', 'SMILE'),
(1667, 734, 1844, '03', 63, 've', 'Venezuela', '58', 'DigiTel C.A.'),
(1668, 734, 1844, '02', 47, 've', 'Venezuela', '58', 'DigiTel C.A.'),
(1669, 734, 1844, '01', 31, 've', 'Venezuela', '58', 'DigiTel C.A.'),
(1670, 734, 1844, '06', 111, 've', 'Venezuela', '58', 'Movilnet C.A.'),
(1671, 734, 1844, '04', 79, 've', 'Venezuela', '58', 'Movistar/TelCel'),
(1672, 452, 1106, '07', 127, 'vn', 'Viet Nam', '84', 'Beeline'),
(1673, 452, 1106, '01', 31, 'vn', 'Viet Nam', '84', 'Mobifone'),
(1674, 452, 1106, '03', 63, 'vn', 'Viet Nam', '84', 'S-Fone/Telecom'),
(1675, 452, 1106, '05', 95, 'vn', 'Viet Nam', '84', 'VietnaMobile'),
(1676, 452, 1106, '08', 143, 'vn', 'Viet Nam', '84', 'Viettel Mobile'),
(1677, 452, 1106, '04', 79, 'vn', 'Viet Nam', '84', 'Viettel Mobile'),
(1678, 452, 1106, '06', 111, 'vn', 'Viet Nam', '84', 'Viettel Mobile'),
(1679, 452, 1106, '02', 47, 'vn', 'Viet Nam', '84', 'Vinaphone'),
(1680, 376, 886, '50', 1295, 'vi', 'Virgin Islands U.S.', '1340', 'Digicel'),
(1681, 421, 1057, '04', 79, 'ye', 'Yemen', '967', 'HITS/Y Unitel'),
(1682, 421, 1057, '02', 47, 'ye', 'Yemen', '967', 'MTN/Spacetel'),
(1683, 421, 1057, '01', 31, 'ye', 'Yemen', '967', 'Sabaphone'),
(1684, 421, 1057, '03', 63, 'ye', 'Yemen', '967', 'Yemen Mob. CDMA'),
(1685, 645, 1605, '03', 63, 'zm', 'Zambia', '260', 'Zamtel/Cell Z/MTS'),
(1686, 645, 1605, '02', 47, 'zm', 'Zambia', '260', 'MTN/Telecel'),
(1687, 645, 1605, '01', 31, 'zm', 'Zambia', '260', 'Airtel/Zain/Celtel'),
(1688, 648, 1608, '04', 79, 'zw', 'Zimbabwe', '263', 'Econet'),
(1689, 648, 1608, '01', 31, 'zw', 'Zimbabwe', '263', 'Net One'),
(1690, 648, 1608, '03', 63, 'zw', 'Zimbabwe', '263', 'Telecel');

-- --------------------------------------------------------

--
-- Table structure for table `cell_hist`
--

CREATE TABLE `cell_hist` (
  `cell_hist_id` bigint(20) NOT NULL,
  `cell_id` bigint(20) NOT NULL,
  `file_id` bigint(20) DEFAULT NULL,
  `rssi` int(11) NOT NULL,
  `lat` decimal(9,4) NOT NULL,
  `lon` decimal(9,4) NOT NULL,
  `alt` decimal(7,2) NOT NULL,
  `accuracy` decimal(10,2) NOT NULL,
  `hist_date` datetime(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cell_id`
--

CREATE TABLE `cell_id` (
  `cell_id` bigint(20) NOT NULL,
  `file_id` bigint(20) DEFAULT NULL,
  `mac` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ssid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `authmode` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chan` int(10) NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `highgps_id` bigint(20) DEFAULT NULL,
  `high_rssi` int(11) DEFAULT NULL,
  `points` bigint(20) DEFAULT NULL,
  `fa` datetime(3) DEFAULT NULL,
  `la` datetime(3) DEFAULT NULL,
  `cell_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
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
  `timestamp` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `graph_min` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `graph_max` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `graph_avg` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `graph_num` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `graph_total` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kmz_min` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kmz_max` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kmz_avg` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kmz_num` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kmz_total` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_min` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_max` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_avg` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_num` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_up_totals` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gpx_size` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gpx_num` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gpx_min` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gpx_max` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gpx_avg` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `daemon_size` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `daemon_num` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `daemon_min` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `daemon_max` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `daemon_avg` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_aps` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wep_aps` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `open_aps` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secure_aps` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nuap` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `num_priv_geo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `num_pub_geo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_orig` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `node_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_user` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otherusers` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT 0,
  `prev_ext` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_date` datetime NOT NULL DEFAULT current_timestamp(),
  `size` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aps` int(11) NOT NULL DEFAULT 0,
  `gps` int(11) NOT NULL DEFAULT 0,
  `ValidGPS` int(1) DEFAULT 0,
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
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_orig` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_user` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otherusers` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_date` datetime NOT NULL DEFAULT current_timestamp(),
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `converted` tinyint(1) NOT NULL,
  `thread_id` int(255) NOT NULL DEFAULT 0,
  `node_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `prev_ext` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_msg` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `files_importing`
--

CREATE TABLE `files_importing` (
  `id` bigint(20) NOT NULL,
  `tmp_id` int(255) DEFAULT 0,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_orig` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_user` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otherusers` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_date` datetime NOT NULL DEFAULT current_timestamp(),
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT 0,
  `prev_ext` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_orig` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_user` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otherusers` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` varchar(14) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_date` datetime NOT NULL DEFAULT current_timestamp(),
  `hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT 0,
  `prev_ext` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
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
  `nextrun` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `pid` int(11) DEFAULT NULL,
  `pidfile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logfile` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schedule`
--

INSERT INTO `schedule` (`id`, `nodename`, `daemon`, `enabled`, `interval`, `status`, `nextrun`) VALUES
(1, 'prod', 'Import', 1, 10, 'Waiting', '2019-02-09 13:34:01'),
(50, 'prod', 'Export', 1, 1440, 'Waiting', '2019-02-11 20:18:27');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(255) NOT NULL,
  `daemon_state` int(2) NOT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `apswithgps` int(255) NOT NULL DEFAULT 0,
  `last_export_file` bigint(20) NOT NULL DEFAULT 0,
  `node_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `daemon_state`, `version`, `apswithgps`, `last_export_file`, `node_name`) VALUES
(1, 1, '0.40', 0, 0, 'prod');

-- --------------------------------------------------------

--
-- Table structure for table `share_waypoints`
--

CREATE TABLE `share_waypoints` (
  `id` int(255) NOT NULL,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gcid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `cat` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `diff` double(3,2) NOT NULL,
  `terain` double(3,2) NOT NULL,
  `lat` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `long` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `c_date` datetime NOT NULL,
  `u_date` datetime NOT NULL,
  `pvt_id` int(255) NOT NULL,
  `shared_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
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
  `admin` tinyint(4) DEFAULT 0,
  `schedule` tinyint(1) NOT NULL DEFAULT 0,
  `imports` tinyint(1) NOT NULL DEFAULT 0,
  `kmz` tinyint(1) NOT NULL DEFAULT 0,
  `new_users` tinyint(1) NOT NULL DEFAULT 0,
  `h_email` tinyint(1) NOT NULL DEFAULT 0,
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
  `id` bigint(20) NOT NULL,
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
  `author` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shared_by` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gcid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `cat` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `diff` double(3,2) NOT NULL,
  `terain` double(3,2) NOT NULL,
  `lat` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `long` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `SECTYPE` int(2) DEFAULT NULL,
  `RADTYPE` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NETTYPE` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `BTX` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `OTX` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `FLAGS` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `HighGps_ID` bigint(20) DEFAULT NULL,
  `File_ID` bigint(20) DEFAULT NULL,
  `high_sig` int(11) DEFAULT NULL,
  `high_rssi` int(11) DEFAULT NULL,
  `high_gps_sig` int(11) DEFAULT NULL,
  `high_gps_rssi` int(11) DEFAULT NULL,
  `ap_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `points` bigint(20) DEFAULT NULL,
  `fa` datetime(3) DEFAULT NULL,
  `la` datetime(3) DEFAULT NULL,
  `ModDate` datetime(3) NOT NULL DEFAULT current_timestamp(3) ON UPDATE current_timestamp(3)
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
  `AccuracyMeters` decimal(10,2) DEFAULT NULL,
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
-- Indexes for table `cell_carriers`
--
ALTER TABLE `cell_carriers`
  ADD PRIMARY KEY (`carrier_id`);

--
-- Indexes for table `cell_hist`
--
ALTER TABLE `cell_hist`
  ADD PRIMARY KEY (`cell_hist_id`);

--
-- Indexes for table `cell_id`
--
ALTER TABLE `cell_id`
  ADD PRIMARY KEY (`cell_id`);

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
  ADD KEY `id` (`id`),
  ADD KEY `user` (`user`),
  ADD KEY `ValidGPS` (`ValidGPS`),
  ADD KEY `completed` (`completed`);

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
  ADD KEY `ap_hash` (`ap_hash`),
  ADD KEY `File_ID` (`File_ID`),
  ADD KEY `BSSID` (`BSSID`),
  ADD KEY `SECTYPE` (`SECTYPE`);

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
  ADD KEY `Hist_Date` (`Hist_Date`),
  ADD KEY `RSSI` (`RSSI`),
  ADD KEY `Sig` (`Sig`),
  ADD KEY `New` (`New`);

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
-- AUTO_INCREMENT for table `cell_carriers`
--
ALTER TABLE `cell_carriers`
  MODIFY `carrier_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1691;

--
-- AUTO_INCREMENT for table `cell_hist`
--
ALTER TABLE `cell_hist`
  MODIFY `cell_hist_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cell_id`
--
ALTER TABLE `cell_id`
  MODIFY `cell_id` bigint(20) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

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
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `wifi_ap_ibfk_2` FOREIGN KEY (`HighGps_ID`) REFERENCES `wifi_gps` (`GPS_ID`);

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
