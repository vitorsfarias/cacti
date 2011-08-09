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
			cacti_log("WARNING: Cacti Poller process terminated by user", TRUE);

			/* record the process as having completed */
			record_cmdphp_done();

			exit;
			break;
		default:
			/* ignore all other signals */
	}
}

/* let the poller server know about cmd.php being finished */
function record_cmdphp_done($pid = "") {
	if ($pid == "") $pid = getmypid();

	db_execute("UPDATE poller_time SET end_time=NOW() WHERE pid=" . $pid);
}

/* let cacti processes know that a poller has started */
function record_cmdphp_started($poller_id) {
	db_execute("INSERT INTO poller_time (poller_id, pid, start_time, end_time) VALUES ($poller_id, " . getmypid() . ", NOW(), '0000-00-00 00:00:00')");
}

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$start = date("Y-n-d H:i:s"); // for runtime measurement

ini_set("max_execution_time", "0");
ini_set("memory_limit", "512M");

$no_http_headers = true;

include(dirname(__FILE__) . "/include/global.php");
require(CACTI_BASE_PATH . "/include/device/device_arrays.php");
require_once(CACTI_BASE_PATH . "/include/poller/poller_constants.php");
include_once(CACTI_BASE_PATH . "/lib/snmp.php");
include_once(CACTI_BASE_PATH . "/lib/poller.php");
include_once(CACTI_BASE_PATH . "/lib/rrd.php");
include_once(CACTI_BASE_PATH . "/lib/ping.php");
include_once(CACTI_BASE_PATH . "/lib/variables.php");

/* correct for a windows PHP bug. fixed in 5.2.0 */
if (CACTI_SERVER_OS == "win32") {
	/* check PHP versions first, we know 5.2.0 and above is fixed */
	if (version_compare("5.2.0", PHP_VERSION, ">=")) {
		$guess = substr(__FILE__,0,2);
		if ($guess == strtoupper($guess)) {
			$response = "ERROR: The PHP Script: CMD.PHP Must be started using the full path to the file and in lower case.  This is a PHP Bug!!!";
			print "\n";
			cacti_log($response,true);

			record_cmdphp_done();
			exit("-1");
		}
	}
}

/* install signal handlers for UNIX only */
if (function_exists("pcntl_signal")) {
	pcntl_signal(SIGTERM, "sig_handler");
	pcntl_signal(SIGINT, "sig_handler");
}

/* record the start time */
list($micro,$seconds) = explode(" ", microtime());
$start = $seconds + $micro;

/* initialize the polling items */
$polling_items = array();

/* determine how often the poller runs from settings */
$polling_interval = read_config_option("poller_interval");

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

/* initialize some variables */
$poller_id = 1;
$first     = 0;
$last      = 0;
$debug     = false;
$verbofity = "";
$stdout    = false;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "--poller":
		$poller_id = $value;
		input_validate_input_number($poller_id);
		break;
	case "-f":
	case "--first":
		$first = $value;
		input_validate_input_number($first);
		break;
	case "-l":
	case "--last":
		$last = $value;
		input_validate_input_number($last);
		break;
	case "--debug":
		$debug  = true;
		$stdout = true;
		set_config_option('log_verbosity', 5, true);
		break;
	case "--verbosity":
		$verbosity = $value;
		input_validate_input_number($verbosity);
		set_config_option('log_verbosity', $verbosity, true);
		break;
	case "--stdout":
		$stdout = true;
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

/* notify cacti processes that a poller is running */
record_cmdphp_started($poller_id);

/* check additional boundary conditions */
if ($first > $last) {
	cacti_log("ERROR: The first device id must be less than the second device id.", $stdout);

	/* record the process as having completed */
	record_cmdphp_done($poller_id);
	exit("-1");
}

if ($first == 0 && $last == 0) {
	$sql_where  = "WHERE poller_id=$poller_id" . ($poller_id == 1 ? " OR poller_id=0":"");
	$sqld_where = "WHERE poller_id=$poller_id" . ($poller_id == 1 ? " OR poller_id=0":"");
}else{
	$sql_where  = "WHERE (poller_id=$poller_id " . ($poller_id == 1 ? " OR poller_id=0":"") . ") AND id>=$first AND id<=$last";
	$sqld_where = "WHERE (poller_id=$poller_id " . ($poller_id == 1 ? " OR poller_id=0":"") . ") AND device_id>=$first AND device_id<=$last";
}

$devices = db_fetch_assoc("SELECT * " .
	"FROM device " .
	$sql_where . " " .
	"AND disabled = '' " .
	"ORDER by id");

$devices      = array_rekey($devices, "id", $device_struc);
$device_count = sizeof($devices);

if (isset($polling_interval)) {
	$polling_items = db_fetch_assoc("SELECT * " .
		"FROM poller_item " .
		$sqld_where . " " .
		"AND rrd_next_step<=0 " .
		"ORDER by device_id");

	$script_server_calls = db_fetch_cell("SELECT count(*) " .
		"FROM poller_item " .
		$sqld_where . " " .
		"AND action IN (" . POLLER_ACTION_SCRIPT_PHP . "," . POLLER_ACTION_SCRIPT_PHP_COUNT . ") " .
		"AND rrd_next_step<=0");

	/* setup next polling interval */
	db_execute("UPDATE poller_item " .
		"SET rrd_next_step=rrd_next_step-" . $polling_interval . " " .
		$sqld_where);

	db_execute("UPDATE poller_item " .
		"SET rrd_next_step=rrd_step-" . $polling_interval . " " .
		$sqld_where . " " .
		"AND rrd_next_step<0");
}else{
	$polling_items = db_fetch_assoc("SELECT * FROM poller_item " .
		$sqld_where . " " .
		"ORDER by device_id");

	$script_server_calls = db_fetch_cell("SELECT count(*) FROM poller_item " .
		$sqld_where . " " .
		"AND action IN (" . POLLER_ACTION_SCRIPT_PHP . "," . POLLER_ACTION_SCRIPT_PHP_COUNT . ")");
}

/* script server is required if either a script server script or a script server reindex is required */
$script_server_calls += db_fetch_cell("SELECT count(*) from poller_reindex WHERE action IN (" . POLLER_ACTION_SCRIPT_PHP . "," . POLLER_ACTION_SCRIPT_PHP_COUNT . ")");

if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_HIGH || $debug) {
	cacti_log("Script Server Calls detected: " . $script_server_calls, $stdout);
}

if ((sizeof($polling_items) > 0) && (read_config_option("poller_enabled") == CHECKED)) {
	$failure_type = "";
	$device_down    = false;
	$new_device     = true;
	$last_device    = '';
	$current_device = '';
	$poll_time    = 0;

	/* create new ping socket for device pinging */
	$ping = new Net_Ping;

	/* startup Cacti php polling server and include the include file for script processing */
	if ($script_server_calls > 0) {
		$cactides = array(
			0 => array("pipe", "r"), // stdin is a pipe that the child will read from
			1 => array("pipe", "w"), // stdout is a pipe that the child will write to
			2 => array("pipe", "w")  // stderr is a pipe to write to
			);

		$cactiphp = proc_open(cacti_escapeshellcmd(read_config_option("path_php_binary")) . " -q " . CACTI_BASE_PATH . "/script_server.php cmd", $cactides, $pipes);
		$output = fgets($pipes[1], 1024);
		if (substr_count($output, "Started") != 0) {
			if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_HIGH || $debug) {
				cacti_log("PHP Script Server Started Properly",$stdout);
			}
		}
	}

	foreach ($polling_items as $item) {
		$data_source  = $item["local_data_id"];
		$current_device = $item["device_id"];

		if ($current_device != $last_device) {
			if ($last_device != '') {
				/* record the device polling time */
				list($micro,$seconds) = explode(" ", microtime());
				$poll_time = ($seconds + $micro) - $poll_time;
				db_execute("UPDATE device SET polling_time='$poll_time' WHERE id='$last_device'");
			}

			$new_device = true;

			/* assume the device is up */
			$device_down = false;

			/* assume we don't have to spike prevent */
			$set_spike_kill = false;

			$device_update_time = date("Y-m-d H:i:s"); // for poller update time
		}

		$device_id = $item["device_id"];

		if (($new_device) && (!empty($device_id))) {
			/* record the start time to calculate device polling time */
			list($micro,$seconds) = explode(" ", microtime());
			$poll_time = $seconds + $micro;

			$ping->device = $item;
			$ping->port = $devices[$device_id]["ping_port"];

			/* perform the appropriate ping check of the device */
			if ($ping->ping($devices[$device_id]["availability_method"], $devices[$device_id]["ping_method"],
				$devices[$device_id]["ping_timeout"], $devices[$device_id]["ping_retries"])) {
				$device_down = false;
				update_device_status(DEVICE_UP, $device_id, $devices, $ping, $devices[$device_id]["availability_method"], $stdout);
			}else{
				$device_down = true;
				update_device_status(DEVICE_DOWN, $device_id, $devices, $ping, $devices[$device_id]["availability_method"], $stdout);
			}

			if (!$device_down) {
				/* do the reindex check for this device 
				 * this is NOT related to a specific polling_item nor data_source */
				$reindex = db_fetch_assoc("select
					poller_reindex.data_query_id,
					poller_reindex.action,
					poller_reindex.op,
					poller_reindex.assert_value,
					poller_reindex.arg1
					from poller_reindex
					where poller_reindex.device_id=" . $item["device_id"]);

				if ((sizeof($reindex) > 0) && (!$device_down)) {
					if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
						cacti_log("Host[$device_id] RECACHE: Processing " . sizeof($reindex) . " items in the auto reindex cache for '" . $item["hostname"] . "'.",$stdout);
					}

					foreach ($reindex as $index_item) {
						$assert_fail = false;

						/* do the check */
						switch ($index_item["action"]) {
						case POLLER_ACTION_SNMP: /* snmp */
							if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG || $debug) {
								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] OID " . $index_item["arg1"], $stdout);
							}
							$output = cacti_snmp_get($item["hostname"], $item["snmp_community"], $index_item["arg1"],
								$item["snmp_version"], $item["snmp_username"], $item["snmp_password"],
								$item["snmp_auth_protocol"], $item["snmp_priv_passphrase"], $item["snmp_priv_protocol"],
								$item["snmp_context"], $item["snmp_port"], $item["snmp_timeout"], read_config_option("snmp_retries"), SNMP_CMDPHP);
							if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug) {
								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] OID: " . $index_item["arg1"] . ", output: " . $output, $stdout);
							}
							break;

						case POLLER_ACTION_SCRIPT: /* script (popen) */
							if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG || $debug) {
								cacti_log("Host[$device_id]: RECACHE DQ[" . $index_item["data_query_id"] . "] Script: " . $index_item["arg1"], $stdout);
							}
							$output = trim(exec_poll($index_item["arg1"]));

							/* remove any quotes from string */
							$output = strip_quotes($output);

							if (!validate_result($output)) {
								if (strlen($output) > 20) {
									$strout = 20;
								} else {
									$strout = strlen($output);
								}

								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] Warning: Result from Script not valid.  Partial Result: " . substr($output, 0, $strout), $stdout);
							}

							if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug) {
								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] Script: " . $index_item["arg1"] . ", output: $output",$stdout);
							}
							break;

						case POLLER_ACTION_SCRIPT_PHP: /* script (php script server) */
							if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG || $debug) {
								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] Script Server " . $index_item["arg1"], $stdout);
							}

							$output = trim(str_replace("\n", "", exec_poll_php($index_item["arg1"], $pipes, $cactiphp)));

							/* remove any quotes from string */
							$output = strip_quotes($output);

							if (!validate_result($output)) {
								if (strlen($output) > 20) {
									$strout = 20;
								} else {
									$strout = strlen($output);
								}

								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] WARNING: Result from Script Server not valid.  Partial Result: " . substr($output, 0, $strout), $stdout);
							}

							if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug) {
								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] Script Server: " . $index_item["arg1"] . ", output: $output", $stdout);
							}

							break;
						case POLLER_ACTION_SNMP_COUNT: /* snmp; count items */
							if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG || $debug) {
								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] OID Count: " . $index_item["arg1"], $stdout);
							}
							$output = sizeof(cacti_snmp_walk($item["hostname"], $item["snmp_community"], $index_item["arg1"],
								$item["snmp_version"], $item["snmp_username"], $item["snmp_password"],
								$item["snmp_auth_protocol"], $item["snmp_priv_passphrase"], $item["snmp_priv_protocol"],
								$item["snmp_context"], $item["snmp_port"], $item["snmp_timeout"], read_config_option("snmp_retries"), SNMP_CMDPHP));
							if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug) {
								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] OID Count: " . $index_item["arg1"] . ", output: " . $output, $stdout);
							}

							break;
						case POLLER_ACTION_SCRIPT_COUNT: /* script (popen); count items */
							if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG || $debug) {
								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] Script Count: " . $index_item["arg1"], $stdout);
							}
							$script_index_array = exec_into_array($index_item["arg1"]);
							$output = sizeof($script_index_array);

							if (!validate_result($output)) {
								if (strlen($output) > 20) {
									$strout = 20;
								} else {
									$strout = strlen($output);
								}

								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] Warning: Result from Script not valid.  Partial Result: " . substr($output, 0, $strout), $stdout);
							}

							if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug) {
								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] Script Count: " . $index_item["arg1"] . ", output: $output",$stdout);
							}
							break;

						case POLLER_ACTION_SCRIPT_PHP_COUNT: /* script (php script server); count items */
							if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG || $debug) {
								cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] Script Server Count: " . $index_item["arg1"], $stdout);
							}

							/* fetch specified index */
							$output = 'U'; # TODO compatibility until option is correctly implemented
							cacti_log("Host[$device_id] DS[$data_source] *SKIPPING* Script Server: " . $item["arg1"] . " (arg_num_indexes required)", $stdout);
							#$output = trim(str_replace("\n", "", exec_poll_php($index_item["arg1"], $pipes, $cactiphp)));

							/* remove any quotes from string */
							#$output = strip_quotes($output);

							#if (!validate_result($output)) {
							#	if (strlen($output) > 20) {
							#		$strout = 20;
							#	} else {
							#		$strout = strlen($output);
							#	}

							#	cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] WARNING: Result from Script Server not valid.  Partial Result: " . substr($output, 0, $strout), $stdout);
							#}

							#if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM) {
							#	cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] Script Server Count: " . $index_item["arg1"] . ", output: $output", $stdout);
							#}

							break;
						default: /* invalid reindex option */
							cacti_log("Host[$device_id] RECACHE DQ[" . $index_item["data_query_id"] . "] ERROR: Invalid reindex option: " . $index_item["action"], $stdout);						
						}


						/* assert the result with the expected value in the db; recache if the assert fails */
						/* TODO: remove magic ":" from poller_command["command"]; this may interfere with scripts */
						if (($index_item["op"] == "=") && ($index_item["assert_value"] != trim($output))) {
							cacti_log("ASSERT: '" . $index_item["assert_value"] . "=" . trim($output) . "' failed. Recaching device '" . $item["hostname"] . "', data query #" . $index_item["data_query_id"], $stdout);
							db_execute("replace into poller_command (poller_id, time, action, command) values ($poller_id, NOW(), " . POLLER_COMMAND_REINDEX . ", '" . $item["device_id"] . ":" . $index_item["data_query_id"] . "')");
							$assert_fail = true;
						}else if (($index_item["op"] == ">") && ($index_item["assert_value"] < trim($output))) {
							cacti_log("ASSERT: '" . $index_item["assert_value"] . ">" . trim($output) . "' failed. Recaching device '" . $item["hostname"] . "', data query #" . $index_item["data_query_id"], $stdout);
							db_execute("replace into poller_command (poller_id, time, action, command) values ($poller_id, NOW(), " . POLLER_COMMAND_REINDEX . ", '" . $item["device_id"] . ":" . $index_item["data_query_id"] . "')");
							$assert_fail = true;
						}else if (($index_item["op"] == "<") && ($index_item["assert_value"] > trim($output))) {
							cacti_log("ASSERT: '" . $index_item["assert_value"] . "<" . trim($output) . "' failed. Recaching device '" . $item["hostname"] . "', data query #" . $index_item["data_query_id"], $stdout);
							db_execute("replace into poller_command (poller_id, time, action, command) values ($poller_id, NOW(), " . POLLER_COMMAND_REINDEX . ", '" . $item["device_id"] . ":" . $index_item["data_query_id"] . "')");
							$assert_fail = true;
						}

						/* update 'poller_reindex' with the correct information if:
						 * 1) the assert fails
						 * 2) the OP code is > or < meaning the current value could have changed without causing
						 *     the assert to fail */
						if (($assert_fail == true) || ($index_item["op"] == ">") || ($index_item["op"] == "<")) {
							db_execute("update poller_reindex set assert_value='$output' where device_id='$device_id' and data_query_id='" . $index_item["data_query_id"] . "' and arg1='" . $index_item["arg1"] . "'");

							/* spike kill logic */
							if (($assert_fail) &&
								(($index_item["op"] == "<") || ($index_item["arg1"] == ".1.3.6.1.2.1.1.3.0"))) {
								/* don't spike kill unless we are certain */
								if (!empty($output)) {
									$set_spike_kill = true;

									if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_DEBUG) {
										cacti_log("Host[$device_id] NOTICE: Spike Kill in Effect for '" . $item["hostname"] . "'.", $stdout);
									}
								}
							}
						}
					}
				}
			}

			$new_device = false;
			$last_device = $current_device;
		}

		if (!$device_down) {
			switch ($item["action"]) {
			case POLLER_ACTION_SNMP: /* snmp */
				if (($item["snmp_version"] == 0) || (($item["snmp_community"] == "") && ($item["snmp_version"] != 3))) {
					cacti_log("Host[$device_id] DS[$data_source] ERROR: Invalid SNMP Data Source.  Please either delete it from the database, or correct it.", $stdout);
					$output = "U";
				}else {
					$output = cacti_snmp_get($item["hostname"], $item["snmp_community"], $item["arg1"],
						$item["snmp_version"], $item["snmp_username"], $item["snmp_password"],
						$item["snmp_auth_protocol"], $item["snmp_priv_passphrase"], $item["snmp_priv_protocol"],
						$item["snmp_context"], $item["snmp_port"], $item["snmp_timeout"], read_config_option("snmp_retries"), SNMP_CMDPHP);

					/* remove any quotes from string */
					$output = strip_quotes($output);

					if (!validate_result($output)) {
						if (strlen($output) > 20) {
							$strout = 20;
						} else {
							$strout = strlen($output);
						}

						cacti_log("Host[$device_id] DS[$data_source] WARNING: Result from SNMP not valid.  Partial Result: " . substr($output, 0, $strout), $stdout);
						$output = "U";
					}
				}

				if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug) {
					cacti_log("Host[$device_id] DS[$data_source] SNMP: v" . $item["snmp_version"] . ": " . $item["hostname"] . ", dsname: " . $item["rrd_name"] . ", oid: " . $item["arg1"] . ", output: $output",$stdout);
				}

				break;
			case POLLER_ACTION_SCRIPT: /* script (popen) */
				$output = trim(exec_poll($item["arg1"]));

				/* remove any quotes from string */
				$output = strip_quotes($output);

				if (!validate_result($output)) {
					if (strlen($output) > 20) {
						$strout = 20;
					} else {
						$strout = strlen($output);
					}

					cacti_log("Host[$device_id] DS[$data_source] WARNING: Result from CMD not valid.  Partial Result: " . substr($output, 0, $strout), $stdout);
				}

				if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug) {
					cacti_log("Host[$device_id] DS[$data_source] CMD: " . $item["arg1"] . ", output: $output",$stdout);
				}

				break;
			case POLLER_ACTION_SCRIPT_PHP: /* script (php script server) */
				$output = trim(str_replace("\n", "", exec_poll_php($item["arg1"], $pipes, $cactiphp)));

				/* remove any quotes from string */
				$output = strip_quotes($output);

				if (!validate_result($output)) {
					if (strlen($output) > 20) {
						$strout = 20;
					} else {
						$strout = strlen($output);
					}

					cacti_log("Host[$device_id] DS[$data_source] WARNING: Result from SERVER not valid.  Partial Result: " . substr($output, 0, $strout), $stdout);
				}

				if (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM || $debug) {
					cacti_log("Host[$device_id] DS[$data_source] SERVER: " . $item["arg1"] . ", output: $output", $stdout);
				}

				break;
			default: /* invalid polling option */
				cacti_log("Host[$device_id] DS[$data_source] ERROR: Invalid polling option: " . $item["action"], $stdout);						
			} /* End Switch */

			if (isset($output)) {
				/* insert a U in place of the actual value if the snmp agent restarts */
				if (($set_spike_kill) && (!substr_count($output, ":"))) {
					db_execute("insert into poller_output (local_data_id, poller_id, rrd_name, time, output) values (" . $item["local_data_id"] . ", $poller_id, '" . $item["rrd_name"] . "','$device_update_time','" . addslashes("U") . "')");
				/* otherwise, just insert the value received from the poller */
				}else{
					db_execute("insert into poller_output (local_data_id, poller_id, rrd_name, time, output) values (" . $item["local_data_id"] . ", $poller_id, '" . $item["rrd_name"] . "', '$device_update_time', '" . addslashes($output) . "')");
				}
			}
		} /* Next Cache Item */
	} /* End foreach */

	/* record the device polling time of last device */
	if ($last_device != '') {
		list($micro,$seconds) = explode(" ", microtime());
		$poll_time = ($seconds + $micro) - $poll_time;
		db_execute("UPDATE device SET polling_time='$poll_time' WHERE id='$last_device'");
	}

	if ($script_server_calls > 0) {
		// close php server process
		fwrite($pipes[0], "quit\r\n");
		fclose($pipes[0]);
		fclose($pipes[1]);
		fclose($pipes[2]);

		$return_value = proc_close($cactiphp);
	}

	if (($stdout) || (read_config_option("log_verbosity") >= POLLER_VERBOSITY_MEDIUM)) {
		/* take time and log performance data */
		list($micro,$seconds) = explode(" ", microtime());
		$end = $seconds + $micro;

		if ($poller_id > 0) {
			cacti_log(sprintf("Poller: %s, Time: %01.4f s, " .
				"Theads: N/A, " .
				"Hosts: %s",
				$poller_id,
				round($end-$start,4),
				$device_count),$stdout);
		}else{
			cacti_log(sprintf("Time: %01.4f s, " .
				"Theads: N/A, " .
				"Hosts: %s",
				round($end-$start,4),
				$device_count),$stdout);
		}
	}
}else if (read_config_option('log_verbosity') >= POLLER_VERBOSITY_MEDIUM) {
	cacti_log("NOTE: There are no items in your poller for this polling cycle!", true, "POLLER");
}

/* record the process as having completed */
record_cmdphp_done();

function display_help() {
	echo "Cacti cmd.php Data Collector, Copyright 2007-2010 - The Cacti Group\n\n";
	echo "The slower, yet easier to use Data Collector for Cacti\n\n";
	echo "usage: cmd.php [--poller=n] [-f n | --first=n] [-l n | --last=n] [--verbosity=n] [--debug]\n\n";
	echo "Optional:\n";
	echo "   --poller=n        Defines which Cacti poller will be handling the polling of the device(s).\n";
	echo "   --verbosity=[1-5] Overrides the Database Value for verbosity allowing easy review of log details.\n";
	echo "   --debug           Prints debug to standard output only.\n";
	echo "    -f n | --first=n Defines the first device for this Data Collector to be polled.\n";
	echo "    -l n | --last=n  Defines the last device for this Data Collector to be polled.\n";
}
