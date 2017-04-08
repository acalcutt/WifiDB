TRUNCATE wifi_pointers;
TRUNCATE wifi_signals;
TRUNCATE wifi_gps;
TRUNCATE user_imports;
TRUNCATE files_bad;
TRUNCATE files;
TRUNCATE files_tmp;
TRUNCATE files_importing;
update wifi.schedule set `nextrun` = 0, `status` = 'Waiting';

select DATEDIFF(`LA`, `FA`) as DD from wifi_pointers WHERE ap_hash = 'f8f1885c2bd202989e4df9f5c66e07a1';

select id, ssid, ap_hash, DATEDIFF(`LA`, `FA`) as DD from wifi_pointers;


SELECT * from files_importing;
SELECT * FROM files_tmp;
SELECT * FROM files_bad;
SELECT * FROM user_imports;


SELECT * from files ORDER BY `id` DESC;
SELECT * from wifi_signals ORDER BY `id` ASC;
SELECT * from wifi_pointers ORDER BY `id` DESC;
SELECT * from wifi_pointers WHERE `ssid` = 'EIHOME';
SELECT * FROM wifi_gps ORDER BY `id` ASC;



SELECT * from wifi.schedule;




CREATE TABLE wifi.wandering_aps
(
  id INT PRIMARY KEY AUTO_INCREMENT,
  aphash VARCHAR(255),
  wander_rating int(255),
  lat_nw VARCHAR(255),
  long_nw VARCHAR(255),
  lat_ne VARCHAR(255),
  long_ne VARCHAR(255),
  lat_se VARCHAR(255),
  long_se VARCHAR(255),
  lat_sw VARCHAR(255),
  long_sw VARCHAR(255)
);