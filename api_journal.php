<?php

// Journal info

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/elastic.php');


//----------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}

//----------------------------------------------------------------------------------------
// Journal articles for a given journal and year
function display_articles_year ($namespace, $value, $year, $callback = '')
{
	global $elastic;
	
	$status = 404;
	
	$fields = '';
	
	switch ($namespace)
	{
		case 'isbn':
			$fields = 'search_result_data.csl.ISBN';
			break;
			
		case 'issn':
		default:
			$fields = 'search_result_data.csl.ISSN';
			break;
	
	}
	
	
	$query_json = '{
	"size": 500,
	"_source": ["id", "search_result_data.name", "search_result_data.description", "search_result_data.thumbnailUrl", "search_data.year", "search_result_data.csl", "search_result_data.created", "search_result_data.modified"],
	"query": {
		"bool": {
			"must": [{
				"multi_match": {
					"query": "' . $value .'",
					"fields": ["' . $fields . '"]
				}
			}],
			"filter": [
				{
					"term": { "search_data.year": ' . $year .'}
				}
			]
		}
	},
	"aggs": {
		"year": {
			"terms": {
				"field": "search_data.year",
				"size": 500
			}
		}
	}
}';
	
	$resp = $elastic->send('POST', '_search?pretty', $post_data = $query_json);
	

	$obj = json_decode($resp);

	$status = 200;

	
	api_output($obj, $callback, $status);
}


//----------------------------------------------------------------------------------------
// Journal articles for a given journal 
function display_articles($namespace, $value, $callback = '')
{
	global $elastic;
	
	$status = 404;
	
	$fields = '';
	
	switch ($namespace)
	{
		case 'isbn':
			$fields = 'search_result_data.csl.ISBN';
			break;
			
		case 'issn':
		default:
			$fields = 'search_result_data.csl.ISSN';
			break;
	
	}	
	
	$query_json = '{
	"size": 5000,
	"_source": ["id", "search_result_data.name", "search_result_data.description", "search_result_data.thumbnailUrl", "search_data.year", "search_result_data.csl"],
	"query": {
		"bool": {
			"must": [{
				"multi_match": {
					"query": "' . $value .'",
					"fields": ["' . $fields . '"]
				}
			}]
		}
	},
	"aggs": {
		"year": {
			"terms": {
				"field": "search_data.year",
				"size": 500
			}
		}
	}
}';
	
	$resp = $elastic->send('POST', '_search?pretty', $post_data = $query_json);
	

	$obj = json_decode($resp);

	$status = 200;

	
	api_output($obj, $callback, $status);
}




//----------------------------------------------------------------------------------------
function main()
{
	$callback = '';
	$handled = false;
	
	//print_r($_GET);
	
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
	
		// ISSN	
		if (isset($_GET['issn']))
		{	
			$issn = $_GET['issn'];
			
			if (!$handled)
			{
				if (isset($_GET['year']))
				{
					$year = $_GET['year'];
					
					
					display_articles_year('issn', $issn, $year, $callback);
					
					$handled = true;
				}	
			}	
			
			if (!$handled)
			{
				display_articles('issn', $issn, $callback);
				
				$handled = true;			
			}	
		}
		
		if (isset($_GET['isbn']))
		{	
			$isbn = $_GET['isbn'];
			
			if (!$handled)
			{
				if (isset($_GET['year']))
				{
					$year = $_GET['year'];
					
					
					display_articles_year('isbn', $isbn, $year, $callback);
					
					$handled = true;
				}	
			}	
			
			if (!$handled)
			{
				display_articles('isbn', $isbn, $callback);
				
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

?>