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
include_once(CACTI_LIBRARY_PATH . "/template.php");
include_once(CACTI_LIBRARY_PATH . "/tree.php");
include_once(CACTI_LIBRARY_PATH . "/html_tree.php");

define("MAX_DISPLAY_PAGES", 21);

$graph_template_actions = array(
ACTION_NONE => "None",
	"1" => "Delete",
	"2" => "Duplicate"
);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }
cacti_log("action: " . $_REQUEST["action"], false, "TEST");	

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
		include_once("./include/top_header.php");

		input_edit();

		include_once("./include/bottom_footer.php");
		break;
	case 'ajax_edit':
		template_edit(false);

		break;
	case 'template_edit':
	case 'edit':
		include_once (CACTI_BASE_PATH . "/include/top_header.php");
		template_edit(true);
		include_once (CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
	case 'ajax_item_dnd':
		graph_template_item_save();

		break;
	case 'ajax_view':
		template();

		break;
	default:
		include_once("./include/top_header.php");

		template();

		include_once("./include/bottom_footer.php");
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
		$save1["description"] = form_input_validate($_POST["description"], "description", "", true, 3);
		$save1["image"] = form_input_validate(basename($_POST["image"]), "image", "", true, 3);

		$save2["id"] = $_POST["graph_template_graph_id"];
		$save2["local_graph_template_graph_id"] = 0;
		$save2["local_graph_id"] = 0;
		$save2["t_image_format_id"] = form_input_validate((isset($_POST["t_image_format_id"]) ? $_POST["t_image_format_id"] : ""), "t_image_format_id", false, 3);
		$save2["image_format_id"] = form_input_validate((isset($_POST["image_format_id"]) ? $_POST["image_format_id"] : ""), "image_format_id", "", true, 3);
		$save2["t_title"] = form_input_validate((isset($_POST["t_title"]) ? $_POST["t_title"] : ""), "t_title", "", true, 3);
		$save2["title"] = form_input_validate((isset($_POST["title"]) ? $_POST["title"] : ""), "title", "", (isset($_POST["t_title"]) ? true : false), 3);
		$save2["t_height"] = form_input_validate((isset($_POST["t_height"]) ? $_POST["t_height"] : ""), "t_height", "", true, 3);
		$save2["height"] = form_input_validate((isset($_POST["height"]) ? $_POST["height"] : ""), "height", "^[0-9]+$", (isset($_POST["t_height"]) ? true : false), 3);
		$save2["t_width"] = form_input_validate((isset($_POST["t_width"]) ? $_POST["t_width"] : ""), "t_width", "", true, 3);
		$save2["width"] = form_input_validate((isset($_POST["width"]) ? $_POST["width"] : ""), "width", "^[0-9]+$", (isset($_POST["t_width"]) ? true : false), 3);
		$save2["t_upper_limit"] = form_input_validate((isset($_POST["t_upper_limit"]) ? $_POST["t_upper_limit"] : ""), "t_upper_limit", "", true, 3);
		$save2["upper_limit"] = form_input_validate((isset($_POST["upper_limit"]) ? $_POST["upper_limit"] : ""), "upper_limit", "", ((isset($_POST["t_upper_limit"]) || (strlen($_POST["upper_limit"]) === 0)) ? true : false), 3);
		$save2["t_lower_limit"] = form_input_validate((isset($_POST["t_lower_limit"]) ? $_POST["t_lower_limit"] : ""), "t_lower_limit", "", true, 3);
		$save2["lower_limit"] = form_input_validate((isset($_POST["lower_limit"]) ? $_POST["lower_limit"] : ""), "lower_limit", "", ((isset($_POST["t_lower_limit"]) || (strlen($_POST["lower_limit"]) === 0)) ? true : false), 3);
		$save2["t_vertical_label"] = form_input_validate((isset($_POST["t_vertical_label"]) ? $_POST["t_vertical_label"] : ""), "t_vertical_label", "", true, 3);
		$save2["vertical_label"] = form_input_validate((isset($_POST["vertical_label"]) ? $_POST["vertical_label"] : ""), "vertical_label", "", true, 3);
		$save2["t_slope_mode"] = form_input_validate((isset($_POST["t_slope_mode"]) ? $_POST["t_slope_mode"] : ""), "t_slope_mode", "", true, 3);
		$save2["slope_mode"] = form_input_validate((isset($_POST["slope_mode"]) ? $_POST["slope_mode"] : ""), "slope_mode", "", true, 3);
		$save2["t_auto_scale"] = form_input_validate((isset($_POST["t_auto_scale"]) ? $_POST["t_auto_scale"] : ""), "t_auto_scale", "", true, 3);
		$save2["auto_scale"] = form_input_validate((isset($_POST["auto_scale"]) ? $_POST["auto_scale"] : ""), "auto_scale", "", true, 3);
		$save2["t_auto_scale_opts"] = form_input_validate((isset($_POST["t_auto_scale_opts"]) ? $_POST["t_auto_scale_opts"] : ""), "t_auto_scale_opts", "", true, 3);
		$save2["auto_scale_opts"] = form_input_validate((isset($_POST["auto_scale_opts"]) ? $_POST["auto_scale_opts"] : ""), "auto_scale_opts", "", true, 3);
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
		$save2["base_value"] = form_input_validate((isset($_POST["base_value"]) ? $_POST["base_value"] : ""), "base_value", "^(1000|1024)$", (isset($_POST["t_base_value"]) ? true : false), 3);
		$save2["t_export"] = form_input_validate((isset($_POST["t_export"]) ? $_POST["t_export"] : ""), "t_export", "", true, 3);
		$save2["export"] = form_input_validate((isset($_POST["export"]) ? $_POST["export"] : ""), "export", "", true, 3);
		$save2["t_unit_value"] = form_input_validate((isset($_POST["t_unit_value"]) ? $_POST["t_unit_value"] : ""), "t_unit_value", "", true, 3);
		$save2["unit_value"] = form_input_validate((isset($_POST["unit_value"]) ? $_POST["unit_value"] : ""), "unit_value", "^(none|NONE|[0-9]+:[0-9]+$)", true, 3);
		$save2["t_unit_exponent_value"] = form_input_validate((isset($_POST["t_unit_exponent_value"]) ? $_POST["t_unit_exponent_value"] : ""), "t_unit_exponent_value", "", true, 3);
		$save2["unit_exponent_value"] = form_input_validate((isset($_POST["unit_exponent_value"]) ? $_POST["unit_exponent_value"] : ""), "unit_exponent_value", "^-?[0-9]+$", true, 3);

		$save2["t_right_axis"] = form_input_validate((isset($_POST["t_right_axis"]) ? $_POST["t_right_axis"] : ""), "t_right_axis", "", true, 3);
		$save2["right_axis"] = form_input_validate((isset($_POST["right_axis"]) ? $_POST["right_axis"] : ""), "right_axis", "^[.0-9]+:-?[.0-9]+$", true, 3);
		$save2["t_right_axis_label"] = form_input_validate((isset($_POST["t_right_axis_label"]) ? $_POST["t_right_axis_label"] : ""), "t_right_axis_label", "", true, 3);
		$save2["right_axis_label"] = form_input_validate((isset($_POST["right_axis_label"]) ? $_POST["right_axis_label"] : ""), "right_axis_label", "", true, 3);
		$save2["t_right_axis_format"] = form_input_validate((isset($_POST["t_right_axis_format"]) ? $_POST["t_right_axis_format"] : ""), "t_right_axis_format", "", true, 3);
		$save2["right_axis_format"] = form_input_validate((isset($_POST["right_axis_format"]) ? $_POST["right_axis_format"] : ""), "right_axis_format", "^[0-9]+$", true, 3);
		$save2["t_only_graph"] = form_input_validate((isset($_POST["t_only_graph"]) ? $_POST["t_only_graph"] : ""), "t_only_graph", "", true, 3);
		$save2["only_graph"] = form_input_validate((isset($_POST["only_graph"]) ? $_POST["only_graph"] : ""), "only_graph", "", true, 3);
		$save2["t_full_size_mode"] = form_input_validate((isset($_POST["t_full_size_mode"]) ? $_POST["t_full_size_mode"] : ""), "t_full_size_mode", "", true, 3);
		$save2["full_size_mode"] = form_input_validate((isset($_POST["full_size_mode"]) ? $_POST["full_size_mode"] : ""), "full_size_mode", "", true, 3);
		$save2["t_no_gridfit"] = form_input_validate((isset($_POST["t_no_gridfit"]) ? $_POST["t_no_gridfit"] : ""), "t_no_gridfit", "", true, 3);
		$save2["no_gridfit"] = form_input_validate((isset($_POST["no_gridfit"]) ? $_POST["no_gridfit"] : ""), "no_gridfit", "", true, 3);
		$save2["t_x_grid"] = form_input_validate((isset($_POST["t_x_grid"]) ? $_POST["t_x_grid"] : ""), "t_x_grid", "", true, 3);
		$save2["x_grid"] = form_input_validate((isset($_POST["x_grid"]) ? $_POST["x_grid"] : ""), "x_grid", "^[0-9]+$", true, 3);
		$save2["t_unit_length"] = form_input_validate((isset($_POST["t_unit_length"]) ? $_POST["t_unit_length"] : ""), "t_unit_length", "", true, 3);
		$save2["unit_length"] = form_input_validate((isset($_POST["unit_length"]) ? $_POST["unit_length"] : ""), "unit_length", "^[0-9]+$", true, 3);
		$save2["t_colortag_back"] = form_input_validate((isset($_POST["t_colortag_back"]) ? $_POST["t_colortag_back"] : ""), "t_colortag_back", "", true, 3);
		$save2["colortag_back"] = form_input_validate((isset($_POST["colortag_back"]) ? $_POST["colortag_back"] : ""), "colortag_back", "^[0-9a-fA-F]{0,8}$", true, 3);
		$save2["t_colortag_canvas"] = form_input_validate((isset($_POST["t_colortag_canvas"]) ? $_POST["t_colortag_canvas"] : ""), "t_colortag_canvas", "", true, 3);
		$save2["colortag_canvas"] = form_input_validate((isset($_POST["colortag_canvas"]) ? $_POST["colortag_canvas"] : ""), "colortag_canvas", "^[0-9a-fA-F]{0,8}$", true, 3);
		$save2["t_colortag_shadea"] = form_input_validate((isset($_POST["t_colortag_shadea"]) ? $_POST["t_colortag_shadea"] : ""), "t_colortag_shadea", "", true, 3);
		$save2["colortag_shadea"] = form_input_validate((isset($_POST["colortag_shadea"]) ? $_POST["colortag_shadea"] : ""), "colortag_shadea", "^[0-9a-fA-F]{0,8}$", true, 3);
		$save2["t_colortag_shadeb"] = form_input_validate((isset($_POST["t_colortag_shadeb"]) ? $_POST["t_colortag_shadeb"] : ""), "t_colortag_shadeb", "", true, 3);
		$save2["colortag_shadeb"] = form_input_validate((isset($_POST["colortag_shadeb"]) ? $_POST["colortag_shadeb"] : ""), "colortag_shadeb", "^[0-9a-fA-F]{0,8}$", true, 3);
		$save2["t_colortag_grid"] = form_input_validate((isset($_POST["t_colortag_grid"]) ? $_POST["t_colortag_grid"] : ""), "t_colortag_grid", "", true, 3);
		$save2["colortag_grid"] = form_input_validate((isset($_POST["colortag_grid"]) ? $_POST["colortag_grid"] : ""), "colortag_grid", "^[0-9a-fA-F]{0,8}$", true, 3);
		$save2["t_colortag_mgrid"] = form_input_validate((isset($_POST["t_colortag_mgrid"]) ? $_POST["t_colortag_mgrid"] : ""), "t_colortag_mgrid", "", true, 3);
		$save2["colortag_mgrid"] = form_input_validate((isset($_POST["colortag_mgrid"]) ? $_POST["colortag_mgrid"] : ""), "colortag_mgrid", "^[0-9a-fA-F]{0,8}$", true, 3);
		$save2["t_colortag_font"] = form_input_validate((isset($_POST["t_colortag_font"]) ? $_POST["t_colortag_font"] : ""), "t_colortag_font", "", true, 3);
		$save2["colortag_font"] = form_input_validate((isset($_POST["colortag_font"]) ? $_POST["colortag_font"] : ""), "colortag_font", "^[0-9a-fA-F]{0,8}$", true, 3);
		$save2["t_colortag_axis"] = form_input_validate((isset($_POST["t_colortag_axis"]) ? $_POST["t_colortag_axis"] : ""), "t_colortag_axis", "", true, 3);
		$save2["colortag_axis"] = form_input_validate((isset($_POST["colortag_axis"]) ? $_POST["colortag_axis"] : ""), "colortag_axis", "^[0-9a-fA-F]{0,8}$", true, 3);
		$save2["t_colortag_frame"] = form_input_validate((isset($_POST["t_colortag_frame"]) ? $_POST["t_colortag_frame"] : ""), "t_colortag_frame", "", true, 3);
		$save2["colortag_frame"] = form_input_validate((isset($_POST["colortag_frame"]) ? $_POST["colortag_frame"] : ""), "colortag_frame", "^[0-9a-fA-F]{0,8}$", true, 3);
		$save2["t_colortag_arrow"] = form_input_validate((isset($_POST["t_colortag_arrow"]) ? $_POST["t_colortag_arrow"] : ""), "t_colortag_arrow", "", true, 3);
		$save2["colortag_arrow"] = form_input_validate((isset($_POST["colortag_arrow"]) ? $_POST["colortag_arrow"] : ""), "colortag_arrow", "^[0-9a-fA-F]{0,8}$", true, 3);
		$save2["t_font_render_mode"] = form_input_validate((isset($_POST["t_font_render_mode"]) ? $_POST["t_font_render_mode"] : ""), "t_font_render_mode", "", true, 3);
		$save2["font_render_mode"] = form_input_validate((isset($_POST["font_render_mode"]) ? $_POST["font_render_mode"] : ""), "font_render_mode", "", true, 3);
		$save2["t_font_smoothing_threshold"] = form_input_validate((isset($_POST["t_font_smoothing_threshold"]) ? $_POST["t_font_smoothing_threshold"] : ""), "t_font_smoothing_threshold", "", true, 3);
		$save2["font_smoothing_threshold"] = form_input_validate((isset($_POST["font_smoothing_threshold"]) ? $_POST["font_smoothing_threshold"] : ""), "font_smoothing_threshold", "^[0-9]*$", true, 3);
		$save2["t_graph_render_mode"] = form_input_validate((isset($_POST["t_graph_render_mode"]) ? $_POST["t_graph_render_mode"] : ""), "t_graph_render_mode", "", true, 3);
		$save2["graph_render_mode"] = form_input_validate((isset($_POST["graph_render_mode"]) ? $_POST["graph_render_mode"] : ""), "graph_render_mode", "", true, 3);
		$save2["t_pango_markup"] = form_input_validate((isset($_POST["t_pango_markup"]) ? $_POST["t_pango_markup"] : ""), "t_pango_markup", "", true, 3);
		$save2["pango_markup"] = form_input_validate((isset($_POST["pango_markup"]) ? $_POST["pango_markup"] : ""), "pango_markup", "", true, 3);
		$save2["t_interlaced"] = form_input_validate((isset($_POST["t_interlaced"]) ? $_POST["t_interlaced"] : ""), "t_interlaced", "", true, 3);
		$save2["interlaced"] = form_input_validate((isset($_POST["interlaced"]) ? $_POST["interlaced"] : ""), "interlaced", "", true, 3);
		$save2["t_tab_width"] = form_input_validate((isset($_POST["t_tab_width"]) ? $_POST["t_tab_width"] : ""), "t_tab_width", "", true, 3);
		$save2["tab_width"] = form_input_validate((isset($_POST["tab_width"]) ? $_POST["tab_width"] : ""), "tab_width", "^[0-9]*$", true, 3);
		$save2["t_watermark"] = form_input_validate((isset($_POST["t_watermark"]) ? $_POST["t_watermark"] : ""), "t_watermark", "", true, 3);
		$save2["watermark"] = form_input_validate((isset($_POST["watermark"]) ? $_POST["watermark"] : ""), "watermark", "", true, 3);
		$save2["t_dynamic_labels"] = form_input_validate((isset($_POST["t_dynamic_labels"]) ? $_POST["t_dynamic_labels"] : ""), "t_dynamic_labels", "", true, 3);
		$save2["dynamic_labels"] = form_input_validate((isset($_POST["dynamic_labels"]) ? $_POST["dynamic_labels"] : ""), "dynamic_labels", "", true, 3);
		$save2["t_force_rules_legend"] = form_input_validate((isset($_POST["t_force_rules_legend"]) ? $_POST["t_force_rules_legend"] : ""), "t_force_rules_legend", "", true, 3);
		$save2["force_rules_legend"] = form_input_validate((isset($_POST["force_rules_legend"]) ? $_POST["force_rules_legend"] : ""), "force_rules_legend", "", true, 3);
		$save2["t_legend_position"] = form_input_validate((isset($_POST["t_legend_position"]) ? $_POST["t_legend_position"] : ""), "t_legend_position", "", true, 3);
		$save2["legend_position"] = form_input_validate((isset($_POST["legend_position"]) ? $_POST["legend_position"] : ""), "legend_position", "", true, 3);
		$save2["t_legend_direction"] = form_input_validate((isset($_POST["t_legend_direction"]) ? $_POST["t_legend_direction"] : ""), "t_legend_direction", "", true, 3);
		$save2["legend_direction"] = form_input_validate((isset($_POST["legend_direction"]) ? $_POST["legend_direction"] : ""), "legend_direction", "", true, 3);
		$save2["t_grid_dash"] = form_input_validate((isset($_POST["t_grid_dash"]) ? $_POST["t_grid_dash"] : ""), "t_grid_dash", "", true, 3);
		$save2["grid_dash"] = form_input_validate((isset($_POST["grid_dash"]) ? $_POST["grid_dash"] : ""), "grid_dash", "^[0-9]*:[0-9]*$", true, 3);
		$save2["t_border"] = form_input_validate((isset($_POST["t_border"]) ? $_POST["t_border"] : ""), "t_border", "", true, 3);
		$save2["border"] = form_input_validate((isset($_POST["border"]) ? $_POST["border"] : ""), "border", "^[0-9]*$", true, 3);

		if (!is_error_message()) {
			$graph_template_id = sql_save($save1, "graph_templates");

			if ($graph_template_id) {
				raise_message(1);

				/* update the image from cache */
#				graph_template_update_cache($graph_template_id, $_POST["image"]);
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

	header("Location: graph_templates.php?action=template_edit&id=" . (empty($graph_template_id) ? $_POST["graph_template_id"] : $graph_template_id));
	exit;
}

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $colors, $graph_template_actions;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if ($_POST["drp_action"] == "1") { /* delete */
			/* do a referential integrity check */
			if (sizeof($selected_items)) {
				foreach($selected_items as $template_id) {
					/* ================= input validation ================= */
					input_validate_input_number($template_id);
					/* ==================================================== */

					if (sizeof(db_fetch_assoc("SELECT * FROM graph_templates_graph WHERE graph_template_id=$template_id AND local_graph_id > 0 LIMIT 1"))) {
						$bad_ids[] = $template_id;
					}else{
						$template_ids[] = $template_id;
					}
				}
			}

			if (isset($bad_ids)) {
				$message = "";
				foreach($bad_ids as $template_id) {
					$message .= (strlen($message) ? "<br>":"") . "<i>Graph Template " . $template_id . " is in use and can not be removed</i>\n";
				}

				$_SESSION['sess_message_gt_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');

				raise_message('gt_ref_int');
			}

			if (isset($template_ids)) {
				db_execute("delete from graph_templates where " . array_to_sql_or($template_ids, "id"));

				$graph_template_input = db_fetch_assoc("select id from graph_template_input where " . array_to_sql_or($template_ids, "graph_template_id"));

				if (sizeof($graph_template_input) > 0) {
					foreach ($graph_template_input as $item) {
						db_execute("delete from graph_template_input_defs where graph_template_input_id=" . $item["id"]);
					}
				}

				db_execute("delete from graph_template_input where " . array_to_sql_or($template_ids, "graph_template_id"));
				db_execute("delete from graph_templates_graph where " . array_to_sql_or($template_ids, "graph_template_id") . " and local_graph_id=0");
				db_execute("delete from graph_templates_item where " . array_to_sql_or($template_ids, "graph_template_id") . " and local_graph_id=0");
				db_execute("delete from device_template_graph where " . array_to_sql_or($template_ids, "graph_template_id"));

				/* "undo" any graph that is currently using this template */
				db_execute("update graph_templates_graph set local_graph_template_graph_id=0,graph_template_id=0 where " . array_to_sql_or($template_ids, "graph_template_id"));
				db_execute("update graph_templates_item set local_graph_template_item_id=0,graph_template_id=0 where " . array_to_sql_or($template_ids, "graph_template_id"));
				db_execute("update graph_local set graph_template_id=0 where " . array_to_sql_or($template_ids, "graph_template_id"));
			}
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				duplicate_graph(0, $selected_items[$i], get_request_var_post("title_format"));
			}
		}

		exit;
	}

	/* setup some variables */
	$graph_list = ""; $graph_array = array();

	/* loop through each of the graphs selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$graph_list .= "<li>" . db_fetch_cell("select name from graph_templates where id=" . $matches[1]) . "</li>";
			$graph_array[] = $matches[1];
		}
	}

	include_once(CACTI_BASE_PATH . "/include/top_header.php");

	print "<form id='gtactions' name='gtactions' action='graph_templates.php' method='post'>\n";

	html_start_box("<strong>" . $graph_template_actions{$_POST["drp_action"]} . "</strong>", "60%", $colors["header_panel"], "3", "center", "");

	if (isset($graph_array) && sizeof($graph_array)) {
		if (get_request_var_post("drp_action") === ACTION_NONE) { /* NONE */
			print "	<tr>
						<td class='textArea'>
							<p>" . "You did not select a valid action. Please select 'Return' to return to the previous menu." . "</p>
						</td>
					</tr>\n";

			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;";
		}elseif (get_request_var_post("drp_action") === "1") { /* delete */
			print "	<tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"]. "'>
						<p>When you click \"Continue\", the following Graph Template(s) will be deleted.  Any Graph(s) associated with
						the Template(s) will become individual Graph(s).</p>
						<p><ul>$graph_list</ul></p>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Delete Graph Template(s)'>";
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			print "	<tr>
					<td class='textArea' bgcolor='#" . $colors["form_alternate1"]. "'>
						<p>When you click \"Continue\", the following Graph Template(s) will be duplicated. You can
						optionally change the title format for the new Graph Template(s).</p>
						<p><ul>$graph_list</ul></p>
						<p><strong>Title Format:</strong><br>"; form_text_box("title_format", "<template_title> (1)", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n
				";
			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Duplicate Graph Template(s)'>";
		}
	}else{
		print "<tr><td bgcolor='#" . $colors["form_alternate1"]. "'><span class='textError'>You must select at least one graph template.</span></td></tr>\n";
		$save_html = "<input type='button' value='Return' onClick='window.history.back()'>";
	}

	print "	<tr>
			<td align='right' bgcolor='#eaeaea'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='selected_items' value='" . (isset($graph_array) ? serialize($graph_array) : '') . "'>
				<input type='hidden' name='drp_action' value='" . $_POST["drp_action"] . "'>
				$save_html
			</td>
		</tr>
		";

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

		$header_label = "[new]";
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
			colors.hex
			from graph_templates_item
			left join data_template_rrd on (graph_templates_item.task_item_id=data_template_rrd.id)
			left join data_local on (data_template_rrd.local_data_id=data_local.id)
			left join data_template_data on (data_local.id=data_template_data.local_data_id)
			left join cdef on (cdef_id=cdef.id)
			left join colors on (color_id=colors.id)
			where graph_templates_item.graph_template_id=" . $_GET["id"] . "
			and graph_templates_item.local_graph_id=0
			order by graph_templates_item.sequence");

		$header_label = "[edit: " . db_fetch_cell("select name from graph_templates where id=" . $_GET["id"]) . "]";
	}

	html_start_box("<strong>Graph Template Items</strong> " . htmlspecialchars($header_label), "100%", $colors["header"], "3", "center", "graph_templates_items.php?action=item_edit&graph_template_id=" . htmlspecialchars(get_request_var("id")));
	draw_graph_items_list($template_item_list, "graph_templates_items.php", "graph_template_id=" . $_GET["id"], false);
	html_end_box();

	html_start_box("<strong>Graph Item Inputs</strong>", "100%", $colors["header"], "3", "center", "graph_templates_inputs.php?action=input_edit&graph_template_id=" . htmlspecialchars(get_request_var("id")));

	print "<tr bgcolor='#" . $colors["header_panel"] . "'>";
		DrawMatrixHeaderItem("Name",$colors["header_text"],2);
	print "</tr>";

	$template_item_list = db_fetch_assoc("select id,name from graph_template_input where graph_template_id=" . $_GET["id"] . " order by name");

	$i = 0;
	if (sizeof($template_item_list) > 0) {
	foreach ($template_item_list as $item) {
		form_alternate_row_color($colors["alternate"],$colors["light"],$i);
	?>
			<td>
				<a class="linkEditMain" href="<?php print htmlspecialchars("graph_templates_inputs.php?action=input_edit&id=" . $item["id"] . "&graph_template_id=" . $_GET["id"]);?>"><?php print htmlspecialchars($item["name"]);?></a>
			</td>
			<td align="right">
				<a href="<?php print htmlspecialchars("graph_templates_inputs.php?action=input_remove&id=" . $item["id"] . "&graph_template_id=" . $_GET["id"]);?>"><img src="images/delete_icon.gif" style="height:10px;width:10px;" border="0" alt="Delete"></a>
			</td>
		</tr>
	<?php
	$i++;
	}
	}else{
		print "<tr bgcolor='#" . $colors["form_alternate2"] . "'><td colspan='2'><em>No Inputs</em></td></tr>";
	}

	html_end_box();
}

/* ----------------------------
    template - Graph Templates
   ---------------------------- */

function _template_edit() {
	global $colors, $struct_graph, $image_types, $fields_graph_template_template_edit;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	/* graph item list goes here */
	if (!empty($_GET["id"])) {
		item();
	}

	if (!empty($_GET["id"])) {
		$template = db_fetch_row("select * from graph_templates where id=" . $_GET["id"]);
		$template_graph = db_fetch_row("select * from graph_templates_graph where graph_template_id=" . $_GET["id"] . " and local_graph_id=0");

		$header_label = "[edit: " . $template["name"] . "]";
	}else{
		$header_label = "[new]";
	}

	html_start_box("<strong>Template</strong> " . htmlspecialchars($header_label), "100%", $colors["header"], "3", "center", "");

	draw_edit_form(array(
		"config" => array(),
		"fields" => inject_form_variables($fields_graph_template_template_edit, (isset($template) ? $template : array()), (isset($template_graph) ? $template_graph : array()))
		));

	html_end_box();

	html_start_box("<strong>Graph Template</strong>", "100%", $colors["header"], "3", "center", "");

	$form_array = array();

	while (list($field_name, $field_array) = each($struct_graph)) {
		$form_array += array($field_name => $struct_graph[$field_name]);

		$form_array[$field_name]["value"] = (isset($template_graph) ? $template_graph[$field_name] : "");
		$form_array[$field_name]["form_id"] = (isset($template_graph) ? $template_graph["id"] : "0");
		$form_array[$field_name]["description"] = "";
		$form_array[$field_name]["sub_checkbox"] = array(
			"name" => "t_" . $field_name,
			"friendly_name" => "Use Per-Graph Value (Ignore this Value)",
			"value" => (isset($template_graph) ? $template_graph{"t_" . $field_name} : "")
			);
	}

	draw_edit_form(
		array(
			"config" => array(
				"no_form_tag" => true
				),
			"fields" => $form_array
			)
		);

	form_hidden_box("rrdtool_version", read_config_option("rrdtool_version"), "");
	html_end_box();

	form_save_button("graph_templates.php", "return");

//Now we need some javascript to make it dynamic
?>
<script language="JavaScript">

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

function template_edit($tabs = false) {
	if ($tabs) {
		$graph_template_tabs = array(
			"general" 	=> "General",
			"items" 	=> "Items",
#			"graphs" 	=> "Graphs",
		);

		/* draw the categories tabs on the top of the page */
		print "<div id='tabs_gtemplate'>\n";
		print "<ul>\n";

		$i = 0;
		if (sizeof($graph_template_tabs) > 0) {
			foreach (array_keys($graph_template_tabs) as $tab_short_name) {
				print "<li><a id='tabs-$i' href='" . htmlspecialchars("graph_templates.php?action=ajax_edit" . (isset($_REQUEST['id']) ? "&id=" . $_REQUEST['id'] . "&template_id=" . $_REQUEST['id']: "") . "&filter=&host_id=-1&tab=$tab_short_name") . "'>$graph_template_tabs[$tab_short_name]</a></li>";
				$i++;

				/* in case this is a new template, exit */
				if (!isset($_REQUEST["id"])) break;
			}
		}
		print "</ul>\n";
		print "</div>";

		print "<script type='text/javascript'>
			$().ready(function() {
				// refer to the div that preceeds the <ul>
				$('#tabs_gtemplate').tabs({ cookie: { expires: 30 } });
			});
		</script>\n";
	}else{
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var("id"));
		/* ==================================================== */

		if (!empty($_REQUEST["id"])) {
			$graph_template = db_fetch_row("select * from graph_templates where id=" . $_REQUEST["id"]);
			$header_label = "[edit: " . $graph_template["name"] . "]";
		}else{
			$graph_template = array();
			$header_label = "[new]";
		}

		switch (get_request_var_request("tab")) {
#			case "graphs":
#				include_once(CACTI_LIBRARY_PATH . "/graph.php");
#
#				cacti_graph();
#
#				break;
			case "items":
				/* graph item list goes here */
				if (!empty($_REQUEST["id"])) {
					graph_template_display_items();
				}

				break;
			default:
				graph_template_display_general($graph_template, $header_label);
	
				break;
		}
	}
}



function graph_template_display_general($graph_template, $header_label) {
	global $colors;
	include_once(CACTI_LIBRARY_PATH . "/graph.php");
	include_once(CACTI_LIBRARY_PATH . "/graph_template.php");

	# fetch all settings for this graph template
	if (isset($graph_template["id"])) {
		$template_graph = db_fetch_row("select * from graph_templates_graph where graph_template_id=" . $graph_template["id"] . " and local_graph_id=0");
	}else {
		$template_graph = array();
	}


	/* draw a second list of headers to split the huge graph_template option set into smaller chunks */
	$template_tabs = array(
		"t_header" 		=> "Header",
	);	
	/* right axis for specific rrdtool versions only */
	if ( read_config_option("rrdtool_version") != RRD_VERSION_1_0 && read_config_option("rrdtool_version") != RRD_VERSION_1_2) {
		$template_tabs += array("t_right_axis" 	=> "Right Axis");
	}
	$template_tabs += array("t_size" 		=> "Size");
	$template_tabs += array("t_limits" 		=> "Limits");
	$template_tabs += array("t_grid" 		=> "Grid");
	$template_tabs += array("t_color" 		=> "Color");
	$template_tabs += array("t_legend" 		=> "Legend");
	$template_tabs += array("t_misc" 		=> "Miscellaneous");


	/* draw the second list of tabs */
	print "<div id='tabs_template'>\n";
	print "<ul>\n";



	if (sizeof($template_tabs) > 0) {
		foreach (array_keys($template_tabs) as $tab_short_name) {
			print "<li><a href=#$tab_short_name>$template_tabs[$tab_short_name]</a></li>";
			#if (!isset($_REQUEST["id"])) break; always print all tabs
		}
	}
	print "</ul>\n";

	print "<script type='text/javascript'>
		$().ready(function() {
			// refer to the div that preceeds the <ul>
			$('#tabs_template').tabs({ cookie: { expires: 30 } });
		});
	</script>\n";


	/* now start the huge form that holds all graph_template table parameters 
	 * we will split that up into chunks of seperated div's 
	 * in case you do NOT like how options are seperated into chunks, 
	 * please change associations of options into arrays 
	 * in include/graph/graph_forms.php */
	print "<form id='graph_template_edit' name='graph_template_edit' method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "'>\n";

	# the template header
	print "<div id='t_header'>";
	html_start_box("Graph Template" . " $header_label", "100%", $colors["header"], "0", "center", "");
	draw_edit_form(array(
		"config" => array("no_form_tag" => true),
		"fields" => inject_form_variables(graph_template_form_list(), (isset($graph_template) ? $graph_template : array()), (isset($template_graph) ? $template_graph : array()))
	));
	html_end_box(false);
	
	/* draw additional sections on the first screen
	 * especially those fields, that are required for a "save" operation
	 */
	/* display all labels */
	html_start_box("Graph Template Labels", "100%", $colors["header"], "0", "center", "");
	draw_template_edit_form('header_graph_labels', graph_labels_form_list(), $template_graph, false);
	html_end_box(false);
	html_start_box("Graph Template Cacti Specifics", "100%", $colors["header"], "0", "center", "");
	draw_template_edit_form('header_graph_cacti', graph_cacti_form_list(), $template_graph, false);
	html_end_box(false);

	/* provide hidden parameters to rule the save function */
	form_hidden_box("graph_template_id", (isset($template_graph["graph_template_id"]) ? $template_graph["graph_template_id"] : "0"), "");
	form_hidden_box("graph_template_graph_id", (isset($template_graph["id"]) ? $template_graph["id"] : "0"), "");
	form_hidden_box("save_component_template", 1, "");

	form_hidden_box("hidden_rrdtool_version", read_config_option("rrdtool_version"), "");
	print "</div>";



	/* print graph configuration
	 * when either graph template id given or new graph template is created
	 * that is: always! */

	/* TODO: we should not use rrd version in the code, when going data-driven */
	if ( read_config_option("rrdtool_version") != RRD_VERSION_1_0 && read_config_option("rrdtool_version") != RRD_VERSION_1_2) {
		print "<div id='t_right_axis'>";
		html_start_box("Graph Template Right Axis Settings", "100%", $colors["header"], "0", "center", "");
		draw_template_edit_form('header_graph_right_axis', graph_right_axis_form_list(), $template_graph, false);
		html_end_box(false);
		print "</div>";
	}

	/* print all size options */
	print "<div id='t_size'>";
	html_start_box("Graph Template Size", "100%", $colors["header"], "0", "center", "");
	draw_template_edit_form('header_graph_size', graph_size_form_list(), $template_graph, false);
	html_end_box(false);
	print "</div>";

	/* print all limit options */
	print "<div id='t_limits'>";
	html_start_box("Graph Template Limits", "100%", $colors["header"], "0", "center", "");
	draw_template_edit_form('header_graph_limits', graph_limits_form_list(), $template_graph, false);
	html_end_box(false);
	print "</div>";

	/* print grid options */
	print "<div id='t_grid'>";
	html_start_box("Graph Template Grid", "100%", $colors["header"], "0", "center", "");
	draw_template_edit_form('header_graph_grid', graph_grid_form_list(), $template_graph, false);
	html_end_box(false);
	print "</div>";

	/* print colors, load jQuery colorpicker below */
	print "<div id='t_color'>";
	html_start_box("Graph Template Color", "100%", $colors["header"], "0", "center", "");
	draw_template_edit_form('header_graph_color', graph_color_form_list(), $template_graph, false);
	html_end_box(false);
	print "</div>";

	/* print all legend options */
	print "<div id='t_legend'>";
	html_start_box("Graph Template Legend", "100%", $colors["header"], "0", "center", "");
	draw_template_edit_form('header_graph_legend', graph_legend_form_list(), $template_graph, false);
	html_end_box(false);
	print "</div>";

	/* print miscellaneous options */
	print "<div id='t_misc'>";
	html_start_box("Graph Template Misc", "100%", $colors["header"], "0", "center", "");
	draw_template_edit_form('header_graph_misc', graph_misc_form_list(), $template_graph, false);
	html_end_box(false);
	print "</div>";

	form_save_button("graph_templates.php", "return");
	print "</div>";		# div id='tabs_template'

	include_once(CACTI_BASE_PATH . "/access/js/colorpicker.js");
	include_once(CACTI_BASE_PATH . "/access/js/graph_template_options.js");
}



function template() {
	global $colors, $graph_template_actions;

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

	html_start_box("<strong>Graph Templates</strong>", "100%", $colors["header"], "3", "center", "graph_templates.php?action=template_edit");

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
		<form name="form_host_template" action="graph_templates.php">
			<table width="100%" cellpadding="0" cellspacing="0">
				<tr>
					<td nowrap style='white-space: nowrap;' width="50">
						Search:&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="40" value="<?php print htmlspecialchars(get_request_var_request("filter"));?>">
					</td>
					<td nowrap style='white-space: nowrap;'>
						&nbsp;<input type="submit" value="Go" title="Set/Refresh Filters">
						<input type="submit" name="clear_x" value="Clear" title="Clear Filters">
					</td>
				</tr>
			</table>
			<input type='hidden' name='page' value='1'>
		</form>
		</td>
	</tr>
	<?php

	html_end_box();

	/* form the 'where' clause for our main sql query */
	$sql_where = "WHERE (graph_templates.name LIKE '%%" . get_request_var_request("filter") . "%%')";

	/* print checkbox form for validation */
	print "<form name='chk' method='post' action='graph_templates.php'>\n";

	html_start_box("", "100%", $colors["header"], "3", "center", "");

	$total_rows = db_fetch_cell("SELECT
		COUNT(graph_templates.id)
		FROM graph_templates
		$sql_where");

	$template_list = db_fetch_assoc("SELECT
		graph_templates.id,graph_templates.name
		FROM graph_templates
		$sql_where
		ORDER BY " . get_request_var_request("sort_column") . " " . get_request_var_request("sort_direction") .
		" LIMIT " . (read_config_option("num_rows_device")*(get_request_var_request("page")-1)) . "," . read_config_option("num_rows_device"));

	/* generate page list */
	$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, read_config_option("num_rows_device"), $total_rows, "graph_templates.php?filter=" . get_request_var_request("filter"));

	$nav = "<tr bgcolor='#" . $colors["header"] . "'>
		<td colspan='7'>
			<table width='100%' cellspacing='0' cellpadding='0' border='0'>
				<tr>
					<td align='left' class='textHeaderDark'>
						<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("graph_templates.php?filter=" . get_request_var_request("filter") . "&page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
					</td>\n
					<td align='center' class='textHeaderDark'>
						Showing Rows " . ((read_config_option("num_rows_device")*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < read_config_option("num_rows_device")) || ($total_rows < (read_config_option("num_rows_device")*get_request_var_request("page")))) ? $total_rows : (read_config_option("num_rows_device")*get_request_var_request("page"))) . " of $total_rows [$url_page_select]
					</td>\n
					<td align='right' class='textHeaderDark'>
						<strong>"; if ((get_request_var_request("page") * read_config_option("num_rows_device")) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("graph_templates.php?filter=" . get_request_var_request("filter") . "&page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * read_config_option("num_rows_device")) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
					</td>\n
				</tr>
			</table>
		</td>
		</tr>\n";

	print $nav;

	$display_text = array(
		"name" => array("Template Title", "ASC"));

	html_header_sort_checkbox($display_text, get_request_var_request("sort_column"), get_request_var_request("sort_direction"), false);

	$i = 0;
	if (sizeof($template_list) > 0) {
		foreach ($template_list as $template) {
			form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $template["id"]);$i++;
			form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars("graph_templates.php?action=template_edit&id=" . $template["id"]) . "'>" . (strlen(get_request_var_request("filter")) ? preg_replace("/(" . preg_quote(get_request_var_request("filter"), "/") . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", htmlspecialchars($template["name"])) : htmlspecialchars($template["name"])) . "</a>", $template["id"]);
			form_checkbox_cell($template["name"], $template["id"]);
			form_end_row();
		}
		print $nav;
	}else{
		print "<tr><td><em>No Graph Templates</em></td></tr>\n";
	}
	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($graph_template_actions);

	print "</form>\n";
}

?>

