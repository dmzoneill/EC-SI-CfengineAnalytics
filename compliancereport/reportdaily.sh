#!/bin/bash

TRACKINGDIR=/srv/cfengine/global/doc/htmldocs/tracking
RECIPIENTS="cfreports@eclists.intel.com"

$TRACKINGDIR/global-compliance < $TRACKINGDIR/pkgstatus.csv > /nfs/site/home/twerwin/EC-SI-CfengineAnalytics/compliancereport/compliance.txt  
/usr/bin/php /nfs/site/home/twerwin/EC-SI-CfengineAnalytics/compliancereport/report.php 

