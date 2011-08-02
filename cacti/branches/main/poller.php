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

/* tick use required as of PHP 4.3.0 to accomodate signal handling */
declare(ticks = 1);

function sig_handler($signo) {
	switch ($signo) {
		case SIGTERM:
		case SIGINT:
			cacti_log("WARNING: Cacti Master Poller process terminated by user", TRUE);

			$running_processes = db_fetch_assoc("SELECT * FROM poller_time WHERE end_time='0000-00-00 00:00:00'");

			if (sizeof($running_processes)) {
			foreach($running_processes as $process) {
				if (function_exists("posix_kill")) {
					cacti_log("WARNING: Termination poller process with pid '" . $process["pid"] . "'", TRUE, "POLLER");
					posix_kill($process["pid"], SIGTERM);
				}
			}
			}

			db_execute("TRUNCATE TABLE poller_time");

			exit;
			break;
		default:
			/* ignore all other signals */
	}
}

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

/* we are not talking to the browser */
$no_http_headers = true;

/* start initialization section */
include(dirname(__FILE__) . "/include/global.php");
include_once(CACTI_BASE_PATH . "/include/poller/poller_arrays.php");
include_once(CACTI_BASE_PATH . "/lib/poller.php");
include_once(CACTI_BASE_PATH . "/lib/data_query.php");
include_once(CACTI_BASE_PATH . "/lib/graph_export.php");
include_once(CACTI_BASE_PATH . "/lib/rrd.php");

/* initialize some variables */
$force     = FALSE;
$debug     = FALSE;
$poller_id = 1;

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

if (sizeof($parms)) {
foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "-d":
	case "--debug":
		$debug = TRUE;
		set_config_option('log_verbosity', 5, true);

		break;
	case "--force":
		$force = TRUE;

		break;
	case "--poller":
		$poller_id = $value;

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

/* install signal handlers for UNIX only */
if (function_exists("pcntl_signal")) {
	pcntl_signal(SIGTERM, "sig_handler");
	pcntl_signal(SIGINT, "sig_handler");
}

plugin_hook('poller_top');

/* record the start time */
list($micro,$seconds) = explode(" ", microtime());
$poller_start         = $seconds + $micro;
$overhead_time = 0;

/* get number of polling items from the database */
$poller_interval = read_config_option("poller_interval");

/* retreive the last time the poller ran */
if ($poller_id == 1) {
	$poller_lastrun = read_config_option('poller_lastrun');
}else{
	$poller_lastrun = read_config_option('poller_lastrun_$poller_id');
}

/* get the current cron interval from the database */
$cron_interval = read_config_option("cron_interval");

if ($cron_interval != 60) {
	$cron_interval = 300;
}

/* see if the user wishes to use process leveling */
$process_leveling = read_config_option("process_leveling");

/* retreive the number of concurrent process settings */
$concurrent_processes = read_config_option("concurrent_processes");

$sql_where = ($poller_id == 1 ? "" : " WHERE poller_id=$poller_id ");
/* assume a scheduled task of either 60 or 300 seconds */
if (isset($poller_interval)) {
	$poller_runs       = $cron_interval / $poller_interval;
	$sql_where = (strlen($sql_where) == 0 ? " WHERE " : " AND ") . " rrd_next_step<=0 ";

	define("MAX_POLLER_RUNTIME", $poller_runs * $poller_interval - 2);
}else{
	$poller_runs       = 1;
	define("MAX_POLLER_RUNTIME", 298);
}

$num_polling_items = db_fetch_cell("SELECT COUNT(*) FROM poller_item $sql_where");
if (isset($concurrent_processes) && $concurrent_processes > 1) {
	$items_perdevice     = array_rekey(db_fetch_assoc("SELECT device_id, COUNT(*) AS data_sources " .
			"FROM poller_item " .
			$sql_where . " " .
			"GROUP BY device_id " .
			"ORDER BY device_id"), "device_id", "data_sources");
}

if (isset($items_perhost) && sizeof($items_perhost)) {
	$items_per_process   = floor($num_polling_items / $concurrent_processes);

	if ($items_per_process == 0) {
		$process_leveling = "off";
	}
}else{
	$process_leveling    = "off";
}

/* some text formatting for platform specific vocabulary */
if (CACTI_SERVER_OS == "unix") {
	$task_type = "Cron";
}else{
	$task_type = "Scheduled Task";
}

if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
	$poller_seconds_sincerun = "never";
	if (isset($poller_lastrun)) {
		$poller_seconds_sincerun = $seconds - $poller_lastrun;
	}

	cacti_log("NOTE: Poller ID: '$poller_id', Poller Int: '$poller_interval', $task_type Int: '$cron_interval', Time Since Last: '$poller_seconds_sincerun', Max Runtime '" . MAX_POLLER_RUNTIME. "', Poller Runs: '$poller_runs'", TRUE, "POLLER");;
}

/* our cron can run at either 1 or 5 minute intervals */
if ($poller_interval <= 60) {
	$min_period = "60";
}else{
	$min_period = "300";
}

/* get to see if we are polling faster than reported by the settings, if so, exit */
if ((isset($poller_lastrun) && isset($poller_interval) && $poller_lastrun > 0) && (!$force)) {
	/* give the user some flexibility to run a little moe often */
	if ((($seconds - $poller_lastrun)*1.3) < MAX_POLLER_RUNTIME) {
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
			cacti_log("NOTE: $task_type is configured to run too often!  The Poller ID: '$poller_id', Poller Int: '$poller_interval' seconds, with a minimum $task_type period of '$min_period' seconds, but only " . ($seconds - $poller_lastrun) . ' seconds have passed since the poller last ran.', true, 'POLLER');
		}
		exit;
	}
}

/* check to see whether we have the poller interval set lower than the poller is actually ran, if so, issue a warning */
if ((($seconds - $poller_lastrun - 5) > MAX_POLLER_RUNTIME) && ($poller_lastrun > 0)) {
	cacti_log("WARNING: $task_type is out of sync with the Poller Interval!  The Poller ID: '$poller_id', Poller Int: '$poller_interval' seconds, with a maximum of a '300' second $task_type, but " . ($seconds - $poller_lastrun) . ' seconds have passed since the last poll!', true, 'POLLER');
}

if ($poller_id == 1) {
	db_execute("REPLACE INTO settings (name,value) VALUES ('poller_lastrun'," . $seconds . ')');
}else{
	db_execute("REPLACE INTO settings (name,value) VALUES ('poller_lastrun_$poller_id'," . $seconds . ')');
}

/* let PHP only run 1 second longer than the max runtime, plus the poller needs lot's of memory */
ini_set("max_execution_time", MAX_POLLER_RUNTIME + 1);
ini_set("memory_limit", "512M");

$poller_runs_completed = 0;
$poller_items_total    = 0;
$polling_devices       = array_merge(array(0 => array("id" => "0")), db_fetch_assoc("SELECT id FROM device WHERE disabled = '' " . ($poller_id == 1 ? "" : "AND poller_id=$poller_id ") . " ORDER BY id"));

while ($poller_runs_completed < $poller_runs) {
	/* record the start time for this loop */
	list($micro,$seconds) = explode(" ", microtime());
	$loop_start = $seconds + $micro;

	/* calculate overhead time */
	if ($overhead_time == 0) {
		$overhead_time = $loop_start - $poller_start;
	}

	/* initialize counters for script file handling */
	$device_count = 1;

	/* initialize file creation flags */
	$change_proc = false;

	/* initialize file and device count pointers */
	$process_number = 0;
	$first_device   = 0;
	$last_device    = 0;

	/* update web paths for the poller */
	if ($poller_id == 1) {
		db_execute("REPLACE INTO settings (name,value) VALUES ('path_webroot','" . addslashes((CACTI_SERVER_OS == "win32") ? strtr(strtolower(substr(dirname(__FILE__), 0, 1)) . substr(dirname(__FILE__), 1),"\\", "/") : dirname(__FILE__)) . "')");
	}

	/* obtain some defaults from the database */
	$poller      = read_config_option("poller_type");
	$max_threads = read_config_option("max_threads");

	/* initialize poller_time and poller_output tables, check poller_output for issues */
	$running_processes = db_fetch_cell("SELECT count(*) FROM poller_time WHERE poller_id=$poller_id AND end_time='0000-00-00 00:00:00'");
	if ($running_processes) {
		cacti_log("WARNING: There are '$running_processes' detected as overrunning a polling process, please investigate", TRUE, "POLLER");
	}
	db_execute("DELETE FROM poller_time WHERE poller_id=$poller_id");

	$issues_limit = 20;
	$issues = db_fetch_assoc("SELECT local_data_id, rrd_name FROM poller_output WHERE poller_id=$poller_id LIMIT " . ($issues_limit + 1));
	
	if (sizeof($issues)) {
		$issue_list = "";
		$count = 0;
		foreach($issues as $issue) {
			if ($count > $issues_limit) {
				break;
			}
			if ($count == 0) {
				$issue_list .= $issue["rrd_name"] . "(DS[" . $issue["local_data_id"] . "])";
			}else{
				$issue_list .= ", " . $issue["rrd_name"] . "(DS[" . $issue["local_data_id"] . "])";
			}
			$count++;
		}

		if ($count > $issues_limit) {
			$issue_list .= ", Additional Issues Remain.  Only showing first $issues_limit";
		}

		cacti_log("WARNING: Poller Output Table not Empty.  Poller ID: '$poller_id', Issues: '$count', Data Sources: $issue_list", FALSE, "POLLER");
		db_execute("DELETE FROM poller_output WHERE poller_id=$poller_id");
	}

	/* mainline */
	if (read_config_option("poller_enabled") == CHECKED) {
		/* determine the number of devices to process per file */
		$devices_per_process = ceil(sizeof($polling_devices) / $concurrent_processes );

		$items_launched    = 0;

		/* exit poller if spine is selected and file does not exist */
		if (($poller == POLLER_SPINE) && (!file_exists(read_config_option("path_spine")))) {
			cacti_log("ERROR: The path: " . read_config_option("path_spine") . " is invalid.  Can not continue", true, "POLLER");
			exit;
		}

		/* Determine Command Name */
		if ($poller == POLLER_SPINE) {
			$command_string = cacti_escapeshellcmd(read_config_option("path_spine"));
			$extra_args     = "";
			$method         = "spine";
			$total_procs    = $concurrent_processes * $max_threads;
			chdir(dirname(read_config_option("path_spine")));
		}else if (CACTI_SERVER_OS == "unix") {
			$command_string = cacti_escapeshellcmd(read_config_option("path_php_binary"));
			$extra_args     = "-q \"" . CACTI_BASE_PATH . "/cmd.php\"";
			$method         = "cmd.php";
			$total_procs    = $concurrent_processes;
		}else{
			$command_string = cacti_escapeshellcmd(read_config_option("path_php_binary"));
			$extra_args     = "-q \"" . strtolower(CACTI_BASE_PATH . "/cmd.php\"");
			$method         = "cmd.php";
			$total_procs    = $concurrent_processes;
		}

		/* add the poller id for the various collectors */
		$extra_args .= " --poller=$poller_id";
		$extra_args = plugin_hook_function('poller_command_args', $extra_args);

		/* Populate each execution file with appropriate information */
		foreach ($polling_devices as $item) {
			if ($device_count == 1) {
				$first_device = $item["id"];
			}

			if ($process_leveling != CHECKED) {
				if ($device_count == $devices_per_process) {
					$last_device    = $item["id"];
					$change_proc  = true;
				}
			}else{
				if (isset($items_perdevice[$item["id"]])) {
					$items_launched += $items_perdevice[$item["id"]];
				}

				if (($items_launched >= $items_per_process) ||
					(sizeof($items_perdevice) == $concurrent_processes)) {
					$last_device      = $item["id"];
					/* if this is the dummy entry for externally updated data sources 
					 * that are not related to any host (host id = 0), do NOT change_proc */
					$change_proc    = ($item["id"] == 0 ? false : true);
					$items_launched = 0;
				}
			}

			$device_count ++;

			if ($change_proc) {
				exec_background($command_string, "$extra_args --first=$first_device --last=$last_device" . ($debug ? " --debug":""));
				usleep(100000);

				$device_count   = 1;
				$change_proc  = false;
				$first_device   = 0;
				$last_device    = 0;

				$process_number++;
			} /* end change_process */
		} /* end for each */

		/* launch the last process */
		if ($device_count > 1) {
			$last_device = $item["id"];

			exec_background($command_string, "$extra_args --first=$first_device --last=$last_device" . ($debug ? " --debug":""));
			usleep(100000);

			$process_number++;
		}

		/* insert the current date/time for graphs */
		if ($poller_id == 1) {
			db_execute("REPLACE INTO settings (name,value) VALUES ('date',NOW())");
		}

		if ($poller == POLLER_CMD) {
			$max_threads = "N/A";
		}

		/* open a pipe to rrdtool for writing */
		$rrdtool_pipe = rrd_init();

		$rrds_processed = 0;
		while (1) {
			$finished_processes = db_fetch_cell("SELECT count(*) FROM poller_time WHERE poller_id=$poller_id AND end_time>'0000-00-00 00:00:00'");

			if ($finished_processes >= $started_processes) {
				$rrds_processed = $rrds_processed + process_poller_output($rrdtool_pipe, TRUE);

				log_cacti_stats($loop_start, $method, $concurrent_processes, $max_threads,
					sizeof($polling_devices), $devices_per_process, $num_polling_items, $rrds_processed);

				break;
			}else {
				if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
					print "Waiting on " . ($process_number - sizeof($polling_items)) . "/$process_number pollers.\n";
				}

				$mtb = microtime(TRUE);
				$rrds_processed = $rrds_processed + process_poller_output($rrdtool_pipe, TRUE);

				/* end the process if the runtime exceeds MAX_POLLER_RUNTIME */
				if (($poller_start + MAX_POLLER_RUNTIME) < time()) {
					cacti_log("Maximum runtime of " . MAX_POLLER_RUNTIME . " seconds exceeded. Exiting.", true, "POLLER");

					log_cacti_stats($loop_start, $method, $concurrent_processes, $max_threads,
						sizeof($polling_devices), $devices_per_process, $num_polling_items, $rrds_processed);

					break;
				}else{
					if((microtime(TRUE) - $mtb) < 1){
						sleep(1);
					}
				}
			}
		}

		rrd_close($rrdtool_pipe);

		/* process poller commands */
		if (db_fetch_cell("SELECT COUNT(*) FROM poller_command WHERE poller_id=$poller_id ") > 0) {
			$command_string = cacti_escapeshellcmd(read_config_option("path_php_binary"));
			$extra_args = "-q \"" . CACTI_BASE_PATH . "/poller_commands.php --poller=$poller_id " . ($debug ? " --debug":"");
			exec_background($command_string, "$extra_args");
		} else {
			/* no re-index or Rechache present on this run
			 * in case, we have more PCOMMANDS than recaching, this has to be moved to poller_commands.php
			 * but then we'll have to call it each time to make sure, stats are updated */
			if ($poller_id == 1) {
				db_execute("REPLACE INTO settings (name,value) VALUES ('stats_recache','RecacheTime:0.0 HostsRecached:0')");
			}else{
				db_execute("REPLACE INTO settings (name,value) VALUES ('stats_recache_$poller_id','RecacheTime:0.0 HostsRecached:0')");
			}
		}

		/* graph export */
		if ($poller_id == 1) {
			if ((read_config_option("export_type") != "disabled") &&
				(read_config_option("export_timing") != "disabled")) {
				$command_string = cacti_escapeshellcmd(read_config_option("path_php_binary"));
				$extra_args = "-q \"" . CACTI_BASE_PATH . "/poller_export.php\"" . ($debug ? " --debug":"");
				exec_background($command_string, "$extra_args");
			}
		}

		if ($method == "spine") {
			chdir(read_config_option("path_webroot"));
		}
	}else if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
		cacti_log("NOTE: There are no items in your poller for this polling cycle!", TRUE, "POLLER");
	}

	$poller_runs_completed++;

	/* record the start time for this loop */
	list($micro,$seconds) = explode(" ", microtime());
	$loop_end = $seconds + $micro;
	$loop_time = $loop_end - $loop_start;

	if ($loop_time < $poller_interval) {
		if ($poller_runs_completed == 1) {
			$sleep_time = $poller_interval - $loop_time - $overhead_time;
		}else{
			$sleep_time = $poller_interval -  $loop_time;
		}

		/* log some nice debug information */
		if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_DEBUG || $debug) {
			echo "Loop  Time is: " . round($loop_time, 2) . "\n";
			echo "Sleep Time is: " . round($sleep_time, 2) . "\n";
			echo "Total Time is: " . round($loop_end - $poller_start, 2) . "\n";
 		}

		/* sleep the appripriate amount of time */
		if ($poller_id == 1) {
			if ($poller_runs_completed < $poller_runs) {
				plugin_hook('poller_bottom');
				usleep($sleep_time * 1000000);
				plugin_hook('poller_top');
			}
		}
	}else if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM || $debug) {
		cacti_log("WARNING: Cacti Polling Cycle Exceeded Poller Interval by " . $loop_end-$loop_start-$poller_interval . " seconds", TRUE, "POLLER");
	}
}

function log_cacti_stats($loop_start, $method, $concurrent_processes, $max_threads, $num_devices,
	$devices_per_process, $num_polling_items, $rrds_processed) {
	global $poller_id;

	/* take time and log performance data */
	list($micro,$seconds) = explode(" ", microtime());
	$loop_end = $seconds + $micro;

	$cacti_stats = sprintf(
		"Poller:%s " .
		"Time:%01.4f " .
		"Method:%s " .
		"Processes:%s " .
		"Threads:%s " .
		"Hosts:%s " .
		"HostsPerProcess:%s " .
		"DataSources:%s " .
		"RRDsProcessed:%s",
		$poller_id,
		round($loop_end-$loop_start,4),
		$method,
		$concurrent_processes,
		$max_threads,
		$num_devices,
		$devices_per_process,
		$num_polling_items,
		$rrds_processed);

	cacti_log("STATS: " . $cacti_stats ,true,"SYSTEM");

	/* insert poller stats into the settings table */
	if ($poller_id == 1) {
		db_execute("REPLACE INTO settings (name,value) VALUES ('stats_poller','$cacti_stats')");
	}else{
		db_execute("REPLACE INTO settings (name,value) VALUES ('stats_poller_$poller_id','$cacti_stats')");
	}

	/* update the poller status */
	$stats = array_rekey(db_fetch_assoc("SELECT action, count(*) AS total 
		FROM poller_item 
		WHERE poller_id=$poller_id" . ($poller_id == 1 ? " OR poller_id=0":"") . " 
		GROUP BY action"), "action", "total");

	db_execute("UPDATE poller 
		SET last_update=NOW(), 
		snmp=" . (isset($stats["0"]) ? $stats["0"]:"0") . ",
		script=" . (isset($stats["1"]) ? $stats["1"]:"0") . ", 
		server=" . (isset($stats["2"]) ? $stats["2"]:"0") . ",
		total_time=" . round($loop_end-$loop_start,4) . "
		WHERE id=$poller_id");
}

function display_help() {
	echo "Cacti Poller Version " . CACTI_VERSION . ", Copyright 2004-2011 - The Cacti Group\n\n";
	echo "A simple command line utility to run the Cacti Poller.\n\n";
	echo "usage: poller.php [--poller=n] [--force] [--debug|-d]\n\n";
	echo "Options:\n";
	echo "    --poller=n     0, The poller id of this poller\n";
	echo "    --force        Override poller overrun detection and force a poller run\n";
	echo "    --debug|-d     Output debug information.  Similar to cacti's DEBUG logging level.\n\n";
}
