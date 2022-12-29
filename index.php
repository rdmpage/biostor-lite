<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/elastic.php');

require_once (dirname(__FILE__) . '/interface-shared.php');




//----------------------------------------------------------------------------------------
// CSL to JSON-LD
function csl_to_jsonld($csl)
{
	// crude hack to get JSON-LD
	
	$obj = new stdclass;
	$obj->{'@context'}	= 'http://schema.org/';
	
	$type = 'schema:Thing';
	
	if (isset($csl->type))
	{
		switch ($csl->type)
		{
			case 'book':
				$type = 'schema:Book';
				break;
			
			case 'chapter':
				$type = 'schema:Chapter';
				break;
			
			case 'article-journal':
			default:
				$type = 'schema:ScholarlyArticle';
				break;	
		}
	}
	
	$obj->{'@type'}	= $type;
	
	//------------------------------------------------------------------------------------
	// name
	$name_done = false;
	
	if (isset($csl->multi))
	{
		if (isset($csl->multi->_key->title))
		{			
			$obj->name = array();
			
			foreach ($csl->multi->_key->title as $language => $value)
			{
				$str = new stdclass;
				$str->{'@language'} = $language;
				$str->{'@value'} = strip_tags($value);
			}		
			$name_done = true;			
		}
	}
	
	if (!$name_done)
	{
		if (isset($csl->title))
		{
			$obj->name = strip_tags($csl->title);
		}
	}
	
	//------------------------------------------------------------------------------------
	// parts of	
	
	if (isset($csl->{'container-title'}))
	{
		if (!isset($obj->isPartOf))
		{
			$obj->isPartOf = array();
		}
		
		$container = new stdclass;
		
		// What type of container is it?
		switch ($obj->{'@type'})
		{
			case 'Chapter':
				$container->{'@type'} = 'Book';
				break;		
				
			case 'ScholarlyArticle':
			default:
				$container->{'@type'} = 'Periodical';
				break;		
		}
		
		// name
		$name_done = false;
	
		if (isset($csl->multi))
		{
			if (isset($csl->multi->_key->{'container-title'}))
			{			
				$container->name = array();
			
				foreach ($csl->multi->_key->{'container-title'} as $language => $value)
				{
					$str = new stdclass;
					$str->{'@language'} = $language;
					$str->{'@value'} = strip_tags($value);
				}		
				$name_done = true;			
			}
		}
	
		if (!$name_done)
		{			
			$container->name = strip_tags($csl->{'container-title'});
		}		
				
		// ISSN?
		if (isset($csl->ISSN))
		{
			$container->issn = array();
			foreach ($csl->ISSN as $issn)
			{
				$container->issn[] = $issn;

				if (!isset($container->sameAs))
				{
					$container->sameAs = array();
				}
				$container->sameAs[] = 'http://issn.org/resource/ISSN/' . $issn;							
			}
		}
		else		
		{
			if (isset($csl->OCLC))
			{
				$container->oclcnum = $csl->OCLC;
				if (!isset($container->sameAs))
				{
					$container->sameAs = array();
				}
				$container->sameAs[] = 'http://www.worldcat.org/oclc/' . $csl->OCLC;
			}		
		}

		$obj->isPartOf[] = $container;
	}
	

	if (isset($csl->volume))
	{
		if (!isset($obj->isPartOf))
		{
			$obj->isPartOf = array();
		}
		$volume = new stdclass;
		$volume->{'@type'} = 'PublicationVolume';
		$volume->volumeNumber = $csl->volume;
		
		$obj->isPartOf[] = $volume;
	}
	
	if (isset($csl->issue))
	{
		if (!isset($obj->isPartOf))
		{
			$obj->isPartOf = array();
		}
		$issue = new stdclass;
		$issue->{'@type'} = 'PublicationIssue';
		$issue->issueNumber = $csl->issue;
		
		$obj->isPartOf[] = $issue;
	}	
		
	//------------------------------------------------------------------------------------
	if (isset($csl->page))
	{
		$obj->pagination = $csl->page;
	}
	
	//------------------------------------------------------------------------------------
	// date 
	if (isset($csl->issued))
	{
		$date = '';
		$d = $csl->issued->{'date-parts'}[0];

		// sanity check
		if (is_numeric($d[0]))
		{
			if ( count($d) > 0 ) $year = $d[0] ;
			if ( count($d) > 1 ) $month = preg_replace ( '/^0+(..)$/' , '$1' , '00'.$d[1] ) ;
			if ( count($d) > 2 ) $day = preg_replace ( '/^0+(..)$/' , '$1' , '00'.$d[2] ) ;
			if ( isset($month) and isset($day) ) $date = "$year-$month-$day";
			else if ( isset($month) ) $date = "$year-$month";
			else if ( isset($year) ) $date = "$year";

			$obj->datePublished = $date;
		}				
	}
	
	//------------------------------------------------------------------------------------
	// identifiers 

	// DOI is sameAs and also an identifier
	if (isset($csl->DOI))
	{		
		$obj->sameAs[] = 'https://doi.org/' . strtolower($csl->DOI);
		
		add_property_value($obj, 'identifier', 'doi', strtolower($csl->DOI));
	}
	
	// Wikidata
	if (isset($csl->WIKIDATA))
	{		
		$obj->sameAs[] = 'http://www.wikidata.org/entity/' . strtolower($csl->WIKIDATA);
		
		add_property_value($obj, 'identifier', 'wikidata', $csl->WIKIDATA);
	}
	
	//------------------------------------------------------------------------------------
	// authors
	if (isset($csl->author))
	{
		$obj->author = array();
	
		foreach ($csl->author as $one_author)
		{
			$author = new stdclass;
			$author->{'@type'} = 'Person';
			
			if (isset($one_author->literal))
			{
				$author->name = $one_author->literal;
			}
			if (isset($one_author->family))
			{
				$author->familyName = $one_author->family;
			}
			if (isset($one_author->given))
			{
				$author->givenName = $one_author->given;
			}
			
			$obj->author[] = $author;		
		}	
	}		
		
	return $obj;
}


//----------------------------------------------------------------------------------------
function do_entity_custom_tags($entity)
{
	$tag_names = array(
		'citation_title',
		'citation_author',
		'citation_doi',
		'citation_journal_title',
		'citation_issn',
		'citation_volume',
		'citation_issue',
		'citation_volume',
		'citation_firstpage',
		'citation_lastpage',
		'citation_abstract_html_url',
		'citation_pdf_url',
		'citation_fulltext_html_url',
		'citation_abstract',
		'citation_date'
	);

	$tags = array();
	
	foreach ($tag_names as $tag_name)
	{
		switch ($tag_name)
		{
			case 'citation_title':
				if (!isset($tags[$tag_name]))
				{
					$tags[$tag_name] = array();
				}
				
				if (isset($entity->name))
				{
					$tags[$tag_name][] = get_literal($entity->name);
				}
				break;
				
			case 'citation_doi':
				$doi = get_property_value($entity, 'identifier', 'doi');
				if ($doi != '')
				{
					if (!isset($tags[$tag_name]))
					{
						$tags[$tag_name] = array();
					}
				
					$tags[$tag_name][] = $doi;
				}
				break;
				
			case 'citation_date':				
				if (isset($entity->datePublished))
				{
					if (!isset($tags[$tag_name]))
					{
						$tags[$tag_name] = array();
					}				
					$tags[$tag_name][] = get_literal($entity->datePublished);
				}			
				break;	
				
			case 'citation_firstpage':
				if (isset($entity->pagination))
				{
					$parts = explode('-', $entity->pagination);
					if (!isset($tags[$tag_name]))
					{
						$tags[$tag_name] = array();
					}				
					$tags[$tag_name][] = $parts[0];
				}
				break;
				
			case 'citation_lastpage':
				if (isset($entity->pagination))
				{
					$parts = explode('-', $entity->pagination);
					if (count($parts) == 2)
					{
						if (!isset($tags[$tag_name]))
						{
							$tags[$tag_name] = array();
						}				
						$tags[$tag_name][] = $parts[1];
					}
				}
				break;
							
			case 'citation_author':	
				if (isset($entity->author))
				{
					foreach ($entity->author as $author)
					{
						if (!isset($tags[$tag_name]))
						{
							$tags[$tag_name] = array();
						}	
						
						$name = array();
						
						if (isset($author->name))
						{
							$name[] = $author->name;
						}
						else
						{
							if (isset($author->givenName))
							{
								$name[] = $author->givenName;
							}
							if (isset($author->familyName))
							{
								$name[] = $author->familyName;
							}
						}
						
						if (count($name) > 1)
						{
							$tags[$tag_name][] = join(' ', $name);
						}
					}
				}							
				break;
				
			case 'citation_pdf_url':
				if (isset($entity->encoding))
				{
					$pdf = '';
					foreach ($entity->encoding as $encoding)
					{
						if ($encoding->encodingFormat == "application/pdf")
						{
							$pdf = $encoding->contentUrl;
						}
					}
		
					if ($pdf != '')
					{
						if (!isset($tags[$tag_name]))
						{
							$tags[$tag_name] = array();
						}				
						$tags[$tag_name][] = $pdf;
					}
				}
				break;
				
				

		
			default:
				break;
		}
	
	}
	
	if (isset($entity->isPartOf))
	{
		foreach ($entity->isPartOf as $part)
		{
			switch ($part->{'@type'})
			{
				case 'PublicationVolume':
					$tag_name = 'citation_volume';
					if (!isset($tags[$tag_name]))
					{
						$tags[$tag_name] = array();
					}				
					$tags[$tag_name][] = $part->volumeNumber;
					break;	

				case 'PublicationIssue':
					$tag_name = 'citation_issue';
					if (!isset($tags[$tag_name]))
					{
						$tags[$tag_name] = array();
					}				
					$tags[$tag_name][] = $part->issueNumber;
					break;	

				case 'Periodical':
					if (isset($part->name))
					{
						$tag_name = 'citation_journal_title';
						if (!isset($tags[$tag_name]))
						{
							$tags[$tag_name] = array();
						}				
						$tags[$tag_name][] = get_literal($part->name);
					}
					
					if (isset($part->issn))
					{
						$tag_name = 'citation_issn';
						if (!isset($tags[$tag_name]))
						{
							$tags[$tag_name] = array();
						}		
					
					}
					break;	
					
				default:
					break;			
				
			
			
			}
		}
	
	}

	return $tags;
}

			


//----------------------------------------------------------------------------------------
// Get one record in JSON-LD
function do_one($id)
{
	global $config;
	global $elastic;
	
	$record = null;
	
	if (preg_match('/^\d+$/', $id))
	{
		$id = 'biostor-' . $id;
	}
	
	$data = $elastic->send('GET', '_doc/' . urlencode($id));
	
	//echo $data;
	
	if ($data != '')
	{
		$obj = json_decode($data);
		
		// convert CSL to RDF
		$csl = $obj->_source->search_result_data->csl;		

		$record = csl_to_jsonld($csl);
				
		$record->{'@id'} = $config['web_server'] . $config['web_root'] . 'reference/' . str_replace('biostor-', '', $id);
		
		if (isset($obj->_source->search_result_data->thumbnailUrl))
		{
			$record->thumbnailUrl = $obj->_source->search_result_data->thumbnailUrl;
		}
				
		if (isset($obj->_source->search_result_data->description))
		{
			$record->description = $obj->_source->search_result_data->description;
		}
		
		// PDF
		$record->encoding = array();
	
		$encoding = new stdclass;
		$encoding->{'@type'} = 'MediaObject';
	
		$encoding->encodingFormat = "application/pdf";
		$encoding->contentUrl = 'https://archive.org/download/' . $id . '/' . $id . '.pdf';
	
		$record->encoding[] = $encoding;
	}
	
	return $record;
}

//----------------------------------------------------------------------------------------
function search_result_to_rdf($obj, $query_string = "")
{
	// process and convert to RDF

	// schema.org DataFeed
	$output = new stdclass;

	$output->{'@context'} = (object)array
		(
			'@vocab'	 			=> 'http://schema.org/',
			'goog' 					=> 'http://schema.googleapis.com/',
			'resultScore'		 	=> 'goog:resultScore',
			
			'biostor'				=> 'https://biostor.org/reference/'
		);

	$output->{'@graph'} = array();
	$output->{'@graph'}[0] = new stdclass;
	$output->{'@graph'}[0]->{'@id'} = "http://example.rss";
	$output->{'@graph'}[0]->{'@type'} = "DataFeed";
	$output->{'@graph'}[0]->dataFeedElement = array();
	
	if (isset($obj->hits))
	{
		$num_hits = 0;
		
		// Elastic 7.6.2
		if (isset($obj->hits->total->value))
		{
			$num_hits = $obj->hits->total->value;
		}
		else
		{
			$num_hits = $obj->hits->total;			
		}
		
		$time = '';
		if (isset($obj->took))
		{
			if ($obj->took > 1000)
			{
				$time = '(' . floor($obj->took/ 1000) . ' seconds)';
			}
			else
			{
				$time = '(' . round($obj->took/ 1000, 2) . ' seconds)';
			}
		}
		
		if ($num_hits == 0)
		{
			// Describe search
			$output->{'@graph'}[0]->description = "No results " . $time;
		}
		else
		{
			// Describe search
			if ($obj->hits->total->value == 1)
			{
				$output->{'@graph'}[0]->description = "One hit ";
			}
			else
			{
				$output->{'@graph'}[0]->description = $obj->hits->total->value . " hits ";
			}
			
			$output->{'@graph'}[0]->description .=  $time;
			
			if ($query_string != '')
			{
				$output->{'@graph'}[0]->query = $query_string;
			}

			foreach ($obj->hits->hits as $hit)
			{
				$dataFeedItem = new stdclass;
				
				$dataFeedItem = new stdclass;
				$dataFeedItem->{'@type'} = "DataFeedItem";
				
				$dataFeedItem->resultScore = $hit->_score;
				
				//echo '<pre>';
				//print_r($hit->_source->search_result_data);
				//echo '</pre>';
				
				if (isset($hit->_source->search_result_data->created))
				{
					$dataFeedItem->dateCreated = gmdate("Y-m-d", $hit->_source->search_result_data->created);					
				}

				if (isset($hit->_source->search_result_data->modified))
				{
					$dataFeedItem->dateModified = gmdate("Y-m-d", $hit->_source->search_result_data->modified);				
				}				
				
				// item
				$dataFeedItem->item = new stdclass;
				
				$dataFeedItem->item->{'@id'} = 'biostor:' . str_replace('biostor-', '', $hit->_id);
				
				// name
				$dataFeedItem->item->name = "";
				
				if (isset($hit->_source->search_result_data->name))
				{
					$dataFeedItem->item->name = $hit->_source->search_result_data->name;				
				}

				// thumbnail
				if (isset($hit->_source->search_result_data->thumbnailUrl))
				{
					$dataFeedItem->item->thumbnailUrl = $hit->_source->search_result_data->thumbnailUrl;				
				}
				
				// description
				if (isset($hit->_source->search_result_data->description))
				{
					$dataFeedItem->item->description = $hit->_source->search_result_data->description;				
				}
				
				// CSL
				if (isset($hit->_source->search_result_data->csl))
				{
					$csl = $hit->_source->search_result_data->csl;
					
					// type
					$type = 'Thing';
				
					if (isset($csl->type))
					{
						switch ($csl->type)
						{
							case 'book':
								$type = 'Book';
								break;
			
							case 'chapter':
								$type = 'Chapter';
								break;
			
							case 'article-journal':
							default:
								$type = 'ScholarlyArticle';
								break;	
						}
					}	
					$dataFeedItem->item->{'@type'}	= $type;
					
					// date
					//------------------------------------------------------------------------------------
					// date 
					if (isset($csl->issued))
					{
						$date = '';
						$d = $csl->issued->{'date-parts'}[0];

						// sanity check
						if (is_numeric($d[0]))
						{
							if ( count($d) > 0 ) $year = $d[0] ;
							if ( count($d) > 1 ) $month = preg_replace ( '/^0+(..)$/' , '$1' , '00'.$d[1] ) ;
							if ( count($d) > 2 ) $day = preg_replace ( '/^0+(..)$/' , '$1' , '00'.$d[2] ) ;
							if ( isset($month) and isset($day) ) $date = "$year-$month-$day";
							else if ( isset($month) ) $date = "$year-$month";
							else if ( isset($year) ) $date = "$year";

							$dataFeedItem->item->datePublished = $date;
						}				
					}									
					
				}
				

				$output->{'@graph'}[0]->dataFeedElement[] = $dataFeedItem;
			}			
		}
	}
	
	return $output;
}

//----------------------------------------------------------------------------------------
// Search
function do_search($q)
{
	global $elastic;
				
	// empty query (just in case we get to this point)
	if ($q == '')
	{
		$obj = new stdclass;
		$obj->hits = new stdclass;
		$obj->hits->total = 0;
		$obj->hits->hits = array();
	}
	else
	{		
		$matched = false;
	
		if (!$matched)
		{
			if (preg_match('/^issn:(?<issn>[0-9]{4}-[0-9]{3}[0-9X])$/u', trim($q), $m))
			{
				$query_json = '{
					"size": 5000,
					"_source": ["id", "search_result_data.name", "search_result_data.description", "search_result_data.thumbnailUrl", "search_data.year", "search_result_data.csl"],
					"query": {
						"bool": {
							"must": {
								"term": { "search_result_data.csl.ISSN.keyword" : "' . $m['issn'] .'" }
							}
						}
					}
				}';		
				$matched = true;
			}	
		}	
	
		if (!$matched)
		{
			if (preg_match('/^oclc:(?<oclc>\d+)$/u', trim($q), $m))
			{
				$query_json = '{
					"size": 5000,
					"_source": ["id", "search_result_data.name", "search_result_data.description", "search_result_data.thumbnailUrl", "search_data.year", "search_result_data.csl"],
					"query": {
						"bool": {
							"must": {
								"term": { "search_result_data.csl.OCLC.keyword" : "' . $m['oclc'] .'" }
							}
						}
					}
				}';		
				$matched = true;
			}	
		}	
	
		if (!$matched)
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
				}]
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
			
			$matched = true;
		}
		
		$resp = $elastic->send('POST', '_search?pretty', $post_data = $query_json);
		
		$obj = json_decode($resp);
		
		//print_r($obj);
	}
	
	$output = search_result_to_rdf($obj, $q);

	return $output;
}

//----------------------------------------------------------------------------------------
function do_welcome()
{
	global $config;
	
	$html = '<div style="font-size:5em;">BioStor</div>';
	
	$html .= '<p>BioStor is an interface to articles in the 
	<a href="https://www.biodiversitylibrary.org">Biodiversity Heritage Library</a> (BHL). 
	It is experimental, and things are likely to change. The articles displayed here are also
	regularly harvested by BHL and so you can always view them there.</a></p>';
	
	$html .= '<p>You can search for articles by title, author, etc. 
	You can also browse the <a href="containers">list of journals</a> for whch BioStor has articles.</p>';
	
	$html .= '<h2>Examples</h2>';
	
	//$html .= '<h3>Articles</h3>';
	
	$json =  '[
				{ "pageID": 43605918, "referenceID": 248475},
				{ "pageID": 35669296, "referenceID": 114607},
				{ "pageID": 43276884, "referenceID": 201883},
				{ "pageID": 48184882, "referenceID": 149688},
				{ "pageID": 49942215, "referenceID": 192990},
				{ "pageID": 48951678, "referenceID": 167448},
				{ "pageID": 52110073, "referenceID": 232256},
				{ "pageID": 41229695, "referenceID": 115363}
				]';
				
				
	$html .= '<div>';
	
	$obj = json_decode($json);
	
	foreach ($obj as $example)
	{
		$html .= '<div class="example">';
		$html .= '<a href="reference/' . $example->referenceID . '">';
		if ($config['use_cloudimage'])
		{
			$html .= '<img src="https://aezjkodskr.cloudimg.io/https://www.biodiversitylibrary.org/pagethumb/' . $example->pageID . ',200,200?height=200">';
		}
		else
		{
			$html .= '<img src="https://www.biodiversitylibrary.org/pagethumb/' . $example->pageID . ',200,200">';
		}
		
		$html .= '</a>';
		$html .= '</div>';
	}
	
	
	$html .= '</div>';					
	
	return $html;
}

//----------------------------------------------------------------------------------------
function do_footer()
{
	$html =  '<a href=".">BioStor</a> is a project by <a href="https://twitter.com/rdmpage">Rod Page</a>. 
It\'s goal is to make discoverable articles in the <a href="https://www.biodiversitylibrary.org">Biodiversity Heritage Library</a> (BHL).';

	return $html;
}

//----------------------------------------------------------------------------------------
function do_issn($issn)
{
	return do_search('issn:' . $issn);
}

//----------------------------------------------------------------------------------------
function do_issn_year($issn, $year)
{
	global $elastic;
	
	$fields = 'search_result_data.csl.ISSN.keyword';
	
	$query_json = '{
	"size": 500,
	"_source": ["id", "search_result_data.name", "search_result_data.description", "search_result_data.thumbnailUrl", "search_data.year", "search_result_data.csl"],
	"query": {
		"bool": {
			"must": {
				"term": { "' . $fields . '" : "' . $issn .'" }
			},
			"filter": [
				{
					"term": { "search_data.year": ' . $year .'}
				}
			]			
		}
	}
}';
	
	$resp = $elastic->send('POST', '_search?pretty', $post_data = $query_json);
	
	$obj = json_decode($resp);
	
	$output = search_result_to_rdf($obj, "issn=$issn, year=$year");

	return $output;
}


//----------------------------------------------------------------------------------------
function display_issn($issn)
{
	global $config;
	
	$title = '';	
	$meta = '';
	$script = '';
	
	// Can we get details about the journal and treat that as the entity for the page?	
	$filename = 'about/' . $issn . '.json';
	
	if (file_exists($filename))
	{
		$json = file_get_contents($filename);
		$entity = json_decode($json);
	}
	else
	{
		$entity = new stdclass;
		$entity->name = "Unknown";
	}
	
	$jsonld = json_encode($entity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			
	$obj = do_issn($issn);
		
	display_html_start($title, $meta, $script, $jsonld);	
	
	// set search bar to ISSN query
	display_header('issn:' . $issn);	
				
	display_main_start();	
		
	//print_r($obj);
	
	// Breadcrumbs
	$path = array();
	
	$path["."] = "Home";	
	$path["containers"] = "Containers";
	$path[""] = $entity->issn[0];

	echo '<ul class="breadcrumb">';
	foreach ($path as $k => $v)
	{
		echo '<li>';		
		if ($k != "")
		{
			echo '<a href="' . $k . '">';
		}
		echo $v;
		if ($k != "")
		{
			echo '</a>';
		}
		echo '</li>';	
	}	
	echo '</ul>';
	
	echo '<h1>' . get_literal($entity->name) . '</h1>';
	
	if (isset($entity->description))
	{
		echo '<p class="description">' . get_literal($entity->description, 'en') . '</p>';
	}
	
	// articles in journal
	//display_list($obj);
	display_decade_list($obj);
		
	display_main_end();	
	display_footer();
	display_html_end();	
}

//----------------------------------------------------------------------------------------
function display_issn_year($issn, $year)
{
	global $config;
	
	$title = '';	
	$meta = '';
	$script = '';
	
	// Can we get details about the journal	
	$filename = 'about/' . $issn . '.json';
	
	if (file_exists($filename))
	{
		$json = file_get_contents($filename);
		$entity = json_decode($json);
	}
	else
	{
		$entity = new stdclass;
		$entity->name = "Unknown";
		$entity->issn[] = "0000-0000";
	}
	
	
	// Do search	
	$obj = do_issn_year($issn, $year);
	
	$jsonld = json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	
	display_html_start($title, $meta, $script, $jsonld);	
	
	display_header();	
				
	display_main_start();	
	
	// Breadcrumbs
	$path = array();
	
	$path["."] = "Home";	
	$path["containers"] = "Containers";
	$path["issn/" . $entity->issn[0]] = get_literal($entity->name);
	$path[""] = $year;

	echo '<ul class="breadcrumb">';
	foreach ($path as $k => $v)
	{
		echo '<li>';		
		if ($k != "")
		{
			echo '<a href="' . $k . '">';
		}
		echo $v;
		if ($k != "")
		{
			echo '</a>';
		}
		echo '</li>';	
	}	
	echo '</ul>';
	
	//print_r($obj);
	
	display_list($obj);
		
	display_main_end();	
	display_footer();
	display_html_end();	
}

//----------------------------------------------------------------------------------------
function do_oclc($oclc)
{
	return do_search('oclc:' . $oclc);
}

//----------------------------------------------------------------------------------------
function do_oclc_year($oclc, $year)
{
	global $elastic;
	
	$fields = 'search_result_data.csl.OCLC.keyword';
	
	$query_json = '{
	"size": 500,
	"_source": ["id", "search_result_data.name", "search_result_data.description", "search_result_data.thumbnailUrl", "search_data.year", "search_result_data.csl"],
	"query": {
		"bool": {
			"must": {
				"term": { "' . $fields . '" : "' . $oclc .'" }
			},
			"filter": [
				{
					"term": { "search_data.year": ' . $year .'}
				}
			]			
		}
	}
}';
	
	$resp = $elastic->send('POST', '_search?pretty', $post_data = $query_json);
	
	$obj = json_decode($resp);
	
	$output = search_result_to_rdf($obj, "oclc=$oclc, year=$year");

	return $output;
}

//----------------------------------------------------------------------------------------
function display_oclc($oclc)
{
	global $config;
	
	$title = '';	
	$meta = '';
	$script = '';
	
	// Can we get details about the journal and treat that as the entity for the page?	
	$filename = 'about/' . $oclc . '.json';
	
	if (file_exists($filename))
	{
		$json = file_get_contents($filename);
		$entity = json_decode($json);
	}
	else
	{
		$entity = new stdclass;
		$entity->name = "Unknown";
		$entity->oclcnum = 0;
	}
	
	$jsonld = json_encode($entity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			
	$obj = do_oclc($oclc);
		
	display_html_start($title, $meta, $script, $jsonld);	
	
	// set search bar to ISSN query
	display_header('oclc:' . $oclc);	
				
	display_main_start();	
		
	//print_r($obj);
	
	// Breadcrumbs
	$path = array();
	
	$path["."] = "Home";	
	$path["containers"] = "Containers";
	$path[""] = $entity->oclcnum;

	echo '<ul class="breadcrumb">';
	foreach ($path as $k => $v)
	{
		echo '<li>';		
		if ($k != "")
		{
			echo '<a href="' . $k . '">';
		}
		echo $v;
		if ($k != "")
		{
			echo '</a>';
		}
		echo '</li>';	
	}	
	echo '</ul>';
	
	echo '<h1>' . get_literal($entity->name) . '</h1>';
	
	if (isset($entity->description))
	{
		echo '<p class="description">' . get_literal($entity->description, 'en') . '</p>';
	}
	
	// articles in journal
	//display_list($obj);
	display_decade_list($obj);
		
	display_main_end();	
	display_footer();
	display_html_end();	
}


//----------------------------------------------------------------------------------------
function display_oclc_year($oclc, $year)
{
	global $config;
	
	$title = '';	
	$meta = '';
	$script = '';
	
	// Can we get details about the journal	
	$filename = 'about/' . $oclc . '.json';
	
	if (file_exists($filename))
	{
		$json = file_get_contents($filename);
		$entity = json_decode($json);
	}
	else
	{
		$entity = new stdclass;
		$entity->name = "Unknown";
		$entity->oclcnum = 0;
	}
	
	
	// Do search	
	$obj = do_oclc_year($oclc, $year);
	
	$jsonld = json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	
	display_html_start($title, $meta, $script, $jsonld);	
	
	display_header();	
				
	display_main_start();	
	
	// Breadcrumbs
	$path = array();
	
	$path["."] = "Home";	
	$path["containers"] = "Containers";
	$path["oclc/" . $entity->oclcnum] = get_literal($entity->name);
	$path[""] = $year;

	echo '<ul class="breadcrumb">';
	foreach ($path as $k => $v)
	{
		echo '<li>';		
		if ($k != "")
		{
			echo '<a href="' . $k . '">';
		}
		echo $v;
		if ($k != "")
		{
			echo '</a>';
		}
		echo '</li>';	
	}	
	echo '</ul>';
	
	//print_r($obj);
	
	display_list($obj);
		
	display_main_end();	
	display_footer();
	display_html_end();	
}


//----------------------------------------------------------------------------------------
function display_google_analytics()
{
	echo "<!-- Google Analytics -->
		<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

		ga('create', 'UA-12127487-1', 'auto');
		ga('send', 'pageview');
		</script>
		<!-- End Google Analytics -->	
";

}

//----------------------------------------------------------------------------------------
function display_containers()
{
	global $config;
	
	$title = 'Containers';	
	$meta = '';
	$script = '';
	$jsonld = '';
	
	$filename = 'about/containers.json';
	
	if (file_exists($filename))
	{
		$json = file_get_contents($filename);
		$obj = json_decode($json);
	}
	else
	{
		$obj = array();
	}
	
	//$jsonld = json_encode($entity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			
		
	display_html_start($title, $meta, $script, $jsonld);	
	
	// set search bar to ISSN query
	display_header();	
				
	display_main_start();	
	
	// 
	echo '<h1>Containers</h1>';
	
	echo '<p class="description">This page lists the journals and other "containers" (such as books) available in  BioStor.</p>';
	
	//print_r($containers);	
	
	// display
	/*
	echo '<div>';
	$letters = array();
	foreach ($obj as $letter => $containers)
	{
		echo '<span style="padding:1em;">' . $letter . '</span> ';
	}
	echo '</div>';
	*/
	
	$html = '';
		
	foreach ($obj as $letter => $containers)
	{
		$html .= '<details>';
		$html .= '<summary>';
		$html .= $letter;
		$html .= '</summary>' . "\n";
	
		$html .= '<ul style="list-style-type: none;">';
	
		foreach($containers as $name => $data)
		{
			$html .= '<li>';
			
			$label = $name;
		
			if (isset($data->issn))
			{
				$html .= '<a href="issn/' . $data->issn[0] . '">';
				
				$label .= ' (' . $data->issn[0] . ')';
			}
			
			if (isset($data->oclcnum))
			{
				$html .= '<a href="oclc/' . $data->oclcnum . '">';
				
				$label .= ' (OCLCnum ' . $data->oclcnum . ')';
			}
			
			
			$html .= $label;
			$html .= '</a>';
			$html .= '</li>';
			
			//echo $name . ' ' . $link . "\n";
		
	
		}
		$html .= '</ul>';
		$html .= '</details>';
	}
	
	echo $html;
	
		
	display_main_end();	
	display_footer();
	display_html_end();	
}


//----------------------------------------------------------------------------------------
function main()
{
	$query = '';
		
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
		
	// Error message
	if (isset($_GET['error']))
	{	
		$error_msg = $_GET['error'];		
		default_display($error_msg);
		exit(0);			
	}
	
	// Show entity
	if (isset($_GET['id']))
	{	
		$id = $_GET['id'];						
		display_entity($id);
		exit(0);
	}
		
	// Show search
	if (isset($_GET['q']))
	{	
		$query = $_GET['q'];
		
		// specialised searches
		if (preg_match('/^issn:(?<issn>[0-9]{4}-[0-9]{3}[0-9X])$/u', trim($query), $m))
		{
			display_issn($m['issn']);
			exit(0);
		}
		
		display_search($query);
		
		exit(0);
	}	
	
	// Custom searches
	if (isset($_GET['issn']))
	{	
		$issn = $_GET['issn'];
		
		if (isset($_GET['year']))
		{	
			$year = $_GET['year'];
			display_issn_year($issn, $year);
			exit(0);			
		}
		
		display_issn($issn);		
		exit(0);
	}	
	
	// Custom searches
	if (isset($_GET['oclc']))
	{	
		$oclc = $_GET['oclc'];
		
		if (isset($_GET['year']))
		{	
			$year = $_GET['year'];
			display_oclc_year($oclc, $year);
			exit(0);			
		}
		
		display_oclc($oclc);		
		exit(0);
	}	
	
	
	// Containers
	if (isset($_GET['containers']))
	{	
		display_containers();
		exit(0);
	}
	
	
}

//----------------------------------------------------------------------------------------

main();

?>