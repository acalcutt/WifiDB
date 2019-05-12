{include file="header.tpl"}
			<div class="main">
				<button type="button" id="sidebarCollapse" class="navbar-btn">
					<span></span>
					<span></span>
					<span></span>
				</button>
				<div class="center">
					<br/>
					<img src="{$themeurl}img/logo.png">
					<form method="post" action="/wifidb/login.php?func=reset_password_finish">
						<table align="center">
							<tbody>
								<tr>
									<td colspan="2">
										{$message}
									</td>
								</tr>
								<tr>
									<td>Username</td>
									<td>
										<input type="text" name="username" value="{$username}" disabled>
										<input type="hidden" name="usernameh" value="{$username}">
										<input type="hidden" name="validate_code" value="{$validate_code}">
									</td>
								</tr>
								<tr>
									<td>Password</td>
									<td><input type="password" name="newpassword"></td>
								</tr>
								<tr>
									<td>Password (again)</td>
									<td><input type="password" name="newpassword2"></td>
								</tr>
								<tr>
									<td colspan="2">
										<p align="center">
											<input type="hidden" name="return" value="{$wifidb_host_url}index.php">
											<input type="submit" value="Reset Password">
										</p>
									</td>
								</tr>
							</tbody>
						</table>
					</form>
				</div>
			</div>
{include file="footer.tpl"}