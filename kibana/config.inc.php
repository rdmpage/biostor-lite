<?php

error_reporting(E_ALL);

// $Id: //

/**
 * @file config.php
 *
 * Global configuration variables (may be added to by other modules).
 *
 */

global $config;

// Date timezone
date_default_timezone_set('UTC');


if (file_exists(dirname(__FILE__) . '/env.php'))
{
	include 'env.php';
}


$config['elastic_options'] = array(
		'index' 	=> 'bslite',
		'protocol' 	=> 'https',
		'host' 		=> '5cd2289357274fb7a62a21fb79cbb855.europe-west2.gcp.elastic-cloud.com',
		'port' 		=> 9243,
		'user' 		=> 'elastic',
		'password' 	=> '3nFWkWWu5bPYfRYsdYKvvhcb',
		);

	
?>
