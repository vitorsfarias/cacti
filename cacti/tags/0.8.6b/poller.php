#!/usr/bin/php -q
<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004 Ian Berry                                            |
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
 | cacti: a php-based graphing solution                                    |
 +-------------------------------------------------------------------------+
 | Most of this code has been designed, written and is maintained by       |
 | Ian Berry. See about.php for specific developer credit. Any questions   |
 | or comments regarding this code should be directed to:                  |
 | - iberry@raxnet.net                                                     |
 +-------------------------------------------------------------------------+
 | - raXnet - http://www.raxnet.net/                                       |
 +-------------------------------------------------------------------------+
*/

define("MAX_POLLER_RUNTIME", 296);

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

/* We are not talking to the browser */
$no_http_headers = true;

/* Start Initialization Section */
include(dirname(__FILE__) . "/include/config.php");
include_once($config["base_path"] . "/lib/poller.php");
include_once($config["base_path"] . "/lib/data_query.php");
include_once($config["base_path"] . "/lib/graph_export.php");
include_once($config["base_path"] . "/lib/rrd.php");

/* Record Start Time */
list($micro,$seconds) = split(" ", microtime());
$start = $seconds + $micro;

/* Let PHP Run Just as Long as It Has To */
ini_set("max_execution_time", "0");

/* Get number of polling items from the database */
$num_polling_items = db_fetch_cell("select count(*) from poller_item");
$polling_hosts = array_merge(array(0 => array("id" => "0")), db_fetch_assoc("select id from host where disabled = '' order by id"));

/* Retreive the number of concurrent process settings */
$concurrent_processes = read_config_option("concurrent_processes");

/* Initialize counters for script file handling */
$host_count = 1;

/* Initialize file creation flags */
$change_files = False;

/* Initialize file and host count pointers */
$process_file_number = 0;
$first_host = 0;
$last_host = 0;

/* Update web paths for the poller */
db_execute("replace into settings (name,value) values ('path_webroot','" . addslashes(($config["cacti_server_os"] == "win32") ? strtolower(substr(dirname(__FILE__), 0, 1)) . substr(dirname(__FILE__), 1) : dirname(__FILE__)) . "')");

// Obtain some defaults from the database
$poller = read_config_option("poller_type");
$max_threads = read_config_option("max_threads");

// Initialize poller_time and poller_output tables
db_execute("truncate table poller_time");

// Enter Mainline Processing
if (($num_polling_items > 0) && (read_config_option("poller_enabled") == "on")) {
	/* Determine the number of hosts to process per file */
	$hosts_per_file = ceil(sizeof($polling_hosts) / $concurrent_processes );

	/* Determine Command Name */
	if (($config["cacti_server_os"] == "unix") and ($poller == "2")) {
		$command_string = read_config_option("path_cactid");
		$extra_args = "";
		$method = "cactid";
		chdir(dirname(read_config_option("path_cactid")));
	}else if ($config["cacti_server_os"] == "unix") {
		$command_string = read_config_option("path_php_binary");
		$extra_args = "-q " . $config["base_path"] . "/cmd.php";
		$method = "cmd.php";
	}else if ($poller == "2") {
		$command_string = read_config_option("path_cactid");
		$extra_args = "";
		$method = "cactid";
		chdir(dirname(read_config_option("path_cactid")));
	}else{
		$command_string = read_config_option("path_php_binary");
		$extra_args = "-q " . strtolower($config["base_path"] . "/cmd.php");
		$method = "cmd.php";
	}

	/* Populate each execution file with appropriate information */
	foreach ($polling_hosts as $item) {
		if ($host_count == 1) {
			$first_host = $item["id"];
		}

		if ($host_count == $hosts_per_file) {
			$last_host = $item["id"];
			$change_files = True;
		}

		$host_count ++;

		if ($change_files) {
			exec_background($command_string, "$extra_args $first_host $last_host");

			$host_count = 1;
			$change_files = False;
			$process_file_number++;
			$first_host = 0;
			$last_host = 0;
		} /* End change_files */
	} /* End For Each */

	if ($host_count > 1) {
		$last_host = $item["id"];

		exec_background($command_string, "$extra_args $first_host $last_host");
		$process_file_number++;
	}

	/* insert the current date/time for graphs */
	db_execute("replace into settings (name,value) values ('date',NOW())");

	if ($poller == "1") {
		$max_threads = "N/A";
	}

	/* open a pipe to rrdtool for writing */
	$rrdtool_pipe = rrd_init();

	$loop_count = 0;
	while (1) {
		$polling_items = db_fetch_assoc("select poller_id,end_time from poller_time where poller_id = 0");

		if (sizeof($polling_items) == $process_file_number) {
			process_poller_output($rrdtool_pipe);

			/* take time and log performance data */
			list($micro,$seconds) = split(" ", microtime());
			$end = $seconds + $micro;

			cacti_log(sprintf("STATS: " .
				"Time: %01.4f s, " .
				"Method: %s, " .
				"Processes: %s, " .
				"Threads: %s, " .
				"Hosts: %s, " .
				"Hosts/Process: %s",
				round($end-$start,4),
				$method,
				$concurrent_processes,
				$max_threads,
				sizeof($polling_hosts),
				$hosts_per_file),true,"SYSTEM");

			break;
		}else {
			if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
				print "Waiting on " . ($process_file_number - sizeof($polling_items)) . "/$process_file_number pollers.\n";
			}

			process_poller_output($rrdtool_pipe);

			/* end the process if the runtime exceeds MAX_POLLER_RUNTIME */
			if (($start + MAX_POLLER_RUNTIME) < time()) {
				rrd_close($rrdtool_pipe);
				cacti_log("Maximum runtime of " . MAX_POLLER_RUNTIME . " seconds exceeded. Exiting.", true, "POLLER");
				exit;
			}

			sleep(1);
			$loop_count++;
		}
	}

	rrd_close($rrdtool_pipe);

	/* process poller commands */
	$poller_commands = db_fetch_assoc("select
		poller_command.action,
		poller_command.command
		from poller_command
		where poller_command.poller_id=0");

	$last_host_id = 0;
	$first_host = true;
	$recached_hosts = 0;

	if (sizeof($poller_commands) > 0) {
		foreach ($poller_commands as $command) {
			switch ($command["action"]) {
			case POLLER_COMMAND_REINDEX:
				list($host_id, $data_query_id) = explode(":", $command["command"]);

				if ($last_host_id != $host_id) {
					$last_host_id = $host_id;
					$first_host = true;
					$recached_hosts = $recached_hosts + 1;
				} else {
					$first_host = false;
				}

				if ($first_host) {
					cacti_log("Host[$host_id] WARNING: Recache Event Detected for Host", true, "POLLER");
				}

				if (read_config_option("log_verbosity") == POLLER_VERBOSITY_DEBUG) {
					cacti_log("Host[$host_id] RECACHE: Re-cache for Host, data query #$data_query_id", true, "POLLER");
				}

				run_data_query($host_id, $data_query_id);

				if (read_config_option("log_verbosity") == POLLER_VERBOSITY_DEBUG) {
					cacti_log("Host[$host_id] RECACHE: Re-cache successful.", true, "POLLER");
				}
			}
		}

		db_execute("delete from poller_command where poller_id=0");

		/* take time and log performance data */
		list($micro,$seconds) = split(" ", microtime());
		$recache = $seconds + $micro;

		cacti_log(sprintf("STATS: " .
			"Time: %01.4f s, " .
			"Hosts Recached: %s",
			round($recache - $end,4),
			$recached_hosts),
			true,"RECACHE");
	}

	/* graph export */
	graph_export();

	if ($method == "cactid") {
		chdir(read_config_option("path_webroot"));
	}

	db_execute("truncate table poller_output");
}else{
	print "There are no items in your poller cache or polling is disabled. Make sure you have at least one data source created. If you do, go to 'Utilities', and select 'Clear Poller Cache'.\n";
}
// End Mainline Processing

?>