<html>
    <head>
		<link rel="stylesheet" href="{$wifidb_host_url}themes/vistumbler/styles.css">
    </head>
    <body style="background-color: #145285">
        <table style="width: 90%; " class="no_border" align="center">
            <tr>
                <td>SSID<a href="?sort=SSID&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"border="0" src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=SSID&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/up.png"></a></td>
				<td>MAC<a href="?sort=mac&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=mac&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/up.png"></a></td>
				<td>Chan<a href="?sort=chan&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=chan&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/up.png"></a></td>
				<td>Radio Type<a href="?sort=radio&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=radio&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/up.png"></a></td>
				<td>Authentication<a href="?sort=auth&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=auth&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/up.png"></a></td>
				<td>Encryption<a href="?sort=encry&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=encry&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/up.png"></a></td>
				<td>Latitude<a href="?sort=lat&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=encry&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/up.png"></a></td>
				<td>Longitude<a href="?sort=long&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=encry&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/up.png"></a></td>
				<td>Last Updated<a href="?sort=LA&ord=ASC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/down.png"></a><a href="?sort=encry&ord=DESC&from=<?php echo $from; ?>&to=<?php echo $inc; ?>"><img height="15" width="15" border="0"src="{$wifidb_host_url}themes/<?php echo $theme; ?>/img/up.png"></a></td>
            </tr>
            {foreach from=$live item=user}
            <tr class="{$ap.color}">
            	<td>{$ap.ssid}</td>
            	<td>{$ap.mac}</td>
            	<td>{$ap.chan}</td>
            	<td>{$ap.radio}</td>
            	<td>{$ap.auth}</td>
            	<td>{$ap.encry}</td>
            	<td>{$ap.lat}</td>
            	<td>{$ap.long}</td>
            	<td>{$ap.LA}</td>
            </tr>
            {foreachelse}
            
            {/foreach}
        </table>
    </body>
</html>