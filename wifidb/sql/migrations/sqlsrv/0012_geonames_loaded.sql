-- When tools/utilites/load_geonames.php last loaded each GeoNames row, so a load can take out what the file no longer has
ALTER TABLE [dbo].[geonames] ADD [loaded_at] [datetime2](0) NULL
GO
ALTER TABLE [dbo].[geonames_admin1] ADD [loaded_at] [datetime2](0) NULL
GO
ALTER TABLE [dbo].[geonames_admin2] ADD [loaded_at] [datetime2](0) NULL
GO
ALTER TABLE [dbo].[geonames_country_names] ADD [loaded_at] [datetime2](0) NULL
GO
