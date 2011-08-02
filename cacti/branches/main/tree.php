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
include_once(CACTI_BASE_PATH . "/lib/api_tree.php");
include_once(CACTI_BASE_PATH . "/lib/tree.php");
include_once(CACTI_BASE_PATH . "/lib/html_tree.php");

$tree_actions = array(
	ACTION_NONE => __("None"),
	"1" => __("Delete")
	);

define("MAX_DISPLAY_PAGES", 21);

input_validate_input_number(get_request_var('tree_id'));
input_validate_input_number(get_request_var('leaf_id'));
input_validate_input_number(get_request_var_post('graph_tree_id'));
input_validate_input_number(get_request_var_post('parent_item_id'));

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch (get_request_var_request("action")) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'item_movedown':
		item_movedown();

		header("Location: tree.php?action=edit&id=" . $_GET["tree_id"]);
		break;
	case 'item_moveup':
		item_moveup();

		header("Location: tree.php?action=edit&id=" . $_GET["tree_id"]);
		break;
	case 'item_edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		item_edit();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
	case 'item_remove':
		item_remove();

		header("Location: tree.php?action=edit&id=" . $_GET["tree_id"]);
		break;
	case 'edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		tree_edit();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
	default:
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		tree();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */
function form_save() {
	require_once(CACTI_BASE_PATH . "/include/graph_tree/graph_tree_constants.php");

	if (isset($_POST["save_component_tree"])) {
		$save["id"] = $_POST["id"];
		$save["name"] = form_input_validate($_POST["name"], "name", "", false, 3);
		$save["sort_type"] = form_input_validate($_POST["sort_type"], "sort_type", "", true, 3);

		if (!is_error_message()) {
			$tree_id = sql_save($save, "graph_tree");

			if ($tree_id) {
				raise_message(1);

				/* sort the tree using the algorithm chosen by the user */
				sort_tree(SORT_TYPE_TREE, $tree_id, get_request_var_post("sort_type"));
			}else{
				raise_message(2);
			}
		}

		if ((is_error_message()) || (empty($_POST["id"]))) {
			header("Location: tree.php?action=edit&id=" . (empty($tree_id) ? $_POST["id"] : $tree_id));
		}else{
			header("Location: tree.php");
		}
		exit;
	}elseif (isset($_POST["save_component_tree_item"])) {
		$tree_item_id = tree_item_save($_POST["id"], $_POST["graph_tree_id"], $_POST["type"], $_POST["parent_item_id"],
			(isset($_POST["title"]) ? $_POST["title"] : ""),
			(isset($_POST["local_graph_id"]) ? $_POST["local_graph_id"] : "0"),
			(isset($_POST["rra_id"]) ? $_POST["rra_id"] : "0"),
			(isset($_POST["device_id"]) ? $_POST["device_id"] : "0"),
			(isset($_POST["device_grouping_type"]) ? $_POST["device_grouping_type"] : "1"),
			(isset($_POST["sort_children_type"]) ? $_POST["sort_children_type"] : "1"),
			(isset($_POST["propagate_changes"]) ? true : false));

		if (is_error_message()) {
			header("Location: tree.php?action=item_edit&tree_item_id=" . (empty($tree_item_id) ? $_POST["id"] : $tree_item_id) . "&tree_id=" . $_POST["graph_tree_id"] . "&parent_id=" . $_POST["parent_item_id"]);
		}else{
			header("Location: tree.php?action=edit&id=" . $_POST["graph_tree_id"]);
		}
		exit;
	}
}

/* -----------------------
    Tree Item Functions
   ----------------------- */

function item_edit() {
	require(CACTI_BASE_PATH . "/include/data_query/data_query_arrays.php");
	require(CACTI_BASE_PATH . "/include/graph_tree/graph_tree_arrays.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("tree_id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$tree_item = db_fetch_row("select * from graph_tree_items where id=" . get_request_var("id"));

		if ($tree_item["local_graph_id"] > 0) { $db_type = TREE_ITEM_TYPE_GRAPH; }
		if ($tree_item["title"] != "") { $db_type = TREE_ITEM_TYPE_HEADER; }
		if ($tree_item["device_id"] > 0) { $db_type = TREE_ITEM_TYPE_DEVICE; }
	}

	if (isset($_GET["type_select"])) {
		$current_type = $_GET["type_select"];
	}elseif (isset($db_type)) {
		$current_type = $db_type;
	}else{
		$current_type = TREE_ITEM_TYPE_HEADER;
	}

	$tree_sort_type = db_fetch_cell("select sort_type from graph_tree where id='" . get_request_var("tree_id") . "'");

	print "<form action='tree.php' name='form_tree' method='post'>\n";

	html_start_box("<strong>" . __("Tree Items") . "</strong>", "100", "3", "center", "");

	form_alternate_row_color("parent_item");
	?>
		<td width="50%">
			<font class="textEditTitle"><?php print __("Parent Item");?></font><br>
			<?php print __("Choose the parent for this header/graph.");?>
		</td>
		<td>
			<?php grow_dropdown_tree($_GET["tree_id"], "parent_item_id", (isset($_GET["parent_id"]) ? $_GET["parent_id"] : get_parent_id($tree_item["id"], "graph_tree_items", "graph_tree_id=" . $_GET["tree_id"])));?>
		</td>
	<?php
	form_end_row();
	form_alternate_row_color("tree_item");
	?>
		<td width="50%">
			<font class="textEditTitle"><?php print __("Tree Item Type");?></font><br>
			<?php print __("Choose what type of tree item this is.");?>
		</td>
		<td>
			<select name="type_select" onChange="window.location=document.form_tree.type_select.options[document.form_tree.type_select.selectedIndex].value">
				<?php
				while (list($var, $val) = each($tree_item_types)) {
					print "<option value='" . htmlspecialchars("tree.php?action=item_edit" . (isset($_GET["id"]) ? "&id=" . $_GET["id"] : "") . (isset($_GET["parent_id"]) ? "&parent_id=" . $_GET["parent_id"] : "") . "&tree_id=" . $_GET["tree_id"] . "&type_select=" . $var) . "'"; if ($var == $current_type) { print " selected"; } print ">$val</option>\n";
				}
				?>
			</select>
		</td>
	<?php
	form_end_row();
	?>
		<tr class='rowSubHeader'>
			<td colspan="2" class='textSubHeaderDark'><?php print __("Tree Item Value");?></td>
		</tr>
	<?php
	switch ($current_type) {
	case TREE_ITEM_TYPE_HEADER:
		$i = 0;

		/* it's nice to default to the parent sorting style for new items */
		if (empty($_GET["id"])) {
			$default_sorting_type = db_fetch_cell("select sort_children_type from graph_tree_items where id=" . $_GET["parent_id"]);
		}else{
			$default_sorting_type = DATA_QUERY_INDEX_SORT_TYPE_NONE;
		}

		form_alternate_row_color("item_title"); ?>
			<td width="50%">
				<font class="textEditTitle"><?php print __("Title");?></font><br>
				<?php print __("If this item is a header, enter a title here.");?>
			</td>
			<td>
				<?php form_text_box("title", (isset($tree_item["title"]) ? $tree_item["title"] : ""), "", "255", 30, "text", (isset($_GET["id"]) ? $_GET["id"] : "0"));?>
			</td>
		<?php
		form_end_row();
		/* don't allow the user to change the tree item ordering if a tree order has been specified */
		if ($tree_sort_type == DATA_QUERY_INDEX_SORT_TYPE_NONE) {
			form_alternate_row_color("sorting_type"); ?>
				<td width="50%">
					<font class="textEditTitle"><?php print __("Sorting Type");?></font><br>
					<?php print __("Choose how children of this branch will be sorted.");?>
				</td>
				<td>
					<?php form_dropdown("sort_children_type", $tree_sort_types, "", "", (isset($tree_item["sort_children_type"]) ? $tree_item["sort_children_type"] : $default_sorting_type), "", "");?>
				</td>
			<?php
			form_end_row();
		}

		if ((!empty($_GET["id"])) && ($tree_sort_type == DATA_QUERY_INDEX_SORT_TYPE_NONE)) {
			form_alternate_row_color("propagate"); ?>
				<td width="50%">
					<font class="textEditTitle"><?php print __("Propagate Changes");?></font><br>
					<?php print __("Propagate all options on this form (except for 'Title') to all child 'Header' items.");?>
				</td>
				<td>
					<?php form_checkbox("propagate_changes", "", __("Propagate Changes"), "", "", "", 0);?>
				</td>
			<?php
			form_end_row();
		}
		break;
	case TREE_ITEM_TYPE_GRAPH:
		form_alternate_row_color("graph"); ?>
			<td width="50%">
				<font class="textEditTitle"><?php print __("Graph");?></font><br>
				<?php print __("Choose a graph from this list to add it to the tree.");?>
			</td>
			<td>
				<?php form_dropdown("local_graph_id", db_fetch_assoc("select graph_templates_graph.local_graph_id as id,graph_templates_graph.title_cache as name from (graph_templates_graph,graph_local) where graph_local.id=graph_templates_graph.local_graph_id and local_graph_id != 0 order by title_cache"), "name", "id", (isset($tree_item["local_graph_id"]) ? $tree_item["local_graph_id"] : ""), "", "");?>
			</td>
		<?php
		form_end_row();
		form_alternate_row_color("rra");
		?>
			<td width="50%">
				<font class="textEditTitle"><?php print __("Round Robin Archive");?></font><br>
				<?php print __("Choose a round robin archive to control how this graph is displayed.");?>
			</td>
			<td>
				<?php form_dropdown("rra_id", db_fetch_assoc("select id,name from rra order by timespan"), "name", "id", (isset($tree_item["rra_id"]) ? $tree_item["rra_id"] : ""), "", "");?>
			</td>
		<?php
		form_end_row();
		break;
	case TREE_ITEM_TYPE_DEVICE:
		form_alternate_row_color("device"); ?>
			<td width="50%">
				<font class="textEditTitle"><?php print __("Host");?></font><br>
				<?php print __("Choose a device here to add it to the tree.");?>
			</td>
			<td>
				<?php form_dropdown("device_id", db_fetch_assoc("select id,CONCAT_WS('',description,' (',hostname,')') as name from device order by description,hostname"), "name", "id", (isset($tree_item["device_id"]) ? $tree_item["device_id"] : ""), "", "");?>
			</td>
		<?php
		form_end_row();
		form_alternate_row_color("graph_grouping");
		?>
			<td width="50%">
				<font class="textEditTitle"><?php print __("Graph Grouping Style");?></font><br>
				<?php print __("Choose how graphs are grouped when drawn for this particular device on the tree.");?>
			</td>
			<td>
				<?php form_dropdown("device_grouping_type", $tree_device_group_types, "", "", (isset($tree_item["device_grouping_type"]) ? $tree_item["device_grouping_type"] : "1"), "", "");?>
			</td>
		<?php
		form_end_row();
		break;
	}

	html_end_box();

	form_hidden_box("id", (isset($_GET["id"]) ? $_GET["id"] : "0"), "");
	form_hidden_box("graph_tree_id", get_request_var("tree_id"), "");
	form_hidden_box("type", $current_type, "");
	form_hidden_box("save_component_tree_item", "1", "");

	form_save_button_alt("path!tree.php|action!edit|id!" . get_request_var("tree_id"));
}

function item_moveup() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("tree_id"));
	/* ==================================================== */

	$order_key = db_fetch_cell("SELECT order_key FROM graph_tree_items WHERE id=" . $_GET["id"]);
	if ($order_key > 0) { branch_up($order_key, 'graph_tree_items', 'order_key', 'graph_tree_id=' . $_GET["tree_id"]); }
}

function item_movedown() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("tree_id"));
	/* ==================================================== */

	$order_key = db_fetch_cell("SELECT order_key FROM graph_tree_items WHERE id=" . $_GET["id"]);
	if ($order_key > 0) { branch_down($order_key, 'graph_tree_items', 'order_key', 'graph_tree_id=' . $_GET["tree_id"]); }
}

function item_remove() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("tree_id"));
	/* ==================================================== */

	if ((read_config_option("deletion_verification") == "on") && (!isset($_GET["confirm"]))) {
		$graph_tree_item = db_fetch_row("select title,local_graph_id,device_id from graph_tree_items where id=" . $_GET["id"]);

		if (!empty($graph_tree_item["local_graph_id"])) {
			$text = __("Are you sure you want to delete the graph item") . " <strong>'" . db_fetch_cell("select title_cache from graph_templates_graph where local_graph_id=" . $graph_tree_item["local_graph_id"]) . "'</strong>?";
		}elseif ($graph_tree_item["title"] != "") {
			$text = __("Are you sure you want to delete the header item") . " <strong>'" . $graph_tree_item["title"] . "'</strong>?";
		}elseif (!empty($graph_tree_item["device_id"])) {
			$text = __("Are you sure you want to delete the device item") . " <strong>'" . db_fetch_cell("select CONCAT_WS('',description,' (',hostname,')') as hostname from device where id=" . $graph_tree_item["device_id"]) . "'</strong>?";
		}

		include(CACTI_BASE_PATH . "/include/top_header.php");
		form_confirm(__("Are You Sure?"), $text, "tree.php?action=edit&id=" . $_GET["tree_id"], "tree.php?action=item_remove&id=" . $_GET["id"] . "&tree_id=" . $_GET["tree_id"]);
		include(CACTI_BASE_PATH . "/include/bottom_footer.php");
		exit;
	}

	if ((read_config_option("deletion_verification") == "") || (isset($_GET["confirm"]))) {
		delete_branch(get_request_var("id"));
	}

	header("Location: tree.php?action=edit&id=" . $_GET["tree_id"]); exit;
}


/* ---------------------
    Tree Functions
   --------------------- */

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $tree_actions, $messages;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === "1") { /* delete */
			/* do a referential integrity check */
			if (sizeof($selected_items)) {
				foreach($selected_items as $tree_id) {
					/* ================= input validation ================= */
					input_validate_input_number($tree_id);
					/* ==================================================== */
					db_execute("delete from graph_tree where id=" . $tree_id);
					db_execute("delete from graph_tree_items where graph_tree_id=" . $tree_id);
				}
			}
		}
		header("Location: tree.php");
		exit;
	}

	/* setup some variables */
	$tree_list = "";

	/* loop through each of the graphs selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$tree_list .= "<li>" . db_fetch_cell("select name from graph_tree where id=" . $matches[1]) . "</li>";
			$tree_array[] = $matches[1];
		}
	}

	include_once("./include/top_header.php");

	html_start_box("<strong>" . $tree_actions{get_request_var_post("drp_action")} . "</strong>", "60", "3", "center", "");

	print "<form action='tree.php' method='post'>\n";

	if (isset($tree_array)) {
		if (get_request_var_post("drp_action") === ACTION_NONE) { /* NONE */
			print "	<tr>
						<td class='textArea'>
							<p>" . __("You did not select a valid action. Please select 'Return' to return to the previous menu.") . "</p>
						</td>
					</tr>\n";
		}elseif (get_request_var_post("drp_action") === "1") { /* delete */
			print "	<tr>
					<td class='topBoxAlt'>
						<p>" . __("When you click 'Continue', the following Tree(s) will be deleted.") . "</p>
						<p><ul>$tree_list</ul></p>
					</td>
				</tr>\n";

			$title = __("Delete Tree(s)");
		}
	}else{
		print "<tr><td class='topBoxAlt'><span class='textError'>" . __("You must select at least one Tree.") . "</span></td></tr>\n";
	}

	if (!isset($tree_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button();
	}else{
		form_continue(serialize($tree_array), get_request_var_post("drp_action"), $title);
	}

	html_end_box();

	include_once("./include/bottom_footer.php");
}

function tree_remove() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if ((read_config_option("deletion_verification") == "on") && (!isset($_GET["confirm"]))) {
		include(CACTI_BASE_PATH . "/include/top_header.php");
		form_confirm(__("Are You Sure?"), __("Are you sure you want to delete the tree") . " <strong>'" . db_fetch_cell("select name from graph_tree where id=" . $_GET["id"]) . "'</strong>?", "tree.php", "tree.php?action=remove&id=" . $_GET["id"]);
		include(CACTI_BASE_PATH . "/include/bottom_footer.php");
		exit;
	}

	if ((read_config_option("deletion_verification") == "") || (isset($_GET["confirm"]))) {
		db_execute("delete from graph_tree where id=" . $_GET["id"]);
		db_execute("delete from graph_tree_items where graph_tree_id=" . $_GET["id"]);
	}
}

function tree_edit() {
	require_once(CACTI_BASE_PATH . "/lib/graph_tree/graph_tree_info.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	/* clean up subaction */
	if (isset($_REQUEST["subaction"])) {
		$_REQUEST["subaction"] = sanitize_search_string(get_request_var("subaction"));
	}

	if (!empty($_GET["id"])) {
		$tree = db_fetch_row("select * from graph_tree where id=" . $_GET["id"]);
		$header_label = __("[edit: ") . $tree["name"] . "]";
	}else{
		$header_label = __("[new]");
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='tree_edit'>\n";
	html_start_box("<strong>" . __("Graph Trees") . "</strong> $header_label", "100", "3", "center", "", true);

	draw_edit_form(array(
		"config" => array("no_form_tag" => true),
		"fields" => inject_form_variables(graph_tree_form_list(), (isset($tree) ? $tree : array()))
		));

	html_end_box(FALSE);

	if (!empty($_GET["id"])) {
		/* setup the tree div's */
		echo "<div id='tree' style='float:left;width:50%;'>";
		html_start_box("<strong>" . __("Tree Items") . "</strong>", "100", "3", "center", "");
		$header_items = array(
			array("name" => __("Item")),
			array("name" => __("Value"))
		);

		print "<tr><td>";
		html_header($header_items, 3, false, 'tree');
		grow_edit_graph_tree(get_request_var("id"), "", "");
		print "</table></td></tr>";		/* end of html_header */
		html_end_box();
		echo "</div>";

		/* setup the graph items div */
		echo "<div id='items' style='float:right;width:50%';>";
		html_start_box("<strong>" . __("Item Filter") . "</strong>", "100", "3", "center", "");
		$header_items = array(
			array("name" => __("Item")),
			array("name" => __("Value"))
		);

		print "<tr><td>";
		html_header($header_items, 3, false, 'tree');
		print "</table></td></tr>";		/* end of html_header */
		html_end_box();
		echo "</div>";
	}

	form_save_button_alt("path!tree.php");
}

function tree_filter() {
	global $item_rows;
	html_start_box("<strong>" . __("Graph Trees") . "</strong>", "100", "3", "center", "tree.php?action=edit", true);
	?>
	<tr class='rowAlternate2'>
		<td>
			<form name='form_tree' action="<?php print basename($_SERVER['PHP_SELF']);?>" method="post">
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

function get_tree_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	$sql_where = "WHERE (graph_tree.name LIKE '%%" . html_get_page_variable("filter") . "%%')";

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$total_rows = db_fetch_cell("SELECT
		COUNT(graph_tree.id)
		FROM graph_tree
		$sql_where");

	return db_fetch_assoc("SELECT *
		FROM graph_tree
		$sql_where
		ORDER BY " . html_get_page_variable('sort_column') . " " . html_get_page_variable('sort_direction') .
		" LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp);
}

function tree($refresh = true) {
	global $tree_actions;
	
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
		"id" => array(
			"name" => __("ID"),
			"order" => "ASC"
		)
	);

	/* initialize page behavior */
	$table->href           = "tree.php";
	$table->session_prefix = "sess_tree";
	$table->filter_func    = "tree_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = $tree_actions;

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_tree_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}
