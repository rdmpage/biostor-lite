<?php

// GeoJSON polygon searches
//
// POST a GeoJSON body to this endpoint and get back the articles whose point
// localities fall inside the supplied polygon. The result is trimmed to a small
// set of key fields (see article_from_hit() below).
//
// Accepted GeoJSON shapes (POST body):
//   - a FeatureCollection (the first Polygon/MultiPolygon feature is used)
//   - a Feature whose geometry is a Polygon/MultiPolygon
//   - a bare geometry: {"type":"Polygon","coordinates":[[[lon,lat],...]]}
//   - a bare geometry: {"type":"MultiPolygon","coordinates":[[[[lon,lat],...]]]}
//
// Note: Elasticsearch geo_polygon only supports a single ring (no holes), so
// only the outer ring of the first polygon is used.

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/elastic.php');

//--------------------------------------------------------------------------------------------------
// Parse JSON and return any errors
function parse_json($json)
{
	$doc = json_decode($json);

	$error = new stdclass;
	$error->code = json_last_error();

	switch ($error->code)
	{
		case JSON_ERROR_NONE:
			$error->msg = 'No errors';
			break;
		case JSON_ERROR_DEPTH:
			$error->msg = 'Maximum stack depth exceeded';
			break;
		case JSON_ERROR_STATE_MISMATCH:
			$error->msg = 'Underflow or the modes mismatch';
			break;
		case JSON_ERROR_CTRL_CHAR:
			$error->msg = 'Unexpected control character found';
			break;
		case JSON_ERROR_SYNTAX:
			$error->msg = 'Syntax error, malformed JSON';
			break;
		case JSON_ERROR_UTF8:
			$error->msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
			break;
		default:
			$error->msg = 'Unknown error';
			break;
	}

	return $error;
}

//----------------------------------------------------------------------------------------
// Pull the outer ring [[lon,lat],...] out of whatever GeoJSON shape we were given.
// Returns an array of [lon,lat] points, or null if no usable polygon was found.
function extract_polygon_ring($geo)
{
	if (!is_object($geo))
	{
		return null;
	}

	// FeatureCollection: use the first feature that carries a polygon
	if (isset($geo->type) && $geo->type == 'FeatureCollection' && isset($geo->features))
	{
		foreach ($geo->features as $feature)
		{
			$ring = extract_polygon_ring($feature);
			if ($ring !== null)
			{
				return $ring;
			}
		}
		return null;
	}

	// Feature: unwrap to its geometry
	if (isset($geo->geometry))
	{
		return extract_polygon_ring($geo->geometry);
	}

	// Bare geometry
	if (isset($geo->type) && isset($geo->coordinates))
	{
		if ($geo->type == 'Polygon')
		{
			// coordinates = [ outer_ring, hole1, ... ]; take the outer ring
			return isset($geo->coordinates[0]) ? $geo->coordinates[0] : null;
		}

		if ($geo->type == 'MultiPolygon')
		{
			// coordinates = [ polygon1, polygon2, ... ]; take first polygon's outer ring
			return isset($geo->coordinates[0][0]) ? $geo->coordinates[0][0] : null;
		}
	}

	return null;
}

//----------------------------------------------------------------------------------------
// Ray-casting point-in-polygon test. $point and each ring vertex are [lon,lat].
// Uses the same (planar) outer ring that was sent to Elasticsearch as geo_polygon
// points, so the points we keep are consistent with what ES matched on.
function point_in_ring($point, $ring)
{
	if (!is_array($point) || count($point) < 2)
	{
		return false;
	}

	$x = $point[0];
	$y = $point[1];

	$inside = false;
	$n = count($ring);

	for ($i = 0, $j = $n - 1; $i < $n; $j = $i++)
	{
		$xi = $ring[$i][0]; $yi = $ring[$i][1];
		$xj = $ring[$j][0]; $yj = $ring[$j][1];

		$intersect = (($yi > $y) != ($yj > $y))
			&& ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

		if ($intersect)
		{
			$inside = !$inside;
		}
	}

	return $inside;
}

//----------------------------------------------------------------------------------------
// Reduce a raw Elasticsearch hit to the key fields we want to expose. Point
// localities are filtered to just those that fall inside $ring.
function article_from_hit($hit, $ring)
{
	$source = $hit->_source;

	$article = new stdclass;
	$article->id = isset($source->id) ? $source->id : null;

	if (isset($source->search_result_data))
	{
		$srd = $source->search_result_data;
		$article->name         = isset($srd->name)         ? $srd->name         : null;
		$article->url          = isset($srd->url)          ? $srd->url          : null;
		$article->thumbnailUrl = isset($srd->thumbnailUrl) ? $srd->thumbnailUrl : null;
		if (isset($srd->csl))
		{
			$article->csl = $srd->csl;
		}
	}

	if (isset($source->search_data))
	{
		$sd = $source->search_data;
		$article->year           = isset($sd->year)           ? $sd->year           : null;
		$article->container      = isset($sd->container)      ? $sd->container      : null;
		$article->authors        = isset($sd->author)         ? $sd->author         : null;
		$article->classification = isset($sd->classification) ? $sd->classification : null;

		// Point localities for this article, filtered to just those inside the
		// search polygon. geo_polygon matches an article if ANY of its points lies
		// inside the polygon, so the raw array may include points outside it.
		if (isset($sd->geometry->coordinates))
		{
			$article->coordinates = array();
			foreach ($sd->geometry->coordinates as $point)
			{
				if (point_in_ring($point, $ring))
				{
					$article->coordinates[] = $point;
				}
			}
		}
	}

	return $article;
}

//----------------------------------------------------------------------------------------
// Geo search
function display_geo_search ($geojson, $callback = '')
{
	global $elastic;

	$geo = json_decode($geojson);

	$ring = extract_polygon_ring($geo);

	if ($ring === null || count($ring) < 3)
	{
		$doc = new stdclass;
		$doc->status = 400;
		$doc->message = 'No usable Polygon/MultiPolygon found in the supplied GeoJSON';
		api_output($doc, $callback, 400);
		return;
	}

	// Build the query. We ask Elasticsearch to return only the fields we need via
	// _source filtering, so the large fulltext field is never sent over the wire.
	$query = new stdclass;
	$query->size = 100;
	$query->_source = array(
		'id',
		'search_result_data.name',
		'search_result_data.url',
		'search_result_data.thumbnailUrl',
		'search_result_data.csl',
		'search_data.year',
		'search_data.container',
		'search_data.author',
		'search_data.classification',
		'search_data.geometry',
	);

	$query->query = new stdclass;
	$query->query->bool = new stdclass;
	$query->query->bool->must = new stdclass;
	$query->query->bool->must->match_all = new stdclass;

	$query->query->bool->filter = new stdclass;
	$query->query->bool->filter->geo_polygon = new stdclass;
	$query->query->bool->filter->geo_polygon->{'search_data.geometry.coordinates'} = new stdclass;
	$query->query->bool->filter->geo_polygon->{'search_data.geometry.coordinates'}->points = $ring;

	$response = $elastic->send('POST',  '_search?pretty', json_encode($query));
	$es = json_decode($response);

	$result = new stdclass;
	$status = 404;

	if ($es && isset($es->hits))
	{
		$status = 200;

		// Elasticsearch returns total as either an int (older) or {value: n} (7.x+)
		if (isset($es->hits->total->value))
		{
			$result->total = $es->hits->total->value;
		}
		else if (isset($es->hits->total))
		{
			$result->total = $es->hits->total;
		}
		else
		{
			$result->total = 0;
		}

		$result->articles = array();
		foreach ($es->hits->hits as $hit)
		{
			$result->articles[] = article_from_hit($hit, $ring);
		}
	}
	else
	{
		// Surface the Elasticsearch error rather than a silent 404
		$result->status = 500;
		$result->message = 'Elasticsearch query failed';
		$result->error = $es;
		$status = 500;
	}

	api_output($result, $callback, $status);
}

//----------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}

//----------------------------------------------------------------------------------------
function main()
{
	global $config;

	$callback = '';
	$handled = false;

	$post_content = file_get_contents('php://input');

	// If no query parameters
	if (count($_GET) == 0 && $post_content == '')
	{
		default_display();
		exit(0);
	}

	$callback = '';
	if (isset($_GET['callback']))
	{
		$callback = $_GET['callback'];
	}

	$debug = false;
	if (isset($_GET['debug']))
	{
		$debug = true;
	}

	// POST
	// Larger items, such as a sequence to BLAST or a set of records to work with
	if (!$handled)
	{
		if ($post_content != '')
		{
			$error = parse_json($post_content);

			if ($error->code == 0)
			{
				// OK
				display_geo_search($post_content, $callback);
				$handled = true;
			}
			else
			{
				// Bad JSON
				$doc = new stdclass;
				$doc->status = 400;
				$doc->message = $error->msg;
				api_output($doc, $callback, 400);
				$handled = true;
			}
		}
	}

	if (!$handled)
	{
		default_display();
	}

}


main();
