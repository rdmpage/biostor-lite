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
	
	$query_json = '{
	"size": 500,
	"_source": ["id", "search_result_data.name", "search_result_data.description", "search_result_data.thumbnailUrl", "search_data.year"],
	"query": {
		"bool": {
			"must": [{
				"multi_match": {
					"query": "' . $value .'",
					"fields": ["search_result_data.csl.ISSN"]
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
				display_issn($issn, $callback);
				
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