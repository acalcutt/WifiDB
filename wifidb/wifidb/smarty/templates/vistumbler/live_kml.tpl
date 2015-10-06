<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
    <!--Live AP KML Template v0.1-->
    <Document>
        <name>RanInt WifiDB KML Newset AP</name>
        <Style id="openStyleDead">
        <IconStyle>
            <scale>0.5</scale>
            <Icon>
                <href>http://vistumbler.sourceforge.net/images/program-images/open.png</href>
            </Icon>
        </IconStyle>
	</Style>
        <Style id="wepStyleDead">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>http://vistumbler.sourceforge.net/images/program-images/secure-wep.png</href>
                </Icon>
            </IconStyle>
        </Style>
        <Style id="secureStyleDead">
            <IconStyle>
            <scale>0.5</scale>
                <Icon>
                    <href>http://vistumbler.sourceforge.net/images/program-images/secure.png</href>
                </Icon>
            </IconStyle>
        </Style>
        <Style id="Location">
            <LineStyle>
                <color>7f0000ff</color>
                <width>4</width>
            </LineStyle>
        </Style>
        {foreach from=$live item=ap}
        <Placemark id="{$ap.mac}">
            <description>
                <![CDATA[<b>SSID: </b>{$ap.ssid}<br />
                <b>Mac Address: </b>{$ap.mac}<br />
                <b>Network Type: </b>{$ap.nt}<br />
                <b>Radio Type: </b>{$ap.radio}<br />
                <b>Channel: </b>{$ap.chan}<br />
                <b>Authentication: </b>{$ap.auth}<br />
                <b>Encryption: </b>{$ap.encry}<br />
                <b>Basic Transfer Rates: </b>{$ap.btx}<br />
                <b>Other Transfer Rates: </b>{$ap.otx}<br />
                <b><br /><b>Last Updated: </b>{$ap.LA}<br />
                <b>Latitude: </b>{$ap.lat}<br />
                <b>Longitude: </b>{$ap.long}<br />
                <b>Manufacturer: </b>{$ap.manuf}
            </description>
            <styleUrl>#secureStyleDead</styleUrl>
            <Point id="{$ap.mac}_GPS">
                <coordinates>{$ap.long},{$ap.lat},500</coordinates>
            </Point>
        </Placemark>
        {foreachelse}

        {/foreach}
    </Document>
</kml>