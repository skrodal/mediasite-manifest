<?php

######
#
# ~~~ NTNU Open Video to Mediasite Import - SCRIPT #2 - Make manifests ~~~
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
foreach ($seriesObject as $key => $serieObj) {
	// Fetch local file for this serie
	$serieObject = json_decode( file_get_contents($config->presentationsMetaPath . $serieObj->guid . '.json') );
	// A few series points to a missing metadata document, record and skip these.
	if($serieObject === null) {
		$seriesWithEmptyMetadata[] = $serieObj;
		continue;
	} 

	// Loop content (x number of presentations) and build each manifest file
	foreach ($serieObject as $key => $presentationObj) {
		// Catch presentations that are missing the [podcastvideo] array with direct url to a media file
		if(!isset($presentationObj->podcastvideo[0])) {
			$presentationsWithVideoMissing['missing_type_'.strtolower($presentationObj->type)][] = $presentationObj;
		} else {
			// By now we have dodged series with empty metadata and presentations with no direct url to video. 
			// Time to build those manifests!

			# Build Manifest
			$xml = new SimpleXMLElement('<IntegrationManifest/>');
			$xml->addAttribute('xmlns:xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
			$xml->addAttribute('xmlns:xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
			$xml->addAttribute('Version', '2.0');

				# Presentation
			    $presentation = $xml->addChild('Presentation');
				    $presentation->Description = $presentationObj->description;
				    $presentation->RecordDateTimeUtc = $presentationObj->date;
					$presentation->Title = $presentationObj->title;
					# Tags (0 - many)
					$tags = $presentation->addChild('Tags');
					// Make array of keyword string
					$keywords = explode(',', $presentationObj->keywords);
					// Loop and add Tag nodes
					foreach ($keywords as $index => $keyword) {
						$tags->addChild('Tag')->addAttribute('Value', trim($keyword));
					}

					# Presenters (only one in this dataset)
					$presenters = $presentation->addChild('Presenters');
						// Single child node per presentation
						$presenter = $presenters->addChild('Presenter');
							// Got nothing to add to these
							$presenter->addChild('AdditionalInfo');
							$presenter->addChild('BioUrl');
							$presenter->addChild('EmailAddress');
							// Name(s) will be populated further down
							$presenter->addChild('FirstName');
							$presenter->addChild('MiddleName');
							$presenter->addChild('LastName');
							// Metadata provides full name, need to split (pure guessing)
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
							// Nothing to add
							$presenter->addChild('Prefix');
							$presenter->addChild('Suffix');
							$presenter->addChild('ImageFileName');

					# Stream - single file only (more means dual-video or worse...)
					$onDemandStreams = $presentation->addChild('OnDemandStreams');
						$onDemandStream = $onDemandStreams->addChild('onDemandStream');
						$onDemandStream->addChild('StreamType', 'Video1');
						$onDemandStream->addChild('FileName');
						// Find highest res available (if any)
						$highestRes = 0;
						$videoURL = null;

						foreach ($presentationObj->podcastvideo as $index => $videoArr) {
							if($videoArr->videoRez > $highestRes) {
								$highestRes = $videoArr->videoRez;
								$videoURL = $videoArr->directURL;
							}
						}
						$onDemandStream->FileName[0] = $videoURL;

						// Turn actual writing to file on/off in config
						if($config->writeManifestsToFile) {
							echo '<li>Writing ' . $config->manifestsRootPath . $presentationObj->guid . 'Manifest.xml' .' to file.</li>';
							file_put_contents($config->manifestsRootPath . $presentationObj->guid . 'Manifest.xml', $xml->asXML());
						} else {
							// Output a single XML sample and exit
							Header('Content-type: text/xml');
							$xml->addAttribute('COMMENT', 'Sample output below: Manifest output to file has been diabled. Edit writeManifestsToFile in config.js to turn on.');
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
