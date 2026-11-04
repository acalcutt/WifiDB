-- The places' position as numbers, indexed, so the nearest places to a point are found in a box around it
-- (lib/geonames.inc.php) instead of by matching the text of latitude and longitude, which only ever looked in one
-- 0.1 degree square. tools/utilites/load_geonames.php fills them from then on; this fills them for what's loaded.
ALTER TABLE [dbo].[geonames] ADD [lat] [decimal](9,6) NULL, [lon] [decimal](9,6) NULL
GO
-- in batches, so twelve million places don't go through as one transaction
DECLARE @done int = 1;
WHILE @done > 0
BEGIN
	UPDATE TOP (100000) [dbo].[geonames] SET [lat] = TRY_CAST([latitude] AS decimal(9,6)), [lon] = TRY_CAST([longitude] AS decimal(9,6))
	WHERE [lat] IS NULL AND TRY_CAST([latitude] AS decimal(9,6)) IS NOT NULL AND TRY_CAST([longitude] AS decimal(9,6)) IS NOT NULL;
	SET @done = @@ROWCOUNT;
END
GO
CREATE NONCLUSTERED INDEX [lat_lon] ON [dbo].[geonames]([lat], [lon])
GO
