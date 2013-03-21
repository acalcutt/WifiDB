<?php
#Database.inc.php, holds the database interactive functions.
#Copyright (C) 2011 Phil Ferland
#
#This program is free software; you can redistribute it and/or modify it under the terms
#of the GNU General Public License as published by the Free Software Foundation; either
#version 2 of the License, or (at your option) any later version.
#
#This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
#without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
#See the GNU General Public License for more details.
#
#You should have received a copy of the GNU General Public License along with this program;
#if not, write to the
#
#   Free Software Foundation, Inc.,
#   59 Temple Place, Suite 330,
#   Boston, MA 02111-1307 USA


#===============================================#
#   Header (writes the Headers for all pages)   #
#===============================================#

function htmlheader($dbcore)
{
    if(@WIFIDB_INSTALL_FLAG != "installing" && $dbcore->sec->logged_in_flag)
    {
        $login_bar = 'Welcome, <a class="links" href="'.$dbcore->URL_PATH.'cp/">'.$dbcore->sec->username.'</a><font size="1"> (Last Logon: '.$dbcore->sec->last_login.')</font>';
    }else
    {
        $wifidb_mysticache_link = "";
        $login_bar = "";
    }
    $dbcore->install_header = $dbcore->check_install_folder($dbcore);
    $dbcore->mysticache = $wifidb_mysticache_link;
    $dbcore->login_bar = $login_bar;
    return 1;
}


#===============================================#
#   Footer (writes the footer for all pages)    #
#===============================================#
function htmlfooter($dbcore)
{
    $out = '';
    if($dbcore->sec->logged_in_flag)
    {
        if($dbcore->sec->privs >= 1000)
        {
            $out .= '<a class="links" href="'.$dbcore->URL_PATH.'/cp/?func=admin_cp">Admin Control Panel</a>  |-|  ';
        }
        if($dbcore->sec->privs >= 10)
        {
            $out .= '<a class="links" href="'.$dbcore->URL_PATH.'/cp/?func=mod_cp">Moderator Control Panel</a>  |-|  ';
        }
        if($dbcore->sec->privs >= 1)
        {
            $out .= '<a class="links" href="'.$dbcore->URL_PATH.'/cp/">User Control Panel</a>';
        }

    }
    $out .= $dbcore->meta->tracker.$dbcore->meta->ads;
    return $out;
}
?>