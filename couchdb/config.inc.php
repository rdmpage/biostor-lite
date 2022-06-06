<?php

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


$config['couchdb_options'] = array(
		'database' => 'biostor',
		'host' => '127.0.0.1',
		'port' => 5984,
		'prefix' => 'http://'
		);	

	
?>