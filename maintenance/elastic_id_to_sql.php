<?php


$filename= 'elastic_ids.txt';


$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
	
	echo 'REPLACE INTO rdmp_elastic(reference_id) VALUES(' . $line . ');' . "\n";

	
}	

?>

