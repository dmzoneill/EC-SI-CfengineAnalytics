<?php

include( "cfengine.class.php" );

$cf = new Cfengine( array( "~/Cfengine/GER" , "~/Cfengine/GLOBAL" ) );
$cf->PrintUnusedPackages();

