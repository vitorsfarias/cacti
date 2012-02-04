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

$rra_actions = array(
	ACTION_NONE => __("None"),
	"1" => __("Delete")
	);

define("MAX_DISPLAY_PAGES", 21);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch (get_request_var_request("action")) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		rra_edit();
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
	case 'ajax_view':
		rra();

		break;
	default:
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		rra();
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
}


/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $rra_actions, $messages;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === "1") { /* delete */
			/* do a referential integrity check */
			if (sizeof($selected_items)) {
			foreach($selected_items as $rra_id) {
				/* ================= input validation ================= */
				input_validate_input_number($rra_id);
				/* ==================================================== */

				if (sizeof(db_fetch_assoc("SELECT * FROM data_template_data_rra WHERE rra_id=$rra_id LIMIT 1"))) {
					$bad_ids[] = $rra_id;
				}else{
					$rra_ids[] = $rra_id;
				}
			}
			}

			if (isset($bad_ids)) {
				$message = "";
				foreach($bad_ids as $rra_id) {
					$message .= (strlen($message) ? "<br>":"") . "<i>RRA " . $rra_id . " is in use and can not be removed</i>\n";
				}

				$_SESSION['sess_message_rra_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');

				raise_message('rra_ref_int');
			}

			if (isset($rra_ids)) {
				db_execute("delete from rra where " . array_to_sql_or($rra_ids, "id"));
				db_execute("delete from rra_cf where " . array_to_sql_or($rra_ids, "rra_id"));
			}
		}

		exit;
	}

	/* setup some variables */
	$rra_list = "";

	/* loop through each of the graphs selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$rra_list .= "<li>" . db_fetch_cell("select name from rra where id=" . $matches[1]) . "</li>";
			$rra_array[] = $matches[1];
		}
	}

	print "<form id='ractions' name='ractions' action='rra.php' method='post'>\n";

	html_start_box("", "100", "3", "center", "");

	if (isset($rra_array)) {
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
						<p>" . __("When you click 'Continue', the following RRA(s) will be deleted.") . "</p>
						<div class='action_list'><ul>$rra_list</ul></div>
					</td>
				</tr>\n";

			$title = __("Delete RRA(s)");
		}
	}else{
		print "<tr><td class='topBoxAlt'><span class='textError'>" . __("You must select at least one RRA.") . "</span></td></tr>\n";

		$title = __("Selection Error");
	}

	if (!isset($rra_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button($title);
	}else{
		form_continue(serialize($rra_array), get_request_var_post("drp_action"), $title, "ractions");
	}

	html_end_box();
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if (isset($_POST["save_component_rra"])) {
		$save["id"] = $_POST["id"];
		$save["hash"] = get_hash_round_robin_archive($_POST["id"]);
		$save["name"] = form_input_validate($_POST["name"], "name", "", false, 3);
		$dummy = form_input_validate(count($_POST["consolidation_function_id"]), "consolidation_function_id", "^[0-9]*$", false, 3);
		$save["x_files_factor"] = form_input_validate($_POST["x_files_factor"], "x_files_factor", "^[01]?(\.[0-9]+)?$", false, 3);
		$save["steps"] = form_input_validate($_POST["steps"], "steps", "^[0-9]*$", false, 3);
		$save["rows"] = form_input_validate($_POST["rows"], "rows", "^[0-9]*$", false, 3);
		$save["timespan"] = form_input_validate($_POST["timespan"], "timespan", "^[0-9]*$", false, 3);

		if (!is_error_message()) {
			$rra_id = sql_save($save, "rra");

			if ($rra_id) {
				raise_message(1);

				db_execute("delete from rra_cf where rra_id=$rra_id");

				if (isset($_POST["consolidation_function_id"])) {
					for ($i=0; ($i < count($_POST["consolidation_function_id"])); $i++) {
						/* ================= input validation ================= */
						input_validate_input_number($_POST["consolidation_function_id"][$i]);
						/* ==================================================== */

						db_execute("insert into rra_cf (rra_id,consolidation_function_id)
							values ($rra_id," . $_POST["consolidation_function_id"][$i] . ")");
					}
				}else{
					raise_message(2);
				}
			}else{
				raise_message(2);
			}
		}

		header("Location: rra.php?action=edit&id=" . (empty($rra_id) ? $_POST["id"] : $rra_id));
		exit;
	}
}

/* -------------------
    RRA Functions
   ------------------- */

function rra_edit() {
	require_once(CACTI_BASE_PATH . "/lib/presets/preset_rra_info.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$rra = db_fetch_row("select * from rra where id=" . $_GET["id"]);
		$header_label = "[edit: " . $rra["name"] . "]";
	}else{
		$header_label = "[new]";
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='rra_edit'>\n";
	html_start_box(__("Round Robin Archives") . " $header_label", "100", 0, "center", "");

	draw_edit_form(array(
		"config" => array(),
		"fields" => inject_form_variables(preset_rra_form_list(), (isset($rra) ? $rra : array()))
		));

	html_end_box();

	form_save_button("rra.php", "return");
}

function rra_filter() {
	global $item_rows;
	html_start_box(__("Round Robin Archives"), "100", "3", "center", "rra.php?action=edit", true);
	?>
	<tr class='rowAlternate3'>
		<td>
			<form name='form_rra' action="<?php print basename($_SERVER['PHP_SELF']);?>" method="post">
			<table cellpadding="0" cellspacing="3">
				<tr>
					<td class="nw50">
						&nbsp;<?php print __("Search:");?>&nbsp;
					</td>
					<td class="w1">
						<input type="text" name="filter" size="40" value="<?php print html_get_page_variable("filter");?>">
					</td>
					<td class="nw50">
						&nbsp;<?php print __("Rows:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="rows" onChange="applyFilterChange(document.form_rra)">
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

function get_rra_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	$sql_where = "WHERE (rra.name LIKE '%%" . html_get_page_variable("filter") . "%%')";

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$total_rows = db_fetch_cell("SELECT
		COUNT(rra.id)
		FROM rra
		$sql_where");

	return db_fetch_assoc("SELECT *
		FROM rra
		$sql_where
		ORDER BY " . html_get_page_variable('sort_column') . " " . html_get_page_variable('sort_direction') .
		" LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp);
}

function rra($refresh = true) {
	global $rra_actions;

	$table = New html_table;

	$table->page_variables = array(
		"page" => array("type" => "numeric", "method" => "request", "default" => "1"),
		"rows" => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"filter" => array("type" => "string", "method" => "request", "default" => ""),
		"sort_column" => array("type" => "string", "method" => "request", "default" => "name"),
		"sort_direction" => array("type" => "string", "method" => "request", "default" => "ASC"));

	$table->table_format = array(
		"name" => array(
			"name" => __("Name"),
			"filter" => true,
			"link" => true,
			"order" => "ASC"
		),
		"x_files_factor" => array(
			"name" => __("X Files Factor"),
			"order" => "ASC",
			"align" => "right"
		),
		"steps" => array(
			"name" => __("Steps"),
			"order" => "ASC",
			"align" => "right"
		),
		"rows" => array(
			"name" => __("Rows"),
			"order" => "ASC",
			"align" => "right"
		),
		"timespan" => array(
			"name" => __("Timespan"),
			"order" => "ASC",
			"align" => "right"
		),
		"id" => array(
			"name" => __("ID"),
			"align" => "right",
			"order" => "ASC"
		)
	);

	/* initialize page behavior */
	$table->href           = "rra.php";
	$table->session_prefix = "sess_rra";
	$table->filter_func    = "rra_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = $rra_actions;

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_rra_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}
