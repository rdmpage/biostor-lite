<?php

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/elastic.php');

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

$start   = 252736;
$end  	 = 252778;


$start   = 252779;
$end  	 = 252779;

$start   = 252780;
$end  	 = 252889;

$start   = 252890;
$end  	 = 252890;

$start   = 252891;
$end  	 = 252917;

$start   = 252918;
$end  	 = 252935;


$start   = 252936;
$end     = 252936;

$start   = 252937;
$end  	 = 252961;

$start   = 252962;
$end  	 = 252962;


$start   = 252000;
$end  	 = 252962;

$start   = 250000;
$end  	 = 252000;

$start   = 245000;
$end  	 = 250000;

$start   = 230000;
$end  	 = 245000;


$ids=array(
);

$ids=array(
111604,
104795,
95327,
153761,
104986,
104987,
252779,
104824,
67258,
104823,
97374,
71022,
);

$ids=array(
217697
);

$ids=array(
104818,
104805,
104948,

);


$ids=array(
107803,
150027,
60636,
);

$ids=array(
217694,
);

$ids=array(
167448,
114607,
115363,
50335

);


for ($id = $start; $id <= $end; $id++)
//foreach ($ids as $id)
{
	echo $id . "\n";
		
	$url = "http://direct.biostor.org:5984/biostor/_design/elastic/_view/biostor?key=" . urlencode('"biostor/' . $id . '"');
	
	//echo $url . "\n";
	
	$json = get($url);

	$obj = json_decode($json);

	//print_r($obj);

	if (1)
	{
		$elastic_doc = new stdclass;
		$elastic_doc->doc = $obj->rows[0]->value;
		$elastic_doc->doc_as_upsert = true;
		
		//print_r($elastic_doc);
		
		$elastic->send('POST',  '_doc/' . urlencode($elastic_doc->doc->id). '/_update', json_encode($elastic_doc));					
	}
}
	
?>
