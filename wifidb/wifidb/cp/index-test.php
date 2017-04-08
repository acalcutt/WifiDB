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

global $switches;
$switches = array('screen'=>"HTML",'extras'=>'');
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "cp");

include('../lib/init.inc.php');
$dbcore->smarty->assign('wifidb_page_label', 'Live Page');

#$theme = $GLOBALS['theme'];
$theme = "vistumbler";
$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_SPECIAL_CHARS);

list($cookie_pass, $username) = explode(':', base64_decode($_COOKIE['WiFiDB_login_yes'], 1));
#echo $username;
#echo $cookie_pass;

#Check if user is logged in
$sql0 = "SELECT * FROM `wifi`.`user_login_hashes` WHERE `username` = ?";
$result = $dbcore->sql->conn->prepare($sql0);
$result->bindParam(1, $username, PDO::PARAM_STR);
$result->execute();
$user_logons = $result->fetchAll();
$login_check = 0;
foreach($user_logons as $logon)
{
	$db_pass = $logon['hash'];
	if(crypt($cookie_pass, $db_pass) == $db_pass)
	{
		$login_check = 1;
	}
}

#echo $login_check;

if($login_check)
{
	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">

	<head>
	<meta http-equiv="Content-Language" content="en-us" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Untitled 2</title>
	<style type="text/css">
	.style1 {
					text-align: center;
					background-color: #5C768B;
	}
	.style2 {
					background-color: #6F899F;
	}
	.style3 {
					color: #DBE3F0;
					font-weight: bold;
	}
	.style4 {
					background-color: #DBE3F0;
	}
	</style>
	</head>

	<body style="background-color: #145285">

	<table style="width: 80%" align="center" BORDER=0 CELLPADDING=0 CELLSPACING=0>
					<tr>
									<td class="style1"><strong>User Control Panel</strong></td>
					</tr>
					<tr>
									<td class="style2">
										<a href="../"><span class="style3">WifiDB Home</span></a> 
										<span class="style3"> | </span>
										<a href="?func=profile"><span class="style3">Profile</span></a> 
										<span class="style3"> | </span>
										<a href="?func=pref"><span class="style3">Email Preferences</span></a>
									</td>
					</tr>
					<tr>
									<td class="style4">
									<div>
	<?php
	switch($func)
	{
		##-------------##
		case '':
			header('Location: https://live.wifidb.net/wifidb/cp/index.php?func=profile');
		break;
		case 'profile':
			#pageheader("User Control Panel --> Profile");
			
			$sql0 = "SELECT * FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
			$result = $dbcore->sql->conn->prepare($sql0);
			$result->bindParam(1, $username, PDO::PARAM_STR);
			$result->execute();
			$user = $result->fetch();
				#user_panel_bar("prof", 0);
				?><tr>
						<td colspan="6" class="style4">
						<form method="post" action="?func=update_user_profile">
						<table  BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 100%">
							<tr>
								<th width="30%" class="style4">Email</th>
								<td class="light"><input type="text" name="email" size="75%" value="<?php echo $user['email'];?>"> Hide? <input name="h_email" type="checkbox" <?php if($newArray['h_email']){echo 'checked';}?>></td>
							</tr>
							<tr>
								<th width="30%" class="style4">Website</th>
								<td class="light"><input type="text" name="website" size="75%" value="<?php echo $user['website'];?>"></td>
							</tr>
							<tr>
								<th width="30%" class="style4">Vistumbler Version</th>
								<td class="light"><input type="text" name="Vis_ver" size="75%" value="<?php echo $user['Vis_ver'];?>"></td>
							</tr>
							<tr class="style4">
								<td colspan="2">
									<p align="center">
										<input type="hidden" name="username" value="<?php echo $user['username'];?>">
										<input type="hidden" name="user_id" value="<?php echo $user['id'];?>">
										<input type="submit" value="Update Me!">
									</p>
								</td>
							</tr>
						</table>
						</form>
						</td>
					</tr>
				</table>
				<?php
			#footer($_SERVER['SCRIPT_FILENAME']);
		break;
		case "update_user_profile":
			#pageheader("User Control Panel --> Profile");
			#user_panel_bar("prof", 0);
			?><tr>
					<td colspan="6" class="dark" align='center'>
			<?php
			$username = addslashes(strtolower($_POST['username']));
			$user_id = addslashes(strtolower($_POST['user_id']));
				$email = htmlentities(addslashes($_POST['email']),ENT_QUOTES);
			$h_email = addslashes($_POST['h_email']);
			if($h_email == "on"){$h_email = 1;}else{$h_email = 0;}
				$website = htmlentities(addslashes($_POST['website']),ENT_QUOTES);
			$Vis_ver = htmlentities(addslashes($_POST['Vis_ver']),ENT_QUOTES);
				$sql0 = "SELECT `id` FROM `wifi`.`user_info` WHERE `username` = '$username' LIMIT 1";
			$result = $dbcore->sql->conn->prepare($sql0);
			$result->execute();
			$array = $result->fetch();
			if($array['id']+0 === $user_id+0)
			{
				$sql1 = "UPDATE `wifi`.`user_info` SET `email` = '$email', `h_email` = '$h_email', `website` = '$website', `Vis_ver` = '$Vis_ver' WHERE `id` = '$user_id' LIMIT 1";
				$result = $dbcore->sql->conn->prepare($sql1);
				if($result->execute())
				{
					echo "Updated user ($user_id) Profile\r\n<br>";
				}else
				{
					echo "There was a serious error: ".$result->errorInfo()."<br>";
					die(footer($_SERVER['SCRIPT_FILENAME']));
				}
				redirect_page('?func=profile', 2000, 'Update User Successful!');
			}else
			{
				Echo "User ID's did not match, there was an error, contact the support forums for more help";
			}
			?>
					</td>
				</tr>
			</table>
			<?php
			#footer($_SERVER['SCRIPT_FILENAME']);
		break;

		##-------------##
		case 'update_user_pref':
			#pageheader("User Control Panel --> Update Preferences");
			#user_panel_bar("pref", 0);
			$username = addslashes(strtolower($_POST['username']));
			$user_id = addslashes(strtolower($_POST['user_id']));
				$mail_updates = ((@$_POST['mail_updates']) == 'on' ? 1 : 0);
			$imports = ((@$_POST['imports']) == 'on' ? 1 : 0);
			$kmz = ((@$_POST['kmz']) == 'on' ? 1 : 0);
			$new_users = ((@$_POST['new_users']) == 'on' ? 1 : 0);
			$statistics = ((@$_POST['statistics']) == 'on' ? 1 : 0);
			$announcements = ((@$_POST['announcements']) == 'on' ? 1 : 0);
			$announce_comment = ((@$_POST['announce_comment']) == 'on' ? 1 : 0);
			$geonamed = ((@$_POST['geonamed']) == 'on' ? 1 : 0);
			$pub_geocache = ((@$_POST['pub_geocache']) == 'on' ? 1 : 0);
			$schedule = ((@$_POST['schedule']) == 'on' ? 1 : 0);
				$sql0 = "SELECT `id` FROM `wifi`.`user_info` WHERE `username` = '$username' LIMIT 1";
			echo $sql0;
			$result = $dbcore->sql->conn->prepare($sql0);
			$result->execute();
			$array = $result->fetch();
			if($array['id']+0 === $user_id+0)
			{
				$sql1 = "UPDATE `wifi`.`user_info` SET
															`mail_updates` = '$mail_updates',
															`schedule`	=	'$schedule',
															`imports` = '$imports',
															`kmz` = '$kmz',
															`new_users` = '$new_users',
															`statistics` = '$statistics',
															`announcements` = '$announcements',
															`announce_comment` = '$announce_comment',
															`geonamed` = '$geonamed',
															`pub_geocache` = '$pub_geocache'
															WHERE `id` = '$user_id'";
				echo $sql1;
				$result = $dbcore->sql->conn->prepare($sql1);
				if($result->execute())
				{
					echo "Updated $username ($user_id) Preferences\r\n<br>";
				}else
				{
					echo "There was a serious error: ".$result->errorInfo()."<br>";
					die();
				}
				redirect_page('?func=pref', 2000, 'Update User Preferences Successful!');
			}else
			{
				Echo "User ID's did not match, there was an error, contact the <a href='http://forum.techidiots.net/forum/viewforum.php?f=47'>support forums</a> for more help.";
			}
			#footer($_SERVER['SCRIPT_FILENAME']);
		break;


		##-------------##
		case 'pref':
			#pageheader("User Control Panel --> Preferences");
			$sql0 = "SELECT * FROM `wifi`.`user_info` WHERE `username` = '$username' LIMIT 1";
			$result = $dbcore->sql->conn->prepare($sql0);
			$result->execute();
			$newArray = $result->fetch();				
				#user_panel_bar("pref", 0);
			?><tr>
					<td colspan="6" class="style4">
					<form method="post" action="?func=update_user_pref">
					<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 100%">
						<tr>
							<th width="30%" class="style4">Email me about updates</th>
							<td align="center" class="dark"><input name="mail_updates" type="checkbox" <?php if($newArray['mail_updates']){echo 'checked';}?>></td>
						</tr>
						<tr>
							<td colspan='2'>
								<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 100%">
									<tr>
										<th width="30%" class="style4">Announcements</th>
										<td align="center" class="light"><input name="announcements" type="checkbox" <?php if($newArray['announcements']){echo 'checked';}?>></td></td>
									</tr>
									<tr>
										<th width="30%" class="style4">Announcement Comments</th>
										<td align="center" class="dark"><input name="announce_comment" type="checkbox" <?php if($newArray['announce_comment']){echo 'checked';}?>></td></td>
									</tr>
									<tr>
										<th width="30%" class="style4">New Public Geocaches</th>
										<td align="center" class="light"><input name="pub_geocache" type="checkbox" <?php if($newArray['pub_geocache']){echo 'checked';}?>></td></td>
									</tr>
									<tr>
										<th width="30%" class="style4">New Users</th>
										<td align="center" class="dark"><input name="new_users" type="checkbox" <?php if($newArray['new_users']){echo 'checked';}?>></td></td>
									</tr>
									<tr>
										<th width="30%" class="style4">Scheduled Import</th>
										<td align="center" class="light"><input name="schedule" type="checkbox" <?php if($newArray['schedule']){echo 'checked';}?>></td></td>
									</tr>
									<tr>
										<th width="30%" class="style4">Import Finished</th>
										<td align="center" class="dark"><input name="imports" type="checkbox" <?php if($newArray['imports']){echo 'checked';}?>></td></td>
									</tr>
									<tr>
										<th width="30%" class="style4">New Full DB KML</th>
										<td align="center" class="light"><input name="kmz" type="checkbox" <?php if($newArray['kmz']){echo 'checked';}?>></td></td>
									</tr>
									<tr>
										<th width="30%" class="style4">GeoNames Daemon</th>
										<td align="center" class="dark"><input name="geonamed" type="checkbox" <?php if($newArray['geonamed']){echo 'checked';}?>></td></td>
									</tr>
									<tr>
										<th width="30%" class="style4">Database Statistics Daemon</th>
										<td align="center" class="light"><input name="statistics" type="checkbox" <?php if($newArray['statistics']){echo 'checked';}?>></td></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<p align="center">
									<input type="hidden" name="username" value="<?php echo $newArray['username'];?>">
									<input type="hidden" name="user_id" value="<?php echo $newArray['id'];?>">
									<input type="submit" value="Update Me!">
								</p>
							</td>
						</tr>
					</table>
					</form>
					</td>
				</tr>
			</table>
			<?php
			#footer($_SERVER['SCRIPT_FILENAME']);
		break;
	}
	?>					</div>			
						</td>
					</tr>
	</table>

	</body>

	</html>
	<?php
}
else
{
	#redirect_page('/'.$root.'/', 2000, 'Not Logged in!');
	header('Location: https://live.wifidb.net/wifidb/login.php?return=/wifidb/cp/index.php'); 
}
?>