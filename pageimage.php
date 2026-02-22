<?php

/*
 * BHL page image proxy for BioStor.
 *
 * Fetches page thumbnail images from the Biodiversity Heritage Library,
 * caches them in the system temp directory, and serves them with long
 * Cache-Control headers so that Cloudflare can cache them at the edge.
 *
 * URL (via .htaccess rewrite): /pageimage/{PageID}/{w}/{h}
 * Direct:                      pageimage.php?id={PageID}&w={w}&h={h}
 *
 * Only ever proxies biodiversitylibrary.org/pagethumb/ URLs — not an open proxy.
 *
 * Based on https://github.com/andrieslouw/imagesweserv
 */

error_reporting(E_ALL);

//--------------------------------------------------------------------------------------------------
function download_file($path, $fname)
{
	$options = array(
		CURLOPT_FILE           => fopen($fname, 'w'),
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS      => 10,
		CURLOPT_URL            => $path,
		CURLOPT_FAILONERROR    => true, // HTTP code > 400 will throw curl error
		CURLOPT_TIMEOUT        => 60,
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; ImageFetcher/5.6; +http://images.weserv.nl/)',
	);

	$ch = curl_init();
	curl_setopt_array($ch, $options);
	$return = curl_exec($ch);

	if ($return === false)
	{
		$error = curl_error($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
		unlink($fname);

		if ($errno == 6)
		{
			header('HTTP/1.1 410 Gone');
			header('X-Robots-Tag: none');
			echo 'Error 410: Hostname of origin is unresolvable (DNS) or blocked by policy.';
			die;
		}

		return array(false, $error);
	}
	else
	{
		curl_close($ch);
		return array(true, null);
	}
}

//--------------------------------------------------------------------------------------------------

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$w  = isset($_GET['w'])  ? (int)$_GET['w']  : 200;
$h  = isset($_GET['h'])  ? (int)$_GET['h']  : 200;

if ($id === 0)
{
	header('HTTP/1.0 400 Bad Request');
	echo 'Error 400: Missing or invalid PageID.';
	die;
}

$url   = 'https://www.biodiversitylibrary.org/pagethumb/' . $id . ',' . $w . ',' . $h;
$fname = sys_get_temp_dir() . '/' . md5('biostor-pagethumb:' . $id . ',' . $w . ',' . $h);

if (!file_exists($fname))
{
	$curl_result = download_file($url, $fname);

	if ($curl_result[0] === false)
	{
		header('HTTP/1.0 404 Not Found');
		echo 'Error 404: Could not fetch image from BHL. ' . $curl_result[1];
		die;
	}
}

$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $fname);
finfo_close($finfo);

header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2678400) . ' GMT'); // 31 days
header('Cache-Control: public, max-age=2678400'); // 31 days; 'public' allows Cloudflare to cache
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($fname));

ob_start();
readfile($fname);
ob_end_flush();

?>
