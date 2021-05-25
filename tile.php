<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/elastic.php');

// tile request will supply x,y and z (zoom level)

$x = 0;
$y = 0;
$zoom = 0;

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

  
//-----------------------------------------------------------------------------------------
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

//-----------------------------------------------------------------------------------------
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
	
	return $obj;

}

//-----------------------------------------------------------------------------------------
function lat_lon_to_xy($lon_lat, $zoom)
{
	$xy = array();
	
	$n = pow(2, $zoom);
	
	$x_pos = ($lon_lat[0] + 180)/360 * $n;
	$x = floor($x_pos);
	
	$relative_x = round(256 * ($x_pos - $x));
	
	$y_pos = (1 - log(tan($lon_lat[1] * M_PI / 180.0) + 1/cos($lon_lat[1] * M_PI / 180.0))/M_PI)/2 * $n;
	$y = floor($y_pos);
	
	$relative_y = round(256 * ($y_pos - $y));

	$xy = array($relative_x , $relative_y);
	
	return $xy;
}


//-----------------------------------------------------------------------------------------
// Query Elastic to get dots on map 


// Create SVG tile
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns:xlink="http://www.w3.org/1999/xlink" 
xmlns="http://www.w3.org/2000/svg" 
width="256" height="256"
overflow="visible"

 >
   <style type="text/css">
	  <![CDATA[     
	  ]]>
   </style>
 <g>';

// Border for debugging
if ($debug)
{
	$xml .= '<rect id="border" x="0" y="0" width="256" height="256" style="stroke-width:1;fill:none;stroke:rgb(192,192,192);" />';	
	$xml .= '<text x="10" y="15">' . $x . ' ' . $y . ' ' . $zoom . '</text>';		
}
	 
$marker_size = 8;
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

// bound to filter points
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
		// We only want to disply points in coordinates array that are within the bounds 
		// of this tile. Because a BioStor JSON document has a set of points, not all need
		// be within this tile.
		
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
			$xy = lat_lon_to_xy	($lon_lat, $zoom);
	
			$x_pos = $xy[0];
			$y_pos = $xy[1];
	
			switch ($marker_shape)
			{
				case 'square':
					$offset = $marker_size / 2;
					$xml .= '<rect id="dot" x="' . ($x_pos - $offset) . '" y="' . ($y_pos - $offset) . '" width="' . $marker_size . '" height="' . $marker_size . '" style="stroke-width:1;"';			
					break;
	
				case 'circle':
				default:
					$radius = $marker_size / 2;
					$offset = 0;
					$xml .= '<circle id="dot" cx="' . ($x_pos - $offset) . '" cy="' . ($y_pos - $offset) . '" r="' . $radius . '" style="stroke-width:1;"';
					break;
			}
		
			$fill = 'rgba(0,0,0,0.5)';
			$fill = 'rgb(208,104,85)'; // Canadensys
	
			$xml .= ' fill="'. $fill . '"';
	
			//$xml .= ' opacity="0.7"';
		
		
			$xml .= ' stroke="rgb(38,38,38)"'; // Canadensys
			$xml .= '/>';	
		}
	}

}
 
$xml .= '
      </g>
	</svg>';
	

// Serve up tile	
header("Content-type: image/svg+xml");
header("Cache-control: max-age=3600");

echo $xml;


?>
