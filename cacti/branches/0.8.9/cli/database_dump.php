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

$no_http_headers = TRUE;
$proceed         = FALSE;
$bin_mysqldump = "mysqldump"; # works, if this file is in user's path; else replace by /full/path/to/command
$bin_head = "head"; # works, if this file is in user's path; else replace by /full/path/to/command
$bin_tail = "tail"; # works, if this file is in user's path; else replace by /full/path/to/command

include(dirname(__FILE__) . "/../include/global.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);
$proceed = TRUE;
$dumpfile = dirname(read_config_option('path_cactilog')) . "/" . $database_default . '.mysql.dump';

if (sizeof($parms)) {
	foreach($parms as $parameter) {
		@list($arg, $value) = @explode("=", $parameter);
		
		switch ($arg) {
			case "--proceed":
				$proceed = $value;
				
				break;
			case "--file":
				$dumpfile = $value;
				
				break;
			case "--version":
			case "-V":
			case "-H":
			case "--help":
				display_help();
				exit(0);
			default:
				echo "ERROR: Invalid Argument: ($arg)\n\n";
			display_help();
			exit(1);
		}
	}
}

if ($proceed != TRUE) {
	echo "\nFATAL: You Must Explicitally Instruct This Script to Proceed with the '--proceed' Option\n\n";
	display_help();
	exit(-1);
}

$output = shell_exec("$bin_mysqldump --user=$database_username --password=$database_password --lock-tables --add-drop-database --add-drop-table $database_default > $dumpfile");
print $output;
$output = shell_exec("$bin_head -1 $dumpfile");
print $output;
$output = shell_exec("$bin_tail -1 $dumpfile");
print $output;


function display_help() {
global $dumpfile;
	echo "Database Dump Utility, Copyright 2008-2012 - The Cacti Group\n\n";
	echo "usage: database_dump.php [--file] [--proceed] [--help | -H | --version | -V]\n\n";
	echo "Optional:\n";
	echo "  --file    relative filename for dumpfile (default: $dumpfile)\n";
}

?>
