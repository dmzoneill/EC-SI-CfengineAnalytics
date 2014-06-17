<?php

define( "REPORT_PATH" , "/nfs/site/home/twerwin/EC-SI-CfengineAnalytics/compliancereport" );

require_once( REPORT_PATH . "/classes/mailer.class.php" );
require_once( REPORT_PATH . "/classes/compliance-reader.class.php" );
require_once( REPORT_PATH . "/classes/compliance-analyser.class.php" );

$ca = new ComplianceAnalyser();

$sendit = new Mailer( "Cfengine Compliance Report" , $ca->GetAnalysis() );
$sendit->attachFile( REPORT_PATH . "/compliance.txt" );
$sendit->embedPicture( "IMAGE1" , REPORT_PATH . "/images/intel-small.jpg" );
$sendit->embedPicture( "IMAGEPACKAGE" , REPORT_PATH . "/images/packages.png" );
$sendit->embedPicture( "IMAGEPSTATES" , REPORT_PATH . "/images/packagestates.png" );
$sendit->embedPicture( "IMAGECOMPLIANCE" , REPORT_PATH . "/images/compliance.png" );
$sendit->addRecipient( "\"Cfengine Reports\" <cfreports@eclists.intel.com>" );
#$sendit->addRecipient( "\"O Neill, David M\" <david.m.oneill@intel.com>" );
$sendit->mail();

