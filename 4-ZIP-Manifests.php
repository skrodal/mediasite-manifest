<!DOCTYPE html>
<html>
<head>
	<title></title>
	<meta charset="UTF-8">
	<style type="text/css">
		.gray { color: gray; }
		.red {color: red;}
		.blue {color: blue;}
		.green {color: green;}
	</style>
</head>
<body>

<h1>Starter zipping, dette vil ta litt (lang) tid....</h1>

<p>TODO</p>

<p>Vurder å kjøre batch på f.eks. 20 i gangen. Når disse 20 er ferdige, refresh, første 20 vil da skippes (finnes allerede) og de neste vil ta over...</p>


</body>
</html>



<?php

run();

######
#
# ~~~ Open Video to Mediasite Import - SCRIPT #3 - ZIP each manifest file with corresponding video file ~~~
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
#
# @author Simon Skrødal
# @since 06.06.2016
#
######



/* TODO: 


	- Need a config entry that points to the path where videos are stored.
	- A nested loop for
		- Each serie folder
		- Each XML-file in this folder
	- ...that will scan for filename (which will need to be stripped from its path)
	- Search for same filename in videopath
	- Move video to current (or new working) folder
	- Zip XML and videofile
	- Next...

*/

function run () {
	$config = json_decode( file_get_contents('etc/config.js'), false );

	echo "<h1>Resultat: </h1>";

	// If publish dir is not created yet, do it now
	if($config->writeZipFilesToFile && !is_dir($config->zipPublishRootPath)){ 
		if(!mkdir($config->zipPublishRootPath)){
			echo "<li class='red'><strong>Failed to create folder " . $config->zipPublishRootPath . "!</strong> - missing access rights?";
			exit();
		} 
		echo "<li class='blue'>Created folder " . $config->zipPublishRootPath;	
	}

	// Make sure source videos are found
	if(!is_dir($config->videoSourceFolderPath)){
		echo "<li><strong>Source video folder (".$config->videoSourceFolderPath.") not found!";
		exit();
	}

	// Loop each and every series folder
	foreach (new DirectoryIterator($config->manifestsRootPath) as $serieDir) {
		// Ignore
	    if($serieDir->isDot()) continue;
	    // If indeed a folder, look at files inside
	    if($serieDir->isDir()){
	    	// Folder name is this serie's GUID
	    	$serieGUID = $serieDir->getFilename();
	    	// Folder name
	    	echo "<h3 class='gray'>Serie: <code>$serieGUID</code></h3>";	
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
					// 
					echo "<li class='gray'>Loaded <code>$manifestFileName</code> :: videofile <code>$videoFileName</code> ";	
					// Check if a file with this name exists in the source folder for all video files
					if(is_file( $videoFilePath )) {
						echo "<span class='green'>(OK) </span>";
						// Path to the folder where the exported zip should reside (serieGUID...)
						$serieExportFolderPath = $config->zipPublishRootPath . $serieGUID . '/';

						// Only if WRITE is enabled in config...
						if($config->writeZipFilesToFile && !is_dir($serieExportFolderPath)){ 
							if(!mkdir($serieExportFolderPath)){
								echo "<li class='red'>Failed to create folder <code>$serieExportFolderPath</code>! Missing access rights?";
								exit();
							} 
							echo "<h3 class='green'>Created folder <code>$serieExportFolderPath</code></h3>";
						}

						/*
						// COPY Manifest file to export folder
						if (!copy($manifestFilePath, $serieExportFolderPath . $manifestFileName )) {
							echo "<h1>Failed to copy" . $manifestFilePath . "!</h1>";
							exit();
						}

						// COPY Video file to export folder
						if (!copy($videoFilePath, $serieExportFolderPath . $videoFileName )) {
							echo "<h1>Failed to copy" . $videoFilePath . "!</h1>";
							exit();
						}
						*/

						$zipPublishFile = $serieExportFolderPath . $videoFileName . '.zip';
						// Make sure ZIP is not already created in a previous run
						if(is_file($zipPublishFile)){
							echo "<span class='blue'>already zipped</span>";
							continue;
						}
						// ZIP the two files
						echo "<ul><li class='blue'>START zipping manifest to $zipPublishFile!</li></ul>";
						if(!create_zip(array($manifestFilePath, $videoFilePath), $zipPublishFile)){
							echo "<ul><li class='red'>Error! Could not create zip file!</li></ul>";
							exit();
						} else {
							echo "<ul><li class='green'>DONE zipping manifest to <code>$zipPublishFile</code></li></ul>";
						}


					} else {
						// TODO: LOG TO EXPORT ERROR FILE
						echo "<span class='red'>(NOT FOUND)</span>";
					}


				}	    		
	    	}
	    }
	    
	}


/*
	// Loop SERIES folders containing manifest xmls...
	$manifestRootDir = new RecursiveDirectoryIterator($config->manifestsRootPath);
	foreach (new RecursiveIteratorIterator($manifestRootDir) as $filename => $file) {
		// 
		$serieFolder = dirname($filename); 

    if (!$file->isDot()) {
        var_dump($file->getFilename());
    }




//$path = "/home/httpd/html/index.php";
//$file = basename($path);         // $file is set to "index.php"
//$file = basename($path, ".php"); // $file is set to "index"


		//echo '<li>' . $serieFolder . ' ---> ' . basename($filename);
		//echo $filename . ' - ' . $file;
	    //echo $filename . ' - ' . $file->getSize() . ' bytes <br/>';
	}


*/

}




/* creates a compressed zip file */
function create_zip($files = array(),$destination = '',$overwrite = false) {
	//if the zip file already exists and overwrite is false, return false
	if(file_exists($destination) && !$overwrite) { return false; }
	//vars
	$valid_files = array();
	//if files were passed in...
	if(is_array($files)) {
		//cycle through each file
		foreach($files as $file) {
			//make sure the file exists
			if(file_exists($file)) {
				$valid_files[] = $file;
			}
		}
	}
	//if we have good files...
	if(count($valid_files)) {
		//create the archive
		$zip = new ZipArchive();
		if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
			return false;
		}
		//add the files
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
		//debug
		echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

		# REQUIRES PHP 7: http://php.net/manual/en/ziparchive.setcompressionindex.php
		# Poorly documented, but seems like CM_STORE is zero compression, which is what we want (for speed)
		#$zip->setCompressionIndex(0, ZipArchive::CM_STORE);
		#$zip->setCompressionIndex(1, ZipArchive::CM_STORE);


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




