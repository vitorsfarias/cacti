<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2010 The Cacti Group                                 |
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

/* exec_poll - executes a command and returns its output
   @param $command - the command to execute
   @returns - the output of $command after execution */
function exec_poll($command) {
	global $config;

	if (function_exists("popen")) {
		if (CACTI_SERVER_OS == "unix") {
			$fp = popen($command, "r");
		}else{
			$fp = popen($command, "rb");
		}

		/* return if the popen command was not successfull */
		if ($fp == 0) {
			cacti_log("WARNING; Problem with POPEN command.");
			return "U";
		}

		$output = fgets($fp, 4096);

		pclose($fp);
	}else{
		$output = `$command`;
	}

	return $output;
}

/* exec_poll_php - sends a command to the php script server and returns the
     output
   @param $command - the command to send to the php script server
   @param $using_proc_function - whether or not this version of php is making use
     of the proc_open() and proc_close() functions (php 4.3+)
   @param $pipes - the array of r/w pipes returned from proc_open()
   @param $proc_fd - the file descriptor returned from proc_open()
   @returns - the output of $command after execution against the php script
     server */
function exec_poll_php($command, $using_proc_function, $pipes, $proc_fd) {
	global $config;
	/* execute using php process */
	if ($using_proc_function == 1) {
		if (is_resource($proc_fd)) {
			/* $pipes now looks like this:
			 * 0 => writeable handle connected to child stdin
			 * 1 => readable handle connected to child stdout
			 * 2 => any error output will be sent to child stderr */

			/* send command to the php server */
			fwrite($pipes[0], $command . "\r\n");

			$output = fgets($pipes[1], 4096);

			if (substr_count($output, "ERROR") > 0) {
				$output = "U";
			}
		}
	/* execute the old fashion way */
	}else{
		/* formulate command */
		$command = read_config_option("path_php_binary") . " " . $command;

		if (function_exists("popen")) {
			if (CACTI_SERVER_OS == "unix")  {
				$fp = popen($command, "r");
			}else{
				$fp = popen($command, "rb");
			}

			/* return if the popen command was not successfull */
			if ($fp == 0) {
				cacti_log("WARNING; Problem with POPEN command.");
				return "U";
			}

			$output = fgets($fp, 4096);

			pclose($fp);
		}else{
			$output = `$command`;
		}
	}

	return $output;
}

/* exec_background - executes a program in the background so that php can continue
     to execute code in the foreground
   @param $filename - the full pathname to the script to execute
   @param $args - any additional arguments that must be passed onto the executable */
function exec_background($filename, $args = "") {
	global $config;

	if (read_config_option("log_verbosity") == POLLER_VERBOSITY_DEBUG) {
		cacti_log("DEBUG: About to Spawn a Remote Process [CMD: $filename, ARGS: $args]", true, "POLLER");
	}

	if (file_exists($filename)) {
		if (CACTI_SERVER_OS == "win32") {
			pclose(popen("start \"Cactiplus\" /I \"" . $filename . "\" " . $args, "r"));
		}else{
			exec($filename . " " . $args . " > /dev/null &");
		}
	}elseif (file_exists_2gb($filename)) {
		exec($filename . " " . $args . " > /dev/null &");
	}
}

/* file_exists_2gb - fail safe version of the file exists function to correct
     for errors in certain versions of php.
   @param $filename - the name of the file to be tested. */
function file_exists_2gb($filename) {
	global $config;

	if (CACTI_SERVER_OS != "win32") {
		system("test -f $filename", $rval);
		return ($rval == 0);
	}else{
		return 0;
	}
}

/* update_reindex_cache - builds a cache that is used by the poller to determine if the
     indexes for a particular data query/device have changed
   @param $device_id - the id of the device to which the data query belongs
   @param $data_query_id - the id of the data query to rebuild the reindex cache for */
function update_reindex_cache($device_id, $data_query_id) {
	global $config;

	require_once(CACTI_BASE_PATH . "/include/data_query/data_query_constants.php");
	require_once(CACTI_BASE_PATH . "/include/data_input/data_input_constants.php");
	include_once(CACTI_BASE_PATH . "/lib/data_query.php");
	include_once(CACTI_BASE_PATH . "/lib/snmp.php");

	/* will be used to keep track of sql statements to execute later on */
	$recache_stack = array();

	$device            = db_fetch_row("select hostname, snmp_community, snmp_version, snmp_username, snmp_password, snmp_auth_protocol, snmp_priv_passphrase, snmp_priv_protocol, snmp_context, snmp_port, snmp_timeout from device where id=$device_id");
	$data_query      = db_fetch_row("select reindex_method, sort_field from device_snmp_query where device_id=$device_id and snmp_query_id=$data_query_id");
	$data_query_type = db_fetch_cell("select data_input.type_id from (data_input,snmp_query) where data_input.id=snmp_query.data_input_id and snmp_query.id=$data_query_id");
	$data_query_xml  = get_data_query_array($data_query_id);

	switch ($data_query["reindex_method"]) {
		case DATA_QUERY_AUTOINDEX_NONE:
			break;
		case DATA_QUERY_AUTOINDEX_BACKWARDS_UPTIME:
			/* the uptime backwards method requires snmp, so make sure snmp is actually enabled
			 * on this device first */
			if ($device["snmp_community"] != "") {
				if (isset($data_query_xml["oid_uptime"])) {
					$oid_uptime = $data_query_xml["oid_uptime"];
				}elseif (isset($data_query_xml["uptime_oid"])) {
					$oid_uptime = $data_query_xml["uptime_oid"];
				}else{
					$oid_uptime = ".1.3.6.1.2.1.1.3.0";
				}

				$assert_value = cacti_snmp_get($device["hostname"],
					$device["snmp_community"],
					$oid_uptime,
					$device["snmp_version"],
					$device["snmp_username"],
					$device["snmp_password"],
					$device["snmp_auth_protocol"],
					$device["snmp_priv_passphrase"],
					$device["snmp_priv_protocol"],
					$device["snmp_context"],
					$device["snmp_port"],
					$device["snmp_timeout"],
					SNMP_POLLER);

				$recache_stack[] = "('$device_id', '$data_query_id', '0', '<', '$assert_value', '$oid_uptime', '1')";
			}

			break;
		case DATA_QUERY_AUTOINDEX_INDEX_COUNT_CHANGE:
			/* this method requires that some command/oid can be used to determine the
			 * current number of indexes in the data query */
			$assert_value = sizeof(db_fetch_assoc("select snmp_index from device_snmp_cache where device_id=$device_id and snmp_query_id=$data_query_id group by snmp_index"));

			if ($data_query_type == DATA_INPUT_TYPE_SNMP_QUERY) {
				if (isset($data_query_xml["oid_num_indexes"])) {
					$recache_stack[] = "($device_id, $data_query_id, 0, '=', '$assert_value', '" . $data_query_xml["oid_num_indexes"] . "', '1')";
				}
			}else if ($data_query_type == DATA_INPUT_TYPE_SCRIPT_QUERY) {
				if (isset($data_query_xml["arg_num_indexes"])) {
					$recache_stack[] = "($device_id, $data_query_id, 1, '=', '$assert_value', '" . get_script_query_path((isset($data_query_xml["arg_prepend"]) ? $data_query_xml["arg_prepend"] . " ": "") . $data_query_xml["arg_num_indexes"], $data_query_xml["script_path"], $device_id) . "', '1')";
				}
			}

			break;
		case DATA_QUERY_AUTOINDEX_VALUE_CHANGE:
			/* this method uses the value of the index OID to determine if a re-index is required */
			$assert_value = db_fetch_cell("select assert_value from poller_reindex where device_id=$device_id and data_query_id=$data_query_id");

			if ($data_query_type == DATA_INPUT_TYPE_SNMP_QUERY) {
				if (isset($data_query_xml["oid_num_indexes"])) {
					$recache_stack[] = "($device_id, $data_query_id, 0, '=', '$assert_value', '" . $data_query_xml["oid_num_indexes"] . "', '1')";
				}
			}else if ($data_query_type == DATA_INPUT_TYPE_SCRIPT_QUERY) {
				if (isset($data_query_xml["arg_num_indexes"])) {
					$recache_stack[] = "($device_id, $data_query_id, 1, '=', '$assert_value', '" . get_script_query_path((isset($data_query_xml["arg_prepend"]) ? $data_query_xml["arg_prepend"] . " ": "") . $data_query_xml["arg_num_indexes"], $data_query_xml["script_path"], $device_id) . "', '1')";
				}
			}

			break;
		case DATA_QUERY_AUTOINDEX_FIELD_VERIFICATION:
			$primary_indexes = db_fetch_assoc("select snmp_index,oid,field_value from device_snmp_cache where device_id=$device_id and snmp_query_id=$data_query_id and field_name='" . $data_query["sort_field"] . "'");

			if (sizeof($primary_indexes) > 0) {
				foreach ($primary_indexes as $index) {
					$assert_value = $index["field_value"];

					if ($data_query_type == DATA_INPUT_TYPE_SNMP_QUERY) {
						$recache_stack[] = "($device_id, $data_query_id, 0, '=', '$assert_value', '" . $data_query_xml["fields"]{$data_query["sort_field"]}["oid"] . "." . $index["snmp_index"] . "', '1')";
					}else if ($data_query_type == DATA_INPUT_TYPE_SCRIPT_QUERY) {
						$recache_stack[] = "('$device_id', '$data_query_id', '1', '=', '$assert_value', '" . get_script_query_path((isset($data_query_xml["arg_prepend"]) ? $data_query_xml["arg_prepend"] . " ": "") . $data_query_xml["arg_get"] . " " . $data_query_xml["fields"]{$data_query["sort_field"]}["query_name"] . " " . $index["snmp_index"], $data_query_xml["script_path"], $device_id) . "', '1')";
					}
				}
			}

			break;
	}

	if (sizeof($recache_stack)) {
		poller_update_poller_reindex_from_buffer($device_id, $data_query_id, $recache_stack);
	}
}

function poller_update_poller_reindex_from_buffer($device_id, $data_query_id, &$recache_stack) {
	/* set all fields present value to 0, to mark the outliers when we are all done */
	db_execute("UPDATE poller_reindex SET present=0 WHERE device_id='$device_id' AND data_query_id='$data_query_id'");

	/* setup the database call */
	$sql_prefix   = "INSERT INTO poller_reindex (device_id, data_query_id, action, op, assert_value, arg1, present) VALUES";
	$sql_suffix   = " ON DUPLICATE KEY UPDATE action=VALUES(action), op=VALUES(op), assert_value=VALUES(assert_value), present=VALUES(present)";

	/* use a reasonable insert buffer, the default is 1MByte */
	$max_packet   = 256000;

	/* setup somme defaults */
	$overhead     = strlen($sql_prefix) + strlen($sql_suffix);
	$buf_len      = 0;
	$buf_count    = 0;
	$buffer       = "";

	foreach($recache_stack AS $record) {
		if ($buf_count == 0) {
			$delim = " ";
		} else {
			$delim = ", ";
		}

		$buffer .= $delim . $record;

		$buf_len += strlen($record);

		if (($overhead + $buf_len) > ($max_packet - 1024)) {
			db_execute($sql_prefix . $buffer . $sql_suffix);

			$buffer    = "";
			$buf_len   = 0;
			$buf_count = 0;
		} else {
			$buf_count++;
		}
	}

	if ($buf_count > 0) {
		db_execute($sql_prefix . $buffer . $sql_suffix);
	}

	/* remove stale records from the poller reindex */
	db_execute("DELETE FROM poller_reindex WHERE device_id='$device_id' AND data_query_id='$data_query_id' AND present='0'");
}

/* process_poller_output - grabs data from the 'poller_output' table and feeds the *completed*
     results to RRDTool for processing
  @param $rrdtool_pipe - the array of pipes containing the file descriptor for rrdtool
  @param $remainder - don't use LIMIT if TRUE */
function process_poller_output(&$rrdtool_pipe, $remainder = FALSE) {
	global $config;

	include_once(CACTI_BASE_PATH . "/lib/rrd.php");

	/* let's count the number of rrd files we processed */
	$rrds_processed = 0;

	if ($remainder) {
		$limit = "";
	}else{
		$limit = "LIMIT 10000";
	}

	/* create/update the rrd files */
	$results = db_fetch_assoc("select
		poller_output.output,
		poller_output.time,
		poller_output.local_data_id,
		poller_item.rrd_path,
		poller_item.rrd_name,
		poller_item.rrd_num
		from (poller_output,poller_item)
		where (poller_output.local_data_id=poller_item.local_data_id and poller_output.rrd_name=poller_item.rrd_name)
		$limit");

	if (sizeof($results) > 0) {
		/* create an array keyed off of each .rrd file */
		foreach ($results as $item) {
			/* trim the default characters, but add single and double quotes */
			$value = trim($item["output"], " \r\n\t\x0B\0\"'");
			$unix_time = strtotime($item["time"]);

			$rrd_update_array{$item["rrd_path"]}["local_data_id"] = $item["local_data_id"];

			/* single one value output */
			if ((is_numeric($value)) || ($value == "U")) {
				$rrd_update_array{$item["rrd_path"]}["times"][$unix_time]{$item["rrd_name"]} = $value;
			/* special case of one value output: hexadecimal to decimal conversion */
			}elseif (is_hexadecimal($value)) {
				/* attempt to accomodate 32bit and 64bit systems */
				$value = str_replace(' ', '', $value);
				if (strlen($value) <= 8 || ((2147483647+1) == intval(2147483647+1))) {
					$rrd_update_array{$item["rrd_path"]}["times"][$unix_time]{$item["rrd_name"]} = hexdec($value);
				}elseif (function_exists("bcpow")) {
					$dec = 0;
					for ($i = 1; $i <= $vallen; $i++) {
						$dec = bcadd($dec, bcmul(strval(hexdec($value[$i - 1])), bcpow('16', strval($vallen - $i))));
					}
					$rrd_update_array{$item["rrd_path"]}["times"][$unix_time]{$item["rrd_name"]} = $dec;
				}else{
					$rrd_update_array{$item["rrd_path"]}["times"][$unix_time]{$item["rrd_name"]} = "U";
				}
			/* multiple value output */
			}else{
				$values = explode(" ", $value);

				$rrd_field_names = array_rekey(db_fetch_assoc("select
					data_template_rrd.data_source_name,
					data_input_fields.data_name
					from (data_template_rrd,data_input_fields)
					where data_template_rrd.data_input_field_id=data_input_fields.id
					and data_template_rrd.local_data_id=" . $item["local_data_id"]), "data_name", "data_source_name");

				if (sizeof($values)) {
				foreach($values as $value) {
					$matches = explode(":", $value);

					if (sizeof($matches) == 2) {
						if (isset($rrd_field_names{$matches[0]})) {
							if (read_config_option("log_verbosity") == POLLER_VERBOSITY_DEBUG) {
								cacti_log("Parsed MULTI output field '" . $matches[0] . ":" . $matches[1] . "' [map " . $matches[0] . "->" . $rrd_field_names{$matches[0]} . "]" , true, "POLLER");
							}

							$rrd_update_array{$item["rrd_path"]}["times"][$unix_time]{$rrd_field_names{$matches[0]}} = $matches[1];
						}
					}
				}
				}
			}

			/* fallback values */
			if ((!isset($rrd_update_array{$item["rrd_path"]}["times"][$unix_time])) && ($item["rrd_name"] != "")) {
				$rrd_update_array{$item["rrd_path"]}["times"][$unix_time]{$item["rrd_name"]} = "U";
			}else if ((!isset($rrd_update_array{$item["rrd_path"]}["times"][$unix_time])) && ($item["rrd_name"] == "")) {
				unset($rrd_update_array{$item["rrd_path"]});
			}
		}

		/* make sure each .rrd file has complete data */
		reset($results);
		$sql_wheres = array();
		foreach ($results as $item) {
			$unix_time = strtotime($item["time"]);

			if (isset($rrd_update_array{$item["rrd_path"]}["times"][$unix_time])) {
				if ($item["rrd_num"] <= sizeof($rrd_update_array{$item["rrd_path"]}["times"][$unix_time])) {
					$sql_wheres[] = " ( local_data_id='{$item['local_data_id']}' AND rrd_name='{$item['rrd_name']}' AND time='{$item['time']}' ) ";
					if (count($sql_wheres) > 100) {
						db_execute("DELETE FROM poller_output WHERE " . implode(" OR ", $sql_wheres));
						$sql_wheres = array();
					}
				}else{
					unset($rrd_update_array{$item["rrd_path"]}["times"][$unix_time]);
				}
			}
		}
		if (count($sql_wheres) > 100) {
			db_execute("DELETE FROM poller_output WHERE " . implode(" OR ", $sql_wheres));
		}
		api_plugin_hook_function('poller_output', $rrd_update_array);

		if (api_plugin_hook_function('poller_on_demand', $results)) {
			$rrds_processed = rrdtool_function_update($rrd_update_array, $rrdtool_pipe);
		}
	}

	return $rrds_processed;
}
