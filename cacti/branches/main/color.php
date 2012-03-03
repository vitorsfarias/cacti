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

define("COLOR_COLUMNS", 5);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch (get_request_var_request("action")) {
	case 'save':
	case 'import':
		form_save();

		break;
	case 'remove_confirm':
		remove_confirm();

		break;
	case 'remove':
		color_remove();

		break;
	case 'edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		color_edit();
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
	case 'ajax_edit':
		color_edit();

		break;
	case 'ajax_view':
		color();

		break;
	default:
		if (isset($_REQUEST["export_x"])) {
			export_colors();
		}elseif (isset($_REQUEST["import_x"])) {
			include_once(CACTI_BASE_PATH . "/include/top_header.php");
			import_colors();
			include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		}else{
			include_once(CACTI_BASE_PATH . "/include/top_header.php");
			color();
			include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		}
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if (isset($_POST["save_component_color"])) {
		$save["id"] = $_POST["id"];
		$save["hex"] = form_input_validate($_POST["hex"], "hex", "^[a-fA-F0-9]+$", false, 3);

		if (!is_error_message()) {
			$color_id = sql_save($save, "colors");

			if ($color_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		if (is_error_message()) {
			header("Location: color.php?action=edit&id=" . (empty($color_id) ? $_POST["id"] : $color_id));
		}else{
			header("Location: color.php");
		}
	}

	if (isset($_POST["save_component_import"])) {
		if (($_FILES["import_file"]["tmp_name"] != "none") && ($_FILES["import_file"]["tmp_name"] != "")) {
			/* file upload */
			$csv_data = file($_FILES["import_file"]["tmp_name"]);

			/* obtain debug information if it's set */
			$debug_data = import_processor($csv_data);
			if(sizeof($debug_data) > 0) {
				$_SESSION["import_debug_info"] = $debug_data;
			}
		}else{
			header("Location: color.php?import_x");
		}

		header("Location: color.php?import_x");
	}

	exit;
}

/* -----------------------
    Color Functions
   ----------------------- */

function import_processor($import_data) {
	/* clean up method string */
	if (isset($_POST["method"])) {
		$method = $_POST["method"];
	}else{
		$method = "Unknown";
	}

	/* set some statistical counters */
	$new_cnt   = 0;
	$exist_cnt = 0;
	$move_cnt  = 0;
	$error_cnt = 0;
	$message   = array();

	$i       = 0;
	$id_col  = "";
	$hex_col = "";
	if (sizeof($import_data)) {
		foreach($import_data as $record) {
			$parts = explode(",", str_replace("\"", "", trim($record)));
			if ($i == 0) {
				if ((substr_count(strtolower($record), "id")) &&
					(substr_count(strtolower($record), "hex")) &&
					(sizeof($parts == 2))) {
					if (trim(strtolower($parts[0])) == "id") {
						$id_col = 0;
					}elseif (trim(strtolower($parts[1])) == "id") {
						$id_col = 1;
					}else{
						array_push($message, "ERROR: CSV File has an invalid header 1");
						break;
					}

					if (trim(strtolower($parts[0])) == "hex") {
						$hex_col = 0;
					}elseif (trim(strtolower($parts[1])) == "hex") {
						$hex_col = 1;
					}else{
						array_push($message, "ERROR: CSV File has an invalid header 2");
						break;
					}
				}else{
					array_push($message, "ERROR: CSV File has an invalid header 3");
					break;
				}
			}else{
				if (strlen($parts[$hex_col]) != 6) {
					array_push($message, "ERROR: ID:'" . $parts[$id_col] . "', Hex:'" . $parts[$hex_col] . "', Hex Invalid Value");
					$error_cnt++;
				}elseif ($method == "merge") {
					/* check if this hex is already imported */
					$existing_hex_id = db_fetch_cell("SELECT id FROM colors WHERE hex='" . $parts[$hex_col] . "'");

					if ($existing_hex_id != "") {
						/* skip, we already have this hex imported */
						array_push($message, "NOTE: ID:'" . $parts[$id_col] . "', Hex:'" . $parts[$hex_col] . "', Already Exists");
						$exist_cnt++;
					}else{
						/* check if the ID already exists */
						$existing_color_id = db_fetch_cell("SELECT id FROM colors WHERE id=" . $parts[$id_col]);

						if ($existing_color_id != "") {
							/* insert the record with a new color id */
							db_execute("INSERT INTO colors (hex) VALUES ('" . $parts[$hex_col] . "')");
							array_push($message, "NOTE: ID:'" . $parts[$id_col] . "', Hex:'" . $parts[$hex_col] . "', Added as New");
						}else{
							/* insert using the given color id */
							db_execute("INSERT INTO colors (id, hex) VALUES (" . $parts[$id_col] . ", '" . $parts[$hex_col] . "')");
							array_push($message, "NOTE: ID:'" . $parts[$id_col] . "', Hex:'" . $parts[$hex_col] . "', Added with Given Id");
						}
						$new_cnt++;
					}
				}else{
					/* check if this hex is already imported */
					$existing_hex_id   = db_fetch_cell("SELECT id FROM colors WHERE hex='" . $parts[$hex_col] . "'");
					$existing_color_id = db_fetch_cell("SELECT id FROM colors WHERE id='" . $parts[$id_col] . "'");

					if ($existing_hex_id == "" && $existing_color_id == "") {
						/* insert using the given color id */
						db_execute("INSERT INTO color (id, hex) VALUES (" . $parts[$id_col] . ", '" . $parts[$hex_col] . "')");
						array_push($message, "NOTE: ID:'" . $parts[$id_col] . "', Hex:'" . $parts[$hex_col] . "', Added as New");
						$new_cnt++;
					}elseif ($existing_hex_id == "" && $existing_color_id != "") {
						/* ----------- STEPS ------------ */
						/* move existing color_id color to new id */
						/* insert new record */
						/* update templates */

						/* save the old information */
						$old_hex = db_fetch_cell("SELECT hex FROM colors WHERE id=" . $existing_color_id);
						$old_id  = $existing_color_id;

						array_push($message, "NOTE: ID:'" . $old_id . "', Hex:'" . $old_hex . "', Moved to New Id");
						array_push($message, "NOTE: ID:'" . $parts[$id_col] . "', Hex:'" . $parts[$hex_col] . "', Added, Moving Old Color");

						$move_cnt++;
						$new_cnt++;

						/* insert the new record */
						db_execute("REPLACE INTO colors SET id=" . $parts[$id_col] . ", hex='" . $parts[$hex_col] . "'");

						/* add the old record to a new color id */
						db_execute("INSERT INTO colors (hex) VALUES ('" . $old_hex . "')");

						/* get the new id */
						$new_id = db_fetch_cell("SELECT id FROM colors WHERE hex='" . $old_hex . "'");

						/* update existing graph templates */
						db_execute("UPDATE graph_templates_items SET color_id=$new_id WHERE color_id=$old_id");
					}elseif ($existing_hex_id != "" && $existing_color_id == "") {
						/* ----------- STEPS ------------ */
						/* update hex_id color to a new id /
						/* insert new record */
						/* update templates */

						/* save the old information */
						$old_hex = db_fetch_cell("SELECT hex FROM colors WHERE id=" . $existing_hex_id);
						$old_id  = $existing_hex_id;

						array_push($message, "NOTE: ID:'" . $old_id . "', Hex:'" . $old_hex . "', Moved to New Id");
						array_push($message, "NOTE: ID:'" . $parts[$id_col] . "', Hex:'" . $parts[$hex_col] . "', Added, Moving Old Color");

						$move_cnt++;
						$new_cnt++;

						/* insert the new record */
						db_execute("REPLACE INTO colors SET id=" . $parts[$id_col] . ", hex='" . $parts[$hex_col] . "'");

						/* add the old record to a new color id */
						db_execute("INSERT INTO colors (hex) VALUES ('" . $old_hex . "')");

						/* get the new id */
						$new_id = db_fetch_cell("SELECT id FROM colors WHERE hex='" . $old_hex . "'");

						/* update existing graph templates */
						db_execute("UPDATE graph_templates_items SET color_id=$new_id WHERE color_id=$old_id");
					}elseif ($existing_hex_id != "" && $existing_color_id != "") {
						if ($existing_hex_id == $existing_color_id) {
							/* do nothing, no changes required */
							$exist_cnt++;
						}else{
							/* save the old information */
							/* insert new record */
							/* move existing hex_id color to new id, update template */
							/* move existing color_id color to new id, update template */

							$old_color_hex = db_fetch_cell("SELECT hex FROM colors WHERE id=" . $existing_color_id);
							$old_color_id  = $existing_color_id;
							$old_hex_hex   = db_fetch_cell("SELECT hex FROM colors WHERE id=" . $existing_hex_id);
							$old_hex_id    = $existing_hex_id;

							array_push($message, "NOTE: ID:'" . $old_color_id . "', Hex:'" . $old_color_hex . "', Moved to New Id");
							array_push($message, "NOTE: ID:'" . $old_hex_id . "', Hex:'" . $old_hex_hex . "', Moved to New Id");
							array_push($message, "NOTE: ID:'" . $parts[$id_col] . "', Hex:'" . $parts[$hex_col] . "', Added, Moving 2 Old Colors");

							$move_cnt += 2;
							$new_cnt  += 2;

							/* insert the new record */
							db_execute("REPLACE INTO colors SET id=" . $parts[$id_col] . ", hex='" . $parts[$hex_col] . "'");

							/* add the old record to a new color id */
							db_execute("INSERT INTO colors (hex) VALUES ('" . $old_color_hex . "')");

							/* get the new id */
							$new_id = db_fetch_cell("SELECT id FROM colors WHERE hex='" . $old_color_hex . "'");

							/* update existing graph templates */
							db_execute("UPDATE graph_templates_items SET color_id=$new_id WHERE color_id=$old_color_id");

							/* add the old record to a new color id */
							db_execute("INSERT INTO colors (hex) VALUES ('" . $old_hex_hex . "')");

							/* get the new id */
							$new_id = db_fetch_cell("SELECT id FROM colors WHERE hex='" . $old_hex_hex . "'");

							/* update existing graph templates */
							db_execute("UPDATE graph_templates_items SET color_id=$new_id WHERE color_id=$old_hex_id");
						}
					}
				}
			}

			$i++;
		}

		array_push($message, "<strong>Import Complete!  Method:'" . ucfirst($method) . "', Total:'" . (sizeof($import_data)-1) . "', Existing:'" . $exist_cnt . "', New:'" . $new_cnt . "', Moved:'" . $move_cnt . "', Errors:'" . $error_cnt . "'</strong>");
	}else{
		array_push($message, "<strong>No Records Found in CSV File</strong>");
	}

	$new_message = "";
	if (sizeof($message)) {
	foreach($message as $row) {
		$new_message .= (strlen($new_message) ? "<br>":"") . $row;
	}
	}
	$_SESSION["import_debug_info"] = (array)$message;
}

function import_colors() {
	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("method", "sess_color_method", "merge");

	?><form name="import" method="post" action="color.php" enctype="multipart/form-data"><?php

	if ((isset($_SESSION["import_debug_info"])) && (is_array($_SESSION["import_debug_info"]))) {
		html_start_box(__("Import Results"), "100", "3", "center", "", true);

		if (sizeof($_SESSION["import_debug_info"])) {
			foreach($_SESSION["import_debug_info"] as $import_result) {
				print "<tr><td class='textInfo'>" . $import_result . "</td></tr>";
			}
		}

		html_end_box(false);

		kill_session_var("import_debug_info");
	}

	html_start_box(__("Import Cacti Colors"), "100", "3", "center", "", true);

	form_alternate_row_color();?>
		<td width='50%'><font class='textEditTitle'>Cacti Color CSV File</font><br>
			Please specify the location of the CSV file containing your Cacti Color information.
		</td>
		<td align='left'>
			<input type='file' size='60' name='import_file'>
		</td>
	</tr><?php
	form_alternate_row_color();?>
		<td width='50%'><font class='textEditTitle'>Import Method</font><br>
			Should the import process either 'Merge', or 'Reorder' your existing data?
		</td>
		<td width="1">
			<select name="method">
				<option value="merge"<?php if (get_request_var_request("method") == "merge") {?> selected<?php }?>><?php print __("Merge (default)");?></option>
				<option value="reorder"<?php if (get_request_var_request("method") == "reorder") {?> selected<?php }?>><?php print __("Reorder");?></option>
			</select>
		</td><?php

	html_end_box(FALSE);

	html_start_box(__("File Format Notes"), "100", "3", "center", "", true, false);

	form_alternate_row_color();?>
		<td>The CSV file <strong>must</strong> contain a header row with the following column headings.
			<br><br>
			<strong>id</strong> - The Color ID known to Cacti<br>
			<strong>hex</strong> - The Hex value for the device.  For example 'FF0000' for Red.<br>
			<br>
			If you choose the <strong>Merge</strong> option, the import process will maintain your Color values.  All Hex values imported will keep their existing color ids.  New Hex values will take the Color id's associated with them unless they are already used by Cacti, in which case they will receive a new Color id.
			<br><br>
			If you choose the <strong>Reorder</strong> option, the import process will alter your Color ids for all Hex values imported.  It will also update your existing Graph Templates to maintain the current color relationship.
			<br>
		</td>
	</tr><?php

	form_hidden_box("save_component_import","1","");

	html_end_box();

	form_save_button_alt("", "import", "import");
}

function export_colors() {
	if (isset($_REQUEST["selectedColors"]) && strlen($_REQUEST["selectedColors"])) {
		$selected_items = explode(",", $_REQUEST["selectedColors"]);
		if (is_array($selected_items)) {
		foreach($selected_items as $color_hex) {
			/* clean up color_id string */
			$color_hex = sanitize_search_string($color_hex);

			$color_hexes[] = $color_hex;
		}
		}
	}else{
		$message = "<i>You need to select the colors that you want for export before you may export them</i>\n";
		$_SESSION['sess_message_color_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');
		raise_message('color_ref_int');

		header("Location: color.php");
		exit;
	}

	$colors = db_fetch_assoc("SELECT * FROM colors WHERE " . array_to_sql_or($color_hexes, "hex"));

	$xport_array = array();
	array_push($xport_array, '"id","hex"');

	if (sizeof($colors)) {
		foreach($colors as $color) {
			array_push($xport_array,'"' . $color['id'] . '","' . $color['hex'] . '"');
		}
	}

	header("Content-type: application/csv");
	header("Content-Disposition: attachment; filename=cacti_colors_xport.csv");

	if (sizeof($xport_array)) {
	foreach($xport_array as $xport_line) {
		print $xport_line . "\n";
	}
	}
}

function remove_confirm() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("id"));
	/* ==================================================== */

	print "<form id='delete' action='color.php' name='delete' method='post'>\n";

	html_start_box("", "100", "3", "center", "");

	$color = db_fetch_row("SELECT * FROM colors WHERE id=" . get_request_var_request("id"));

	?>
	<tr>
		<td class='topBoxAlt'>
			<p><?php print __("When you click 'Continue', the following Color item will be deleted.");?></p>
			<p>Color HEX: <b><?php print $color["hex"];?></b><br>Color: <b style='width:30px;background-color:#<?php print $color["hex"];?>;'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</b><br>
		</td>
	</tr>
	<tr>
		<td align='right'>
			<input id='cancel' type='button' value='<?php print __("Cancel");?>' onClick='$("#cdialog").dialog("close");' name='cancel'>
			<input id='continue' type='button' value='<?php print __("Continue");?>' name='continue' title='<?php print __("Remove CDEF Item");?>'>
		</td>
	</tr>
	</form>
	<?php

	html_end_box();

	?>
	</form>
	<script type='text/javascript'>
	$('#continue').click(function(data) {
		$.post('color.php', { action: "remove", id: <?php print get_request_var("id");?> }, function(data) {
			$('#cdialog').dialog('close');
			$.get('color.php?action=ajax_view', function(data) {
				$('#content').html(data);
			});
		});
        });
        </script>
	<?php
}

function color_remove() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_post("id"));
	/* ==================================================== */

	if (sizeof(db_fetch_assoc("SELECT * FROM graph_templates_item WHERE color_id=" . get_request_var_post("id") . " LIMIT 1"))) {
		$message = "<i>Color " . get_request_var_post("id") . " is in use and can not be removed</i>\n";

		$_SESSION['sess_message_color_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');

		raise_message('color_ref_int');
	}else{
		db_execute("DELETE FROM colors WHERE id=" . get_request_var_post("id"));
	}
}

function color_edit() {
	require_once(CACTI_BASE_PATH . "/lib/presets/preset_color_info.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$color = db_fetch_row("select * from colors where id=" . $_GET["id"]);
	}

	print "<form id='color_edit' name='color_edit' method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "'>\n";

	draw_edit_form(array(
		"config" => array(),
		"fields" => inject_form_variables(preset_color_form_list(), (isset($color) ? $color : array()))
		));

	include_once(CACTI_BASE_PATH . "/access/js/colorpicker.js");

	//form_ajax_save("Edit Color", "color_edit"); works only for jQuery UI Dialog == inplace edit, not for ADD
	form_save_button("color.php", "return");
}

function color() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("columns"));
	/* ==================================================== */

	/* clean up search string */
	if (isset($_REQUEST["filter"])) {
		$_REQUEST["filter"] = sanitize_search_string(get_request_var("filter"));
	}

	/* clean up red string */
	if (isset($_REQUEST["rorder"])) {
		$_REQUEST["rorder"] = sanitize_search_string(get_request_var("rorder"));
	}

	/* clean up green string */
	if (isset($_REQUEST["gorder"])) {
		$_REQUEST["gorder"] = sanitize_search_string(get_request_var("gorder"));
	}

	/* clean up blue string */
	if (isset($_REQUEST["border"])) {
		$_REQUEST["border"] = sanitize_search_string(get_request_var("border"));
	}

	/* remember these search fields in session vars so we don't have to keep passing them around */
	load_current_session_value("rorder", "sess_color_rorder", "a");
	load_current_session_value("border", "sess_color_border", "a");
	load_current_session_value("gorder", "sess_color_gorder", "a");
	load_current_session_value("filter", "sess_color_filter", "");
	load_current_session_value("columns", "sess_color_columns", COLOR_COLUMNS);

	?>
	<script type="text/javascript">
	<!--
	var selectedColors = new Array();
	var newselectedColors = new Array();

	function addObject1() {
		event.preventDefault();
		$.get("color.php?action=ajax_edit", function(data) {
			$('#cdialog').html(data);
			$('#cdialog').dialog({ title: "<?php print __("Add Color");?>", minHeight: 80, minWidth: 400 });
		});
	}

	function selectAll() {
		selectedColors = new Array();

		$('[id^="hex_"]').each(function(data) {
			id=$(this).attr('id').split('_');
			toggleSelect(id[1]);
		});
	}

	function deselectAll() {
		selectedColors = new Array();

		$('[id^="hex_"]').each(function(data) {
			id=$(this).attr('id').split('_');
			$("#hex_" + id[1]).css("background-color", "");
			$("#check_" + id[1]).css("background-color", "");
		});
	}

	function toggleSelect(colorHex) {
		var newselectedColors = new Array();
		var action = 'check';

		var j = 0;
		var i = 0;
		if (selectedColors.length > 0) {
			for (var j = 0; j < selectedColors.length; j++) {
				if (selectedColors[j] == colorHex) {
					action = 'uncheck';
				}else{
					newselectedColors[i] = selectedColors[j];
					i++
				}
			}
		}else{
			selectedColors[i] = colorHex;
		}

		if (action == 'check') {
			newselectedColors[i] = colorHex
			$("#hex_" + colorHex).css("background-color", "yellow");
			$("#check_" + colorHex).css("background-color", "yellow");
		}else{
			$("#hex_" + colorHex).css("background-color", "");
			$("#check_" + colorHex).css("background-color", "");
		}

		selectedColors = newselectedColors;
		document.getElementById("selectedColors").value = selectedColors;
	}

	function applyFilterChange(objForm) {
		strURL = '?rorder=' + objForm.rorder.value;
		strURL = strURL + '&gorder=' + objForm.gorder.value;
		strURL = strURL + '&border=' + objForm.border.value;
		strURL = strURL + '&filter=' + objForm.filter.value;
		strURL = strURL + '&columns=' + objForm.columns.value;
		strURL = strURL + '&selected=' + selectedColors;
		document.location = strURL;
	}
	-->
	</script>
	<?php

	html_start_box(__("Colors"), "100", "3", "center", "color.php?action=edit", true);
	?>
	<tr class='rowAlternate3'>
		<td>
			<form name="form_color" method="get" action="color.php">
			<table cellpadding="0" cellspacing="3">
				<tr>
					<td class="nw30">
						&nbsp;<?php print __("Red:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="rorder" onChange="applyFilterChange(document.form_color)">
							<option value="-1"<?php if (get_request_var_request("rorder") == "-1") {?> selected<?php }?>><?php print __("None");?></option>
							<option value="a"<?php if (get_request_var_request("rorder") == "a") {?> selected<?php }?>><?php print __("Ascending");?></option>
							<option value="d"<?php if (get_request_var_request("rorder") == "d") {?> selected<?php }?>><?php print __("Descending");?></option>
						</select>
					</td>
					<td class="nw30">
						&nbsp;<?php print __("Green:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="gorder" onChange="applyFilterChange(document.form_color)">
							<option value="-1"<?php if (get_request_var_request("gorder") == "-1") {?> selected<?php }?>><?php print __("None");?></option>
							<option value="a"<?php if (get_request_var_request("gorder") == "a") {?> selected<?php }?>><?php print __("Ascending");?></option>
							<option value="d"<?php if (get_request_var_request("gorder") == "d") {?> selected<?php }?>><?php print __("Descending");?></option>
						</select>
					</td>
					<td class="nw30">
						&nbsp;<?php print __("Blue:");?>&nbsp;
					</td>
					<td width="1">
						<select name="border" onChange="applyFilterChange(document.form_color)">
							<option value="-1"<?php if (get_request_var_request("border") == "-1") {?> selected<?php }?>><?php print __("None");?></option>
							<option value="a"<?php if (get_request_var_request("border") == "a") {?> selected<?php }?>><?php print __("Ascending");?></option>
							<option value="d"<?php if (get_request_var_request("border") == "d") {?> selected<?php }?>><?php print __("Descending");?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="nw50">
						&nbsp;<?php print __("Search:");?>&nbsp;
					</td>
					<td class="w1">
						<input type="text" name="filter" size="20" value="<?php print $_REQUEST["filter"];?>">
					</td>
					<td class="nw30">
						&nbsp;<?php print __("Columns:");?>&nbsp;
					</td>
					<td width="1">
						<select name="columns" onChange="applyFilterChange(document.form_color)">
							<option value="-1"<?php if (get_request_var_request("columns") == "-1") {?> selected<?php }?>><?php print __("Default");?></option>
							<option value="4"<?php if (get_request_var_request("columns") == "4") {?> selected<?php }?>>4 Columns</option>
							<option value="5"<?php if (get_request_var_request("columns") == "5") {?> selected<?php }?>>5 Columns</option>
							<option value="6"<?php if (get_request_var_request("columns") == "6") {?> selected<?php }?>>6 Columns</option>
							<option value="7"<?php if (get_request_var_request("columns") == "7") {?> selected<?php }?>>7 Columns</option>
							<option value="8"<?php if (get_request_var_request("columns") == "8") {?> selected<?php }?>>8 Columns</option>
							<option value="9"<?php if (get_request_var_request("columns") == "9") {?> selected<?php }?>>9 Columns</option>
							<option value="10"<?php if (get_request_var_request("columns") == "10") {?> selected<?php }?>>10 Columns</option>
						</select>
					</td>
					<td class="w1">
						&nbsp;<input type="submit" Value="<?php print __("Go");?>" name="go" align="middle">
					</td>
					<td class="w1">
						<input type="submit" value="<?php print __("Import");?>" name="import_x" align="middle">
						<input type="submit" value="<?php print __("Export");?>" name="export_x" align="middle">
						<input type="button" value="<?php print __("Select All");?>" onClick="selectAll();" align="middle">
						<input type="button" value="<?php print __("Deselect All");?>" onClick="deselectAll();" align="middle">
						<input type="hidden" id="selectedColors" name="selectedColors">
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php
	html_end_box(false);

	html_start_box("&nbsp;", "100", "3", "center", "");

	print "<tr class='rowSubHeader'>";
	$i = 0;

	if ($_REQUEST['columns'] == -1) {
		$columns = COLOR_COLUMNS;
	}else{
		$columns = $_REQUEST["columns"];
	}

	while ($i < $columns) {
		print "<th id='Hex$i' class='textSubHeaderDark'>" . __("Hex") . "</th>"
		. "<th id='Class$i' class='textSubHeaderDark'>" . __("Color") . "</th>"
		. "<th id='spacer$i' class='textSubHeaderDark'>&nbsp;</th>";
		$i++;
	}

	print "</tr>";

	$order_by = "";
	if (get_request_var_request("rorder") == 'a') {
		$order_by = "ORDER BY substring(hex,1,2) ASC";
	}elseif (get_request_var_request("rorder") == 'd') {
		$order_by = "ORDER BY substring(hex,1,2) DESC";
	}

	if (get_request_var_request("gorder") == 'a') {
		$order_by .= (strlen($order_by) ? ",":"ORDER BY") . " substring(hex,3,2) ASC";
	}elseif (get_request_var_request("gorder") == 'd') {
		$order_by .= (strlen($order_by) ? ",":"ORDER BY") . " substring(hex,3,2) DESC";
	}

	if (get_request_var_request("border") == 'a') {
		$order_by .= (strlen($order_by) ? ",":"ORDER BY") . " substring(hex,5,2) ASC";
	}elseif (get_request_var_request("border") == 'd') {
		$order_by .= (strlen($order_by) ? ",":"ORDER BY") . " substring(hex,5,2) DESC";
	}

	if ($_REQUEST["filter"] != "") {
		$sql_where = "WHERE hex LIKE '%%" . $_REQUEST["filter"] . "%%'";
	}else{
		$sql_where = "";
	}

	$color_list = db_fetch_assoc("SELECT * FROM colors $sql_where $order_by");

	if (sizeof($color_list) > 0) {
		$j=0; ## even/odd counter
		foreach ($color_list as $color) {
			$j++;
			if ($j % $columns == 1) {
				form_alternate_row_color();
				?>
					<td id="<?php print 'hex_' . $color['hex'];?>" width='1'>
						<a id='anchor_<?php print $color['id'];?>' href='#' class='linkEditMain'><?php print $color["hex"];?></a>
					</td>
					<td id="<?php print 'color_' . $color['hex'];?>" bgcolor="#<?php print $color['hex'];?>" width="10%">&nbsp;</td>
					<td id="<?php print 'check_' . $color['hex'];?>" align="center">
						<img id="<?php print $color["id"];?>" class="delete buttonSmall" src="images/delete_icon.gif" alt="<?php print __("Delete");?>" align='middle'></a>
					</td>
				<?php
				$j=1;
			}elseif ($j != $columns) {
				?>
					<td id="<?php print 'hex_' . $color['hex'];?>" width='1'>
						<a id='anchor_<?php print $color['id'];?>' href='#' class='linkEditMain'><?php print $color["hex"];?></a>
					</td>
					<td id="<?php print 'color_' . $color['hex'];?>" bgcolor="#<?php print $color['hex'];?>" width="10%">&nbsp;</td>
					<td id="<?php print 'check_' . $color['hex'];?>" align="center">
						<img id="<?php print $color["id"];?>" class="delete buttonSmall" src="images/delete_icon.gif" alt="<?php print __("Delete");?>" align='middle'></a>
					</td>
				<?php
				$j=$j++;
			} else { 
				?>
					<td id="<?php print 'hex_' . $color['hex'];?>" width='1'>
						<a id='anchor_<?php print $color['id'];?>' href='#' class='linkEditMain'><?php print $color["hex"];?></a>
					</td>
					<td id="<?php print 'color_' . $color['hex'];?>" bgcolor="#<?php print $color['hex'];?>" width="10%">&nbsp;</td>
					<td id="<?php print 'check_' . $color['hex'];?>" align="center">
						<img id="<?php print $color["id"];?>" class="delete buttonSmall" src="images/delete_icon.gif" alt="<?php print __("Delete");?>" align='middle'></a>
					</td>
				<?php
				form_end_row();
			}
		}

		/* check for completion of odd number second column */
		if ($j == 1) {
			print "<td colspan=" . ($columns * 3) . "></td>";
			form_end_row();
		}
	}
	html_end_box();

	?>
	<script type='text/javascript'>
	$('[id^="color_"]').click(function() {
		id=$(this).attr('id').split('_');
		toggleSelect(id[1]);
	});
	$('[id^="anchor_"]').click(function() {
		id=$(this).attr('id').split('_');
		$.get("color.php?action=ajax_edit&id="+id[1], function(data) {
			$('#cdialog').html(data);
			$('#cdialog').dialog({ title: "<?php print __("Edit Color");?>", minHeight: 80, minWidth: 400 });
		});
	});

	$('.delete').click(function (data) {
		id = $(this).attr('id');
		request = "color.php?action=remove_confirm&id="+id;
		$.get(request, function(data) {
			$('#cdialog').html(data);
			$('#cdialog').dialog({ title: "<?php print __("Delete Color");?>", minHeight: 80, minWidth: 500 });
		});
	}).css("cursor", "pointer");
        </script>
	<?php
}

