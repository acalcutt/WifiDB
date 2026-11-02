<!--
Traffic (admin/traffic.php): the site's own page views, unique visitors and activity.
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				<table class="content_table">
					<tr>
						<td class="subheading">Traffic</td>
					</tr>
					<tr>
						<td class="header">Page views of the site's pages (not the API, map tiles or downloads), and unique visitors, counted by the site on every node and added up each minute. Times are UTC. Bots aren't counted, and no visitor's address is kept.</td>
					</tr>
					{if $traffic_error}<tr><td class="light-centered"><font color="Red">{$traffic_error|escape:'html':'UTF-8':false}</font></td></tr>{/if}
					<tr class="light"><td><canvas id="traffic_hours" height="90"></canvas></td></tr>
					<tr class="light"><td><canvas id="traffic_days" height="90"></canvas></td></tr>
					<tr class="light"><td><canvas id="traffic_months" height="90"></canvas></td></tr>
				</table>

				<table class="content_table">
					<tr><td class="subheading" colspan="4">What people did</td></tr>
					<tr class="header"><td></td><td align="right">Last day</td><td align="right">Last 7 days</td><td align="right">Last 30 days</td></tr>
					{foreach $traffic_events as $e}
					<tr class="{cycle values='dark,light'}"><td>{$e.label|escape:'html':'UTF-8':false}</td><td align="right">{$e.day}</td><td align="right">{$e.week}</td><td align="right">{$e.month}</td></tr>
					{/foreach}
				</table>

				<table class="content_table">
					<tr><td class="subheading" colspan="3">By day of the week (average, last 60 days)</td></tr>
					<tr class="header"><td></td><td align="right">Unique visitors</td><td align="right">Page views</td></tr>
					{foreach $traffic_weekdays as $d}
					<tr class="{cycle values='dark,light'}"><td>{$d.day|escape:'html':'UTF-8':false}</td><td align="right">{$d.visitors}</td><td align="right">{$d.pageviews}</td></tr>
					{/foreach}
				</table>

				<table class="content_table">
					<tr><td class="subheading" colspan="3">Countries, {$traffic_month_label|escape:'html':'UTF-8':false}</td></tr>
					<tr class="header"><td>Country</td><td align="right">Unique visitors</td><td align="right">Page views</td></tr>
					{foreach $traffic_countries as $c}
					<tr class="{cycle values='dark,light'}"><td>{if $c[0] == '--'}Not known{else}{$c[0]|escape:'html':'UTF-8':false}{/if}</td><td align="right">{$c[1]}</td><td align="right">{$c[2]}</td></tr>
					{foreachelse}
					<tr class="light"><td colspan="3">Nothing counted yet this month.</td></tr>
					{/foreach}
				</table>

				<table class="content_table">
					<tr><td class="subheading" colspan="2">Pages, {$traffic_month_label|escape:'html':'UTF-8':false}</td></tr>
					<tr class="header"><td>Page</td><td align="right">Page views</td></tr>
					{foreach $traffic_pages as $p}
					<tr class="{cycle values='dark,light'}"><td>{$p[0]|escape:'html':'UTF-8':false}</td><td align="right">{$p[2]}</td></tr>
					{foreachelse}
					<tr class="light"><td colspan="2">Nothing counted yet this month.</td></tr>
					{/foreach}
				</table>

				<table class="content_table">
					<tr><td class="subheading" colspan="3">By month</td></tr>
					<tr class="header"><td>Month</td><td align="right">Unique visitors</td><td align="right">Page views</td></tr>
					{foreach $traffic_months as $m}
					<tr class="{cycle values='dark,light'}"><td>{$m.period|escape:'html':'UTF-8':false}</td><td align="right">{$m.visitors}</td><td align="right">{$m.pageviews}</td></tr>
					{/foreach}
				</table>

				<table class="content_table">
					<tr><td class="subheading" colspan="3">By day</td></tr>
					<tr class="header"><td>Day</td><td align="right">Unique visitors</td><td align="right">Page views</td></tr>
					{foreach $traffic_days as $d}
					<tr class="{cycle values='dark,light'}"><td>{$d.period|escape:'html':'UTF-8':false}</td><td align="right">{$d.visitors}</td><td align="right">{$d.pageviews}</td></tr>
					{/foreach}
				</table>
			</div>
<script type="application/json" id="traffic_data">{$traffic_chart_json}</script>
<script src="{$wifidb_host_url}lib/js/chart.min.js"></script>
{literal}
<script>
(function () {
	var data = JSON.parse(document.getElementById('traffic_data').textContent);
	if (!window.Chart) { return; }
	[['hours', 'By hour, last 48 hours'], ['days', 'By day, last 60 days'], ['months', 'By month, last 24 months']].forEach(function (c) {
		var d = data[c[0]];
		new Chart(document.getElementById('traffic_' + c[0]), {
			type: 'bar',
			data: { labels: d.labels, datasets: [
				{ label: 'Unique visitors', data: d.visitors, backgroundColor: '#ff8b60' },
				{ label: 'Page views', data: d.pageviews, backgroundColor: '#5f99cf' }
			] },
			options: { animation: false, plugins: { title: { display: true, text: c[1] } }, scales: { y: { beginAtZero: true } } }
		});
	});
})();
</script>
{/literal}
{include file="footer.tpl"}
