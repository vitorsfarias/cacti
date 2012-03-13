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

$no_http_headers = TRUE;
$proceed         = FALSE;

include(dirname(__FILE__) . "/../include/global.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
$me = array_shift($parms);
$device_id = NULL;

if (sizeof($parms)) {
	foreach($parms as $parameter) {
		@list($arg, $value) = @explode("=", $parameter);

		switch ($arg) {
		case "--device-id":
			$device_id = $value;
			break;
		case "--proceed":
			$proceed = TRUE;

			break;
		case "--version":
		case "-V":
		case "-H":
		case "--help":
			display_help($me);
			exit(0);
		default:
			printf(__("ERROR: Invalid Argument: (%s)\n\n"), $arg);
			display_help($me);
			exit(1);
		}
	}
}

if ($proceed == FALSE) {
	echo "\n" . __("FATAL: You Must Explicitally Instruct This Script to Proceed with the '--proceed' Option") . "\n\n";
	display_help($me);
	exit -1;
}

/* check ownership of the current base path */
$base_rra_path = CACTI_RRA_PATH;
$owner_id      = fileowner($base_rra_path);
$group_id      = filegroup($base_rra_path);

/* turn off the poller */
disable_poller();

$poller_running = shell_exec("ps -ef | grep poller.php | wc -l");
if ($poller_running == "1") {
	/* turn on the poller */
	enable_poller();

	echo __("FATAL: The Poller is Currently Running") . "\n";
	exit -4;
}

/* turn on extended paths from in the database */
set_config_option("extended_paths", CHECKED);

/* fetch all DS having wrong path */
$data_sources = db_fetch_assoc("SELECT
				local_data_id,
				device_id,
				data_source_path,
				CONCAT('<path_rra>/', device_id, '/', local_data_id, '.rrd') AS new_data_source_path,
				REPLACE(data_source_path, '<path_rra>', '$base_rra_path') AS rrd_path,
				REPLACE(CONCAT('<path_rra>/', device_id, '/', local_data_id, '.rrd'), '<path_rra>', '$base_rra_path') AS new_rrd_path
					FROM data_template_data
					INNER JOIN data_local ON data_local.id=data_template_data.local_data_id
					INNER JOIN device ON device.id=data_local.device_id
				WHERE data_source_path != CONCAT('<path_rra>/', device_id, '/', local_data_id, '.rrd')"
				 . ($device_id === NULL ? "" : " AND device_id=$device_id"));

/* setup some counters */
$done_count   = 0;
$warn_count   = 0;

/* scan all data sources */
foreach ($data_sources as $info) {
	$new_base_path = "$base_rra_path" . "/" . $info["device_id"];
	$new_rrd_path  = $info["new_rrd_path"];
	$old_rrd_path  = $info["rrd_path"];

	/* create one subfolder for every device */
	if (!is_dir($new_base_path)) {
		/* see if we can create the directory for the new file */
		if (mkdir($new_base_path, 0775)) {
			printf(__("NOTE: New Directory '%s' Created for RRD Files\n"), $new_base_path);
			if (CACTI_SERVER_OS != "win32") {
				if (chown($new_base_path, $owner_id) && chgrp($new_base_path, $group_id)) {
					printf(__("NOTE: New Directory '%s' Permissions Set\n"), $new_base_path);
				} else {
					/* turn on the poller */
					enable_poller();

					printf(__("FATAL: Could not Set Permissions for Directory '%s'\n"), $new_base_path);
					exit -5;
				}
			}
		} else {
			/* turn on the poller */
			enable_poller();

			printf(__("FATAL: Could NOT Make New Directory '$new_base_path'\n"), $new_base_path);
			exit -1;
		}
	}

	/* copy the file, update the database and remove the old file */
	if (!file_exists($old_rrd_path)) {
		$warn_count++;

		printf(__("WARNING: Legacy RRA Path '%s' Does not exist, Skipping\n"), $old_rrd_path);

		/* alter database */
		update_database($info);
	} elseif (link($old_rrd_path, $new_rrd_path)) {
		$done_count++;

		printf(__("NOTE: HardLink Complete for File '%a" . "' -> '" . "%a'\n"), $old_rrd_path, $new_rrd_path);
		if (CACTI_SERVER_OS != "win32") {
			if (chown($new_rrd_path, $owner_id) && chgrp($new_rrd_path, $group_id)) {
				printf(__("NOTE: Permissions set for '%s'\n"), $new_rrd_path);
			}else{
				/* turn on the poller */
				enable_poller();

				printf(__("FATAL: Could not Set Permissions for File '%s'\n"), $new_rrd_path);
				exit -6;
			}
		}

		/* alter database */
		update_database($info);

		if (unlink($old_rrd_path)) {
			printf(__("NOTE: Old File '%s' Removed\n"), $new_rrd_path);
		} else {
			/* turn on the poller */
			enable_poller();

			printf(__("FATAL: Old File '%s' Could not be removed\n"), $old_rrd_path);
			exit -2;
		}
	} else {
		/* turn on the poller */
		enable_poller();

		printf(__("FATAL: Could not Copy RRD File '%1s' to '%2s'\n"), $old_rrd_path, $new_rrd_path);
		exit -3;
	}
}

/* finally re-enable the poller */
enable_poller();

printf(__("NOTE: Process Complete, '%1d' Completed, '%2d' Skipped\n"), $done_count, $warn_count);

/* update database */
function update_database($info) {
	/* upate table poller_item */
	db_execute("UPDATE poller_item
		SET rrd_path = '" . $info["new_rrd_path"] . "'
		WHERE local_data_id=" . $info["local_data_id"]);

	/* update table data_template_data */
	db_execute("UPDATE data_template_data
		SET data_source_path='" . $info["new_data_source_path"] . "'
		WHERE local_data_id=" . $info["local_data_id"]);

	printf(__("NOTE: Database Changes Complete for File '%s'\n"), $info["new_rrd_path"]);
}

/* turn on the poller */
function enable_poller() {
	set_config_option('poller_enabled', 'on');
}

/* turn off the poller */
function disable_poller() {
	set_config_option('poller_enabled', '');
}

function display_help($me) {
	echo "Structured RRA Paths Utility V1.0" . ", " . __("Copyright 2008-2012 - The Cacti Group") . "\n";
	echo __("A simple command line utility that converts a Cacti system from using") . "\n";
	echo __("legacy RRA paths to using structured RRA paths with the following") . "\n";
	echo __("naming convention: <path_rra>/device-id/local_data_id.rrd") . "\n\n";
	echo __("This utility is designed for very large Cacti systems.") . "\n\n";
	echo __("On Linux OS, superuser is required to apply file ownership.") . "\n\n";
	echo __("The utility follows the process below:") . "\n";
	echo __("  1) Disables the Cacti Poller") . "\n";
	echo __("  2) Checks for a Running Poller.") . "\n\n";
	echo __("If it Finds a Running Poller, it will:") . "\n";
	echo __("  1) Re-enable the Cacti Poller") . "\n";
	echo __("  2) Exit") . "\n\n";
	echo __("Else, it will:") . "\n";
	echo __("  1) Enable Structured Paths in the Console (Settings->Paths)") . "\n\n";
	echo __("Then, for Each File, it will:") . "\n";
	echo __("  1) Create the Structured Path, if Necessary") . "\n";
	echo __("  2) Copy the File to the Strucured Path Using the New Name") . "\n";
	echo __("  3) Alter the two Database Tables Required") . "\n";
	echo __("  4) Remove the Old File") . "\n\n";
	echo __("Once all Files are Complete, it will") . "\n";
	echo __("  1) Re-enable the Cacti Poller") . "\n\n";
	echo __("If the utility encounters a problem along the way, it will:") . "\n";
	echo __("  1) Re-enable the Cacti Poller") . "\n";
	echo __("  2) Exit") . "\n\n";
	echo __("usage: ") . $me . " --proceed [--help | -H | --version | -V] [--device-id=<device id>]\n\n";
}
