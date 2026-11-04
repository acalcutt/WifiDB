-- Notifications can also be e-mailed, each kind on its own (lib/notify.inc.php: in_app for the Inbox, email off until
-- chosen), replacing user_info's schedule, imports, kmz and new_users. What a member had chosen there carries over;
-- an administrator with import mail also got the error mails, so they keep those.
ALTER TABLE [dbo].[notification_prefs] ADD [email] [tinyint] NOT NULL CONSTRAINT [DF_notification_prefs_email] DEFAULT 0
GO
UPDATE [dbo].[notification_prefs] SET [email] = 1 WHERE [type] = 'import_started' AND [username] IN (SELECT [username] FROM [dbo].[user_info] WHERE [schedule] = 1)
GO
INSERT INTO [dbo].[notification_prefs] ([username], [type], [in_app], [email]) SELECT u.[username], 'import_started', 1, 1 FROM [dbo].[user_info] u WHERE u.[schedule] = 1 AND NOT EXISTS (SELECT 1 FROM [dbo].[notification_prefs] p WHERE p.[username] = u.[username] AND p.[type] = 'import_started')
GO
UPDATE [dbo].[notification_prefs] SET [email] = 1 WHERE [type] = 'import_finished' AND [username] IN (SELECT [username] FROM [dbo].[user_info] WHERE [imports] = 1)
GO
INSERT INTO [dbo].[notification_prefs] ([username], [type], [in_app], [email]) SELECT u.[username], 'import_finished', 1, 1 FROM [dbo].[user_info] u WHERE u.[imports] = 1 AND NOT EXISTS (SELECT 1 FROM [dbo].[notification_prefs] p WHERE p.[username] = u.[username] AND p.[type] = 'import_finished')
GO
UPDATE [dbo].[notification_prefs] SET [email] = 1 WHERE [type] = 'admin_alert' AND [username] IN (SELECT [username] FROM [dbo].[user_info] WHERE [imports] = 1 AND [admin] = '1')
GO
INSERT INTO [dbo].[notification_prefs] ([username], [type], [in_app], [email]) SELECT u.[username], 'admin_alert', 1, 1 FROM [dbo].[user_info] u WHERE u.[imports] = 1 AND u.[admin] = '1' AND NOT EXISTS (SELECT 1 FROM [dbo].[notification_prefs] p WHERE p.[username] = u.[username] AND p.[type] = 'admin_alert')
GO
UPDATE [dbo].[notification_prefs] SET [email] = 1 WHERE [type] = 'export' AND [username] IN (SELECT [username] FROM [dbo].[user_info] WHERE [kmz] = 1)
GO
INSERT INTO [dbo].[notification_prefs] ([username], [type], [in_app], [email]) SELECT u.[username], 'export', 0, 1 FROM [dbo].[user_info] u WHERE u.[kmz] = 1 AND NOT EXISTS (SELECT 1 FROM [dbo].[notification_prefs] p WHERE p.[username] = u.[username] AND p.[type] = 'export')
GO
UPDATE [dbo].[notification_prefs] SET [email] = 1 WHERE [type] = 'new_user' AND [username] IN (SELECT [username] FROM [dbo].[user_info] WHERE [new_users] = 1)
GO
INSERT INTO [dbo].[notification_prefs] ([username], [type], [in_app], [email]) SELECT u.[username], 'new_user', 1, 1 FROM [dbo].[user_info] u WHERE u.[new_users] = 1 AND NOT EXISTS (SELECT 1 FROM [dbo].[notification_prefs] p WHERE p.[username] = u.[username] AND p.[type] = 'new_user')
GO
