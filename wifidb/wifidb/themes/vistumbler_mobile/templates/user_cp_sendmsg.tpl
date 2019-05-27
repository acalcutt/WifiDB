<!--
Database.inc.php, holds the database interactive functions.
Copyright (C) 2014 Andrew Calcutt

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
-->
{include file="header.tpl"}
			<div class="main">
{include file="topmenu.tpl"}
{include file="user_cp_header.tpl"}
				<div style="text-align: center;">
					<div style="display: inline-block; text-align: left;">
						<form action="{$wifidb_host_url}cp/index.php?func=sendmsg_submit" method="post" enctype="multipart/form-data">
							<ul class="wrapper">
								<li class="form-row>
									<label for="from_user">From</label><br />
									<input type="text" id="from_user" name="from_user" disabled="disabled" value="{$touser.username}">
									<input type="hidden" id="from_id" name="from_id" value="{$touser.id}">
								</li>
								<li class="form-row>
									<label for="to_id">To</label><br />
									<select id="to_id" name="to_id">
									{foreach $fromusers as $fromuser} 
										<option value="{$fromuser.id}"{if $fromuser.id eq $to} selected{/if}>{$fromuser.username}</option>
									{/foreach}
									</select>
								</li>
								<li class="form-row>
									<label for="subject">Subject</label><br />
									<input type="text" id="subject" name="subject" placeholder="Enter a subject..." value="{$title}">
								</li>
								<li>
									<label for="message">Message</label><br />
									<textarea id="message" name="message" placeholder="Enter a message..." style="height:300px;width:100%;"></textarea>
								</li>
								<li>
									<input type="hidden" id="thread_id" name="thread_id" value="{$thread_id}">
									<button type="submit">Submit</button>
								</li>
							</ul>
						</form>
						<script>
							var textarea = document.getElementById('message');
							sceditor.create(textarea, {
								format: 'bbcode',
								icons: 'monocons',
								autoUpdate: true,
								autoExpand: true,
								style: '{$themeurl}lib/sceditor/minified/themes/content/default.min.css'
							});
						</script>
					</div>
				</div>
			</div>
{include file="footer.tpl"}