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
include_once(CACTI_LIBRARY_PATH . "/graph.php");
include_once(CACTI_LIBRARY_PATH . "/tree.php");
include_once(CACTI_LIBRARY_PATH . "/data_source.php");
include_once(CACTI_LIBRARY_PATH . "/template.php");
include_once(CACTI_LIBRARY_PATH . "/html_tree.php");
include_once(CACTI_LIBRARY_PATH . "/html_form_template.php");
include_once(CACTI_LIBRARY_PATH . "/rrd.php");
include_once(CACTI_LIBRARY_PATH . "/data_query.php");
include_once(CACTI_LIBRARY_PATH . "/graph.php");

define("MAX_DISPLAY_PAGES", 21);

$graph_actions = plugin_hook_function('graphs_action_array', graph_actions_list());

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch (get_request_var_request("action")) {
	case 'save':
		graph_form_save();

		break;
	case 'actions':
		graph_form_actions();

		break;
	case 'graph_diff':
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		graph_diff();
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
	case 'graph_item':
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		_graph_item();
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
	case 'graph_remove':
		graph_remove();

		header("Location: graphs.php");

		break;
	case 'graph_edit':
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		_graph_edit();
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
	case 'ajax_get_graph_templates':
		ajax_get_graph_templates();

		break;
	case 'ajax_get_devices_detailed':
		ajax_get_devices_detailed();

		break;
	case 'ajax_get_devices_brief':
		ajax_get_devices_brief();

		break;
	case 'ajax_graph_item_dnd':
		graph_item_dnd();

		break;
	case 'ajax_view':
		_cacti_graph();

		break;
	default:
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		_cacti_graph();
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
}

/* -----------------------
    item - Graph Items
   ----------------------- */

function _graph_item() {
	global $colors, $consolidation_functions, $graph_item_types, $struct_graph_item;

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
			data_template_rrd.data_source_name,
			cdef.name as cdef_name,
			colors.hex
			from graph_templates_item
			left join data_template_rrd on (graph_templates_item.task_item_id=data_template_rrd.id)
			left join data_local on (data_template_rrd.local_data_id=data_local.id)
			left join data_template_data on (data_local.id=data_template_data.local_data_id)
			left join cdef on (cdef_id=cdef.id)
			left join colors on (color_id=colors.id)
			where graph_templates_item.local_graph_id=" . $_GET["id"] . "
			order by graph_templates_item.sequence");

		$host_id = db_fetch_cell("select host_id from graph_local where id=" . $_GET["id"]);
		$header_label = "[edit: " . htmlspecialchars(get_graph_title($_GET["id"])) . "]";
	}

	$graph_template_id = db_fetch_cell("select graph_template_id from graph_local where id=" . $_GET["id"]);

	if (empty($graph_template_id)) {
		$add_text = "graphs_items.php?action=graph_item_edit&local_graph_id=" . $_GET["id"] . "&host_id=$host_id";
	}else{
		$add_text = "";
	}

	html_start_box("<strong>Graph Items</strong> $header_label", "100%", $colors["header"], "3", "center", $add_text);
	draw_graph_items_list($template_item_list, "graphs_items.php", "local_graph_id=" . $_GET["id"], (empty($graph_template_id) ? false : true));
	html_end_box();
}

/* ------------------------------------
    graph - Graphs
   ------------------------------------ */

function _graph_edit() {
	global $colors, $struct_graph, $image_types, $consolidation_functions, $graph_item_types, $struct_graph_item;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	$use_graph_template = true;

	if (!empty($_GET["id"])) {
		$local_graph_template_graph_id = db_fetch_cell("select local_graph_template_graph_id from graph_templates_graph where local_graph_id=" . $_GET["id"]);

		$graphs = db_fetch_row("select * from graph_templates_graph where local_graph_id=" . $_GET["id"]);
		$graphs_template = db_fetch_row("select * from graph_templates_graph where id=$local_graph_template_graph_id");

		$host_id = db_fetch_cell("select host_id from graph_local where id=" . $_GET["id"]);
		$header_label = "[edit: " . htmlspecialchars(get_graph_title($_GET["id"])) . "]";

		if ($graphs["graph_template_id"] == "0") {
			$use_graph_template = false;
		}
	}else{
		$header_label = "[new]";
		$use_graph_template = false;
	}

	/* handle debug mode */
	if (isset($_GET["debug"])) {
		if ($_GET["debug"] == "0") {
			kill_session_var("graph_debug_mode");
		}elseif ($_GET["debug"] == "1") {
			$_SESSION["graph_debug_mode"] = true;
		}
	}

	if (!empty($_GET["id"])) {
		?>
		<table width="100%" align="center">
			<tr>
				<td class="textInfo" colspan="2" valign="top">
					<?php print htmlspecialchars(get_graph_title($_GET["id"]));?>
				</td>
				<td class="textInfo" align="right" valign="top">
					<span style="color: #c16921;">*<a href='<?php print htmlspecialchars("graphs.php?action=graph_edit&id=" . (isset($_GET["id"]) ? $_GET["id"] : "0") . "&debug=" . (isset($_SESSION["graph_debug_mode"]) ? "0" : "1"));?>'>Turn <strong><?php print (isset($_SESSION["graph_debug_mode"]) ? "Off" : "On");?></strong> Graph Debug Mode.</a></span><br>
					<?php
						if (!empty($graphs["graph_template_id"])) {
							?><span style="color: #c16921;">*<a href='<?php print htmlspecialchars("graph_templates.php?action=template_edit&id=" . (isset($graphs["graph_template_id"]) ? $graphs["graph_template_id"] : "0"));?>'>Edit Graph Template.</a></span><br><?php
						}
						if (!empty($_GET["host_id"]) || !empty($host_id)) {
							?><span style="color: #c16921;">*<a href='<?php print htmlspecialchars("host.php?action=edit&id=" . (isset($_GET["host_id"]) ? $_GET["host_id"] : $host_id));?>'>Edit Host.</a></span><br><?php
						}
					?>
				</td>
			</tr>
		</table>
		<br>
		<?php
	}

	html_start_box("<strong>Graph Template Selection</strong> $header_label", "100%", $colors["header"], "3", "center", "");

	$form_array = array(
		"graph_template_id" => array(
			"method" => "drop_sql",
			"friendly_name" => "Selected Graph Template",
			"description" => "Choose a graph template to apply to this graph. Please note that graph data may be lost if you change the graph template after one is already applied.",
			"value" => (isset($graphs) ? $graphs["graph_template_id"] : "0"),
			"none_value" => "None",
			"sql" => "select graph_templates.id,graph_templates.name from graph_templates order by name"
			),
		"host_id" => array(
			"method" => "drop_sql",
			"friendly_name" => "Host",
			"description" => "Choose the host that this graph belongs to.",
			"value" => (isset($_GET["host_id"]) ? $_GET["host_id"] : $host_id),
			"none_value" => "None",
			"sql" => "select id,CONCAT_WS('',description,' (',hostname,')') as name from host order by description,hostname"
			),
		"graph_template_graph_id" => array(
			"method" => "hidden",
			"value" => (isset($graphs) ? $graphs["id"] : "0")
			),
		"local_graph_id" => array(
			"method" => "hidden",
			"value" => (isset($graphs) ? $graphs["local_graph_id"] : "0")
			),
		"local_graph_template_graph_id" => array(
			"method" => "hidden",
			"value" => (isset($graphs) ? $graphs["local_graph_template_graph_id"] : "0")
			),
		"_graph_template_id" => array(
			"method" => "hidden",
			"value" => (isset($graphs) ? $graphs["graph_template_id"] : "0")
			),
		"_host_id" => array(
			"method" => "hidden",
			"value" => (isset($host_id) ? $host_id : "0")
			)
		);

	draw_edit_form(
		array(
			"config" => array(),
			"fields" => $form_array
			)
		);

	html_end_box();

	/* only display the "inputs" area if we are using a graph template for this graph */
	if (!empty($graphs["graph_template_id"])) {
		html_start_box("<strong>Supplemental Graph Template Data</strong>", "100%", $colors["header"], "3", "center", "");

		draw_nontemplated_fields_graph($graphs["graph_template_id"], $graphs, "|field|", "<strong>Graph Fields</strong>", true, true, 0);
		draw_nontemplated_fields_graph_item($graphs["graph_template_id"], $_GET["id"], "|field|_|id|", "<strong>Graph Item Fields</strong>", true);

		html_end_box();
	}

	/* graph item list goes here */
	if ((!empty($_GET["id"])) && (empty($graphs["graph_template_id"]))) {
		item();
	}

	if (!empty($_GET["id"])) {
		?>
		<table width="100%" align="center">
			<tr>
				<td align="center" class="textInfo" colspan="2">
					<img src="<?php print htmlspecialchars("graph_image.php?action=edit&local_graph_id=" . $_GET["id"] . "&rra_id=" . read_graph_config_option("default_rra_id"));?>" alt="">
				</td>
				<?php
				if ((isset($_SESSION["graph_debug_mode"])) && (isset($_GET["id"]))) {
					$graph_data_array["output_flag"] = RRDTOOL_OUTPUT_STDERR;
					$graph_data_array["print_source"] = 1;
					?>
					<td>
						<span class="textInfo">RRDTool Command:</span><br>
						<pre><?php print @rrdtool_function_graph($_GET["id"], 1, $graph_data_array);?></pre>
						<span class="textInfo">RRDTool Says:</span><br>
						<?php unset($graph_data_array["print_source"]);?>
						<pre><?php print @rrdtool_function_graph($_GET["id"], 1, $graph_data_array);?></pre>
					</td>
					<?php
				}
				?>
			</tr>
		</table>
		<br>
		<?php
	}

	if (((isset($_GET["id"])) || (isset($_GET["new"]))) && (empty($graphs["graph_template_id"]))) {
		html_start_box("<strong>Graph Configuration</strong>", "100%", $colors["header"], "3", "center", "");

		$form_array = array();

		while (list($field_name, $field_array) = each($struct_graph)) {
			$form_array += array($field_name => $struct_graph[$field_name]);

			$form_array[$field_name]["value"] = (isset($graphs) ? $graphs[$field_name] : "");
			$form_array[$field_name]["form_id"] = (isset($graphs) ? $graphs["id"] : "0");

			if (!(($use_graph_template == false) || ($graphs_template{"t_" . $field_name} == "on"))) {
				$form_array[$field_name]["method"] = "template_" . $form_array[$field_name]["method"];
				$form_array[$field_name]["description"] = "";
			}
		}

		draw_edit_form(
			array(
				"config" => array(
					"no_form_tag" => true
					),
				"fields" => $form_array
				)
			);

		html_end_box();
	}

	if ((isset($_GET["id"])) || (isset($_GET["new"]))) {
		form_hidden_box("save_component_graph","1","");
		form_hidden_box("save_component_input","1","");
	}else{
		form_hidden_box("save_component_graph_new","1","");
	}

	form_hidden_box("rrdtool_version", read_config_option("rrdtool_version"), "");
	form_save_button("graphs.php");

//Now we need some javascript to make it dynamic
?>
<script language="JavaScript">

dynamic();

function dynamic() {
	//alert("RRDTool Version is '" + document.getElementById('rrdtool_version').value + "'");
	//alert("Log is '" + document.getElementById('auto_scale_log').checked + "'");
	if (document.getElementById('scale_log_units')) {
		document.getElementById('scale_log_units').disabled=true;
		if ((document.getElementById('rrdtool_version').value != 'rrd-1.0.x') &&
			(document.getElementById('auto_scale_log').checked)) {
			document.getElementById('scale_log_units').disabled=false;
		}
	}
}

function changeScaleLog() {
	//alert("Log changed to '" + document.getElementById('auto_scale_log').checked + "'");
	if (document.getElementById('scale_log_units')) {
		document.getElementById('scale_log_units').disabled=true;
		if ((document.getElementById('rrdtool_version').value != 'rrd-1.0.x') &&
			(document.getElementById('auto_scale_log').checked)) {
			document.getElementById('scale_log_units').disabled=false;
		}
	}
}
</script>
<?php

}

function _cacti_graph() {
	global $colors, $graph_actions, $item_rows;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("host_id"));
	input_validate_input_number(get_request_var_request("graph_rows"));
	input_validate_input_number(get_request_var_request("template_id"));
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
		kill_session_var("sess_graph_current_page");
		kill_session_var("sess_graph_filter");
		kill_session_var("sess_graph_sort_column");
		kill_session_var("sess_graph_sort_direction");
		kill_session_var("sess_graph_host_id");
		kill_session_var("sess_graph_rows");
		kill_session_var("sess_graph_template_id");

		unset($_REQUEST["page"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["sort_column"]);
		unset($_REQUEST["sort_direction"]);
		unset($_REQUEST["host_id"]);
		unset($_REQUEST["graph_rows"]);
		unset($_REQUEST["template_id"]);
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("page", "sess_graph_current_page", "1");
	load_current_session_value("filter", "sess_graph_filter", "");
	load_current_session_value("sort_column", "sess_graph_sort_column", "title_cache");
	load_current_session_value("sort_direction", "sess_graph_sort_direction", "ASC");
	load_current_session_value("host_id", "sess_graph_host_id", "-1");
	load_current_session_value("graph_rows", "sess_graph_rows", read_config_option("num_rows_graph"));
	load_current_session_value("template_id", "sess_graph_template_id", "-1");

	/* if the number of rows is -1, set it to the default */
	if (get_request_var_request("graph_rows") == -1) {
		$_REQUEST["graph_rows"] = read_config_option("num_rows_graph");
	}

	?>
	<script type="text/javascript">
	<!--

	function applyGraphsFilterChange(objForm) {
		strURL = '?host_id=' + objForm.host_id.value;
		strURL = strURL + '&graph_rows=' + objForm.graph_rows.value;
		strURL = strURL + '&filter=' + objForm.filter.value;
		strURL = strURL + '&template_id=' + objForm.template_id.value;
		document.location = strURL;
	}

	-->
	</script>
	<?php

	html_start_box("<strong>Graph Management</strong>", "100%", $colors["header"], "3", "center", "graphs.php?action=graph_edit&host_id=" . htmlspecialchars(get_request_var_request("host_id")));

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
			<form name="form_graph_id" action="graphs.php">
			<table cellpadding="1" cellspacing="0">
				<tr>
					<td width="50">
						Host:&nbsp;
					</td>
					<td width="1">
						<select name="host_id" onChange="applyGraphsFilterChange(document.form_graph_id)">
							<option value="-1"<?php if (get_request_var_request("host_id") == "-1") {?> selected<?php }?>>Any</option>
							<option value="0"<?php if (get_request_var_request("host_id") == "0") {?> selected<?php }?>>None</option>
							<?php
							if (read_config_option("auth_method") != 0) {
								/* get policy information for the sql where clause */
								$current_user = db_fetch_row("select * from user_auth where id=" . $_SESSION["sess_user_id"]);
								$sql_where = get_graph_permissions_sql($current_user["policy_graphs"], $current_user["policy_hosts"], $current_user["policy_graph_templates"]);

								$hosts = db_fetch_assoc("SELECT DISTINCT host.id, CONCAT_WS('',host.description,' (',host.hostname,')') as name
									FROM (graph_templates_graph,host)
									LEFT JOIN graph_local ON (graph_local.host_id=host.id)
									LEFT JOIN graph_templates ON (graph_templates.id=graph_local.graph_template_id)
									LEFT JOIN user_auth_perms ON ((graph_templates_graph.local_graph_id=user_auth_perms.item_id and user_auth_perms.type=1 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (host.id=user_auth_perms.item_id and user_auth_perms.type=3 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (graph_templates.id=user_auth_perms.item_id and user_auth_perms.type=4 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . "))
									WHERE graph_templates_graph.local_graph_id=graph_local.id
									" . (empty($sql_where) ? "" : "and $sql_where") . "
									ORDER BY name");
							}else{
								$hosts = db_fetch_assoc("SELECT DISTINCT host.id, CONCAT_WS('',host.description,' (',host.hostname,')') as name
									FROM host
									ORDER BY name");
							}

							if (sizeof($hosts) > 0) {
								foreach ($hosts as $host) {
									print "<option value='" . $host["id"] . "'"; if (get_request_var_request("host_id") == $host["id"]) { print " selected"; } print ">" . title_trim(htmlspecialchars($host["name"]), 40) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td width="70">
						&nbsp;Template:&nbsp;
					</td>
					<td width="1">
						<select name="template_id" onChange="applyGraphsFilterChange(document.form_graph_id)">
							<option value="-1"<?php if (get_request_var_request("template_id") == "-1") {?> selected<?php }?>>Any</option>
							<option value="0"<?php if (get_request_var_request("template_id") == "0") {?> selected<?php }?>>None</option>
							<?php
							if (read_config_option("auth_method") != 0) {
								$templates = db_fetch_assoc("SELECT DISTINCT graph_templates.id, graph_templates.name
									FROM (graph_templates_graph,graph_local)
									LEFT JOIN host ON (host.id=graph_local.host_id)
									LEFT JOIN graph_templates ON (graph_templates.id=graph_local.graph_template_id)
									LEFT JOIN user_auth_perms ON ((graph_templates_graph.local_graph_id=user_auth_perms.item_id and user_auth_perms.type=1 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (host.id=user_auth_perms.item_id and user_auth_perms.type=3 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (graph_templates.id=user_auth_perms.item_id and user_auth_perms.type=4 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . "))
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
									print "<option value='" . $template["id"] . "'"; if (get_request_var_request("template_id") == $template["id"]) { print " selected"; } print ">" . title_trim(htmlspecialchars($template["name"]), 40) . "</option>\n";
								}
							}
							?>
						</select>
					</td>
					<td width="120" nowrap style='white-space: nowrap;'>
						&nbsp;<input type="submit" value="Go" title="Set/Refresh Filters">
						<input type="submit" name="clear_x" value="Clear" title="Clear Filters">
					</td>
				</tr>
			</table>
			<table cellpadding="1" cellspacing="0">
				<tr>
					<td width="50">
						&nbsp;Search:&nbsp;
					</td>
					<td>
						<input type="text" name="filter" size="40" value="<?php print htmlspecialchars(get_request_var_request("filter"));?>">
					</td>
					<td nowrap style='white-space: nowrap;' width="50">
						Rows per Page:&nbsp;
					</td>
					<td width="1">
						<select name="graph_rows" onChange="applyGraphsFilterChange(document.form_graph_id)">
							<option value="-1"<?php if (get_request_var_request("graph_rows") == "-1") {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows) > 0) {
								foreach ($item_rows as $key => $value) {
									print "<option value='" . $key . "'"; if (get_request_var_request("graph_rows") == $key) { print " selected"; } print ">" . htmlspecialchars($value) . "</option>\n";
								}
							}
							?>
						</select>
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
	if (strlen(get_request_var_request("filter"))) {
		$sql_where = " AND (graph_templates_graph.title_cache like '%%" . get_request_var_request("filter") . "%%'" .
			" OR graph_templates.name like '%%" . get_request_var_request("filter") . "%%')";
	}else{
		$sql_where = "";
	}

	if (get_request_var_request("host_id") == "-1") {
		/* Show all items */
	}elseif (get_request_var_request("host_id") == "0") {
		$sql_where .= " AND graph_local.host_id=0";
	}elseif (!empty($_REQUEST["host_id"])) {
		$sql_where .= " AND graph_local.host_id=" . get_request_var_request("host_id");
	}

	if (get_request_var_request("template_id") == "-1") {
		/* Show all items */
	}elseif (get_request_var_request("template_id") == "0") {
		$sql_where .= " AND graph_templates_graph.graph_template_id=0";
	}elseif (!empty($_REQUEST["template_id"])) {
		$sql_where .= " AND graph_templates_graph.graph_template_id=" . get_request_var_request("template_id");
	}

	/* allow plugins to modify sql_where */
	$sql_where .= plugin_hook_function('graphs_sql_where', $sql_where);

	/* print checkbox form for validation */
	print "<form name='chk' method='post' action='graphs.php'>\n";

	html_start_box("", "100%", $colors["header"], "3", "center", "");

	$total_rows = db_fetch_cell("SELECT
		COUNT(graph_templates_graph.id)
		FROM (graph_local,graph_templates_graph)
		LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
		WHERE graph_local.id=graph_templates_graph.local_graph_id
		$sql_where");

	$graph_list = db_fetch_assoc("SELECT
		graph_templates_graph.id,
		graph_templates_graph.local_graph_id,
		graph_templates_graph.height,
		graph_templates_graph.width,
		graph_templates_graph.title_cache,
		graph_templates.name,
		graph_local.host_id
		FROM (graph_local,graph_templates_graph)
		LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
		WHERE graph_local.id=graph_templates_graph.local_graph_id
		$sql_where
		ORDER BY " . $_REQUEST["sort_column"] . " " . get_request_var_request("sort_direction") .
		" LIMIT " . (get_request_var_request("graph_rows")*(get_request_var_request("page")-1)) . "," . get_request_var_request("graph_rows"));

	/* generate page list */
	$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, get_request_var_request("graph_rows"), $total_rows, "graphs.php?filter=" . get_request_var_request("filter") . "&host_id=" . get_request_var_request("host_id"));

	$nav = "<tr bgcolor='#" . $colors["header"] . "'>
			<td colspan='5'>
				<table width='100%' cellspacing='0' cellpadding='0' border='0'>
					<tr>
						<td align='left' class='textHeaderDark'>
							<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("graphs.php?filter=" . get_request_var_request("filter") . "&host_id=" . get_request_var_request("host_id") . "&page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
						</td>\n
						<td align='center' class='textHeaderDark'>
							Showing Rows " . ((get_request_var_request("graph_rows")*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < get_request_var_request("graph_rows")) || ($total_rows < (get_request_var_request("graph_rows")*get_request_var_request("page")))) ? $total_rows : (get_request_var_request("graph_rows")*get_request_var_request("page"))) . " of $total_rows [$url_page_select]
						</td>\n
						<td align='right' class='textHeaderDark'>
							<strong>"; if ((get_request_var_request("page") * get_request_var_request("graph_rows")) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("graphs.php?filter=" . get_request_var_request("filter") . "&host_id=" . get_request_var_request("host_id") . "&page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * get_request_var_request("graph_rows")) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
						</td>\n
					</tr>
				</table>
			</td>
		</tr>\n";

	print $nav;

	$display_text = array(
		"title_cache" => array("Graph Title", "ASC"),
		"local_graph_id" => array("ID", "ASC"),
		"name" => array("Template Name", "ASC"),
		"height" => array("Size", "ASC"));

	html_header_sort_checkbox($display_text, get_request_var_request("sort_column"), get_request_var_request("sort_direction"), false);

	$i = 0;
	if (sizeof($graph_list) > 0) {
		foreach ($graph_list as $graph) {
			/* we're escaping strings here, so no need to escape them on form_selectable_cell */
			$template_name = ((empty($graph["name"])) ? "<em>None</em>" : htmlspecialchars($graph["name"]));
			form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $graph["local_graph_id"]); $i++;
			form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars("graphs.php?action=graph_edit&id=" . $graph["local_graph_id"]) . "' title='" . htmlspecialchars($graph["title_cache"]) . "'>" . ((get_request_var_request("filter") != "") ? preg_replace("/(" . preg_quote(get_request_var_request("filter"), "/") . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", title_trim(htmlspecialchars($graph["title_cache"]), read_config_option("max_title_graph"))) : title_trim(htmlspecialchars($graph["title_cache"]), read_config_option("max_title_graph"))) . "</a>", $graph["local_graph_id"]);
			form_selectable_cell($graph["local_graph_id"], $graph["local_graph_id"]);
			form_selectable_cell(((get_request_var_request("filter") != "") ? preg_replace("/(" . preg_quote(get_request_var_request("filter"), "/") . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", $template_name) : $template_name), $graph["local_graph_id"]);
			form_selectable_cell($graph["height"] . "x" . $graph["width"], $graph["local_graph_id"]);
			form_checkbox_cell($graph["title_cache"], $graph["local_graph_id"]);
			form_end_row();
		}

		/* put the nav bar on the bottom as well */
		print $nav;
	}else{
		print "<tr><td><em>No Graphs Found</em></td></tr>";
	}

	html_end_box(false);

	/* add a list of tree names to the actions dropdown */
	tree_add_tree_names_to_actions_array();

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($graph_actions);

	print "</form>\n";
}

?>

