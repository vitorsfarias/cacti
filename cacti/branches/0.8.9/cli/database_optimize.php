<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2014 The Cacti Group                                 |
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
array_shift($parms);

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
		case "-h":
		case "-v":
		case "-V":
		case "--version":
		case "--help":
			display_help();
			exit;
		default:
			print "ERROR: Invalid Parameter " . $parameter . "\n\n";
		display_help();
		exit;
	}
}
echo "Optimizing All Cacti Database Tables\n";

db_execute("UNLOCK TABLES");

$tables = db_fetch_assoc("SHOW TABLE STATUS FROM " . $database_default . " WHERE `Data_free` > 0");

if (sizeof($tables)) {
	foreach($tables AS $table) {
		echo "Optimizing Table -> '" . $table['Name'] . "', freeing " . $table['Data_free'] . " bytes:";
		$status = db_execute("Optimize TABLE " . $table['Name']);
		echo ($status == 0 ? " Failed" : " Successful") . "\n";
	}
}


/* display_help - displays the usage of the function */
function display_help () {
	print "Cacti Database Optimization Tool 1.1, Copyright 2004-2014 - The Cacti Group\n\n";
	print "usage: database_optimize.php [--debug] [--form] [--help]\n\n";
	print "--debug - Display verbose output during execution\n";
	print "--help  - Display this help message\n";
}
?>