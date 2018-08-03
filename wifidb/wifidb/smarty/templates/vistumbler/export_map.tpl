<!--
Database.inc.php, holds the database interactive functions.
Copyright (C) 2011 Phil Ferland

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
                                        <table style="50%" cellspacing="3" cellpadding="0" class="style3">
                                            <tr>
                                                <td class="style2">
                                                    {$results.mesg}
                                                </td>
                                            </tr>
                                        </table>
{include file="footer.tpl"}


<!DOCTYPE html>
<html>

<head>
    <meta charset='utf-8' />
    <title></title>
    <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />
    <script src='https://omt.wifidb.net//mapbox-gl.js'></script>
    <link href='https://omt.wifidb.net//mapbox-gl.css' rel='stylesheet' />
    <style>

    
    #map {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 100%;
    }
    
    </style>
</head>

<body>
    <div id='map'></div>
    <script>
    var map = new mapboxgl.Map({
        container: 'map',
        style: 'https://omt.wifidb.net/styles/osm-bright/style.json',
        center: [-73.41402, 42.1844767],
		zoom: 3,
        pitch: 40,
    });

    function init() {
{$layer_source_all}

    };

    map.once('style.load', function(e) {
        init();
        map.addControl(new mapboxgl.NavigationControl());
        map.on('click', function(e) {
            var features = map.queryRenderedFeatures(e.point, {
                layers: [{$layer_name_all}]
            });
            if (!features.length) {
                return;
            }
            var feature = features[0];

            var popup = new mapboxgl.Popup()
                .setLngLat(map.unproject(e.point))
                .setHTML('<a href="https://live.wifidb.net/wifidb/opt/fetch.php?id=' + feature.properties.id + '"><h3>' + feature.properties.ssid + '</h3></a>' +
                    '<ul>' +
                    '<li>SSID: <b>' + feature.properties.ssid + '</b></li>' +
                    '<li>MAC: <b>' + feature.properties.mac + '</b></li>' +
                    '<li>CHAN: <b>' + feature.properties.chan + '</b></li>' +
                    '<li>NETWORK TYPE: <b>' + feature.properties.NT + '</b></li>' +
                    '<li>ENCRYPTION: <b>' + feature.properties.encry + '</b></li>' +
                    '<li>RADIO TYPE: <b>' + feature.properties.radio + '</b></li>' +
                    '<li>BASIC TX: <b>' + feature.properties.BTx + '</b></li>' +
                    '<li>OTHER TX: <b>' + feature.properties.OTx + '</b></li>' +
                    '<li>LATITUDE: <b>' + feature.properties.lat + '</b></li>' +
                    '<li>LONGITUDE: <b>' + feature.properties.long + '</b></li>' +
                    '<li>ALTITUDE: <b>' + feature.properties.alt + '</b></li>' +
                    '<li>First Active: <b>' + feature.properties.FA + '</b></li>' +
                    '<li>Last Active: <b>' + feature.properties.LA + '</b></li>' +
                    '<li>Username: <a href="https://live.wifidb.net/wifidb/opt/userstats.php?func=alluserlists&user=' + feature.properties.username + '"><b>' + feature.properties.username + '</b></a></li>' +
                    '</ul>')
                .addTo(map);
        });

        //Hide loading bar once tiles from geojson are loaded
        map.on('data', function(e) {})

        // Use the same approach as above to indicate that the symbols are clickable
        // by changing the cursor style to 'pointer'.
        map.on('mousemove', function(e) {
            var features = map.queryRenderedFeatures(e.point, {
                layers: [{$layer_name_all}]
            });
            map.getCanvas().style.cursor = (features.length) ? 'pointer' : '';
        });
    });
    </script>
</body>

</html>