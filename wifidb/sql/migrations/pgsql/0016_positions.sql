-- Where each access point and cell tower / Bluetooth device most likely is, from all its readings (lib/position.inc.php):
-- est_lat and est_lon (DDMM.MMMM, as wifi_gps keeps them), est_alt, est_accuracy (metres) and est_points (readings
-- used; 0: none to place). Filled here from the strongest reading, as the maps showed until now (est_points NULL: not
-- worked out yet); the positions cron job (tools/utilites/update_positions.php) then works each one out, and imports
-- keep them up to date.
ALTER TABLE wifi_ap ADD COLUMN est_lat numeric(9,4) NULL, ADD COLUMN est_lon numeric(9,4) NULL, ADD COLUMN est_alt numeric(7,2) NULL,
  ADD COLUMN est_accuracy numeric(10,2) NULL, ADD COLUMN est_points integer NULL;
ALTER TABLE cell_id ADD COLUMN est_lat numeric(9,4) NULL, ADD COLUMN est_lon numeric(9,4) NULL, ADD COLUMN est_alt numeric(7,2) NULL,
  ADD COLUMN est_accuracy numeric(10,2) NULL, ADD COLUMN est_points integer NULL;
UPDATE wifi_ap a SET est_lat = g."Lat", est_lon = g."Lon", est_alt = g."Alt", est_accuracy = g."AccuracyMeters"
  FROM wifi_gps g WHERE g."GPS_ID" = a."HighGps_ID" AND NOT (g."Lat" = 0 AND g."Lon" = 0);
UPDATE cell_id c SET est_lat = g."Lat", est_lon = g."Lon", est_alt = g."Alt", est_accuracy = g."AccuracyMeters"
  FROM wifi_gps g WHERE g."GPS_ID" = c.highgps_id AND NOT (g."Lat" = 0 AND g."Lon" = 0);
CREATE INDEX wifi_ap_est_latlon ON wifi_ap (est_lat, est_lon);
CREATE INDEX cell_id_est_latlon ON cell_id (est_lat, est_lon);
-- what's left to work out (est_points NULL), found at once, and an empty index once it's all done
CREATE INDEX wifi_ap_est_todo ON wifi_ap ("AP_ID") WHERE est_points IS NULL;
CREATE INDEX cell_id_est_todo ON cell_id (cell_id) WHERE est_points IS NULL;
