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

$ids=array(
247175,
);

// 2023-06-10
$ids=array(
87808,
235793,
235151,
72378,
53329,
53330,
53335,
53336,
59215,
59235,
59240,
59241,
59242,
59244,
59245,
59246,
60700,
65952,
66401,
66402,
66404,
66412,
66415,
66416,
66418,
69661,
69685,
71308,
77749,
77750,
87744,
87745,
87746,
87747,
87748,
87749,
87750,
87751,
87752,
87753,
87754,
87755,
87756,
87757,
87758,
87759,
87760,
87761,
87763,
87764,
87765,
87766,
87767,
87768,
87769,
87770,
87771,
87772,
87773,
87774,
87775,
87776,
87777,
87778,
87779,
87780,
87781,
87782,
87783,
87784,
87785,
87786,
87787,
87788,
87789,
87790,
87791,
87792,
87793,
87794,
87795,
87796,
87797,
87799,
87800,
87801,
87802,
87803,
87804,
87805,
87806,
87807,
87808,
87809,
87810,
87811,
97647,
97649,
97651,
97652,

);

$ids=array(67469);

$ids=array(
71939,

);

/*

12909	Micropropagation of common ash (Fraxinus excelsior L.) 	Plant Cell Tissue Organ Culture			16164808
44521	A cladistic evaluation of the cosmopolitan genus Eumeces Wiegmann (Reptilia, Squamata, Scincidae)	Russian Journal of Herpetology	7	1	0
47799	A new species of Chronogaster Cobb, 1913 (Nemata : Plectidae) with am amended diagnosis of the genus and discussion of cuticular ornamentation	Revue de Nématologie	6	257	0
57459	Arachnida.  In C. V. Riley (ed.), Scientific results of the U.S. Eclipse expedition to West Africa 1889-90. Report on the Insecta, Arachnida and Myriapoda	Proc. U. S. nat. Mus.	16	586	15780952
71135	Hemiptera	Archiv für Naturgeschichte	66		6956834
74521	latrodectus	Transactions of The San Diego Society of Natural History			4303181
78612	meteorite	Proceedings of The United States National Museum			7608263
98821	Neue Canthidium – arten	Entomologische Nachrichten			18636017
100699	Opinion 1479. Antispila Hübner, [1825] (Insecta, Lepidoptera): Antispila stadtmuellerella Hübner, [1825] designated as type species. 	Bulletin of Zoological Nomenclature	45	79	12229754
*/


$ids=array(
12909 ,98821 ,78612 ,74521 ,100699, 57459 ,47799 ,71135 ,44521
);

$ids=array(
133745,
);

foreach ($ids as $id)
{
	$doc_id = 'biostor-' . $id;
	
	echo $doc_id . "\n";
	
	$elastic->send('DELETE',  '_doc/' . urlencode($doc_id));					
}
	
?>
