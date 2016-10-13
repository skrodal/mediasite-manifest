<?php 


######
#
# ~~~ Open Video to Mediasite Import - SCRIPT #3 - Download videos via HTTP ~~~
# 
# Scans all Manifest files and downloads each file in turn.
#
# Options (in config):
# 
# - Testtrun on/off with `downloadFilesViaHTTP`
# - Path to files with links in `downloadURLsPath`
# - Limit number of downloads with `downloadFilesLimit` (false == no limit)
# 
# Requirements:
# 
# - Must have run script #2, `2-Make-Manifests.php`, first.
#
# @author Simon Skrødal
# @since 13.10.2016
#
######


$config = json_decode( file_get_contents('etc/config.js'), false );

if(!$config->downloadFilesViaHTTP){
	exit("EXIT: Download is disabled in config" . PHP_EOL);
}
// Check that we have a folder with files with links
if (count(glob($config->downloadURLsPath ."*")) === 0 ) {
	exit("EXIT: Found no files with URLs in folder " . $config->downloadURLsPath . PHP_EOL);
}
//
$videoSourceFolderPath = $config->videoSourceFolderPath;
if(!($videoSourceFolderPath)){
	exit("EXIT: Video source path is not set in config" . PHP_EOL);
}
//
if(!is_dir($videoSourceFolderPath)){
	mkdir($videoSourceFolderPath);
}

// If false, we download everything (could be TBs of data!!)
$numFilesToDownload = $config->downloadFilesLimit;
// To keep track
$filesDownloaded = 0;
//
$fileSeriesList = glob($config->downloadURLsPath . '*.json');

echo "Number of files: " . sizeof($fileSeriesList) . PHP_EOL;

foreach ($fileSeriesList as $fileName) {
	echo "Reading URLs for series " . $fileName . PHP_EOL;
	$seriesFile = json_decode( file_get_contents($fileName), false );
	foreach ($seriesFile as $key => $presentationURL) {
		if($numFilesToDownload !== false && $filesDownloaded >= $numFilesToDownload){
			exit("EXIT: Done downloading " . $filesDownloaded . " presentation files" . PHP_EOL);
		}
		echo "START download from URL " . $presentationURL . PHP_EOL;
		if(!downloadFromURL($presentationURL, $videoSourceFolderPath)){
			exit("EXIT: Failed to download file " . $presentationURL . PHP_EOL);
		};
		echo "DONE download from URL " . $presentationURL . PHP_EOL;
		$filesDownloaded++;
	}
}

exit("EXIT: Done downloading " . $filesDownloaded . " presentation files" . PHP_EOL);



function downloadFromURL($url, $localPath){
	set_time_limit(0);
	// Full path to media file on disk
	$fp = fopen ($localPath . basename($url), 'w+');
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 50);
	// Write downloaded media to file
	curl_setopt($ch, CURLOPT_FILE, $fp); 
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	// Returns TRUE on success or FALSE on failure
	$response = curl_exec($ch); 
	curl_close($ch);
	fclose($fp);
	return $response;
}

