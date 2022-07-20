<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/api_utils.php');
require_once (dirname(__FILE__) . '/elastic.php');

//----------------------------------------------------------------------------------------
function to_jats($obj)
{

	$csl = $obj->search_result_data->csl;
	$csl->id = str_replace('biostor-', '', $obj->id);
	
	$impl = new DOMImplementation();

	$doc = $impl->createDocument(null, '',
		$impl->createDocumentType("article", 
			"SYSTEM", 
			"jats-archiving-dtd-1.0/JATS-archivearticle1.dtd"));
	
	// http://stackoverflow.com/questions/8615422/php-xml-how-to-output-nice-format
	$doc->preserveWhiteSpace = false;
	$doc->formatOutput = true;	
	
	// root element is <records>
	$article = $doc->appendChild($doc->createElement('article'));
	
	$article->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
	
	$front = $article->appendChild($doc->createElement('front'));
	
	if (isset($csl->{"container-title"}))
	{	
		$journal_meta = $front->appendChild($doc->createElement('journal-meta'));
		$journal_title_group = $journal_meta->appendChild($doc->createElement('journal-title-group'));
		$journal_title = $journal_title_group->appendChild($doc->createElement('journal-title'));
		$journal_title->appendChild($doc->createTextNode($csl->{"container-title"}));
	}
	
	if (isset($csl->ISSN))
	{
		$issn = $journal_meta->appendChild($doc->createElement('issn'));
		$issn->appendChild($doc->createTextNode($csl->ISSN[0]));
	}
	
	$article_meta = $front->appendChild($doc->createElement('article-meta'));
	
	$article_id = $article_meta->appendChild($doc->createElement('article-id'));
	$article_id->setAttribute('pub-id-type', 'biostor');
	$article_id->appendChild($doc->createTextNode($csl->id));

	if (isset($csl->DOI))
	{
		$article_id = $article_meta->appendChild($doc->createElement('article-id'));
		$article_id->setAttribute('pub-id-type', 'doi');
		$article_id->appendChild($doc->createTextNode($csl->DOI));
	}
	
	$title_group = $article_meta->appendChild($doc->createElement('title-group'));
	$article_title = $title_group->appendChild($doc->createElement('article-title'));
	$article_title->appendChild($doc->createTextNode($csl->title));
	
	if (isset($csl->author) && count($csl->author) > 0)
	{
		$contrib_group = $article_meta->appendChild($doc->createElement('contrib-group'));
		
		foreach ($csl->author as $author)
		{
			$contrib = $contrib_group->appendChild($doc->createElement('contrib'));
			$contrib->setAttribute('contrib-type', 'author');
			
			$name = $contrib->appendChild($doc->createElement('name'));
			
			if (isset($author->family))
			{			
				$surname = $name->appendChild($doc->createElement('surname'));
				$surname->appendChild($doc->createTextNode($author->family));
			}
			if (isset($author->given))
			{
				$given_name = $name->appendChild($doc->createElement('given-names'));
				$given_name->appendChild($doc->createTextNode($author->given));
			}
		}
	}
	
	if (isset($csl->issued))
	{
		$pub_date = $article_meta->appendChild($doc->createElement('pub-date'));
		$pub_date->setAttribute('pub-type', 'ppub');
		
		if (count($csl->issued->{'date-parts'}[0]) == 1)
		{
			$year = $pub_date->appendChild($doc->createElement('year'));
			$year->appendChild($doc->createTextNode($csl->issued->{'date-parts'}[0][0]));		
		}

		if (count($csl->issued->{'date-parts'}[0]) == 2)
		{
			$month = $pub_date->appendChild($doc->createElement('month'));
			$month->appendChild($doc->createTextNode($csl->issued->{'date-parts'}[0][1]));		
		}
		
		if (count($csl->issued->{'date-parts'}[0]) == 3)
		{
			$month = $pub_date->appendChild($doc->createElement('day'));
			$month->appendChild($doc->createTextNode($csl->issued->{'date-parts'}[0][2]));		
		}
	}
	
	if (isset($csl->volume))
	{
		$volume = $article_meta->appendChild($doc->createElement('volume'));
		$volume->appendChild($doc->createTextNode($csl->volume));
	}
	if (isset($csl->issue))
	{
		$issue = $article_meta->appendChild($doc->createElement('issue'));
		$issue->appendChild($doc->createTextNode($csl->issue));
	}
	
	if (isset($csl->page))
	{
		if (preg_match('/(.*)-(.*)/', $csl->page, $m))
		{
			$fpage = $article_meta->appendChild($doc->createElement('fpage'));
			$fpage->appendChild($doc->createTextNode($m[1]));		
			
			$lpage = $article_meta->appendChild($doc->createElement('lpage'));
			$lpage->appendChild($doc->createTextNode($m[2]));		
								
		}
		else
		{
			$fpage = $article_meta->appendChild($doc->createElement('fpage'));
			$fpage->appendChild($doc->createTextNode($csl->page));		
		}	
	}
	
	// BHL pages
	$body = $article->appendChild($doc->createElement('body'));
	
	/*
	if (count($reference->text) > 0)
	{
		foreach ($reference->text as $text)
		{
			$preformat = $body->appendChild($doc->createElement('preformat'));
			$preformat->appendChild($doc->createTextNode($text));
		}
	}
	*/
	
	$supplementary_material = $body->appendChild($doc->createElement('supplementary-material'));
	$supplementary_material->setAttribute('content-type', 'scanned-pages');
	
	foreach ($obj->search_result_data->bhl_pages as $page_count => $PageID)
	{
		$graphic = $supplementary_material->appendChild($doc->createElement('graphic'));
		
		$graphic->setAttribute('id', 'graphic-' . $page_count);
		
		$graphic->setAttribute('xlink:href', 'http://www.biodiversitylibrary.org/pagethumb/' . $PageID);
		//$graphic->setAttribute('xlink:role', $PageID);
		
		$page_name = 'scanned-page';
		if (isset($obj->search_result_data->page_numbers))
		{
			if (isset($obj->search_result_data->page_numbers[$page_count]))
			{
				$page_name = $obj->search_result_data->page_numbers[$page_count];
			}
		}
		
		$graphic->setAttribute('xlink:title', $page_name );
	}

	return $doc->saveXML();
}




//----------------------------------------------------------------------------------------
function default_display()
{
	echo "hi";
}

//----------------------------------------------------------------------------------------
// URL (e.g., PDF) exists
function display_head ($url, $callback)
{
	$obj = new stdclass;
	$obj->url = $url;
	$obj->found = false;

	$status = 404;
	
	if (api_head($url))
	{
		$status = 200;
		$obj->found = true;
	}
		
	api_output($obj, $callback, $status);
}	
	
//----------------------------------------------------------------------------------------
// One record
function display_one ($id, $format= '', $callback = '')
{
	global $elastic;

	$obj = null;
	$status = 404;
		
	$data = $elastic->send('GET', '_doc/' . urlencode($id));
	
	if ($data != '')
	{
		$obj = json_decode($data);
		
		if ($format == 'citeproc')
		{
			if (isset($obj->_source->search_result_data->csl))
			{
				$obj = $obj->_source->search_result_data->csl;
			}
		}
		
		if ($format == 'xml')
		{
			$xml = to_jats($obj->_source);
			
			header("Content-type: application/xml");
			echo $xml;
			exit();
		}
		
		
		$status = 200;
	}
		
	api_output($obj, $callback, $status);
}	

//----------------------------------------------------------------------------------------
// Full text search using Elastic
function display_elastic_search ($q, $filter=null, $from = 0, $size = 20, $callback = '')
{
	global $elastic;
	
	$status = 404;
				
	if ($q == '')
	{
		$obj = new stdclass;
		$obj->hits = new stdclass;
		$obj->hits->total = 0;
		$obj->hits->hits = array();
		
		$status = 200;
	}
	else
	{		
		// query type		
		$query_json = '';
		
		if ($filter)
		{
			if (isset($filter->author))
			{
				// author search is different( but not working yet)	
				$query_json = 		
	'{
	"size":50,
    "query": {
        "bool": {
            "must": [ {
				   "multi_match" : {
				  "query": "<QUERY>",
				  "fields":["search_data.author"] 
				}
				}]
        }
    }
	}';
			$query_json = str_replace('<QUERY>', $q, $query_json);
			
			// echo $query_json;
			
			}
		}
		
		// default is search on fulltext fields
		if ($query_json == '')
		{
			$query_json = '{
			"size":50,
				"query": {
					"bool" : {
						"must" : [ {
				   "multi_match" : {
				  "query": "<QUERY>",
				  "fields":["search_data.fulltext", "search_data.fulltext_boosted^4"] 
				}
				}],
			"filter": <FILTER>
				}
			},
			"aggs": {
			"type" :{
				"terms": { "field" : "search_data.type.keyword" }
			  },
			  "year" :{
				"terms": { "field" : "search_data.year" }
			  },
			  "container" :{
				"terms": { "field" : "search_data.container.keyword" }
			  },
			  "author" :{
				"terms": { "field" : "search_data.author.keyword" }
			  },
			  "classification" :{
				"terms": { "field" : "search_data.classification.keyword" }
			  }  

			}

	
			}';
			
			$query_json = str_replace('<QUERY>', $q, $query_json);
		}
	
	$filter_string = '[]';
	
	if ($filter)
	{
		$f = array();
		
		if (isset($filter->year))
		{
			$one_filter = new stdclass;
			$one_filter->match = new stdclass;
			$one_filter->match->{'search_data.year'} = $filter->year;
			
			$f[] = $one_filter;			
		}

		// this doesn't work
		if (isset($filter->author))
		{
			$one_filter = new stdclass;
			$one_filter->match = new stdclass;
			$one_filter->match->{'search_data.author'} = $filter->author;
			
			$f[] = $one_filter;			
		}
		
		$filter_string = json_encode($f);
	}
	
	$query_json = str_replace('<FILTER>', $filter_string, $query_json);
	
	
	$resp = $elastic->send('POST', '_search?pretty', $post_data = $query_json);
	

		$obj = json_decode($resp);

		$status = 200;
	}
	
	api_output($obj, $callback, 200);
}

//----------------------------------------------------------------------------------------
// Geo search
function display_geo ($geojson, $format = 'json', $callback = '')
{
	global $elastic;
	
	$obj = null;
	$status = 404;

	$geo = json_decode($geojson);
	
	$query = new stdclass;
	$query->size = 100;
	$query->query = new stdclass;
	$query->query->bool = new stdclass;
	$query->query->bool->must = new stdclass;
	$query->query->bool->must->match_all = new stdclass;
	
	$query->query->bool->filter = new stdclass;
	$query->query->bool->filter->geo_polygon = new stdclass;
	$query->query->bool->filter->geo_polygon->{'search_data.geometry.coordinates'} = new stdclass;
	$query->query->bool->filter->geo_polygon->{'search_data.geometry.coordinates'}->points = array();
	
	if (isset($geo->geometry->coordinates))
	{
		$query->query->bool->filter->geo_polygon->{'search_data.geometry.coordinates'}->points = $geo->geometry->coordinates[0];
	}
	
	$response = $elastic->send('POST',  '_search?pretty', json_encode($query));					
	$obj = json_decode($response);
	
	if ($obj)
	{
		$status = 200;
	}
		
	api_output($obj, $callback, $format, $status);
}	



//--------------------------------------------------------------------------------------------------
function main()
{
	global $config;

	$callback = '';
	$handled = false;
	
	
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
	
	if (isset($_GET['callback']))
	{	
		$callback = $_GET['callback'];
	}
	
	// Submit job
	if (!$handled)
	{
		if (isset($_GET['id']))
		{	
			$id = $_GET['id'];
			
			$format = '';
			
			if (isset($_GET['format']))
			{
				$format = $_GET['format'];
			}			
			
			if (!$handled)
			{
				display_one($id, $format, $callback);
				$handled = true;
			}
			
		}
	}
	
	if (!$handled)
	{
		if (isset($_GET['pdf']))
		{	
			$pdf = $_GET['pdf'];
			
			display_head($pdf, $callback);
			$handled = true;
		}
	}
	
	if (!$handled)
	{
		if (isset($_GET['geo']) && ($_GET['geo'] != ''))
		{	
			$geo = $_GET['geo'];
			
			$format = 'json';
			
			if (isset($_GET['format']))
			{
				$format = $_GET['format'];
			}						
			
			if (!$handled)
			{
				display_geo($geo, $format, $callback);
				$handled = true;
			}
			
		}
	}	
	
	
	if (!$handled)
	{
		if (isset($_GET['q']))
		{	
			$q = $_GET['q'];
			
			// Elastic
			$from = 0;
			$size = 10;
			
			$filter = null;
			
			if (isset($_GET['year']))
			{
				if (!$filter)
				{
					$filter = new stdclass;
				}
			
				$filter->year = (Integer)$_GET['year'];
			}			

			if (isset($_GET['author']))
			{
				if (!$filter)
				{
					$filter = new stdclass;
				}
			
				$filter->author = $_GET['author'];
			}											
			
			display_elastic_search($q, $filter, $from, $size, $callback);
			
			$handled = true;
		}
			
	}
	
	if (!$handled)
	{
		default_display();
	}

}


main();

?>