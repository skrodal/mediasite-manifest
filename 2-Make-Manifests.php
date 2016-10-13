<?php

######
#
# ~~~ Open Video to Mediasite Import - SCRIPT #2 - Make manifests ~~~
# 
# Scans all Open Video metadata files (series and presentations) and:  
# 
# - Filters out series with no metadata
# - Filters out presentations with no direct url to video file
# - Creates a Mediasite-compliant manifest file for the rest of the presentations
#
# Options (in config):
# 
# - Testtrun on/off with `writeManifestsToFile`
# - Log all series/presentations with missing data to folder `error_logs` with `logMissingData`
# 
# Requirements:
# 
# - Must have run script #1, `1-Download-Metadata.php`, first (as this downloads all metadata to local disk).
#
# @author Simon Skrødal
# @since 04.12.2015
#
######


$config = json_decode( file_get_contents('etc/config.js'), false );


# Metadata content
	// Read series from local copy (script #1 should have downloaded this)
	$seriesJSON = file_get_contents($config->seriesMetaPathToFile);
	// Decode content
	$seriesObject = json_decode($seriesJSON);
# Containers for tracking troublesome series and presentations
	$seriesWithEmptyMetadata = [];
	$presentationsWithVideoMissing = [];


# Loop all series found in the series metadata file, and for each serie
# 
# - read the corresponding metatdata file from local disk
# - catch any serie/presentation with missing data
# - create manifests for all presentations with sufficient meta
foreach ($seriesObject as $seriesKey => $serieObj) {
	// Fetch local file for this serie
	$serieObject = json_decode( file_get_contents($config->presentationsMetaPath . $serieObj->guid . '.json') );
	// A few series points to a missing metadata document, record and skip these.
	if($serieObject === null) {
		$seriesWithEmptyMetadata[] = $serieObj;
		continue;
	} 

// IS SERIE TAGGED OFFLINE?
//if($serieObj->online == 0){
//	echo "<li>" . $serieObj->title;
//}

	// Create one folder per serie (XML-files will be stored here)
	if($config->writeManifestsToFile) {
		$serieFolderPath = $config->manifestsRootPath . $serieObj->guid . '/';
		if(!is_dir($serieFolderPath)){
			mkdir($serieFolderPath);
		}
	}

	// List of all presentation URLs for this serie
	$downloadURLsForSerie = [];
	// Make an array of SERIE keyword string (we add these as Tags to every presentation in the serie)
	$serieKeywords = explode(',', $serieObj->keywords);
	// Also add the serie GUID and coursecode as tags
	array_push($serieKeywords, 'sguid-' . $serieObj->guid);

	if(!empty($serieObj->coursecode)){
		array_push($serieKeywords, 'code-' . $serieObj->coursecode);
	} //else{echo "<li>Note: Coursecode not set for serie $serieObj->guid</li>";}

	// Loop content (x number of presentations) and build each manifest file
	foreach ($serieObject as $serieKey => $presentationObj) {

		// Catch presentations that are missing the [podcastvideo] array with direct url to a media file
		if(!isset($presentationObj->podcastvideo[0])) {
			$presentationsWithVideoMissing['missing_type_'.strtolower($presentationObj->type)][] = $presentationObj;
		} else {
			// By now we have dodged series with empty metadata and presentations with no direct url to video. 
			// Time to build those manifests!

// IS PRESENTATION TAGGED OFFLINE?
//if($presentationObj->online == 0){
//	echo "<li>" . $serieObj->title;
//}

			# Build Manifest
			$xml = new SimpleXMLElement('<IntegrationManifest/>');
			$xml->addAttribute('xmlns:xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
			$xml->addAttribute('xmlns:xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
			$xml->addAttribute('Version', '2.0');

				# Presentation
			    $presentation = $xml->addChild('Presentation');
				    $presentation->Description = $presentationObj->description;
				    $presentation->RecordDateTimeUtc = str_replace(" ","T",trim($presentationObj->date));
					$presentation->Title = $presentationObj->title;
					# Tags (0 - many)
					$tags = $presentation->addChild('Tags');
					// Make array of PRESENTATION keyword string
					$keywords = explode(',', $presentationObj->keywords);
					array_push($keywords, 'pguid-' . $presentationObj->guid);
					// MERGE serie keywords with presentation keywords. Trim/make lower case and remove duplicates.
					$keywords = array_unique(array_map('trim', array_map('strtolower', array_merge($keywords, $serieKeywords))));
					// Loop presentation keywords and add as Tag nodes
					foreach ($keywords as $index => $keyword) {
						$tags->addChild('Tag')->addAttribute('Value', $keyword);
					}

					# Presenters (only one in this dataset)
					$presenters = $presentation->addChild('Presenters');
						// Single child node per presentation
						$presenter = $presenters->addChild('Presenter');
							// We don't have an email address or bio url, but Mediasite import will fail without these fields being present and not empty...
							$presenter->addChild('EmailAddress');
							$presenter->EmailAddress[0] = 'multimedie@adm.ntnu.no';
							$presenter->addChild('BioUrl');
							$presenter->BioUrl[0] = 'http://www.ntnu.no/';
							// Name(s) will be populated further down
							$presenter->addChild('FirstName');
							$presenter->addChild('MiddleName');
							$presenter->addChild('LastName');
							// Metadata provides full name, need to split and presume any name in between first/last is middle
							$presenterName = explode(' ', $presentationObj->presenter);
							// Try to populate names that exist (anything bewteen 1st and last count as middle name...)
							foreach ($presenterName as $index => $value) {
								// First name
								if($index === 0) { $presenter->FirstName[0] = $presenterName[$index]; continue; }
								// Last index in array is last name
								if($index === sizeof($presenterName)-1) { $presenter->LastName[0] = $presenterName[$index]; continue; }
								// Anything between first and last array item counts as middle name...
								$presenter->MiddleName[0] = $presenterName[$index];	
							}
							// 
							$presenter->addChild('Order', '0');
							
								// Empties
								$presenter->addChild('AdditionalInfo');
								$presenter->addChild('Prefix');
								$presenter->addChild('Suffix');
								$presenter->addChild('ImageFileName');
							
					# Stream - single file only (more means dual-video or worse...)
					$onDemandStreams = $presentation->addChild('OnDemandStreams');
						$onDemandStream = $onDemandStreams->addChild('OnDemandStream');
						$onDemandStream->addChild('StreamType', 'Video1');
						$onDemandStream->addChild('FileName');
						// Find highest res available (if any)
						$highestRes = 0;
						$videoURL = null;

						foreach ($presentationObj->podcastvideo as $index => $videoArr) {
							if($videoArr->videoRez > $highestRes) {
								// TODO: Check that URL points to a videofile!
								$highestRes = $videoArr->videoRez;
								// Get filename only - drop path
								$videoURL = $videoArr->directURL;
							}
						}
						// If we do not have a $videoURL by this point, this presentation's metadata failed to provide a URL pointing to an existing videofile
						if(is_null($videoURL)){
							echo "NO VIDEO URL FOUND FOR PRESENTATION " . $presentationObj->guid . "IN SERIE " . $serieObj->guid . PHP_EOL;
							continue;
						}

						//
						$onDemandStream->FileName[0] = basename($videoURL);
						// Add resolution as a Tag
						$tags->addChild('Tag')->addAttribute('Value', 'res-' . $highestRes);
						// Add video URL as meta info (not used in import, but useful for tests)
						$meta = $xml->addChild('Meta');
						$meta->addChild('url');
						$meta->url[0] = $videoURL;
						// Add to list to be stored to file
						$downloadURLsForSerie[] = $videoURL;

						// Create a file per serie filled with direct links to presentations (for downloading via HTTP)
						if($config->writeDownloadURLsToFile) {
							$downloadURLsPath = $config->downloadURLsPath;
							if(!is_dir($downloadURLsPath)){
								mkdir($downloadURLsPath);
							}
							echo "Writing presentation URLs for this series to " . $serieObj->guid;
							file_put_contents($downloadURLsPath . $serieObj->guid . '.json', json_encode($downloadURLsForSerie));
						}

						// Turn actual writing to file on/off in config
						if($config->writeManifestsToFile) {

							echo '<li>Writing ' . $serieFolderPath . $presentationObj->guid . '_manifest.xml' .' to file ('.$highestRes.'p).</li>';
							file_put_contents($serieFolderPath . $presentationObj->guid . '_manifest.xml', $xml->asXML());
						} else {

							// Output a single XML sample and exit in first iteration of loop
							Header('Content-type: text/xml');
							$xml->addAttribute('COMMENT', 'Sample output below: Manifest output to file has been diabled. Edit writeManifestsToFile in config.js to turn on.');
							//
							print($xml->asXML());
							exit();

						}
		} // end if/else
		
	} // end loop in serie

} // end series loop

// Log troublesome series/presentations to file?
if($config->logMissingData) {
	file_put_contents($config->errorLogsPath . 'seriesWithEmptyMetadata.json', json_encode($seriesWithEmptyMetadata));
	file_put_contents($config->errorLogsPath . 'presWithVideoMissing.json', json_encode($presentationsWithVideoMissing));

	echo '<br><br><hr><br><br>';
	echo '<li>Log of series with missing metadata written to ' . $config->errorLogsPath . 'seriesWithEmptyMetadata.json' . '</li>';
	echo '<li>Log of presentations with missing URL to video file written to ' . $config->errorLogsPath . 'presWithVideoMissing.json' . '</li>';
	echo '<br><br><hr><br><br>';
}
