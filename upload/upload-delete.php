<?php

// Delete records from Elastic

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');
require_once (dirname(dirname(__FILE__)) . '/elastic.php');


$ids=array();

$ids=array(
50096,
50097,
50098,
50099,
50100,
50101,
50102,
50103,
50104,
50105,
50106,
50107,
50108,
);

foreach ($ids as $id)
{
	$doc_id = 'biostor-' . $id;
	
	$elastic->send('DELETE',  '_doc/' . urlencode($doc_id));					
}
	
?>
