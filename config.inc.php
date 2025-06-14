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

$local = false;
//$local = true;

$config['site_name'] = "BioStor";

if ($local)
{
	$config['web_server']	= 'http://localhost';
	$config['web_root']		= '/biostor-lite/';
}
else
{
	//$config['web_server']	= 'https://biostor-lite.herokuapp.com';
	$config['web_server']	= 'https://biostor.org';
	$config['web_root']		= '/';
}

// Cloudimage-----------------------------------------------------------------------------

$config['use_cloudimage'] = true;
$config['use_cloudimage'] = false;


// Elastic--------------------------------------------------------------------------------

$config['use_elastic'] = true;

if (file_exists(dirname(__FILE__) . '/env.php'))
{
	include 'env.php';
}

//if ($local)
if (0)
{
	$config['elastic_options'] = array(
			'index' 	=> 'bslite',
			'protocol' 	=> 'http',
			'host' 		=> '127.0.0.1',
			'port' 		=> 9200
			);
}
else
{
	$config['elastic_options'] = array(
			'index' 	=> 'elasticsearch/bslite',
			'protocol' 	=> 'http',
			'host' 		=> '35.204.73.93',
			'port' 		=> 80,
			'user' 		=> getenv('ELASTIC_USERNAME'),
			'password' 	=> getenv('ELASTIC_PASSWORD'),
			);
			
	$config['elastic_options'] = array(
			'index' 	=> 'bslite',
			'protocol' 	=> 'http',
			'host' 		=> '65.108.58.109',
			'port' 		=> 80,
			'user' 		=> getenv('ELASTIC_USERNAME'),
			'password' 	=> getenv('ELASTIC_PASSWORD'),			
			);			
}
	
?>
