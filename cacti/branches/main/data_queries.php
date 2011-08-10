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
include_once(CACTI_BASE_PATH . "/lib/data_query.php");
include_once(CACTI_BASE_PATH . "/lib/utility.php");

define("MAX_DISPLAY_PAGES", 21);

$dq_actions = array(
	ACTION_NONE => __("None"),
	"1" => __("Delete"),
	"2" => __("Duplicate")
	);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch (get_request_var_request("action")) {
	case 'save':
		data_query_form_save();

		break;
	case 'actions':
		data_query_form_actions();

		break;
	case 'item_moveup_dssv':
		data_query_item_moveup_dssv();

		header("Location: data_queries.php?action=item_edit&id=" . $_GET["snmp_query_graph_id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_movedown_dssv':
		data_query_item_movedown_dssv();

		header("Location: data_queries.php?action=item_edit&id=" . $_GET["snmp_query_graph_id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_remove_dssv':
		data_query_item_remove_dssv();

		header("Location: data_queries.php?action=item_edit&id=" . $_GET["snmp_query_graph_id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_moveup_gsv':
		data_query_item_moveup_gsv();

		header("Location: data_queries.php?action=item_edit&id=" . $_GET["snmp_query_graph_id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_movedown_gsv':
		data_query_item_movedown_gsv();

		header("Location: data_queries.php?action=item_edit&id=" . $_GET["snmp_query_graph_id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_remove_gsv':
		data_query_item_remove_gsv();

		header("Location: data_queries.php?action=item_edit&id=" . $_GET["snmp_query_graph_id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_remove':
		data_query_item_remove();

		header("Location: data_queries.php?action=edit&id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		data_query_item_edit();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
	case 'edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		data_query_edit();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
	case 'ajax_dt_save':
		data_query_dt_dnd();

		break;
	case 'ajax_gt_save':
		data_query_gt_dnd();

		break;
	default:
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		data_query();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function data_query_form_save() {
	if (isset($_POST["save_component_snmp_query"])) {
		$save["id"] = $_POST["id"];
		$save["hash"] = get_hash_data_query($_POST["id"]);
		$save["name"] = form_input_validate($_POST["name"], "name", "", false, 3);
		$save["description"] = form_input_validate($_POST["description"], "description", "", true, 3);
		$save["image"] = form_input_validate(basename($_POST["image"]), "image", "", true, 3);
		$save["xml_path"] = form_input_validate($_POST["xml_path"], "xml_path", "", false, 3);
		$save["data_input_id"] = $_POST["data_input_id"];

		if (!is_error_message()) {
			$snmp_query_id = sql_save($save, "snmp_query");

			if ($snmp_query_id) {
				raise_message(1);

				/* save the updated image */
				data_query_update_cache($snmp_query_id, $_POST["image"]);
			}else{
				raise_message(2);
			}
		}

		header("Location: data_queries.php?action=edit&id=" . (empty($snmp_query_id) ? $_POST["id"] : $snmp_query_id));
		exit;
	}elseif (isset($_POST["save_component_snmp_query_item"])) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("id"));
		/* ==================================================== */

		$redirect_back = false;

		$save["id"] = $_POST["id"];
		$save["hash"] = get_hash_data_query($_POST["id"], "data_query_graph");
		$save["snmp_query_id"] = $_POST["snmp_query_id"];
		$save["name"] = form_input_validate($_POST["name"], "name", "", false, 3);
		$save["graph_template_id"] = $_POST["graph_template_id"];

		if (!is_error_message()) {
			$snmp_query_graph_id = sql_save($save, "snmp_query_graph");

			if ($snmp_query_graph_id) {
				raise_message(1);

				/* if the user changed the graph template, go through and delete everything that
				was associated with the old graph template */
				if ($_POST["graph_template_id"] != $_POST["_graph_template_id"]) {
					db_execute("delete from snmp_query_graph_rrd_sv where snmp_query_graph_id=$snmp_query_graph_id");
					db_execute("delete from snmp_query_graph_sv where snmp_query_graph_id=$snmp_query_graph_id");
					$redirect_back = true;
				}

				db_execute("delete from snmp_query_graph_rrd where snmp_query_graph_id=$snmp_query_graph_id");

				while (list($var, $val) = each($_POST)) {
					if (preg_match("/^dsdt_([0-9]+)_([0-9]+)_check/i", $var)) {
						$data_template_id = preg_replace("/^dsdt_([0-9]+)_([0-9]+).+/", "\\1", $var);
						$data_template_rrd_id = preg_replace("/^dsdt_([0-9]+)_([0-9]+).+/", "\\2", $var);

						db_execute ("replace into snmp_query_graph_rrd (snmp_query_graph_id,data_template_id,data_template_rrd_id,snmp_field_name) values($snmp_query_graph_id,$data_template_id,$data_template_rrd_id,'" . $_POST{"dsdt_" . $data_template_id . "_" . $data_template_rrd_id . "_snmp_field_output"} . "')");
					}elseif ((preg_match("/^svds_([0-9]+)_x/i", $var, $matches)) && (!empty($_POST{"svds_" . $matches[1] . "_text"})) && (!empty($_POST{"svds_" . $matches[1] . "_field"}))) {
						/* suggested values -- data templates */
						$sequence = get_sequence(0, "sequence", "snmp_query_graph_rrd_sv", "snmp_query_graph_id=" . $_POST["id"]  . " and data_template_id=" . $matches[1] . " and field_name='" . $_POST{"svds_" . $matches[1] . "_field"} . "'");
						$hash = get_hash_data_query(0, "data_query_sv_data_source");
						db_execute("insert into snmp_query_graph_rrd_sv (hash,snmp_query_graph_id,data_template_id,sequence,field_name,text) values ('$hash'," . get_request_var_post("id") . "," . $matches[1] . ",$sequence,'" . $_POST{"svds_" . $matches[1] . "_field"} . "','" . $_POST{"svds_" . $matches[1] . "_text"} . "')");

						$redirect_back = true;
						clear_messages();
					}elseif ((preg_match("/^svg_x/i", $var)) && (!empty($_POST{"svg_text"})) && (!empty($_POST{"svg_field"}))) {
						/* suggested values -- graph templates */
						$sequence = get_sequence(0, "sequence", "snmp_query_graph_sv", "snmp_query_graph_id=" . $_POST["id"] . " and field_name='" . $_POST{"svg_field"} . "'");
						$hash = get_hash_data_query(0, "data_query_sv_graph");
						db_execute("insert into snmp_query_graph_sv (hash,snmp_query_graph_id,sequence,field_name,text) values ('$hash'," . get_request_var_post("id") . ",$sequence,'" . $_POST{"svg_field"} . "','" . $_POST{"svg_text"} . "')");

						$redirect_back = true;
						clear_messages();
					}
				}
			}else{
				raise_message(2);
			}
		}

		if ((is_error_message()) || (empty($_POST["id"])) || ($redirect_back == true)) {
			header("Location: data_queries.php?action=item_edit&id=" . (empty($snmp_query_graph_id) ? $_POST["id"] : $snmp_query_graph_id) . "&snmp_query_id=" . $_POST["snmp_query_id"]);
		}else{
			header("Location: data_queries.php?action=edit&id=" . $_POST["snmp_query_id"]);
		}
		exit;
	}
}

function data_query_form_actions() {
	global $dq_actions;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === "1") { /* delete */
			/* do a referential integrity check */
			if (sizeof($selected_items)) {
				foreach($selected_items as $query_id) {
					/* ================= input validation ================= */
					input_validate_input_number($query_id);
					/* ==================================================== */
					
					$graph_templates = db_fetch_assoc("SELECT DISTINCT graph_template_id AS id FROM snmp_query_graph WHERE snmp_query_id=$query_id");
					
					$in_clause = "";
					if (sizeof($graph_templates)) {
						foreach($graph_templates as $graph_template) {
							$in_clause .= (strlen($in_clause) ? ", ":"") . $graph_template["id"];
						}
					}
					
					if (sizeof(db_fetch_assoc("SELECT * FROM graph_templates_graph WHERE graph_template_id IN($in_clause) LIMIT 1"))) {
						$bad_ids[] = $query_id;
					}else{
						$query_ids[] = $query_id;
					}
				}		
						
				if (isset($bad_ids)) {
					$message = "";
					foreach($bad_ids as $query_id) {
						$message .= (strlen($message) ? "<br>":"") . "<i>Data Query " . $query_id . " is in use and can not be removed</i>\n";
					}
					
					$_SESSION['sess_message_dt_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');
					
					raise_message('dt_ref_int');
				}
				
				if (isset($query_ids)) {
					foreach($query_ids as $query_id) {
						/* ================= input validation ================= */
						input_validate_input_number($query_id);
						/* ==================================================== */			
						data_query_remove($query_id);
					}
				}
			}
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */
				
				duplicate_data_query($selected_items[$i], get_request_var_post("title_format"));
			}
		}

		exit;
	}

	/* setup some variables */
	$dq_list = ""; $dq_array = array();

	/* loop through each of the data queries and process them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$dq_list .= "<li>" . db_fetch_cell("SELECT snmp_query.name FROM snmp_query WHERE id='" . $matches[1] . "'") . "</li>";
			$dq_array[] = $matches[1];
		}
	}

	print "<form id='query_actions' action='data_queries.php' method='post' name='query_actions'>\n";

	html_start_box("", "100", "3", "center", "");

	if (sizeof($dq_array)) {
		if (get_request_var_post("drp_action") === ACTION_NONE) { /* NONE */
			print "	<tr>
						<td class='textArea'>
							<p>" . __("You did not select a valid action. Please select 'Return' to return to the previous menu.") . "</p>
						</td>
					</tr>\n";

			$title = __("Selection Error");
		}elseif (get_request_var_post("drp_action") === "1") { /* delete */
			$graphs = array();

			print "
				<tr>
					<td class='textArea'>
						<p>" . __("When you click \"Continue\", the following Data Queries will be deleted.") . "</p>
						<ul>$dq_list</ul>
					</td>
				</tr>\n";

			$title = __("Delete Data Querie(s)");
		}elseif (get_request_var_post("drp_action") === "2") { /* duplicate */
			print "	<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following Data Queries will be duplicated. You can optionally change the title format for the new Data Query.") . "</p>
						<ul>$dq_list</ul>
						<p><strong>" . __("Title Format:") . "</strong><br>"; form_text_box("title_format", "<data_query_name> (1)", "", "255", "30", "text"); print "</p>
					</td>
				</tr>\n";

			$title = __("Duplicate Data Queries");
		}
	} else {
		print "	<tr>
				<td class='textArea'>
					<p>" . __("You must first select a Data Query.  Please select 'Return' to return to the previous menu.") . "</p>
				</td>
			</tr>\n";
	}

	if (!sizeof($dq_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button($title);
	}else{
		form_continue(serialize($dq_array), get_request_var_post("drp_action"), $title, "query_actions");
	}

	html_end_box();
}

/* ----------------------------
    Data Query Graph Functions
   ---------------------------- */

function data_query_item_remove_gsv() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	db_execute("delete from snmp_query_graph_sv where id=" . $_GET["id"]);
}

function data_query_item_remove_dssv() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	db_execute("delete from snmp_query_graph_rrd_sv where id=" . $_GET["id"]);
}

function data_query_item_remove() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("snmp_query_id"));
	/* ==================================================== */

	if ((read_config_option("deletion_verification") == CHECKED) && (!isset($_GET["confirm"]))) {
		include(CACTI_BASE_PATH . "/include/top_header.php");
		form_confirm(__("Are You Sure?"), __("Are you sure you want to delete the Data Query Graph") . " <strong>'" . db_fetch_cell("select name from snmp_query_graph where id=" . $_GET["id"]) . "'</strong>?", "data_queries.php?action=edit&id=" . $_GET["snmp_query_id"], "data_queries.php?action=item_remove&id=" . $_GET["id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		include(CACTI_BASE_PATH . "/include/bottom_footer.php");
		exit;
	}

	if ((read_config_option("deletion_verification") == "") || (isset($_GET["confirm"]))) {
		$graph_template_id = db_fetch_cell("SELECT graph_template_id FROM snmp_query_graph WHERE id=" . $_GET["id"]);

		if (!sizeof(db_fetch_assoc("SELECT * FROM graph_templates_graph " .
				"LEFT JOIN graph_local ON (graph_templates_graph.local_graph_id = graph_local.id) " .
				"WHERE graph_templates_graph.graph_template_id=" . $graph_template_id .
				" AND graph_local.snmp_query_id=" . $_GET["snmp_query_id"] .
				" AND graph_templates_graph.local_graph_template_graph_id<>0"))) {
			db_execute("delete from snmp_query_graph where id=" . $_GET["id"]);
			db_execute("delete from snmp_query_graph_rrd where snmp_query_graph_id=" . $_GET["id"]);
			db_execute("delete from snmp_query_graph_rrd_sv where snmp_query_graph_id=" . $_GET["id"]);
			db_execute("delete from snmp_query_graph_sv where snmp_query_graph_id=" . $_GET["id"]);
		}else{
			$message = "<i>Graph Template " . $graph_template_id . " is in use and can not be removed</i>\n";
			$_SESSION['sess_message_gt_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');
			raise_message('gt_ref_int');
		}
	}
}

function data_query_item_edit() {
	require_once(CACTI_BASE_PATH . "/lib/data_query.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("snmp_query_id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$snmp_query_item = db_fetch_row("select * from snmp_query_graph where id=" . $_GET["id"]);
	}

	if (!empty($_GET["snmp_query_id"])) {
		$snmp_query = db_fetch_row("select name,xml_path from snmp_query where id=" . get_request_var("snmp_query_id"));
		$header_label = __("[edit: ") . $snmp_query["name"] . "]";
	}else{
		$header_label = __("[new]");
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='data_query_item_edit'>\n";
	html_start_box(__("Associated Graph/Data Source Templates") . " $header_label", "100", 0, "center", "");

	draw_edit_form(array(
		"config" => array("no_form_tag" => true),
		"fields" => inject_form_variables(data_query_item_form_list(), (isset($snmp_query_item) ? $snmp_query_item : array()), $_GET)
		));

	html_end_box(true);

	if (!empty($snmp_query_item["id"])) {
		html_start_box(__("Associated Data Source Templates"), "100", "0", "center", "", false, "assoc_data_templates");
		$header_items = array(
			array("name" => __("Data Source Name")),
			array("name" => __("Associated XML Field")),
			array("name" => __("Use this Field"), "align" => "right")
		);

		print "<tr><td>";
		html_header($header_items, 1, false, 'data_templates', 'left wp100');

		$data_templates = db_fetch_assoc("select
			data_template.id,
			data_template.name
			from (data_template, data_template_rrd, graph_templates_item)
			where graph_templates_item.task_item_id=data_template_rrd.id
			and data_template_rrd.data_template_id=data_template.id
			and data_template_rrd.local_data_id=0
			and graph_templates_item.local_graph_id=0
			and graph_templates_item.graph_template_id=" . $snmp_query_item["graph_template_id"] . "
			group by data_template.id
			order by data_template.name");

		$i = 0;
		if (sizeof($data_templates) > 0) {
			foreach ($data_templates as $data_template) {
				print "	<tr class='rowHeader'>
							<td><span>Data Source Template - " . $data_template["name"] . "</span></td>
							<td></td>
							<td></td>
						</tr>";

				$data_template_rrds = db_fetch_assoc("SELECT " .
					"data_template_rrd.id, " .
					"data_template_rrd.data_source_name, " .
					"snmp_query_graph_rrd.snmp_field_name, " .
					"snmp_query_graph_rrd.snmp_query_graph_id " .
					"FROM data_template_rrd " .
					"LEFT JOIN snmp_query_graph_rrd ON (snmp_query_graph_rrd.data_template_rrd_id=data_template_rrd.id and snmp_query_graph_rrd.snmp_query_graph_id=" . $_GET["id"] . " and snmp_query_graph_rrd.data_template_id=" . $data_template["id"] . ") " .
					"WHERE data_template_rrd.data_template_id=" . $data_template["id"] .
					" AND data_template_rrd.local_data_id=0 " .
					"ORDER BY data_template_rrd.data_source_name");

				if (sizeof($data_template_rrds) > 0) {
					foreach ($data_template_rrds as $data_template_rrd) {
						if (empty($data_template_rrd["snmp_query_graph_id"])) {
							$old_value = "";
						}else{
							$old_value = CHECKED;
						}

						form_alternate_row_color("data_template_rrd" . $data_template_rrd["id"]);
						print "<td>\n";
						print $data_template_rrd["data_source_name"];
						print "</td>\n<td>";
						$snmp_queries = get_data_query_array($_GET["snmp_query_id"]);
						$xml_outputs = array();

						while (list($field_name, $field_array) = each($snmp_queries["fields"])) {
							if ($field_array["direction"] == "output") {
								$xml_outputs[$field_name] = $field_name . " (" . $field_array["name"] . ")";;
							}
						}

						form_dropdown("dsdt_" . $data_template["id"] . "_" . $data_template_rrd["id"] . "_snmp_field_output",$xml_outputs,"","",$data_template_rrd["snmp_field_name"],"","");
						print "</td>\n<td style='text-align:right;'>";
						form_checkbox("dsdt_" . $data_template["id"] . "_" . $data_template_rrd["id"] . "_check", $old_value, "", "", "", get_request_var("id")); print "<br>";
						print "</td>\n";
						form_end_row();
					}
				}
			}
		}

		print "</table></td></tr>";		/* end of html_header */
		html_end_box();

		html_start_box(__("Suggested Values: Data Source Templates"), "100", 0, "center", "");

		reset($data_templates);

		/* suggested values for data templates */
		if (sizeof($data_templates) > 0) {
			foreach ($data_templates as $data_template) {

				$header_items = array(
					array("name" => __("Data Source Template") . " - " . $data_template["name"], "&nbsp;")
				);

				print "<tr><td>";
				html_header($header_items, 3, true, 'data_template_suggested_values_' . $data_template["id"], 'left wp100');

				$suggested_values = db_fetch_assoc("select
					text,
					field_name,
					id
					from snmp_query_graph_rrd_sv
					where snmp_query_graph_id=" . $_GET["id"] . "
					and data_template_id=" . $data_template["id"] . "
					order by field_name,sequence");

				if (sizeof($suggested_values) > 0) {
					foreach ($suggested_values as $suggested_value) {
						form_alternate_row_color($suggested_value["id"], true);
						?>
							<td>
								<strong><?php print $suggested_value["field_name"];?></strong>
							</td>
							<td>
								<?php print $suggested_value["text"];?>
							</td>
							<td align="right" style="text-align:right;">
								<a href="<?php print htmlspecialchars("data_queries.php?action=item_remove_dssv&snmp_query_graph_id=" . $_GET["id"] . "&id=" . $suggested_value["id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]. "&data_template_id=" . $data_template["id"]);?>">
									<img class="buttonSmall" src="images/delete_icon.gif" alt="<?php print __("Delete");?>" align="right">
								</a>
							</td>
						<?php
						form_end_row();
					}
				}

				form_alternate_row_color("nodrag" . $data_template["id"], false, "nodrag nodrop");
				?>
					<td>
						<input type="text" name="svds_<?php print $data_template["id"];?>_field" size="15">
					</td>
					<td>
						<input type="text" name="svds_<?php print $data_template["id"];?>_text" size="30">
					</td>
					<td style="text-align:right;">
						<input type="submit" value="<?php print __("Add");?>" name="svds_<?php print $data_template["id"];?>_x">
					</td>
				<?php
				form_end_row();
				print "</table></td></tr>";		/* end of html_header */
			}
		}
		html_end_box(false);

		/* we need a new javascript for each table */
		if (sizeof($data_templates) > 0) {
			foreach ($data_templates as $data_template) {
				print("<script type='text/javascript'>
						$('#data_template_suggested_values_" . $data_template["id"] . "').tableDnD({
								onDrop: function(table, row) {
									$.get(\"data_queries.php?action=ajax_dt_save&dt_id=" . $data_template["id"] . "&gt_id=" . $_GET["id"] . "&\"+$.tableDnD.serialize());
								}
							});
						</script>\n");
			}
		}

		/* suggested values for graphs templates */
		$suggested_values = db_fetch_assoc("select
			text,
			field_name,
			id
			from snmp_query_graph_sv
			where snmp_query_graph_id=" . $_GET["id"] . "
			order by field_name,sequence");

		html_start_box(__("Suggested Values: Graph Templates"), "100", 0, "center", "");
		$header_items = array(
			array("name" => __("Graph Template") . " - " . db_fetch_cell("select name from graph_templates where id=" . $snmp_query_item["graph_template_id"]), "&nbsp;")
		);

		print "<tr><td>";
		html_header($header_items, 3, false, 'graph_template_suggested_values_' . get_request_var("id"), 'left wp100');

		if (sizeof($suggested_values) > 0) {
			foreach ($suggested_values as $suggested_value) {
				form_alternate_row_color($suggested_value["id"], true);
				?>
					<td>
						<strong><?php print $suggested_value["field_name"];?></strong>
					</td>
					<td>
						<?php print $suggested_value["text"];?>
					</td>
					<td align="right" style="text-align:right">
						<a href="<?php print htmlspecialchars("data_queries.php?action=item_remove_gsv&snmp_query_graph_id=" . $_GET["id"] . "&id=" . $suggested_value["id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);?>">
							<img class="buttonSmall" src="images/delete_icon.gif" alt="<?php print __("Delete");?>" align="right">
						</a>
					</td>
				<?php
				form_end_row();
			}
		}

		form_alternate_row_color("nodrag", false, "nodrag nodrop");
		?>
			<td>
				<input type="text" name="svg_field" size="15">
			</td>
			<td>
				<input type="text" name="svg_text" size="30">
			</td>
			<td style="text-align:right;">
				<input type="submit" value="<?php print __("Add");?>" name="svg_x">
			</td>
		<?php
		form_end_row();
		print "</table></td></tr>";
		html_end_box();
	}
	?>
	<script type="text/javascript">
	$('#graph_template_suggested_values_<?php print get_request_var("id");?>').tableDnD({
		onDrop: function(table, row) {
			$.get("data_queries.php?action=ajax_gt_save&gt_id=<?php print $_GET["id"];?>&"+$.tableDnD.serialize());
		}
	});
	</script>
	<?php

	form_save_button_alt("path!data_queries.php|action!edit|id!" . get_request_var("snmp_query_id"));
}

/* ---------------------
    Data Query Functions
   --------------------- */

function data_query_dt_dnd() {
	/* ================= Input validation ================= */
	input_validate_input_number(get_request_var("dt_id"));
	input_validate_input_number(get_request_var("gt_id"));
	/* ================= Input validation ================= */

	/* $_REQUEST variable depends on data template id */
	$_request_var = 'data_template_suggested_values_' . $_GET['dt_id'];

	if(!isset($_REQUEST[$_request_var]) || !is_array($_REQUEST[$_request_var])) exit;
	/* remove all rows not related to a suggested value */
	foreach ($_REQUEST[$_request_var] as $key => $value) {
		if (!is_numeric($value)) unset($_REQUEST[$_request_var][$key]);
	}
	$new_data = $_REQUEST[$_request_var]; /* array(seq => id) */

	$old_order = array();
	$new_order = array();


	/*
	 * get old sequence information
	 */
	$sql = "SELECT " .
				"id, " .
				"sequence, " .
				"field_name " .
				"FROM snmp_query_graph_rrd_sv " .
				"WHERE data_template_id=" . $_GET['dt_id'] . " " .
				"AND snmp_query_graph_id=" . $_GET['gt_id'] . " " .
				"ORDER BY field_name, sequence";
	$old_data = db_fetch_assoc($sql);

	/* rekey old data to get old_order*/
	if (sizeof($old_data)) {
		foreach($old_data as $item) {
			$old_order[$item["id"]]["field_name"] = $item["field_name"];
			$old_order[$item["id"]]["sequence"] = $item["sequence"];
		}
	} /* array(id => array(field_name, sequence)) */

	/*
	 * build new_order but take field_name into account!
	 */
	$sequence = array();							/* remember sequence for each field_name seperately 	*/
	foreach($new_data as $key => $id) {
		$fname = $old_order[$id]["field_name"];		/* this is the field we're working on 					*/
		if (!isset($sequence[$fname])) {
			$sequence[$fname] = 1; 					/* restart sequence_no each time a new field is found 	*/
		}

		if ($sequence[$fname] != $old_order[$id]["sequence"]) { 	/* sequence has been changed 			*/
			$new_order[$id] = $sequence[$fname];					/* remember this record for update		*/
		}
		$sequence[$fname]++;						/* increment sequence for current field					*/
	}


	/* ==================================================== */
	if(sizeof($new_order) == 0) exit;
	foreach($new_order as $id => $sequence) {
		# update the template item itself
		$sql = "UPDATE snmp_query_graph_rrd_sv SET sequence = $sequence WHERE id = $id";
		db_execute($sql);
	}
}

function data_query_gt_dnd() {
	/* ================= Input validation ================= */
	input_validate_input_number(get_request_var("gt_id"));
	/* ================= Input validation ================= */

	/* $_REQUEST variable depends on data template id */
	$_request_var = 'graph_template_suggested_values_' . $_GET['gt_id'];

	if(!isset($_REQUEST[$_request_var]) || !is_array($_REQUEST[$_request_var])) exit;
	/* remove all rows not related to a suggested value */
	foreach ($_REQUEST[$_request_var] as $key => $value) {
		if (!is_numeric($value)) unset($_REQUEST[$_request_var][$key]);
	}
	$new_data = $_REQUEST[$_request_var]; /* array(seq => id) */

	$old_order = array();
	$new_order = array();


	/*
	 * get old sequence information
	 */
	$sql = "SELECT " .
				"id, " .
				"sequence, " .
				"field_name " .
				"FROM snmp_query_graph_sv " .
				"WHERE snmp_query_graph_id=" . $_GET['gt_id'] . " " .
				"ORDER BY field_name, sequence";
	$old_data = db_fetch_assoc($sql);

	/* rekey old data to get old_order*/
	if (sizeof($old_data)) {
		foreach($old_data as $item) {
			$old_order[$item["id"]]["field_name"] = $item["field_name"];
			$old_order[$item["id"]]["sequence"] = $item["sequence"];
		}
	} /* array(id => array(field_name, sequence)) */



	/*
	 * build new_order but take field_name into account!
	 */
	$sequence = array();							/* remember sequence for each field_name seperately 	*/
	foreach($new_data as $key => $id) {
		$fname = $old_order[$id]["field_name"];		/* this is the field we're working on 					*/
		if (!isset($sequence[$fname])) {
			$sequence[$fname] = 1; 					/* restart sequence_no each time a new field is found 	*/
		}

		if ($sequence[$fname] != $old_order[$id]["sequence"]) { 	/* sequence has been changed 			*/
			$new_order[$id] = $sequence[$fname];					/* remember this record for update		*/
		}
		$sequence[$fname]++;						/* increment sequence for current field					*/
	}


	/* ==================================================== */
	if(sizeof($new_order) == 0) exit;
	foreach($new_order as $id => $sequence) {
		# update the template item itself
		$sql = "UPDATE snmp_query_graph_sv SET sequence = $sequence WHERE id = $id";
		db_execute($sql);
	}
}

function data_query_remove($id) {
	$snmp_query_graph = db_fetch_assoc("select id from snmp_query_graph where snmp_query_id=" . $id);

	if (sizeof($snmp_query_graph) > 0) {
	foreach ($snmp_query_graph as $item) {
		db_execute("delete from snmp_query_graph_rrd where snmp_query_graph_id=" . $item["id"]);
	}
	}

	db_execute("delete from snmp_query where id=" . $id);
	db_execute("delete from snmp_query_graph where snmp_query_id=" . $id);
	db_execute("delete from device_template_snmp_query where snmp_query_id=" . $id);
	db_execute("delete from device_snmp_query where snmp_query_id=" . $id);
	db_execute("delete from device_snmp_cache where snmp_query_id=" . $id);
}

function data_query_validate_cache() {
	$queries = db_fetch_assoc("SELECT * FROM snmp_query WHERE image=''");

	if (sizeof($queries)) {
	foreach($queries as $query) {
		data_query_update_cache($query["id"], data_query_get_image($query["image"]));
	}
	}
}

function data_query_update_cache($id, $image) {
	/* accomodate both URL and BASE paths */
	if (strpos($image, "'" . CACTI_URL_PATH . "'") == 0) {
		$image = str_replace(CACTI_URL_PATH, CACTI_BASE_PATH, $image);
	}

	copy($image, CACTI_CACHE_PATH . "/images/" . basename($image));
	db_execute("UPDATE snmp_query SET image='" . basename($image) . "' WHERE id=" . $id);
}

function data_query_get_image($image) {
	if ($image == '') {
		return CACTI_BASE_PATH . "/images/tree_icons/dataquery.png";
	}elseif (file_exists(CACTI_BASE_PATH . "/images/tree_icons/$image")){
		return CACTI_BASE_PATH . "/images/tree_icons/$image";
	}elseif (file_exists(CACTI_CACHE_PATH . "/images/$image")) {
		return CACTI_BASE_PATH . "/images/$image";
	}else{
		return CACTI_BASE_PATH . "/images/dataquery.png";
	}
}

function data_query_display_image($image) {
	return "<img src='" . CACTI_CACHE_URL_PATH . "/images/" . basename($image) . "' alt='' class='img_filter'>";
}

function data_query_edit() {
	require_once(CACTI_BASE_PATH . "/lib/data_query.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$snmp_query = db_fetch_row("select * from snmp_query where id=" . $_GET["id"]);
		$header_label = "[edit: " . $snmp_query["name"] . "]";
	}else{
		$header_label = "[new]";
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='data_query_edit'>\n";
	html_start_box(__("Data Queries") . " $header_label", "100", 0, "center", "");

	draw_edit_form(array(
		"config" => array("no_form_tag" => true),
		"fields" => inject_form_variables(data_query_form_list(), (isset($snmp_query) ? $snmp_query : array()))
		));

	print "</table></td></tr>";		/* end of html_header */
	html_end_box();

	if (!empty($snmp_query["id"])) {
		$xml_filename = str_replace("<path_cacti>", CACTI_BASE_PATH, $snmp_query["xml_path"]);

		if ((file_exists($xml_filename)) && (is_file($xml_filename))) {
			$text = "<font color='#0d7c09'><strong>" . __("Successfully located XML file") . "</strong></font>";
			$xml_file_exists = true;
		}else{
			$text = "<span class='warning'>" . __("Could not locate XML file.") . "</span>";
			$xml_file_exists = false;
		}

		html_start_box("", "100", "3", "center", "");
		print "<tr class='textArea'><td>$text</td></tr>";
		html_end_box();

		if ($xml_file_exists == true) {
			html_start_box(__("Associated Graph Templates"), "100", "0", "center", "data_queries.php?action=item_edit&snmp_query_id=" . $snmp_query["id"]);
			$header_items = array(
				array("name" => __("Name")),
				array("name" => __("Graph Template Name")));

			print "<tr><td>";
			html_header($header_items, 2, false, 'assoc_graph_templates', 'left wp100');

			$snmp_query_graphs = db_fetch_assoc("select
				snmp_query_graph.id,
				graph_templates.name as graph_template_name,
				snmp_query_graph.name
				from snmp_query_graph
				left join graph_templates on (snmp_query_graph.graph_template_id=graph_templates.id)
				where snmp_query_graph.snmp_query_id=" . $snmp_query["id"] . "
				order by snmp_query_graph.name");

			if (sizeof($snmp_query_graphs) > 0) {
			foreach ($snmp_query_graphs as $snmp_query_graph) {
				form_alternate_row_color("id" . $snmp_query["id"] . "_" . $snmp_query_graph["id"], true);
				?>
					<td>
						<strong><a href="<?php print htmlspecialchars("data_queries.php?action=item_edit&id=". $snmp_query_graph["id"] . "&snmp_query_id=" . $snmp_query["id"]);?>"><?php print $snmp_query_graph["name"];?></a></strong>
					</td>
					<td>
						<?php print $snmp_query_graph["graph_template_name"];?>
					</td>
					<td align="right" style="text-align:right">
						<a href="<?php print htmlspecialchars("data_queries.php?action=item_remove&id=" . $snmp_query_graph["id"] . "&snmp_query_id=" . $snmp_query["id"]);?>">
							<img class="buttonSmall" src="images/delete_icon.gif" alt="<?php print __("Delete");?>" align="right">
						</a>
					</td>
				<?php
				form_end_row();
			}
			}else{
				print "<tr><td><em>" . __("No Graph Templates Defined.") . "</em></td></tr>";
			}

			print "</table></td></tr>";		/* end of html_header */
			html_end_box();
		}
	}

	form_save_button("data_queries.php", "return");
}

function data_queries_filter() {
	global $item_rows;

	html_start_box(__("Data Queries"), "100", "3", "center", "data_queries.php?action=edit", true);
	?>
	<tr class="rowAlternate3 noprint">
		<td class="noprint">
			<form name="form_graph_id" action="data_queries.php">
			<table cellpadding="0" cellspacing="3">
				<tr class="noprint">
					<td class="nw50">
						&nbsp;<?php print __("Search:");?>&nbsp;
					</td>
					<td width="1">
						<input type="text" name="filter" size="40" value="<?php print html_get_page_variable("filter");?>">
					</td>
					<td class="nw50">
						&nbsp;<?php print __("Rows:");?>&nbsp;
					</td>
					<td width="1">
						<select name="rows" onChange="applyFilterChange(document.form_graph_id)">
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

function get_data_query_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	if (strlen(html_get_page_variable("filter"))) {
		$sql_where = "WHERE (snmp_query.name LIKE '%%" . html_get_page_variable("filter") . "%%'
			OR data_input.name LIKE '%%" . html_get_page_variable("filter") . "%%')
			OR snmp_query.description LIKE '%%" . html_get_page_variable("filter") . "%%'";
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
		FROM snmp_query
		INNER JOIN data_input
		ON (snmp_query.data_input_id=data_input.id)
		$sql_where");

	return db_fetch_assoc("SELECT
		snmp_query.id,
		snmp_query.name,
		snmp_query.description,
		snmp_query.image,
		data_input.name AS data_input_method
		FROM snmp_query
		INNER JOIN data_input
		ON (snmp_query.data_input_id=data_input.id)
		$sql_where
		ORDER BY " . html_get_page_variable('sort_column') . " " . html_get_page_variable('sort_direction') . "
		LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp);
}

function data_query($refresh = true) {
	global $dq_actions;

	data_query_validate_cache();

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
			"link" => true,
			"filter" => true,
			"order" => "ASC"
		),
		"description" => array(
			"name" => __("Description"),
			"filter" => true,
			"order" => "ASC"
		),
		"id" => array(
			"name" => __("ID"),
			"align" => "right",
			"order" => "ASC"
		),
		"image" => array(
			"name" => __("Image"),
			"sort" => false,
			"function" => "data_query_display_image",
			"params" => array("image"),
			"align" => "center"
		),
		"data_input_method" => array(
			"name" => __("Data Input Method"),
			"filter" => true,
			"order" => "ASC"
		)
	);

	/* initialize page behavior */
	$table->href           = "data_queries.php";
	$table->session_prefix = "sess_data_queries";
	$table->filter_func    = "data_queries_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = $dq_actions;

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_data_query_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}

?>
