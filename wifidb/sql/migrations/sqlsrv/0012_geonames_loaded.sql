-- When tools/utilites/load_geonames.php last loaded each GeoNames row, so a load can take out what the file no longer has
ALTER TABLE [dbo].[geonames] ADD [loaded_at] [datetime2](0) NULL
GO
-- for when the places were last refreshed (load_geonames.php --scheduled) and the rows a load leaves behind
CREATE NONCLUSTERED INDEX [loaded_at] ON [dbo].[geonames]([loaded_at])
GO
ALTER TABLE [dbo].[geonames_admin1] ADD [loaded_at] [datetime2](0) NULL
GO
ALTER TABLE [dbo].[geonames_admin2] ADD [loaded_at] [datetime2](0) NULL
GO
ALTER TABLE [dbo].[geonames_country_names] ADD [loaded_at] [datetime2](0) NULL
GO
