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

You should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");

include('../lib/init.inc.php');

$theme = filter_input(INPUT_GET, 'theme', FILTER_SANITIZE_STRING);
$theme_tn = $theme."/thumbnail.PNG";
$theme_ss = $theme."/screenshot.PNG";
$theme_details = file($theme.'/details.txt');

$author_exp = explode(":", $theme_details[0]);
$site_exp = explode(":", $theme_details[1]);
$version = explode(":", $theme_details[2]);
$date = explode(":", $theme_details[3]);
$details = "";
$count = count($theme_details)-1;
foreach($theme_details as $key=>$detail)
{
    if($key < 5){continue;}
    $details .= $detail;
}

$dbcore->smarty->assign('theme', $theme);
$dbcore->smarty->assign('theme_image_url', $theme_ss);
$dbcore->smarty->assign('theme_tn', $theme_tn);
$dbcore->smarty->assign('theme_author', $author_exp[1]);
$dbcore->smarty->assign('author_url', $site_exp[1]);
$dbcore->smarty->assign('theme_ver', $version[1]);
$dbcore->smarty->assign('author_date', $date[1]);
$dbcore->smarty->assign('theme_details', str_replace("\r\n", "<br />", $details));

$dbcore->smarty->display('themes_template.tpl'); 
?>