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
include_once(CACTI_LIBRARY_PATH . "/xaxis.php");

define("MAX_DISPLAY_PAGES", 21);

define("XAXIS_ACTION_DELETE", "1");
define("XAXIS_ACTION_DUPLICATE", "2");
$xaxis_actions = array(
	XAXIS_ACTION_DELETE => "Delete",
	XAXIS_ACTION_DUPLICATE => "Duplicate"
	);

$xaxis_actions = plugin_hook_function('xaxis_action_array', $xaxis_actions);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

//print_r($_REQUEST);

switch (get_request_var_request("action")) {
	case 'save':
		xaxis_form_save();

		break;
	case 'actions':
		xaxis_form_actions();

		break;
	case 'item_movedown':
		xaxis_item_movedown();

		header("Location: xaxis.php?action=edit&id=" . $_GET["xaxis_id"]);
		break;
	case 'item_moveup':
		xaxis_item_moveup();

		header("Location: xaxis.php?action=edit&id=" . $_GET["xaxis_id"]);
		break;
	case 'item_remove':
		xaxis_item_remove();

		header("Location: xaxis.php?action=edit&id=" . $_GET["xaxis_id"]);
		break;
	case 'item_edit':
		include_once("./include/top_header.php");

		xaxis_item_edit();

		include_once("./include/bottom_footer.php");
		break;
	case 'remove':
		xaxis_remove();

		header ("Location: xaxis.php");
		break;
	case 'edit':
		include_once("./include/top_header.php");

		xaxis_edit();

		include_once("./include/bottom_footer.php");
		break;
	default:
		include_once("./include/top_header.php");

		xaxis();

		include_once("./include/bottom_footer.php");
		break;
}

/* --------------------------
 Form Functions
 -------------------------- */

function xaxis_form_save() {
	if (isset($_POST["save_component_xaxis"])) {
		$save["id"]   = $_POST["id"];
		$save["hash"] = get_hash_xaxis($_POST["id"]);
		$save["name"] = form_input_validate($_POST["name"], "name", "", false, 3);

		if (!is_error_message()) {
			$xaxis_id = sql_save($save, "graph_templates_xaxis");

			if ($xaxis_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		header("Location: xaxis.php?action=edit&id=" . (empty($xaxis_id) ? $_POST["id"] : $xaxis_id));
		exit;
	}

	if ((isset($_POST["save_component_item"]))) {
		$save["id"]   = $_POST["id"];
		$save["hash"] = get_hash_xaxis($_POST["id"], "xaxis_item");
		$save["item_name"] = form_input_validate($_POST["item_name"], "item_name", "", true, 3);
		$save["xaxis_id"] = form_input_validate($_POST["xaxis_id"], "xaxis_id", "^[0-9]+$", false, 3);
		$save["timespan"] = form_input_validate($_POST["timespan"], "timespan", "^[0-9]+$", false, 3);
		$save["gtm"] = form_input_validate($_POST["gtm"], "gtm", "", false, 3);
		$save["gst"] = form_input_validate($_POST["gst"], "gst", "^[0-9]+$", false, 3);
		$save["mtm"] = form_input_validate($_POST["mtm"], "mtm", "", false, 3);
		$save["mst"] = form_input_validate($_POST["mst"], "mst", "^[0-9]+$", false, 3);
		$save["ltm"] = form_input_validate($_POST["ltm"], "ltm", "", false, 3);
		$save["lst"] = form_input_validate($_POST["lst"], "lst", "^[0-9]+$", false, 3);
		$save["lpr"] = form_input_validate($_POST["lpr"], "lpr", "^[0-9]+$", false, 3);
		$save["lfm"] = form_input_validate($_POST["lfm"], "lfm", "", true, 3);

		if (!is_error_message()) {
			$xaxis_item_id = sql_save($save, "graph_templates_xaxis_items");

			if ($xaxis_item_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		if (is_error_message()) {
			header("Location: xaxis.php?action=item_edit&xaxis_id=" . $_POST["xaxis_id"] . "&id=" . (empty($xaxis_item_id) ? $_POST["id"] : $xaxis_item_id));
		}else{
			header("Location: xaxis.php?action=edit&id=" . (!empty($_POST["xaxis_id"]) ? $_POST["xaxis_id"] : 0));
		}
		exit;
	}
}

/* ------------------------
 The "actions" function
 ------------------------ */

function xaxis_form_actions() {
	global $colors, $xaxis_actions;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === "1") { /* delete */
			/* do a referential integrity check */
			if (sizeof($selected_items)) {
				foreach($selected_items as $xaxis_id) {
					/* ================= input validation ================= */
					input_validate_input_number($xaxis_id);
					/* ==================================================== */

					$graph_data = db_fetch_assoc("SELECT " .
									"local_graph_id, " .
									"graph_template_id, " .
									"graph_template_xaxis.id, " .
									"graph_template_xaxis.name " .
									"FROM graph_templates_xaxis " .
									"LEFT JOIN graph_templates_graph " .
									"ON (graph_templates_xaxis.id = graph_templates_graph.x_grid) " .
									"WHERE graph_template_xaxis.id=" . $xaxis_id .
									" LIMIT 1");
					if (sizeof($graph_data)) {
						$bad_ids[$xaxis_id] = $graph_data;
					}else{
						$xaxis_ids[] = $xaxis_id;
					}
				}
			}

			if (isset($bad_ids)) {
				$message = "";
				foreach($bad_ids as $key => $value) {
					$message .= (strlen($message) ? "<br>":"") . "<i>" .
					__("X-Axis Preset Id/Name ($s, $s) is in use by Graph/Template ($d, $d) and can not be removed", $key, $value["name"], $value["local_graph_id"], $value["graph_template_id"]) .
					"</i>\n";
				}

				$_SESSION['sess_message_xaxis_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');

				raise_message('xaxis_ref_int');
			}

			if (isset($xaxis_ids)) {
				db_execute("delete from graph_templates_xaxis where " . array_to_sql_or($xaxis_ids, "id"));
				db_execute("delete from graph_templates_xaxis_items where " . array_to_sql_or($xaxis_ids, "xaxis_id"));
			}
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				duplicate_xaxis($selected_items[$i], get_request_var_post("title_format"));
			}
		}

		header("Location: xaxis.php");
		exit;
	}

	/* setup some variables */
	$xaxis_list = "";

	/* loop through each of the graphs selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$xaxis_list .= "<li>" . db_fetch_cell("select name from graph_templates_xaxis where id=" . $matches[1]) . "</li>";
			$xaxis_array[] = $matches[1];
		}
	}

	include_once("./include/top_header.php");

	print "<form id='xactions' name='xactions' action='xaxis.php' method='post'>\n";
	html_start_box("<strong>" . $xaxis_actions{$_POST["drp_action"]} . "</strong>", "60%", $colors["header_panel"], 3, "center", "");

	if (isset($xaxis_array) && sizeof($xaxis_array)) {
		if (get_request_var_post("drp_action") === "1") { /* delete */
			print "	<tr>
					<td class='topBoxAlt'>
						<p>" . "When you click 'Continue', the following X-Axis Preset(s) will be deleted." . "</p>
						<p><ul>$xaxis_list</ul></p>
					</td>
				</tr>\n";

			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Delete X-Axis Preset(s)'>";
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			print "	<tr>
					<td class='topBoxAlt'>
						<p>" . "When you click 'Continue', the following X-Axis Preset(s) will be duplicated. You can optionally change the title format for the new X-Axis Preset(s)." . "</p>
						<p><ul>$xaxis_list</ul></p>
						<p><strong>" . "Title Format:" . "</strong><br>"; form_text_box("title_format", "<xaxis_title> (1)", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n";

			$save_html = "<input type='button' value='Cancel' onClick='window.history.back()'>&nbsp;<input type='submit' value='Continue' title='Duplicate xaxis(s)'>";
		}
	}else{
		print "<tr><td bgcolor='#" . $colors["form_alternate1"]. "'><span class='textError'>You must select at least one X-Axis.</span></td></tr>\n";
		$save_html = "<input type='button' value='Return' onClick='window.history.back()'>";
	}

	print "	<tr>
			<td align='right' bgcolor='#eaeaea'>
				<input type='hidden' name='action' value='actions'>
				<input type='hidden' name='selected_items' value='" . (isset($xaxis_array) ? serialize($xaxis_array) : '') . "'>
				<input type='hidden' name='drp_action' value='" . $_POST["drp_action"] . "'>
				$save_html
			</td>
		</tr>
		";

	html_end_box();

	include_once("./include/bottom_footer.php");
}

/* ---------------------
 X-Axis Functions
 --------------------- */

function xaxis_item_remove_confirm() {
	require(CACTI_BASE_PATH . "/include/presets/preset_xaxis_arrays.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("id"));
	input_validate_input_number(get_request_var_request("item_id"));
	/* ==================================================== */

	print "<form id='delete' action='xaxis.php' name='delete' method='post'>\n";

	html_start_box("", "100", "3", "center", "");

	$xaxis       = db_fetch_row("SELECT * FROM graph_templates_xaxis WHERE id=" . get_request_var_request("id"));
	$xaxis_item  = db_fetch_row("SELECT * FROM graph_templates_xaxis_items WHERE id=" . get_request_var_request("item_id"));

	?>
	<tr>
		<td class='topBoxAlt'>
			<p><?php print "When you click 'Continue', the following X-Axis item will be deleted.";?></p>
			<p>X-Axis Name: '<?php print $xaxis["name"];?>'<br>
			<em><?php print $xaxis_item["item_name"] . " " . $xaxis_item["timespan"];?></em>
		</td>
	</tr>
	<tr>
		<td align='right'>
			<input id='cancel' type='button' value='<?php print "Cancel";?>' onClick='$("#cdialog").dialog("close";' name='cancel')>
			<input id='continue' type='button' value='<?php print "Continue";?>' name='continue' title='<?php print "Remove X-Axis Item";?>'>
		</td>
	</tr>
	</form>
	<?php

	html_end_box();

	?>
	</form>
	<script type='text/javascript'>
	$('#continue').click(function(data) {
		$.post('xaxis.php?action=item_remove', { item_id: <?php print get_request_var("item_id");?>, id: <?php print get_request_var("id");?> }, function(data) {
			$('#cdialog').dialog('close');
			$.get('xaxis.php?action=ajax_edit&id=<?php print get_request_var("id");?>', function(data) {
				$('#content').html(data);
			});
		});
        });
        </script>
	<?php
}
		
function xaxis_item_remove() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_post("id"));
	/* ==================================================== */

	db_execute("DELETE FROM graph_templates_xaxis_items WHERE id=" . $_GET["id"]);
}

function xaxis_item_edit() {
	require(CACTI_BASE_PATH . "/include/presets/preset_xaxis_forms.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("xaxis_id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$xaxis_items = db_fetch_row("select * from graph_templates_xaxis_items where id=" . $_GET["id"]);
		$header_label = "[edit: " . $xaxis_items["item_name"] . "]";
	}else{
		$header_label = "[new]";
	}

	print "<form id='xaxis_item_edit' name='xaxis_item_edit' method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "'>\n";
	html_start_box("X-Axis Items" . " $header_label", "100%", "aaaaaa", 3, "center", "");

	draw_edit_form(
		array(
			"config" => array("no_form_tag" => true),
			"fields" => inject_form_variables(preset_xaxis_item_form_list(), (isset($xaxis_items) ? $xaxis_items : array()))
			)
		);

	html_end_box();

	form_hidden_box("id", (isset($_GET["id"]) ? $_GET["id"] : "0"), "");
	form_hidden_box("xaxis_id", get_request_var("xaxis_id"), "0");
	form_hidden_box("save_component_item", "1", "");

	form_save_button("xaxis.php?action=edit&id=" . $_GET["xaxis_id"]);
}

function xaxis_edit() {
	global $colors;
	require(CACTI_BASE_PATH . "/include/presets/preset_xaxis_arrays.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$xaxis = db_fetch_row("select * from graph_templates_xaxis where id=" . $_GET["id"]);
		$header_label = "[edit: " . $xaxis["name"] . "]";
	}else{
		$header_label = "[new]";
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='xaxis_edit'>\n";
	html_start_box("X-Axis Presets" . " $header_label", "100%", $colors["header"], 3, "center", "");

	draw_edit_form(array(
		"config" => array("no_form_tag" => true),
		"fields" => inject_form_variables(preset_xaxis_form_list(), (isset($xaxis) ? $xaxis : array()))
	));

	html_end_box();

	if (!empty($_GET["id"])) {
		$sql_query = "SELECT * FROM graph_templates_xaxis_items WHERE xaxis_id=" . $_GET["id"] . " ORDER BY timespan ASC";
		$xaxis_items = db_fetch_assoc($sql_query);

		html_start_box("X-Axis Items", "100%", $colors["header"], 3, "center", "xaxis.php?action=item_edit&xaxis_id=" . $_GET["id"]);

		print "<tr bgcolor='#" . $colors["header_panel"] . "'>";
			DrawMatrixHeaderItem("Item",$colors["header_text"],1);
			DrawMatrixHeaderItem("Name",$colors["header_text"],1);
			DrawMatrixHeaderItem("Timespan",$colors["header_text"],1);
			DrawMatrixHeaderItem("Global Grid Span",$colors["header_text"],1);
			DrawMatrixHeaderItem("Steps",$colors["header_text"],1);
			DrawMatrixHeaderItem("Major Grid Span",$colors["header_text"],1);
			DrawMatrixHeaderItem("Steps",$colors["header_text"],1);
			DrawMatrixHeaderItem("Label Grid Span",$colors["header_text"],1);
			DrawMatrixHeaderItem("Steps",$colors["header_text"],1);
			DrawMatrixHeaderItem("Relative Label Position",$colors["header_text"],1);
			DrawMatrixHeaderItem("Label Format",$colors["header_text"],1);
			DrawMatrixHeaderItem("&nbsp;",$colors["header_text"],2);
		print "</tr>";

		if (sizeof($xaxis_items) > 0) {
			$i = 0;
			foreach ($xaxis_items as $xaxis_item) {
				form_alternate_row_color($colors["alternate"],$colors["light"],$i);
				form_selectable_cell("<a style='white-space:nowrap;' class='linkEditMain' href='" . htmlspecialchars("xaxis.php?action=item_edit&id=" . $xaxis_item["id"] . "&xaxis_id=" . $_GET["id"]) . "'>Item# $i</a>", $xaxis_item["id"]);
				form_selectable_cell((isset($xaxis_item["item_name"]) ? $xaxis_item["item_name"] : ''), $xaxis_item["id"]);
				form_selectable_cell((isset($xaxis_item["timespan"]) ? $xaxis_item["timespan"] : 0), $xaxis_item["id"], '', '', 'right');
				form_selectable_cell((isset($rrd_xaxis_timespans[$xaxis_item["gtm"]]) ? $rrd_xaxis_timespans[$xaxis_item["gtm"]] : "None"), $xaxis_item["id"]);
				form_selectable_cell((isset($xaxis_item["gst"]) ? $xaxis_item["gst"] : 0), $xaxis_item["id"], '', '', 'right');
				form_selectable_cell((isset($rrd_xaxis_timespans[$xaxis_item["mtm"]]) ? $rrd_xaxis_timespans[$xaxis_item["mtm"]] : "None"), $xaxis_item["id"]);
				form_selectable_cell((isset($xaxis_item["mst"]) ? $xaxis_item["mst"] : 0), $xaxis_item["id"], '', '', 'right');
				form_selectable_cell((isset($rrd_xaxis_timespans[$xaxis_item["ltm"]]) ? $rrd_xaxis_timespans[$xaxis_item["ltm"]] : "None"), $xaxis_item["id"]);
				form_selectable_cell((isset($xaxis_item["lst"]) ? $xaxis_item["lst"] : 0), $xaxis_item["id"], '', '', 'right');
				form_selectable_cell((isset($xaxis_item["lpr"]) ? $xaxis_item["lpr"] : 0), $xaxis_item["id"], '', '', 'right');
				form_selectable_cell((isset($xaxis_item["lfm"]) ? $xaxis_item["lfm"] : "None"), $xaxis_item["id"]);
				?>
				<td align="right">
					<a href="<?php print htmlspecialchars("xaxis.php?action=item_remove&id=" . $xaxis_item["id"] . "&xaxis_id=" . $xaxis["id"]);?>"><img src="images/delete_icon.gif" style="height:10px;width:10px;" border="0" alt="Delete X-Axis Preset Item"></a>
				</td>
				<?php
				$i++;
				form_end_row();
			}
		}else{
			print "<tr><td><em>" . "No X-Axis Preset Items" . "</em></td></tr>";
		}
		html_end_box();
	}

	form_hidden_box("id", (isset($_GET["id"]) ? $_GET["id"] : "0"), "");
	form_hidden_box("save_component_xaxis", "1", "");
	form_save_button("xaxis.php", "return");
}

function xaxis_filter() {
	global $item_rows;

	?>
	<?php

	html_start_box("X-Axis Presets", "100%", 3, "center", "xaxis.php?action=edit");
	?>
	<tr class='rowAlternate3'>
		<td>
		<form action="xaxis.php" name="form_xaxis" method="post">
		<table cellpadding="0" cellspacing="3">
			<tr>
				<td class="w1">
					<?php print "Search:";?>
				</td>
				<td class="w1">
					<input type="text" name="filter" size="30" value="<?php print html_get_page_variable("filter");?>">
				</td>
				<td class="w1">
					<?php print "Rows:";?>
				</td>
				<td class="w1">
					<select name="rows" onChange="applyFilterChange(document.form_xaxis)">
						<option value="-1"
						<?php if (html_get_page_variable("rows") == "-1") {?> selected
						<?php }?>>Default</option>
						<?php
						if (sizeof($item_rows) > 0) {
							foreach ($item_rows as $key => $value) {
								print "<option value='" . $key . "'"; if (html_get_page_variable("rows") == $key) { print " selected"; } print ">" . $value . "</option>\n";
							}
						}
						?>
					</select></td>
				<td class="w1">
					<input type="submit" Value="<?php print "Go";?>" name="go" align="middle">
					<input type="submit" Value="<?php print "Clear";?>" name="clear" align="middle">
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

function get_xaxis_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	if (strlen(html_get_page_variable("filter"))) {
		$sql_where = "WHERE (name LIKE '%%" . html_get_page_variable("filter") . "%%')";
	}else{
		$sql_where = "";
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$total_rows = db_fetch_cell("SELECT COUNT(id)
		FROM graph_templates_xaxis
		$sql_where");

	return db_fetch_assoc("SELECT *
		FROM graph_templates_xaxis
		$sql_where
		ORDER BY " . html_get_page_variable('sort_column') . " " . html_get_page_variable('sort_direction') .
		" LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp);
}

function _xaxis($refresh = true) {
	global $xaxis_actions;

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
			"name" => "Name",
			"filter" => true,
			"link" => true,
			"order" => "ASC"
		),
		"id" => array(
			"name" => "ID",
			"align" => "right",
			"order" => "ASC"
		)
	);

	/* initialize page behavior */
	$table->href           = "xaxis.php";
	$table->session_prefix = "sess_xaxis";
	$table->filter_func    = "xaxis_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = $xaxis_actions;
	$table->table_id       = "xaxis_presets";

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_xaxis_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}

function xaxis() {
	global $colors, $xaxis_actions;

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
		kill_session_var("sess_xaxis_current_page");
		kill_session_var("sess_xaxis_filter");
		kill_session_var("sess_xaxis_sort_column");
		kill_session_var("sess_xaxis_sort_direction");

		unset($_REQUEST["page"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["sort_column"]);
		unset($_REQUEST["sort_direction"]);

	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("page", "sess_xaxis_current_page", "1");
	load_current_session_value("filter", "sess_xaxis_filter", "");
	load_current_session_value("sort_column", "sess_xaxis_sort_column", "name");
	load_current_session_value("sort_direction", "sess_xaxis_sort_direction", "ASC");

	html_start_box("<strong>X-Axis Presets</strong>", "100%", $colors["header"], 3, "center", "xaxis.php?action=edit");

	?>
	<tr bgcolor="#<?php print $colors["panel"];?>">
		<td>
			<form name="form_xaxis" action="xaxis.php">
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
	$sql_where = "WHERE (graph_templates_xaxis.name LIKE '%%" . get_request_var_request("filter") . "%%')";

	/* print checkbox form for validation */
	print "<form name='chk' method='post' action='xaxis.php'>\n";

	html_start_box("", "100%", $colors["header"], "3", "center", "");

	$total_rows = db_fetch_cell("SELECT
		COUNT(graph_templates_xaxis.id)
		FROM graph_templates_xaxis
		$sql_where");

	$xaxis_list = db_fetch_assoc("SELECT
		graph_templates_xaxis.id,graph_templates_xaxis.name
		FROM graph_templates_xaxis
		$sql_where
		ORDER BY " . get_request_var_request("sort_column") . " " . get_request_var_request("sort_direction") .
		" LIMIT " . (read_config_option("num_rows_device")*(get_request_var_request("page")-1)) . "," . read_config_option("num_rows_device"));

	/* generate page list */
	$url_page_select = get_page_list(get_request_var_request("page"), MAX_DISPLAY_PAGES, read_config_option("num_rows_device"), $total_rows, "xaxis.php?filter=" . get_request_var_request("filter"));

	$nav = "<tr bgcolor='#" . $colors["header"] . "'>
		<td colspan='7'>
			<table width='100%' cellspacing='0' cellpadding='0' border='0'>
				<tr>
					<td align='left' class='textHeaderDark'>
						<strong>&lt;&lt; "; if (get_request_var_request("page") > 1) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("xaxis.php?filter=" . get_request_var_request("filter") . "&page=" . (get_request_var_request("page")-1)) . "'>"; } $nav .= "Previous"; if (get_request_var_request("page") > 1) { $nav .= "</a>"; } $nav .= "</strong>
					</td>\n
					<td align='center' class='textHeaderDark'>
						Showing Rows " . ((read_config_option("num_rows_device")*(get_request_var_request("page")-1))+1) . " to " . ((($total_rows < read_config_option("num_rows_device")) || ($total_rows < (read_config_option("num_rows_device")*get_request_var_request("page")))) ? $total_rows : (read_config_option("num_rows_device") * get_request_var_request("page"))) . " of $total_rows [$url_page_select]
					</td>\n
					<td align='right' class='textHeaderDark'>
						<strong>"; if ((get_request_var_request("page") * read_config_option("num_rows_device")) < $total_rows) { $nav .= "<a class='linkOverDark' href='" . htmlspecialchars("xaxis.php?filter=" . get_request_var_request("filter") . "&page=" . (get_request_var_request("page")+1)) . "'>"; } $nav .= "Next"; if ((get_request_var_request("page") * read_config_option("num_rows_device")) < $total_rows) { $nav .= "</a>"; } $nav .= " &gt;&gt;</strong>
					</td>\n
				</tr>
			</table>
		</td>
		</tr>\n";

	print $nav;

	$display_text = array(
		"name" => array("XAXIS Title", "ASC"));

	html_header_sort_checkbox($display_text, get_request_var_request("sort_column"), get_request_var_request("sort_direction"), false);

	$i = 0;
	if (sizeof($xaxis_list) > 0) {
		foreach ($xaxis_list as $xaxis) {
			form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $xaxis["id"]);$i++;
			form_selectable_cell("<a class='linkEditMain' href='" . htmlspecialchars("xaxis.php?action=edit&id=" . $xaxis["id"]) . "'>" . (strlen(get_request_var_request("filter")) ? preg_replace("/(" . preg_quote(get_request_var_request("filter"), "/") . ")/i", "<span style='background-color: #F8D93D;'>\\1</span>", htmlspecialchars($xaxis["name"])) : htmlspecialchars($xaxis["name"])) . "</a>", $xaxis["id"]);
			form_checkbox_cell($xaxis["name"], $xaxis["id"]);
			form_end_row();
		}
		print $nav;
	}else{
		print "<tr><td><em>No XAXISs</em></td></tr>\n";
	}
	html_end_box(false);

	/* draw the dropdown containing a list of available actions for this form */
	draw_actions_dropdown($xaxis_actions);

	print "</form>\n";
}