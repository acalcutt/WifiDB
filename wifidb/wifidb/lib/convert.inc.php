<?php
/*
convert.inc.php, functions to convert values
Copyright (C) 2015 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/
class convert extends dbcore
{
	public function __construct($config)
	{
		parent::__construct($config);
	}

	/**
	 * @param int $sig_in
	 * @return float
	 */
	public function Sig2dBm($sig_in = 0)
	{
		$dBm = ((($this->dBmMaxSignal - $this->dBmDissociationSignal) * $sig_in) - (20 * $this->dBmMaxSignal) + (100 * $this->dBmDissociationSignal)) / 80;
		$dbm_out =  round($dBm);
		return $dbm_out;
	}

	/**
	 * @param int $sig_in
	 * @return float
	 */
	public function dBm2Sig($sig_in = 0)
	{
		$SIG = 100 - 80 * ($this->dBmMaxSignal - $sig_in) / ($this->dBmMaxSignal - $this->dBmDissociationSignal);
		if($SIG < 0){$SIG = 0;}
		$round = round($SIG);
		return $round;
	}

	#===============================#
	#   Convert GeoCord DecDeg to DegMin	#
	#===============================#
	/**
	 * @param string $geocord_in
	 * @return string
	 */
	# 4-1-2014 : Re-written as All to DMM by acalcutt. Based on Vistumbler _Format_GPS_All_to_DMM() function.
	public function all2dm($geocord_in="")
	{
		$return = "0.0000";

		$pattern[0] = '/N /';
		$pattern[1] = '/E /';
		$replacement = "";
		$geocord_in = preg_replace($pattern, $replacement, $geocord_in);

		$pattern_neg[0] = '/S /';
		$pattern_neg[1] = '/W /';
		$replacement_neg = "-";
		$geocord_in = preg_replace($pattern_neg, $replacement_neg, $geocord_in);

		$sign = ($geocord_in[0] == "-") ? "-" : "";
		$geocord_in = str_replace("-", "", $geocord_in);# Temporarily remove "-" sign if it exists (otherwise the addition below won't work)

		$geocord_exp = explode(" ", $geocord_in);
		$sections = count($geocord_exp);

		if ($sections == 1)
		{
			$latlon_exp = explode(".", $geocord_exp[0]);
			if (strlen($latlon_exp[1]) == 4)
			{
				#DMM to DMM
				$return = $sign.((int)$latlon_exp[0]).".".$latlon_exp[1];
			}
			elseif (strlen($latlon_exp[1]) == 7)
			{
				#DDD to DMM
				$DD = $latlon_exp[0] * 100;
				$MM = ((float)(".".$latlon_exp[1])) * 60;
				$return = $sign.number_format($DD + $MM, 4, ".", "");
			}
		}
		elseif ($sections == 3)
		{
			#DDMMSS to DMM
			$DDSTR = substr($sections[0], 0, -1);
			$MMSTR = substr($sections[1], 0, -1);
			$SSSTR = substr($sections[2], 0, -1);

			$DD = $DDSTR * 100;
			$MM = $MMSTR + ($SSSTR / 60);
			$return = $sign.number_format($DD + $MM, 4, ".", "");
		}

		return $return;
	}


	/**
	 * @param string $geocord_in
	 * @return int|string
	 * @throws ErrorException
	 */
	# 4-1-2014 : Re-written by acalcutt. Based on Vistumbler _Format_GPS_DMM_to_DDD() function.
	public function dm2dd($geocord_in = "")
	{
		#echo "dm2dd in\r\n";
		#var_dump($geocord_in);

		$return="0.0000000";

		$sign = ($geocord_in[0] == "-") ? "-" : "";
		$geocord_in = str_replace("-", "", $geocord_in);# Temporarily remove "-" sign if it exists (otherwise the addition below won't work)

		$latlon_exp = explode(".", $geocord_in);
		$sections = count($latlon_exp);
		if ($sections == 2)
		{
			$latlonleft = (float) substr($latlon_exp[0], 0, -2);
			$latlonright = ((float)(substr($latlon_exp[0], (strlen($latlon_exp[0])-2)) . '.' . $latlon_exp[1])) / 60;
			$return = $sign.number_format($latlonleft + $latlonright , 7);

		}

		#echo "dm2dd out\r\n";
		#var_dump($return);
		return $return;
	}

	/**
	 * @param string $Found_Capabilities
	 * @return array
	 */
	public function findCapabilities($Found_Capabilities = "")
	{
		// Normalize input and use unified detection logic
		// This replaces the older repetitive branches with a single normalized parser.

		// Normalize
		$cap = strtoupper((string)$Found_Capabilities);

		// Determine cipher/encryption
		$encr = null;
		if (strpos($cap, 'GCMP') !== false || strpos($cap, 'GCMP-256') !== false) {
			$encr = 'GCMP';
		} elseif (strpos($cap, 'CCMP') !== false || strpos($cap, 'AES') !== false) {
			$encr = 'CCMP';
		} elseif (strpos($cap, 'TKIP') !== false) {
			$encr = 'TKIP';
		} elseif (strpos($cap, 'WEP') !== false) {
			$encr = 'WEP';
		} else {
			$encr = 'None';
		}

		// Determine auth (WPA/WPA2/WPA3/OWE/Open) and whether Enterprise (EAP) or PSK/SAE
		$auth = 'Open';
		$sectype = 1; // 1=open, 2=wep, 3=wpafamily

		// Flags
		$hasWPA3 = (strpos($cap, 'WPA3') !== false) || (strpos($cap, 'SAE') !== false);
		$hasWPA2 = (strpos($cap, 'WPA2') !== false) || (strpos($cap, 'RSN') !== false);
		$hasWPA = (strpos($cap, 'WPA') !== false);
		$hasEAP = (strpos($cap, 'EAP') !== false);
		$hasPSK = (strpos($cap, 'PSK') !== false) || (strpos($cap, 'PERSONAL') !== false);
		$hasOWE = (strpos($cap, 'OWE') !== false);

		if ($encr === 'WEP') {
			$auth = 'Open';
			$sectype = 2;
		} elseif ($hasWPA3) {
			$sectype = 3;
			if ($hasEAP) {
				$auth = 'WPA3-Enterprise';
			} elseif (strpos($cap, 'SAE') !== false) {
				$auth = 'WPA3-SAE';
			} else {
				$auth = 'WPA3';
			}
		} elseif ($hasWPA2) {
			$sectype = 3;
			if ($hasEAP) {
				$auth = 'WPA2-Enterprise';
			} elseif ($hasPSK) {
				$auth = 'WPA2-Personal';
			} else {
				$auth = 'WPA2';
			}
		} elseif ($hasWPA) {
			$sectype = 3;
			if ($hasEAP) {
				$auth = 'WPA-Enterprise';
			} elseif ($hasPSK) {
				$auth = 'WPA-Personal';
			} else {
				$auth = 'WPA';
			}
		} elseif ($hasOWE) {
			// Opportunistic Wireless Encryption - encrypted but no auth exchange
			$sectype = 3;
			$auth = 'OWE';
		} else {
			$auth = 'Open';
			$encr = ($encr === 'None') ? 'None' : $encr;
		}

		// Network type
		if (strpos($cap, 'IBSS') !== false) {
			$nt = 'Ad-Hoc';
		} else {
			$nt = 'Infrastructure';
		}

		// Ensure sensible defaults
		if ($encr === null) { $encr = 'None'; }

		return array($auth, $encr, $sectype, $nt);
	}

	/**
	 * @param $frequency
	 * @return array
	 */
	public function findFreq($frequency = 0)
	{
		// Accept either a channel number (1..165) or an RF frequency in MHz.
		// If frequency looks like a small integer (<= 200) treat it as channel.
		$chan = 0;
		$radio = "802.11g";

		if (is_numeric($frequency)) {
			$f = (int)$frequency;
			if ($f <= 0) {
				$chan = 0;
				$radio = "802.11g";
			} elseif ($f <= 200) {
				// Treated as channel number
				$chan = $f;
				$radio = ($chan <= 14) ? "802.11g" : "802.11n";
			} else {
				// Treated as MHz
				if ($f == 2484) {
					$chan = 14;
					$radio = "802.11g";
				} elseif ($f >= 2400 && $f < 2500) {
					$chan = (int) round(($f - 2407) / 5);
					$radio = "802.11g";
				} elseif ($f >= 5000 && $f < 5955) {
					// 5 GHz band
					$chan = (int) round(($f - 5000) / 5);
					$radio = "802.11n";
				} elseif ($f >= 5955 && $f <= 7125) {
					// 6 GHz (Wi‑Fi 6E / Wi‑Fi 7) band
					$chan = (int) round(($f - 5950) / 5);
					$radio = "802.11ax";
				} else {
					$chan = $f;
					$radio = ($f < 5000) ? "802.11g" : "802.11n";
				}
			}
		} else {
			$chan = $frequency;
		}

		$out = array($chan, $radio);
		return $out;
	}

	/**
	 * @param $source
	 */
	public function txt($source)
	{
		$apdata = array();
		$gpsdata = array();
		$return = file($source);

		//create interval for progress
		$line = count($return);
		$stat_c = $line/97;
		$complete = 0;
		$n=0;
		$N=0;
		$c = 0;
		$cc = 0;
		if ($this->debug ==1){echo $stat_c."\r\n";}
		if ($this->debug ==1){echo $line."\r\n";}
		// Start the main loop
		foreach($return as $ret)
		{
			$c++;
			$cc++;
			if ($ret[0] == "#"){continue;}
			$wifi = explode("|",$ret);
			$ret_count = count($wifi);
			if ($ret_count == 17)// test to see if the data is in correct format
			{
				if ($cc >= $stat_c)
				{
					$cc=0;
					$complete++;
					echo $complete."% - ";
					if ($complete == 100 ){ echo "\r\n\r\n";}
				}
				//format date and time
				$datetime = explode(" ",$wifi[13]);
				$date = $datetime[0];
				$time = $datetime[1];

				// Create the Security Type number for the respective Access point
				if ($wifi[4]=="Open"&&$wifi[5]=="None"){$sectype="1";}
				if ($wifi[4]=="Open"&&$wifi[5]=="WEP"){$sectype="2";}
				if ($wifi[4]=="WPA-Personal" or $wifi[4] =="WPA2-Personal"){$sectype="3";}

				if ($GLOBALS["debug"] ==1)
				{echo "\$n = ".$n."\n\$N = ".$N."\n";}
				$n++;
				$N++;
				$sig=$n.",".$wifi[3];
				$gpsdata[$n]=array(
					"id"=>$n,
					"lat"=>$this->all2dm(number_format($wifi[8], 7)),
					"long"=>$this->all2dm(number_format($wifi[9], 7)),
					"sats"=>'0',
					"hdp"=> '0.0',
					"alt"=> '0.0',
					"geo"=> '-0.0',
					"kmh"=> '0.0',
					"mph"=> '0.0',
					"track"=> '0.0',
					"date"=>$date,
					"time"=>$time
				);
				$apdata[$N]=array(
					"ssid"=>$wifi[0],
					"mac"=>$wifi[1],
					"man"=>$wifi[2],
					"auth"=>$wifi[4],
					"encry"=>$wifi[5],
					"highsig"=>$wifi[3],
					"highRSSI"=>$this->Sig2dBm($wifi[3]),
					"sectype"=>$sectype,
					"radio"=>$wifi[6],
					"chan"=>$wifi[7],
					"btx"=>$wifi[10],
					"otx"=>$wifi[11],
					"nt"=>$wifi[14],
					"label"=>$wifi[15],
					"sig"=>$sig
				);
				if ($GLOBALS["debug"] == 1 )
				{
					echo "\n\n+_+_+_+_+_+_\n".$gpsdata[$n]["lat"]."  +_\n".$gpsdata[$n]["long"]."  +_\n".$gpsdata[$n]["sats"]."  +_\n".$gpsdata[$n]["date"]."  +_\n".$gpsdata[$n]["time"]."  +_\n";
					echo "Access Point Number: ".$N."\n";
					echo "=-=-=-=-=-=-\n".$apdata[$N]["ssid"]."  =-\n".$apdata[$N]["mac"]."  =-\n".$apdata[$N]["auth"]."  =-\n".$apdata[$N]["encry"]."  =-\n".$apdata[$N]["sectype"]."  =-\n".$apdata[$N]["radio"]."  =-\n".$apdata[$N]["chan"]."  =-\n".$apdata[$N]["btx"]."  =-\n".$apdata[$N]["otx"]."  =-\n".$apdata[$N]["nt"]."  =-\n".$apdata[$N]["label"]."  =-\n".$apdata[$N]["sig"]."\n";
				}

			}else
			{
				echo "\nLine: ".$c." - Wrong data type, dropping row\n";
			}
		}
	}

	/**
	 * Get a center latitude,longitude from an array of like geopoints
	 *
	 * @param array data 2 dimensional named array of latitudes and longitudes
	 * For Example:
	 * $data = array
	 * (
	 *   0 = > array("lat" => 45.849382, "long" => 76.322333),
	 *   1 = > array("lat" => 45.843543, "long" => 75.324143),
	 *   2 = > array("lat" => 45.765744, "long" => 76.543223),
	 *   3 = > array("lat" => 45.784234, "long" => 74.542335)
	 * );
	*/
	function GetCenterFromDegrees($data)
	{
		
		if (!is_array($data)) return FALSE;

		$num_coords = count($data);

		$X = 0.0;
		$Y = 0.0;
		$Z = 0.0;

		foreach ($data as $coord)
		{
			$lat = $coord['lat'] * pi() / 180;
			$lon = $coord['long'] * pi() / 180;
			

			$a = cos($lat) * cos($lon);
			$b = cos($lat) * sin($lon);
			$c = sin($lat);

			$X += $a;
			$Y += $b;
			$Z += $c;
			
		}

		$X /= $num_coords;
		$Y /= $num_coords;
		$Z /= $num_coords;

		$lon = atan2($Y, $X);
		$hyp = sqrt($X * $X + $Y * $Y);
		$lat = atan2($Z, $hyp);
		

		return array("lat" => $lat * 180 / pi(),"long" => $lon * 180 / pi());
	}
}
