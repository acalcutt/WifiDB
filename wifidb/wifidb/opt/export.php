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
define("SWITCH_EXTRAS", "export");

include('../lib/init.inc.php');
$dbcore->smarty->assign('wifidb_page_label', 'Export Page');

$func=$_GET['func'];

switch($func)
{
        #--------------------------
        case "exp_user_all_kml":
            $user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
            $results = $dbcore->export->UserAll($user);
            $dbcore->smarty->assign('results', $results);
            $dbcore->smarty->display('export_results.tpl');
            break;
        #--------------------------
        case "exp_user_list":
            $row = (int)($_REQUEST['row'] ? $_REQUEST['row']: 0);
            $result = $dbcore->export->UserList($row);
            $dbcore->smarty->assign('results', $result);
            $dbcore->smarty->display('export_results.tpl');
            break;
        #--------------------------
        default:
            $imports = array();
            $usernames = array();
            $sql = "SELECT `id`,`title`, `username`, `aps`, `date` FROM `wifi`.`user_imports`";
            $result = $dbcore->sql->conn->query($sql);
            while($user_array = $result->fetch(2))
            {
                $imports[] = array(
                                "id"=>$user_array["id"],
                                "username"=>$user_array["username"],
                                "title"=>$user_array["title"],
                                "aps"=>$user_array["aps"],
                                "date"=>$user_array["date"]
                             );
            }
            
            $sql = "SELECT `username` FROM `wifi`.`user_imports`";
            $result = $dbcore->sql->conn->query($sql);
            while($user_array = $result->fetch(2))
            {
                $usernames[] = $user_array["username"];
            }
            $usernames = array_unique($usernames);

            $dbcore->smarty->assign('wifidb_export_imports_all', $imports);
            $dbcore->smarty->assign('wifidb_export_users_all', $usernames);
            $dbcore->smarty->display('export_index.tpl');
        break;
}