-- Where each access point and cell tower / Bluetooth device most likely is, from all its readings (lib/position.inc.php):
-- est_lat and est_lon (DDMM.MMMM, as wifi_gps keeps them), est_alt, est_accuracy (metres) and est_points (readings
-- used; 0: none to place). Filled here from the strongest reading, as the maps showed until now (est_points NULL: not
-- worked out yet); the positions cron job (tools/utilites/update_positions.php) then works each one out, and imports
-- keep them up to date.
ALTER TABLE `wifi_ap` ADD COLUMN `est_lat` decimal(9,4) DEFAULT NULL, ADD COLUMN `est_lon` decimal(9,4) DEFAULT NULL,
  ADD COLUMN `est_alt` decimal(7,2) DEFAULT NULL, ADD COLUMN `est_accuracy` decimal(10,2) DEFAULT NULL, ADD COLUMN `est_points` int(11) DEFAULT NULL;
ALTER TABLE `cell_id` ADD COLUMN `est_lat` decimal(9,4) DEFAULT NULL, ADD COLUMN `est_lon` decimal(9,4) DEFAULT NULL,
  ADD COLUMN `est_alt` decimal(7,2) DEFAULT NULL, ADD COLUMN `est_accuracy` decimal(10,2) DEFAULT NULL, ADD COLUMN `est_points` int(11) DEFAULT NULL;
UPDATE `wifi_ap` a INNER JOIN `wifi_gps` g ON g.`GPS_ID` = a.`HighGps_ID`
  SET a.`est_lat` = g.`Lat`, a.`est_lon` = g.`Lon`, a.`est_alt` = g.`Alt`, a.`est_accuracy` = g.`AccuracyMeters`, a.`ModDate` = a.`ModDate`
  WHERE NOT (g.`Lat` = 0 AND g.`Lon` = 0);
UPDATE `cell_id` c INNER JOIN `wifi_gps` g ON g.`GPS_ID` = c.`highgps_id`
  SET c.`est_lat` = g.`Lat`, c.`est_lon` = g.`Lon`, c.`est_alt` = g.`Alt`, c.`est_accuracy` = g.`AccuracyMeters`, c.`ModDate` = c.`ModDate`
  WHERE NOT (g.`Lat` = 0 AND g.`Lon` = 0);
CREATE INDEX `est_latlon` ON `wifi_ap` (`est_lat`, `est_lon`);
CREATE INDEX `est_latlon` ON `cell_id` (`est_lat`, `est_lon`);
-- what's left to work out, found at once (and nothing to scan once it's all done)
CREATE INDEX `est_points` ON `wifi_ap` (`est_points`);
CREATE INDEX `est_points` ON `cell_id` (`est_points`);
