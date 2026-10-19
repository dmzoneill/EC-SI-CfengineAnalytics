<?php

namespace ComplianceReport;

function reportRoot(): string
{
    if (defined('REPORT_PATH')) {
        return REPORT_PATH;
    }

    return dirname(__DIR__);
}

function pchartConstant(string $name, int $fallback): int
{
    if (defined($name)) {
        return constant($name);
    }

    return $fallback;
}

function fallbackObject(): object
{
    return new /**
     * @method array GetSites()
     * @method array GetSitePackages(mixed $site)
     * @method array GetBeta()
     * @method array GetDevelopTesting()
     * @method array GetDevelopNotTesting()
     * @method array GetDevelopAll()
     * @method array GetEolInUse()
     * @method array GetEolDelete()
     * @method array GetProduction()
     * @method array GetPromoteBeta()
     * @method array GetPromoteDevelopment()
     * @method array GetTesting()
     * @method array GetPromoteTesting()
     * @method array GetTestingViolations()
     * @method array GetSitesPackageCount(mixed $status = null)
     * @method array GetCompliance()
     * @method int GetPackageCount()
     * @method mixed loadPalette(mixed $path, mixed $overwrite)
     * @method mixed addPoints(mixed $points, mixed $name)
     * @method mixed setAbscissa(mixed $name)
     * @method mixed setAbscissaName(mixed $name)
     * @method mixed setAxisName(mixed $axis, mixed $name)
     * @method mixed setAxisDisplay(mixed $axis, mixed $format, mixed $mode)
     * @method mixed drawGradientArea(mixed $x1, mixed $y1, mixed $x2, mixed $y2, mixed $direction, mixed $settings)
     * @method mixed setFontProperties(mixed $settings)
     * @method mixed drawText(mixed $x, mixed $y, mixed $text, mixed $settings)
     * @method mixed setGraphArea(mixed $x1, mixed $y1, mixed $x2, mixed $y2)
     * @method mixed drawScale(mixed $settings)
     * @method mixed setShadow(mixed $enabled, mixed $settings)
     * @method mixed drawLegend(mixed $x, mixed $y)
     * @method mixed drawBarChart(mixed $settings)
     * @method mixed autoOutput(mixed $path)
     * @method mixed drawRadar(mixed $picture, mixed $data, mixed $settings)
     */
    class {
        /** @var mixed */
        private $sink;

        /**
         * @param mixed $name
         * @param array<int, mixed> $arguments
         * @return mixed
         */
        public function __call($name, $arguments)
        {
            $this->sink = array($name, $arguments);

            return $this->sink;
        }
    };
}

function pchartStub(): object
{
    return fallbackObject();
}

function createPData(): object
{
    if (class_exists('pData', false)) {
        return new \pData();
    }

    return pchartStub();
}

function createPImage(int $width, int $height, object $data, ?bool $transparent = null): object
{
    if (class_exists('pImage', false)) {
        $image = (new \ReflectionClass(\pImage::class))->newInstanceWithoutConstructor();
        if ($transparent === null) {
            $image->pImage($width, $height, $data);
        } else {
            $image->pImage($width, $height, $data, $transparent);
        }

        return $image;
    }

    return pchartStub();
}

function createPRadar(): object
{
    if (class_exists('pRadar', false)) {
        return new \pRadar();
    }

    return pchartStub();
}

function createReader(): object
{
    if (class_exists('ComplianceReader', false)) {
        return new \ComplianceReader();
    }

    return fallbackObject();
}

function renderSiteBarChart(
    object $picture,
    int $width,
    int $height,
    string $title,
    int $titleY,
    int $graphTop,
    array $axisBoundaries,
    int $shadowX,
    int $shadowY,
    int $shadowAlpha,
    bool $drawLegend,
    string $outputPath
): void {
    $settings = array(
        "StartR" => 240,
        "StartG" => 240,
        "StartB" => 240,
        "EndR" => 180,
        "EndG" => 180,
        "EndB" => 180,
        "Alpha" => 20
    );
    $picture->drawGradientArea(
        150,
        100,
        $width - 30,
        $height - 50,
        pchartConstant('DIRECTION_HORIZONTAL', 690002),
        $settings
    );

    $settings = array(
        "FontName" => reportRoot() . "/pchart/fonts/verdana.ttf",
        "FontSize" => 8,
        "R" => 110,
        "G" => 110,
        "B" => 110
    );
    $picture->setFontProperties($settings);
    $picture->drawText(
        $width / 2,
        $titleY,
        $title,
        array(
            "FontSize" => 20,
            "Align" => pchartConstant('TEXT_ALIGN_TOPMIDDLE', 690402),
            "R" => 15,
            "G" => 84,
            "B" => 147
        )
    );
    $picture->setGraphArea(150, $graphTop, $width - 30, $height - 50);

    $settings = array(
        "InnerTickWidth" => 0,
        "OuterTickWidth" => 10,
        "Mode" => pchartConstant('SCALE_MODE_MANUAL', 690205),
        "ManualScale" => $axisBoundaries,
        "LabelRotation" => 30,
        "DrawXLines" => false,
        "GridR" => 0,
        "GridG" => 0,
        "GridB" => 0,
        "GridTicks" => 0,
        "GridAlpha" => 30,
        "AxisAlpha" => 30,
        "Pos" => pchartConstant('SCALE_POS_TOPBOTTOM', 690102)
    );
    $picture->drawScale($settings);

    $settings = array(
        "X" => $shadowX,
        "Y" => $shadowY,
        "R" => 0,
        "G" => 0,
        "B" => 0,
        "Alpha" => $shadowAlpha
    );
    $picture->setShadow(true, $settings);
    if ($drawLegend) {
        $picture->drawLegend($width - 100, 120);
    }

    $settings = array(
        "Floating0Serie" => "Floating 0",
        "Surrounding" => 20,
        "DisplayValues" => 1,
        "Gradient" => 1,
        "AroundZero" => 1
    );
    $picture->drawBarChart($settings);
    $picture->autoOutput($outputPath);
}

class ComplianceAnalyser
{
    /** @var object */
    private $cpi;

    public function __construct()
    {
        require_once __DIR__ . "/../pchart/class/pData.class.php";
        require_once __DIR__ . "/../pchart/class/pDraw.class.php";
        require_once __DIR__ . "/../pchart/class/pImage.class.php";
        require_once __DIR__ . "/../pchart/class/pRadar.class.php";
        require_once __DIR__ . "/compliance-reader.class.php";

        $this->cpi = createReader();

        $this->generateSiteCompliance();
        $this->generateSitePackages();
        $this->generatePackageStates();
    }

    public function getAnalysis(): string
    {
        $today = date("D M j G:i:s T Y");

        $sitereport = "<h2>Site Overview</h2><table style='border:1px solid #0F5493;width:1040px'>";

        $cols = 3;
        $i = $cols;

        foreach ($this->cpi->GetSites() as $site) {
            if ($i % $cols == 0) {
                $trc = ( $i % ($cols * 2 ) == 0 ) ? "tron" : "troff";
                $sitereport .= "<tr class='$trc'>";
            }

            $packages = $this->cpi->GetSitePackages($site);

            $sitereport .= "<td><table>\n";
            $sitereport .= "<tr>\n";
            $sitereport .= "<td colspan='4'><h3>$site</h3></td>\n";
            $sitereport .= "</tr>\n";

            if (count($packages) > 0) {
                $t = 1;
                foreach ($packages as $package) {
                    $c = "<b class='" . $package[0] . "'>";
                    $ce = "</b>";

                    $sitereport .= "<tr><td>$t</td><td>$c" . $package[0]
                        . "$ce</td><td>$c" . $package[1]
                        . "$ce</td><td>$c" . $package[2] . "$ce</td></tr>\n";
                    $t++;
                }
            }

            $sitereport .= "</table></td>\n";

            if ($i % $cols == $cols - 1) {
                $sitereport .= "</tr>";
            }

            $i++;
        }

        if ($i % $cols > 0 && $i % $cols != $cols - 1) {
            $sitereport .= "<td colspan='" . ( $i % $cols ) . "'></td></tr>";
        }

        $sitereport .= "</table>";


        //beta
        $pkgreport = "<h2>Still in Beta</h2><table class='pkgt'>";
        $i = 1;
        foreach ($this->cpi->GetBeta() as $pkg) {
            $pkgreport .= $this->packageTableRow(
                $i,
                "Beta package ",
                $pkg[0],
                "<td class='pkgm'> still needs </td><td> " . $pkg[1] . "</td></tr>"
            );
            $i++;
        }
        $pkgreport .= "</table>";


        //development testing
        $pkgreport .= "<h2>Still in Development Testing</h2><table class='pkgt'>";
        $i = 1;
        foreach ($this->cpi->GetDevelopTesting() as $pkg) {
            $pkgreport .= $this->packageTableRow(
                $i,
                "Development package ",
                $pkg[0],
                "<td class='pkgm'> still needs </td><td> " . $pkg[1] . "</td></tr>"
            );
            $i++;
        }
        $pkgreport .= "</table>";


        //development not testing
        $pkgreport .= "<h2>Still in Development</h2><table class='pkgt'>";
        $i = 1;
        foreach ($this->cpi->GetDevelopNotTesting() as $pkg) {
            $pkgreport .= $this->packageTableRow(
                $i,
                "No sites are using Development package  ",
                $pkg[0],
                "<td></td></tr>"
            );
            $i++;
        }
        $pkgreport .= "</table>";


        //development all
        $pkgreport .= "<h2>All Versions in Development</h2><table class='pkgt'>";
        $i = 1;
        foreach ($this->cpi->GetDevelopAll() as $pkg) {
            $pkgreport .= $this->packageTableRow(
                $i,
                "Package  ",
                $pkg[0],
                "<td> are both Development. Fix one of them. </td><td></td></tr>"
            );
            $i++;
        }
        $pkgreport .= "</table>";


        // eol in use testing
        $pkgreport .= "<h2>Active EOL Packages</h2><table class='pkgt'>";
        $i = 1;
        foreach ($this->cpi->GetEolInUse() as $pkg) {
            $pkgreport .= $this->packageTableRow(
                $i,
                "EOL package ",
                $pkg[0],
                "<td class='pkgm'> is still in use by </td><td> " . $pkg[1] . "</td></tr>"
            );
            $i++;
        }
        $pkgreport .= "</table>";


        // eol delete
        $pkgreport .= "<h2>Delete EOL Packages</h2><table class='pkgt'>";
        $i = 1;
        foreach ($this->cpi->GetEolDelete() as $pkg) {
            $pkgreport .= $this->packageTableRow(
                $i,
                "No sites are using EOL package ",
                $pkg[0],
                "<td> </td><td></td></tr>"
            );
            $i++;
        }
        $pkgreport .= "</table>";


        // production still in testing
        $pkgreport .= "<h2>Should be Development</h2><table class='pkgt'>";
        $i = 1;
        foreach ($this->cpi->GetProduction() as $pkg) {
            $pkgreport .= $this->packageTableRow(
                $i,
                "Production package",
                $pkg[0],
                "<td class='pkgm'></td><td> should " . $pkg[1] . "</td></tr>"
            );
            $i++;
        }
        $pkgreport .= "</table>";


        // promote beta
        $pkgreport .= "<h2>Promote To Development</h2><table class='pkgt'>";
        $i = 1;
        foreach ($this->cpi->GetPromoteBeta() as $pkg) {
            $pkgreport .= $this->packageTableRow(
                $i,
                "Promote Beta package ",
                $pkg[0],
                "<td class='pkgm'> to Production.</td><td> " . $pkg[1] . "</td></tr>"
            );
            $i++;
        }
        $pkgreport .= "</table>";

        $betacount = count($this->cpi->GetPromoteBeta());


        // promote Development
        $pkgreport .= "<h2>Promote To Beta</h2><table class='pkgt'>";
        $i = 1;
        foreach ($this->cpi->GetPromoteDevelopment() as $pkg) {
            $pkgreport .= $this->packageTableRow(
                $i,
                "Promote Development package ",
                $pkg[0],
                "<td class='pkgm'> to Beta.</td><td> " . $pkg[1] . "</td></tr>"
            );
            $i++;
        }
        $pkgreport .= "</table>";

        $devcount = count($this->cpi->GetPromoteDevelopment());


        // Testing
        $pkgreport .= "<h2>Testing</h2><table class='pkgt'>";
        $i = 1;
        foreach ($this->cpi->GetTesting() as $pkg) {
            $pkgreport .= $this->packageTableRow(
                $i,
                "Testing package ",
                $pkg[0],
                "<td class='pkgm'> stills needs </td><td> " . $pkg[1] . "</td></tr>"
            );
            $i++;
        }
        $pkgreport .= "</table>";

        $testingcount = count($this->cpi->GetTesting());


        // Promote Testing
        $pkgreport .= "<h2>Promote Testing</h2><table class='pkgt'>";
        $i = 1;
        foreach ($this->cpi->GetPromoteTesting() as $pkg) {
            $pkgreport .= $this->packageTableRow(
                $i,
                "Testing package ",
                $pkg[0],
                "<td class='pkgm'> to Beta </td><td> " . $pkg[1] . "</td></tr>"
            );
            $i++;
        }
        $pkgreport .= "</table>";

        // Testing Promotion violations
        $pkgreport .= "<h2>Testing Production Promotion Violations</h2><table class='pkgt'>";
        $i = 1;
        foreach ($this->cpi->GetTestingViolations() as $pkg) {
            $pkgreport .= $this->packageTableRow(
                $i,
                "Testing package ",
                $pkg[0],
                "<td class='pkgm'> deployed to production by </td><td> " . $pkg[1] . "</td></tr>"
            );
            $i++;
        }
        $pkgreport .= "</table>";

        $pkgreport = "<h1>Package Overview</h1>" . $pkgreport;

        $promoHeading = "<h3 style='margin-top:5px;margin-left:20px;margin-bottom:5px'>"
            . "Promotions this week: Testing <b> $testingcount</b>, "
            . "Beta <b>$betacount</b>, Development <b>$devcount</b> </h3>";

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
						$promoHeading
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

    private function generatePackageStates(): void
    {
        $points = array(
            count($this->cpi->GetBeta()),
            count($this->cpi->GetDevelopTesting()),
            count($this->cpi->GetDevelopNotTesting()),
            count($this->cpi->GetDevelopAll()),
            count($this->cpi->GetEolInUse()),
            count($this->cpi->GetEolDelete()),
            count($this->cpi->GetProduction()),
            count($this->cpi->GetPromoteBeta()),
            count($this->cpi->GetPromoteDevelopment()),
            count($this->cpi->GetTesting()),
            count($this->cpi->GetPromoteTesting()),
            count($this->cpi->GetTestingViolations())
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

        $data = createPData();
        $data->loadPalette(reportRoot() . "/pchart/palettes/blind.color", true);
        $data->addPoints($points, "Packages");
        $data->addPoints($pointnames, "Status");
        $data->setAbscissa("Status");

        $picture = createPImage($width, $height, $data, true);

        $settings = array(
            "FontName" => reportRoot() . "/pchart/fonts/verdana.ttf",
            "FontSize" => 7,
            "R" => 110,
            "G" => 110,
            "B" => 110
        );

        $picture->setFontProperties($settings);

        $picture->drawText(
            $width / 2,
            10,
            "Transitioning Packages",
            array(
                "FontSize" => 20,
                "Align" => pchartConstant('TEXT_ALIGN_TOPMIDDLE', 690402),
                "R" => 15,
                "G" => 84,
                "B" => 147
            )
        );

        $splitChart = createPRadar();

        $picture->setGraphArea(100, 60, $width - 140, $height - 140);

        $settings = array(
            "DrawPoly" => true,
            "WriteValues" => true,
            "ValueFontSize" => 8,
            "LabelPos" => pchartConstant('RADAR_LABELS_HORIZONTAL', 690022),
            "Layout" => pchartConstant('RADAR_LAYOUT_CIRCLE', 690012),
            "BackgroundGradient" => array(
                "StartR" => 255,
                "StartG" => 255,
                "StartB" => 255,
                "StartAlpha" => 100,
                "EndR" => 20,
                "EndG" => 51,
                "EndB" => 94,
                "EndAlpha" => 10
            )
        );

        $splitChart->drawRadar($picture, $data, $settings);

        $picture->autoOutput(reportRoot() . "/images/packagestates.png");
    }

    private function generateSitePackages(): void
    {
        $width = 500;
        $height = 1520;
        $data = createPData();
        $data->loadPalette(reportRoot() . "/pchart/palettes/blind.color", true);
        $data->addPoints($this->cpi->GetSitesPackageCount("EXCLUDED"), "Excluded");
        $data->addPoints($this->cpi->GetSitesPackageCount("Deploy"), "Deploy");
        $data->addPoints($this->cpi->GetSitesPackageCount("Upgrade"), "Upgrade");
        $data->addPoints($this->cpi->GetSitesPackageCount("Retire"), "Retire");
        $data->addPoints($this->cpi->GetSitesPackageCount(), "Site");
        $data->setAbscissa("Site");
        $data->setAbscissaName("Site");
        $data->setAxisName(0, "Packages");
        $data->setAxisDisplay(0, pchartConstant('AXIS_FORMAT_METRIC', 680004), 1);

        $picture = createPImage($width, $height, $data);
        renderSiteBarChart(
            $picture,
            $width,
            $height,
            "Package Deployment Status",
            10,
            90,
            array(
                0 => array(
                    "Min" => 0,
                    "Max" => $this->cpi->GetPackageCount()
                )
            ),
            2,
            2,
            10,
            true,
            reportRoot() . "/images/packages.png"
        );
    }

    private function generateSiteCompliance(): void
    {
        $width = 500;
        $height = 850;
        $data = createPData();
        $data->loadPalette(reportRoot() . "/pchart/palettes/blind.color", true);
        $data->addPoints($this->cpi->GetCompliance(), "Percent");
        $data->addPoints($this->cpi->GetSites(), "Site");
        $data->setAbscissa("Site");
        $data->setAbscissaName("Site");
        $data->setAxisName(0, "Percent");
        $data->setAxisDisplay(0, pchartConstant('AXIS_FORMAT_METRIC', 680004), 1);

        $picture = createPImage($width, $height, $data);
        renderSiteBarChart(
            $picture,
            $width,
            $height,
            "Production Compliance By Site",
            20,
            100,
            array(
                0 => array("Min" => 0, "Max" => 100),
                1 => array("Min" => 0, "Max" => $this->cpi->GetPackageCount())
            ),
            1,
            1,
            40,
            false,
            reportRoot() . "/images/compliance.png"
        );
    }

    private function packageTableRow(int $index, string $label, string $name, string $suffix): string
    {
        $trc = ( $index % 2 == 0 ) ? "tron" : "troff";

        return "<tr class='$trc'><td class='pkgn'>$index</td>"
            . "<td class='pkgs'>$label</td>"
            . "<td class='pkgp'><b class='Upgrade'>" . $name . "</b></td>"
            . $suffix;
    }
}
