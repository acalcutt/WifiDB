Vistumbler WiFiDB -> Read-me
===================

  A set of PHP-scripts to manage a MySQL based Wireless Database over the web.

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
		MariaDB 10.3 or later
		Apache 2.4 or later
		A Web-browser (doh!)
		
  Summary:
		WiFiDB is a PHP, and MySQL based set of scripts that is intended to manage 
		Wireless Access points found with the Vistumber Wireless scanning software  

		
  Installation:
		NOTE: If you are using Linux, you must chown & chgrp the wifidb folder, to the user 
		that you have apache or what ever HTTP server you are using (This is so that 
		the installer can create the config file, and to generate the graph images 
		and export KML files)
		
		1.) Create tools directory (ex. /opt/wdbtools/)
		2.) Copy the /wifidb/tools folder into the tools directory created in step 1
		3.) Copy the /wifidb/wifidb folder into your website root directory
		4.) Create a blank mysql database(ex. wifi) and import the 'blank_db.sql' file.
		5.) Create a mysql user that has access to the database created in step 4
		4.) Update your daemon config file, [tools]/daemon.config.inc.php
		5.) Update your website config file, [webroot]wifidb/lib/config.inc.php
		
		
  Change Log:
		/[WiFiDB Path]/ver.php
  Support:
		Go to the Vistumber WifiDB section of these forums http://forum.techidiots.net/forum/
