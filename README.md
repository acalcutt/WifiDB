Vistumbler WiFiDB -> Read-me
===================

Vistumbler WiFiDB is a PHP, and MSSQL based set of scripts that is intended to manage Wireless Access points made with the Vistumber Wireless scanning software  

  Project Phase: Beta
  --------------
  http://www.wifidb.net/

	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
	You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA, Or go here:  http://www.gnu.org/licenses/gpl-2.0.txt
		
  Requirements
  --------------
* PHP 7.2 or later  
	* GD2 (included with PHP now)  
	* ZipArchive class  
	* SQLiteDatabase class  
	* bcmath class  
* One of the supported databases:  
	* Microsoft SQL 2019 or Later (needed for UTF8 Support) -- the reference deployment  
	* PostgreSQL 11 or later (needed for covering indexes) -- support in progress, see below  
	* MySQL / MariaDB  
* Apache 2.4 or later  
* A Web-browser (doh!)  

  Installation
  --------------
	NOTE: If you are using Linux, you must chown & chgrp the wifidb folder, to the user 
	that you have apache or what ever HTTP server you are using 
	
	1.) Set up a debian instance with apache and php  
	2.) Set up a Microsoft SQL Instance (The free sql developer version, windows or linux, will work fine)  
	3.) Create tools directory (ex. /opt/wdbtools/)  
	4.) Copy the /wifidb/tools folder from gitbub into the tools directory created in the previous step  
	5.) Copy the /wifidb/wifidb folder from github into your website root directory  
	6.) Create a blank mssql database(ex. wifi) and import the 'blank_db.sqlsrv' file into it. (Note: Replace all instances of 'prod_wifi' in blank_db.sqlsrv with what you named your database)  
	7.) Create a mssql user that has access to the database created in the previous step  
	8.) Update your daemon config file, [tools]/daemon.config.inc.php  
	9.) Update your website config file, [webroot]wifidb/lib/config.inc.php  

  PostgreSQL (work in progress)
  --------------
	PostgreSQL support is being added alongside the existing MySQL and MSSQL
	back-ends. Set 'srvc' and 'driver' to 'pgsql' (port 5432) in config.inc.php,
	then create the database with:

		createdb wifi
		psql -d wifi -f blank_db.pgsql

	Unlike blank_db.sqlsrv there is no database name to search-and-replace --
	blank_db.pgsql does not reference one.

	Note on identifier case: the schema keeps mixed-case names (GPS_ID, ModDate,
	ValidGPS, Country, ...) because the PHP layer reads result rows by those exact
	keys. MySQL and SQL Server match column names case-insensitively; PostgreSQL
	folds unquoted identifiers to lower case. blank_db.pgsql therefore creates
	every identifier double-quoted, and the pgsql query branches must double-quote
	any mixed-case column they reference. A plain lower-case name needs no quoting.

	Status: the schema, the PDO connection layer and the core library queries are
	converted. The remaining query sites are listed by tools/utilites/pg_audit.py.
	
  To Import Manually:  
	cd [tools]/daemon  
	php importd.php -o -v  

  To Import by Cron Job:  
	Schedule the .sh files in [tools]/cron  

  Change Log:  
		/[WiFiDB Path]/ver.php  
  Support:  
		Go to the Vistumber WifiDB section of these forums http://forum.techidiots.net/forum/  
