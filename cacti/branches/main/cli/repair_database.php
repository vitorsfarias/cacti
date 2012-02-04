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

/* process calling arguments */
$parms = $_SERVER["argv"];
$me = array_shift($parms);

global $debug;

$debug = FALSE;
$form  = "";
$force = FALSE;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "-d":
	case "--debug":
		$debug = TRUE;
		break;
	case "--force":
		$force = TRUE;
		break;
	case "-form":
	case "--form":
		$form = " USE_FRM";
		break;
	case "-h":
	case "-v":
	case "-V":
	case "--version":
	case "--help":
		display_help($me);
		exit;
	default:
		echo __("ERROR: Invalid Parameter %s", $parameter) . "\n\n";
		display_help($me);
		exit;
	}
}
echo __("Repairing All Cacti Database Tables") . "\n";

/* verify correct database connection */
$db_conn = $database_sessions[$database_default];
if (!$db_conn) {
	echo __("Database settings are wrong. No database connect possible." . "\n");
	return FALSE;
} 

/* run on all tables of database, thus including all plugin tables */
db_execute("UNLOCK TABLES");
$tables = db_fetch_assoc("SHOW TABLES FROM " . $database_default);

if (sizeof($tables)) {
	foreach($tables AS $table) {
		/* try to access table to verify status */
		echo __("Checking Table -> '%s': ", $table['Tables_in_' . $database_default]);
		$status = db_execute("SELECT * FROM " . $table['Tables_in_' . $database_default] . $form . " LIMIT 1");
		$en = mysql_errno($db_conn);
		$error = mysql_error($db_conn);
		
		switch ($en) {
		case 0:
			/* everything is fine */
			echo __("Ok, no repair required" . "\n");
			continue;
			break;
			
		case 1194:
			/* database is corrupt, so run a repair */
			echo $error;
			$status = db_execute("REPAIR TABLE " . $table['Tables_in_' . $database_default] . $form);
			$en = mysql_errno($db_conn);
			$error = mysql_error($db_conn);
			echo ($status == 0 ? __(" -> Repair failed") : __(" -> Repair successful")) . "\n";
			break;
			
		default:
			/* don't know what happens, print error to user */
			echo __("Unknown database error %s when trying to verify table. Message: %s", $en, $error) . "\n";
			
		}
	}
}

echo "\n" . __("NOTE: Checking for Invalid Cacti Templates") . "\n";

/* keep track of total rows */
$total_rows = 0;

/* remove invalid consolidation function and RRA's */
$rows = db_fetch_cell("SELECT count(*) FROM rra_cf LEFT JOIN rra ON rra_cf.rra_id=rra.id WHERE rra.id IS NULL;");
$total_rows += $rows;
if ($rows > 0) {
	if ($force) {
		db_execute("DELETE FROM rra_cf WHERE rra_id NOT IN (SELECT id FROM rra)");
		echo __("NOTE: %d Invalid Consolidation Function Rows Removed from Database", $rows) . "\n";
	}else {
		echo __("NOTE: %d Invalid Consolidation Function Rows Found in Database", $rows) . "\n";
	}
}

/* remove invalid RRA's from the Database */
$rows = db_fetch_cell("SELECT count(*) FROM data_template_data_rra LEFT JOIN rra ON data_template_data_rra.rra_id=rra.id WHERE rra.id IS NULL");
$total_rows += $rows;
if ($rows > 0) {
	if ($force) {
		db_execute("DELETE FROM data_template_data_rra WHERE rra_id NOT IN (SELECT id FROM rra)");
		echo __("NOTE: %d Invalid Data Template Data RRA Rows Removed from Database", $rows) . "\n";
	}else {
		echo __("NOTE: %d Invalid Data Template Data RRA Rows Found In Database", $rows) . "\n";
	}

}

/* remove invalid GPrint Presets from the Database */
$rows = db_fetch_cell("SELECT count(*) FROM graph_templates_item LEFT JOIN graph_templates_gprint ON graph_templates_item.gprint_id=graph_templates_gprint.id WHERE graph_templates_gprint.id IS NULL AND graph_templates_item.gprint_id>0");
$total_rows += $rows;
if ($rows > 0) {
	if ($force) {
		db_execute("DELETE FROM graph_templates_item WHERE gprint_id NOT IN (SELECT id FROM graph_templates_gprint) AND gprint_id>0");
		echo __("NOTE: %d Invalid GPrint Preset Rows Removed from Graph Templates", $rows) . "\n";
	}else {
		echo __("NOTE: %d Invalid GPrint Preset Rows Found in Graph Templates", $rows) . "\n";
	}
}

/* remove invalid CDEF Items from the Database */
$rows = db_fetch_cell("SELECT count(*) FROM cdef_items LEFT JOIN cdef ON cdef_items.cdef_id=cdef.id WHERE cdef.id IS NULL");
$total_rows += $rows;
if ($rows > 0) {
	if ($force) {
		db_execute("DELETE FROM cdef_items WHERE cdef_id NOT IN (SELECT id FROM cdef)");
		echo __("NOTE: %d Invalid CDEF Item Rows Removed from Graph Templates", $rows). "\n";
	}else {
		echo __("NOTE: %d Invalid CDEF Item Rows Found in Graph Templates", $rows) . "\n";
	}
}

/* remove invalid Data Templates from the Database */
$rows = db_fetch_cell("SELECT count(*) FROM data_template_data LEFT JOIN data_input ON data_template_data.data_input_id=data_input.id WHERE data_input.id IS NULL");
$total_rows += $rows;
if ($rows > 0) {
	if ($force) {
		db_execute("DELETE FROM data_template_data WHERE data_input_id NOT IN (SELECT id FROM data_input)");
		echo __("NOTE: %d Invalid Data Input Rows Removed from Data Templates", $rows) . "\n";
	}else {
		echo __("NOTE: %d Invalid Data Input Rows Found in Data Templates", $rows) . "\n";
	}
}

/* remove invalid Data Input Fields from the Database */
$rows = db_fetch_cell("SELECT count(*) FROM data_input_fields LEFT JOIN data_input ON data_input_fields.data_input_id=data_input.id WHERE data_input.id IS NULL");
$total_rows += $rows;
if ($rows > 0) {
	if ($force) {
		db_execute("DELETE FROM data_input_fields WHERE data_input_fields.data_input_id NOT IN (SELECT id FROM data_input)");
		echo __("NOTE: %d Invalid Data Input Field Rows Removed from Data Templates", $rows) . "\n";
	}else {
		echo __("NOTE: %d Invalid Data Input Field Rows Found in Data Templates", $rows) . "\n";
	}
}

/* remove invalid Data Input Data Rows from the Database in two passes */
$rows = db_fetch_cell("SELECT count(*) FROM data_input_data LEFT JOIN data_template_data ON data_template_data.data_input_id=data_input_data.data_template_data_id WHERE data_template_data.data_input_id IS NULL AND data_template_data.data_input_id>0");
$total_rows += $rows;
if ($rows > 0) {
	if ($force) {
		db_execute("DELETE FROM data_input_data WHERE data_input_data.data_template_data_id NOT IN (SELECT data_input_id FROM data_template_data)");
		echo __("NOTE: %d Invalid Data Input Data Rows Removed from Data Templates", $rows) . "\n";
	}else {
		echo __("NOTE: %d Invalid Data Input Data Rows Found in Data Templates", $rows) ."\n";
	}
}
$rows = db_fetch_cell("SELECT count(*) FROM data_input_data LEFT JOIN data_input_fields ON data_input_fields.id=data_input_data.data_input_field_id WHERE data_input_fields.id IS NULL");
$total_rows += $rows;
if ($rows > 0) {
	if ($force) {
		db_execute("DELETE FROM data_input_data WHERE data_input_data.data_input_field_id NOT IN (SELECT id FROM data_input_fields)");
		echo __("NOTE: %d Invalid Data Input Data Rows Removed from Data Templates", $rows) . "\n";
	}else {
		echo __("NOTE: %d Invalid Data Input Data Rows Found in Data Templates", $rows) . "\n";
	}
}

/* remove invalid VDEF Items from the Database */
$rows = db_fetch_cell("SELECT count(*) FROM vdef_items LEFT JOIN vdef ON vdef_items.vdef_id=vdef.id WHERE vdef.id IS NULL");
$total_rows += $rows;
if ($rows > 0) {
	if ($force) {
		db_execute("DELETE FROM vdef_items WHERE vdef_id NOT IN (SELECT id FROM vdef)");
		echo __("NOTE: %d Invalid VDEF Item Rows Removed from Graph Templates", $rows). "\n";
	}else {
		echo __("NOTE: %d Invalid VDEF Item Rows Found in Graph Templates", $rows) . "\n";
	}
}

if ($total_rows > 0 && !$force) {
	echo "\n" . __("WARNING: Serious Cacti Template Problems found in your Database.  Using the '--force' option will remove the invalid records. However, these changes can be catastrophic to existing data sources. Therefore, you should contact your support organization prior to proceeding with that repair.") . "\n\n";
}elseif ($total_rows == 0) {
	echo __("NOTE: No Invalid Cacti Template Records found in your Database") . "\n\n";
}

/*	display_help - displays the usage of the function */
function display_help($me) {
	echo "Cacti Database Repair Tool v1.0" . ", " . __("Copyright 2004-2011 - The Cacti Group") . "\n";
	echo __("usage: ") . $me . " [-d] [-h] [--form] [--help] [-v] [-V] [--version]\n\n";
	echo "   -form         " . __("Force rebuilding the indexes from the database creation syntax") . "\n";
	echo "   -d            " . __("Display verbose output during execution") . "\n";
	echo "   -v --version  " . __("Display this help message") . "\n";
	echo "   -h --help     " . __("Display this help message") . "\n";
}
