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

include("./include/auth.php");
include_once(CACTI_LIBRARY_PATH . "/utility.php");

define("MAX_DISPLAY_PAGES", 21);

$device_actions = array(
	ACTION_NONE => __("None"),
	"1" => __("Delete"),
	"2" => __("Duplicate")
	);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }


switch (get_request_var_request("action")) {
	case 'save':
		device_template_form_save();

		break;
	case 'save_gt':
		device_template_form_save_gt();

		break;
	case 'save_dq':
		device_template_form_save_dq();

		break;
	case 'actions':
		device_template_form_actions();

		break;
	case 'item_remove_gt':
		device_template_item_remove_gt();

		header("Location: device_templates.php?action=edit&tab=graph_templates&id=" . $_GET["device_template_id"]);

		break;
	case 'item_remove_dq':
		device_template_item_remove_dq();

		header("Location: device_templates.php?action=edit&tab=data_queries&id=" . $_GET["device_template_id"]);

		break;
	case 'ajax_edit':
		device_template_edit(false);

		break;
	case 'edit':
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		device_template_edit(true);
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
	case 'ajax_view':
		device_template();

		break;
	default:
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		device_template();
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function device_template_form_save() {
	if (isset($_POST["save_component_template"])) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("id"));
		/* ==================================================== */
		$redirect_back = false;
		$tab = "&tab=general";

		$save["id"] 					= get_request_var_post("id");
		$save["hash"]					= get_hash_device_template($_POST["id"]);
		$save["name"]					= form_input_validate($_POST["name"], "name", "", false, 3);
		$save["description"]			= form_input_validate($_POST["description"], "description", "", true, 3);
		$save["image"]					= form_input_validate(basename($_POST["image"]), "image", "", true, 3);
		$save["override_defaults"]		= form_input_validate((isset($_POST["override_defaults"]) ? "on":""), "override_defaults", "", true, 3);
		$save["override_permitted"]		= form_input_validate((isset($_POST["override_permitted"]) ? "on":""), "override_permitted", "", true, 3);
		$save["snmp_version"]			= form_input_validate($_POST["snmp_version"], "snmp_version", "", true, 3);
		$save["snmp_version"]			= form_input_validate($_POST["snmp_version"], "snmp_version", "", true, 3);
		$save["snmp_community"]			= form_input_validate($_POST["snmp_community"], "snmp_community", "", true, 3);
		$save["snmp_username"]			= form_input_validate($_POST["snmp_username"], "snmp_username", "", true, 3);
		$save["snmp_password"]			= form_input_validate($_POST["snmp_password"], "snmp_password", "", true, 3);
		$save["snmp_auth_protocol"]		= form_input_validate($_POST["snmp_auth_protocol"], "snmp_auth_protocol", "", true, 3);
		$save["snmp_priv_passphrase"]	= form_input_validate($_POST["snmp_priv_passphrase"], "snmp_priv_passphrase", "", true, 3);
		$save["snmp_priv_protocol"]		= form_input_validate($_POST["snmp_priv_protocol"], "snmp_priv_protocol", "", true, 3);
		$save["snmp_context"]			= form_input_validate($_POST["snmp_context"], "snmp_context", "", true, 3);
		$save["snmp_port"]				= form_input_validate($_POST["snmp_port"], "snmp_port", "^[0-9]+$", false, 3);
		$save["snmp_timeout"]			= form_input_validate($_POST["snmp_timeout"], "snmp_timeout", "^[0-9]+$", false, 3);
		$save["availability_method"]	= form_input_validate($_POST["availability_method"], "availability_method", "^[0-9]+$", false, 3);
		$save["ping_method"]			= form_input_validate($_POST["ping_method"], "ping_method", "^[0-9]+$", false, 3);
		$save["ping_port"]				= form_input_validate($_POST["ping_port"], "ping_port", "^[0-9]+$", true, 3);
		$save["ping_timeout"]			= form_input_validate($_POST["ping_timeout"], "ping_timeout", "^[0-9]+$", true, 3);
		$save["ping_retries"]			= form_input_validate($_POST["ping_retries"], "ping_retries", "^[0-9]+$", true, 3);
		$save["max_oids"]				= form_input_validate($_POST["max_oids"], "max_oids", "^[0-9]+$", true, 3);
		$save["device_threads"]			= form_input_validate($_POST["device_threads"], "device_threads", "^[0-9]+$", true, 3);

		if (!is_error_message()) {
			$device_template_id = sql_save($save, "device_template");

			if ($device_template_id) {
				raise_message(1);

				/* update the image from the cache */
				device_template_update_cache($device_template_id, $_POST["image"]);
			}else{
				raise_message(2);
			}
		}
	}

	header("Location: device_templates.php?action=edit&tab=general&id=" . (empty($device_template_id) ? $_POST["id"] : $device_template_id));
	exit;
}

/**
 * Add a new Graph Template to the current Device Template.
 * Optionally, ask for confirmation to update related devices.
 */
function device_template_form_save_gt() {
	require_once(CACTI_LIBRARY_PATH . "/functions.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_post("id"));
	input_validate_input_number(get_request_var_post("graph_template_id"));
	/* ==================================================== */

	/* We are on the graph templates tab.
	 * The user just requested to add a new graph template to this device template.
	 *
	 * We will add the graph template to the device template.
	 * Then, we will perform two runs:
	 * 1. We will prompt the user with a list of devices to be updated and ask for confirmation
	 *    If the device list is empty, there will be NO confirmation!
	 * 2. After confirmation. we will actually update the devices
	 */

	/* We will prompt the user with a list of devices to be updated and ask for confirmation */
	if (!isset($_POST["selected_items"])) {

		/*
		 * unconditionally add the new graph template to this device template
		 * this DOES NOT require a confirmation!
		 */
		db_execute("REPLACE INTO device_template_graph (device_template_id,graph_template_id) VALUES(" .
					get_request_var_post("id") . "," . get_request_var_post("graph_template_id") . ")");

		/*
		 * list all related hosts for confirmation,
		 * but omit those devices that already have this graph template
		 * */
		$new_gt_device_entries = db_fetch_assoc("SELECT device.id AS device_id, " .
											"device.description AS description, " .
											"device.hostname AS hostname " .
											"FROM device " .
											"WHERE	device.device_template_id = " . get_request_var_post("id") . " " .
											"AND		device.id NOT IN (" .
												"SELECT device_graph.device_id " .
												"FROM   device_graph " .
												"WHERE  device_graph.graph_template_id = " . get_request_var_post("graph_template_id") .
											")");

		/* if there are devices to work on */
		if (sizeof($new_gt_device_entries) > 0) {
			$device_list = ""; $i = 0; $device_array = array();
			/* fetch the graph template's name */
			$template_name = db_fetch_cell("SELECT name FROM graph_templates WHERE id = " . get_request_var_post("graph_template_id"));

			/* list all devices to be treated for confirmation */
			foreach($new_gt_device_entries as $entry) {
				$device_list .= "<li>" . $entry["hostname"] . " - " . $entry["description"] . "</li>\n";
				$device_array[$i++] = $entry["device_id"];
			}

			/* now draw the html page */
			include_once(CACTI_INCLUDE_PATH . "/top_header.php");
			html_start_box(__("Confirm"), "60", "3", "center", "");

			print "<form action='device_templates.php' method='post' id='device_template_add_gt'>";
			# pass device template id and graph_template id to the updating code below
			form_hidden_box("id", get_request_var_post("id"), "");
			form_hidden_box("graph_template_id", get_request_var_post("graph_template_id"), "");
			print "
					<tr>
						<td class='textArea'>
							<p>" . __("Are you sure you want to add the following Graph Template:") . " <strong>" . $template_name . "</strong><br>" .
								__("All devices currently attached to the current Device Template will be updated.") . "</p>
							<p><ul>$device_list</ul></p>
						</td>
					</tr>\n
					";

			form_continue2(serialize($device_array), "save_gt");
			html_end_box();
			include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");
			exit;
		}
	} else {
		/*
		 * 2. After confirmation. we will actually update the devices
		 */
		/* get all confirmed devices that were passed as a serializes array */
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));
		if (sizeof($selected_items)) {

			foreach($selected_items as $device_id) {
				/* ================= input validation ================= */
				input_validate_input_number($device_id);
				/* ==================================================== */
				/* add the Graph Template */
				db_execute("REPLACE INTO device_graph ( device_id, graph_template_id ) VALUES (" .
							$device_id . "," . get_request_var_post("graph_template_id") . ")");
			}
		}
	}

	/* now let's return to the graph_templates tab of the current device template */
	header("Location: device_templates.php?action=edit&tab=graph_templates&id=" . get_request_var_post("id"));
	exit;
}


function device_template_form_save_dq() {
	/* required for "run_data_query" */
	include_once(CACTI_LIBRARY_PATH . "/data_query.php");

	/* We are on the data queries tab.
	 *
	 * User may request EITHER a reindex method update
	 *                  OR     adding a new data query
	 */

	if (isset($_POST["reindex"])) {
		/*
		 * loop for all possible changes of reindex_method
		 * post variable is build like this
		 * 		reindex_method_device_template_<device_id>_query_<snmp_query_id>_method_<old_reindex_method>
		 * if values of this variable differs from <old_reindex_method>, we will have to update
		 */
		while (list($var,$val) = each($_POST)) {
			if (preg_match("/^reindex_method_device_template_([0-9]+)_query_([0-9]+)_method_([0-9]+)$/", $var, $matches)) {
				/* ================= input validation ================= */
				input_validate_input_number(get_request_var_post("id"));
				input_validate_input_number($matches[1]); # device_template
				input_validate_input_number($matches[2]); # snmp_query_id
				input_validate_input_number($matches[3]); # old reindex method
				$reindex_method = $val;
				input_validate_input_number($reindex_method); # new reindex_method
				/* ==================================================== */

				# change reindex method of this very item
				if ( $reindex_method != $matches[3]) {
					db_execute("REPLACE INTO device_template_snmp_query (device_template_id,snmp_query_id,reindex_method) VALUES (" .
								$matches[1] . "," . $matches[2] . "," . $reindex_method .
								")");
					$reindex_performed = true;
				}
			}
		}
	}elseif (!isset($_POST["selected_items"])) {
		/*
		 * The user just requested to add a new data query to this device template.
		 *
		 * We will add the data query to the device template.
		 * Then, we will perform two runs:
		 * 1. We will prompt the user with a list of devices to be updated and ask for confirmation
		 *    If the device list is empty, there will be NO confirmation!
		 * 2. After confirmation. we will actually update the devices
		 */

		/* We will prompt the user with a list of devices to be updated and ask for confirmation */

		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("id"));
		input_validate_input_number(get_request_var_post("snmp_query_id"));
		input_validate_input_number(get_request_var_post("reindex_method"));
		/* ==================================================== */

		/*
		 * unconditionally add the new data query to this device template
		 * this DOES NOT require a confirmation!
		 */
		db_execute("REPLACE INTO device_template_snmp_query (device_template_id,snmp_query_id, reindex_method) VALUES(" .
					get_request_var_post("id") . "," . get_request_var_post("snmp_query_id") . ", " . get_request_var_post("reindex_method") . ")");

		/*
		 * list all related hosts for confirmation,
		 * but omit those devices that already have this graph template
		 * */
		$new_dq_device_entries = db_fetch_assoc("SELECT device.id AS device_id, " .
											"device.description AS description, " .
											"device.hostname AS hostname " .
											"FROM  	device " .
											"WHERE	device.device_template_id = " . get_request_var_post("id") . " " .
											"AND	device.id NOT IN (" .
												"SELECT device_snmp_query.device_id " .
												"FROM   device_snmp_query " .
												"WHERE  device_snmp_query.snmp_query_id = " . $_POST["snmp_query_id"] .
											")");

		if (sizeof($new_dq_device_entries) > 0) {
			$device_list = ""; $i = 0; $device_array = array();
			/* fetch the graph template's name */
			$template_name = db_fetch_cell("SELECT name FROM snmp_query WHERE id = " . get_request_var_post("snmp_query_id"));

			/* list all devices to be treated for confirmation */
			foreach($new_dq_device_entries as $entry) {
				$device_list .= "<li>" . $entry["hostname"] . " - " . $entry["description"] . "</li>\n";
				$device_array[$i++] = $entry["device_id"];
			}

			/* now draw the html page */
			include_once(CACTI_INCLUDE_PATH . "/top_header.php");
			html_start_box(__("Confirm"), "60", "3", "center", "");

			print "<form action='device_templates.php' method='post' id='device_template_add_dq'>";
			# pass device template id, data query id and reindex method to the updating code below
			form_hidden_box("id", get_request_var_post("id"), "");
			form_hidden_box("snmp_query_id", get_request_var_post("snmp_query_id"), "");
			form_hidden_box("reindex_method", get_request_var_post("reindex_method"), "");
			print "
					<tr>
						<td class='textArea'>
							<p>" . __("Are you sure you want to add the following Data Query:") . " <strong>" . $template_name . "</strong><br>" .
								__("All devices currently attached to the current Device Template will be updated.") . "</p>
							<p><ul>$device_list</ul></p>
						</td>
					</tr>\n
					";

			form_continue2(serialize($device_array), "save_dq");
			html_end_box();
			include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");
			exit;
		}
	} else {
		/*
		 * 2. After confirmation. we will actually update the devices
		 */
		/* get all confirmed devices that were passed as a serializes array */
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));
		if (sizeof($selected_items)) {

			foreach($selected_items as $device_id) {
				/* ================= input validation ================= */
				input_validate_input_number($device_id);
				/* ==================================================== */
				/* add the Data Query */
				db_execute("REPLACE INTO device_snmp_query (device_id,snmp_query_id,reindex_method)
							VALUES (". $device_id . ","
							. get_request_var_post("snmp_query_id") . ","
							. get_request_var_post("reindex_method") . "
							)");

				/* recache snmp data */
				run_data_query($device_id, get_request_var_post("snmp_query_id"));
			}
		}
	}

	header("Location: device_templates.php?action=edit&tab=data_queries&id=" . (empty($device_template_id) ? $_POST["id"] : $device_template_id));
	exit;
}

/* ------------------------
    The "actions" function
   ------------------------ */

function device_template_form_actions() {
	global $device_actions;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === "1") { /* delete */
			/* do a referential integrity check */
			if (sizeof($selected_items)) {
			foreach($selected_items as $template_id) {
				/* ================= input validation ================= */
				input_validate_input_number($template_id);
				/* ==================================================== */

				if (sizeof(db_fetch_assoc("SELECT * FROM device WHERE device_template_id=$template_id LIMIT 1"))) {
					$bad_ids[] = $template_id;
				}else{
					$template_ids[] = $template_id;
				}
			}
			}

			if (isset($bad_ids)) {
				$message = "";
				foreach($bad_ids as $template_id) {
					$message .= (strlen($message) ? "<br>":"") . "<i>Device Template " . $template_id . " is in use and can not be removed</i>\n";
				}

				$_SESSION['sess_message_dt_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');

				raise_message('dt_ref_int');
			}

			if (isset($template_ids)) {
				db_execute("delete from device_template where " . array_to_sql_or($template_ids, "id"));
				db_execute("delete from device_template_snmp_query where " . array_to_sql_or($template_ids, "device_template_id"));
				db_execute("delete from device_template_graph where " . array_to_sql_or($template_ids, "device_template_id"));

				/* "undo" any device that is currently using this template */
				db_execute("update device set device_template_id=0 where " . array_to_sql_or($template_ids, "device_template_id"));
			}
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				duplicate_device_template($selected_items[$i], get_request_var_post("title_format"));
			}
		}

		exit;
	}

	/* setup some variables */
	$device_list = ""; $device_array = array();

	/* loop through each of the device templates selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$device_list .= "<li>" . db_fetch_cell("select name from device_template where id=" . $matches[1]) . "</li>";
			$device_array[] = $matches[1];
		}
	}

	print "<form id='dactions' name='dactions' action='device_templates.php' method='post'>\n";

	html_start_box("", "100", "3", "center", "");

	if (sizeof($device_array)) {
		if (get_request_var_post("drp_action") === ACTION_NONE) { /* NONE */
			print "	<tr>
						<td class='textArea'>
							<p>" . __("You did not select a valid action. Please select 'Return' to return to the previous menu.") . "</p>
						</td>
					</tr>\n";

			$title = __("Selection Error");
		}elseif (get_request_var_post("drp_action") === "1") { /* delete */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Device Template(s) will be deleted.  All devices currently attached this these Device Template(s) will lose their template assocation.") . "</p>
						<div class='action_list'><ul>$device_list</ul></div>
					</td>
				</tr>\n";

			$title = __("Delete Device Template(s)");
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Device Template(s) will be duplicated. You can optionally change the title format for the new Device Template(s).") . "</p>
						<div class='action_list'><ul>$device_list</ul></div>
						<p><strong>" . __("Title Format:") . "</strong><br>"; form_text_box("title_format", "<template_title> (1)", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n";

			$title = __("Duplicate Device Template(s)");
		}
	} else {
		print "	<tr>
				<td class='textArea'>
					<p>" . __("You must first select a Device Template.  Please select 'Return' to return to the previous menu.") . "</p>
				</td>
			</tr>\n";

		$title = __("Selection Error");
	}

	if (!sizeof($device_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button($title);
	}else{
		form_continue(serialize($device_array), get_request_var_post("drp_action"), $title, "dactions");
	}

	html_end_box();
}

/* ---------------------
    Template Functions
   --------------------- */

function device_template_item_remove_gt() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("device_template_id"));
	/* ==================================================== */

	db_execute("delete from device_template_graph where graph_template_id=" . $_GET["id"] . " and device_template_id=" . $_GET["device_template_id"]);
}

function device_template_item_remove_dq() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("device_template_id"));
	/* ==================================================== */

	db_execute("delete from device_template_snmp_query where snmp_query_id=" . $_GET["id"] . " and device_template_id=" . $_GET["device_template_id"]);
}

function device_template_edit($tabs = false) {
	if ($tabs) {
		/* remember if there's something we want to show to the user */
		$device_template_tabs = array(
			"general" 			=> __("General"),
			"graph_templates" 	=> __("Associated Graph Templates"),
			"data_queries" 		=> __("Associated Data Queries"),
			"devices" 			=> __("Devices"),
		);

		/* draw the categories tabs on the top of the page */
		print "<div id='tabs_dt'>\n";
		print "<ul>\n";

		$i = 1;
		if (sizeof($device_template_tabs) > 0) {
			foreach (array_keys($device_template_tabs) as $tab_short_name) {
				print "<li><a id='tabs-$i' href='" . htmlspecialchars("device_templates.php?action=ajax_edit" . (isset($_REQUEST['id']) ? "&id=" . $_REQUEST['id'] . "&template_id=" . $_REQUEST['id']: "") . "&tab=$tab_short_name") . "'>$device_template_tabs[$tab_short_name]</a></li>";
				$i++;

				if (!isset($_REQUEST["id"])) break;
			}
		}

		print "</ul>\n";
		print "</div>\n";

		print "<script type='text/javascript'>
			$().ready(function() {
				$('#tabs_dt').tabs({cookie:{}});
			});
		</script>\n";
	}else{
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var("id"));
		/* ==================================================== */

		if (!empty($_REQUEST["id"]) && $_REQUEST["id"] > 0) {
			$device_template = db_fetch_row("select * from device_template where id=" . $_REQUEST["id"]);
			$header_label = __("[edit: ") . $device_template["name"] . "]";
		}else{
			$device_template = array();
			$header_label = __("[new]");
			$_REQUEST["id"] = 0;
		}

		switch (get_request_var_request("tab")) {
			case "devices":
				require_once(CACTI_INCLUDE_PATH . "/device/device_arrays.php");
				include_once(CACTI_LIBRARY_PATH . "/device.php");
				include_once(CACTI_LIBRARY_PATH . "/graph.php");
				include_once(CACTI_LIBRARY_PATH . "/utility.php");
				include_once(CACTI_LIBRARY_PATH . "/tree.php");
				include_once(CACTI_LIBRARY_PATH . "/snmp.php");
				include_once(CACTI_LIBRARY_PATH . "/ping.php");
				include_once(CACTI_LIBRARY_PATH . "/html_tree.php");
				include_once(CACTI_LIBRARY_PATH . "/data_query.php");
				include_once(CACTI_LIBRARY_PATH . "/sort.php");
				include_once(CACTI_LIBRARY_PATH . "/html_form_template.php");
				include_once(CACTI_LIBRARY_PATH . "/template.php");

				device();

				break;
			case "graph_templates":
				if (!empty($_REQUEST["id"])) {
					device_template_display_gt($device_template, $header_label);
				}
	
				break;
			case "data_queries":
				if (!empty($_REQUEST["id"])) {
					device_template_display_dq($device_template, $header_label);
				}

				break;
			default:
				device_template_display_general($device_template, $header_label);

				break;
		}
	}
}


function device_template_display_general($device_template, $header_label) {
	require(CACTI_INCLUDE_PATH . "/data_query/data_query_arrays.php");
	require_once(CACTI_LIBRARY_PATH . "/device_template.php");

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='device_template_edit'>\n";
	html_start_box(__("Device Templates") . " $header_label", "100", "0", "center", "", true);

	draw_edit_form(array(
		"config" => array("no_form_tag" => true),
		"fields" => inject_form_variables(device_template_form_list(), (isset($device_template) ? $device_template : array()))
		));

	html_end_box();
	form_hidden_box("id", (isset($device_template["id"]) ? $device_template["id"] : "0"), "");
	form_hidden_box("save_component_template", "1", "");

	?>
	<script type="text/javascript">
	<!--

	/* set the visibility of the SNMP options available
	   depending on the SNMP version currently defined */
	function setSNMPVisibility(snmp_version) {
		//alert("changeHostForm SNMP Version is '" + snmp_version + "'");

		switch(snmp_version) {
		case "<?php print SNMP_VERSION_NONE;?>": // SNMP none
//			$('#snmp_version').attr("disabled","disabled");
			$('#row_snmp_version').css('display', 'none');
			$('#row_snmp_username').css('display', 'none');
			$('#row_snmp_password').css('display', 'none');
			$('#row_snmp_community').css('display', 'none');
			$('#row_snmp_auth_protocol').css('display', 'none');
			$('#row_snmp_priv_passphrase').css('display', 'none');
			$('#row_snmp_priv_protocol').css('display', 'none');
			$('#row_snmp_context').css('display', 'none');
			$('#row_snmp_port').css('display', 'none');
			$('#row_snmp_timeout').css('display', 'none');
			$('#row_max_oids').css('display', 'none');

			break;
		case "<?php print SNMP_VERSION_1;?>": // SNMP V1
		case "<?php print SNMP_VERSION_2;?>": // SNMP V2
//			$('#snmp_version').removeAttr("disabled");
			$('#row_snmp_version').css('display', '');
			$('#row_snmp_username').css('display', 'none');
			$('#row_snmp_password').css('display', 'none');
			$('#row_snmp_community').css('display', '');
			$('#row_snmp_auth_protocol').css('display', 'none');
			$('#row_snmp_priv_passphrase').css('display', 'none');
			$('#row_snmp_priv_protocol').css('display', 'none');
			$('#row_snmp_context').css('display', 'none');
			$('#row_snmp_port').css('display', '');
			$('#row_snmp_timeout').css('display', '');
			$('#row_max_oids').css('display', '');

			break;
		case "<?php print SNMP_VERSION_3;?>": // SNMP V3
//			$('#snmp_version').removeAttr("disabled");
			$('#row_snmp_version').css('display', '');
			$('#row_snmp_username').css('display', '');
			$('#row_snmp_password').css('display', '');
			$('#row_snmp_community').css('display', 'none');
			$('#row_snmp_auth_protocol').css('display', '');
			$('#row_snmp_priv_passphrase').css('display', '');
			$('#row_snmp_priv_protocol').css('display', '');
			$('#row_snmp_context').css('display', '');
			$('#row_snmp_port').css('display', '');
			$('#row_snmp_timeout').css('display', '');
			$('#row_max_oids').css('display', '');

			break;
		}
	}



	/* set the visibility of the ping_port
	   in case we have an ICMP ping, you can't set a port
	 */
	function setPingPortVisibility(ping_method) {
		//alert("setPingPortVisibility Ping Method is '" + ping_method + "'");
		
		switch(ping_method) {
		case "<?php print PING_NONE;?>": // ping nothing
			/* deactivate all PING options */
			$('#row_ping_method').css('display', 'none');
			$('#row_ping_port').css('display', 'none');
			$('#row_ping_timeout').css('display', 'none');
			$('#row_ping_retries').css('display', 'none');

			break;
		case "<?php print PING_ICMP;?>": // ping icmp
			/* ICMP ping does not take a port */
			$('#row_ping_method').css('display', '');
			$('#row_ping_port').css('display', 'none');
			$('#row_ping_timeout').css('display', '');
			$('#row_ping_retries').css('display', '');

			break;
		case "<?php print PING_UDP;?>": // ping udp
		case "<?php print PING_TCP;?>": // ping tcp
			$('#row_ping_method').css('display', '');
			$('#row_ping_port').css('display', '');
			$('#row_ping_timeout').css('display', '');
			$('#row_ping_retries').css('display', '');

			break;
		}
	}

	/* this function is called when
	   - availibility options changes
	   - ping method changes
	   - SNMP version changes
	   - and on page load
	   it will cover the required changes by calling appropriate functions
	   that are responsible for each specific change
	 */
	function changeHostForm() {
		ping_method         = $('#ping_method').val();
		//alert("Ping Method is '" + ping_method + "'");
		snmp_version        = $('#snmp_version').val();
		//alert("SNMP Version is '" + snmp_version + "'");		
		availability        = $('#availability_method').val();
		//alert("Availability is '" + availability + "'");


		switch(availability) {
		case "<?php print AVAIL_NONE;?>": // availability none
			/* deactivate PING */
			setPingPortVisibility("<?php print PING_NONE;?>")
			/* deactivate SNMP */
			setSNMPVisibility("<?php print SNMP_VERSION_NONE;?>")

			break;
		case "<?php print AVAIL_PING;?>": // ping
			/* set PING */
			setPingPortVisibility(ping_method)
			/* deactivate SNMP */
			setSNMPVisibility("<?php print SNMP_VERSION_NONE;?>")

			break;
		case "<?php print AVAIL_SNMP;?>": // snmp
		case "<?php print AVAIL_SNMP_GET_SYSDESC;?>": // snmp
		case "<?php print AVAIL_SNMP_GET_NEXT;?>": // snmp
			/* deactivate PING */
			setPingPortVisibility("<?php print PING_NONE;?>")
			/* set SNMP, take care when previous SNMP version was SNMP_VERSION_NONE */
			if (snmp_version == <?php print SNMP_VERSION_NONE;?>) {
				/* this at least allows for displaying the SNMP versions 
				   and thus the user may change to values required */
				snmp_version = <?php print '"' . SNMP_VERSION_1 . '"';?>;
				$('#snmp_version').val(snmp_version);
			}
			setSNMPVisibility(snmp_version)

			break;
		case "<?php print AVAIL_SNMP_AND_PING;?>": // ping and snmp
		case "<?php print AVAIL_SNMP_OR_PING;?>": // ping or snmp
			/* set PING */
			setPingPortVisibility(ping_method)
			/* set SNMP, take care when previous SNMP version was SNMP_VERSION_NONE */
			if (snmp_version == <?php print SNMP_VERSION_NONE;?>) {
				/* this at least allows for displaying the SNMP versions 
				   and thus the user may change to values required */
				snmp_version = <?php print '"' . SNMP_VERSION_1 . '"';?>;
				$('#snmp_version').val(snmp_version);
			}
			setSNMPVisibility(snmp_version)

			break;
		}
	}


	/* enable/disable setting of 
	   - availability options
	   - ping options
	   - SNMP options
	   - threading
	   as a result of templating being enabled or disabled
	 */
	function toggleAvailabilityAndSnmp(template_enabled){
		//alert("toggleAvailabilityAndSnmp called");
	
		/* in case templating is disabled and override is allowed
		   => allow for editing those options on device level
		      by removing the "disabled" attribute
		 */ 
		if (template_enabled) {
			$('#row_override_permitted').show();
			$('#row_availability_header').show();
			$('#row_availability_method').show();
			$('#row_ping_header').show();
			$('#row_ping_method').show();
			$('#row_ping_port').show();
			$('#row_ping_timeout').show();
			$('#row_ping_retries').show();
			$('#row_snmp_spacer').show();
			$('#row_snmp_version').show();
			$('#row_snmp_username').show();
			$('#row_snmp_password').show();
			$('#row_snmp_community').show();
			$('#row_snmp_auth_protocol').show();
			$('#row_snmp_priv_passphrase').show();
			$('#row_snmp_priv_protocol').show();
			$('#row_snmp_context').show();
			$('#row_snmp_port').show();
			$('#row_snmp_timeout').show();
			$('#row_max_oids').show();
			$('#row_device_threads').show();
			
			changeHostForm();
		}else{
			$('#row_override_permitted').hide();
			$('#row_availability_header').hide();
			$('#row_availability_method').hide();
			$('#row_ping_header').hide();
			$('#row_ping_method').hide();
			$('#row_ping_port').hide();
			$('#row_ping_timeout').hide();
			$('#row_ping_retries').hide();
			$('#row_snmp_spacer').hide();
			$('#row_snmp_version').hide();
			$('#row_snmp_username').hide();
			$('#row_snmp_password').hide();
			$('#row_snmp_community').hide();
			$('#row_snmp_auth_protocol').hide();
			$('#row_snmp_priv_passphrase').hide();
			$('#row_snmp_priv_protocol').hide();
			$('#row_snmp_context').hide();
			$('#row_snmp_port').hide();
			$('#row_snmp_timeout').hide()
			$('#row_max_oids').hide();
			$('#row_device_threads').hide();
		}
	}

	/* jQuery stuff */
	$().ready(function() {
		toggleAvailabilityAndSnmp(document.getElementById('override_defaults').checked);

		/* Hide options when override is turned off */
		$("#override_defaults").change(function() {
			toggleAvailabilityAndSnmp(this.checked);
		});

		/* Hide "Uptime Goes Backwards" if snmp_version has been set to "None" */
		$("#snmp_version").change(function() {
				/* get PHP constants into javascript namespace */
				var reindex_none = <?php print DATA_QUERY_AUTOINDEX_NONE;?>;
				var reindex_reboot = <?php print DATA_QUERY_AUTOINDEX_BACKWARDS_UPTIME;?>;
				/* we require numeric values for comparison */
				var current_reindex = parseInt($(this).val());
				switch (current_reindex)
				{
					case reindex_none:
						/* now that SNMP is disabled, select reindex method "None" */
						$("#reindex_method option[value=" + reindex_none + "]").attr('selected', 'true');
						/* disable SNMP options: "Uptime Goes Backwards" never works with pure Script Data Queries */
						$("#reindex_method option[value=" + reindex_reboot + "]").attr('disabled', 'true');
						$("#reindex_method option[value=" + reindex_reboot + "]").attr('title', '<?php print __("Disabled due to SNMP settings");?>');
						break;
					default:
						/* "Uptime Goes Backwards" is allowed again */
						$("#reindex_method option[value=" + reindex_reboot + "]").removeAttr("disabled");
						$("#reindex_method option[value=" + reindex_reboot + "]").attr('title', '');
						/* select this again as default reindex method */
						/* TODO: this ignores the default reindex method of the associated Device Template
						   to get it, an AJAX call is required */
						$("#reindex_method option[value=" + reindex_reboot + "]").attr('selected', 'true');
			}
		});
	});

	//-->
	</script>
	<?php

	form_save_button("device_templates.php", "return");
}


function device_template_display_gt($device_template, $header_label) {

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='device_template_gt_edit'>\n";
	html_start_box(__("Associated Graph Templates") . " $header_label", "100", "3", "center", "", true);
	print "<tr><td>";
	html_header(array(array("name" => __("Graph Template Name"))), 3);

	/* list all graph templates that are currentyl associated */
	$selected_graph_templates = db_fetch_assoc("SELECT " .
			"graph_templates.id, " .
			"graph_templates.name " .
			"FROM device_template_graph " .
			"LEFT JOIN graph_templates ON (device_template_graph.graph_template_id = graph_templates.id) " .
			"WHERE device_template_graph.device_template_id=" . $device_template["id"] . " " .
			"ORDER BY graph_templates.name");

	/* Now list all graph templates, that have NOT yet been associated.
	 * This is to prevent duplicate assignments for the same graph template
	 */
	$available_graph_templates = db_fetch_assoc("SELECT " .
			"graph_templates.id, " .
			"graph_templates.name " .
			"FROM graph_templates " .
			"WHERE graph_templates.id NOT IN (" .
				"SELECT graph_templates.id " .
				"FROM device_template_graph " .
				"LEFT JOIN graph_templates ON (device_template_graph.graph_template_id = graph_templates.id) " .
				"WHERE device_template_graph.device_template_id=" . $device_template["id"] .
			") " .
			"ORDER BY graph_templates.name");


	$i = 0;
	if (sizeof($selected_graph_templates) > 0) {
		foreach ($selected_graph_templates as $item) {
			form_alternate_row_color("selected_graph_template" . $item["id"], true);
			$i++;
			?>
				<td style="padding: 4px;"><strong><?php print $i;?>)</strong> <?php print $item["name"];?>
				</td>
				<td align='right' nowrap><input
					type='button'
					value='<?php print __("Remove");?>'
					onClick='document.location="<?php print htmlspecialchars("device_templates.php?action=item_remove_gt&id=" . $item["id"] . "&device_template_id=" . $device_template["id"]);?>"'
					title='<?php print __("Delete Graph Template Association");?>'>
				</td>
			<?php
			form_end_row();
		}
	}else{
		print "<tr><td><em>" . __("No associated graph templates.") . "</em></td></tr>";
	}

	form_alternate_row_color("add_template" . get_request_var("id"), false);
	?>
	<td nowrap><?php form_dropdown("graph_template_id",$available_graph_templates,"name","id","","","");?>
	</td>
	<td align="right">&nbsp;<input type="submit"
		value="<?php print __("Add Template");?>" name="add_gt_y">
	</td>
	<input type='hidden' name='action' value='save_gt'>
	<?php
	form_end_row();
	print "</table></td></tr>";		/* end of html_header */
	html_end_box(true);
	form_hidden_box("id", (isset($device_template["id"]) ? $device_template["id"] : "0"), "");
	print "</form>";
}


function device_template_display_dq($device_template, $header_label) {
	require(CACTI_INCLUDE_PATH . "/data_query/data_query_arrays.php");

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='device_template_dq_edit'>\n";
	html_start_box(__("Associated Data Queries") . " $header_label", "100", "3", "center", "", true);
	print "<tr><td>";
	html_header(array(array("name" => __("Data Query Name")), array("name" => __("Re-Index Method"))), 2);

	/* list all data queries that are currently associated */
	$selected_data_queries = db_fetch_assoc("SELECT " .
			"snmp_query.id, " .
			"snmp_query.name, " .
			"device_template_snmp_query.reindex_method " .
			"FROM device_template_snmp_query " .
			"LEFT JOIN snmp_query ON (device_template_snmp_query.snmp_query_id = snmp_query.id) " .
			"WHERE device_template_snmp_query.device_template_id=" . $device_template["id"] . " " .
			"ORDER BY snmp_query.name");

	/* Now list all data queries, that have NOT yet been associated.
	 * This is to prevent duplicate assignments for the same data query
	 */
	$available_data_queries = db_fetch_assoc("SELECT " .
			"snmp_query.id, " .
			"snmp_query.name " .
			"FROM snmp_query " .
			"WHERE snmp_query.id NOT IN (" .
				"SELECT snmp_query.id " .
				"FROM device_template_snmp_query " .
				"LEFT JOIN snmp_query ON (device_template_snmp_query.snmp_query_id = snmp_query.id) " .
				"WHERE device_template_snmp_query.device_template_id=" . $device_template["id"] .
			") " .
			"ORDER BY snmp_query.name");

	$i = 0;
	if (sizeof($selected_data_queries) > 0) {
		foreach ($selected_data_queries as $item) {
			form_alternate_row_color("selected_data_query" . $item["id"], true);
			$i++;
			?>
				<td style="padding: 4px;"><strong><?php print $i;?>)</strong>
					<?php print $item["name"];?>
				</td>
				<td><?php form_dropdown("reindex_method_device_template_".get_request_var("id")."_query_".$item["id"]."_method_".$item["reindex_method"],$reindex_types,"","",$item["reindex_method"],"","","","");?>
				</td>
				<td align='right' nowrap><input
					type='button'
					value='<?php print __("Remove");?>'
					onClick='document.location="<?php print htmlspecialchars("device_templates.php?action=item_remove_dq&id=" . $item["id"] . "&device_template_id=" . $device_template["id"]);?>"'
					title='<?php print __("Delete Data Query Association");?>'>
				</td>
			<?php
			form_end_row();
		}
	}else{
		print "<tr><td><em>" . __("No associated data queries.") . "</em></td></tr>";
	}

	/* add new data queries */
	form_alternate_row_color("add_data_query", false);
	?>
	<td nowrap><?php form_dropdown("snmp_query_id",$available_data_queries,"name","id","","","");?>
	</td>
	<td nowrap><?php form_dropdown("reindex_method",$reindex_types,"","","1","","");?>
	</td>
	<td align="right">&nbsp;<input type="submit"
		value="<?php print __("Add Data Query");?>" name="add_dq_y" align="middle">
	</td>
	<?php
	form_end_row();

	/* update the reindex methods */
	form_alternate_row_color("reindex", false);
	?>
	<td nowrap colspan="3" align="right">&nbsp;<input type="submit"
		value="<?php print __("Update Re-Index Methods");?>" name="reindex">
	</td>
	<?php
	form_end_row();

	print "</table></td></tr>";		/* end of html_header */
	html_end_box(true);
	form_hidden_box("action", "save_dq", "save_dq");
	form_hidden_box("id", (isset($device_template["id"]) ? $device_template["id"] : "0"), "");
	print "</form>";
}

function device_templates_filter() {
	global $item_rows;

	html_start_box(__("Device Templates"), "100", "3", "center", "device_templates.php?action=edit", true);
	?>
	<tr class='rowAlternate3'>
		<td>
			<form name="form_device_template" action="device_templates.php">
			<table cellpadding="0" cellspacing="3">
				<tr>
					<td class="w1">
						<?php print __("Search:");?>
					</td>
					<td class="w1">
						<input type="text" name="filter" size="30" value="<?php print html_get_page_variable("filter");?>">
					</td>
					<td class="w1">
						<?php print __("Rows:");?>
					</td>
					<td class="w1">
						<select name="rows" onChange="applyFilterChange(document.form_device_template)">
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
					<td class="w1">
						<input type="submit" Value="<?php print __("Go");?>" name="go" align="middle">
						<input type="submit" Value="<?php print __("Clear");?>" name="clear" align="middle">
					</td>
				</tr>
			</table>
			<div><input type='hidden' name='page' value='1'></div>
			</form>
		</td>
	</tr>
	<?php
	html_end_box(false);
	?>
	<script type="text/javascript">
	<!--
	function applyFilterChange(objForm) {
		strURL = '?rows=' + objForm.rows.value;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}
	-->
	</script>
	<?php
}

function get_device_template_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	if (strlen(html_get_page_variable("filter"))) {
		$sql_where = "WHERE (device_template.name LIKE '%%" . html_get_page_variable("filter") . "%%')
			OR (device_template.description LIKE '%%" . html_get_page_variable("filter") . "%%')";
	}else{
		$sql_where = "";
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$total_rows = db_fetch_cell("SELECT
		COUNT(device_template.id)
		FROM device_template
		$sql_where");

	return db_fetch_assoc("SELECT *
		FROM device_template
		$sql_where
		ORDER BY " . html_get_page_variable('sort_column') . " " . html_get_page_variable('sort_direction') .
		" LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp);
}

function device_template_validate_cache() {
	$templates = db_fetch_assoc("SELECT * FROM device_template WHERE image=''");

	if (sizeof($templates)) {
	foreach($templates as $t) {
		device_template_update_cache($t["id"], device_template_get_image($t["image"]));
	}
	}
}

function device_template_update_cache($id, $image) {
	/* accomodate both URL and BASE paths */
	if (strpos($image, CACTI_URL_PATH) === false) {
		$image = str_replace(CACTI_URL_PATH, CACTI_BASE_PATH, $image);
	}
	copy($image, CACTI_CACHE_PATH . "/images/" . basename($image));
	db_execute("UPDATE device_template SET image='" . basename($image) . "' WHERE id=" . $id);
}

function device_template_get_image($image) {
	if ($image == '') {
		return CACTI_BASE_PATH . "/images/icons/tree/device.gif";
	}elseif (file_exists(CACTI_BASE_PATH . "/images/icons/tree/$image")){
		return CACTI_BASE_PATH . "/images/icons/tree/$image";
	}elseif (file_exists(CACTI_CACHE_PATH . "/images/$image")) {
		return CACTI_BASE_PATH . "/images/$image";
	}else{
		return CACTI_BASE_PATH . "/images/device.gif";
	}
}

function device_template_display_image($image) {
	return "<img src='" . CACTI_CACHE_URL_PATH . "/images/" . basename($image) . "' alt='' class='img_filter'>";
}

function device_template($refresh = true) {
	global $device_actions;

	device_template_validate_cache();

	$table = New html_table;

	$table->page_variables = array(
		"page"           => array("type" => "numeric", "method" => "request", "default" => "1"),
		"rows"           => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"filter"         => array("type" => "string",  "method" => "request", "default" => ""),
		"sort_column"    => array("type" => "string",  "method" => "request", "default" => "name"),
		"sort_direction" => array("type" => "string",  "method" => "request", "default" => "ASC")
	);

	$table->table_format = array(
		"name" => array(
			"name" => __("Template Title"),
			"filter" => true,
			"link" => true,
			"order" => "ASC"
		),
		"description" => array(
			"name" => __("Description"),
			"filter" => true,
			"link" => true,
			"order" => "ASC"
		),
		"nosort" => array(
			"name" => __("Availbility/SNMP Settings"),
			"function" => "display_device_template_control",
			"params" => array("override_defaults", "override_permitted"),
			"sort" => false
		),
		"image" => array(
			"name" => __("Image"),
			"sort" => false,
			"function" => "device_template_display_image",
			"params" => array("image"),
			"align" => "center"
		),
		"id" => array(
			"name" => __("ID"),
			"align" => "right",
			"order" => "ASC"
		)
	);

	/* initialize page behavior */
	$table->href           = "device_templates.php";
	$table->session_prefix = "sess_device_templates";
	$table->filter_func    = "device_templates_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = $device_actions;
	$table->table_id       = "device_templates";

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_device_template_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}

function display_device_template_control($override_defaults, $override_permitted) {
	return ($override_defaults == "on" ? __("Template controls Availability and SNMP") . ($override_permitted == "on" ? __(", User can override"):__(", Template propagation is forced")):__("Using System Defaults"));
}
