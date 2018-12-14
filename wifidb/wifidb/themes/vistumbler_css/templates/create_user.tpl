{include file="header.tpl"}
			<div class="main">
				<div class="center">
					<br/>
					<img src="{$themeurl}img/logo.png">
					<h2>Create User</h2>
					<h3>{$message}</h3>
					<form method="post" action="/wifidb/login.php?func=create_user_proc">
						<table align="center">
							<tr>
								<td>Username</td>
								<td><input type="text" name="time_user" value=""></td>
							</tr>
							<tr>
								<td>Password</td>
								<td><input type="password" name="time_pass"></td>
							</tr>
							<tr>
								<td>Password (again)</td>
								<td><input type="password" name="time_pass2"></td>
							</tr>
							<tr>
								<td>Email</td>
								<td><input type="text" name="time_email" value=""></td>
							</tr>
							<tr>
								<td colspan="2"><p align="center"><input type="submit" value="Create Me!"></p></td>
							</tr>
						</table>
					</form>
				</div>
			</div>
{include file="footer.tpl"}