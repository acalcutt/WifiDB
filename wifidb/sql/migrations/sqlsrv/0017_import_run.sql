-- Which run has a file on Files Importing, so one that dies part way can be shown as failed (lib/import_queue.inc.php)
ALTER TABLE [dbo].[files_importing] ADD [import_pid] [int] NULL, [import_node] [nvarchar](255) NULL
