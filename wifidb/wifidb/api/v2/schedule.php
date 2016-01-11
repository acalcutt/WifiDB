<?php
/*
schedule.php
Copyright (C) 2016 Phil Ferland

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
define("SWITCH_EXTRAS", "apiv2");

include('../../lib/init.inc.php');

$dbcore->Func     = (@$_REQUEST['func'] ? $_REQUEST['func'] : "");
$dbcore->StartDate     = (@$_REQUEST['StartDate'] ? $_REQUEST['StartDate'] : "");
$dbcore->EndDaate      = (@$_REQUEST['EndDate'] ? $_REQUEST['EndDate'] : "");
if($dbcore->Func === ""){ die("Try feeding me some good bits."); }
if(($dbcore->StartDate === "") OR ($dbcore->EndDate === ""))
{
    $dbcore->AllDateRange = 1;
}else
{
    $dbcore->AllDateRange = 0;
}

switch(strtolower($dbcore->Func))
{
    case "waiting":
        $result = $dbcore->GetWaitingScheduleTable();
        break;

    case "importing":
        $result = $dbcore->GetImpotingScheduleTable();
        break;

    case "finished":
        $result = $dbcore->GetFinishedScheduleTable();
        break;

    case "bad":
        $result = $dbcore->GetBadScheduleTable();
        break;

    default:
        $result = -1;
        break;
}
if($result <= 0)
{
    $dbcore->Output();#array("error"=>"Error in API;"));
}
else
{
    $dbcore->Output();
}