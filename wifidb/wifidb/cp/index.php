<?php
include('../lib/database.inc.php');

pageheader("Show all APs");
include('../lib/config.inc.php');
$theme = $GLOBALS['theme'];
$func = '';
$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_SPECIAL_CHARS);
if($GLOBALS['login_check'])
{
	switch($func)
	{
		##-------------##
		case 'profile':
			$sql0 = "SELECT * FROM `$db`.`$user_logins_table` WHERE `username` = '$username' LIMIT 1";
			$result = mysql_query($sql0, $conn);
			$newArray = mysql_fetch_array($result);
			#if(!$newArray['h_email'])
			#{
			$email = $newArray['email'];
			#}
			#else{$email = "Email is hidden";}
			$website = $newArray['website'];
			$Vis_ver = $newArray['Vis_ver'];
			
			?>
			<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
				<tr>
					<th class="style3"><a class="links" href="index.php">Overview</a></th>
					<th class="cp_select_coloum"><a class="links_s" href="index.php?func=profile">Profile</a></th>
					<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
					<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
					<th class="style3"><a class="links" href="index.php?func=boeyes">Mysticache</a></th>
					<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
				</tr>
				<tr>
					<td colspan="6">
					<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?func=update_user_profile">
					<table  BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 100%">
						<tr>
							<th width="30%" class="style3">Email</th>
							<td align="center" class="style2"><input type="text" name="email" size="<?php echo strlen($email)*2;?>px" value="<?php echo $email;?>"> Hide? <input name="h_email" type="checkbox" <?php if($newArray['h_email']){echo 'checked="checked"';}?> value="<?php echo $newArray['h_email'];?>"></td>
						</tr>
						<tr>
							<th width="30%" class="style3">Website</th>
							<td align="center" class="style2"><input type="text" name="website" size="<?php echo strlen($website)*1.5;?>px" value="<?php echo $website;?>"></td>
						</tr>
						<tr>
							<th width="30%" class="style3">Vistumbler Version</th>
							<td align="center" class="style2"><input type="text" name="Vis_ver" size="<?php echo strlen($Vis_ver)*1.5;?>px" value="<?php echo $Vis_ver;?>"></td>
						</tr>
						<tr>
							<td colspan="2"><p align="center"><input type="submit" value="Update Me!"></p></td>
						</tr>
					</table>
					</form>
					</td>
				</tr>
			</table>
			<?php
		break;
		
		
		
		##-------------##
		case 'update_user_pref':
		
		
		break;
		
		
		
		
		##-------------##
		case 'pref':
			?>
			<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
				<tr>
					<th class="style3"><a class="links" href="index.php">Overview</a></th>
					<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
					<th class="cp_select_coloum"><a class="links_s" href="index.php?func=pref">Preferences</a></th>
					<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
					<th class="style3"><a class="links" href="index.php?func=boeyes">Mysticache</a></th>
					<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
				</tr>
				<tr>
					<td colspan="6">
					<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?func=update_user_pref">
					<table  BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 100%">
						<tr>
							<th width="30%" class="style3">Email me about updates</th>
							<td align="center" class="style2"><input name="mail_updates" type="checkbox" <?php if($newArray['mail_updatesmail_updates']){echo 'checked="checked"';}?> value="<?php echo $newArray['email_updates'];?>"></td>
						</tr>
						<tr>
							<th width="30%" class="style3">Hide Login Status</th>
							<td align="center" class="style2"><input name="h_status" type="checkbox" <?php if($newArray['h_status']){echo 'checked="checked"';}?> value="<?php echo $newArray['h_status'];?>"></td>
						</tr>
						<tr>
							<td colspan="2"><p align="center"><input type="submit" value="Update Me!"></p></td>
						</tr>
					</table>
					</form>
					</td>
				</tr>
			</table>
			<?php
		break;
		
		
		##-------------##
		case 'permissions':
			?>
			<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
				<tr>
					<th class="style3"><a class="links" href="index.php">Overview</a></th>
					<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
					<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
					<th class="cp_select_coloum"><a class="links_s" href="index.php?func=permissions">Permissions</a></th>
					<th class="style3"><a class="links" href="index.php?func=boeyes">Mysticache</a></th>
					<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
				</tr>
				<tr>
					<td colspan="6">
					
					</td>
				</tr>
			</table>
			<?php
		break;
		
		
		##-------------##
		case 'boeyes':
			$boeye_func = '';
			$boeye_func = filter_input(INPUT_GET, 'boeye_func', FILTER_SANITIZE_SPECIAL_CHARS);
			switch($boeye_func)
			{
				case "fetch_wpt":
					
				break;
				
				case "list_all":
					$ord	=	addslashes($_GET['ord']);
					$sort	=	addslashes($_GET['sort']);
					$from	=	addslashes($_GET['from']);
					$from	=	$from+0;
					$from_	=	$from+0;
					$inc	=	addslashes($_GET['to']);
					$inc	=	$inc+0;
				#	echo $from."<br>";
					if ($from=="" or !is_int($from)){$from=0;}
					if ($from_=="" or !is_int($from_)){$from_=0;}
					if ($inc=="" or !is_int($inc)){$inc=100;}
					if ($_COOKIE['WiFiDB_page_limit']){$inc = $_COOKIE['WiFiDB_page_limit'];}else{$inc=100;}
					if ($ord=="" or !is_string($ord)){$ord="ASC";}
					if ($sort=="" or !is_string($sort)){$sort="id";}

					?>
					<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
						<tr>
							<th class="style3"><a class="links" href="index.php">Overview</a></th>
							<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
							<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
							<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
							<th class="cp_select_coloum"><a class="links" href="index.php?func=boeyes">Mysticache</a></th>
							<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
						</tr>
						<tr><td colspan="4"></td><td colspan="2" class="cp_select_coloum"><font size="2"><a class="links_s" href="?func=boeyes&boeye_func=list_all">List All</a> |-| <a class="links" href="?func=boeyes&boeye_func=import_switch">Import</a> (<a class="links" href="?func=boeyes&boeye_func=import_gpx">GPX</a>) (<a class="links" href="?func=boeyes&boeye_func=import_LOC">LOC</a>) (<a class="links" href="?func=boeyes&boeye_func=import_csv">CSV</a>)</font></td></tr>
						<tr>
							<td colspan="8">
							<table  BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 100%">
								<tr>
									<th class="style3">ID<a href="?func=boeyes&boeye_func=list_all&sort=id&ord=ASC&from=<?php echo $from;?>&to=<?php echo $inc;?>&token=<?php echo $_SESSION["token"];?>"><img height="15" width="15" border="0"border="0" src="<?php echo "/".$GLOBALS['root']."/";?>themes/<?php echo $theme;?>/img/down.png"></a><a href="?func=boeyes&boeye_func=list_all&sort=id&ord=DESC&from=<?php echo $from;?>&to=<?php echo $inc;?>&token=<?php echo $_SESSION["token"];?>"><img height="15" width="15" border="0"border="0" src="<?php echo "/".$GLOBALS['root']."/";?>themes/<?php echo $theme;?>/img/up.png"></a></th>
									<th class="style3">Name<a href="?func=boeyes&boeye_func=list_all&sort=name&ord=ASC&from=<?php echo $from;?>&to=<?php echo $inc;?>&token=<?php echo $_SESSION["token"];?>"><img height="15" width="15" border="0"border="0" src="<?php echo "/".$GLOBALS['root']."/";?>themes/<?php echo $theme;?>/img/down.png"></a><a href="?func=boeyes&boeye_func=list_all&sort=name&ord=DESC&from=<?php echo $from;?>&to=<?php echo $inc;?>&token=<?php echo $_SESSION["token"];?>"><img height="15" width="15" border="0"border="0" src="<?php echo "/".$GLOBALS['root']."/";?>themes/<?php echo $theme;?>/img/up.png"></a></th>
									<th class="style3">Edit?</th>
									<th class="style3">Delete?</th>
									<th class="style3">Lat<a href="?func=boeyes&boeye_func=list_all&sort=lat&ord=ASC&from=<?php echo $from;?>&to=<?php echo $inc;?>&token=<?php echo $_SESSION["token"];?>"><img height="15" width="15" border="0"border="0" src="<?php echo "/".$GLOBALS['root']."/";?>themes/<?php echo $theme;?>/img/down.png"></a><a href="?func=boeyes&boeye_func=list_all&sort=lat&ord=DESC&from=<?php echo $from;?>&to=<?php echo $inc;?>&token=<?php echo $_SESSION["token"];?>"><img height="15" width="15" border="0"border="0" src="<?php echo "/".$GLOBALS['root']."/";?>themes/<?php echo $theme;?>/img/up.png"></a></th>
									<th class="style3">Long<a href="?func=boeyes&boeye_func=list_all&sort=long&ord=ASC&from=<?php echo $from;?>&to=<?php echo $inc;?>&token=<?php echo $_SESSION["token"];?>"><img height="15" width="15" border="0"border="0" src="<?php echo "/".$GLOBALS['root']."/";?>themes/<?php echo $theme;?>/img/down.png"></a><a href="?func=boeyes&boeye_func=list_all&sort=long&ord=DESC&from=<?php echo $from;?>&to=<?php echo $inc;?>&token=<?php echo $_SESSION["token"];?>"><img height="15" width="15" border="0"border="0" src="<?php echo "/".$GLOBALS['root']."/";?>themes/<?php echo $theme;?>/img/up.png"></a></th>
									<th class="style3">Catagory<a href="?func=boeyes&boeye_func=list_all&sort=cat&ord=ASC&from=<?php echo $from;?>&to=<?php echo $inc;?>&token=<?php echo $_SESSION["token"];?>"><img height="15" width="15" border="0"border="0" src="<?php echo "/".$GLOBALS['root']."/";?>themes/<?php echo $theme;?>/img/down.png"></a><a href="?func=boeyes&boeye_func=list_all&sort=cat&ord=DESC&from=<?php echo $from;?>&to=<?php echo $inc;?>&token=<?php echo $_SESSION["token"];?>"><img height="15" width="15" border="0"border="0" src="<?php echo "/".$GLOBALS['root']."/";?>themes/<?php echo $theme;?>/img/up.png"></a></th>
									<th class="style3">Share?</th>
								</tr>
								<?php
								$user_cache = $username."_waypoints";
								$sql0 = "SELECT * FROM `$db`.`$user_cache` ORDER BY `$sort` $ord LIMIT $from, $inc";
								$result = mysql_query($sql0, $conn);
								while($gcache = mysql_fetch_array($result))
								{
									if($tracker == 0)
									{
										$style_class = "light";
										$tracker = 1;
									}else
									{
										$style_class = "dark";
										$tracker = 0;
									}
										?><tr>
											<td class="<?php echo $style_class;?>">
												<?php echo $gcache['id'];?>
											</td>
											<td class="<?php echo $style_class;?>">
												<a class="links" href="?func=boeyes&boeye_func=fetch_wpt&id=<?php echo $gcache['id'];?>"><?php echo $gcache['name'];?></a>
											</td>
											<td class="<?php echo $style_class;?>">
												<a class="links" href="?func=boeyes&boeye_func=update_wpt&id=<?php echo $gcache['id'];?>">Edit</a>
											</td>
											<td class="<?php echo $style_class;?>">
												<a class="links" href="?func=boeyes&boeye_func=remove_wpt&id=<?php echo $gcache['id'];?>">Delete</a>
											</td>
											<td class="<?php echo $style_class;?>">
												<?php echo $gcache['lat'];?>
											</td>
											<td class="<?php echo $style_class;?>">
												<?php echo $gcache['long'];?>
											</td>
											<td class="<?php echo $style_class;?>">
												<?php echo $gcache['cat'];?>
											</td>
											<td class="<?php echo $style_class;?>">
												<form method="post" action="?func=boeyes&boeye_func=<?php if($gcache['share'] == 1){echo "remove_share_wpt_proc";}else{echo "share_wpt_proc";}?>" name="insertForm"  enctype="multipart/form-data">
												<input name="share_wpt_id" type="checkbox" <?php if($gcache['share']==1){echo "checked";}?> value="<?php echo $gcache['id']; ?>" >
												<input type="submit" value="<?php if($gcache['share'] == 1){echo "Hide Me!";}else{echo "Share Me!";}?>">
												</form>
											</td>
										</tr>
								<?php
								}
								?>
								<tr><td colspan="7"><CENTER>
							<?php
							$sql0 = "SELECT * FROM `$db`.`$user_cache`";
							$result = mysql_query($sql0, $conn);
							$total_rows = mysql_num_rows($result);
								
							$from_fwd=$from;
							$from = 0;
							$page = 1;
							$pages = $total_rows/$inc;
							$pages_exp = explode(".",$pages);
					#		echo $pages.' --- '.$pages_exp[1].'<BR>';
							$pages_end = "0.".$pages_exp[1];
							$pages_end = $pages_end+0;
							$pages = $pages-$pages_end;
					#		echo $pages.' --- '.$pages_end.'<BR>';
							$mid_page = ($from_/$inc)+1;
							$pages_together = ' [<a class="links" href="?func=boeyes&boeye_func=list_all&from=0&to='.$inc.'&sort='.$sort.'&ord='.$ord.'&token='.$_SESSION["token"].'">First</a>] - ';
							for($I=0; $I<=$pages; $I++)
							{
								if($I >= ($mid_page - 6) AND $I <= ($mid_page + 4))
								{
					#				echo $mid_page." --- ".$I." --- ".$page."<BR>";
									
									if($mid_page == $page)
									{
										$pages_together .= ' <i><u>'.$page.'</u></i> - ';
									}else
									{
										$pages_together .= ' <a class="links" href="?func=boeyes&boeye_func=list_all&from='.$from.'&to='.$inc.'&sort='.$sort.'&ord='.$ord.'&token='.$_SESSION["token"].'">'.$page.'</a> - ';
									}
								}
								$from=$from+$inc;
								$page++;
							}
							$pages_together .= ' [<a class="links" href="?func=boeyes&boeye_func=list_all&from='.(($pages)*$inc).'&to='.$inc.'&sort='.$sort.'&ord='.$ord.'&token='.$_SESSION["token"].'">Last</a>]  ';
							echo "<br>Page: < ".$pages_together." >";
							?>
							</CENTER>
							</td></tr></table>
							</td>
						</tr>
					</table>
					<?php
				break;
				
				case "remove_share_wpt_proc":
				
					include('../lib/wdb_xml.inc.php');
					$wdbxml = new WDB_XML();
					?>
					<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
						<tr>
							<th class="style3"><a class="links" href="index.php">Overview</a></th>
							<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
							<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
							<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
							<th class="cp_select_coloum"><a class="links" href="index.php?func=boeyes">Mysticache</a></th>
							<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
						</tr>
						<tr><td colspan="4"></td><td colspan="2" class="cp_select_coloum"><font size="2"><a class="links_s" href="?func=boeyes&boeye_func=list_all">List All</a> |-| <a class="links" href="?func=boeyes&boeye_func=import_switch">Import</a> (<a class="links" href="?func=boeyes&boeye_func=import_gpx">GPX</a>) (<a class="links" href="?func=boeyes&boeye_func=import_LOC">LOC</a>) (<a class="links" href="?func=boeyes&boeye_func=import_csv">CSV</a>)</font></td></tr>
						<tr>
							<td colspan="6">
								<CENTER>
									<?php
									$id = 0;
									$id = filter_input(INPUT_POST, 'share_wpt_id', FILTER_SANITIZE_SPECIAL_CHARS);
									$share_rtn = $wdbxml->share_wpt($id);
							#		dump($share_rtn);
									switch($share_rtn)
									{
										case is_array($share_rtn):
											list($username, $error) = $share_rtn;
											echo $error;
											break;

										case "login":
											echo "You are not logged in, please do so.";
											break;

										case 1:
											echo "Share of Waypoint: ".$name."<br>Was sucssesfull.";
											break;
									}
									?>
								</CENTER>
							</td></tr></table>
							</td>
						</tr>
					</table>
					<?php
				break;
				
				case "share_wpt_proc":
				
					include('../lib/wdb_xml.inc.php');
					$wdbxml = new WDB_XML();
					?>
					<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
						<tr>
							<th class="style3"><a class="links" href="index.php">Overview</a></th>
							<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
							<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
							<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
							<th class="cp_select_coloum"><a class="links" href="index.php?func=boeyes">Mysticache</a></th>
							<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
						</tr>
						<tr><td colspan="4"></td><td colspan="2" class="cp_select_coloum"><font size="2"><a class="links_s" href="?func=boeyes&boeye_func=list_all">List All</a> |-| <a class="links" href="?func=boeyes&boeye_func=import_switch">Import</a> (<a class="links" href="?func=boeyes&boeye_func=import_gpx">GPX</a>) (<a class="links" href="?func=boeyes&boeye_func=import_LOC">LOC</a>) (<a class="links" href="?func=boeyes&boeye_func=import_csv">CSV</a>)</font></td></tr>
						<tr>
							<td colspan="6">
								<CENTER>
									<?php
									$id = 0;
									$id = filter_input(INPUT_POST, 'share_wpt_id', FILTER_SANITIZE_SPECIAL_CHARS);
									$share_rtn = $wdbxml->share_wpt($id);
							#		dump($share_rtn);
									switch($share_rtn)
									{
										case is_array($share_rtn):
											list($username, $error) = $share_rtn;
											echo $error;
											break;

										case "login":
											echo "You are not logged in, please do so.";
											break;

										case 1:
											echo "Share of Waypoint: ".$name."<br>Was sucssesfull.";
											break;
									}
									?>
								</CENTER>
							</td></tr></table>
							</td>
						</tr>
					</table>
					<?php
				break;
				
				case "update_wpt":
					$id = 0;
					$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
					$User_cache = $username.'_waypoints';
					$select = "SELECT * FROM `$db`.`$User_cache` WHERE `id` = '$id'";
					$return = mysql_query($select, $conn);
					$pri_wpt = mysql_fetch_array($return);
					?><table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
						<tr>
							<th class="style3"><a class="links" href="index.php">Overview</a></th>
							<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
							<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
							<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
							<th class="cp_select_coloum"><a class="links_s" href="index.php?func=boeyes">Mysticache</a></th>
							<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
						</tr>
						<tr><td colspan="4"></td><td colspan="2" class="cp_select_coloum"><font size="2"><a class="links" href="?func=boeyes&boeye_func=list_all">List All</a> |-| <a class="links" href="?func=boeyes&boeye_func=import_switch">Import</a> (<a class="links" href="?func=boeyes&boeye_func=import_gpx">GPX</a>) (<a class="links" href="?func=boeyes&boeye_func=import_LOC">LOC</a>) (<a class="links" href="?func=boeyes&boeye_func=import_csv">CSV</a>)</font></td></tr>
						<tr>
							<td colspan="6">
								<CENTER>
									<h2>Edit Geocache data</h2>
									<form method="post" action="?func=boeyes&boeye_func=update_wpt_proc" name="insertForm"  enctype="multipart/form-data">
									<table align="center" class="tree">
										<tr >
											<td  align="center" style="width: 20%" class="dark">Name</td>
											<td class="dark">
												<input type="text" name="name" value="<?php echo $pri_wpt['name'];?>" size="40" class="textfield" tabindex="4"/>
											</td>
										</tr>
										<tr class="odd">
											<td  align="center" style="width: 20%" class="light">GCID</td>
											<td class="light">
												<input type="text" name="gcid" value="<?php echo $pri_wpt['gcid'];?>" size="40" class="textfield" tabindex="7" />
											</td>
										</tr>
										<tr >
											<td  align="center" class="dark">Notes</td>
											<td class="dark">
												<textarea name="notes" tabindex="10" style="width: 481px; height: 216px" ><?php echo $pri_wpt['notes'];?></textarea>            
											</td>
										</tr>
										<tr class="odd">
											<td  align="center" class="light">Catagory</td>
											<td class="light">
												<select name="cat" size="4" multiple="multiple" tabindex="13">
												<?php
												foreach(get_set("`".$db."`.`".$share_cache."`",$column) as $set)
												{
													if($pri_wpt['cat'] == $set)
													{
														?>
														<option value="<?php echo $set;?>" SELECTED><?php echo $set;?></option>
														<?php
													}else
													{
														?>
														<option value="<?php echo $set;?>"><?php echo $set;?></option>
														<?php
													}
												}
												?>
												</select>
											</td>
										</tr>
										<tr >
											<td  align="center" class="dark">Type:::Symbol</td>
											<td class="dark">
												<input type="text" name="type" value="<?php echo $pri_wpt['type'];?>" size="40" class="textfield" tabindex="16" style="width: 335px" />
											</td>
											</tr>
										<tr class="odd">
											<td  align="center" class="light">Lat</td>
											<td class="light">
												<input type="text" name="lat" value="<?php echo $pri_wpt['lat'];?>" size="40" class="textfield" tabindex="19" style="width: 100px"/>
											</td>
										</tr>
										<tr >
											<td  align="center" class="dark">Long</td>
											<td class="dark">
												<input type="text" name="long" value="<?php echo $pri_wpt['long'];?>" size="40" class="textfield" tabindex="22" style="width: 100px"/>
											</td>
										</tr>
										<tr class="odd">
											<td  align="center" class="light">Link</td>
											<td class="dark">
												<input type="text" name="link" value="<?php echo $pri_wpt['link'];?>" size="40" class="textfield" tabindex="25" style="width: 476px" />
											</td>
										</tr>
										<tr>
											<td colspan='2'>
												<CENTER><input type="submit" value="Update Me!"></CENTER>
											</td>
										</tr>
									</table>
									</form>
								</CENTER>
							</td></tr></table>
							</td>
						</tr>
					</table>
					<?php
				break;
				
				
				case "update_wpt_proc":
					include('../lib/wdb_xml.inc.php');
					$wdbxml = new WDB_XML();
					$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
					$name = filter_input(INPUT_GET, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
					$gcid = filter_input(INPUT_GET, 'gcid', FILTER_SANITIZE_SPECIAL_CHARS);
					$notes = filter_input(INPUT_GET, 'notes', FILTER_SANITIZE_SPECIAL_CHARS);
					$cat = filter_input(INPUT_GET, 'cat', FILTER_SANITIZE_SPECIAL_CHARS);
					$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
					$lat = filter_input(INPUT_GET, 'lat', FILTER_SANITIZE_SPECIAL_CHARS);
					$long = filter_input(INPUT_GET, 'long', FILTER_SANITIZE_SPECIAL_CHARS);
					$link = filter_input(INPUT_GET, 'link', FILTER_SANITIZE_SPECIAL_CHARS);
					?>
					<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
						<tr>
							<th class="style3"><a class="links" href="index.php">Overview</a></th>
							<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
							<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
							<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
							<th class="cp_select_coloum"><a class="links_s" href="index.php?func=boeyes">Mysticache</a></th>
							<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
						</tr>
						<tr><td colspan="4"></td><td colspan="2" class="cp_select_coloum"><font size="2"><a class="links" href="?func=boeyes&boeye_func=list_all">List All</a> |-| <a class="links" href="?func=boeyes&boeye_func=import_switch">Import</a> (<a class="links" href="?func=boeyes&boeye_func=import_gpx">GPX</a>) (<a class="links" href="?func=boeyes&boeye_func=import_LOC">LOC</a>) (<a class="links" href="?func=boeyes&boeye_func=import_csv">CSV</a>)</font></td></tr>
						<tr>
							<td colspan="6">
								<CENTER>
								<?php
								$update = $wdbxml->update_wpt($id = 0, $name, $gcid, $notes, $cat, $type, $lat, $long, $link);
							#	dump($update);
								switch($update)
								{
									case is_array($update):
										list($username, $error) = $update;
										echo $error;
										break;

									case "login":
										echo "You are not logged in, please do so.";
										break;

									case 1:
										echo "Update of Waypoint: ".$update."<br>Was sucssesfull.";
										break;
								}
								?>
								</CENTER>
							</td>
						</tr>
					</table>
					<?php
				break;
				
				
				case "import_gpx":
					?>
					<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
						<tr>
							<th class="style3"><a class="links" href="index.php">Overview</a></th>
							<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
							<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
							<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
							<th class="cp_select_coloum"><a class="links" href="index.php?func=boeyes">Mysticache</a></th>
							<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
						</tr>
						<tr><td colspan="4"></td><td colspan="2" class="cp_select_coloum"><font size="2"><a class="links" href="?func=boeyes&boeye_func=list_all">List All</a> |-| <a class="links" href="?func=boeyes&boeye_func=import_switch">Import</a> (<a class="links_s" href="?func=boeyes&boeye_func=import_gpx">GPX</a>) (<a class="links" href="?func=boeyes&boeye_func=import_LOC">LOC</a>) (<a class="links" href="?func=boeyes&boeye_func=import_csv">CSV</a>)</font></td></tr>
						<tr>
							<td colspan="6">
								<CENTER>
								<h2>Import Mysticache GPX file</h2>
									<form action="?func=boeyes&boeye_func=import_gpx_proc" method="post" enctype="multipart/form-data">
									<TABLE BORDER=1 CELLPADDING=2 CELLSPACING=0>
										<TR height="40">
											<TD class="style4">
												<P>File location: 
												</P>
											</TD>
											<TD>
												<P><A NAME="file"></A><INPUT TYPE=FILE NAME="file" SIZE=56 STYLE="width: 5.41in; height: 0.25in"></P>
											</TD>
										</TR>
										<TR>
											<TD>.</TD><TD>
												<P>
											<?php	
												if($rebuild === 0)
												{
												echo '<INPUT TYPE=SUBMIT NAME="submit" VALUE="Submit" STYLE="width: 0.71in; height: 0.36in"></P>';
												}else{echo "The database is in  rebuild mode, please wait...";}
											?>
											</TD>
										</TR>
									</TABLE>
									</form>
								</CENTER>
							</td>
						</tr>
					</table>
					<?php
					
				break;
				
				case "import_gpx_proc":
					
					?>
					<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
						<tr>
							<th class="style3"><a class="links" href="index.php">Overview</a></th>
							<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
							<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
							<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
							<th class="cp_select_coloum"><a class="links" href="index.php?func=boeyes">Mysticache</a></th>
							<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
						</tr>
						<tr><td colspan="4"></td><td colspan="2" class="cp_select_coloum"><font size="2"><a class="links" href="?func=boeyes&boeye_func=list_all">List All</a> |-| <a class="links" href="?func=boeyes&boeye_func=import_switch">Import</a> (<a class="links_s" href="?func=boeyes&boeye_func=import_gpx">GPX</a>) (<a class="links" href="?func=boeyes&boeye_func=import_LOC">LOC</a>) (<a class="links" href="?func=boeyes&boeye_func=import_csv">CSV</a>)</font></td></tr>
						<tr>
							<td colspan="6">
								<CENTER><?php
					include('../lib/wdb_xml.inc.php');
					$wdbxml = new WDB_XML();
					
					if($_POST["user"] !== ''){$user = addslashes($_POST["user"]);}else{$user="Unknown";}
					if($_POST["notes"] !== ''){$notes = addslashes($_POST["notes"]);}else{$notes="No Notes";}
					if($_POST['title'] !== ''){$title = addslashes($_POST['title']);}else{$title="Untitled";}
					
					$uploadfolder = getcwd().'/up/'.$username.'/';
					if(!(is_dir($uploadfolder)))
					{
					#	echo "Make Folder $daily_folder\n";
						mkdir($uploadfolder);
					}
					
					$tmp		=	$_FILES['file']['tmp_name'];
					$filename	=	$_FILES['file']['name'];
					$rand		=	rand();
					$xml_file 	= 	$uploadfolder.$rand.'_'.$filename;
					
					if (copy($tmp, $xml_file))
					{
						$import_rtn = $wdbxml->import_xml($xml_file);
						switch($import_rtn)
						{
							case is_array($import_rtn):
								list($username, $wpts) = $import_rtn;
								echo "<h2>Imported!</h2>User: ".$username."<BR> Num of Wpts: ".$wpts;
								break;

							case "login":
								echo "You are not logged in, please do so.";
								break;

							case 0:
								echo "There was a faital error in importing.";
								break;
						}
					}else
					{
						echo "Could not copy";
					}
					?>
								</CENTER>
							</td>
						</tr>
					</table>
					<?php
				break;
				
				case "import_switch":
					?>
					<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
						<tr>
							<th class="style3"><a class="links" href="index.php">Overview</a></th>
							<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
							<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
							<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
							<th class="cp_select_coloum"><a class="links" href="index.php?func=boeyes">Mysticache</a></th>
							<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
						</tr>
						<tr><td colspan="4"></td><td colspan="2" class="cp_select_coloum"><font size="2"><a class="links" href="?func=boeyes&boeye_func=list_all">List All</a> |-| <a class="links" href="?func=boeyes&boeye_func=import_switch">Import</a> (<a class="links_s" href="?func=boeyes&boeye_func=import_gpx">GPX</a>) (<a class="links" href="?func=boeyes&boeye_func=import_LOC">LOC</a>) (<a class="links" href="?func=boeyes&boeye_func=import_csv">CSV</a>)</font></td></tr>
						<tr>
							<td colspan="6">
								<CENTER>
									<h3>All supported files are Mysticache Exports</h3>
									<table>
										<tr>
											<td><a class="links" href="?func=boeyes&boeye_func=import_gpx">Import GPX</a> - <a class="links_sample" href="http://vistumbler.svn.sourceforge.net/viewvc/vistumbler/Mysticache/sample.gpx?view=markup">Sample</a></td>
										</tr>
										<tr>
											<td><a class="links" href="?func=boeyes&boeye_func=import_loc">Import LOC</a> - <a class="links_sample" href="http://vistumbler.svn.sourceforge.net/viewvc/vistumbler/Mysticache/test.loc?view=markup">Sample</a></td>
										</tr>
										<tr>
											<td><a class="links" href="?func=boeyes&boeye_func=import_csv">Import CSV</a> - <a class="links_sample" href="">Sample</a></td>
										</tr>
									</table>
								</CENTER>
							</td>
						</tr>
					</table>
					<?php
				break;
				
				default:
					$User_cache = $username.'_waypoints';
					
					$select = "SELECT * FROM `$db`.`$User_cache`";
					$return = mysql_query($select, $conn);
					$num_wpts = mysql_num_rows($return);
					
					$select = "SELECT * FROM `$db`.`$User_cache` WHERE `share` = '1'";
					$return = mysql_query($select, $conn);
					$num_shared_wpts = mysql_num_rows($return);

					?>
					<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
						<tr>
							<th class="style3"><a class="links" href="index.php">Overview</a></th>
							<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
							<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
							<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
							<th class="cp_select_coloum"><a class="links_s" href="index.php?func=boeyes">Mysticache</a></th>
							<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
						</tr>
						<tr><td colspan="4"></td><td colspan="2" class="cp_select_coloum"><font size="2"><a class="links" href="?func=boeyes&boeye_func=list_all">List All</a> |-| <a class="links" href="?func=boeyes&boeye_func=import_switch">Import</a> (<a class="links" href="?func=boeyes&boeye_func=import_gpx">GPX</a>) (<a class="links" href="?func=boeyes&boeye_func=import_LOC">LOC</a>) (<a class="links" href="?func=boeyes&boeye_func=import_csv">CSV</a>)</font></td></tr>
						<tr>
							<td colspan="6">
							<CENTER><table BORDER=1 CELLPADDING=2 CELLSPACING=0 width ="50%">
								<tr>
									<th colspan="2" class="style4">
									Cache Statistics
									</th>
								</tr>
								<tr>
									<td width="60%">
										Number of Caches:
									</td>
									
									<td>
										<?php echo $num_wpts;?>
									</td>
								</tr>
								<tr>
									<td width="60%">
										Number of Shared Caches:
									</td>
									<td>
										<?php echo $num_shared_wpts;?>
									</td>
								</tr>
							</table>
							</CENTER>
							</td>
						</tr>
					</table>
					<?php
				break;
			}
			
		break;
		
		
		##-------------##
		case 'foes':
			?>
			<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
				<tr>
					<th class="style3"><a class="links" href="index.php">Overview</a></th>
					<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
					<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
					<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
					<th class="style3"><a class="links" href="index.php?func=boeyes">Mysticache</a></th>
					<th class="cp_select_coloum"><a class="links_s" href="index.php?func=foes">Friends / Foes</a></th>
				</tr>
				<tr>
					<td colspan="6">
					
					</td>
				</tr>
			</table>
			<?php
		break;
		
		
		##-------------##
		default:
			$username = $GLOBALS['username'];
			
			################
			$sql0 = "SELECT * FROM `$db`.`$user_logins_table` WHERE `username` = '$username' LIMIT 1";
			$result = mysql_query($sql0, $conn);
			$newArray = mysql_fetch_array($result);
			$last_login = $newArray['last_login'];
			$join_date = $newArray['join_date'];
			
			###############3
			$sql = "SELECT * FROM `$db`.`users` WHERE `username` LIKE '$username' ORDER BY `id` DESC LIMIT 1";
			$user_query = mysql_query($sql, $conn) or die(mysql_error($conn));
			$user_last = mysql_fetch_array($user_query);
			
			$last_import_id = $user_last['id'];
			$user_aps = $user_last['aps'];
			$user_gps = $user_last['gps'];
			
			$last_import_title = $user_last['title'];
			$last_import_date = $user_last['date'];
			
			###########
			$sql = "SELECT `title` FROM `$db`.`users` WHERE `username` LIKE '$username' ORDER BY `aps` DESC LIMIT 1";
			$user_query = mysql_query($sql, $conn) or die(mysql_error($conn));
			$user_largest = mysql_fetch_array($user_query);
			$large_import_title = $user_last['title'];
			
			#########
			$max = 0;
			$max_ssid = '';
			$sql = "SELECT * FROM `$db`.`users` WHERE `username` LIKE '$username' ORDER BY `id` DESC";
			$user_query = mysql_query($sql, $conn) or die(mysql_error($conn));
			while($user_ap_l = mysql_fetch_array($user_query))
			{
				$pnts_exp = explode("-",$user_ap_l['points']);
				
				foreach($pnts_exp as $key=>$point)
				{
	#				echo $point."-";
					$pnt_exp = explode(":",$point);
					$pnt = explode(",",$pnt_exp[0]);
					$pnt_id = $pnt[1];
	#				echo '<BR>'.$pnt_id.'<BR>';
					$sql = "SELECT * FROM `$db`.`wifi0` WHERE `id` = '$pnt_id' LIMIT 1";
					$ap_qry = mysql_query($sql, $conn) or die(mysql_error($conn));
					$ap_ary = mysql_fetch_array($ap_qry);
					$id = $ap_ary['id'];
					$ssid_ptb_ = $ap_ary["ssid"];
					$ssids_ptb = str_split($ap_ary['ssid'],25);
					$ssid_ptb = smart_quotes($ssids_ptb[0]);
					$table		=	$ssid_ptb.'-'.$ap_ary["mac"].'-'.$ap_ary["sectype"].'-'.$ap_ary["radio"].'-'.$ap_ary['chan'];
					$table_gps	=	$table.$gps_ext;
					
					$sql = "SELECT * FROM `$db_st`.`$table_gps`";
					$ap_qry = mysql_query($sql, $conn) or die(mysql_error($conn));
					$rows = mysql_num_rows($ap_qry);
	#				echo $rows."<br>";
					if($rows > $max){$max = $rows; $max_ssid = $ap_ary['ssid']."( ".$rows." )";}
				}
			}
			$sql = "SELECT * FROM `$db`.`users` WHERE `username` LIKE '$username' ORDER BY `id` DESC";
			$user_query = mysql_query($sql, $conn) or die(mysql_error($conn));
			while($user_ap_l = mysql_fetch_array($user_query))
			{
#				echo $user_ap_l['points']."<BR>";
				$pnts_exp = explode("-",$user_ap_l['points']);
				$pnts_exp = array_reverse($pnts_exp);
				foreach($pnts_exp as $key => $points_ex)
				{
#					echo $points_ex." - ";
					$pnt_e = explode(",",$points_ex);
				#	echo $pnt_e[0]." - ";
					if($pnt_e[0] == "1"){continue;}
					$pnt = explode(":",$pnt_e[1]);
					$pnt_id = $pnt[0];
					$sql = "SELECT `ssid` FROM `$db`.`wifi0` WHERE `id` = '$pnt_id' LIMIT 1";
			#		echo $sql."<BR>";
					$ap_qry = mysql_query($sql, $conn) or die(mysql_error($conn));
					$ap_ary = mysql_fetch_array($ap_qry);
					$new_ssid = $ap_ary["ssid"];
#					echo $new_ssid."<BR>";
					break;
				}
				if(@$new_ssid != ''){break;}
			}
			if(@$new_ssid == ''){$new_ssid = "No New APs, all are updates";}
			if(@$last_import_title == ''){$last_import_title = "No New APs, all are updates";}
			if(@$large_import_title == ''){$large_import_title = "No New APs, all are updates";}
			if(@$max_ssid == ''){$max_ssid = "No New APs, all are updates";}
			?>
			<table BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 95%">
				<tr>
					<th class="cp_select_coloum"><a class="links_s" href="index.php">Overview</a></th>
					<th class="style3"><a class="links" href="index.php?func=profile">Profile</a></th>
					<th class="style3"><a class="links" href="index.php?func=pref">Preferences</a></th>
					<th class="style3"><a class="links" href="index.php?func=permissions">Permissions</a></th>
					<th class="style3"><a class="links" href="index.php?func=boeyes">Mysticache</a></th>
					<th class="style3"><a class="links" href="index.php?func=foes">Friends / Foes</a></th>
				</tr>
				<tr>
					<td colspan="6">
					<table  BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 100%">
						<tr>
							<th width="30%" class="style3">Largest Import</th>
							<td align="center" class="style2"><?php echo $large_import_title;?></td>
						</tr>
						<tr>
							<th width="30%" class="style3">Last Import</th>
							<td align="center" class="style2"><?php echo $last_import_title;?></td>
						</tr>
						<tr>
							<th width="30%" class="style3">AP with most GPS</th>
							<td align="center" class="style2"><?php echo $max_ssid;?></td>
						</tr>
						<tr>
							<th width="30%" class="style3">Last New AP</th>
							<td align="center" class="style2"><?php echo $new_ssid;?></td>
						</tr>
						<tr>
							<th width="30%" class="style3">Last Login</th>
							<td align="center" class="style2"><?php echo $last_login;?></td>
						</tr>
						<tr>
							<th width="30%" class="style3">Join Date</th>
							<td align="center" class="style2"><?php echo $join_date;?></td>
						</tr>
					</table>
					</td>
				</tr>
			</table>
			<?php
		break;	
	}
}else
{
	?>
	<script type="text/javascript">
	function reload()
	{
		location.href = '/<?php echo $root;?>/';
	}
	</script>
	<body onload="reload()"></body>
	<?php
}

footer($_SERVER['SCRIPT_FILENAME']);
?>