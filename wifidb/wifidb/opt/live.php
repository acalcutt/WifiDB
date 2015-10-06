<?php
/*
Database.inc.php, holds the database interactive functions.
Copyright (C) 2011 Phil Ferland

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
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");

include('../lib/init.inc.php');
$dbcore->smarty->assign('wifidb_page_label', 'Live Page');

$theme = $config['default_theme'];

$ord	=	@filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_SPECIAL_CHARS);
$sort	=	@filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_SPECIAL_CHARS);
$from	=	@filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT)+0;
$from	=	$from+0;
$from_	=	$from+0;
$inc	=	@filter_input(INPUT_GET, 'to', FILTER_SANITIZE_NUMBER_INT)+0;
$inc	=	$inc+0;
$view	=	@filter_input(INPUT_GET, 'view', FILTER_SANITIZE_NUMBER_INT)+0;
$date   =       date($dbcore->datetime_format, time()-3600);
if ($view==0 or !is_int($view)){$view=1800;}
if ($from==0 or !is_int($from)){$from=0;}
if ($from_==0 or !is_int($from_)){$from_=0;}
if ($inc==0 or !is_int($inc)){$inc=100;}
if ($ord=="" or !is_string($ord)){$ord="ASC";}
if ($sort=="" or !is_string($sort)){$sort="chan";}
#$date_time = strtotime($date);
#$sql = "SELECT `username` FROM `wifi`.`live_aps` WHERE la >= ? ORDER BY `$sort` $ord LIMIT $from, $inc";
#$prep = $dbcore->sql->conn->prepare($sql);
#$prep->bindParam(1, $date_time, PDO::PARAM_INT);
#$prep->execute();
#$fetch = $prep->fetchAll(2);
#die();


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <link rel="stylesheet" href="https://live.wifidb.net/wifidb/themes/vistumbler/styles.css" />
        <title>Wireless DataBase   *Alpha* 0.30 Build 1 *Pre-Release*   --&gt; Index Page</title>
        <meta name="description" content="A Wireless Database based off of scans from Vistumbler." /><meta name="keywords" content="WiFiDB, linux, windows, vistumbler, Wireless, database, DB, php, mysql" />
        
    </head>
    <body style="background-color: #145285">
        
		<p class="annunc_text"></p>
        
        <table style="width: 90%; " class="no_border" align="center">
            <tr>
                <td>
                    <table>
                        <tr>
                            <td style="width: 228px">
                                <a href="http://www.wifidb.net">
                                <img alt="Random Intervals Logo" src="https://live.wifidb.net/wifidb/themes/vistumbler/img/logo.png" 
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table style="width: 90%" align="center">
            <tr>
                <td style="width: 165px; height: 114px" valign="top">
                    <table style="width: 100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="width: 10px; height: 20px" class="cell_top_left">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                            <td class="cell_top_mid" style="height: 20px">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="185" height="1" />
                            </td>
                            <td style="width: 10px" class="cell_top_right">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                        </tr>
                        <tr width="185px">
                            <td class="cell_side_left">&nbsp;</td>
                            <td class="cell_color">
                                <div class="inside_dark_header">WiFiDB Links</div>
                                <div class="inside_text_bold"><strong>
                                    <a href="https://live.wifidb.net/wifidb/">Main Page</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="https://live.wifidb.net/wifidb/all.php?sort=SSID&ord=DESC&from=0&to=100">View All APs</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="https://live.wifidb.net/wifidb/import/">Import</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="https://live.wifidb.net/wifidb/opt/scheduling.php">Files Waiting for Import</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="https://live.wifidb.net/wifidb/opt/scheduling.php?func=done">Files Already Imported</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="https://live.wifidb.net/wifidb/opt/scheduling.php?func=daemon_kml">Daemon Generated kml</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="https://live.wifidb.net/wifidb/opt/export.php?func=index">Export</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="https://live.wifidb.net/wifidb/opt/search.php">Search</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="https://live.wifidb.net/wifidb/opt/userstats.php?func=allusers">View All Users</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a class="links" href="http://forum.techidiots.net/forum/viewforum.php?f=47">Help / Support</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="https://live.wifidb.net/wifidb/ver.php">WiFiDB Version</a></strong></div>

                                    
                                <!--=========================-->
                            </td>
                            <td class="cell_side_right">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="cell_bot_left">&nbsp;</td>
                            <td class="cell_bot_mid">&nbsp;</td>
                            <td class="cell_bot_right">&nbsp;</td>
                        </tr>
                        <tr>
                            <td style="width: 10px; height: 20px" class="cell_top_left">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                            <td class="cell_top_mid" style="height: 20px">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="185" height="1" />
                            </td>
                            <td style="width: 10px" class="cell_top_right">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                        </tr>
                        <tr>
                            <td class="cell_side_left">&nbsp;</td>
                            <td class="cell_color">
					<div class="inside_dark_header">Vistumbler Links</div>
					<div class="inside_text_bold"><a class="inside_text_bold" href="index.html">Vistumbler Home</a></div>
					<div class="inside_text_bold"><a href="https://forum.techidiots.net/forum/">Forum</a></div>
					<div class="inside_text_bold"><a href="https://github.com/RIEI/Vistumbler/wiki">Wiki</a></div>
					<div class="inside_text_bold"><a href="https://github.com/RIEI/Vistumbler">Git Repository</a></div>								
					<div class="inside_text_bold"><a href="https://sourceforge.net/projects/vistumbler/">Sourceforge Page</a></div>
					<div class="inside_text_bold"><a href="downloads.html">Downloads</a></div>
					<div class="inside_text_bold"><a href="verhist.html">Version History</a></div>
					<div class="inside_text_bold"><a href="donate.htm">Donate</a></div>
					<div class="inside_dark_header">Other Projects</div>
					<div class="inside_text_bold"><a href="http://uns.techidiots.net/">UNS Home</a></div>
					<div class="inside_text_bold"><a href="http://www.wifidb.net">WifiDB Home</a></div>
					<div class="inside_text_bold"><a href="http://mysticache.techidiots.net">Mysticache Home</a></div>
					<div class="inside_text_bold"><a href="http://www.techidiots.net/project-pages">TechIdiots Projects</a></div>
					<div class="inside_text_bold"><a href="http://www.techidiots.net/">TechIdiots.net</a></div>
                            </td>
                            <td class="cell_side_right">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="cell_bot_left">&nbsp;</td>
                            <td class="cell_bot_mid">&nbsp;</td>
                            <td class="cell_bot_right">&nbsp;</td>
                        </tr>
                    </table>
                </td>
                    <td style="height: 114px" valign="top" class="center">
                    <table style="width: 100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="width: 10px; height: 20px" class="cell_top_left">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                            <!-- ------ WiFiDB Login Bar ---- -->
                            <td class="cell_top_mid" style="height: 20px" align="left">
                                
                            </td>
                            <td class="cell_top_mid" style="height: 20px" align="right">
                                
                            </td>
                            <!-- ---------------------------- -->
                            <td style="width: 10px" class="cell_top_right">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                        </tr>
                        <tr>
                            <td class="cell_side_left">&nbsp;</td>
                            <td class="cell_color_centered" align="center" colspan="2">
                                <div align="center">

<h2>Showing the Last <?php echo $view; ?> Seconds worth of APs.</h2>
<table border="1" width="100%" cellspacing="0">
    <tr class="style4">
        <td>
            Select Window of time to view:
        </td>
        <td>
            <a href="?sort=<?php echo $sort; ?>&ord=<?php echo $ord; ?>&from=<?php echo $from; ?>&to=<?php echo $to; ?>&view=1800">30 Minutes</a>
        </td>
        <td>
            <a href="?sort=<?php echo $sort; ?>&ord=<?php echo $ord; ?>&from=<?php echo $from; ?>&to=<?php echo $to; ?>&view=3600">60 Minutes</a>
        </td>
        <td>
            <a href="?sort=<?php echo $sort; ?>&ord=<?php echo $ord; ?>&from=<?php echo $from; ?>&to=<?php echo $to; ?>&view=7200">2 Hours</a>
        </td>
        <td>
            <a href="?sort=<?php echo $sort; ?>&ord=<?php echo $ord; ?>&from=<?php echo $from; ?>&to=<?php echo $to; ?>&view=21600">6 Hours</a>
        </td>
        <td>
            <a href="?sort=<?php echo $sort; ?>&ord=<?php echo $ord; ?>&from=<?php echo $from; ?>&to=<?php echo $to; ?>&view=86400">1 Day</a>
        </td>
        <td>
            <a href="?sort=<?php echo $sort; ?>&ord=<?php echo $ord; ?>&from=<?php echo $from; ?>&to=<?php echo $to; ?>&view=604800">1 Week</a>
        </td>
    </tr>
</table>
<table border="1" width="100%" cellspacing="0">
<tr class="style4">
    <td>Expand Graph</td>
    <td>Expand Map</td>
    <td>SSID<a href="?sort=SSID&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"border="0" src="../themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=SSID&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="../themes/<?php echo $theme; ?>/img/up.png"></a></td>
    <td>MAC<a href="?sort=mac&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="../themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=mac&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="../themes/<?php echo $theme; ?>/img/up.png"></a></td>
    <td>Chan<a href="?sort=chan&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="../themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=chan&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="../themes/<?php echo $theme; ?>/img/up.png"></a></td>
    <td>Radio Type<a href="?sort=radio&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0" src="../themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=radio&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="../themes/<?php echo $theme; ?>/img/up.png"></a></td>
    <td>Authentication<a href="?sort=auth&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0" src="../themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=auth&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="../themes/<?php echo $theme; ?>/img/up.png"></a></td>
    <td>Encryption<a href="?sort=encry&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0" src="../themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=encry&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="../themes/<?php echo $theme; ?>/img/up.png"></a></td>
    <td>First Seen<a href="?sort=fa&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0" src="../themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=fa&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="../themes/<?php echo $theme; ?>/img/up.png"></a></td>
    <td>Last Seen<a href="?sort=lu&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0" src="../themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=lu&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="../themes/<?php echo $theme; ?>/img/up.png"></a></td>
    <td>Username<a href="?sort=username&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0" src="../themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=username&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="../themes/<?php echo $theme; ?>/img/up.png"></a></td>
</tr>
<?php



$row_color = 0;
date_default_timezone_set('UTC');
$date = date("Y-m-d H:i:s.u", time()-$view);
$date_time = strtotime($date);
$sql = "SELECT id,ssid,mac,radio,chan,auth,encry,sectype,sig,fa,la,username,Label FROM `wifi`.`live_aps` WHERE la >= ? ORDER BY `$sort` $ord LIMIT $from, $inc";
$prep = $dbcore->sql->conn->prepare($sql);
$prep->bindParam(1, $date_time, PDO::PARAM_INT);
$prep->execute();
$aps = $prep->fetchAll();
$count=$prep->rowCount();
if($count != 0)
{
    $tablerowid = 0;
    foreach($aps as $array)
    {
        $tablerowid++;
        $tablerowid2 = $tablerowid+1;
        if($row_color == 1)
        {$row_color = 0; $color = "light";}
        else{$row_color = 1; $color = "dark";}
        $id = $array['id'];
        $ssid = $array['ssid'];
        $mac = $array['mac'];
        $mac_exp = str_split($mac,2);
        $mac = implode(":",$mac_exp);
        $chan = $array['chan'];
        $radio = $array['radio'];
        $auth = $array['auth'];
        $encry = $array['encry'];
        if($radio=="a")
        {$radio="802.11a";}
        elseif($radio=="b")
        {$radio="802.11b";}
        elseif($radio=="g")
        {$radio="802.11g";}
        elseif($radio=="n")
        {$radio="802.11n";}
        else
        {$radio="Unknown Radio";}
        $sig_exp = explode("|", $array['sig']);
        $maps_compile_a = array();
        $n=0;
        foreach($sig_exp as $sig)
        {
            $n++;
            $sig_e = explode("~", $sig);
            $gps_id = $sig_e[0];
            $sql = "SELECT * FROM `wifi`.`live_gps` WHERE `id` = ?";
			$prep2 = $dbcore->sql->conn->prepare($sql);
			$prep2->bindParam(1, $gps_id, PDO::PARAM_INT);
			$prep2->execute();
			$gps = $prep2->fetchAll();
            foreach($gps as $array_gps)
            {
                if(str_replace("N ", "", $array_gps['lat']) == "0000.0000"){continue;}
                if(str_replace("E ", "", $array_gps['long']) == "0000.0000"){continue;}
                $lat = dm2dd($array_gps['lat']);
                $long = dm2dd($array_gps['long']);
                $maps_compile_a[] = "
                               var myLatLng$n = new google.maps.LatLng($lat, $long);
                               var beachMarker$n = new google.maps.Marker({position: myLatLng$n, map: map, icon: image});";
			}
			
        }
        $maps_compile = implode("\r\n", $maps_compile_a)
        ?>
        <SCRIPT LANGUAGE="JavaScript">
            // Row Hide function.
            // by tcadieux
            function double_func<?php echo $tablerowid2; ?>(one, two)
            {
                expandcontract(one, two);
                initialize<?php echo $tablerowid2; ?>();
            }
        </SCRIPT>

            <tr class="<?php echo $color; ?>">
                <td align="center" onclick="expandcontract('<?php echo $tablerowid;?>','ClickIcon<?php echo $tablerowid;?>')" id="ClickIcon<?php echo $tablerowid;?>" style="cursor: pointer; cursor: hand;">+</td>
                <td align="center" onclick="double_func<?php echo $tablerowid2; ?>('<?php echo $tablerowid2;?>','ClickIcon<?php echo $tablerowid2;?>')" id="ClickIcon<?php echo $tablerowid2;?>" style="cursor: pointer; cursor: hand;">+</td>
                <td align="center"><a class="links" href="liveap.php?out=html&id=<?php echo $id; ?>"><?php echo $ssid; ?></a></td>
                <td align="center"><?php echo $mac; ?></td>
                <td align="center"><?php echo $chan; ?></td>
                <td align="center"><?php echo $radio; ?></td>
                <td align="center"><?php echo $auth; ?></td>
                <td align="center"><?php echo $encry; ?></td>
                <td align="center"><?php echo $array['fa']; ?></td>
                <td align="center"><?php echo $array['la']; ?></td>
                <td align="center"><?php echo $array['username']; ?></td>
            </tr>
            <tr>
                <tbody id="<?php echo $tablerowid;?>" style="display:none">
                    <td colspan="11">
                        <iframe width="100%" height="500px"src="liveap.php?out=img&id=<?php echo $id; ?>"></iframe>
                    </td>
                </tbody>
                <tbody id="<?php echo $tablerowid2;?>" style="display:none">
                    <td colspan="11">
                        <?php
                        if($lat != "")
                        {
                            ?>
                        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
                        <script type="text/javascript">
                            function initialize<?php echo $tablerowid2;?>() {
                                var myOptions = {
                                  zoom: 16,
                                  center:new google.maps.LatLng(<?php echo $lat; ?>, <?php echo $long; ?>),
                                  mapTypeId: google.maps.MapTypeId.ROADMAP
                                }
                                var map = new google.maps.Map(document.getElementById("map_canvas<?php echo $tablerowid2;?>"), myOptions);
                                <?php

                                switch($array['sectype'])
                                {
                                    case 1:
                                        echo "var image = 'http://vistumbler.sourceforge.net/images/program-images/open.png';";
                                        break;
                                    case 2:
                                        echo "var image = 'http://vistumbler.sourceforge.net/images/program-images/secure-wep.png';";
                                        break;
                                    case 3:
                                        echo "var image = 'http://vistumbler.sourceforge.net/images/program-images/secure.png';";
                                        break;
                                }
                                echo $maps_compile;
                                ?>
                            }
                        </script>
                        <?php
                        }
                        ?>
                    <div style="width:100%;height:500px;" id="map_canvas<?php echo $tablerowid2;?>">
                        <?php
                        if($no_gps)
                        {
                            ?>
                        <h2>There is no valid GPS for this AP, so Maps has been disabled.</h2>
                            <?php
                        }
                        ?>
                    </div>
                    </td>
                </tbody>
            </tr>
        <?php
        $tablerowid = $tablerowid2;
        #break;
    }
}else
{
    	?>
            <tr>
                    <td align="center" colspan="11">
                            <b>There are no Access Points imported as of yet, go grab some with Vistumbler and import them.<br />
                            Come on... you know you want too.</b>
                    </td>
            </tr>
	<?php
}
?></table>
</div>
                                <br>
                            </td>
                            <td class="cell_side_right">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="cell_side_left">&nbsp;</td>
                            <td colspan="2" class="cell_color_centered"></td>
                            <td class="cell_side_right">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="cell_bot_left">&nbsp;</td>
                            <td class="cell_bot_mid" colspan="2" align="center">&nbsp;</td>
                            <td class="cell_bot_right">&nbsp;</td>
                        </tr>
                    </table>
                    <div class="inside_text_center" align=center>
                        <strong>
                            Random Intervals Wireless DataBase  *Alpha* 0.30 Build 1 *Pre-Release*  <br />
                        </strong>
                    </div>
                    <br />
                </td>
            </tr>
        </table>
    </body>
</html>

<?php
function dm2dd($geocord_in = "")
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
            $latlonleft = substr($latlon_exp[0], 0, -2);
            $latlonright = ((float)(substr($latlon_exp[0], (strlen($latlon_exp[0])-2)) . '.' . $latlon_exp[1])) / 60;
            $return = $sign.number_format($latlonleft + $latlonright , 7);
            
        }
        
        #echo "dm2dd out\r\n";
        #var_dump($return);
        return $return;
    }
?>