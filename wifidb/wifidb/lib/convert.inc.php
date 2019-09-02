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
		If(stristr($Found_Capabilities, "WPA2") And stristr($Found_Capabilities, "CCMP") And stristr($Found_Capabilities, "EAP"))
		{	$Found_AUTH = "WPA2-Enterprise";
			$Found_ENCR = "CCMP";
			$Found_SecType = 3;
		}ElseIf(stristr($Found_Capabilities, "WPA") And stristr($Found_Capabilities, "CCMP") And stristr($Found_Capabilities, "EAP"))
		{	$Found_AUTH = "WPA-Enterprise";
			$Found_ENCR = "CCMP";
			$Found_SecType = 3;
		}ElseIf(stristr($Found_Capabilities, "WPA2") And stristr($Found_Capabilities, "CCMP"))
		{	$Found_AUTH = "WPA2-Personal";
			$Found_ENCR = "CCMP";
			$Found_SecType = 3;
		}ElseIf(stristr($Found_Capabilities, "WPA") And stristr($Found_Capabilities, "CCMP"))
		{	$Found_AUTH = "WPA-Personal";
			$Found_ENCR = "CCMP";
			$Found_SecType = 3;
		}ElseIf(stristr($Found_Capabilities, "WPA2") And stristr($Found_Capabilities, "TKIP") And stristr($Found_Capabilities, "EAP"))
		{	$Found_AUTH = "WPA2-Enterprise";
			$Found_ENCR = "TKIP";
			$Found_SecType = 3;
		}ElseIf(stristr($Found_Capabilities, "WPA") And stristr($Found_Capabilities, "TKIP") And stristr($Found_Capabilities, "EAP"))
		{	$Found_AUTH = "WPA-Enterprise";
			$Found_ENCR = "TKIP";
			$Found_SecType = 3;
		}ElseIf(stristr($Found_Capabilities, "WPA2") And stristr($Found_Capabilities, "TKIP"))
		{	$Found_AUTH = "WPA2-Personal";
			$Found_ENCR = "TKIP";
			$Found_SecType = 3;
		}ElseIf(stristr($Found_Capabilities, "WPA") And stristr($Found_Capabilities, "TKIP"))
		{	$Found_AUTH = "WPA-Personal";
			$Found_ENCR = "TKIP";
			$Found_SecType = 3;
		}ElseIf(stristr($Found_Capabilities, "WEP"))
		{	$Found_AUTH = "Open";
			$Found_ENCR = "WEP";
			$Found_SecType = 2;
		}Else
		{	$Found_AUTH = "Open";
			$Found_ENCR = "None";
			$Found_SecType = 1;
		}
		if(stristr($Found_Capabilities, "IBSS"))
		{
			$nt = "Ad-Hoc";
		}else
		{
			$nt = "Infrastructure";
		}
		$out = array($Found_AUTH, $Found_ENCR, $Found_SecType, $nt);
		return $out;
	}

	/**
	 * @param $frequency
	 * @return array
	 */
	public function findFreq($frequency = 0)
	{
		switch(true)
		{
			case ($frequency == 2412 || $frequency == 1):
				$chan = 1;
				$radio = "802.11g";
				break;
			case ($frequency == 2417 || $frequency == 2):
				$chan = 2;
				$radio = "802.11g";
				break;
			case ($frequency == 2422 || $frequency == 3):
				$chan = 3;
				$radio = "802.11g";
				break;
			case ($frequency == 2427 || $frequency == 4):
				$chan = 4;
				$radio = "802.11g";
				break;
			case ($frequency == 2432 || $frequency == 5):
				$chan = 5;
				$radio = "802.11g";
				break;
			case ($frequency == 2437 || $frequency == 6):
				$chan = 6;
				$radio = "802.11g";
				break;
			case ($frequency == 2442 || $frequency == 7):
				$chan = 7;
				$radio = "802.11g";
				break;
			case ($frequency == 2447 || $frequency == 8):
				$chan = 8;
				$radio = "802.11g";
				break;
			case ($frequency == 2452 || $frequency == 9):
				$chan = 9;
				$radio = "802.11g";
				break;
			case ($frequency == 2457 || $frequency == 10):
				$chan = 10;
				$radio = "802.11g";
				break;
			case ($frequency == 2462 || $frequency == 11):
				$chan = 11;
				$radio = "802.11g";
				break;
			case ($frequency == 2467 || $frequency == 12):
				$chan = 12;
				$radio = "802.11g";
				break;
			case ($frequency == 2472 || $frequency == 13):
				$chan = 13;
				$radio = "802.11g";
				break;
			case ($frequency == 2484 || $frequency == 14):
				$chan = 14;
				$radio = "802.11g";
				break;
			case ($frequency == 5180 || $frequency == 36):
				$chan = 36;
				$radio = "802.11n";
				break;
			case ($frequency == 5200 || $frequency == 40):
				$chan = 40;
				$radio = "802.11n";
				break;
			case ($frequency == 5220 || $frequency == 44):
				$chan = 44;
				$radio = "802.11n";
				break;
			case ($frequency == 5240 || $frequency == 48):
				$chan = 48;
				$radio = "802.11n";
				break;
			case ($frequency == 5260 || $frequency == 52):
				$chan = 52;
				$radio = "802.11n";
				break;
			case ($frequency == 5280 || $frequency == 56):				
				$chan = 56;
				$radio = "802.11n";
				break;
			case ($frequency == 5300 || $frequency == 60):
				$chan = 60;
				$radio = "802.11n";
				break;
			case ($frequency == 5320 || $frequency == 64):
				$chan = 64;
				$radio = "802.11n";
				break;
			case ($frequency == 5500 || $frequency == 100):
				$chan = 100;
				$radio = "802.11n";
				break;
			case ($frequency == 5520 || $frequency == 104):
				$chan = 104;
				$radio = "802.11n";
				break;
			case ($frequency == 5540 || $frequency == 108):
				$chan = 108;
				$radio = "802.11n";
				break;
			case ($frequency == 5560 || $frequency == 112):
				$chan = 112;
				$radio = "802.11n";
				break;
			case ($frequency == 5580 || $frequency == 116):
				$chan = 116;
				$radio = "802.11n";
				break;
			case ($frequency == 5600 || $frequency == 120):
				$chan = 120;
				$radio = "802.11n";
				break;
			case ($frequency == 5620 || $frequency == 124):
				$chan = 124;
				$radio = "802.11n";
				break;
			case ($frequency == 5640 || $frequency == 128):
				$chan = 128;
				$radio = "802.11n";
				break;
			case ($frequency == 5660 || $frequency == 132):
				$chan = 132;
				$radio = "802.11n";
				break;
			case ($frequency == 5680 || $frequency == 136):
				$chan = 136;
				$radio = "802.11n";
				break;
			case ($frequency == 5700 || $frequency == 140):
				$chan = 140;
				$radio = "802.11n";
				break;
			case ($frequency == 5720 || $frequency == 144):
				$chan = 144;
				$radio = "802.11n";
				break;
			case ($frequency == 5745 || $frequency == 149):
				$chan = 149;
				$radio = "802.11n";
				break;
			case ($frequency == 5765 || $frequency == 153):
				$chan = 153;
				$radio = "802.11n";
				break;
			case ($frequency == 5785 || $frequency == 157):
				$chan = 157;
				$radio = "802.11n";
				break;
			case ($frequency == 5805 || $frequency == 161):
				$chan = 161;
				$radio = "802.11n";
				break;
			case ($frequency == 5825 || $frequency == 165):
				$chan = 165;
				$radio = "802.11n";
				break;
			default:
				$chan = $frequency;
				$radio = "802.11g";
				break;
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
