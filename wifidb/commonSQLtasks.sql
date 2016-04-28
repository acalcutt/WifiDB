TRUNCATE wifi_pointers;
TRUNCATE wifi_signals;
TRUNCATE wifi_gps;
TRUNCATE user_imports;
TRUNCATE files_bad;
TRUNCATE files;
TRUNCATE files_tmp;
TRUNCATE files_importing;



SELECT * from files;
SELECT * from files_importing;
SELECT * FROM files_tmp;
SELECT * FROM files_bad;
SELECT * FROM user_imports;


SELECT * from wifi_signals;

SELECT * from wifi_pointers;


SELECT * from wifi.schedule;

update wifi.schedule set `nextrun` = 0, `status` = 'Waiting';