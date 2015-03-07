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

require '../lib/init.inc.php';

$func    =   filter_input(INPUT_GET, 'func', FILTER_SANITIZE_STRING);

if(@$_REQUEST['ssid'] === "%" or @$_REQUEST['mac'] === "%" or @$_REQUEST['radio'] === "%" or @$_REQUEST['chan'] === "%" or @$_REQUEST['auth'] === "%" or @$_REQUEST['encry'] === "%" )
{
    $dbcore->mesg = 'Come on man, you cant search for all of something, thats what <a class="links" href="../all.php">this page</a> is for!';
    $dbcore->smarty->assign('mesg', $dbcore->mesg);
    $dbcore->smarty->display('search_results.tpl');
}else
{
    $ord    =   filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
    $sort   =	filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
    $from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
    $inc    =	filter_input(INPUT_GET, 'to', FILTER_SANITIZE_NUMBER_INT);
    
    if(@$_REQUEST['ssid'])
    {
        $ssid   =   $_REQUEST['ssid'];
    }else
    {
        $ssid   =   "";
    }
    
    if(@$_REQUEST['mac'])
    {
        $mac    =   $_REQUEST['mac'];
    }else
    {
        $mac    =   "";
    }
    
    if(@$_REQUEST['radio'])
    {
        $radio  =   $_REQUEST['radio'];
    }else
    {
        $radio  =   "";
    }
    
    if(@$_REQUEST['chan'])
    {
        $chan   =   $_REQUEST['chan'];
    }else
    {
        $chan   =   "";
    }
    
    if(@$_REQUEST['auth'])
    {
        $auth   =   $_REQUEST['auth'];
    }else
    {
        $auth   =   "";
    }
    
    if(@$_REQUEST['encry'])
    {
        $encry  =   $_REQUEST['encry'];
    }else
    {
        $encry  =   "";
    }
    if ($from == ""){$from = 0;}
    if ($inc == ""){$inc = 100;}
    if ($ord == ""){$ord = "ASC";}
    if ($sort == ""){$sort = "ssid";}
    $to = ($from+$inc);
    
    list($total_rows, $results_all, $save_url, $export_url) = $dbcore->Search($ssid, $mac, $radio, $chan, $auth, $encry, $ord, $sort, $from, $inc);
    if($total_rows === 0)
    {
        $dbcore->smarty->assign('mesg', 'There where no results, please <a class="links" href="search.php" title="Search for Access Points">try again</a>');
    }
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
	$dbcore->GeneratePages($total_rows, $from, $inc, $sort, $ord, "", "", $ssid, $mac, $chan, $radio, $auth, $encry);
	$dbcore->smarty->assign('page_list', $dbcore->pages_together);
	$dbcore->smarty->assign('results_all', $results_all);
	$dbcore->smarty->display('search_results.tpl');
}
?>
