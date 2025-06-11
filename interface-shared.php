<?php

// Shared core interface for simple JSON-LD based app

// Thing about what we need to define in config so we can make this more portable, 
//e.g. themes, rukles for internal links via identifiers, etc.

error_reporting(E_ALL);
require_once (dirname(__FILE__) . '/config.inc.php');

//----------------------------------------------------------------------------------------
// Literals may be strings, objects (e.g., a []@language, @value] pair), or an array.
// Handle this and return a string
function get_literal($key, $language='en')
{
	$literal = '';
	
	if (is_string($key))
	{
		$literal = $key;
	}
	else
	{
		if (is_object($key) && !is_array($key))
		{
			$literal = $key->{'@value'};
		}
		else
		{
			if (is_array($key))
			{
				$values = array();
				
				foreach ($key as $k)
				{
					if (is_object($k))
					{
						if ($language != '')
						{
							if ($language == $k->{'@language'})
							{
								$values[] = $k->{'@value'};
							}
						}
						else
						{
							$values[] = $k->{'@value'};
						}
					}
				}
				
				$literal = join(" / ", $values);
			}
		}
	}
	
	return $literal;
}


//----------------------------------------------------------------------------------------
// Return a property value if it exists, otherwise an empty string
function get_property_value ($item, $key, $propertyName)
{
	$value = '';
	
	if (isset($item->{$key}))
	{
		$n = count($item->{$key});
		$i = 0;
		while ($value == '' && ($i < $n) )
		{
			if ($item->{$key}[$i]->name == $propertyName)
			{
				$value = $item->{$key}[$i]->value;
			}	
			
			$i++;	
		}
	}
	
	return $value;
}

//----------------------------------------------------------------------------------------
// Add a property value to an item. $key is the predicate that has the property,
// e.g. "identifier" 
function add_property_value (&$item, $key, $propertyName, $propertyValue)
{
	$found = false;
	
	$found = (get_property_value($item, $key, $propertyName) == $propertyValue);
	
	if (!$found)
	{
		// If we don't have this key then create it
		if (!isset($item->{$key}))
		{
			$item->{$key} = array();		
		}	
	
		$property = new stdclass;
		$property->{"@type"} = "PropertyValue";
		$property->name  = $propertyName;
		$property->value = $propertyValue;
		$item->{$key}[] = $property;
	}
}

//----------------------------------------------------------------------------------------
function do_entity_twitter_tags($entity, $tag_names)
{
	$tags = array();
	
	foreach ($tag_names as $tag_name)
	{
		switch ($tag_name)
		{
			case 'twitter:card':
				if (!isset($tags[$tag_name]))
				{
					$tags[$tag_name] = array();
				}
				$tags[$tag_name][] = "summary";
				break;

			case 'twitter:title':
				if (!isset($tags[$tag_name]))
				{
					$tags[$tag_name] = array();
				}
				
				if (isset($entity->name))
				{
					$tags[$tag_name][] = get_literal($entity->name);
				}
				break;

			case 'twitter:image':
				if (!isset($tags[$tag_name]))
				{
					$tags[$tag_name] = array();
				}
				
				if (isset($entity->thumbnailUrl))
				{
					// BHL hack
					$entity->thumbnailUrl = preg_replace('/,\d+,\d+$/', '', $entity->thumbnailUrl);
				
					$tags[$tag_name][] = $entity->thumbnailUrl;
				}
				break;
					
			case 'twitter:description':
				if (!isset($tags[$tag_name]))
				{
					$tags[$tag_name] = array();
				}
				
				if (isset($entity->description))
				{
					$tags[$tag_name][] = get_literal($entity->description);
				}
				break;

		
			default:
				break;
		}
	
	}

	return $tags;
}

//----------------------------------------------------------------------------------------
function do_entity_og_tags($entity, $tag_names)
{
	$tags = array();
	
	foreach ($tag_names as $tag_name)
	{
		switch ($tag_name)
		{
			case 'og:type':
				if (!isset($tags[$tag_name]))
				{
					$tags[$tag_name] = array();
				}
				$tags[$tag_name][] = "website";
				break;

			case 'og:title':
				if (!isset($tags[$tag_name]))
				{
					$tags[$tag_name] = array();
				}
				
				if (isset($entity->name))
				{
					$tags[$tag_name][] = get_literal($entity->name);
				}
				break;

			case 'og:image':
				if (!isset($tags[$tag_name]))
				{
					$tags[$tag_name] = array();
				}
				
				if (isset($entity->thumbnailUrl))
				{
					// BHL hack
					$entity->thumbnailUrl = preg_replace('/,\d+,\d+$/', '', $entity->thumbnailUrl);

					$tags[$tag_name][] = $entity->thumbnailUrl;
				}
				break;
					
			case 'og:description':
				if (!isset($tags[$tag_name]))
				{
					$tags[$tag_name] = array();
				}
				
				if (isset($entity->description))
				{
					$tags[$tag_name][] = get_literal($entity->description);
				}
				break;

		
			default:
				break;
		}
	
	}

	return $tags;
}



//----------------------------------------------------------------------------------------
// this needs to be able to be customised....
function display_entity_details($entity)
{
	global $config;

	// Custom stuff
	// Breadcrumbs
	$path = array();
	
	$path["."] = "Home";
	
	if (isset($entity->isPartOf))
	{
		foreach ($entity->isPartOf as $container)
		{			
			if (isset($container->issn))
			{
				$path["issn/" . $container->issn[0]] = get_literal($container->name);
			
				$year = substr($entity->datePublished, 0, 4);
				$path["issn/" . $container->issn[0] . '/year/' . $year] = $year;
			}
			elseif (isset($container->oclcnum))
			{
				$path["oclc/" . $container->oclcnum] = get_literal($container->name);
		
				$year = substr($entity->datePublished, 0, 4);
				$path["oclc/" . $container->oclcnum . '/year/' . $year] = $year;
			}				
			elseif (isset($container->isbn))
			{
				$path["isbn/" . $container->isbn] = get_literal($container->name);
			}
		}
	}
	
	echo '<ul class="breadcrumb">';
	foreach ($path as $k => $v)
	{
		echo '<li><a href="' . $k . '">' . $v . '</a></li>';	
	}	
	echo '</ul>';
	
	echo '<h1>';
	
	$title = 'Untitled';
	if (isset($entity->name))
	{
		$title = get_literal($entity->name);
	}

	echo $title;
	echo '</h1>'  . "\n";
	
	// authors
	if (isset($entity->author))
	{
		echo '<div>';
		foreach ($entity->author as $author)
		{
			$name = array();
			
			if (isset($author->name))
			{
				$name[] = $author->name;
			}
			else
			{
				if (isset($author->givenName))
				{
					$name[] = $author->givenName;
				}
				if (isset($author->familyName))
				{
					$name[] = $author->familyName;
				}
			}
			
			if (count($name) > 1)
			{
				echo '<div class="author"><a href="?q=' . urlencode(join(' ', $name)) . '">' . join(' ', $name) . '</a></div>';
			}
		}
		echo '</div>';
	}							
	
	
	if (isset($entity->description))
	{
		echo '<p>' . $entity->description . '</p>';
	}
	
	if (isset($entity->datePublished))
	{
		echo '<p>Date published:' . $entity->datePublished . '</p>';
	}
	
	
	// actions
	
	$pdf = '';
	$cite = '';
	$bhl = '';
	$doi = '';
	
	// echo json_encode($entity);
	
	// Custom stuff
		
	// PDF
	if (isset($entity->encoding))
	{
		$pdf = '';
		foreach ($entity->encoding as $encoding)
		{
			if ($encoding->encodingFormat == "application/pdf")
			{
				$pdf = $encoding->contentUrl;
			}
		}
	}
	
	// DOI
	$doi = get_property_value($entity, 'identifier', 'doi');
	
	// BHL
	if (isset($entity->thumbnailUrl))
	{
		if (preg_match('/pagethumb\/(?<id>\d+)/', $entity->thumbnailUrl, $m))
		{
			$bhl = $m['id'];
		}
	}
	
	// Wikidata
	$wikidata = get_property_value($entity, 'identifier', 'wikidata');
		
	echo '<div>';
	
	if ($doi != '')
	{
		echo '<div class="actions">DOI: <a href="https://doi.org/' . $doi . '" target="_new">' . $doi . '</a></div>';
	}
	
	if ($wikidata != '')
	{
		echo '<div class="actions">Wikidata: <a href="https://www.wikidata.org/wiki/' . $wikidata . '" target="_new" onClick="ga(\'send\', \'event\', { eventCategory: \'Outbound Link\', eventAction: \'WIKIDATA\', eventLabel: event.target.href} );">'. $wikidata . '</a></div>';
	}	
	
	if ($bhl != '')
	{
		echo '<div class="actions">BHL: <a href="https://www.biodiversitylibrary.org/page/' . $bhl . '" target="_new" onClick="ga(\'send\', \'event\', { eventCategory: \'Outbound Link\', eventAction: \'BHL\', eventLabel: event.target.href} );">' . $bhl . '</a></div>';
	}
		
	if ($pdf != '')
	{
		echo '<div class="actions">PDF: <a href="' . $pdf . '" target="_new" onClick="ga(\'send\', \'event\', { eventCategory: \'Outbound Link\', eventAction: \'PDF\', eventLabel: event.target.href} );">View</a></div>';
	}
		
	echo '</div>';
	
	if (0)
	{
		echo '<pre>';
		print_r($entity);
		echo '</pre>';
		
		echo json_encode($entity);
	}
	
	echo '<h2>Page images</h2>';
	echo '<div class="gallery">';
	echo '<ul>';
	foreach ($entity->hasPart->hasPart as $image)
	{
		echo '<li>';
		echo '<img src="' . $image->thumbnailUrl . ',80,80"';
		
		echo ' title="' . $image->caption . '"';
		
		echo '>';
		echo '</li>';
	}
	echo '</ul>';
	echo '</div>';
	
	
	/*
	// hack to display one page
	if (isset($entity->thumbnailUrl))
	{
		$imageUrl = $entity->thumbnailUrl;
		$imageUrl = preg_replace('/\d+,\d+$/', '500,500', $imageUrl);
	
		echo '<div class="bhlpage">';
		
		if ($bhl != '')
		{
			echo '<a href="https://www.biodiversitylibrary.org/page/' . $bhl . '" target="_new">';
		}
		
		if ($config['use_cloudimage'])
		{		
			echo '<img src="https://aezjkodskr.cloudimg.io/' . $imageUrl . 'height=500">';
		}
		else
		{
			echo '<img src="' . $imageUrl . '">';		
		}
		
		if ($bhl != '')
		{
			echo '</a>';
		}
	
	
		echo '</div>';
	}
	*/
	
	/*
	if ($pdf != '')
	{
		// pdf.js
		echo '<iframe id="pdf" width="100%" height="600" frameborder="0" src="pdfjs/web/viewer.html?file=' 
			. urlencode('../../pdfproxy.php?url=' . urlencode($pdf)) . '"></iframe>';
	
	}
	*/
	
	/*
	if ($pdf != '')
	{
		// direct embed of PDF (doesn't work on iOS)
		//echo '<div style="margin-top:1em;"><object data="' . $pdf . '" width="100%" height="800"></object>';
		echo '<div style="margin-top:1em;"><iframe style="border:none;" src="' . $pdf . '" width="100%" height="800"></iframe>';
	}
	*/
	
	// map
	
	if (isset($entity->geometry))
	{
		echo '<h2>Localities in the text</h2>';
		echo '<div id="map" style="width:100%; height:300px;"></div>';
	}
	
}

//----------------------------------------------------------------------------------------
function display_entity($id)
{
	global $config;
	
	$entity = do_one($id);
	
	$ok = ($entity != null);			
	if (!$ok)
	{
		// bounce
		header('Location: ?error=Record not found' . "\n\n");
		exit(0);
	}
	
	$title = 'Untitled';
	if (isset($entity->name))
	{
		$title = get_literal($entity->name);
	}

	// tags
	$meta = '';	
	$tags = do_entity_twitter_tags($entity, ['twitter:card', 'twitter:title', 'twitter:image', 'twitter:description']);
	$tags = array_merge($tags, do_entity_custom_tags($entity));
	foreach ($tags as $key => $values)
	{
		foreach ($values as $value)
		{
			$meta .= '<meta name="' . $key . '" content="' . htmlentities($value, ENT_HTML5) . '" />';
		}
	}
	
	$tags = do_entity_og_tags($entity, ['og:type', 'og:title', 'og:image', 'og:description']);
	foreach ($tags as $key => $values)
	{
		foreach ($values as $value)
		{
			$meta .= '<meta property="' . $key . '" content="' . htmlentities($value, ENT_HTML5) . '" />';
		}
	}	
	
	// JSON-LD
	$script = '';
	$jsonld = json_encode($entity);	
		
 	display_html_start($title, $meta, $script, $jsonld);
 	display_header();	
	display_main_start();
	
 	display_entity_details($entity);	
 	
 	if (isset($entity->geometry))
 	{
 		echo '<script>
 			create_map();
 			add_data(' . json_encode($entity->geometry) . ');
 		</script>';
 	}

	
	display_main_end();	
	display_footer();	
	display_html_end();	
}


//----------------------------------------------------------------------------------------
// Start of HTML document
function display_html_start($title = '', $meta = '', $script = '', $jsonld = '', $onload = '')
{
	global $config;
	
	echo '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		
		<!-- base -->
    	<base href="' . $config['web_root'] . '" /><!--[if IE]></base><![endif]-->
				
		<!--Let browser know website is optimized for mobile-->
		<meta name="viewport" content="width=device-width, initial-scale=1.0" /> 
		
		<!-- do we want a coloured browser bar in Safari? -->
		<!-- <meta name="theme-color" content="rgb(231,224,185)" /> -->
		
		<!-- favicon -->
		<link href="static/biostor-shadow32x32.png" rel="icon" type="image/png">    
		
		<!--- canonical -->
		<link rel="canonical" href="' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" . '">
		
		<!-- RSS -->
		<link rel="alternate" type="application/rdf+xml" title="BioStor" href="' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/feed.php?format=rss1" . '">
		<link rel="alternate" type="application/rss+xml" title="BioStor" href="' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/feed.php?format=rss2" . '">
		<link rel="alternate" type="application/atom+xml" title="BioStor" href="' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/feed.php?format=atom" . '">
		
		<!-- leaflet -->
		<link rel="stylesheet" type="text/css" href="js/leaflet-0.7.3/leaflet.css" />
		<script src="js/leaflet-0.7.3/leaflet.js" type="text/javascript"></script>

		';	
		
	display_google_analytics();
	
	// map stuff
	echo "<script>
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
			var osmAttrib='Map data © <a href=\"http://openstreetmap.org\">OpenStreetMap</a> contributors';
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
	</script>";
		
		
	echo '<style type="text/css">' . "\n";
			
	echo " 
	body {
		padding:0;
		margin:0;
		font-family: sans-serif;
		font-size:1em;
		
		/* gives us a sticky footer */
		display: flex;
    	min-height: 100vh;
    	flex-direction: column;
	}
	
	h1 {
		font-weight:normal;
		line-height: 1.2em;
	}
	
	main {
		margin: 0 auto;
		width:800px;	
		
		/* gives us a sticky footer */
		flex: 1 0 auto;	
	}
	
	
	nav {
		position: sticky; 
		top: 0;
		/* background:rgb(231,224,185); */
		padding:1em;
		border-bottom:1px solid rgb(222,222,222);
		
		background:white;
		z-index:1000;
	}
	
	footer {
		/* background:rgb(231,224,185); */
		padding:1em;
		border-top:1px solid rgb(222,222,222);
	}
		
	/* searchbox */
    .flexbox { display: flex; }
    .flexbox .stretch { flex: 1; }
    .flexbox .normal { flex: 0; margin: 0 0 0 1rem; }
    .flexbox div input { 
    	width: 95%; 
    	font-size:1em; 
    	border:1px solid rgb(222,222,222); 
    	font-weight:bold;
    	padding: 0.5em 1em;
    	border-radius: 0.2em;
     }
    .flexbox div button { 
    	font-size:1em; 
    	/*
    	background: rgb(128,128,128); 
    	color:white;
    	*/
    	background:white;
    	border:1px solid rgb(192,192,192);
    	
    	padding: 0.5em 1em;
    	border-radius: 0.2em;
    	
    	-webkit-appearance: none;
    	display: inline-block;
        /* border: none;    */
    }
  
    
	a {
		text-decoration: none;
		color:#1a0dab;
	}
	
	a:hover {
		text-decoration: underline;		
	}
	
	article {
	 	display: block;
	 	overflow:auto;
	 	padding:1em;
	}
	
	@media (max-width: 480px) {
	  main {
		width:90%;
		
	  }
	  
	  h1 {
	  	font-size:1.5em;
	  }
	  
	  .flexbox div input { 
    	width: 90%; 
      }
	  
	  article {
	  	padding-left:0.5em;
	  	padding-right:0.5em;
	  	border-bottom:1px solid rgb(192,192,192);
	  }
	}	
	
	p {
		color: rgb(64,64,64);
	}
	
    .thumbnail {
		float:left;
		width:100px;
		height:100px;        
    }
    
    .thumbnail img {
    	border:1px solid rgb(222,222,222);
    	object-fit:contain;
    	margin:auto;
    	display:block;
    	height:100px;   	
    }
    
    .author {
    	padding-right:0.5em;
    	display:inline;
    }

    .actions {
    	padding-right:0.5em;
    	display:inline;
    }
    
	/* Style the list */
	ul.breadcrumb {
	  list-style: none;
	  padding:0px;
	}

	/* Display list items side by side */
	ul.breadcrumb li {
	  display: inline;
	}

	/* Add a slash symbol (/) before/behind each list item */
	ul.breadcrumb li+li:before {
	  padding:0.5em;
	  content: \">\";
	}

	/* BHL page */
	
	.bhlpage {
		width: 100%;
		padding-top:1em;
		padding-bottom:1em;
	}
	
	.bhlpage img {
		width: 100%;
		border:1px solid rgb(222,222,222);
	}
 
     .example {
		float:left;
		width:100px;
		height:100px; 
		padding:1em;       
    }
    
    .example img {
    	border:1px solid rgb(222,222,222);
    	object-fit:contain;
    	margin:auto;
    	display:block;
    	height:100px;   	
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

	details {
		border:1px solid rgb(128,128,128);
		margin-bottom: 1em;
		background:rgba(192,192,192,0.5);
		border-radius:4px;
	}
	
	summary {
	    padding:0.5em;
		outline-style: none; 
		background:rgb(128,128,128);
		color:white;
		border-radius:4px;
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

	/* heavily based on https://css-tricks.com/adaptive-photo-layout-with-flexbox/ */
	.gallery ul {
	  display: flex;
	  flex-wrap: wrap;
	  
	  list-style:none;
	  padding-left:2px;
	  /* background-color:rgb(224,224,224); */
	}

	.gallery li {
	  height: 80px;
	  padding:2px;
	  /*flex-grow: 1;*/
  
	}

/*
	.gallery li:last-child {
	  flex-grow: 10;
	}
	*/

	.gallery img {
	  max-height: 90%;
	  min-width: 90%;
	  object-fit: cover;
	  vertical-align: bottom;
	  
	  border:1px solid rgb(192,192,192);
	}	


 
	";

	echo '</style>';		
		
	//echo '<meta name="theme-color" content="#1a5d8d">';
	echo '<title>' . htmlentities($title, ENT_HTML5). '</title>'  . "\n";
	
	if ($meta != '')
	{
		echo $meta;
	}

	if ($script != '')
	{
		echo '<script>' . "\n";
		echo $script;
		echo '</script>' . "\n";
	}
	
	if ($jsonld != '')
	{
		echo '<script type="application/ld+json">' . "\n";
		echo $jsonld  . "\n";
		echo '</script>' . "\n";
	}
				
	echo '</head>' . "\n";
	
	if ($onload == '')
	{
		echo '<body>' . "\n";
	}
	else
	{
		echo '<body onload="' . $onload . '">'  . "\n";
	}
}

//----------------------------------------------------------------------------------------
// Header
function display_header($q = '')
{
	echo '<nav>' . "\n";
	//echo '<div>';
	display_search_bar($q);	
	//echo '</div>';
	echo '</nav>' . "\n";
}

//----------------------------------------------------------------------------------------
// Footer
function display_footer()
{
	echo '<footer>' . "\n";
	echo do_footer();
	echo '</footer>' . "\n";
}

//----------------------------------------------------------------------------------------
// End of HTML document
function display_html_end()
{
	global $config;
	
	echo 
'<script>
<!-- any end of document script goes here -->
</script>';

	echo '</body>'  . "\n";
	echo '</html>'  . "\n";
}

//----------------------------------------------------------------------------------------
// Display a simple search bar, optionally with the current query string
function display_search_bar($q = '')
{
	echo '
	<form class="flexbox" method="GET" action=".">
            <div class="stretch">
                <input type="input" id="q" name="q" placeholder="Search..." value="' . $q . '" autofocus/>
            </div>
            <div class="normal">
                <button onclick="search();">Search</button>
            </div>
    </form>	
	';
}

//----------------------------------------------------------------------------------------
function display_main_start()
{
	echo '<main>
<div class="container">'  . "\n";
}

//----------------------------------------------------------------------------------------
function display_main_end()
{
	echo '</div>
</main>'  . "\n";
}

//----------------------------------------------------------------------------------------
// Display a list, such as a search result
function display_list($data)
{
	global $config;
	
	$html = '';	
	
	/*
	if (isset($data->{'@graph'}[0]->query))
	{
		$html .= '<h2>query: ' . $data->{'@graph'}[0]->query . '</h2>';
	}
	*/
	
	$html .= '<p>' . $data->{'@graph'}[0]->description . '</p>';
	
	foreach ($data->{'@graph'}[0]->dataFeedElement as $dataFeedElement)
	{
		$html .=  '<article>';
		
		$html .=  '<div class="thumbnail">';
		if (isset($dataFeedElement->item->thumbnailUrl))
		{
			if ($config['use_cloudimage'])
			{
				$html .= '<img src="https://aezjkodskr.cloudimg.io/' . $dataFeedElement->item->thumbnailUrl . '?height=200">';
			}
			else
			{
				$html .= '<img height="200" src="' . $dataFeedElement->item->thumbnailUrl . '">';
			}
		}		
		$html .= '</div>';
		
		$html .=  '<div style="margin-left:100px;">';
		
		$html .= '<div style="font-size:1.2em;line-height:1.2em;display:block;padding-bottom:0.5em;">' ;
		$html .= '<a href="reference/' . str_replace('biostor:', '', $dataFeedElement->item->{'@id'}) . '">';
		$html .= $dataFeedElement->item->name;	
		$html .= '</a>';
		
		$html .=  '</div>';
		
		if (isset($dataFeedElement->item->description))
		{
			$html .=  '<div style="color:rgb(64,64,64)">';
			$html .= $dataFeedElement->item->description;
			$html .=  '</div>';
		}
		
		// Dates for this record
		$html .= '<div style="font-size:0.7em;color:#999;">';
		if (isset($dataFeedElement->dateCreated))
		{
			$html .= 'Created: ' . $dataFeedElement->dateCreated;
		}		
		if (isset($dataFeedElement->dateModified))
		{
			$html .= ', modified: ' . $dataFeedElement->dateModified;
		}		
		$html .= '<div>';
		
		$html .=  '</div>';
		
		$html .=  '</article>';
	}
	
	echo $html;
}

//----------------------------------------------------------------------------------------
// Display a list with items grouped by decade, such as a search result fior a journal
function display_decade_list($data)
{
	global $config;
	
	$decades = array();
	foreach ($data->{'@graph'}[0]->dataFeedElement as $dataFeedElement)
	{
		if (isset($dataFeedElement->item->datePublished))
		{
			$year = substr($dataFeedElement->item->datePublished, 0, 4);
			if (is_numeric($year))
			{
				$decade = floor($year/10);
			
				if (!isset($decades[$decade]))
				{
					$decades[$decade] = array();
				}
				if (!isset($decades[$decade][$year]))
				{
					$decades[$decade][$year] = array();
				}
				$decades[$decade][$year][] = $dataFeedElement->item;
			}
		}
	}	

	//print_r($decades);
	
	ksort($decades);
	
	$html = '';
	
	foreach ($decades as $decade => $years)
	{
		$html .= '<details>';
		$html .= '<summary>';
		$html .= '<span style="font-size:1.5em;">' . ($decade * 10) . '</span>';
		$html .= '</summary>' . "\n";
		$html .= '<section class="works">';
		
		ksort($years);
		
		foreach ($years as $year => $items)
		{
			$html .= '<div class="works year">' . $year . '</div>';
			
			foreach ($items as $item)
			{
				$html .= '<div class="works">';
				
				if (isset($item->thumbnailUrl))
				{
					$html .= '<a class="works" href="reference/' . str_replace('biostor:', '', $item->{'@id'}) . '"';
					
					if (isset($item->name))
					{
						$title = get_literal($item->name);
						$html .= ' title="' . addcslashes($title, '"') . '"';
					}
					
					$html .='>';
							
					if ($config['use_cloudimage'])
					{
						$html .= '<img loading="lazy" class="works" src="https://aezjkodskr.cloudimg.io/' . $item->thumbnailUrl . '?height=200">';
					}
					else
					{
						$html .= '<img height="200" loading="lazy" class="works" src="' . $item->thumbnailUrl . '">';					
					}
					$html .= '</a>';
				}		
				
				
				$html .= '</div>';
			}
		}
		
		$html .= '</section>';
		$html .= '</details>';
	
	}
	
	echo $html;
	
/*				var template_decades = `
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
											<img class="works" src="https://aezjkodskr.cloudimg.io/<%= data[decade][year][i].thumbnailUrl %>?height=200">
											
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
*/	
	

	/*
	$html = '';	
	
	$html .= '<p>' . $data->{'@graph'}[0]->description . '</p>';
	
	foreach ($data->{'@graph'}[0]->dataFeedElement as $dataFeedElement)
	{
		$html .=  '<article>';
		
		$html .=  '<div class="thumbnail">';
		if (isset($dataFeedElement->item->thumbnailUrl))
		{
			//$html .= '<img src="' . $dataFeedElement->item->thumbnailUrl . '">';
			$html .= '<img src="https://aezjkodskr.cloudimg.io/' . $dataFeedElement->item->thumbnailUrl . '?height=200">';
		}		
		$html .= '</div>';
		
		$html .=  '<div style="margin-left:100px;">';
		
		$html .= '<div style="font-size:1.2em;line-height:1.2em;display:block;padding-bottom:1em;">' ;
		$html .= '<a href="reference/' . str_replace('biostor:', '', $dataFeedElement->item->{'@id'}) . '">';
		$html .= $dataFeedElement->item->name;	
		$html .= '</a>';
		
		$html .=  '</div>';
		
		if (isset($dataFeedElement->item->description))
		{
			$html .=  '<div style="color:rgb(64,64,64)">';
			$html .= $dataFeedElement->item->description;
			$html .=  '</div>';
		}
		
		// Dates for this record
		$html .= '<div style="font-size:0.7em;">';
		if (isset($dataFeedElement->dateCreated))
		{
			$html .= 'Created: ' . $dataFeedElement->dateCreated;
		}		
		if (isset($dataFeedElement->dateModified))
		{
			$html .= ', modified: ' . $dataFeedElement->dateModified;
		}		
		$html .= '<div>';
		
		$html .=  '</div>';
		
		$html .=  '</article>';
	}
	
	echo $html;
	*/
}



//----------------------------------------------------------------------------------------
function display_search($q)
{
	global $config;
	
	$title = $q;	
	$meta = '';
	$script = '';
	
	$obj = do_search($q);
	
	$jsonld = json_encode($obj);
	
	display_html_start($title, $meta, $script, $jsonld);	
	display_header($q);				
	display_main_start();	
	display_list($obj);
	display_main_end();	
	display_footer();
	display_html_end();	
}


//----------------------------------------------------------------------------------------
// Home page, or badness happened
function default_display($error_msg = '')
{
	global $config;
	
	$title = $config['site_name'];
	$meta = '';
	$script = '';
	
	display_html_start($title, $meta, $script);
	display_header();
	display_main_start();
	
	if ($error_msg != '')
	{
		echo '<div><strong>Error!</strong> ' . $error_msg . '</div>';
	}
	else
	{
		echo do_welcome();
	}
			
	display_main_end();
	display_footer();
	display_html_end();
}


?>
