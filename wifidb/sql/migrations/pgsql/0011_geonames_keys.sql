-- Each GeoNames row once, so tools/utilites/load_geonames.php can update a place, region or country in place: a key on
-- the places' geonameid and the countries' ISO code, after the copies a load without --replace left are taken out
-- (the oldest row of each is kept)
DELETE FROM geonames a USING geonames b WHERE a.geonameid = b.geonameid AND a.id > b.id;
CREATE UNIQUE INDEX geonames_geonameid ON geonames (geonameid);
DELETE FROM geonames_country_names a USING geonames_country_names b WHERE a."ISO" = b."ISO" AND a.id > b.id;
CREATE UNIQUE INDEX geonames_country_names_iso ON geonames_country_names ("ISO");
