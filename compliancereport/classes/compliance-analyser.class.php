<?php

require_once( REPORT_PATH . "/pchart/class/pData.class.php" ); 
require_once( REPORT_PATH . "/pchart/class/pDraw.class.php" ); 
require_once( REPORT_PATH . "/pchart/class/pImage.class.php" ); 
require_once( REPORT_PATH . "/pchart/class/pRadar.class.php" );

class ComplianceAnalyser
{
	private $cpi = null;

	public function __construct()
	{
		$this->cpi = new ComplianceReader();
		
		$this->GenerateSiteCompliance();
		$this->GenerateSitePackages();
		$this->GeneratePackageStates();
	}
	
	public function GetAnalysis()
	{
		$today = date("D M j G:i:s T Y");  

		$sitereport = "<h2>Site Overview</h2><table style='border:1px solid #0F5493;width:1040px'>";

		$cols = 3;
		$i = $cols;

		foreach( $this->cpi->GetSites() as $site )
		{   
			if( $i % $cols == 0 )
			{
				$trc = ( $i % ($cols * 2 ) == 0 ) ? "tron" : "troff";
				$sitereport .= "<tr class='$trc'>";
			}

			$packages = $this->cpi->GetSitePackages( $site );

			$sitereport .= "<td><table>\n";
			$sitereport .= "<tr>\n";
			$sitereport .= "<td colspan='4'><h3>$site</h3></td>\n";
			$sitereport .= "</tr>\n";

			if( count( $packages ) > 0 )
			{
				$t = 1;
				foreach( $packages as $package )
				{
					$c = "<b class='" . $package[0] . "'>";
					$ce = "</b>";

					$sitereport .= "<tr><td>$t</td><td>$c" . $package[0] . "$ce</td><td>$c" . $package[1] . "$ce</td><td>$c" . $package[2] . "$ce</td></tr>\n";
					$t++;
				}
			}

			$sitereport .= "</table></td>\n";

			if( $i % $cols == $cols - 1 )
			{
				$sitereport .= "</tr>";
			}

			$i++;
		}

		if( $i % $cols > 0 && $i % $cols != $cols - 1 )
		{
			$sitereport .= "<td colspan='" . ( $i % $cols ) . "'></td></tr>";
		}

		$sitereport .= "</table>";


		//beta
		$pkgreport = "<h2>Still in Beta</h2><table class='pkgt'>";
		$i = 1;
		foreach( $this->cpi->GetBeta() as $pkg )
		{
			$trc = ( $i % 2 == 0 ) ? "tron" : "troff";
			$pkgreport .= "<tr class='$trc'><td class='pkgn'>$i</td><td class='pkgs'>Beta package </td><td class='pkgp'><b class='Upgrade'>" . $pkg[0] . "</b></td><td class='pkgm'> still needs </td><td> " . $pkg[1] . "</td></tr>";
			$i++;
		}
		$pkgreport .= "</table>";


		//development testing
		$pkgreport .= "<h2>Still in Development Testing</h2><table class='pkgt'>";
		$i = 1;
		foreach( $this->cpi->GetDevelopTesting() as $pkg )
		{
			$trc = ( $i % 2 == 0 ) ? "tron" : "troff";
			$pkgreport .= "<tr class='$trc'><td class='pkgn'>$i</td><td class='pkgs'>Development package </td><td class='pkgp'><b class='Upgrade'>" . $pkg[0] . "</b></td><td class='pkgm'> still needs </td><td> " . $pkg[1] . "</td></tr>";
			$i++;
		}
		$pkgreport .= "</table>";


		//development not testing
		$pkgreport .= "<h2>Still in Development</h2><table class='pkgt'>";
		$i = 1;
		foreach( $this->cpi->GetDevelopNotTesting() as $pkg )
		{
			$trc = ( $i % 2 == 0 ) ? "tron" : "troff";
			$pkgreport .= "<tr class='$trc'><td class='pkgn'>$i</td><td class='pkgs'>No sites are using Development package  </td><td class='pkgp'><b class='Upgrade'>" . $pkg[0] . "</b></td><td></td></tr>";
			$i++;
		}
		$pkgreport .= "</table>";


		//development all
		$pkgreport .= "<h2>All Versions in Development</h2><table class='pkgt'>";
		$i = 1;
		foreach( $this->cpi->GetDevelopAll() as $pkg )
		{
			$trc = ( $i % 2 == 0 ) ? "tron" : "troff";
			$pkgreport .= "<tr class='$trc'><td class='pkgn'>$i</td><td class='pkgs'>Package  </td><td class='pkgp'><b class='Upgrade'>" . $pkg[0] . "</b></td><td> are both Development. Fix one of them. </td><td></td></tr>";
			$i++;
		}
		$pkgreport .= "</table>";


		// eol in use testing
		$pkgreport .= "<h2>Active EOL Packages</h2><table class='pkgt'>";
		$i = 1;
		foreach( $this->cpi->GetEolInUse() as $pkg )
		{
			$trc = ( $i % 2 == 0 ) ? "tron" : "troff";
			$pkgreport .= "<tr class='$trc'><td class='pkgn'>$i</td><td class='pkgs'>EOL package </td><td class='pkgp'><b class='Upgrade'>" . $pkg[0] . "</b></td><td class='pkgm'> is still in use by </td><td> " . $pkg[1] . "</td></tr>";
			$i++;
		}
		$pkgreport .= "</table>";


		// eol delete
		$pkgreport .= "<h2>Delete EOL Packages</h2><table class='pkgt'>";
		$i = 1;
		foreach( $this->cpi->GetEolDelete() as $pkg )
		{
			$trc = ( $i % 2 == 0 ) ? "tron" : "troff";
			$pkgreport .= "<tr class='$trc'><td class='pkgn'>$i</td><td class='pkgs'>No sites are using EOL package </td><td class='pkgp'><b class='Upgrade'>" . $pkg[0] . "</b></td><td> </td><td></td></tr>";
			$i++;
		}
		$pkgreport .= "</table>";


		// production sitll in testing
		$pkgreport .= "<h2>Should be Development</h2><table class='pkgt'>";
		$i = 1;
		foreach( $this->cpi->GetProduction() as $pkg )
		{
			$trc = ( $i % 2 == 0 ) ? "tron" : "troff";
			$pkgreport .= "<tr class='$trc'><td class='pkgn'>$i</td><td class='pkgs'>Production package</td><td class='pkgp'><b class='Upgrade'>" . $pkg[0] . "</b></td><td class='pkgm'></td><td> should " . $pkg[1] . "</td></tr>";
			$i++;
		}
		$pkgreport .= "</table>";


		// promote beta
		$pkgreport .= "<h2>Promote To Development</h2><table class='pkgt'>";
		$i = 1;
		foreach( $this->cpi->GetPromoteBeta() as $pkg )
		{
			$trc = ( $i % 2 == 0 ) ? "tron" : "troff";
			$pkgreport .= "<tr class='$trc'><td class='pkgn'>$i</td><td class='pkgs'>Promote Beta package </td><td class='pkgp'><b class='Upgrade'>" . $pkg[0] . "</b></td><td class='pkgm'> to Production.</td><td> " . $pkg[1] . "</td></tr>";
			$i++;
		}
		$pkgreport .= "</table>";
		
		$betacount = count( $this->cpi->GetPromoteBeta() );


		// promote Development
		$pkgreport .= "<h2>Promote To Beta</h2><table class='pkgt'>";
		$i = 1;
		foreach( $this->cpi->GetPromoteDevelopment() as $pkg )
		{
			$trc = ( $i % 2 == 0 ) ? "tron" : "troff";
			$pkgreport .= "<tr class='$trc'><td class='pkgn'>$i</td><td class='pkgs'>Promote Development package </td><td class='pkgp'><b class='Upgrade'>" . $pkg[0] . "</b></td><td class='pkgm'> to Beta.</td><td> " . $pkg[1] . "</td></tr>";
			$i++;
		}
		$pkgreport .= "</table>";
		
		$devcount = count( $this->cpi->GetPromoteDevelopment() );
		
		
		// Testing
		$pkgreport .= "<h2>Testing</h2><table class='pkgt'>";
		$i = 1;
		foreach( $this->cpi->GetTesting() as $pkg )
		{
			$trc = ( $i % 2 == 0 ) ? "tron" : "troff";
			$pkgreport .= "<tr class='$trc'><td class='pkgn'>$i</td><td class='pkgs'>Testing package </td><td class='pkgp'><b class='Upgrade'>" . $pkg[0] . "</b></td><td class='pkgm'> stills needs </td><td> " . $pkg[1] . "</td></tr>";
			$i++;
		}
		$pkgreport .= "</table>";
		
		$testingcount = count( $this->cpi->GetTesting() );		
		
		
		// Promote Testing
		$pkgreport .= "<h2>Promote Testing</h2><table class='pkgt'>";
		$i = 1;
		foreach( $this->cpi->GetPromoteTesting() as $pkg )
		{
			$trc = ( $i % 2 == 0 ) ? "tron" : "troff";
			$pkgreport .= "<tr class='$trc'><td class='pkgn'>$i</td><td class='pkgs'>Testing package </td><td class='pkgp'><b class='Upgrade'>" . $pkg[0] . "</b></td><td class='pkgm'> to Beta </td><td> " . $pkg[1] . "</td></tr>";
			$i++;
		}
		$pkgreport .= "</table>";
		
		$promotetestingcount = count( $this->cpi->GetPromoteTesting() );	


		// Testing Promotion violations
		$pkgreport .= "<h2>Testing Production Promotion Violations</h2><table class='pkgt'>";
		$i = 1;
		foreach( $this->cpi->GetTestingViolations() as $pkg )
		{
			$trc = ( $i % 2 == 0 ) ? "tron" : "troff";
			$pkgreport .= "<tr class='$trc'><td class='pkgn'>$i</td><td class='pkgs'>Testing package </td><td class='pkgp'><b class='Upgrade'>" . $pkg[0] . "</b></td><td class='pkgm'> deployed to production by </td><td> " . $pkg[1] . "</td></tr>";
			$i++;
		}
		$pkgreport .= "</table>";
		
		$violationtestingcount = count( $this->cpi->GetTestingViolations() );	


		$pkgreport = "<h1>Package Overview</h1>" . $pkgreport;


		$body = "
		<!DOCTYPE html>
		<html>
		<head>
		<title>Compliance report</title>
		<style type=\"text/css\">
		body
		{
		margin:5px;
		padding:5px;
		font-family: 'Neo Sans Intel';
		}
		body,table,td,pre
		{ 
		font-family: 'Neo Sans Intel';
		font-size: 12px;
		color: #544E4F;
		} 
		td
		{
		font-family: 'Neo Sans Intel';
		text-align:left;
		vertical-align:top;
		}
		.pkgt
		{
			width:1040px;
			border:1px solid #0F5493;
		}
		.pkgn
		{
			width:30px
		}
		.pkgs
		{
			width:180px;
		}
		.pkgp
		{
			width:250px;
		}
		.pkgm
		{
			width:120px;
		}
		.Deploy
		{
		color: #5C7E8D;
		font-weight:normal;
		}
		.EXCLUDED
		{
		color: #551A8B;
		font-weight:normal;
		}
		.Upgrade
		{
		color: #14335E;
		font-weight:normal;
		}
		.Retire
		{
		color: #E7AE3D;
		font-weight:normal;
		}
		.tron
		{
		background-color:#eeeeee;
		}
		.troff
		{
		background-color:#ffffff;
		}
		h1
		{
		font-family: 'Neo Sans Intel';
		font-size:24pt;
		text-align:left;
		color: #0F5493;
		}
		h2
		{ 
		font-family: 'Neo Sans Intel';
		color: #0F5493;
		font-size:18pt
		}
		h3
		{ 
		font-family: 'Neo Sans Intel';
		color: #0F5493;
		font-size:14pt
		} 	
		</style>
		</head>
		<body>
			<table style='padding:10px; width:1060px'>
				<tr>
				<td style='width:160px'><img src='IMAGE1' alt='intel-logo' style='width:160px'/></td>
				<td style='width:900px'>
					<div style='text-align:left; margin-top:30px;margin-left:30px;margin-bottom:10px'>
						<h1 style='margin-top:0px;margin-bottom:5px'>Cfengine Compliance Report</h1>
						<h3 style='margin-top:5px;margin-left:20px;margin-bottom:5px'>Promotions this week: Testing <b> $testingcount</b>, Beta <b>$betacount</b>, Development <b>$devcount</b> </h3>
						<div style='margin-top:0px;margin-left:35px;font-size:11pt'>$today</div>
					</div>
				</td>
				</tr>
				<tr>
				<td colspan='2'> 
					<table>
						<tr>
							<td>
							<img src='IMAGEPACKAGE' alt='intel-logo' width='500'/>
							</td>
							<td>						
							<img src='IMAGECOMPLIANCE' alt='intel-logo' width='500'/><br>
							<img src='IMAGEPSTATES' alt='intel-logo' width='500'/><br>
							</td>
						</tr>
						<tr>
							<td colspan='2'>
							$sitereport
							$pkgreport
							</td>
						</tr>
					</table>
				</td>
				</tr>
			</table>
		</body>
		</html>
		";
		
		return $body;
	}
	
	private function GeneratePackageStates()
	{
		$points = array(
			count( $this->cpi->GetBeta() ),
			count( $this->cpi->GetDevelopTesting() ),
			count( $this->cpi->GetDevelopNotTesting() ),
			count( $this->cpi->GetDevelopAll() ),
			count( $this->cpi->GetEolInUse() ),
			count( $this->cpi->GetEolDelete() ),
			count( $this->cpi->GetProduction() ),
			count( $this->cpi->GetPromoteBeta() ),
			count( $this->cpi->GetPromoteDevelopment() ),
			count( $this->cpi->GetTesting() ),
			count( $this->cpi->GetPromoteTesting() ),
			count( $this->cpi->GetTestingViolations() )
		);

		$pointnames = array(
			"In Beta Testing",
			"In Development Testing",
			"Development Not In Testing",
			"All Versions In Development",
			"EOL In Use",
			"EOL To Be Deleted",
			"Production Not Tested",
			"Promote To Production",
			"Promote To Development",
			"Testing",
			"Promote Testing To Beta",
			"Production Testing Violations"
		);
		
		$width = 500;
		$height = 430;

		$MyData = new pData();   
		$MyData->loadPalette( REPORT_PATH . "/pchart/palettes/blind.color", TRUE ); 
		$MyData->addPoints( $points , "Packages" ); 
		$MyData->addPoints( $pointnames ,"Status" ); 
		$MyData->setAbscissa( "Status" ); 

		$myPicture = new pImage( $width, $height, $MyData, TRUE );
		
		$settings = array(  
			"FontName"=>REPORT_PATH . "/pchart/fonts/verdana.ttf", 
			"FontSize"=>7, 
			"R"=>110, 
			"G"=>110, 
			"B"=>110
		);

		$myPicture->setFontProperties( $settings ); 

		$myPicture->drawText( $width / 2, 10, "Transitioning Packages", array( "FontSize"=>20, "Align"=>TEXT_ALIGN_TOPMIDDLE, "R"=>15, "G"=>84, "B"=>147 ) );

		$SplitChart = new pRadar(); 

		$myPicture->setGraphArea( 100, 60, $width - 140, $height - 140 ); 
		
		$settings = array(   
			"DrawPoly"=>TRUE,
			"WriteValues"=>TRUE,
			"ValueFontSize"=>8,
			"LabelPos"=>RADAR_LABELS_HORIZONTAL,
			"Layout"=>RADAR_LAYOUT_CIRCLE,
			"BackgroundGradient"=>array(    
				"StartR"=>255,
				"StartG"=>255,
				"StartB"=>255,
				"StartAlpha"=>100,
				"EndR"=>20,
				"EndG"=>51,
				"EndB"=>94,
				"EndAlpha"=>10
			)
		);

		$SplitChart->drawRadar( $myPicture, $MyData, $settings ); 

		$myPicture->autoOutput( REPORT_PATH . "/images/packagestates.png" );
	}
	
	private function GenerateSitePackages()
	{
		$width = 500;
		$height = 1520;

		$MyData = new pData();   
		$MyData->loadPalette( REPORT_PATH . "/pchart/palettes/blind.color", TRUE ); 
		$MyData->addPoints( $this->cpi->GetSitesPackageCount( "EXCLUDED" ), "Excluded" ); 
		$MyData->addPoints( $this->cpi->GetSitesPackageCount( "Deploy" ), "Deploy" ); 
		$MyData->addPoints( $this->cpi->GetSitesPackageCount( "Upgrade" ), "Upgrade" );
		$MyData->addPoints( $this->cpi->GetSitesPackageCount( "Retire" ), "Retire" );
		$MyData->addPoints( $this->cpi->GetSitesPackageCount() ,"Site" ); 
		$MyData->setAbscissa( "Site" ); 
		$MyData->setAbscissaName( "Site" );
		$MyData->setAxisName( 0, "Packages" );
		$MyData->setAxisDisplay( 0, AXIS_FORMAT_METRIC, 1 ); 

		$myPicture = new pImage( $width, $height, $MyData ); 
		
		$settings = array(  
			"StartR"=>240,
			"StartG"=>240,
			"StartB"=>240,
			"EndR"=>180,
			"EndG"=>180,
			"EndB"=>180,
			"Alpha"=>20
		);

		$myPicture->drawGradientArea( 150, 100, $width - 30, $height - 50, DIRECTION_HORIZONTAL, $settings );
		
		$settings = array(  
			"FontName"=>REPORT_PATH . "/pchart/fonts/verdana.ttf", 
			"FontSize"=>8, 
			"R"=>110, 
			"G"=>110, 
			"B"=>110
		);

		$myPicture->setFontProperties( $settings ); 

		$myPicture->drawText( $width / 2, 10, "Package Deployment Status", array( "FontSize"=>20, "Align"=>TEXT_ALIGN_TOPMIDDLE, "R"=>15, "G"=>84, "B"=>147 ) );

		$myPicture->setGraphArea( 150, 90, $width - 30 , $height - 50 ); 

		$AxisBoundaries = array(    
			0=>array(   
				"Min"=>0, 
				"Max"=>$this->cpi->GetPackageCount() 
			)
		); 
		
		$settings = array(  
			"InnerTickWidth"=>0,
			"OuterTickWidth"=>10,
			"Mode"=>SCALE_MODE_MANUAL,
			"ManualScale"=>$AxisBoundaries,
			"LabelRotation"=>30,
			"DrawXLines"=>False,
			"GridR"=>0,
			"GridG"=>0,
			"GridB"=>0,
			"GridTicks"=>0,
			"GridAlpha"=>30,
			"AxisAlpha"=>30,
			"Pos"=>SCALE_POS_TOPBOTTOM
		);

		$myPicture->drawScale( $settings ); 

		$settings = array( 
			"X"=>2,
			"Y"=>2,
			"R"=>0,
			"G"=>0,
			"B"=>0,
			"Alpha"=>10 
		);
		
		$myPicture->setShadow( TRUE, $settings );
		$myPicture->drawLegend( $width - 100, 120 );
		
		$settings = array( 
			"Floating0Serie"=>"Floating 0",
			"Surrounding"=>20,
			"DisplayValues"=>1, 
			"Gradient"=>1, 
			"AroundZero"=>1
		);	

		$myPicture->drawBarChart( $settings ); 
		$myPicture->autoOutput( REPORT_PATH . "/images/packages.png" ); 
	}
	
	private function GenerateSiteCompliance()
	{
		$width = 500;
		$height = 850;

		$MyData = new pData();   
		$MyData->loadPalette( REPORT_PATH . "/pchart/palettes/blind.color", TRUE ); 
		$MyData->addPoints( $this->cpi->GetCompliance() , "Percent" ); 
		$MyData->addPoints( $this->cpi->GetSites() , "Site" ); 
		$MyData->setAbscissa( "Site");
		$MyData->setAbscissaName( "Site");
		$MyData->setAxisName( 0, "Percent" ); 
		$MyData->setAxisDisplay( 0, AXIS_FORMAT_METRIC, 1 ); 

		$myPicture = new pImage( $width , $height, $MyData );

		$settings = array(  
			"StartR"=>240,
			"StartG"=>240,
			"StartB"=>240,
			"EndR"=>180,
			"EndG"=>180,
			"EndB"=>180,
			"Alpha"=>20
		);

		$myPicture->drawGradientArea( 150, 100, $width - 30, $height - 50, DIRECTION_HORIZONTAL, $settings );
		
		$settings = array(  
			"FontName"=>REPORT_PATH . "/pchart/fonts/verdana.ttf", 
			"FontSize"=>8, 
			"R"=>110, 
			"G"=>110, 
			"B"=>110 
		);

		$myPicture->setFontProperties( $settings ); 

		$myPicture->drawText( $width / 2, 20, "Production Compliance By Site", array( "FontSize"=>20, "Align"=>TEXT_ALIGN_TOPMIDDLE, "R"=>15, "G"=>84, "B"=>147 ) );

		$myPicture->setGraphArea( 150, 100, $width - 30, $height - 50 ); 

		$AxisBoundaries = array(  
			0=>array( "Min"=>0,	"Max"=>100 ), 
			1=>array( "Min"=>0,	"Max"=>$this->cpi->GetPackageCount()
		));
		
		$settings = array(    
			"InnerTickWidth"=>0,
			"OuterTickWidth"=>10,
			"Mode"=>SCALE_MODE_MANUAL,
			"ManualScale"=>$AxisBoundaries,
			"LabelRotation"=>30,
			"DrawXLines"=>False,
			"GridR"=>0,
			"GridG"=>0,
			"GridB"=>0,
			"GridTicks"=>0,
			"GridAlpha"=>30,
			"AxisAlpha"=>30,
			"Pos"=>SCALE_POS_TOPBOTTOM 
		); 

		$myPicture->drawScale( $settings ); 
		
		$settings = array(    
			"X"=>1,
			"Y"=>1,
			"R"=>0,
			"G"=>0,
			"B"=>0,
			"Alpha"=>40
		); 

		$myPicture->setShadow( TRUE, $settings ); 
		
		$settings = array(    
			"Floating0Serie"=>"Floating 0",
			"Surrounding"=>20, 
			"DisplayValues"=>1, 
			"Gradient"=>1, 
			"AroundZero"=>1
		); 

		$myPicture->drawBarChart( $settings ); 

		$myPicture->autoOutput( REPORT_PATH . "/images/compliance.png" ); 
	}
	
}
