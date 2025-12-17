<!--
Admin Action Result Template
Copyright (C) 2025 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				<div class="center">
					<h2>Action Result</h2>
					<table class="content_table">
						<tbody>
							<tr class="subheading">
								<th class="subheading">{if $message_type == 'success'}Success{else}Error{/if}</th>
							</tr>
							<tr class="{if $message_type == 'success'}light{else}dark{/if}">
								<td class="{if $message_type == 'success'}light{else}dark{/if}" style="padding: 20px; {if $message_type == 'error'}color: #cc0000;{/if}">
									{$message|escape:'htmlall'}
								</td>
							</tr>
							<tr class="light">
								<td class="light" style="text-align: center; padding: 15px;">
									<a href="{$return_url}" style="background-color: #4a6fa5; color: white; padding: 10px 20px; text-decoration: none;">Return</a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
{include file="footer.tpl"}
