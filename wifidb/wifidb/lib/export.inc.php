<?php
/*
Export.inc.php, holds the WiFiDB exporting functions.
Copyright (C) 2012 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

ou should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/
class export extends dbcore
{
	public function __construct($config, $createKMLObj, $convertObj, $ZipObj){
		parent::__construct($config);

		$this->convert = $convertObj;
		$this->createKML = $createKMLObj;
		$this->Zip = $ZipObj;
		$this->daemon_folder_stats = array();
		$this->named = 0;
		$this->month_names  = array(
			1=>'January',
			2=>'February',
			3=>'March',
			4=>'April',
			5=>'May',
			6=>'June',
			7=>'July',
			8=>'August',
			9=>'September',
			10=>'October',
			11=>'November',
			12=>'December',
		);
		$this->ver_array['export']  =   array(
			"last_edit"			 =>  "2015-03-06",
			"Exportkml"		  =>  "1.0",
			"ExportDailykml"		=>  "1.1",
			"ExportSingleAP"		=>  "1.0",
			"ExportCurrentAPkml"	=>  "1.0",
			"GenerateDaemonKMLData" =>  "1.1",
			"GenerateDaemonKMLLinks"=>  "1.0",
			"HistoryKMLLink"		=>  "1.0",
			"FulldbKMLLink"		 =>  "1.0",
			"DailydbKMLLink"		=>  "1.0",
			"GenerateUpdateKML"	 =>  "1.0",
			"ExportAllVS1"		  =>  "2.0",
			"ExportAllGPX"		  =>  "2.0",
		);
	}

	public function CreateBoundariesKML()
	{
		$boundaries_kml_file = $this->PATH.'out/daemon/boundaries.kml';
		$this->verbosed("Generating World Boundaries KML File : ".$boundaries_kml_file);

		$results = $this->sql->conn->query("SELECT * FROM `wifi`.`boundaries`");
		$fetched = $results->fetchAll(2);
		$KML_data = "";
		foreach($fetched as $boundary)
		{
			$KML_data .= $this->createKML->PlotBoundary($boundary);
		}

		$KMLFolderdata = $this->createKML->createFolder("World Boundaries", $KML_data, 0);
		$this->createKML->createKML($boundaries_kml_file, "World Boundaries", $KMLFolderdata);
		chmod($boundaries_kml_file, 0664);
		return $boundaries_kml_file;
	}

	/*
	 * Export to Google KML File
	 */
	public function Exportkml($date = NULL, $type = "full", $only_new = 0, $new_icons = 0)
	{
	
		#Set Date if it was not set
		if($date === NULL)
		{
			$date = date($this->date_format);
		}
		
		#Set if APs are Labeled or not
		if($this->named)
		{
			$this->verbosed("Starting Export of Labeled ".$type." KML.");
			$labeled = "_label";
		}
		else
		{
			$this->verbosed("Starting Export of Non-Labeled ".$type."  KML.");
			$labeled = "";
		}

		#Create directory to store kmz using current date
		$daily_folder = $this->PATH.'out/daemon/'.$date;
		if(!@file_exists($daily_folder))
		{
			$this->verbosed("Need to make a daily export folder...", 1);
			if(!@mkdir($daily_folder))
			{
				$this->verbosed("Error making new daily export folder...", -1);
			}
		}else
		{
			if(file_exists($daily_folder."/".$type."_db".$labeled.".kml") && file_exists($daily_folder."/".$type."_db".$labeled.".kmz")){$this->verbosed($type." DB Export for (".$date.") already exists."); return -1;}
		}
		
		#Create directory to store temp kmz files
		$kmztmp_folder =  $daily_folder.'/tmp';
		if(!@file_exists($kmztmp_folder))
		{
			$this->verbosed("Need to make kmz file folder...", 1);
			if(!@mkdir($kmztmp_folder))
			{
				$this->verbosed("Error making kmz file folder...", -1);
			}
		}

		$this->verbosed("Compiling Data for Export.");

		if($type == "full")
		{
			#Create Queries
			$user_query = "SELECT DISTINCT(username) FROM `wifi`.`user_imports` ORDER BY `username` ASC";
			$user_list_query = "SELECT `id`, `points`, `username`, `title`, `date` FROM `wifi`.`user_imports` WHERE `username` LIKE ? AND `points` != ''";
		}
		elseif($type == "daily")
		{
			#Get the date of the latest import
			$sql = "SELECT `date` FROM `wifi`.`user_imports` ORDER BY `date` DESC LIMIT 1";
			$date_query = $dbcore->sql->conn->query($sql);
			$date_fetch = $date_query->fetch(2);
			$datestamp = $date_fetch['date'];
			$datestamp_split = explode(" ", $datestamp);
			$latest_date = $datestamp_split[0];
			$latest_date = (empty($latest_date)) ? $date : $latest_date;
			
			#Create Queries
			$date_search = $latest_date."%";
			$user_query = "SELECT DISTINCT(username) FROM `wifi`.`user_imports` WHERE `date` LIKE '$date_search' ORDER BY `username` ASC";
			$user_list_query = "SELECT `id`, `points`, `username`, `title`, `date` FROM `wifi`.`user_imports` WHERE `username` LIKE ? AND `points` != '' AND `date` LIKE '$date_search'";
		}	
		
		$ZipC = clone $this->Zip;
		
		#Get list of users and go through them
		$results="";
		$lists = 0;
		$prep_user = $this->sql->conn->query($user_query);
		$fetch_user = $prep_user->fetchAll();
		$prep_user_list = $this->sql->conn->prepare($user_list_query);
		foreach($fetch_user as $user)
		{
			#Get users lists and go through them
			$user_results = "";
			$user_files = 0;
			$username = $user['username'];
			$prep_user_list->bindParam(1, $username, PDO::PARAM_STR);
			$prep_user_list->execute();
			$fetch_imports = $prep_user_list->fetchAll();
			foreach($fetch_imports as $import)
			{
				$id = $import['id'];
				$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $id.'_'.$import['title']);
				$ListKML = $this->UserListKml($import['points'], $this->named, $only_new, $new_icons);
				$list_results = $ListKML['region'].$ListKML['data'];
				if($list_results !== "")
				{
					#Create List KML Structure
					$list_results = $this->createKML->createFolder($title, $list_results, 0);
					$list_results = $this->createKML->createKMLstructure($title, $list_results);

					#Add list kml into final kmz
					$list_kml_name = $username."_".$title.$labeled.".kml";
					$ZipC->addFile($list_results, 'files/'.$list_kml_name);
					
					#Create Network Link to this kml for the final doc.kml
					$user_results .= $this->createKML->createNetworkLink('files/'.$list_kml_name, $title.' ( List ID:'.$id.')' , 0, 0, "onChange", 86400, 0, $ListKML['region']);

					#Increment variables (duh)
					++$user_files;
					++$lists;
				}
				unset($list_results);
			}
			#If this user had results, create a folder with their data
			if($user_results){$results .= $this->createKML->createFolder($username.' ('.$user_files.' Files)', $user_results, 0);}
			unset($user_results);
		}
		#Create the final KMZ
		if($results == ""){$results = $this->createKML->createFolder("No Exports with GPS", $results, 0);}else{$results = $this->createKML->createFolder("All Exports", $results, 0);}
		#$regions_link = $this->createKML->createNetworkLink($this->URL_PATH.'out/daemon/boundaries.kml', "Regions to save precious CPU cycles.", 1, 0, "once", 60);
		#$results .= $this->createKML->createFolder("WifiDB Newest AP", $regions_link, 1, 1);
		
		$results = $this->createKML->createFolder($type." Database Export", $results, 0);
		$results = $this->createKML->createKMLstructure("WiFiDB ".$type." Database Export", $results);
		
		$kmz_tmp = $kmztmp_folder."/".$type."_db".$labeled.".kmz";
		$this->verbosed("Writing the ".$type." KMZ File. ($lists Lists) : ".$kmz_tmp);
		$ZipC->addFile($results, 'doc.kml');
		$ZipC->setZipFile($kmz_tmp);
		$ZipC->getZipFile();
		if (file_exists($kmz_tmp)) 
		{
			$kmz_filepath = $daily_folder."/".$type."_db".$labeled.".kmz";
			rename($kmz_tmp , $kmz_filepath);
			if (file_exists($kmz_filepath)) 
			{
				$this->verbosed("KMZ created at ".$kmz_filepath);
				chmod($kmz_filepath, 0664);
				###
				$link = $this->daemon_out.$type.'_db'.$labeled.'.kmz';
				$this->verbosed('Creating symlink from "'.$kmz_filepath.'" to "'.$link.'"');
				unlink($link);
				symlink($kmz_filepath, $link);
				chmod($link, 0664);
			}
			else
			{
				$this->verbosed("Final KMZ file file does not exist :/ ");
			}
		}
		else
		{
			$this->verbosed("KMZ temp file does not exist :/ ");
		}

		if (file_exists($kmztmp_folder)){rmdir($kmztmp_folder);}
		
		return $daily_folder;
	}

	/*
	 * Export All Daily Aps to KML
	 */
	public function ExportSingleAP( $id = 0, $new_ap = 0, $limit = NULL, $from = NULL)
	{
		if($id === 0 || !is_int($id))
		{
			throw new ErrorException("AP ID is empty or not an Integer, supply one.");
			return 0;
		}
		$sql2 = "SELECT `ap_hash`, `lat`, `long`, `alt` FROM `wifi`.`wifi_pointers` WHERE `id` = '$id'";

		$prep2 = $this->sql->conn->query($sql2);
		$this->sql->checkError(__LINE__, __FILE__);
		$ap_fetch = $prep2->fetch(2);
		$sql3 = "SELECT
  `wifi_signals`.signal, `wifi_signals`.ap_hash, `wifi_signals`.rssi, `wifi_signals`.time_stamp,
  `wifi_gps`.lat, `wifi_gps`.`long`, `wifi_gps`.sats, `wifi_gps`.hdp, `wifi_gps`.alt, `wifi_gps`.geo,
  `wifi_gps`.kmh, `wifi_gps`.mph, `wifi_gps`.track, `wifi_gps`.date, `wifi_gps`.time
FROM `wifi`.`wifi_signals`
  LEFT JOIN `wifi`.`wifi_gps` ON `wifi_signals`.`gps_id` = `wifi_gps`.`id`
WHERE `wifi_signals`.`ap_hash` = '".$ap_fetch['ap_hash']."' AND `wifi_gps`.`lat` != '0.0000'";
		if(!empty($limit))
		{
			$sql3 .= " LIMIT $limit";
			if(!empty($from))
			{
				$sql3 .= ", $from";
			}
		}
		#echo $sql3;
		$data[$ap_fetch['ap_hash']] = $ap_fetch;
		$data[$ap_fetch['ap_hash']]['new_ap'] = $new_ap;
		$data[$ap_fetch['ap_hash']]['lat'] = $ap_fetch['lat'];
		$data[$ap_fetch['ap_hash']]['long'] = $ap_fetch['long'];
		$data[$ap_fetch['ap_hash']]['alt'] = $ap_fetch['alt'];
		$prep3 = $this->sql->conn->query($sql3);
		$this->sql->checkError();
		$sig_gps_data = $prep3->fetchAll(2);
		if(count($sig_gps_data) < 1)
		{
			#echo "No GPS\n";
			return -1;
		}
		$data[$ap_fetch['ap_hash']]['gdata'] = $sig_gps_data;

		return $data;
	}

	/*
	 * Export to Garmin GPX File
	 */
	public function ExportGPXAll()
	{
		$this->verbosed("Starting GPX Export of WiFiDB.");
		$sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE `lat` != '0.0000' AND `mac` != '00:00:00:00:00:00' ORDER by `id` ASC";
		$prep = $this->sql->conn->execute($sql);
		$aparray_all = $prep->fetchAll(2);
		$this->verbosed("Pointers Table Queried.");
		$err = $this->sql->conn->errorCode();
		if($err[0] !== "00000")
		{
			$this->logd("Error fetching from Pointers table to generate GPX All: ".var_export($this->sql->conn->errorInfo(), 1));
			$this->verbosed("Error Fetching data from Pointers Table :(", -1);
			return -1;
		}

		foreach($aparray_all as $aparray)
		{
			$file_data  = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\" ?>
<gpx xmlns=\"http://www.topografix.com/GPX/1/1\"
	creator=\"WiFiDB 0.16 Build 2\"
	version=\"1.1\"
	xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
	xsi:schemaLocation=\"http://www.topografix.com/GPX/1/1\">";
			// write file header buffer var

			$type = $aparray['sectype'];
			switch($type)
			{
				case 1:
					$color = "Navaid, Green";
					break;
				case 2:
					$color = "Navaid, Amber";
					break;
				case 3:
					$color = "Navaid, Red";
					break;
				default:
					$color = "Navaid, Green";
					break;
			}
			$date = $aparray["date"];
			$time = $aparray["time"];
			$alt = $aparray['alt'] * 3.28;
			$lat = $this->convert->dm2dd($aparray['lat']);
			$long = $this->convert->dm2dd($aparray['long']);

			$file_data .= "<wpt lat=\"".$lat."\" lon=\"".$long."\">\r\n"
				."<ele>".$alt."</ele>\r\n"
				."<time>".$date."T".$time."Z</time>\r\n"
				."<name>".$aparray['ssid']."</name>\r\n"
				."<cmt>".$aparray['mac']."</cmt>\r\n"
				."<desc>".$aparray['label']."</desc>\r\n"
				."<sym>".$color."</sym>\r\n<extensions>\r\n"
				."<gpxx:WaypointExtension xmlns:gpxx=\"http://www.garmin.com/xmlschemas/GpxExtensions/v3\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www.garmin.com/xmlschemas/GpxExtensions/v3/GpxExtensionsv3.xsd\">\r\n"
				."<gpxx:DisplayMode>SymbolAndName</gpxx:DisplayMode>\r\n<gpxx:Categories>\r\n"
				."<gpxx:Category>Category ".$type."</gpxx:Category>\r\n</gpxx:Categories>\r\n</gpxx:WaypointExtension>\r\n</extensions>\r\n</wpt>\r\n\r\n";
			if($aparray['rssi'])
			{
				$signals = explode("\\", $aparray['signals']);
			}else
			{
				$signals = explode("-",$aparray['sig']);
			}

			$file_data .= "<trk>\r\n<name>GPS Track</name>\r\n<trkseg>\r\n";
			foreach($signals as $signal)
			{
				$sig_exp	= explode(",",$signal);
				$gpsid	  = $sig_exp[0];

				$sql = "SELECT * FROM `wifi`.`wifi_gps` WHERE `id` = ? LIMIT 1";
				$prepgps = $this->sql->conn->prepare($sql);
				$prepgps->bindParam(1, $gpsid, PDO::PARAM_INT);
				$prepgps->execute();
				$gps = $prepgps->fetch(2);

				$alt = $gps['alt'] * 3.28;

				$lat =& $this->convert->dm2dd($gps['lat']);

				$long =& $this->convert->dm2dd($gps['long']);
				$file_data .= "<trkpt lat=\"".$lat."\" lon=\"".$long."\">\r\n"
					."<ele>".$alt."</ele>\r\n"
					."<time>".$date."T".$time."Z</time>\r\n"
					."</trkpt>\r\n";
			}
			$this->verbosed('Plotted AP: '.$aparray['ssid']);
		}

		$file_data .= "</trkseg>\r\n</trk></gpx>";
		$file_ext = "wifidb_".date($this->datetime_format).".gpx";
		$filename = ($this->gpx_out.$file_ext);
		$filewrite = fopen($filename, "w");
		if($filewrite == FALSE)
		{
			$this->logd("Error trying to write the GPX file: $filename");
			$this->verbosed("Error trying to write the GPX file: $filename  :(", -1);
			return -1;
		}
		$fileappend = fopen($filename, "a");
		fwrite($fileappend, $file_data);
		fclose($fileappend);

		#chmod($daily_folder.'/full_db'.$labeled.'.kml', 0750);

		return 1;
	}

	public function ExportCurrentAPkml($named=0, $new_icons=0)
	{
		$KML_data="";
		$sql = "SELECT `id`, `ssid`, `ap_hash` FROM `wifi`.`wifi_pointers` WHERE `lat` != '0.0000' ORDER BY `id` DESC LIMIT 1";
		$result = $this->sql->conn->query($sql);
		$ap_array = $result->fetch(2);
		if($ap_array['id'])
		{
			$id = (int)$ap_array['id'];
			list($KML_AP_data, $export_ssid) = $this->SingleApKml($id, $named, $new_icons);
			$KML_data = $KML_AP_data;
		}
		Return $KML_data;
	}

	public function ExportCurrentAP($named=0, $new_icons=0)
	{
		$KML_data = ExportCurrentAPkml($named, $new_icons);
		if($KML_data=""){$KML_data = $this->createKML->createFolder("No Access Points Found", "", 0, 0);}
		if($named){$KML_data = $this->createKML->createKMLstructure("Newest AP Labeled", $KML_data);}else{$KML_data = $this->createKML->createKMLstructure("Newest AP", $KML_data);}
		if($named){$kmz_filename = $this->daemon_out."newestAP_label.kmz";}else{$kmz_filename = $this->daemon_out."newestAP.kmz";}
		$this->Zip->addFile($KML_data, 'doc.kml');
		$this->Zip->setZipFile($kmz_filename);
		$this->Zip->getZipFile();
		
		if (file_exists($kmz_filename)) 
		{
			$this->verbosed("Newest AP KMZ created at ".$kmz_filename);
			chmod($kmz_filename, 0664);
		}
		else
		{
			$this->verbosed("Failed to Create Newest KMZ file :/ ");
		}
	}

	public function GatherAllExports()
	{
		$scan = scandir($this->daemon_out);
		foreach($scan as $file)
		{
			if($file === "."){continue;}
			if($file === ".."){continue;}
			if($file === "history"){continue;}
			if($file === "history.kml"){continue;}
			if($file === "history.kmz"){continue;}
			if($file === "boundaries.kml"){continue;}
			if($file === "full_db.kml"){continue;}
			if($file === "full_db.kmz"){continue;}
			if($file === "full_db_label.kml"){continue;}
			if($file === "full_db_label.kmz"){continue;}
			if($file === "daily_db_label.kmz"){continue;}
			if($file === "daily_db_label.kml"){continue;}
			if($file === "daily_db.kmz"){continue;}
			if($file === "daily_db.kml"){continue;}
			if($file === "newestAP_label.kml"){continue;}
			if($file === "newestAP_label.kmz"){continue;}
			if($file === "newestAP.kml"){continue;}
			if($file === "newestAP.kmz"){continue;}
			if($file === "update.kml"){continue;}
			if($file === "update.kmz"){continue;}
			var_dump($file);
			foreach(scandir($this->daemon_out.$file) as $subfile)
			{
				if($subfile === "."){continue;}
				if($subfile === ".."){continue;}
				if($subfile === "daily_db.kml"){continue;}

				if($this->named) {
					if ($subfile === "daily_db.kmz") {
						var_dump($subfile);
						continue;
					}

				} else {
					if ($subfile === "daily_db_label.kmz") {
						var_dump($subfile);
						continue;
					}
				}

			}
			echo "\n";
		}
	}

	/*
	 * Generate the Daily Daemon KML files
	 */
	public function GenerateDaemonKMLData()
	{
		$date = date($this->date_format);
		$this->named = 0;
		$this->Exportkml($date, "full" ,1 ,0);
		$this->named = 1;
		$this->Exportkml($date, "full" ,1 ,0);

		$this->named = 0;
		$this->Exportkml($date, "daily" ,0 ,1);
		$this->named = 1;
		$this->Exportkml($date, "daily" ,0 ,1);

		if($this->HistoryKMLLink() === -1)
		{
			$this->verbosed("Failed to Create Daemon History KML Links", -1);
		}else
		{
			$this->verbosed("Created Daemon History KML Links");
		}

		if($this->GenerateUpdateKML() === -1)
		{
			$this->verbosed("Failed to Create Update.kml File", -1);
		}else
		{
			$this->verbosed("Created Update.kml File");
		}
		return 1;
	}

	/*
	 * Create the Archival KML links
	 */
	public function HistoryKMLLink()
	{
		$this->daemon_folder_stats['history'] = array();
		$daemon_export = $this->PATH."out/daemon/";
		$dir = opendir($daemon_export);
		$files = array();
		while ($file = readdir($dir))
		{
			if($file == "." || $file == ".." || $file == ".svn"){continue;}
			if(is_dir($daemon_export.$file))
			{
				$files[] = $file;
			}
		}
		sort($files);
		closedir($dir);

		foreach($files as $entry)
		{
			$matches = array();
			preg_match("/([0-9]{4}\-[0-9]{2}\-[0-9]{2})/", $entry, $matches, PREG_OFFSET_CAPTURE);
			if(@$matches[0])
			{
				$date_exp = explode("-", $entry);
				$year = $date_exp[0]+0;
				$month = $date_exp[1]+0;
				$day = $date_exp[2]+0;
				$month_label = $this->month_names[$month];
				$this->daemon_folder_stats['history'][$year][$month_label][$day] = $entry;
			}

		}
		$generated = array();
		foreach($this->daemon_folder_stats['history'] as $key=>$year)
		{
			$output = $daemon_export.'history/'.$key.'.kmz';
			$current_year = date("Y")+0;
			if(file_exists($output) && $key != $current_year)
			{
				$generated[] = $key.'.kmz';
				continue;
			}
			$kml_data = '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
<Folder>
		<name>'.$key.'</name>
		<open>0</open>';

			foreach($year as $key1=>$month)
			{
				$kml_data .= '
		<Folder>
				<name>'.$key1.'</name>
				<open>0</open>';
				foreach($month as $key2=>$day)
				{
					if(file_exists($daemon_export.$day.'/daily_db.kmz'))
					{
						$daily_db_kmz_nl = '
						<NetworkLink>
								<name>Daily KMZ</name>
								<visibility>0</visibility>
								<Link>
										<href>'.$this->URL_PATH.'out/daemon/'.$day.'/daily_db.kmz</href>
								</Link>
						</NetworkLink>';
					}else
					{
						$daily_db_kmz_nl = '';
					}

					if(file_exists($daemon_export.$day.'/daily_db_label.kmz'))
					{
						$daily_db_kmz_label_nl = '
						<NetworkLink>
								<name>Daily Labeled KMZ</name>
								<visibility>0</visibility>
								<Link>
										<href>'.$this->URL_PATH.'out/daemon/'.$day.'/daily_db_label.kmz</href>
								</Link>
						</NetworkLink>';
					}else
					{
						$daily_db_kmz_label_nl = '';
					}

					if(file_exists($daemon_export.$day.'/full_db.kmz'))
					{
						$full_db_kmz_nl = '
						<NetworkLink>
								<name>Full DB KMZ</name>
								<visibility>0</visibility>
								<Link>
										<href>'.$this->URL_PATH.'out/daemon/'.$day.'/full_db.kmz</href>
								</Link>
						</NetworkLink>';
					}else
					{
						$full_db_kmz_nl = '';
					}

					if(file_exists($daemon_export.$day.'/full_db_label.kmz'))
					{
						$full_db_label_kmz_nl = '
						<NetworkLink>
								<name>Full DB Labeled KMZ</name>
								<visibility>0</visibility>
								<Link>
										<href>'.$this->URL_PATH.'out/daemon/'.$day.'/full_db_label.kmz</href>
								</Link>
						</NetworkLink>';
					}else
					{
						$full_db_label_kmz_nl = '';
					}

					$kml_data .= '
				<Folder>
						<name>'.$key2.'</name>
						<open>0</open>'.$daily_db_kmz_nl.
						$daily_db_kmz_label_nl.
						$full_db_kmz_nl.
						$full_db_label_kmz_nl.'
				</Folder>';
				}
				$kml_data .= '</Folder>';
			}
			$kml_data .= '</Folder></kml>';
			
			$this->Zip->addFile($kml_data, 'doc.kml');
			$this->Zip->setZipFile($output);
			$this->Zip->getZipFile();
			
			if (file_exists($output)) 
			{
				$this->verbosed("KMZ created at ".$output);
				chmod($output, 0664);
			}
			else
			{
				$this->verbosed("Failed to Create KMZ file :/ ");
			}		

			$generated[] = $key.'.kmz';
		}

		$kml_data = '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
<Folder>
		<name>WiFiDB Archive</name>
		<open>0</open>';
		foreach($generated as $year)
		{
			$year_name = str_replace(".kml", "", $year);
			$kml_data .= '
				<NetworkLink>
						<name>'.$year_name.'</name>
						<visibility>0</visibility>
						<Link>
								<href>'.$this->URL_PATH.'out/daemon/history/'.$year.'</href>
						</Link>
				</NetworkLink>';
		}
		$kml_data .= '
</Folder>
</kml>';
		$output = $daemon_export.'history.kml';
		
		$this->Zip->addFile($kml_data, 'doc.kml');
		$this->Zip->setZipFile($output);
		$this->Zip->getZipFile();
		
		if (file_exists($output)) 
		{
			$this->verbosed("KMZ created at ".$output);
			chmod($output, 0664);
		}
		else
		{
			$this->verbosed("Failed to Create KMZ file :/ ");
		}		
	}

	/*
	 * Generate the updated KML Link
	 */
	public function GenerateUpdateKML()
	{
		$full_link = $this->createKML->createNetworkLink($this->URL_PATH.'out/daemon/full_db.kmz', "Full DB Export (No Label)", 0, 0, "onInterval", 3600).
			$this->createKML->createNetworkLink($this->URL_PATH.'out/daemon/full_db_label.kmz', "Full DB Export (Label)", 0, 0, "onInterval", 3600);
		$full_folder = $this->createKML->createFolder("WifiDB Full DB Export", $full_link, 1, 1);

		$daily_link = $this->createKML->createNetworkLink($this->URL_PATH.'out/daemon/daily_db.kmz', "Daily DB Export (No Label)", 1, 0, "onInterval", 3600).
			$this->createKML->createNetworkLink($this->URL_PATH.'out/daemon/daily_db_label.kmz', "Daily DB Export (Label)", 0, 0, "onInterval", 3600);
		$daily_folder = $this->createKML->createFolder("WifiDB Daily DB Export", $daily_link, 1, 1);

		$new_AP_link = $this->createKML->createNetworkLink($this->URL_PATH.'api/latest.php?labeled=0',"Newest AP w/ Fly To (No Label)", 0, 1, "onInterval", 60).
			$this->createKML->createNetworkLink($this->URL_PATH.'api/latest.php?labeled=1',"Newest AP w/ Fly To (Labeled)", 0, 1, "onInterval", 60).
			$this->createKML->createNetworkLink($this->URL_PATH.'api/latest.php?labeled=0',"Newest AP (No Label)", 0, 0, "onInterval", 60).
			$this->createKML->createNetworkLink($this->URL_PATH.'api/latest.php?labeled=1',"Newest AP (Labeled)", 1, 0, "onInterval", 60);
		$new_AP_folder = $this->createKML->createFolder("WifiDB Newest AP", $new_AP_link, 1, 1);

		//$archive_link = $this->createKML->createNetworkLink($this->URL_PATH.'out/daemon/history.kmz', "Archived History", 0, 0, "onInterval", 86400);
		//$archive_folder = $this->createKML->createFolder("Historical Archives", $archive_link, 1);

		//$KML_data = $full_folder.$daily_folder.$new_AP_folder.$regions_folder;#.$archive_folder;
		$KML_data = $new_AP_folder.$daily_folder.$full_folder;#.$archive_folder;
		$KML_data = $this->createKML->createKMLstructure("Combined KMZ Network Link", $KML_data);
		
		$kmz_filename = $this->daemon_out.'update.kmz';
		$this->verbosed("Writing KMZ : ".$kmz_filename);
		$this->Zip->addFile($KML_data, 'doc.kml');
		$this->Zip->setZipFile($kmz_filename);
		$this->Zip->getZipFile();
		
		if (file_exists($kmz_filename)) 
		{
			$this->verbosed("KMZ created at ".$kmz_filename);
			chmod($kmz_filename, 0664);
		}
		else
		{
			$this->verbosed("Failed create KMZ file :/ ");
		}
		
		return $kmz_filename;
	}

	public function UserAllKml($user)
	{
		if(!is_string($user))
		{
			throw new ErrorException('$user value for export::UserAll() is not a string');
			return 0;
		}
		$sql = "SELECT * FROM `wifi`.`user_imports` WHERE `username` = ?";
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $user, PDO::PARAM_STR);
		$prep->execute();
		$this->sql->checkError(__LINE__, __FILE__);
		$user_imports = $prep->fetchAll();
		$uicount = count($user_imports);

		$KML_data="";
		if($uicount < 1)
		{
			throw new ErrorException("User selected is empty, try again.");
		}else
		{
			foreach($user_imports as $import)
			{
				$points = explode("-", $import['points']);
				foreach($points as $point)
				{
					list($id, $new_old) = explode(":", $point);
					$sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE `id` = '$id' And `lat` != '0.0000' AND `mac` != '00:00:00:00:00:00'";
					$result = $this->sql->conn->query($sql);
					while($array = $result->fetch(2))
					{
						$ret = $this->ExportSingleAP((int)$array['id'], 1);
						if(is_array($ret) && count($ret[$array['ap_hash']]['gdata']) > 0)
						{
							$this->createKML->ClearData();
							$this->createKML->LoadData($ret);
							$KML_data .= $this->createKML->PlotAllAPs(1, 1, $this->named);
						}
					}
				}
			}
		}
		return $KML_data;
	}

	public function UserAll($user)
	{
		$KML_data = $this->UserAllKml($user);
		if($KML_data == "")
		{
			$results = array("mesg" => 'This export has no APs with gps. No KMZ file has been exported');
		}
		else
		{
			$user_fn = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
			$kmz_filename = $this->kml_out.$user_fn.".kmz";
			#$this->verbosed("Writing KMZ for ".$user_fn." : ".$kmz_filename);
			$KML_data = $this->createKML->createKMLstructure($user_fn, $KML_data);
			$this->Zip->addFile($KML_data, 'doc.kml');
			$this->Zip->setZipFile($kmz_filename);
			$this->Zip->getZipFile();
			
			if (file_exists($kmz_filename)) 
			{
				$results = array("mesg" => 'File is ready: <a href="'.$this->kml_htmlpath.$user_fn.'.kmz">'.$user_fn.'.kmz</a>');
			}
			else
			{
				$results = array("mesg" => 'Error: No kmz file... what am I supposed to do with that? :/');
			}
		}
		return $results;
	}

	public function SingleAp($id, $named=0, $new_icons=0)
	{
		if(!is_int($id))
		{
			throw new ErrorException('$id value for export::SingleAp() is NaN');
			return 0;
		}

		list($KML_data, $export_ssid) = $this->SingleApKml($id, $named, $new_icons);
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $id."-".$export_ssid);
		$KML_data = $this->createKML->createKMLstructure($title, $KML_data);
		$kmz_filename = $this->kml_out.$title.".kmz";
		$this->Zip->addFile($KML_data, 'doc.kml');
		$this->Zip->setZipFile($kmz_filename);
		$this->Zip->getZipFile();
		if (file_exists($kmz_filename)) 
		{
			$results = array("mesg" => 'File is ready: <a href="'.$this->kml_htmlpath.$title.'.kmz">'.$title.'.kmz</a>');
		}
		else
		{
			$results = array("mesg" => 'Error: No kmz file... what am I supposed to do with that? :/');
		}
		return $results;
	}

	public function SingleApKml($id, $named=0, $new_icons=0)
	{
		$KML_data = "";
		$export_ssid="";
		$sql = "SELECT `mac`, `ssid`, `chan`, `radio`, `NT`, `sectype`, `auth`, `encry`, `BTx`, `OTx`, `FA`, `LA`, `lat`, `long`, `alt`, `manuf` FROM `wifi`.`wifi_pointers` WHERE `id` = '$id' And `lat` != '0.0000' AND `mac` != '00:00:00:00:00:00'";
		$result = $this->sql->conn->query($sql);
		while($ap_fetch = $result->fetch(2))
		{
			$export_ssid=$ap_fetch['ssid'];
			
			#Get AP KML
			$ap_info = array(
			"id" => $id,
			"new_ap" => $new_icons,
			"named" => $named,
			"mac" => $ap_fetch['mac'],
			"ssid" => $ap_fetch['ssid'],
			"chan" => $ap_fetch['chan'],
			"radio" => $ap_fetch['radio'],
			"NT" => $ap_fetch['NT'],
			"sectype" => $ap_fetch['sectype'],
			"auth" => $ap_fetch['auth'],
			"encry" => $ap_fetch['encry'],
			"BTx" => $ap_fetch['BTx'],
			"OTx" => $ap_fetch['OTx'],
			"FA" => $ap_fetch['FA'],
			"LA" => $ap_fetch['LA'],
			"lat" => $ap_fetch['lat'],
			"long" => $ap_fetch['long'],
			"alt" => $ap_fetch['alt'],
			"manuf" => $ap_fetch['manuf'],
			);
			$KML_data = $this->createKML->PlotAP($ap_info);
		}

		if($KML_data == ""){$KML_data = $this->createKML->createFolder("AP has no GPS", $KML_data, 0);}
		
		return array($KML_data, $export_ssid);
	}

	public function UserListKml($points, $named=0, $only_new=0, $new_icons=0)
	{
		$KML_data="";
		$KML_region="";
		$Import_KML_Data="";
		$box_latlon = array();
		$points = explode("-", $points);
		foreach($points as $point)
		{
			list($id, $new_old) = explode(":", $point);
			if($only_new == 1 and $new_old == 1){continue;}
			$sql = "SELECT `mac`, `ssid`, `chan`, `radio`, `NT`, `sectype`, `auth`, `encry`, `BTx`, `OTx`, `FA`, `LA`, `lat`, `long`, `alt`, `manuf` FROM `wifi`.`wifi_pointers` WHERE `id` = '$id' And `lat` != '0.0000' AND `mac` != '00:00:00:00:00:00'";
			$result = $this->sql->conn->query($sql);
			while($ap_fetch = $result->fetch(2))
			{
				#Get AP KML
				$ap_info = array(
				"id" => $id,
				"new_ap" => $new_icons,
				"named" => $named,
				"mac" => $ap_fetch['mac'],
				"ssid" => $ap_fetch['ssid'],
				"chan" => $ap_fetch['chan'],
				"radio" => $ap_fetch['radio'],
				"NT" => $ap_fetch['NT'],
				"sectype" => $ap_fetch['sectype'],
				"auth" => $ap_fetch['auth'],
				"encry" => $ap_fetch['encry'],
				"BTx" => $ap_fetch['BTx'],
				"OTx" => $ap_fetch['OTx'],
				"FA" => $ap_fetch['FA'],
				"LA" => $ap_fetch['LA'],
				"lat" => $ap_fetch['lat'],
				"long" => $ap_fetch['long'],
				"alt" => $ap_fetch['alt'],
				"manuf" => $ap_fetch['manuf'],
				);
				$Import_KML_Data .=$this->createKML->PlotAP($ap_info);
				
				$latlon_info = array(
				"lat" => $ap_fetch['lat'],
				"long" => $ap_fetch['long'],
				);
				$box_latlon[] = $latlon_info;
			}
		}
		
		if($Import_KML_Data != "")
		{
			$KML_data = $Import_KML_Data;
			$final_box = $this->FindBox($box_latlon);
			list($distance_calc, $minLodPix, $distance) = $this->distance($final_box[0], $final_box[2], $final_box[1], $final_box[3], "K"); # North, East, South, West
			$KML_region = $this->createKML->PlotRegionBox($final_box, $distance_calc, $minLodPix, uniqid());
		}
		
		$ret_data = array(
		"data" => $KML_data,
		"region" => $KML_region,
		);
		
		return $ret_data;
	}

	public function UserList($row, $OutputPath = 0, $file_hash = '', $date = '')
	{
		if(!is_int($row))
		{
			throw new ErrorException('$row value for export::UserList() is NaN');
			return 0;
		}
		$sql = "SELECT * FROM `wifi`.`user_imports` WHERE `id` = ?";
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $row, PDO::PARAM_INT);
		$prep->execute();
		$this->sql->checkError(__LINE__, __FILE__);
		$fetch = $prep->fetch();

		$ListKML = $this->UserListKml($fetch['points']);
		$KML_data = $ListKML['region'].$ListKML['data'];
		if($KML_data == "")
		{
			$results = array("mesg" => 'This export has no APs with gps. No KMZ file has been exported');
		}
		else
		{
			$KML_data = $this->createKML->createFolder($fetch['username']." - ".$fetch['title']." - ".$fetch['date'], $KML_data, 0);
			$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $fetch['title']);

			$kmz_filename = $this->kml_out.$title.".kmz";
			#$this->verbosed("Writing KMZ for ".$title." : ".$kmz_filename);
			$KML_data = $this->createKML->createKMLstructure($title, $KML_data);
			$this->Zip->addFile($KML_data, 'doc.kml');
			$this->Zip->setZipFile($kmz_filename);
			$this->Zip->getZipFile();
			
			if (file_exists($kmz_filename)) 
			{
				$results = array("mesg" => 'File is ready: <a href="'.$this->kml_htmlpath.$title.'.kmz">'.$title.'.kmz</a>');
			}
			else
			{
				$results = array("mesg" => 'Error: No kmz file... what am I supposed to do with that? :/');
			}			
		}
		return $results;
	}

	public function exp_search($ResultList)
	{
		$KML_data = "";
		foreach($ResultList as $ResultAP) {
			$ret = $this->ExportSingleAP((int)$ResultAP['id'], 1);
			if(is_array($ret) && count($ret[$ResultAP['ap_hash']]['gdata']) > 0)
			{
				$this->createKML->ClearData();
				$this->createKML->LoadData($ret);
				$KML_data .= $this->createKML->PlotAllAPs(1, 1, $this->named);
			}
		}

		if($KML_data == "")
		{
			$results = array("mesg" => 'This export has no APs with gps. No KMZ file has been exported');
		}
		else
		{
			$KML_data = $this->createKML->createFolder("Search Export", $KML_data, 0);
			$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), "Search_Export");
			$kmz_filename = $this->kml_out.$title.".kmz";
			#$this->verbosed("Writing KMZ for ".$title." : ".$kmz_filename);
			$KML_data = $this->createKML->createKMLstructure($title, $KML_data);
			$this->Zip->addFile($KML_data, 'doc.kml');
			$this->Zip->setZipFile($kmz_filename);
			$this->Zip->getZipFile();
			
			if (file_exists($kmz_filename)) 
			{
				$results = array("mesg" => 'File is ready: <a href="'.$this->kml_htmlpath.$title.'.kmz">'.$title.'.kmz</a>');
			}
			else
			{
				$results = array("mesg" => 'Error: No kmz file... what am I supposed to do with that? :/');
			}
		}
		return $results;
	}

	function FindBox($points = array())
	{
		$North = NULL;
		$South = NULL;
		$East = NULL;
		$West = NULL;
		foreach($points as $elements)
		{
			#var_dump($elements);
			if(@$elements['long'] == '' || @$elements['lat'] == '')
			{
				continue;
			}
			if($North == NULL)
			{
				$North = $elements['lat'];
			}
			if($South == NULL)
			{
				$South = $elements['lat'];
			}

			if($East == NULL)
			{
				$East = $elements['long'];
			}
			if($West == NULL)
			{
				$West = $elements['long'];
			}

			if((float)$North < (float)$elements['lat'])
			{
				$North = $elements['lat'];
			}
			if((float)$South > (float)$elements['lat'])
			{
				$South = $elements['lat'];
			}
			if((float)$East < (float)$elements['long'])
			{
				$East = $elements['long'];
			}
			if((float)$West > (float)$elements['long'])
			{
				$West = $elements['long'];
			}
		}
		#var_dump(array( $North, $South, $East, $West));
		return array( $North, $South, $East, $West);
	}

	function distance($lat1, $lon1, $lat2, $lon2, $unit)
	{
		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		$unit = strtoupper($unit);
		if ($unit == "K") {
			$ret = ($miles * 1.609344);
		}
		elseif ($unit == "N")
		{
			$ret = ($miles * 0.8684);
		}
		else
		{
			$ret = $miles;
		}
		
		if($ret > 400)
		{
			$distance_calc = 3000;
			$minLodPix = 64;
		}
		elseif($ret > 300)
		{
			$distance_calc = 1000;
			$minLodPix = 128;
		}
		elseif($ret > 200)
		{
			$distance_calc = 1000;
			$minLodPix = 256;
		}
		elseif($ret > 100)
		{
			$distance_calc = 1000;
			$minLodPix = 512;
		}
		else
		{
			$distance_calc = 1000;
			$minLodPix = 1024;
		}
		
		return array($distance_calc, $minLodPix, $ret);
	}
	
	function get_point($latitude, $longitude, $bearing, $distance, $unit = 'm')
	{
		//	Radius of earth.  3959 miles or 6371 kilometers.
		//  Pass in coordinates in Decimal form.  Example: -41.5786214
		if ($unit == 'm')
		{
			$radius = 3959;
		}
		elseif ($unit == 'km')
		{
			$radius = 6371;
		}

		//	New latitude in degrees.
		$new_latitude = rad2deg(asin(sin(deg2rad($latitude)) * cos($distance / $radius) + cos(deg2rad($latitude)) * sin($distance / $radius) * cos(deg2rad($bearing))));
				
		//	New longitude in degrees.
		$new_longitude = rad2deg(deg2rad($longitude) + atan2(sin(deg2rad($bearing)) * sin($distance / $radius) * cos(deg2rad($latitude)), cos($distance / $radius) - sin(deg2rad($latitude)) * sin(deg2rad($new_latitude))));

		//  Assign new latitude and longitude to an array to be returned to the caller.
		$coord['latitude'] = $new_latitude;
		$coord['longitude'] = $new_longitude;

		return $coord;
	}

}
