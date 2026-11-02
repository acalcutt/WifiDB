-- Signing in with a linked Google, GitHub, Discord or Facebook account: lib/linked_logins.inc.php

-- One row per outside account, and the member it was linked to
CREATE TABLE `user_linked_logins` (
  `provider` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` datetime NOT NULL,
  `last_used` datetime DEFAULT NULL,
  PRIMARY KEY (`provider`, `subject`),
  KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A sign-in on its way through the provider, or one waiting to be linked: minutes long, and in the database so any
-- node can finish what another started
CREATE TABLE `linked_login_states` (
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires` datetime NOT NULL,
  PRIMARY KEY (`token`),
  KEY `expires` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
