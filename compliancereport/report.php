<?php

require_once __DIR__ . "/report-path.php";
require_once __DIR__ . "/classes/mailer.class.php";
require_once __DIR__ . "/classes/compliance-reader.class.php";
require_once __DIR__ . "/classes/compliance-analyser.class.php";

$reportDirectory = defined("REPORT_PATH") ? REPORT_PATH : __DIR__;

if (
    class_exists("Mailer", false)
    && class_exists("ComplianceReport\\ComplianceAnalyser", false)
) {
    $ca = new ComplianceReport\ComplianceAnalyser();

    $sendit = new Mailer("Cfengine Compliance Report", $ca->getAnalysis());
    $sendit->attachFile($reportDirectory . "/compliance.txt");
    $sendit->embedPicture("IMAGE1", $reportDirectory . "/images/intel-small.jpg");
    $sendit->embedPicture("IMAGEPACKAGE", $reportDirectory . "/images/packages.png");
    $sendit->embedPicture("IMAGEPSTATES", $reportDirectory . "/images/packagestates.png");
    $sendit->embedPicture(
        "IMAGECOMPLIANCE",
        $reportDirectory . "/images/compliance.png"
    );
    $sendit->addRecipient("\"Cfengine Reports\" <cfreports@eclists.intel.com>");
    // $sendit->addRecipient("\"O Neill, David M\" <david.m.oneill@intel.com>");
    $sendit->mail();
}
