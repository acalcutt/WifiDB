-- Live API version 2 (lib/live_session.inc.php, api/live.php): a session belongs to the account that started it, with
-- the title and notes its import gets once it's finished
ALTER TABLE [dbo].[live_titles] ADD [started_at] [datetime2](0) NULL, [completed_at] [datetime2](0) NULL
GO
CREATE NONCLUSTERED INDEX [live_users_session] ON [dbo].[live_users]([session_id])
GO
