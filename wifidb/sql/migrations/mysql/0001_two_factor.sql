-- Two-factor sign-in (authenticator apps and passkeys), recovery codes and admin mode: lib/two_factor.inc.php

CREATE TABLE `user_two_factor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_key` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alg` int(11) DEFAULT NULL,
  `sign_count` bigint(20) NOT NULL DEFAULT 0,
  `last_step` bigint(20) NOT NULL DEFAULT 0,
  `created` datetime NOT NULL,
  `last_used` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_recovery_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `used` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `two_factor_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempted` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `username_attempted` (`username`,`attempted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `user_info`
  ADD COLUMN `mfa_login` tinyint(1) NOT NULL DEFAULT 1,
  ADD COLUMN `mfa_prefs` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `user_login_hashes`
  ADD COLUMN `mfa_pending` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN `mfa_at` datetime DEFAULT NULL,
  ADD COLUMN `admin_until` datetime DEFAULT NULL,
  ADD COLUMN `challenge` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN `challenge_expires` datetime DEFAULT NULL;
