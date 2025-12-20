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

function header_theme()
{
    global $login_check;
    include_once($GLOBALS['half_path'].'/lib/database.inc.php');
    include_once($GLOBALS['half_path'].'/lib/config.inc.php');
    $head		= 	$GLOBALS['header'];
    $half_path	=	$GLOBALS['half_path'];
    if(!$install)
    {
        include_once($GLOBALS['half_path'].'/lib/security.inc.php');
        $sec = new security();
        $login_check = $sec->login_check();
        if(is_array($login_check) or $login_check == "No Cookie"){$login_check = 0;}
    }else
    {
        $login_check = 0;
    }
    if($output == "detailed")
    {
        check_install_folder();
        if($GLOBALS['login_check'])
        {
            $wifidb_mysticache_link = '<div class="inside_text_bold"><a class="links" href="/cp/?func=boeyes&boeye_func=list_all&sort=id&ord=ASC&from=0&to=100">List All My Caches</a></div>';
            login_bar("vistumbler");
        }
    }
}


#===============================================#
#   Footer (writes the footer for all pages)    #
#===============================================#

function footer()
{
    if(@$GLOBALS['login_check'])
    {
        $privs = $GLOBALS['privs'];
        $priv_name = $GLOBALS['priv_name'];
        $out = '';
        if($privs >= 1000)
        {
            $out .= '<a class="links" href="'.$GLOBALS['UPATH'].'/cp/?func=admin_cp">Admin Control Panel</a>  |-|  ';
        }
        if($privs >= 10)
        {
            $out .= '<a class="links" href="'.$GLOBALS['UPATH'].'/cp/?func=mod_cp">Moderator Control Panel</a>  |-|  ';
        }
        if($privs >= 1)
        {
            $out .= '<a class="links" href="'.$GLOBALS['UPATH'].'/cp/">User Control Panel</a>';
        }

    }
    $out .= $GLOBALS['tracker'].$GLOBALS['ads'];
    return $out;
}
?>
