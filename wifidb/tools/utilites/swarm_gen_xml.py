__author__ = 'pferland'

import os, sys, cymysql, time, datetime
from Config import Config

daemon_cfg_obj = Config("/etc/wifidb/config.ini")
daemon_config = daemon_cfg_obj.ConfigMap("daemon")

www_config_obj = Config( daemon_config['wifidb_install']+"/lib/config.ini")
www_config = www_config_obj.ConfigMap("sql")

conn = cymysql.connect(host='172.16.1.18', user='root', passwd='saNsui20si', db='wifi')
#conn = cymysql.connect(host=www_config['sql_host'], user=www_config['db_user'], passwd=www_config['db_pwd'], db=www_config['db'])

header = "<?xml version=\"1.0\"?>\r\n<file_events>\r\n"
footer = "</file_events>"
events = ""

cur = conn.cursor()
cur.execute("SELECT `points`,`title`,`username`,`date`,`aps` FROM `wifi`.`users_imports` order by `id` DESC");

array = cur.fetchall()
total = len(array)
import_count = 0
total_aps = 0
for imports in array:
    if not imports[3]:
        continue
    date_pre = str(imports[3])
    date = int(time.mktime(datetime.datetime.strptime(date_pre, "%Y-%m-%d %H:%M:%S").timetuple()))
    import_count += 1
    i = 0
    print str(import_count) + "/" + str(total) + " : User: " + str(imports[2]) + " - Date: " + str(imports[3]) + " (" + str(date) + ")" + " - Title: " + str(imports[1]) + " - APcount: " + str(imports[4]);
    author = str(imports[2])
    points_explode = str(imports[0]).split("-")
    for point in points_explode:

        point_exp = point.split(",")
        gps_id = point_exp[0]

        id_new_old = point_exp[1].split(":")
        apid = id_new_old[0]
        new_old = id_new_old[1]

        cur.execute("SELECT `sectype` FROM `wifi0` WHERE `id`= %s" % apid)
        sectype = cur.fetchone()
        if sectype[0] == '1':
            sec_type = "open"

        if sectype[0] == '2':
            sec_type = "wep"

        if sectype[0] == '3':
            sec_type = "wpa"

        if not sec_type:
            sec_type = "open"
                                                                        #
        events += "\t" + '<event date="' + str(date) + '000" filename="' + str(apid) + '.' + str(sec_type) + '" author="' + str(author) + '" />' + "\r\n";
        i += 1

    print "APs: " + str(i)
    total_aps += i
    print total_aps
    print "------------------------------------------------------------------"


xml_all = header + events + footer

print "write events.xml to current directory"
f = open("events.xml", "w")
f.write(xml_all)
f.close()
