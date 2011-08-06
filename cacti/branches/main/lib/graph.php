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

function &graph_form_list() {
	require(CACTI_BASE_PATH . "/include/graph/graph_forms.php");

	return $struct_graph;
}

function &graph_labels_form_list() {
	require(CACTI_BASE_PATH . "/include/graph/graph_forms.php");

	return $struct_graph_labels;
}

function &graph_right_axis_form_list() {
	require(CACTI_BASE_PATH . "/include/graph/graph_forms.php");

	return $struct_graph_right_axis;
}

function &graph_size_form_list() {
	require(CACTI_BASE_PATH . "/include/graph/graph_forms.php");

	return $struct_graph_size;
}

function &graph_limits_form_list() {
	require(CACTI_BASE_PATH . "/include/graph/graph_forms.php");

	return $struct_graph_limits;
}

function &graph_grid_form_list() {
	require(CACTI_BASE_PATH . "/include/graph/graph_forms.php");

	return $struct_graph_grid;
}

function &graph_color_form_list() {
	require(CACTI_BASE_PATH . "/include/graph/graph_forms.php");

	return $struct_graph_color;
}

function &graph_legend_form_list() {
	require(CACTI_BASE_PATH . "/include/graph/graph_forms.php");

	return $struct_graph_legend;
}

function &graph_misc_form_list() {
	require(CACTI_BASE_PATH . "/include/graph/graph_forms.php");

	return $struct_graph_misc;
}

function &graph_cacti_form_list() {
	require(CACTI_BASE_PATH . "/include/graph/graph_forms.php");

	return $struct_graph_cacti;
}

function &graph_item_form_list() {
	require(CACTI_BASE_PATH . "/include/graph/graph_forms.php");

	return $struct_graph_item;
}

function graph_actions_list() {
	require(CACTI_BASE_PATH . "/include/graph/graph_arrays.php");
	global $graph_actions;

	return $graph_actions;
}

function get_graph_tree_items() {

	/* Make sure nothing is cached */
	header("Cache-Control: must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header("Expires: ". gmdate("D, d M Y H:i:s", mktime(date("H"), date("i"), date("s"), date("m")-1, date("d"), date("Y")))." GMT");
	header("Last-Modified: ". gmdate("D, d M Y H:i:s")." GMT");
	header("Content-Type: application/json; charset=utf-8");

	/* parse the id string
	 * prototypes:
	 * tree_id, tree_id_leaf_id, tree_id_leaf_id_hgd_dq
	 * tree_id_leaf_id_hgd_dqi, tree_id_leaf_id_hgd_gt
	 */
	$tree_id         = 0;
	$leaf_id         = 0;
	$device_group_type = array('na', 0);

	if (isset($_REQUEST["id"])) {
		$id_array = explode("_", $_REQUEST["id"]);
		$type     = "";

		if (sizeof($id_array)) {
			foreach($id_array as $part) {
				if (is_numeric($part)) {
					switch($type) {
						case "tree":
							$tree_id = $part;
							break;
						case "leaf":
							$leaf_id = $part;
							break;
						case "dqi":
							$device_group_type = array("dqi", $part);
							break;
						case "dq":
							$device_group_type = array("dq", $part);
							break;
						case "gt":
							$device_group_type = array("gt", $part);
							break;
						default:
							break;
					}
				}else{
					$type = trim($part);
				}
			}
		}
	}

	// cacti_log("tree_id: '" . $tree_id . ", leaf_id: '" . $leaf_id . ", hgt: '" . $device_group_type[0] . "," . $device_group_type[1] . "'", false); 

	if (is_numeric($_REQUEST["id"]) || $tree_id <= 0) {
		$tree_items = get_tree_leaf_items($tree_id, $leaf_id, $device_group_type, true);
	}else{
		$tree_items = get_tree_leaf_items($tree_id, $leaf_id, $device_group_type);
	}

	if (sizeof($tree_items)) {
		$total_items = sizeof($tree_items);

		$i = 0;
		echo "[";
		foreach($tree_items as $item) {
			$node_id  = "tree_" . $item["tree_id"];
			$node_id .= "_leaf_" . $item["leaf_id"];
			switch ($item["type"]) {
				case "tree":
					$children = true;
					$icon     = "";
					break;
				case "graph":
					$children = false;
					$icon     = CACTI_URL_PATH . "images/tree_icons/graph.gif";
					break;
				case "device":
					if (read_graph_config_option("expand_devices") == CHECKED) {
						$children = true;
					}else{
						$children = false;
					}
					$icon     = CACTI_URL_PATH . "images/tree_icons/device.gif";
					break;
				case "header":
					$children = true;
					$icon     = "";
					break;
				case "dq":
					$children = true;
					$icon     = "";
					$node_id .= "_" . $item["type"] . "_" . $item["id"];
					$icon     = CACTI_URL_PATH . "images/tree_icons/dataquery.png";
					break;
				case "dqi":
					$children = false;
					$icon     = "";
					$node_id .= "_" . $item["type"] . "_" . $item["id"];
					break;
				case "gt":
					$children = false;
					$node_id .= "_" . $item["type"] . "_" . $item["id"];
					$icon     = CACTI_URL_PATH . "images/tree_icons/template.png";
					break;
				default:
			}
			echo '{"attr":{"id":"' . $node_id . '","rel":"' . $item["type"] . '"},"data":"' . $item["name"] . '","state":' . ($children ? '"closed"':'""') . '}';
			if(++$i < $total_items) echo ",\n";
		}
		echo "]";
	}

	exit();
}

function get_graph_tree_graphs() {

	include_once(CACTI_BASE_PATH . "/lib/timespan_settings.php");

	/* Make sure nothing is cached */
	header("Cache-Control: must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header("Expires: ". gmdate("D, d M Y H:i:s", mktime(date("H"), date("i"), date("s"), date("m")-1, date("d"), date("Y")))." GMT");
	header("Last-Modified: ". gmdate("D, d M Y H:i:s")." GMT");

	/* parse the id string
	 * prototypes:
	 * tree_id, tree_id_leaf_id, tree_id_leaf_id_hgd_dq
	 * tree_id_leaf_id_hgd_dqi, tree_id_leaf_id_hgd_gt
	 */
	$tree_id         = 0;
	$leaf_id         = 0;
	$device_group_type = array('na', 0);

	if (!isset($_REQUEST["id"])) {
		if (isset($_SESSION["sess_graph_navigation"])) {
			$_REQUEST["id"] = $_SESSION["sess_graph_navigation"];
		}
	}

	/* process the id information */
	if (isset($_REQUEST["id"])) {
		$_SESSION["sess_graph_navigation"] = $_REQUEST["id"];
		$id_array = explode("_", $_REQUEST["id"]);
		$type     = "";

		if (sizeof($id_array)) {
			foreach($id_array as $part) {
				if (is_numeric($part)) {
					switch($type) {
						case "tree":
							$tree_id = $part;
							break;
						case "leaf":
							$leaf_id = $part;
							break;
						case "dqi":
							$device_group_type = array("dqi", $part);
							break;
						case "dq":
							$device_group_type = array("dq", $part);
							break;
						case "gt":
							$device_group_type = array("gt", $part);
							break;
						default:
							break;
					}
				}else{
					$type = trim($part);
				}
			}
		}
	}

	get_graph_tree_content($tree_id, $leaf_id, $device_group_type);
	exit();
}

/* --------------------------
    graph form functions
   -------------------------- */

function graph_form_save() {
	if ((isset($_POST["save_component_graph_new"])) && (!empty($_POST["graph_template_id"]))) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("graph_template_id"));
		/* ==================================================== */

		$save["id"] = $_POST["local_graph_id"];
		$save["graph_template_id"] = $_POST["graph_template_id"];
		$save["device_id"] = $_POST["device_id"];

		$local_graph_id = sql_save($save, "graph_local");

		change_graph_template($local_graph_id, get_request_var_post("graph_template_id"), true);

		/* update the title cache */
		update_graph_title_cache($local_graph_id);
	}

	if (isset($_POST["save_component_graph"])) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("graph_template_id"));
		input_validate_input_number(get_request_var_post("_graph_template_id"));
		/* ==================================================== */

		$save1["id"] = form_input_validate($_POST["local_graph_id"], "local_graph_id", "^[0-9]+$", false, 3);
		$save1["device_id"] = form_input_validate($_POST["device_id"], "device_id", "^[-0-9]+$", false, 3);
		$save1["graph_template_id"] = form_input_validate($_POST["graph_template_id"], "graph_template_id", "^[0-9]+$", false, 3);

		$save2["id"] = form_input_validate($_POST["graph_template_graph_id"], "graph_template_graph_id", "^[0-9]+$", false, 3);
		$save2["local_graph_template_graph_id"] = form_input_validate($_POST["local_graph_template_graph_id"], "local_graph_template_graph_id", "^[0-9]+$", false, 3);
		$save2["graph_template_id"] = form_input_validate($_POST["graph_template_id"], "graph_template_id", "^[0-9]+$", false, 3);
		$save2["image_format_id"] = form_input_validate((isset($_POST["image_format_id"]) ? $_POST["image_format_id"] : ""), "image_format_id", "", true, 3);
		$save2["title"] = form_input_validate((isset($_POST["title"]) ? $_POST["title"] : ""), "title", "", (isset($_POST["t_title"]) ? true : false), 3);
		$save2["height"] = form_input_validate((isset($_POST["height"]) ? $_POST["height"] : ""), "height", "^[0-9]+$", (isset($_POST["t_height"]) ? true : false), 3);
		$save2["width"] = form_input_validate((isset($_POST["width"]) ? $_POST["width"] : ""), "width", "^[0-9]+$", (isset($_POST["t_width"]) ? true : false), 3);
		$save2["upper_limit"] = form_input_validate((isset($_POST["upper_limit"]) ? $_POST["upper_limit"] : ""), "upper_limit", "", ((isset($_POST["t_upper_limit"]) || (strlen($_POST["upper_limit"]) === 0)) ? true : false), 3);
		$save2["lower_limit"] = form_input_validate((isset($_POST["lower_limit"]) ? $_POST["lower_limit"] : ""), "lower_limit", "", ((isset($_POST["t_lower_limit"]) || (strlen($_POST["lower_limit"]) === 0)) ? true : false), 3);
		$save2["vertical_label"] = form_input_validate((isset($_POST["vertical_label"]) ? $_POST["vertical_label"] : ""), "vertical_label", "", true, 3);
		$save2["slope_mode"] = form_input_validate((isset($_POST["slope_mode"]) ? $_POST["slope_mode"] : ""), "slope_mode", "", true, 3);
		$save2["auto_scale"] = form_input_validate((isset($_POST["auto_scale"]) ? $_POST["auto_scale"] : ""), "auto_scale", "", true, 3);
		$save2["auto_scale_opts"] = form_input_validate((isset($_POST["auto_scale_opts"]) ? $_POST["auto_scale_opts"] : ""), "auto_scale_opts", "", true, 3);
		$save2["auto_scale_log"] = form_input_validate((isset($_POST["auto_scale_log"]) ? $_POST["auto_scale_log"] : ""), "auto_scale_log", "", true, 3);
		$save2["scale_log_units"] = form_input_validate((isset($_POST["scale_log_units"]) ? $_POST["scale_log_units"] : ""), "scale_log_units", "", true, 3);
		$save2["auto_scale_rigid"] = form_input_validate((isset($_POST["auto_scale_rigid"]) ? $_POST["auto_scale_rigid"] : ""), "auto_scale_rigid", "", true, 3);
		$save2["alt_y_grid"] = form_input_validate((isset($_POST["alt_y_grid"]) ? $_POST["alt_y_grid"] : ""), "alt_y_grid", "", true, 3);
		$save2["auto_padding"] = form_input_validate((isset($_POST["auto_padding"]) ? $_POST["auto_padding"] : ""), "auto_padding", "", true, 3);
		$save2["base_value"] = form_input_validate((isset($_POST["base_value"]) ? $_POST["base_value"] : ""), "base_value", "^(1000|1024)$", (isset($_POST["t_base_value"]) ? true : false), 3);
		$save2["export"] = form_input_validate((isset($_POST["export"]) ? $_POST["export"] : ""), "export", "", true, 3);
		$save2["unit_value"] = form_input_validate((isset($_POST["unit_value"]) ? $_POST["unit_value"] : ""), "unit_value", "^(none|NONE|[0-9]+:[0-9]+$)", true, 3);
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
			$local_graph_id = sql_save($save1, "graph_local");
		}

		if (!is_error_message()) {
			$save2["local_graph_id"] = $local_graph_id;
			$graph_templates_graph_id = sql_save($save2, "graph_templates_graph");

			if ($graph_templates_graph_id) {
				raise_message(1);

				/* if template information chanegd, update all necessary template information */
				if ($_POST["graph_template_id"] != $_POST["_graph_template_id"]) {
					/* check to see if the number of graph items differs, if it does; we need user input */
					if ((!empty($_POST["graph_template_id"])) && (!empty($_POST["local_graph_id"])) && (sizeof(db_fetch_assoc("select id from graph_templates_item where local_graph_id=$local_graph_id")) != sizeof(db_fetch_assoc("select id from graph_templates_item where local_graph_id=0 and graph_template_id=" . $_POST["graph_template_id"])))) {
						/* set the template back, since the user may choose not to go through with the change
						at this point */
						db_execute("update graph_local set graph_template_id=" . $_POST["_graph_template_id"] . " where id=$local_graph_id");
						db_execute("update graph_templates_graph set graph_template_id=" . $_POST["_graph_template_id"] . " where local_graph_id=$local_graph_id");

						header("Location: graphs.php?action=graph_diff&id=$local_graph_id&graph_template_id=" . $_POST["graph_template_id"]);
						exit;
					}
				}
			}else{
				raise_message(2);
			}

			/* update the title cache */
			update_graph_title_cache($local_graph_id);
		}

		if ((!is_error_message()) && ($_POST["graph_template_id"] != $_POST["_graph_template_id"])) {
			change_graph_template($local_graph_id, get_request_var_post("graph_template_id"), true);
		}elseif (!empty($_POST["graph_template_id"])) {
			update_graph_data_query_cache($local_graph_id);
		}
	}

	if (isset($_POST["save_component_input"])) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("local_graph_id"));
		/* ==================================================== */

		/* first; get the current graph template id */
		$graph_template_id = db_fetch_cell("select graph_template_id from graph_local where id=" . $_POST["local_graph_id"]);

		/* get all inputs that go along with this graph template */
		$input_list = db_fetch_assoc("select id,column_name from graph_template_input where graph_template_id=$graph_template_id");

		if (sizeof($input_list) > 0) {
		foreach ($input_list as $input) {
			/* we need to find out which graph items will be affected by saving this particular item */
			$item_list = db_fetch_assoc("select
				graph_templates_item.id
				from (graph_template_input_defs,graph_templates_item)
				where graph_template_input_defs.graph_template_item_id=graph_templates_item.local_graph_template_item_id
				and graph_templates_item.local_graph_id=" . $_POST["local_graph_id"] . "
				and graph_template_input_defs.graph_template_input_id=" . $input["id"]);

			/* loop through each item affected and update column data */
			if (sizeof($item_list) > 0) {
			foreach ($item_list as $item) {
				/* if we are changing templates, the POST vars we are searching for here will not exist.
				this is because the db and form are out of sync here, but it is ok to just skip over saving
				the inputs in this case. */
				if (isset($_POST{$input["column_name"] . "_" . $input["id"]})) {
					db_execute("update graph_templates_item set " . $input["column_name"] . "='" . $_POST{$input["column_name"] . "_" . $input["id"]} . "' where id=" . $item["id"]);
				}
			}
			}
		}
		}
	}

	if (isset($_POST["save_component_graph_diff"])) {
		if (get_request_var_post("type") == "1") {
			$intrusive = true;
		}elseif (get_request_var_post("type") == "2") {
			$intrusive = false;
		}

		change_graph_template(get_request_var_post("local_graph_id"), get_request_var_post("graph_template_id"), $intrusive);
	}

	if ((isset($_POST["save_component_graph_new"])) && (empty($_POST["graph_template_id"]))) {
		header("Location: graphs.php?action=edit&device_id=" . $_POST["device_id"] . "&new=1");
	}elseif ((is_error_message()) || (empty($_POST["local_graph_id"])) || (isset($_POST["save_component_graph_diff"])) || ($_POST["graph_template_id"] != $_POST["_graph_template_id"]) || ($_POST["device_id"] != $_POST["_device_id"])) {
		header("Location: graphs.php?action=edit&id=" . (empty($local_graph_id) ? $_POST["local_graph_id"] : $local_graph_id) . (isset($_POST["device_id"]) ? "&device_id=" . $_POST["device_id"] : ""));
	}else{
		header("Location: graphs.php");
	}
	exit;
}

/* ------------------------
    The "actions" function
   ------------------------ */

function graph_form_actions() {
	require(CACTI_BASE_PATH . "/include/graph_tree/graph_tree_arrays.php");

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === GRAPH_ACTION_DELETE) { /* delete */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */
			}

			if (!isset($_POST["delete_type"])) { $_POST["delete_type"] = 1; }

			switch (get_request_var_post("delete_type")) {
				case '2': /* delete all data sources referenced by this graph */
					$data_sources = array_rekey(db_fetch_assoc("SELECT data_template_data.local_data_id
						FROM (data_template_rrd, data_template_data, graph_templates_item)
						WHERE graph_templates_item.task_item_id=data_template_rrd.id
						AND data_template_rrd.local_data_id=data_template_data.local_data_id
						AND graph_templates_item.local_graph_id=" . $selected_items[$i] . "
						AND data_template_data.local_data_id > 0"), "local_data_id", "local_data_id");

					if (sizeof($data_sources)) {
						data_source_remove_multi($data_sources);
					}

					break;
			}

			graph_remove_multi($selected_items);
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_CHANGE_TEMPLATE) { /* change graph template */
			input_validate_input_number(get_request_var_post("graph_template_id"));
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				change_graph_template($selected_items[$i], get_request_var_post("graph_template_id"), true);
			}
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_DUPLICATE) { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				duplicate_graph($selected_items[$i], 0, get_request_var_post("title_format"));
			}
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_CONVERT_TO_TEMPLATE) { /* graph -> graph template */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				graph_to_graph_template($selected_items[$i], get_request_var_post("title_format"));
			}
		}elseif (preg_match("/^tr_([0-9]+)$/", get_request_var_post("drp_action"), $matches)) { /* place on tree */
			input_validate_input_number(get_request_var_post("tree_id"));
			input_validate_input_number(get_request_var_post("tree_item_id"));
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				tree_item_save(0, get_request_var_post("tree_id"), TREE_ITEM_TYPE_GRAPH, get_request_var_post("tree_item_id"), "", $selected_items[$i], read_graph_config_option("default_rra_id"), 0, 0, 0, false);
			}
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_CHANGE_HOST) { /* change device */
			input_validate_input_number(get_request_var_post("device_id"));
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				db_execute("update graph_local set device_id=" . $_POST["device_id"] . " where id=" . $selected_items[$i]);
				update_graph_title_cache($selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_REAPPLY_SUGGESTED_NAMES) { /* reapply suggested naming */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				reapply_suggested_graph_title($selected_items[$i]);
				update_graph_title_cache($selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_RESIZE) { /* resize graphs */
			input_validate_input_number(get_request_var_post("graph_height"));
			input_validate_input_number(get_request_var_post("graph_width"));
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				resize_graphs($selected_items[$i], get_request_var_post('graph_width'), get_request_var_post('graph_height'));
			}
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_ENABLE_EXPORT) { /* enable graph export */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */
				db_execute("UPDATE graph_templates_graph SET export='on' WHERE local_graph_id=" . $selected_items[$i]);
			}
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_DISABLE_EXPORT) { /* disable graph export */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */
				db_execute("UPDATE graph_templates_graph SET export='' WHERE local_graph_id=" . $selected_items[$i]);
			}
		} else {
			plugin_hook_function('graphs_action_execute', get_request_var_post('drp_action'));
		}

		header("Location: graphs.php");
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

			$graph_list .= "<li>" . get_graph_title($matches[1]) . "</li>";
			$graph_array[] = $matches[1];
		}
	}

	include_once(CACTI_BASE_PATH . "/include/top_header.php");

	/* add a list of tree names to the actions dropdown */
	$graph_actions = array_merge(graph_actions_list(), tree_add_tree_names_to_actions_array());

	$graph_actions[ACTION_NONE] = __("None");

	html_start_box($graph_actions{get_request_var_post("drp_action")}, "60", "3", "center", "");

	print "<form action='graphs.php' method='post'>\n";

	if (sizeof($graph_array)) {
		if (get_request_var_post("drp_action") === ACTION_NONE) { /* NONE */
			print "	<tr>
						<td class='textArea'>
							<p>" . __("You did not select a valid action. Please select 'Return' to return to the previous menu.") . "</p>
						</td>
					</tr>\n";
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_DELETE) { /* delete */
			$graphs = array();

			/* find out which (if any) data sources are being used by this graph, so we can tell the user */
			if (isset($graph_array)) {
				$data_sources = db_fetch_assoc("select
					data_template_data.local_data_id,
					data_template_data.name_cache
					from (data_template_rrd,data_template_data,graph_templates_item)
					where graph_templates_item.task_item_id=data_template_rrd.id
					and data_template_rrd.local_data_id=data_template_data.local_data_id
					and " . array_to_sql_or($graph_array, "graph_templates_item.local_graph_id") . "
					and data_template_data.local_data_id > 0
					group by data_template_data.local_data_id
					order by data_template_data.name_cache");
			}

			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Graph(s) will be deleted.") . "</p>
						<p><ul>$graph_list</ul></p>
						";
						if (sizeof($data_sources) > 0) {
							print "<tr class='rowAlternate1'><td class='textArea'><p class='textArea'>" . __("The following Data Source(s) are in use by these Graph(s):") . "</p>\n";

							foreach ($data_sources as $data_source) {
								print "<strong>" . $data_source["name_cache"] . "</strong><br>\n";
							}

							print "<br>";
							form_radio_button("delete_type", "1", "1", __("Leave the Data Source(s) untouched."), "1"); print "<br>";
							form_radio_button("delete_type", "1", "2", __("Delete all <strong>Data Source(s)</strong> referenced by these Graph(s)."), "1"); print "<br>";
							print "</td></tr>";
						}
					print "
					</td>
				</tr>\n";

			$title = __("Delete Graph(s)");
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_CHANGE_TEMPLATE) { /* change graph template */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Graph(s) will be re-associated with the Graph Template below.  Be aware that all warnings will be suppressed during the conversion, so graph data loss is possible.") . "</p>
						<p><ul>$graph_list</ul></p>
						<p><strong>" . __("New Graph Template:") . "</strong><br>"; form_dropdown("graph_template_id",db_fetch_assoc("select graph_templates.id,graph_templates.name from graph_templates order by name"),"name","id","","","0"); print "</p>
					</td>
				</tr>\n";

			$title = __("Change Graph(s) Graph Template");
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_DUPLICATE) { /* duplicate */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Graph(s) will be duplicated. You can optionally change the title format for the new Graph(s).") . "</p>
						<p><ul>$graph_list</ul></p>
						<p><strong>" . __("Title Format:") . "</strong><br>"; form_text_box("title_format", __("<graph_title> (1)"), "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n";

			$title = __("Duplicate Graph(s)");
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_CONVERT_TO_TEMPLATE) { /* graph -> graph template */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Graph(s) will be converted into Graph Template(s).  You can optionally change the title format for the new Graph Template(s).") . "</p>
						<p><ul>$graph_list<ul></p>
						<p><strong>" . __("Title Format:") . "</strong><br>"; form_text_box("title_format", __("<graph_title> Template"), "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n";

			$title = __("Convert Graph(s) to Graph Template(s)");
		}elseif (preg_match("/^tr_([0-9]+)$/", get_request_var_post("drp_action"), $matches)) { /* place on tree */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Graph(s) will be placed under the Tree Branch selected below.") . "</p>
						<p><ul>$graph_list</ul></p>
						<p><strong>" . __("Destination Branch:") . "</strong><br>"; grow_dropdown_tree($matches[1], "tree_item_id", "0"); print "</p>
					</td>
				</tr>\n
				<div><input type='hidden' name='tree_id' value='" . $matches[1] . "'></div>\n";

			$title = __("Place Graph(s) on a Tree");
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_CHANGE_HOST) { /* change device */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Graph(s) will be re-associated with the Device selected below.") . "</p>
						<p><ul>$graph_list</ul></p>
						<p><strong>" . __("New Device:") . "</strong><br>"; form_dropdown("device_id",db_fetch_assoc("select id,CONCAT_WS('',description,' (',hostname,')') as name from device order by description,hostname"),"name","id","","","0"); print "</p>
					</td>
				</tr>\n";

			$title = __("Change Graph(s) Device");
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_REAPPLY_SUGGESTED_NAMES) { /* reapply suggested naming to device */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Graph(s) will have their suggested naming conventions recalculated and applied to the Graph(s).") . "</p>
						<p><ul>$graph_list</ul></p>
					</td>
				</tr>\n";

			$title = __("Reapply Suggested Names");
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_RESIZE) { /* reapply suggested naming to device */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Graph(s) will be resized per your specifications.") . "</p>
						<p><ul>$graph_list</ul></p>
						<p><strong>" . __("Graph Height:") . "</strong><br>"; form_text_box("graph_height", "", "", "255", "30", "text"); print "</p>
						<p><strong>" . __("Graph Width:") . "</strong><br>"; form_text_box("graph_width", "", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n";

			$title = __("Resize Graph(s)");
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_ENABLE_EXPORT) { /* enable graph export */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Graph(s) will be enabled for Graph Export.") . "</p>
						<p><ul>$graph_list</ul></p>
					</td>
				</tr>\n";

			$title = __("Enable Graph Export");
		}elseif (get_request_var_post("drp_action") === GRAPH_ACTION_DISABLE_EXPORT) { /* disable graph export */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Graph(s) will be disabled for Graph Export.") . "</p>
						<p><ul>$graph_list</ul></p>
					</td>
				</tr>\n";

			$title = __("Disable Graph Export");
		} else {
			$save['drp_action'] = $_POST['drp_action'];
			$save['graph_list'] = $graph_list;
			$save['graph_array'] = $graph_array;
			$save['title'] = "";
			plugin_hook_function('graphs_action_prepare', $save);

			if (strlen($save['title'])) {
				$title = $save['title'];
			}else{
				$title = '';
			}
		}
	} else {
		print "	<tr>
				<td class='textArea'>
					<p>" . __("You must first select a Graph.  Please select 'Return' to return to the previous menu.") . "</p>
				</td>
			</tr>\n";
	}

	if (!sizeof($graph_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button();
	}else{
		form_continue(serialize($graph_array), get_request_var_post("drp_action"), $title);
	}

	html_end_box();

	include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
}

/* -----------------------
    item - Graph Items
   ----------------------- */

function graph_item() {
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
			data_template_rrd.data_source_name,
			cdef.name as cdef_name,
			colors.hex
			from graph_templates_item
			left join data_template_rrd on (graph_templates_item.task_item_id=data_template_rrd.id)
			left join data_local on (data_template_rrd.local_data_id=data_local.id)
			left join data_template_data on (data_local.id=data_template_data.local_data_id)
			left join cdef on (cdef_id=cdef.id)
			left join colors on (color_id=colors.id)
			where graph_templates_item.local_graph_id=" . get_request_var("id") . "
			order by graph_templates_item.sequence");

		$device_id = db_fetch_cell("select device_id from graph_local where id=" . get_request_var("id"));
		$header_label = __("[edit: %s]", get_graph_title(get_request_var("id")));
	}

	$graph_template_id = db_fetch_cell("select graph_template_id from graph_local where id=" . get_request_var("id"));

	if (empty($graph_template_id)) {
		$add_text = "graphs_items.php?action=item_edit&amp;local_graph_id=" . get_request_var("id") . "&amp;device_id=$device_id";
	}else{
		$add_text = "";
	}

	html_start_box(__("Graph Items") . " $header_label", "100", "3", "center", $add_text);
	draw_graph_items_list($template_item_list, "graphs_items.php", "local_graph_id=" . get_request_var("id"), (empty($graph_template_id) ? false : true));
	html_end_box();
}

/* ------------------------------------
    graph - Graphs
   ------------------------------------ */

function graph_item_dnd() {
	/* ================= Input validation ================= */
	input_validate_input_number(get_request_var("id"));

	if(!isset($_REQUEST['graph_item']) || !is_array($_REQUEST['graph_item'])) exit;
	/* graph_item table contains one row defined as "nodrag&nodrop" */
	unset($_REQUEST['graph_item'][0]);

	/* delivered graph_item ids has to be exactly the same like we have stored */
	$old_order = array();
	$new_order = $_REQUEST['graph_item'];

	$sql = "SELECT id, sequence FROM graph_templates_item WHERE local_graph_id = " . $_GET['id'] . " and graph_template_id=0";
	$graph_templates_items = db_fetch_assoc($sql);

	if(sizeof($graph_templates_items)>0) {
		foreach($graph_templates_items as $item) {
			$old_order[$item['sequence']] = $item['id'];
		}
	}else {
		exit;
	}

	# compute difference of arrays
	$diff = array_diff_assoc($new_order, $old_order);
	# nothing to do?
	if(sizeof($diff) == 0) exit;
	/* ==================================================== */

	foreach($diff as $sequence => $graph_templates_item_id) {
		# update the template item itself
		$sql = "UPDATE graph_templates_item SET sequence = $sequence WHERE id = $graph_templates_item_id";
		db_execute($sql);
		# update all items referring the template item
		$sql = "UPDATE graph_templates_item SET sequence = $sequence WHERE local_graph_template_item_id = $graph_templates_item_id";
		db_execute($sql);
	}
}

function graph_diff() {
	require(CACTI_BASE_PATH . "/include/presets/preset_rra_arrays.php");
	require(CACTI_BASE_PATH . "/include/graph/graph_arrays.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("graph_template_id"));
	/* ==================================================== */

	$template_query = "select
		graph_templates_item.id,
		graph_templates_item.text_format,
		graph_templates_item.value,
		graph_templates_item.hard_return,
		graph_templates_item.consolidation_function_id,
		graph_templates_item.graph_type_id,
		CONCAT_WS(' - ',data_template_data.name,data_template_rrd.data_source_name) as task_item_id,
		cdef.name as cdef_id,
		colors.hex as color_id
		from graph_templates_item
		left join data_template_rrd on (graph_templates_item.task_item_id=data_template_rrd.id)
		left join data_local on (data_template_rrd.local_data_id=data_local.id)
		left join data_template_data on (data_local.id=data_template_data.local_data_id)
		left join cdef on (cdef_id=cdef.id)
		left join colors on (color_id=colors.id)";

	/* first, get information about the graph template as that's what we're going to model this
	graph after */
	$graph_template_items = db_fetch_assoc("
		$template_query
		where graph_templates_item.graph_template_id=" . get_request_var("graph_template_id") . "
		and graph_templates_item.local_graph_id=0
		order by graph_templates_item.sequence");

	/* next, get information about the current graph so we can make the appropriate comparisons */
	$graph_items = db_fetch_assoc("
		$template_query
		where graph_templates_item.local_graph_id=" . get_request_var("id") . "
		order by graph_templates_item.sequence");

	$graph_template_inputs = db_fetch_assoc("select
		graph_template_input.column_name,
		graph_template_input_defs.graph_template_item_id
		from (graph_template_input,graph_template_input_defs)
		where graph_template_input.id=graph_template_input_defs.graph_template_input_id
		and graph_template_input.graph_template_id=" . get_request_var("graph_template_id"));

	/* ok, we want to loop through the array with the GREATEST number of items so we don't have to worry
	about tacking items on the end */
	if (sizeof($graph_template_items) > sizeof($graph_items)) {
		$items = $graph_template_items;
	}else{
		$items = $graph_items;
	}

	?>
	<table class='topBoxAlt'>
		<tr>
			<td class="textArea">
				<?php print __("The template you have selected requires some changes to be made to the structure of your graph. Below is a preview of your graph along with changes that need to be completed as shown in the left-hand column.");?>
			</td>
		</tr>
	</table>
	<br>
	<?php

	html_start_box(__("Graph Preview"), "100", "3", "center", "");

	$graph_item_actions = array("normal" => "", "add" => "+", "delete" => "-");

	$group_counter = 0; $i = 0; $mode = "normal"; $_graph_type_name = "";

	if (sizeof($items) > 0) {
		$struct_graph_item = graph_item_form_list();
	foreach ($items as $item) {
		reset($struct_graph_item);

		/* graph grouping display logic */
		$bold_this_row = false; $use_custom_row_color = false; $action_css = ""; unset($graph_preview_item_values);

		if ((sizeof($graph_template_items) > sizeof($graph_items)) && ($i >= sizeof($graph_items))) {
			$mode = "add";
			$user_message = __("When you click save, the items marked with a '<strong>+</strong>' will be added <strong>(Recommended)</strong>.");
		}elseif ((sizeof($graph_template_items) < sizeof($graph_items)) && ($i >= sizeof($graph_template_items))) {
			$mode = "delete";
			$user_message = __("When you click save, the items marked with a '<strong>-</strong>' will be removed <strong>(Recommended)</strong>.");
		}

		/* here is the fun meshing part. first we check the graph template to see if there is an input
		for each field of this row. if there is, we revert to the value stored in the graph, if not
		we revert to the value stored in the template. got that? ;) */
		for ($j=0; ($j < count($graph_template_inputs)); $j++) {
			if ($graph_template_inputs[$j]["graph_template_item_id"] == (isset($graph_template_items[$i]["id"]) ? $graph_template_items[$i]["id"] : "")) {
				/* if we find out that there is an "input" covering this field/item, use the
				value from the graph, not the template */
				$graph_item_field_name = (isset($graph_template_inputs[$j]["column_name"]) ? $graph_template_inputs[$j]["column_name"] : "");
				$graph_preview_item_values[$graph_item_field_name] = (isset($graph_items[$i][$graph_item_field_name]) ? $graph_items[$i][$graph_item_field_name] : "");
			}
		}

		/* go back through each graph field and find out which ones haven't been covered by the
		"inputs" above. for each one, use the value from the template */
		while (list($field_name, $field_array) = each($struct_graph_item)) {
			if ($mode == "delete") {
				$graph_preview_item_values[$field_name] = (isset($graph_items[$i][$field_name]) ? $graph_items[$i][$field_name] : "");
			}elseif (!isset($graph_preview_item_values[$field_name])) {
				$graph_preview_item_values[$field_name] = (isset($graph_template_items[$i][$field_name]) ? $graph_template_items[$i][$field_name] : "");
			}
		}

		/* "prepare" array values */
		$consolidation_function_id = $graph_preview_item_values["consolidation_function_id"];
		$graph_type_id = $graph_preview_item_values["graph_type_id"];

		/* color logic */
		if (($graph_type_id != GRAPH_ITEM_TYPE_GPRINT) && ($graph_item_types[$graph_type_id] != $_graph_type_name)) {
			$bold_this_row = true; $use_custom_row_color = true; $hard_return = "";

			if ($group_counter % 2 == 0) {
				$alternate_color_1 = "EEEEEE";
				$alternate_color_2 = "EEEEEE";
				$custom_row_color = "D5D5D5";
			}else{
				$alternate_color_1 = "E7E9F2";
				$alternate_color_2 = "E7E9F2";
				$custom_row_color = "D2D6E7";
			}

			$group_counter++;
		}

		$_graph_type_name = $graph_item_types[$graph_type_id];

		/* alternating row colors */
		if ($use_custom_row_color == false) {
			if ($i % 2 == 0) {
				$action_column_color = $alternate_color_1;
			}else{
				$action_column_color = $alternate_color_2;
			}
		}else{
			$action_column_color = $custom_row_color;
		}

		print "<tr bgcolor='#$action_column_color'>"; $i++;

		/* make the left-hand column blue or red depending on if "add"/"remove" mode is set */
		if ($mode == "add") {
			$action_column_color = "00438C";
			$action_css = "";
		}elseif ($mode == "delete") {
			$action_column_color = "C63636";
			$action_css = "text-decoration: line-through;";
		}

		if ($bold_this_row == true) {
			$action_css .= " font-weight:bold;";
		}

		/* draw the TD that shows the user whether we are going to: KEEP, ADD, or DROP the item */
		print "<td width='1%' bgcolor='#$action_column_color' style='font-weight: bold; color: white;'>" . $graph_item_actions[$mode] . "</td>";
		print "<td style='$action_css'><strong>" . __("Item") . " # " . $i . "</strong></td>\n";

		if (empty($graph_preview_item_values["task_item_id"])) { $graph_preview_item_values["task_item_id"] = "No Task"; }

		switch ($graph_type_id) {
			case GRAPH_ITEM_TYPE_AREA:
			case GRAPH_ITEM_TYPE_AREASTACK:
			case GRAPH_ITEM_TYPE_GPRINT:
			case GRAPH_ITEM_TYPE_LINE1:
			case GRAPH_ITEM_TYPE_LINE2:
			case GRAPH_ITEM_TYPE_LINE3:
			case GRAPH_ITEM_TYPE_LINESTACK:
				$matrix_title = "(" . $graph_preview_item_values["task_item_id"] . "): " . $graph_preview_item_values["text_format"];
				break;
			case GRAPH_ITEM_TYPE_HRULE:
				$matrix_title = "VRULE: " . $graph_preview_item_values["value"];
				break;
				case GRAPH_ITEM_TYPE_VRULE:
				$matrix_title = "HRULE: " . $graph_preview_item_values["value"];
				break;
			case GRAPH_ITEM_TYPE_COMMENT:
				$matrix_title = "COMMENT: " . $graph_preview_item_values["text_format"];
				break;
		}

		/* use the cdef name (if in use) if all else fails */
		if ($matrix_title == "") {
			if ($graph_preview_item_values["cdef_id"] != "") {
				$matrix_title .= "CDEF: " . $graph_preview_item_values["cdef_id"];
			}
		}

		if ($graph_preview_item_values["hard_return"] == CHECKED) {
			$hard_return = "<strong><font color=\"#FF0000\">&lt;HR&gt;</font></strong>";
		}

		print "<td style='$action_css'>" . htmlspecialchars($matrix_title) . $hard_return . "</td>\n";
		print "<td style='$action_css'>" . $graph_item_types{$graph_preview_item_values["graph_type_id"]} . "</td>\n";
		print "<td style='$action_css'>" . $consolidation_functions{$graph_preview_item_values["consolidation_function_id"]} . "</td>\n";
		print "<td" . ((!empty($graph_preview_item_values["color_id"])) ? " bgcolor='#" . $graph_preview_item_values["color_id"] . "'" : "") . " width='1%'>&nbsp;</td>\n";
		print "<td style='$action_css'>" . $graph_preview_item_values["color_id"] . "</td>\n";

		print "</tr>";
	}
	}else{
		form_alternate_row_color();
		?>
			<td colspan="7">
				<em><?php print __("No Items");?></em>
			</td>
		<?php
		form_end_row();
	}
	html_end_box();

	?>
	<form action="graphs.php" method="post">
	<table class='topBoxAlt'>
		<tr>
			<td class="textArea">
				<input type='radio' name='type' value='1' checked>&nbsp;<?php print $user_message;?><br>
				<input type='radio' name='type' value='2'>&nbsp;<?php print __("When you click save, the graph items will remain untouched (could cause inconsistencies).");?>
			</td>
		</tr>
	</table>

	<br>

	<input type="hidden" name="action" value="save">
	<input type="hidden" name="save_component_graph_diff" value="1">
	<input type="hidden" name="local_graph_id" value="<?php print get_request_var("id");?>">
	<input type="hidden" name="graph_template_id" value="<?php print get_request_var("graph_template_id");?>">
	<?php

	form_save_button_alt("action!edit|id!" . get_request_var("id"));
}

function graph_edit() {

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	$use_graph_template = true;

	if (!empty($_GET["id"])) {
		$local_graph_template_graph_id = db_fetch_cell("select local_graph_template_graph_id from graph_templates_graph where local_graph_id=" . get_request_var("id"));

		$graphs = db_fetch_row("select * from graph_templates_graph where local_graph_id=" . get_request_var("id"));

		$graphs_template = db_fetch_row("select * from graph_templates_graph where id=$local_graph_template_graph_id");

		$device_id = db_fetch_cell("select device_id from graph_local where id=" . get_request_var("id"));
		$header_label = __("[edit: ") . get_graph_title(get_request_var("id")) . "]";

		if ($graphs["graph_template_id"] == "0") {
			$use_graph_template = false;
		}
		?>
		<script type="text/javascript">
		<!--
		var disabled = true;

		$().ready(function() {
			$("input").attr("disabled","disabled");
			$("select").attr("disabled","disabled");
			$("#cancel").removeAttr("disabled");
			$("#graph_options").closest("td").before("<td class='lock w1 textHeaderDark'><?php print __("Template is locked");?></td>");
		});

		function changeGraphState() {
			if (disabled) {
				$("input").removeAttr("disabled");
				$("select").removeAttr("disabled");
				$(".lock").html("<?php print __("Template is unlocked");?>");
				disabled = false;
				rrdtool_graph_dependencies(); // even when unlocking, disable distinct rrdtool options
			}else{
				$("input").attr("disabled","disabled");
				$("select").attr("disabled","disabled");
				$("#cancel").removeAttr("disabled");
				$(".lock").html("<?php print __("Template is locked");?>");
				disabled = true;
			}
		}
		//-->
		</script>
		<?php
	}else{
		$graphs = array();
		$graphs_template = array();
		$header_label = __("[new]");
		$use_graph_template = false;
	}

	/* handle debug mode */
	if (isset($_GET["debug"])) {
		if (get_request_var("debug") == "0") {
			kill_session_var("graph_debug_mode");
		}elseif (get_request_var("debug") == "1") {
			$_SESSION["graph_debug_mode"] = true;
		}
	}

	$dd_menu_options = 'cacti_dd_menu=graph_options&graph_id=' . (isset($_GET["id"]) ? get_request_var("id") : 0);

	if (!empty($graphs["graph_template_id"])) {
		$dd_menu_options .= '&graph_template_id=' . (isset($graphs["graph_template_id"]) ? $graphs["graph_template_id"] : "0");
	}
	if (!empty($_GET["device_id"]) || !empty($device_id)) {
		$dd_menu_options .= '&device_id=' . (isset($_GET["device_id"]) ? get_request_var("device_id") : $device_id);
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='graph_edit'>\n";
	html_start_box(__("Graph Template Selection") . " $header_label", "100", 0, "center", (!empty($_GET['id']) ? "menu::" . __("Graph Options") . ":graph_options:html_start_box:" . $dd_menu_options : ""), "");

	$form_array = array(
		"graph_template_id" => array(
			"method" => "autocomplete",
			"callback_function" => "graphs.php?action=ajax_get_graph_templates",
			"friendly_name" => __("Selected Graph Template"),
			"description" => __("Choose a graph template to apply to this graph.  Please note that graph data may be lost if you change the graph template after one is already applied."),
			"id" => (isset($graphs["graph_template_id"]) ? $graphs["graph_template_id"] : "0"),
			"name" => db_fetch_cell("SELECT name FROM graph_templates WHERE id=" . (isset($graphs["graph_template_id"]) ? $graphs["graph_template_id"] : "0"))
			),
		"device_id" => array(
			"method" => "autocomplete",
			"callback_function" => "graphs.php?action=ajax_get_devices_detailed",
			"friendly_name" => __("Host"),
			"description" => __("Choose the device that this graph belongs to."),
			"id" => (isset($_GET["device_id"]) ? get_request_var("device_id") : $device_id),
			"name" => db_fetch_cell("SELECT CONCAT_WS('',description,' (',hostname,')') FROM device WHERE id=" . (isset($_GET['device_id']) ? $_GET['device_id'] : $device_id))
			),
		"graph_template_graph_id" => array(
			"method" => "hidden",
			"value" => (isset($graphs["id"]) ? $graphs["id"] : "0")
			),
		"local_graph_id" => array(
			"method" => "hidden",
			"value" => (isset($graphs["local_graph_id"]) ? $graphs["local_graph_id"] : "0")
			),
		"local_graph_template_graph_id" => array(
			"method" => "hidden",
			"value" => (isset($graphs["local_graph_template_graph_id"]) ? $graphs["local_graph_template_graph_id"] : "0")
			),
		"_graph_template_id" => array(
			"method" => "hidden",
			"value" => (isset($graphs["graph_template_id"]) ? $graphs["graph_template_id"] : "0")
			),
		"_device_id" => array(
			"method" => "hidden",
			"value" => (isset($device_id) ? $device_id : "0")
			)
		);

	draw_edit_form(
		array(
			"config" => array(),
			"fields" => $form_array
			)
		);

	html_end_box();

#	print "<form method='post' action='graphs.php'>\n";
	/* only display the "inputs" area if we are using a graph template for this graph */
	if (!empty($graphs["graph_template_id"])) {
		html_start_box(__("Supplemental Graph Template Data"), "100", "0", "center", "");

		draw_nontemplated_fields_graph($graphs["graph_template_id"], $graphs, "|field|", __("Graph Fields"), true, 0);
		draw_nontemplated_fields_graph_item($graphs["graph_template_id"], get_request_var("id"), "|field|_|id|", __("Graph Item Fields"));

		html_end_box();
	}

	/* graph item list goes here */
	if ((!empty($_GET["id"])) && (!array_key_exists("graph_template_id", $graphs))) {
		graph_template_item();
	}

	if (!empty($_GET["id"])) {
		print "<div id=\"center\">";
		print "<img src=" . htmlspecialchars("graph_image.php?action=edit&local_graph_id=" . get_request_var("id") . "&rra_id=" . read_graph_config_option("default_rra_id")) . " alt=\"" . get_request_var("id") . "\">";
		
		if ((isset($_SESSION["graph_debug_mode"])) && (isset($_GET["id"]))) {
					$graph_data_array = array();
					$graph_data_array["output_flag"] = RRDTOOL_OUTPUT_STDERR;
					/* make rrdtool_function_graph to only print the command without executing it */
					$graph_data_array["print_source"] = 1;
						
		print "<br><span class=\"textInfo\">" .  __("RRDTool Command:") . "</span><br>";
					print "<pre>";
					print rrdtool_function_graph(get_request_var("id"), read_graph_config_option("default_rra_id"), $graph_data_array);
					print "</pre>";
		print "<span class=\"textInfo\">" . __("RRDTool Says:") . "</span><br>";
		
		/* make rrdtool_function_graph to generate AND execute the rrd command, but only for fetching the "return code" */
		unset($graph_data_array["print_source"]);
		print "<pre>";
		print rrdtool_function_graph(get_request_var("id"), read_graph_config_option("default_rra_id"), $graph_data_array);
		print "</pre>";
				}
	}

	if (((isset($_GET["id"])) || (isset($_GET["new"]))) && (empty($graphs["graph_template_id"]))) {
		html_start_box(__("Graph Configuration"), "0", "center", "");

		############
		html_start_box(__("Labels"), "100", "0", "center", "", true);
		draw_template_edit_form('header_graph_labels', graph_labels_form_list(), $graphs, $use_graph_template);
		html_end_box(false);
		/* TODO: we should not use rrd version in the code, when going data-driven */
		if ( read_config_option("rrdtool_version") != RRD_VERSION_1_0 && read_config_option("rrdtool_version") != RRD_VERSION_1_2) {
			html_start_box(__("Right Axis Settings"), "100", "0", "center", "", true, "table_graph_template_right_axis");
			draw_template_edit_form('header_graph_right_axis', graph_right_axis_form_list(), $graphs, $use_graph_template);
		}
		html_end_box(false);
		html_start_box(__("Graph Template Size"), "100", "0", "center", "", true, "table_graph_template_size");
		draw_template_edit_form('header_graph_size', graph_size_form_list(), $graphs, $use_graph_template);
		html_end_box(false);
		html_start_box(__("Graph Template Limits"), "100", "0", "center", "", true, "table_graph_template_limits");
		draw_template_edit_form('header_graph_limits', graph_limits_form_list(), $graphs, $use_graph_template);
		html_end_box(false);
		html_start_box(__("Graph Template Grid"), "100", "0", "center", "", true, "table_graph_template_grid");
		draw_template_edit_form('header_graph_grid', graph_grid_form_list(), $graphs, $use_graph_template);
		html_end_box(false);
		html_start_box(__("Graph Template Color"), "100", "0", "center", "", true, "table_graph_template_color");
		draw_template_edit_form('header_graph_color', graph_color_form_list(), $graphs, $use_graph_template);
		html_end_box(false);
		html_start_box(__("Graph Template Legend"), "100", "0", "center", "", true, "table_graph_template_misc");
		draw_template_edit_form('header_graph_legend', graph_legend_form_list(), $graphs, $use_graph_template);
		html_end_box(false);
		html_start_box(__("Graph Template Misc"), "100", "0", "center", "", true, "table_graph_template_misc");
		draw_template_edit_form('header_graph_misc', graph_misc_form_list(), $graphs, $use_graph_template);
		html_end_box(false);
		html_start_box(__("Graph Template Cacti Specifics"), "100", "0", "center", "", true, "table_graph_template_cacti");
		draw_template_edit_form('header_graph_cacti', graph_cacti_form_list(), $graphs, $use_graph_template);
		html_end_box(false);
	}

	if ((isset($_GET["id"])) || (isset($_GET["new"]))) {
		form_hidden_box("save_component_graph","1","");
		form_hidden_box("save_component_input","1","");
	}else{
		form_hidden_box("save_component_graph_new","1","");
	}

	form_hidden_box("hidden_rrdtool_version", read_config_option("rrdtool_version"), "");
	form_save_button_alt();

	include_once(CACTI_BASE_PATH . "/access/js/colorpicker.js");
	include_once(CACTI_BASE_PATH . "/access/js/graph_template_options.js");

	?>
	<script type="text/javascript">
	$('#graph_item').tableDnD({
		onDrop: function(table, row) {
			$.get("graphs.php?action=ajax_graph_item_dnd&id=<?php isset($_GET["id"]) ? print get_request_var("id") : print "";?>&"+$.tableDnD.serialize());
//			location.reload();
		}
	});
 	</script>
<?php
}

function graphs_filter() {
	global $item_rows;

	html_start_box(__("Graph Management"), "100", "3", "center", "graphs.php?action=edit&device_id=" . html_get_page_variable("device_id"), true);
	?>
	<tr class='rowAlternate2'>
		<td>
			<form name="form_graph_id" action="graphs.php">
			<table cellpadding="0" cellspacing="3">
				<tr>
					<td>
						&nbsp;<?php print __("Host:");?>&nbsp;
						<?php
						if (isset($_REQUEST["device_id"])) {
							$hostname = db_fetch_cell("SELECT description as name FROM device WHERE id=" . html_get_page_variable("device_id") . " ORDER BY description,hostname");
						} else {
							$hostname = "";
						}
						?>
						<input class="ac_field" type="text" id="device" size="30" value="<?php print $hostname; ?>">
						<input type="hidden" id="device_id">
					</td>
					<td>
						&nbsp;<?php print __("Template:");?>&nbsp;
						<select name="template_id" onChange="applyGraphsFilterChange(document.form_graph_id)">
							<option value="-1"<?php if (html_get_page_variable("template_id") == "-1") {?> selected<?php }?>><?php print __("Any");?></option>
							<option value="0"<?php if (html_get_page_variable("template_id") == "0") {?> selected<?php }?>><?php print __("None");?></option>
							<?php
							if (read_config_option("auth_method") != AUTH_METHOD_NONE) {
								$templates = db_fetch_assoc("SELECT DISTINCT graph_templates.id, graph_templates.name
									FROM (graph_templates_graph,graph_local)
									LEFT JOIN device ON (device.id=graph_local.device_id)
									LEFT JOIN graph_templates ON (graph_templates.id=graph_local.graph_template_id)
									LEFT JOIN user_auth_perms ON ((graph_templates_graph.local_graph_id=user_auth_perms.item_id and user_auth_perms.type=" . PERM_GRAPHS . " and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (device.id=user_auth_perms.item_id and user_auth_perms.type=" . PERM_DEVICES . " and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (graph_templates.id=user_auth_perms.item_id and user_auth_perms.type=" . PERM_GRAPH_TEMPLATES . " and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . "))
									WHERE graph_templates_graph.local_graph_id=graph_local.id
									AND graph_templates.id IS NOT NULL
									" . (empty($sql_where) ? "" : "AND $sql_where") . "
									ORDER BY name");
							}else{
								$templates = db_fetch_assoc("SELECT DISTINCT graph_templates.id, graph_templates.name
									FROM graph_templates
									ORDER BY name");
							}

							if (sizeof($templates) > 0) {
							foreach ($templates as $template) {
								print "<option value='" . $template["id"] . "'"; if (html_get_page_variable("template_id") == $template["id"]) { print " selected"; } print ">" . title_trim($template["name"], 40) . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td class="nw120">
						&nbsp;<input type="button" Value="<?php print __("Go");?>" name="go" onClick="applyGraphsFilterChange(document.form_graph_id)">
						<input type="button" Value="<?php print __("Clear");?>" name="clear" onClick="clearGraphsFilterChange(document.form_graph_id)">
					</td>
				</tr>
				<tr>
					<td>
						&nbsp;<?php print __("Search:");?>&nbsp;
						<input type="text" name="filter" size="40" value="<?php print html_get_page_variable("filter");?>" onChange="applyGraphsFilterChange(document.form_graph_id)">
					</td>
					<td>
						&nbsp;<?php print __("Rows:");?>&nbsp;
						<select name="rows" onChange="applyGraphsFilterChange(document.form_graph_id)">
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
	<!--
	$().ready(function() {
		$("#device").autocomplete("graphs.php?action=ajax_get_devices_brief", { minChars: 0, max: 8, highlight: false, scroll: true, scrollHeight: 300 });
		$("#device").result(function(event, data, formatted) {
			if (data) {
				$(this).parent().find("#device_id").val(data[1]);
				applyGraphsFilterChange(document.form_graph_id);
			}else{
				$(this).parent().find("#device_id").val(0);
			}
		});
	});

	function clearGraphsFilterChange(objForm) {
		strURL = '?filter=';
		if (objForm.tab) {
			strURL = strURL + '&tab=' + objForm.tab.value;
			<?php
			# now look for more parameters
			if (isset($_REQUEST["device_id"])) {
				print "strURL = strURL + '&device_id=" . html_get_page_variable("device_id") . "';";
			}
			print "strURL = strURL + '&template_id=-1';";
			?>
		}else {
			strURL = strURL + '&device_id=-1';
			strURL = strURL + '&template_id=-1';
		}

		strURL = strURL + '&rows=-1';
		document.location = strURL;
	}

	function applyGraphsFilterChange(objForm) {
		strURL = '?filter=' + objForm.filter.value;
		if (objForm.tab) {
			strURL = strURL + '&tab=' + objForm.tab.value;
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
		document.location = strURL;
	}
	-->
	</script>
	<?php
}

function get_graph_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	if (strlen(html_get_page_variable("filter"))) {
		$sql_where = "AND (graph_templates_graph.title_cache like '%%" . html_get_page_variable("filter") . "%%'" .
			" OR graph_templates.name like '%%" . html_get_page_variable("filter") . "%%')";
	}else{
		$sql_where = "";
	}

	if (html_get_page_variable("device_id") == "-1") {
		/* Show all items */
	}elseif (html_get_page_variable("device_id") == "0") {
		$sql_where .= " AND graph_local.device_id=0";
	}elseif (html_get_page_variable("device_id") != "") {
		$sql_where .= " AND graph_local.device_id=" . html_get_page_variable("device_id");
	}

	if (html_get_page_variable("template_id") == "-1") {
		/* Show all items */
	}elseif (html_get_page_variable("template_id") == "0") {
		$sql_where .= " AND graph_templates_graph.graph_template_id=0";
	}elseif (html_get_page_variable("template_id") != "") {
		$sql_where .= " AND graph_templates_graph.graph_template_id=" . html_get_page_variable("template_id");
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_graph");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$total_rows = db_fetch_cell("SELECT
		COUNT(graph_templates_graph.id)
		FROM (graph_local,graph_templates_graph)
		LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
		WHERE graph_local.id=graph_templates_graph.local_graph_id
		$sql_where");

	return db_fetch_assoc("SELECT
		graph_templates_graph.id,
		graph_templates_graph.local_graph_id,
		graph_templates_graph.height,
		graph_templates_graph.width,
		graph_templates_graph.title_cache,
		graph_templates.name,
		graph_local.device_id
		FROM (graph_local,graph_templates_graph)
		LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
		WHERE graph_local.id=graph_templates_graph.local_graph_id
		$sql_where
		ORDER BY " . html_get_page_variable('sort_column') . " " . html_get_page_variable('sort_direction') .
		" LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp);
}

function graph($refresh = true) {
	global $item_rows;
	require_once(CACTI_BASE_PATH . "/include/auth/auth_constants.php");

	$table = New html_table;

	$table->page_variables = array(
		"page"           => array("type" => "numeric", "method" => "request", "default" => "1"),
		"rows"           => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"filter"         => array("type" => "string",  "method" => "request", "default" => ""),
		"tab"            => array("type" => "string",  "method" => "request", "default" => "", "nosession" => true),
		"device_id"      => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"template_id"    => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"sort_column"    => array("type" => "string",  "method" => "request", "default" => "title_cache"),
		"sort_direction" => array("type" => "string",  "method" => "request", "default" => "ASC")
	);

	$table->table_format = array(
		"title_cache" => array(
			"name" => __("Graph Title"),
			"filter" => true,
			"link" => true,
			"order" => "ASC"
		),
		"local_graph_id" => array(
			"name" => __("ID"),
			"order" => "ASC",
			"align" => "right"
		),
		"name" => array(
			"name" => __("Template Name"),
			"filter" => true,
			"order" => "ASC"
		),
		"height" => array(
			"name" => __("Size"),
			"order" => "ASC",
			"function" => "display_graph_size",
			"params" => array("height", "width"),
			"sort" => false,
			"align" => "right"
		)
	);

	/* initialize page behavior */
	$table->href           = "graphs.php";
	$table->session_prefix = "sess_graphs";
	$table->filter_func    = "graphs_filter";
	$table->key_field      = "local_graph_id";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = array_merge(graph_actions_list(), tree_add_tree_names_to_actions_array());

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_graph_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}

/* --------------------------
    graph new functions
   -------------------------- */

function graphs_new_form_save() {
	if (substr_count($_SERVER["REQUEST_URI"], "/devices.php")) {
		$file = "devices.php?action=edit&tab=newgraphs&id=" . $_REQUEST["device_id"];
	}else{
		$file = "graphs_new.php?device_id=". $_REQUEST["device_id"];
	}


	if (isset($_POST["save_component_graph"])) {
		/* summarize the 'create graph from device template/snmp index' stuff into an array */
		while (list($var, $val) = each($_POST)) {
			if (preg_match('/^cg_(\d+)$/', $var, $matches)) {
				$selected_graphs["cg"]{$matches[1]}{$matches[1]} = true;
			}elseif (preg_match('/^cg_g$/', $var)) {
				if (get_request_var_post("cg_g") > 0) {
					$selected_graphs["cg"]{$_POST["cg_g"]}{$_POST["cg_g"]} = true;
				}
			}elseif (preg_match('/^sg_(\d+)_([a-f0-9]{32})$/', $var, $matches)) {
				$selected_graphs["sg"]{$matches[1]}{$_POST{"sgg_" . $matches[1]}}{$matches[2]} = true;
			}
		}

		if (isset($selected_graphs)) {
			device_new_graphs(get_request_var_post("device_id"), get_request_var_post("device_template_id"), $selected_graphs);
			exit;
		}

		header("Location: " . $file);
		exit;
	}

	if (isset($_POST["save_component_new_graphs"])) {
		device_new_graphs_save();

		header("Location: " . $file);
		exit;
	}
}

/* ---------------------
    Misc Functions
   --------------------- */

function draw_edit_form_row($field_array, $field_name, $previous_value) {
	$field_array["value"] = $previous_value;

	draw_edit_form(
		array(
			"config" => array(
				"no_form_tag" => true,
				"force_row_color" => "F5F5F5"
				),
			"fields" => array(
				$field_name => $field_array
				)
			)
		);
}

/* -------------------
    Data Query Functions
   ------------------- */

function graphs_new_reload_query() {
	run_data_query(get_request_var("device_id"), get_request_var("id"));
}

/* -------------------
    New Graph Functions
   ------------------- */

function device_new_graphs_save() {
	$selected_graphs_array = unserialize(stripslashes($_POST["selected_graphs_array"]));

	$values = array();

	/* form an array that contains all of the data on the previous form */
	while (list($var, $val) = each($_POST)) {
		if (preg_match("/^g_(\d+)_(\d+)_(\w+)/", $var, $matches)) { /* 1: snmp_query_id, 2: graph_template_id, 3: field_name */
			if (empty($matches[1])) { /* this is a new graph from template field */
				$values["cg"]{$matches[2]}["graph_template"]{$matches[3]} = $val;
			}else{ /* this is a data query field */
				$values["sg"]{$matches[1]}{$matches[2]}["graph_template"]{$matches[3]} = $val;
			}
		}elseif (preg_match("/^gi_(\d+)_(\d+)_(\d+)_(\w+)/", $var, $matches)) { /* 1: snmp_query_id, 2: graph_template_id, 3: graph_template_input_id, 4:field_name */
			/* ================= input validation ================= */
			input_validate_input_number($matches[3]);
			/* ==================================================== */

			/* we need to find out which graph items will be affected by saving this particular item */
			$item_list = db_fetch_assoc("select
				graph_template_item_id
				from graph_template_input_defs
				where graph_template_input_id=" . $matches[3]);

			/* loop through each item affected and update column data */
			if (sizeof($item_list) > 0) {
			foreach ($item_list as $item) {
				if (empty($matches[1])) { /* this is a new graph from template field */
					$values["cg"]{$matches[2]}["graph_template_item"]{$item["graph_template_item_id"]}{$matches[4]} = $val;
				}else{ /* this is a data query field */
					$values["sg"]{$matches[1]}{$matches[2]}["graph_template_item"]{$item["graph_template_item_id"]}{$matches[4]} = $val;
				}
			}
			}
		}elseif (preg_match("/^d_(\d+)_(\d+)_(\d+)_(\w+)/", $var, $matches)) { /* 1: snmp_query_id, 2: graph_template_id, 3: data_template_id, 4:field_name */
			if (empty($matches[1])) { /* this is a new graph from template field */
				$values["cg"]{$matches[2]}["data_template"]{$matches[3]}{$matches[4]} = $val;
			}else{ /* this is a data query field */
				$values["sg"]{$matches[1]}{$matches[2]}["data_template"]{$matches[3]}{$matches[4]} = $val;
			}
		}elseif (preg_match("/^c_(\d+)_(\d+)_(\d+)_(\d+)/", $var, $matches)) { /* 1: snmp_query_id, 2: graph_template_id, 3: data_template_id, 4:data_input_field_id */
			if (empty($matches[1])) { /* this is a new graph from template field */
				$values["cg"]{$matches[2]}["custom_data"]{$matches[3]}{$matches[4]} = $val;
			}else{ /* this is a data query field */
				$values["sg"]{$matches[1]}{$matches[2]}["custom_data"]{$matches[3]}{$matches[4]} = $val;
			}
		}elseif (preg_match("/^di_(\d+)_(\d+)_(\d+)_(\d+)_(\w+)/", $var, $matches)) { /* 1: snmp_query_id, 2: graph_template_id, 3: data_template_id, 4:local_data_template_rrd_id, 5:field_name */
			if (empty($matches[1])) { /* this is a new graph from template field */
				$values["cg"]{$matches[2]}["data_template_item"]{$matches[4]}{$matches[5]} = $val;
			}else{ /* this is a data query field */
				$values["sg"]{$matches[1]}{$matches[2]}["data_template_item"]{$matches[4]}{$matches[5]} = $val;
			}
		}
	}

	debug_log_clear("new_graphs");

	while (list($form_type, $form_array) = each($selected_graphs_array)) {
		$current_form_type = $form_type;

		while (list($form_id1, $form_array2) = each($form_array)) {
			/* enumerate information from the arrays stored in post variables */
			if ($form_type == "cg") {
				$graph_template_id = $form_id1;
			}elseif ($form_type == "sg") {
				while (list($form_id2, $form_array3) = each($form_array2)) {
					$snmp_index_array = $form_array3;

					$snmp_query_array["snmp_query_id"] = $form_id1;
					$snmp_query_array["snmp_index_on"] = get_best_data_query_index_type($_POST["device_id"], $form_id1);
					$snmp_query_array["snmp_query_graph_id"] = $form_id2;
				}

				$graph_template_id = db_fetch_cell("select graph_template_id from snmp_query_graph where id=" . $snmp_query_array["snmp_query_graph_id"]);
			}

			if ($current_form_type == "cg") {
				$return_array = create_complete_graph_from_template($graph_template_id, $_POST["device_id"], "", $values["cg"]);

				debug_log_insert("new_graphs", "Created graph: " . get_graph_title($return_array["local_graph_id"]));

				/* lastly push device-specific information to our data sources */
				if (sizeof($return_array["local_data_id"])) { # we expect at least one data source associated
					foreach($return_array["local_data_id"] as $item) {
						push_out_device(get_request_var_post("device_id"), $item);
					}
				} else {
					debug_log_insert("new_graphs", "ERROR: no Data Source associated. Check Template");
				}
			}elseif ($current_form_type == "sg") {
				while (list($snmp_index, $true) = each($snmp_index_array)) {
					$snmp_query_array["snmp_index"] = decode_data_query_index($snmp_index, $snmp_query_array["snmp_query_id"], $_POST["device_id"]);

					$return_array = create_complete_graph_from_template($graph_template_id, $_POST["device_id"], $snmp_query_array, $values["sg"]{$snmp_query_array["snmp_query_id"]});

					debug_log_insert("new_graphs", "Created graph: " . get_graph_title($return_array["local_graph_id"]));

					/* lastly push device-specific information to our data sources */
					if (sizeof($return_array["local_data_id"])) { # we expect at least one data source associated
						foreach($return_array["local_data_id"] as $item) {
							push_out_device(get_request_var_post("device_id"), $item);
						}
					} else {
						debug_log_insert("new_graphs", "ERROR: no Data Source associated. Check Template");
					}
				}
			}
		}
	}
}

function device_new_graphs($device_id, $device_template_id, $selected_graphs_array) {
	if (substr_count($_SERVER["REQUEST_URI"], "/devices.php")) {
		$file = "devices.php?action=edit&tab=newgraphs&id=" . $_REQUEST["device_id"];
		$file2 = "devices.php";
	}else{
		$file = "graphs_new.php?device_id=". $_REQUEST["device_id"];
		$file2 = "graphs_new.php";
	}

	/* we use object buffering on this page to allow redirection to another page if no
	fields are actually drawn */
	ob_start();

	include_once(CACTI_BASE_PATH . "/include/top_header.php");

	print "<form action='" . $file2 . "' method='post'>\n";

	$snmp_query_id = 0;
	$num_output_fields = array();

	while (list($form_type, $form_array) = each($selected_graphs_array)) {
		while (list($form_id1, $form_array2) = each($form_array)) {
			if ($form_type == "cg") {
				$graph_template_id = $form_id1;

				html_start_box(__("Create Graph from '%s'", db_fetch_cell("select name from graph_templates where id=$graph_template_id")), "100", "3", "center", "");
			}elseif ($form_type == "sg") {
				while (list($form_id2, $form_array3) = each($form_array2)) {
					/* ================= input validation ================= */
					input_validate_input_number($snmp_query_id);
					/* ==================================================== */

					$snmp_query_id = $form_id1;
					$snmp_query_graph_id = $form_id2;
					$num_graphs = sizeof($form_array3);

					$snmp_query = db_fetch_row("select
						snmp_query.name,
						snmp_query.xml_path
						from snmp_query
						where snmp_query.id=$snmp_query_id");

					$graph_template_id = db_fetch_cell("select graph_template_id from snmp_query_graph where id=$snmp_query_graph_id");
				}

				/* DRAW: Data Query */
				html_start_box(__("Create") . $num_graphs . __("Graph") . (($num_graphs>1) ? "s" : "") . " from '" . db_fetch_cell("select name from snmp_query where id=$snmp_query_id") . "'", "100", "3", "center", "");
			}

			/* ================= input validation ================= */
			input_validate_input_number($graph_template_id);
			/* ==================================================== */

			$data_templates = db_fetch_assoc("select
				data_template.name as data_template_name,
				data_template_rrd.data_source_name,
				data_template_data.*
				from (data_template, data_template_rrd, data_template_data, graph_templates_item)
				where graph_templates_item.task_item_id=data_template_rrd.id
				and data_template_rrd.data_template_id=data_template.id
				and data_template_data.data_template_id=data_template.id
				and data_template_rrd.local_data_id=0
				and data_template_data.local_data_id=0
				and graph_templates_item.local_graph_id=0
				and graph_templates_item.graph_template_id=" . $graph_template_id . "
				group by data_template.id
				order by data_template.name");

			$graph_template = db_fetch_row("select
				graph_templates.name as graph_template_name,
				graph_templates_graph.*
				from (graph_templates, graph_templates_graph)
				where graph_templates.id=graph_templates_graph.graph_template_id
				and graph_templates.id=" . $graph_template_id . "
				and graph_templates_graph.local_graph_id=0");
			$graph_template_name = db_fetch_cell("select name from graph_templates where id=" . $graph_template_id);

			array_push($num_output_fields, draw_nontemplated_fields_graph($graph_template_id, $graph_template, "g_$snmp_query_id" . "_" . $graph_template_id . "_|field|", "Graph [Template: " . $graph_template["graph_template_name"] . "]", false, (isset($snmp_query_graph_id) ? $snmp_query_graph_id : 0)));
			array_push($num_output_fields, draw_nontemplated_fields_graph_item($graph_template_id, 0, "gi_" . $snmp_query_id . "_" . $graph_template_id . "_|id|_|field|", "Graph Items [Template: " . $graph_template_name . "]"));

			/* DRAW: Data Sources */
			if (sizeof($data_templates) > 0) {
			foreach ($data_templates as $data_template) {
				array_push($num_output_fields, draw_nontemplated_fields_data_source($data_template["data_template_id"], 0, $data_template, "d_" . $snmp_query_id . "_" . $graph_template_id . "_" . $data_template["data_template_id"] . "_|field|", "Data Source [Template: " . $data_template["data_template_name"] . "]", false, (isset($snmp_query_graph_id) ? $snmp_query_graph_id : 0)));

				$data_template_items = db_fetch_assoc("select
					data_template_rrd.*
					from data_template_rrd
					where data_template_rrd.data_template_id=" . $data_template["data_template_id"] . "
					and local_data_id=0");

				array_push($num_output_fields, draw_nontemplated_fields_data_source_item($data_template["data_template_id"], $data_template_items, "di_" . $snmp_query_id . "_" . $graph_template_id . "_" . $data_template["data_template_id"] . "_|id|_|field|", "", false, false, (isset($snmp_query_graph_id) ? $snmp_query_graph_id : 0)));
				array_push($num_output_fields, draw_nontemplated_fields_custom_data($data_template["id"], "c_" . $snmp_query_id . "_" . $graph_template_id . "_" . $data_template["data_template_id"] . "_|id|", "Custom Data [Template: " . $data_template["data_template_name"] . "]", false, $snmp_query_id));
			}
			}

			html_end_box();
		}
	}

	/* no fields were actually drawn on the form; just save without prompting the user */
	if (array_sum($num_output_fields) == 0) {
		ob_end_clean();

		/* since the user didn't actually click "Create" to POST the data; we have to
		pretend like they did here */
		$_POST["device_template_id"] = $device_template_id;
		$_POST["device_id"] = $device_id;
		$_POST["save_component_new_graphs"] = "1";
		$_POST["selected_graphs_array"] = serialize($selected_graphs_array);

		device_new_graphs_save();

		header("Location: " . $file);
		exit;
	}

	/* flush the current output buffer to the browser */
	ob_end_flush();

	form_hidden_box("device_template_id", $device_template_id, "0");
	form_hidden_box("device_id", $device_id, "0");
	form_hidden_box("save_component_new_graphs", "1", "");
	print "<input type='hidden' name='selected_graphs_array' value='" . serialize($selected_graphs_array) . "'>\n";

	form_save_button_alt("device_id!$device_id");

	include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
}

/* -------------------
    Graph Functions
   ------------------- */

function graphs_new() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("device_id"));
	input_validate_input_number(get_request_var_request("graph_type"));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var("filter"));
	}

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST["clear_x"])) {
		if (!substr_count($_SERVER["REQUEST_URI"], "/devices.php")) {
			kill_session_var("sess_graphs_new_device_id");
		}

		kill_session_var("sess_graphs_new_filter");

		if (!substr_count($_SERVER["REQUEST_URI"], "/devices.php")) {
			unset($_REQUEST["device_id"]);
		}

		unset($_REQUEST["filter"]);

		$changed = true;
	}else{
		/* if any of the settings changed, reset the page number */
		$changed = false;
		$changed += check_changed("device_id",    "sess_graphs_new_device_id");
		$changed += check_changed("graph_type", "sess_graphs_new_graph_type");
		$changed += check_changed("filter",     "sess_graphs_new_filter");
	}

	load_current_session_value("device_id",    "sess_graphs_new_device_id",    db_fetch_cell("select id from device order by description,hostname limit 1"));
	load_current_session_value("graph_type", "sess_graphs_new_graph_type", read_config_option("default_graphs_new_dropdown"));
	load_current_session_value("filter",     "sess_graphs_new_filter",     "");

	if (substr_count($_SERVER["REQUEST_URI"], "/devices.php")) {
		$file = "devices.php?action=edit&tab=newgraphs&id=" . $_REQUEST["device_id"];
		$file2 = "devices.php";
	}else{
		$file = "graphs_new.php?device_id=". $_REQUEST["device_id"];
		$file2 = "graphs_new.php";
	}

	$device       = db_fetch_row("select id,description,hostname,device_template_id from device where id=" . $_REQUEST["device_id"]);
	$row_limit    = read_config_option("num_rows_data_query");
	$debug_log    = debug_log_return("new_graphs");
	$onReadyFuncs = array();

	?>
	<script type="text/javascript">
	<!--
	function applyGraphsNewFilterChange(objForm) {
		strURL = '?action=edit&tab=newgraphs';
		strURL = strURL + '&graph_type=' + objForm.graph_type.value;
		strURL = strURL + '&device_id=' + objForm.device_id.value;
		strURL = strURL + '&filter=' + objForm.filter.value;;
		document.location = strURL;
	}
	-->
	</script>
	<?php

	html_start_box($device["description"] . "(" . $device["hostname"] . ") " . db_fetch_cell("select name from device_template where id=" . $device["device_template_id"]), "100", "3", "center", "");

	?>
	<tr class='rowAlternate2'>
		<td>
			<form name="form_graphs_new" method="post" action="<?php print $file2;?>">
			<table cellpadding="0" align="left">
				<tr>
					<?php if (!isset($_REQUEST["tab"])) { ?>
					<td class="nw50">
						&nbsp;Host:&nbsp;
					</td>
					<td width="1">
						<select name="device_id" onChange="applyGraphsNewFilterChange(document.form_graphs_new)">
						<?php
						$devices = db_fetch_assoc("select id,CONCAT_WS('',description,' (',hostname,')') as name from device order by description,hostname");

						if (sizeof($devices) > 0) {
						foreach ($devices as $item) {
							print "<option value='" . $item["id"] . "'"; if (get_request_var_request("device_id") == $item["id"]) { print " selected"; } print ">" . $item["name"] . "</option>\n";
						}
						}
						?>
						</select>
					</td>
					<?php } ?>
					<td class="nw50">
						&nbsp;<?php print __("Type:");?>&nbsp;
					</td>
					<td width="1">
						<select name="graph_type" onChange="applyGraphsNewFilterChange(document.form_graphs_new)">
						<option value="-2"<?php if (get_request_var_request("graph_type") == "-2") {?> selected<?php }?>><?php print __("All");?></option>
						<option value="-1"<?php if (get_request_var_request("graph_type") == "-1") {?> selected<?php }?>><?php print __("Graph Template Based");?></option>
						<?php

						$snmp_queries = db_fetch_assoc("SELECT
							snmp_query.id,
							snmp_query.name,
							snmp_query.xml_path
							FROM (snmp_query,device_snmp_query)
							WHERE device_snmp_query.snmp_query_id=snmp_query.id
							AND device_snmp_query.device_id=" . $device["id"] . "
							ORDER BY snmp_query.name");

						if (sizeof($snmp_queries) > 0) {
						foreach ($snmp_queries as $query) {
							print "<option value='" . $query["id"] . "'"; if (get_request_var_request("graph_type") == $query["id"]) { print " selected"; } print ">" . $query["name"] . "</option>\n";
						}
						}
						?>
						</select>
					</td>
					<?php if (get_request_var_request("graph_type") > 0) {?>
					<td class="nw50">
						&nbsp;Search:&nbsp;
					</td>
					<td>
						<input type="text" name="filter" size="30" width="200" value="<?php print $_REQUEST["filter"];?>">
					</td>
					<td align="left" class="nw120">
						&nbsp;<input type="submit" name="go" value="<?php print __("Go");?>" align="middle">
						<input type="submit" name="clear_x" value="<?php print __("Clear");?>" align="middle">
						<input type="hidden" name="action" value="edit">
						<input type="hidden" name="tab" value="newgraphs">
					</td>
					<?php }else{
					form_hidden_box("device_template_id", $device["device_template_id"], "0");
					form_hidden_box("filter", get_request_var_request("filter"), "");
					}
					form_hidden_box("device_id", $device["id"], "0");
				?>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php

	html_end_box(false);

	$total_rows = sizeof(db_fetch_assoc("select graph_template_id from device_graph where device_id=" . $_REQUEST["device_id"]));

	$i = 0;

	if ($changed) {
		foreach($snmp_queries as $query) {
			kill_session_var("sess_graphs_new_page" . $query["id"]);
			unset($_REQUEST["page" . $query["id"]]);
			load_current_session_value("page" . $query["id"], "sess_graphs_new_page" . $query["id"], "1");
		}
	}

	if (get_request_var_request("graph_type") > 0) {
		load_current_session_value("page" . get_request_var_request("graph_type"), "sess_graphs_new_page" . get_request_var_request("graph_type"), "1");
	}else if (get_request_var_request("graph_type") == -2) {
		foreach($snmp_queries as $query) {
			load_current_session_value("page" . $query["id"], "sess_graphs_new_page" . $query["id"], "1");
		}
	}

	print "<form name='chk' method='post' action='" . $file2 . "'>";
	print "<script type='text/javascript'>\nvar created_graphs = new Array()\n</script>\n";
	if (get_request_var_request("graph_type") < 0) {

		$graph_templates = db_fetch_assoc("SELECT
			graph_templates.id AS graph_template_id,
			graph_templates.name AS graph_template_name
			FROM (device_graph,graph_templates)
			WHERE device_graph.graph_template_id=graph_templates.id
			AND device_graph.device_id=" . $_REQUEST["device_id"] . "
			ORDER BY graph_templates.name");

		$template_graphs = db_fetch_assoc("SELECT
			graph_local.graph_template_id
			FROM (graph_local,device_graph)
			WHERE graph_local.graph_template_id=device_graph.graph_template_id
			AND graph_local.device_id=device_graph.device_id
			AND graph_local.device_id=" . $device["id"] . "
			GROUP BY graph_local.graph_template_id");

		if (sizeof($template_graphs) > 0) {
			print "\n<script type='text/javascript'>\n<!--\n";
			print "var gt_created_graphs = new Array(";

			$cg_ctr = 0;
			foreach ($template_graphs as $template_graph) {
				print (($cg_ctr > 0) ? "," : "") . "'" . $template_graph["graph_template_id"] . "'";

				$cg_ctr++;
			}

			print ")\n";
			print "//-->\n</script>\n";
		} else {
			print "<script type='text/javascript'>\nvar gt_created_graphs = new Array()\n</script>\n";
		}

		$onReadyFuncs[] = "setGraphStatus()";

		html_start_box(__("Graph Templates"), "100", "3", "center", "");
		print "	<tr class='rowSubHeader'>
				<td class='textSubHeaderDark'>" . __("Graph Template Name") . "</td>
				<td class='rowSubHeader' width='1%' align='center' style='" . get_checkbox_style() . "'><input type='checkbox' style='margin: 0px;' name='all_cg' title='" . __("Select All") . "' onClick='selectAllGraphs(\"cg\",this.checked)'></td>\n
			</tr>\n";

		/* create a row for each graph template associated with the device template */
		if (sizeof($graph_templates) > 0) {
		foreach ($graph_templates as $graph_template) {
			$query_row = $graph_template["graph_template_id"];

			form_alternate_row_color("gt_line" . $query_row, true);

			print "<td style='line-height: 1.5em;padding:0px 5px 0px 5px;' onClick='toggleGraph(\"" . $query_row . "\")'>" . $graph_template["graph_template_name"] . "</td>";
			print "<td align='right'>
						<input type='checkbox' name='cg_$query_row' id='cg_$query_row' onChange='toggleGraph(\"" . $query_row . "\", true)'>
					</td>";
			form_end_row();
		}
		}

		$available_graph_templates = db_fetch_assoc("SELECT
			graph_templates.id, graph_templates.name
			FROM snmp_query_graph RIGHT JOIN graph_templates
			ON (snmp_query_graph.graph_template_id = graph_templates.id)
			WHERE (((snmp_query_graph.name) Is Null)) ORDER BY graph_templates.name");

		/* create a row at the bottom that lets the user create any graph they choose */
		form_alternate_row_color();
		print "<td colspan='2' width='60' nowrap>
					<strong>" . __("Create:") . "</strong>&nbsp;";
					form_dropdown("cg_g", $available_graph_templates, "name", "id", "", "(Select a graph type to create)", "", "textArea");
		print "		</td>
			</tr>";

		html_end_box();
	}

	if ($_REQUEST["graph_type"] != -1) {
		$snmp_queries = db_fetch_assoc("SELECT
			snmp_query.id,
			snmp_query.name,
			snmp_query.xml_path
			FROM (snmp_query,device_snmp_query)
			WHERE device_snmp_query.snmp_query_id=snmp_query.id
			AND device_snmp_query.device_id=" . $device["id"] .
			($_REQUEST["graph_type"] != -2 ? " AND snmp_query.id=" . $_REQUEST["graph_type"] : '') . "
			ORDER BY snmp_query.name");

		if (sizeof($snmp_queries) > 0) {
		foreach ($snmp_queries as $snmp_query) {
			unset($total_rows);

			if (isset($_REQUEST["page" . $snmp_query["id"]])) {
				$page = $_REQUEST["page" . $snmp_query["id"]];
			}elseif (!$changed) {
				$page = $_REQUEST["page" . $snmp_query["id"]];
			}else{
				$page = 1;
			}

			$xml_array = get_data_query_array($snmp_query["id"]);

			$num_input_fields = 0;
			$num_visible_fields = 0;

			if ($xml_array != false) {
				/* loop through once so we can find out how many input fields there are */
				reset($xml_array["fields"]);
				while (list($field_name, $field_array) = each($xml_array["fields"])) {
					if ($field_array["direction"] == "input") {
						$num_input_fields++;

						if (!isset($total_rows)) {
							$total_rows = db_fetch_cell("SELECT count(*) FROM device_snmp_cache WHERE device_id=" . $device["id"] . " and snmp_query_id=" . $snmp_query["id"] . " AND field_name='$field_name'");
						}
					}
				}
			}

			if (!isset($total_rows)) {
				$total_rows = 0;
			}

			$snmp_query_graphs = db_fetch_assoc("SELECT snmp_query_graph.id,snmp_query_graph.name FROM snmp_query_graph WHERE snmp_query_graph.snmp_query_id=" . $snmp_query["id"] . " ORDER BY snmp_query_graph.name");

			if (sizeof($snmp_query_graphs) > 0) {
				print "<script type='text/javascript'>\n<!--\n";

				foreach ($snmp_query_graphs as $snmp_query_graph) {
					$created_graphs = db_fetch_assoc("SELECT DISTINCT
						data_local.snmp_index
						FROM (data_local,data_template_data)
						LEFT JOIN data_input_data ON (data_template_data.id=data_input_data.data_template_data_id)
						LEFT JOIN data_input_fields ON (data_input_data.data_input_field_id=data_input_fields.id)
						WHERE data_local.id=data_template_data.local_data_id
						AND data_input_fields.type_code='output_type'
						AND data_input_data.value='" . $snmp_query_graph["id"] . "'
						AND data_local.device_id=" . $device["id"]);

					print "created_graphs[" . $snmp_query_graph["id"] . "] = new Array(";

					$cg_ctr = 0;
					if (sizeof($created_graphs) > 0) {
					foreach ($created_graphs as $created_graph) {
						print (($cg_ctr > 0) ? "," : "") . "'" . encode_data_query_index($created_graph["snmp_index"]) . "'";

						$cg_ctr++;
					}
					}

					print ")\n";
				}

				print "//-->\n</script>\n";
			}

			html_start_box_dq($snmp_query["name"], $snmp_query["id"], $device["id"], $num_input_fields+1, "100", "0", "center");

			if ($xml_array != false) {
				$html_dq_header = "";
				$snmp_query_indexes = array();

				reset($xml_array["fields"]);

				/* if there is a where clause, get the matching snmp_indexes */
				$sql_where = "";
				if (strlen(get_request_var_request("filter"))) {
					$sql_where = "";
					$indexes = db_fetch_assoc("SELECT DISTINCT snmp_index
						FROM device_snmp_cache
						WHERE field_value LIKE '%%" . get_request_var_request("filter") . "%%'
						AND snmp_query_id=" . $snmp_query["id"] . "
						AND device_id=" . $device["id"]);

					if (sizeof($indexes)) {
						foreach($indexes as $index) {
							if (strlen($sql_where)) {
								$sql_where .= ", '" . $index["snmp_index"] . "'";
							}else{
								$sql_where .= " AND snmp_index IN('" . $index["snmp_index"] . "'";
							}
						}

						$sql_where .= ")";
					}
				}

				if ((strlen(get_request_var_request("filter")) == 0) ||
					((strlen(get_request_var_request("filter"))) && (sizeof($indexes)))) {
					/* determine the sort order */
					if (isset($xml_array["index_order_type"])) {
						if ($xml_array["index_order_type"] == "numeric") {
							$sql_order = "ORDER BY CAST(snmp_index AS unsigned)";
						}else if ($xml_array["index_order_type"] == "alphabetic") {
							$sql_order = "ORDER BY snmp_index";
						}else if ($xml_array["index_order_type"] == "natural") {
							$sql_order = "ORDER BY INET_ATON(snmp_index)";
						}else{
							$sql_order = "";
						}
					}else{
						$sql_order = "";
					}

					/* get the unique field values from the database */
					$field_names = db_fetch_assoc("SELECT DISTINCT field_name
						FROM device_snmp_cache
						WHERE device_id=" . $device["id"] . "
						AND snmp_query_id=" . $snmp_query["id"]);

					/* build magic query */
					$sql_query  = "SELECT device_id, snmp_query_id, snmp_index";
					$num_visible_fields = sizeof($field_names);
					$i = 0;
					if (sizeof($field_names) > 0) {
						foreach($field_names as $column) {
							$field_name = $column["field_name"];
							$sql_query .= ", MAX(CASE WHEN field_name='$field_name' THEN field_value ELSE NULL END) AS '$field_name'";
							$i++;
						}
					}

					$sql_query .= " FROM device_snmp_cache
						WHERE device_id=" . $device["id"] . "
						AND snmp_query_id=" . $snmp_query["id"] . "
						$sql_where
						GROUP BY device_id, snmp_query_id, snmp_index
						$sql_order
						LIMIT " . ($row_limit*($page-1)) . "," . $row_limit;

					$rows_query = "SELECT device_id, snmp_query_id, snmp_index FROM device_snmp_cache
						WHERE device_id=" . $device["id"] . "
						AND snmp_query_id=" . $snmp_query["id"] . "
						$sql_where
						GROUP BY device_id, snmp_query_id, snmp_index";

					$snmp_query_indexes = db_fetch_assoc($sql_query);

					$total_rows = sizeof(db_fetch_assoc($rows_query));

					if (($page-1) * $row_limit > $total_rows) {
						$page = 1;
						$_REQUEST["page" . $query["id"]] = $page;
						load_current_session_value("page" . $query["id"], "sess_graphs_new_page" . $query["id"], "1");
					}

					if (substr_count($_SERVER["REQUEST_URI"], "/devices.php")) {
						$file = "devices.php?action=edit&tab=newgraphs&id=" . $_REQUEST["device_id"];
						$file2 = "devices.php";
					}else{
						$file = "graphs_new.php?device_id=". $_REQUEST["device_id"];
						$file2 = "graphs_new.php";
					}

					if ($total_rows > $row_limit) {
						/* generate page list navigation */
						$nav = html_create_nav($page, MAX_DISPLAY_PAGES, $row_limit, $total_rows, 40, $file, "page" . $snmp_query["id"]);

						print $nav;
					}

					while (list($field_name, $field_array) = each($xml_array["fields"])) {
						if ($field_array["direction"] == "input") {
							foreach($field_names as $row) {
								if ($row["field_name"] == $field_name) {
									$html_dq_header .= "<th style='padding:0px 5px 0px 5px;' class='textSubHeaderDark'>" . $field_array["name"] . "</th>\n";
									break;
								}
							}
						}
					}

					if (!sizeof($snmp_query_indexes)) {
						print "<tr class='rowAlternate1'><td>" . __("This data query returned 0 rows, perhaps there was a problem executing this data query. You can %s run this data query in debug mode %s to get more information.", "<a href='" . htmlspecialchars("devices.php?action=query_verbose&id=" . $snmp_query["id"] . "&device_id=" . $device["id"]) . "'>", "</a>") . "</td></tr>\n";
					}else{
						print "<tr class='rowSubHeader'>
								$html_dq_header
								<td class='rowSubHeader' width='1%' align='center' style='" . get_checkbox_style() . "'><input type='checkbox' style='margin: 0px;' name='all_" . $snmp_query["id"] . "' title='" . __("Select All") ."' onClick='selectAllDataQueries(\"" . $snmp_query["id"] . "\", this.checked);'></td>\n
							</tr>\n";
					}

					$row_counter    = 0;
					$fields         = array_rekey($field_names, "field_name", "field_name");
					if (sizeof($snmp_query_indexes) > 0) {
					foreach($snmp_query_indexes as $row) {
						$query_row = $snmp_query["id"] . "_" . encode_data_query_index($row["snmp_index"]);

						print "<tr class='rowAlternate" . ($row_counter % 2 ? "1":"2") . "' id='line" . $query_row . "' onClick='toggleDataQueryGraph(" . $snmp_query["id"] . ",\"" . encode_data_query_index($row["snmp_index"]) . "\")'>";

						reset($xml_array["fields"]);
						while (list($field_name, $field_array) = each($xml_array["fields"])) {
							if ($field_array["direction"] == "input") {
								if (in_array($field_name, $fields)) {
									if (isset($row[$field_name])) {
										print "<td>" . (strlen($_REQUEST["filter"]) ? preg_replace("/(" . preg_quote($_REQUEST["filter"]) . ")/i", "<span class=\"filter\">\\1</span>", $row[$field_name]) : $row[$field_name]) . "</td>";
									}else{
										print "<td></td>";
									}
								}
							}
						}

						print "<td style='padding-right: 4px;' align='right'>";
						print "<input type='checkbox' name='sg_$query_row' id='sg_$query_row' onChange='toggleDataQueryGraph(" . $snmp_query["id"] . ",\"" . encode_data_query_index($row["snmp_index"]) . "\", true)'>";
						print "</td>";
						print "</tr>\n";

						$row_counter++;
					}
					}

					if ($total_rows > $row_limit) {
						print $nav;
					}
				}else{
					print "<tr class='rowAlternate1'><td colspan='2' style='color: red; font-size: 12px; font-weight: bold;'>" . __("Search Returned no Rows.") . "</td></tr>\n";
				}
			}else{
				print "<tr class='rowAlternate1'><td colspan='2' style='color: red; font-size: 12px; font-weight: bold;'>" . __("Error in data query.") . "</td></tr>\n";
			}

			/* draw the graph template drop down here */
			$data_query_graphs = db_fetch_assoc("select snmp_query_graph.id,snmp_query_graph.name from snmp_query_graph where snmp_query_graph.snmp_query_id=" . $snmp_query["id"] . " order by snmp_query_graph.name");

			if (sizeof($data_query_graphs) == 1) {
				html_end_box();

				form_hidden_box("sgg_" . $snmp_query["id"], $data_query_graphs[0]["id"], "");
			}elseif (sizeof($data_query_graphs) > 1) {
				html_end_box(FALSE);

				print "<table align='center' width='100%'>
						<tr>
							<td width='1' valign='top'>
								<img src='images/arrow.gif' alt='' align='middle'>&nbsp;
							</td>
							<td align='right'>
								<span class=\"italic\">" . __("Select a graph type:") . "</span>&nbsp;
								<select name='sgg_" . $snmp_query["id"] . "' id='sgg_" . $snmp_query["id"] . "' onChange='setDataQueryGraphStatus(" . $snmp_query["id"] . ");'>
									"; html_create_list($data_query_graphs,"name","id","0"); print "
								</select>
							</td>
						</tr>
					</table>\n";
			}
			$onReadyFuncs[] = "setDataQueryGraphStatus(" . $snmp_query["id"] . ")";
		}
		}
	}

	form_hidden_box("save_component_graph", "1", "");
	form_hidden_box("device_id", $device["id"], "0");
	form_hidden_box("device_template_id", $device["device_template_id"], "0");

	form_save_button_alt("url!" . (isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : ""));

	if (sizeof($onReadyFuncs)) {
		print "<script type='text/javascript'>\n";
		print "$().ready(function() {\n";
		foreach($onReadyFuncs as $func) {
			print "\t" . $func . "\n";
		}
		print "	});\n</script>\n";
	}

	if (!empty($debug_log)) {
		debug_log_clear("new_graphs");
		?>
		<table class='topBoxAlt'>
			<tr>
				<td class='mono'>
					<?php print $debug_log;?>
				</td>
			</tr>
		</table>
		<br>
		<?php
	}
}

/** graph_remove -  remove a graph
 *
 * @param int $local_graph_id
 * @param bool $delete_ds
 * @return unknown_type
 */
function graph_remove($local_graph_id, $delete_ds) {
	require_once(CACTI_BASE_PATH . "/include/auth/auth_constants.php");
		
	if (empty($local_graph_id)) {
		return;
	}
	
	if ($delete_ds) {
                /* delete all data sources referenced by this graph */
                $data_sources = db_fetch_assoc("SELECT
                        data_template_data.local_data_id
                        FROM (data_template_rrd,data_template_data,graph_templates_item)
                        WHERE graph_templates_item.task_item_id=data_template_rrd.id
                        AND data_template_rrd.local_data_id=data_template_data.local_data_id
                        AND graph_templates_item.local_graph_id=" . $id . "
                        AND data_template_data.local_data_id > 0");

                echo __("Removing graph and all resources for graph id ") . $id;
                if (sizeof($data_sources) > 0) {
                        foreach ($data_sources as $data_source) {
				data_source_remove($data_source["local_data_id"]);
                        }
                }
        } else {
                echo __("Removing graph but keeping resources for graph id ") . $id;
        }

	db_execute("delete from graph_templates_graph where local_graph_id=$local_graph_id");
	db_execute("delete from graph_templates_item where local_graph_id=$local_graph_id");
	db_execute("delete from graph_tree_items where local_graph_id=$local_graph_id");
	db_execute("delete from user_auth_perms where item_id=$local_graph_id and type=" . PERM_GRAPHS);
	db_execute("delete from graph_local where id=$local_graph_id");
	
	if (is_error_message()) {
                echo __(". ERROR: Failed to remove this graph") . "\n";
        } else {
                echo __(". Success - removed graph-id: (%d)", $id) . "\n";
        }

}

/** graph_remove_multi - remove multiple graphs
 *
 * @param array $local_graph_ids
 * @return unknown_type
 */
function graph_remove_multi($local_graph_ids) {
	/* initialize variables */
	$ids_to_delete = "";
	$i = 0;

	/* build the array */
	if (sizeof($local_graph_ids)) {
		foreach($local_graph_ids as $local_graph_id) {
			if ($i == 0) {
				$ids_to_delete .= $local_graph_id;
			}else{
				$ids_to_delete .= ", " . $local_graph_id;
			}

			$i++;

			if (($i % 1000) == 0) {
				db_execute("DELETE FROM graph_templates_graph WHERE local_graph_id IN ($ids_to_delete)");
				db_execute("DELETE FROM graph_templates_item WHERE local_graph_id IN ($ids_to_delete)");
				db_execute("DELETE FROM graph_tree_items WHERE local_graph_id IN ($ids_to_delete)");
				db_execute("DELETE FROM graph_local WHERE id IN ($ids_to_delete)");

				$i = 0;
				$ids_to_delete = "";
			}
		}

		if ($i > 0) {
			db_execute("DELETE FROM graph_templates_graph WHERE local_graph_id IN ($ids_to_delete)");
			db_execute("DELETE FROM graph_templates_item WHERE local_graph_id IN ($ids_to_delete)");
			db_execute("DELETE FROM graph_tree_items WHERE local_graph_id IN ($ids_to_delete)");
			db_execute("DELETE FROM graph_local WHERE id IN ($ids_to_delete)");
		}
	}
}

/** resize_graphs - resizes the selected graph, overriding the template value
   @param $graph_templates_graph_id - the id of the graph to resize
   @param $graph_width - the width of the resized graph
   @param $graph_height - the height of the resized graph
  */
function resize_graphs($local_graph_id, $graph_width, $graph_height) {
	global $config;

	/* get graphs template id */
	db_execute("UPDATE graph_templates_graph SET width=" . $graph_width . ", height=" . $graph_height . " WHERE local_graph_id=" . $local_graph_id);
}

/** reapply_suggested_graph_title - reapplies the suggested name to a graph title
   @param int $graph_templates_graph_id - the id of the graph to reapply the name to
*/
function reapply_suggested_graph_title($local_graph_id) {
	global $config;

	/* get graphs template id */
	$graph_template_id = db_fetch_cell("select graph_template_id from graph_templates_graph where local_graph_id=" . $local_graph_id);

	/* if a non-template graph, simply return */
	if ($graph_template_id == 0) {
		return;
	}

	/* get the host associated with this graph for data queries only
	 * there's no "reapply suggested title" for "simple" graph templates */
	$graph_local = db_fetch_row("select device_id, graph_template_id, snmp_query_id, snmp_index from graph_local where snmp_query_id>0 AND id=" . $local_graph_id);
	/* if this is not a data query graph, simply return */
	if (!isset($graph_local["device_id"])) {
		return;
	}
	/* get data source associated with the graph */
	$data_local = db_fetch_cell("SELECT " .
		"data_template_data.local_data_id " .
		"FROM (data_template_rrd,data_template_data,graph_templates_item) " .
		"WHERE graph_templates_item.task_item_id=data_template_rrd.id " .
		"AND data_template_rrd.local_data_id=data_template_data.local_data_id " .
		"AND graph_templates_item.local_graph_id=" . $local_graph_id. " " .
		"GROUP BY data_template_data.local_data_id");
	
	$snmp_query_graph_id = db_fetch_cell("SELECT " .
		"data_input_data.value from data_input_data " .
		"JOIN data_input_fields ON (data_input_data.data_input_field_id=data_input_fields.id) " .
		"JOIN data_template_data ON (data_template_data.id = data_input_data.data_template_data_id) ".
		"WHERE data_input_fields.type_code = 'output_type' " .
		"AND data_template_data.local_data_id=" . $data_local );

	/* no snmp query graph id found */
	if ($snmp_query_graph_id == 0) {
		return;
	}

	/* get the suggested values from the suggested values cache */
	$suggested_values = db_fetch_assoc("SELECT " .
		"text, " .
		"field_name " .
		"FROM snmp_query_graph_sv " .
		"WHERE snmp_query_graph_id=" . $snmp_query_graph_id . " " . 
		"AND field_name = 'title' " .
		"ORDER BY sequence");

	$found = false;
	if (sizeof($suggested_values) > 0) {
		foreach ($suggested_values as $suggested_value) {
			/* once we find a match; don't try to find more */
			if (!$found) {
				$subs_string = substitute_snmp_query_data($suggested_value["text"], $graph_local["device_id"], $graph_local["snmp_query_id"], $graph_local["snmp_index"], read_config_option("max_data_query_field_length"));
				/* if there are no '|' characters, all of the substitutions were successful */
				if ((!substr_count($subs_string, "|query"))) {
					db_execute("UPDATE graph_templates_graph SET " . $suggested_value["field_name"] . "='" . $suggested_value["text"] . "' WHERE local_graph_id=" . $local_graph_id);
					/* once we find a working value, stop */
					$found = true;
				}
			}
		}
	}
}

/** get_graphs_from_datasource - get's all graphs related to a data source
   @param $local_data_id - the id of the data source
   @returns - array($id => $name_cache) returns the graph id's and names of the graphs
  */
function get_graphs_from_datasource($local_data_id) {
	return array_rekey(db_fetch_assoc("SELECT DISTINCT graph_templates_graph.local_graph_id AS id,
		graph_templates_graph.title_cache AS name
		FROM (graph_templates_graph
		INNER JOIN graph_templates_item
		ON graph_templates_graph.local_graph_id=graph_templates_item.local_graph_id)
		INNER JOIN data_template_rrd
		ON graph_templates_item.task_item_id=data_template_rrd.id
		WHERE graph_templates_graph.local_graph_id>0
		AND data_template_rrd.local_data_id=$local_data_id"), "id", "name");
}
