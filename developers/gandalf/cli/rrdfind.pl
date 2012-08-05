#!/usr/bin/perl
# -----------------------------------------------------------------------------

$NAME_		= basename($0);    									# my own name
$PURPOSE_	= "find specific rrd files";
$SYNOPSIS_	= "$NAME_ 
				-f <filemask> 
				-s <step>
				-i
			   [-d <debug>]";
$REQUIRES_	= "Getopt::Std, File::Basename, File::stat, File::Copy, File::KGlob, RRDp";
$VERSION_	= "Version 0.1";
$DATE_		= "2012-08-04";
$AUTHOR_	= "gandalf";         

# -----------------------------------------------------------------------------
# This program is distributed under the terms of the GNU General Public License

# --- required Modules --------------------------------------------------------
use Getopt::Std;
use File::Basename;
use File::stat;
use File::Copy;
#use File::KGlob;
use RRDp;

# --- initialization ----------------------------------------------------------
my $rrd_binary	= "/usr/bin/rrdtool";	# path to rrd binary, to be customized! <<<<<<<<<<<<<<<<<<<<
my $debug 		= 0;               		# minimal output
my $info  		= 0;               		# no rrdtool info needed

# --- usage -------------------------------------------------------------------
sub usage {

	print STDOUT "$NAME_ $VERSION_ - $PURPOSE_
	Usage:	  $SYNOPSIS_
	Requires: $REQUIRES_
	Author:	  $AUTHOR_
	Date:	  $DATE_
	Options:
		  -f, filemask of the source rrds
		  -s, search for specific 'step'
		  -i, invoke rrdtool info
		  -d, debug level (0=standard, 1=function trace, 2=verbose)
		  -h, usage and options (this help)
		  
	-s must be given
	No parameter validation done. Hope you know what you're going to do!\n\n";
	exit 1;
}

# --- write_log ---------------------------------------------------------------
sub write_log {
	
	my $_level = $_[0];
	my $_text  = $_[1];
	
	if ( $debug >= $_level ) {
		print $_text;
	}
	return 0;
}

# --- main --------------------------------------------------------------------
# --- assign input parameters -------------------------------------------------
getopts('hid:f:s:');
&usage() if $opt_h;
defined($opt_i) ? ($info		= $opt_i) : ($info			= "");
defined($opt_d) ? ($debug		= $opt_d) : ($debug			= 0 );
defined($opt_s) ? ($step 		= $opt_s) : ($step			= "");

# --- check for dependent parms -----------------------------------------------
if ( !defined($opt_f) ) { &write_log(0, "Option -f missing\n\n"); &usage; } else { $filemask   = $opt_f; };
if ( !defined($opt_s) ) { &write_log(0, "Option -s missing\n\n"); &usage; }

# --- start RRD pipe to get faster results ------------------------------------
RRDp::start $rrd_binary;

# --- loop for all files of given filemask ------------------------------------
my @files = glob($filemask);
for my $file ( @files ) {
	RRDp::cmd "info " . $file;
	my $_answer = RRDp::read;
	# --- split answer into array at each newline -----------------------------
	my @_output = split /^/m, $$_answer;

	foreach (@_output) {
		# --- if output looks like: step = 300 -------------------------
		if ( /^step = $step$/) {
			print ($file . "\n");
			last;
		}
	}

}

# --- print some nice statistics ----------------------------------------------
my $status = RRDp::end;
