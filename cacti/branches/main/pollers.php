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
include_once(CACTI_BASE_PATH . "/lib/utility.php");

define("MAX_DISPLAY_PAGES", 21);

$poller_actions = array(
	ACTION_NONE => __("None"),
	"1" => __("Delete"),
	"2" => __("Duplicate")
	);

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
		poller_edit();
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
	case 'ajax_view':
		poller();

		break;
	default:
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		poller();
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	/* save the poller */
	if (isset($_POST["save_component_poller"])) {
		$save["id"]          = $_POST["id"];

		$save["disabled"]    = form_input_validate((isset($_POST["disabled"]) ? get_request_var_post("disabled"):""), "disabled", "", true, 3);
		$save["description"] = form_input_validate(get_request_var_post("description"), "description", "", false, 3);
		$save["hostname"]    = form_input_validate(get_request_var_post("hostname"), "hostname", "", true, 3);

		if (!is_error_message()) {
			$poller_id = sql_save($save, "poller");

			if ($poller_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		if (is_error_message()) {
			header("Location: pollers.php?action=edit&id=" . (empty($poller_id) ? $_POST["id"] : $poller_id));
		}else{
			header("Location: pollers.php");
		}
		exit;
	}
}

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $poller_actions;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === "1") { /* delete */
			/* do a referential integrity check */
			if (sizeof($selected_items)) {
			foreach($selected_items as $poller_id) {
				/* ================= input validation ================= */
				input_validate_input_number($poller_id);
				/* ==================================================== */

				if (sizeof(db_fetch_assoc("SELECT * FROM device WHERE poller_id=$poller_id LIMIT 1")) || $poller_id == 1) {
					$bad_ids[] = $poller_id;
				}else{
					$poller_ids[] = $poller_id;
				}
			}
			}

			if (isset($bad_ids)) {
				$message = "";
				foreach($bad_ids as $poller_id) {
					$message .= (strlen($message) ? "<br>":"") . "<i>" . sprintf(__("Poller '%s' is in use or is the system poller and can not be removed"), $poller_id) . "</i>\n";
				}

				$_SESSION['sess_message_poller_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');

				raise_message('poller_ref_int');
			}

			if (isset($poller_ids)) {
				db_execute("delete from poller where " . array_to_sql_or($poller_ids, "id"));
				db_execute("update poller_item set poller_id=0 where " . array_to_sql_or($poller_ids, "poller_id"));
				db_execute("update device set poller_id=0 where " . array_to_sql_or($poller_ids, "poller_id"));
			}
		}elseif (get_request_var_post("drp_action") === "2") { /* disable */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				db_execute("update poller set disabled='on' where " . array_to_sql_or($selected_items, "id"));
			}
		}elseif (get_request_var_post("drp_action") === "3") { /* enable */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				db_execute("update poller set disabled='' where " . array_to_sql_or($selected_items, "id"));
			}
		}

		exit;
	}

	/* setup some variables */
	$poller_list = ""; $poller_array = array();

	/* loop through each of the pollers selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$poller_list .= "<li>" . db_fetch_cell("select description from poller where id=" . $matches[1]) . "</li>";
			$poller_array[] = $matches[1];
		}
	}

	print "<form id='pactions' name='pactions' action='pollers.php' method='post'>\n";

	html_start_box("", "100", "3", "center", "");

	if (sizeof($poller_array)) {
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
						<p>". __("When you click 'Continue', the following Poller(s) will be deleted.  All devices currently attached this these Poller(s) will be reassigned to the default poller.") . "</p>
						<div class='action_list'><ul>$poller_list</ul></div>
					</td>
				</tr>\n";

			$title = __("Delete Poller(s)");
		}elseif (get_request_var_post("drp_action") === "2") { /* disable */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Poller(s) will be disabled.  All Devices currently attached to these Poller(s) will no longer have their Graphs updated.") . "</p>
						<div class='action_list'><ul>$poller_list</ul></div>
					</td>
				</tr>\n";

			$title = __("Disable Poller(s)");
		}elseif (get_request_var_post("drp_action") === "3") { /* enable */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Poller(s) will be enabled.  All Devices currently attached to these Poller(s) will resume updating their Graphs.") . "</p>
						<div class='action_list'><ul>$poller_list</ul></div>
					</td>
				</tr>\n";

			$title = __("Enable Poller(s)");
		}
	} else {
		print "	<tr>
				<td class='textArea'>
					<p>" . __("You must first select a Poller.  Please select 'Return' to return to the previous menu.") . "</p>
				</td>
			</tr>\n";

		$title = __("Selection Error");
	}

	if (!sizeof($poller_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button($title);
	}else{
		form_continue(serialize($poller_array), get_request_var_post("drp_action"), $title, "pactions");
	}

	html_end_box();
}

/* ---------------------
    Template Functions
   --------------------- */

function poller_edit() {
	require_once(CACTI_BASE_PATH . "/lib/poller.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	/* remember if there's something we want to show to the user */
	$debug_log = debug_log_return("poller");

	if (!empty($debug_log)) {
		debug_log_clear("poller");
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

	if (!empty($_GET["id"])) {
		$poller = db_fetch_row("select * from poller where id=" . $_GET["id"]);
		$header_label = __("[edit: ") . $poller["description"] . "]";
	}else{
		$header_label = __("[new]");
		$_GET["id"] = 0;
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='poller_edit'>\n";

	html_start_box(__("Pollers") . " $header_label", "100", 0, "center", "");

	draw_edit_form(array(
		"config" => array("form_name" => "chk", "no_form_tag" => true),
		"fields" => inject_form_variables(poller_form_list(), (isset($poller) ? $poller : array()))
		));

	html_end_box();

	form_save_button_alt();
}

function pollers_filter() {
	global $item_rows;

	html_start_box(__("Pollers"), "100", "3", "center", "pollers.php?action=edit", true);
	?>
	<tr class='rowAlternate3'>
		<td>
			<form name="form_pollers" action="pollers.php">
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
						<select name="rows" onChange="applyFilterChange(document.form_pollers)">
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

function get_poller_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	if (html_get_page_variable("filter") != "") {
		$sql_where = "WHERE (p.description LIKE '%%" . $_REQUEST["filter"] . "%%')";
	}else{
		$sql_where = "";
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$total_rows = db_fetch_cell("SELECT
		COUNT(*)
		FROM poller
		$sql_where");

	return db_fetch_assoc("SELECT p.*,
		count(h.id) AS total_devices
		FROM poller AS p
		LEFT JOIN device AS h ON h.poller_id=p.id
		$sql_where
		GROUP BY p.id
		ORDER BY p." . html_get_page_variable('sort_column') . " " . html_get_page_variable('sort_direction') .
		" LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp);
}

function poller($refresh = true) {
	global $poller_actions;

	$table = New html_table;

	$table->page_variables = array(
		"page" => array("type" => "numeric", "method" => "request", "default" => "1"),
		"rows" => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"filter" => array("type" => "string", "method" => "request", "default" => ""),
		"sort_column" => array("type" => "string", "method" => "request", "default" => "description"),
		"sort_direction" => array("type" => "string", "method" => "request", "default" => "ASC"));

	$table->table_format = array(
		"description" => array(
			"name" => __("Description"),
			"link" => true,
			"filter" => true,
			"order" => "ASC"
		),
		"nosort1" => array(
			"name" => __("Status"),
			"function" => "get_colored_poller_status",
			"align" => "center",
			"params" => array("disabled", "last_update"),
			"sort" => false
		),
		"hostname" => array(
			"name" => __("Hostname"),
			"filter" => true,
			"order" => "ASC"
		),
		"total_devices" => array(
			"name" => __("Devices"),
			"align" => "right",
			"order" => "DESC"
		),
		"snmp" => array(
			"name" => __("SNMP Items"),
			"align" => "right",
			"order" => "DESC"
		),
		"script" => array(
			"name" => __("Script Items"),
			"align" => "right",
			"order" => "DESC"
		),
		"server" => array(
			"name" => __("Server Items"),
			"align" => "right",
			"order" => "DESC"
		),
		"total_time" => array(
			"name" => __("Last Runtime"),
			"format" => "round,2",
			"align" => "right",
			"order" => "DESC"
		),
		"last_update" => array(
			"name" => __("Last Updated"),
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
	$table->href           = "pollers.php";
	$table->session_prefix = "sess_pollers";
	$table->filter_func    = "pollers_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = $poller_actions;

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_poller_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}

function display_poller_poller_items($id) {
	return db_fetch_cell("SELECT count(*) FROM poller_item WHERE poller_id=" . $id);
}
