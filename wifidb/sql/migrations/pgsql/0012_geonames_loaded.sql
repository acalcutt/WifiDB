-- When tools/utilites/load_geonames.php last loaded each GeoNames row, so a load can take out what the file no longer has
ALTER TABLE geonames ADD COLUMN loaded_at timestamp(0) NULL;
ALTER TABLE geonames_admin1 ADD COLUMN loaded_at timestamp(0) NULL;
ALTER TABLE geonames_admin2 ADD COLUMN loaded_at timestamp(0) NULL;
ALTER TABLE geonames_country_names ADD COLUMN loaded_at timestamp(0) NULL;
