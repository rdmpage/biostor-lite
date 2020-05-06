<?php

$url = 'https://archive.org/download/biostor-201883/biostor-201883.pdf';

if (isset($_GET['url']))
{
	$url = $_GET['url'];
}


header("Location: " . $url);

?>
