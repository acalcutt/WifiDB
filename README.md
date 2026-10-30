Vistumbler WiFiDB -> Read-me
===================

Vistumbler WiFiDB is a PHP set of scripts, backed by Microsoft SQL Server or MariaDB, that is intended to manage Wireless Access points made with the Vistumber Wireless scanning software  

  Project Phase: Beta
  --------------
  http://www.wifidb.net/

	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
	You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA, Or go here:  http://www.gnu.org/licenses/gpl-2.0.txt
		
  Requirements
  --------------
* PHP 8.2 or later (8.3 or 8.4 recommended)  
	* Use a release with the October 2026 security fixes (CVE-2026-109618 and CVE-2026-109619): 8.2.35, 8.3.36, 8.4.27, 8.5.12 or later, or your distribution's patched package.  
	* PHP 8.1 and earlier, including 7.x, are not supported.  
	* Extensions: gd, zip, bcmath, mbstring, curl, xml, sockets (the websocket daemon)  
	* A database driver, one of:  
		* pdo_mysql (PHP's driver for MariaDB), for MariaDB  
		* pdo_sqlsrv and Microsoft's ODBC Driver 18 for SQL Server (msodbcsql18), for SQL Server  
* One of the following databases  
	* MariaDB 10.6 or later (utf8mb4). MySQL is not supported: blank_db.sql uses index sizes that only MariaDB allows  
	* Microsoft SQL Server 2019 or later (needed for UTF8 support)  
* Apache 2.4 or later  
* A Web-browser (doh!)  

  Installation
  --------------
	NOTE: If you are using Linux, you must chown & chgrp the wifidb folder, to the user 
	that you have apache or what ever HTTP server you are using 
	
	1.) Set up a Debian or Ubuntu instance with apache and PHP 8.2 or later and the extensions above  
	2.) Set up a database server, MariaDB or Microsoft SQL Server (the free developer edition, windows or linux, will work fine)  
	3.) Create tools directory (ex. /opt/wdbtools/)  
	4.) Copy the /wifidb/tools folder from this repository into the tools directory created in the previous step  
	5.) Copy the /wifidb/wifidb folder from this repository into your website root directory  
	6.) Create a blank database (ex. wifi) and import the schema into it  
		* MariaDB: import 'blank_db.sql'  
		* SQL Server: import 'blank_db.sqlsrv' (Note: Replace all instances of 'prod_wifi' in blank_db.sqlsrv with what you named your database)  
	7.) Create a database user that has access to the database created in the previous step  
	8.) Update your daemon config file, [tools]/daemon.config.inc.php (start from daemon.config.inc.php.example)  
	9.) Update your website config file, [webroot]wifidb/lib/config.inc.php (start from config.inc.php.example)  
		In the "SQL Info" section, use the SQL Server settings ('srvc' => 'sqlsrv') or the MariaDB settings ('srvc' => 'mysql', which is the name of PHP's driver for it).  
	
  To Import Manually:  
	cd [tools]/daemon  
	php importd.php -o -v  

  To Import by Cron Job:  
	Schedule the scripts in [tools]/cron (the ones that talk to SQL Server use sqlcmd, edit their server, user and password lines first)  

  Daemons:  
	The scripts in [tools]/daemon each run one background job: importd (imports uploaded files), exportd and  
	geojsond (exports), statsd (caches the statistics), mvtd and mltd (map tiles), manufupd (MAC manufacturers),  
	admind (clears stale daemon PID files) and websocketd. rund.php starts and stops them.  

  Upgrading from an older install:  
	* Install PHP 8.2 or later and make sure the extensions above are present.  
	* Back up the user_info table first. Passwords are now stored with bcrypt cost 12. Existing passwords keep working  
	  and each one is upgraded the next time its user logs in.  
	* The bundled Smarty is now 4.5. Delete the contents of [webroot]/wifidb/smarty/templates_c/ so the templates recompile.  
	* Themes are chosen from the folders in [webroot]/wifidb/themes/; a theme cookie naming anything else is ignored.  
	* Some old utilities in [tools]/utilites were removed because they could not run against the current code.  

  Running the Tests:  
	The tests need no web server, config file or database, just PHP 8.2 or later and Composer.  
	composer install  
	vendor/bin/phpunit  

	They cover the helper functions, password and key generation, coordinate and signal conversions, and a compile of  
	every Smarty template in both themes, which catches a template syntax error without running the site.  
	CI (.gitlab-ci.yml and .github/workflows/ci.yml) lints every PHP file and runs the tests on PHP 8.2, 8.3, 8.4 and 8.5.  

  Releasing:  
	New entries go in CHANGELOG.md under "## master". To make a release, run the bump:  
	GitLab: Build > Pipelines > New pipeline, set BUMP_VERSION (patch, minor, major, ...).  
	GitHub: Actions > Create bump version PR > Run workflow.  
	It raises VERSION, moves the "## master" entries into the new version and opens a merge request. Merging it builds  
	and boot-tests the Proxmox template and publishes the release with that version's changelog as the notes.  

  Change Log:  
		/[WiFiDB Path]/ver.php  
  Support:  
		Go to the Vistumber WifiDB section of these forums http://forum.techidiots.net/forum/  
