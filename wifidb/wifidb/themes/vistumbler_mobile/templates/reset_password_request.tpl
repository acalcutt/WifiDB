{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				<div class="center">
					<br/>
					<img src="{$themeurl}img/logo.png">
					<form method="post" action="/wifidb/login.php?func=reset_password_request_proc">
						<table align="center">
							<tbody>
								<tr>
									<td colspan="2">
										{$message}
									</td>
								</tr>
								<tr>
									<td>Username</td>
									<td><input type="text" name="username_f"></td>
								</tr>
								<tr>
									<td>E-mail Address</td>
									<td><input type="text" name="email_f"></td>
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