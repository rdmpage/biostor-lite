<?php



$item = 125545;

if (isset($_GET['item']))
{
	$item = $_GET['item'];
}

//print_r($_REQUEST);


//----------------------------------------------------------------------------------------
function get($url)
{
	$data = null;
	
	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	return $data;
}

//----------------------------------------------------------------------------------------


$colour_index = 0;
$colours = array("#FF6600", "#6666CC", "#FFFF99", '#66FF66');


$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?op=GetItemMetadata&itemid=' 
	. $item . '&ocr=t&pages=t&apikey=' . '0d4f0303-712e-49e0-92c5-2113a5959159' . '&format=json';

$json = get($url);


$item_data = json_decode($json);


// articles		
$url = 'http://direct.biostor.org/itemarticles.php?item=' . $item;

$json = get($url);

$item_articles = json_decode($json);
	
// colours for articles
$page_colours = array();


$colour_no_article = 'none';

foreach ($item_data->Result->Pages as $page)
{
	$page_colours[$page->PageID] = $colour_no_article ;
}

$page_to_biostor = array();


$colour_index = 0;

if (isset($item_articles->articles))
{

	foreach ($item_articles->articles as $article)
	{
		$colour = $colours[$colour_index];
	
		foreach ($article->bhl_pages as $PageID)
		{
			$page_colours[$PageID] = $colour;
		
			$page_to_biostor[$PageID] = $article->reference_id;
		}	

		$colour_index++;
		if ($colour_index == count($colours))
		{
			$colour_index = 0;
		}
	}
}

// display


$html = '<html>';	
$html .= '<head>';	
$html .= '<link rel="stylesheet" type="text/css" href="css/materialize.min.css">';
$html .= '</head>';	

$html .= '<body>';	

$html .= '<div style="background:#EEE;display: block;overflow: auto;">';	

foreach ($item_data->Result->Pages as $page)
{
	$html .=  '<a ';
	$html .=  'style="background-color:' . $page_colours[$page->PageID] . ';padding:10px;margin:0px;float:left;width:auto;height:auto;"';
	
	
	if ($page_colours[$page->PageID] == $colour_no_article )
	{
		$html .= ' href="https://biodiversitylibrary.org/page/' . $page->PageID . '"';
	}
	else
	{
		$html .= ' href="https://biostor.org/reference/' . $page_to_biostor[$page->PageID] . '"';		
	}
	$html .= ' target="_new"';
	$html .= '>';
	
	
	$html .= '<img style="border:1px solid rgb(192,192,192);" height="130" src="http://exeg5le.cloudimg.io/s/height/200/http://biodiversitylibrary.org/pagethumb/' . $page->PageID . ',200,200" />';
	
	if (isset($page->PageNumbers))
	{
		$html .=  '<div style="text-align:center">' . $page->PageNumbers[0]->Prefix . '&nbsp;' . str_replace('%', '&nbsp;', $page->PageNumbers[0]->Number) . '</div>';
	}
	else
	{
		$html .=  '<div style="text-align:center">' . $page->PageID . '</div>';
	}
	$html .=  '</a>';
}	

$html .= '</div>';		

$html .= '</body>';	
$html .= '</html>';	

echo $html;

?>