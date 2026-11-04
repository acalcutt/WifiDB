-- Each GeoNames row once, so tools/utilites/load_geonames.php can update a place, region or country in place: the
-- places are already keyed by geonameid here; the countries get a key on their ISO code, after the copies a load
-- without --replace left are taken out (the oldest row of each is kept)
DELETE c1 FROM `geonames_country_names` c1 JOIN `geonames_country_names` c2 ON c1.`ISO` = c2.`ISO` AND c1.`id` > c2.`id`;
ALTER TABLE `geonames_country_names` ADD UNIQUE KEY `ISO` (`ISO`);
