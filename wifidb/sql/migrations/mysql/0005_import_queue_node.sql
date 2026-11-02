-- The node an upload came in on, so that without the shared files bucket only that node's importer takes it (the file
-- is on its disk). NULL (uploads queued before this): any node takes it.

ALTER TABLE `files_tmp` ADD COLUMN `node_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
ALTER TABLE `files_importing` ADD COLUMN `node_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL;
