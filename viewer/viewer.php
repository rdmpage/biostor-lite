<?php

/*
	This is a simple page to load a viewer for a manifest
*/
	
$manifest_uri = $_GET['manifest_uri'];
	
// which canvas (=page) to show?
$cv = 0;
	
if (isset($_GET['cv']))
{
	$cv = $_GET['cv'];
}
	
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" type="text/css" href="uv/uv.css">
    <script src="uv/lib/offline.js"></script>
    <script src="uv/helpers.js"></script>
    <title>BioStor IIIF Viewer</title>
    <style>
    	body {
    		margin:0px;
    	}
        #uv {
            width: 100%;
            height: 1000px;
        }
    </style>
</head>
<body>
	
		<div id="uv" class="uv"></div>
	    <script>
	        window.addEventListener('uvLoaded', function (e) {
	            createUV('#uv', {
	                iiifResourceUri: '<?php echo $manifest_uri ?>',
					configUri: 'uv-config.json',
					canvasIndex: <?php echo $cv ?>,
	            }, new UV.URLDataProvider());
	        }, false);
	    </script>
	    <script src="uv/uv.js"></script>
	
	
</body>
</html>
	

	