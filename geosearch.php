<?php

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');

?>
<!DOCTYPE html>
<html>
	<head>

		<meta charset="utf-8" />

		<!-- favicon -->
		<link href="static/biostor-shadow32x32.png" rel="icon" type="image/png">

		<title>
			BioStor-Lite &middot; GeoJSON search
		</title>

		<!-- base -->
		<base href="<?php echo $config['web_root']; ?>" /><!--[if IE]></base><![endif]-->

		<!--Let browser know website is optimized for mobile-->
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />

		<!--Import Google Icon Font-->
		<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

		<!-- local libraries (same set map.php uses) -->
		<script src="js/jquery.js"></script>
		<script src="js/ejs.js"></script>
		<link rel="stylesheet" type="text/css" href="css/materialize.min.css">
		<script type="text/javascript" src="js/materialize.min.js"></script>

		<link rel="stylesheet" type="text/css" href="js/leaflet-0.7.3/leaflet.css" />
		<script src="js/leaflet-0.7.3/leaflet.js" type="text/javascript"></script>

		<style>
			/* fixed footer, see https://materializecss.com/footer.html */
			body {
				display: flex;
				min-height: 100vh;
				flex-direction: column;
			}

			main {
				flex: 1 0 auto;
			}

			#map {
				width: auto;
				height: 100vh;
			}

			#results {
				height: 55vh;
				overflow-y: auto;
			}

			#geojson {
				font-family: monospace;
				font-size: 0.8em;
				height: 8em;
			}

			h1 {
				font-size: 2em;
			}

			/* locality dot on the map, matching BioStor's marker colour */
			.locality-dot {
				color: rgb(208, 104, 85);
				fillColor: rgb(208, 104, 85);
			}

			@media screen and (max-width: 600px) {
				#map {
					height: 50vh;
				}

				#results {
					height: 40vh;
				}
			}
		</style>

		<script>

			var map;
			var searchLayer = null;   // the uploaded/pasted GeoJSON polygon
			var pointsLayer = null;   // point localities found inside it

			//--------------------------------------------------------------------------------
			// Basic map, no drawing tools: this page displays supplied GeoJSON
			function create_map() {
				map = new L.Map('map');

				var osmUrl = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
				var osmAttrib = 'Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
				var osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 18, attribution: osmAttrib});

				map.setView(new L.LatLng(0, 0), 2);
				map.addLayer(osm);
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
					M.textareaAutoResize($('#geojson'));
					run_search();
				};
				reader.readAsText(input.files[0]);
			}

			//--------------------------------------------------------------------------------
			// Draw the supplied GeoJSON on the map. L.geoJson handles Polygon AND
			// MultiPolygon (and Feature/FeatureCollection) natively, so no hand-rolled
			// coordinate walking is needed, and getBounds() works for all of them.
			function show_geojson(geo) {
				if (searchLayer) {
					map.removeLayer(searchLayer);
					searchLayer = null;
				}

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
					// getBounds throws if the geometry had no drawable coordinates
					console.log('Could not fit bounds: ' + e);
				}
			}

			//--------------------------------------------------------------------------------
			// Plot the point localities that were found inside the polygon.
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
			// POST the GeoJSON (raw body) to api_geo.php and render the article list
			function run_search() {
				var text = document.getElementById('geojson').value;

				if (text.trim() === '') {
					M.toast({html: 'Please paste or upload some GeoJSON first'});
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

				// show what we are about to search
				show_geojson(geo);

				document.getElementById('results').innerHTML = 'Searching&hellip;';

				$.ajax({
					url: 'api_geo.php',
					type: 'POST',
					data: text,               // raw GeoJSON body; api_geo.php reads php://input
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
						<div class="row" style="margin-bottom:0;">
							<div class="col s3">
								<% if (data[i].thumbnailUrl) { %>
									<a href="reference/<%- ref %>" target="_new">
										<img class="z-depth-1" style="width:80px;background:white;" src="<%- data[i].thumbnailUrl %>?height=100">
									</a>
								<% } %>
							</div>
							<div class="col s9">
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

				<!-- map -->
				<div class="col s12 m8">
					<div class="row">
						<div id="map"></div>
					</div>
				</div>

				<!-- side panel -->
				<div id="sidepanel" class="col s12 m4">
					<div id="heading">
						<a href="./">BioStor</a>
						<h1>GeoJSON search</h1>
					</div>

					<p>Upload a GeoJSON file or paste GeoJSON below, then search for BioStor
					articles that have point localities inside that area. Polygons and
					MultiPolygons (e.g. islands) are both supported.</p>

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

					<div class="input-field">
						<textarea id="geojson" class="materialize-textarea"
							placeholder='{ "type": "Polygon", "coordinates": [ [ [lon,lat], ... ] ] }'></textarea>
						<label for="geojson">GeoJSON</label>
					</div>

					<button class="btn waves-effect waves-light" onclick="run_search();">
						Search
						<i class="material-icons right">search</i>
					</button>

					<div id="results" class="row" style="margin-top:1em;"></div>
				</div>

			</div>
		</main>

		<footer>
			<div class="container">
				<div class="row">
					<div class="divider"></div>
					<a href=".">BioStor-Lite</a> is a project by <a href="https://twitter.com/rdmpage">Rod Page</a>.
					Its goal is to make discoverable articles in the <a href="https://www.biodiversitylibrary.org">Biodiversity Heritage Library</a> (BHL).
					See also <a href="map.php">Map</a> and <a href="match.html">Match references</a>.
				</div>
			</div>
		</footer>

		<script>
			create_map();
		</script>
	</body>
</html>
