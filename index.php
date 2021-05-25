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
	
	/* body and main styles to give us a fixed footer, see https://materializecss.com/footer.html */	
body {
    display: flex;
    min-height: 100vh;
    flex-direction: column;
  }
  
/* https://codepen.io/furnace/pen/PGygEd */
nav.clean {
  background: none;
  box-shadow: none;
  height:2em;
  line-height:2em;
  
}
nav.clean .breadcrumb {
  color: black;
  font-size:1em;
}
nav.clean .breadcrumb:before {
  color: rgba(0, 0, 0, 0.7);
}


  main {
    flex: 1 0 auto;
  }		
			
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
		
		<script>
			const Cite = require('citation-js')
		</script>

		<script>
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
		</script>
		
		
    
  <script>
    
		var map;
		var geojson = null;

    		
		// http://gis.stackexchange.com/a/116193
		// http://jsfiddle.net/GFarkas/qzdr2w73/4/
    	// The most important part is the border-radius property. 
    	// It will round your shape at the corners. To create a regular circle with it, 
    	// you have to calculate the radius with the border. 
    	// The formula is width / 2 + border * 4 if width = height.
		var icon = new L.divIcon({className: 'mydivicon'});		

		//--------------------------------------------------------------------------------
		function onEachFeature(feature, layer) {
			// does this feature have a property named popupContent?
			if (feature.properties && feature.properties.popupContent) {
				//console.log(feature.properties.popupContent);
				// content must be a string, see http://stackoverflow.com/a/22476287
				layer.bindPopup(String(feature.properties.popupContent));
			}
		}	
			
		//--------------------------------------------------------------------------------
		function create_map() {
			map = new L.Map('map');

			// create the tile layer with correct attribution
			var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
			var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
			var osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 12, attribution: osmAttrib});		

			map.setView(new L.LatLng(0, 0),4);
			map.addLayer(osm);		
		}
		
		//--------------------------------------------------------------------------------
		function clear_map() {
			if (geojson) {
				map.removeLayer(geojson);
			}
		}	
	
		//--------------------------------------------------------------------------------
		function add_data(data) {
			clear_map();
		
			geojson = L.geoJson(data, { 

			pointToLayer: function (feature, latlng) {
                return L.marker(latlng, {
                    icon: icon});
            },			
			style: function (feature) {
				return feature.properties && feature.properties.style;
			},
			onEachFeature: onEachFeature,
			}).addTo(map);
			
			// Open popups on hover
  			geojson.on('mouseover', function (e) {
    			e.layer.openPopup();
  			});
		
			if (data.type) {
				if (data.type == 'Polygon') {
					for (var i in data.coordinates) {
					  minx = 180;
					  miny = 90;
					  maxx = -180;
					  maxy = -90;
				  
					  for (var j in data.coordinates[i]) {
						minx = Math.min(minx, data.coordinates[i][j][0]);
						miny = Math.min(miny, data.coordinates[i][j][1]);
						maxx = Math.max(maxx, data.coordinates[i][j][0]);
						maxy = Math.max(maxy, data.coordinates[i][j][1]);
					  }
					}
					
					bounds = L.latLngBounds(L.latLng(miny,minx), L.latLng(maxy,maxx));
					map.fitBounds(bounds);
				}
				if (data.type == 'MultiPoint') {
					minx = 180;
					miny = 90;
					maxx = -180;
					maxy = -90;				
					for (var i in data.coordinates) {
						minx = Math.min(minx, data.coordinates[i][0]);
						miny = Math.min(miny, data.coordinates[i][1]);
						maxx = Math.max(maxx, data.coordinates[i][0]);
						maxy = Math.max(maxy, data.coordinates[i][1]);
					}
					
					bounds = L.latLngBounds(L.latLng(miny,minx), L.latLng(maxy,maxx));
					map.fitBounds(bounds);
				}
				if (data.type == 'FeatureCollection') {
					minx = 180;
					miny = 90;
					maxx = -180;
					maxy = -90;				
					for (var i in data.features) {
						//console.log(JSON.stringify(data.features[i]));
					
						minx = Math.min(minx, data.features[i].geometry.coordinates[0]);
						miny = Math.min(miny, data.features[i].geometry.coordinates[1]);
						maxx = Math.max(maxx, data.features[i].geometry.coordinates[0]);
						maxy = Math.max(maxy, data.features[i].geometry.coordinates[1]);
						
					}
					
					bounds = L.latLngBounds(L.latLng(miny,minx), L.latLng(maxy,maxx));
					map.fitBounds(bounds);
				}
			}		    					
		}
    	</script>
		
		
		<script>
		
			        //--------------------------------------------------------------------------------
				var template_decades = `
					<% for(var decade in data) {%>
						<li>
							<div class="collapsible-header"><%= (decade * 10) %></div>
							<div class="collapsible-body">
					
								<div class="row">
								
								<section class="works">
							
						
								<% for (var year in data[decade]) { %>
						
									<div class="works year teal lighten-2 ">
										<%= year %>
									</div>
							
									<% for (var i in data[decade][year]) { %>
										<div class="works">
											<!-- <%= data[decade][year][i].name %> -->
											<a class="works" href="reference/<%- i.replace(/biostor-/, '') %>">
											<img class="works" src="https://aipbvczbup.cloudimg.io/s/height/200/<%= data[decade][year][i].thumbnailUrl %>">
											
											<span class="works"><%= data[decade][year][i].name %></span>
											</a>
										</div>
									<% } %>

								<% } %>
								
								</section>
							
								</div>
							
							
							
						</div>
						</li>
						
			
					<% } %>
			
				`;				
				

						
			
			
			        //--------------------------------------------------------------------------------
				var template_results = `
					<div>
			
					<% for(var i in data) {%>
						<div class="row">
						
							<div class="col s12 m2 hide-on-small-only" style="text-align:center">
								<% if (data[i].thumbnailUrl)  {%>
									<a href="reference/<%- i.replace(/biostor-/, '') %>">
										<img class="z-depth-1" style="background:white;" src="https://aipbvczbup.cloudimg.io/s/height/100/<%- data[i].thumbnailUrl %>" >
									</a>
								<% } %>
							</div>
					
							<div class="col s12 m10">
								<div style="font-size:1.5em;line-height:1.2em;">
									<a href="reference/<%- i.replace(/biostor-/, '') %>">
									<%- balance(data[i].name) %>
									</a>
								</div>
							
								<div>
								<span style="color:rgb(64,64,64);">			
									<%- data[i].description %>
								</span>
								</div>
							</div>

						</div>
						
			
					<% } %>
					</div>
			
				`;				
				
			        //--------------------------------------------------------------------------------
				var template_record = `
				<%
				
			//----------------------------------------------------------------------------------------
			// Convert ISO data to a human-readable string (PubMed-style)
			// My databases use -00 to indicate no month or no day, and this confuses Javascript
			// Date so we need to set the options appropriately
			isodate_to_string = function (datestring) {
			
			// By default assume datestring is a year only
			var options = {};
			options.year = 'numeric';
			
			// Test for valid month, then day (because we use -00 to indicate no data)
			var m = null;
			
			if (!m) {	
				m = datestring.match(/^([0-9]{4})$/);
				if (m) {
					// year only
					datestring = m[1]; 
				}
			}
			
			if (!m) {		
				m = datestring.match(/^([0-9]{4})-([0-9]{2})-00/);
				if (m) {
					
					if (m[2] == '00') {
						// Javascript can't handle -00-00 date string so set to January 1st 
						// which won't be output as we're only outputting the year
						datestring = m[1] + '-01-01';
					} else {
						// We have a month but no day
						datestring = m[1] + '-' + m[2] + '-01';
						options.month = 'short';
					}		
				}
			}
			
			if (!m) {	
				m = datestring.match(/^([0-9]{4})-([0-9]{2})-([0-9]{2})/);
				if (m) {
					// we have yea, month, and day
					options.month = 'short';
					options.day = 'numeric';
				}
			}
			
			var d = new Date(datestring);
			datestring = d.toLocaleString('en-gb', options);
			
			return datestring;		
			}		
			
			%>
			
					<div class="row">
					
					<div class="col s12 m2 hide-on-small-only">
										
							<% if (data.thumbnailUrl)  {%>
								<img  class="z-depth-2" style="background:white;" src="https://aipbvczbup.cloudimg.io/s/height/100/<%- data.thumbnailUrl %>" >
							<% } %>

					</div>
					
					<div class="col s12 m10">
					
					<!-- bread crumbs -->
					<% if (data.csl['container-title'] && data.csl.ISSN) { %>
						<nav class="clean">
							<div class="nav-wrapper">
								<a href="./" class="breadcrumb">Home</a>
								
								<% 
								var container = data.csl['container-title'];
								if (container.length > 30) {
									container = container.substring(0,30) + '…';
								}
								%>
								
								<a href="./issn/<%- data.csl.ISSN %>" class="breadcrumb">
									<%- container %>
								</a>
								
								<% if (data.csl.issued) { %>
									<a href="issn/<%- data.csl.ISSN[0] %>/year/<%- data.csl.issued['date-parts'][0][0] %>" class="breadcrumb"><%- data.csl.issued['date-parts'][0][0] %></a>
								<% } %>
							</div>
						</nav>
					<% } %>
					
					<% if (data.csl['container-title'] && data.csl.ISBN) { %>
						<nav class="clean">
							<div class="nav-wrapper">
								<a href="./" class="breadcrumb">Home</a>
								
								<% 
								var container = data.csl['container-title'];
								if (container.length > 30) {
									container = container.substring(0,30) + '…';
								}
								%>
								
								<a href="./isbn/<%- data.csl.ISBN %>" class="breadcrumb">
									<%- container %>
								</a>
							</div>
						</nav>
					<% } %>
					
								
					<!-- headline is item name -->
					<b style="font-size:1.5em;">				
						<%- data.name %>				
					</b>
					
					<!-- authors -->
					<% if (data.csl.author) { %>
						<div class="section">					
						<% for(var i in data.csl.author) {
							var parts = [];
							if (data.csl.author[i].literal) {
								parts.push(data.csl.author[i].literal);
							} else {
								if (data.csl.author[i].given) {
									parts.push(data.csl.author[i].given);
								}
								if (data.csl.author[i].family) {
									parts.push(data.csl.author[i].family);
								}
							} %>
							<div class="chip">
								<a href="?q=<%- encodeURIComponent(parts.join(' ')) %>">
								<%- parts.join(' ') %>
								</a>
							</div>
						<% } %>
						</div>
					<% } %>
					
					<!-- publication outlet -->
					<div>
						<% if (data.csl['container-title']) { %>
							Published in
							<em>
							<%- data.csl['container-title'] %>
							</em>
						<% } %>
						
						<% if (data.csl.volume) { %>
							<%- data.csl.volume %>
						<% } %>
			
						<% if (data.csl.page) { %>
							pages
							<%- data.csl.page %>
						<% } %>
						
						<% if (data.csl.issued) {
							var date_parts = [];
							date_parts.push(data.csl.issued['date-parts'][0][0]);
							if (data.csl.issued['date-parts'][0][1]) {
								date_parts.push(String("00" + data.csl.issued['date-parts'][0][1]).slice(-2));
							}
							if (data.csl.issued['date-parts'][0][2]) {
								date_parts.push(String("00" + data.csl.issued['date-parts'][0][2]).slice(-2));
							}
						    var datestring = date_parts.join('-'); %>
						    (
							<%- isodate_to_string(datestring) %>
							)
						<% } %>
					</div>
													
					<!-- actions -->
					<div class="section" >
						<a class="btn" onclick="show_cite('<%- encodeURIComponent(JSON.stringify(data.csl)).replace(/\'/g, "\\\\'") %>')";><i class="material-icons">format_quote</i></a>					
						<% if (data.url)  {%>
							<a class="btn" href="<%- data.url %>" onClick="ga('send', 'event', { eventCategory: 'Outbound Link', eventAction: 'BHL', eventLabel: event.target.href} );">View at BHL</a>
						<% } %>	

						<% if (data.csl.URL)  {
						    var biostor_id = data.csl.URL;
						    biostor_id = biostor_id.replace('https://biostor.org/reference/', '');
							var manifest = 'https://iiif.archivelab.org/iiif/biostor-' + biostor_id + '/manifest.json';
						%>
							<a id="iiif" style="display:none;" class="btn" href="viewer/viewer.php?manifest_uri=<%- manifest %>" onClick="ga('send', 'event', { eventCategory: 'Outbound Link', eventAction: 'IIIF', eventLabel: event.target.href} );">View IIIF</a>
						<% } %>	
						
						
						<% if (data.csl.DOI)  {%>
							<a class="btn" href="https://doi.org/<%- data.csl.DOI %>" onClick="ga('send', 'event', { eventCategory: 'Outbound Link', eventAction: 'DOI', eventLabel: event.target.href} );">DOI:<%- data.csl.DOI %></a>
						<% } %>	


						<!-- PDF -->
						<a id="pdf" style="display:none;" class="btn" href="" onClick="ga('send', 'event', { eventCategory: 'Outbound Link', eventAction: 'PDF', eventLabel: event.target.href} );">PDF</a>												
						
						
					</div>
					
					<!-- map -->
					<div id="map" class="section" style="width:100%; height:300px;">
					</div>
				`;		
				
			        //--------------------------------------------------------------------------------
				function show_record(id) {
					document.getElementById('results').innerHTML = "Retrieving...";

					$.getJSON('./api.php?id=' + encodeURIComponent(id)
							+ '&callback=?',
						function(data){ 
							if (data._source) {
			
								//console.log(JSON.stringify(data._source.search_result_data.csl));
								
								//history.pushState(null, data._source.search_result_data.name, id.replace(/biostor-/, 'reference/'));
			
								// Render template 	
								html = ejs.render(template_record, { data: data._source.search_result_data });
			
								// Display
								document.getElementById('results').innerHTML = html;
								
								// Map
								if (data._source.search_data.geometry) {
									create_map();
    	        					add_data(data._source.search_data.geometry);
    	        				}
    	        				
    	        				// Do we have a PDF?
    	        				have_pdf(id);
    	        				
    	        				
							}
						}
					);
				}
				
			        //--------------------------------------------------------------------------------
				function have_pdf(id) {
				
					var pdf_url = 'https://archive.org/download/' + id + '/' + id + '.pdf';
					
					$.getJSON('./api.php?pdf=' + encodeURIComponent(pdf_url)
							+ '&callback=?',
						function(data){ 
							if (data.found) {
								// show PDF button
								var e = document.getElementById('pdf');
								if (e) {
									e.style.display = 'inline-block';
									//e.href = pdf_url;
									e.href = 'pdfproxy.php?url=' + encodeURIComponent(pdf_url);
								}
								// if we have PDF we have IIIF
								e = document.getElementById('iiif');
								if (e) {
									e.style.display = 'inline-block';
								}
							}
						}
					);
				}
				
				
				
			        //--------------------------------------------------------------------------------
				function show_cite(csl) {
				
					csl = decodeURIComponent(csl);
					
					var data = new Cite(csl);
											
					var template_cite = `
					<h5>Cite</h5>
					<table>
						<tr>
							<td style="vertical-align:top;font-weight:bold;">APA</td>
							<td>
								<%- data.format('bibliography', {format: 'html', template: 'apa', lang: 'en' }); %>
							</td>
						</tr>
						<tr>
							<td style="vertical-align:top;font-weight:bold;">BibTeX</td>
							<td>
								<div style="font-family:monospace;white-space:pre;">
<%=	data.format('bibtex'); %>
								</div>
							</td>
						</tr>
						<tr>
							<td style="vertical-align:top;font-weight:bold;">RIS</td>
							<td>
								<div style="font-family:monospace;white-space:pre;">
<%=	data.format('ris'); %>
								</div>
							</td>
						</tr>
					</table>										
					`;
					
					var html = ejs.render(template_cite, { data: data });
			
					// Display
					document.getElementById('modal-content').innerHTML = html;
					$('#modal').modal('open');
				}		
				
			        //--------------------------------------------------------------------------------
				// http://stackoverflow.com/a/11407464
				$(document).keypress(function(event){		
					var keycode = (event.keyCode ? event.keyCode : event.which);			
					if(keycode == '13'){
						search();   
					}
				});    
			    
			        //--------------------------------------------------------------------------------
				//http://stackoverflow.com/a/25359264
				$.urlParam = function(name){
					var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
					if (results==null){
					   return null;
					}
					else{
					   return results[1] || 0;
					}
				}        
				
			        //--------------------------------------------------------------------------------
				function search() {      
			      	document.activeElement.blur();
			      	document.getElementById('collapsible').innerHTML = "";
			      	document.getElementById('results').innerHTML = "Searching...";
			      	
					var text = document.getElementById('query').value;
					
					var m = text.match(/item:(\d+)/);
					if (m) {
						window.location.replace('item.php?item=' + m[1]);					
					}
					
					// Add query to browser history
					history.pushState(null, null, "?q=" + encodeURIComponent(text));

				
					$.getJSON('./api.php?q=' + encodeURIComponent(text)
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
					
						}
					);  			
				}	
				
			        //--------------------------------------------------------------------------------
				function show_isbn(isbn) {      
			      	document.getElementById('results').innerHTML = "Searching...";
			      
					$.getJSON('./api_journal.php?isbn=' + isbn 
							+ '&callback=?',			
						function(data){
					
							console.log(JSON.stringify(data, null, 2));
								
							if (data.hits) {
								if (data.hits.total > 0) {
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
					
						}
					);  			
				}	
									
				
			        //--------------------------------------------------------------------------------
				function show_issn_year(issn, year) {      
			      	document.getElementById('results').innerHTML = "Searching...";
			      
					var text = document.getElementById('query').value;
									
					$.getJSON('./api_journal.php?issn=' + issn + '&year=' + year
							+ '&callback=?',			
						function(data){
					
							console.log(JSON.stringify(data, null, 2));
								
							if (data.hits) {
								if (data.hits.total > 0) {
									var hits = [];
									for (var i in data.hits.hits) {
										// filter out approximate ISSN matches (horrible kludge)
										var ok = true;
											
										if (data.hits.hits[i]._source.search_result_data.csl.ISSN) {
											ok = (data.hits.hits[i]._source.search_result_data.csl.ISSN[0] == issn);
										} else {
											ok = false;
										}
										
										if (ok) {
											hits[data.hits.hits[i]._id] = data.hits.hits[i]._source.search_result_data;
										}
											
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
					
						}
					);  			
				}	
				
			        //--------------------------------------------------------------------------------
				function show_issn(issn) {      
			      	document.getElementById('results').innerHTML = "Searching...";
			      
					var text = document.getElementById('query').value;
									
					$.getJSON('./api_journal.php?issn=' + issn 
							+ '&callback=?',			
						function(data){
					
							console.log(JSON.stringify(data, null, 2));
								
							if (data.hits) {
								if (data.hits.total > 0) {
								
									var container = '';
								
									var hits = [];
									var decades = {};
									for (var i in data.hits.hits) {
										// filter out approximate ISSN matches (horrible kludge)
										var ok = true;
											
										if (data.hits.hits[i]._source.search_result_data.csl.ISSN) {
											ok = (data.hits.hits[i]._source.search_result_data.csl.ISSN[0] == issn);
										} else {
											ok = false;
										}
										
										if (ok) {
											hits[data.hits.hits[i]._id] = data.hits.hits[i]._source.search_result_data;
											
											if (container == '') {
												container = data.hits.hits[i]._source.search_result_data.csl['container-title'];
											}
											
											if (data.hits.hits[i]._source.search_result_data.csl.issued) {
												var year = data.hits.hits[i]._source.search_result_data.csl.issued['date-parts'][0][0];
												var decade = Math.floor(year / 10);
			
												if (!decades[decade]) {
													decades[decade] = {};
												}
												if (!decades[decade][year]) {
													decades[decade][year] = {};
												}
												decades[decade][year][data.hits.hits[i]._id] = data.hits.hits[i]._source.search_result_data;												
											
											}
											
											
										}
											
									}

									// Render template 	
									var html = ejs.render(template_decades, { data : decades });
			
									// Display
									//document.getElementById('results').innerHTML = JSON.stringify(decades);
									document.getElementById('collapsible').innerHTML = html;
									$('.collapsible').collapsible('open', 1);
									
									html = '<nav class="clean">';
									html += '<div class="nav-wrapper">';
									html += '<a href="./" class="breadcrumb">Home</a>';
									html += '<a href="./issn/' + issn + '" class="breadcrumb">' + container + '</a>';
									html += '</div>';
									html += '</nav>';

									document.getElementById('results').innerHTML = html;
								}
								else
								{
									document.getElementById('results').innerHTML = 'Nothing found!';
								}
							}		
					
						}
					);  			
				}										
			
			
		</script>
		<script type="text/javascript">
			window.onload=function(){
			  
					$(document).ready(function() {
					  $('#modal').modal(); 	
					  $('.collapsible').collapsible();				 
					});
					
					
			   }
		</script>
	</head>
	<body>
		<header></header>
		<main>
			<div class="container">
	<!-- search box -->
				<div class="row">
					<div class="input-field col s12">
						<i class="material-icons prefix">
							search
						</i>
						<input style="font-size:2em;" type="text" id="query" placeholder="Search"> 
					</div>
	<!-- <button class="btn-large type="submit" style="font-size:2em;" id="search" onclick="search();">Find</button> -->
				</div>
	<!-- Modal popup -->
				<div id="modal" class="modal" style="z-index: 1003; display: none; opacity: 0; transform: scaleX(0.7); top: 4%;">
					<div class="modal-content">
						<div id="modal-content">
							Content
						</div>
					</div>
					<div class="modal-footer">
						<a class=" modal-action modal-close btn-flat">
							<i class="material-icons left">
								clear
							</i>
							Close
						</a>
					</div>
				</div>
				<div id="results">
				</div>
				<div id="container">
					<ul id="collapsible" class="collapsible collapsible-accordion"></ul>				
				</div>
			</div>
		</main>
		
		<footer >
			<div class="container">
            	<div class="row">
            	<div class="divider"></div>
            		<a href=".">BioStor-Lite</a> is a project by <a href="https://twitter.com/rdmpage">Rod Page</a>. 
            		It's goal is to make discoverable articles in the <a href="https://www.biodiversitylibrary.org">Biodiversity Heritage Library</a> (BHL). 
            		See also the <a href="map.php">map</a> and the 
 <a href="match.html">Match references</a> reconciliation service.
            	</div>
            </div>
			
		
		</footer>


		<script>
			<?php
			
			$has_parameters = false;
			
			$q = '';			
			
			if (isset($_GET['q']))
			{
				$q = $_GET['q'];
				
				$has_parameters = true;
				
				echo '
				var query = decodeURIComponent("' . addcslashes($q, '"') . '");
			   	$("#query").val(query); 
			   	search();				
				';
			}
				
			$id = '';
		
			if (isset($_GET['id']))
			{
				$id = $_GET['id'];
				
				$has_parameters = true;
				
				echo 'show_record("' . $id . '");';			
			}
			
			if (isset($_GET['issn']))
			{
				$has_parameters = true;
				
				$issn = $_GET['issn'];
				
				if (isset($_GET['year']))
				{
					$year = $_GET['year'];
					
					// works for a year
					echo 'show_issn_year("' . $issn . '", "' . $year . '");';							
				}
				else
				{
					// whole journal
					echo 'show_issn("' . $issn . '");';							
				}
			}	
			
			if (isset($_GET['isbn']))
			{
				$has_parameters = true;
				
				$isbn = $_GET['isbn'];

				// chapters in book
				echo 'show_isbn("' . $isbn . '");';							

			}			
					
			
			if (!$has_parameters)
			{
			?>
			
				// Home page
				
				var template_home = `					
					<div class="row">
					<section class="covers">
						<% for (var i in data) {%>
							<div class="covers">
								<a href="reference/<%= data[i].referenceID %>">
								<img class="covers" src="https://aipbvczbup.cloudimg.io/s/height/200/https://www.biodiversitylibrary.org/pagethumb/<%= data[i].pageID %>,200,200">
								</a>
							</div>						
						<%}%>
					</section>
					</div>
				
				`;		
				
				var examples_1 = [
				{ pageID: 43605918, referenceID: 248475},
				{ pageID: 35669296, referenceID: 114607},
				{ pageID: 43276884, referenceID: 201883},
				{ pageID: 48184882, referenceID: 149688}
				];
				
				var examples_2 = [
				,
				{ pageID: 49942215, referenceID: 192990},
				{ pageID: 48951678, referenceID: 167448},
				{ pageID: 52110073, referenceID: 232256},
				{ pageID: 41229695, referenceID: 115363},
				];
				
				/*
				var examples_3 = [
				,
				{ pageID: 59075507, referenceID: 252946},
				{ pageID: 59107876, referenceID: 252963 }, // Death comes on two wings: a review of dipteran natural enemies of arachnids
				{ pageID: , referenceID: },
				{ pageID: , referenceID: },
				];
				*/
				
				// { pageID: 0, referenceID: 0},
				// { pageID: 0, referenceID: 0},
				// { pageID: 0, referenceID: 0},
				// { pageID: 0, referenceID: 0},
				
			
				var html = '<h2>BioStor-Lite: find articles in BHL</h2>';
				
				html += ejs.render(template_home, { data : examples_1 });
				
				html += ejs.render(template_home, { data : examples_2 });
				
				document.getElementById('results').innerHTML = html;

			<?php
			}
			
			?>
			
			/*
			// do we have a URL parameter?
			var query = $.urlParam('q');
			if (query) {
			   query = decodeURIComponent(query);
			   $('#query').val(query); 
			   search();
			}
			
			// view one record?
			var id = $.urlParam('id');
			
			if (id) {
			   id = decodeURIComponent(id);
			   show_record(id);
			}
					
			*/
					
		</script>

	</body>
</html>

