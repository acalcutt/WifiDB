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

$func    =   filter_input(INPUT_GET, 'func', FILTER_SANITIZE_STRING);
global $switches;
if($func)
{
    $switches = array('screen'=>"HTML", 'extras'=>'export');
}else
{
    $switches = array('screen'=>"HTML", 'extras'=>'');
}

require '../lib/init.inc.php';

$mesg = "";
$sql_a=array();

if (@$_POST['ssid'] === "%" or @$_POST['mac'] === "%" or @$_POST['radio'] === "%" or @$_POST['chan'] === "%" or @$_POST['auth'] === "%" or @$_POST['encry'] === "%" )
{
    $mesg .= 'Come on man, you cant search for all of something, thats what <a class="links" href="../all.php">this page</a> is for!';
}else
{
    $ord    =   filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
    $sort   =	filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
    $from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
    $inc    =	filter_input(INPUT_GET, 'to', FILTER_SANITIZE_NUMBER_INT);
    
    if(!@$_GET['ssid'])
    {
        $ssid   =   filter_input(INPUT_POST, 'ssid', FILTER_SANITIZE_STRING);
    }else
    {
        $ssid   =   filter_input(INPUT_GET, 'ssid', FILTER_SANITIZE_STRING);
    }
    
    if(!@$_GET['mac'])
    {
        $mac    =   filter_input(INPUT_POST, 'mac', FILTER_SANITIZE_STRING);
    }else
    {
        $mac    =   filter_input(INPUT_GET, 'mac', FILTER_SANITIZE_STRING);
    }
    
    if(!@$_GET['radio'])
    {
        $radio  =   filter_input(INPUT_POST, 'radio', FILTER_SANITIZE_STRING);
    }else
    {
        $radio  =   filter_input(INPUT_GET, 'radio', FILTER_SANITIZE_STRING);
    }
    
    if(!@$_GET['chan'])
    {
        $chan   =   filter_input(INPUT_POST, 'chan', FILTER_SANITIZE_NUMBER_INT);
    }else
    {
        $chan   =   filter_input(INPUT_GET, 'chan', FILTER_SANITIZE_NUMBER_INT);
    }
    
    if(!@$_GET['auth'])
    {
        $auth   =   filter_input(INPUT_POST, 'auth', FILTER_SANITIZE_STRING);
    }else
    {
        $auth   =   filter_input(INPUT_GET, 'auth', FILTER_SANITIZE_STRING);
    }
    
    if(!@$_GET['encry'])
    {
        $encry  =   filter_input(INPUT_POST, 'encry', FILTER_SANITIZE_STRING);
    }else
    {
        $encry  =   filter_input(INPUT_GET, 'encry', FILTER_SANITIZE_STRING);
    }
    if ($from==""){$from=0;}
    if ($inc==""){$inc=100;}
    if ($ord==""){$ord="ASC";}
    if ($sort==""){$sort="ssid";}
    
    $to=$from+$inc;
    
    $save_url = 'ord='.$ord.'&sort='.$sort.'&from='.$from.'&to='.$inc;
    $export_url = '';
    if($ssid!='')
    {
        $save_url   .= '&ssid='.$ssid;
        $export_url .= '&ssid='.$ssid;
        $sql_a[]    =  " `ssid` like '".$ssid."%' ";
        $args[]     =  $ssid;
    }
    
    if($mac!='')
    {
        $save_url   .= '&mac='.$mac;
        $export_url .= '&mac='.$mac;
        $sql_a[]    =  " `mac` like '".$mac."%' ";
        $args[]     =  $mac;
    }
    
    if($radio!='')
    {
        $save_url   .= '&radio='.$radio;
        $export_url .= '&radio='.$radio;
        $sql_a[]    =  " `radio` like '".$radio."%' ";
        $args[]     =  $radio;
    }
    
    if($chan!='')
    {
        $save_url   .= '&chan='.$chan;
        $export_url .= '&chan='.$chan;
        $sql_a[]    =  " `chan` like '".$chan."%' ";
        $args[]     =  $chan;
    }
    
    if($auth!='')
    {
        $save_url   .= '&auth='.$auth;
        $export_url .= 'auth='.$auth.'&';
        $sql_a[]    =  " `auth` like '".$auth."%' ";
        $args[]     =  $auth;
    }
    
    if($encry!='')
    {
        $save_url   .= '&encry='.$encry;
        $export_url .= '&encry='.$encry;
        $sql_a[]    =  " `encry` like '".$encry."%' ";
        $args[]     =  $encry;
    }
    
    if(!$sql_a)
    {
        $mesg .= '<h2>There where no results, please try again<br>
            <A class="links" HREF="javascript:history.go(-1)">Go back</a> and do it right!</h2>';
    }else
    {
        if($func == "export")
        {
            $database = new database();
            $database->exp_search($sql_a);
        }else
        {
            $sql = "SELECT * FROM `{$dbcore->sql->db}`.`{$dbcore->sql->pointers_table}` WHERE " . implode(' AND ', $sql_a) ." ORDER BY `{$sort}` {$ord} LIMIT {$from}, {$inc}";
            $result = $dbcore->sql->conn->query($sql);

            $sql = "SELECT * FROM `{$dbcore->sql->db}`.`{$dbcore->sql->pointers_table}` WHERE " . implode(' AND ', $sql_a) ." ORDER BY `{$sort}` {$ord}";
            $result1 = $dbcore->sql->conn->query($sql);

            $total_rows = $result1->rowCount();

            if($total_rows === 0)
            {
                $mesg .= 'There where no results, please try again';
            }else
            {
                $row_color = 0;
                $results_all = array();
                $i=0;
                while ($newArray = $result->fetch(2))
                {
                    if($row_color == 1)
                    {
                        $row_color = 0;
                        $results_all[$i]['class'] = "light";    
                    }
                    else{
                        $row_color = 1;
                        $results_all[$i]['class'] = "dark";
                    }
                    
                    $results_all[$i]['id'] = $newArray['id'];
                    $results_all[$i]['ssid'] = $newArray['ssid'];
                    $results_all[$i]['mac'] = $newArray['mac'];
                    $results_all[$i]['chan'] = $newArray['chan'];
                    $results_all[$i]['auth'] = $newArray['auth'];
                    $results_all[$i]['encry'] = $newArray['encry'];
                    if($newArray['radio']=="a")
                    {
                        $results_all[$i]['radio']="802.11a";
                    }
                    elseif($newArray['radio']=="b")
                    {
                        $results_all[$i]['radio']="802.11b";
                    }
                    elseif($newArray['radio']=="g")
                    {
                        $results_all[$i]['radio']="802.11g";
                    }
                    elseif($newArray['radio']=="n")
                    {
                        $results_all[$i]['radio']="802.11n";
                    }
                    else
                    {
                        $results_all[$i]['radio']="Unknown Radio";
                    }
                    $i++;
                }
            }
        }
    }
}

##---------------------------------------------##
$dbcore->gen_pages($total_rows, $from, $inc, $sort, $ord, $ssid, $mac, $chan, $radio, $auth, $encry);

##---------------------------------------------##

$dbcore->smarty->assign('wifidb_page_label', 'Search Results Page');

$dbcore->smarty->assign('total_rows', $total_rows);

$dbcore->smarty->assign('to', $to);
$dbcore->smarty->assign('from', $from);

$dbcore->smarty->assign('ssid_search', $ssid);
$dbcore->smarty->assign('mac_search', $mac);
$dbcore->smarty->assign('radio_search', $radio);
$dbcore->smarty->assign('chan_search', $chan);
$dbcore->smarty->assign('auth_search', $auth);
$dbcore->smarty->assign('encry_search', $encry);

$dbcore->smarty->assign('save_url', $save_url);
$dbcore->smarty->assign('export_url', $export_url);
$dbcore->smarty->assign('page_list', $dbcore->pages_together);
$dbcore->smarty->assign('results_all', $results_all);

$dbcore->smarty->display('search_results.tpl');
?>