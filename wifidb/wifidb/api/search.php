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
define("SWITCH_EXTRAS", "api");

require '../lib/init.inc.php';

if(@$_REQUEST['ssid'] == "%" || @$_REQUEST['mac'] == "%" || @$_REQUEST['radio'] == "%" || @$_REQUEST['chan'] == "%" || @$_REQUEST['auth'] == "%" || @$_REQUEST['encry'] == "%" )
{
    $dbcore->mesg = 'Come on man, you can`t wildcard search for all of something, be more specific...';
    $dbcore->Output();
}else
{
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
    
    $dbcore->search($ssid, $mac, $radio, $chan, $auth, $encry);
    $dbcore->Output();
}
?>
