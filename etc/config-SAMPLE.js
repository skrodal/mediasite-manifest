{
    "seriesURL"                 :   "....&type=serier&format=json",
	"comment"					: 	"URL to the main metadata file for all Open Video series.",

    "singleSerieURL"   	       	:   ".....type=presentasjoner&",
    "comment"					: 	"Base URL to get metadata for a single serie. Neet to append `&guid={serieGUID}&format=json for each serie to be fetched.",

    "seriesMetaPathToFile"      :   "metadata_source/series_main/series.json",
    "comment"					: 	"Script #1 will save meta returned from `seriesURL` to this file. Script #2 will read from here.",

    "presentationsMetaPath"    	:   "metadata_source/series_split/",
    "comment"					: 	"Script #1 will save metadata returned from each and every `singleSerieURL` as single files to this folder. Script #2 will read from these.",

    "manifestsRootPath"       	:   "manifests/",
	"comment"					: 	"Script #2 will write manifest files to this folder.",    

    "errorLogsPath"				: 	"error_logs/",
	"comment"					: 	"If {logMissingData} is enabled, Script #2 will write logfiles for a) empty serie metadata files and b) presentations with missing url to videofile.",    

	"logMissingData"			: 	false, 
	"comment"					: 	"Script #2 - enable/disable logging",

    "writeManifestsToFile"		: 	false, 
    "comment"					: 	"Script #2 - enable/disable generating/writing manifests",

    "downloadAndSaveMetadata"	: 	false,
	"comment"					: 	"Script #1 - enable/disable download/storing of metadata files"
}