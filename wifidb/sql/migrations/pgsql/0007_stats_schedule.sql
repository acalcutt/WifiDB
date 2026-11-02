-- The stats daemon (statsd: the figures on the Stats page) only runs for a node with a 'Stats' row in schedule, and the
-- schema had none. If no node has one, each node gets one, hourly and due at once, switched on for the first node only
-- (the stats are worked out once for all). A database that has one anywhere is left as it is.

INSERT INTO schedule (nodename, daemon, enabled, "interval", status, nextrun)
SELECT s.node_name, 'Stats', CASE WHEN s.id = (SELECT MIN(f.id) FROM settings f) THEN 1 ELSE 0 END, 60, 'Waiting', '2000-01-01 00:00:00'
FROM settings s
WHERE NOT EXISTS (SELECT 1 FROM schedule x WHERE x.daemon = 'Stats');
