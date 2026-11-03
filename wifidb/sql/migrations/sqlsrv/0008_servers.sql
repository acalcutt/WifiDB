-- admin/servers.php: each node's report (tools/daemon/server_status.php, each minute: version, load, memory, disks,
-- services) and its updater's (wifidb-update: the releases it sees, and how an update is going); and the update an
-- administrator asked for, which every node's updater does in turn.
CREATE TABLE [dbo].[server_status](
	[node_name] [nvarchar](255) NOT NULL PRIMARY KEY,
	[version] [nvarchar](64) NULL,
	[reported_at] [datetime2](0) NULL,
	[info] [nvarchar](max) NULL,
	[releases] [nvarchar](max) NULL,
	[releases_at] [datetime2](0) NULL,
	[update_request] [int] NULL,
	[update_version] [nvarchar](64) NULL,
	[update_state] [nvarchar](20) NULL,
	[update_message] [nvarchar](max) NULL,
	[update_at] [datetime2](0) NULL
)
GO
CREATE TABLE [dbo].[server_update_requests](
	[id] [int] IDENTITY(1,1) NOT NULL PRIMARY KEY,
	[version] [nvarchar](64) NOT NULL,
	[backup] [smallint] NOT NULL DEFAULT 0,
	[backup_taken] [smallint] NOT NULL DEFAULT 0,
	[requested_by] [nvarchar](255) NULL,
	[requested_at] [datetime2](0) NULL,
	[cancelled] [smallint] NOT NULL DEFAULT 0
)
GO
