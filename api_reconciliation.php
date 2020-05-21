<?php

// Match specimen codes to GBIF occurrences
require_once (dirname(__FILE__) . '/config.inc.php');

require_once (dirname(__FILE__) . '/reconciliation_api.php');

require_once (dirname(__FILE__) . '/fingerprint.php');
require_once (dirname(__FILE__) . '/lcs.php');

require_once (dirname(__FILE__) . '/api_utils.php');


//----------------------------------------------------------------------------------------
class BioStorService extends ReconciliationService
{
	//----------------------------------------------------------------------------------------------
	function __construct()
	{
		$this->name 			= 'BioStor';
		
		$this->identifierSpace 	= 'https://biostor.org/';
		$this->schemaSpace 		= 'http://rdf.freebase.com/ns/type.object.id';
		$this->Types();
		
		$view_url = 'https://biostor.org/reference/{{id}}';

		$preview_url = '';	
		$width = 430;
		$height = 300;
		
		if ($view_url != '')
		{
			$this->View($view_url);
		}
		if ($preview_url != '')
		{
			$this->Preview($preview_url, $width, $height);
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function Types()
	{
		$type = new stdclass;
		$type->id = 'https://schema.org/CreativeWork';
		$type->name = 'CreativeWork';
		$this->defaultTypes[] = $type;
	} 	
		

	
	// Elastic 
	//----------------------------------------------------------------------------------------------
	// Handle an individual query
	function OneQuery($query_key, $text, $limit = 1, $properties = null)
	{
		global $config;
		
		// clean text
		$text = str_replace(':', '', $text);
		$text = str_replace('"', '', $text);
		
		// BioStor search API
		$url = $config['web_server'] . $config['web_root'] . 'api.php?q=' . urlencode($text);
		
		//file_put_contents('/tmp/q.txt', $url, FILE_APPEND);
		
		$json = api_get($url);
		
		//file_put_contents('/tmp/q.txt', $json, FILE_APPEND);

		if ($json != '')
		{
			$obj = json_decode($json);
			
			if (isset($obj->hits))
			{
				for ($i = 0; $i < 3; $i++)
				{
					$row = $obj->hits->hits[$i];
					// check 
					
					$v1 = finger_print($text);
					
					$hit_text = '';
					
					if (isset($obj->hits->hits[$i]->_source->search_result_data->creator))
					{
						$hit_text .= join(' ', $obj->hits->hits[$i]->_source->search_result_data->creator);
						$hit_text .= ' ';
					}
					
					$hit_text .= $obj->hits->hits[$i]->_source->search_result_data->name;
					$hit_text .= $obj->hits->hits[$i]->_source->search_result_data->description;
					
					$hit_text = preg_replace('/Published\s+in\s+/', '', $hit_text);
					$hit_text = preg_replace('/,\s+in\s+/', ' ', $hit_text);
					$hit_text = preg_replace('/\s+volume/', '', $hit_text);
					$hit_text = preg_replace('/,\s+pages\s+/', ' ', $hit_text);
					
					$v2 = finger_print($hit_text);
					
					$lcs = new LongestCommonSequence($v1, $v2);
					$d = $lcs->score();
					
					// echo $d;
					
					$score = min($d / strlen($v1), $d / strlen($v2));
					
					if ($score > 0.60)
					{
						$hit = new stdclass;
						$hit->id 	= str_replace('biostor-', '', $obj->hits->hits[$i]->_id);
				
						$hit->name 	= $obj->hits->hits[$i]->_source->search_result_data->name;
				
						$hit->score = ($score > 0.8);
						$hit->match = true;
						$this->StoreHit($query_key, $hit);
					}				
				
				
				}
			}
		}
		

		
	}	
	
	
	
	
}

$service = new BioStorService();


if (0)
{
	file_put_contents('/tmp/q.txt', $_REQUEST['queries'], FILE_APPEND);
}


$service->Call($_REQUEST);

?>