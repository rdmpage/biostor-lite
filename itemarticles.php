<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/elastic.php');


$ItemID = -1;

if (isset($_GET['item']))
{
	$ItemID = $_GET['item'];
}

// handle possible synonym
if (isset($_GET['ItemID']))
{
	$ItemID = $_GET['ItemID'];
}


$callback = '';

if (isset($_GET['callback']))
{
	$callback = $_GET['callback'];
}

$obj = new stdclass;
$obj->ItemID = $ItemID;
$obj->articles = array();

// get records for this item
$query_json = 		
	'{
	"size": 1000,
	"query": {
		"term": {
			"search_data.item": {
				"value": <ITEM>
			}
		}
	}
}';

$query_json = str_replace('<ITEM>', (Integer)$ItemID, $query_json);
		
$resp = $elastic->send('POST', '_search?pretty', $post_data = $query_json);

$response_obj = json_decode($resp);

// print_r($response_obj);

foreach ($response_obj->hits->hits as $hit)
{
	// build article that BHL will import
	
	$article = new stdclass;
	$article->reference_id = str_replace('biostor-', '', $hit->_source->id);
		
	foreach ($hit->_source->search_result_data->csl as $k => $v)
	{
		switch ($k)		
		{
			case 'type':
				switch ($v)
				{
					case 'article-journal':
						$article->genre = 'article';
						break;
				
					default:
						$article->genre = $v;
						break;
				}
				break;
		
			case 'title':
			case 'volume':
			case 'issue':
				$article->{$k} = $v;
				break;
				
			case 'author':
				$article->authors = array();
				foreach ($v as $a)
				{
					$author = new stdclass;
					$author->lastname = $a->family;
					$author->forename = $a->given;
					
					if (isset($a->id))
					{
						$a->id;
					}
					
					$article->authors[] = $author;
				}
				break;
				
			case 'container-title':
				$article->{'secondary-title'} = $v;
				break;
				
			case 'page':
				if (preg_match('/(.*)-(.*)/', $v, $m))
				{
					$article->spage = $m[1];
					$article->epage = $m[2];						
				}
				else
				{
					$article->spage = $v;
				}
				break;
				
			case 'issued':
				$article->year = $v->{'date-parts'}[0][0];
				
				// ISO date
				$article->date = $v->{'date-parts'}[0][0];
				if (isset($v->{'date-parts'}[0][1]))
				{
					$article->date .= '-' . str_pad($v->{'date-parts'}[0][1], 2, '0', STR_PAD_LEFT);
					
					if (isset($v->{'date-parts'}[0][2]))
					{
						$article->date .= '-' . str_pad($v->{'date-parts'}[0][2], 2, '0', STR_PAD_LEFT);
					}					
				}				
				break;
							
			case 'ISSN':
				$article->issn = $v[0];
				break;
			
			case 'DOI':
				$article->doi = $v;
				break;
				
	
			default:
				break;
		}
	}
	
	// Dates created and modified
	$article->created = date(DATE_ISO8601, $hit->_source->search_data->created);
	$article->modified = date(DATE_ISO8601, $hit->_source->search_data->modified);
	
	// BHL pages	
	$article->bhl_pages = $hit->_source->search_result_data->bhl_pages;
	$article->PageID = $article->bhl_pages[0];
	
	$obj->articles[] = $article;
}



header('Content-type: text/plain');
if ($callback != '')
{
	echo $callback .'(';
}

echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if ($callback != '')
{
	echo ')';
}

?>

