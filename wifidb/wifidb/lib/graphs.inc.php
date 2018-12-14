<?php
/*

Copyright (C) 2011 Phil Ferland,2018 Andrew Calcutt

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

class graphs
{
	public function __construct($PATH, $URL_PATH)
	{
		$this->PATH		 =   $PATH;
		$this->URL_PATH	 =   $URL_PATH;
		$this->lastedit	 =   "05-05-2013";
		$this->ver_graph	=   array("graphs" => array(
								"wifiline"  =>  "2.0.4",
								"wifibar"   =>  "2.0.4",
								"imagegrid" =>  "1.0"));
	}

#==============================================================================================================================================================#
#													Image Grid Function														 #
#==============================================================================================================================================================#

	private function imagegrid($image, $w, $h, $s, $color)
	{
		$ws = $w/$s;
		$hs = $h/$s;
		for($iw=0; $iw < $ws; ++$iw)
		{
			imageline($image, ($iw-0)*$s, 60 , ($iw-0)*$s, $w , $color);
		}
		for($ih=0; $ih<$hs; ++$ih)
		{
			imageline($image, 0, $ih*$s, $w , $ih*$s, $color);
		}
	}

#==============================================================================================================================================================#
#													WiFi Graph Linegraph														 #
#==============================================================================================================================================================#

	public function wifigraphline($apdata)
	{
		$ssid = $apdata['ssid'];
		$mac = $apdata['mac'];
		$man = $apdata['man'];
		$auth = $apdata['auth'];
		$encry = $apdata['encry'];
		$radio = $apdata['radio'];
		$chan = $apdata['chan'];
		$lat = $apdata['lat'];
		$long = $apdata['long'];
		$BTx = $apdata['btx'];
		$OTx = $apdata['otx'];
		$FA = $apdata['fa'];
		$LU = $apdata['lu'];
		$NT = $apdata['nt'];
		$user = $apdata['user'];
		$sig = $apdata['sig'];
		$name = $apdata['name'];
		$bgc = $apdata['bgc'];
		$linec = $apdata['linec'];
		$text = $apdata['text'];

		$y=20;
		$yy=21;
		$u=20;
		$uu=21;
		if ($text == 'rand' or $text == '')
		{
			$tr = rand(50,200);
			$tg = rand(50,200);
			$tb = rand(50,200);
		}else
		{
			$text_color = explode(':', $text);
			$tr=$text_color[0];
			$tg=$text_color[1];
			$tb=$text_color[2];
		}
		if ($linec == 'rand' or $linec == '')
		{
			$r = rand(50,200);
			$g = rand(50,200);
			$b = rand(50,200);
		}else
		{
			$line_color = explode(':', $linec);
			$r=$line_color[0];
			$g=$line_color[1];
			$b=$line_color[2];
		}
		if($ssid == "" || $ssid == " ")
		{
			$ssid="UNNAMED";
		}
		$signal = explode("-", $sig);
		$count = count($signal);
		$c1 = 'SSID: '.$ssid.'   Channel: '.$chan.'   Radio: '.$radio.'   Network: '.$NT.'   OTx: '.$OTx;
		$check[] = strlen($c1);
		$c2 = 'Mac: '.$mac.'   Auth: '.$auth.' '.$encry.'   BTx: '.$BTx.'   Lat: '.$lat.'   Long: '.$long;
		$check[] = strlen($c2);
		$c3 = 'Manuf: '.$man.'   User: '.$user.'   First: '.$FA.'   Last: '.$LU;
		$check[] = strlen($c3);
		#FIND OUT IF THE IMG NEEDS TO BE WIDER
		rsort($check);
		if(1000 < ($count*6.2))
		{
			$Height = 500;
			$wid	= ($count*6.2)+40;

		}elseif(1000 < ($check[0]*6.2))
		{
			$Height = 500;
			$wid	= ($check[0]*6.2)+40;
		}else
		{
			$wid	= 1000;
			$Height = 500;
		}
		$img	= ImageCreateTrueColor($wid, $Height);
		$bgcc	= explode(":",$bgc);
		$bg	 = imagecolorallocate($img, $bgcc[0], $bgcc[1], $bgcc[2]);
		if($bgc !== "000:000:000")
		{
			$grid   = imagecolorallocate($img,0,0,0);
		}else
		{
			$grid   = imagecolorallocate($img,255,255,255);
		}
		$tcolor = imagecolorallocate($img, $tr, $tg, $tb);
		$col = imagecolorallocate($img, $r, $g, $b);
		imagefill($img,0,0,$bg); #PUT HERE SO THAT THE TEXT DOESN'T HAVE BLACK FILLINGS (eww)
		imagestring($img, 4, 21, 3, $c1, $tcolor);
		imagestring($img, 4, 21, 23, $c2, $tcolor);
		imagestring($img, 4, 21, 43, $c3, $tcolor);
		#signal strenth numbers--
		$p=460;
		$I=0;
		while($I<105)
		{
			imagestring($img, 4, 3, $p, $I, $tcolor);
			$I=$I+5;
			$p=$p-20;
		}
		#end signal strenth numbers--
		imagesetstyle($img, array($bg, $grid));
		$n=0;
		$nn=1;
		imagesetstyle($img,array($bg,$grid));
		$this->imagegrid($img,$wid,$Height,19.99,$grid);
		while($count>0)
		{
			imageline($img, $y, 459-(@$signal[$n]*4), $y=$y+6, 459-(@$signal[$nn]*4), $col);
			imageline($img, $u, 460-(@$signal[$n]*4), $u=$u+6, 460-(@$signal[$nn]*4), $col);
			imageline($img, $yy, 459-(@$signal[$n]*4), $yy=$yy+6, 459-(@$signal[$nn]*4), $col);
			imageline($img, $uu, 460-(@$signal[$n]*4), $uu=$uu+6, 460-(@$signal[$nn]*4), $col);
			$n++;
			$nn++;
			$count--;
		}
		$date = date("m-d-y");
		$file = 'out/graph/'.$name.'_'.$date.'_'.str_pad(rand(0,999999), 6, "0").'_v.png';
		$filepath = $this->PATH.$file;
		$file_url = $this->URL_PATH.$file;
		ImagePNG($img, $filepath);
		ImageDestroy($img);
		$array = array( $ssid,
			$file,
			$file_url);
		return $array;
	}

	#==============================================================================================================================================================#
	#													WiFi Graph Bargraph															 #
	#==============================================================================================================================================================#
	public function wifigraphbar($apdata = array())
	{
		$ssid = $apdata['ssid'];
		$mac = $apdata['mac'];
		$man = $apdata['man'];
		$auth = $apdata['auth'];
		$encry = $apdata['encry'];
		$radio = $apdata['radio'];
		$chan = $apdata['chan'];
		$lat = $apdata['lat'];
		$long = $apdata['long'];
		$BTx = $apdata['btx'];
		$OTx = $apdata['otx'];
		$FA = $apdata['fa'];
		$LU = $apdata['lu'];
		$NT = $apdata['nt'];
		$user = $apdata['user'];
		$sig = $apdata['sig'];
		$name = $apdata['name'];
		$bgc = $apdata['bgc'];
		$linec = $apdata['linec'];
		$text = $apdata['text'];
		$p=460;
		$I=0;

		if ($text == 'rand' or $text == '')
		{
			$tr = rand(50,200);
			$tg = rand(50,200);
			$tb = rand(50,200);
		}else
		{
			$text_color = explode(':', $text);
			$tr=$text_color[0];
			$tg=$text_color[1];
			$tb=$text_color[2];
		}
		if ($linec == 'rand' or $linec == '')
		{
			$r = rand(50,200);
			$g = rand(50,200);
			$b = rand(50,200);
		}else
		{
			$line_color = explode(':', $linec);
			$r=$line_color[0];
			$g=$line_color[1];
			$b=$line_color[2];
		}
		if ($ssid==""or$ssid==" ")
		{
			$ssid="UNNAMED";
		}
		$signal = explode("-", $sig);
		$count = (count($signal)-1);
		$c1 = 'SSID: '.$ssid.'   Channel: '.$chan.'   Radio: '.$radio.'   Network: '.$NT.'   OTx: '.$OTx;
		$check[] = strlen($c1);
		$c2 = 'Mac: '.$mac.'   Auth: '.$auth.' '.$encry.'   BTx: '.$BTx.'   Lat: '.$lat.'   Long: '.$long;
		$check[] = strlen($c2);
		$c3 = 'Manuf: '.$man.'   User: '.$user.'   First: '.$FA.'   Last: '.$LU;
		$check[] = strlen($c3);
		#FIND OUT IF THE IMG NEEDS TO BE WIDER
		if(1000 < ($count*3))
		{
			$Height = 500;
			$wid	= ($count*3)+38;

		}elseif(1000 < ($check[0]*8))
		{
			$Height = 500;
			$wid	= ($check[0]*8)+40;
		}else
		{
			$wid	= 1000;
			$Height = 500;
		}
		$img	= ImageCreateTrueColor($wid, $Height);
		$bgcc	= explode(":",$bgc);
		$bg	 = imagecolorallocate($img, $bgcc[0], $bgcc[1], $bgcc[2]);
		if($bgc !== "000:000:000")
		{
			$grid   = imagecolorallocate($img,0,0,0);
		}else
		{
			$grid   = imagecolorallocate($img,255,255,255);
		}
		$tcolor = imagecolorallocate($img, $tr, $tg, $tb);
		$col = imagecolorallocate($img, $r, $g, $b);
		imagefill($img,0,0,$bg); #PUT HERE SO THAT THE TEXT DOESNT HAVE BLACK FILLINGS (eww)
		imagestring($img, 4, 21, 3, $c1, $tcolor);
		imagestring($img, 4, 21, 23, $c2, $tcolor);
		imagestring($img, 4, 21, 43, $c3, $tcolor);
		#signal strenth numbers--
		while($I<105)
		{
			imagestring($img, 4, 3, $p, $I, $tcolor);
			$I=$I+5;
			$p=$p-20;
		}
		#end signal strenth numbers--
		imagesetstyle($img, array($bg, $grid));
		$X=20;
		$n=0;
		imagesetstyle($img,array($bg,$grid));
		$this->imagegrid($img,$wid,$Height,19.99,$grid);
		while($count>=0)
		{
			if ($signal[$n]==0)
			{
				$signal[$n]=1;
				imageline($img, $X ,459, $X, 459-($signal[$n]), $col);
				$X++;
				imageline($img, $X ,459, $X, 459-($signal[$n]), $col);
				$X=$X+2;
			}
			else
			{
				imageline($img, $X ,459, $X, 459-($signal[$n]*4), $col);
				$X++;
				imageline($img, $X ,459, $X, 459-($signal[$n]*4), $col);
				$X=$X+2;
			}
			$n++;
			$count--;
		}
		$date = date("m-d-y");
		$file = '/out/graph/'.$name.'_'.$date.'_'.str_pad(rand(0,999999), 6, "0").'.png';
		$filepath = $this->PATH.$file;
		$file_url = $this->URL_PATH.$file;
		ImagePNG($img, $filepath);
		ImageDestroy($img);
		$array = array( $ssid,
			$file,
			$file_url);
		return $array;
	}

	public function timeline($bgcolor = "", $lcolor = "", $start = "", $end = "")
	{
		if ($text == 'rand')
		{
			$tr = rand(50,200);
			$tg = rand(50,200);
			$tb = rand(50,200);
		}else
		{
			$text_color = explode(':', $text);
			$tr=$text_color[0];
			$tg=$text_color[1];
			$tb=$text_color[2];
		}
		if ($linec == 'rand')
		{
			$r = rand(50,200);
			$g = rand(50,200);
			$b = rand(50,200);
		}else
		{
			$line_color = explode(':', $linec);
			$r=$line_color[0];
			$g=$line_color[1];
			$b=$line_color[2];
		}
	}
}#end Graphs CLASS
?>