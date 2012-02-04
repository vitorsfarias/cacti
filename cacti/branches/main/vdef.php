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
include_once(CACTI_BASE_PATH . "/lib/vdef.php");

define("MAX_DISPLAY_PAGES", 21);

$vdef_actions = array(
	"1" => __("Delete"),
	"2" => __("Duplicate")
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
	case 'item_remove_confirm':
		item_remove_confirm();

		break;
	case 'item_remove':
		item_remove();

		break;
	case 'item_edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		item_edit();
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
	case 'ajax_edit':
		vdef_edit();

		break;
	case 'edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		vdef_edit();
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
	case 'ajax_dnd':
		vdef_item_dnd();

		break;
	case 'ajax_view':
		vdef();

		break;
	default:
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		vdef();
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
}

/* --------------------------
    Global Form Functions
   -------------------------- */

function draw_vdef_preview($vdef_id) {
	print "<pre>vdef=" . get_vdef($vdef_id, true) . "</pre>";
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if (isset($_POST["save_component_vdef"])) {
		$save["id"] = $_POST["id"];
		$save["hash"] = get_hash_vdef($_POST["id"]);
		$save["name"] = form_input_validate($_POST["name"], "name", "", false, 3);

		if (!is_error_message()) {
			$vdef_id = sql_save($save, "vdef");

			if ($vdef_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		header("Location: vdef.php?action=edit&id=" . (empty($vdef_id) ? $_POST["id"] : $vdef_id));
	}elseif (isset($_POST["save_component_item"])) {
		$sequence = get_sequence($_POST["id"], "sequence", "vdef_items", "vdef_id=" . $_POST["vdef_id"]);

		$save["id"] 		= $_POST["id"];
		$save["hash"] 		= get_hash_vdef($_POST["id"], "vdef_item");
		$save["vdef_id"] 	= $_POST["vdef_id"];
		$save["sequence"] 	= $sequence;
		$save["type"] 		= $_POST["type"];
		$save["value"] 		= $_POST["value"];

		if (!is_error_message()) {
			$vdef_item_id = sql_save($save, "vdef_items");

			if ($vdef_item_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		if (is_error_message()) {
			header("Location: vdef.php?action=item_edit&vdef_id=" . $_POST["vdef_id"] . "&id=" . (empty($vdef_item_id) ? $_POST["id"] : $vdef_item_id));
		}else{
			header("Location: vdef.php?action=edit&id=" . $_POST["vdef_id"]);
		}
	}
}

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $vdef_actions;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === "1") { /* delete */
			/* do a referential integrity check */
			if (sizeof($selected_items)) {
			foreach($selected_items as $vdef_id) {
				/* ================= input validation ================= */
				input_validate_input_number($vdef_id);
				/* ==================================================== */

				if (sizeof(db_fetch_assoc("SELECT * FROM graph_templates_item WHERE vdef_id=$vdef_id LIMIT 1"))) {
					$bad_ids[] = $vdef_id;
				}else{
					$vdef_ids[] = $vdef_id;
				}
			}
			}

			if (isset($bad_ids)) {
				$message = "";
				foreach($bad_ids as $vdef_id) {
					$message .= (strlen($message) ? "<br>":"") . "<i>VDEF " . $vdef_id . " is in use and can not be removed</i>\n";
				}

				$_SESSION['sess_message_vdef_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');

				raise_message('vdef_ref_int');
			}

			if (isset($vdef_ids)) {
				db_execute("delete from vdef where " . array_to_sql_or($vdef_ids, "id"));
				db_execute("delete from vdef_items where " . array_to_sql_or($vdef_ids, "vdef_id"));
			}
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				duplicate_vdef($selected_items[$i], get_request_var_post("title_format"));
			}
		}

		exit;
	}

	/* setup some variables */
	$vdef_list = "";

	/* loop through each of the graphs selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$vdef_list .= "<li>" . db_fetch_cell("select name from vdef where id=" . $matches[1]) . "</li>";
			$vdef_array[] = $matches[1];
		}
	}

	print "<form id='vdef_actions' action='vdef.php' method='post' name='vdef_actions'>\n";

	html_start_box("", "100", "3", "center", "");

	if (isset($vdef_array)) {
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
						<p>" . __("When you click 'Continue', the following VDEF(s) will be deleted.") . "</p>
						<p><ul>$vdef_list</ul></p>
					</td>
				</tr>\n";

			$title = __("Delete VDEF(s)");
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			print "	<tr>
					<td class='topBoxAlt'>
						<p>" . __("When you click 'Continue', the following VDEF(s) will be duplicated. You can optionally change the title format for the new VDEF(s).") . "</p>
						<p><ul>$vdef_list</ul></p>
						<p><strong>" . __("Title Format:") . "</strong><br>"; form_text_box("title_format", "<vdef_title> (1)", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n";

			$title = __("Duplicate VDEF(s)");
		}
	}else{
		print "<tr><td class='topBoxAlt'><span class='textError'>" . __("You must select at least one VDEF.") . "</span></td></tr>\n";
	}

	if (!isset($vdef_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button($title);
	}else{
		form_continue(serialize($vdef_array), get_request_var_post("drp_action"), $title, "vdef_actions");
	}

	html_end_box();
}

/* --------------------------
    VDEF Item Functions
   -------------------------- */

function item_remove_confirm() {
	require(CACTI_BASE_PATH . "/include/presets/preset_vdef_arrays.php");
	require_once(CACTI_BASE_PATH . "/lib/presets/preset_vdef_info.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("id"));
	input_validate_input_number(get_request_var_request("vdef_id"));
	/* ==================================================== */

	print "<form id='delete' action='vdef.php' name='delete' method='post'>\n";

	html_start_box("", "100", "3", "center", "");

	$vdef       = db_fetch_row("SELECT * FROM vdef WHERE id=" . get_request_var_request("id"));
	$vdef_item  = db_fetch_row("SELECT * FROM vdef_items WHERE id=" . get_request_var_request("vdef_id"));

	?>
	<tr>
		<td class='topBoxAlt'>
			<p><?php print __("When you click 'Continue', the following VDEF item will be deleted.");?></p>
			<p>VDEF Name: '<?php print $vdef["name"];?>'<br>
			<em><?php $vdef_item_type = $vdef_item["type"]; print $vdef_item_types[$vdef_item_type];?></em>: <strong><?php print get_vdef_item_name($vdef_item["id"]);?></strong></p>
		</td>
	</tr>
	<tr>
		<td align='right'>
			<input id='cancel' type='button' value='<?php print __("Cancel");?>' onClick='$("#cdialog").dialog("close");' name='cancel'>
			<input id='continue' type='button' value='<?php print __("Continue");?>' name='continue' title='<?php print __("Remove VDEF Item");?>'>
		</td>
	</tr>
	</form>
	<?php

	html_end_box();

	?>
	</form>
	<script type='text/javascript'>
	$('#continue').click(function(data) {
		$.post('vdef.php?action=item_remove', { vdef_id: <?php print get_request_var("vdef_id");?>, id: <?php print get_request_var("id");?> }, function(data) {
			$('#cdialog').dialog('close');
			$.get('vdef.php?action=ajax_edit&id=<?php print get_request_var("id");?>', function(data) {
				$('#content').html(data);
			});
		});
        });
        </script>
	<?php
}
		
function item_remove() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_post("id"));
	input_validate_input_number(get_request_var_post("vdef_id"));
	/* ==================================================== */

	db_execute("DELETE FROM vdef_items WHERE id=" . get_request_var_post("vdef_id"));
}

function item_edit() {
	global $custom_vdef_data_source_types;
	require(CACTI_BASE_PATH . "/include/presets/preset_vdef_arrays.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("vdef_id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$vdef = db_fetch_row("select * from vdef_items where id=" . get_request_var("id"));
		$current_type = $vdef["type"];
		$values[$current_type] = $vdef["value"];
	}
	print "<div>";
	draw_vdef_preview(get_request_var("vdef_id"));
	print "</div>";

	if (!empty($_GET["vdef_id"])) {
		$header_label = "[edit: " . db_fetch_cell("select name from vdef where id=" . get_request_var("vdef_id")) . "]";
	}else {
		$header_label = "[new]";
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='form_vdef'>\n";
	html_start_box(__("VDEF Items") . " $header_label", "100", 0, "center", "", true);

	if (isset($_GET["type_select"])) {
		$current_type = $_GET["type_select"];
	}elseif (isset($vdef["type"])) {
		$current_type = $vdef["type"];
	}else{
		$current_type = CVDEF_ITEM_TYPE_FUNCTION;
	}
	form_alternate_row_color("vdef_item_type"); ?>
		<td width="50%">
			<font class="textEditTitle"><?php print __("VDEF Item Type");?></font><br>
			<?php print __("Choose what type of VDEF item this is.");?>
		</td>
		<td>
			<select name="type_select" onChange="window.location=document.form_vdef.type_select.options[document.form_vdef.type_select.selectedIndex].value">
				<?php
				while (list($var, $val) = each($vdef_item_types)) {
					print "<option value='" . htmlspecialchars("vdef.php?action=item_edit" . (isset($_GET["id"]) ? "&id=" . get_request_var("id") : "") . "&vdef_id=" . $_GET["vdef_id"] . "&type_select=$var") . "'"; if ($var == $current_type) { print " selected"; } print ">$val</option>\n";
				}
				?>
			</select>
		</td>
	<?php
	form_end_row();
	form_alternate_row_color("vdef_item_value");
	?>
		<td width="50%">
			<font class="textEditTitle"><?php print __("VDEF Item Value");?></font><br>
			<?php print __("Enter a value for this VDEF item.");?>
		</td>
		<td>
			<?php
			switch ($current_type) {
			case '1':
				form_dropdown("value", $vdef_functions, "", "", (isset($vdef["value"]) ? $vdef["value"] : ""), "", "");
				break;
#			case '2':
#				form_dropdown("value", $vdef_operators, "", "", (isset($vdef["value"]) ? $vdef["value"] : ""), "", "");
#				break;
			case '4':
				form_dropdown("value", $custom_vdef_data_source_types, "", "", (isset($vdef["value"]) ? $vdef["value"] : ""), "", "");
				break;
#			case '5':
#				form_dropdown("value", db_fetch_assoc("select name,id from vdef order by name"), "name", "id", (isset($vdef["value"]) ? $vdef["value"] : ""), "", "");
#				break;
			case '6':
				form_text_box("value", (isset($vdef["value"]) ? $vdef["value"] : ""), "", "255", 30, "text", (isset($_GET["id"]) ? get_request_var("id") : "0"));
				break;
			}
			?>
		</td>
	<?php
	form_end_row();

	html_end_box();

	form_hidden_box("id", (isset($_GET["id"]) ? get_request_var("id") : "0"), "");
	form_hidden_box("type", $current_type, "");
	form_hidden_box("vdef_id", $_GET["vdef_id"], "");
	form_hidden_box("save_component_item", "1", "");

	form_save_button_alt("path!vdef.php|action!edit|id!" . get_request_var("vdef_id"));
}

/* ---------------------
    VDEF Functions
   --------------------- */

function vdef_item_dnd() {
	/* ================= Input validation ================= */
		input_validate_input_number(get_request_var("id"));
	/* ================= Input validation ================= */

	if(!isset($_REQUEST['vdef_item']) || !is_array($_REQUEST['vdef_item'])) exit;
	/* vdef table contains one row defined as "nodrag&nodrop" */
	unset($_REQUEST['vdef_item'][0]);

	/* delivered vdef ids has to be exactly the same like we have stored */
	$old_order = array();
	$new_order = $_REQUEST['vdef_item'];

	$sql = "SELECT id, sequence FROM vdef_items WHERE vdef_id = " . $_GET['id'];
	$vdef_items = db_fetch_assoc($sql);

	if(sizeof($vdef_items)>0) {
		foreach($vdef_items as $item) {
			$old_order[$item['sequence']] = $item['id'];
		}
	}else {
		exit;
	}
	if(sizeof(array_diff($new_order, $old_order))>0) exit;

	/* the set of sequence numbers has to be the same too */
	if(sizeof(array_diff_key($new_order, $old_order))>0) exit;
	/* ==================================================== */

	foreach($new_order as $sequence => $vdef_id) {
		$sql = "UPDATE vdef_items SET sequence = $sequence WHERE id = $vdef_id";
		db_execute($sql);
	}

	draw_vdef_preview(get_request_var("id"));
}

function vdef_edit() {
	require(CACTI_BASE_PATH . "/include/presets/preset_vdef_arrays.php");
	require_once(CACTI_BASE_PATH . "/lib/presets/preset_vdef_info.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$vdef = db_fetch_row("select * from vdef where id=" . get_request_var("id"));
		$header_label = __("[edit: ") . $vdef["name"] . "]";
	}else{
		$header_label = __("[new]");
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='vdef_edit'>\n";
	html_start_box(__("VDEF's") . " $header_label", "100", 0, "center", "");

	draw_edit_form(array(
		"config" => array("no_form_tag" => true),
		"fields" => inject_form_variables(preset_vdef_form_list(), (isset($vdef) ? $vdef : array()))
		));

	html_end_box();
	form_hidden_box("id", (isset($vdef["id"]) ? $vdef["id"] : "0"), "");
	form_hidden_box("save_component_vdef", "1", "");

	if (!empty($_GET["id"])) {
		echo "<table id='preview' class='left startBox1 wp100'>";
		draw_vdef_preview(get_request_var("id"));
		echo "</table><br>";

		html_start_box(__("VDEF Items"), "100", 0, "center", "vdef.php?action=item_edit&vdef_id=" . $vdef["id"], false, "vdef");
		$header_items = array(
			array("name" => __("Item")),
			array("name" => __("Item Value"))
		);

		print "<tr><td>";
		html_header($header_items, 2, false, 'vdef_item','left wp100');

		$vdef_items = db_fetch_assoc("select * from vdef_items where vdef_id=" . get_request_var("id") . " order by sequence");
		$i = 0;
		if (sizeof($vdef_items) > 0) {
			foreach ($vdef_items as $vdef_item) {
				form_alternate_row_color($vdef_item["id"], true);
					?>
					<td>
						<a class="linkEditMain" href="<?php print htmlspecialchars("vdef.php?action=item_edit&id=" . $vdef_item["id"] . "&vdef_id=" . $vdef["id"]);?>">Item #<?php print $i;?></a>
					</td>
					<td>
						<em><?php $vdef_item_type = $vdef_item["type"]; print $vdef_item_types[$vdef_item_type];?></em>: <strong><?php print get_vdef_item_name($vdef_item["id"]);?></strong>
					</td>
					<td align="right" style="text-align:right">
						<img id="<?php print $vdef["id"] . "_" . $vdef_item["id"];?>" class="delete buttonSmall" src="images/delete_icon.gif" alt="<?php print __("Delete");?>" title="<?php print __("Delete VDEF Item");?>" align="middle">
					</td>
			<?php
			form_end_row();
			$i++;
			}
		}
		print "</table></td></tr>";		/* end of html_header */
		html_end_box();
	}
	form_save_button("vdef.php", "return");

	?>
	<script type="text/javascript">
	$('#vdef_item').tableDnD({
		onDrop: function(table, row) {
			$.get('vdef.php?action=ajax_dnd&id=<?php isset($_GET["id"]) ? print get_request_var("id") : print 0;?>&'+$.tableDnD.serialize(), function(data) {
				if (data) {
					$('#preview').html(data);
				}
			});
		}
	});

	$('.delete').click(function (data) {
                id = $(this).attr('id').split("_");
		request = "vdef.php?action=item_remove_confirm&id="+id[0]+"&vdef_id="+id[1];
                $.get(request, function(data) {
                        $('#cdialog').html(data);
                        $('#cdialog').dialog({ title: "<?php print __("Delete VDEF Item");?>", minHeight: 80, minWidth: 500 });
                });
        }).css("cursor", "pointer");
	</script>
	<?php
}

function vdef_filter() {
	global $item_rows;

	html_start_box(__("VDEF's"), "100", "3", "center", "vdef.php?action=edit", true);
	?>
	<tr class='rowAlternate3'>
		<td>
			<form name="form_vdef" action="vdef.php">
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
						<select name="rows" onChange="applyFilterChange(document.form_vdef)">
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

function get_vdef_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	if (strlen(html_get_page_variable("filter"))) {
		$sql_where = "WHERE (vdef.name LIKE '%%" . html_get_page_variable("filter") . "%%')";
	}else{
		$sql_where = "";
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$total_rows = db_fetch_cell("SELECT
		COUNT(vdef.id)
		FROM vdef
		$sql_where");

	return db_fetch_assoc("SELECT
		vdef.id, vdef.name
		FROM vdef
		$sql_where
		ORDER BY " . html_get_page_variable('sort_column') . " " . html_get_page_variable('sort_direction') .
		" LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp);
}

function vdef($refresh = true) {
	global $vdef_actions;

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
			"name" => __("VDEF Title"),
			"link" => true,
			"filter" => true,
			"order" => "ASC"
		),
		"id" => array(
			"name" => __("ID"),
			"align" => "right",
			"order" => "ASC"
		)
	);

	/* initialize page behavior */
	$table->href           = "vdef.php";
	$table->session_prefix = "sess_vdef";
	$table->filter_func    = "vdef_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = $vdef_actions;

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_vdef_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}
