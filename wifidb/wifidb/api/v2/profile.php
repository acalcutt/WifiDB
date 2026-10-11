<?php
/*
/api/v2/profile.php
Copyright (C) 2026 Andrew Calcutt

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
#The signed-in user's profile: totals, imports and their files still in the queue (see README.md).
#POST (or GET) username + apikey; optional from/inc page the imports list (inc at most 100).
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "apiv2");

include('../../lib/init.inc.php');

$from = (isset($_REQUEST['from']) ? (int) $_REQUEST['from'] : 0);
$inc  = (isset($_REQUEST['inc'])  ? (int) $_REQUEST['inc']  : 25);
$dbcore->GetUserProfile($from, $inc);
$dbcore->Output();
