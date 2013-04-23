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
define("SWITCH_EXTRAS", "");

include('lib/init.inc.php');

$conn = $GLOBALS['conn'];
$db = $GLOBALS['db'];
$manual = $GLOBALS['manual'];

$func_name_view = preg_replace(array('/\22/', '/\27/'), '_', mysql_real_escape_string($_GET['func_name_view']));
$func = preg_replace(array('/\22/', '/\27/'), '_', mysql_real_escape_string($_GET['func']));
switch($func)
{
	case "func_view":
		$sql = "SELECT * FROM `wifi`.`manual` WHERE `function_name` LIKE '$func_name_view' LIMIT 1";
		#echo $sql;
		$return = mysql_query($sql, $conn);
		$array = mysql_fetch_array($return);
		?>
		<style>
		body {
			background:#112233;
			margin:0;
			line-height: 1.5em;
			color:#000000	;
			font-size/* */:/**/small; font-style:normal; font-variant:normal; font-weight:normal; font-family:Trebuchet MS
		}
		</style>
		<title>WiFiDB <?php echo $GLOBALS['ver']['wifidb']; ?> Documentation: <?php echo $array['function_name']; ?></title>
		</head>
		<body topmargin="10" leftmargin="14">
		<font color="white">
		<table align="left">
		<?php
		echo "Class: ".$array['class']."<br>Function: ".$array['function'];
		echo "<br>Version: ".$array['late_ver'];
		 ?>
		<hr>

		<?php echo $array['desc']; ?>

		<br>
		<br>
		Example:
		<table bgcolor="#304D80" border="0">
			<tr>
				<td>
					<code>

						<?php echo $array['example']; ?>

					</code>
				</td>
			</tr>
		</table>
		</table>
		</font>
		<?php
	break;

	case "index":
		$sql = "SELECT * FROM `wifi`.`manual` ORDER BY `class` ASC";
		echo $sql;
		$return = mysql_query($sql, $conn);
		$rows = mysql_num_rows($return);
		?>
		<link rel="stylesheet" href="themes/wifidb/styles.css">
		<title>WiFiDB <?php echo $GLOBALS['ver']['wifidb']; ?> Documentation Index</title>
		</head>
		<body topmargin="10" leftmargin="14">
		<table bgcolor="#304D80" width="75%" align="center" border="0">
			<tr class="style4">
				<th>
					ID
				</th>
				<th>
					Function Name
				</th>
				<th>
					Class
				</th>
				<th>
					Version
				</th>
			</tr>
			<?php
			if($rows > 0)
			{
				$fclass=0;
				$n = 1;
				while($array = mysql_fetch_array($return))
				{
					if($fclass){$fclass = 0; $class = "dark";}else{$fclass = 1; $class="light";}
					echo "
					<tr class='$class'>
						<td>$n</td>
						<td><a class='links' href='?func=func_view&func_name_view=".$array['function_name']."'>".$array['function_name']."</a></td>
						<td>".$array['late_ver']."</td>
						<td>".$array['class']."</td>
					</tr>";
					$n++;
				}
			}else
			{
				echo "
				<tr>
					<td align='center' colspan='3'>There are no Functions in the Manuals Table.. thats not right at all.. go bitch slap phil.</td>
				</tr>";
			}
	break;

	default:
		redirect_page("?func=index", 2000, "No Query string, redirecting to the manual index");
	break;
}
?>
</body>
</html>