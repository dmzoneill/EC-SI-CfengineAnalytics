<?php

include( "cfengine.class.php" );

$cf = new Cfengine( array( "~/Cfengine/GER" , "~/Cfengine/GLOBAL" ) );
$cf->PrintNoStatusPackages();
$cf->PrintEolPackages();
$cf->PrintBetaPackages();
$cf->PrintDevelopmentPackages();
$cf->PrintLegacyProductionPackages();
$cf->PrintProductionPackages();

