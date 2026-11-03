-- The site's own traffic counts (lib/traffic.inc.php, admin/traffic.php), from every node into one place
-- Page views and unique visitors (and what people did: kind 'event') by hour, day and month
CREATE TABLE [dbo].[traffic_counts](
	[kind] [nvarchar](16) NOT NULL,
	[item] [nvarchar](128) NOT NULL,
	[span] [nvarchar](8) NOT NULL,
	[period] [datetime2](0) NOT NULL,
	[pageviews] [bigint] NOT NULL DEFAULT 0,
	[visitors] [bigint] NOT NULL DEFAULT 0,
	PRIMARY KEY ([kind], [item], [span], [period])
)
GO
-- Who has been counted in a period already (a salted hash, not who they are), deleted once the period is over
CREATE TABLE [dbo].[traffic_visitors](
	[kind] [nvarchar](16) NOT NULL,
	[item] [nvarchar](128) NOT NULL,
	[span] [nvarchar](8) NOT NULL,
	[period] [datetime2](0) NOT NULL,
	[visitor] [nchar](20) NOT NULL,
	PRIMARY KEY ([kind], [item], [span], [period], [visitor])
)
GO
CREATE NONCLUSTERED INDEX [span_period] ON [dbo].[traffic_visitors]([span], [period])
GO
