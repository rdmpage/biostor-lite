<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/elastic.php');


//--------------------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}

//--------------------------------------------------------------------------------------------------
// URL (e.g., PDF) exists
function display_head ($url, $callback)
{
	$obj = new stdclass;
	$obj->url = $url;
	$obj->found = false;

	$status = 404;
	
	if (api_head($url))
	{
		$status = 200;
		$obj->found = true;
	}
		
	api_output($obj, $callback, $status);
}	
	
//--------------------------------------------------------------------------------------------------
// One record
function display_one ($id, $format= '', $callback = '')
{
	global $elastic;

	$obj = null;
	$status = 404;
		
	$data = $elastic->send('GET', '_doc/' . urlencode($id));
	
	if ($data != '')
	{
		$obj = json_decode($data);
		
		if ($format == 'citeproc')
		{
			if (isset($obj->_source->search_result_data->csl))
			{
				$obj = $obj->_source->search_result_data->csl;
			}
		}
		
		$status = 200;
	}
		
	api_output($obj, $callback, $status);
}	

//--------------------------------------------------------------------------------------------------
// Full text search using Elastic
function display_elastic_search ($q, $filter=null, $from = 0, $size = 20, $callback = '')
{
	global $elastic;
	
	$status = 404;
				
	if ($q == '')
	{
		$obj = new stdclass;
		$obj->hits = new stdclass;
		$obj->hits->total = 0;
		$obj->hits->hits = array();
		
		$status = 200;
	}
	else
	{		
		// query type		
		$query_json = '';
		
		if ($filter)
		{
			if (isset($filter->author))
			{
				// author search is different( but not working yet)	
				$query_json = 		
	'{
	"size":50,
    "query": {
        "bool": {
            "must": [ {
				   "multi_match" : {
				  "query": "<QUERY>",
				  "fields":["search_data.author"] 
				}
				}]
        }
    }
	}';
			$query_json = str_replace('<QUERY>', $q, $query_json);
			
			// echo $query_json;
			
			}
		}
		
		// default is search on fulltext fields
		if ($query_json == '')
		{
			$query_json = '{
			"size":50,
				"query": {
					"bool" : {
						"must" : [ {
				   "multi_match" : {
				  "query": "<QUERY>",
				  "fields":["search_data.fulltext", "search_data.fulltext_boosted^4"] 
				}
				}],
			"filter": <FILTER>
				}
			},
			"aggs": {
			"type" :{
				"terms": { "field" : "search_data.type.keyword" }
			  },
			  "year" :{
				"terms": { "field" : "search_data.year" }
			  },
			  "container" :{
				"terms": { "field" : "search_data.container.keyword" }
			  },
			  "author" :{
				"terms": { "field" : "search_data.author.keyword" }
			  },
			  "classification" :{
				"terms": { "field" : "search_data.classification.keyword" }
			  }  

			}

	
			}';
			
			$query_json = str_replace('<QUERY>', $q, $query_json);
		}
	
	$filter_string = '[]';
	
	if ($filter)
	{
		$f = array();
		
		if (isset($filter->year))
		{
			$one_filter = new stdclass;
			$one_filter->match = new stdclass;
			$one_filter->match->{'search_data.year'} = $filter->year;
			
			$f[] = $one_filter;			
		}

		// this doesn't work
		if (isset($filter->author))
		{
			$one_filter = new stdclass;
			$one_filter->match = new stdclass;
			$one_filter->match->{'search_data.author'} = $filter->author;
			
			$f[] = $one_filter;			
		}
		
		$filter_string = json_encode($f);
	}
	
	$query_json = str_replace('<FILTER>', $filter_string, $query_json);
	
	
	$resp = $elastic->send('POST', '_search?pretty', $post_data = $query_json);
	

		$obj = json_decode($resp);

		$status = 200;
	}
	
	api_output($obj, $callback, 200);
}

//----------------------------------------------------------------------------------------
// Geo search
function display_geo ($geojson, $format = 'json', $callback = '')
{
	global $elastic;
	
	$obj = null;
	$status = 404;

	$geo = json_decode($geojson);
	
	$query = new stdclass;
	$query->size = 100;
	$query->query = new stdclass;
	$query->query->bool = new stdclass;
	$query->query->bool->must = new stdclass;
	$query->query->bool->must->match_all = new stdclass;
	
	$query->query->bool->filter = new stdclass;
	$query->query->bool->filter->geo_polygon = new stdclass;
	$query->query->bool->filter->geo_polygon->{'search_data.geometry.coordinates'} = new stdclass;
	$query->query->bool->filter->geo_polygon->{'search_data.geometry.coordinates'}->points = array();
	
	if (isset($geo->geometry->coordinates))
	{
		$query->query->bool->filter->geo_polygon->{'search_data.geometry.coordinates'}->points = $geo->geometry->coordinates[0];
	}
	
	$response = $elastic->send('POST',  '_search?pretty', json_encode($query));					
	$obj = json_decode($response);
	
	if ($obj)
	{
		$status = 200;
	}
		
	api_output($obj, $callback, $format, $status);
}	



//--------------------------------------------------------------------------------------------------
function main()
{
	global $config;

	$callback = '';
	$handled = false;
	
	
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
	
	if (isset($_GET['callback']))
	{	
		$callback = $_GET['callback'];
	}
	
	// Submit job
	if (!$handled)
	{
		if (isset($_GET['id']))
		{	
			$id = $_GET['id'];
			
			$format = '';
			
			if (isset($_GET['format']))
			{
				$format = $_GET['format'];
			}			
			
			if (!$handled)
			{
				display_one($id, $format, $callback);
				$handled = true;
			}
			
		}
	}
	
	if (!$handled)
	{
		if (isset($_GET['pdf']))
		{	
			$pdf = $_GET['pdf'];
			
			display_head($pdf, $callback);
			$handled = true;
		}
	}
	
	if (!$handled)
	{
		if (isset($_GET['geo']) && ($_GET['geo'] != ''))
		{	
			$geo = $_GET['geo'];
			
			$format = 'json';
			
			if (isset($_GET['format']))
			{
				$format = $_GET['format'];
			}						
			
			if (!$handled)
			{
				display_geo($geo, $format, $callback);
				$handled = true;
			}
			
		}
	}	
	
	
	if (!$handled)
	{
		if (isset($_GET['q']))
		{	
			$q = $_GET['q'];
			
			// Elastic
			$from = 0;
			$size = 10;
			
			$filter = null;
			
			if (isset($_GET['year']))
			{
				if (!$filter)
				{
					$filter = new stdclass;
				}
			
				$filter->year = (Integer)$_GET['year'];
			}			

			if (isset($_GET['author']))
			{
				if (!$filter)
				{
					$filter = new stdclass;
				}
			
				$filter->author = $_GET['author'];
			}											
			
			display_elastic_search($q, $filter, $from, $size, $callback);
			
			$handled = true;
		}
			
	}
	
	if (!$handled)
	{
		default_display();
	}

}


main();

?>