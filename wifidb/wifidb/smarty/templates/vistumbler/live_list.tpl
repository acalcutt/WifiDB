{include file="vistumbler_header.tpl"}
                        <tr>
                            <td class="cell_side_left">&nbsp;</td>
                            <td class="cell_color_centered" align="center" colspan="2">
                                <div align="center">
                                    <table border="1" width="100%" cellspacing="0">
                                    {foreach from=$live item=user}
                                        <tr class="{$user.color}">
                                            <td><a class="links" href="{$wifidb_host_url}opt/userstats.php?id={$user.username}">{$user.username}</a></td>
                                        </tr>
                                        <tr>
                                            <td>
                                            	<iframe src="{$wifidb_host_url}opt/live_list_user.php?user={$user.username}&ord=ASC&sort=SSID"></iframe>
                                            </td>
                                        </tr>
                                    {foreachelse}
                                        <tr>
                                            <td>Awww crap, no one is using the Live Access Point feature :(</td>
                                        </tr>
                                    {/foreach}
                                    </table>
                                </div>
                                <br>
                            </td>
                            <td class="cell_side_right">&nbsp;</td>
                        </tr>
{include file="vistumbler_footer.tpl"}