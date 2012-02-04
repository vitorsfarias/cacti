#!/usr/bin/php -q
<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2012 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;

include(dirname(__FILE__) . "/../include/global.php");
require_once(CACTI_BASE_PATH . "/lib/functions.php");
require_once(CACTI_BASE_PATH . "/lib/fonts.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
$me = array_shift($parms);

$debug = FALSE;
$force = FALSE;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "-d":
	case "--debug":
		$debug = TRUE;
		break;
	case "-v":
	case "--version":
	case "-h":
	case "--help":
		display_help($me);
		exit;
	default:
		echo __("ERROR: Invalid Parameter %s", $parameter) . "\n\n";
		display_help($me);
		exit;
	}
}

if (read_config_option("rrdtool_version") == "rrd-1.0.x" ||
	read_config_option("rrdtool_version") == "rrd-1.2.x") {

	# rrdtool 1.0 and 1.2 use font files
	$success = create_filebased_fontlist($debug);

} else {

	# higher rrdtool versions use pango fonts
	$success = create_pango_fontlist($debug);	
	
}


if ($success) {	
	print __("%d font items inserted into font table.", $success) . "\n";
} else {
	print __("No fonts found.") . "\n";
}	


/*	display_help - displays the usage of the function */
function display_help($me) {
	echo "Cacti Update Font List Tool v1.0" . ", " . __("Copyright 2004-2011 - The Cacti Group") . "\n";
	echo __("usage: ") . $me . " [-d] [--debug] [-h]  [--help] [-v] [--version]\n\n";
	echo "   -d --debug    " . __("Display verbose output during execution") . "\n";
	echo "   -v --version  " . __("Display the version") . "\n";
	echo "   -h --help     " . __("Display this help message") . "\n";
}
