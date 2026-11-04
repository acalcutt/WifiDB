-- Each GeoNames row once, so tools/utilites/load_geonames.php can update a place, region or country in place: a key on
-- the places' geonameid and the countries' ISO code, after the copies a load without --replace left are taken out
-- (the oldest row of each is kept)
WITH d AS (SELECT ROW_NUMBER() OVER (PARTITION BY [geonameid] ORDER BY [id]) AS rn FROM [dbo].[geonames]) DELETE FROM d WHERE rn > 1
GO
CREATE UNIQUE NONCLUSTERED INDEX [geonames_geonameid] ON [dbo].[geonames]([geonameid])
GO
WITH d AS (SELECT ROW_NUMBER() OVER (PARTITION BY [ISO] ORDER BY [id]) AS rn FROM [dbo].[geonames_country_names] WHERE [ISO] IS NOT NULL) DELETE FROM d WHERE rn > 1
GO
-- (SQL Server would let only one country without an ISO code into a plain unique key)
CREATE UNIQUE NONCLUSTERED INDEX [geonames_country_names_ISO] ON [dbo].[geonames_country_names]([ISO]) WHERE [ISO] IS NOT NULL
GO
