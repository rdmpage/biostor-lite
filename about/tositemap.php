<?php

// Generate list of container URLs that we can use as a sitemap


//----------------------------------------------------------------------------------------
// read source files
$basedir = '.';

$files = scandir($basedir);

$locs = array();

$letters = array();

foreach ($files as $filename)
{
	if (preg_match('/\.json$/', $filename))
	{	
		// do stuff on $basedir . '/' . $filename
		
		$json = file_get_contents($basedir . '/' . $filename);
		$obj = json_decode($json);
		
		if (isset($obj->{'@id'}))
		{
			$locs[] = $obj->{'@id'};
		}
	}
}

// print_r($locs);

echo '<?xml version="1.0" encoding="utf-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
   xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";
   
foreach ($locs as $loc)
{
	echo '<url>';
	echo '<loc>' . $loc . '</loc>';
	echo '</url>';
	echo "\n";
}

echo '</urlset>' . "\n";

?>
