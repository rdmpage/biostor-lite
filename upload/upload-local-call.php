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
103286,
105191,
105964,
113228,
144491,
310947,
310948,
310949,
310950,
310951,
310952,
310953,
310954,
310955,
310956,
310957,
310958,
310959,
310960,
310961,
310962,
310963,
310964,
310965,
310966,
310967,
310968,
310969,
310970,
310971,
310972,
310973,
310974,
310975,
310976,
310977,
310978,
310979,
310980,
310981,
310982,
310983,
310984,
310985,
310986,
310987,
310988,
310989,
310990,
310991,
310992,
310993,
310994,
310995,
310996,
310997,
310998,
310999,
311000,
311001,
311002,
311003,
311004,
311005,
311006,
311007,
311008,
311009,
311010,
311011,
311012,
311013,
311014,
311015,
311016,
311017,
311018,
311019,
311020,
311021,
311022,
311023,
311024,
311025,
311026,
311027,
311028,
311029,
311030,
311031,
311032,
311033,
311034,
311035,
311036,
311037,
311038,
311039,
311040,
311041,
311042,
311043,
311044,
311045,
311046,
311047,
311048,
311049,
311050,
311051,
311052,);

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
		
		//echo $json;

		$obj = json_decode($json);

		if ($obj)
		{
			$elastic_doc = new stdclass;
			$elastic_doc->doc = $obj;
			$elastic_doc->doc_as_upsert = true;
		
			//print_r($elastic_doc);
			
			//exit();
			
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
		else
		{
			$failed[] = $id;
		}
		
		$count++;
	}
	
	// Give server a break every 10 items
	if (($count % 5) == 0)
	{
		echo "\n*** failed so far ***\n";
		echo join("\n", $failed) . "\n";
		echo "\n";
		$rand = rand(1000000, 3000000);
		echo "\n...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n\n";
		usleep($rand);
	}
	
}

echo "\n\nFailed:\n";

echo join("\n", $failed) . "\n";
	
?>
