<?php

// feed

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/elastic.php');

$feed_format 	= 'atom';
//$feed_format 	= 'rss2';
//$feed_format 	= 'rss1';

if (isset($_GET['format']))
{
	$feed_format = $_GET['format'];
}

$query_json = '{
	"size": 100,
	"sort": [{
		"search_data.modified": {
			"order": "desc"
		}
	}]
}';

$resp = $elastic->send('POST', '_search?pretty', $post_data = $query_json);

$obj = json_decode($resp);

//print_r($obj);




$feed_title 	= 'BioStor';
$feed_url 		= $config['web_server'] . $config['web_root'] . 'feed.php';

$feed = new DomDocument('1.0', 'UTF-8');
$feed->formatOutput = true;

switch ($feed_format)
{
	case 'atom':
		$rss = $feed->createElement('feed');
		$rss->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
		
		//$rss->setAttribute('xmlns:geo', 'http://www.w3.org/2003/01/geo/wgs84_pos#');
		//$rss->setAttribute('xmlns:georss', 'http://www.georss.org/georss');
		//$rss->setAttribute('xmlns:georss', 'http://www.georss.org/georss');

		//$rss->setAttribute('xmlns:prism', 'http://prismstandard.org/namespaces/1.2/basic/');
		
		$rss = $feed->appendChild($rss);
	
		// feed
	
		// title
		$title = $feed->createElement('title');
		$title = $rss->appendChild($title);
		$value = $feed->createTextNode($feed_title);
		$value = $title->appendChild($value);
	
		// link
		$link = $feed->createElement('link');
		$link->setAttribute('href', $feed_url);
		$link = $rss->appendChild($link);
	
		$link = $feed->createElement('link');
		$link->setAttribute('rel', 'self');
		$link->setAttribute('type', 'application/atom+xml');
		$link->setAttribute('href', $feed_url);
		$link = $rss->appendChild($link);
			
		// updated
		$updated = $feed->createElement('updated');
		$updated = $rss->appendChild($updated);
		$value = $feed->createTextNode(date(DATE_ATOM));
		$value = $updated->appendChild($value);
	
		// id
		$id = $feed->createElement('id');
		$id = $rss->appendChild($id);
		$id->appendChild($feed->createTextNode($feed_url ));
		
		
		// items
		if (isset($obj->hits))
		{
			foreach ($obj->hits->hits as $hit)
			{
				$item = $rss->appendChild($feed->createElement('entry'));
				
				$item_id = $config['web_server'] . $config['web_root'] . 'reference/' . str_replace('biostor-', '', $hit->_source->id);
				
				$id = $item->appendChild($feed->createElement('id'));
				$id->appendChild($feed->createTextNode($item_id));

				$link = $item->appendChild($feed->createElement('link'));
				$link->setAttribute('rel', 'alternate');
				$link->setAttribute('type', 'text/html');
				$link->setAttribute('href', $item_id);					
							
				if (isset($hit->_source->search_result_data->name))
				{
					$title = $item->appendChild($feed->createElement('title'));
					$title->appendChild($feed->createTextNode($hit->_source->search_result_data->name));
				
				}
				
				if (isset($hit->_source->search_result_data->description))
				{
					$description_content = '';
				
					if (isset($hit->_source->search_result_data->thumbnailUrl))
					{
						$thumbnailUrl = $hit->_source->search_result_data->thumbnailUrl;
						$thumbnailUrl = preg_replace('/,\d+,\d+$/', ',240,240', $thumbnailUrl);
						
					
						$description_content = '<p>' . '<img src="' . $thumbnailUrl . '" width="240"></p>';
						$description_content .= '<p>' . $hit->_source->search_result_data->description . '</p>';
					}
					else
					{
						$description_content .= '<p>' . $hit->_source->search_result_data->description . '</p>';				
					}
				
					$description = $item->appendChild($feed->createElement('content'));
					$description->setAttribute('type', 'html');	
					$description->appendChild($feed->createTextNode($description_content));
				}
				
				if (isset($hit->_source->search_result_data->created))
				{
					$published = $item->appendChild($feed->createElement('published'));
					$published->appendChild($feed->createTextNode(date(DATE_ATOM, $hit->_source->search_result_data->created)));				
				}
				
				if (isset($hit->_source->search_result_data->modified))
				{
					$updated = $item->appendChild($feed->createElement('updated'));
					$updated->appendChild($feed->createTextNode(date(DATE_ATOM, $hit->_source->search_result_data->modified)));
				}				
					
			}
		}		
		
	
		/*
		// items
		foreach ($dataFeed->dataFeedElement as $dataFeedElement)
		{
			$item = $rss->appendChild($feed->createElement('entry'));
			
			// title
			if (isset($dataFeedElement->name))
			{
				$title = $item->appendChild($feed->createElement('title'));
				$title->appendChild($feed->createTextNode($dataFeedElement->name));
			}
			
			rss_content($dataFeedElement, $feed, $item);
			
			// id
			if (isset($dataFeedElement->id))
			{
				$id = $item->appendChild($feed->createElement('id'));
				$id->appendChild($feed->createTextNode($dataFeedElement->id));
			}
										
			// link
			if (isset($dataFeedElement->url))
			{
				$link = $item->appendChild($feed->createElement('link'));
				$link->setAttribute('rel', 'alternate');
				$link->setAttribute('type', 'text/html');
				$link->setAttribute('href', $dataFeedElement->url);					
			}
			
			if (isset($dataFeedElement->pdf))
			{
				$link = $item->appendChild($feed->createElement('link'));
				$link->setAttribute('rel', 'alternate');
				$link->setAttribute('type', 'application/pdf');
				$link->setAttribute('href', $dataFeedElement->pdf);					
			}
							
			// published
			if (isset($dataFeedElement->datePublished))
			{
				$published = $item->appendChild($feed->createElement('published'));
				$published->appendChild($feed->createTextNode(date(DATE_ATOM, strtotime($dataFeedElement->datePublished))));
			}
			
			// updated
			if (isset($dataFeedElement->dateModified))
			{
				$updated = $item->appendChild($feed->createElement('updated'));
				$updated->appendChild($feed->createTextNode(date(DATE_ATOM, strtotime($dataFeedElement->dateModified))));
			}
			else
			{
				// ATOM expects updated so use datePublished
				if (isset($dataFeedElement->datePublished))
				{
					$updated = $item->appendChild($feed->createElement('updated'));
					$updated->appendChild($feed->createTextNode(date(DATE_ATOM, strtotime($dataFeedElement->datePublished))));
				}
				
			}
			
			
			// bibliographic details
			if (isset($dataFeedElement->doi))
			{
				$doi = $item->appendChild($feed->createElement('prism:doi'));
				$doi->appendChild($feed->createTextNode(strtolower($dataFeedElement->doi)));
			}
			
		
			
			// geo
			rss_geo($dataFeedElement, $feed, $item);
		
		}
		*/
	
		break;
		
	case 'rss2':
		$rss = $feed->createElement('rss');
		$rss->setAttribute('version', '2.0');
		
		//$rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
		//$rss->setAttribute('xmlns:georss', 'http://www.georss.org/georss');
		//$rss->setAttribute('xmlns:prism', 'http://prismstandard.org/namespaces/1.2/basic/');			
		
		$rss = $feed->appendChild($rss);

		// channel
		$channel = $feed->createElement('channel');
		$channel = $rss->appendChild($channel);
	
		// title
		$title = $channel->appendChild($feed->createElement('title'));
		$title->appendChild($feed->createTextNode($feed_title));
		
		// description
		//rss_content($dataFeed, $feed, $channel, 'description');
		
		// link
		$link = $channel->appendChild($feed->createElement('link'));
		$link->appendChild($feed->createTextNode($feed_url));
		
		$link = $feed->createElement('atom:link');
		$link->setAttribute('rel', 'self');
		$link->setAttribute('type', 'application/atom+xml');
		$link->setAttribute('href', $feed_url);
		$link = $channel->appendChild($link);
		
		/*
		foreach ($dataFeed->dataFeedElement as $dataFeedElement)
		{
			$item = $channel->appendChild($feed->createElement('item'));
			
			// title
			if (isset($dataFeedElement->name))
			{
				$title = $item->appendChild($feed->createElement('title'));
				$title->appendChild($feed->createTextNode($dataFeedElement->name));
			}
			
			// description
			if (isset($dataFeedElement->description))
			{
				$description_content = '';
				
				if (isset($dataFeedElement->thumbnailUrl))
				{
					$description_content = '<p>' . '<img src="' . $dataFeedElement->thumbnailUrl . '" width="240"></p>';
					$description_content .= '<p>' . $dataFeedElement->description . '</p>';
					$description_content .= '<p>' . $dataFeedElement->url . '</p>';
					
				}
				else
				{
					$description_content = $dataFeedElement->description;
				}				
			
				$description = $item->appendChild($feed->createElement('description'));
				$description->appendChild($feed->createTextNode($description_content));
			}
			
			// link
			if (isset($dataFeedElement->url))
			{
				$link = $item->appendChild($feed->createElement('link'));
				$link->appendChild($feed->createTextNode($dataFeedElement->url));
			}
			
			// pubDate
			if (isset($dataFeedElement->datePublished))
			{
				$pubDate = $item->appendChild($feed->createElement('pubDate'));
				$pubDate->appendChild($feed->createTextNode(date(DATE_RSS, strtotime($dataFeedElement->datePublished))));
			}
			
			if (isset($dataFeedElement->url))
			{
				$guid = $item->appendChild($feed->createElement('guid'));
				$guid->setAttribute('href', $dataFeedElement->url);					
			}
			
			
			// bibliographic details
			if (isset($dataFeedElement->doi))
			{
				$doi = $item->appendChild($feed->createElement('prism:doi'));
				$doi->appendChild($feed->createTextNode(strtolower($dataFeedElement->doi)));
			}	
						
			
			// geo
			rss_geo($dataFeedElement, $feed, $item);
			
			
		}
		*/
					
		break;
		
	
	case 'rss1':
		$rss = $feed->createElement('rdf:RDF');
		$rss->setAttribute('xmlns', 'http://purl.org/rss/1.0/');
		$rss->setAttribute('xmlns:rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
		
		//$rss->setAttribute('xmlns:prism', 'http://prismstandard.org/namespaces/1.2/basic/');

		$rss = $feed->appendChild($rss);

		// channel
		$channel = $feed->createElement('channel');
		$channel->setAttribute('rdf:about', $feed_url);
		$channel = $rss->appendChild($channel);
	
		// title
		$title = $channel->appendChild($feed->createElement('title'));
		$title->appendChild($feed->createTextNode($feed_title));

		// link
		$link = $channel->appendChild($feed->createElement('link'));
		$link->appendChild($feed->createTextNode($feed_url));

		// description
		$description = $channel->appendChild($feed->createElement('description'));
		$description->appendChild($feed->createTextNode($feed_title));

		// items
		$items = $channel->appendChild($feed->createElement('items'));
		$seq = $items->appendChild($feed->createElement('rdf:Seq'));
		
		/*
		foreach ($dataFeed->dataFeedElement as $dataFeedElement)
		{
			$li = $seq->appendChild($feed->createElement('rdf:li'));
			$li->setAttribute('rdf:resource', $dataFeedElement->url);
		}			
		
		foreach ($dataFeed->dataFeedElement as $dataFeedElement)
		{
			$item = $rss->appendChild($feed->createElement('item'));
			$item->setAttribute('rdf:about', $dataFeedElement->url);
			
			// title
			if (isset($dataFeedElement->name))
			{
				$title = $item->appendChild($feed->createElement('title'));
				$title->appendChild($feed->createTextNode($dataFeedElement->name));
			}
			
			// link
			if (isset($dataFeedElement->url))
			{
				$link = $item->appendChild($feed->createElement('link'));
				$link->appendChild($feed->createTextNode($dataFeedElement->url));
			}
			
			// description
			if (isset($dataFeedElement->description))
			{
				$description = $item->appendChild($feed->createElement('description'));
				$description->appendChild($feed->createTextNode($dataFeedElement->description));
			}				
			
			// could add more RDF here so we could feed a triple store
			/*
			// bibliographic details
			if (isset($dataFeedElement->doi))
			{
				$doi = $item->appendChild($feed->createElement('prism:doi'));
				$doi->appendChild($feed->createTextNode(strtolower($dataFeedElement->doi)));
			}
			
			
		}
		*/
		

		break;
	
	default:
		break;
}

header("Content-type: application/xml");
//header("Content-type: text/plain");
echo $feed->saveXML();




?>
