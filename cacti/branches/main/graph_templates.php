<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2009 The Cacti Group                                 |
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
include_once(CACTI_BASE_PATH . "/lib/utility.php");
include_once(CACTI_BASE_PATH . "/lib/template.php");
include_once(CACTI_BASE_PATH . "/lib/tree.php");
include_once(CACTI_BASE_PATH . "/lib/html_tree.php");

define("MAX_DISPLAY_PAGES", 21);

$graph_actions = array(
	1 => __("Delete"),
	2 => __("Duplicate")
	);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }


switch ($_REQUEST["action"]) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'template_remove':
		template_remove();

		header("Location: graph_templates.php");
		break;
	case 'input_remove':
		input_remove();

		header("Location: graph_templates.php?action=template_edit&id=" . $_GET["graph_template_id"]);
		break;
	case 'input_edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		input_edit();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
	case 'template_edit':
		include_once (CACTI_BASE_PATH . "/include/top_header.php");

		template_edit();

		include_once (CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
	default:
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		template();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if (isset($_POST["save_component_template"])) {
		$save1["id"] = $_POST["graph_template_id"];
		$save1["hash"] = get_hash_graph_template($_POST["graph_template_id"]);
		$save1["name"] = form_input_validate($_POST["name"], "name", "", false, 3);

		$save2["id"] = $_POST["graph_template_graph_id"];
		$save2["local_graph_template_graph_id"] = 0;
		$save2["local_graph_id"] = 0;
		$save2["t_image_format_id"] = (isset($_POST["t_image_format_id"]) ? $_POST["t_image_format_id"] : "");
		$save2["image_format_id"] = form_input_validate($_POST["image_format_id"], "image_format_id", "", true, 3);
		$save2["t_title"] = form_input_validate((isset($_POST["t_title"]) ? $_POST["t_title"] : ""), "t_title", "", true, 3);
		$save2["title"] = form_input_validate($_POST["title"], "title", "", (isset($_POST["t_title"]) ? true : false), 3);
		$save2["t_height"] = form_input_validate((isset($_POST["t_height"]) ? $_POST["t_height"] : ""), "t_height", "", true, 3);
		$save2["height"] = form_input_validate($_POST["height"], "height", "^[0-9]+$", (isset($_POST["t_height"]) ? true : false), 3);
		$save2["t_width"] = form_input_validate((isset($_POST["t_width"]) ? $_POST["t_width"] : ""), "t_width", "", true, 3);
		$save2["width"] = form_input_validate($_POST["width"], "width", "^[0-9]+$", (isset($_POST["t_width"]) ? true : false), 3);
		$save2["t_upper_limit"] = form_input_validate((isset($_POST["t_upper_limit"]) ? $_POST["t_upper_limit"] : ""), "t_upper_limit", "", true, 3);
		$save2["upper_limit"] = form_input_validate($_POST["upper_limit"], "upper_limit", "", (isset($_POST["t_upper_limit"]) ? true : false), 3);
		$save2["t_lower_limit"] = form_input_validate((isset($_POST["t_lower_limit"]) ? $_POST["t_lower_limit"] : ""), "t_lower_limit", "", true, 3);
		$save2["lower_limit"] = form_input_validate($_POST["lower_limit"], "lower_limit", "", (isset($_POST["t_lower_limit"]) ? true : false), 3);
		$save2["t_vertical_label"] = form_input_validate((isset($_POST["t_vertical_label"]) ? $_POST["t_vertical_label"] : ""), "t_vertical_label", "", true, 3);
		$save2["vertical_label"] = form_input_validate($_POST["vertical_label"], "vertical_label", "", true, 3);
		$save2["t_slope_mode"] = form_input_validate((isset($_POST["t_slope_mode"]) ? $_POST["t_slope_mode"] : ""), "t_slope_mode", "", true, 3);
		$save2["slope_mode"] = form_input_validate((isset($_POST["slope_mode"]) ? $_POST["slope_mode"] : ""), "slope_mode", "", true, 3);
		$save2["t_auto_scale"] = form_input_validate((isset($_POST["t_auto_scale"]) ? $_POST["t_auto_scale"] : ""), "t_auto_scale", "", true, 3);
		$save2["auto_scale"] = form_input_validate((isset($_POST["auto_scale"]) ? $_POST["auto_scale"] : ""), "auto_scale", "", true, 3);
		$save2["t_auto_scale_opts"] = form_input_validate((isset($_POST["t_auto_scale_opts"]) ? $_POST["t_auto_scale_opts"] : ""), "t_auto_scale_opts", "", true, 3);
		$save2["auto_scale_opts"] = form_input_validate($_POST["auto_scale_opts"], "auto_scale_opts", "", true, 3);
		$save2["t_auto_scale_log"] = form_input_validate((isset($_POST["t_auto_scale_log"]) ? $_POST["t_auto_scale_log"] : ""), "t_auto_scale_log", "", true, 3);
		$save2["auto_scale_log"] = form_input_validate((isset($_POST["auto_scale_log"]) ? $_POST["auto_scale_log"] : ""), "auto_scale_log", "", true, 3);
		$save2["t_scale_log_units"] = form_input_validate((isset($_POST["t_scale_log_units"]) ? $_POST["t_scale_log_units"] : ""), "t_scale_log_units", "", true, 3);
		$save2["scale_log_units"] = form_input_validate((isset($_POST["scale_log_units"]) ? $_POST["scale_log_units"] : ""), "scale_log_units", "", true, 3);
		$save2["t_auto_scale_rigid"] = form_input_validate((isset($_POST["t_auto_scale_rigid"]) ? $_POST["t_auto_scale_rigid"] : ""), "t_auto_scale_rigid", "", true, 3);
		$save2["auto_scale_rigid"] = form_input_validate((isset($_POST["auto_scale_rigid"]) ? $_POST["auto_scale_rigid"] : ""), "auto_scale_rigid", "", true, 3);
		$save2["t_alt_y_grid"] = form_input_validate((isset($_POST["t_alt_y_grid"]) ? $_POST["t_alt_y_grid"] : ""), "t_alt_y_grid", "", true, 3);
		$save2["alt_y_grid"] = form_input_validate((isset($_POST["alt_y_grid"]) ? $_POST["alt_y_grid"] : ""), "alt_y_grid", "", true, 3);
		$save2["t_auto_padding"] = form_input_validate((isset($_POST["t_auto_padding"]) ? $_POST["t_auto_padding"] : ""), "t_auto_padding", "", true, 3);
		$save2["auto_padding"] = form_input_validate((isset($_POST["auto_padding"]) ? $_POST["auto_padding"] : ""), "auto_padding", "", true, 3);
		$save2["t_base_value"] = form_input_validate((isset($_POST["t_base_value"]) ? $_POST["t_base_value"] : ""), "t_base_value", "", true, 3);
		$save2["base_value"] = form_input_validate($_POST["base_value"], "base_value", "^[0-9]+$", (isset($_POST["t_base_value"]) ? true : false), 3);
		$save2["t_export"] = form_input_validate((isset($_POST["t_export"]) ? $_POST["t_export"] : ""), "t_export", "", true, 3);
		$save2["export"] = form_input_validate((isset($_POST["export"]) ? $_POST["export"] : ""), "export", "", true, 3);
		$save2["t_unit_value"] = form_input_validate((isset($_POST["t_unit_value"]) ? $_POST["t_unit_value"] : ""), "t_unit_value", "", true, 3);
		$save2["unit_value"] = form_input_validate($_POST["unit_value"], "unit_value", "", true, 3);
		$save2["t_unit_exponent_value"] = form_input_validate((isset($_POST["t_unit_exponent_value"]) ? $_POST["t_unit_exponent_value"] : ""), "t_unit_exponent_value", "", true, 3);
		$save2["unit_exponent_value"] = form_input_validate($_POST["unit_exponent_value"], "unit_exponent_value", "^-?[0-9]+$", true, 3);

		if (!is_error_message()) {
			$graph_template_id = sql_save($save1, "graph_templates");

			if ($graph_template_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		if (!is_error_message()) {
			$save2["graph_template_id"] = $graph_template_id;
			$graph_template_graph_id = sql_save($save2, "graph_templates_graph");

			if ($graph_template_graph_id) {
				raise_message(1);

				push_out_graph($graph_template_graph_id);
			}else{
				raise_message(2);
			}
		}
	}

	if ((is_error_message()) || (empty($_POST["graph_template_id"]))) {
		header("Location: graph_templates.php?action=template_edit&id=" . (empty($graph_template_id) ? $_POST["graph_template_id"] : $graph_template_id));
	}else{
		header("Location: graph_templates.php");
	}
}

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $colors, $graph_actions;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if ($_POST["drp_action"] == "1") { /* delete */
			db_execute("delete from graph_templates where " . array_to_sql_or($selected_items, "id"));

			$graph_template_input = db_fetch_assoc("select id from graph_template_input where " . array_to_sql_or($selected_items, "graph_template_id"));

			if (sizeof($graph_template_input) > 0) {
			foreach ($graph_template_input as $item) {
				db_execute("delete from graph_template_input_defs where graph_template_input_id=" . $item["id"]);
			}
			}

			db_execute("delete from graph_template_input where " . array_to_sql_or($selected_items, "graph_template_id"));
			db_execute("delete from graph_templates_graph where " . array_to_sql_or($selected_items, "graph_template_id") . " and local_graph_id=0");
			db_execute("delete from graph_templates_item where " . array_to_sql_or($selected_items, "graph_template_id") . " and local_graph_id=0");
			db_execute("delete from host_template_graph where " . array_to_sql_or($selected_items, "graph_template_id"));

			/* "undo" any graph that is currently using this template */
			db_execute("update graph_templates_graph set local_graph_template_graph_id=0,graph_template_id=0 where " . array_to_sql_or($selected_items, "graph_template_id"));
			db_execute("update graph_templates_item set local_graph_template_item_id=0,graph_template_id=0 where " . array_to_sql_or($selected_items, "graph_template_id"));
			db_execute("update graph_local set graph_template_id=0 where " . array_to_sql_or($selected_items, "graph_template_id"));
		}elseif ($_POST["drp_action"] == "2") { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				duplicate_graph(0, $selected_items[$i], $_POST["title_format"]);
			}
		}

		header("Location: graph_templates.php");
		exit;
	}

	/* setup some variables */
	$graph_list = ""; $i = 0; $graph_array = array();

	/* loop through each of the graphs selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (ereg("^chk_([0-9]+)$", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$graph_list .= "<li>" . db_fetch_cell("select name from graph_templates where id=" . $matches[1]) . "<br>";
			$graph_array[$i] = $matches[1];
		}

		$i++;
	}

	include_once(CACTI_BASE_PATH . "/include/top_header.php");

	html_start_box("<strong>" . $graph_actions{$_POST["drp_action"]} . "</strong>", "60%", $colors["header_panel"], "3", "center", "");

	print "<form action='graph_templates.php' method='post'>\n";

	if (sizeof($graph_array)) {
		if ($_POST["drp_action"] == "1") { /* delete */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("Are you sure you want to delete the following graph templates? Any graphs attached to these templates will become individual graphs.") . "</p>
						<p>$graph_list</p>
					</td>
				</tr>\n
				";
		}elseif ($_POST["drp_action"] == "2") { /* duplicate */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click save, the following graph templates will be duplicated. You can optionally change the title format for the new graph templates.") . "</p>
						<p>$graph_list</p>
						<p><strong>" . __("Title Format:") . "</strong><br>"; form_text_box("title_format", "<template_title> (1)", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n
				";
		}
	} else {
		print "	<tr>
				<td class='textArea'>
					<p>" . __("You must first select a Graph Template.  Please select 'Return' to return to the previous menu.") . "</p>
				</td>
			</tr>\n";
	}

	if (!sizeof($graph_array)) {
		form_return_button_alt();
	}else{
		form_yesno_button_alt(serialize($graph_array), $_POST["drp_action"]);
	}

	html_end_box();

	include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
}

function item() {
	global $colors, $consolidation_functions, $graph_item_types;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (empty($_GET["id"])) {
		$template_item_list = array();

		$header_label = __("[new]");
	}else{
		$template_item_list = db_fetch_assoc("select
			graph_templates_item.id,
			graph_templates_item.text_format,
			graph_templates_item.value,
			graph_templates_item.hard_return,
			graph_templates_item.graph_type_id,
			graph_templates_item.consolidation_function_id,
			CONCAT_WS(' - ',data_template_data.name,data_template_rrd.data_source_name) as data_source_name,
			cdef.name as cdef_name,
			colors.hex,
			graph_templates_gprint.name as gprint_name
			from graph_templates_item
			left join data_template_rrd on (graph_templates_item.task_item_id=data_template_rrd.id)
			left join data_local on (data_template_rrd.local_data_id=data_local.id)
			left join data_template_data on (data_local.id=data_template_data.local_data_id)
			left join cdef on (cdef_id=cdef.id)
			left join colors on (color_id=colors.id)
			left join graph_templates_gprint on (gprint_id=graph_templates_gprint.id)
			where graph_templates_item.graph_template_id=" . $_GET["id"] . "
			and graph_templates_item.local_graph_id=0
			order by graph_templates_item.sequence");

		$header_label = __("[edit: ") . db_fetch_cell("select name from graph_templates where id=" . $_GET["id"]) . "]";
	}

	html_start_box("<strong>" . __("Graph Template Items") . "</strong> $header_label", "100%", $colors["header"], "0", "center", "graph_templates_items.php?action=item_edit&graph_template_id=" . $_GET["id"]);
	draw_graph_items_list($template_item_list, "graph_templates_items.php", "graph_template_id=" . $_GET["id"], false);
	html_end_box(false);

	html_start_box("<strong>" . __("Graph Item Inputs") . "</strong>", "100%", $colors["header"], "3", "center", "graph_templates_inputs.php?action=input_edit&graph_template_id=" . $_GET["id"]);

	print "<tr class='rowSubHeader'>";
		DrawMatrixHeaderItem(__("Name"), $colors["header_text"], 2);
	print "</tr>";

	$template_item_list = db_fetch_assoc("select id,name from graph_template_input where graph_template_id=" . $_GET["id"] . " order by name");

	if (sizeof($template_item_list) > 0) {
	foreach ($template_item_list as $item) {
		form_alternate_row_color("item" . $item["id"]);
		?>
			<td>
				<a class="linkEditMain" href='<?php print htmlspecialchars("graph_templates_inputs.php?action=input_edit&id=" . $item["id"] . "&graph_template_id=" . $_GET["id"]);?>'><?php print $item["name"];?></a>
			</td>
			<td align="right">
				<a href='<?php print htmlspecialchars("graph_templates_inputs.php?action=input_remove&id=" . $item["id"] . "&graph_template_id=" . $_GET["id"]);?>'><img class="buttonSmall" src="images/delete_icon.gif" alt="<?php print __("Delete");?>" align='middle'></a>
			</td>
		<?php
		form_end_row();
	}
	}else{
		print "<tr class='rowAlternate1'><td colspan='2'><em>" . __("No Inputs") . "</em></td></tr>";
	}

	html_end_box();
}

/* ----------------------------
    template - Graph Templates
   ---------------------------- */

function template_edit() {
	global $colors, $struct_graph, $image_types, $fields_graph_template_template_edit;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='template_edit'>\n";

	/* graph item list goes here */
	if (!empty($_GET["id"])) {
		item();
	}

	if (!empty($_GET["id"])) {
		$template = db_fetch_row("select * from graph_templates where id=" . $_GET["id"]);
		$template_graph = db_fetch_row("select * from graph_templates_graph where graph_template_id=" . $_GET["id"] . " and local_graph_id=0");

		$header_label = __("[edit: ") . $template["name"] . "]";
	}else{
		$header_label = __("[new]");
	}

	html_start_box("<strong>" . __("Template") . "</strong> $header_label", "100%", $colors["header"], "0", "center", "");
	$header_items = array(__("Field"), __("Value"));
	print "<tr><td>";
	html_header($header_items, 1, true, 'header_template');

	draw_edit_form(array(
		"config" => array(),
		"fields" => inject_form_variables($fields_graph_template_template_edit, (isset($template) ? $template : array()), (isset($template_graph) ? $template_graph : array()))
		));

	print "</table></td></tr>";		/* end of html_header */
	html_end_box();

	html_start_box("<strong>" . __("Graph Template") . "</strong>", "100%", $colors["header"], "0", "center", "");
	$header_items = array(__("Field"), __("Value"));
	print "<tr><td>";
	html_header($header_items, 1, true, 'header_graph_template');

	$form_array = array();

	while (list($field_name, $field_array) = each($struct_graph)) {
		$form_array += array($field_name => $struct_graph[$field_name]);

		$form_array[$field_name]["value"] = (isset($template_graph) ? $template_graph[$field_name] : "");
		$form_array[$field_name]["form_id"] = (isset($template_graph) ? $template_graph["id"] : "0");
#		$form_array[$field_name]["description"] = "";
		$form_array[$field_name]["sub_checkbox"] = array(
			"name" => "t_" . $field_name,
			"friendly_name" => "<em>" . __("Use Per-Graph Value (Ignore this Value)") . "</em>",
			"value" => (isset($template_graph) ? $template_graph{"t_" . $field_name} : ""),
			"class" => (isset($form_array[$field_name]["class"]) ? $form_array[$field_name]["class"] : "")
		);
	}

	draw_edit_form(
		array(
			"config" => array(
				),
			"fields" => $form_array
			)
		);

	print "</table></td></tr>";		/* end of html_header */
	html_end_box();
	form_hidden_box("rrdtool_version", read_config_option("rrdtool_version"), "");

	form_save_button_alt();
?>
<script type="text/javascript">
	$('#graph_item').tableDnD({
		onDrop: function(table, row) {
//			alert($.tableDnD.serialize());
			$('#AjaxResult').load("lib/ajax/jquery.tablednd/graph_templates_item.ajax.php?id=<?php print $_GET["id"];?>&"+$.tableDnD.serialize());
		}
	});
</script>
<?php

	include_once(CACTI_BASE_PATH . "/lib/jquery/field_description_hover.js");

//Now we need some javascript to make it dynamic
?>
<script type="text/javascript">

dynamic();

function dynamic() {
	//alert("RRDTool Version is '" + document.getElementById('rrdtool_version').value + "'");
	//alert("Log is '" + document.getElementById('auto_scale_log').checked + "'");
	document.getElementById('t_scale_log_units').disabled=true;
	document.getElementById('scale_log_units').disabled=true;
	if ((document.getElementById('rrdtool_version').value != 'rrd-1.0.x') &&
		(document.getElementById('auto_scale_log').checked)) {
		document.getElementById('t_scale_log_units').disabled=false;
		document.getElementById('scale_log_units').disabled=false;
	}
}

function changeScaleLog() {
	//alert("Log changed to '" + document.getElementById('auto_scale_log').checked + "'");
	document.getElementById('t_scale_log_units').disabled=true;
	document.getElementById('scale_log_units').disabled=true;
	if ((document.getElementById('rrdtool_version').value != 'rrd-1.0.x') &&
		(document.getElementById('auto_scale_log').checked)) {
		document.getElementById('t_scale_log_units').disabled=false;
		document.getElementById('scale_log_units').disabled=false;
	}
}
</script>
<?php

}

function template() {
	global $colors, $graph_actions;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("page"));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var("filter"));
	}

	/* clean up sort_column string */
	if (isset($_REQUEST["sort_column"])) {
		$_REQUEST["sort_column"] = sanitize_search_string(get_request_var("sort_column"));
	}

	/* clean up sort_direction string */
	if (isset($_REQUEST["sort_direction"])) {
		$_REQUEST["sort_direction"] = sanitize_search_string(get_request_var("sort_direction"));
	}

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST["clear_x"])) {
		kill_session_var("sess_graph_template_current_page");
		kill_session_var("sess_graph_template_filter");
		kill_session_var("sess_graph_template_sort_column");
		kill_session_var("sess_graph_template_sort_direction");

		unset($_REQUEST["page"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["sort_column"]);
		unset($_REQUEST["sort_direction"]);

	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("page", "sess_graph_template_current_page", "1");
	load_current_session_value("filter", "sess_graph_template_filter", "");
	load_current_session_value("sort_column", "sess_graph_template_sort_column", "name");
	load_current_session_value("sort_direction", "sess_graph_template_sort_direction", "ASC");

	html_start_box("<strong>" . __("Graph Templates") . "</strong>", "100%", $colors["header"], "3", "center", "graph_templates.php?action=template_edit", true);
	?>
	<tr class='rowAlternate2'>
		<td>
			<form name="form_graph_template" method='post' action='<?php print basename($_SERVER["PHP_SELF"]);?>'>
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td style='white-space:nowrap;width:50px;'>
						<?php print __("Search:");?>&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="40" value="<?php print $_REQUEST["filter"];?>">
					</td>
					<td style='white-space:nowrap;width:120px;'>
						&nbsp;<input type="submit" Value="<?php print __("Go");?>" name="go" align="middle">
						<input type="submit" Value="<?php print __("Clear");?>" name="clear_x" align="middle">
					</td>
				</tr>
			</table>
			<div><input type='hidden' name='page' value='1'></div>
			</form>
		</td>
	</tr>
	<?php
	html_end_box(false);

	/* form the 'where' clause for our main sql query */
	$sql_where = "WHERE (graph_templates.name LIKE '%%" . $_REQUEST["filter"] . "%%')";

	html_start_box("", "100%", $colors["header"], "0", "center", "");

	$total_rows = db_fetch_cell("SELECT
		COUNT(graph_templates.id)
		FROM graph_templates
		$sql_where");

	$template_list = db_fetch_assoc("SELECT
		graph_templates.id,graph_templates.name
		FROM graph_templates
		$sql_where
		ORDER BY " . $_REQUEST['sort_column'] . " " . $_REQUEST['sort_direction'] .
		" LIMIT " . (read_config_option("num_rows_device")*($_REQUEST["page"]-1)) . "," . read_config_option("num_rows_device"));

	/* generate page list navigation */
	$nav = html_create_nav($_REQUEST["page"], MAX_DISPLAY_PAGES, read_config_option("num_rows_device"), $total_rows, 7, "graph_templates.php");

	print $nav;
	html_end_box(false);

	$display_text = array(
		"name" => array(__("Template Title"), "ASC"));

	html_header_sort_checkbox($display_text, $_REQUEST["sort_column"], $_REQUEST["sort_direction"]);

	if (sizeof($template_list) > 0) {
		foreach ($template_list as $template) {
			form_alternate_row_color('line' . $template["id"], true);
			form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars("graph_templates.php?action=template_edit&id=" . $template["id"]) . "'>" . (strlen($_REQUEST["filter"]) ? eregi_replace("(" . preg_quote($_REQUEST["filter"]) . ")", "<span style='background-color: #F8D93D;'>\\1</span>", $template["name"]) : $template["name"]) . "</a>", $template["id"]);
			form_checkbox_cell($template["name"], $template["id"]);
			form_end_row();
		}

		form_end_table();

		print $nav;
	}else{
		print "<tr><td><em>" . __("No Graph Templates") . "</em></td></tr>\n";
	}

	print "</table>\n";	# end table of html_header_sort_checkbox

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($graph_actions);
	print "</form>\n";	# end form of html_header_sort_checkbox
}

?>
