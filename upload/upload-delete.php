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

$ids=array(
237583,
237585,
237586,
237587,
237588,
);

$ids=array(
237898
);

$ids=array(
246266,
246265,
266013,
266018,
85588,
97992,
236244,
102401,
97997,
246260,
246259,
266067,
51777,
266069,
266072,
52012,
246257,
98380,
246254,
);

// to do
$ids=array(
69192,
74728,
20454,
);

foreach ($ids as $id)
{
	$doc_id = 'biostor-' . $id;
	
	$elastic->send('DELETE',  '_doc/' . urlencode($doc_id));					
}
	
?>
