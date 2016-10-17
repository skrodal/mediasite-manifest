# Mediasite Content Importer

Note: These scripts were developed for a very specific purpose/system and most likely not reusable for anything else.

## Workflow

Migrates content from a specific, proprietary, video system to Mediasite. 

The 4 scripts should be run in order:

1. Download metadata from originating system
2. Make Mediasite-compliant XML manifestfiles (1 file per video) from metadata 
3. Optional - download videos in Manifestfiles via HTTP
4. Make ZIPs: one videofile and one Manifestfile per presentation

Each zip can then be consumed by Mediasite import (e.g. placed in a watchfolder).

## Requirements

- PHP7 (required to make use of zero-compression flag in ZipArchive, which *significantly* speeds up zipping 1000s of import packages)
- PHP ZipArchive
- PHP SimpleXMLElement(for creating XML manifestfiles)
- PHP cURL (for downloading media via HTTP)

Simon Skrødal, 2016
