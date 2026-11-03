-- The admin daemon (admind: deleting and resetting imports, and the other admin jobs) only runs for a node with an
-- 'Admin' row in schedule, and the schema had none, so on a new install admin jobs were never done. Each node without
-- one gets one, due at once and checked each minute; every node can have it, since each job goes to one node.
INSERT INTO [dbo].[schedule] ([nodename], [daemon], [enabled], [interval], [status], [nextrun])
SELECT s.[node_name], 'Admin', 1, 1, 'Waiting', '20000101 00:00:00' FROM [dbo].[settings] s
WHERE NOT EXISTS (SELECT 1 FROM [dbo].[schedule] x WHERE x.[nodename] = s.[node_name] AND x.[daemon] = 'Admin')
GO
