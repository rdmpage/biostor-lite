<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/elastic.php');

// tile request will supply x,y and z (zoom level)


$x 		= 0;
$y 		= 0;
$zoom 	= 0;

define ('TILE_SIZE', 256);
define ('MARKER_SIZE', 10);

$debug = false;
//$debug = true;


if (isset($_GET['x']))
{
	$x = (Integer)$_GET['x'];
}

if (isset($_GET['y']))
{
	$y = (Integer)$_GET['y'];
}

if (isset($_GET['z']))
{
	$zoom = (Integer)$_GET['z'];
}

//----------------------------------------------------------------------------------------
// Convert x,y,zoom tuple to latitude and longitude
function xyz_to_lat_long($x, $y, $zoom)
{
	$lon_lat = array();
	$n = pow(2, $zoom);

	$longitude_deg = $x / $n * 360.0 - 180.0;
	$latitude_rad = atan(sinh(M_PI * (1 - 2 * $y / $n)));
	$latitude_deg = $latitude_rad * 180.0 /  M_PI;

	$lon_lat = array($longitude_deg, $latitude_deg);

	return $lon_lat;
}

//----------------------------------------------------------------------------------------
// Convert tile to corresponding bounding box in lat,lon coordinates. We inflate 
// this by the size of the marker so that we can draw parts of markers whose centre
// is outside the tile (otherwise markers near tile edges will be clipped)
function xyz_to_bounding_box($x, $y, $zoom)
{
	$obj = new stdclass;
	
	$lon_lat = xyz_to_lat_long($x, $y, $zoom);
	
	$obj->top_left = new stdclass;
	$obj->top_left->lat = $lon_lat[1];
	$obj->top_left->lon = $lon_lat[0];

	$lon_lat = xyz_to_lat_long($x + 1, $y + 1, $zoom);
	
	$obj->bottom_right = new stdclass;
	$obj->bottom_right->lat = $lon_lat[1];
	$obj->bottom_right->lon = $lon_lat[0];
	
	// inflate
	if (1)
	{
		$dx = (abs($obj->top_left->lon - $obj->bottom_right->lon))/ TILE_SIZE;
		$dx *= MARKER_SIZE;
		
		$dy = (abs($obj->top_left->lat - $obj->bottom_right->lat))/ TILE_SIZE;
		$dy *= MARKER_SIZE;
	
		$obj->top_left->lat = min(90, $obj->top_left->lat + $dy);
		$obj->bottom_right->lat = max(-90, $obj->bottom_right->lat - $dy);
	
		$obj->top_left->lon = max(-180, $obj->top_left->lon - $dy);
		$obj->bottom_right->lon = min(180, $obj->bottom_right->lon + $dy);
		
	}
	return $obj;
}

//----------------------------------------------------------------------------------------
// Convert (lat,lon) pair to x,y coordinates within a tile 
function lat_lon_to_xy($lon_lat, $tile_x, $tile_y, $zoom)
{
	$xy = array();
	
	$n = pow(2, $zoom);
	
	$x_pos = ($lon_lat[0] + 180)/360 * $n;
	$x = floor($x_pos);
	
	/*
	if ($x < $tile_x)
	{
		$x_pos -= TILE_SIZE;
	}
	elseif ($x > $tile_x)
	{
		$x_pos += TILE_SIZE;
	}
	*/

	$relative_x = round(TILE_SIZE * ($x_pos - $tile_x));
	
	$y_pos = (1 - log(tan($lon_lat[1] * M_PI / 180.0) + 1/cos($lon_lat[1] * M_PI / 180.0))/M_PI)/2 * $n;
	
	/*
	$y_pos =
	
		(1 - 
			log(
				tan($lon_lat[1] * M_PI / 180.0)
				 + 
				 1/cos($lon_lat[1] * M_PI / 180.0)
				)
				/M_PI
		)
		/
		2
		 * $n;
	
	
	
	
	
	*/
	
	// (1 - LOG(TAN(RADIANS(x) + 1/COS(RADIANS(x)))/PI())/2
	
	$y = floor($y_pos);
	
	/*
	if ($y < $tile_y)
	{
		$y_pos -= TILE_SIZE;
	}
	elseif ($y > $tile_y)
	{
		$y_pos += TILE_SIZE;
	}
	*/
	
	
	$relative_y = round(TILE_SIZE * ($y_pos - $tile_y));

	$xy = array($relative_x , $relative_y);
	
	//echo $lon_lat[0] . "|" . $lon_lat[1] . "|" . $x_pos . '|' . $y_pos . "|" . $x . "|" . $y . '|' . $relative_x . '|' . $relative_y . "\n" ;
	
	return $xy;
}


//----------------------------------------------------------------------------------------


// Create SVG tile
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns:xlink="http://www.w3.org/1999/xlink" 
xmlns="http://www.w3.org/2000/svg" 
width="' . TILE_SIZE . '" height="' . TILE_SIZE . '">
   <style type="text/css">
	  <![CDATA[     
	  ]]>
   </style>
 <g>';

// Border for debugging
if ($debug)
{
	$xml .= '<rect id="border" x="0" y="0" width="' . TILE_SIZE . '" height="' . TILE_SIZE . '" style="stroke-width:1;fill:none;stroke:rgb(192,192,192);" />';	
	$xml .= '<text x="10" y="15">' . $x . ' ' . $y . ' ' . $zoom . '</text>';		
}
	 
$marker_shape = 'circle';
//$marker_shape = 'square';

$bounding_box = xyz_to_bounding_box($x, $y, $zoom);


if ($debug)
{
	$xml .= '<text x="10" y="20" style="font-size:12px">' . json_encode($bounding_box) . '</text>';
}

$query_json = '{
	"size": 10,
	"query": {
		"bool": {
			"must": {
				"match_all": {}
			},
			"filter": []
		}
	},
	"aggs": {
		"zoom": {
			"geohash_grid": {
				"field": "search_data.geometry.coordinates",
				"precision": 0
			}
		}
	}
}';

$query = json_decode($query_json);

$query->size = 1000;

$geo_filter = new stdclass;
$geo_filter->geo_bounding_box = new stdclass;
$geo_filter->geo_bounding_box->{'search_data.geometry.coordinates'} = $bounding_box;

$query->query->bool->filter = $geo_filter;

$query->aggs->zoom->geohash_grid->precision = 8;
//$query->aggs->zoom->geohash_grid->precision = $zoom;

//echo json_encode($query);

$response =	$elastic->send('POST',  '_search', json_encode($query));					

$response_obj = json_decode($response);

//print_r($response_obj);

// bounds to filter points
$min_lon_lat = array(
	min($bounding_box->top_left->lon, $bounding_box->bottom_right->lon),
	min($bounding_box->top_left->lat, $bounding_box->bottom_right->lat),
	);
	
$max_lon_lat = array(
	max($bounding_box->top_left->lon, $bounding_box->bottom_right->lon),	
	max($bounding_box->top_left->lat, $bounding_box->bottom_right->lat),	
	);

foreach ($response_obj->hits->hits as $hit)
{
	// compute place in tile
	
	foreach ($hit->_source->search_data->geometry->coordinates as $lon_lat)
	{
		// We only want to display points in coordinates array that are within the bounds 
		// of this tile. Because a BioStor JSON document has a set of all points in 
		// that document, we need to filter out those points.
		
		$show = false;		
		if ($lon_lat[0] >= $min_lon_lat[0] && ($lon_lat[0] <= $max_lon_lat[0]))
		{
			if ($lon_lat[1] >= $min_lon_lat[1] && ($lon_lat[1]  <= $max_lon_lat[1]))
			{
				$show = true;
			}	
		}		
		
		if ($show)
		{
			$xy = lat_lon_to_xy	($lon_lat, $x, $y, $zoom);
	
			$x_pos = $xy[0];
			$y_pos = $xy[1];

			switch ($marker_shape)
			{
				case 'square':
					$offset = MARKER_SIZE / 2;
					$xml .= '<rect id="dot" x="' . ($x_pos - $offset) . '" y="' . ($y_pos - $offset) . '" width="' . MARKER_SIZE. '" height="' . MARKER_SIZE . '" style="stroke-width:1;"';			
					break;
	
				case 'circle':
				default:
					$radius = MARKER_SIZE / 2;
					$offset = 0;
					$xml .= '<circle id="dot" cx="' . ($x_pos - $offset) . '" cy="' . ($y_pos - $offset) . '" r="' . $radius . '" style="stroke-width:0.5;"';
					break;
			}
		
			// styles
			
			if (1)
			{
				// Canadensys
				$fill 		= 'rgb(208,104,85)';
				$stroke 	= 'rgb(38,38,38)';
				$opacity	= '1.0';				
			}

			if (0)
			{
				$fill 		= 'rgb(255,0,0)';
				$stroke 	= 'none';
				$opacity	= '0.5';				
			}
			
			$xml .= ' fill="'. $fill . '"';
			$xml .= ' stroke="'. $stroke . '"';
			$xml .= ' opacity="'. $opacity . '"';
			
			$xml .= '/>';	
		}
	}

}
 
$xml .= '
      </g>
	</svg>';
	

// Serve up tile	
header("Content-type: image/svg+xml");

if (!$debug)
{
	header("Cache-control: max-age=3600");
}

echo $xml;


?>
