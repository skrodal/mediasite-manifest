<?php

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

$config = json_decode( file_get_contents('etc/config.js'), false );

