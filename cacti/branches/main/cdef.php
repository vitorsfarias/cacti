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

include("./include/auth.php");
include_once(CACTI_BASE_PATH . "/lib/utility.php");
include_once(CACTI_BASE_PATH . "/lib/cdef.php");

define("MAX_DISPLAY_PAGES", 21);

$cdef_actions = array(
	ACTION_NONE => __("None"),
	"1" => __("Delete"),
	"2" => __("Duplicate")
	);

/* set default action */
if (!isset($_REQUEST["action"])) $_REQUEST["action"] = "";

switch (get_request_var_request("action")) {
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
		cdef_edit();

		break;
	case 'edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		cdef_edit();
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
	case 'ajax_dnd':
		cdef_dnd();

		break;
	case 'ajax_view':
		cdef();

		break;
	default:
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		cdef();
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
}

/* --------------------------
    Global Form Functions
   -------------------------- */

function draw_cdef_preview($cdef_id) {
	print "<tr><td><pre>cdef=" . get_cdef($cdef_id, true) . "</pre></td></tr>";
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if (isset($_POST["save_component_cdef"])) {
		$save["id"]   = $_POST["id"];
		$save["hash"] = get_hash_cdef($_POST["id"]);
		$save["name"] = form_input_validate($_POST["name"], "name", "", false, 3);

		if (!is_error_message()) {
			$cdef_id = sql_save($save, "cdef");

			if ($cdef_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		header("Location: cdef.php?action=edit&id=" . (empty($cdef_id) ? $_POST["id"] : $cdef_id));
		exit;
	}elseif (isset($_POST["save_component_item"])) {
		$sequence = get_sequence($_POST["id"], "sequence", "cdef_items", "cdef_id=" . $_POST["cdef_id"]);

		$save["id"]       = $_POST["id"];
		$save["hash"]     = get_hash_cdef($_POST["id"], "cdef_item");
		$save["cdef_id"]  = $_POST["cdef_id"];
		$save["sequence"] = $sequence;
		$save["type"]     = $_POST["type"];
		$save["value"]    = $_POST["value"];

		if (!is_error_message()) {
			$cdef_item_id = sql_save($save, "cdef_items");

			if ($cdef_item_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}

		if (is_error_message()) {
			header("Location: cdef.php?action=item_edit&cdef_id=" . $_POST["cdef_id"] . "&id=" . (empty($cdef_item_id) ? $_POST["id"] : $cdef_item_id));
		}else{
			header("Location: cdef.php?action=edit&id=" . $_POST["cdef_id"]);
		}
		exit;
	}
}

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $cdef_actions;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === "1") { /* delete */
			/* do a referential integrity check */
			if (sizeof($selected_items)) {
			foreach($selected_items as $cdef_id) {
				/* ================= input validation ================= */
				input_validate_input_number($cdef_id);
				/* ==================================================== */

				if (sizeof(db_fetch_assoc("SELECT * FROM graph_templates_item WHERE cdef_id=$cdef_id LIMIT 1"))) {
					$bad_ids[] = $cdef_id;
				}else{
					$cdef_ids[] = $cdef_id;
				}
			}
			}

			if (isset($bad_ids)) {
				$message = "";
				foreach($bad_ids as $cdef_id) {
					$message .= (strlen($message) ? "<br>":"") . "<i>CDEF " . $cdef_id . " is in use and can not be removed</i>\n";
				}

				$_SESSION['sess_message_cdef_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');

				raise_message('cdef_ref_int');
			}

			if (isset($cdef_ids)) {
				db_execute("delete from cdef where " . array_to_sql_or($cdef_ids, "id"));
				db_execute("delete from cdef_items where " . array_to_sql_or($cdef_ids, "cdef_id"));
			}
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				duplicate_cdef($selected_items[$i], get_request_var_post("title_format"));
			}
		}
		exit;
	}

	/* setup some variables */
	$cdef_list = "";

	/* loop through each of the graphs selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$cdef_list .= "<li>" . db_fetch_cell("select name from cdef where id=" . $matches[1]) . "<br>";
			$cdef_array[] = $matches[1];
		}
	}

	print "<form id='cdef_actions' action='cdef.php' method='post' name='cdef_actions'>\n";

	html_start_box("", "100", "0", "center", "");

	if (isset($cdef_array)) {
		if (get_request_var_post("drp_action") === ACTION_NONE) { /* NONE */
			print "	<tr>
					<td class='topBoxAlt'>
						<p>" . __("You did not select a valid action. Please select 'Return' to return to the previous menu.") . "</p>
					</td>
				</tr>\n";

			$title = __("Selection Error");
		}elseif (get_request_var_post("drp_action") === "1") { /* delete */
			print "	<tr>
					<td class='topBoxAlt'>
						<p>" . __("When you click 'Continue', the selected CDEFs will be deleted.") . "</p>
						<div class='action_list'><ul>$cdef_list</ul></div>
					</td>
				</tr>\n";

			$title = __("Delete CDEF(s)");
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			print "	<tr>
					<td class='topBoxAlt'>
						<p>" . __("When you click 'Continue', the following CDEFs will be duplicated. You can optionally change the title format for the new CDEFs.") . "</p>
						<div class='action_list'><ul>$cdef_list</ul></div>
						<p><strong>" . __("Title Format:") . "</strong><br>"; form_text_box("title_format", "<cdef_title> (1)", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n";

			$title = __("Duplicate CDEF(s)");
		}
	}else{
		print "<tr><td class='topBoxAlt'><p>" . __("You must select at least one CDEF.") . "</p></td></tr>\n";
	}

	if (!isset($cdef_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button($title);
	}else{
		form_continue(serialize($cdef_array), get_request_var_post("drp_action"), $title, "cdef_actions");
	}

	html_end_box();
}

/* --------------------------
    CDEF Item Functions
   -------------------------- */

function item_remove_confirm() {
	require(CACTI_BASE_PATH . "/include/presets/preset_cdef_arrays.php");
	require_once(CACTI_BASE_PATH . "/lib/presets/preset_cdef_info.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("id"));
	input_validate_input_number(get_request_var_request("cdef_id"));
	/* ==================================================== */

	print "<form id='delete' action='cdef.php' name='delete' method='post'>\n";

	html_start_box("", "100", "3", "center", "");

	$cdef       = db_fetch_row("SELECT * FROM cdef WHERE id=" . get_request_var_request("id"));
	$cdef_item  = db_fetch_row("SELECT * FROM cdef_items WHERE id=" . get_request_var_request("cdef_id"));

	?>
	<tr>
		<td class='topBoxAlt'>
			<p><?php print __("When you click 'Continue', the following CDEF item will be deleted.");?></p>
			<p>CDEF Name: '<?php print $cdef["name"];?>'<br>
			<em><?php $cdef_item_type = $cdef_item["type"]; print $cdef_item_types[$cdef_item_type];?></em>: <strong><?php print get_cdef_item_name($cdef_item["id"]);?></strong>
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
		$.post('cdef.php?action=item_remove', { cdef_id: <?php print get_request_var("cdef_id");?>, id: <?php print get_request_var("id");?> }, function(data) {
			$('#cdialog').dialog('close');
			$.get('cdef.php?action=ajax_edit&id=<?php print get_request_var("id");?>', function(data) {
				$('#content').html(data);
			});
		});
        });
        </script>
	<?php
}
		
function item_remove() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("cdef_id"));
	/* ==================================================== */

	db_execute("delete from cdef_items where id=" . $_GET["id"]);
}

function cdef_dnd(){
	/* ================= Input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ================= Input validation ================= */

	if(!isset($_REQUEST['cdef_item']) || !is_array($_REQUEST['cdef_item'])) exit;
	/* cdef table contains one row defined as "nodrag&nodrop" */
	unset($_REQUEST['cdef_item'][0]);

	/* delivered cdef ids has to be exactly the same like we have stored */
	$old_order = array();
	$new_order = $_REQUEST['cdef_item'];

	$sql = "SELECT id, sequence FROM cdef_items WHERE cdef_id = " . $_GET['id'];
	$cdef_items = db_fetch_assoc($sql);

	if(sizeof($cdef_items)>0) {
		foreach($cdef_items as $item) {
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

	foreach($diff as $sequence => $cdef_id) {
		$sql = "UPDATE cdef_items SET sequence = $sequence WHERE id = $cdef_id";
		db_execute($sql);
	}

	draw_cdef_preview(get_request_var("id"));
}

function item_edit() {
	require(CACTI_BASE_PATH . "/include/presets/preset_cdef_arrays.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("cdef_id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$cdef = db_fetch_row("select * from cdef_items where id=" . $_GET["id"]);
		$current_type = $cdef["type"];
		$values[$current_type] = $cdef["value"];
	}

	print "<div>";
	draw_cdef_preview(get_request_var("cdef_id"));
	echo "</div>";

	print "<form action='cdef.php' name='form_cdef' method='post'>\n";
	html_start_box(__("CDEF Items") . " [edit: " . db_fetch_cell("select name from cdef where id=" . $_GET["cdef_id"]) . "]", "100", "3", "center", "");

	if (isset($_GET["type_select"])) {
		$current_type = $_GET["type_select"];
	}elseif (isset($cdef["type"])) {
		$current_type = $cdef["type"];
	}else{
		$current_type = CVDEF_ITEM_TYPE_FUNCTION;
	}

	form_alternate_row_color("cdef_item_type"); ?>
		<td width="50%">
			<font class="textEditTitle"><?php print __("CDEF Item Type");?></font><br>
			<?php print __("Choose what type of CDEF item this is.");?>
		</td>
		<td>
			<select name="type_select" onChange="window.location=document.form_cdef.type_select.options[document.form_cdef.type_select.selectedIndex].value">
				<?php
				while (list($var, $val) = each($cdef_item_types)) {
					print "<option value='" . htmlspecialchars("cdef.php?action=item_edit" . (isset($_GET["id"]) ? "&id=" . $_GET["id"] : "") . "&cdef_id=" . $_GET["cdef_id"] . "&type_select=$var") . "'"; if ($var == $current_type) { print " selected"; } print ">$val</option>\n";
				}
				?>
			</select>
		</td>
	<?php
	form_end_row();
	form_alternate_row_color("cdef_item_value");
	?>
		<td width="50%">
			<font class="textEditTitle"><?php print __("CDEF Item Value");?></font><br>
			<?php print __("Enter a value for this CDEF item.");?>
		</td>
		<td>
			<?php
			switch ($current_type) {
			case '1':
				form_dropdown("value", $cdef_functions, "", "", (isset($cdef["value"]) ? $cdef["value"] : ""), "", "");
				break;
			case '2':
				form_dropdown("value", $cdef_operators, "", "", (isset($cdef["value"]) ? $cdef["value"] : ""), "", "");
				break;
			case '4':
				form_dropdown("value", $custom_data_source_types, "", "", (isset($cdef["value"]) ? $cdef["value"] : ""), "", "");
				break;
			case '5':
				form_dropdown("value", db_fetch_assoc("select name,id from cdef order by name"), "name", "id", (isset($cdef["value"]) ? $cdef["value"] : ""), "", "");
				break;
			case '6':
				form_text_box("value", (isset($cdef["value"]) ? $cdef["value"] : ""), "", "255", 60, "text", (isset($_GET["id"]) ? $_GET["id"] : "0"));
				break;
			}
			?>
		</td>
	<?php
	form_end_row();

	form_hidden_box("id", (isset($_GET["id"]) ? $_GET["id"] : "0"), "");
	form_hidden_box("type", $current_type, "");
	form_hidden_box("cdef_id", get_request_var("cdef_id"), "");
	form_hidden_box("save_component_item", "1", "");

	html_end_box();

	form_save_button_alt("path!cdef.php|action!edit|id!" . get_request_var("cdef_id"));
}

/* ---------------------
    CDEF Functions
   --------------------- */

function cdef_edit() {
	require(CACTI_BASE_PATH . "/include/presets/preset_cdef_arrays.php");
	require_once(CACTI_BASE_PATH . "/lib/presets/preset_cdef_info.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$cdef = db_fetch_row("select * from cdef where id=" . $_GET["id"]);
		$header_label = __("[edit: ") . $cdef["name"] . "]";
	}else{
		$header_label = __("[new]");
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='cdef_edit'>\n";

	html_start_box(__("CDEF's") . " $header_label", "100", 0, "center", "");

	draw_edit_form(array(
		"config" => array("no_form_tag" => true),
		"fields" => inject_form_variables(preset_cdef_form_list(), (isset($cdef) ? $cdef : array()))
		));

	html_end_box();

	if (!empty($_GET["id"])) {
		print "<div>";	
		draw_cdef_preview(get_request_var("id"));
		print "</div>";

		html_start_box(__("CDEF Items"), "100", 0, "center", "cdef.php?action=item_edit&cdef_id=" . $cdef["id"], false, "cdef");

		$header_items = array(
			array("name" => __("Item")),
			array("name" => __("Item Value"))
		);

		print "<tr><td>";

		html_header($header_items, 2, false, 'cdef_item','left wp100');

		$cdef_items = db_fetch_assoc("select * from cdef_items where cdef_id=" . $_GET["id"] . " order by sequence");

		$i = 0;
		if (sizeof($cdef_items) > 0) {
			foreach ($cdef_items as $cdef_item) {
				form_alternate_row_color($cdef_item["id"], true);
					?>
					<td>
						<a class="linkEditMain" href="<?php print htmlspecialchars("cdef.php?action=item_edit&id=" . $cdef_item["id"] . "&cdef_id=" . $cdef["id"]);?>">Item #<?php print $i;?></a>
					</td>
					<td>
						<em><?php $cdef_item_type = $cdef_item["type"]; print $cdef_item_types[$cdef_item_type];?></em>: <strong><?php print get_cdef_item_name($cdef_item["id"]);?></strong>
					</td>
					<td align="right" style='text-align:right;width:16px;'>
						<img id="<?php print $cdef["id"] . "_" . $cdef_item["id"];?>" class="delete buttonSmall" src="images/delete_icon.gif" alt="<?php print __("Delete");?>" title="<?php print __("Delete CDEF Item");?>" align="middle">
					</td>
			<?php

			form_end_row();
			$i++;
			}
		}

		print "</table></td></tr>";		/* end of html_header */

		html_end_box();
	}
	form_save_button("cdef.php", "return");

	?>
	<script type="text/javascript">
	$('#cdef_item').tableDnD({
		onDrop: function(table, row) {
			$.get('cdef.php?action=ajax_dnd&id=<?php isset($_GET["id"]) ? print get_request_var("id") : print 0;?>&'+$.tableDnD.serialize(), function(data) {
				if (data) {
					$('#preview').html(data);
				}
			});
		}
	});

	$('.delete').click(function (data) {
                id = $(this).attr('id').split("_");
		request = "cdef.php?action=item_remove_confirm&id="+id[0]+"&cdef_id="+id[1];
                $.get(request, function(data) {
                        $('#cdialog').html(data);
                        $('#cdialog').dialog({ title: "<?php print __("Delete CDEF Item");?>", minHeight: 80, minWidth: 500 });
                });
	}).css("cursor", "pointer");
	</script>
<?php
}

function cdef_filter() {
	global $item_rows;

	html_start_box(__("CDEF's"), "100", "3", "center", "cdef.php?action=edit", true);
	?>
	<tr class='rowAlternate3'>
		<td>
			<form name="form_cdef" action="cdef.php">
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
						<select name="rows" onChange="applyFilterChange(document.form_cdef)">
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

function get_cdef_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	if (strlen(html_get_page_variable("filter"))) {
		$sql_where = "WHERE (cdef.name LIKE '%%" . html_get_page_variable("filter") . "%%')";
	}else{
		$sql_where = "";
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$total_rows = db_fetch_cell("SELECT
		COUNT(cdef.id)
		FROM cdef
		$sql_where");

	return db_fetch_assoc("SELECT
		cdef.id, cdef.name
		FROM cdef
		$sql_where
		ORDER BY " . html_get_page_variable('sort_column') . " " . html_get_page_variable('sort_direction') .
		" LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp);
}

function cdef($refresh = true) {
	global $cdef_actions;

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
			"name" => __("CDEF Title"),
			"order" => "ASC",
			"filter" => true,
			"link" => true
		),
		"id" => array(
			"name" => __("ID"),
			"align" => "right",
			"order" => "ASC"
		)
	);

	/* initialize page behavior */
	$table->href           = "cdef.php";
	$table->session_prefix = "sess_cdef";
	$table->filter_func    = "cdef_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = $cdef_actions;

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_cdef_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}
