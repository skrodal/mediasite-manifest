<?php

######
#
# ~~~ Open Video to Mediasite Import - SCRIPT #4 - ZIP each manifest file with corresponding video file ~~~
# 
# Scans every XML-file in each and every series folder in $config->manifestsRootPath, and:  
# 
# - Find corresponding videofile in <FileName> (split path from filename)
# - Move file to XML-folder
# - ZIP videofile with XML file
#
# Options (in config):
# 
# -
# 
# Requirements:
# 
# - Must have run script #1, `1-Download-Metadata.php`, first (as this downloads all metadata to local disk).
# - Must have run script #2, `2-Make-Manifests.php`, first (as this creates manifest files (XML) from series metadata to local disk).
# - Optionally script #3 (if files are to be fetched from remote via HTTP)
#
# @author Simon Skrødal
# @since 06.06.2016
#
######



$config = json_decode( file_get_contents('etc/config.js'), false );

if(!$config->writeZipFilesToFile){
	exit("EXIT: Script is disabled in config." . PHP_EOL);
}

run($config);


function run ($config) {

	// If publish dir is not created yet, do it now
	if(!is_dir($config->zipPublishRootPath)){ 
		if(!mkdir($config->zipPublishRootPath)){
			exit( "EXIT! Failed to create folder " . $config->zipPublishRootPath . "!- missing access rights?" . PHP_EOL );
		} 
	}
	// Make sure source videos are found
	if(!is_dir($config->videoSourceFolderPath)){
		exit("EXIT! Source video folder (".$config->videoSourceFolderPath.") not found!" . PHP_EOL);
	}
	// Loop each and every series folder
	foreach (new DirectoryIterator($config->manifestsRootPath) as $serieDir) {
		// Ignore
	    if($serieDir->isDot()) continue;
	    // If indeed a folder, look at files inside
	    if($serieDir->isDir()){
	    	// Folder name is this serie's GUID
	    	$serieGUID = $serieDir->getFilename();
	    	// Loop all XMLs in current folder
	    	foreach (new DirectoryIterator($config->manifestsRootPath . $serieGUID) as $metaFile) {
	    		// Double check for file and extension
				if (!$metaFile->isDot() && $metaFile->isFile() && $metaFile->getExtension() === 'xml') {
					// Path to a single presentation's manifest file
					$manifestFilePath = $config->manifestsRootPath . $serieGUID . '/' . $metaFile;
					$manifestFileName = $metaFile->getFilename();
					// Load the manifest xml 
					$xmlFile = simplexml_load_file($manifestFilePath);
					// Grab video filename from manifest xml (the one with the highest res, as added by Script#2)
					$videoFileName = $xmlFile->Presentation->OnDemandStreams->OnDemandStream->FileName;
					// Path to the videofile defined in the manifest
					$videoFilePath= $config->videoSourceFolderPath . $videoFileName;
					// Check if a file with this name exists in the source folder for all video files
					if(is_file( $videoFilePath )) {
						// Path to the folder where the exported zip should reside (serieGUID...)
						$serieExportFolderPath = $config->zipPublishRootPath . $serieGUID . '/';
						// Only if WRITE is enabled in config...
						if($config->writeZipFilesToFile && !is_dir($serieExportFolderPath)){ 
							if(!mkdir($serieExportFolderPath)){
								exit("EXIT! Failed to create folder $serieExportFolderPath! Missing access rights?" . PHP_EOL);
							} 
						}

						$zipPublishFile = $serieExportFolderPath . $videoFileName . '.zip';
						// Make sure ZIP is not already created in a previous run
						if(is_file($zipPublishFile)){
							echo "INFO: Found an already zipped file for video $videoFileName. Skipping.";
							continue;
						}
						// ZIP the two files
						if(!create_zip(array($manifestFilePath, $videoFilePath), $zipPublishFile)){
							exit("EXIT! Could not make zip $zipPublishFile" . PHP_EOL);
						} else {
							echo "DONE zipping manifest to $zipPublishFile" . PHP_EOL;
						}
					} else {
						// TODO: LOG TO EXPORT ERROR FILE
						echo "ERROR: File $videoFilePath was not found!" .PHP_EOL;
					}
				}	    		
	    	}
	    }
	    
	}
}




/**
 * Creates ZIP consumable by Mediasite import. Contains two files:
 *
 * 1. MediasiteIntegrationManifest.xml with metadata
 * 2. Video file with same filename as found in <FileName></FileName> of the Manifest file
 *
 * NOTE! The function uses ZIP options available only to PHP 7!
 */
function create_zip($files = array(),$destination,$overwrite = false) {
	// Store paths to be zipped here
	$valid_files = [];
	// Checked passed in paths (should be only two: one manifest and one video)
	if(is_array($files)) {
		foreach($files as $file) {
			// ...and make sure the file exists
			if(file_exists($file)) {
				$valid_files[] = $file;
			}
		}
	}
	// Were both paths found on disk?
	if(sizeof($valid_files) == 2) {
		// Create the archive
		$zip = new ZipArchive();	
		if(!$zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE)) {
			return false;
		}
		# REQUIRES PHP 7: http://php.net/manual/en/ziparchive.setcompressionindex.php
		# Poorly documented, but seems like CM_STORE is zero compression, which is what we want (for speed) (have tested and seems to work)
		# Uncomment the next two lines if PHP7 is not available!
		$zip->setCompressionIndex(0, ZipArchive::CM_STORE);
		$zip->setCompressionIndex(1, ZipArchive::CM_STORE);

		// Add the files to the zip
		foreach($valid_files as $file) {
			// The XML file needs renaming to work in Mediasite import:
			$path_parts = pathinfo($file);
			if(strcasecmp($path_parts['extension'], 'xml') == 0){
				// Second parameter is new filename inside zip
				$zip->addFile($file,'MediasiteIntegrationManifest.xml');
			} else {
				// For video, keep same filename
				$zip->addFile($file,basename($file));
			}
		}
		echo "Done zipping $destination :: the archive contains $zip->numFiles files with status: " . $zip->getStatusString() . PHP_EOL;

		//close the zip -- done!
		$zip->close();
		
		//check to make sure the file exists
		return file_exists($destination);
	}
	else
	{
		return false;
	}
}
