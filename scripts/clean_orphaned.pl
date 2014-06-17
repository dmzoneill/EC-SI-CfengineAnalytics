#!/usr/bin/perl -w
use strict; 
use warnings;
use File::Find;

my $tarcontents = `tar -tf /var/cfengine/local/cf.tar 2> /dev/null`;
my @fsFiles = ();
my @fsDirs = ();
my @tarfileContents = split( '\n' , $tarcontents );
my @deleteThese = ();
my $searchPath = "/var/cfengine/local/";

sub main
{
	find( { wanted => \&process_file , no_chdir => 1 } , $searchPath );

	foreach my $tarFile ( @tarfileContents )
	{
		if( in_array( \@fsFiles , $tarFile ) == 0 )
		{
			push( @deleteThese , $tarFile );
		}
	}

	deleteOrphans();
}

sub deleteOrphans
{
	foreach my $deletion ( @deleteThese )
	{
		print $deletion . " delete\n";
	}
}

sub process_file 
{
    	if ( -f $_ ) 
	{
        	push( @fsFiles , $_ );
	} 
	else 
	{
		push( @fsDirs , $_ );
	}
}

sub in_array 
{     
	my ( $arr , $search_for ) = @_;     
	my %items = map { $_ => 1 } @$arr; 
	return ( exists( $items{ $search_for } ) ) ? 1 : 0; 
}


main();
