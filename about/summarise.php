<?php

// Generate list of journals for each letter

//----------------------------------------------------------------------------------------
// Literals may be strings, objects (e.g., a []@language, @value] pair), or an array.
// Handle this and return a string
function get_literal($key, $language='en')
{
	$literal = '';
	
	if (is_string($key))
	{
		$literal = $key;
	}
	else
	{
		if (is_object($key) && !is_array($key))
		{
			$literal = $key->{'@value'};
		}
		else
		{
			if (is_array($key))
			{
				$values = array();
				
				foreach ($key as $k)
				{
					if (is_object($k))
					{
						if ($language != '')
						{
							if ($language == $k->{'@language'})
							{
								$values[] = $k->{'@value'};
							}
						}
						else
						{
							$values[] = $k->{'@value'};
						}
					}
				}
				
				$literal = join(" / ", $values);
			}
		}
	}
	
	return $literal;
}

//----------------------------------------------------------------------------------------
// read source files
$basedir = '.';

$files = scandir($basedir);

$letters = array();

foreach ($files as $filename)
{
	if (preg_match('/\.json$/', $filename))
	{	
		// do stuff on $basedir . '/' . $filename
		
		$json = file_get_contents($basedir . '/' . $filename);
		
		echo $json;
		
		$obj = json_decode($json);
		
		if (isset($obj->name))
		{
			$sort_name = get_literal($obj->name);
			$sort_name = preg_replace('/^The\s+/', '', $sort_name);
						
			$letter = mb_substr($sort_name, 0, 1);
			
			if (!isset($letters[$letter]))
			{
				$letters[$letter] = array();
			}
			if (!isset($letters[$letter][$sort_name]))
			{
				$letters[$letter][$sort_name] = array();
			}
			
			$letters[$letter][$sort_name] = $obj;
		}
	}
}


// Output an object we can use to create pages

ksort($letters);

foreach ($letters as $letter => $containers)
{
	ksort($letters[$letter]);

}

file_put_contents('containers.json', json_encode($letters));


/*
ksort($letters);

print_r($letters);

foreach ($letters as $letter => $containers)
{
	ksort($containers);
	
	foreach($containers as $name => $data)
	{
		$link = '';
		
		if (isset($data->issn))
		{
			$link = $data->issn[0];
		}
		echo $name . ' ' . $link . "\n";
		
	
	}
}
*/



?>
