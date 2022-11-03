<?php

// Check whether record(s) exist

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/elastic.php');

function get($url)
{
	$ch = curl_init(); 
	
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 		
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
	
	curl_setopt ($ch, CURLOPT_TIMEOUT, 2);
	
	//curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "HEAD");
	
	// http://stackoverflow.com/a/770200
	//curl_setopt($ch, CURLOPT_NOBODY, true);

	$response = curl_exec($ch);
	
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	curl_close($ch);
	
	return $http_code;

}


$ids=array(
274043,
274054,
274071,
274083,
274089,
274116,
274121,
274122,
274129,
274133,
274136,
274156,
274166,
274172,
274188,
274193,
274196,
274220,
274221,
274232,
274239,
274243,
274252,
274266,
274270,
274272,
274275,
274283,
274284,
274286,
274289,
274293,
274294,
274304,
274322,
274331,
274344,
274351,
274352,
274353,
274355,
274358,
274360,
274363,
274376,
274380,
274392,
274409,
274414,
274416,
274422,
274424,
274426,
274431,
274437,
274441,
274448,
274450,
274464,
274467);

$start = 274026;
$end   = 274470;

$missing = array();

$count = 1;

$codes = array();

//for ($id = $start; $id <= $end; $id++)
foreach ($ids as $id)
{
	echo $id . "\n";
	
	$url = $elastic->protocol . '://' . $elastic->host . ':' . $elastic->port . '/' . $elastic->index;		
	$url .= '/_doc/' . urlencode('biostor-' . $id);
		
	echo $url . "\n";
	
	$http_code = get($url);
	
	if (!isset($codes[$http_code]))
	{
		$codes[$http_code] = array();
	}
	$codes[$http_code][] = $id;
		
	
	// Give server a break every 10 items
	if (($count++ % 5) == 0)
	{
		$rand = rand(1000000, 3000000);
		echo "\n...sleeping for " . round(($rand / 1000000),2) . ' seconds' . "\n\n";
		usleep($rand);
		
		print_r($codes);
	}
	
}

print_r($codes);

$missing = $codes[404];

echo "\nMissing:\n";

echo join("\n", $missing) . "\n";
	
?>
