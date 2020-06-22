<?php

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/elastic.php');

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


$start   = 252694;
$end  	 = 252735;

$start   = 252736;
$end  	 = 252778;


$start   = 252779;
$end  	 = 252779;

$start   = 252780;
$end  	 = 252889;

$start   = 252890;
$end  	 = 252890;

$start   = 252891;
$end  	 = 252917;

$start   = 252918;
$end  	 = 252935;


$start   = 252936;
$end     = 252936;

$start   = 252937;
$end  	 = 252961;

$start   = 252962;
$end  	 = 252962;

$start   = 252963;
$end  	 = 253148;

$start   = 253149;
$end  	 = 253262;

$start   = 253262;
$end  	 = 253370;

$start   = 253371;
$end     = 253371;

$start   = 253372;
$end  	 = 253373;

$start   = 253374;
$end     = 253374;

$start   = 217700;
$end     = 217700;


$start   = 253375;
$end     = 253375;

$start   = 253376;
$end     = 253376;

$start   = 253377;
$end     = 253386;

$start   = 253387;
$end     = 253390;

$start   = 253391;
$end     = 253404;

$start   = 253405;
$end     = 253421;

$start   = 253422;
$end     = 253513;

$start   = 253514;
$end     = 253528;

$start   = 253550;
$end     = 253550;

$start   = 253552;
$end     = 253564;

$start   = 253565;
$end     = 253594;

$start   = 253595;
$end     = 253597;

$start   = 253598;
$end     = 253784;

$start   = 253785;
$end     = 253824;

$start   = 253825;
$end     = 253870;

$start   = 253871;
$end     = 253881;

$start   = 253882;
$end     = 253883;

$start   = 253884;
$end     = 253884;

$start   = 253885;
$end     = 253930;

$start   = 253931;
$end     = 253968;

$start   = 253976;
$end   	 = 253989;

$start   = 253990;
$end   	 = 253991;


$start   = 253994;
$end   	 = 254079;

$start   = 254080;
$end   	 = 254080;


$start   = 254082;
$end   	 = 254082;

$start   = 254083;
$end   	 = 254144;

$start   = 254145;
$end   	 = 254303;


$start   = 254306;
$end   	 = 254323;

$start   = 254331;
$end   	 = 254365;

$start   = 254366;
$end   	 = 254382;

$start   = 254383;
$end   	 = 254508;

$start   = 254509;
$end   	 = 255054;

$start   = 255057;
$end   	 = 255065;

$start   = 255066;
$end   	 = 255092;

$start   = 255093;
$end   	 = 255299;

$start   = 255300;
$end   	 = 255300;


$start   = 255301;
$end   	 = 255301;

$start   = 255302;
$end   	 = 255484;

$start   = 255495;
$end   	 = 255604;

$start   = 255605;
$end   	 = 255616;

$start   = 255617;
$end   	 = 255623;


$start   = 255624;
$end   	 = 255624;

$start   = 255641;
$end   	 = 255641;

$start   = 255642;
$end   	 = 255688;

$start   = 255689;
$end   	 = 256072;

$start   = 256073;
$end   	 = 256073;

$start   = 256074;
$end   	 = 256448;

$start   = 256449;
$end   	 = 256747;



$start   = 256748;
$end   	 = 256981;

$start   = 256982;
$end   	 = 257039;

$start   = 257041;
$end   	 = 257041;

$start   = 257042;
$end   	 = 257357;

$start   = 257358;
$end   	 = 257358;

$start   = 257358;
$end   	 = 257394;





$ids=array(
246230,
246231,
246232,
246233,
246234,
246235,
246240,
246241,
);

$ids=array(
246234,
);

for ($id = $start; $id <= $end; $id++)
//foreach ($ids as $id)
{
	echo $id . "\n";
		
	$url = "http://direct.biostor.org:5984/biostor/_design/elastic/_view/biostor?key=" . urlencode('"biostor/' . $id . '"');
	
	echo $url . "\n";
	
	$json = get($url);

	$obj = json_decode($json);

	//print_r($obj);

	if (1)
	{
		$elastic_doc = new stdclass;
		$elastic_doc->doc = $obj->rows[0]->value;
		$elastic_doc->doc_as_upsert = true;
		
		//print_r($elastic_doc);
		
		$elastic->send('POST',  '_doc/' . urlencode($elastic_doc->doc->id). '/_update', json_encode($elastic_doc));					
	}
}
	
?>
