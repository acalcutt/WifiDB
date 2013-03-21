create database wifi_st;

create user root@192.168.1.17 identified by 'saNsui20si';
grant all on *.* to root@192.168.1.17 with grant option;

create user pferland@192.168.4.125 identified by 'W!reS!99';
grant all on wifi.* to pferland@192.168.4.125;
grant all on wifi_st.* to pferland@192.168.4.125;