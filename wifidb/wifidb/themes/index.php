<?php
/*
themes/index.php
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

$func = '';
$theme_post = '';

$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_STRING);
if($func == 'change')
{
    $theme_post = @filter_input(INPUT_POST, 'theme', FILTER_SANITIZE_STRING);
    $cookie_path = (@$dbcore->root != '' ? '/'.$dbcore->root : '/');
    setcookie('wifidb_theme', $theme_post, (time()+(86400 * 7)), $cookie_path, $dbcore->sec->domain, $dbcore->sec->ssl);// 86400 = 1 day * 7 (for one week)
    //echo "'wifidb_theme' , $theme_post , (time()+(86400 * 7)), $cookie_path\r\n";
    header('Location: ?');
}
$themes_array = array();
$dh = opendir(".") or die("couldn't open directory");
while (($file = readdir($dh)) == true)
{
    if (!is_file($file))
    {
        if($file == '.'){continue;}
        if($file == '..'){continue;}
        if($file == '.svn'){continue;}
        if($file == 'index.php'){continue;}
        if($file == 'theme.txt'){continue;}
        if($file == 'themes_template.php'){continue;}
        $themes_array[] = $file;
    }
}

$dbcore->smarty->assign("wifidb_page_label", "Themes Chooser Page");
$dbcore->smarty->assign("wifidb_themes_all", $themes_array);

$dbcore->smarty->display('themes_chooser.tpl');