#!/usr/bin/perl
# -----------------------------------------------------------------------------

$NAME_		= basename($0);    									# my own name
$PURPOSE_	= "resize an existing rrd";
$SYNOPSIS_	= "$NAME_ 
				-f <filemask> 
				-r <rra> | -s <actual row size>
				-o <output dir> 
				-g <growth> 
				-i
			   [-d <debug>]";
$REQUIRES_	= "Getopt::Std, File::Basename, File::stat, File::Copy, File::KGlob, RRDp";
$VERSION_	= "Version 0.43";
$DATE_		= "2006-01-15";
$AUTHOR_	= "Reinhard Scheck";         

# -----------------------------------------------------------------------------
# This program is distributed under the terms of the GNU General Public License

# --- required Modules --------------------------------------------------------
use Getopt::Std;
use File::Basename;
use File::stat;
use File::Copy;
use File::KGlob;
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
		  -r, rra to be changed (first rra denotes as -r 0)
		  -s, take only rra's with exactly that actual row size
		  -o, output directory for resized rrds
		  -g, growth (number of data points to be ADDED to those already defined)
		  -i, invoke rrdtool info instead of resizing
		  -d, debug level (0=standard, 1=function trace, 2=verbose)
		  -h, usage and options (this help)
		  
	-s or -r must be given. -s will override -r option
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

# --- make_output_dir ---------------------------------------------------------
sub make_output_dir {

	my $_dir = $_[0];
	&write_log(1, "function make_output_dir called, parms: $_dir\n");

	if ( mkdir $_dir ) { return 0; }
	else {
		&write_log(1, "function make_output_dir for $_dir returns: $!\n");
		return 1;
	}
}

# --- file_size ---------------------------------------------------------------
sub file_size {

	my $_file = $_[0];
	&write_log(1, "function file_size called, parms: $_file\n");

	my $_stats = stat( $_file );
	print ".." . $_stats->size;
}

# --- rrdinfo -----------------------------------------------------------------
sub rrdinfo {

	my $_file = $_[0];
	&write_log(1, "function rrdinfo called, parms: $_file\n");

	my $_new = basename( $_file );
	&write_log(0, "-- RRDTOOL INFO $_new ...\n");

	RRDp::cmd "info " . $_file;
	my $_answer = RRDp::read;
	# --- split answer into array at each newline -----------------------------
	my @_output = split /^/m, $$_answer;
	foreach (@_output) {
		print if /type|rows|cf/;
	}
}

# --- get_rras ----------------------------------------------------------------
sub get_rras {

	my $_file 		= $_[0];
	my $_size	 	= $_[1];
	my $_new_rra	= "";
	&write_log(1, "function get_rras called, parms: $_file $_size\n");

	RRDp::cmd "info " . $_file;
	my $_answer = RRDp::read;
	# --- split answer into array at each newline -----------------------------
	my @_output = split /^/m, $$_answer;
	foreach (@_output) {
		# --- if output looks like: rra[0].rows = 600 -------------------------
		if ( /^rra\[(\d+)]\.rows = $_size$/) {
			# --- append to wanted rras ---------------------------------------
			$_new_rra .= $1 . " ";
		}
	}
	return $_new_rra;
}

# --- resize ------------------------------------------------------------------
sub resize {

	my $_file 		= $_[0];
	my $_rra	  	= $_[1];
	my $_growth 	= $_[2];
	my $_dir		= $_[3];
	&write_log(1, "function resize called, parms: $_file $_rra $_growth $_dir\n");

	# --- deal with these files -----------------------------------------------
	my $_old_rrd = "orig.rrd";      # intermediate file
	my $_new_rrd = "resize.rrd";    # filename set by rrdtool resize, FIXED
	my $_i       = 0;

	# --- get filename and print ----------------------------------------------
	my $_new = basename( $_file );

	&write_log(0, "-- RRDTOOL RESIZE $_new RRA ($_rra) growing $_growth");

	# --- copy to workfile, print size ----------------------------------------
	copy($_file, $_old_rrd);
	&file_size($_old_rrd);

	# --- resize workfile creates "resize.rrd" for all RRAs given -------------
	&write_log(0, ".. RRA");
	foreach $_i ( split /\s+/, $_rra ) {
		&write_log(0, "#$_i");
		RRDp::cmd "resize " . $_old_rrd . " " . $_i . " GROW " . $_growth;
		my $_answer = RRDp::read;
		move($_new_rrd, $_old_rrd);	# for next iteration
	}

	# --- print new size ------------------------------------------------------
	&file_size($_old_rrd);

	# --- move to target dir --------------------------------------------------
	my $_stats = stat( $_file );
	chown $_stats->uid, $_stats->gid, $_old_rrd;
	move($_old_rrd, $_dir."/".$_new);
	&write_log(0, ".. Done.\n");
}

# --- main --------------------------------------------------------------------
# --- assign input parameters -------------------------------------------------
getopts('hid:f:r:o:g:s:');
&usage() if $opt_h;
defined($opt_i) ? ($info		= $opt_i) : ($info			= "");
defined($opt_d) ? ($debug		= $opt_d) : ($debug			= 0 );
defined($opt_r) ? ($rra			= $opt_r) : ($rra			= "");
defined($opt_s) ? ($size 		= $opt_s) : ($size			= "");

# --- check for dependent parms -----------------------------------------------
if ( !defined($opt_f) ) { &write_log(0, "Option -f missing\n\n"); &usage; } else { $filemask   = $opt_f; };
if ( !defined($opt_o) ) { &write_log(0, "Option -o missing\n\n"); &usage; } else { $output_dir = $opt_o; };
if ( !defined($opt_g) ) { &write_log(0, "Option -g missing\n\n"); &usage; } else { $growth     = $opt_g; };
if ( !defined($opt_r) && !defined($opt_s) ) { &write_log(0, "Option -r or -s missing\n\n"); &usage; }

# --- create output directory -------------------------------------------------
&make_output_dir("$output_dir");

# --- start RRD pipe to get faster results ------------------------------------
RRDp::start $rrd_binary;

# --- loop for all files of given filemask ------------------------------------
my @files = glob($filemask);
for my $file ( @files ) {
	if ($info) { &rrdinfo($file); }
	else {
		# --- take only rra's of wanted size ----------------------------------
		$rra = &get_rras($file, $size) if defined($opt_s);
		&resize($file, $rra, $growth, $output_dir) if ( $rra ne "" );
	}
}

# --- print some nice statistics ----------------------------------------------
my $status = RRDp::end;
print("user time: ", $RRDp::user, " system time: ", $RRDp::sys, " real time: ", $RRDp::real . "\n");
