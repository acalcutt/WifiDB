-- The places' position as numbers, indexed, so the nearest places to a point are found in a box around it
-- (lib/geonames.inc.php) instead of by matching the text of latitude and longitude, which only ever looked in one
-- 0.1 degree square. tools/utilites/load_geonames.php fills them from then on; this fills them for what's loaded.
ALTER TABLE geonames ADD COLUMN lat numeric(9,6) NULL, ADD COLUMN lon numeric(9,6) NULL;
UPDATE geonames SET lat = latitude::numeric, lon = longitude::numeric
  WHERE latitude ~ '^-?[0-9]+([.][0-9]+)?$' AND longitude ~ '^-?[0-9]+([.][0-9]+)?$';
CREATE INDEX geonames_lat_lon ON geonames (lat, lon);
