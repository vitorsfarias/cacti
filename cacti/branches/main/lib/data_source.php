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

/** data_source_remove - removea data source along with all related objects
 *
 * @param int $local_data_id - the id of the data source to be removed
 * @return unknown_type
 */
function data_source_remove($local_data_id) {
	if (empty($local_data_id)) {
		return;
	}

	$data_template_data_id = db_fetch_cell("select id from data_template_data where local_data_id=$local_data_id");

	if (!empty($data_template_data_id)) {
		db_execute("delete from data_template_data_rra where data_template_data_id=$data_template_data_id");
		db_execute("delete from data_input_data where data_template_data_id=$data_template_data_id");
	}

	db_execute("delete from data_template_data where local_data_id=$local_data_id");
	db_execute("delete from data_template_rrd where local_data_id=$local_data_id");
	db_execute("delete from poller_item where local_data_id=$local_data_id");
	db_execute("delete from data_local where id=$local_data_id");
}

/** data_source_remove_multi - remove multiple data sources along with all related objects
 *
 * @param unknown_type $local_data_ids - array of data sources to be removed
 * @return unknown_type
 */
function data_source_remove_multi($local_data_ids) {
	$ids_to_delete     = "";
	$dtd_ids_to_delete = "";
	$i = 0;
	$j = 0;

	if (sizeof($local_data_ids)) {
		foreach($local_data_ids as $local_data_id) {
			if ($i == 0) {
				$ids_to_delete .= $local_data_id;
			}else{
				$ids_to_delete .= ", " . $local_data_id;
			}

			$i++;

			if (($i % 1000) == 0) {
				$data_template_data_ids = db_fetch_assoc("SELECT id
					FROM data_template_data
					WHERE local_data_id IN ($ids_to_delete)");

				if (sizeof($data_template_data_ids)) {
					foreach($data_template_data_ids as $data_template_data_id) {
						if ($j == 0) {
							$dtd_ids_to_delete .= $data_template_data_id["id"];
						}else{
							$dtd_ids_to_delete .= ", " . $data_template_data_id["id"];
						}

						$j++;

						if ($j % 1000) {
							db_execute("DELETE FROM data_template_data_rra WHERE data_template_data_id IN ($dtd_ids_to_delete)");
							db_execute("DELETE FROM data_input_data WHERE data_template_data_id IN ($dtd_ids_to_delete)");

							$dtd_ids_to_delete = "";
							$j = 0;
						}
					}

					if ($j > 0) {
						db_execute("DELETE FROM data_template_data_rra WHERE data_template_data_id IN ($dtd_ids_to_delete)");
						db_execute("DELETE FROM data_input_data WHERE data_template_data_id IN ($dtd_ids_to_delete)");
					}
				}

				db_execute("DELETE FROM data_template_data WHERE local_data_id IN ($ids_to_delete)");
				db_execute("DELETE FROM data_template_rrd WHERE local_data_id IN ($ids_to_delete)");
				db_execute("DELETE FROM poller_item WHERE local_data_id IN ($ids_to_delete)");
				db_execute("DELETE FROM data_local WHERE id IN ($ids_to_delete)");

				$i = 0;
				$ids_to_delete = "";
			}
		}
	}

	if ($i > 0) {
		db_execute("DELETE FROM data_template_data WHERE local_data_id IN ($ids_to_delete)");
		db_execute("DELETE FROM data_template_rrd WHERE local_data_id IN ($ids_to_delete)");
		db_execute("DELETE FROM poller_item WHERE local_data_id IN ($ids_to_delete)");
		db_execute("DELETE FROM data_local WHERE id IN ($ids_to_delete)");
	}
}

/** data_source_enable - enable a data source
 *
 * @param int $local_data_id - the id of the data source to be enabled
 * @return unknown_type
 */
function data_source_enable($local_data_id) {
	db_execute("UPDATE data_template_data SET active='on' WHERE local_data_id=$local_data_id");
	update_poller_cache($local_data_id, true);
 }

 /** data_source_disable - disable a data source
  *
  * @param int $local_data_id - the id of the data source to be disabled
  * @return unknown_type
  */
function data_source_disable($local_data_id) {
	db_execute("DELETE FROM poller_item WHERE local_data_id=$local_data_id");
	db_execute("UPDATE data_template_data SET active='' WHERE local_data_id=$local_data_id");
}

/** data_source_disable_multi - disable multiple data sources
 *
 * @param array $local_data_ids - array of ids of the data sources to be disabled
 * @return unknown_type
 */
function data_source_disable_multi($local_data_ids) {
	/* initialize variables */
	$ids_to_disable = "";
	$i = 0;

	/* build the array */
	if (sizeof($local_data_ids)) {
		foreach($local_data_ids as $local_data_id) {
			if ($i == 0) {
				$ids_to_disable .= $local_data_id;
			}else{
				$ids_to_disable .= ", " . $local_data_id;
			}

			$i++;

			if (!($i % 1000)) {
				db_execute("DELETE FROM poller_item WHERE local_data_id IN ($ids_to_disable)");
				db_execute("UPDATE data_template_data SET active='' WHERE local_data_id IN ($ids_to_disable)");

				$i = 0;
				$ids_to_disable = "";
			}
		}

		if ($i > 0) {
			db_execute("DELETE FROM poller_item WHERE local_data_id IN ($ids_to_disable)");
			db_execute("UPDATE data_template_data SET active='' WHERE local_data_id IN ($ids_to_disable)");
		}
	}
}

/** reapply_suggested_data_source_title - reapply the suggested title to a data source
 *
 * @param int $local_data_id - the id of the data source to be treated
 * @return unknown_type
 */
function reapply_suggested_data_source_title($local_data_id) {
	$data_template_data_id = db_fetch_cell("select id from data_template_data where local_data_id=$local_data_id");
	if (empty($data_template_data_id)) {
		return;
	}

	/* require query type data sources only (snmp_query_id > 0) */
	$data_local = db_fetch_row("SELECT id, device_id, data_template_id, snmp_query_id, snmp_index FROM data_local WHERE snmp_query_id>0 AND id=$local_data_id");
	/* if this is not a data query graph, simply return */
	if (!isset($data_local["device_id"])) {
		return;
	}

	$snmp_query_graph_id = db_fetch_cell("SELECT " .
		"data_input_data.value from data_input_data " .
		"JOIN data_input_fields ON (data_input_data.data_input_field_id=data_input_fields.id) " .
		"JOIN data_template_data ON (data_template_data.id = data_input_data.data_template_data_id) ".
		"WHERE data_input_fields.type_code = 'output_type' " .
		"AND data_template_data.local_data_id=" . $data_local["id"] );

	/* no snmp query graph id found */
	if ($snmp_query_graph_id == 0) {
		return;
	}

	$suggested_values = db_fetch_assoc("SELECT " .
		"text, " .
		"field_name " .
		"FROM snmp_query_graph_rrd_sv " .
		"WHERE snmp_query_graph_id=" . $snmp_query_graph_id . " " . 
		"AND data_template_id=" . $data_local["data_template_id"] . " " .
		"AND field_name = 'name' " .
		"ORDER BY sequence");

	$suggested_values_data = array();
	if (sizeof($suggested_values) > 0) {
		foreach ($suggested_values as $suggested_value) {
			if(!isset($suggested_values_data{$suggested_value["field_name"]})) {
 				$subs_string = substitute_snmp_query_data($suggested_value["text"],$data_local["device_id"],
								$data_local["snmp_query_id"], $data_local["snmp_index"],
								read_config_option("max_data_query_field_length"));
				/* if there are no '|query' characters, all of the substitutions were successful */
				if (!substr_count($subs_string, "|query")) {
					db_execute("UPDATE data_template_data SET " . $suggested_value["field_name"] . "='" . $suggested_value["text"] . "' WHERE local_data_id=" . $local_data_id);
					/* once we find a working value for that very field, stop */
					$suggested_values_data{$suggested_value["field_name"]} = true;
				}
			}
		}
	}
}

function data_source_form_list() {
	require(CACTI_INCLUDE_PATH . "/data_source/data_source_forms.php");

	return $struct_data_source;
}

function data_source_item_form_list() {
	require(CACTI_INCLUDE_PATH . "/data_source/data_source_forms.php");

	return $struct_data_source_item;
}

function fields_data_source_form_list() {
	require(CACTI_INCLUDE_PATH . "/data_source/data_source_forms.php");

	return $fields_data_source;
}

/* --------------------------
    The Save Function
   -------------------------- */

function data_source_form_save() {
	if ((isset($_POST["save_component_data_source_new"])) && (!empty($_POST["data_template_id"]))) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("device_id"));
		input_validate_input_number(get_request_var_post("data_template_id"));
		/* ==================================================== */

		$save["id"] = $_POST["local_data_id"];
		$save["data_template_id"] = $_POST["data_template_id"];
		$save["device_id"] = $_POST["device_id"];

		$local_data_id = sql_save($save, "data_local");

		change_data_template($local_data_id, get_request_var_post("data_template_id"));

		/* update the title cache */
		update_data_source_title_cache($local_data_id);

		/* update device data */
		if (!empty($_POST["device_id"])) {
			push_out_device(get_request_var_post("device_id"), $local_data_id);
		}
	}

	if ((isset($_POST["save_component_data"])) && (!is_error_message())) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("data_template_data_id"));
		/* ==================================================== */

		/* ok, first pull out all 'input' values so we know how much to save */
		$input_fields = db_fetch_assoc("select
			data_template_data.data_input_id,
			data_local.device_id,
			data_input_fields.id,
			data_input_fields.input_output,
			data_input_fields.data_name,
			data_input_fields.regexp_match,
			data_input_fields.allow_nulls,
			data_input_fields.type_code
			from data_template_data
			left join data_input_fields on (data_input_fields.data_input_id=data_template_data.data_input_id)
			left join data_local on (data_template_data.local_data_id=data_local.id)
			where data_template_data.id=" . $_POST["data_template_data_id"] . "
			and data_input_fields.input_output='in'");

		if (sizeof($input_fields) > 0) {
		foreach ($input_fields as $input_field) {
			if (isset($_POST{"value_" . $input_field["id"]})) {
				/* save the data into the 'data_input_data' table */
				$form_value = $_POST{"value_" . $input_field["id"]};

				/* we shouldn't enforce rules on fields the user cannot see (ie. templated ones) */
				$is_templated = db_fetch_cell("select t_value from data_input_data where data_input_field_id=" . $input_field["id"] . " and data_template_data_id=" . db_fetch_cell("select local_data_template_data_id from data_template_data where id=" . $_POST["data_template_data_id"]));

				if ($is_templated == "") {
					$allow_nulls = true;
				}elseif ($input_field["allow_nulls"] == CHECKED) {
					$allow_nulls = true;
				}elseif (empty($input_field["allow_nulls"])) {
					$allow_nulls = false;
				}

				/* run regexp match on input string */
				$form_value = form_input_validate($form_value, "value_" . $input_field["id"], $input_field["regexp_match"], $allow_nulls, 3);

				if (!is_error_message()) {
					db_execute("replace into data_input_data (data_input_field_id,data_template_data_id,t_value,value) values
						(" . $input_field["id"] . "," . get_request_var_post("data_template_data_id") . ",'','$form_value')");
				}
			}
		}
		}
	}

	if ((isset($_POST["save_component_data_source"])) && (!is_error_message())) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("local_data_id"));
		input_validate_input_number(get_request_var_post("current_rrd"));
		input_validate_input_number(get_request_var_post("data_template_id"));
		input_validate_input_number(get_request_var_post("device_id"));
		/* ==================================================== */

		$save1["id"] = $_POST["local_data_id"];
		$save1["data_template_id"] = $_POST["data_template_id"];
		$save1["device_id"] = $_POST["device_id"];

		$save2["id"] = $_POST["data_template_data_id"];
		$save2["local_data_template_data_id"] = $_POST["local_data_template_data_id"];
		$save2["data_template_id"] = $_POST["data_template_id"];
		$save2["data_input_id"] = form_input_validate($_POST["data_input_id"], "data_input_id", "", true, 3);
		$save2["name"] = form_input_validate($_POST["name"], "name", "", false, 3);
		$save2["data_source_path"] = form_input_validate($_POST["data_source_path"], "data_source_path", "", true, 3);
		$save2["active"] = form_input_validate((isset($_POST["active"]) ? $_POST["active"] : ""), "active", "", true, 3);
		$save2["rrd_step"] = form_input_validate($_POST["rrd_step"], "rrd_step", "^[0-9]+$", false, 3);

		if (!is_error_message()) {
			$local_data_id = sql_save($save1, "data_local");

			$save2["local_data_id"] = $local_data_id;
			$data_template_data_id = sql_save($save2, "data_template_data");

			if ($data_template_data_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

#		if (!is_error_message()) {
#			/* if this is a new data source and a template has been selected, skip item creation this time
#			otherwise it throws off the templatate creation because of the NULL data */
#			if ((!empty($_POST["local_data_id"])) || (empty($_POST["data_template_id"]))) {
#				/* if no template was set before the save, there will be only one data source item to save;
#				otherwise there might be >1 */
#				if (empty($_POST["hidden_data_template_id"])) {
#					$rrds[0]["id"] = $_POST["current_rrd"];
#				}else{
#					$rrds = db_fetch_assoc("select id from data_template_rrd where local_data_id=" . $_POST["local_data_id"]);
#				}
#
#				if (sizeof($rrds) > 0) {
#				foreach ($rrds as $rrd) {
#					if (empty($_POST["hidden_data_template_id"])) {
#						$name_modifier = "";
#					}else{
#						$name_modifier = "_" . $rrd["id"];
#					}
#
#					$save3["id"] = $rrd["id"];
#					$save3["local_data_id"] = $local_data_id;
#					$save3["local_data_template_rrd_id"] = db_fetch_cell("select local_data_template_rrd_id from data_template_rrd where id=" . $rrd["id"]);
#					$save3["data_template_id"] = $_POST["data_template_id"];
#					$save3["rrd_maximum"] = form_input_validate($_POST["rrd_maximum$name_modifier"], "rrd_maximum$name_modifier", "^(-?([0-9]+(\.[0-9]*)?|[0-9]*\.[0-9]+)([eE][+\-]?[0-9]+)?)|U$", false, 3);
#					$save3["rrd_minimum"] = form_input_validate($_POST["rrd_minimum$name_modifier"], "rrd_minimum$name_modifier", "^(-?([0-9]+(\.[0-9]*)?|[0-9]*\.[0-9]+)([eE][+\-]?[0-9]+)?)|U$", false, 3);
#					$save3["rrd_heartbeat"] = form_input_validate($_POST["rrd_heartbeat$name_modifier"], "rrd_heartbeat$name_modifier", "^[0-9]+$", false, 3);
#					$save3["data_source_type_id"] = $_POST["data_source_type_id$name_modifier"];
#					$save3["data_source_name"] = form_input_validate($_POST["data_source_name$name_modifier"], "data_source_name$name_modifier", "^[a-zA-Z0-9_-]{1,19}$", false, 3);
#					$save3["data_input_field_id"] = form_input_validate((isset($_POST["data_input_field_id$name_modifier"]) ? $_POST["data_input_field_id$name_modifier"] : "0"), "data_input_field_id$name_modifier", "", true, 3);
#
#					$data_template_rrd_id = sql_save($save3, "data_template_rrd");
#
#					if ($data_template_rrd_id) {
#						raise_message(1);
#					}else{
#						raise_message(2);
#					}
#				}
#				}
#			}
#		}

		if (!is_error_message()) {
			if (!empty($_POST["rra_id"])) {
				/* save entries in 'selected rras' field */
				db_execute("delete from data_template_data_rra where data_template_data_id=$data_template_data_id");

				for ($i=0; ($i < count($_POST["rra_id"])); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($_POST["rra_id"][$i]);
					/* ==================================================== */

					db_execute("insert into data_template_data_rra (rra_id,data_template_data_id)
						values (" . $_POST["rra_id"][$i] . ",$data_template_data_id)");
				}
			}

			if ($_POST["data_template_id"] != $_POST["hidden_data_template_id"]) {
				/* update all necessary template information */
				change_data_template($local_data_id, get_request_var_post("data_template_id"));
			}elseif (!empty($_POST["data_template_id"])) {
				update_data_source_data_query_cache($local_data_id);
			}

			if ($_POST["device_id"] != $_POST["hidden_device_id"]) {
				/* push out all necessary device information */
				push_out_device(get_request_var_post("device_id"), $local_data_id);

				/* reset current device for display purposes */
				$_SESSION["sess_data_source_currenthidden_device_id"] = $_POST["device_id"];
			}

			/* if no data source path has been entered, generate one */
			if (empty($_POST["data_source_path"])) {
				generate_data_source_path($local_data_id);
			}

			/* update the title cache */
			update_data_source_title_cache($local_data_id);
		}
	}

	/* update the poller cache last to make sure everything is fresh */
	if ((!is_error_message()) && (!empty($local_data_id))) {
		update_poller_cache($local_data_id, true);
	}

	if ((isset($_POST["save_component_data_source_new"])) && (empty($_POST["data_template_id"]))) {
		header("Location: " . html_get_location("data_sources.php") . "?action=edit&device_id=" . $_POST["device_id"] . "&new=1");
	}elseif ((is_error_message()) || ($_POST["data_template_id"] != $_POST["hidden_data_template_id"]) || ($_POST["data_input_id"] != $_POST["hidden_data_input_id"]) || ($_POST["device_id"] != $_POST["hidden_device_id"])) {
		header("Location: " . html_get_location("data_sources.php") . "?action=edit&id=" . (empty($local_data_id) ? $_POST["local_data_id"] : $local_data_id) . "&device_id=" . $_POST["device_id"] . "&view_rrd=" . (isset($_POST["current_rrd"]) ? $_POST["current_rrd"] : "0"));
	}else{
		header("Location: " . html_get_location("data_sources.php"));
	}

	exit;
}

/* ------------------------
    The "actions" function
   ------------------------ */

function data_source_form_actions() {
	require(CACTI_INCLUDE_PATH . "/data_source/data_source_arrays.php");

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === DS_ACTION_DELETE) { /* delete */
			if (!isset($_POST["delete_type"])) { $_POST["delete_type"] = 1; }

			switch (get_request_var_post("delete_type")) {
				case '2': /* delete all graph items tied to this data source */
					$data_template_rrds = array_rekey(db_fetch_assoc("select id from data_template_rrd where " . array_to_sql_or($selected_items, "local_data_id")), "id", "id");

					/* loop through each data source item */
					if (sizeof($data_template_rrds) > 0) {
						db_execute("delete from graph_templates_item where task_item_id IN (" . implode(",", $data_template_rrds) . ") and local_graph_id > 0");
					}

					break;
				case '3': /* delete all graphs tied to this data source */
					$graphs = array_rekey(db_fetch_assoc("select
						graph_templates_graph.local_graph_id
						from (data_template_rrd,graph_templates_item,graph_templates_graph)
						where graph_templates_item.task_item_id=data_template_rrd.id
						and graph_templates_item.local_graph_id=graph_templates_graph.local_graph_id
						and " . array_to_sql_or($selected_items, "data_template_rrd.local_data_id") . "
						and graph_templates_graph.local_graph_id > 0
						group by graph_templates_graph.local_graph_id"), "local_graph_id", "local_graph_id");

					if (sizeof($graphs) > 0) {
						graph_remove_multi($graphs);
					}

					break;
			}

			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */
			}

			data_source_remove_multi($selected_items);
		}elseif (get_request_var_post("drp_action") === DS_ACTION_CHANGE_TEMPLATE) { /* change graph template */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				input_validate_input_number(get_request_var_post("data_template_id"));
				/* ==================================================== */

				change_data_template($selected_items[$i], get_request_var_post("data_template_id"));
			}
		}elseif (get_request_var_post("drp_action") === DS_ACTION_CHANGE_HOST) { /* change device */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				input_validate_input_number(get_request_var_post("device_id"));
				/* ==================================================== */

				db_execute("update data_local set device_id=" . $_POST["device_id"] . " where id=" . $selected_items[$i]);
				push_out_device(get_request_var_post("device_id"), $selected_items[$i]);
				update_data_source_title_cache($selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === DS_ACTION_DUPLICATE) { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				duplicate_data_source($selected_items[$i], 0, get_request_var_post("title_format"));
			}
		}elseif (get_request_var_post("drp_action") === DS_ACTION_CONVERT_TO_TEMPLATE) { /* data source -> data template */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				data_source_to_data_template($selected_items[$i], get_request_var_post("title_format"));
			}
		}elseif (get_request_var_post("drp_action") === DS_ACTION_ENABLE) { /* data source enable */
			for ($i=0;($i<count($selected_items));$i++) {
				data_source_enable($selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === DS_ACTION_DISABLE) { /* data source disable */
			for ($i=0;($i<count($selected_items));$i++) {
				data_source_disable($selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === DS_ACTION_REAPPLY_SUGGESTED_NAMES) { /* reapply suggested data source naming */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */
				reapply_suggested_data_source_title($selected_items[$i]);
				update_data_source_title_cache($selected_items[$i]);
			}
		}

		exit;
	}

	/* setup some variables */
	$ds_list = ""; $ds_array = array();

	/* loop through each of the graphs selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$ds_list .= "<li>" . get_data_source_title($matches[1]) . "</li>";
			$ds_array[] = $matches[1];
		}
	}

	$ds_actions[ACTION_NONE] = __("None");

	print "<form id='ds_actions' name='ds_actions' action='data_sources.php' method='post'>\n";

	html_start_box("", "100", "3", "center", "");

	if (sizeof($ds_array)) {
		if (get_request_var_post("drp_action") === ACTION_NONE) { /* NONE */
			print "	<tr>
						<td class='textArea'>
							<p>" . __("You did not select a valid action. Please select 'Return' to return to the previous menu.") . "</p>
						</td>
					</tr>\n";

			$title = __("Selection Error");
		}elseif (get_request_var_post("drp_action") === DS_ACTION_DELETE) { /* delete */
			$graphs = array();

			/* find out which (if any) graphs are using this data source, so we can tell the user */
			if (isset($ds_array)) {
				$graphs = db_fetch_assoc("select
					graph_templates_graph.local_graph_id,
					graph_templates_graph.title_cache
					from (data_template_rrd,graph_templates_item,graph_templates_graph)
					where graph_templates_item.task_item_id=data_template_rrd.id
					and graph_templates_item.local_graph_id=graph_templates_graph.local_graph_id
					and " . array_to_sql_or($ds_array, "data_template_rrd.local_data_id") . "
					and graph_templates_graph.local_graph_id > 0
					group by graph_templates_graph.local_graph_id
					order by graph_templates_graph.title_cache");
			}

			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Data Source(s) will be deleted.") . "</p>
						<ul>$ds_list</ul>";
						if (sizeof($graphs) > 0) {
							form_alternate_row_color();

							print "<td class='textArea'><p class='textArea'>" . __("The following Graph(s) are using these Data Source(s):") . "</p>\n";

							print "<div class='action_list'><ul>";
							foreach ($graphs as $graph) {
								print "<li>" . $graph["title_cache"] . "</li>\n";
							}
							print "</ul></div>";

							form_radio_button("delete_type", "3", "1", __("Leave the Graph(s) untouched."), "1"); print "<br>";
							form_radio_button("delete_type", "3", "2", __("Delete all <strong>Graph Item(s)</strong> that reference these Data Source(s)."), "1"); print "<br>";
							form_radio_button("delete_type", "3", "3", __("Delete all <strong>Graph(s)</strong> that reference these Data Source(s)."), "1"); print "<br>";
							print "</td></tr>";
						}
					print "
					</td>
				</tr>\n";

			$title = __("Delete Data Source(s)");
		}elseif (get_request_var_post("drp_action") === DS_ACTION_CHANGE_TEMPLATE) { /* change graph template */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Data Source(s) will be re-associated with the choosen Graph Template. Be aware that all warnings will be suppressed during the conversion, so Graph data loss is possible.") . "</p>
						<div class='action_list'><ul>$ds_list</ul></div>
						<p><strong>". __("New Data Source Template:") . "</strong><br>"; form_dropdown("data_template_id",db_fetch_assoc("select data_template.id,data_template.name from data_template order by data_template.name"),"name","id","","","0"); print "</p>
					</td>
				</tr>\n";

			$title = __("Change Data Source(s) Graph Template");
		}elseif (get_request_var_post("drp_action") === DS_ACTION_CHANGE_HOST) { /* change device */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Data Source(s) will be re-associated with the Device below.") . "</p>
						<div class='action_list'><ul>$ds_list</ul></div>
						<p><strong>" . __("New Device:") . "</strong><br>"; form_dropdown("device_id",db_fetch_assoc("select id,CONCAT_WS('',description,' (',hostname,')') as name from device order by description,hostname"),"name","id","","","0"); print "</p>
					</td>
				</tr>\n";

			$title = __("Change Data Source(s) Device");
		}elseif (get_request_var_post("drp_action") === DS_ACTION_DUPLICATE) { /* duplicate */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Data Source(s) will be duplicated. You can optionally change the title format for the new Data Source(s).") . "</p>
						<div class='action_list'><ul>$ds_list</ul></div>
						<p><strong>" . __("Title Format:") . "</strong><br>"; form_text_box("title_format", "<ds_title> (1)", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n";

			$title = __("Duplicate Data Source(s)");
		}elseif (get_request_var_post("drp_action") === DS_ACTION_CONVERT_TO_TEMPLATE) { /* data source -> data template */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue' the following Data Source(s) will be converted into Data Template(s).  You can optionally change the title format for the new Data Template(s).") . "</p>
						<div class='action_list'><ul>$ds_list</ul></div>
						<p><strong>" . __("Title Format:") . "</strong><br>"; form_text_box("title_format", "<ds_title> Template", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n";

			$title = __("Convert Data Soruce(s) to Data Template(s)");
		}elseif (get_request_var_post("drp_action") === DS_ACTION_ENABLE) { /* data source enable */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Data Source(s) will be enabled.") . "</p>
						<div class='action_list'><ul>$ds_list</ul></div>
					</td>
				</tr>\n";

			$title = __("Enable Data Source(s)");
		}elseif (get_request_var_post("drp_action") === DS_ACTION_DISABLE) { /* data source disable */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Data Source(s) will be disabled.") . "</p>
						<div class='action_list'><ul>$ds_list</ul></div>
					</td>
				</tr>\n";


			$title = __("Disable Data Source(s)");
		}elseif (get_request_var_post("drp_action") === DS_ACTION_REAPPLY_SUGGESTED_NAMES) { /* reapply suggested data source naming */
			print "	<tr>
					<td class='topBoxAlt'>
						<p>" . __("When you click 'Continue', the following Data Source(s) will will have their suggested naming conventions recalculated.") . "</p>
						<div class='action_list'><ul>$ds_list</ul></div>
					</td>
				</tr>\n";

			$title = __("Reapply Suggested Names");
		} else {
			$save['drp_action'] = $_POST['drp_action'];
			$save['ds_list'] = $ds_list;
			$save['ds_array'] = (isset($ds_array)? $ds_array : array());
			$save['title'] = '';
			plugin_hook_function('data_source_action_prepare', $save);

			if (strlen($save['title'])) {
				$title = $save['title'];
			}else{
				$title = '';
			}
		}
	} else {
		print "	<tr>
				<td class='textArea'>
					<p>" . __("You must first select a Data Source.  Please select 'Return' to return to the previous menu.") . "</p>
				</td>
			</tr>\n";

		$title = __("Selection Error");
	}

	if (isset($_POST['tab'])) {
		form_hidden_box('tab', get_request_var_post('tab'), '');
		form_hidden_box('id',  get_request_var_post('id'), '');
	}

	if (!sizeof($ds_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button($title);
	}else{
		form_continue(serialize($ds_array), get_request_var_post("drp_action"), $title, "ds_actions");
	}

	html_end_box();
}

/* ----------------------------
    data - Custom Data
   ---------------------------- */

function data_source_toggle_status() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (get_request_var("newstate") == 1) {
		data_source_enable(get_request_var("id"));
	}else{
		cacti_log("Disabling Bad DS");
		data_source_disable(get_request_var("id"));
	}

	header("Location: " . $_SERVER["HTTP_REFERER"]);
	exit;
}

function data_source_data_edit() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$data = db_fetch_row("select id,data_input_id,data_template_id,name,local_data_id from data_template_data where local_data_id=" . $_GET["id"]);
		$template_data = db_fetch_row("select id,data_input_id from data_template_data where data_template_id=" . $data["data_template_id"] . " and local_data_id=0");

		$device = db_fetch_row("select device.id,device.hostname from (data_local,device) where data_local.device_id=device.id and data_local.id=" . $_GET["id"]);

		$header_label = __("[edit: ") . $data["name"] . "]";
	}else{
		$header_label = __("[new]");
	}

	print "<form action='data_sources.php' method='post'>\n";

	$i = 0;
	if (!empty($data["data_input_id"])) {
		/* get each INPUT field for this data input source */
		$fields = db_fetch_assoc("select * from data_input_fields where data_input_id=" . $data["data_input_id"] . " and input_output='in' order by sequence");

		html_start_box(__("Custom Data") . " " . __("[data input:") . " " . db_fetch_cell("select name from data_input where id=" . $data["data_input_id"]) . "]", "100", "3", "center", "");

		/* loop through each field found */
		if (sizeof($fields) > 0) {
			foreach ($fields as $field) {
				$data_input_data = db_fetch_row("select * from data_input_data where data_template_data_id=" . $data["id"] . " and data_input_field_id=" . $field["id"]);

				if (sizeof($data_input_data) > 0) {
					$old_value = $data_input_data["value"];
				}else{
					$old_value = "";
				}

				/* if data template then get t_value from template, else always allow user input */
				if (empty($data["data_template_id"])) {
					$can_template = CHECKED;
				}else{
					$can_template = db_fetch_cell("select t_value from data_input_data where data_template_data_id=" . $template_data["id"] . " and data_input_field_id=" . $field["id"]);
				}

				form_alternate_row_color();

				if ((!empty($device["id"])) && (preg_match('/^' . VALID_HOST_FIELDS . '$/i', $field["type_code"]))) {
					print "<td width='50%'><strong>" . $field["name"] . "</strong> (" . __("From Host:") . " " . $device["hostname"] . ")</td>\n";
					print "<td><em>$old_value</em></td>\n";
				}elseif (empty($can_template)) {
					print "<td width='50%'><strong>" . $field["name"] . "</strong> (" . __("From Data Source Template") . ")</td>\n";
					print "<td><em>" . (empty($old_value) ? __("Nothing Entered") : $old_value) . "</em></td>\n";
				}else{
					print "<td width='50%'><strong>" . $field["name"] . "</strong></td>\n";
					print "<td>";

					draw_custom_data_row("value_" . $field["id"], $field["id"], $data["id"], $old_value);

					print "</td>";
				}

				print "</tr>\n";
			}
		}else{
			print "<tr><td><em>" . __("No Input Fields for the Selected Data Input Source") . "</em></td></tr>";
		}

		html_end_box();
	}

	form_hidden_box("local_data_id", (isset($data) ? $data["local_data_id"] : "0"), "");
	form_hidden_box("data_template_data_id", (isset($data) ? $data["id"] : "0"), "");
	form_hidden_box("save_component_data", "1", "");
}

/* ------------------------
    Data Source Functions
   ------------------------ */

function data_source_rrd_remove() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	db_execute("delete from data_template_rrd where id=" . $_GET["id"]);
	db_execute("update graph_templates_item set task_item_id=0 where task_item_id=" . $_GET["id"]);

	header("Location: " . html_get_location("data_sources.php") . "action=edit&id=" . $_GET["local_data_id"]);
	exit;
}

function data_source_rrd_add() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	db_execute("insert into data_template_rrd (local_data_id,rrd_maximum,rrd_minimum,rrd_heartbeat,data_source_type_id,
		data_source_name) values (" . get_request_var("id") . ",100,0,600,1,'ds')");
	$data_template_rrd_id = db_fetch_insert_id();

	header("Location: " . html_get_location("data_sources.php") . "action=edit&id=" . $_GET["id"] . "&view_rrd=$data_template_rrd_id");
	exit;
}

function data_source_edit() {
	require_once(CACTI_LIBRARY_PATH . "/data_source.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	$use_data_template = true;
	$device_id = 0;

	if (!empty($_GET["id"])) {
		$data_local 		= db_fetch_row("select device_id,data_template_id from data_local where id='" . $_GET["id"] . "'");
		$data       		= db_fetch_row("select * from data_template_data where local_data_id='" . $_GET["id"] . "'");
		$data_source_items 	= db_fetch_assoc("select * from data_template_rrd where local_data_id=" . $_GET["id"] . " order by data_source_name");

		if (isset($data_local["data_template_id"]) && $data_local["data_template_id"] >= 0) {
			$data_template      = db_fetch_row("select id,name from data_template where id='" . $data_local["data_template_id"] . "'");
			$data_template_data = db_fetch_row("select * from data_template_data where data_template_id='" . $data_local["data_template_id"] . "' and local_data_id=0");
		} else {
			$_SESSION["sess_messages"] = 'Data Source "' . $_GET["id"] . '" does not exist.';
			header ("Location: " . html_get_location("data_sources.php"));
			exit;
		}

		$header_label = __("[edit: ") . get_data_source_title($_GET["id"]) . "]";

		if (empty($data_local["data_template_id"])) {
			$use_data_template = false;
		}

	}else{
		$header_label = __("[new]");

		$use_data_template = false;
	}

	/* handle debug mode */
	if (isset($_GET["debug"])) {
		if (get_request_var("debug") == "0") {
			kill_session_var("ds_debug_mode");
		}elseif (get_request_var("debug") == "1") {
			$_SESSION["ds_debug_mode"] = true;
		}
	}

	/* handle info mode */
	if (isset($_GET["info"])) {
		if (get_request_var("info") == "0") {
			kill_session_var("ds_info_mode");
		}elseif (get_request_var("info") == "1") {
			$_SESSION["ds_info_mode"] = true;
		}
	}

	include_once(CACTI_INCLUDE_PATH . "/top_header.php");

	if (!empty($_GET["id"])) {
		?>
		<script type="text/javascript">
		<!--
		var disabled = true;

		$().ready(function() {
			if ($("#hidden_data_template_id").val() == 0) {
				unlockTemplate();
				$("#data_source_options").closest("td").before("<td class='lock w1 textHeaderDark'><?php print __("Template is unlocked");?></td>");
				disabled = false;
			}else{
				lockTemplate();
				$("#data_source_options").closest("td").before("<td class='lock w1 textHeaderDark'><?php print __("Template is locked");?></td>");
				disabled = true;
			}
		});

		function unlockTemplate() {
				$("input").removeAttr("disabled");
				$("select").removeAttr("disabled");
				$("#cancel").removeAttr("disabled");
				$("#save").removeAttr("disabled");
		}
		
		function lockTemplate() {
				$("input").attr("disabled","disabled")
				$("select").attr("disabled","disabled")
				$("#save").attr("disabled", "disabled");
				$("#cancel").removeAttr("disabled");
		}

		function changeDSState() {
			if (disabled) {
				unlockTemplate();
				$(".lock").html("<?php print __("Template is unlocked");?>");
				disabled = false;
			}else{
				lockTemplate();
				$(".lock").html("<?php print __("Template is locked");?>");
				disabled = true;
			}
		}
		-->
		</script>
		<?php
	}

	$dd_menu_options = "";
	if (isset($data)) {
		$dd_menu_options = 'cacti_dd_menu=data_source_options&data_source_id=' . (isset($_GET["id"]) ? get_request_var("id") : 0);
		$dd_menu_options .= '&newstate=' . (($data["active"] == CHECKED) ? "0" : "1");
	}
	if (!empty($data_template["id"])) {
		$dd_menu_options .= '&data_template_id=' . (isset($data_template["id"]) ? $data_template["id"] : "0");
	}
	if (!empty($_GET["device_id"]) || !empty($data_local["device_id"])) {
		$dd_menu_options .= '&device_id=' . (isset($_GET["device_id"]) ? $_GET["device_id"] : $data_local["device_id"]);
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='data_source_edit'>\n";
	html_start_box(__("Data Source Template Selection") . " $header_label", "100", 0, "center", (isset($_GET["id"]) ? "menu::" . __("Data Source Options") . ":data_source_options:html_start_box:" . $dd_menu_options : ""),"");

	$form_array = fields_data_source_form_list();
	$form_array["data_template_id"]["id"] = (isset($data_template["id"]) ? $data_template["id"] : "0");
	$form_array["data_template_id"]["value"] = db_fetch_cell("SELECT name FROM data_template WHERE id=" . $form_array["data_template_id"]["id"]);
	$form_array["device_id"]["id"] = (isset($_GET["device_id"]) ? $_GET["device_id"] : $data_local["device_id"]);
	$form_array["device_id"]["value"] = db_fetch_cell("SELECT CONCAT_WS('',description,' (',hostname,')') FROM device WHERE id=" . $form_array["device_id"]["id"]);

	draw_edit_form(
		array(
			"config" => array("no_form_tag" => true),
			"fields" => $form_array
			)
		);

	html_end_box();
	form_hidden_box("hidden_data_template_id", (isset($data_template["id"]) ? $data_template["id"] : "0"), "");
	form_hidden_box("hidden_device_id", (empty($data_local["device_id"]) ? (isset($_GET["device_id"]) ? $_GET["device_id"] : "0") : $data_local["device_id"]), "");
	form_hidden_box("hidden_data_input_id", (isset($data["data_input_id"]) ? $data["data_input_id"] : "0"), "");
	form_hidden_box("data_template_data_id", (isset($data) ? $data["id"] : "0"), "");
	form_hidden_box("local_data_template_data_id", (isset($data) ? $data["local_data_template_data_id"] : "0"), "");
	form_hidden_box("local_data_id", (isset($data) ? $data["local_data_id"] : "0"), "");

	/* only display the "inputs" area if we are using a data template for this data source */
	if (!empty($data["data_template_id"])) {

		html_start_box(__("Supplemental Data Source Template Data"), "100", 0, "center", "");

		draw_nontemplated_fields_data_source($data["data_template_id"], $data["local_data_id"], $data, "|field|", "<strong>" . __("Data Source Fields") . "</strong>", true, 0);
		draw_nontemplated_fields_data_source_item($data["data_template_id"], $data_source_items, "|field|_|id|", "<strong>" . __("Data Source Item Fields") . "</strong>", true, true, 0);
		draw_nontemplated_fields_custom_data($data["id"], "value_|id|", "<strong>" . __("Custom Data") . "</strong>", true, 0);

		html_end_box();

		form_hidden_box("save_component_data","1","");
	}

	if (((isset($_GET["id"])) || (isset($_GET["new"]))) && (empty($data["data_template_id"]))) {
		html_start_box(__("Data Source"), "100", "3", "center", "");

		$form_array = array();

		$struct_data_source = data_source_form_list();
		while (list($field_name, $field_array) = each($struct_data_source)) {
			$form_array += array($field_name => $struct_data_source[$field_name]);

			$form_array[$field_name]["value"] = (isset($data[$field_name]) ? $data[$field_name] : "");
			$form_array[$field_name]["form_id"] = (empty($data["id"]) ? "0" : $data["id"]);

			if (!(($use_data_template == false) || (!empty($data_template_data{"t_" . $field_name})) || ($field_array["flags"] == "NOTEMPLATE"))) {
				$form_array[$field_name]["method"] = "template_" . $form_array[$field_name]["method"];
			}
		}

		draw_edit_form(
			array(
				"config" => array("no_form_tag" => true),
				"fields" => inject_form_variables($form_array, (isset($data) ? $data : array()))
				)
			);

		html_end_box();


		if (!empty($_GET["id"])) {

			html_start_box(__("Data Source Items"), "100", "0", "center", "data_sources_items.php?action=item_edit&local_data_id=" . $_GET["id"], true);
			draw_data_template_items_list($data_source_items, "data_sources_items.php", "local_data_id=" . $_GET["id"], $use_data_template);
			html_end_box(false);
		}

		/* data source data goes here */
		data_source_data_edit();
	}

	/* display the debug mode box if the user wants it */
	if ((isset($_SESSION["ds_debug_mode"])) && (isset($_GET["id"]))) {
		?>
		<table width="100%" align="center">
			<tr>
				<td>
					<span class="textInfo"><?php print __("Data Source Debug");?></span><br>
					<pre><?php print rrdtool_function_create(get_request_var("id"), true);?></pre>
				</td>
			</tr>
		</table>
		<?php
	}

	if ((isset($_SESSION["ds_info_mode"])) && (isset($_GET["id"]))) {
		$rrd_info = rrdtool_function_info($_GET["id"]);

		if (sizeof($rrd_info["rra"])) {
			$diff = rrdtool_cacti_compare($_GET["id"], $rrd_info);
			rrdtool_info2html($rrd_info, $diff);
			rrdtool_tune($rrd_info["filename"], $diff, true);
		}
	}

	if ((isset($_GET["id"])) || (isset($_GET["new"]))) {
		form_hidden_box("save_component_data_source","1","");
	}else{
		form_hidden_box("save_component_data_source_new","1","");
	}

	form_save_button_alt();

	include_once(CACTI_BASE_PATH . "/access/js/data_source_item.js");
	include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");
}

function get_poller_interval($seconds) {
	if ($seconds == 0) {
		return "<em>" . __("External") . "</em>";
	}else if ($seconds < 60) {
		return "<em>" . $seconds . " " . __("Seconds") . "</em>";
	}else if ($seconds == 60) {
		return __("1 Minute");
	}else{
		return "<em>" . ($seconds / 60) . " " . __("Minutes") . "</em>";
	}
}

function data_source_filter() {
	global $item_rows;

	html_start_box(__("Data Sources"), "100", "3", "center", "data_sources.php?action=edit&device_id=" . html_get_page_variable("device_id"), true);
	?>
	<tr class='rowAlternate3'>
		<td>
			<form id="form_data_sources" action="data_sources.php" name="form_data_sources" onSubmit="javascript:return false">
			<table cellpadding="0" cellspacing="3">
				<tr>
					<td class="w1">
						<?php print __("Search:");?>
					</td>
					<td class="w1">
						<input type="text" name="filter" size="30" value="<?php print html_get_page_variable("filter");?>" onChange="applyDSFilterChange(document.form_data_sources)">
					</td>
					<td class="w1">
						<?php print __("Rows:");?>
					</td>
					<td class="w1">
						<select name="rows" onChange="applyDSFilterChange(document.form_data_sources)">
							<option value="-1"<?php if (html_get_page_variable("rows") == "-1") {?> selected<?php }?>><?php print __("Default");?></option>
							<?php
							if (sizeof($item_rows) > 0) {
							foreach ($item_rows as $key => $value) {
								print "<option value='" . $key . "'"; if (html_get_page_variable("rows") == $key) { print " selected"; } print ">" . $value . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td class="w1">
						<input type="button" value="<?php print __("Go");?>" name="go" align="middle" onClick="applyDSFilterChange(document.form_data_sources)">
					</td>
					<td class="w1">
						<input type="button" value="<?php print __("Clear");?>" name="clear" align="middle" onClick="clearDSFilterChange(document.form_data_sources)">
					</td>
				</tr>
				<tr>
					<td class="w1">
						<?php print __("Device:");?>
					</td>
					<td class="w1">
						<?php
						if (html_get_page_variable("device_id") > 0) {
							$hostname = db_fetch_cell("SELECT description as name FROM device WHERE id=" . html_get_page_variable("device_id") . " ORDER BY description,hostname");
						} else {
							$hostname = "";
						}
						?>
						<input type="text" id="device" size="30" value="<?php print $hostname; ?>">
						<input type="hidden" id="device_id">
					</td>
					<td class="w1">
						<?php print __("Template:");?>
					</td>
					<td class="w1">
						<select name="template_id" onChange="applyDSFilterChange(document.form_data_sources)">
							<option value="-1"<?php if (html_get_page_variable("template_id") == "-1") {?> selected<?php }?>><?php print __("Any");?></option>
							<option value="0"<?php if (html_get_page_variable("template_id") == "0") {?> selected<?php }?>><?php print __("None");?></option>
							<?php

							$templates = db_fetch_assoc("SELECT DISTINCT data_template.id, data_template.name
								FROM data_template
								INNER JOIN data_template_data
								ON data_template.id=data_template_data.data_template_id
								WHERE data_template_data.local_data_id>0
								ORDER BY data_template.name");

							if (sizeof($templates) > 0) {
							foreach ($templates as $template) {
								print "<option value='" . $template["id"] . "'"; if (html_get_page_variable("template_id") == $template["id"]) { print " selected"; } print ">" . title_trim($template["name"], 40) . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td class="w1">
						<?php print __("Method:");?>
					</td>
					<td class="w1">
						<select name="method_id" onChange="applyDSFilterChange(document.form_data_sources)">
							<option value="-1"<?php if (html_get_page_variable("method_id") == "-1") {?> selected<?php }?>><?php print __("Any");?></option>
							<option value="0"<?php if (html_get_page_variable("method_id") == "0") {?> selected<?php }?>><?php print __("None");?></option>
							<?php

							$methods = db_fetch_assoc("SELECT DISTINCT data_input.id, data_input.name
								FROM data_input
								INNER JOIN data_template_data
								ON data_input.id=data_template_data.data_input_id
								WHERE data_template_data.local_data_id>0
								ORDER BY data_input.name");

							if (sizeof($methods) > 0) {
							foreach ($methods as $method) {
								print "<option value='" . $method["id"] . "'"; if (html_get_page_variable("method_id") == $method["id"]) { print " selected"; } print ">" . title_trim($method["name"], 40) . "</option>\n";
							}
							}
							?>
						</select>
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='1'>
			<?php if (html_get_page_variable("tab") != "") {?>
			<input type='hidden' id='tab' name='tab' value='<?php print html_get_page_variable("tab");?>'>
			<?php }?>
			</form>
		</td>
	</tr>
	<?php
	html_end_box(false);
	?>
	<script type="text/javascript">
	$().ready(function() {
		$("#device").autocomplete({
			// provide data via call to layout.php which in turn calls ajax_get_devices_brief
			source: "layout.php?action=ajax_get_devices_brief",
			// start selecting, even if no letter typed
			minLength: 0,
			// what to do with data returned
			select: function(event, ui) {
				if (ui.item) {
					// provide the id found to hidden variable device_id
					$(this).parent().find("#device_id").val(ui.item.id);
				}else{
					// in case we didn't find anything, use "any" device
					$(this).parent().find("#device_id").val(-1);
				}
				// and now apply all changes from this autocomplete to the filter
				applyDSFilterChange(document.form_data_sources);
			}			
		});
	});

	function clearDSFilterChange(objForm) {
		strURL = '?filter=';
		if (objForm.tab) {
			strURL = strURL + '&action='+objForm.tab.value+'&tab=' + objForm.tab.value;
			<?php
			# now look for more parameters
			if (isset($_REQUEST["device_id"])) {
				print "strURL = strURL + '&device_id=" . html_get_page_variable("device_id") . "';";
			}
			print "strURL = strURL + '&template_id=-1';";
			?>
		}else {
			strURL = strURL + '&action=ajax_view';
			strURL = strURL + '&device_id=-1';
			strURL = strURL + '&template_id=-1';
		}

		strURL = strURL + '&rows=-1';
		strURL = strURL + '&method_id=-1';

		$loc = $('#form_data_sources').closest('div[id^="ui-tabs"]');
		if ($loc.attr('id')) {
			$.get(strURL, function(data) {
				$loc.html(data);
			});
		}else{
			$.get(strURL, function(data) {
				$('#content').html(data);
			});
		}
	}

	function applyDSFilterChange(objForm) {
		strURL = '?filter=' + objForm.filter.value;
		if (objForm.tab) {
			strURL = strURL + '&action='+objForm.tab.value+'&tab=' + objForm.tab.value;
		}else{
			strURL = strURL + '&action=ajax_view';
		}
		if (objForm.device_id.value) {
			strURL = strURL + '&device_id=' + objForm.device_id.value;
		}else{
			<?php print (isset($_REQUEST["device_id"]) ? "strURL = strURL + '&device_id=" . html_get_page_variable("device_id") . "&id=" . html_get_page_variable("device_id") . "';" : "strURL = strURL + '&device_id=-1';");?>
		}
		if (objForm.template_id.value) {
			strURL = strURL + '&template_id=' + objForm.template_id.value;
		}else{
			<?php print (isset($_REQUEST["template_id"]) ? "strURL = strURL + '&template_id=" . html_get_page_variable("template_id") . "&id=" . html_get_page_variable("template_id") . "';" : "strURL = strURL + '&template_id=-1';");?>
		}
		strURL = strURL + '&rows=' + objForm.rows.value;
		strURL = strURL + '&method_id=' + objForm.method_id.value;

		$loc = $('#form_data_sources').closest('div[id^="ui-tabs"]');
		if ($loc.attr('id')) {
			$.get(strURL, function(data) {
				$loc.html(data);
			});
		}else{
			$.get(strURL, function(data) {
				$('#content').html(data);
			});
		}
	}
	</script>
	<?php
}

function get_data_source_records(&$total_rows, &$rowspp) {
	if (strlen(html_get_page_variable("filter"))) {
		$sql_where = "AND (data_template_data.name_cache like '%%" . html_get_page_variable("filter") . "%%'" .
			" OR data_template_data.local_data_id like '%%" . html_get_page_variable("filter") . "%%'" .
			" OR data_template.name like '%%" . html_get_page_variable("filter") . "%%'" .
			" OR data_input.name like '%%" . html_get_page_variable("filter") . "%%')";
	}else{
		$sql_where = "";
	}

	if (html_get_page_variable("device_id") == "-1") {
		/* Show all items */
	}elseif (html_get_page_variable("device_id") == "0") {
		$sql_where .= " AND data_local.device_id=0";
	}else {
		$sql_where .= " AND data_local.device_id=" . html_get_page_variable("device_id");
	}

	if (html_get_page_variable("template_id") == "-1") {
		/* Show all items */
	}elseif (html_get_page_variable("template_id") == "0") {
		$sql_where .= " AND data_template_data.data_template_id=0";
	}else {
		$sql_where .= " AND data_template_data.data_template_id=" . html_get_page_variable("template_id");
	}

	if (html_get_page_variable("method_id") == "-1") {
		/* Show all items */
	}elseif (html_get_page_variable("method_id") == "0") {
		$sql_where .= " AND data_template_data.data_input_id=0";
	}else {
		$sql_where .= " AND data_template_data.data_input_id=" . html_get_page_variable("method_id");
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_data_source");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$total_rows = sizeof(db_fetch_assoc("SELECT
		data_local.id
		FROM (data_local,data_template_data)
		LEFT JOIN data_input
		ON (data_input.id=data_template_data.data_input_id)
		LEFT JOIN data_template
		ON (data_local.data_template_id=data_template.id)
		WHERE data_local.id=data_template_data.local_data_id
		$sql_where"));

	$dssql = "SELECT
		data_template_data.local_data_id,
		data_template_data.name_cache,
		data_template_data.active,
		data_input.name as data_input_name,
		data_template.name as data_template_name,
		data_local.device_id
		FROM (data_local,data_template_data)
		LEFT JOIN data_input
		ON (data_input.id=data_template_data.data_input_id)
		LEFT JOIN data_template
		ON (data_local.data_template_id=data_template.id)
		WHERE data_local.id=data_template_data.local_data_id
		$sql_where
		ORDER BY ". html_get_page_variable('sort_column') . " " . html_get_page_variable('sort_direction') .
		" LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp;

	return db_fetch_assoc($dssql);
}

function data_source($refresh = true) {
	global $item_rows;

	require(CACTI_INCLUDE_PATH . "/data_source/data_source_arrays.php");

	$table = New html_table;

	$table->page_variables = array(
		"page"           => array("type" => "numeric", "method" => "request", "default" => "1"),
		"rows"           => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"filter"         => array("type" => "string",  "method" => "request", "default" => ""),
		"tab"            => array("type" => "string",  "method" => "request", "default" => "", "nosession" => true),
		"device_id"      => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"method_id"      => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"template_id"    => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"sort_column"    => array("type" => "string",  "method" => "request", "default" => "name_cache"),
		"sort_direction" => array("type" => "string",  "method" => "request", "default" => "ASC")
	);

	$table->table_format = array(
		"name_cache" => array(
			"name" => __("Name"),
			"link" => true,
			"filter" => true,
			"order" => "ASC"
		),
		"data_input_name" => array(
			"name" => __("Data Input Method"),
			"function" => "display_data_input_name",
			"params" => array("data_input_name"),
			"order" => "ASC"
		),
		"nosort" => array(
			"name" => __("Poller Interval"),
			"order" => "ASC",
			"function" => "display_poller_interval",
			"sort" => false,
			"align" => "right"
		),
		"active" => array(
			"name" => __("Status"),
			"function" => "display_checkbox_status",
			"params" => array("active"),
			"order" => "ASC"
		),
		"data_template_name" => array(
			"name" => __("Template Name"),
			"filter" => true,
			"order" => "ASC"
		),
		"local_data_id" => array(
			"name" => __("ID"),
			"order" => "ASC",
			"align" => "right"
		)
	);

	/* initialize page behavior */
	$table->href           = "data_sources.php";
	$table->session_prefix = "sess_data_sources";
	$table->filter_func    = "data_source_filter";
	$table->key_field      = "local_data_id";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = $ds_actions;
	$table->table_id       = "data_sources";
	if (isset($_REQUEST['parent'])) {
		$table->parent    = get_request_var_request('parent');
		$table->parent_id = get_request_var_request('parent_id');
	}

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_data_source_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}

