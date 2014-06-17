<?php

class ComplianceReader
{
    private $packagecount = 0;
    private $filelines = array();
    private $sites = array();
    private $packages = array();
    private $problems = array();

    private $beta = array();
    private $developtesting = array();
    private $developmentnottesting = array();
    private $developall = array();
    private $eolinuse = array();
    private $eoldelete = array();
    private $production = array();
    private $promotebeta = array();
    private $promotedevelopment = array();
	private $testing = array();
	private $testingpromote = array();
	private $testingviolationprod = array();
	
    private $site_keys = null;

    public function __construct()
    {
        $this->filelines = file( REPORT_PATH . "/compliance.txt" );
        $this->parseSitesPercentage();
        $this->parsePackageCount();
        $this->parseSitePackages();
        $this->parseTransitivePackages();
    }

    private function parsePackageCount()
    {
        if( preg_match( "/([0-9]+)/", $this->filelines[ 1 ], $match ) > 0 )
        {
            $this->packagecount = $match[ 1 ];
        }
    }

    private function parseTransitivePackages()
    {
        $began = false;

        for( $x = 0; $x < count( $this->filelines ); $x++ )
        {
            $line = trim( $this->filelines[ $x ] );

            if( $line == "" ) continue;

            if( preg_match( "/Package state problems/", $line, $match ) > 0 )
            {
                $began = true;        
            }
            else if( $began != false )
            {
				if( preg_match( "/^Beta package\s+(.*?)\s+still needs(.*)$/", $line, $care ) > 0 )
				{
					$this->beta[] = array( $care[1] , $care[2] ); 
				}
				else if( preg_match( "/^Development package\s+(.*?)\s+still needs (.*)$/", $line, $care ) > 0 )
				{
					$this->developtesting[] = array( $care[1] , $care[2] ); 
				}
				else if( preg_match( "/^No sites are using Development package (.*?)$/", $line, $care ) > 0 )
				{
					$this->developmentnottesting[] = array( $care[1] , isset( $care[2] ) ? $care[2] : "" ); 
				}
				else if( preg_match( "/^EOL package\s+(.*?)\s+is still in use by (.*)$/", $line, $care ) > 0 )
				{
					$this->eolinuse[] = array( $care[1] , $care[2] ); 
				}
				else if( preg_match( "/^No sites are using EOL package (.*)$/", $line, $care ) > 0 )
				{
					$this->eoldelete[] = array( $care[1] ); 
				}
				else if( preg_match( "/^Package (.*?) are both (.*)$/", $line, $care ) > 0 )
				{
					$this->developall[] = array( $care[1] , $care[2] ); 
				}
				else if( preg_match( "/^Production package(.*?)should(.*?)$/", $line, $care ) > 0 )
				{
					$this->production[] = array( $care[1] , $care[2] ); 
				}
				else if( preg_match( "/^Promote Beta package (.*) to Production.(.*)$/", $line, $care ) > 0 )
				{
					$this->promotebeta[] = array( $care[1] , $care[2] ); 
				}
				else if( preg_match( "/^Promote Development package (.*) to Beta. (.*)$/", $line, $care ) > 0 )
				{
					$this->promotedevelopment[] = array( $care[1] , $care[2] ); 
				}
				else if( preg_match( "/^Testing package\s+(.*?)\s+still needs (.*)$/" , $line, $care ) > 0 )
				{
					$this->testing[] = array( $care[1] , $care[2] ); 
				}
				else if( preg_match( "/^Testing package\s+(.*?)\s+deployed to production by (.*)$/" , $line, $care ) > 0 )
				{
					$this->testingviolationprod[] = array( $care[1] , $care[2] ); 
				}
				else if( preg_match( "/^Promote Testing package (.*) to Beta.(.*)$/", $line, $care ) > 0 )
				{
					$this->testingpromote[] = array( $care[1] , $care[2] ); 
				}
            }
        }
    }

    private function parseSitePackages()
    {
        $end = false;
        $began = false;
        $site = null;

        for( $x = 0; $x < count( $this->filelines ) && $end == false; $x++ )
        {
            $line = trim( $this->filelines[ $x ] );

            if( $line == "" ) continue;

            if( in_array( $line , $this->GetSites() ) )
            {
                $began = true;        
                $site = $line;
                $this->packages[ $site ] = array();
            }
            else if( $began != false )
            {
                if( preg_match( "/^(Retire|Upgrade|Deploy|EXCLUDED).*/" , $line , $dontcare ) > 0 )
                {
                    $status = preg_split( "/\s+/" , $line );
                    $this->packages[ $site ][] = array( $status[0] , $status[1] , $status[2] ); 
                }
                else
                {
                    $began = false;
                }
            }
        }
    }

    private function parseSitesPercentage()
    {
        for( $x = 4; $x < count( $this->filelines ); $x++ )
        {
            if( preg_match( "/^\s+([0-9]+)%[\s+|\s+\*+]+(.*)$/" , $this->filelines[ $x ], $match ) > 0 )
            {
                $this->sites[ $match[ 2 ] ] = $match[ 1 ];
            }
        }
    }

    public function GetSites()
    {
        if( $this->site_keys == null )
        {
            $this->site_keys = array_keys( $this->sites );
        }

        return $this->site_keys;
    }

    public function GetCompliance()
    {
        return array_values( $this->sites );
    }

    public function GetPackageCount()
    {
        return $this->packagecount;
    }

    public function GetSitePackages( $site = null )
    {
        if( $site == null )
        {
            return $this->packages;
        }

        return isset( $this->packages[ $site ] ) ? $this->packages[ $site ] : array();
    }

    public function GetSitesPackageCount( $type = null )
    {
        $arr = array();

        foreach( $this->packages as $site => $sitepkgs )
        {
            $arr[ $site ] = 0;

            foreach( $sitepkgs as $pkg )
            {
                if( $pkg[ 0 ] == $type )
                {
                    $arr[ $site ] +=  1;
                }
            }
        }

        return ( $type == null) ? array_keys( $arr ) : $arr;
    }

    public function GetBeta()
    {
        return $this->beta;
    }

    public function GetDevelopTesting()
    {
        return $this->developtesting;
    }

    public function GetDevelopNotTesting()
    {
        return $this->developmentnottesting;
    }
    
    public function GetDevelopAll()
    {
        return $this->developall;
    }

    public function GetEolInUse()
    {
        return $this->eolinuse;
    }

    public function GetEolDelete()
    {
        return $this->eoldelete;
    }

    public function GetProduction()
    {
        return $this->production;
    }

    public function GetPromoteBeta()
    {
        return $this->promotebeta;
    }

    public function GetPromoteDevelopment()
    {
        return $this->promotedevelopment;
    }
	
	public function GetTesting()
    {
        return $this->testing;
    }	
	
	public function GetPromoteTesting()
    {
        return $this->testingpromote;
    }	
	
	public function GetTestingViolations()
    {
        return $this->testingviolationprod;
    }	
}
