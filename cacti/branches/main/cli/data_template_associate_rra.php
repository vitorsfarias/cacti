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
if (!isset ($_SERVER["argv"][0]) || isset ($_SERVER['REQUEST_METHOD']) || isset ($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

/* We are not talking to the browser */
$no_http_headers = true;

include (dirname(__FILE__) . "/../include/global.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
$me    = array_shift($parms);
$debug = FALSE;	# no debug mode
$data_template_id = 0;
$quietMode = false;
$rra = '';

if (sizeof($parms)) {
	foreach ($parms as $parameter) {
		@ list ($arg, $value) = @ explode("=", $parameter);

		switch ($arg) {
			case "-d":
			case "--debug":				$debug 						= TRUE; 		break;
			case "--rra":				$rra						= trim($value);	break;
			case "--data-template-id" :	$data_template_id 			= trim($value);	break;
			case "-V":
			case "-H":
			case "--help":
			case "--version":		display_help($me);								exit(0);
			case "--quiet":			$quietMode = TRUE;								break;
			default:				echo __("ERROR: Invalid Argument: (%s)", $arg) . "\n\n"; display_help($me); exit(1);
		}
	}

	$data_template = db_fetch_row("SELECT * from data_template WHERE id=$data_template_id");
	if (sizeof($data_template)) {
		if (!$quietMode) print __("Working on Data Template Id %s: %s\n", $data_template_id, $data_template["name"]);
	} else {
		print __("ERROR: Invalid data template id given: %s\n", $data_template_id);
		exit(1);
	}

	$data_rra = explode(':', $rra);
	if (sizeof($data_rra)) {
		foreach ($data_rra as $rra_id) {
			$rra_curr = db_fetch_row("SELECT * FROM rra WHERE id=" . $rra_id);
			if (sizeof($rra_curr)) {
				if (!$quietMode) print __("Working on RRA Id %s:%s:%s:%s:%s\n", $rra_id, $rra_curr["name"], $rra_curr["x_files_factor"], $rra_curr["steps"], $rra_curr["rows"], $rra_curr["timespan"]);
			} else {
				print __("ERROR: Invalid rra id given: %s\n", $rra_id);
				exit(1);
			}
		}
		associate($data_template["id"], $data_rra, $debug, $quietMode);
	} else {
		print __("ERROR: Invalid rra definition given: %s\n", $rra);
		exit(1);
	}

} else {
	display_help();
	exit (0);
}


function associate($data_template_id, $data_rra, $debug, $quiet) {

	/* get a list of data sources using this template 
	 * including the template itself */
	$data_sources = db_fetch_assoc("SELECT
			data_template_data.id
			FROM data_template_data
			WHERE data_template_id=$data_template_id");

	if (sizeof($data_sources) > 0) {
		foreach ($data_sources as $data_source) {
			if (!$quiet) print __("Working on data source id %s\n", $data_source["id"]);
			if ($debug) continue;

			/* make sure to update the 'data_template_data_rra' table for each data source */
			db_execute("DELETE
						FROM data_template_data_rra
						WHERE data_template_data_id=" . $data_source["id"]);

			reset($data_rra);

			if (sizeof($data_rra) > 0) {
				foreach ($data_rra as $rra) {
					db_execute("INSERT INTO data_template_data_rra
									(data_template_data_id,rra_id)
									VALUES (" . $data_source["id"] . "," . $rra["rra_id"] . ")");
				}
			}
		}
	}
	return;
}

function display_help($me) {
	echo __("Data Template Associate RRA Script 1.0") . ", " . __("Copyright 2004-2011 - The Cacti Group") . "\n";
	echo __("A simple command line utility to associate RRA definitions to a data template in Cacti") . "\n\n";
	echo __("usage:") . $me . __("--rra='rra-1:..:rra-n' --data-template-id=[ID]") . "\n";
	echo __("Required:") . "\n";
	echo "    --rra                  " . __("the rra ids that shall be associated, seperated by colon") . "\n";
	echo "    --data-template-id     " . __("the data template id") . "\n";
}
?>
