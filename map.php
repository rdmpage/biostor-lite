<?php

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');

?>

<!DOCTYPE html>
<html>
	<head>

		<!-- Google Analytics -->
		<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

		ga('create', 'UA-12127487-1', 'auto');
		ga('send', 'pageview');
		</script>
		<!-- End Google Analytics -->

		<meta charset="utf-8" />

   		<!-- favicon -->
		<link href="static/biostor-shadow32x32.png" rel="icon" type="image/png">

		<title>
			BioStor-Lite
		</title>
		<style>


/* dot on map */
.mydivicon{
    width: 12px
    height: 12px;
    border-radius: 10px;
    background: rgb(208,104,85);
    border: 1px solid rgb(38,38,38);
    opacity: 0.85
}


section.covers{
  display: flex;
  flex-wrap: wrap;
}

section.covers::after{
  content: \'\';
  flex-grow: 999999999;
}

div.covers{
  flex-grow: 1;
  margin: 4px;
  height: 160px;
}

img.covers{
  height: 160px;
  object-fit: contain;
  max-width: 100%;
  min-width: 100%;
  vertical-align: bottom;
}

section.works{
  display: flex;
  flex-wrap: wrap;
}

section.works::after{
  content: \'\';
  flex-grow: 999999999;
}

div.works{
  /*flex-grow: 1;*/
  margin: 2px;
  height: 120px;
  width:80px;
  border:1px solid #b2dfdb;
  overflow-wrap:break-word;
  overflow:hidden;
  font-size:1em;
  line-height:1.0em;
  padding:0px;
  position:relative;
}

div.works.year {
	text-align:center;
	line-height:120px;
	font-size:2em;
	padding:0px;
	color:#004d40 ;
}

a.works {
	text-decoration:none;
	color:#004d40;

}

img.works{
  object-fit: cover;
  max-width: 100%;
  min-width: 100%;
  vertical-align: bottom;
}

span.works {
	font-size:0.7em;
	line-height:1em;
	position:absolute;
	overflow-wrap:break-word;
	overflow:hidden;
	left:0px;
	top:60px;
	height:60px;
	width:100%;
	background-color:rgba(13, 77, 64, 0.3);
	/*color:white;&*/
	z-index:10;
	padding:4px;
}

		</style>


		<!-- base -->
    	<base href="<?php echo $config['web_root']; ?>" /><!--[if IE]></base><![endif]-->


		<!--Let browser know website is optimized for mobile-->
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />

		<!--Import Google Icon Font-->
		<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">


		<!-- local --->
		<script src="js/jquery.js"></script>
		<script src="js/ejs.js"></script>
		<script src="js/citation.js" type="text/javascript"></script>
		<link rel="stylesheet" type="text/css" href="css/materialize.min.css">
		<script type="text/javascript" src="js/materialize.min.js"></script>


		<link rel="stylesheet" type="text/css" href="js/leaflet-0.7.3/leaflet.css" />
		<script src="js/leaflet-0.7.3/leaflet.js" type="text/javascript"></script>

		<link rel="stylesheet" href="js/leaflet.draw/leaflet.draw.css" />
		<script src="js/leaflet.draw/leaflet.draw.js" type="text/javascript"></script>


	<style>
		/* body and main styles; no footer, page is sized to one viewport */
		body {
			display: flex;
			min-height: 100vh;
			flex-direction: column;
		}

		main {
			flex: 1 0 auto;
		}

		/* kill the default Materialize row margin so the map fills the viewport
		   exactly and the page does not scroll on desktop */
		main .row {
			margin-bottom: 0;
		}

		#map {
			width:auto;
			height:100vh;
		}

		#results {
			height:45vh;
			overflow-y:auto;
		}

		/* Fixed-height, internally scrolling GeoJSON box. We deliberately do NOT
		   use Materialize's .materialize-textarea class, which auto-grows the field
		   as you move through the text and pushes the rest of the layout down. */
		#geojson {
			font-family: monospace;
			font-size: 0.8em;
			height: 8em;
			width: 100%;
			box-sizing: border-box;
			overflow-y: auto;
			resize: vertical;
			padding: 6px 8px;
			border: 1px solid #9e9e9e;
			border-radius: 2px;
		}

		#geojson-label {
			display: block;
			font-size: 0.8rem;
			color: #9e9e9e;
			margin-bottom: 4px;
		}

		h1 {
			font-size:2em;
			visibility: visible;
		}

		#heading {
			visibility: visible;
		}

		@media screen and (max-width: 600px) {
			#map {
				height:50vh;
			}

			#results {
				height:40vh;
			}

			#heading {
				visibility: hidden;
				height:0px;
			}

			h1 {
				visibility: hidden;
				margin:0px;
				padding:0px;
			}
		}
	</style>

  <script>

		var map;
		var drawnItems = null;   // shapes drawn on the map
		var searchLayer = null;  // uploaded / pasted GeoJSON polygon
		var pointsLayer = null;  // point localities found inside the area

		//--------------------------------------------------------------------------------
		// The large map where we display results
		function create_map() {
			map = new L.Map('map');

			// create the tile layer with correct attribution
			var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';

			var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
			var osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 12, attribution: osmAttrib});

			map.setView(new L.LatLng(0, 0),4);
			map.addLayer(osm);

			/* BioStor data points as tiles */
			var dotsAttrib='BioStor';
			var dots = new L.TileLayer('tile.php?x={x}&y={y}&z={z}',
				{minZoom: 0, maxZoom: 14, attribution: dotsAttrib});

			map.addLayer(dots);

			drawnItems = new L.FeatureGroup();
			map.addLayer(drawnItems);

			var drawControl = new L.Control.Draw({
				position: 'topleft',
				draw: {
					marker: false, // turn off marker
					polygon: {
						shapeOptions: {
							color: 'purple'
						},
						allowIntersection: false,
						drawError: {
							color: 'orange',
							timeout: 1000
						},
						showArea: true,
						metric: false,
						repeatMode: true
					},
					polyline: false,
					rect: {
						shapeOptions: {
							color: 'green'
						},
					},
					circle: false
				},
				edit: {
					featureGroup: drawnItems
				}
			});
			map.addControl(drawControl);

			map.on('draw:created', function (e) {
				var layer = e.layer;

				// a freshly drawn shape replaces any previous search area
				clear_search_shapes();
				drawnItems.addLayer(layer);

				try {
					map.fitBounds(layer.getBounds());
				} catch (err) {
				}

				var geo = layer.toGeoJSON();

				// Put the drawn shape's GeoJSON in the text box so the user can
				// copy it or tweak and re-run the same search.
				document.getElementById('geojson').value = JSON.stringify(geo, null, 2);
				update_copy_button();

				do_search(geo);
			});


		}

		//--------------------------------------------------------------------------------
		// Remove any previously drawn / uploaded search area from the map
		function clear_search_shapes() {
			if (drawnItems) {
				drawnItems.clearLayers();
			}
			if (searchLayer) {
				map.removeLayer(searchLayer);
				searchLayer = null;
			}
		}

		//--------------------------------------------------------------------------------
		// Read the file chooser, drop its contents into the textarea, and search
		function handle_file(input) {
			if (!input.files || input.files.length === 0) {
				return;
			}
			var reader = new FileReader();
			reader.onload = function (e) {
				document.getElementById('geojson').value = e.target.result;
				update_copy_button();
				run_search();
			};
			reader.readAsText(input.files[0]);
		}

		//--------------------------------------------------------------------------------
		// Draw supplied GeoJSON on the map. L.geoJson handles Polygon AND MultiPolygon
		// (and Feature / FeatureCollection) natively, so getBounds() works for all of
		// them without any hand-rolled coordinate walking.
		function show_geojson(geo) {
			searchLayer = L.geoJson(geo, {
				style: {
					color: 'purple',
					weight: 2,
					fillOpacity: 0.1
				}
			});
			map.addLayer(searchLayer);

			try {
				map.fitBounds(searchLayer.getBounds());
			} catch (e) {
				console.log('Could not fit bounds: ' + e);
			}
		}

		//--------------------------------------------------------------------------------
		// Search from the textarea (also used by the file uploader)
		function run_search() {
			var text = document.getElementById('geojson').value;

			if (text.trim() === '') {
				M.toast({html: 'Please paste or upload some GeoJSON, or draw on the map'});
				return;
			}

			var geo;
			try {
				geo = JSON.parse(text);
			} catch (e) {
				document.getElementById('results').innerHTML =
					'<p class="red-text">That is not valid JSON: ' + e + '</p>';
				return;
			}

			clear_search_shapes();
			show_geojson(geo);
			do_search(geo);
		}

		//--------------------------------------------------------------------------------
		// Copy the current GeoJSON to the clipboard
		function copy_geojson() {
			var text = document.getElementById('geojson').value;

			if (text.trim() === '') {
				M.toast({html: 'Nothing to copy yet'});
				return;
			}

			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(text).then(
					function () { M.toast({html: 'GeoJSON copied to clipboard'}); },
					function () { fallback_copy(); }
				);
			} else {
				fallback_copy();
			}
		}

		// Fallback for browsers without the async clipboard API (or non-secure origins)
		function fallback_copy() {
			var ta = document.getElementById('geojson');
			ta.focus();
			ta.select();
			try {
				document.execCommand('copy');
				M.toast({html: 'GeoJSON copied to clipboard'});
			} catch (e) {
				M.toast({html: 'Could not copy'});
			}
		}

		// Grey out the Copy button when there is nothing to copy, otherwise show it
		// in the normal button colour like the other buttons.
		function update_copy_button() {
			var btn = document.getElementById('copy-btn');
			if (!btn) {
				return;
			}
			if (document.getElementById('geojson').value.trim() === '') {
				btn.classList.add('grey', 'lighten-1');
			} else {
				btn.classList.remove('grey', 'lighten-1');
			}
		}

		//--------------------------------------------------------------------------------
		// Plot the point localities found inside the area.
		// api_geo.php returns coordinates as [lon, lat]; Leaflet wants [lat, lon].
		function show_points(articles) {
			if (pointsLayer) {
				map.removeLayer(pointsLayer);
				pointsLayer = null;
			}

			pointsLayer = L.layerGroup();

			for (var i in articles) {
				var coords = articles[i].coordinates || [];
				for (var j in coords) {
					var lon = coords[j][0];
					var lat = coords[j][1];
					var marker = L.circleMarker([lat, lon], {
						radius: 5,
						color: 'rgb(38,38,38)',
						weight: 1,
						fillColor: 'rgb(208,104,85)',
						fillOpacity: 0.85
					});
					marker.bindPopup(articles[i].name || articles[i].id);
					pointsLayer.addLayer(marker);
				}
			}

			map.addLayer(pointsLayer);
		}

		//--------------------------------------------------------------------------------
		// POST the GeoJSON (raw body) to api_geo.php and render the article list.
		// Works for both drawn shapes (layer.toGeoJSON()) and uploaded/pasted GeoJSON.
		function do_search(geo) {
			document.getElementById('results').innerHTML = 'Searching&hellip;';

			$.ajax({
				url: 'api_geo.php',
				type: 'POST',
				data: JSON.stringify(geo),   // raw GeoJSON body; api_geo.php reads php://input
				contentType: 'application/json',
				dataType: 'text'
			})
			.done(function (response) {
				var data;
				try {
					data = JSON.parse(response);
				} catch (e) {
					document.getElementById('results').innerHTML =
						'<p class="red-text">Unexpected response from server.</p>';
					return;
				}

				if (data.status && data.status >= 400) {
					document.getElementById('results').innerHTML =
						'<p class="red-text">' + (data.message || 'Search failed') + '</p>';
					return;
				}

				var articles = data.articles || [];

				if (articles.length === 0) {
					document.getElementById('results').innerHTML = 'Nothing found in this area.';
					return;
				}

				show_points(articles);

				var html = ejs.render(template_results, {total: data.total, data: articles});
				document.getElementById('results').innerHTML = html;
			})
			.fail(function (xhr) {
				document.getElementById('results').innerHTML =
					'<p class="red-text">Search request failed (' + xhr.status + ').</p>';
			});
		}


			        //--------------------------------------------------------------------------------
				// Result template. Uses the trimmed fields api_geo.php returns:
				// id, name, url, thumbnailUrl, csl, year, container, authors, coordinates
				var template_results = `
					<p class="grey-text"><%- total %> article<%- (total == 1 ? '' : 's') %> found</p>
					<% for (var i in data) { %>
						<% var ref = String(data[i].id).replace(/biostor-/, ''); %>
						<div class="card-panel small">
							<div style="display:flex; align-items:flex-start;">
								<% if (data[i].thumbnailUrl) { %>
									<div style="flex:0 0 60px; margin-right:12px;">
										<a href="reference/<%- ref %>" target="_new">
											<img class="z-depth-1" style="width:60px;background:white;" src="<%- data[i].thumbnailUrl %>?height=100">
										</a>
									</div>
								<% } %>
								<div style="flex:1 1 auto; min-width:0; overflow-wrap:break-word;">
									<span class="black-text">
										<a href="reference/<%- ref %>" target="_new"><%- data[i].name %></a>
									</span>
									<br />
									<span class="grey-text">
										<% if (data[i].authors) { %><%- data[i].authors.join(', ') %><% } %>
										<% if (data[i].year) { %>(<%- data[i].year %>)<% } %>
										<% if (data[i].container) { %>&mdash; <em><%- [].concat(data[i].container).join('; ') %></em><% } %>
									</span>
									<br />
									<span class="grey-text" style="font-size:0.8em;">
										<%- (data[i].coordinates ? data[i].coordinates.length : 0) %>
										localit<%- ((data[i].coordinates ? data[i].coordinates.length : 0) == 1 ? 'y' : 'ies') %> in this area
									</span>
								</div>
							</div>
						</div>
					<% } %>
				`;



    	</script>

	</head>
	<body>
		<header></header>
		<main>
			<div class="row">

			 <div class="col s12 m8">

			   <div class="row">
				  <div id="map"></div>
				</div>
			  </div><!-- end main panel -->

			  <!-- side panel -->
			  <div id="sidepanel" class="col s12 m4" >
			  	<div id="heading">
			  		<a href="./">BioStor</a>
			  		<h1>Map</h1>
			  	</div>
				<p>Each dot represents a (latitude, longitude) pair mentioned in an article in BioStor.
				Use the polygon or square drawing tools on the map to search by region, or upload / paste
				GeoJSON below. Polygons and MultiPolygons (e.g. islands) are both supported.</p>

				<div class="file-field input-field">
					<div class="btn">
						<span>File</span>
						<input type="file" accept=".geojson,.json,application/geo+json,application/json"
							onchange="handle_file(this);">
					</div>
					<div class="file-path-wrapper">
						<input class="file-path validate" type="text" placeholder="Upload a .geojson file">
					</div>
				</div>

				<div style="display:flex; align-items:center; justify-content:space-between;">
					<label id="geojson-label" for="geojson">GeoJSON</label>
					<a id="copy-btn" class="btn-small waves-effect waves-light" onclick="copy_geojson();"
						title="Copy GeoJSON to clipboard" style="margin-bottom:4px; cursor:pointer;">
						<i class="material-icons left">content_copy</i>Copy
					</a>
				</div>
				<textarea id="geojson" oninput="update_copy_button();"
					placeholder='{ "type": "Polygon", "coordinates": [ [ [lon,lat], ... ] ] }'></textarea>

				<button class="btn waves-effect waves-light" onclick="run_search();">
					Search
					<i class="material-icons right">search</i>
				</button>

				<div id="results" class="row" style="margin-top:1em;">
				</div>
			  </div>

			</div>
		</main>

		<script>
			create_map();
			update_copy_button();
		</script>
	</body>
</html>
