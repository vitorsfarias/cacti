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

include ("./include/auth.php");
include_once(CACTI_BASE_PATH . "/lib/tree.php");
include_once(CACTI_BASE_PATH . "/lib/html_tree.php");
include_once(CACTI_BASE_PATH . "/lib/utility.php");
include_once(CACTI_BASE_PATH . "/lib/template.php");

define("MAX_DISPLAY_PAGES", 21);

$ds_template_actions = array(
	ACTION_NONE => __("None"),
	"1" => __("Delete"),
	"2" => __("Duplicate")
	);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch (get_request_var_request("action")) {
	case 'save':
		data_source_template_save();

		break;
	case 'actions':
		data_source_template_form_actions();

		break;
	case 'rrd_add':
		template_rrd_add();

		break;
	case 'rrd_remove':
		template_rrd_remove();

		break;
	case 'template_remove':
		template_remove();

		header("Location: data_templates.php");
		break;
	case 'edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		data_source_template_edit();
		include_once (CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
	case 'ajax_view':
		data_source_template();

		break;
	default:
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		data_source_template();
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
}

/* --------------------------
    The Save Function
   -------------------------- */
/**
 * data_source_template_save	- save to data_template and data_template_data
 */
function data_source_template_save() {
	require(CACTI_BASE_PATH . "/include/data_source/data_source_arrays.php");

	if (isset($_POST["save_component_template"])) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("data_input_id"));
		input_validate_input_number(get_request_var_post("data_template_id"));
		/* ==================================================== */

		/* save: data_template */
		$save1["id"] = $_POST["data_template_id"];
		$save1["hash"] = get_hash_data_template($_POST["data_template_id"]);
		$save1["name"] = form_input_validate($_POST["template_name"], "template_name", "", false, 3);
		$save1["description"] = form_input_validate($_POST["description"], "description", "", true, 3);

		/* save: data_template_data */
		$save2["id"] = $_POST["data_template_data_id"];
		$save2["local_data_template_data_id"] = 0;
		$save2["local_data_id"] = 0;

		$save2["data_input_id"] = form_input_validate($_POST["data_input_id"], "data_input_id", "", true, 3);
		$save2["t_name"] = form_input_validate((isset($_POST["t_name"]) ? $_POST["t_name"] : ""), "t_name", "", true, 3);
		$save2["name"] = form_input_validate($_POST["name"], "name", "", (isset($_POST["t_name"]) ? true : false), 3);
		$save2["t_active"] = form_input_validate((isset($_POST["t_active"]) ? $_POST["t_active"] : ""), "t_active", "", true, 3);
		$save2["active"] = form_input_validate((isset($_POST["active"]) ? $_POST["active"] : ""), "active", "", true, 3);
		$save2["t_rrd_step"] = form_input_validate((isset($_POST["t_rrd_step"]) ? $_POST["t_rrd_step"] : ""), "t_rrd_step", "", true, 3);
		$save2["rrd_step"] = form_input_validate($_POST["rrd_step"], "rrd_step", "^[0-9]+$", (isset($_POST["t_rrd_step"]) ? true : false), 3);
		$save2["t_rra_id"] = form_input_validate((isset($_POST["t_rra_id"]) ? $_POST["t_rra_id"] : ""), "t_rra_id", "", true, 3);

		if (!is_error_message()) {
			$data_template_id = sql_save($save1, "data_template");

			if ($data_template_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		if (!is_error_message()) {
			$save2["data_template_id"] = $data_template_id;
			$data_template_data_id = sql_save($save2, "data_template_data");

			if ($data_template_data_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		/* update actual device template information for live devices in table data_template_data */
		if ((!is_error_message()) && ($save2["id"] > 0)) {
			db_execute("UPDATE data_template_data SET data_input_id = '" . $_POST["data_input_id"] . "' WHERE data_template_id = " . $_POST["data_template_id"] . ";");
		}

		if (!is_error_message()) {
			/* ok, first pull out all 'input' values so we know how much to save */
			$input_fields = db_fetch_assoc("SELECT " .
				"id, " .
				"input_output, " .
				"regexp_match, " .
				"allow_nulls, " .
				"type_code, " .
				"data_name " .
				"FROM data_input_fields " .
				"WHERE data_input_id=" . $_POST["data_input_id"] . " " .
				"AND input_output='in'");

			/* pass#1 for validation */
			if (sizeof($input_fields) > 0) {
				foreach ($input_fields as $input_field) {
					$form_value = "value_" . $input_field["data_name"];

					if ((isset($_POST[$form_value])) && ($input_field["type_code"] == "")) {
						if ((isset($_POST["t_" . $form_value])) &&
							(get_request_var_post("t_" . $form_value) == CHECKED)) {
							$not_required = true;
						}else if ($input_field["allow_nulls"] == CHECKED) {
							$not_required = true;
						}else{
							$not_required = false;
						}

						form_input_validate(get_request_var_post($form_value), "value_" . $input_field["data_name"], $input_field["regexp_match"], $not_required, 3);
					}
				}
			}

			/* save entries in 'selected rras' field */
			db_execute("DELETE FROM data_template_data_rra WHERE data_template_data_id=$data_template_data_id");

			if (isset($_POST["rra_id"])) {
				for ($i=0; ($i < count($_POST["rra_id"])); $i++) {
					/* ================= input validation ================= */
					input_validate_input_number($_POST["rra_id"][$i]);
					/* ==================================================== */

					db_execute("INSERT INTO data_template_data_rra (rra_id,data_template_data_id)
						VALUES (" . $_POST["rra_id"][$i] . ",$data_template_data_id)");
				}
			}

			if (!empty($_POST["data_template_id"])) {
				/* push out all data source settings to child data source using this template */
				push_out_data_source($data_template_data_id);

				db_execute("DELETE FROM data_input_data WHERE data_template_data_id=$data_template_data_id");

				reset($input_fields);
				if (sizeof($input_fields) > 0) {
				foreach ($input_fields as $input_field) {
					$form_value = "value_" . $input_field["data_name"];

					if (isset($_POST[$form_value])) {
						/* save the data into the 'device_template_data' table */
						if (isset($_POST{"t_value_" . $input_field["data_name"]})) {
							$template_this_item = CHECKED;
						}else{
							$template_this_item = "";
						}

						if ((!empty($form_value)) || (!empty($_POST{"t_value_" . $input_field["data_name"]}))) {
							db_execute("INSERT INTO data_input_data (data_input_field_id,data_template_data_id,t_value,value)
								VALUES (" . $input_field["id"] . ",$data_template_data_id,'$template_this_item','" . trim(get_request_var_post($form_value)) . "')");
						}
					}
				}
				}

				/* push out all "custom data" for this data source template */
				push_out_data_source_custom_data($data_template_id);
				push_out_device(0, 0, $data_template_id);
			}
		}

	}

	header("Location: data_templates.php?action=edit&id=" . (empty($data_template_id) ? $_POST["data_template_id"] : $data_template_id));
}

/* ------------------------
    The "actions" function
   ------------------------ */
/**
 * data_source_template_form_actions	- perform actions on a list of selected data templates
 */
function data_source_template_form_actions() {
	global $ds_template_actions;

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
				
				/* show all data sources that are in use by the current template to be deleted
				 * remember: "real" data sources are those with a lical_data_id > 0 */
				$in_use = db_fetch_assoc("SELECT * FROM data_template_data WHERE data_template_id=$template_id  AND local_data_id > 0");
				if (sizeof($in_use)) {
					/* create an array of bad id's; we will need that later for printing a himan readable error messsage 
					 * index is the current template that shall be deleted
					 * remember that we may delete multiple templates in one run*/
					$bad_ids[$template_id] = array();
					foreach ($in_use as $data_template_data) {
						/* for the given data template, save 
						 * $bad_ids = array(
						 *   data_template_id = array(			-- all failing data templates, per id
						 *     data_template_data_id = array(   -- per data template: all failing data sources, per id
						 *       name)));                       -- per data source: name of the data source
						 */
						$bad_ids[$template_id][$data_template_data["id"]] = $data_template_data["name_cache"];
					}
				}else{
					$template_ids[] = $template_id;
				}
			}
			}

			if (isset($bad_ids)) {
				$message = __("Following Data Templates were not removed because they are still in use by some Data Source." . "<br><ul>");
				foreach($bad_ids as $template_id => $data_template_data) {
					$message .= "<li>" . __("Data Source Template " . $template_id . " is in use and can not be removed") . "<ul>";
					foreach ($data_template_data as $data_source_id => $name_cache) {
						$message .=  "<li>" . __("Data Template Id: ") . " " . $template_id . " ";
						$message .=  __("Data Source Id: ") . " " . $data_source_id . " ";
						$message .=  __("Data Source Name: '") . " " . $name_cache . "'</li>";
					}
					$message .= "</li>";
				}
				$message .= "</li>";

				$_SESSION['sess_message_dt_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');

				raise_message('dt_ref_int');
			}

			if (isset($template_ids)) {
				$data_template_datas = db_fetch_assoc("SELECT id FROM data_template_data WHERE " . array_to_sql_or($template_ids, "data_template_id") . " AND local_data_id=0");

				if (sizeof($data_template_datas) > 0) {
				foreach ($data_template_datas as $data_template_data) {
					db_execute("DELETE FROM data_template_data_rra WHERE data_template_data_id=" . $data_template_data["id"]);
				}
				}

				db_execute("DELETE FROM data_template_data WHERE " . array_to_sql_or($template_ids, "data_template_id") . " AND local_data_id=0");
				db_execute("DELETE FROM data_template_rrd WHERE " . array_to_sql_or($template_ids, "data_template_id") . " AND local_data_id=0");
				db_execute("DELETE FROM snmp_query_graph_rrd WHERE " . array_to_sql_or($template_ids, "data_template_id"));
				db_execute("DELETE FROM snmp_query_graph_rrd_sv WHERE " . array_to_sql_or($template_ids, "data_template_id"));
				db_execute("DELETE FROM data_template WHERE " . array_to_sql_or($template_ids, "id"));

				/* "undo" any graph that is currently using this template */
				db_execute("UPDATE data_template_data set local_data_template_data_id=0,data_template_id=0 WHERE " . array_to_sql_or($template_ids, "data_template_id"));
				db_execute("UPDATE data_template_rrd set local_data_template_rrd_id=0,data_template_id=0 WHERE " . array_to_sql_or($template_ids, "data_template_id"));
				db_execute("UPDATE data_local set data_template_id=0 WHERE " . array_to_sql_or($template_ids, "data_template_id"));
			}
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				duplicate_data_source(0, $selected_items[$i], get_request_var_post("title_format"));
			}
		}

		header("Location: data_templates.php");
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

			$ds_list .= "<li>" . db_fetch_cell("select name from data_template where id=" . $matches[1]) . "</li>";
			$ds_array[] = $matches[1];
		}
	}

	print "<form id='tactions' name='tactions' action='data_templates.php' method='post'>\n";

	html_start_box("", "100", "3", "center", "");

	if (sizeof($ds_array)) {
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
						<p>" . __("When  you click 'Continue',  the following Data Template(s) will be deleted.  Any Data Source(s) attached to these Data Template(s) will become individual Data Source(s).") . "</p>
						<div class='action_list'><ul>$ds_list</ul></div>
					</td>
				</tr>\n";

			$title = __("Delete Data Template(s)");
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Data Template(s) will be duplicated. You can optionally change the title format for the new Data Template(s).") . "</p>
						<div class='action_list'><ul>$ds_list</ul></div>
						<p><strong>" . __("Title Format:") . "</strong><br>"; form_text_box("title_format", "<template_title> (1)", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n";

			$title = __("Duplicate Data Template(s)");
		}
	} else {
		print "	<tr>
				<td class='textArea'>
					<p>" . __("You must first select a Data Source Template.  Please select 'Return' to return to the previous menu.") . "</p>
				</td>
			</tr>\n";

		$title = __("Selection Error");
	}

	if (!sizeof($ds_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button();
	}else{
		form_continue(serialize($ds_array), get_request_var_post("drp_action"), $title, "tactions");
	}

	html_end_box();
}

/* ----------------------------
    template - Data Source Templates
   ---------------------------- */
/**
 * template_rrd_add	 - obsolete?
 */
function template_rrd_add() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("local_data_id"));
	/* ==================================================== */

	$hash = get_hash_data_template(0, "data_template_item");

	db_execute("insert into data_template_rrd (hash,data_template_id,rrd_maximum,rrd_minimum,rrd_heartbeat,data_source_type_id,
		data_source_name) values ('$hash'," . get_request_var("id") . ",100,0,600,1,'ds')");
	$data_template_rrd_id = db_fetch_insert_id();

	/* add this data template item to each data source using this data template */
	$children = db_fetch_assoc("select local_data_id from data_template_data where data_template_id=" . $_GET["id"] . " and local_data_id>0");

	if (sizeof($children) > 0) {
	foreach ($children as $item) {
		db_execute("insert into data_template_rrd (local_data_template_rrd_id,local_data_id,data_template_id,rrd_maximum,rrd_minimum,rrd_heartbeat,data_source_type_id,
			data_source_name) values ($data_template_rrd_id," . $item["local_data_id"] . "," . get_request_var("id") . ",100,0,600,1,'ds')");
	}
	}

	header("Location: data_templates.php?action=edit&id=" . $_GET["id"] . "&view_rrd=$data_template_rrd_id");
	exit;
}

/**
 * data_source_template_edit	- edit the data template
 */
function data_source_template_edit() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("view_rrd"));
	/* ==================================================== */

	$data_template_tabs = array(
		"general" 		=> __("General"),
		"items" 		=> __("Items"),
		"datasources" 	=> __("Data Sources")
	);

	if (!empty($_GET["id"])) {
		$data_template = db_fetch_row("SELECT * FROM data_template WHERE id=" . $_REQUEST["id"]);
		$header_label = __("[edit: ") . $data_template["name"] . "]";
		if (!db_fetch_cell("SELECT COUNT(*) FROM data_local WHERE data_template_id=" . $_REQUEST["id"])) {
			unset($data_template_tabs["datasources"]);
		}
	}else{
		$data_template = array();
		$header_label = __("[new]");
		unset($data_template_tabs["datasources"]);
	}

	/* set the default settings category */
	if (!isset($_REQUEST["tab"])) {
		/* there is no selected tab; select the first one */
		$current_tab = array_keys($data_template_tabs);
		$current_tab = $current_tab[0];
	}else{
		$current_tab = $_REQUEST["tab"];
	}

	/* draw the categories tabs on the top of the page */
	print "<table width='100%' cellspacing='0' cellpadding='0' align='center'><tr>";
	print "<td><div class='tabs'>";

	if (sizeof($data_template_tabs) > 0) {
		foreach (array_keys($data_template_tabs) as $tab_short_name) {
			print "<div class='tabDefault'><a " . (($tab_short_name == $current_tab) ? "class='tabSelected'" : "class='tabDefault'") . " href='" . htmlspecialchars("data_templates.php?action=edit" . (isset($_REQUEST['id']) ? "&id=" . $_REQUEST['id'] . "&template_id=" . $_REQUEST['id']: "") . "&filter=&device_id=-1&tab=$tab_short_name") . "'>$data_template_tabs[$tab_short_name]</a></div>";

			if (!isset($_REQUEST["id"])) break;
		}
	}
	print "</div></td></tr></table>";

	if (!isset($_REQUEST["tab"])) {
		$_REQUEST["tab"] = "general";
	}

	switch (get_request_var_request("tab")) {
		case "datasources":
			include_once(CACTI_BASE_PATH . "/lib/data_source/data_source_form.php");
			include_once(CACTI_BASE_PATH . "/lib/utility.php");
			include_once(CACTI_BASE_PATH . "/lib/graph.php");
			include_once(CACTI_BASE_PATH . "/lib/data_source.php");
			include_once(CACTI_BASE_PATH . "/lib/template.php");
			include_once(CACTI_BASE_PATH . "/lib/html_form_template.php");
			include_once(CACTI_BASE_PATH . "/lib/rrd.php");
			include_once(CACTI_BASE_PATH . "/lib/data_query.php");

			data_source();

			break;

		case "items":
			/* graph item list goes here */
			if (!empty($_GET["id"])) {
				data_template_display_items();
			}

			break;
		default:
			data_source_template_display_general($data_template, $header_label);

			break;
	}
}

function data_source_template_display_general($data_template, $header_label) {
	require_once(CACTI_BASE_PATH . "/lib/data_source.php");
	require_once(CACTI_BASE_PATH . "/lib/data_template.php");

	# fetch all settings for this graph template
	if (isset($data_template["id"])) {
		$template_data = db_fetch_row("SELECT * FROM data_template_data WHERE data_template_id=" . $data_template["id"] . " AND local_data_id=0");
	}else {
		$template_data = array();
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='data_data_source_template_edit'>\n";

	# the template header
	html_start_box(__("Data Source Template") . " $header_label", "100", 0, "center", "", true);

	draw_edit_form(array(
		"config" => array("no_form_tag" => true),
		"fields" => inject_form_variables(data_template_form_list(), (isset($data_template) ? $data_template : array()), (isset($template_data) ? $template_data : array()))
		));

	html_end_box(false);
	form_hidden_box("data_template_id", (isset($template_data["data_template_id"]) ? $template_data["data_template_id"]: "0"), "0");
	form_hidden_box("data_template_data_id", (isset($template_data["id"]) ? $template_data["id"]: "0"), "0");
	form_hidden_box("current_rrd", (isset($_GET["current_rrd"]) ? $_GET["current_rrd"] : "0"), "0");
	form_hidden_box("save_component_template", 1, "");


	html_start_box(__("Data Source"), "100", 0, "center", "", true);
	draw_template_edit_form('header_data_source', data_source_form_list(), $template_data, false);
	html_end_box();


	$i = 0;
	if (!empty($_GET["id"])) {
		/* get each INPUT field for this data input source */
		$fields = db_fetch_assoc("SELECT * FROM data_input_fields WHERE data_input_id=" . $template_data["data_input_id"] . " AND input_output='in' ORDER BY name");

		html_start_box(__("Custom Data") . " " . __("[data input:") . " " . db_fetch_cell("SELECT name FROM data_input WHERE id=" . $template_data["data_input_id"]) . "]", "100", 0, "center", "", true);

		/* loop through each field found */
		if (sizeof($fields) > 0) {
		foreach ($fields as $field) {
			$data_input_data = db_fetch_row("SELECT t_value,value FROM data_input_data WHERE data_template_data_id=" . $template_data["id"] . " AND data_input_field_id=" . $field["id"]);

			if (sizeof($data_input_data) > 0) {
				$old_value = $data_input_data["value"];
			}else{
				$old_value = "";
				/* initialize data in case there's no data input data for this very input field */
				$data_input_data["t_value"] = "";
				$data_input_data["value"] = "";
			}

			form_alternate_row_color("custom_data" . $field["id"]); ?>
				<td class='template_checkbox'>
					<strong><?php print $field["name"];?></strong><br>
					<?php form_checkbox("t_value_" . $field["data_name"], $data_input_data["t_value"], "<em>Use Per-Data Source Value (Ignore this Value)</em>", "", "", get_request_var("id"));?>
				</td>
				<td>
					<?php form_text_box("value_" . $field["data_name"],$old_value,"","");?>
					<?php if ((preg_match('/^' . VALID_HOST_FIELDS . '$/i', $field["type_code"])) && ($data_input_data["t_value"] == "")) { print "<br><em>Value will be derived from the device if this field is left empty.</em>\n"; } ?>
				</td>
			<?php
			form_end_row();
		}
		}else{
			print "<tr><td><em>" . __("No Input Fields for the Selected Data Input Source") . "</em></td></tr>";
		}

		html_end_box(false);
	}

	form_save_button("data_templates.php", "return");
}

/**
 * data_source_template_item	- list all data template items
 */
function data_template_display_items() {
	require(CACTI_BASE_PATH . "/include/graph/graph_arrays.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$template = db_fetch_row("SELECT * FROM data_template WHERE id=" . $_REQUEST["id"]);
		$template_item_list = db_fetch_assoc("SELECT * FROM data_template_rrd WHERE data_template_id=" . $_REQUEST["id"] . " AND local_data_id=0 ORDER BY data_source_name");
		$header_label = __("[edit: ") . $template["name"] . "]";
	}else{
		$template_item_list = array();
		$header_label = __("[new]");
	}

	html_start_box(__("Data Source Items") . " $header_label", "100", "0", "center", "data_templates_items.php?action=item_edit&data_template_id=" . $_REQUEST["id"], true);
	draw_data_template_items_list($template_item_list, "data_templates_items.php", "data_template_id=" . $_REQUEST["id"], false);
	html_end_box(true);
	form_save_button("data_templates.php", "return");
}

function data_templates_filter() {
	global $item_rows;

	html_start_box(__("Data Source Templates"), "100", "3", "center", "data_templates.php?action=edit", true);
	?>
	<tr class='rowAlternate3'>
		<td>
			<form name="form_data_template" action="data_templates.php">
			<table cellpadding="0" cellspacing="3">
				<tr>
					<td class="nw50">
						&nbsp;<?php print __("Search:");?>&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="40" value="<?php print html_get_page_variable("filter");?>">
					</td>
					<td class="nw50">
						&nbsp;<?php print __("Rows:");?>&nbsp;
					</td>
					<td width="1">
						<select name="rows" onChange="applyFilterChange(document.form_data_template)">
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
						&nbsp;<input type="submit" Value="<?php print __("Go");?>" name="go" align="middle">
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

function get_data_template_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	if (html_get_page_variable("filter") != "") {
		$sql_where = "WHERE ((data_template.name like '%%" . html_get_page_variable("filter") . "%%')
			OR (data_template.description LIKE '%%" . html_get_page_variable("filter") . "%%'))
			AND data_template_data.local_data_id = 0";
	}else{
		$sql_where = "WHERE data_template_data.local_data_id = 0";
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$total_rows = db_fetch_cell("SELECT " .
		"COUNT(data_template.id) " .
		"FROM data_template " .
		"LEFT JOIN data_template_data ON (data_template.id = data_template_data.data_template_id) " .
		$sql_where);

	return db_fetch_assoc("SELECT " .
		"data_template.id, " .
		"data_template.name, " .
		"data_template.description, " .
		"data_input.name AS data_input_method, " .
		"data_template_data.active AS active " .
		"FROM data_template " .
		"LEFT JOIN data_template_data ON (data_template.id = data_template_data.data_template_id) " .
		"LEFT JOIN data_input ON (data_template_data.data_input_id = data_input.id) " .
		$sql_where .
		" ORDER BY " . html_get_page_variable('sort_column') . " " . html_get_page_variable('sort_direction') .
		" LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp);
}

/**
 * data_source_template	- show all data templates
 */
function data_source_template($refresh = true) {
	global $ds_template_actions;

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
			"name" => __("Template Name"),
			"filter" => true,
			"link" => true,
			"order" => "ASC"
		),
		"description" => array(
			"name" => ("Description"),
			"filter" => true,
			"link" => true,
			"order" => "ASC"
		),
		"id" => array(
			"name" => __("ID"),
			"order" => "ASC"
		),
		"data_input_method" => array(
			"name" => __("Data Input Method"),
			"emptyval" => "<em>None</em>",
			"order" => "ASC"
		),
		"active" => array(
			"name" => __("Status"),
			"function" => "display_checkbox_status",
			"params" => array("active"),
			"order" => "ASC"
		)
	);

	/* initialize page behavior */
	$table->href           = "data_templates.php";
	$table->session_prefix = "sess_data_templates";
	$table->filter_func    = "data_templates_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = $ds_template_actions;

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_data_template_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}
