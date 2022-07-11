<?php

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/elastic.php');

//----------------------------------------------------------------------------------------
function get($url)
{
	$data = null;
	
	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	return $data;
}


$start   = 252694;
$end  	 = 252735;

$ids=array(

);

$ids=array(
//260711,
113663,
);

$force = true;
//$force = false;

$count = 1;

$failed = array();

//for ($id = $start; $id <= $end; $id++)
foreach ($ids as $id)
{
	echo $id . "\n";
	
	if ($elastic->exists('biostor-' . $id) && !$force)
	{
		echo "Have it already\n";
	}
	else
	{
		echo "Adding\n";
			
		// local BioStor
		$url = 'http://localhost/biostor-classic/www/reference/' . $id . '.elastic';
	
		echo $url . "\n";
	
		$json = get($url);
		
		echo $json;

		$obj = json_decode($json);

		if ($obj)
		{
			$elastic_doc = new stdclass;
			$elastic_doc->doc = $obj;
			$elastic_doc->doc_as_upsert = true;
		
			//print_r($elastic_doc);
			
			//echo json_encode($elastic_doc);
		
			// $response = $elastic->send('POST',  '_doc/' . urlencode($elastic_doc->doc->id). '/_update', json_encode($elastic_doc));					
			$response = $elastic->send('POST',  '_update/' . urlencode($elastic_doc->doc->id), json_encode($elastic_doc));					
			echo $response;
			
			$response_obj = json_decode($response);
			
			if (isset($response_obj->error))
			{
				$failed[] = $id;
			}
		}
	}
	
	// Give server a break every 10 items
	if (($count++ % 10) == 0)
	{
		$rand = rand(1000000, 3000000);
		echo "\n...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n\n";
		usleep($rand);
	}
	
}

echo "\n\nFailed:\n";

echo join("\n", $failed) . "\n";
	
?>
