<?php

// Extract list of all BioStor ids in IA

// php -d memory_limit=-1 ia_ids.php

// Query to get list is:
// https://archive.org/advancedsearch.php?q=collection%3A%28biostor%29&fl%5B%5D=identifier&sort%5B%5D=&sort%5B%5D=&sort%5B%5D=&rows=300000&page=1&output=json&callback=callback&save=yes#raw


$filename= 'ia.json';

$json = file_get_contents($filename);

$obj = json_decode($json);

//print_r($obj);

foreach ($obj->response->docs as $doc)
{
	$id = str_replace('biostor-', '', $doc->identifier);
	
	echo 'REPLACE INTO rdmp_ia(reference_id) VALUES(' . $id . ');' . "\n";
}

?>

