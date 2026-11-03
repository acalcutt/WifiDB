-- The node an upload came in on, so that without the shared files bucket only that node's importer takes it (the file
-- is on its disk). NULL (uploads queued before this): any node takes it.
ALTER TABLE [dbo].[files_tmp] ADD [node_name] [nvarchar](255) NULL
GO
ALTER TABLE [dbo].[files_importing] ADD [node_name] [nvarchar](255) NULL
GO
