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

$gprint_actions = array(
	"1" => __("Delete")
	);

define("MAX_DISPLAY_PAGES", 21);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch (get_request_var_request("action")) {
	case 'save':
		gprint_presets_form_save();

		break;
	case 'actions':
		gprint_presets_form_actions();

		break;
	case 'edit':
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		gprint_presets_edit();
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
	case 'ajax_view':
		gprint_presets();

		break;
	default:
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		gprint_presets();
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
}


/* ------------------------
    The "actions" function
   ------------------------ */

function gprint_presets_form_actions() {
	global $gprint_actions;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === "1") { /* delete */
			/* do a referential integrity check */
			if (sizeof($selected_items)) {
			foreach($selected_items as $gprint_id) {
				/* ================= input validation ================= */
				input_validate_input_number($gprint_id);
				/* ==================================================== */

				if (sizeof(db_fetch_assoc("SELECT gprint_id FROM graph_templates_item WHERE gprint_id=$gprint_id LIMIT 1 UNION (SELECT right_axis_format AS gprint_id FROM graph_templates_graph WHERE right_axis_format=$gprint_id LIMIT 1)"))) {
					$bad_ids[] = $gprint_id;
				}else{
					$gprint_ids[] = $gprint_id;
				}
			}
			}

			if (isset($bad_ids)) {
				$message = "";
				foreach($bad_ids as $gprint_id) {
					$message .= (strlen($message) ? "<br>":"") . "<i>GPrint " . $gprint_id . " is in use and can not be removed</i>\n";
				}

				$_SESSION['sess_message_gprint_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');

				raise_message('gprint_ref_int');
			}

			if (isset($gprint_ids)) {
				db_execute("delete from graph_templates_gprint where " . array_to_sql_or($selected_items, "id"));
			}
		}

		exit;
	}

	/* setup some variables */
	$gprint_list = "";

	/* loop through each of the items selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$gprint_list .= "<li>" . db_fetch_cell("select name from graph_templates_gprint where id=" . $matches[1]) . "</li>";
			$gprint_array[] = $matches[1];
		}
	}

	print "<form id='gpactions' name='gpactions' action='gprint_presets.php' method='post'>\n";

	html_start_box("", "100", "3", "center", "");

	if (isset($gprint_array)) {
		if (get_request_var_post("drp_action") === ACTION_NONE) { /* NONE */
			print "	<tr>
						<td class='textArea'>
							<p>" . __("You did not select a valid action. Please select 'Return' to return to the previous menu.") . "</p>
						</td>
					</tr>\n";

			$title = __("Selection Error");
		}elseif (get_request_var_post("drp_action") === "1") { /* delete */
			print "	<tr>
					<td class='topBoxAlt'>
						<p>" . __("When you click 'Continue', the following GPRINT Preset(s) will be delete.") . "</p>
						<div class='action_list'><ul>$gprint_list</ul></div>
					</td>
				</tr>\n";

			$title = __("Delete GPRINT Preset(s)");
		}
	}else{
		print "<tr><td class='topBoxAlt'><span class='textError'>" . __("You must select at least one GPRINT preset.") . "</span></td></tr>\n";

		$title = __("Selection Error");
	}

	if (!isset($gprint_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button($title);
	}else{
		form_continue(serialize($gprint_array), get_request_var_post("drp_action"), $title, "gpactions");
	}

	html_end_box();
}

/* --------------------------
    The Save Function
   -------------------------- */

function gprint_presets_form_save() {
	if (isset($_POST["save_component_gprint_presets"])) {
		$save["id"] = $_POST["id"];
		$save["hash"] = get_hash_gprint($_POST["id"]);
		$save["name"] = form_input_validate($_POST["name"], "name", "", false, 3);
		$save["gprint_text"] = form_input_validate($_POST["gprint_text"], "gprint_text", "", false, 3);

		if (!is_error_message()) {
			$gprint_preset_id = sql_save($save, "graph_templates_gprint");

			if ($gprint_preset_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		if (is_error_message()) {
			header("Location: gprint_presets.php?action=edit&id=" . (empty($gprint_preset_id) ? $_POST["id"] : $gprint_preset_id));
		}else{
			header("Location: gprint_presets.php");
		}
		exit;
	}
}

/* -----------------------------------
    gprint_presets - GPRINT Presets
   ----------------------------------- */

function gprint_presets_edit() {
	require_once(CACTI_LIBRARY_PATH . "/gprint.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$gprint_preset = db_fetch_row("select * from graph_templates_gprint where id=" . $_GET["id"]);
		$header_label = __("[edit: ") . $gprint_preset["name"] . "]";
	}else{
		$header_label = __("[new]");
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='gprint_edit'>\n";
	html_start_box(__("GPRINT Presets") . " $header_label", "100", 0, "center", "");

	draw_edit_form(array(
		"config" => array("no_form_tag" => true),
		"fields" => inject_form_variables(preset_gprint_form_list(), (isset($gprint_preset) ? $gprint_preset : array()))
		));

	html_end_box();

	form_save_button("gprint_presets.php", "return");
}

function gprint_presets_filter() {
	global $item_rows;

	html_start_box(__("GPRINT Presets"), "100", "3", "center", "gprint_presets.php?action=edit", true);
	?>
	<tr class='rowAlternate3'>
		<td>
			<form name="form_gprint" action="gprint_presets.php">
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
						<select name="rows" onChange="applyFilterChange(document.form_gprint)">
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
			<input type='hidden' name='page' value='1'>
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

function get_gprint_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	if (strlen(html_get_page_variable("filter"))) {
		$sql_where = "WHERE (graph_templates_gprint.name LIKE '%%" . html_get_page_variable("filter") . "%%')";
	}else{
		$sql_where = "";
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$total_rows = db_fetch_cell("SELECT
		COUNT(id)
		FROM graph_templates_gprint
		$sql_where");

	return db_fetch_assoc("SELECT
		id,
		name
		FROM graph_templates_gprint
		$sql_where
		ORDER BY " . html_get_page_variable('sort_column') . " " . html_get_page_variable('sort_direction') .
		" LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp);
}

function gprint_presets($refresh = true) {
	global $gprint_actions;

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
			"name" => __("Name"),
			"filter" => true,
			"link" => true,
			"order" => "ASC"
		),
		"id" => array(
			"name" => __("ID"),
			"align" => "right",
			"order" => "ASC"
		)
	);

	/* initialize page behavior */
	$table->href           = "gprint_presets.php";
	$table->session_prefix = "sess_gprint";
	$table->filter_func    = "gprint_presets_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = $gprint_actions;
	$table->table_id       = "gprint_presets";

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_gprint_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}
