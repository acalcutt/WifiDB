-- Signing in with a linked Google, GitHub, Discord or Facebook account: lib/linked_logins.inc.php
-- One row per outside account, and the member it was linked to
CREATE TABLE [dbo].[user_linked_logins](
	[provider] [nvarchar](20) NOT NULL,
	[subject] [nvarchar](255) NOT NULL,
	[username] [nvarchar](255) NOT NULL,
	[email] [nvarchar](255) NULL,
	[display_name] [nvarchar](255) NULL,
	[created] [datetime2](0) NOT NULL,
	[last_used] [datetime2](0) NULL,
	PRIMARY KEY ([provider], [subject])
)
GO
CREATE NONCLUSTERED INDEX [username] ON [dbo].[user_linked_logins]([username])
GO
-- A sign-in on its way through the provider, or one waiting to be linked: minutes long, and in the database so any
-- node can finish what another started
CREATE TABLE [dbo].[linked_login_states](
	[token] [nvarchar](64) NOT NULL PRIMARY KEY,
	[data] [nvarchar](max) NOT NULL,
	[expires] [datetime2](0) NOT NULL
)
GO
CREATE NONCLUSTERED INDEX [expires] ON [dbo].[linked_login_states]([expires])
GO
