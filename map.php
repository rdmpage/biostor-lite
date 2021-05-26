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
		

		
		<!-- cloud -->
		<!-- <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.js"></script> -->
		<!-- <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.css">  -->
		<!-- <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.js"></script> -->		
		<!-- <script src="https://cdn.jsdelivr.net/npm/citation-js@0.4.0-7/build/citation.js" type="text/javascript"></script> -->
		<!-- <script src="https://cdn.jsdelivr.net/npm/ejs@2.6.1/ejs.min.js" integrity="sha256-ZS2YSpipWLkQ1/no+uTJmGexwpda/op53QxO/UBJw4I=" crossorigin="anonymous"> -->
  		 <!-- leaflet -->
		<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.css" /> -->
		<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.js" type="text/javascript"></script> -->
		
		
		
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
		/* body and main styles to give us a fixed footer, see https://materializecss.com/footer.html */	
		body {
			display: flex;
			min-height: 100vh;
			flex-direction: column;
		}

		main {
			flex: 1 0 auto;
		}		

		#map {
			width:auto;
			height:100vh;
		}

		#results {
			height:70vh;
			overflow-y:auto;
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
		var drawnItems = null;

		//--------------------------------------------------------------------------------
		// The large map where we display results
		function create_map() {
			map = new L.Map('map');

			// create the tile layer with correct attribution
			var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	
			/* This is where we can change the base map tiles */
			// GBIF
			// osmUrl = 'https://api.mapbox.com/v4/mapbox.outdoors/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoicmRtcGFnZSIsImEiOiJjajJrdmJzbW8wMDAxMnduejJvcmEza2k4In0.bpLlN9O6DylOJyACE8IteA';
	
			var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
			var osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 12, attribution: osmAttrib});		

			map.setView(new L.LatLng(0, 0),4);
			map.addLayer(osm);	
		
			/* This is where we add custom tiles, e.g. with data points */
			
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
				var type = e.layerType,
					layer = e.layer;

				drawnItems.addLayer(layer);
		
				// alert(JSON.stringify(layer.toGeoJSON()));
			
				console.log(JSON.stringify(layer.toGeoJSON()));

				do_geo_search(layer.toGeoJSON());
			
			});						
			
	
		}
		
		//--------------------------------------------------------------------------------
		function do_geo_search(geo) {
		
			// clear stuff
			document.getElementById('results').innerHTML = "Searching...";
			
			// move to where search is
			for (var i in geo.geometry.coordinates) {
				  minx = 180;
				  miny = 90;
				  maxx = -180;
				  maxy = -90;
		  
				  for (var j in geo.geometry.coordinates[i]) {
					minx = Math.min(minx, geo.geometry.coordinates[i][j][0]);
					miny = Math.min(miny, geo.geometry.coordinates[i][j][1]);
					maxx = Math.max(maxx, geo.geometry.coordinates[i][j][0]);
					maxy = Math.max(maxy, geo.geometry.coordinates[i][j][1]);
				  }
				}
			
			bounds = L.latLngBounds(L.latLng(miny,minx), L.latLng(maxy,maxx));
			map.fitBounds(bounds);			
				
			
			// console.log(JSON.stringify(geo_filter, null, 2));				
			
			$.getJSON('api.php?geo=' 
					+ encodeURI(JSON.stringify(geo))
					+ '&callback=?',
				function(data){
					console.log(JSON.stringify(data, null, 2));
					
							if (data.hits) {
								var hit_count = 0;
								
								if (typeof data.hits.total === 'object') {
									hit_count = data.hits.total.value;
								} else {
									hit_count = data.hits.total;
								}
							
								if (hit_count > 0) {
									var hits = [];
									for (var i in data.hits.hits) {
										hits[data.hits.hits[i]._id] = data.hits.hits[i]._source.search_result_data;
									}

									// Render template 	
									var html = ejs.render(template_results, { data : hits });
			
									// Display
									document.getElementById('results').innerHTML = html;
								}
								else
								{
									document.getElementById('results').innerHTML = 'Nothing found!';
								}
							}		
				
				});								
		}
		
		
			//------------------------------------------------------------------------------------
			// https://osric.com/chris/accidental-developer/2012/11/balancing-tags-in-html-and-xhtml-excerpts/
			
			// balance:
			// - takes an excerpted or truncated XHTML string
			// - returns a well-balanced XHTML string
			function balance(string) {
			  // Check for broken tags, e.g. <stro
			  // Check for a < after the last >, indicating a broken tag
			  if (string.lastIndexOf("<") > string.lastIndexOf(">")) {
				// Truncate broken tag
				string = string.substring(0,string.lastIndexOf("<"));
			  }
			
			  // Check for broken elements, e.g. &lt;strong&gt;Hello, w
			  // Get an array of all tags (start, end, and self-closing)
			  var tags = string.match(/<[^>]+>/g);
			  var stack = new Array();
			  for (tag in tags) {
				if (tags[tag].search("/") <= 0) {
				  // start tag -- push onto the stack
				  stack.push(tags[tag]);
				} else if (tags[tag].search("/") == 1) {
				  // end tag -- pop off of the stack
				  stack.pop();
				} else {
				  // self-closing tag -- do nothing
				}
			  }
			
			  // stack should now contain only the start tags of the broken elements,
			  // the most deeply-nested start tag at the top
			  while (stack.length > 0) {
				// pop the unmatched tag off the stack
				var endTag = stack.pop();
				// get just the tag name
				endTag = endTag.substring(1,endTag.search(/[ >]/));
				// append the end tag
				string += "</" + endTag + ">";
			  }
			
			  // Return the well-balanced XHTML string
			  return(string);
			}	
		
		
			        //--------------------------------------------------------------------------------
				var template_results = `
					<% for(var i in data) {%>
						<div class="card-panel small">
						
							<div class="row">
								<div class="col s3">
									<% if (data[i].thumbnailUrl)  {%>
											<a href="reference/<%- i.replace(/biostor-/, '') %>">
												<img class="z-depth-1" style="background:white;" src="https://aipbvczbup.cloudimg.io/s/height/100/<%- data[i].thumbnailUrl %>" >
											</a>
									<% } %>
								</div>
								
								<div class="col s9">
									<span class="black-text">
										<a href="reference/<%- i.replace(/biostor-/, '') %>" target="_new">
										<%- balance(data[i].name) %>
										</a>								
									</span>
									<br />
									<span style="grey-text">			
										<%- data[i].description %>
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
				<p>Each dot represents (latitude, longitude) pair that is mentioned in an article in BioStor. 
				Use the polygon or
				square drawing tools on the map to search for articles by region.
				<div id="results" class="row">
				</div>
			  </div>
				
			</div>
		</main>
		
 		
		
		<footer >
			<div class="container">
            	<div class="row">
            	<div class="divider"></div>
            		<a href=".">BioStor-Lite</a> is a project by <a href="https://twitter.com/rdmpage">Rod Page</a>. 
            		It' goal is to make discoverable articles in the <a href="https://www.biodiversitylibrary.org">Biodiversity Heritage Library</a> (BHL). 
            		See also <a href="match.html">Match references</a> reconciliation service.
            	</div>
            </div>
		</footer>
		
		
		<script>
			create_map();
		</script>
	</body>
</html>

