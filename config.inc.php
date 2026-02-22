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

// Image proxy----------------------------------------------------------------------------
// Self-hosted BHL image proxy (pageimage.php), cacheable by Cloudflare.
// Replaces the old CloudImage CDN integration.

$config['use_image_proxy'] = true;

// Cloudimage (legacy, superseded by use_image_proxy)-------------------------------------

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

// Image proxy URL helper-----------------------------------------------------------------

/**
 * Return the URL for a proxied BHL page thumbnail.
 * The proxy serves images with long Cache-Control headers so Cloudflare caches them.
 *
 * @param int $pageID  BHL PageID
 * @param int $w       Desired width in pixels
 * @param int $h       Desired height in pixels
 * @return string      Absolute URL to the local image proxy endpoint
 */
function pageimage_url($pageID, $w, $h)
{
	global $config;
	return $config['web_server'] . $config['web_root'] . 'pageimage/' . (int)$pageID . '/' . (int)$w . '/' . (int)$h;
}

?>
