<?php

// Original proxy was a security mess so let's just redirect to home page

error_reporting(E_ALL);

require_once(dirname(__FILE__) . '/config.inc.php');

$url = $config['web_server'] . $config['web_root'];
	
header("Location: " . $url);

?>
