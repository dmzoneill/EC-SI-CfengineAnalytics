<?php

class Cfengine
{
    private $pkgs = array();
    private $conf = array();
    private $globalrepodir = ".";
    private $regionrepodir = ".";

    public function __construct( $args = array() )
    {
        $this->regionrepodir = isset( $args[ 0 ] ) ? $args[ 0 ] : ".";
        $this->globalrepodir = isset( $args[ 1 ] ) ? $args[ 1 ] : ".";
        $this->ReadRepo( $this->regionrepodir );
        $this->AnalyseConf();
    }

    private function RGlob( $pattern='*', $flags = 0, $path='' )
    {
        $paths = glob( $path . '*', GLOB_MARK|GLOB_ONLYDIR|GLOB_NOSORT );
        $files = glob( $path . $pattern, $flags );

        foreach( $paths as $path ) 
        { 
            $files = array_merge( $files, $this->RGlob( $pattern, $flags, $path ) ); 
        }
        
        return $files;
    }

    private function VersionNotRepo( $versions )
    {
        $found = true;

        foreach( $versions as $version )
        {
            if( preg_match( "/repo/" , $version , $nill ) == 0 )
            {
                $found = false;
                break;
            }
        }

        return $found;
    }

    private function IsWorkingCopy( $servertype = 1 )
    {
        $checkdir = ( $servertype == 1 ) ? $this->regionrepodir : $this->globalrepodir;
        
        if( !file_exists( "$checkdir/.svn" ) )
        {
            print "$checkdir is not working copy\n";
            exit;
        }
    }

    public function ReadRepo( $path = "." )
    {
        $this->IsWorkingCopy( 1 );

        $this->regionrepodir = $path;

        if( file_exists( $this->regionrepodir . "/manifest.conf" ) && file_exists( $this->regionrepodir . "/manifest3.conf" ) )
        {
            $conf2 = file( $this->regionrepodir . "/manifest.conf" );
            $conf3 = file( $this->regionrepodir . "/manifest3.conf" );
            $this->conf = array_merge( $conf2 , $conf3 );
        }
        else
        {
            print "Unable to read manifest.conf or manifest3.conf files from " . $this->regionrepodir . "\n";
            exit;
        }
    }

    private function AnalyseConf()
    {
        $this->pkgs = array();

        foreach( $this->conf as $line )
        {
            $line = trim( $line );
    
            if( $line == "" )
                continue;

            if( substr( $line , 0 , 1 ) == "#" )
                continue;

            if( substr( $line , 0 , 1 ) == "[" )
                continue;

            if( substr( $line , 0 , 10 ) == "PRECEDENCE" )
                continue;

            if( substr( $line , 0 , 7 ) == "INCLUDE" )
                continue;

            if( stristr( $line , "/" ) )
            {
                $line = explode( "/" , $line );
                $line = $line[ 1 ];
            } 

            $line = explode( "=" , $line );
            $pkg = $line[ 0 ]; 
            $ver = $line[ 1 ];

            if( !array_key_exists( $pkg , $this->pkgs ) )
            {
                $this->pkgs[ $pkg ] = array( $ver );
            }
            else
            {
                if( !in_array( $ver , $this->pkgs[ $pkg ] ) )
                {
                    $this->pkgs[ $pkg ][] = $ver;
                }
            }
        }

        ksort( $this->pkgs );
    }

    public function PrintUnusedPackages()
    {
        $pkgdirs = $this->RGlob( '*' , GLOB_ONLYDIR , $this->regionrepodir . "/packages/" );
        sort( $pkgdirs );

        foreach( $pkgdirs as $pkgdir )
        {
            $parts = explode( "/" , $pkgdir );
            $pkg = $parts[ 3 ];
            $ver = $parts[ 4 ];

            if( $ver == "" )
            {
                continue;
            }

            if( !array_key_exists( $pkg , $this->pkgs ) )
            {
                print "Unused pkg: $pkg\n";
            }
            else
            {
                if( !in_array( $ver , $this->pkgs[ $pkg ] ) && $this->VersionNotRepo( $this->pkgs[ $pkg ] ) )
                {
                    print "Unused pkg ver: $pkg $ver \n";
                }
            }
        }
    }

    private function PackageInUse( $pkg , $ver )
    {
        if( in_array( $pkg , $this->pkgs ) )
        {
            if( in_array( $ver , $this->pkgs[ $pkg ] ) )
            {
                return true;
            }
        }

        return false;
    }

    private function PrintPackages( $type , $used )
    {
        $this->IsWorkingCopy( 2 );

        foreach( $this->pkgs as $pkg => $verarr )
        {
            foreach( $verarr as $ver )
            {
                $status = null;
                $inuse = false;

                if( file_exists( $this->globalrepodir . "/packages/$pkg/$ver/STATUS" ) )
                {
                    $status = file( $this->globalrepodir . "/packages/$pkg/$ver/STATUS" );
                }
                else if( file_exists( $this->regionrepodir . "/packages/$pkg/$ver/STATUS" ) )
                {
                    $status = file( $this->regionrepodir . "/packages/$pkg/$ver/STATUS" );
                }

                if( $status != null )
                {
                    if( preg_match( "/^status:\s+$type\s+?\$/i" , $status[ 0 ] ) )
                    {
                        print $type . ": " . $pkg . " " . $ver . "\n";
                    }   
                }
                else if( $type == "" )
                {
                    print "NOSTATUS: " . $pkg . " " . $ver . "\n";
                }            
            }
        }
    }

    public function printEolPackages() 
    {
        $this->printpackages( "eol" , false );
    }

    public function printProductionPackages() 
    {
        $this->printpackages( "production" , false );
    }

    public function PrintLegacyProductionPackages() 
    {
        $this->PrintPackages( "legacyprod" , false );
    }

    public function PrintBetaPackages()
    {
        $this->PrintPackages( "beta" , false );
    }

    public function PrintDevelopmentPackages()
    {
        $this->PrintPackages( "development" , false );
    }

    public function PrintNoStatusPackages()
    {
        $this->PrintPackages( "" , false );
    }

    public function printEolPackagesUsed() 
    {
        $this->printpackages( "eol" , true );
    }

    public function printProductionPackagesUsed() 
    {
        $this->printpackages( "production" , true );
    }

    public function PrintLegacyProductionPackagesUsed() 
    {
        $this->PrintPackages( "legacyprod" , true );
    }

    public function PrintBetaPackagesUsed()
    {
        $this->PrintPackages( "beta" , true );
    }

    public function PrintDevelopmentPackagesUsed()
    {
        $this->PrintPackages( "development" , true );
    }

    public function PrintNoStatusPackagesUsed()
    {
        $this->PrintPackages( "" , true );
    }
}

