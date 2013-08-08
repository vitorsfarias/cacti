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

/* Function naming conventions
 * rrdtool_*			utility functions
 * rrdtool_function_*	perform basic rrdtool functionality like create, update, fetch, ...
 * rrd_*				deal with rrd files and their structure
 * rrdgraph_*			deal with creating graphs. Graph export counts among them
 * rrdgraph_option_*	build global rrdtool graph option like font, x-grid, ...
 * get_*				fetch data from database and return the result set
 */

include_once(CACTI_INCLUDE_PATH . "/rrd/rrd_constants.php");


/** sanitize rrdtool command before execution
 * @param string $command - prepared rrdtool command
 */
function escape_command($command) {
	return $command;		# we escape every single argument now, no need for "special" escaping
	#return preg_replace("/(\\\$|`)/", "", $command); # current cacti code
	#return preg_replace((\\\$(?=\w+|\*|\@|\#|\?|\-|\\\$|\!|\_|[0-9]|\(.*\))|`(?=.*(?=`)))","$2", $command);  #suggested by ldevantier to allow for a single $
}


/** initialize rrdtool stream
 * @param bool $output_to_term - output destination terminal?
 * @return resource - rrdtool file descriptor
 */
function rrd_init($output_to_term = TRUE) {
	/* set the rrdtool default font */
	if (read_config_option("path_rrdtool_default_font")) {
		putenv("RRD_DEFAULT_FONT=" . read_config_option("path_rrdtool_default_font"));
	}

	if ($output_to_term) {
		$command = read_config_option("path_rrdtool") . " - ";
	}else{
		if (CACTI_SERVER_OS == "win32") {
			$command = read_config_option("path_rrdtool") . " - > nul";
		}else{
			$command = read_config_option("path_rrdtool") . " - > /dev/null 2>&1";
		}
	}

	return popen($command, "w");
}


/** close rrdtool stream
 * @param resource $rrdtool_pipe - rrdtool file descriptor
 */
function rrd_close($rrdtool_pipe) {
	/* close the rrdtool file descriptor */
	if (is_resource($rrdtool_pipe)) {
		pclose($rrdtool_pipe);
	}
}


/** execute rrdtool command via stream
 * @param string $command_line - rrdtool command line
 * @param bool $log_to_stdout - logging to stdout
 * @param int $output_flag - type of output requested
 * @param resource $rrdtool_pipe - pipe used for rrdtool command
 * @param string $logopt - log option
 */
function rrdtool_execute($command_line, $log_to_stdout, $output_flag, &$rrdtool_pipe = "", $logopt = "WEBLOG") {
	static $last_command;

	if (!is_numeric($output_flag)) {
		$output_flag = RRDTOOL_OUTPUT_STDOUT;
	}

	/* WIN32: before sending this command off to rrdtool, get rid
	of all of the '\' characters. Unix does not care; win32 does.
	Also make sure to replace all of the fancy \'s at the end of the line,
	but make sure not to get rid of the "\n"'s that are supposed to be
	in there (text format) */
	$command_line = str_replace("\\\n", " ", $command_line);

	/* output information to the log file if appropriate */
	if (read_config_option("log_graph") == CHECKED) {
		cacti_log("CACTI2RRD: " . read_config_option("path_rrdtool") . " $command_line", $log_to_stdout, $logopt);
	}

	/* if we want to see the error output from rrdtool; make sure to specify this */
	if (($output_flag == RRDTOOL_OUTPUT_STDERR) && (!is_resource($rrdtool_pipe))) {
		$command_line .= " 2>&1";
	}

	/* use popen to eliminate the zombie issue */
	if (CACTI_SERVER_OS == "unix") {
		$pipe_mode = "r";
	}else{
		$pipe_mode = "rb";
	}

	/* an empty $rrdtool_pipe array means no fp is available */
	if (!is_resource($rrdtool_pipe)) {
		session_write_close();
		$fp = popen(read_config_option("path_rrdtool") . escape_command(" $command_line"), $pipe_mode);
		if (!is_resource($fp)) {
			unset($fp);
		}
	}else{
		$i = 0;

		while (1) {
			if (fwrite($rrdtool_pipe, escape_command(" $command_line") . "\r\n") == false) {
				cacti_log("ERROR: Detected RRDtool Crash on '$command_line'.  Last command was '$last_command'");

				/* close the invalid pipe */
				rrd_close($rrdtool_pipe);

				/* open a new rrdtool process */
				$rrdtool_pipe = rrd_init();

				if ($i > 4) {
					cacti_log("FATAL: RRDtool Restart Attempts Exceeded. Giving up on '$command_line'.");

					break;
				}else{
					$i++;
				}

				continue;
			}else{
				fflush($rrdtool_pipe);

				break;
			}
		}
	}

	/* store the last command to provide rrdtool segfault diagnostics */
	$last_command = $command_line;

	switch ($output_flag) {
		case RRDTOOL_OUTPUT_NULL:
			return; break;
		case RRDTOOL_OUTPUT_STDOUT:
			if (isset($fp) && is_resource($fp)) {
				$line = "";
				while (!feof($fp)) {
					$line .= fgets($fp, 4096);
				}

				pclose($fp);

				return $line;
			}

			break;
		case RRDTOOL_OUTPUT_STDERR:
			if (isset($fp) && is_resource($fp)) {
				$output = fgets($fp, 1000000);

				pclose($fp);

				if (substr($output, 1, 3) == "PNG") {
					return __("PNG Output OK");
				}

				if (substr($output, 0, 5) == "GIF87") {
					return __("GIF Output OK");
				}

				if (substr($output, 0, 5) == "<?xml") {
					return __("SVG/XML Output OK");
				}

				print $output;
			}

			break;
		case RRDTOOL_OUTPUT_GRAPH_DATA:
			if (isset($fp) && is_resource($fp)) {
				$line = "";
				while (!feof($fp)) {
					$line .= fgets($fp, 4096);
				}

				pclose($fp);

				return $line;
			}

			break;
	}
}


/** create a new rrd file
 * @param int $local_data_id - id of graph
 * @param bool $show_source - show command only
 * @param resource $rrdtool_pipe - pipe for rrdtool command
 */
function rrdtool_function_create($local_data_id, $show_source, &$rrdtool_pipe = "") {
	include(CACTI_INCLUDE_PATH . "/global_arrays.php");
	require(CACTI_INCLUDE_PATH . "/presets/preset_rra_arrays.php");
	require(CACTI_INCLUDE_PATH . "/data_source/data_source_arrays.php");

	$data_source_path = get_data_source_path($local_data_id, true);

	/* ok, if that passes lets check to make sure an rra does not already
	exist, the last thing we want to do is overright data! */
	if ($show_source != true) {
		if (file_exists($data_source_path) == true) {
			return -1;
		}
	}

	/* the first thing we must do is make sure there is at least one
	rra associated with this data source... *
	UPDATE: As of version 0.6.6, we are splitting this up into two
	SQL strings because of the multiple DS per RRD support. This is
	not a big deal however since this function gets called once per
	data source */

	$rras = db_fetch_assoc("select
		data_template_data.rrd_step,
		rra.x_files_factor,
		rra.steps,
		rra.rows,
		rra_cf.consolidation_function_id,
		(rra.rows*rra.steps) as rra_order
		from data_template_data
		left join data_template_data_rra on (data_template_data.id=data_template_data_rra.data_template_data_id)
		left join rra on (data_template_data_rra.rra_id=rra.id)
		left join rra_cf on (rra.id=rra_cf.rra_id)
		where data_template_data.local_data_id=$local_data_id
		and (rra.steps is not null or rra.rows is not null)
		order by rra_cf.consolidation_function_id,rra_order");

	/* if we find that this DS has no RRA associated; get out */
	if (sizeof($rras) <= 0) {
		cacti_log("ERROR: There are no RRA's assigned to local_data_id: $local_data_id.");
		return false;
	}

	/* create the "--step" line */
	$create_ds = RRD_NL . "--step ". $rras[0]["rrd_step"] . " " . RRD_NL;

	/* query the data sources to be used in this .rrd file */
	$data_sources = db_fetch_assoc("SELECT
		data_template_rrd.id,
		data_template_rrd.rrd_heartbeat,
		data_template_rrd.rrd_minimum,
		data_template_rrd.rrd_maximum,
		data_template_rrd.rrd_compute_rpn,
		data_template_rrd.data_source_type_id
		FROM data_template_rrd
		WHERE data_template_rrd.local_data_id=$local_data_id
		ORDER BY local_data_template_rrd_id");

	/* ONLY make a new DS entry if:
	- There is multiple data sources and this item is not the main one.
	- There is only one data source (then use it) */

	if (sizeof($data_sources) > 0) {
	foreach ($data_sources as $data_source) {
		/* use the cacti ds name by default or the user defined one, if entered */
		$data_source_name = get_data_source_item_name($data_source["id"]);

		/* special format for COMPUTE data source type */
		if ( $data_source["data_source_type_id"] == DATA_SOURCE_TYPE_COMPUTE ) {
			$create_ds .= "DS:$data_source_name:" . $data_source_types{$data_source["data_source_type_id"]} . ":" . (empty($data_source["rrd_compute_rpn"]) ? "U" : $data_source["rrd_compute_rpn"]) . RRD_NL;
		} else {
			if (empty($data_source["rrd_maximum"])) {
				$data_source["rrd_maximum"] = "U";
			} elseif (strpos($data_source["rrd_maximum"], "|query_") !== false) {
				$data_local = db_fetch_row("SELECT * FROM data_local WHERE id=" . $local_data_id);
				$data_source["rrd_maximum"] = substitute_snmp_query_data($data_source["rrd_maximum"],$data_local["host_id"], $data_local["snmp_query_id"], $data_local["snmp_index"], 0, false);
			} elseif (($data_source["rrd_maximum"] != "U") && is_numeric($data_source["rrd_minimum"]) && (int)$data_source["rrd_maximum"]<=(int)$data_source["rrd_minimum"]) {
				$data_source["rrd_maximum"] = (int)$data_source["rrd_minimum"]+1;
			}

			/* min==max==0 won't work with rrdtool */
			if ($data_source["rrd_minimum"] == 0 && $data_source["rrd_maximum"] == 0) {
				$data_source["rrd_maximum"] = "U";
			}

			$create_ds .= "DS:$data_source_name:" . $data_source_types{$data_source["data_source_type_id"]} . ":" .
							$data_source["rrd_heartbeat"] . ":" . $data_source["rrd_minimum"] . ":" . $data_source["rrd_maximum"] . RRD_NL;
		}
	}
	}

	$create_rra = "";
	/* loop through each available RRA for this DS */
	foreach ($rras as $rra) {
		$create_rra .= "RRA:" . $consolidation_functions{$rra["consolidation_function_id"]} . ":" . $rra["x_files_factor"] . ":" . $rra["steps"] . ":" . $rra["rows"] . RRD_NL;
	}

	/* check for structured path configuration, if in place verify directory
	   exists and if not create it.
	 */
	if (read_config_option("extended_paths") == CHECKED) {
		if (!is_dir(dirname($data_source_path))) {
			if (mkdir(dirname($data_source_path), 0775)) {
				if (CACTI_SERVER_OS != "win32") {
					$owner_id      = fileowner(CACTI_RRA_PATH);
					$group_id      = filegroup(CACTI_RRA_PATH);

					if ((chown(dirname($data_source_path), $owner_id)) &&
						(chgrp(dirname($data_source_path), $group_id))) {
						/* permissions set ok */
					}else{
						cacti_log("ERROR: Unable to set directory permissions for '" . dirname($data_source_path) . "'", FALSE);
					}
				}
			}else{
				cacti_log("ERROR: Unable to create directory '" . dirname($data_source_path) . "'", FALSE);
			}
		}
	}

	if ($show_source == true) {
		return read_config_option("path_rrdtool") . " create" . RRD_NL . "$data_source_path$create_ds$create_rra";
	}else{
		rrdtool_execute("create $data_source_path $create_ds$create_rra", true, RRDTOOL_OUTPUT_STDOUT, $rrdtool_pipe, "POLLER");
	}
}


/** update existing rrd file with new data
 * @param array $update_cache_array - array with data for update
 * @param resource $rrdtool_pipe - pipe for rrdtool command
 */
function rrdtool_function_update($update_cache_array, &$rrdtool_pipe = "") {
	/* lets count the number of rrd files processed */
	$rrds_processed = 0;

	while (list($rrd_path, $rrd_fields) = each($update_cache_array)) {
		$create_rrd_file = false;

		/* create the rrd if one does not already exist */
		if (!file_exists($rrd_path)) {
			rrdtool_function_create($rrd_fields["local_data_id"], false, $rrdtool_pipe);

			$create_rrd_file = true;
		}

		if ((is_array($rrd_fields["times"])) && (sizeof($rrd_fields["times"]) > 0)) {
			ksort($rrd_fields["times"]);

			while (list($update_time, $field_array) = each($rrd_fields["times"])) {
				if (empty($update_time)) {
					/* default the rrdupdate time to now */
					$current_rrd_update_time = "N";
				}else if ($create_rrd_file == true) {
					/* for some reason rrdtool will not let you update using times less than the
					rrd create time */
					$current_rrd_update_time = "N";
				}else{
					$current_rrd_update_time = $update_time;
				}

				$i = 0; $rrd_update_template = ""; $rrd_update_values = $current_rrd_update_time . ":";
				while (list($field_name, $value) = each($field_array)) {
					$rrd_update_template .= $field_name;

					/* if we have "invalid data", give rrdtool an Unknown (U) */
					if ((!isset($value)) || (!is_numeric($value))) {
						$value = "U";
					}

					$rrd_update_values .= $value;

					if (($i+1) < count($field_array)) {
						$rrd_update_template .= ":";
						$rrd_update_values .= ":";
					}

					$i++;
				}

				rrdtool_execute("update $rrd_path --template $rrd_update_template $rrd_update_values", true, RRDTOOL_OUTPUT_STDOUT, $rrdtool_pipe, "POLLER");
				$rrds_processed++;
			}
		}
	}

	return $rrds_processed;
}

$rrd_fetch_cache = array();


/** given a data source, return all of its data in an array
 *	@param $local_data_id - the data source to fetch data for
 *	@param $start_time - the start time to use for the data calculation. this value can
 *		either be absolute (unix timestamp) or relative (to now)
 *	@param $end_time - the end time to use for the data calculation. this value can
 *		either be absolute (unix timestamp) or relative (to now)
 *	@param $resolution - the accuracy of the data measured in seconds
 *	@param $show_unknown - Show unknown 'NAN' values in the output as 'U'
 *	@return - (array) an array containing all data in this data source broken down
 *		by each data source item. the maximum of all data source items is included in
 *		an item called 'ninety_fifth_percentile_maximum' 
 */
function rrdtool_function_fetch($local_data_id, $start_time, $end_time, $resolution = 0, $show_unknown = false, $rrdtool_file = null) {
	global $rrd_fetch_cache;

	/* validate local data id */
	if (empty($local_data_id) && is_null($rrdtool_file)) {
		return array();
	}

	/* the cache hash is used to identify unique items in the cache */
	$current_hash_cache = md5($local_data_id . $start_time . $end_time . $resolution . $show_unknown . $rrdtool_file);

	/* return the cached entry if available */
	if (isset($rrd_fetch_cache[$current_hash_cache])) {
		return $rrd_fetch_cache[$current_hash_cache];
	}

	/* initialize fetch array */
	$fetch_array = array();

	/* check if we have been passed a file instead of lodal data source to look up */
	if (is_null($rrdtool_file)) {
		$data_source_path = get_data_source_path($local_data_id, true);
	}else{
		$data_source_path = $rrdtool_file;
	}

	/* update the rrd from boost if applicable */
	plugin_hook_function('rrdtool_function_fetch_cache_check', $local_data_id);

	/* build and run the rrdtool fetch command with all of our data */
	$cmd_line = "fetch $data_source_path AVERAGE -s $start_time -e $end_time";
	if ($resolution > 0) {
		$cmd_line .= " -r $resolution";
	}
	$output = rrdtool_execute($cmd_line, false, RRDTOOL_OUTPUT_STDOUT);

	/* grab the first line of the output which contains a list of data sources in this rrd output */
	$line_one_eol = strpos($output, "\n");
	$line_one = substr($output, 0, $line_one_eol);
	$output = substr($output, $line_one_eol);

	/* split the output into an array */
	$output = preg_split('/[\r\n]{1,2}/', $output, null, PREG_SPLIT_NO_EMPTY);

	/* find the data sources in the rrdtool output */
	if (preg_match_all('/\S+/', $line_one, $data_source_names)) {
		/* version 1.0.49 changed the output slightly, remove the timestamp label if present */
		if (preg_match('/^timestamp/', $line_one)) {
			array_shift($data_source_names[0]);
		}
		$fetch_array["data_source_names"] = $data_source_names[0];

		/* build a regular expression to match each data source value in the rrdtool output line */
		$regex = '/[0-9]+:\s+';
		for ($i=0; $i < count($fetch_array["data_source_names"]); $i++) {
			$regex .= '([\-]?[0-9]{1}[.,][0-9]+e[\+-][0-9]{2,3}|-?[Nn][Aa][Nn])';

			if ($i < count($fetch_array["data_source_names"]) - 1) {
				$regex .= '\s+';
			}
		}
		$regex .= '/';
	}

	/* loop through each line of the output */
	$fetch_array["values"] = array();
	for ($j = 0; $j < count($output); $j++) {
		$matches = array();
		$max_array = array();
		/* match the output line */
		if (preg_match($regex, $output[$j], $matches)) {
			/* only process the output line if we have the correct number of matches */
			if (count($matches) - 1 == count($fetch_array["data_source_names"])) {
				/* get all values from the line and set them to the appropriate data source */
				for ($i=1; $i <= count($fetch_array["data_source_names"]); $i++) {
					if (! isset($fetch_array["values"][$i - 1])) {
						$fetch_array["values"][$i - 1] = array();
					}
					if ((strtolower($matches[$i]) == "nan") || (strtolower($matches[$i]) == "-nan")) {
						if ($show_unknown) {
							$fetch_array["values"][$i - 1][$j] = "U";
						}
					} else {
						list($mantisa, $exponent) = explode('e', $matches[$i]);
						$mantisa = str_replace(",",".",$mantisa);
						$value = ($mantisa * (pow(10, (float)$exponent)));
						$fetch_array["values"][$i - 1][$j] = ($value * 1);
						$max_array[$i - 1] = $value;
					}
				}
				/* get max value for values on the line */
				if (count($max_array) > 0) {
					$fetch_array["values"][count($fetch_array["data_source_names"])][$j] = max($max_array);
				}
			}
		}
	}
	/* add nth percentile maximum data source */
	if (isset($fetch_array["values"][count($fetch_array["data_source_names"])])) {
		$fetch_array["data_source_names"][count($fetch_array["data_source_names"])] = "nth_percentile_maximum";
	}

	/* clear the cache if it gets too big */
	if (sizeof($rrd_fetch_cache) >= MAX_FETCH_CACHE_SIZE) {
		$rrd_fetch_cache = array();
	}

	/* update the cache */
	if (MAX_FETCH_CACHE_SIZE > 0) {
		$rrd_fetch_cache[$current_hash_cache] = $fetch_array;
	}

	return $fetch_array;
}


/** perform the rrdtool graph command
 * @param int $local_graph_id 		- id of graph
 * @param int $rra_id 				- id of rra
 * @param array $graph_data_array 	- predefined graph data (start, end time)
 * @param resource $rrdtool_pipe 	- pipe for rrdtool command
 */
function rrdtool_function_graph($local_graph_id, $rra_id, $graph_data_array, &$rrdtool_pipe = "") {
#cacti_log(__FUNCTION__ . " local graph id: " . $local_graph_id, false, "TEST");
#cacti_log(__FUNCTION__ . " rra id: " . $rra_id, false, "TEST");
#cacti_log(__FUNCTION__ . " graph data array: " . serialize($graph_data_array), false, "TEST");
	include(CACTI_INCLUDE_PATH . "/global_arrays.php");
	require(CACTI_INCLUDE_PATH . "/presets/preset_rra_arrays.php");
	require(CACTI_INCLUDE_PATH . "/graph/graph_arrays.php");
	include_once(CACTI_LIBRARY_PATH . "/cdef.php");
	include_once(CACTI_LIBRARY_PATH . "/vdef.php");
	include_once(CACTI_LIBRARY_PATH . "/graph_variables.php");
	include_once(CACTI_LIBRARY_PATH . "/variables.php");
	include_once(CACTI_LIBRARY_PATH . "/time.php");
	
	/* prevent command injection!
	 * This function prepares an rrdtool graph statement to be executed by the web server.
	 * We have to take care, that the attacker does not insert shell code.
	 * As some rrdtool parameters accept "Cacti variables", we have to perform the
	 * variable substitution prior to vulnerability checks.
	 * We will enclose all parameters in quotes and substitute quotation marks within
	 * those parameters. 
	 */
	
	
	/* +++++++++++++++++++++++ INIT PHASE +++++++++++++++++++++++++++++++++++++++++
	 * before we do anything; make sure the user has permission to view this graph,
	 * if not then get out
	 * ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
	if ((read_config_option("auth_method") != AUTH_METHOD_NONE) && (isset($_SESSION["sess_user_id"]))) {
		if (!is_graph_allowed($local_graph_id)) {
			return "GRAPH ACCESS DENIED";
		}
	}
	
	/* update execution environment */
	rrdgraph_put_environment();
	
	/* +++++++++++++++++++++++ Hook +++++++++++++++++++++++++++++++++++++++++++++++
	 * call cache hook at start and, if found, return with that data 
	 * ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
	$data = plugin_hook_function('rrdtool_function_graph_cache_check', array('local_graph_id' => $local_graph_id,'rra_id' => $rra_id,'rrd_struc' => $rrdtool_pipe,'graph_data_array' => $graph_data_array, 'return' => false));
	if (isset($data['return']) && $data['return'] != false)
		return $data['return'];
	
	
	
	/* +++++++++++++++++++++++ FETCH DATA +++++++++++++++++++++++++++++ */
	/* override: graph start time */
	$ds_step = get_step($local_graph_id);
	/* get graph data for given graph id */
	$graph = get_graph_data($local_graph_id);
	/* merge $graph_data_array into $graph */
	$graph = merge_graph_data ($graph, $graph_data_array);
	
	/* get graph item data for given graph id */
	$graph_items = get_graph_item_data($local_graph_id);
	/* get rra data, depends on a lot of parameters */
	$rra = get_rra_data($rra_id, $ds_step, $local_graph_id, $graph_data_array);
	$seconds_between_graph_updates = ($ds_step * $rra["steps"]);
	$rrdtool_version = read_config_option("rrdtool_version");
	
	# start and end time are used multiple times
	list($graph_start, $graph_end) = rrdgraph_start_end($graph, $rra, $seconds_between_graph_updates);
	$graph["graph_start"] = $graph_start;
	$graph["graph_end"] = $graph_end;
	
	
	
	/* +++++++++++++++++++++++ GRAPH OPTIONS ++++++++++++++++++++++++++ *
	 * get global opts and substitute CACTI variables for later use     *
	 * ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
#cacti_log(__FUNCTION__ . " graph array: " . serialize($graph), false, "TEST");
	$graph_opts = rrdgraph_options($graph, $rra, $rrdtool_version);
	$graph_date = date_time_format();  /* TODO: who needs this ? */
	
	/* Replace "|query_*|" in the graph command to replace e.g. vertical_label.  */
	$graph_opts = rrdgraph_substitute_host_query_data($graph_opts, $graph, NULL);
#cacti_log(__FUNCTION__ . " graph opts: " . $graph_opts, false, "TEST");
	
	
	
	/* +++++++++++++++++++++++ GRAPH ITEMS ++++++++++++++++++++++++++++ *
	 * determine some intermediate results to be used later             *
	 * ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
	$graph_defs 			= "";		# store rrdtool DEF statements here
	$greatest_text_format 	= 0;		# stores the greatest text length found for a legend entry => auto padding
	$graph_variables 		= array();	# all graph variables go here; substitutions
	$cf_ds_cache 			= array();	# cache for all pairs of [consolidation function] and [data source id]
	# we will print a legend by default; nolegend has to be set explicitely
	$print_legend 			= (! isset($graph_data_array["graph_nolegend"]));	
	
	if (sizeof($graph_items) > 0) {

		/* fill in cdef strings as "cdef_cache" for later use */
		get_all_graph_cdefs($graph_items);
		/* fill in vdef strings as "vdef_cache" for later use */
		get_all_graph_vdefs($graph_items);
		/* get reference consolidation function, store as "cf_reference" */
		rrdgraph_compute_cfs($graph_items);
		/* build list of rrd DEF statements for this graph and
		 * fill the cache for pairs of [consolidation function] and [data source id] */
		$graph_defs .= rrdgraph_defs($graph_items, $graph_start, $graph_end, $cf_ds_cache);
	
		/* LEGEND: TEXT SUBSTITUTION (<>'s), modify graph pseudo variables */
		$graph_variables = rrdgraph_pseudo_variable_substitutions(
			$graph, $graph_items, $graph_start, $graph_end,	$rra["steps"], $ds_step, $print_legend);

		/* in case we have to print a legend
		 * there's more to do
		 * - set hard return
		 * - perform legend substitution to replace Cacti pseudo variables by their replacements
		 * - compute auto-padding
		 * $graph_items array is treated as a scratchpad here;
		 * intermediate results are stored in this array for later use
		 */
		if ($print_legend) {
			/* define hard returns as "hardreturn" */
			rrdgraph_compute_hardreturns($graph_items);
			
			/* autopadding processing has to be performed after variable substitutions
			 * because item length's may change after substitution
			 * get greatest text length for use with autopadding.
			 * We will save the results as "text_padding" per item in $graph_items */
			rrdgraph_text_padding($graph_items,	$graph["auto_padding"],	$graph_variables);
		}
	}
#cacti_log(__FUNCTION__ . " graph defs: " . $graph_defs, false, "TEST");
	
	
	
	/* +++++++++++++++++++++++ GRAPH ITEMS: CDEF's ++++++++++++++++++++++++++++++++++++++++++++++++++ *
	 * loop over all graph items and use data computed above
	 * 
	 * requires
	 * $graph_items:	all graph items for this graph
	 * $graph_variables graph variables
	 * $cf_ds_cache:	array of DEFs+CFs
	 * 
	 * creates
	 * $cf_id:			id of CF reference (the CF used with the DEF, not the CF used with any GPRINT)
	 * $cdef_graph_defs	cdef string for rrdtool graph
	 * $cdef_cache:		cache of all CDEFs
	 * ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
	$txt_graph_items = "";
	$cdef_cache = $vdef_cache = array();
	$i = 0;
	reset($graph_items);
	
	if (sizeof($graph_items) > 0) {
		foreach ($graph_items as $graph_item) {
			$cf_id = $graph_item["cf_reference"]; 
			$graph_item_id = $graph_item["graph_templates_item_id"];
			$current_graph_item_type = $graph_item["graph_type_id"];

			
			/* +++++++++++++++++++++++ GRAPH ITEMS: CDEF's +++++++++++++++++++++++ */
			/* make cdef string here; a note about CDEF's in cacti. A CDEF is neither unique to a
			 * data source or global cdef, but is unique when those two variables combine. */
			$graph_defs .= rrdgraph_cdefs($graph, $graph_item, $graph_items, $graph_variables, $cf_id, $i, $seconds_between_graph_updates, $cf_ds_cache, $cdef_cache);
#cacti_log(__FUNCTION__ . " graph cdefs: " . $graph_defs, false, "TEST");

			/* IF this graph item has a data source... get a DEF name for it, 
			 * or the cdef if that applies to this graph item */
			if ($graph_item["cdef_id"] == "0") {
				if (isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}[$cf_id])) {
					$data_source_name = generate_graph_def_name(strval($cf_ds_cache{$graph_item["data_template_rrd_id"]}[$cf_id]));
				}else{
					$data_source_name = "";
				}
			}else{
				$data_source_name = "cdef" . generate_graph_def_name(strval($cdef_cache{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id]));
			}

			
			/* +++++++++++++++++++++++ GRAPH ITEMS: VDEF's +++++++++++++++++++++++ */
			if ($rrdtool_version != RRD_VERSION_1_0) {
				/* make vdef string here, copied from cdef stuff */
				$graph_defs .= rrdgraph_vdefs($graph_item, $graph_variables, $cf_id, $i, $cf_ds_cache, $vdef_cache);
				/* add the cdef string to the end of the def string */
				#$graph_defs .= $vdef_graph_defs;
#cacti_log(__FUNCTION__ . " graph vdefs: " . $graph_defs, false, "TEST");

				/* IF this graph item has a data source... 
				 * get a the vdef if that applies to this graph item */
				if ($graph_item["vdef_id"] == "0") {
					/* do not overwrite $data_source_name that stems from cdef above */
				}else{
					$data_source_name = "vdef" . generate_graph_def_name(strval($vdef_cache{$graph_item["vdef_id"]}{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id]));
				}
			}


			/* +++++++++++++++++++++++ GRAPH ITEMS +++++++++++++++++++++++ */
			/* most of the calculations have been done above. now we have for print everything out
			 * in an RRDTool-friendly fashion */
			$need_rrd_nl = TRUE;
			/* to make things easier... if there is no text format set; set blank text */
			if (!isset($graph_variables["text_format"][$graph_item_id])) {
				$graph_variables["text_format"][$graph_item_id] = "";
			} else {
				$graph_variables["text_format"][$graph_item_id] = str_replace(':', '\:', $graph_variables["text_format"][$graph_item_id]); /* escape colons */
				$graph_variables["text_format"][$graph_item_id] = str_replace('"', '\"', $graph_variables["text_format"][$graph_item_id]); /* escape doublequotes */
			}

			/* now build the legend text, 
			 * use all arrays and variables that have been prepared
			 * $need_rrd_nl will be changed within this function */
			$txt_graph_items .= rrdgraph_compute_item_text(
				$graph, $graph_item, $graph_variables, $data_source_name, $rrdtool_version, $need_rrd_nl);
		
			$i++;
			
			if (($i < sizeof($graph_items)) && ($need_rrd_nl)) {
				$txt_graph_items .= RRD_NL;
			}
		}
	}
#cacti_log(__FUNCTION__ . " graph items: " . $txt_graph_items, false, "TEST");
	
	
	/* +++++++++++++++++++++++ Hook +++++++++++++++++++++++++++++++++++++++++++++++ */
#cacti_log(__FUNCTION__ . " RRDTOOL: " . (read_config_option("path_rrdtool") . " graph $graph_opts$graph_defs$txt_graph_items"), false, "TEST");
	$graph_array = plugin_hook_function('rrd_graph_graph_options', 
						array('graph_opts' 		=> $graph_opts, 
							'graph_defs' 		=> $graph_defs, 
							'txt_graph_items' 	=> $txt_graph_items, 
							'graph_id' 			=> $local_graph_id, 
							'start' 			=> $graph_start, 
							'end' 				=> $graph_end));
	/* retrieve information from last hook */
	if (!empty($graph_array)) {
		/* display the timespan for zoomed graphs,
		 * but prefer any setting made by the user */
		$graph_array['txt_graph_items'] = substitute_graph_data($graph_array['txt_graph_items'], $graph, $graph_data_array, $rrdtool_version);
		$graph_defs = $graph_array['graph_defs'];
		$txt_graph_items = $graph_array['txt_graph_items'];
		$graph_opts = $graph_array['graph_opts'];
	}
	
	/* +++++++++++++++++++++++ Output Phase +++++++++++++++++++++++++++++++++++++++ */
	/* either print out the source or pass the source onto rrdtool to get us a nice graph */
#cacti_log(__FUNCTION__ . " RRDTOOL: " . (read_config_option("path_rrdtool") . " graph $graph_opts$graph_defs$txt_graph_items"), false, "TEST");
	if (isset($graph_data_array["print_source"])) {
		# since pango markup allows for <span> tags, we need to escape this stuff using htmlspecialchars
		print htmlspecialchars(read_config_option("path_rrdtool") . " graph $graph_opts$graph_defs$txt_graph_items");
		/* TODO: delete, when tests finished */
		print "<br><br><br><strong>This is the old rrdtool graph code:</strong><br>";
		rrdtool_function_graph_old($local_graph_id, $rra_id, $graph_data_array, $rrdtool_pipe);
	}elseif (isset($graph_data_array["export"])) {
		rrdtool_execute("graph $graph_opts$graph_defs$txt_graph_items", false, RRDTOOL_OUTPUT_NULL, $rrdtool_pipe);
		return 0;
	}else{
		/* +++++++++++++++++++++++ Hook ++++++++++++++++++++++++++++++++++++++++++++ */
		$graph_data_array = plugin_hook_function('prep_graph_array', $graph_data_array);
		
		if (isset($graph_data_array["output_flag"])) {
			$output_flag = $graph_data_array["output_flag"];
		}else{
			$output_flag = RRDTOOL_OUTPUT_GRAPH_DATA;
		}
		$output = rrdtool_execute("graph $graph_opts$graph_defs$txt_graph_items", false, $output_flag, $rrdtool_pipe);
		
		/* +++++++++++++++++++++++ Hook ++++++++++++++++++++++++++++++++++++++++++++ */
		plugin_hook_function('rrdtool_function_graph_set_file', array('output' => $output, 'local_graph_id' => $local_graph_id, 'rra_id' => $rra_id));
		
		return $output;
	}
}


function rrdtool_function_graph_old($local_graph_id, $rra_id, $graph_data_array, &$rrdtool_pipe = "") {
	include(CACTI_INCLUDE_PATH . "/global_arrays.php");
	require(CACTI_INCLUDE_PATH . "/presets/preset_rra_arrays.php");
	require(CACTI_INCLUDE_PATH . "/graph/graph_arrays.php");
	include_once(CACTI_LIBRARY_PATH . "/cdef.php");
	include_once(CACTI_LIBRARY_PATH . "/vdef.php");
	include_once(CACTI_LIBRARY_PATH . "/graph_variables.php");
	include_once(CACTI_LIBRARY_PATH . "/variables.php");
	include_once(CACTI_LIBRARY_PATH . "/time.php");

	/* prevent command injection
	 * This function prepares an rrdtool graph statement to be executed by the web server.
	 * We have to take care, that the attacker does not insert shell code.
	 * As some rrdtool parameters accept "Cacti variables", we have to perform the
	 * variable substitution prior to vulnerability checks.
	 * We will enclose all parameters in quotes and substitute quotation marks within
	 * those parameters. 
	 */

	/* before we do anything; make sure the user has permission to view this graph,
	 * if not then get out */
	if ((read_config_option("auth_method") != AUTH_METHOD_NONE) && 
		(isset($_SESSION["sess_user_id"])) &&
		!(is_graph_allowed($local_graph_id))) {
		return "GRAPH ACCESS DENIED";
	}

	/* update execution environment */
	rrdgraph_put_environment();

	$data = plugin_hook_function('rrdtool_function_graph_cache_check', array('local_graph_id' => $local_graph_id,'rra_id' => $rra_id,'rrd_struc' => $rrdtool_pipe,'graph_data_array' => $graph_data_array, 'return' => false));
	if (isset($data['return']) && $data['return'] != false)
		return $data['return'];


	/* fetch data */
	/* find the step and how often this graph is updated with new data */
	$ds_step = get_step($local_graph_id);
	/* get graph data */
	$graph = get_graph_data($local_graph_id);
	/* get graph item data */
	$graph_items = get_graph_item_data($local_graph_id);
	/* get rra data */
	$rra = get_rra_data($rra_id, $ds_step, $local_graph_id, $graph_data_array);
	$seconds_between_graph_updates = ($ds_step * $rra["steps"]);
	$rrdtool_version = read_config_option("rrdtool_version");

	# start and end time are used multiple times
	list($graph_start, $graph_end) = rrdgraph_start_end($graph_data_array, $rra, $seconds_between_graph_updates);
	$graph["graph_start"] = $graph_start;
	$graph["graph_end"] = $graph_end;

	/* +++++++++++++++++++++++ GRAPH OPTIONS +++++++++++++++++++++++ */

	# generate all global rrdtool graph options
	$graph_opts = '';
	$graph_opts .= rrdgraph_options($graph, $graph_data_array, $rra, $rrdtool_version);

	$graph_opts .= rrdgraph_option_scale($graph);

	$graph_date = date_time_format();

	/* ggf. erst bei der Gesamt-Command-Generierung */
	/* Replace "|query_*|" in the graph command to replace e.g. vertical_label.  */
	$graph_opts = rrdgraph_substitute_host_query_data($graph_opts, $graph, NULL);















/* DEF, CDEF, VDEF handling
 * graph items
 */

	/* define some variables */
	$graph_defs = "";
	$txt_graph_items = "";
	$text_padding = "";
	$greatest_text_format = 0;
	$last_graph_type = "";
	$i = 0; $j = 0;
	$last_graph_cf = array();
	if (sizeof($graph_items) > 0) {

		/* we need to add a new column "cf_reference", so unless PHP 5 is used, this foreach syntax is required */
		foreach ($graph_items as $key => $graph_item) {
			/* mimic the old behavior: LINE[123], AREA and STACK items use the CF specified in the graph item */
			if ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_AREA  ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_STACK ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE1 ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE2 ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE3 ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINESTACK ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_TICK) {
				$graph_cf = $graph_item["consolidation_function_id"];
				/* remember the last CF for this data source for use with GPRINT
				 * if e.g. an AREA/AVERAGE and a LINE/MAX is used
				 * we will have AVERAGE first and then MAX, depending on GPRINT sequence */
				$last_graph_cf["data_source_name"]["local_data_template_rrd_id"] = $graph_cf;
				/* remember this for second foreach loop */
				$graph_items[$key]["cf_reference"] = $graph_cf;
			}elseif ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_AVERAGE) {
				$graph_cf = $graph_item["consolidation_function_id"];
				$graph_items[$key]["cf_reference"] = $graph_cf;
			}elseif ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_LAST) {
				$graph_cf = $graph_item["consolidation_function_id"];
				$graph_items[$key]["cf_reference"] = $graph_cf;
			}elseif ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_MAX) {
				$graph_cf = $graph_item["consolidation_function_id"];
				$graph_items[$key]["cf_reference"] = $graph_cf;
			}elseif ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_MIN) {
				$graph_cf = $graph_item["consolidation_function_id"];
				$graph_items[$key]["cf_reference"] = $graph_cf;
				#}elseif ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT) {
				#/* ATTENTION!
				# * the "CF" given on graph_item edit screen for GPRINT is indeed NOT a real "CF",
				# * but an aggregation function
				# * see "man rrdgraph_data" for the correct VDEF based notation
				# * so our task now is to "guess" the very graph_item, this GPRINT is related to
				# * and to use that graph_item's CF */
				#if (isset($last_graph_cf["data_source_name"]["local_data_template_rrd_id"])) {
				#	$graph_cf = $last_graph_cf["data_source_name"]["local_data_template_rrd_id"];
				#	/* remember this for second foreach loop */
				#	$graph_items[$key]["cf_reference"] = $graph_cf;
				#} else {
				#	$graph_cf = generate_graph_best_cf($graph_item["local_data_id"], $graph_item["consolidation_function_id"]);
				#	/* remember this for second foreach loop */
				#	$graph_items[$key]["cf_reference"] = $graph_cf;
				#}
			}else{
				/* all other types are based on the best matching CF */
				#GRAPH_ITEM_TYPE_COMMENT
				#GRAPH_ITEM_TYPE_HRULE
				#GRAPH_ITEM_TYPE_VRULE
				#GRAPH_ITEM_TYPE_TEXTALIGN
				$graph_cf = generate_graph_best_cf($graph_item["local_data_id"], $graph_item["consolidation_function_id"]);
				/* remember this for second foreach loop */
				$graph_items[$key]["cf_reference"] = $graph_cf;
			}


/* this is command generation -
 * should be moved to the end of all stuff
 */
			if ((!empty($graph_item["local_data_id"])) && (!isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}[$graph_cf]))) {
				/* use a user-specified ds path if one is entered */
				$data_source_path = get_data_source_path($graph_item["local_data_id"], true);

				/* FOR WIN32: Escape all colon for drive letters (ex. D\:/path/to/rra) */
				$data_source_path = str_replace(":", "\:", $data_source_path);

				if (!empty($data_source_path)) {
					/* NOTE: (Update) Data source DEF names are created using the graph_item_id; then passed
					to a function that matches the digits with letters. rrdtool likes letters instead
					of numbers in DEF names; especially with CDEF's. cdef's are created
					the same way, except a 'cdef' is put on the beginning of the hash */
					$graph_defs .= "DEF:" . generate_graph_def_name(strval($i)) . "=\"$data_source_path\":" . cacti_escapeshellarg($graph_item["data_source_name"]) . ":" . $consolidation_functions[$graph_cf];
					if ($graph_item["shift"] == CHECKED && $graph_item["value"] > 0) {	# create a SHIFTed DEF
						$graph_defs .= ":start=" . $graph["graph_start"] . "-" . $graph_item["value"];
						$graph_defs .= ":end=" . $graph["graph_end"] . "-" . $graph_item["value"];
					}
					$graph_defs .= RRD_NL;
/* keep this, fills the cache */
					$cf_ds_cache{$graph_item["data_template_rrd_id"]}[$graph_cf] = "$i";

					$i++;
				}
			}

			/* cache cdef value here to support data query variables in the cdef string */
			if (empty($graph_item["cdef_id"])) {
				$graph_item["cdef_cache"] = "";
				$graph_items[$j]["cdef_cache"] = "";
			}else{
				$graph_item["cdef_cache"] = get_cdef($graph_item["cdef_id"]);
				$graph_items[$j]["cdef_cache"] = get_cdef($graph_item["cdef_id"]);
			}

			/* cache vdef value here */
			if (empty($graph_item["vdef_id"])) {
				$graph_item["vdef_cache"] = "";
				$graph_items[$j]["vdef_cache"] = "";
			}else{
				$graph_item["vdef_cache"] = get_vdef($graph_item["vdef_id"]);
				$graph_items[$j]["vdef_cache"] = get_vdef($graph_item["vdef_id"]);
			}


			/* +++++++++++++++++++++++ LEGEND: TEXT SUBSTITUTION (<>'s) +++++++++++++++++++++++ */
/* put this into subroutine */
			/* note the current item_id for easy access */
			$graph_item_id = $graph_item["graph_templates_item_id"];

			/* the following fields will be searched for graph variables */
			$variable_fields = array(
				"text_format" => array(
					"process_no_legend" => false
					),
				"value" => array(
					"process_no_legend" => true
					),
				"cdef_cache" => array(
					"process_no_legend" => true
					),
				"vdef_cache" => array(
					"process_no_legend" => true
					)
				);

			/* loop through each field that we want to substitute values for:
			currently: text format and value */
			while (list($field_name, $field_array) = each($variable_fields)) {
				/* certain fields do not require values when the legend is not to be shown */
				if (($field_array["process_no_legend"] == false) && (isset($graph_data_array["graph_nolegend"]))) {
					continue;
				}

				$graph_variables[$field_name][$graph_item_id] = $graph_item[$field_name];

				/* date/time substitution */
				if (strstr($graph_variables[$field_name][$graph_item_id], "|date_time|")) {
					$graph_variables[$field_name][$graph_item_id] = str_replace("|date_time|", date(date_time_format(), strtotime(db_fetch_cell("select value from settings where name='date'"))), $graph_variables[$field_name][$graph_item_id]);
				}

				/* data source title substitution */
				if (strstr($graph_variables[$field_name][$graph_item_id], "|data_source_title|")) {
					$graph_variables[$field_name][$graph_item_id] = str_replace("|data_source_title|", get_data_source_title($graph_item["local_data_id"]), $graph_variables[$field_name][$graph_item_id]);
				}

				/* data query variables */
				$graph_variables[$field_name][$graph_item_id] = rrdgraph_substitute_host_query_data($graph_variables[$field_name][$graph_item_id], $graph, $graph_item);

				/* Nth percentile */
				if (preg_match_all("/\|([0-9]{1,2}):(bits|bytes):(\d):(current|total|max|total_peak|all_max_current|all_max_peak|aggregate_max|aggregate_sum|aggregate_current|aggregate):(\d)?\|/", $graph_variables[$field_name][$graph_item_id], $matches, PREG_SET_ORDER)) {
					foreach ($matches as $match) {
						$graph_variables[$field_name][$graph_item_id] = str_replace($match[0], variable_nth_percentile($match, $graph_item, $graph_items, $graph_start, $graph_end), $graph_variables[$field_name][$graph_item_id]);
					}
				}

				/* bandwidth summation */
				if (preg_match_all("/\|sum:(\d|auto):(current|total|atomic):(\d):(\d+|auto)\|/", $graph_variables[$field_name][$graph_item_id], $matches, PREG_SET_ORDER)) {
					foreach ($matches as $match) {
						$graph_variables[$field_name][$graph_item_id] = str_replace($match[0], variable_bandwidth_summation($match, $graph_item, $graph_items, $graph_start, $graph_end, $rra["steps"], $ds_step), $graph_variables[$field_name][$graph_item_id]);
					}
				}
			}
/* ------------------------------------*/







			/* if we are not displaying a legend there is no point in us even processing the auto padding,
			text format stuff. */
			if (!isset($graph_data_array["graph_nolegend"])) {
				/* set hard return variable if selected (\n) */
				if ($graph_item["hard_return"] == CHECKED) {
					$hardreturn[$graph_item_id] = "\\n";
				}else{
					$hardreturn[$graph_item_id] = "";
				}

				/* +++++++++++++++++++++++ LEGEND: AUTO PADDING (<>'s) +++++++++++++++++++++++ */
				if ($graph["auto_padding"] == CHECKED) {
					$greatest_text_format = rrdgraph_auto_padding($greatest_text_format, $graph_item["graph_type_id"], $graph_variables["text_format"][$graph_item_id]);
				}



				
			}

			$j++;
		}
	}

	/* +++++++++++++++++++++++ GRAPH ITEMS: CDEF's +++++++++++++++++++++++ */

	$i = 0;
	reset($graph_items);

	if (sizeof($graph_items) > 0) {
	foreach ($graph_items as $graph_item) {
		/* first we need to check if there is a DEF for the current data source/cf combination. if so,
		we will use that */
		if (isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}{$graph_item["consolidation_function_id"]})) {
			$cf_id = $graph_item["consolidation_function_id"];
		}else{
		/* if there is not a DEF defined for the current data source/cf combination, then we will have to
		improvise. choose the first available cf in the following order: AVERAGE, MAX, MIN, LAST */
			if (isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}[RRA_CF_TYPE_AVERAGE])) {
				$cf_id = RRA_CF_TYPE_AVERAGE; /* CF: AVERAGE */
			}elseif (isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}[RRA_CF_TYPE_MAX])) {
				$cf_id = RRA_CF_TYPE_MAX; /* CF: MAX */
			}elseif (isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}[RRA_CF_TYPE_MIN])) {
				$cf_id = RRA_CF_TYPE_MIN; /* CF: MIN */
			}elseif (isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}[RRA_CF_TYPE_LAST])) {
				$cf_id = RRA_CF_TYPE_LAST; /* CF: LAST */
			}else{
				$cf_id = RRA_CF_TYPE_AVERAGE; /* CF: AVERAGE */
			}
		}
		/* now remember the correct CF reference */
		$cf_id = $graph_item["cf_reference"];









/* new function */

		/* make cdef string here; a note about CDEF's in cacti. A CDEF is neither unique to a
		data source of global cdef, but is unique when those two variables combine. */
		$cdef_graph_defs = "";

		if ((!empty($graph_item["cdef_id"])) && (!isset($cdef_cache{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id]))) {

			$cdef_string 	= $graph_variables["cdef_cache"]{$graph_item["graph_templates_item_id"]};
			$magic_item 	= array();
			$already_seen	= array();
			$sources_seen	= array();
			$count_all_ds_dups = 0;
			$count_all_ds_nodups = 0;
			$count_similar_ds_dups = 0;
			$count_similar_ds_nodups = 0;

			/* if any of those magic variables are requested ... */
			if (preg_match("/(ALL_DATA_SOURCES_(NO)?DUPS|SIMILAR_DATA_SOURCES_(NO)?DUPS)/", $cdef_string) ||
				preg_match("/(COUNT_ALL_DS_(NO)?DUPS|COUNT_SIMILAR_DS_(NO)?DUPS)/", $cdef_string)) {

				/* now walk through each case to initialize array*/
				if (preg_match("/ALL_DATA_SOURCES_DUPS/", $cdef_string)) {
					$magic_item["ALL_DATA_SOURCES_DUPS"] = "";
				}
				if (preg_match("/ALL_DATA_SOURCES_NODUPS/", $cdef_string)) {
					$magic_item["ALL_DATA_SOURCES_NODUPS"] = "";
				}
				if (preg_match("/SIMILAR_DATA_SOURCES_DUPS/", $cdef_string)) {
					$magic_item["SIMILAR_DATA_SOURCES_DUPS"] = "";
				}
				if (preg_match("/SIMILAR_DATA_SOURCES_NODUPS/", $cdef_string)) {
					$magic_item["SIMILAR_DATA_SOURCES_NODUPS"] = "";
				}
				if (preg_match("/COUNT_ALL_DS_DUPS/", $cdef_string)) {
					$magic_item["COUNT_ALL_DS_DUPS"] = "";
				}
				if (preg_match("/COUNT_ALL_DS_NODUPS/", $cdef_string)) {
					$magic_item["COUNT_ALL_DS_NODUPS"] = "";
				}
				if (preg_match("/COUNT_SIMILAR_DS_DUPS/", $cdef_string)) {
					$magic_item["COUNT_SIMILAR_DS_DUPS"] = "";
				}
				if (preg_match("/COUNT_SIMILAR_DS_NODUPS/", $cdef_string)) {
					$magic_item["COUNT_SIMILAR_DS_NODUPS"] = "";
				}

				/* loop over all graph items */
				for ($t=0;($t<count($graph_items));$t++) {

					/* only work on graph items, omit GRPINTs, COMMENTs and stuff */
					if (($graph_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_AREA ||
						$graph_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_STACK ||
						$graph_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_LINE1 ||
						$graph_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_LINE2 ||
						$graph_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_LINE3 ||
						$graph_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_LINESTACK) &&
						(!empty($graph_items[$t]["data_template_rrd_id"]))) {
						/* if the user screws up CF settings, PHP will generate warnings if left unchecked */

						/* matching consolidation function? */
						if (isset($cf_ds_cache{$graph_items[$t]["data_template_rrd_id"]}[$cf_id])) {
							$def_name = generate_graph_def_name(strval($cf_ds_cache{$graph_items[$t]["data_template_rrd_id"]}[$cf_id]));

							/* do we need ALL_DATA_SOURCES_DUPS? */
							if (isset($magic_item["ALL_DATA_SOURCES_DUPS"])) {
								$magic_item["ALL_DATA_SOURCES_DUPS"] .= ($count_all_ds_dups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,$def_name,$def_name,UN,0,$def_name,IF,IF"; /* convert unknowns to '0' first */
							}

							/* do we need COUNT_ALL_DS_DUPS? */
							if (isset($magic_item["COUNT_ALL_DS_DUPS"])) {
								$magic_item["COUNT_ALL_DS_DUPS"] .= ($count_all_ds_dups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,1,$def_name,UN,0,1,IF,IF"; /* convert unknowns to '0' first */
							}

							$count_all_ds_dups++;

							/* check if this item also qualifies for NODUPS  */
							if(!isset($already_seen[$def_name])) {
								if (isset($magic_item["ALL_DATA_SOURCES_NODUPS"])) {
									$magic_item["ALL_DATA_SOURCES_NODUPS"] .= ($count_all_ds_nodups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,$def_name,$def_name,UN,0,$def_name,IF,IF"; /* convert unknowns to '0' first */
								}
								if (isset($magic_item["COUNT_ALL_DS_NODUPS"])) {
									$magic_item["COUNT_ALL_DS_NODUPS"] .= ($count_all_ds_nodups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,1,$def_name,UN,0,1,IF,IF"; /* convert unknowns to '0' first */
								}
								$count_all_ds_nodups++;
								$already_seen[$def_name]=TRUE;
							}

							/* check for SIMILAR data sources */
							if ($graph_item["data_source_name"] == $graph_items[$t]["data_source_name"]) {

								/* do we need SIMILAR_DATA_SOURCES_DUPS? */
								if (isset($magic_item["SIMILAR_DATA_SOURCES_DUPS"]) && ($graph_item["data_source_name"] == $graph_items[$t]["data_source_name"])) {
									$magic_item["SIMILAR_DATA_SOURCES_DUPS"] .= ($count_similar_ds_dups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,$def_name,$def_name,UN,0,$def_name,IF,IF"; /* convert unknowns to '0' first */
								}

								/* do we need COUNT_SIMILAR_DS_DUPS? */
								if (isset($magic_item["COUNT_SIMILAR_DS_DUPS"]) && ($graph_item["data_source_name"] == $graph_items[$t]["data_source_name"])) {
									$magic_item["COUNT_SIMILAR_DS_DUPS"] .= ($count_similar_ds_dups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,1,$def_name,UN,0,1,IF,IF"; /* convert unknowns to '0' first */
								}

								$count_similar_ds_dups++;

								/* check if this item also qualifies for NODUPS  */
								if(!isset($sources_seen{$graph_items[$t]["data_template_rrd_id"]})) {
									if (isset($magic_item["SIMILAR_DATA_SOURCES_NODUPS"])) {
										$magic_item["SIMILAR_DATA_SOURCES_NODUPS"] .= ($count_similar_ds_nodups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,$def_name,$def_name,UN,0,$def_name,IF,IF"; /* convert unknowns to '0' first */
									}
									if (isset($magic_item["COUNT_SIMILAR_DS_NODUPS"]) && ($graph_item["data_source_name"] == $graph_items[$t]["data_source_name"])) {
										$magic_item["COUNT_SIMILAR_DS_NODUPS"] .= ($count_similar_ds_nodups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,1,$def_name,UN,0,1,IF,IF"; /* convert unknowns to '0' first */
									}
									$count_similar_ds_nodups++;
									$sources_seen{$graph_items[$t]["data_template_rrd_id"]} = TRUE;
								}
							} # SIMILAR data sources
						} # matching consolidation function?
					} # only work on graph items, omit GRPINTs, COMMENTs and stuff
				} #  loop over all graph items

				/* if there is only one item to total, don't even bother with the summation.
				 * Otherwise cdef=a,b,c,+,+ is fine. */
				if ($count_all_ds_dups > 1 && isset($magic_item["ALL_DATA_SOURCES_DUPS"])) {
					$magic_item["ALL_DATA_SOURCES_DUPS"] .= str_repeat(",+", ($count_all_ds_dups - 2)) . ",+";
				}
				if ($count_all_ds_nodups > 1 && isset($magic_item["ALL_DATA_SOURCES_NODUPS"])) {
					$magic_item["ALL_DATA_SOURCES_NODUPS"] .= str_repeat(",+", ($count_all_ds_nodups - 2)) . ",+";
				}
				if ($count_similar_ds_dups > 1 && isset($magic_item["SIMILAR_DATA_SOURCES_DUPS"])) {
					$magic_item["SIMILAR_DATA_SOURCES_DUPS"] .= str_repeat(",+", ($count_similar_ds_dups - 2)) . ",+";
				}
				if ($count_similar_ds_nodups > 1 && isset($magic_item["SIMILAR_DATA_SOURCES_NODUPS"])) {
					$magic_item["SIMILAR_DATA_SOURCES_NODUPS"] .= str_repeat(",+", ($count_similar_ds_nodups - 2)) . ",+";
				}
				if ($count_all_ds_dups > 1 && isset($magic_item["COUNT_ALL_DS_DUPS"])) {
					$magic_item["COUNT_ALL_DS_DUPS"] .= str_repeat(",+", ($count_all_ds_dups - 2)) . ",+";
				}
				if ($count_all_ds_nodups > 1 && isset($magic_item["COUNT_ALL_DS_NODUPS"])) {
					$magic_item["COUNT_ALL_DS_NODUPS"] .= str_repeat(",+", ($count_all_ds_nodups - 2)) . ",+";
				}
				if ($count_similar_ds_dups > 1 && isset($magic_item["COUNT_SIMILAR_DS_DUPS"])) {
					$magic_item["COUNT_SIMILAR_DS_DUPS"] .= str_repeat(",+", ($count_similar_ds_dups - 2)) . ",+";
				}
				if ($count_similar_ds_nodups > 1 && isset($magic_item["COUNT_SIMILAR_DS_NODUPS"])) {
					$magic_item["COUNT_SIMILAR_DS_NODUPS"] .= str_repeat(",+", ($count_similar_ds_nodups - 2)) . ",+";
				}
			}

			$cdef_string = str_replace("CURRENT_DATA_SOURCE", generate_graph_def_name(strval((isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}[$cf_id]) ? $cf_ds_cache{$graph_item["data_template_rrd_id"]}[$cf_id] : "0"))), $cdef_string);

			/* ALL|SIMILAR_DATA_SOURCES(NO)?DUPS are to be replaced here */
			if (isset($magic_item["ALL_DATA_SOURCES_DUPS"])) {
				$cdef_string = str_replace("ALL_DATA_SOURCES_DUPS", $magic_item["ALL_DATA_SOURCES_DUPS"], $cdef_string);
			}
			if (isset($magic_item["ALL_DATA_SOURCES_NODUPS"])) {
				$cdef_string = str_replace("ALL_DATA_SOURCES_NODUPS", $magic_item["ALL_DATA_SOURCES_NODUPS"], $cdef_string);
			}
			if (isset($magic_item["SIMILAR_DATA_SOURCES_DUPS"])) {
				$cdef_string = str_replace("SIMILAR_DATA_SOURCES_DUPS", $magic_item["SIMILAR_DATA_SOURCES_DUPS"], $cdef_string);
			}
			if (isset($magic_item["SIMILAR_DATA_SOURCES_NODUPS"])) {
				$cdef_string = str_replace("SIMILAR_DATA_SOURCES_NODUPS", $magic_item["SIMILAR_DATA_SOURCES_NODUPS"], $cdef_string);
			}

			/* COUNT_ALL|SIMILAR_DATA_SOURCES(NO)?DUPS are to be replaced here */
			if (isset($magic_item["COUNT_ALL_DS_DUPS"])) {
				$cdef_string = str_replace("COUNT_ALL_DS_DUPS", $magic_item["COUNT_ALL_DS_DUPS"], $cdef_string);
			}
			if (isset($magic_item["COUNT_ALL_DS_NODUPS"])) {
				$cdef_string = str_replace("COUNT_ALL_DS_NODUPS", $magic_item["COUNT_ALL_DS_NODUPS"], $cdef_string);
			}
			if (isset($magic_item["COUNT_SIMILAR_DS_DUPS"])) {
				$cdef_string = str_replace("COUNT_SIMILAR_DS_DUPS", $magic_item["COUNT_SIMILAR_DS_DUPS"], $cdef_string);
			}
			if (isset($magic_item["COUNT_SIMILAR_DS_NODUPS"])) {
				$cdef_string = str_replace("COUNT_SIMILAR_DS_NODUPS", $magic_item["COUNT_SIMILAR_DS_NODUPS"], $cdef_string);
			}

			/* data source item variables */
			$cdef_string = str_replace("CURRENT_DS_MINIMUM_VALUE", (empty($graph_item["rrd_minimum"]) ? "0" : $graph_item["rrd_minimum"]), $cdef_string);
			$cdef_string = str_replace("CURRENT_DS_MAXIMUM_VALUE", (empty($graph_item["rrd_maximum"]) ? "0" : $graph_item["rrd_maximum"]), $cdef_string);
			$cdef_string = str_replace("CURRENT_GRAPH_MINIMUM_VALUE", (empty($graph["lower_limit"]) ? "0" : $graph["lower_limit"]), $cdef_string);
			$cdef_string = str_replace("CURRENT_GRAPH_MAXIMUM_VALUE", (empty($graph["upper_limit"]) ? "0" : $graph["upper_limit"]), $cdef_string);
			$_time_shift_start = strtotime(read_graph_config_option("day_shift_start")) - strtotime("00:00");
			$_time_shift_end = strtotime(read_graph_config_option("day_shift_end")) - strtotime("00:00");
			$cdef_string = str_replace("TIME_SHIFT_START", (empty($_time_shift_start) ? "64800" : $_time_shift_start), $cdef_string);
			$cdef_string = str_replace("TIME_SHIFT_END", (empty($_time_shift_end) ? "28800" : $_time_shift_end), $cdef_string);

			/* replace query variables in cdefs */
			$cdef_string = rrdgraph_substitute_host_query_data($cdef_string, $graph, $graph_item);

			/* make the initial "virtual" cdef name: 'cdef' + [a,b,c,d...] */
			$cdef_graph_defs .= "CDEF:cdef" . generate_graph_def_name(strval($i)) . "=";
			$cdef_graph_defs .= cacti_escapeshellarg(sanitize_cdef($cdef_string), true);
			$cdef_graph_defs .= " \\\n";

			/* the CDEF cache is so we do not create duplicate CDEF's on a graph */
			$cdef_cache{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id] = "$i";
		}

		/* add the cdef string to the end of the def string */
		$graph_defs .= $cdef_graph_defs;

		/* +++++++++++++++++++++++ GRAPH ITEMS: VDEF's +++++++++++++++++++++++ */
/* new function */
		if ($rrdtool_version != RRD_VERSION_1_0) {

			/* make vdef string here, copied from cdef stuff */
			$vdef_graph_defs = "";

			if ((!empty($graph_item["vdef_id"])) && (!isset($vdef_cache{$graph_item["vdef_id"]}{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id]))) {
				$vdef_string = $graph_variables["vdef_cache"]{$graph_item["graph_templates_item_id"]};
				if ($graph_item["cdef_id"] != "0") {
					/* "calculated" VDEF: use (cached) CDEF as base, only way to get calculations into VDEFs, lvm */
					$vdef_string = "cdef" . str_replace("CURRENT_DATA_SOURCE", generate_graph_def_name(strval(isset($cdef_cache{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id]) ? $cdef_cache{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id] : "0")), $vdef_string);
			 	} else {
					/* "pure" VDEF: use DEF as base */
					$vdef_string = str_replace("CURRENT_DATA_SOURCE", generate_graph_def_name(strval(isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}[$cf_id]) ? $cf_ds_cache{$graph_item["data_template_rrd_id"]}[$cf_id] : "0")), $vdef_string);
				}
				# It would be possible to refer to a CDEF, but that's all. So ALL_DATA_SOURCES_NODUPS and stuff can't be used directly!
				#$vdef_string = str_replace("ALL_DATA_SOURCES_NODUPS", $magic_item["ALL_DATA_SOURCES_NODUPS"], $vdef_string);
				#$vdef_string = str_replace("ALL_DATA_SOURCES_DUPS", $magic_item["ALL_DATA_SOURCES_DUPS"], $vdef_string);
				#$vdef_string = str_replace("SIMILAR_DATA_SOURCES_NODUPS", $magic_item["SIMILAR_DATA_SOURCES_NODUPS"], $vdef_string);
				#$vdef_string = str_replace("SIMILAR_DATA_SOURCES_DUPS", $magic_item["SIMILAR_DATA_SOURCES_DUPS"], $vdef_string);

				/* make the initial "virtual" vdef name */
				$vdef_graph_defs .= "VDEF:vdef" . generate_graph_def_name(strval($i)) . "=";
				$vdef_graph_defs .= cacti_escapeshellarg(sanitize_cdef($vdef_string));
				$vdef_graph_defs .= " \\\n";

				/* the VDEF cache is so we do not create duplicate VDEF's on a graph,
				 * but take info account, that same VDEF may use different CDEFs
				 * so index over VDEF_ID, CDEF_ID per DATA_TEMPLATE_RRD_ID, lvm */
				$vdef_cache{$graph_item["vdef_id"]}{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id] = "$i";
			}

			/* add the cdef string to the end of the def string */
			$graph_defs .= $vdef_graph_defs;
		}
















		/* note the current item_id for easy access */
		$graph_item_id = $graph_item["graph_templates_item_id"];

		/* if we are not displaying a legend there is no point in us even processing the auto padding,
		text format stuff. */
		if ((!isset($graph_data_array["graph_nolegend"])) && ($graph["auto_padding"] == CHECKED)) {
			/* only applies to AREA, STACK and LINEs */
			if ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_AREA ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_STACK ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE1 ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE2 ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE3 ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINESTACK ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_TICK) {
				$text_format_length = strlen($graph_variables["text_format"][$graph_item_id]);

				/* we are basing how much to pad on area and stack text format,
				not gprint. but of course the padding has to be displayed in gprint,
				how fun! */

				$pad_number = ($greatest_text_format - $text_format_length);
				//cacti_log("MAX: $greatest_text_format, CURR: $text_format_lengths[$item_dsid], DSID: $item_dsid");
				$text_padding = str_pad("", $pad_number);

			/* two GPRINT's in a row screws up the padding, lets not do that */
			} else if (($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_AVERAGE ||
						$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_LAST ||
						$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_MAX ||
						$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_MIN) && (
						$last_graph_type == GRAPH_ITEM_TYPE_GPRINT_AVERAGE ||
						$last_graph_type == GRAPH_ITEM_TYPE_GPRINT_LAST ||
						$last_graph_type == GRAPH_ITEM_TYPE_GPRINT_MAX ||
						$last_graph_type == GRAPH_ITEM_TYPE_GPRINT_MIN)) {
				$text_padding = "";
			}

			$last_graph_type = $graph_item["graph_type_id"];
		}





		/* we put this in a variable so it can be manipulated before mainly used
		if we want to skip it, like below */
		$current_graph_item_type = $graph_item["graph_type_id"];

		/* IF this graph item has a data source... get a DEF name for it, or the cdef if that applies
		to this graph item */
		if ($graph_item["cdef_id"] == "0") {
			if (isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}[$cf_id])) {
				$data_source_name = generate_graph_def_name(strval($cf_ds_cache{$graph_item["data_template_rrd_id"]}[$cf_id]));
			}else{
				$data_source_name = "";
			}
		}else{
			$data_source_name = "cdef" . generate_graph_def_name(strval($cdef_cache{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id]));
		}

		/* IF this graph item has a data source... get a DEF name for it, or the vdef if that applies
		to this graph item */
		if ($graph_item["vdef_id"] == "0") {
			/* do not overwrite $data_source_name that stems from cdef above */
		}else{
			$data_source_name = "vdef" . generate_graph_def_name(strval($vdef_cache{$graph_item["vdef_id"]}{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id]));
		}






		/* to make things easier... if there is no text format set; set blank text */
		if (!isset($graph_variables["text_format"][$graph_item_id])) {
			$graph_variables["text_format"][$graph_item_id] = "";
		} else {
			$graph_variables["text_format"][$graph_item_id] = str_replace(':', '\:', $graph_variables["text_format"][$graph_item_id]); /* escape colons */
			$graph_variables["text_format"][$graph_item_id] = str_replace('"', '\"', $graph_variables["text_format"][$graph_item_id]); /* escape doublequotes */
		}

		if (!isset($hardreturn[$graph_item_id])) {
			$hardreturn[$graph_item_id] = "";
		}




		/* +++++++++++++++++++++++ GRAPH ITEMS +++++++++++++++++++++++ */
/* graph_item["line_width"] = rrdtool_init_line_width
 * graph_item["color_code"] = rrdtool_init_color_code
 * graph_item["dash"] = rrdtool_init_dash
 */
		/* most of the calculations have been done above. now we have for print everything out
		in an RRDTool-friendly fashion */
		$need_rrd_nl = TRUE;

		/* initialize line width support */
		if ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE1 ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE2 ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE3) {
			if ($rrdtool_version == RRD_VERSION_1_0) {
				# round line_width to 1 <= line_width <= 3
				if ($graph_item["line_width"] < 1) {$graph_item["line_width"] = 1;}
				if ($graph_item["line_width"] > 3) {$graph_item["line_width"] = 3;}

				$graph_item["line_width"] = intval($graph_item["line_width"]);
			}
		}

		/* initialize color support */
		$graph_item_color_code = "";
		if (!empty($graph_item["hex"])) {
			$graph_item_color_code = "#" . $graph_item["hex"];
			if ($rrdtool_version != RRD_VERSION_1_0) {
				$graph_item_color_code .= $graph_item["alpha"];
			}
		}


		/* initialize dash support */
		$dash = "";
		if ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE1 ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE2 ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE3 ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINESTACK ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_HRULE ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_VRULE) {
			if ($rrdtool_version != RRD_VERSION_1_0 &&
				$rrdtool_version != RRD_VERSION_1_2) {
				if (!empty($graph_item["dashes"])) {
					$dash .= ":dashes=" . $graph_item["dashes"];
				}
				if (!empty($graph_item["dash_offset"])) {
					$dash .= ":dash-offset=" . $graph_item["dash_offset"];
				}
			}
		}



/* subroutine */

		switch($graph_item["graph_type_id"]) {
			case GRAPH_ITEM_TYPE_COMMENT:
				if (!isset($graph_data_array["graph_nolegend"])) {
					$comment_string = $graph_item_types{$graph_item["graph_type_id"]} . ":\"" .
						substr(rrdgraph_substitute_host_query_data(str_replace(":", "\:", $graph_variables["text_format"][$graph_item_id]), $graph, $graph_item),0,198) .
						$hardreturn[$graph_item_id] . "\" ";
					if (trim($comment_string) == 'COMMENT:"\n"') {
						$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ':" \n"'; # rrdtool will skip a COMMENT that holds a NL only; so add a blank to make NL work
					}elseif (trim($comment_string) != "COMMENT:\"\"") {
						$txt_graph_items .= $comment_string;
					}
				}
				break;


			case GRAPH_ITEM_TYPE_TEXTALIGN:
				if (!empty($graph_item["textalign"]) &&
					$rrdtool_version != RRD_VERSION_1_0 &&
					$rrdtool_version != RRD_VERSION_1_2) {
						$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . $graph_item["textalign"];
					}
				break;


			case GRAPH_ITEM_TYPE_GPRINT_AVERAGE:
				if (!isset($graph_data_array["graph_nolegend"])) {
					/* rrdtool 1.2.x VDEFs must suppress the consolidation function on GPRINTs */
					if ($rrdtool_version != RRD_VERSION_1_0) {
						if ($graph_item["vdef_id"] == "0") {
							$txt_graph_items .= "GPRINT:" . $data_source_name . ":AVERAGE:\"$text_padding" . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $hardreturn[$graph_item_id] . "\" ";
						}else{
							$txt_graph_items .= "GPRINT:" . $data_source_name . ":\"$text_padding" . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $hardreturn[$graph_item_id] . "\" ";
						}
					}else {
						$txt_graph_items .= "GPRINT:" . $data_source_name . ":AVERAGE:\"$text_padding" . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $hardreturn[$graph_item_id] . "\" ";
					}
				}
				break;


			case GRAPH_ITEM_TYPE_GPRINT_LAST:
				if (!isset($graph_data_array["graph_nolegend"])) {
					/* rrdtool 1.2.x VDEFs must suppress the consolidation function on GPRINTs */
					if ($rrdtool_version != RRD_VERSION_1_0) {
						if ($graph_item["vdef_id"] == "0") {
							$txt_graph_items .= "GPRINT:" . $data_source_name . ":LAST:\"$text_padding" . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $hardreturn[$graph_item_id] . "\" ";
						}else{
							$txt_graph_items .= "GPRINT:" . $data_source_name . ":\"$text_padding" . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $hardreturn[$graph_item_id] . "\" ";
						}
					}else {
						$txt_graph_items .= "GPRINT:" . $data_source_name . ":LAST:\"$text_padding" . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $hardreturn[$graph_item_id] . "\" ";
					}
				}
				break;


			case GRAPH_ITEM_TYPE_GPRINT_MAX:
				if (!isset($graph_data_array["graph_nolegend"])) {
					/* rrdtool 1.2.x VDEFs must suppress the consolidation function on GPRINTs */
					if ($rrdtool_version != RRD_VERSION_1_0) {
						if ($graph_item["vdef_id"] == "0") {
							$txt_graph_items .= "GPRINT:" . $data_source_name . ":MAX:\"$text_padding" . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $hardreturn[$graph_item_id] . "\" ";
						}else{
							$txt_graph_items .= "GPRINT:" . $data_source_name . ":\"$text_padding" . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $hardreturn[$graph_item_id] . "\" ";
						}
					}else {
						$txt_graph_items .= "GPRINT:" . $data_source_name . ":MAX:\"$text_padding" . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $hardreturn[$graph_item_id] . "\" ";
					}
				}
				break;


			case GRAPH_ITEM_TYPE_GPRINT_MIN:
				if (!isset($graph_data_array["graph_nolegend"])) {
					/* rrdtool 1.2.x VDEFs must suppress the consolidation function on GPRINTs */
					if ($rrdtool_version != RRD_VERSION_1_0) {
						if ($graph_item["vdef_id"] == "0") {
							$txt_graph_items .= "GPRINT:" . $data_source_name . ":MIN:\"$text_padding" . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $hardreturn[$graph_item_id] . "\" ";
						}else{
							$txt_graph_items .= "GPRINT:" . $data_source_name . ":\"$text_padding" . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $hardreturn[$graph_item_id] . "\" ";
						}
					}else {
						$txt_graph_items .= "GPRINT:" . $data_source_name . ":MIN:\"$text_padding" . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $hardreturn[$graph_item_id] . "\" ";
					}
				}
				break;


			case GRAPH_ITEM_TYPE_AREA:
				$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . $data_source_name . $graph_item_color_code . ":" . "\"" . $graph_variables["text_format"][$graph_item_id] . $hardreturn[$graph_item_id] . "\" ";
				if ($graph_item["shift"] == CHECKED && $graph_item["value"] > 0) {	# create a SHIFT statement
					$txt_graph_items .= RRD_NL . "SHIFT:" . $data_source_name . ":" . $graph_item["value"];
				}
				break;


			case GRAPH_ITEM_TYPE_STACK:
				if ($rrdtool_version != RRD_VERSION_1_0) {
					$txt_graph_items .= "AREA:" . $data_source_name . $graph_item_color_code . ":" . "\"" . $graph_variables["text_format"][$graph_item_id] . $hardreturn[$graph_item_id] . "\":STACK";
				}else {
					$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . $data_source_name . $graph_item_color_code . ":" . "\"" . $graph_variables["text_format"][$graph_item_id] . $hardreturn[$graph_item_id] . "\" ";
				}
				if ($graph_item["shift"] == CHECKED && $graph_item["value"] > 0) {	# create a SHIFT statement
					$txt_graph_items .= RRD_NL . "SHIFT:" . $data_source_name . ":" . $graph_item["value"];
				}
				break;


			case GRAPH_ITEM_TYPE_LINE1:
			case GRAPH_ITEM_TYPE_LINE2:
			case GRAPH_ITEM_TYPE_LINE3:
				$txt_graph_items .= "LINE" . $graph_item["line_width"] . ":" . $data_source_name . $graph_item_color_code . ":" . "\"" . $graph_variables["text_format"][$graph_item_id] . $hardreturn[$graph_item_id] . "\"" . $dash;
				if ($graph_item["shift"] == CHECKED && $graph_item["value"] > 0) {	# create a SHIFT statement
					$txt_graph_items .= RRD_NL . "SHIFT:" . $data_source_name . ":" . $graph_item["value"];
				}
				break;


			case GRAPH_ITEM_TYPE_LINESTACK:
				if ($rrdtool_version != RRD_VERSION_1_0) {
					$txt_graph_items .= "LINE" . $graph_item["line_width"] . ":" . $data_source_name . $graph_item_color_code . ":" . "\"" . $graph_variables["text_format"][$graph_item_id] . $hardreturn[$graph_item_id] . "\":STACK" . $dash;
				}
				if ($graph_item["shift"] == CHECKED && $graph_item["value"] > 0) {	# create a SHIFT statement
					$txt_graph_items .= RRD_NL . "SHIFT:" . $data_source_name . ":" . $graph_item["value"];
				}
				break;


			case GRAPH_ITEM_TYPE_TICK:
				if ($rrdtool_version != RRD_VERSION_1_0) {
					$_fraction 	= (empty($graph_item["graph_type_id"]) 						? "" : (":" . $graph_item["value"]));
					$_legend 	= (empty($graph_variables["text_format"][$graph_item_id]) 	? "" : (":" . "\"" . $graph_variables["text_format"][$graph_item_id] . $hardreturn[$graph_item_id] . "\""));
					$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . $data_source_name . $graph_item_color_code . $_fraction . $_legend;
				}
				break;


			case GRAPH_ITEM_TYPE_HRULE:
				$graph_variables["value"][$graph_item_id] = str_replace(":", "\:", $graph_variables["value"][$graph_item_id]); /* escape colons */
				/* perform variable substitution; if this does not return a number, rrdtool will FAIL! */
				$substitute = rrdgraph_substitute_host_query_data($graph_variables["value"][$graph_item_id], $graph, $graph_item);
				if (is_numeric($substitute)) {
					$graph_variables["value"][$graph_item_id] = $substitute;
				}
				$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . $graph_variables["value"][$graph_item_id] . $graph_item_color_code . ":\"" . $graph_variables["text_format"][$graph_item_id] . $hardreturn[$graph_item_id] . "\"" . $dash;
				break;


			case GRAPH_ITEM_TYPE_VRULE:
				if (substr_count($graph_item["value"], ":")) {
					$value_array = explode(":", $graph_item["value"]);

					if ($value_array[0] < 0) {
						$value = date("U") - (-3600 * $value_array[0]) - 60 * $value_array[1];
					}else{
						$value = date("U", mktime($value_array[0],$value_array[1],0));
					}
				}else if (is_numeric($graph_item["value"])) {
					$value = $graph_item["value"];
				}

				$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . $value . $graph_item_color_code . ":\"" . $graph_variables["text_format"][$graph_item_id] . $hardreturn[$graph_item_id] . "\"" . $dash;
				break;


			default:
				$need_rrd_nl = FALSE;

		}

		$i++;

		if (($i < sizeof($graph_items)) && ($need_rrd_nl)) {
			$txt_graph_items .= RRD_NL;
		}
	}
	}







/* put everything together, just to mimic the old behavior */

/* pass all arrays to plugin hook */

	$graph_array = plugin_hook_function('rrd_graph_graph_options', array('graph_opts' => $graph_opts, 'graph_defs' => $graph_defs, 'txt_graph_items' => $txt_graph_items, 'graph_id' => $local_graph_id, 'start' => $graph_start, 'end' => $graph_end));




	if (!empty($graph_array)) {

		/* display the timespan for zoomed graphs,
		 * but prefer any setting made by the user */
		$graph_array['txt_graph_items'] = substitute_graph_data($graph_array['txt_graph_items'], $graph, $graph_data_array, $rrdtool_version);

		$graph_defs = $graph_array['graph_defs'];
		$txt_graph_items = $graph_array['txt_graph_items'];
		$graph_opts = $graph_array['graph_opts'];
	}

	/* either print out the source or pass the source onto rrdtool to get us a nice graph */
	if (isset($graph_data_array["print_source"])) {
		# since pango markup allows for <span> tags, we need to escape this stuff using htmlspecialchars
		print htmlspecialchars(read_config_option("path_rrdtool") . " graph $graph_opts$graph_defs$txt_graph_items");
	}else{
		if (isset($graph_data_array["export"])) {
			rrdtool_execute("graph $graph_opts$graph_defs$txt_graph_items", false, RRDTOOL_OUTPUT_NULL, $rrdtool_pipe);
			return 0;
		}else{
			$graph_data_array = plugin_hook_function('prep_graph_array', $graph_data_array);

			if (isset($graph_data_array["output_flag"])) {
				$output_flag = $graph_data_array["output_flag"];
			}else{
				$output_flag = RRDTOOL_OUTPUT_GRAPH_DATA;
			}
			$output = rrdtool_execute("graph $graph_opts$graph_defs$txt_graph_items", false, $output_flag, $rrdtool_pipe);

			plugin_hook_function('rrdtool_function_graph_set_file', array('output' => $output, 'local_graph_id' => $local_graph_id, 'rra_id' => $rra_id));

			return $output;
		}
	}
}


/** export rrd data
 * @param int $local_graph_id - id of graph
 * @param int $rra_id - id of rra
 * @param array $xport_data_array - predefined data for xport (start, end time) 
 * @param array $xport_meta - additional meta data to be returned
 */
function rrdtool_function_xport($local_graph_id, $rra_id, $xport_data_array, &$xport_meta) {
	require(CACTI_INCLUDE_PATH . "/presets/preset_rra_arrays.php");

	include_once(CACTI_LIBRARY_PATH . "/cdef.php");
	include_once(CACTI_LIBRARY_PATH . "/graph_variables.php");
	include_once(CACTI_LIBRARY_PATH . "/xml.php");
	include(CACTI_INCLUDE_PATH . "/global_arrays.php");

	/* before we do anything; make sure the user has permission to view this graph,
	if not then get out */
	if ((read_config_option("auth_method") != AUTH_METHOD_NONE) && (isset($_SESSION["sess_user_id"]))) {
		$access_denied = !(is_graph_allowed($local_graph_id));

		if ($access_denied == true) {
			return "GRAPH ACCESS DENIED";
		}
	}

	/* find the step and how often this graph is updated with new data */
	$ds_step = db_fetch_cell("select
		data_template_data.rrd_step
		from (data_template_data,data_template_rrd,graph_templates_item)
		where graph_templates_item.task_item_id=data_template_rrd.id
		and data_template_rrd.local_data_id=data_template_data.local_data_id
		and graph_templates_item.local_graph_id=$local_graph_id
		limit 0,1");
	$ds_step = empty($ds_step) ? 300 : $ds_step;

	/* if no rra was specified, we need to figure out which one RRDTool will choose using
	 * "best-fit" resolution fit algorithm */
	if (empty($rra_id)) {
		if ((empty($xport_data_array["graph_start"])) || (empty($xport_data_array["graph_end"]))) {
			$rra["rows"] = 600;
			$rra["steps"] = 1;
			$rra["timespan"] = 86400;
		}else{
			/* get a list of RRAs related to this graph */
			$rras = get_associated_rras($local_graph_id);

			if (sizeof($rras) > 0) {
				foreach ($rras as $unchosen_rra) {
					/* the timespan specified in the RRA "timespan" field may not be accurate */
					$real_timespan = ($ds_step * $unchosen_rra["steps"] * $unchosen_rra["rows"]);

					/* make sure the current start/end times fit within each RRA's timespan */
					if ( (($xport_data_array["graph_end"] - $xport_data_array["graph_start"]) <= $real_timespan) && ((time() - $xport_data_array["graph_start"]) <= $real_timespan) ) {
						/* is this RRA better than the already chosen one? */
						if ((isset($rra)) && ($unchosen_rra["steps"] < $rra["steps"])) {
							$rra = $unchosen_rra;
						}else if (!isset($rra)) {
							$rra = $unchosen_rra;
						}
					}
				}
			}

			if (!isset($rra)) {
				$rra["rows"] = 600;
				$rra["steps"] = 1;
			}
		}
	}else{
		$rra = db_fetch_row("select timespan,rows,steps from rra where id=$rra_id");
	}

	$seconds_between_graph_updates = ($ds_step * $rra["steps"]);

	/* override: graph start time */
	if ((!isset($xport_data_array["graph_start"])) || ($xport_data_array["graph_start"] == "0")) {
		$graph_start = -($rra["timespan"]);
	}else{
		$graph_start = $xport_data_array["graph_start"];
	}

	/* override: graph end time */
	if ((!isset($xport_data_array["graph_end"])) || ($xport_data_array["graph_end"] == "0")) {
		$graph_end = -($seconds_between_graph_updates);
	}else{
		$graph_end = $xport_data_array["graph_end"];
	}

	$graph = db_fetch_row("select
		graph_local.id AS local_graph_id,
		graph_local.host_id,
		graph_local.snmp_query_id,
		graph_local.snmp_index,
		graph_templates_graph.title_cache,
		graph_templates_graph.vertical_label,
		graph_templates_graph.slope_mode,
		graph_templates_graph.auto_scale,
		graph_templates_graph.auto_scale_opts,
		graph_templates_graph.auto_scale_log,
		graph_templates_graph.scale_log_units,
		graph_templates_graph.auto_scale_rigid,
		graph_templates_graph.alt_y_grid,
		graph_templates_graph.auto_padding,
		graph_templates_graph.base_value,
		graph_templates_graph.upper_limit,
		graph_templates_graph.lower_limit,
		graph_templates_graph.height,
		graph_templates_graph.width,
		graph_templates_graph.image_format_id,
		graph_templates_graph.unit_value,
		graph_templates_graph.unit_exponent_value,
		graph_templates_graph.export
		from (graph_templates_graph,graph_local)
		where graph_local.id=graph_templates_graph.local_graph_id
		and graph_templates_graph.local_graph_id=$local_graph_id");

	/* lets make that sql query... */
	$xport_items = db_fetch_assoc("select
		graph_templates_item.id as graph_templates_item_id,
		graph_templates_item.cdef_id,
		graph_templates_item.vdef_id,
		graph_templates_item.text_format,
		graph_templates_item.value,
		graph_templates_item.hard_return,
		graph_templates_item.consolidation_function_id,
		graph_templates_item.graph_type_id,
		graph_templates_item.line_width,
		graph_templates_item.dashes,
		graph_templates_item.dash_offset,
		graph_templates_item.shift,
		graph_templates_item.textalign,
		graph_templates_gprint.gprint_text,
		colors.hex,
		graph_templates_item.alpha,
		data_template_rrd.id as data_template_rrd_id,
		data_template_rrd.local_data_id,
		data_template_rrd.rrd_minimum,
		data_template_rrd.rrd_maximum,
		data_template_rrd.data_source_name,
		data_template_rrd.local_data_template_rrd_id
		from graph_templates_item
		left join data_template_rrd on (graph_templates_item.task_item_id=data_template_rrd.id)
		left join colors on (graph_templates_item.color_id=colors.id)
		left join graph_templates_gprint on (graph_templates_item.gprint_id=graph_templates_gprint.id)
		where graph_templates_item.local_graph_id=$local_graph_id
		order by graph_templates_item.sequence");

	/* +++++++++++++++++++++++ XPORT OPTIONS +++++++++++++++++++++++ */

	/* override: graph start time */
	if ((!isset($xport_data_array["graph_start"])) || ($xport_data_array["graph_start"] == "0")) {
		$xport_start = -($rra["timespan"]);
	}else{
		$xport_start = $xport_data_array["graph_start"];
	}

	/* override: graph end time */
	if ((!isset($xport_data_array["graph_end"])) || ($xport_data_array["graph_end"] == "0")) {
		$xport_end = -($seconds_between_graph_updates);
	}else{
		$xport_end = $xport_data_array["graph_end"];
	}

	/* basic export options */
	$xport_opts =
		"--start=$xport_start" . RRD_NL .
		"--end=$xport_end" . RRD_NL .
		"--maxrows=10000" . RRD_NL;

	$xport_defs = "";

	$i = 0; $j = 0;
	$nth = 0; $sum = 0;
	if (sizeof($xport_items) > 0) {
		/* we need to add a new column "cf_reference", so unless PHP 5 is used, this foreach syntax is required */
		foreach ($xport_items as $key => $xport_item) {
			/* mimic the old behavior: LINE[123], AREA, STACK and GPRINT items use the CF specified in the graph item */
			if ($xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_AREA  ||
				$xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_STACK ||
				$xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE1 ||
				$xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE2 ||
				$xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE3 ||
				$xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINESTACK) {
				$xport_cf = $xport_item["consolidation_function_id"];
				$last_xport_cf["data_source_name"]["local_data_template_rrd_id"] = $xport_cf;
				/* remember this for second foreach loop */
				$xport_items[$key]["cf_reference"] = $xport_cf;
			}elseif ($xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_AVERAGE) {
				$graph_cf = $xport_item["consolidation_function_id"];
				$xport_items[$key]["cf_reference"] = $graph_cf;
			}elseif ($xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_LAST) {
				$graph_cf = $xport_item["consolidation_function_id"];
				$xport_items[$key]["cf_reference"] = $graph_cf;
			}elseif ($xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_MAX) {
				$graph_cf = $xport_item["consolidation_function_id"];
				$xport_items[$key]["cf_reference"] = $graph_cf;
			}elseif ($xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_MIN) {
				$graph_cf = $xport_item["consolidation_function_id"];
				$xport_items[$key]["cf_reference"] = $graph_cf;
			#}elseif ($xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT) {
				#/* ATTENTION!
				# * the "CF" given on graph_item edit screen for GPRINT is indeed NOT a real "CF",
				# * but an aggregation function
				# * see "man rrdgraph_data" for the correct VDEF based notation
				# * so our task now is to "guess" the very graph_item, this GPRINT is related to
				# * and to use that graph_item's CF */
				#if (isset($last_xport_cf["data_source_name"]["local_data_template_rrd_id"])) {
				#	$xport_cf = $xport_item["data_source_name"]["local_data_template_rrd_id"];
				#	/* remember this for second foreach loop */
				#	$xport_items[$key]["cf_reference"] = $xport_cf;
				#} else {
				#	$xport_cf = generate_graph_best_cf($xport_item["local_data_id"], $xport_item["consolidation_function_id"]);
				#	/* remember this for second foreach loop */
				#	$xport_items[$key]["cf_reference"] = $xport_cf;
				#}
			}else{
				/* all other types are based on the best matching CF */
				$xport_cf = generate_graph_best_cf($xport_item["local_data_id"], $xport_item["consolidation_function_id"]);
				/* remember this for second foreach loop */
				$xport_items[$key]["cf_reference"] = $xport_cf;
			}

			if ((!empty($xport_item["local_data_id"])) &&
				(!isset($cf_ds_cache{$xport_item["data_template_rrd_id"]}[$xport_cf]))) {
				/* use a user-specified ds path if one is entered */
				$data_source_path = get_data_source_path($xport_item["local_data_id"], true);

				/* FOR WIN32: Escape all colon for drive letters (ex. D\:/path/to/rra) */
				$data_source_path = str_replace(":", "\:", $data_source_path);

				if (!empty($data_source_path)) {
					/* NOTE: (Update) Data source DEF names are created using the graph_item_id; then passed
					to a function that matches the digits with letters. rrdtool likes letters instead
					of numbers in DEF names; especially with CDEF's. cdef's are created
					the same way, except a 'cdef' is put on the beginning of the hash */
					$xport_defs .= "DEF:" . generate_graph_def_name(strval($i)) . "=\"$data_source_path\":" . cacti_escapeshellarg($xport_item["data_source_name"]) . ":" . $consolidation_functions[$xport_cf] . RRD_NL;

					$cf_ds_cache{$xport_item["data_template_rrd_id"]}[$xport_cf] = "$i";

					$i++;
				}
			}

			/* cache cdef value here to support data query variables in the cdef string */
			if (empty($xport_item["cdef_id"])) {
				$xport_item["cdef_cache"] = "";
				$xport_items[$j]["cdef_cache"] = "";
			}else{
				$xport_item["cdef_cache"] = get_cdef($xport_item["cdef_id"]);
				$xport_items[$j]["cdef_cache"] = get_cdef($xport_item["cdef_id"]);
			}

			/* +++++++++++++++++++++++ LEGEND: TEXT SUBSTITUTION (<>'s) +++++++++++++++++++++++ */

			/* note the current item_id for easy access */
			$xport_item_id = $xport_item["graph_templates_item_id"];

			/* the following fields will be searched for graph variables */
			$variable_fields = array(
				"text_format" => array(
					"process_no_legend" => false
					),
				"value" => array(
					"process_no_legend" => true
					),
				"cdef_cache" => array(
					"process_no_legend" => true
					)
				);

			/* loop through each field that we want to substitute values for:
			currently: text format and value */
			while (list($field_name, $field_array) = each($variable_fields)) {
				/* certain fields do not require values when the legend is not to be shown */
				if (($field_array["process_no_legend"] == false) && (isset($xport_data_array["graph_nolegend"]))) {
					continue;
				}

				$xport_variables[$field_name][$xport_item_id] = $xport_item[$field_name];

				/* date/time substitution */
				if (strstr($xport_variables[$field_name][$xport_item_id], "|date_time|")) {
					$xport_variables[$field_name][$xport_item_id] = str_replace("|date_time|", date(date_time_format(), strtotime(db_fetch_cell("select value from settings where name='date'"))), $xport_variables[$field_name][$xport_item_id]);
				}

				/* data query variables */
				$xport_variables[$field_name][$xport_item_id] = rrdgraph_substitute_host_query_data($xport_variables[$field_name][$xport_item_id], $graph, $xport_item);

				/* Nth percentile */
				if (preg_match_all("/\|([0-9]{1,2}):(bits|bytes):(\d):(current|total|max|total_peak|all_max_current|all_max_peak|aggregate_max|aggregate_sum|aggregate_current|aggregate):(\d)?\|/", $xport_variables[$field_name][$xport_item_id], $matches, PREG_SET_ORDER)) {
					foreach ($matches as $match) {
						if ($field_name == "value") {
							$xport_meta["NthPercentile"][$nth]["format"] = $match[0];
							$xport_meta["NthPercentile"][$nth]["value"]  = str_replace($match[0], variable_nth_percentile($match, $xport_item, $xport_items, $graph_start, $graph_end), $xport_variables[$field_name][$xport_item_id]);
							$nth++;
						}
					}
				}

				/* bandwidth summation */
				if (preg_match_all("/\|sum:(\d|auto):(current|total|atomic):(\d):(\d+|auto)\|/", $xport_variables[$field_name][$xport_item_id], $matches, PREG_SET_ORDER)) {
					foreach ($matches as $match) {
						if ($field_name == "text_format") {
							$xport_meta["Summation"][$sum]["format"] = $match[0];
							$xport_meta["Summation"][$sum]["value"]  = str_replace($match[0], variable_bandwidth_summation($match, $xport_item, $xport_items, $graph_start, $graph_end, $rra["steps"], $ds_step), $xport_variables[$field_name][$xport_item_id]);
							$sum++;
						}
					}
				}
			}

			$j++;
		}
	}

	/* +++++++++++++++++++++++ CDEF's +++++++++++++++++++++++ */

	$i = 0;
	$j = 1;
	if (is_array($xport_items)) {
		reset($xport_items);
	}

	$xport_item_stack_type = "";
	$txt_xport_items       = "";
	$stacked_columns       = array();

	if (sizeof($xport_items) > 0) {
	foreach ($xport_items as $xport_item) {
		/* first we need to check if there is a DEF for the current data source/cf combination. if so,
		we will use that */
		if (isset($cf_ds_cache{$xport_item["data_template_rrd_id"]}{$xport_item["consolidation_function_id"]})) {
			$cf_id = $xport_item["consolidation_function_id"];
		}else{
		/* if there is not a DEF defined for the current data source/cf combination, then we will have to
		improvise. choose the first available cf in the following order: AVERAGE, MAX, MIN, LAST */
			if (isset($cf_ds_cache{$xport_item["data_template_rrd_id"]}[RRA_CF_TYPE_AVERAGE])) {
				$cf_id = RRA_CF_TYPE_AVERAGE; /* CF: AVERAGE */
			}elseif (isset($cf_ds_cache{$xport_item["data_template_rrd_id"]}[RRA_CF_TYPE_MAX])) {
				$cf_id = RRA_CF_TYPE_MAX; /* CF: MAX */
			}elseif (isset($cf_ds_cache{$xport_item["data_template_rrd_id"]}[RRA_CF_TYPE_MIN])) {
				$cf_id = RRA_CF_TYPE_MIN; /* CF: MIN */
			}elseif (isset($cf_ds_cache{$xport_item["data_template_rrd_id"]}[RRA_CF_TYPE_LAST])) {
				$cf_id = RRA_CF_TYPE_LAST; /* CF: LAST */
			}else{
				$cf_id = RRA_CF_TYPE_AVERAGE; /* CF: AVERAGE */
			}
		}
		/* now remember the correct CF reference */
		$cf_id = $xport_item["cf_reference"];

		/* make cdef string here; a note about CDEF's in cacti. A CDEF is neither unique to a
		data source of global cdef, but is unique when those two variables combine. */
		$cdef_xport_defs = ""; $cdef_all_ds_dups = ""; $cdef_similar_ds_dups = "";
		$cdef_similar_ds_nodups = ""; $cdef_all_ds_nodups = "";

		if ((!empty($xport_item["cdef_id"])) && (!isset($cdef_cache{$xport_item["cdef_id"]}{$xport_item["data_template_rrd_id"]}[$cf_id]))) {

			$cdef_string = $xport_variables["cdef_cache"]{$xport_item["graph_templates_item_id"]};
			$magic_item 	= array();
			$already_seen	= array();
			$sources_seen	= array();
			$count_all_ds_dups = 0;
			$count_all_ds_nodups = 0;
			$count_similar_ds_dups = 0;
			$count_similar_ds_nodups = 0;

			/* if any of those magic variables are requested ... */
			if (preg_match("/(ALL_DATA_SOURCES_(NO)?DUPS|SIMILAR_DATA_SOURCES_(NO)?DUPS)/", $cdef_string) ||
				preg_match("/(COUNT_ALL_DS_(NO)?DUPS|COUNT_SIMILAR_DS_(NO)?DUPS)/", $cdef_string)) {

				/* now walk through each case to initialize array*/
				if (preg_match("/ALL_DATA_SOURCES_DUPS/", $cdef_string)) {
					$magic_item["ALL_DATA_SOURCES_DUPS"] = "";
				}
				if (preg_match("/ALL_DATA_SOURCES_NODUPS/", $cdef_string)) {
					$magic_item["ALL_DATA_SOURCES_NODUPS"] = "";
				}
				if (preg_match("/SIMILAR_DATA_SOURCES_DUPS/", $cdef_string)) {
					$magic_item["SIMILAR_DATA_SOURCES_DUPS"] = "";
				}
				if (preg_match("/SIMILAR_DATA_SOURCES_NODUPS/", $cdef_string)) {
					$magic_item["SIMILAR_DATA_SOURCES_NODUPS"] = "";
				}
				if (preg_match("/COUNT_ALL_DS_DUPS/", $cdef_string)) {
					$magic_item["COUNT_ALL_DS_DUPS"] = "";
				}
				if (preg_match("/COUNT_ALL_DS_NODUPS/", $cdef_string)) {
					$magic_item["COUNT_ALL_DS_NODUPS"] = "";
				}
				if (preg_match("/COUNT_SIMILAR_DS_DUPS/", $cdef_string)) {
					$magic_item["COUNT_SIMILAR_DS_DUPS"] = "";
				}
				if (preg_match("/COUNT_SIMILAR_DS_NODUPS/", $cdef_string)) {
					$magic_item["COUNT_SIMILAR_DS_NODUPS"] = "";
				}

				/* loop over all graph items */
				for ($t=0;($t<count($xport_items));$t++) {

					/* only work on graph items, omit GRPINTs, COMMENTs and stuff */
					if (($xport_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_AREA ||
						$xport_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_STACK ||
						$xport_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_LINE1 ||
						$xport_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_LINE2 ||
						$xport_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_LINE3 ||
						$xport_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_LINESTACK) &&
						(!empty($xport_items[$t]["data_template_rrd_id"]))) {
						/* if the user screws up CF settings, PHP will generate warnings if left unchecked */

						/* matching consolidation function? */
						if (isset($cf_ds_cache{$xport_items[$t]["data_template_rrd_id"]}[$cf_id])) {
							$def_name = generate_graph_def_name(strval($cf_ds_cache{$xport_items[$t]["data_template_rrd_id"]}[$cf_id]));

							/* do we need ALL_DATA_SOURCES_DUPS? */
							if (isset($magic_item["ALL_DATA_SOURCES_DUPS"])) {
								$magic_item["ALL_DATA_SOURCES_DUPS"] .= ($count_all_ds_dups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,$def_name,$def_name,UN,0,$def_name,IF,IF"; /* convert unknowns to '0' first */
							}

							/* do we need COUNT_ALL_DS_DUPS? */
							if (isset($magic_item["COUNT_ALL_DS_DUPS"])) {
								$magic_item["COUNT_ALL_DS_DUPS"] .= ($count_all_ds_dups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,1,$def_name,UN,0,1,IF,IF"; /* convert unknowns to '0' first */
							}

							$count_all_ds_dups++;

							/* check if this item also qualifies for NODUPS  */
							if(!isset($already_seen[$def_name])) {
								if (isset($magic_item["ALL_DATA_SOURCES_NODUPS"])) {
									$magic_item["ALL_DATA_SOURCES_NODUPS"] .= ($count_all_ds_nodups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,$def_name,$def_name,UN,0,$def_name,IF,IF"; /* convert unknowns to '0' first */
								}
								if (isset($magic_item["COUNT_ALL_DS_NODUPS"])) {
									$magic_item["COUNT_ALL_DS_NODUPS"] .= ($count_all_ds_nodups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,1,$def_name,UN,0,1,IF,IF"; /* convert unknowns to '0' first */
								}
								$count_all_ds_nodups++;
								$already_seen[$def_name]=TRUE;
							}

							/* check for SIMILAR data sources */
							if ($xport_item["data_source_name"] == $xport_items[$t]["data_source_name"]) {

								/* do we need SIMILAR_DATA_SOURCES_DUPS? */
								if (isset($magic_item["SIMILAR_DATA_SOURCES_DUPS"]) && ($xport_item["data_source_name"] == $xport_items[$t]["data_source_name"])) {
									$magic_item["SIMILAR_DATA_SOURCES_DUPS"] .= ($count_similar_ds_dups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,$def_name,$def_name,UN,0,$def_name,IF,IF"; /* convert unknowns to '0' first */
								}

								/* do we need COUNT_SIMILAR_DS_DUPS? */
								if (isset($magic_item["COUNT_SIMILAR_DS_DUPS"]) && ($xport_item["data_source_name"] == $xport_items[$t]["data_source_name"])) {
									$magic_item["COUNT_SIMILAR_DS_DUPS"] .= ($count_similar_ds_dups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,1,$def_name,UN,0,1,IF,IF"; /* convert unknowns to '0' first */
								}

								$count_similar_ds_dups++;

								/* check if this item also qualifies for NODUPS  */
								if(!isset($sources_seen{$xport_items[$t]["data_template_rrd_id"]})) {
									if (isset($magic_item["SIMILAR_DATA_SOURCES_NODUPS"])) {
										$magic_item["SIMILAR_DATA_SOURCES_NODUPS"] .= ($count_similar_ds_nodups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,$def_name,$def_name,UN,0,$def_name,IF,IF"; /* convert unknowns to '0' first */
									}
									if (isset($magic_item["COUNT_SIMILAR_DS_NODUPS"]) && ($xport_item["data_source_name"] == $xport_items[$t]["data_source_name"])) {
										$magic_item["COUNT_SIMILAR_DS_NODUPS"] .= ($count_similar_ds_nodups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,1,$def_name,UN,0,1,IF,IF"; /* convert unknowns to '0' first */
									}
									$count_similar_ds_nodups++;
									$sources_seen{$xport_items[$t]["data_template_rrd_id"]} = TRUE;
								}
							} # SIMILAR data sources
						} # matching consolidation function?
					} # only work on graph items, omit GRPINTs, COMMENTs and stuff
				} #  loop over all graph items

				/* if there is only one item to total, don't even bother with the summation.
				 * Otherwise cdef=a,b,c,+,+ is fine. */
				if ($count_all_ds_dups > 1 && isset($magic_item["ALL_DATA_SOURCES_DUPS"])) {
					$magic_item["ALL_DATA_SOURCES_DUPS"] .= str_repeat(",+", ($count_all_ds_dups - 2)) . ",+";
				}
				if ($count_all_ds_nodups > 1 && isset($magic_item["ALL_DATA_SOURCES_NODUPS"])) {
					$magic_item["ALL_DATA_SOURCES_NODUPS"] .= str_repeat(",+", ($count_all_ds_nodups - 2)) . ",+";
				}
				if ($count_similar_ds_dups > 1 && isset($magic_item["SIMILAR_DATA_SOURCES_DUPS"])) {
					$magic_item["SIMILAR_DATA_SOURCES_DUPS"] .= str_repeat(",+", ($count_similar_ds_dups - 2)) . ",+";
				}
				if ($count_similar_ds_nodups > 1 && isset($magic_item["SIMILAR_DATA_SOURCES_NODUPS"])) {
					$magic_item["SIMILAR_DATA_SOURCES_NODUPS"] .= str_repeat(",+", ($count_similar_ds_nodups - 2)) . ",+";
				}
				if ($count_all_ds_dups > 1 && isset($magic_item["COUNT_ALL_DS_DUPS"])) {
					$magic_item["COUNT_ALL_DS_DUPS"] .= str_repeat(",+", ($count_all_ds_dups - 2)) . ",+";
				}
				if ($count_all_ds_nodups > 1 && isset($magic_item["COUNT_ALL_DS_NODUPS"])) {
					$magic_item["COUNT_ALL_DS_NODUPS"] .= str_repeat(",+", ($count_all_ds_nodups - 2)) . ",+";
				}
				if ($count_similar_ds_dups > 1 && isset($magic_item["COUNT_SIMILAR_DS_DUPS"])) {
					$magic_item["COUNT_SIMILAR_DS_DUPS"] .= str_repeat(",+", ($count_similar_ds_dups - 2)) . ",+";
				}
				if ($count_similar_ds_nodups > 1 && isset($magic_item["COUNT_SIMILAR_DS_NODUPS"])) {
					$magic_item["COUNT_SIMILAR_DS_NODUPS"] .= str_repeat(",+", ($count_similar_ds_nodups - 2)) . ",+";
				}
			}

			$cdef_string = str_replace("CURRENT_DATA_SOURCE", generate_graph_def_name(strval((isset($cf_ds_cache{$xport_item["data_template_rrd_id"]}[$cf_id]) ? $cf_ds_cache{$xport_item["data_template_rrd_id"]}[$cf_id] : "0"))), $cdef_string);

			/* ALL|SIMILAR_DATA_SOURCES(NO)?DUPS are to be replaced here */
			if (isset($magic_item["ALL_DATA_SOURCES_DUPS"])) {
				$cdef_string = str_replace("ALL_DATA_SOURCES_DUPS", $magic_item["ALL_DATA_SOURCES_DUPS"], $cdef_string);
			}
			if (isset($magic_item["ALL_DATA_SOURCES_NODUPS"])) {
				$cdef_string = str_replace("ALL_DATA_SOURCES_NODUPS", $magic_item["ALL_DATA_SOURCES_NODUPS"], $cdef_string);
			}
			if (isset($magic_item["SIMILAR_DATA_SOURCES_DUPS"])) {
				$cdef_string = str_replace("SIMILAR_DATA_SOURCES_DUPS", $magic_item["SIMILAR_DATA_SOURCES_DUPS"], $cdef_string);
			}
			if (isset($magic_item["SIMILAR_DATA_SOURCES_NODUPS"])) {
				$cdef_string = str_replace("SIMILAR_DATA_SOURCES_NODUPS", $magic_item["SIMILAR_DATA_SOURCES_NODUPS"], $cdef_string);
			}

			/* COUNT_ALL|SIMILAR_DATA_SOURCES(NO)?DUPS are to be replaced here */
			if (isset($magic_item["COUNT_ALL_DS_DUPS"])) {
				$cdef_string = str_replace("COUNT_ALL_DS_DUPS", $magic_item["COUNT_ALL_DS_DUPS"], $cdef_string);
			}
			if (isset($magic_item["COUNT_ALL_DS_NODUPS"])) {
				$cdef_string = str_replace("COUNT_ALL_DS_NODUPS", $magic_item["COUNT_ALL_DS_NODUPS"], $cdef_string);
			}
			if (isset($magic_item["COUNT_SIMILAR_DS_DUPS"])) {
				$cdef_string = str_replace("COUNT_SIMILAR_DS_DUPS", $magic_item["COUNT_SIMILAR_DS_DUPS"], $cdef_string);
			}
			if (isset($magic_item["COUNT_SIMILAR_DS_NODUPS"])) {
				$cdef_string = str_replace("COUNT_SIMILAR_DS_NODUPS", $magic_item["COUNT_SIMILAR_DS_NODUPS"], $cdef_string);
			}

			/* data source item variables */
			$cdef_string = str_replace("CURRENT_DS_MINIMUM_VALUE", (empty($xport_item["rrd_minimum"]) ? "0" : $xport_item["rrd_minimum"]), $cdef_string);
			$cdef_string = str_replace("CURRENT_DS_MAXIMUM_VALUE", (empty($xport_item["rrd_maximum"]) ? "0" : $xport_item["rrd_maximum"]), $cdef_string);
			$cdef_string = str_replace("CURRENT_GRAPH_MINIMUM_VALUE", (empty($graph["lower_limit"]) ? "0" : $graph["lower_limit"]), $cdef_string);
			$cdef_string = str_replace("CURRENT_GRAPH_MAXIMUM_VALUE", (empty($graph["upper_limit"]) ? "0" : $graph["upper_limit"]), $cdef_string);

			/* replace query variables in cdefs */
			$cdef_string = rrdgraph_substitute_host_query_data($cdef_string, $graph, $xport_item);

			/* make the initial "virtual" cdef name: 'cdef' + [a,b,c,d...] */
			$cdef_xport_defs .= "CDEF:cdef" . generate_graph_def_name(strval($i)) . "=";
			$cdef_xport_defs .= cacti_escapeshellarg(sanitize_cdef($cdef_string));
			$cdef_xport_defs .= " \\\n";

			/* the CDEF cache is so we do not create duplicate CDEF's on a graph */
			$cdef_cache{$xport_item["cdef_id"]}{$xport_item["data_template_rrd_id"]}[$cf_id] = "$i";
		}

		/* add the cdef string to the end of the def string */
		$xport_defs .= $cdef_xport_defs;

		/* note the current item_id for easy access */
		$xport_item_id = $xport_item["graph_templates_item_id"];

		/* IF this graph item has a data source... get a DEF name for it, or the cdef if that applies
		to this graph item */
		if ($xport_item["cdef_id"] == "0") {
			if (isset($cf_ds_cache{$xport_item["data_template_rrd_id"]}[$cf_id])) {
				$data_source_name = generate_graph_def_name(strval($cf_ds_cache{$xport_item["data_template_rrd_id"]}[$cf_id]));
			}else{
				$data_source_name = "";
			}
		}else{
			$data_source_name = "cdef" . generate_graph_def_name(strval($cdef_cache{$xport_item["cdef_id"]}{$xport_item["data_template_rrd_id"]}[$cf_id]));
		}

		/* +++++++++++++++++++++++ XPORT ITEMS +++++++++++++++++++++++ */

		$need_rrd_nl = TRUE;
		if ($xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_AREA ||
			$xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_STACK ||
			$xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE1 ||
			$xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE2 ||
			$xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE3 ||
			$xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINESTACK) {
			/* give all export items a name */
			if (trim($xport_variables["text_format"][$xport_item_id]) == "") {
				$legend_name = "col" . $j . "-" . $data_source_name;
			}else{
				$legend_name = $xport_variables["text_format"][$xport_item_id];
			}
			$stacked_columns["col" . $j] = ($xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_STACK) ? 1 : 0;
			$stacked_columns["col" . $j] = ($xport_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINESTACK) ? 1 : 0;
			$j++;

			$txt_xport_items .= "XPORT:" . $data_source_name . ":" . "\"" . str_replace(":", "", $legend_name) . "\"";
		}else{
			$need_rrd_nl = FALSE;
		}

		$i++;

		if (($i < sizeof($xport_items)) && ($need_rrd_nl)) {
			$txt_xport_items .= RRD_NL;
		}
	}
	}

	$output_flag = RRDTOOL_OUTPUT_STDOUT;

	$xport_array = rrdxport2array(@rrdtool_execute("xport $xport_opts$xport_defs$txt_xport_items", false, $output_flag));

	/* add host and graph information */
	$xport_array["meta"]["stacked_columns"]= $stacked_columns;
	$xport_array["meta"]["title_cache"]    = $graph["title_cache"];
	$xport_array["meta"]["vertical_label"] = $graph["vertical_label"];
	$xport_array["meta"]["local_graph_id"] = $local_graph_id;
	$xport_array["meta"]["host_id"]        = $graph["host_id"];

	return $xport_array;
}


/** given a data source id, return rrdtool info array
 * @param $data_source_id - data source id
 * @return - (array) an array containing all data from rrdtool info command
 */
function rrdtool_function_info($data_source_id) {
	/* Get the path to rrdtool file */
	$data_source_path = get_data_source_path($data_source_id, true);

	/* Execute rrdtool info command */
	$cmd_line = " info " . $data_source_path;
	$output = rrdtool_execute($cmd_line, RRDTOOL_OUTPUT_NULL, RRDTOOL_OUTPUT_STDOUT);
	if (sizeof($output) == 0) {
		return false;
	}

	/* Parse the output */
	$matches = array();
	$rrd_info = array( 'rra' => array(), "ds" => array() );
	$output = explode("\n", $output);
	foreach ($output as $line) {
		$line = trim($line);
		if (preg_match("/^ds\[(\S+)\]\.(\S+) = (\S+)$/", $line, $matches)) {
			$rrd_info["ds"][$matches[1]][$matches[2]] = $matches[3];
		} elseif (preg_match("/^rra\[(\S+)\]\.(\S+)\[(\S+)\]\.(\S+) = (\S+)$/", $line, $matches)) {
			$rrd_info['rra'][$matches[1]][$matches[2]][$matches[3]][$matches[4]] = $matches[5];
		} elseif (preg_match("/^rra\[(\S+)\]\.(\S+) = (\S+)$/", $line, $matches)) {
			$rrd_info['rra'][$matches[1]][$matches[2]] = $matches[3];
		} elseif (preg_match("/^(\S+) = \"(\S+)\"$/", $line, $matches)) {
			$rrd_info[$matches[1]] = $matches[2];
		} elseif (preg_match("/^(\S+) = (\S+)$/", $line, $matches)) {
			$rrd_info[$matches[1]] = $matches[2];
		}
	}
	$output = "";
	$matches = array();

	/* Return parsed values */
	return $rrd_info;

}


/** get all rrd files related to the given data-template-id
 * @param int $data_template_id	- the id of the data template
 * @param bool $debug			- debug mode requested
 * @return array					- all rrd files
 */
function get_data_template_rrd($data_template_id) {
	$files = array ();
	/* fetch all rrd file names that are related to the given data template */
	$raw_files = db_fetch_assoc("SELECT " .
	"data_source_path " .
	"FROM data_template_data " .
	"WHERE data_template_id=" . $data_template_id . " " .
	"AND local_data_id > 0"); # do NOT fetch a template!

	if (sizeof($raw_files)) {
		foreach ($raw_files as $file) {
			/* build /full/qualified/file/names */
			$files[] = str_replace('<path_rra>', CACTI_RRA_PATH, $file['data_source_path']);
		}
	}
	return $files;
}


/** get all rrd files related to the given data-template-id
 * @param int $data_source_id	- the id of the data template
 * @param bool $debug			- debug mode requested
 * @return array					- the rrd file
 */
function get_data_source_rrd($data_source_id) {
	$files[] = str_replace('<path_rra>', CACTI_RRA_PATH, db_fetch_cell("SELECT data_source_path FROM data_template_data WHERE local_data_id=" . $data_source_id));
	return $files;
}


/** get step size (related to polling interval) for given graph
 * @param int $local_graph_id - id of current graph
 * @return	int	- step size found
 */
function get_step($local_graph_id) {
	$ds_step = db_fetch_cell("select
		data_template_data.rrd_step
		from (data_template_data,data_template_rrd,graph_templates_item)
		where graph_templates_item.task_item_id=data_template_rrd.id
		and data_template_rrd.local_data_id=data_template_data.local_data_id
		and graph_templates_item.local_graph_id=$local_graph_id
		limit 0,1");
	$ds_step = empty($ds_step) ? 300 : $ds_step;
	return $ds_step;
}


/** get required graph data for given graph
 * @param int $local_graph_id - id of current graph
 * @return array - graph data
 */
function get_graph_data($local_graph_id) {
	$graph = db_fetch_row("select
	graph_local.id AS local_graph_id,
	graph_local.host_id,
	graph_local.snmp_query_id,
	graph_local.snmp_index,
	graph_templates_graph.title_cache,
	graph_templates_graph.vertical_label,
	graph_templates_graph.slope_mode,
	graph_templates_graph.auto_scale,
	graph_templates_graph.auto_scale_opts,
	graph_templates_graph.auto_scale_log,
	graph_templates_graph.scale_log_units,
	graph_templates_graph.auto_scale_rigid,
	graph_templates_graph.alt_y_grid,
	graph_templates_graph.auto_padding,
	graph_templates_graph.base_value,
	graph_templates_graph.upper_limit,
	graph_templates_graph.lower_limit,
	graph_templates_graph.height,
	graph_templates_graph.width,
	graph_templates_graph.image_format_id,
	graph_templates_graph.unit_value,
	graph_templates_graph.unit_exponent_value,
	graph_templates_graph.export,
	graph_templates_graph.right_axis,
	graph_templates_graph.right_axis_label,
	graph_templates_graph.right_axis_format,
	graph_templates_graph.only_graph,
	graph_templates_graph.full_size_mode,
	graph_templates_graph.no_gridfit,
	graph_templates_graph.x_grid,
	graph_templates_graph.unit_length,
	graph_templates_graph.colortag_back,
	graph_templates_graph.colortag_canvas,
	graph_templates_graph.colortag_shadea,
	graph_templates_graph.colortag_shadeb,
	graph_templates_graph.colortag_grid,
	graph_templates_graph.colortag_mgrid,
	graph_templates_graph.colortag_font,
	graph_templates_graph.colortag_axis,
	graph_templates_graph.colortag_frame,
	graph_templates_graph.colortag_arrow,
	graph_templates_graph.font_render_mode,
	graph_templates_graph.font_smoothing_threshold,
	graph_templates_graph.graph_render_mode,
	graph_templates_graph.pango_markup,
	graph_templates_graph.interlaced,
	graph_templates_graph.tab_width,
	graph_templates_graph.watermark,
	graph_templates_graph.dynamic_labels,
	graph_templates_graph.force_rules_legend,
	graph_templates_graph.legend_position,
	graph_templates_graph.legend_direction,
	graph_templates_graph.grid_dash,
	graph_templates_graph.border
	from (graph_templates_graph,graph_local)
	where graph_local.id=graph_templates_graph.local_graph_id
	and graph_templates_graph.local_graph_id=$local_graph_id");
	
	return $graph;
}

/** merge graph data for given graph
 * @param array $graph - graph data
 * @param array $graph_data_array - additional graph data
 * @return array - merged graph data
 */
function merge_graph_data ($graph, $graph_data_array) {
	
	/* make sure, that $graph_data_array succeeds $graph, so don't use array_merge */
	foreach ($graph_data_array as $key => $value) {
		#cacti_log("Parameter: " . $key . " value: " . $value, true, "TEST");
		$graph{$key} = $graph_data_array{$key};		
	}
	return $graph;
}

/** get required graph item data for given graph
 * @param int $local_graph_id - id of current graph
 * @return array - graph item data
 */
function get_graph_item_data($local_graph_id) {
	$graph_items = db_fetch_assoc("select
		graph_templates_item.id as graph_templates_item_id,
		graph_templates_item.cdef_id,
		graph_templates_item.vdef_id,
		graph_templates_item.text_format,
		graph_templates_item.value,
		graph_templates_item.hard_return,
		graph_templates_item.consolidation_function_id,
		graph_templates_item.graph_type_id,
		graph_templates_item.line_width,
		graph_templates_item.dashes,
		graph_templates_item.dash_offset,
		graph_templates_item.shift,
		graph_templates_item.textalign,
		graph_templates_gprint.gprint_text,
		colors.hex,
		graph_templates_item.alpha,
		data_template_rrd.id as data_template_rrd_id,
		data_template_rrd.local_data_id,
		data_template_rrd.rrd_minimum,
		data_template_rrd.rrd_maximum,
		data_template_rrd.data_source_name,
		data_template_rrd.local_data_template_rrd_id
		from graph_templates_item
		left join data_template_rrd on (graph_templates_item.task_item_id=data_template_rrd.id)
		left join colors on (graph_templates_item.color_id=colors.id)
		left join graph_templates_gprint on (graph_templates_item.gprint_id=graph_templates_gprint.id)
		where graph_templates_item.local_graph_id=$local_graph_id
		order by graph_templates_item.sequence");
	return $graph_items;
}


/** get best fit rra
 * @param int $rra_id - id of rra
 * @param int $ds_step - step size
 * @param int $local_graph_id - id of graph
 * @param int $graph_array - array with initial graph data (graph_start, graph_end)
 * @return array - rra parameters
 */
function get_rra_data($rra_id, $ds_step, $local_graph_id, $graph_array) {
	$rra = array();
	/* if no rra was specified, we need to figure out which one RRDTool will choose using
	 * "best-fit" resolution fit algorithm */
	if (empty($rra_id)) {
		if ((!isset($graph_array["graph_start"])) || (!isset($graph_array["graph_end"]))) {
			$rra["rows"] = 600;
			$rra["steps"] = 1;
			$rra["timespan"] = 86400;
		}else{
			/* get a list of RRAs related to this graph */
			$rras = get_associated_rras($local_graph_id);

			if (sizeof($rras) > 0) {
				foreach ($rras as $unchosen_rra) {
					/* the timespan specified in the RRA "timespan" field may not be accurate */
					$real_timespan = ($ds_step * $unchosen_rra["steps"] * $unchosen_rra["rows"]);

					/* make sure the current start/end times fit within each RRA's timespan */
					if ( (($graph_array["graph_end"] - $graph_array["graph_start"]) <= $real_timespan) && ((time() - $graph_array["graph_start"]) <= $real_timespan) ) {
						/* is this RRA better than the already chosen one? */
						if ((isset($rra)) && ($unchosen_rra["steps"] < $rra["steps"])) {
							$rra = $unchosen_rra;
						}else if (!isset($rra)) {
							$rra = $unchosen_rra;
						}
					}
				}
			}

			if (!isset($rra)) {
				$rra["rows"] = 600;
				$rra["steps"] = 1;
			}
		}
	}else{
		$rra = db_fetch_row("select timespan,rows,steps from rra where id=$rra_id");
	}
	
	return $rra;
}


/** get all cdef strings for this graph
 * @param array $graph_items - all graph items
 * @return - array will be modified and have new entry for "cdef_cache"
 */
function get_all_graph_cdefs(&$graph_items) {

	reset($graph_items);
	foreach ($graph_items as $key => $graph_item) {
		/* cache cdef value here to support data query variables in the cdef string */
		if (empty($graph_item["cdef_id"])) {
			$graph_items[$key]["cdef_cache"] = "";
		}else{
			$graph_items[$key]["cdef_cache"] = get_cdef($graph_item["cdef_id"]);
		}
	}	
}


/** get all vdef strings for this graph
 * @param array $graph_items - all graph items
 * @return - array will be modified and have new entry for "vdef_cache"
 */
function get_all_graph_vdefs(&$graph_items) {

	reset($graph_items);
	foreach ($graph_items as $key => $graph_item) {
		/* cache cdef value here to support data query variables in the cdef string */
		if (empty($graph_item["vdef_id"])) {
			$graph_items[$key]["vdef_cache"] = "";
		}else{
			$graph_items[$key]["vdef_cache"] = get_vdef($graph_item["vdef_id"]);
		}
	}	
}


/** rrdgraph_option_font	- set the rrdtool font option
 * @param $type			- the type of font: DEFAULT, TITLE, AXIS, UNIT, LEGEND, WATERMARK
 * @param $no_legend	- special handling for TITLE if legend is suppressed
 * @return				- rrdtool --font option for the given font type
 */
function rrdgraph_option_font($type, $no_legend = "") {
	/* first, fetch the font from user specific settings
	 * if not available, use the global setting */
	if (strlen(read_graph_config_option($type . "_font"))) {
		$font = read_graph_config_option($type . "_font");
	}else{
		$font = read_config_option($type . "_font");
	}
	if (strlen(read_graph_config_option($type . "_size"))) {
		$size = read_graph_config_option($type . "_size");
	}else{
		$size = read_config_option($type . "_size");
	}

	/* global font may be empty */
	if(strlen($font)) {
		/* do some simple checks */
		if (read_config_option("rrdtool_version") == "rrd-1.0.x" ||
			read_config_option("rrdtool_version") == "rrd-1.2.x") { # rrdtool 1.0 and 1.2 use font files
			if (!is_file($font)) {
				$font = "";
			}
		} else {	# rrdtool 1.3+ use fontconfig
			/* verifying all possible pango font params is too complex to be tested here
			 * so we only escape the font
			 */
			$font = cacti_escapeshellarg($font);
		}
	}

	if ($type == "title") {
		if (!empty($no_legend)) {
			$size = $size * .70;
		}elseif (($size <= 4) || ($size == "")) {
			$size = 12;
		}
	}else if (($size <= 4) || ($size == "")) {
		$size = 8;
	}

	return "--font " . strtoupper($type) . ":" . $size . ":" . $font . RRD_NL;
}


/** set colortags for rrdtool graph
 * @param string $type - type of color tag
 * @param string $colortag - value to be used for this tag
 * @return string - formatted colortag rrdtool graph option
 */
function rrdgraph_option_colortag($type, $colortag) {
	$tag = "";
	$sequence = read_config_option("colortag_sequence");

	switch ($sequence) {
		case COLORTAGS_GLOBAL:
			$colortag = read_config_option("colortag_" . $type);
			if (!empty($colortag)) {$tag = $colortag;}
			break;

		case COLORTAGS_USER:
			$colortag = read_graph_config_option("colortag_" . $type);
			if (!empty($colortag)) {$tag = $colortag;}
			break;

		case COLORTAGS_TEMPLATE:
			if (!empty($colortag)) {$tag = $colortag;}
			break;

		case COLORTAGS_UTG:
			$colortag = read_graph_config_option("colortag_" . $type);			# user tag "for all graphs" comes first
			if (!empty($colortag)) {$tag = $colortag;}

			if (empty($tag) && !empty($colortag)) {								# graph specific tag comes next
				$tag = $colortag;
			}

			if (empty($tag)) {													# global tag is least priority
				$colortag = read_config_option("colortag_" . $type);
				if (!empty($colortag)) {$tag = $colortag;}
			}
			break;

		case COLORTAGS_TUG:
			if (empty($tag) && !empty($colortag)) {								# graph specific tag comes first
				$tag = $colortag;
			}

			$colortag = read_graph_config_option("colortag_" . $type);			# user tag "for all graphs" comes next
			if (!empty($colortag)) {$tag = $colortag;}

			if (empty($tag)) {													# global tag is least priority
				$colortag = read_config_option("colortag_" . $type);
				if (!empty($colortag)) {$tag = $colortag;}
			}
			break;
	}

	if (!empty($tag)) {
		return "--color " . $type . "#" . $tag . RRD_NL;
	} else {
		return "";
	}
}


/** define x-grid format, if any
 * @param int $xaxis_id - id of an xaxis definition
 * @param int $start - timestamp for start
 * @param int $end - timestamp for end
 * @return string - formatted x-axis option for rrdtool graph
 */
function rrdgraph_option_x_grid($xaxis_id, $start, $end) {

	$format = "";
	$xaxis_items = db_fetch_assoc("SELECT timespan, gtm, gst, mtm, mst, ltm, lst, lpr, lfm " .
					"FROM graph_templates_xaxis_items WHERE xaxis_id=" . $xaxis_id .
					" AND timespan > " . ($end - $start) .
					" ORDER BY timespan ASC LIMIT 1");
	# find best matching timestamp
	if (sizeof($xaxis_items)) {
		foreach ($xaxis_items as $xaxis_item) { # there's only one matching entry due to LIMIT 1
			$format .= $xaxis_item["gtm"] . ":";
			$format .= $xaxis_item["gst"] . ":";
			$format .= $xaxis_item["mtm"] . ":";
			$format .= $xaxis_item["mst"] . ":";
			$format .= $xaxis_item["ltm"] . ":";
			$format .= $xaxis_item["lst"] . ":";
			$format .= $xaxis_item["lpr"] . ":";
			$format .= $xaxis_item["lfm"];
		}
	}

	if (!empty($format)) {
		$format = "--x-grid " . cacti_escapeshellarg($format) . RRD_NL;
	}

	return $format;
}


/** rrdgraph_option_scale		compute scaling parameters for rrd graphs
 * @param $graph			graph options
 * @return				graph options prepared for use with rrdtool graph
 */
function rrdgraph_option_scale($graph) {

	$scale = "";

	/* do query_ substitions for upper and lower limit */
	if (isset($graph["lower_limit"])) {
		$graph["lower_limit"] = rrdgraph_substitute_host_query_data($graph["lower_limit"], $graph, null);
	}
	if (isset($graph["upper_limit"])) {
		$graph["upper_limit"] = rrdgraph_substitute_host_query_data($graph["upper_limit"], $graph, null);
	}

	if ($graph["auto_scale"] == CHECKED) {
		switch ($graph["auto_scale_opts"]) {
			case GRAPH_ALT_AUTOSCALE: /* autoscale ignores lower, upper limit */
				$scale = "--alt-autoscale" . RRD_NL;
				break;
			case GRAPH_ALT_AUTOSCALE_MIN: /* autoscale-max, accepts a given lower limit */
				$scale = "--alt-autoscale-max" . RRD_NL;
				if ( is_numeric($graph["lower_limit"])) {
					$scale .= "--lower-limit=" . cacti_escapeshellarg($graph["lower_limit"]) . RRD_NL;
				}
				break;
			case GRAPH_ALT_AUTOSCALE_MAX: /* autoscale-min, accepts a given upper limit */
				if (read_config_option("rrdtool_version") != RRD_VERSION_1_0) {
					$scale = "--alt-autoscale-min" . RRD_NL;
					if ( is_numeric($graph["upper_limit"])) {
						$scale .= "--upper-limit=" . cacti_escapeshellarg($graph["upper_limit"]) . RRD_NL;
					}
				}
				break;
			case GRAPH_ALT_AUTOSCALE_LIMITS: /* auto_scale with limits */
				$scale = "--alt-autoscale" . RRD_NL;
				if ( is_numeric($graph["upper_limit"])) {
					$scale .= "--upper-limit=" . cacti_escapeshellarg($graph["upper_limit"]) . RRD_NL;
				}
				if ( is_numeric($graph["lower_limit"])) {
					$scale .= "--lower-limit=" . cacti_escapeshellarg($graph["lower_limit"]) . RRD_NL;
				}
				break;
		}
	}else{
		if ( is_numeric($graph["upper_limit"])) {
			$scale .= "--upper-limit=" . cacti_escapeshellarg($graph["upper_limit"]) . RRD_NL;
		}
		if ( is_numeric($graph["lower_limit"])) {
			$scale .= "--lower-limit=" . cacti_escapeshellarg($graph["lower_limit"]) . RRD_NL;
		}
	}

	if ($graph["auto_scale_log"] == CHECKED) {
		$scale .= "--logarithmic" . RRD_NL;
	}

	/* --units=si only defined for logarithmic y-axis scaling, even if it doesn't hurt on linear graphs */
	if (($graph["scale_log_units"] == CHECKED) &&
		($graph["auto_scale_log"] == CHECKED)) {
		$scale .= "--units=si" . RRD_NL;
	}

	if ($graph["auto_scale_rigid"] == CHECKED) {
		$scale .= "--rigid" . RRD_NL;
	}

	return $scale;
}


/** rrdgraph_option_image_format		determine image format for rrdtool graph statement
 * @param $image_format_id		the id of the wanted image format
 * @param $rrdtool_version		rrdtool version used for checks
 * @return						--imgformat string
 */
function rrdgraph_option_image_format($image_format_id, $rrdtool_version) {
	require(CACTI_INCLUDE_PATH . "/graph/graph_arrays.php");

	$format = "--imgformat=";

	switch($rrdtool_version) {
		case RRD_VERSION_1_0:
			if ($image_format_id == IMAGE_TYPE_PNG || $image_format_id == IMAGE_TYPE_GIF) {
				$format .= $image_types{$image_format_id};
			} else {
				$format .= $image_types{IMAGE_TYPE_PNG};
			}
			break;

		case RRD_VERSION_1_2:
			if ($image_format_id == IMAGE_TYPE_PNG || $image_format_id == IMAGE_TYPE_SVG) {
				$format .= $image_types{$image_format_id};
			} else {
				$format .= $image_types{IMAGE_TYPE_PNG};
			}
			break;

		case RRD_VERSION_1_3:
			if ($image_format_id == IMAGE_TYPE_PNG || $image_format_id == IMAGE_TYPE_SVG) {
				$format .= $image_types{$image_format_id};
			} else {
				$format .= $image_types{IMAGE_TYPE_PNG};
			}
			break;

		case RRD_VERSION_1_4:
			if ($image_format_id == IMAGE_TYPE_PNG || $image_format_id == IMAGE_TYPE_SVG) {
				$format .= $image_types{$image_format_id};
			} else {
				$format .= $image_types{IMAGE_TYPE_PNG};
			}
			break;

		default:
			$format .= $image_types{IMAGE_TYPE_PNG};
			break;

	}

	$format .= RRD_NL;
	return $format;
}


/** determine all graph options required for rrdtool graph
 * @param array $graph 				- graph data
 * @param array $rra 				- rra data
 * @param string $version 			- rrdtool version
 * @return string 					- options formatted as required for rrdtool graph
 */
function rrdgraph_options($graph, $rra, $version) {

	$option = "";

	/* export options: either output to stream or to file */
	if (isset($graph["export"]) && isset($graph["export_filename"])) {
		$option = read_config_option("path_html_export") . "/" . $graph["export_filename"] . RRD_NL;
	}else{
		if (empty($graph["output_filename"])) {
				$option = "-" . RRD_NL;
		}else{
			$option = $graph["output_filename"] . RRD_NL;
		}
	}

	# image format
	$option .= rrdgraph_option_image_format($graph["image_format_id"], $version);


	foreach ($graph as $key => $value) {
		#cacti_log("Parameter: " . $key . " value: " . $value . " RRDTool: " . $version, true, "TEST");
		switch ($key) {
			case "graph_start":
				if (!empty($value)) {
					$option .= "--start=" . cacti_escapeshellarg($value) . RRD_NL;
				}
				break;

			case "graph_end":
				if (!empty($value)) {
					$option .= "--end=" . cacti_escapeshellarg($value) . RRD_NL;
				}
				break;

			case "height":
				/* override: graph height (in pixels), passed via graph_data_array */
				if (isset($graph["graph_height"]) && preg_match("/^[0-9]+$/", $graph["graph_height"])) {
					$option .= "--height=" . $graph["graph_height"] . RRD_NL;
				}else{
					$option .= "--height=" . $value . RRD_NL;
				}
				break;

			case "width":
				/* override: graph width (in pixels), passed via graph_data_array */
				if (isset($graph["graph_width"]) && preg_match("/^[0-9]+$/", $graph["graph_width"])) {
					$option .= "--width=" . $graph["graph_width"] . RRD_NL;
				}else{
					$option .= "--width=" . $value . RRD_NL;
				}
				break;

			case "graph_nolegend":
				/* override: skip drawing the legend? */
				if (isset($graph["graph_nolegend"])) {
					$option .= "--no-legend" . RRD_NL;
				}else{
					$option .= "";
				}
				break;

			case "title_cache":
				if (!empty($value)) {
					$option .= "--title=" . cacti_escapeshellarg($value) . RRD_NL;
				}
				break;

			case "alt_y_grid":
				if ($value == CHECKED) 	{$option .= "--alt-y-grid" . RRD_NL;}
				break;

			case "unit_value":
				if (!empty($value)) {
					$option .= "--y-grid=" . cacti_escapeshellarg($value) . RRD_NL;
				}
				break;

			case "unit_exponent_value":
				if (preg_match("/^[0-9]+$/", $value)) {
					$option .= "--units-exponent=" . $value . RRD_NL;
				}
				break;

			case "base_value":
				if ($value == 1000 || $value == 1024) {
					$option .= "--base=" . $value . RRD_NL;
				}
				break;

			case "vertical_label":
				if (!empty($value)) {
					$option .= "--vertical-label=" . cacti_escapeshellarg($value) . RRD_NL;
				}
				break;

			case "slope_mode":
				/* rrdtool 1.2.x, 1.3.x does not provide smooth lines, let's force it */
				if ($version != RRD_VERSION_1_0) {
					if ($value == CHECKED) {
						$option .= "--slope-mode" . RRD_NL;
					}
				}
				break;

			case "right_axis":
				if ($version != RRD_VERSION_1_0 && $version != RRD_VERSION_1_2) {
					if (!empty($value)) {
						$option .= "--right-axis " . cacti_escapeshellarg($value) . RRD_NL;
					}
				}
				break;

			case "right_axis_label":
				if ($version != RRD_VERSION_1_0 && $version != RRD_VERSION_1_2) {
					if (!empty($value)) {
						$option .= "--right-axis-label " . cacti_escapeshellarg($value) . RRD_NL;
					}
				}
				break;

			case "right_axis_format":
				if ($version != RRD_VERSION_1_0 && $version != RRD_VERSION_1_2) {
					if (!empty($value)) {
						$format = db_fetch_cell('SELECT gprint_text from graph_templates_gprint WHERE id=' . $value);
						$option .= "--right-axis-format " . cacti_escapeshellarg($format) . RRD_NL;
					}
				}
				break;

			case "only_graph":
				if ($value == CHECKED) {
					$option .= "--only-graph" . RRD_NL;
				}
				break;

			case "full_size_mode":
				if ($version != RRD_VERSION_1_0 && $version != RRD_VERSION_1_2) {
					if ($value == CHECKED) {
						$option .= "--full-size-mode" . RRD_NL;
					}
				}
				break;

			case "no_gridfit":
				if ($version != RRD_VERSION_1_0) {
					if ($value == CHECKED) {
						$option .= "--no-gridfit" . RRD_NL;
					}
				}
				break;

			case "x_grid":
				if (!empty($value)) {
					$option .= rrdgraph_option_x_grid($value, $graph["graph_start"], $graph["graph_end"]);
				}
				break;

			case "unit_length":
				if (!empty($value)) {
					$option .= "--units-length " . cacti_escapeshellarg($value) . RRD_NL;
				}
				break;

			case "font_render_mode":
				if ($version != RRD_VERSION_1_0) {
					if (!empty($value)) {
						$option .= "--font-render-mode " . cacti_escapeshellarg($value) . RRD_NL;
					}
				}
				break;

			case "font_smoothing_threshold":
				if ($version != RRD_VERSION_1_0) {
					if (!empty($value)) {
						$option .= "--font-smoothing-threshold " . cacti_escapeshellarg($value) . RRD_NL;
					}
				}
				break;

			case "graph_render_mode":
				if ($version != RRD_VERSION_1_0 && $version != RRD_VERSION_1_2) {
					if (!empty($value)) {
						$option .= "--graph-render-mode " . cacti_escapeshellarg($value) . RRD_NL;
					}
				}
				break;

			case "pango_markup":
				if ($version != RRD_VERSION_1_0 && $version != RRD_VERSION_1_2) {
					if (!empty($value)) {
						$option .= "--pango-markup" . RRD_NL;
					}
				}
				break;

			case "interlaced":
				if ($value == CHECKED) {
					$option .= "--interlaced" . RRD_NL;
				}
				break;

			case "tab_width":
				if ($version != RRD_VERSION_1_0) {
					if (!empty($value)) {
						$option .= "--tabwidth " . cacti_escapeshellarg($value) . RRD_NL;
					}
				}
				break;

			case "watermark":
				if ($version != RRD_VERSION_1_0) {
					if (!empty($value)) {
						$option .= "--watermark " . cacti_escapeshellarg($value) . RRD_NL;
					}
				}
				break;

			case "dynamic_labels":
				if ($value == CHECKED) {
					$option .= "--dynamic-labels" . RRD_NL;
				}
				break;

			case "force_rules_legend":
				if ($value == CHECKED) {
					$option .= "--force-rules-legend" . RRD_NL;
				}
				break;

			case "legend_position":
				if ($version != RRD_VERSION_1_0 && $version != RRD_VERSION_1_2 && $version != RRD_VERSION_1_3) {
					if (!empty($value)) {
						$option .= "--legend-position " . cacti_escapeshellarg($value) . RRD_NL;
					}
				}
				break;

			case "legend_direction":
				if ($version != RRD_VERSION_1_0 && $version != RRD_VERSION_1_2 && $version != RRD_VERSION_1_3) {
					if (!empty($value)) {
						$option .= "--legend-direction " . cacti_escapeshellarg($value) . RRD_NL;
					}
				}
				break;

			case "grid_dash":
				if ($version != RRD_VERSION_1_0 && $version != RRD_VERSION_1_2 && $version != RRD_VERSION_1_3) {
					if (!empty($value)) {
						$option .= "--grid-dash " . cacti_escapeshellarg($value) . RRD_NL;
					}
				}
				break;

			case "border":
				if ($version != RRD_VERSION_1_0 && $version != RRD_VERSION_1_2 && $version != RRD_VERSION_1_3) {
					if (preg_match("/^[0-9]+$/", $value)) { # stored as string, do not use ===; border=0 is valid but != empty border!
						$option .= "--border " . cacti_escapeshellarg($value) . RRD_NL;
					}
				}
				break;

		}
	}

	/* rrdtool 1.2.x++ font options */
	if ($version != RRD_VERSION_1_0) {
		/* title fonts */
		$option .= rrdgraph_option_font("title", ((!empty($graph_data_array["graph_nolegend"])) ? $graph_data_array["graph_nolegend"] : ""));

		/* axis fonts */
		$option .= rrdgraph_option_font("axis");

		/* legend fonts */
		$option .= rrdgraph_option_font("legend");

		/* unit fonts */
		$option .= rrdgraph_option_font("unit");
	}

	/* rrdtool 1.3.x++ colortag options */
	if ($version != RRD_VERSION_1_0 && $version != RRD_VERSION_1_2) {
		/* title fonts */
		$option .= rrdgraph_option_colortag("BACK", $graph["colortag_back"]);
		$option .= rrdgraph_option_colortag("CANVAS", $graph["colortag_canvas"]);
		$option .= rrdgraph_option_colortag("SHADEA", $graph["colortag_shadea"]);
		$option .= rrdgraph_option_colortag("SHADEB", $graph["colortag_shadeb"]);
		$option .= rrdgraph_option_colortag("GRID", $graph["colortag_grid"]);
		$option .= rrdgraph_option_colortag("MGRID", $graph["colortag_mgrid"]);
		$option .= rrdgraph_option_colortag("FONT", $graph["colortag_font"]);
		$option .= rrdgraph_option_colortag("AXIS", $graph["colortag_axis"]);
		$option .= rrdgraph_option_colortag("FRAME", $graph["colortag_frame"]);
		$option .= rrdgraph_option_colortag("ARROW", $graph["colortag_arrow"]);
	}


	$option .= rrdgraph_option_scale($graph);


	return $option;
}


/** compute rrd graph DEF statement(s)
 * @param array $graph_items - all graph items
 * @param int $start - graph start time
 * @param int $end - graph end time
 * @param array $cf_ds_cache - the cache for [consolidation function] and [data source id] to be filled and passed back
 * @return string - rrd graph DEF statement for current graph
 */
function rrdgraph_defs($graph_items, $start, $end, &$cf_ds_cache) {
	require(CACTI_INCLUDE_PATH . "/presets/preset_rra_arrays.php");
	/* create an rrdtool DEF statement for each data source we use
	 * using a data source == we have a valid local_data_id
	 * $cf_ds_cache stores all those references, that have already been build (array of DEFs+CFs)
	 * thus, in case a data source is referred to multiple times, we will have one DEF only
	 * just to meet the requirements of rrdtool
	 * $cf_ds_cache takes two dimensions into account:
	 *   data_template_rrd_id == per different data source (rrd file)
	 *   graph_cf             == per different consolidation function
	 * TODO: why data_template_rrd_id and not local_data_id
	 * TODO: why graph_cf and not cf_reference?
	 */
	$graph_defs = '';
	
	reset($graph_items);
	foreach ($graph_items as $key => $graph_item) {
		$graph_item_id = $graph_item["graph_templates_item_id"];
		if ((!empty($graph_item["local_data_id"])) && (!isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}{$graph_item["cf_reference"]}))) {
			/* use a user-specified ds path if one is entered */
			$data_source_path = get_data_source_path($graph_item["local_data_id"], true);
			
			/* FOR WIN32: Escape all colon for drive letters (ex. D\:/path/to/rra) */
			$data_source_path = str_replace(":", "\:", $data_source_path);
			
			if (!empty($data_source_path)) {
				$i = sizeof($cf_ds_cache); # instead of using a counter, same results are achieved this way
				/* NOTE: (Update) Data source DEF names are created using the graph_item_id; then passed
				 * to a function that matches the digits with letters. rrdtool likes letters instead
				 * of numbers in DEF names; especially with CDEF's. cdef's are created
				 * the same way, except a 'cdef' is put on the beginning of the hash */
				$graph_defs .= "DEF:" . generate_graph_def_name(strval($i)) . "=\"$data_source_path\":" . cacti_escapeshellarg($graph_item["data_source_name"]) . ":" . $consolidation_functions{$graph_item["cf_reference"]};
				if ($graph_item["shift"] == CHECKED && $graph_item["value"] > 0) {	# create a SHIFTed DEF
					$graph_defs .= ":start=" . $start . "-" . $graph_item["value"];
					$graph_defs .= ":end=" . $end . "-" . $graph_item["value"];
				}
				$graph_defs .= RRD_NL;
				
				#			old code
				#			$cf_ds_cache{$graph_item["data_template_rrd_id"]}{$graph_item["cf_reference"]} = "$i";
				#			$i++;	# new cache entry found, increment counter to have a new DEF identify on the next run
				
				$cf_ds_cache{$graph_item["data_template_rrd_id"]}{$graph_item["cf_reference"]} = "$i";
#cacti_log(__FUNCTION__ . " cf_ds_cache rrd id: " . $graph_item["data_template_rrd_id"] .  " cf ref: " . $graph_item["cf_reference"] . " i: ". $i, false, "TEST");
			}
		}
	}
	return $graph_defs;
}


/**
 * build a CDEF statement
 * @param array $graph		 					- current graph data
 * @param array $graph_item	 					- current graph item
 * @param array $graph_items 					- all graph items
 * @param array $graph_variables 				- all graph variables
 * @param int $cf_id 							- current id of consolidation function
 * @param int $i 								- current graph item index
 * @param int $seconds_between_graph_updates	- seconds between graph updates aka polling interval
 * @param array $cf_ds_cache 					- cache of cf-ds pairs
 * @param array $cdef_cache 					- cache of cdefs to avoid duplicate cdefs
 * @return										- CDEF statement for rrdtool
 */
function rrdgraph_cdefs($graph, $graph_item, $graph_items, $graph_variables, $cf_id, $i, $seconds_between_graph_updates, $cf_ds_cache, &$cdef_cache) {
#cacti_log(__FUNCTION__ . " started", false, "TEST");

	$cdef_graph_defs = '';
	
	if ((!empty($graph_item["cdef_id"])) && (!isset($cdef_cache{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id]))) {

		$cdef_string 	= $graph_variables["cdef_cache"]{$graph_item["graph_templates_item_id"]};
#cacti_log(__FUNCTION__ . " cdef: $cdef_string", false, "TEST");
		$magic_item 	= array();
		$already_seen	= array();
		$sources_seen	= array();
		$count_all_ds_dups = 0;
		$count_all_ds_nodups = 0;
		$count_similar_ds_dups = 0;
		$count_similar_ds_nodups = 0;

		/* if any of those magic variables are requested ... */
		if (preg_match("/(ALL_DATA_SOURCES_(NO)?DUPS|SIMILAR_DATA_SOURCES_(NO)?DUPS)/", $cdef_string) ||
			preg_match("/(COUNT_ALL_DS_(NO)?DUPS|COUNT_SIMILAR_DS_(NO)?DUPS)/", $cdef_string)) {

			/* now walk through each case to initialize array*/
			if (preg_match("/ALL_DATA_SOURCES_DUPS/", $cdef_string)) {
				$magic_item["ALL_DATA_SOURCES_DUPS"] = "";
			}
			if (preg_match("/ALL_DATA_SOURCES_NODUPS/", $cdef_string)) {
				$magic_item["ALL_DATA_SOURCES_NODUPS"] = "";
			}
			if (preg_match("/SIMILAR_DATA_SOURCES_DUPS/", $cdef_string)) {
				$magic_item["SIMILAR_DATA_SOURCES_DUPS"] = "";
			}
			if (preg_match("/SIMILAR_DATA_SOURCES_NODUPS/", $cdef_string)) {
				$magic_item["SIMILAR_DATA_SOURCES_NODUPS"] = "";
			}
			if (preg_match("/COUNT_ALL_DS_DUPS/", $cdef_string)) {
				$magic_item["COUNT_ALL_DS_DUPS"] = "";
			}
			if (preg_match("/COUNT_ALL_DS_NODUPS/", $cdef_string)) {
				$magic_item["COUNT_ALL_DS_NODUPS"] = "";
			}
			if (preg_match("/COUNT_SIMILAR_DS_DUPS/", $cdef_string)) {
				$magic_item["COUNT_SIMILAR_DS_DUPS"] = "";
			}
			if (preg_match("/COUNT_SIMILAR_DS_NODUPS/", $cdef_string)) {
				$magic_item["COUNT_SIMILAR_DS_NODUPS"] = "";
			}

			/* loop over all graph items */
			for ($t=0;($t<count($graph_items));$t++) {

				/* only work on graph items, omit GRPINTs, COMMENTs and stuff */
				if (($graph_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_AREA ||
					$graph_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_STACK ||
					$graph_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_LINE1 ||
					$graph_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_LINE2 ||
					$graph_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_LINE3 ||
					$graph_items[$t]["graph_type_id"] == GRAPH_ITEM_TYPE_LINESTACK) &&
					(!empty($graph_items[$t]["data_template_rrd_id"]))) {
					/* if the user screws up CF settings, PHP will generate warnings if left unchecked */
#cacti_log(__FUNCTION__ . " item type id=" . $graph_items[$t]["graph_type_id"], false, "TEST");

					/* matching consolidation function? */
#cacti_log(__FUNCTION__ . " dt rrd id " . $graph_items[$t]["data_template_rrd_id"], false, "TEST");
#cacti_log(__FUNCTION__ . " cf id " . $cf_id, false, "TEST");
					if (isset($cf_ds_cache{$graph_items[$t]["data_template_rrd_id"]}[$cf_id])) {
						$def_name = generate_graph_def_name(strval($cf_ds_cache{$graph_items[$t]["data_template_rrd_id"]}[$cf_id]));
#cacti_log(__FUNCTION__ . " working on " . $def_name, false, "TEST");

						/* do we need ALL_DATA_SOURCES_DUPS? */
						if (isset($magic_item["ALL_DATA_SOURCES_DUPS"])) {
							$magic_item["ALL_DATA_SOURCES_DUPS"] .= ($count_all_ds_dups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,$def_name,$def_name,UN,0,$def_name,IF,IF"; /* convert unknowns to '0' first */
						}

						/* do we need COUNT_ALL_DS_DUPS? */
						if (isset($magic_item["COUNT_ALL_DS_DUPS"])) {
							$magic_item["COUNT_ALL_DS_DUPS"] .= ($count_all_ds_dups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,1,$def_name,UN,0,1,IF,IF"; /* convert unknowns to '0' first */
						}

						$count_all_ds_dups++;

						/* check if this item also qualifies for NODUPS  */
						if(!isset($already_seen[$def_name])) {
#cacti_log(__FUNCTION__ . " new " . $def_name, false, "TEST");
							if (isset($magic_item["ALL_DATA_SOURCES_NODUPS"])) {
								$magic_item["ALL_DATA_SOURCES_NODUPS"] .= ($count_all_ds_nodups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,$def_name,$def_name,UN,0,$def_name,IF,IF"; /* convert unknowns to '0' first */
							}
							if (isset($magic_item["COUNT_ALL_DS_NODUPS"])) {
								$magic_item["COUNT_ALL_DS_NODUPS"] .= ($count_all_ds_nodups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,1,$def_name,UN,0,1,IF,IF"; /* convert unknowns to '0' first */
							}
							$count_all_ds_nodups++;
#cacti_log(__FUNCTION__ . " nodups count " . $count_all_ds_nodups, false, "TEST");
							$already_seen[$def_name]=TRUE;
						}

						/* check for SIMILAR data sources */
						if ($graph_item["data_source_name"] == $graph_items[$t]["data_source_name"]) {

							/* do we need SIMILAR_DATA_SOURCES_DUPS? */
							if (isset($magic_item["SIMILAR_DATA_SOURCES_DUPS"]) && ($graph_item["data_source_name"] == $graph_items[$t]["data_source_name"])) {
								$magic_item["SIMILAR_DATA_SOURCES_DUPS"] .= ($count_similar_ds_dups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,$def_name,$def_name,UN,0,$def_name,IF,IF"; /* convert unknowns to '0' first */
							}

							/* do we need COUNT_SIMILAR_DS_DUPS? */
							if (isset($magic_item["COUNT_SIMILAR_DS_DUPS"]) && ($graph_item["data_source_name"] == $graph_items[$t]["data_source_name"])) {
								$magic_item["COUNT_SIMILAR_DS_DUPS"] .= ($count_similar_ds_dups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,1,$def_name,UN,0,1,IF,IF"; /* convert unknowns to '0' first */
							}

							$count_similar_ds_dups++;

							/* check if this item also qualifies for NODUPS  */
							if(!isset($sources_seen{$graph_items[$t]["data_template_rrd_id"]})) {
								if (isset($magic_item["SIMILAR_DATA_SOURCES_NODUPS"])) {
									$magic_item["SIMILAR_DATA_SOURCES_NODUPS"] .= ($count_similar_ds_nodups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,$def_name,$def_name,UN,0,$def_name,IF,IF"; /* convert unknowns to '0' first */
								}
								if (isset($magic_item["COUNT_SIMILAR_DS_NODUPS"]) && ($graph_item["data_source_name"] == $graph_items[$t]["data_source_name"])) {
									$magic_item["COUNT_SIMILAR_DS_NODUPS"] .= ($count_similar_ds_nodups == 0 ? "" : ",") . "TIME," . (time() - $seconds_between_graph_updates) . ",GT,1,$def_name,UN,0,1,IF,IF"; /* convert unknowns to '0' first */
								}
								$count_similar_ds_nodups++;
								$sources_seen{$graph_items[$t]["data_template_rrd_id"]} = TRUE;
							}
						} # SIMILAR data sources
					} # matching consolidation function?
				} # only work on graph items, omit GRPINTs, COMMENTs and stuff
			} #  loop over all graph items

			/* if there is only one item to total, don't even bother with the summation.
			 * Otherwise cdef=a,b,c,+,+ is fine. */
			if ($count_all_ds_dups > 1 && isset($magic_item["ALL_DATA_SOURCES_DUPS"])) {
				$magic_item["ALL_DATA_SOURCES_DUPS"] .= str_repeat(",+", ($count_all_ds_dups - 2)) . ",+";
			}
			if ($count_all_ds_nodups > 1 && isset($magic_item["ALL_DATA_SOURCES_NODUPS"])) {
				$magic_item["ALL_DATA_SOURCES_NODUPS"] .= str_repeat(",+", ($count_all_ds_nodups - 2)) . ",+";
			}
			if ($count_similar_ds_dups > 1 && isset($magic_item["SIMILAR_DATA_SOURCES_DUPS"])) {
				$magic_item["SIMILAR_DATA_SOURCES_DUPS"] .= str_repeat(",+", ($count_similar_ds_dups - 2)) . ",+";
			}
			if ($count_similar_ds_nodups > 1 && isset($magic_item["SIMILAR_DATA_SOURCES_NODUPS"])) {
				$magic_item["SIMILAR_DATA_SOURCES_NODUPS"] .= str_repeat(",+", ($count_similar_ds_nodups - 2)) . ",+";
			}
			if ($count_all_ds_dups > 1 && isset($magic_item["COUNT_ALL_DS_DUPS"])) {
				$magic_item["COUNT_ALL_DS_DUPS"] .= str_repeat(",+", ($count_all_ds_dups - 2)) . ",+";
			}
			if ($count_all_ds_nodups > 1 && isset($magic_item["COUNT_ALL_DS_NODUPS"])) {
				$magic_item["COUNT_ALL_DS_NODUPS"] .= str_repeat(",+", ($count_all_ds_nodups - 2)) . ",+";
			}
			if ($count_similar_ds_dups > 1 && isset($magic_item["COUNT_SIMILAR_DS_DUPS"])) {
				$magic_item["COUNT_SIMILAR_DS_DUPS"] .= str_repeat(",+", ($count_similar_ds_dups - 2)) . ",+";
			}
			if ($count_similar_ds_nodups > 1 && isset($magic_item["COUNT_SIMILAR_DS_NODUPS"])) {
				$magic_item["COUNT_SIMILAR_DS_NODUPS"] .= str_repeat(",+", ($count_similar_ds_nodups - 2)) . ",+";
			}
		}

		$cdef_string = str_replace("CURRENT_DATA_SOURCE", generate_graph_def_name(strval((isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}[$cf_id]) ? $cf_ds_cache{$graph_item["data_template_rrd_id"]}[$cf_id] : "0"))), $cdef_string);

		/* ALL|SIMILAR_DATA_SOURCES(NO)?DUPS are to be replaced here */
		if (isset($magic_item["ALL_DATA_SOURCES_DUPS"])) {
			$cdef_string = str_replace("ALL_DATA_SOURCES_DUPS", $magic_item["ALL_DATA_SOURCES_DUPS"], $cdef_string);
		}
		if (isset($magic_item["ALL_DATA_SOURCES_NODUPS"])) {
			$cdef_string = str_replace("ALL_DATA_SOURCES_NODUPS", $magic_item["ALL_DATA_SOURCES_NODUPS"], $cdef_string);
		}
		if (isset($magic_item["SIMILAR_DATA_SOURCES_DUPS"])) {
			$cdef_string = str_replace("SIMILAR_DATA_SOURCES_DUPS", $magic_item["SIMILAR_DATA_SOURCES_DUPS"], $cdef_string);
		}
		if (isset($magic_item["SIMILAR_DATA_SOURCES_NODUPS"])) {
			$cdef_string = str_replace("SIMILAR_DATA_SOURCES_NODUPS", $magic_item["SIMILAR_DATA_SOURCES_NODUPS"], $cdef_string);
		}

		/* COUNT_ALL|SIMILAR_DATA_SOURCES(NO)?DUPS are to be replaced here */
		if (isset($magic_item["COUNT_ALL_DS_DUPS"])) {
			$cdef_string = str_replace("COUNT_ALL_DS_DUPS", $magic_item["COUNT_ALL_DS_DUPS"], $cdef_string);
		}
		if (isset($magic_item["COUNT_ALL_DS_NODUPS"])) {
			$cdef_string = str_replace("COUNT_ALL_DS_NODUPS", $magic_item["COUNT_ALL_DS_NODUPS"], $cdef_string);
		}
		if (isset($magic_item["COUNT_SIMILAR_DS_DUPS"])) {
			$cdef_string = str_replace("COUNT_SIMILAR_DS_DUPS", $magic_item["COUNT_SIMILAR_DS_DUPS"], $cdef_string);
		}
		if (isset($magic_item["COUNT_SIMILAR_DS_NODUPS"])) {
			$cdef_string = str_replace("COUNT_SIMILAR_DS_NODUPS", $magic_item["COUNT_SIMILAR_DS_NODUPS"], $cdef_string);
		}

		/* data source item variables */
		$cdef_string = str_replace("CURRENT_DS_MINIMUM_VALUE", (empty($graph_item["rrd_minimum"]) ? "0" : $graph_item["rrd_minimum"]), $cdef_string);
		$cdef_string = str_replace("CURRENT_DS_MAXIMUM_VALUE", (empty($graph_item["rrd_maximum"]) ? "0" : $graph_item["rrd_maximum"]), $cdef_string);
		$cdef_string = str_replace("CURRENT_GRAPH_MINIMUM_VALUE", (empty($graph["lower_limit"]) ? "0" : $graph["lower_limit"]), $cdef_string);
		$cdef_string = str_replace("CURRENT_GRAPH_MAXIMUM_VALUE", (empty($graph["upper_limit"]) ? "0" : $graph["upper_limit"]), $cdef_string);
		$_time_shift_start = strtotime(read_graph_config_option("day_shift_start")) - strtotime("00:00");
		$_time_shift_end = strtotime(read_graph_config_option("day_shift_end")) - strtotime("00:00");
		$cdef_string = str_replace("TIME_SHIFT_START", (empty($_time_shift_start) ? "64800" : $_time_shift_start), $cdef_string);
		$cdef_string = str_replace("TIME_SHIFT_END", (empty($_time_shift_end) ? "28800" : $_time_shift_end), $cdef_string);
		$cdef_string = str_replace("GRAPH_START", (empty($graph["graph_start"]) ? "0" : $graph["graph_start"]), $cdef_string);
		$cdef_string = str_replace("GRAPH_END", (empty($graph["graph_end"]) ? "0" : $graph["graph_end"]), $cdef_string);

		/* replace query variables in cdefs */
		$cdef_string = rrdgraph_substitute_host_query_data($cdef_string, $graph, $graph_item);

		/* make the initial "virtual" cdef name: 'cdef' + [a,b,c,d...] */
		$cdef_graph_defs = "CDEF:cdef" . generate_graph_def_name(strval($i)) . "=";
		$cdef_graph_defs .= cacti_escapeshellarg(sanitize_cdef($cdef_string), true);
		$cdef_graph_defs .= " \\\n";

		/* the CDEF cache is so we do not create duplicate CDEF's on a graph */
		$cdef_cache{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id] = "$i";
	}

	return $cdef_graph_defs;
	
}


/**
 * build a VDEF statement
 * @param array $graph_item	 					- current graph item
 * @param array $graph_variables 				- all graph variables
 * @param int $cf_id 							- current id of consolidation function
 * @param int $i 								- current graph item index
 * @param array $cf_ds_cache 					- cache of cf-ds pairs
 * @param array $vdef_cache 					- cache of vdefs to avoid duplicate vdefs
 * @return										- VDEF statement for rrdtool
 */
function rrdgraph_vdefs($graph_item, $graph_variables, $cf_id, $i, $cf_ds_cache, &$vdef_cache) {
	$vdef_graph_defs = "";

	if ((!empty($graph_item["vdef_id"])) && (!isset($vdef_cache{$graph_item["vdef_id"]}{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id]))) {
		$vdef_string = $graph_variables["vdef_cache"]{$graph_item["graph_templates_item_id"]};
		/* do we refer to a CDEF within this VDEF? */
		if ($graph_item["cdef_id"] != "0") {
			/* "calculated" VDEF: use (cached) CDEF as base, only way to get calculations into VDEFs */
			$vdef_string = "cdef" . str_replace("CURRENT_DATA_SOURCE", generate_graph_def_name(strval(isset($cdef_cache{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id]) ? $cdef_cache{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id] : "0")), $vdef_string);
	 	} else {
			/* "pure" VDEF: use DEF as base */
			$vdef_string = str_replace("CURRENT_DATA_SOURCE", generate_graph_def_name(strval(isset($cf_ds_cache{$graph_item["data_template_rrd_id"]}[$cf_id]) ? $cf_ds_cache{$graph_item["data_template_rrd_id"]}[$cf_id] : "0")), $vdef_string);
		}
		# It would be possible to refer to a CDEF, but that's all. So ALL_DATA_SOURCES_NODUPS and stuff can't be used directly!
		#$vdef_string = str_replace("ALL_DATA_SOURCES_NODUPS", $magic_item["ALL_DATA_SOURCES_NODUPS"], $vdef_string);
		#$vdef_string = str_replace("ALL_DATA_SOURCES_DUPS", $magic_item["ALL_DATA_SOURCES_DUPS"], $vdef_string);
		#$vdef_string = str_replace("SIMILAR_DATA_SOURCES_NODUPS", $magic_item["SIMILAR_DATA_SOURCES_NODUPS"], $vdef_string);
		#$vdef_string = str_replace("SIMILAR_DATA_SOURCES_DUPS", $magic_item["SIMILAR_DATA_SOURCES_DUPS"], $vdef_string);

		/* make the initial "virtual" vdef name */
		$vdef_graph_defs .= "VDEF:vdef" . generate_graph_def_name(strval($i)) . "=";
		$vdef_graph_defs .= cacti_escapeshellarg(sanitize_cdef($vdef_string));
		$vdef_graph_defs .= " \\\n";

		/* the VDEF cache is so we do not create duplicate VDEF's on a graph,
		 * but take info account, that same VDEF may use different CDEFs
		 * so index over VDEF_ID, CDEF_ID per DATA_TEMPLATE_RRD_ID */
		$vdef_cache{$graph_item["vdef_id"]}{$graph_item["cdef_id"]}{$graph_item["data_template_rrd_id"]}[$cf_id] = "$i";
	}
	return $vdef_graph_defs;
}

/**
 * build the text padding for tabular display of legend entries
 * remember: works with mono-spaced fonts only, as it depends on counting chars
 * @param array $graph_items		- all graph items
 * @param array $auto_padding		- autopadding attribute of this graph
 * @param array $graph_variables	- all graph variables
 */
function rrdgraph_text_padding(&$graph_items, $auto_padding, $graph_variables) {
	/* the concept of padding is based on
	 * - using monospaced fonts
	 * - assuming, that each AREA, STACK, LINE starts on a new line
	 * - only the first GPRINT entry following the AREA, STACK, LINE will have to be adjusted
	 * if we are not displaying a legend there is no point in us even processing the auto padding stuff. */
	if ($auto_padding == CHECKED) {
		/* get the greatest text length of all entries to compute
		 * the amount of spaces to be filled in */
		$greatest_text_format = rrdgraph_compute_text_length($graph_items, $graph_variables);

		reset($graph_items);
		$next_padding = "";	# start with an empty padding
		foreach ($graph_items as $key => $graph_item) {
			/* initialize new graph_item attribute "text_padding" */
			$graph_items[$key]["text_padding"] = "";
			$graph_item_type_id = $graph_item["graph_type_id"];
			/* only applies to AREA, STACK and LINEs */
			if ($graph_item_type_id == GRAPH_ITEM_TYPE_AREA ||
				$graph_item_type_id == GRAPH_ITEM_TYPE_STACK ||
				$graph_item_type_id == GRAPH_ITEM_TYPE_LINE1 ||
				$graph_item_type_id == GRAPH_ITEM_TYPE_LINE2 ||
				$graph_item_type_id == GRAPH_ITEM_TYPE_LINE3 ||
				$graph_item_type_id == GRAPH_ITEM_TYPE_LINESTACK ||
				$graph_item_type_id == GRAPH_ITEM_TYPE_TICK) {
	
				/* we are basing how much to pad on area and stack text format,
				 * not gprint. but of course the padding has to be displayed in gprint,
				 * how fun! */
				$graph_item_id = $graph_item["graph_templates_item_id"];
				$text_format = $graph_variables["text_format"][$graph_item_id];
	
				/* apply the computed padding to the next column in sequence, so remember the value */
				$next_padding = str_pad("", ($greatest_text_format - strlen($text_format)));
			} else if ($graph_item_type_id == GRAPH_ITEM_TYPE_GPRINT_AVERAGE ||
						$graph_item_type_id == GRAPH_ITEM_TYPE_GPRINT_LAST ||
						$graph_item_type_id == GRAPH_ITEM_TYPE_GPRINT_MAX ||
						$graph_item_type_id == GRAPH_ITEM_TYPE_GPRINT_MIN) {
				$graph_items[$key]["text_padding"] = $next_padding;
				$next_padding = "";	# apply padding only once a line and for GPRINTs only
			}
		}
	}
}


/**
 * required to cover "old" CF behaviour
 * GPRINTs will be treated relative to the referencing graph item (AREA, LINE, ...)
 * @param array $graph_items - all graph items, array will be updated with a new element "cf_reference"
 */
function rrdgraph_compute_cfs(&$graph_items) {
	require_once(CACTI_INCLUDE_PATH . "/graph/graph_constants.php");
	require(CACTI_INCLUDE_PATH . "/graph/graph_arrays.php");
	require(CACTI_INCLUDE_PATH . "/presets/preset_rra_arrays.php");
		
	reset($graph_items);
	/* we need to add a new column "cf_reference", so unless PHP 5 is used, this foreach syntax is required */
	foreach ($graph_items as $key => $graph_item) {
		/* mimic the old behavior: LINE[123], AREA and STACK items use the CF specified in the graph item */
		if ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_AREA  ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_STACK ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE1 ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE2 ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE3 ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINESTACK ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_TICK) {
			$graph_cf = $graph_item["consolidation_function_id"];
			/* remember the last CF for this data source for use with GPRINT
			 * if e.g. an AREA/AVERAGE and a LINE/MAX is used
			 * we will have AVERAGE first and then MAX, depending on GPRINT sequence */
			#$last_graph_cf["data_source_name"]["local_data_template_rrd_id"] = $graph_cf;
			/* remember this for second foreach loop */
			$graph_items[$key]["cf_reference"] = $graph_cf;
		}elseif ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_AVERAGE) {
			$graph_cf = $graph_item["consolidation_function_id"];
			$graph_items[$key]["cf_reference"] = $graph_cf;
		}elseif ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_LAST) {
			$graph_cf = $graph_item["consolidation_function_id"];
			$graph_items[$key]["cf_reference"] = $graph_cf;
		}elseif ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_MAX) {
			$graph_cf = $graph_item["consolidation_function_id"];
			$graph_items[$key]["cf_reference"] = $graph_cf;
		}elseif ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT_MIN) {
			$graph_cf = $graph_item["consolidation_function_id"];
			$graph_items[$key]["cf_reference"] = $graph_cf;
			#}elseif ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_GPRINT) {
			#/* ATTENTION!
			# * the "CF" given on graph_item edit screen for GPRINT is indeed NOT a real "CF",
			# * but an aggregation function
			# * see "man rrdgraph_data" for the correct VDEF based notation
			# * so our task now is to "guess" the very graph_item, this GPRINT is related to
			# * and to use that graph_item's CF */
			#if (isset($last_graph_cf["data_source_name"]["local_data_template_rrd_id"])) {
			#	$graph_cf = $last_graph_cf["data_source_name"]["local_data_template_rrd_id"];
			#	/* remember this for second foreach loop */
			#	$graph_items[$key]["cf_reference"] = $graph_cf;
			#} else {
			#	$graph_cf = generate_graph_best_cf($graph_item["local_data_id"], $graph_item["consolidation_function_id"]);
			#	/* remember this for second foreach loop */
			#	$graph_items[$key]["cf_reference"] = $graph_cf;
			#}
		}else{
			/* all other types are based on the best matching CF */
			#GRAPH_ITEM_TYPE_COMMENT
			#GRAPH_ITEM_TYPE_HRULE
			#GRAPH_ITEM_TYPE_VRULE
			#GRAPH_ITEM_TYPE_TEXTALIGN
			$graph_cf = generate_graph_best_cf($graph_item["local_data_id"], $graph_item["consolidation_function_id"]);
			/* remember this for second foreach loop */
			$graph_items[$key]["cf_reference"] = $graph_cf;
		}
#cacti_log(__FUNCTION__ . " key: $key graph_type: " . $graph_item["graph_type_id"] . " graph item type: " . $graph_item_types[$graph_item["graph_type_id"]] . " reference: $graph_cf", false, "TEST");
#cacti_log(__FUNCTION__ . " key: $key cf: " . $graph_item["consolidation_function_id"] . " cf: " . $consolidation_functions[$graph_item["consolidation_function_id"]], false, "TEST");
	}
}


/** get all hard returns for this graph
 * @param array $graph_items - all graph items
 * @return - array will be modified and have new entry for "hardreturn"
 */
function rrdgraph_compute_hardreturns(&$graph_items) {

	reset($graph_items);
	foreach ($graph_items as $key => $graph_item) {
		/* define a CRLF in case we need a hard return */
		$graph_items[$key]["hardreturn"] = "";
		if ($graph_item["hard_return"] == CHECKED) {
			$graph_items[$key]["hardreturn"] = "\\n";
		}
	}	
}


/**
 * get the greatest length of a legend line
 * for use with autopadding only
 * @param array $graph_items	- array of graph items
 * @param array $graph_variables - all variables used for this graph
 * @return int					- greatest line length
 */
function rrdgraph_compute_text_length($graph_items, $graph_variables) {

	$greatest_text_format = 0;
	reset($graph_items);
	foreach ($graph_items as $key => $graph_item) {
		$graph_item_type_id = $graph_item["graph_type_id"];
		if ($graph_item_type_id == GRAPH_ITEM_TYPE_AREA ||
			$graph_item_type_id == GRAPH_ITEM_TYPE_STACK ||
			$graph_item_type_id == GRAPH_ITEM_TYPE_LINE1 ||
			$graph_item_type_id == GRAPH_ITEM_TYPE_LINE2 ||
			$graph_item_type_id == GRAPH_ITEM_TYPE_LINE3 ||
			$graph_item_type_id == GRAPH_ITEM_TYPE_LINESTACK ||
			$graph_item_type_id == GRAPH_ITEM_TYPE_TICK) {
			$text_format_length = strlen($graph_variables["text_format"]{$graph_item["graph_templates_item_id"]});
	
			if ($text_format_length > $greatest_text_format) {
				$greatest_text_format = $text_format_length;
			}
		}
	}	
	return $greatest_text_format;
}


/**
 * build legend text using all graph (item) variables
 * this varies with the graph type used
 * @param array $graph				- all graph data
 * @param array $graph_item			- graph item data of current item
 * @param array $graph_variables	- graph variables
 * @param string $data_source_name	- current data source name
 * @param string $rrdtool_version	- rrdtool version used
 * @param bool $need_rrd_nl			- will be changed!
 * @return string					- legend text
 */
function rrdgraph_compute_item_text($graph, $graph_item, $graph_variables, $data_source_name, $rrdtool_version, &$need_rrd_nl) {
	require(CACTI_INCLUDE_PATH . "/graph/graph_arrays.php");
	
	$graph_item_id = $graph_item["graph_templates_item_id"];
	
	/* initialize line width support */
	if ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE1 ||
		$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE2 ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE3) {
		if ($rrdtool_version == RRD_VERSION_1_0) {
			# round line_width to 1 <= line_width <= 3
			if ($graph_item["line_width"] < 1) {$graph_item["line_width"] = 1;}
			if ($graph_item["line_width"] > 3) {$graph_item["line_width"] = 3;}
			
			$graph_item["line_width"] = intval($graph_item["line_width"]);
		}
	}
	
	/* initialize color support */
	$graph_item_color_code = "";
	if (!empty($graph_item["hex"])) {
		$graph_item_color_code = "#" . $graph_item["hex"];
		if ($rrdtool_version != RRD_VERSION_1_0) {
			$graph_item_color_code .= $graph_item["alpha"];
		}
	}
	
	
	/* initialize dash support */
	$dash = "";
	if ($graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE1 ||
		$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE2 ||
			$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINE3 ||
				$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_LINESTACK ||
					$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_HRULE ||
						$graph_item["graph_type_id"] == GRAPH_ITEM_TYPE_VRULE) {
		if ($rrdtool_version != RRD_VERSION_1_0 &&
			$rrdtool_version != RRD_VERSION_1_2) {
			if (!empty($graph_item["dashes"])) {
				$dash .= ":dashes=" . $graph_item["dashes"];
			}
			if (!empty($graph_item["dash_offset"])) {
				$dash .= ":dash-offset=" . $graph_item["dash_offset"];
			}
		}
	}
	
	
#cacti_log(__FUNCTION__ . " legend: " . $graph_variables["text_format"][$graph_item_id], false, "TEST");	
#cacti_log(__FUNCTION__ . " type: " . $graph_item["graph_type_id"] . " padding: >" . $graph_item["text_padding"] . "<", false, "TEST");	
#cacti_log(__FUNCTION__ . " cf: " . $graph_item["consolidation_function_id"] . " type: >" . $graph_item["graph_type_id"] . "<", false, "TEST");	
	
	
	$txt_graph_items = '';
	switch($graph_item["graph_type_id"]) {
		case GRAPH_ITEM_TYPE_COMMENT:
			if (!isset($graph_data_array["graph_nolegend"])) {
				# perform variable substitution first (in case this will yield an empty results or brings command injection problems)
				$comment_arg = rrdgraph_substitute_host_query_data($graph_variables["text_format"][$graph_item_id], $graph, $graph_item);
				# next, compute the argument of the COMMENT statement and perform injection counter measures
				if (trim($comment_arg) == '') { # an empty COMMENT must be treated with care
					$comment_arg = cacti_escapeshellarg(' ' . $graph_item["hardreturn"]);
				} else {
					$comment_arg = cacti_escapeshellarg($comment_arg . $graph_item["hardreturn"]);
				}
				
				# create rrdtool specific command line
				if (read_config_option("rrdtool_version") != "rrd-1.0.x") {
					$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . str_replace(":", "\:", $comment_arg) . " ";
				}else {
					$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . $comment_arg . " ";
				}
			}

			break;
			
			
		case GRAPH_ITEM_TYPE_TEXTALIGN:
			if (!empty($graph_item["textalign"]) &&
					$rrdtool_version != RRD_VERSION_1_0 &&
						$rrdtool_version != RRD_VERSION_1_2) {
				$txt_graph_items .= $graph_item["graph_type_id"] . ":" . cacti_escapeshellarg($graph_item_types{$graph_item["textalign"]});
			}
			break;
			
			
		case GRAPH_ITEM_TYPE_GPRINT:
			if (!isset($graph_data_array["graph_nolegend"])) {
				/* rrdtool 1.2.x VDEFs must suppress the consolidation function on GPRINTs */
				if ($rrdtool_version != RRD_VERSION_1_0) {
					if ($graph_item["vdef_id"] == "0") {
						$txt_graph_items .= "GPRINT:" . $data_source_name . cacti_escapeshellarg(":AVERAGE:" . "GPRINT DEPRECATED" . $graph_item["gprint_text"] . $graph_item["hardreturn"]) . " ";
					}else{
						$txt_graph_items .= "GPRINT:" . $data_source_name . ":" . cacti_escapeshellarg("GPRINT DEPRECATED" . $graph_item["gprint_text"] . $graph_item["hardreturn"]) . " ";
					}
				}else {
					$txt_graph_items .= "GPRINT:" . $data_source_name . cacti_escapeshellarg(":AVERAGE:" . "GPRINT DEPRECATED" . $graph_item["gprint_text"] . $graph_item["hardreturn"]) . " ";
				}
			}
			break;
			
			
		case GRAPH_ITEM_TYPE_GPRINT_AVERAGE:
		case GRAPH_ITEM_TYPE_GPRINT_LAST:
		case GRAPH_ITEM_TYPE_GPRINT_MAX:
		case GRAPH_ITEM_TYPE_GPRINT_MIN:
			if (!isset($graph_data_array["graph_nolegend"])) {
				/* rrdtool 1.2.x VDEFs must suppress the consolidation function on GPRINTs */
				if ($rrdtool_version != RRD_VERSION_1_0) {
					if ($graph_item["vdef_id"] == "0") {
						$txt_graph_items .= "GPRINT:" . $data_source_name . ":" . $graph_item_gprint_cf{$graph_item["graph_type_id"]} . ":" . cacti_escapeshellarg($graph_item["text_padding"] . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $graph_item["hardreturn"]) . " ";
					}else{
						$txt_graph_items .= "GPRINT:" . $data_source_name . ":" . cacti_escapeshellarg($graph_item["text_padding"] . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $graph_item["hardreturn"]) . " ";
					}
				}else {
					$txt_graph_items .= "GPRINT:" . $data_source_name . ":" . $graph_item_gprint_cf{$graph_item["graph_type_id"]} . ":" . cacti_escapeshellarg($graph_item["text_padding"] . $graph_variables["text_format"][$graph_item_id] . $graph_item["gprint_text"] . $graph_item["hardreturn"]) . " ";
				}
			}
			break;
			
			
		case GRAPH_ITEM_TYPE_AREA:
			$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . $data_source_name . $graph_item_color_code . ":" . cacti_escapeshellarg($graph_variables["text_format"][$graph_item_id] . $graph_item["hardreturn"]) . " ";
			if ($graph_item["shift"] == CHECKED && $graph_item["value"] > 0) {	# create a SHIFT statement
				$txt_graph_items .= RRD_NL . "SHIFT:" . $data_source_name . ":" . cacti_escapeshellarg($graph_item["value"]);
			}
			break;
			
			
		case GRAPH_ITEM_TYPE_STACK:
			if ($rrdtool_version != RRD_VERSION_1_0) {
				$txt_graph_items .= "AREA:" . $data_source_name . $graph_item_color_code . ":" . cacti_escapeshellarg($graph_variables["text_format"][$graph_item_id] . $graph_item["hardreturn"]) . ":STACK ";
			}else {
				$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . $data_source_name . $graph_item_color_code . ":" . cacti_escapeshellarg($graph_variables["text_format"][$graph_item_id] . $graph_item["hardreturn"]) . " ";
			}
			if ($graph_item["shift"] == CHECKED && $graph_item["value"] > 0) {	# create a SHIFT statement
				$txt_graph_items .= RRD_NL . "SHIFT:" . $data_source_name . ":" . cacti_escapeshellarg($graph_item["value"]);
			}
			break;
			
			
		case GRAPH_ITEM_TYPE_LINE1:
		case GRAPH_ITEM_TYPE_LINE2:
		case GRAPH_ITEM_TYPE_LINE3:
			$txt_graph_items .= "LINE" . $graph_item["line_width"] . ":" . $data_source_name . $graph_item_color_code . ":" . cacti_escapeshellarg($graph_variables["text_format"][$graph_item_id] . $graph_item["hardreturn"]) . "" . $dash;
			if ($graph_item["shift"] == CHECKED && $graph_item["value"] > 0) {	# create a SHIFT statement
				$txt_graph_items .= RRD_NL . "SHIFT:" . $data_source_name . ":" . cacti_escapeshellarg($graph_item["value"]);
			}
			break;
			
			
		case GRAPH_ITEM_TYPE_LINESTACK:
			if ($rrdtool_version != RRD_VERSION_1_0) {
				$txt_graph_items .= "LINE" . $graph_item["line_width"] . ":" . $data_source_name . $graph_item_color_code . ":" . cacti_escapeshellarg($graph_variables["text_format"][$graph_item_id] . $graph_item["hardreturn"]) . ":STACK" . $dash;
			}
			if ($graph_item["shift"] == CHECKED && $graph_item["value"] > 0) {	# create a SHIFT statement
				$txt_graph_items .= RRD_NL . "SHIFT:" . $data_source_name . ":" . cacti_escapeshellarg($graph_item["value"]);
			}
			break;
			
			
		case GRAPH_ITEM_TYPE_TICK:
			if ($rrdtool_version != RRD_VERSION_1_0) {
				$_fraction 	= (empty($graph_item["graph_type_id"]) 						? "" : (":" . cacti_escapeshellarg($graph_item["value"])));
				$_legend 	= (empty($graph_variables["text_format"][$graph_item_id]) 	? "" : (":" . cacti_escapeshellarg($graph_variables["text_format"][$graph_item_id] . $graph_item["hardreturn"]) . " "));
				$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . $data_source_name . $graph_item_color_code . $_fraction . $_legend;
			}
			break;
			
			
		case GRAPH_ITEM_TYPE_HRULE:
			$graph_variables["value"][$graph_item_id] = str_replace(":", "\:", $graph_variables["value"][$graph_item_id]); /* escape colons */
			/* perform variable substitution; if this does not return a number, rrdtool will FAIL! */
			$substitute = rrdgraph_substitute_host_query_data($graph_variables["value"][$graph_item_id], $graph, $graph_item);
			if (is_numeric($substitute)) {
				$graph_variables["value"][$graph_item_id] = $substitute;
			}
			$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . $graph_variables["value"][$graph_item_id] . $graph_item_color_code . ":" . cacti_escapeshellarg($graph_variables["text_format"][$graph_item_id] . $graph_item["hardreturn"]) . "" . $dash;
			break;
			
			
		case GRAPH_ITEM_TYPE_VRULE:
			if (substr_count($graph_item["value"], ":")) {
				$value_array = explode(":", $graph_item["value"]);
				
				if ($value_array[0] < 0) {
					$value = date("U") - (-3600 * $value_array[0]) - 60 * $value_array[1];
				}else{
					$value = date("U", mktime($value_array[0],$value_array[1],0));
				}
			}else if (is_numeric($graph_item["value"])) {
				$value = $graph_item["value"];
			}
			
			$txt_graph_items .= $graph_item_types{$graph_item["graph_type_id"]} . ":" . cacti_escapeshellarg($value) . $graph_item_color_code . ":" . cacti_escapeshellarg($graph_variables["text_format"][$graph_item_id] . $graph_item["hardreturn"]) . "" . $dash;
			break;
			
			
		default:
			$need_rrd_nl = FALSE;
		
	}
#cacti_log(__FUNCTION__ . " legend: " . $txt_graph_items, false, "TEST");
	return $txt_graph_items;
}


/** get length of legend strings for auto padding by whitespace
 * @param int $greatest_text_format - current greates test length
 * @param int $graph_item_type_id - type of graph item
 * @param string $text - current item's legend text string
 * @return int greatest text length found
 */
function rrdgraph_auto_padding($greatest_text_format, $graph_item_type_id, $text) {	# TODO: delete this, as soon as rrdtool_cuntion_graph_old is deleted
	/* PADDING: remember this is not perfect! its main use is for the basic graph setup of:
	AREA - GPRINT-CURRENT - GPRINT-AVERAGE - GPRINT-MAXIMUM \n
	of course it can be used in other situations, however may not work as intended.
	If you have any additions to this small piece of code, feel free to send them to me. */
	/* only applies to AREA, STACK and LINEs */
	if ($graph_item_type_id == GRAPH_ITEM_TYPE_AREA ||
		$graph_item_type_id == GRAPH_ITEM_TYPE_STACK ||
		$graph_item_type_id == GRAPH_ITEM_TYPE_LINE1 ||
		$graph_item_type_id == GRAPH_ITEM_TYPE_LINE2 ||
		$graph_item_type_id == GRAPH_ITEM_TYPE_LINE3 ||
		$graph_item_type_id == GRAPH_ITEM_TYPE_LINESTACK ||
		$graph_item_type_id == GRAPH_ITEM_TYPE_TICK) {
		$text_format_length = strlen($text);

		if ($text_format_length > $greatest_text_format) {
			$greatest_text_format = $text_format_length;
		}
	}
	return $greatest_text_format;
}


/** process specific substitutions for graph legend
 * @param array $graph - current graph data
 * @param array $graph_items - all graph items
 * @param int $graph_start - graph start time
 * @param int $graph_end - graph end time
 * @param int $rra_steps - current rra step count
 * @param int $ds_step - step size (poller interval)
 * @param bool $print_legend - true, when legend has to be printed
 * @return array - array of all graph_variables
 */
function rrdgraph_pseudo_variable_substitutions($graph, $graph_items, $graph_start, $graph_end, $rra_steps, $ds_step, $print_legend) {
	include(CACTI_INCLUDE_PATH . "/graph/graph_arrays.php");
#cacti_log(__FUNCTION__ . " started", false, "TEST");
	
	$graph_variables = array();	
	reset($graph_items);
	foreach ($graph_items as $key => $graph_item) {
		/* note the current item_id for easy access */
		$graph_item_id = $graph_item["graph_templates_item_id"];
#cacti_log(__FUNCTION__ . " id: $graph_item_id", false, "TEST");
		
		/* loop through each field that we want to substitute values for 
		 * always start fresh looping through all variable fields */
		reset($variable_fields);
		while (list($field_name, $field_array) = each($variable_fields)) {
#cacti_log(__FUNCTION__ . " field: $field_name", false, "TEST");
			/* certain fields do not require values when the legend is not to be shown 
			 * other fields have to be computed even if legend is not to be shown */
			if (($field_array["process_no_legend"] == false) && (! $print_legend)) {
				continue;
			}
			
			$graph_variables[$field_name][$graph_item_id] = $graph_item[$field_name];
			
			/* date/time substitution */
			if (strstr($graph_variables[$field_name][$graph_item_id], "|date_time|")) {
				$graph_variables[$field_name][$graph_item_id] = str_replace("|date_time|", date(date_time_format(), strtotime(db_fetch_cell("select value from settings where name='date'"))), $graph_variables[$field_name][$graph_item_id]);
			}
			
			/* data source title substitution */
			if (strstr($graph_variables[$field_name][$graph_item_id], "|data_source_title|")) {
				$graph_variables[$field_name][$graph_item_id] = str_replace("|data_source_title|", get_data_source_title($graph_item["local_data_id"]), $graph_variables[$field_name][$graph_item_id]);
			}
			
			/* data query variables */
			$graph_variables[$field_name][$graph_item_id] = rrdgraph_substitute_host_query_data($graph_variables[$field_name][$graph_item_id], $graph, $graph_item);
			
			/* Nth percentile */
			if (preg_match_all("/\|([0-9]{1,2}):(bits|bytes):(\d):(current|total|max|total_peak|all_max_current|all_max_peak|aggregate_max|aggregate_sum|aggregate_current|aggregate):(\d)?\|/", $graph_variables[$field_name][$graph_item_id], $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$graph_variables[$field_name][$graph_item_id] = str_replace($match[0], variable_nth_percentile($match, $graph_item, $graph_items, $graph_start, $graph_end), $graph_variables[$field_name][$graph_item_id]);
				}
			}
			
			/* bandwidth summation */
			if (preg_match_all("/\|sum:(\d|auto):(current|total|atomic):(\d):(\d+|auto)\|/", $graph_variables[$field_name][$graph_item_id], $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$graph_variables[$field_name][$graph_item_id] = str_replace($match[0], variable_bandwidth_summation($match, $graph_item, $graph_items, $graph_start, $graph_end, $rra_steps, $ds_step), $graph_variables[$field_name][$graph_item_id]);
				}
			}
#cacti_log(__FUNCTION__ . " variable: " . $graph_variables[$field_name][$graph_item_id], false, "TEST");
		}
	}	
	return $graph_variables;
}


/** rrdgraph_substitute_host_query_data substitute |host*| and |query*| type variables
 * @param $txt_graph_item 	the variable to be substituted
 * @param $graph				from table graph_templates_graph
 * @param $graph_item			from table graph.templates_item
 * returns					variable substituted by value
 */
function rrdgraph_substitute_host_query_data($txt_graph_item, $graph, $graph_item) {
	/* replace host variables in graph elements */
	if (empty($graph["host_id"])) {
		/* if graph has no associated host determine host_id from graph item data source */
		if (!empty($graph_item["local_data_id"])) {
			$host_id = db_fetch_cell("select host_id from data_local where id='" . $graph_item["local_data_id"] . "'");
		}
	}
	else {
		$host_id = $graph["host_id"];
	}
	$txt_graph_item = substitute_host_data($txt_graph_item, '|','|', $host_id);

	/* replace query variables in graph elements */
	if (preg_match("/\|query_[a-zA-Z0-9_]+\|/", $txt_graph_item)) {
		/* default to the graph data query information from the graph */
		if (empty($graph_item["local_data_id"])) {
			$txt_graph_item = substitute_snmp_query_data($txt_graph_item, $graph["host_id"], $graph["snmp_query_id"], $graph["snmp_index"]);
		/* use the data query information from the data source if possible */
		}else{
			$data_local = db_fetch_row("select snmp_index,snmp_query_id,host_id from data_local where id='" . $graph_item["local_data_id"] . "'");
			$txt_graph_item = substitute_snmp_query_data($txt_graph_item, $data_local["host_id"], $data_local["snmp_query_id"], $data_local["snmp_index"]);
		}
	}

	/* replace query variables in graph elements */
	if (preg_match("/\|input_[a-zA-Z0-9_]+\|/", $txt_graph_item)) {
		return substitute_data_input_data($txt_graph_item, $graph, $graph_item["local_data_id"]);
	}

	return $txt_graph_item;
}


/** 
 * modify execution environment
 */
function rrdgraph_put_environment() {
	/* rrdtool fetches the default font from it's execution environment
	 * you won't find that default font on the rrdtool statement itself!
	 * set the rrdtool default font via environment variable */
	if (read_config_option("path_rrdtool_default_font")) {
		putenv("RRD_DEFAULT_FONT=" . read_config_option("path_rrdtool_default_font"));
	}

	/* set always the TZ variable to the user defined time zone. It doesn't matter if time zone support is enabled or not */
	#putenv('TZ=' . CACTI_CUSTOM_POSIX_TZ_STRING);	# TODO: postponed until i18n
}


/** rrdgraph_start_end		computes start and end timestamps in unixtime format
 * @param array $graph_data_array				override parameters for start, end, e.g. for zooming
 * @param int $rra								rra parameters used for this graph
 * @param int $seconds_between_graph_updates
 * @return										array of start, end time
 */
function rrdgraph_start_end($graph_data_array, $rra, $seconds_between_graph_updates) {

	/* override: graph start time */
	if ((!isset($graph_data_array["graph_start"])) || ($graph_data_array["graph_start"] == "0")) {
		$graph_start = -($rra["timespan"]);
	}else{
		$graph_start = $graph_data_array["graph_start"];
	}

	/* override: graph end time */
	if ((!isset($graph_data_array["graph_end"])) || ($graph_data_array["graph_end"] == "0")) {
		$graph_end = -($seconds_between_graph_updates);
	}else{
		$graph_end = $graph_data_array["graph_end"];
	}

	return array($graph_start, $graph_end);

}


/** rrdtool_cacti_compare 	compares cacti information to rrd file information
 * @param $data_source_id		the id of the data source
 * @param $info				rrdtool info as an array
 * @return					array build like $info defining html class in case of error
 */
function rrdtool_cacti_compare($data_source_id, &$info) {
	require(CACTI_INCLUDE_PATH . "/presets/preset_rra_arrays.php");
	require(CACTI_INCLUDE_PATH . "/data_source/data_source_arrays.php");

	/* get cacti header information for given data source id */
	$cacti_header_array = db_fetch_row("SELECT " .
										"local_data_template_data_id, " .
										"rrd_step " .
									"FROM " .
										"data_template_data " .
									"WHERE " .
										"local_data_id=$data_source_id");

	$cacti_file = get_data_source_path($data_source_id, true);

	/* get cacti DS information */
	$cacti_ds_array = db_fetch_assoc("SELECT " .
									"data_source_name, " .
									"data_source_type_id, " .
									"rrd_heartbeat, " .
									"rrd_maximum, " .
									"rrd_minimum " .
								"FROM " .
									"data_template_rrd " .
								"WHERE " .
									"local_data_id = $data_source_id");

	/* get cacti RRA information */
	$cacti_rra_array = db_fetch_assoc("SELECT " .
									"rra_cf.consolidation_function_id AS cf, " .
									"rra.x_files_factor AS xff, " .
									"rra.steps AS steps, " .
									"rra.rows AS rows " .
								"FROM " .
									"rra, " .
									"rra_cf, " .
									"data_template_data_rra " .
								"WHERE " .
									"data_template_data_rra.data_template_data_id = " . $cacti_header_array["local_data_template_data_id"] .	" AND " .
									"data_template_data_rra.rra_id = rra.id AND " .
									"rra_cf.rra_id = rra.id " .
								"ORDER BY " .
									"rra_cf.consolidation_function_id, " .
									"rra.steps");


	$diff = array();
	/* -----------------------------------------------------------------------------------
	 * header information
	 -----------------------------------------------------------------------------------*/
	if ($cacti_header_array["rrd_step"] != $info["step"]) {
		$diff["step"] = __("required rrd step size is '%s'", $cacti_header_array["rrd_step"]);
	}

	/* -----------------------------------------------------------------------------------
	 * data source information
	 -----------------------------------------------------------------------------------*/
	if (sizeof($cacti_ds_array) > 0) {
		foreach ($cacti_ds_array as $key => $data_source) {
			$ds_name = $data_source["data_source_name"];

			/* try to print matching rrd file's ds information */
			if (isset($info["ds"][$ds_name]) ) {
				if (!isset($info["ds"][$ds_name]["seen"])) {
					$info["ds"][$ds_name]["seen"] = TRUE;
				} else {
					continue;
				}

				$ds_type = trim($info["ds"][$ds_name]["type"], '"');
				if ($data_source_types[$data_source["data_source_type_id"]] != $ds_type) {
					$diff["ds"][$ds_name]["type"] = __("type for data source '%s' should be '%s'", $ds_name, $data_source_types[$data_source["data_source_type_id"]]);
					$diff["tune"][] = $info["filename"] . " " . "--data-source-type " . $ds_name . ":" . $data_source_types[$data_source["data_source_type_id"]];
				}

				if ($data_source["rrd_heartbeat"] != $info["ds"][$ds_name]["minimal_heartbeat"]) {
					$diff["ds"][$ds_name]["minimal_heartbeat"] = __("heartbeat for data source '%s' should be '%s'", $ds_name, $data_source["rrd_heartbeat"]);
					$diff["tune"][] = $info["filename"] . " " . "--heartbeat " . $ds_name . ":" . $data_source["rrd_heartbeat"];
				}

				if ($data_source["rrd_minimum"] != $info["ds"][$ds_name]["min"]) {
					$diff["ds"][$ds_name]["min"] = __("rrd minimum for data source '%s' should be '%s'", $ds_name, $data_source["rrd_minimum"]);
					$diff["tune"][] = $info["filename"] . " " . "--maximum " . $ds_name . ":" . $data_source["rrd_minimum"];
				}

				if ($data_source["rrd_maximum"] != $info["ds"][$ds_name]["max"]) {
					$diff["ds"][$ds_name]["max"] = __("rrd maximum for data source '%s' should be '%s'", $ds_name, $data_source["rrd_maximum"]);
					$diff["tune"][] = $info["filename"] . " " . "--minimum " . $ds_name . ":" . $data_source["rrd_maximum"];
				}
			} else {
				# cacti knows this ds, but the rrd file does not
				$info["ds"][$ds_name]["type"] = $data_source_types[$data_source["data_source_type_id"]];
				$info["ds"][$ds_name]["minimal_heartbeat"] = $data_source["rrd_heartbeat"];
				$info["ds"][$ds_name]["min"] = $data_source["rrd_minimum"];
				$info["ds"][$ds_name]["max"] = $data_source["rrd_maximum"];
				$info["ds"][$ds_name]["seen"] = TRUE;
				$diff["ds"][$ds_name]["error"] = __("DS '%s' missing in rrd file", $ds_name);
			}
		}
	}
	/* print all data sources still known to the rrd file (no match to cacti ds will happen here) */
	if (sizeof($info["ds"]) > 0) {
		foreach ($info["ds"] as $ds_name => $data_source) {
			if (!isset($data_source["seen"])) {
				$diff["ds"][$ds_name]["error"] = __("DS '%s' missing in cacti definition", $ds_name);
			}
		}
	}


	/* -----------------------------------------------------------------------------------
	 * RRA information
	 -----------------------------------------------------------------------------------*/
	$resize = TRUE;		# assume a resize operation as long as no rra duplicates are found
	# scan cacti rra information for duplicates of (CF, STEPS)
	if (sizeof($cacti_rra_array) > 0) {
		for ($i=0; $i<= sizeof($cacti_rra_array)-1; $i++) {
			$cf = $cacti_rra_array{$i}["cf"];
			$steps = $cacti_rra_array{$i}["steps"];
			foreach($cacti_rra_array as $cacti_rra_id => $cacti_rra) {
				if ($cf == $cacti_rra["cf"] && $steps == $cacti_rra["steps"] && ($i != $cacti_rra_id)) {
					$diff['rra'][$i]["error"] = __("Cacti RRA '%s' has same cf/steps (%s, %s) as '%s'", $i, $consolidation_functions{$cf}, $steps, $cacti_rra_id);
					$diff['rra'][$cacti_rra_id]["error"] = __("Cacti RRA '%s' has same cf/steps (%s, %s) as '%s'", $cacti_rra_id, $consolidation_functions{$cf}, $steps, $i);
					$resize = FALSE;
				}
			}
		}
	}
	# scan file rra information for duplicates of (CF, PDP_PER_ROWS)
	if (sizeof($info['rra']) > 0) {
		for ($i=0; $i<= sizeof($info['rra'])-1; $i++) {
			$cf = $info['rra']{$i}["cf"];
			$steps = $info['rra']{$i}["pdp_per_row"];
			foreach($info['rra'] as $file_rra_id => $file_rra) {
				if (($cf == $file_rra["cf"]) && ($steps == $file_rra["pdp_per_row"]) && ($i != $file_rra_id)) {
					$diff['rra'][$i]["error"] = __("File RRA '%s' has same cf/steps (%s, %s) as '%s'", $i, $cf, $steps, $file_rra_id);
					$diff['rra'][$file_rra_id]["error"] = __("File RRA '%s' has same cf/steps (%s, %s) as '%s'", $file_rra_id, $cf, $steps, $i);
					$resize = FALSE;
				}
			}
		}
	}

	/* print all RRAs known to cacti and add those from matching rrd file */
	if (sizeof($cacti_rra_array) > 0) {
		foreach($cacti_rra_array as $cacti_rra_id => $cacti_rra) {
			/* find matching rra info from rrd file
			 * do NOT assume, that rra sequence is kept ($cacti_rra_id != $file_rra_id may happen)!
			 * Match is assumed, if CF and STEPS/PDP_PER_ROW match; so go for it */
			foreach ($info['rra'] as $file_rra_id => $file_rra) {

				/* in case of mismatch, $file_rra["pdp_per_row"] might not be defined */
				if (!isset($file_rra["pdp_per_row"])) $file_rra["pdp_per_row"] = 0;

				if ($consolidation_functions{$cacti_rra["cf"]} == trim($file_rra["cf"], '"') &&
					$cacti_rra["steps"] == $file_rra["pdp_per_row"]) {

					if (!isset($info['rra'][$file_rra_id]["seen"])) {
						# mark both rra id's as seen to avoid printing them as non-matching
						$info['rra'][$file_rra_id]["seen"] = TRUE;
						$cacti_rra_array[$cacti_rra_id]["seen"] = TRUE;
					} else {
						continue;
					}

					if ($cacti_rra["xff"] != $file_rra["xff"]) {
						$diff['rra'][$file_rra_id]["xff"] = __("xff for cacti rra id '%s' should be '%s'", $cacti_rra_id, $cacti_rra["xff"]);
					}

					if ($cacti_rra["rows"] != $file_rra["rows"] && $resize) {
						$diff['rra'][$file_rra_id]["rows"] = __("number of rows for cacti rra id '%s' should be '%s'", $cacti_rra_id, $cacti_rra["rows"]);
						if ($cacti_rra["rows"] > $file_rra["rows"]) {
							$diff["resize"][] = $info["filename"] . " " . $cacti_rra_id . " GROW " . ($cacti_rra["rows"] - $file_rra["rows"]);
						} else {
							$diff["resize"][] = $info["filename"] . " " . $cacti_rra_id . " SHRINK " . ($file_rra["rows"] - $cacti_rra["rows"]);
						}
					}
				}
			}
			# if cacti knows an rra that has no match, consider this as an error
			if (!isset($cacti_rra_array[$cacti_rra_id]["seen"])) {
				# add to info array for printing, the index $cacti_rra_id has no real meaning
				$info['rra']["cacti_" . $cacti_rra_id]["cf"] = $consolidation_functions{$cacti_rra["cf"]};
				$info['rra']["cacti_" . $cacti_rra_id]["steps"] = $cacti_rra["steps"];
				$info['rra']["cacti_" . $cacti_rra_id]["xff"] = $cacti_rra["xff"];
				$info['rra']["cacti_" . $cacti_rra_id]["rows"] = $cacti_rra["rows"];
				$diff['rra']["cacti_" . $cacti_rra_id]["error"] = __("RRA '%s' missing in rrd file", $cacti_rra_id);
			}
		}
	}

	# if the rrd file has an rra that has no cacti match, consider this as an error
	if (sizeof($info['rra']) > 0) {
		foreach ($info['rra'] as $file_rra_id => $file_rra) {
			if (!isset($info['rra'][$file_rra_id]["seen"])) {
				$diff['rra'][$file_rra_id]["error"] = __("RRA '%s' missing in cacti definition", $file_rra_id);
			}
		}
	}

	return $diff;

}


/** take output from rrdtool info array and build html table
 * @param array $info_array - array of rrdtool info data
 * @param array $diff - array of differences between definition and current rrd file settings
 * @return string - html code
 */
function rrdtool_info2html($info_array, $diff=array()) {
	html_start_box(__("RRD File Information"), "100", 0, "center", "");

	# header data
	$header_items = array(array("name" => __("Header")), array("name" => ''));
	print "<tr><td>";
	html_header($header_items, 1, false, 'info_header');
	# add human readable timestamp
	if (isset($info_array["last_update"])) {
		$info_array["last_update"] .= " [" . date(date_time_format(), $info_array["last_update"]) . "]";
	}
	$loop = array(
		"filename" 		=> $info_array["filename"],
		"rrd_version"	=> $info_array["rrd_version"],
		"step" 			=> $info_array["step"],
		"last_update"	=> $info_array["last_update"]);
	foreach ($loop as $key => $value) {
		form_alternate_row_color($key, true);
		form_selectable_cell($key, 'key');
		form_selectable_cell($value, 'value', "", ((isset($diff[$key]) ? "textError" : "")));
		form_end_row();
	}
	form_end_table();

	# data sources
	$header_items = array(
		array("name" => __("Data Source Items")),
		array("name" => __('Type')),
		array("name" => __('Minimal Heartbeat'), "align" => "right"),
		array("name" => __('Min'), "align" => "right"),
		array("name" => __('Max'), "align" => "right"),
		array("name" => __('Last DS'), "align" => "right"),
		array("name" => __('Value'), "align" => "right"),
		array("name" => __('Unkown Sec'), "align" => "right")
	);
	print "<tr><td>";
	html_header($header_items, 1, false, 'info_ds');
	if (sizeof($info_array["ds"]) > 0) {
		foreach ($info_array["ds"] as $key => $value) {
			form_alternate_row_color('line' . $key, true);
			form_selectable_cell($key, 																			'name', 				"", (isset($diff["ds"][$key]["error"]) 				? "textError" : ""));
			form_selectable_cell((isset($value['type']) 				? $value['type'] : ''), 				'type', 				"", (isset($diff["ds"][$key]['type']) 				? "textError, right" : "right"));
			form_selectable_cell((isset($value['minimal_heartbeat']) 	? $value['minimal_heartbeat'] : ''), 	'minimal_heartbeat', 	"", (isset($diff["ds"][$key]['minimal_heartbeat'])	? "textError, right" : "right"));
			form_selectable_cell((isset($value['min']) 					? floatval($value['min']) : ''), 		'min', 					"", (isset($diff["ds"][$key]['min']) 				? "textError, right" : "right"));
			form_selectable_cell((isset($value['max']) 					? floatval($value['max']) : ''), 		'max', 					"", (isset($diff["ds"][$key]['max']) 				? "textError, right" : "right"));
			form_selectable_cell((isset($value['last_ds']) 				? $value['last_ds'] : ''), 				'last_ds', '', 'right');
			form_selectable_cell((isset($value['value']) 				? floatval($value['value']) : ''), 		'value', '', 'right');
			form_selectable_cell((isset($value['unknown_sec']) 			? $value['unknown_sec'] : ''), 			'unknown_sec', '', 'right');
			form_end_row();
		}
		form_end_table();
	}


	# round robin archive
	$header_items = array(
		array("name" => __("Round Robin Archive")),
		array("name" => __('Consolidation Function')),
		array("name" => __('Rows'), "align" => "right"),
		array("name" => __('Cur Row'), "align" => "right"),
		array("name" => __('PDP per Row'), "align" => "right"),
		array("name" => __('X Files Factor'), "align" => "right"),
		array("name" => __('CDP Prep Value (0)'), "align" => "right"),
		array("name" => __('CDP Unknown Datapoints (0)'), "align" => "right")
	);
	print "<tr><td>";
	html_header($header_items, 1, false, 'info_rra');
	if (sizeof($info_array['rra']) > 0) {
		foreach ($info_array['rra'] as $key => $value) {
			form_alternate_row_color('line_' . $key, true);
			form_selectable_cell($key, 																										'name', 			"", (isset($diff['rra'][$key]["error"]) ? "textError" : ""));
			form_selectable_cell((isset($value['cf']) 								? $value['cf'] : ''), 									'cf', '', 'right');
			form_selectable_cell((isset($value['rows']) 							? $value['rows'] : ''), 								'rows', 			"", (isset($diff['rra'][$key]['rows']) 	? "textError, right" : "right"));
			form_selectable_cell((isset($value['cur_row']) 							? $value['cur_row'] : ''), 								'cur_row', '', 'right');
			form_selectable_cell((isset($value['pdp_per_row']) 						? $value['pdp_per_row'] : ''), 							'pdp_per_row', '', 'right');
			form_selectable_cell((isset($value['xff']) 								? floatval($value['xff']) : ''), 						'xff', 				"", (isset($diff['rra'][$key]['xff']) 	? "textError, right" : "right"));
			form_selectable_cell((isset($value['cdp_prep'][0]['value']) 			? (strtolower($value['cdp_prep'][0]['value']) == "nan") ? $value['cdp_prep'][0]['value'] : floatval($value['cdp_prep'][0]['value']) : ''), 'value', '', 'right');
			form_selectable_cell((isset($value['cdp_prep'][0]['unknown_datapoints'])? $value['cdp_prep'][0]['unknown_datapoints'] : ''), 	'unknown_datapoints', '', 'right');
			form_end_row();
		}
		form_end_table();
	}


	print "</table></td></tr>";		/* end of html_header */


	html_end_box();
}


/** rrdtool_tune			- create rrdtool tune/resize commands
 * 						  html+cli enabled
 * @param $rrd_file		- rrd file name
 * @param $diff			- array of discrepancies between cacti setttings and rrd file info
 * @param $show_source	- only show text+commands or execute all commands, execute is for cli mode only!
 */
function rrdtool_tune($rrd_file, $diff, $show_source=TRUE) {

	function print_leaves($array, $nl) {
		foreach ($array as $key => $line) {
			if (!is_array($line)) {
				print $line . $nl;
			} else {
				if ($key === "tune") continue;
				if ($key === "resize") continue;
				print_leaves($line, $nl);
			}
		}

	}


	$cmd = array();
	# for html/cli mode
	if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
		$nl = "<br/>";
	} else {
		$nl = "\n";
	}

	if ($show_source && sizeof($diff)) {
		# print error descriptions
		print_leaves($diff, $nl);
	}

	if (isset($diff["tune"]) && sizeof($diff["tune"])) {
		# create tune commands
		foreach ($diff["tune"] as $line) {
			if ($show_source == true) {
				print read_config_option("path_rrdtool") . " tune " . $line . $nl;
			}else{
				rrdtool_execute("tune $line", true, RRDTOOL_OUTPUT_STDOUT);
			}
		}
	}

	if (isset($diff["resize"]) && sizeof($diff["resize"])) {
		# each resize goes into an extra line
		foreach ($diff["resize"] as $line) {
			if ($show_source == true) {
				print read_config_option("path_rrdtool") . " resize " . $line . $nl;
				print __("rename %s to %s", dirname($rrd_file) . "/resize.rrd", $rrd_file) . $nl;
			}else{
				rrdtool_execute("resize $line", true, RRDTOOL_OUTPUT_STDOUT);
				rename(dirname($rrd_file) . "/resize.rrd", $rrd_file);
			}
		}
	}
}


/** Given a data source id, check the rrdtool file to the data source definition
 * @param $data_source_id - data source id
 * @return - (array) an array containing issues with the rrdtool file definition vs data source
 */
function rrd_check($data_source_id) {
	global $rrd_tune_array;
	require(CACTI_INCLUDE_PATH . "/data_source/data_source_arrays.php");

	$data_source_name = get_data_source_item_name($rrd_tune_array["data_source_id"]);
	$data_source_type = $data_source_types{$rrd_tune_array["data-source-type"]};
	$data_source_path = get_data_source_path($rrd_tune_array["data_source_id"], true);


}


/** Given a data source id, update the rrdtool file to match the data source definition
 * @param $data_source_id - data source id
 * @return - 1 success, 2 false
 */
function rrd_repair($data_source_id) {
	global $rrd_tune_array;
	require(CACTI_INCLUDE_PATH . "/data_source/data_source_arrays.php");

	$data_source_name = get_data_source_item_name($rrd_tune_array["data_source_id"]);
	$data_source_type = $data_source_types{$rrd_tune_array["data-source-type"]};
	$data_source_path = get_data_source_path($rrd_tune_array["data_source_id"], true);


}


/** add a (list of) datasource(s) to an (array of) rrd file(s)
 * @param array $file_array	- array of rrd files
 * @param array $ds_array	- array of datasouce parameters
 * @param bool $debug		- debug mode
 * @return mixed			- success (bool) or error message (array)
 */
function rrd_datasource_add($file_array, $ds_array, $debug) {
#	require_once(CACTI_LIBRARY_PATH . "/rrd.php");
	require(CACTI_INCLUDE_PATH . "/data_source/data_source_arrays.php");

	$rrdtool_pipe = rrd_init();

	/* iterate all given rrd files */
	foreach ($file_array as $file) {
		/* create a DOM object from an rrdtool dump */
		$dom = new domDocument;
		$dom->loadXML(rrdtool_execute("dump $file", false, RRDTOOL_OUTPUT_STDOUT, $rrdtool_pipe, 'UTIL'));
		if (!$dom) {
			$check["err_msg"] = __('Error while parsing the XML of rrdtool dump');
			return $check;
		}

		/* rrdtool dump depends on rrd file version:
		 * version 0001 => RRDTool 1.0.x
		 * version 0003 => RRDTool 1.2.x, 1.3.x, 1.4.x
		 */
		$version = trim($dom->getElementsByTagName('version')->item(0)->nodeValue);

		/* now start XML processing */
		foreach ($ds_array as $ds) {
			/* first, append the <DS> strcuture in the rrd header */
			if ($ds['type'] === $data_source_types[DATA_SOURCE_TYPE_COMPUTE]) {
				rrd_append_compute_ds($dom, $version, $ds['name'], $ds['type'], $ds['cdef']);
			} else {
				rrd_append_ds($dom, $version, $ds['name'], $ds['type'], $ds['heartbeat'], $ds['min'], $ds['max']);
			}
			/* now work on the <DS> structure as part of the <cdp_prep> tree */
			rrd_append_cdp_prep_ds($dom, $version);
			/* add <V>alues to the <database> tree */
			rrd_append_value($dom);
		}

		if ($debug) {
			echo $dom->saveXML();
		} else {
			/* for rrdtool restore, we need a file, so write the XML to disk */
			$xml_file = $file . '.xml';
			$rc = $dom->save($xml_file);
			/* verify, if write was successful */
			if ($rc === false) {
				$check["err_msg"] = __('ERROR while writing XML file: %s', $xml_file);
				return $check;
			} else {
				/* are we allowed to write the rrd file? */
				if (is_writable($file)) {
					/* restore the modified XML to rrd */
					rrdtool_execute("restore -f $xml_file $file", false, RRDTOOL_OUTPUT_STDOUT, $rrdtool_pipe, 'UTIL');
					/* scratch that XML file to avoid filling up the disk */
					unlink($xml_file);
					cacti_log(__("Added datasource(s) to rrd file: %s", $file), false, 'UTIL');
				} else {
					$check["err_msg"] = __('ERROR: RRD file %s not writeable', $file);
					return $check;
				}
			}
		}
	}

	rrd_close($rrdtool_pipe);

	return true;
}


/** delete a (list of) rra(s) from an (array of) rrd file(s)
 * @param array $file_array	- array of rrd files
 * @param array $rra_array	- array of rra parameters
 * @param bool $debug		- debug mode
 * @return mixed			- success (bool) or error message (array)
 */
function rrd_rra_delete($file_array, $rra_array, $debug) {
	require_once (CACTI_LIBRARY_PATH . "/rrd.php");
	$rrdtool_pipe = '';

	/* iterate all given rrd files */
	foreach ($file_array as $file) {
		/* create a DOM document from an rrdtool dump */
		$dom = new domDocument;
		$dom->loadXML(rrdtool_execute("dump $file", false, RRDTOOL_OUTPUT_STDOUT, $rrdtool_pipe, 'UTIL'));
		if (!$dom) {
			$check["err_msg"] = __('Error while parsing the XML of rrdtool dump');
			return $check;
		}

		/* now start XML processing */
		foreach ($rra_array as $rra) {
			rrd_delete_rra($dom, $rra, $debug);
		}

		if ($debug) {
			echo $dom->saveXML();
		} else {
			/* for rrdtool restore, we need a file, so write the XML to disk */
			$xml_file = $file . '.xml';
			$rc = $dom->save($xml_file);
			/* verify, if write was successful */
			if ($rc === false) {
				$check["err_msg"] = __('ERROR while writing XML file: %s', $xml_file);
				return $check;
			} else {
				/* are we allowed to write the rrd file? */
				if (is_writable($file)) {
					/* restore the modified XML to rrd */
					rrdtool_execute("restore -f $xml_file $file", false, RRDTOOL_OUTPUT_STDOUT, $rrdtool_pipe, 'UTIL');
					/* scratch that XML file to avoid filling up the disk */
					unlink($xml_file);
					cacti_log(__("Deleted rra(s) from rrd file: %s", $file), false, 'UTIL');
				} else {
					$check["err_msg"] = __('ERROR: RRD file %s not writeable', $file);
					return $check;
				}
			}
		}
	}

	rrd_close($rrdtool_pipe);

	return true;
}


/** clone a (list of) rra(s) from an (array of) rrd file(s)
 * @param array $file_array	- array of rrd files
 * @param string $cf		- new consolidation function
 * @param array $rra_array	- array of rra parameters
 * @param bool $debug		- debug mode
 * @return mixed			- success (bool) or error message (array)
 */
function rrd_rra_clone($file_array, $cf, $rra_array, $debug) {
	require_once (CACTI_LIBRARY_PATH . "/rrd.php");
	$rrdtool_pipe = '';

	/* iterate all given rrd files */
	foreach ($file_array as $file) {
		/* create a DOM document from an rrdtool dump */
		$dom = new domDocument;
		$dom->loadXML(rrdtool_execute("dump $file", false, RRDTOOL_OUTPUT_STDOUT, $rrdtool_pipe, 'UTIL'));
		if (!$dom) {
			$check["err_msg"] = __('Error while parsing the XML of rrdtool dump');
			return $check;
		}

		/* now start XML processing */
		foreach ($rra_array as $rra) {
			rrd_copy_rra($dom, $cf, $rra, $debug);
		}

		if ($debug) {
			echo $dom->saveXML();
		} else {
			/* for rrdtool restore, we need a file, so write the XML to disk */
			$xml_file = $file . '.xml';
			$rc = $dom->save($xml_file);
			/* verify, if write was successful */
			if ($rc === false) {
				$check["err_msg"] = __('ERROR while writing XML file: %s', $xml_file);
				return $check;
			} else {
				/* are we allowed to write the rrd file? */
				if (is_writable($file)) {
					/* restore the modified XML to rrd */
					rrdtool_execute("restore -f $xml_file $file", false, RRDTOOL_OUTPUT_STDOUT, $rrdtool_pipe, 'UTIL');
					/* scratch that XML file to avoid filling up the disk */
					unlink($xml_file);
					cacti_log(__("Deleted rra(s) from rrd file: %s", $file), false, 'UTIL');
				} else {
					$check["err_msg"] = __('ERROR: RRD file %s not writeable', $file);
					return $check;
				}
			}
		}
	}

	rrd_close($rrdtool_pipe);

	return true;
}


/** appends a <DS> subtree to an RRD XML structure
 * @param object $dom	- the DOM object, where the RRD XML is stored
 * @param string $version- rrd file version
 * @param string $name	- name of the new ds
 * @param string $type	- type of the new ds
 * @param int $min_hb	- heartbeat of the new ds
 * @param string $min	- min value of the new ds or [NaN|U]
 * @param string $max	- max value of the new ds or [NaN|U]
 * @return object		- modified DOM
 */
function rrd_append_ds($dom, $version, $name, $type, $min_hb, $min, $max) {

	/* rrdtool version dependencies */
	if ($version === RRD_FILE_VERSION1) {
		$last_ds = "U";
	}
	elseif ($version === RRD_FILE_VERSION3) {
		$last_ds = "UNKN";
	}

	/* create <DS> subtree */
	$new_dom = new DOMDocument;
	/* pretty print */
	$new_dom->formatOutput = true;
	/* this defines the new node structure */
	$new_dom->loadXML("
			<ds>
				<name> $name </name>
				<type> $type </type>
				<minimal_heartbeat> $min_hb </minimal_heartbeat>
				<min> $min </min>
				<max> $max </max>

				<!-- PDP Status -->
				<last_ds> $last_ds </last_ds>
				<value> 0.0000000000e+00 </value>
				<unknown_sec> 0 </unknown_sec>
			</ds>");
	/* create a node element from new document */
	$new_node = $new_dom->getElementsByTagName("ds")->item(0);
	#echo $new_dom->saveXML();	# print new node

	/* get XPATH notation required for positioning */
	#$xpath = new DOMXPath($dom);
	/* get XPATH for entry where new node will be inserted
	 * which is the <rra> entry */
	#$insert = $xpath->query('/rrd/rra')->item(0);
	$insert = $dom->getElementsByTagName("rra")->item(0);

	/* import the new node */
	$new_node = $dom->importNode($new_node, true);
	/* and insert it at the correct place */
	$insert->parentNode->insertBefore($new_node, $insert);
}


/** COMPUTE DS: appends a <DS> subtree to an RRD XML structure
 * @param object $dom	- the DOM object, where the RRD XML is stored
 * @param string $version- rrd file version
 * @param string $name	- name of the new ds
 * @param string $type	- type of the new ds
 * @param int $cdef		- the cdef rpn used for COMPUTE
 * @return object		- modified DOM
 */
function rrd_append_compute_ds($dom, $version, $name, $type, $cdef) {

	/* rrdtool version dependencies */
	if ($version === RRD_FILE_VERSION1) {
		$last_ds = "U";
	}
	elseif ($version === RRD_FILE_VERSION3) {
		$last_ds = "UNKN";
	}

	/* create <DS> subtree */
	$new_dom = new DOMDocument;
	/* pretty print */
	$new_dom->formatOutput = true;
	/* this defines the new node structure */
	$new_dom->loadXML("
			<ds>
				<name> $name </name>
				<type> $type </type>
				<cdef> $cdef </cdef>

				<!-- PDP Status -->
				<last_ds> $last_ds </last_ds>
				<value> 0.0000000000e+00 </value>
				<unknown_sec> 0 </unknown_sec>
			</ds>");
	/* create a node element from new document */
	$new_node = $new_dom->getElementsByTagName("ds")->item(0);

	/* get XPATH notation required for positioning */
	#$xpath = new DOMXPath($dom);
	/* get XPATH for entry where new node will be inserted
	 * which is the <rra> entry */
	#$insert = $xpath->query('/rrd/rra')->item(0);
	$insert = $dom->getElementsByTagName("rra")->item(0);

	/* import the new node */
	$new_node = $dom->importNode($new_node, true);
	/* and insert it at the correct place */
	$insert->parentNode->insertBefore($new_node, $insert);
}


/** append a <DS> subtree to the <CDP_PREP> subtrees of a RRD XML structure
 * @param object $dom		- the DOM object, where the RRD XML is stored
 * @param string $version	- rrd file version
 * @return object			- the modified DOM object
 */
function rrd_append_cdp_prep_ds($dom, $version) {

	/* get all <cdp_prep><ds> entries */
	#$cdp_prep_list = $xpath->query('/rrd/rra/cdp_prep');
	$cdp_prep_list = $dom->getElementsByTagName("rra")->item(0)->getElementsByTagName("cdp_prep");

	/* get XPATH notation required for positioning */
	#$xpath = new DOMXPath($dom);

	/* get XPATH for source <ds> entry */
	#$src_ds = $xpath->query('/rrd/rra/cdp_prep/ds')->item(0);
	$src_ds = $dom->getElementsByTagName("rra")->item(0)->getElementsByTagName("cdp_prep")->item(0)->getElementsByTagName("ds")->item(0);
	/* clone the source ds entry to preserve RRDTool notation */
	$new_ds = $src_ds->cloneNode(true);

	/* rrdtool version dependencies */
	if ($version === RRD_FILE_VERSION3) {
		$new_ds->getElementsByTagName("primary_value")->item(0)->nodeValue = " NaN ";
		$new_ds->getElementsByTagName("secondary_value")->item(0)->nodeValue = " NaN ";
	}

	/* the new node always has default entries */
	$new_ds->getElementsByTagName("value")->item(0)->nodeValue = " NaN ";
	$new_ds->getElementsByTagName("unknown_datapoints")->item(0)->nodeValue = " 0 ";


	/* iterate all entries found, equals "number of <rra>" times "number of <ds>" */
	if ($cdp_prep_list->length) {
		foreach ($cdp_prep_list as $cdp_prep) {
			/* $cdp_prep now points to the next <cdp_prep> XML Element
			 * and append new ds entry at end of <cdp_prep> child list */
			$cdp_prep->appendChild($new_ds);
		}
	}
}


/** append a <V>alue element to the <DATABASE> subtrees of a RRD XML structure
 * @param object $dom	- the DOM object, where the RRD XML is stored
 * @return object		- the modified DOM object
 */
function rrd_append_value($dom) {

	/* get XPATH notation required for positioning */
	#$xpath = new DOMXPath($dom);

	/* get all <cdp_prep><ds> entries */
	#$itemList = $xpath->query('/rrd/rra/database/row');
	$itemList = $dom->getElementsByTagName("row");

	/* create <V> entry to preserve RRDTool notation */
	$new_v = $dom->createElement("v", " NaN ");

	/* iterate all entries found, equals "number of <rra>" times "number of <ds>" */
	if ($itemList->length) {
		foreach ($itemList as $item) {
			/* $item now points to the next <cdp_prep> XML Element
			 * and append new ds entry at end of <cdp_prep> child list */
			$item->appendChild($new_v);
		}
	}
}
/** delete an <RRA> subtree from the <RRD> XML structure
 * @param object $dom		- the DOM document, where the RRD XML is stored
 * @param array $rra_parm	- a single rra parameter set, given by the user
 * @return object			- the modified DOM object
 */
function rrd_delete_rra($dom, $rra_parm) {

	/* find all RRA DOMNodes */
	$rras = $dom->getElementsByTagName('rra');

	/* iterate all entries found */
	$nb = $rras->length;
	for ($pos = 0; $pos < $nb; $pos++) {
		/* retrieve all RRA DOMNodes one by one */
		$rra = $rras->item($pos);
		$cf = $rra->getElementsByTagName('cf')->item(0)->nodeValue;
		$pdp_per_row = $rra->getElementsByTagName('pdp_per_row')->item(0)->nodeValue;
		$xff = $rra->getElementsByTagName('xff')->item(0)->nodeValue;
		$rows = $rra->getElementsByTagName('row')->length;

		if ($cf 			== $rra_parm['cf'] &&
			$pdp_per_row 	== $rra_parm['pdp_per_row'] &&
			$xff 			== $rra_parm['xff'] &&
			$rows 			== $rra_parm['rows']) {
			print(__("RRA (CF=%s, ROWS=%d, PDP_PER_ROW=%d, XFF=%1.2f) removed from RRD file\n", $cf, $rows, $pdp_per_row, $xff));
			/* we need the parentNode for removal operation */
			$parent = $rra->parentNode;
			$parent->removeChild($rra);
			break; /* do NOT accidentally remove more than one element, else loop back to forth */
		}
	}
	return $dom;
}


/** clone an <RRA> subtree of the <RRD> XML structure, replacing cf
 * @param object $dom		- the DOM document, where the RRD XML is stored
 * @param string $cf		- new consolidation function
 * @param array $rra_parm	- a single rra parameter set, given by the user
 * @return object			- the modified DOM object
 */
function rrd_copy_rra($dom, $cf, $rra_parm) {

	/* find all RRA DOMNodes */
	$rras = $dom->getElementsByTagName('rra');

	/* iterate all entries found */
	$nb = $rras->length;
	for ($pos = 0; $pos < $nb; $pos++) {
		/* retrieve all RRA DOMNodes one by one */
		$rra = $rras->item($pos);
		$_cf = $rra->getElementsByTagName('cf')->item(0)->nodeValue;
		$_pdp_per_row = $rra->getElementsByTagName('pdp_per_row')->item(0)->nodeValue;
		$_xff = $rra->getElementsByTagName('xff')->item(0)->nodeValue;
		$_rows = $rra->getElementsByTagName('row')->length;

		if ($_cf 			== $rra_parm['cf'] &&
			$_pdp_per_row 	== $rra_parm['pdp_per_row'] &&
			$_xff 			== $rra_parm['xff'] &&
			$_rows 			== $rra_parm['rows']) {
			print(__("RRA (CF=%s, ROWS=%d, PDP_PER_ROW=%d, XFF=%1.2f) adding to RRD file\n", $cf, $_rows, $_pdp_per_row, $_xff));
			/* we need the parentNode for append operation */
			$parent = $rra->parentNode;

			/* get a clone of the matching RRA */
			$new_rra = $rra->cloneNode(true);
			/* and find the "old" cf */
			#$old_cf = $new_rra->getElementsByTagName('cf')->item(0);
			/* now replace old cf with new one */
			#$old_cf->childNodes->item(0)->replaceData(0,20,$cf);
			$new_rra->getElementsByTagName("cf")->item(0)->nodeValue = $cf;

			/* append new rra entry at end of the list */
			$parent->appendChild($new_rra);
			break; /* do NOT accidentally clone more than one element, else loop back to forth */
		}
	}
	return $dom;
}
