-- Where each access point and cell tower / Bluetooth device most likely is, from all its readings (lib/position.inc.php):
-- est_lat and est_lon (DDMM.MMMM, as wifi_gps keeps them), est_alt, est_accuracy (metres) and est_points (readings
-- used). Filled here from the strongest reading, as the maps showed until now (est_points NULL: not worked out yet);
-- tools/utilites/update_positions.php then works each one out, and imports keep them up to date.
ALTER TABLE [dbo].[wifi_ap] ADD [est_lat] [decimal](9,4) NULL, [est_lon] [decimal](9,4) NULL, [est_alt] [decimal](7,2) NULL,
  [est_accuracy] [decimal](10,2) NULL, [est_points] [int] NULL
GO
ALTER TABLE [dbo].[cell_id] ADD [est_lat] [decimal](9,4) NULL, [est_lon] [decimal](9,4) NULL, [est_alt] [decimal](7,2) NULL,
  [est_accuracy] [decimal](10,2) NULL, [est_points] [int] NULL
GO
UPDATE a SET a.[est_lat] = g.[Lat], a.[est_lon] = g.[Lon], a.[est_alt] = g.[Alt], a.[est_accuracy] = g.[AccuracyMeters]
  FROM [dbo].[wifi_ap] a INNER JOIN [dbo].[wifi_gps] g ON g.[GPS_ID] = a.[HighGps_ID]
  WHERE NOT (g.[Lat] = 0 AND g.[Lon] = 0)
GO
UPDATE c SET c.[est_lat] = g.[Lat], c.[est_lon] = g.[Lon], c.[est_alt] = g.[Alt], c.[est_accuracy] = g.[AccuracyMeters]
  FROM [dbo].[cell_id] c INNER JOIN [dbo].[wifi_gps] g ON g.[GPS_ID] = c.[highgps_id]
  WHERE NOT (g.[Lat] = 0 AND g.[Lon] = 0)
GO
-- the map tiles' query (export.inc.php BboxDateArray) reads everything it needs from this one
CREATE NONCLUSTERED INDEX [IX_wifi_ap_est_covering] ON [dbo].[wifi_ap]([est_lat], [est_lon])
  INCLUDE([BSSID], [SSID], [CHAN], [AUTH], [ENCR], [SECTYPE], [RADTYPE], [NETTYPE], [BTX], [OTX], [fa], [la], [points],
          [high_gps_sig], [high_gps_rssi], [File_ID], [est_alt], [est_accuracy])
  WHERE ([est_lat] IS NOT NULL AND [points] IS NOT NULL)
GO
CREATE NONCLUSTERED INDEX [IX_cell_id_est_latlon] ON [dbo].[cell_id]([est_lat], [est_lon])
GO
