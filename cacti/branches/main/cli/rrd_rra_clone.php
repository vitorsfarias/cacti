<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2011 The Cacti Group                                 |
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

/* We are not talking to the browser */
$no_http_headers = true;
/* required includes */
include(dirname(__FILE__)."/../include/global.php");
require(CACTI_BASE_PATH . "/include/data_source/data_source_arrays.php");
include_once(CACTI_BASE_PATH."/lib/api_rrd.php");

/* verify required PHP extension */
if (!extension_loaded("DOM")) {
	print(__("Extension 'DOM' is missing. This extension requires PHP Version 5.") . "\n");
	exit;
}


/* process calling arguments */
$parms 		= $_SERVER["argv"];
$me 		= array_shift($parms);
$debug		= FALSE;	# no debug mode
$delimiter 	= ':';		# default delimiter for separating ds arguments, if not given by user
$separator 	= ';';		# default delimiter for separating multiple ds', if not given by user

if (sizeof($parms)) {
	foreach ($parms as $parameter) {
		@ list ($arg, $value) = @ explode("=", $parameter);

		switch ($arg) {
			case "-d":
			case "--debug":				$debug 						= TRUE; 		break;
			case "--delim":				$delimiter					= trim($value);	break;
			case "--sep":				$separator					= trim($value);	break;
			case "--data-template-id" :	$data_template_id 			= trim($value);	break;
			case "--data-source-id" :	$data_source_id 			= trim($value);	break;
			case "--rrd":				$rrd 						= trim($value);	break;
			case "--rra":				$rra_parm					= trim($value); break;
			case "--cf":				$cf							= trim($value); break;
			case "-V":
			case "-H":
			case "--help":
			case "--version":		display_help($me);								exit(0);
			case "--quiet":			$quietMode = TRUE;								break;
			default:				echo __("ERROR: Invalid Argument: (%s)", $arg) . "\n\n"; display_help($me); exit(1);
		}
	}

	/* Now we either have
	 * - a data template id or
	 * - a data source id or
	 * - a plain rrd file name
	 * At the end, we need an array of file names for processing
	 * rrdtool dump - modify XML - rrdtool restore
	 */
	$file_array = array();
	if (isset($data_template_id) && ($data_template_id > 0)) {
		/* get file array for a given data template id */
		$file_array = get_data_template_rrd($data_template_id);
	}elseif (isset($data_source_id) && ($data_source_id > 0)) {
		/* get file array (single element) for a given data source id */
		$file_array = get_data_source_rrd($data_source_id);
	}elseif (isset($rrd)) {
		if (!file_exists($rrd)) {
			echo __("ERROR: You must supply a valid rrd file name.") . "\n";
			echo __("Found:") . " $rrd\n";
			exit (1);
		}else {
			$file_array[] = $rrd;
		}
	}
	/* verify if at least one valid rrd file was given */
	if (!sizeof($file_array)) {
		echo __("ERROR: No valid rrd file name found.") . "\n";
		echo __("Please either supply %s or %s or %s", "--data-template-id", "--data-source-id", "--rrd") . "\n";
		exit (1);
	}

	/* cf must equal [AVERAGE|MAX|MIN|LAST] */
	if (!isset($cf) || (!preg_match('/^(AVERAGE|MAX|MIN|LAST)$/', $cf))) {
		echo __("ERROR: You must supply a valid consolidation function.") . "\n";
		echo __("Found: %s\n", (isset($cf) ? $cf : __("no cf found")));
		exit (1);
	}

	/* we may have multiple rra's to copy
	 * so let's first get the array of rra's
	 * $rra_array = array(
	 * 		0 => array(
	 * 			'cf' => cf
	 * 			'rows' => rows
	 * 			'pdp_per_row' => pdp_per_row
	 * 			'xff' => xff
	 * 			)
	 * 		1 => array(
	 * 			...
	 * 			)
	 * 		...
	 * 		)
	 */
	$rra_parm_array = explode($separator, $rra_parm);
	if (sizeof($rra_parm_array)) {
		foreach($rra_parm_array as $key => $value) {
			/* verify the given parameters */
			@list($_cf, $_rows, $_pdp_per_row, $_xff) = explode(":", $value);
			/* cf must equal [AVERAGE|MAX|MIN|LAST] */
			if ((!preg_match('/^(AVERAGE|MAX|MIN|LAST)$/', $cf))) {
				echo __("ERROR: You must supply a valid consolidation function.") . "\n";
				echo __("Found: %s\n", (isset($_cf) ? $_cf : __("no cf found")));
				exit (1);
			}else {
				$rra_array[$key]['cf'] = $_cf;
			}
			/* rows must be numeric */
			if ((!preg_match('/^[0-9]*$/', $_rows))) {
				echo __("ERROR: You must supply a valid row number.") . "\n";
				echo __("Found: %s\n", $_rows);
				exit (1);
			}else {
				$rra_array[$key]['rows'] = $_rows;
			}
			/* pdp_per_row must be numeric */
			if ((!preg_match('/^[0-9]*$/', $_pdp_per_row))) {
				echo __("ERROR: You must supply a valid pdp_per_row number.") . "\n";
				echo __("Found: %s\n", $pdp_per_row);
				exit (1);
			}else {
				$rra_array[$key]['pdp_per_row'] = $_pdp_per_row;
			}
			/* xff must be numeric < 1 */
			if ((!preg_match('/^[.0-9]*$/', $_xff) || (int)$_xff >= 1)) {
				echo __("ERROR: You must supply a valid xff number.") . "\n";
				echo __("Found: %s\n", $_xff);
				exit (1);
			}else {
				$rra_array[$key]['xff'] = $_xff;
			}

		}
	}

	$rc= api_rrd_rra_clone($file_array, $cf, $rra_array, $debug);
	if (isset($rc["err_msg"])) {
		print $rc["err_msg"] . "\n\n";
		display_help($me);
		exit(1);
	}

	#exit($rc);
}else{
	display_help($me);
	exit(0);
}


function display_help($me) {
	echo "Copy RRA from RRD File Script 1.0" . ", " . __("Copyright 2004-2011 - The Cacti Group") . "\n";
	echo __("A simple command line utility to copy an rra in an existing RRD file") . "\n\n";
	echo __("usage: ") . $me . " --rra= [--data-template-id=] [--data-source-id=] [--rrd=]\n";
	echo "       [--delim=] [--sep=] [-d]\n\n";
	echo __("Required:") . "\n";
	echo "   --rra                   " . __("specifies the rra to be deleted.") . "\n";
	echo "                           " . __("Format is 'cf:rows:pdp_per_row:xff [;cf:rows:pdp_per_row:xff ...]'") . "\n";
	echo __("One of [%s|%s|%s] must be given.", '--data-template-id', '--data-source-id', '--rrd') . "\n";
	echo __("Write permissions to the files is required.") . "\n";
	echo "   --data-template-id      " . __("Id of a data-template.") . " " . __("All related rrd files will be modified") . "\n";
	echo "   --data-source-id        " . __("Id of a data-source.") . " " . __("The related rrd file will be modified") . "\n";
	echo "   --rrd                   " . __("RRD file name.") . " " . __("The related rrd file will be modified") . "\n";
	echo __("Optional:") . "\n";
	echo "   --delim                 " . __("Delimiter to separate the --rra parameters") . " " . __("Defaults to '%s'", ":") . "\n";
	echo "   --sep	                 " . __("Separator for multiple RRA parameters") . __("Defaults to '%s'", ";") .  "\n";
	echo "   --debug, -d             " . __("Debug Mode, no updates made, but printing the SQL for updates") . "\n";
	echo __("Examples:") . "\n";
	echo "   php -q " . $me . " --rra='AVERAGE:500:1:0.5' --rrd='rra/system_temperature.rrd'\n";
	echo "   " . __("deletes the given RRA from the given rrd file") . "\n";
	echo "   php -q " . $me . " --rra='AVERAGE:500:1:0.5; MAX:500:1:0.5' --data-template-id=1\n";
	echo "   " . __("deletes both RRAs from all rrd files related to data-template-id 1") . "\n";
}

