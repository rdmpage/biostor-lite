<?php

// get all ids in the Elasic index, for example if we want to check if we have missed any
// https://www.elastic.co/guide/en/elasticsearch/reference/current/paginate-search-results.html#scroll-search-results

error_reporting(E_ALL);

require_once (dirname(dirname(__FILE__)) . '/elastic.php');

$scroll = '1m';

$ids = array();

// 1.

$q = new stdclass;
$q->size = 100;

$response =	$elastic->send('POST',  '_search?scroll=' . $scroll, json_encode($q));					

$response_obj = json_decode($response);

//print_r($response_obj);

//exit();

// now loop through using scroll. Note that the first call above is to our index,
// but subsequent calls are direct to the server NOT the index

$done = false;

while (!$done)
{
	
	foreach ($response_obj->hits->hits as $hit)
	{
		echo $hit->_id . "\n";
	
		$ids[] = str_replace('biostor-', '', $hit->_id);
	
	}
	
	if (count($response_obj->hits->hits) == 0)
	{
		$done = true;
	}
	else
	{
		$q = new stdclass;
		$q->scroll = $scroll;
		$q->scroll_id = $response_obj->_scroll_id;
		
		$ch = curl_init(); 
		
		$url = $elastic->protocol . '://' . $elastic->host . ':' . $elastic->port . '/_search/scroll';
		
		curl_setopt ($ch, CURLOPT_URL, $url); 
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 		
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		if (isset($elastic->user))
		{
			curl_setopt($ch, CURLOPT_USERPWD, $elastic->user . ":" . $elastic->password); 
		}

		// Set HTTP headers
		$headers = array();
		$headers[] = 'Content-type: application/json'; // we are sending JSON
		$headers[] = 'Expect:'; 
    	curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
		
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($q));

		$response = curl_exec($ch);
		
		//echo $response;
		
		$response_obj = json_decode($response);
	}
}

file_put_contents('ids.txt', join("\n", $ids));


?>
