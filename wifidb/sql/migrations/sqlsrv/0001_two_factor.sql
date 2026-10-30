-- Two-factor sign-in (authenticator apps and passkeys), recovery codes and admin mode: lib/two_factor.inc.php
CREATE TABLE [dbo].[user_two_factor](
	[id] [int] IDENTITY(1,1) NOT NULL PRIMARY KEY,
	[username] [nvarchar](255) NOT NULL,
	[type] [nvarchar](10) NOT NULL,
	[name] [nvarchar](100) NOT NULL,
	[secret] [nvarchar](1024) NOT NULL,
	[public_key] [nvarchar](max) NULL,
	[alg] [int] NULL,
	[sign_count] [bigint] NOT NULL DEFAULT 0,
	[last_step] [bigint] NOT NULL DEFAULT 0,
	[created] [datetime] NOT NULL,
	[last_used] [datetime] NULL
)
GO
CREATE NONCLUSTERED INDEX [username] ON [dbo].[user_two_factor]([username])
GO
CREATE TABLE [dbo].[user_recovery_codes](
	[id] [int] IDENTITY(1,1) NOT NULL PRIMARY KEY,
	[username] [nvarchar](255) NOT NULL,
	[code_hash] [nvarchar](255) NOT NULL,
	[created] [datetime] NOT NULL,
	[used] [datetime] NULL
)
GO
CREATE NONCLUSTERED INDEX [username] ON [dbo].[user_recovery_codes]([username])
GO
CREATE TABLE [dbo].[two_factor_attempts](
	[id] [int] IDENTITY(1,1) NOT NULL PRIMARY KEY,
	[username] [nvarchar](255) NOT NULL,
	[attempted] [datetime] NOT NULL
)
GO
CREATE NONCLUSTERED INDEX [username_attempted] ON [dbo].[two_factor_attempts]([username],[attempted])
GO
ALTER TABLE [dbo].[user_info] ADD
	[mfa_login] [tinyint] NOT NULL CONSTRAINT [DF_user_info_mfa_login] DEFAULT 1,
	[mfa_prefs] [tinyint] NOT NULL CONSTRAINT [DF_user_info_mfa_prefs] DEFAULT 0
GO
ALTER TABLE [dbo].[user_login_hashes] ADD
	[mfa_pending] [tinyint] NOT NULL CONSTRAINT [DF_user_login_hashes_mfa_pending] DEFAULT 0,
	[mfa_at] [datetime] NULL,
	[admin_until] [datetime] NULL,
	[challenge] [nvarchar](255) NULL,
	[challenge_expires] [datetime] NULL
GO
