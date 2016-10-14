<?php

######
#
# ~~~ Open Video to Mediasite Import - SCRIPT #1 - Metadata download ~~~
# 
# Downloads all Open Video metadata (series and presentations) to disk. 
# 
# Takes some time, but when complete, we have a local working copy of each file.
#
# Run this file ONCE to download all metadata. You can edit config `downloadAndSaveMetadata` 
# to avoid accidentally running it again.
#
# Following this script's download of all metadata, run script #2, `2-Make-Manifests.php` to generate 
# manifest files and zip these with video.
#
# @author Simon Skrødal
# @since 04.12.2015
#
######

$config = json_decode( file_get_contents('etc/config.js'), false );

// Set in config
if($config->downloadAndSaveMetadata) {
	# Metadata content
		// Read series from remote location
		$seriesJSON = file_get_contents($config->seriesURL);
		// Store the file as JSON for later use (script 2: make manifests)
		file_put_contents($config->seriesMetaPathToFile, $seriesJSON);
		// Decode content
		$seriesObject = json_decode($seriesJSON);

	# Loop all series found in the series metadata file, and for each serie
	# 
	# - download the metadata for the serie
	# - store serie (presentations) metadata content to file (name == {guid}.json)
	foreach ($seriesObject as $key => $serie) {
		// Fetch remote file
		$presentationsFile = file_get_contents($config->singleSerieURL . 'guid='.$serie->guid.'&format=json');
		// Store this serie's presentations metadata to file
		file_put_contents($config->presentationsMetaPath . $serie->guid.'.json', $presentationsFile);
	}
} else {
	echo "Metadata download has been diabled. Edit 'downloadAndStoreMetadata' in config to turn on." . PHP_EOL;
}
