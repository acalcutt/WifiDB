Vistumbler WiFiDB -> Read-me
===================

  A set of PHP-scripts to manage a MSSQL based Wireless Database over the web.

  Project Phase: Beta
  --------------
  http://www.wifidb.net/

	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
	You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA, Or go here:  http://www.gnu.org/licenses/gpl-2.0.txt
		
  Requirements:
    PHP 7.2 or later
      GD2 (included with PHP now)
      ZipArchive class
      SQLiteDatabase class
      bcmath class
    Microsoft SQL 2019 or Later (needed for UTF8 Support)
    Apache 2.4 or later
    A Web-browser (doh!)

  Summary:
		WiFiDB is a PHP, and MSSQL based set of scripts that is intended to manage 
		Wireless Access points found with the Vistumber Wireless scanning software  

		
  Installation:
		NOTE: If you are using Linux, you must chown & chgrp the wifidb folder, to the user 
		that you have apache or what ever HTTP server you are using (This is so that 
		the installer can create the config file, and to generate the graph images 
		and export KML files)
		
		1.) Set up a debian instance with apache and php
		2.) Set up a Microsoft SQL Instance (The free sql developer version, windows or linux, will work fine)
		3.) Create tools directory (ex. /opt/wdbtools/)
		3.) Copy the /wifidb/tools folder from gitbub into the tools directory created in step 1
		5.) Copy the /wifidb/wifidb folder from github into your website root directory
		6.) Create a blank mssql database(ex. wifi) and import the 'blank_db.sqlsrv' file into it.
		7.) Create a mssql user that has access to the database created in step 4
		8.) Update your daemon config file, [tools]/daemon.config.inc.php
		9.) Update your website config file, [webroot]wifidb/lib/config.inc.php
		
  To Import Manually:
	cd [tools]/daemon
	php importd.php -o -v

  To Import by Cron Job:
	Schedule the .sh files in [tools]/cron
		
		
  Change Log:
		/[WiFiDB Path]/ver.php
  Support:
		Go to the Vistumber WifiDB section of these forums http://forum.techidiots.net/forum/
