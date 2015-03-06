	{foreach from=$items item=item}
	<Style id="#{$name}Style">
		<PolyStyle>
			<fill>0</fill>
		</PolyStyle>
	</Style>
	<Region id="#{$name}Region">
		<LatLonAltBox>
			<north>{$North}</north>
			<south>{$South}</south>
			<east>{$East}</east>
			<west>{$West}</west>
			<minAltitude>0</minAltitude>
			<maxAltitude>3000</maxAltitude>
		</LatLonAltBox>
		<Lod>
			<minLodPixels>768</minLodPixels>
			<maxLodPixels>-1</maxLodPixels>
			<minFadeExtent>0</minFadeExtent>
			<maxFadeExtent>0</maxFadeExtent>
		</Lod>
	</Region>
	<Placemark>
		<name></name>
		<styleUrl>#{$name}Style</styleUrl>
		<MultiGeometry>
			{foreach from=$Polygons item=Points}
			<Polygon>
				<outerBoundaryIs>
					<LinearRing>
						<coordinates>
							{$Points}
						</coordinates>
					</LinearRing>
				</outerBoundaryIs>
			</Polygon>
			{/foreach}
		</MultiGeometry>
	</Placemark>
	{/foreach}
