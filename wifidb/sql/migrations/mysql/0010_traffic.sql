-- The site's own traffic counts (lib/traffic.inc.php, admin/traffic.php), from every node into one place

-- Page views and unique visitors (and what people did: kind 'event') by hour, day and month
CREATE TABLE `traffic_counts` (
  `kind` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `span` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` datetime NOT NULL,
  `pageviews` bigint(20) NOT NULL DEFAULT 0,
  `visitors` bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY (`kind`, `item`, `span`, `period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Who has been counted in a period already (a salted hash, not who they are), deleted once the period is over
CREATE TABLE `traffic_visitors` (
  `kind` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `span` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period` datetime NOT NULL,
  `visitor` char(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`kind`, `item`, `span`, `period`, `visitor`),
  KEY `span_period` (`span`, `period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
