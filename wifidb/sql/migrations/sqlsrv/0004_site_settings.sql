-- Site settings an administrator changes on admin/settings.php (lib/site_settings.inc.php). A setting with no row here
-- comes from config.inc.php.
CREATE TABLE [dbo].[site_settings](
	[name] [nvarchar](64) NOT NULL PRIMARY KEY,
	[value] [nvarchar](max) NULL,
	[updated_by] [nvarchar](255) NULL,
	[updated_at] [datetime2](0) NULL
)
GO
