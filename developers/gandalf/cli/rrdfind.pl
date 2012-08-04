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

	RRDp::cmd "info " . $_file;
	my $_answer = RRDp::read;
	# --- split answer into array at each newline -----------------------------
	my @_output = split /^/m, $$_answer;
	return @_output;
}

# --- get_rrd ----------------------------------------------------------------
sub get_step {

	my @_info 		= @_;
	&write_log(1, "get_step\n");

	foreach (@_info) {
		# --- if output looks like: step = 300 -------------------------
		if ( /^step = (\d+)$/) {
			&write_log(2, "found step, $1\n");
			# --- return step size found ---------------------------------------
			return $1;
		}
	}
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
getopts('hid:f:s:');
&usage() if $opt_h;
defined($opt_i) ? ($info		= $opt_i) : ($info			= "");
defined($opt_d) ? ($debug		= $opt_d) : ($debug			= 0 );
defined($opt_s) ? ($step 		= $opt_s) : ($step			= "");

# --- check for dependent parms -----------------------------------------------
if ( !defined($opt_f) ) { &write_log(0, "Option -f missing\n\n"); &usage; } else { $filemask   = $opt_f; };
if ( !defined($opt_s) ) { &write_log(0, "Option -s missing\n\n"); &usage; }

# --- create output directory -------------------------------------------------
#&make_output_dir("$output_dir");

# --- start RRD pipe to get faster results ------------------------------------
RRDp::start $rrd_binary;

# --- loop for all files of given filemask ------------------------------------
my $found = true;
my @files = glob($filemask);
for my $file ( @files ) {
	if ($info) { &rrdinfo($file); }
	else {
		&write_log(2, "working on $file\n");
		# --- take only rra's of wanted size ----------------------------------
		@rrdinfo = &rrdinfo($file);
		# pass rrd info array by reference ------------------------------------
		$found = ($step == &get_step(@rrdinfo)) if defined($opt_s);
		print ($file . "\n") if $found;
		#&do_something($file, $found) if ( $found ne "" );
	}
}

# --- print some nice statistics ----------------------------------------------
my $status = RRDp::end;
#print("user time: ", $RRDp::user, " system time: ", $RRDp::sys, " real time: ", $RRDp::real . "\n");
