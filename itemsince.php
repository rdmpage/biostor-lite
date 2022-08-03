<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/elastic.php');

$since = date('Y-m-d', time());

if (isset($_GET['since']))
{
	$since = $_GET['since'];
	
	// Check format
	if (preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/', $since))
	{
	}
	else
	{
		$since = date('Y-m-d', time());
	}
}

$callback = '';

if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

$obj = new stdclass;
$obj->since = $since;
$obj->count = 0;
$obj->limit = 1000;
$obj->items = array();

$timestamp = strtotime($since);

// get records since timestamp
$query_json = 		
	'{
	"size": 1000,
	"_source" :[ "search_data.item"],
	"query": {
		"range": {
			"search_data.modified": { 
				"gte": <TIMESTAMP> 
            }		
        }
	}
}';

$query_json = str_replace('<TIMESTAMP>', $timestamp, $query_json);
		
$resp = $elastic->send('POST', '_search?pretty', $post_data = $query_json);
	

$response_obj = json_decode($resp);

//print_r($response_obj);

foreach ($response_obj->hits->hits as $hit)
{
	$obj->items[] = $hit->_source->search_data->item;
}

$obj->count = count($obj->items);


header('Content-type: text/plain');
if ($callback != '')
{
	echo $callback .'(';
}

echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if ($callback != '')
{
	echo ')';
}

?>

