<?php
/*
fetch.php, fetches a single AP's details.
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
define("SWITCH_EXTRAS", "fed");

include('../lib/init.inc.php');

switch(strtolower($_REQUEST['func']))
{
    case "listdatatypes":
        $dbcore->smarty->assign('FedServerId',  (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->display('FedServerDataTypes.tpl');
        break;

    case "listusers":
        $dbcore->federation->SelectFedServer( (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->assign('FedUsers', $dbcore->federation->GetFedUsers() );
        $dbcore->smarty->display('FedUsersIndex.tpl');
        break;

    case "userdata":
        $dbcore->federation->SelectFedServer( (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->assign('FedUsers', $dbcore->federation->GetUserData((int)$_REQUEST['UserID']) );
        $dbcore->smarty->display('FedUserData.tpl');
        break;

    case "searchusers":
        $dbcore->federation->SelectFedServer( (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->display('FedUsersSearchIndex.tpl');
        break;

    case "searchusersresult":
        $dbcore->federation->SelectFedServer( (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->assign('FedUsers', $dbcore->federation->SearchUsers($_REQUEST['SearchValue']) );
        $dbcore->smarty->display('FedUsersSearchResult.tpl');
        break;

    ####################################
    ####################################
    case "listimports":
        $dbcore->federation->SelectFedServer( (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->assign('FedImports', $dbcore->federation->GetFedUserImports() );
        $dbcore->smarty->display('FedImportsIndex.tpl');
        break;

    case "importdata":
        $dbcore->federation->SelectFedServer( (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->assign('FedImportData', $dbcore->federation->GetFedImportData($_REQUEST['ImportID']) );
        $dbcore->smarty->display('FedImportData.tpl');
        break;

    case "searchimports":
        $dbcore->federation->SelectFedServer( (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->display('FedImportSearchIndex.tpl');
        break;

    case "searchimportsresult":
        $dbcore->federation->SelectFedServer( (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->assign('FedImportSearchResults', $dbcore->federation->SearchImports($_REQUEST['title'], $_REQUEST['user'], $_REQUEST['min_ap'], $_REQUEST['max_ap'], $_REQUEST['min_gps'], $_REQUEST['max_gps'], $_REQUEST['min_date'], $_REQUEST['max_date'] ) );
        $dbcore->smarty->display('FedImportsSearchResult.tpl');
        break;

    ####################################
    ####################################
    case "listaps":
        $dbcore->federation->SelectFedServer( (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->assign('FedApList', $dbcore->federation->GetAPs() );
        $dbcore->smarty->display('FedAPsIndex.tpl');
        break;

    case "apdata":
        $dbcore->federation->SelectFedServer( (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->assign('FedApData', $dbcore->federation->GetApData() );
        $dbcore->smarty->display('FedAPData.tpl');
        break;

    case "searchaps":
        $dbcore->federation->SelectFedServer( (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->display('FedAPSearchIndex.tpl');
        break;

    case "searchapresult":
        $dbcore->federation->SelectFedServer( (int)$_REQUEST['FedServerID']);
        $dbcore->smarty->assign('FedServerID', $dbcore->federation->FedServerID);
        $dbcore->smarty->assign('FedAPSearchResults', $dbcore->federation->SearchAPs() );
        $dbcore->smarty->display('FedAPSearchResult.tpl');
        break;

    ####################################
    ####################################
    default:
        $dbcore->smarty->assign('FedServers', $dbcore->federation->GetFedServersList() );
        $dbcore->smarty->display('FedServersIndex.tpl');
        break;
}