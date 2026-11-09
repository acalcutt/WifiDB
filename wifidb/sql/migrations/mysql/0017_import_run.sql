-- Which run has a file on Files Importing, so one that dies part way can be shown as failed (lib/import_queue.inc.php)
ALTER TABLE `files_importing` ADD COLUMN `import_pid` int DEFAULT NULL, ADD COLUMN `import_node` varchar(255) DEFAULT NULL;
