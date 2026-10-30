-- In-app notifications and who wants which ones: lib/notify.inc.php
CREATE TABLE [dbo].[notifications](
	[id] [bigint] IDENTITY(1,1) NOT NULL PRIMARY KEY,
	[username] [nvarchar](255) NOT NULL,
	[type] [nvarchar](40) NOT NULL,
	[title] [nvarchar](255) NOT NULL,
	[body] [nvarchar](max) NULL,
	[link] [nvarchar](500) NULL,
	[created] [datetime2](0) NOT NULL,
	[read_at] [datetime2](0) NULL
)
GO
CREATE NONCLUSTERED INDEX [username_read] ON [dbo].[notifications]([username], [read_at])
GO
CREATE NONCLUSTERED INDEX [created] ON [dbo].[notifications]([created])
GO
CREATE TABLE [dbo].[notification_prefs](
	[username] [nvarchar](255) NOT NULL,
	[type] [nvarchar](40) NOT NULL,
	[in_app] [tinyint] NOT NULL,
	CONSTRAINT [PK_notification_prefs] PRIMARY KEY ([username], [type])
)
GO
