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

function repopulate_poller_cache() {
	$poller_data = db_fetch_assoc("select id from data_local");
	$poller_items   = array();
	$local_data_ids = array();	# this will lead to all cache items being deleted!

	if (sizeof($poller_data) > 0) {
		foreach ($poller_data as $data) {
				$poller_items = array_merge($poller_items, update_poller_cache($data["id"]));
		}

		/* delete all existing entries ... */
		db_execute("DELETE FROM poller_item");
		/* .. prior to recreating everything from scratch */
		poller_update_poller_cache_from_buffer($local_data_ids, $poller_items);

	}
}

function update_poller_cache_from_query($host_id, $data_query_id) {
	$poller_data = db_fetch_assoc("select id from data_local where host_id = '$host_id' and snmp_query_id = '$data_query_id'");

	$poller_items = $local_data_ids = array();

	if (sizeof($poller_data) > 0) {
		foreach ($poller_data as $data) {
			$local_data_ids[] = $data["id"];

			$poller_items = array_merge($poller_items, update_poller_cache($data["id"]));
		}

		if (sizeof($local_data_ids)) {
			poller_update_poller_cache_from_buffer($local_data_ids, $poller_items);
		}
	}
}


/** create poller cache items for given data source
 * @param int $local_data_id - the data source id to act on
 * @param bool $commit - either update directly or return resulting array
 */
function update_poller_cache($local_data_id, $commit = false) {
	global $cnn_id;

	include_once(CACTI_INCLUDE_PATH . "/data_input/data_input_constants.php");
	include_once(CACTI_LIBRARY_PATH . "/data_query.php");
	include_once(CACTI_LIBRARY_PATH . "/poller.php");

	$poller_items = array();

	$data_input = db_fetch_row("select
		data_input.id,
		data_input.type_id,
		data_template_data.id as data_template_data_id,
		data_template_data.data_template_id,
		data_template_data.active,
		data_template_data.rrd_step
		from (data_template_data,data_input)
		where data_template_data.data_input_id=data_input.id
		and data_template_data.local_data_id=$local_data_id");

	$data_source = db_fetch_row("select host_id,snmp_query_id,snmp_index from data_local where id=$local_data_id");

	/* we have to perform some additional sql queries if this is a "query" */
	if (($data_input["type_id"] == DATA_INPUT_TYPE_SNMP_QUERY) ||
		($data_input["type_id"] == DATA_INPUT_TYPE_SCRIPT_QUERY) ||
		($data_input["type_id"] == DATA_INPUT_TYPE_QUERY_SCRIPT_SERVER)){
		$field = data_query_field_list($data_input["data_template_data_id"]);

		if (strlen($field["output_type"])) {
			$output_type_sql = "and snmp_query_graph_rrd.snmp_query_graph_id=" . $cnn_id->qstr($field["output_type"]);
		}else{
			$output_type_sql = "";
		}

		$outputs = db_fetch_assoc("select
			snmp_query_graph_rrd.snmp_field_name,
			data_template_rrd.id as data_template_rrd_id
			from (snmp_query_graph_rrd,data_template_rrd)
			where snmp_query_graph_rrd.data_template_rrd_id=data_template_rrd.local_data_template_rrd_id
			$output_type_sql
			and snmp_query_graph_rrd.data_template_id=" . $data_input["data_template_id"] . "
			and data_template_rrd.local_data_id=$local_data_id
			order by data_template_rrd.id");
	}

	if ($data_input["active"] == "on") {
		if (($data_input["type_id"] == DATA_INPUT_TYPE_SCRIPT) || ($data_input["type_id"] == DATA_INPUT_TYPE_PHP_SCRIPT_SERVER)) { /* script */
			/* fall back to non-script server actions if the user is running a version of php older than 4.3 */
			if (($data_input["type_id"] == DATA_INPUT_TYPE_PHP_SCRIPT_SERVER) && (function_exists("proc_open"))) {
				$action = POLLER_ACTION_SCRIPT_PHP;
				$script_path = get_full_script_path($local_data_id);
			}else if (($data_input["type_id"] == DATA_INPUT_TYPE_PHP_SCRIPT_SERVER) && (!function_exists("proc_open"))) {
				$action = POLLER_ACTION_SCRIPT;
				$script_path = read_config_option("path_php_binary") . " -q " . get_full_script_path($local_data_id);
			}else{
				$action = POLLER_ACTION_SCRIPT;
				$script_path = get_full_script_path($local_data_id);
			}

			$num_output_fields = sizeof(db_fetch_assoc("select id from data_input_fields where data_input_id=" . $data_input["id"] . " and input_output='out' and update_rra='on'"));

			if ($num_output_fields == 1) {
				$data_template_rrd_id = db_fetch_cell("select id from data_template_rrd where local_data_id=$local_data_id");
				$data_source_item_name = get_data_source_item_name($data_template_rrd_id);
			}else{
				$data_source_item_name = "";
			}

			$poller_items[] = poller_cache_item_add($data_source["host_id"], array(), $local_data_id, $data_input["rrd_step"], $action, $data_source_item_name, 1, $script_path);
		}else if ($data_input["type_id"] == DATA_INPUT_TYPE_SNMP) { /* snmp */
			/* get the host override fields */
			$data_template_id = db_fetch_cell("SELECT data_template_id FROM data_template_data WHERE local_data_id=$local_data_id");

			/* get host fields first */
			$host_fields = array_rekey(db_fetch_assoc("SELECT
				data_input_fields.type_code,
				data_input_data.value
				FROM data_input_fields
				LEFT JOIN data_input_data
				ON (data_input_fields.id=data_input_data.data_input_field_id and data_input_data.data_template_data_id=" . $data_input["data_template_data_id"] . ")
				WHERE ((type_code LIKE 'snmp_%') OR (type_code='hostname') OR (type_code='host_id'))
				AND data_input_data.value != ''"), "type_code", "value");

			$data_template_fields = array_rekey(db_fetch_assoc("SELECT
				data_input_fields.type_code,
				data_input_data.value
				FROM data_input_fields
				LEFT JOIN data_input_data
				ON (data_input_fields.id=data_input_data.data_input_field_id and data_input_data.data_template_data_id=$data_template_id)
				WHERE ((type_code LIKE 'snmp_%') OR (type_code='hostname'))
				AND data_template_data_id=$data_template_id
				AND data_input_data.value != ''"), "type_code", "value");

			if (sizeof($host_fields)) {
				if (sizeof($data_template_fields)) {
				foreach($data_template_fields as $key => $value) {
					if (!isset($host_fields[$key])) {
						$host_fields[$key] = $value;
					}
				}
				}
			} elseif (sizeof($data_template_fields)) {
				$host_fields = $data_template_fields;
			}

			$data_template_rrd_id = db_fetch_cell("select id from data_template_rrd where local_data_id=$local_data_id");

			$poller_items[] = poller_cache_item_add($data_source["host_id"], $host_fields, $local_data_id, $data_input["rrd_step"], 0, get_data_source_item_name($data_template_rrd_id), 1, (isset($host_fields["snmp_oid"]) ? $host_fields["snmp_oid"] : ""));
		}else if ($data_input["type_id"] == DATA_INPUT_TYPE_SNMP_QUERY) { /* snmp query */
			$snmp_queries = get_data_query_array($data_source["snmp_query_id"]);

			/* get the host override fields */
			$data_template_id = db_fetch_cell("SELECT data_template_id FROM data_template_data WHERE local_data_id=$local_data_id");

			/* get host fields first */
			$host_fields = array_rekey(db_fetch_assoc("SELECT
				data_input_fields.type_code,
				data_input_data.value
				FROM data_input_fields
				LEFT JOIN data_input_data
				ON (data_input_fields.id=data_input_data.data_input_field_id and data_input_data.data_template_data_id=" . $data_input["data_template_data_id"] . ")
				WHERE ((type_code LIKE 'snmp_%') OR (type_code='hostname'))
				AND data_input_data.value != ''"), "type_code", "value");

			$data_template_fields = array_rekey(db_fetch_assoc("SELECT
				data_input_fields.type_code,
				data_input_data.value
				FROM data_input_fields
				LEFT JOIN data_input_data
				ON (data_input_fields.id=data_input_data.data_input_field_id and data_input_data.data_template_data_id=$data_template_id)
				WHERE ((type_code LIKE 'snmp_%') OR (type_code='hostname'))
				AND data_template_data_id=$data_template_id
				AND data_input_data.value != ''"), "type_code", "value");

			if (sizeof($host_fields)) {
				if (sizeof($data_template_fields)) {
				foreach($data_template_fields as $key => $value) {
					if (!isset($host_fields[$key])) {
						$host_fields[$key] = $value;
					}
				}
				}
			} elseif (sizeof($data_template_fields)) {
				$host_fields = $data_template_fields;
			}

			if (sizeof($outputs) > 0) {
			foreach ($outputs as $output) {
				if (isset($snmp_queries["fields"]{$output["snmp_field_name"]}["oid"])) {
					$oid = $snmp_queries["fields"]{$output["snmp_field_name"]}["oid"] . "." . $data_source["snmp_index"];

					if (isset($snmp_queries["fields"]{$output["snmp_field_name"]}["oid_suffix"])) {
						$oid .= "." . $snmp_queries["fields"]{$output["snmp_field_name"]}["oid_suffix"];
					}
				}

				if (!empty($oid)) {
					$poller_items[] = poller_cache_item_add($data_source["host_id"], $host_fields, $local_data_id, $data_input["rrd_step"], 0, get_data_source_item_name($output["data_template_rrd_id"]), sizeof($outputs), $oid);
				}
			}
			}
		}else if (($data_input["type_id"] == DATA_INPUT_TYPE_SCRIPT_QUERY) || ($data_input["type_id"] == DATA_INPUT_TYPE_QUERY_SCRIPT_SERVER)) { /* script query */
			$script_queries = get_data_query_array($data_source["snmp_query_id"]);

			/* get the host override fields */
			$data_template_id = db_fetch_cell("SELECT data_template_id FROM data_template_data WHERE local_data_id=$local_data_id");

			/* get host fields first */
			$host_fields = array_rekey(db_fetch_assoc("SELECT
				data_input_fields.type_code,
				data_input_data.value
				FROM data_input_fields
				LEFT JOIN data_input_data
				ON (data_input_fields.id=data_input_data.data_input_field_id and data_input_data.data_template_data_id=" . $data_input["data_template_data_id"] . ")
				WHERE ((type_code LIKE 'snmp_%') OR (type_code='hostname'))
				AND data_input_data.value != ''"), "type_code", "value");

			$data_template_fields = array_rekey(db_fetch_assoc("SELECT
				data_input_fields.type_code,
				data_input_data.value
				FROM data_input_fields
				LEFT JOIN data_input_data
				ON (data_input_fields.id=data_input_data.data_input_field_id and data_input_data.data_template_data_id=$data_template_id)
				WHERE ((type_code LIKE 'snmp_%') OR (type_code='hostname'))
				AND data_template_data_id=$data_template_id
				AND data_input_data.value != ''"), "type_code", "value");

			if (sizeof($host_fields)) {
				if (sizeof($data_template_fields)) {
				foreach($data_template_fields as $key => $value) {
					if (!isset($host_fields[$key])) {
						$host_fields[$key] = $value;
					}
				}
				}
			} elseif (sizeof($data_template_fields)) {
				$host_fields = $data_template_fields;
			}

			if (sizeof($outputs) > 0) {
				foreach ($outputs as $output) {
					if (isset($script_queries["fields"]{$output["snmp_field_name"]}["query_name"])) {
						$identifier = $script_queries["fields"]{$output["snmp_field_name"]}["query_name"];

						/* fall back to non-script server actions if the user is running a version of php older than 4.3 */
						if (($data_input["type_id"] == DATA_INPUT_TYPE_QUERY_SCRIPT_SERVER) && (function_exists("proc_open"))) {
							$action = POLLER_ACTION_SCRIPT_PHP;
							$script_path = get_script_query_path((isset($script_queries["arg_prepend"]) ? $script_queries["arg_prepend"] : "") . " " . $script_queries["arg_get"] . " " . $identifier . " " . $data_source["snmp_index"], $script_queries["script_path"] . " " . $script_queries["script_function"], $data_source["host_id"]);
						}else if (($data_input["type_id"] == DATA_INPUT_TYPE_QUERY_SCRIPT_SERVER) && (!function_exists("proc_open"))) {
							$action = POLLER_ACTION_SCRIPT;
							$script_path = read_config_option("path_php_binary") . " -q " . get_script_query_path((isset($script_queries["arg_prepend"]) ? $script_queries["arg_prepend"] : "") . " " . $script_queries["arg_get"] . " " . $identifier . " " . $data_source["snmp_index"], $script_queries["script_path"], $data_source["host_id"]);
						}else{
							$action = POLLER_ACTION_SCRIPT;
							$script_path = get_script_query_path((isset($script_queries["arg_prepend"]) ? $script_queries["arg_prepend"] : "") . " " . $script_queries["arg_get"] . " " . $identifier . " " . $data_source["snmp_index"], $script_queries["script_path"], $data_source["host_id"]);
						}
					}

					if (isset($script_path)) {
						$poller_items[] = poller_cache_item_add($data_source["host_id"], $host_fields, $local_data_id, $data_input["rrd_step"], $action, get_data_source_item_name($output["data_template_rrd_id"]), sizeof($outputs), $script_path);
					}
				}
			}
		}
	}

	if ($commit) {
		poller_update_poller_cache_from_buffer((array)$local_data_id, $poller_items);
	} else {
		return $poller_items;
	}
}

function push_out_data_input_method($data_input_id) {
	$local_data_ids = db_fetch_assoc("SELECT DISTINCT local_data_id
		FROM data_template_data
		WHERE data_input_id='$data_input_id'
		AND local_data_id>0");

	$poller_items = array();
	$_my_local_data_ids = array();

	if (sizeof($local_data_ids)) {
		foreach($local_data_ids as $row) {
			$_my_local_data_ids[] = $row["local_data_id"];

			$poller_items = array_merge($poller_items, update_poller_cache($row["local_data_id"]));
		}

		if (sizeof($_my_local_data_ids)) {
			poller_update_poller_cache_from_buffer($_my_local_data_ids, $poller_items);
		}
	}
}

/** mass update of poller cache - can run in parallel to poller
 * @param array/int $local_data_ids - either a scalar (all ids) or an array of data source to act on
 * @param array $poller_items - the new items for poller cache
 */
function poller_update_poller_cache_from_buffer($local_data_ids, &$poller_items) {
	/* set all fields present value to 0, to mark the outliers when we are all done */
	$ids = array();
	if (sizeof($local_data_ids)) {
		$count = 0;
		foreach($local_data_ids as $id) {
			if ($count == 0) {
				$ids = $id;
			} else {
				$ids .= ", " . $id;
			}
			$count++;
		}

		db_execute("UPDATE poller_item SET present=0 WHERE local_data_id IN ($ids)");
	} else {
		/* don't mark anything in case we have no $local_data_ids => 
		 *this would flush the whole table at bottom of this function */
	}

	/* setup the database call */
	$sql_prefix   = "INSERT INTO poller_item (local_data_id, poller_id, host_id, action, hostname, " .
			"snmp_community, snmp_version, snmp_timeout, snmp_username, snmp_password, " .
			"snmp_auth_protocol, snmp_priv_passphrase, snmp_priv_protocol, snmp_context, " .
			"snmp_port, rrd_name, rrd_path, rrd_num, rrd_step, rrd_next_step, arg1, arg2, arg3, present) " .
			"VALUES";

	$sql_suffix   = " ON DUPLICATE KEY UPDATE poller_id=VALUES(poller_id), host_id=VALUES(host_id), action=VALUES(action), hostname=VALUES(hostname), " .
		"snmp_community=VALUES(snmp_community), snmp_version=VALUES(snmp_version), snmp_timeout=VALUES(snmp_timeout), " .
		"snmp_username=VALUES(snmp_username), snmp_password=VALUES(snmp_password), snmp_auth_protocol=VALUES(snmp_auth_protocol), " .
		"snmp_priv_passphrase=VALUES(snmp_priv_passphrase), snmp_priv_protocol=VALUES(snmp_priv_protocol), " .
		"snmp_context=VALUES(snmp_context), snmp_port=VALUES(snmp_port), rrd_path=VALUES(rrd_path), rrd_num=VALUES(rrd_num), " .
		"rrd_step=VALUES(rrd_step), rrd_next_step=VALUES(rrd_next_step), arg1=VALUES(arg1), arg2=VALUES(arg2), " .
		"arg3=VALUES(arg3), present=VALUES(present)";

	/* use a reasonable insert buffer, the default is 1MByte */
	$max_packet   = 256000;

	/* setup somme defaults */
	$overhead     = strlen($sql_prefix) + strlen($sql_suffix);
	$buf_len      = 0;
	$buf_count    = 0;
	$buffer       = "";

	if (sizeof($poller_items)) {
	foreach($poller_items AS $record) {
		/* take care of invalid entries */
		if (strlen($record) == 0) continue;

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
	}

	if ($buf_count > 0) {
		db_execute($sql_prefix . $buffer . $sql_suffix);
	}

	/* remove stale records from the poller cache */
	if (sizeof($ids)) {
		db_execute("DELETE FROM poller_item WHERE present=0 AND local_data_id IN ($ids)");
	}else{
		/* only handle explicitely given local_data_ids */
	}
}


/** for a given data template, update all input data and the poller cache
 * @param int $host_id - id of host, if any
 * @param int $local_data_id - id of a single data source, if any
 * @param int $data_template_id - id of data template
 * works on table data_input_data and poller cache
 */
function push_out_host($host_id, $local_data_id = 0, $data_template_id = 0) {
	global $cnn_id;
	/* ok here's the deal: first we need to find every data source that uses this data template.
	 * then we go through each of those data sources, finding each one using a data input method
	 * with "special fields". 
	 * If we find one, fill it with data from associated host */
	/* setup the poller items array */
	$poller_items   = array();
	$local_data_ids = array();
	$hosts          = array();
	$sql_where      = "";

	/* setup the sql where, and if using a host, get it's host information */
	if ($host_id != 0) {
		/* get all information about this host so we can write it to the data source */
		$hosts[$host_id] = db_fetch_row("select id AS host_id, host.* from host where id=$host_id");

		$sql_where .= " AND data_local.host_id=$host_id";
	}

	/* sql where for local_data_id */
	if ($local_data_id != 0) {
		$sql_where .= " AND data_local.id=$local_data_id";
	}

	/* sql where for data_template_id */
	if ($data_template_id != 0) {
		$sql_where .= " AND data_template_data.data_template_id=$data_template_id";
	}

	$data_sources = db_fetch_assoc("SELECT
		data_template_data.id,
		data_template_data.data_input_id,
		data_template_data.local_data_id,
		data_template_data.local_data_template_data_id,
		data_local.host_id
		FROM (data_local, data_template_data)
		WHERE data_local.id=data_template_data.local_data_id
		AND data_template_data.data_input_id>0
		$sql_where");

	/* loop through each matching data source */
	if (sizeof($data_sources) > 0) {
	foreach ($data_sources as $data_source) {
		/* set the host information */
		if (!isset($hosts[$data_source["host_id"]])) {
			$hosts[$data_source["host_id"]] = db_fetch_row("select * from host where id=" . $data_source["host_id"]);
		}
		$host = $hosts[$data_source["host_id"]];

		/* get field information from the data template */
		if (!isset($template_fields{$data_source["local_data_template_data_id"]})) {
			$template_fields{$data_source["local_data_template_data_id"]} = db_fetch_assoc("select
				data_input_data.value,
				data_input_data.t_value,
				data_input_fields.id,
				data_input_fields.type_code
				from data_input_fields left join data_input_data
				on (data_input_fields.id=data_input_data.data_input_field_id and data_input_data.data_template_data_id=" . $data_source["local_data_template_data_id"] . ")
				where data_input_fields.data_input_id=" . $data_source["data_input_id"] . "
				and (data_input_data.t_value='' or data_input_data.t_value is null)
				and data_input_fields.input_output='in'");
		}

		reset($template_fields{$data_source["local_data_template_data_id"]});

		/* loop through each field contained in the data template and push out a host value if:
		 - the field is a valid "host field"
		 - the value of the field is empty
		 - the field is set to 'templated' */
		if (sizeof($template_fields{$data_source["local_data_template_data_id"]})) {
		foreach ($template_fields{$data_source["local_data_template_data_id"]} as $template_field) {
			if ((preg_match('/^' . VALID_HOST_FIELDS . '$/i', $template_field["type_code"])) && ($template_field["value"] == "") && ($template_field["t_value"] == "")) {
				db_execute("replace into data_input_data (data_input_field_id,data_template_data_id,value) values (" . $template_field["id"] . "," . $data_source["id"] . "," . $cnn_id->qstr($host{$template_field["type_code"]}) . ")");
			}
		}
		}

		/* flag an update to the poller cache as well */
		$local_data_ids[] = $data_source["local_data_id"];
		$poller_items     = array_merge($poller_items, update_poller_cache($data_source["local_data_id"]));
	}
	}

	if (sizeof($local_data_ids)) {
		poller_update_poller_cache_from_buffer($local_data_ids, $poller_items);
	}
}

function duplicate_graph($_local_graph_id, $_graph_template_id, $graph_title) {
	require(CACTI_LIBRARY_PATH . "/graph.php");

	if (!empty($_local_graph_id)) {
		$graph_local = db_fetch_row("select * from graph_local where id=$_local_graph_id");
		$graph_template_graph = db_fetch_row("select * from graph_templates_graph where local_graph_id=$_local_graph_id");
		$graph_template_items = db_fetch_assoc("select * from graph_templates_item where local_graph_id=$_local_graph_id");

		/* create new entry: graph_local */
		$save["id"] = 0;
		$save["graph_template_id"] = $graph_local["graph_template_id"];
		$save["host_id"] = $graph_local["host_id"];
		$save["snmp_query_id"] = $graph_local["snmp_query_id"];
		$save["snmp_index"] = $graph_local["snmp_index"];

		$local_graph_id = sql_save($save, "graph_local");

		$graph_template_graph["title"] = str_replace("<graph_title>", $graph_template_graph["title"], $graph_title);
	}elseif (!empty($_graph_template_id)) {
		$graph_template = db_fetch_row("select * from graph_templates where id=$_graph_template_id");
		$graph_template_graph = db_fetch_row("select * from graph_templates_graph where graph_template_id=$_graph_template_id and local_graph_id=0");
		$graph_template_items = db_fetch_assoc("select * from graph_templates_item where graph_template_id=$_graph_template_id and local_graph_id=0");
		$graph_template_inputs = db_fetch_assoc("select * from graph_template_input where graph_template_id=$_graph_template_id");

		/* create new entry: graph_templates */
		$save["id"] = 0;
		$save["hash"] = get_hash_graph_template(0);
		$save["name"] = str_replace("<template_title>", $graph_template["name"], $graph_title);

		$graph_template_id = sql_save($save, "graph_templates");
	}

	unset($save);
	$struct_graph = graph_form_list();
	reset($struct_graph);

	/* create new entry: graph_templates_graph */
	$save["id"] = 0;
	$save["local_graph_id"] = (isset($local_graph_id) ? $local_graph_id : 0);
	$save["local_graph_template_graph_id"] = (isset($graph_template_graph["local_graph_template_graph_id"]) ? $graph_template_graph["local_graph_template_graph_id"] : 0);
	$save["graph_template_id"] = (!empty($_local_graph_id) ? $graph_template_graph["graph_template_id"] : $graph_template_id);
	$save["title_cache"] = $graph_template_graph["title_cache"];

	reset($struct_graph);
	while (list($field, $array) = each($struct_graph)) {
		$save{$field} = $graph_template_graph{$field};
		$save{"t_" . $field} = $graph_template_graph{"t_" . $field};
	}

	$graph_templates_graph_id = sql_save($save, "graph_templates_graph");

	/* create new entry(s): graph_templates_item */
	$struct_graph_item = graph_item_form_list();
	if (sizeof($graph_template_items) > 0) {
	foreach ($graph_template_items as $graph_template_item) {
		unset($save);
		reset($struct_graph_item);

		$save["id"] = 0;
		/* save a hash only for graph_template copy operations */
		$save["hash"] = (!empty($_graph_template_id) ? get_hash_graph_template(0, "graph_template_item") : 0);
		$save["local_graph_id"] = (isset($local_graph_id) ? $local_graph_id : 0);
		$save["graph_template_id"] = (!empty($_local_graph_id) ? $graph_template_item["graph_template_id"] : $graph_template_id);
		$save["local_graph_template_item_id"] = (isset($graph_template_item["local_graph_template_item_id"]) ? $graph_template_item["local_graph_template_item_id"] : 0);

		while (list($field, $array) = each($struct_graph_item)) {
			$save{$field} = $graph_template_item{$field};
		}

		$graph_item_mappings{$graph_template_item["id"]} = sql_save($save, "graph_templates_item");
	}
	}

	if (!empty($_graph_template_id)) {
		/* create new entry(s): graph_template_input (graph template only) */
		if (sizeof($graph_template_inputs) > 0) {
		foreach ($graph_template_inputs as $graph_template_input) {
			unset($save);

			$save["id"]                = 0;
			$save["graph_template_id"] = $graph_template_id;
			$save["name"]              = $graph_template_input["name"];
			$save["description"]       = $graph_template_input["description"];
			$save["column_name"]       = $graph_template_input["column_name"];
			$save["hash"]              = get_hash_graph_template(0, "graph_template_input");

			$graph_template_input_id   = sql_save($save, "graph_template_input");

			$graph_template_input_defs = db_fetch_assoc("select * from graph_template_input_defs where graph_template_input_id=" . $graph_template_input["id"]);

			/* create new entry(s): graph_template_input_defs (graph template only) */
			if (sizeof($graph_template_input_defs) > 0) {
			foreach ($graph_template_input_defs as $graph_template_input_def) {
				db_execute("insert into graph_template_input_defs (graph_template_input_id,graph_template_item_id)
					values ($graph_template_input_id," . $graph_item_mappings{$graph_template_input_def["graph_template_item_id"]} . ")");
			}
			}
		}
		}
	}

	if (!empty($_local_graph_id)) {
		update_graph_title_cache($local_graph_id);
	}
}

function duplicate_data_source($_local_data_id, $_data_template_id, $data_source_title) {
	require(CACTI_LIBRARY_PATH . "/data_source.php");

	if (!empty($_local_data_id)) {
		$data_local = db_fetch_row("select * from data_local where id=$_local_data_id");
		$data_template_data = db_fetch_row("select * from data_template_data where local_data_id=$_local_data_id");
		$data_template_rrds = db_fetch_assoc("select * from data_template_rrd where local_data_id=$_local_data_id");

		$data_input_datas = db_fetch_assoc("select * from data_input_data where data_template_data_id=" . $data_template_data["id"]);
		$data_template_data_rras = db_fetch_assoc("select * from data_template_data_rra where data_template_data_id=" . $data_template_data["id"]);

		/* create new entry: data_local */
		$save["id"] = 0;
		$save["data_template_id"] = $data_local["data_template_id"];
		$save["host_id"] = $data_local["host_id"];
		$save["snmp_query_id"] = $data_local["snmp_query_id"];
		$save["snmp_index"] = $data_local["snmp_index"];

		$local_data_id = sql_save($save, "data_local");

		$data_template_data["name"] = str_replace("<ds_title>", $data_template_data["name"], $data_source_title);
	}elseif (!empty($_data_template_id)) {
		$data_template = db_fetch_row("select * from data_template where id=$_data_template_id");
		$data_template_data = db_fetch_row("select * from data_template_data where data_template_id=$_data_template_id and local_data_id=0");
		$data_template_rrds = db_fetch_assoc("select * from data_template_rrd where data_template_id=$_data_template_id and local_data_id=0");

		$data_input_datas = db_fetch_assoc("select * from data_input_data where data_template_data_id=" . $data_template_data["id"]);
		$data_template_data_rras = db_fetch_assoc("select * from data_template_data_rra where data_template_data_id=" . $data_template_data["id"]);

		/* create new entry: data_template */
		$save["id"] = 0;
		$save["hash"] = get_hash_data_template(0);
		$save["name"] = str_replace("<template_title>", $data_template["name"], $data_source_title);

		$data_template_id = sql_save($save, "data_template");
	}

	unset($save);
	$struct_data_source = data_source_form_list();
	unset($struct_data_source["rra_id"]);
	unset($struct_data_source["data_source_path"]);
	reset($struct_data_source);

	/* create new entry: data_template_data */
	$save["id"] = 0;
	$save["local_data_id"] = (isset($local_data_id) ? $local_data_id : 0);
	$save["local_data_template_data_id"] = (isset($data_template_data["local_data_template_data_id"]) ? $data_template_data["local_data_template_data_id"] : 0);
	$save["data_template_id"] = (!empty($_local_data_id) ? $data_template_data["data_template_id"] : $data_template_id);
	$save["name_cache"] = $data_template_data["name_cache"];

	while (list($field, $array) = each($struct_data_source)) {
		$save{$field} = $data_template_data{$field};

		if ($array["flags"] != "ALWAYSTEMPLATE") {
			$save{"t_" . $field} = $data_template_data{"t_" . $field};
		}
	}

	$data_template_data_id = sql_save($save, "data_template_data");

	/* create new entry(s): data_template_rrd */
	$struct_data_source_item = data_source_item_form_list();
	if (sizeof($data_template_rrds) > 0) {
	foreach ($data_template_rrds as $data_template_rrd) {
		unset($save);
		reset($struct_data_source_item);

		$save["id"]                         = 0;
		$save["local_data_id"]              = (isset($local_data_id) ? $local_data_id : 0);
		$save["local_data_template_rrd_id"] = (isset($data_template_rrd["local_data_template_rrd_id"]) ? $data_template_rrd["local_data_template_rrd_id"] : 0);
		$save["data_template_id"]           = (!empty($_local_data_id) ? $data_template_rrd["data_template_id"] : $data_template_id);
		if ($save["local_data_id"] == 0) {
			$save["hash"]                   = get_hash_data_template($data_template_rrd["local_data_template_rrd_id"], "data_template_item");
		} else {
			$save["hash"] = '';
		}

		while (list($field, $array) = each($struct_data_source_item)) {
			$save{$field} = $data_template_rrd{$field};

			if (isset($data_template_rrd{"t_" . $field})) {
				$save{"t_" . $field} = $data_template_rrd{"t_" . $field};
			}
		}

		$data_template_rrd_id = sql_save($save, "data_template_rrd");
	}
	}

	/* create new entry(s): data_input_data */
	if (sizeof($data_input_datas) > 0) {
	foreach ($data_input_datas as $data_input_data) {
		db_execute("insert into data_input_data (data_input_field_id,data_template_data_id,t_value,value) values
			(" . $data_input_data["data_input_field_id"] . ",$data_template_data_id,'" . $data_input_data["t_value"] .
			"','" . $data_input_data["value"] . "')");
	}
	}

	/* create new entry(s): data_template_data_rra */
	if (sizeof($data_template_data_rras) > 0) {
	foreach ($data_template_data_rras as $data_template_data_rra) {
		db_execute("insert into data_template_data_rra (data_template_data_id,rra_id) values ($data_template_data_id,
			" . $data_template_data_rra["rra_id"] . ")");
	}
	}

	if (!empty($_local_data_id)) {
		update_data_source_title_cache($local_data_id);
	}
}

function duplicate_host_template($_host_template_id, $host_template_title) {
	global $fields_host_template_edit;

	$host_template = db_fetch_row("select * from host_template where id=$_host_template_id");
	$host_template_graphs = db_fetch_assoc("select * from host_template_graph where host_template_id=$_host_template_id");
	$host_template_data_queries = db_fetch_assoc("select * from host_template_snmp_query where host_template_id=$_host_template_id");

	/* substitute the title variable */
	$host_template["name"] = str_replace("<template_title>", $host_template["name"], $host_template_title);

	/* create new entry: host_template */
	$save["id"] = 0;
	$save["hash"] = get_hash_host_template(0);

	reset($fields_host_template_edit);
	while (list($field, $array) = each($fields_host_template_edit)) {
		if (!preg_match("/^hidden/", $array["method"])) {
			$save[$field] = $host_template[$field];
		}
	}

	$host_template_id = sql_save($save, "host_template");

	/* create new entry(s): host_template_graph */
	if (sizeof($host_template_graphs) > 0) {
	foreach ($host_template_graphs as $host_template_graph) {
		db_execute("insert into host_template_graph (host_template_id,graph_template_id) values ($host_template_id," . $host_template_graph["graph_template_id"] . ")");
	}
	}

	/* create new entry(s): host_template_snmp_query */
	if (sizeof($host_template_data_queries) > 0) {
	foreach ($host_template_data_queries as $host_template_data_query) {
		db_execute("insert into host_template_snmp_query (host_template_id,snmp_query_id) values ($host_template_id," . $host_template_data_query["snmp_query_id"] . ")");
	}
	}
}

function duplicate_cdef($_cdef_id, $cdef_title) {
	global $fields_cdef_edit;

	$cdef = db_fetch_row("select * from cdef where id=$_cdef_id");
	$cdef_items = db_fetch_assoc("select * from cdef_items where cdef_id=$_cdef_id");

	/* substitute the title variable */
	$cdef["name"] = str_replace("<cdef_title>", $cdef["name"], $cdef_title);

	/* create new entry: host_template */
	$save["id"] = 0;
	$save["hash"] = get_hash_cdef(0);

	reset($fields_cdef_edit);
	while (list($field, $array) = each($fields_cdef_edit)) {
		if (!preg_match("/^hidden/", $array["method"])) {
			$save[$field] = $cdef[$field];
		}
	}

	$cdef_id = sql_save($save, "cdef");

	/* create new entry(s): cdef_items */
	if (sizeof($cdef_items) > 0) {
		foreach ($cdef_items as $cdef_item) {
			unset($save);

			$save["id"] = 0;
			$save["hash"] = get_hash_cdef(0, "cdef_item");
			$save["cdef_id"] = $cdef_id;
			$save["sequence"] = $cdef_item["sequence"];
			$save["type"] = $cdef_item["type"];
			$save["value"] = $cdef_item["value"];

			sql_save($save, "cdef_items");
		}
	}
}

function duplicate_vdef($_vdef_id, $vdef_title) {
	require_once(CACTI_LIBRARY_PATH . "/vdef.php");

	$vdef = db_fetch_row("select * from vdef where id=$_vdef_id");
	$vdef_items = db_fetch_assoc("select * from vdef_items where vdef_id=$_vdef_id");

	/* substitute the title variable */
	$vdef["name"] = str_replace("<vdef_title>", $vdef["name"], $vdef_title);

	/* create new entry: vdef */
	$save["id"] = 0;
	$save["hash"] = get_hash_vdef(0);

	$fields_vdef_edit = preset_vdef_form_list();
	reset($fields_vdef_edit);
	while (list($field, $array) = each($fields_vdef_edit)) {
		if (!preg_match("/^hidden/", $array["method"])) {
			$save[$field] = $vdef[$field];
		}
	}

	$vdef_id = sql_save($save, "vdef");

	/* create new entry(s): vdef_items */
	if (sizeof($vdef_items) > 0) {
		foreach ($vdef_items as $vdef_item) {
			unset($save);

			$save["id"] = 0;
			$save["hash"] = get_hash_vdef(0, "vdef_item");
			$save["vdef_id"] = $vdef_id;
			$save["sequence"] = $vdef_item["sequence"];
			$save["type"] = $vdef_item["type"];
			$save["value"] = $vdef_item["value"];

			sql_save($save, "vdef_items");
		}
	}
}

function duplicate_xaxis($_xaxis_id, $xaxis_title) {
	require_once(CACTI_LIBRARY_PATH . "/xaxis.php");

	$xaxis = db_fetch_row("select * from graph_templates_xaxis where id=$_xaxis_id");
	$xaxis_items = db_fetch_assoc("select * from graph_templates_xaxis_items where xaxis_id=$_xaxis_id ORDER BY timespan");

	/* create new entry: xaxis */
	$save["id"] = 0;
	$save["hash"] = get_hash_xaxis(0);
	/* substitute the title variable */
	$save["name"] = str_replace("<xaxis_title>", $xaxis["name"], $xaxis_title);

	$xaxis_id = sql_save($save, "graph_templates_xaxis");
	$fields_xaxis_item_edit = preset_xaxis_item_form_list();

	/* create new entry(s): xaxis_items */
	if (sizeof($xaxis_items) > 0) {
		foreach ($xaxis_items as $xaxis_item) {
			unset($save);

			$save["id"] = 0;
			$save["hash"] = get_hash_xaxis(0, "xaxis_item");
			$save["xaxis_id"] = $xaxis_id;
			reset($fields_xaxis_item_edit);
			while (list($field, $array) = each($fields_xaxis_item_edit)) {
				if (!preg_match("/^hidden/", $array["method"])) {
					$save[$field] = $xaxis_item[$field];
				}
			}

			sql_save($save, "graph_templates_xaxis_items");
		}
	}
}

/**
 * update the font cache via browser
 */
function repopulate_font_cache() {
require_once(CACTI_LIBRARY_PATH . "/functions.php");
require_once(CACTI_LIBRARY_PATH . "/fonts.php");


if (read_config_option("rrdtool_version") == "rrd-1.0.x" ||
	read_config_option("rrdtool_version") == "rrd-1.2.x") {

	# rrdtool 1.0 and 1.2 use font files
	$success = create_filebased_fontlist();

} else {

	# higher rrdtool versions use pango fonts
	$success = create_pango_fontlist();

}

}

function font_cache_filter() {
	global $item_rows;

	html_start_box("Font Cache Items", "100", "3", "center", "", true);
	?>
	<tr class='rowAlternate3'>
		<td>
			<form name="form_fontcache" action="utilities.php">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td class="nw50">
						<?php print "Search:";?>
					</td>
					<td class="w1">
						<input type="text" name="filter" size="30" value="<?php print html_get_page_variable("filter");?>">
					</td>
					<td class="nw50">
						<?php print "Rows:";?>
					</td>
					<td class="w1">
						<select name="rows" onChange="applyFontCacheFilterChange(document.form_fontcache)">
							<option value="-1"<?php if (html_get_page_variable("rows") == "-1") {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows) > 0) {
							foreach ($item_rows as $key => $value) {
								print "<option value='" . $key . "'"; if (html_get_page_variable("rows") == $key) { print " selected"; } print ">" . $value . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td class="nw120">
						<input type="submit" Value="<?php print "Go";?>" name="go" align="middle">
						<input type="button" Value="<?php print "Clear";?>" name="clear" align="middle" onClick="clearFontCacheFilterChange(document.form_userlog)">
					</td>
				</tr>
			</table>
			<div><input type='hidden' name='page' value='1'></div>
			<div><input type='hidden' name='action' value='view_font_cache'></div>
			<div><input type='hidden' name='page_referrer' value='view_font_cache'></div>
			</form>
		</td>
	</tr>
	<?php
	html_end_box(false);
	?>
	<script type="text/javascript">
	<!--

	function clearFontCacheFilterChange(objForm) {
		strURL = '?filter=';
		strURL = strURL + '&rows=-1';
		strURL = strURL + '&action=view_font_cache';
		strURL = strURL + '&page=1';
		document.location = strURL;
	}

	function applyFontCacheFilterChange(objForm) {
		strURL = '?filter=' + objForm.filter.value;
		strURL = strURL + '&rows=' + objForm.rows.value;
		strURL = strURL + '&action=view_font_cache';
		strURL = strURL + '&page=1';
		document.location = strURL;
	}

	-->
	</script>
	<?php
}

/**
 * fetch rows from font table
 */
function font_cache_get_records(&$total_rows, &$rowspp) {

	/* form the 'where' clause for our main sql query */
	$sql_where = "";

	$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . " font LIKE '%%" . html_get_page_variable("filter") . "%%'";

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_data_source");
	}else{
		$rowspp = html_get_page_variable("rows");
	}


	$total_rows = db_fetch_cell("SELECT
		COUNT(*)
		FROM fonts
		$sql_where");

	$font_sql = "SELECT *
		FROM fonts
		$sql_where
		ORDER BY " . html_get_page_variable("sort_column") . " " . html_get_page_variable("sort_direction") . "
		LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp;

	//	print $font_sql;

	return db_fetch_assoc($font_sql);
}


/**
 * view all fonts available in Cacti font cache and provide some filter options
 */
function utilities_view_font_cache($refresh=true) {
	global $item_rows, $colors;

	define("MAX_DISPLAY_PAGES", 21);

	$table = New html_table;

	$table->page_variables = array(
		"page"           => array("type" => "numeric", "method" => "request", "default" => "1"),
		"rows"           => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"filter"         => array("type" => "string",  "method" => "request", "default" => ""),
		"sort_column"    => array("type" => "string",  "method" => "request", "default" => "font"),
		"sort_direction" => array("type" => "string",  "method" => "request", "default" => "ASC")
	);


	$table->table_format = array(
		"font" => array(
			"name" => "Font Name",
			"filter" => true,
			"order" => "ASC"
		),
	);

	/* initialize page behavior */
	$table->key_field      = "id";
	$table->href           = "utilities.php?action=view_font_cache&page_referrer=view_font_cache";
	$table->session_prefix = "sess_font_cache";
	$table->filter_func    = "font_cache_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->sortable       = true;
	$table->table_id       = "font_cache";
#	$table->actions        = $font_cache_actions;

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = font_cache_get_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();

}

?>
