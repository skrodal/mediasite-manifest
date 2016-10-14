{
    "seriesURL"                 :   "....&type=serier&format=json",
    "comment"                   :   "URL to the main metadata file for all Open Video series.",

    "singleSerieURL"            :   ".....type=presentasjoner&",
    "comment"                   :   "Base URL to get metadata for a single serie. Neet to append `&guid={serieGUID}&format=json for each serie to be fetched.",

    "seriesMetaPathToFile"      :   "metadata_source/series_main/series.json",
    "comment"                   :   "Script #1 will save meta returned from `seriesURL` to this file. Script #2 will read from here.",

    "presentationsMetaPath"     :   "metadata_source/series_split/",
    "comment"                   :   "Script #1 will save metadata returned from each and every `singleSerieURL` as single files to this folder. Script #2 will read from these.",

    "downloadAndSaveMetadata"   :   false,
    "comment"                   :   "Script #1 - enable/disable download/storing of metadata files",

    "manifestsRootPath"         :   "manifests/",
    "comment"                   :   "Script #2 will write manifest folders/files to this folder.",    

    "errorLogsPath"             :   "error_logs/",
    "comment"                   :   "If {logMissingData} is enabled, Script #2 will write logfiles for a) empty serie metadata files and b) presentations with missing url to videofile.",    

    "logMissingData"            :   false, 
    "comment"                   :   "Script #2 - enable/disable logging",

    "writeManifestsToFile"      :   false, 
    "comment"                   :   "Script #2 - enable/disable generating/writing manifests",

    "writeDownloadURLsToFile"   :   true,
    "comment"                   :   "Script #2 - write direct URLs for each presentations (useful for script #3)",

    "downloadURLsPath"          :   "metadata_source/series_presentation_urls/",
    "comment"                   :   "Script #2 - path to store download URLs - one file per serie",

    "downloadFilesViaHTTP"      :   false, 
    "downloadFilesLimit"        :   2,
    "comment"                   :   "Script #3 - enable/disable downloading of videos from URLs (found in manifests). Set filelimit to false to download all.",

    "writeZipFilesToFile"       :   false, 
    "comment"                   :   "Script #4 - enable/disable zipping of video+manifest",

    "videoSourceFolderPath"     :   "sourcevideos/", 
    "comment"                   :   "This is the folder where all video files are stored. Script #3 will save to here, Script #4 will search in here for all presentation videos to be zipped.", 

    "zipPublishRootPath"        :   "openvideoimport/", 
    "comment"                   :   "Resulting zipped files will be stored here by Script#4."

}