#!/usr/bin/php -q
<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2013 The Cacti Group                                 |
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

include(dirname(__FILE__) . "/../include/global.php");
include_once(CACTI_LIBRARY_PATH . "/export.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
$me = array_shift($parms);

if (sizeof($parms)) {
	$type = "";
	$template_id = 0;
	$include_dependencies = true;

	foreach($parms as $parameter) {
		@list($arg, $value) = @explode("=", $parameter);

		switch ($arg) {
			case "--type":
				$type = trim($value);

				break;
			case "--template-id":
				$template_id = trim($value);

				break;
			case "--no-include-dependencies":
				$include_dependencies = false;

				break;
			case "--version":
			case "-V":
			case "-H":
			case "--help":
				display_help($me);
				exit(0);
			default:
				printf(__("ERROR: Invalid Argument: (%s)\n\n"), $arg);
				exit(1);
		}
	}
	
	/* verify parameters passed by the user */
	
	/* allow to dump "all" templates of one type */
	if (strtolower($template_id) == "all") {
		$sql_where = "";
	} else {
		/* else verify the given id */
		$template_id = _input_validate_input_number($template_id);
		$sql_where = " where id = $template_id";
	}
	
	switch ($type) {
		/* use all types listed as elements of $export_type
		 * in case we allow custom export types for plugins, we need a hook here */
		case "graph_template":
			$template_array = db_fetch_assoc("select id, name from graph_templates" . $sql_where);
			break;
		case "data_template":
			$template_array = db_fetch_assoc("select id, name from data_template" . $sql_where);
			break;
		case "host_template":
		case "device_template":
			$template_array = db_fetch_assoc("select id, name from host_template" . $sql_where);
			break;
		case "data_query":
			$template_array = db_fetch_assoc("select id, name from snmp_query" . $sql_where);
			break;
		default:
			printf(__("ERROR: invalid template type %s \n\n"), $type);
			display_help($me);
			exit(1);
	}

	if(sizeof($template_array)) {
		foreach ($template_array as $template) {
			print get_item_xml($type, $template["id"], $include_dependencies);			
		}
	} else {
		echo __("ERROR: no valid template(s) found") . "\n\n";
		display_help($me);
		exit(1);
	}
} else {
	echo __("ERROR: no parameters given") . "\n\n";
	display_help($me);
	exit(1);
}



function _input_validate_input_number($value) {
	if ((!is_numeric($value)) && ($value != "")) {
		_die_input_error();
	} else {
		return $value;
	}
}


function _die_input_error() {
	global $me;
	print __("Validation error." . "\n\n");
	display_help($me);
	exit(1);
}


function display_help($me) {
	echo "Template Export Script 1.1" . ", " . __("Copyright 2004-2012 - The Cacti Group") . "\n";
	echo __("A simple command line utility to export a Template into Cacti") . "\n\n";
	echo __("usage: ") . "\n";
	echo $me . " --type=[graph_template|data_template|device_template|data_query]\n";
	echo "       --template-id=[nn|all]  [--no-include-dependencies]  [-h][--help]  [-v][--version]\n";
	echo __("Required:") . "\n";
	echo "   --type                    " . __("the type of the template to be exported") . "\n";
	echo "   --template-id             " . __("the id of the template to be exported") . "\n";
	echo __("Optional:") . "\n";
	echo "   --no-include-dependencies " . __("do not include dependencies (not recommended)\n");
	echo "   -v --version              " . __("Display this help message") . "\n";
	echo "   -h --help                 " . __("Display this help message") . "\n";
}
