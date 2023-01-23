<?php

error_reporting(E_ALL);

// Match specimen codes to GBIF occurrences
require_once (dirname(__FILE__) . '/config.inc.php');

require_once (dirname(__FILE__) . '/reconciliation_api.php');

require_once (dirname(__FILE__) . '/compare.php');
require_once (dirname(__FILE__) . '/lcs.php');

require_once (dirname(__FILE__) . '/api_utils.php');


//----------------------------------------------------------------------------------------
// Convert CSL into a string we can use to compare with
function csl_to_string ($csl, $include_authors = true)
{
	$keys = ['author', 'issued', 'title', 'container-title', 'volume', 'issue', 'page', 'DOI', 'ISSN'];

	$terms = array();
	
	foreach ($keys as $k)
	{
		if (isset($csl->$k))
		{
			switch ($k)
			{
				case 'author':
					if ($include_authors) // include if we want to
					{
						foreach ($csl->$k as $author)
						{
							$author_parts = array();
							if (isset($author->family))
							{
								$author_parts[] = $author->family;
							}
							if (isset($author->given))
							{
								$author_parts[] = $author->given;
							}
							$terms[] = join(', ', $author_parts);
						}
					}
					break;
					
				case 'issued':
					$terms[] = $csl->$k->{'date-parts'}[0][0];
					break;
					
				case 'DOI':
					// eat(?)
					if (preg_match('/10\.\d+/', $csl->$k))
					{
						$terms[] = $csl->$k;
					}
					break;
					
				case 'ISSN':
					// eat(?)
					break;
					
				default:
					if (is_array($csl->$k))
					{
						$terms[] = html_entity_decode($csl->$k[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
					}
					else
					{
						$terms[] = html_entity_decode($csl->$k, ENT_QUOTES | ENT_HTML5, 'UTF-8');
					}
					break;
			
			}
		}
	
	}
	
	return join(' ', $terms);
}


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
		
		$debug = false;
		
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
					// we have some possible hits, so check them
					
					// does query string have authors?
					// check this as some sources, e.g. ION, won't
					
					$have_authors = false;
					if (preg_match('/(\b[0-9]{4}[a-z]?\b.*)$/', $text, $m)) // look for year string
					{
						$have_authors = strlen($m[1]) > 4;						
					}
					
					$hit_text = csl_to_string ($obj->hits->hits[$i]->_source->search_result_data->csl, $have_authors);
					
		
					// compare
					if ($debug)
					{
						echo "\n";
						$result = compare_common_subsequence($text, $hit_text, true);
						echo "\n";
					}
					else
					{
						$result = compare_common_subsequence($text, $hit_text, false);						
					}
					
					$matched = false;

					if ($result->normalised[1] > 0.85)
					{
						// one string is almost an exact substring of the other
						if ($result->normalised[0] > 0.75)
						{
							// and the shorter string matches a good chunk of the bigger string
							$matched = true;	
						}
					}
					
					if ($matched)
					{
						$hit = new stdclass;
						$hit->id 	= str_replace('biostor-', '', $obj->hits->hits[$i]->_id);
				
						$hit->name 	= $obj->hits->hits[$i]->_source->search_result_data->name;
				
						$hit->score = $result->normalised[1];
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