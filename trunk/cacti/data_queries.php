<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2003 Ian Berry                                            |
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
 | cacti: a php-based graphing solution                                    |
 +-------------------------------------------------------------------------+
 | Most of this code has been designed, written and is maintained by       |
 | Ian Berry. See about.php for specific developer credit. Any questions   |
 | or comments regarding this code should be directed to:                  |
 | - iberry@raxnet.net                                                     |
 +-------------------------------------------------------------------------+
 | - raXnet - http://www.raxnet.net/                                       |
 +-------------------------------------------------------------------------+
*/

include ('include/auth.php');
include_once ("include/functions.php");
include_once ("include/config_arrays.php");
include_once ('include/form.php');

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch ($_REQUEST["action"]) {
	case 'save':
		form_save();
		
		break;
	case 'item_moveup_dssv':
		snmp_item_moveup_dssv();
		
		header ("Location: snmp.php?action=item_edit&id=" . $_GET["snmp_query_graph_id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_movedown_dssv':
		snmp_item_movedown_dssv();
		
		header ("Location: snmp.php?action=item_edit&id=" . $_GET["snmp_query_graph_id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_remove_dssv':
		snmp_item_remove_dssv();
		
		header ("Location: snmp.php?action=item_edit&id=" . $_GET["snmp_query_graph_id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_moveup_gsv':
		snmp_item_moveup_gsv();
		
		header ("Location: snmp.php?action=item_edit&id=" . $_GET["snmp_query_graph_id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_movedown_gsv':
		snmp_item_movedown_gsv();
		
		header ("Location: snmp.php?action=item_edit&id=" . $_GET["snmp_query_graph_id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_remove_gsv':
		snmp_item_remove_gsv();
		
		header ("Location: snmp.php?action=item_edit&id=" . $_GET["snmp_query_graph_id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		break;
	case 'item_remove':
		snmp_item_remove();
		
		break;
	case 'item_edit':
		include_once ("include/top_header.php");
		
		snmp_item_edit();
		
		include_once ("include/bottom_footer.php");
		break;
	case 'remove':
		snmp_remove();
		
		header ("Location: snmp.php");
		break;
	case 'edit':
		include_once ("include/top_header.php");
		
		snmp_edit();
		
		include_once ("include/bottom_footer.php");
		break;
	default:
		include_once ("include/top_header.php");
		
		snmp();
		
		include_once ("include/bottom_footer.php");
		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if (isset($_POST["save_component_snmp_query"])) {
		$save["id"] = $_POST["id"];
		$save["name"] = form_input_validate($_POST["name"], "name", "", false, 3);
		$save["description"] = form_input_validate($_POST["description"], "description", "", true, 3);
		$save["xml_path"] = form_input_validate($_POST["xml_path"], "xml_path", "", false, 3);
		$save["data_input_id"] = $_POST["data_input_id"];
		
		if (!is_error_message()) {
			$snmp_query_id = sql_save($save, "snmp_query");
			
			if ($snmp_query_id) {
				raise_message(1);
				
				db_execute ("delete from snmp_query_field where snmp_query_id=$snmp_query_id");
				
				while (list($var, $val) = each($_POST)) {
					if (eregi("^mdt_([0-9]+)_check", $var)) {
						$data_input_field_id = ereg_replace("^mdt_([0-9]+).+", "\\1", $var);
						
						db_execute ("replace into snmp_query_field (snmp_query_id,data_input_field_id,action_id) values($snmp_query_id,$data_input_field_id,'" . $_POST{"mdt_" . $data_input_field_id . "_action_id"} . "')");
					}
				}
			}else{
				raise_message(2);
			}
		}
		
		if ((is_error_message()) || (empty($_POST["id"]))) {
			header ("Location: snmp.php?action=edit&id=" . (empty($snmp_query_id) ? $_POST["id"] : $snmp_query_id));
		}else{
			header ("Location: snmp.php");
		}
	}elseif (isset($_POST["save_component_snmp_query_item"])) {
		$redirect_back = false;
		
		$save["id"] = $_POST["id"];
		$save["snmp_query_id"] = $_POST["snmp_query_id"];
		$save["name"] = form_input_validate($_POST["name"], "name", "", false, 3);
		$save["graph_template_id"] = $_POST["graph_template_id"];
		
		if (!is_error_message()) {
			$snmp_query_graph_id = sql_save($save, "snmp_query_graph");	
			
			if ($snmp_query_graph_id) {
				raise_message(1);
				
				db_execute ("delete from snmp_query_graph_rrd where snmp_query_graph_id=$snmp_query_graph_id");
				
				while (list($var, $val) = each($_POST)) {
					if (eregi("^dsdt_([0-9]+)_([0-9]+)_check", $var)) {
						$data_template_id = ereg_replace("^dsdt_([0-9]+)_([0-9]+).+", "\\1", $var);
						$data_template_rrd_id = ereg_replace("^dsdt_([0-9]+)_([0-9]+).+", "\\2", $var);
						
						db_execute ("replace into snmp_query_graph_rrd (snmp_query_graph_id,data_template_id,data_template_rrd_id,snmp_field_name) values($snmp_query_graph_id,$data_template_id,$data_template_rrd_id,'" . $_POST{"dsdt_" . $data_template_id . "_" . $data_template_rrd_id . "_snmp_field_output"} . "')");
					}elseif ((eregi("^svds_([0-9]+)_x", $var, $matches)) && (!empty($_POST{"svds_" . $matches[1] . "_text"})) && (!empty($_POST{"svds_" . $matches[1] . "_field"}))) {
						/* suggested values -- data templates */
						$sequence = get_sequence(0, "sequence", "snmp_query_graph_rrd_sv", "snmp_query_graph_id=" . $_POST["id"]  . " and data_template_id=" . $matches[1] . " and field_name='" . $_POST{"svds_" . $matches[1] . "_field"} . "'");
						db_execute("insert into snmp_query_graph_rrd_sv (snmp_query_graph_id,data_template_id,sequence,field_name,text) values (" . $_POST["id"] . "," . $matches[1] . ",$sequence,'" . $_POST{"svds_" . $matches[1] . "_field"} . "','" . $_POST{"svds_" . $matches[1] . "_text"} . "')"); 
						
						$redirect_back = true;
						clear_messages();
					}elseif ((eregi("^svg_x", $var)) && (!empty($_POST{"svg_text"})) && (!empty($_POST{"svg_field"}))) {
						/* suggested values -- graph templates */
						$sequence = get_sequence(0, "sequence", "snmp_query_graph_sv", "snmp_query_graph_id=" . $_POST["id"] . " and field_name='" . $_POST{"svg_field"} . "'");
						db_execute("insert into snmp_query_graph_sv (snmp_query_graph_id,sequence,field_name,text) values (" . $_POST["id"] . ",$sequence,'" . $_POST{"svg_field"} . "','" . $_POST{"svg_text"} . "')"); 
						
						$redirect_back = true;
						clear_messages();
					}
				}
			}else{
				raise_message(2);
			}
		}
		
		if ((is_error_message()) || (empty($_POST["id"])) || ($redirect_back == true)) {
			header ("Location: snmp.php?action=item_edit&id=" . (empty($snmp_query_graph_id) ? $_POST["id"] : $snmp_query_graph_id) . "&snmp_query_id=" . $_POST["snmp_query_id"]);
		}else{
			header ("Location: snmp.php?action=edit&id=" . $_POST["snmp_query_id"]);
		}
	}
}

/* ----------------------------
    SNMP Query Graph Functions
   ---------------------------- */

function snmp_item_movedown_gsv() {
	move_item_down("snmp_query_graph_sv", $_GET["id"], "snmp_query_graph_id=" . $_GET["snmp_query_graph_id"] . " and field_name=" . $_GET["field_name"]);
}

function snmp_item_moveup_gsv() {
	move_item_up("snmp_query_graph_sv", $_GET["id"], "snmp_query_graph_id=" . $_GET["snmp_query_graph_id"] . " and field_name=" . $_GET["field_name"]);
}

function snmp_item_remove_gsv() {
	db_execute("delete from snmp_query_graph_sv where id=" . $_GET["id"]);
}

function snmp_item_movedown_dssv() {
	move_item_down("snmp_query_graph_rrd_sv", $_GET["id"], "data_template_id=" . $_GET["data_template_id"] . " and snmp_query_graph_id=" . $_GET["snmp_query_graph_id"] . " and field_name=" . $_GET["field_name"]);
}

function snmp_item_moveup_dssv() {
	move_item_up("snmp_query_graph_rrd_sv", $_GET["id"], "data_template_id=" . $_GET["data_template_id"] . " and snmp_query_graph_id=" . $_GET["snmp_query_graph_id"] . " and field_name=" . $_GET["field_name"]);
}

function snmp_item_remove_dssv() {
	db_execute("delete from snmp_query_graph_rrd_sv where id=" . $_GET["id"]);
}

function snmp_item_remove() {
	if ((read_config_option("remove_verification") == "on") && (!isset($_GET["confirm"]))) {
		include ('include/top_header.php');
		form_confirm("Are You Sure?", "Are you sure you want to delete the SNMP Query Graph <strong>'" . db_fetch_cell("select name from snmp_query_graph where id=" . $_GET["id"]) . "'</strong>?", $_SERVER["HTTP_REFERER"], "snmp.php?action=remove&id=" . $_GET["id"] . "&snmp_query_id=" . $_GET["snmp_query_id"]);
		include ('include/bottom_footer.php');
		exit;
	}
	
	if ((read_config_option("remove_verification") == "") || (isset($_GET["confirm"]))) {
		db_execute("delete from snmp_query_graph where id=" . $_GET["id"]);
		db_execute("delete from snmp_query_graph_rrd where snmp_query_graph_id=" . $_GET["id"]);
	}
}

function snmp_item_edit() {
	include_once ("include/xml_functions.php");
	
	global $colors, $paths;
	
	if (!empty($_GET["id"])) {
		$snmp_query_item = db_fetch_row("select * from snmp_query_graph where id=" . $_GET["id"]);
	}
	
	$snmp_query = db_fetch_row("select name,xml_path from snmp_query where id=" . $_GET["snmp_query_id"]);
	$header_label = "[edit: " . $snmp_query["name"] . "]";
	
	start_box("<strong>Associated Graph/Data Templates</strong> $header_label", "98%", $colors["header"], "3", "center", "");
	
	?>
	<form method="post" action="snmp.php">
	
	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">Name</font><br>
			A name for this associated graph.
		</td>
		<?php form_text_box("name",(isset($snmp_query_item) ? $snmp_query_item["name"] : ""),"","100", "40");?>
	</tr>
	
	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Graph Template</font><br>
			Choose a graph template to use for this association.
		</td>
		<?php form_dropdown("graph_template_id",db_fetch_assoc("select id,name from graph_templates order by name"),"name","id",(isset($snmp_query_item) ? $snmp_query_item["graph_template_id"] : "0"),"","");?>
	</tr>
	<?php
	end_box();
	
	start_box("<strong>Associated Data Templates</strong>", "98%", $colors["header"], "3", "center", "");
	
	if (!empty($snmp_query_item["id"])) {
		$data_templates = db_fetch_assoc("select
			data_template.id,
			data_template.name
			from data_template, data_template_rrd, graph_templates_item
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
			print "	<tr bgcolor='#" . $colors["header_panel"] . "'>
					<td><span style='color: white; font-weight: bold;'>Data Template - " . $data_template["name"] . "</span></td>
				</tr>";
			
			$data_template_rrds = db_fetch_assoc("select
				data_template_rrd.id,
				data_template_rrd.data_source_name,
				snmp_query_graph_rrd.snmp_field_name,
				snmp_query_graph_rrd.snmp_query_graph_id
				from data_template_rrd
				left join snmp_query_graph_rrd on (snmp_query_graph_rrd.data_template_rrd_id=data_template_rrd.id and snmp_query_graph_rrd.snmp_query_graph_id=" . $_GET["id"] . " and snmp_query_graph_rrd.data_template_id=" . $data_template["id"] . ")
				where data_template_rrd.data_template_id=" . $data_template["id"] . "
				and data_template_rrd.local_data_id=0
				order by data_template_rrd.data_source_name");
			
			$i = 0;
			if (sizeof($data_template_rrds) > 0) {
			foreach ($data_template_rrds as $data_template_rrd) {
				if (empty($data_template_rrd["snmp_query_graph_id"])) {
					$old_value = "";
				}else{
					$old_value = "on";
				}
				
				form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],$i); $i++;
				?>
					<td>
						<table cellspacing="0" cellpadding="0" border="0" width="100%">
							<tr>
								<td width="200">
									<strong>Data Source:</strong>
								</td>
								<td width="200">
									<?php print $data_template_rrd["data_source_name"];?>
								</td>
								<td width="1">
									<?php
									$data = implode("",file(str_replace("<path_cacti>", $paths["cacti"], $snmp_query["xml_path"])));
									$snmp_queries = xml2array($data);
									$xml_outputs = array();
									
									while (list($field_name, $field_array) = each($snmp_queries["fields"][0])) {
										$field_array = $field_array[0];
										
										if ($field_array["direction"] == "output") {
											$xml_outputs[$field_name] = $field_name . " (" . $field_array["name"] . ")";;	
										}
									}
									
									form_base_dropdown("dsdt_" . $data_template["id"] . "_" . $data_template_rrd["id"] . "_snmp_field_output",$xml_outputs,"","",$data_template_rrd["snmp_field_name"],"","");?>
								</td>
								<td align="right">
									<?php form_base_checkbox("dsdt_" . $data_template["id"] . "_" . $data_template_rrd["id"] . "_check", $old_value, "", "",$_GET["id"],true);?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<?php
			}
			}
		}
		}
		
		end_box();
		
		start_box("<strong>Suggested Values</strong>", "98%", $colors["header"], "3", "center", "");
		
		reset($data_templates);
		
		/* suggested values for data templates */
		if (sizeof($data_templates) > 0) {
		foreach ($data_templates as $data_template) {
			$suggested_values = db_fetch_assoc("select
				text,
				field_name,
				id
				from snmp_query_graph_rrd_sv
				where snmp_query_graph_id=" . $_GET["id"] . "
				and data_template_id=" . $data_template["id"] . "
				order by field_name,sequence");
			
			print "	<tr bgcolor='#" . $colors["header_panel"] . "'>
					<td><span style='color: white; font-weight: bold;'>Data Template - " . $data_template["name"] . "</span></td>
				</tr>";
			
			$i = 0;
			if (sizeof($suggested_values) > 0) {
			foreach ($suggested_values as $suggested_value) {
				form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],$i); $i++;
				?>
					<td>
						<table cellspacing="0" cellpadding="0" border="0" width="100%">
							<tr>
								<td width="80">
									<strong><?php print $suggested_value["field_name"];?></strong>
								</td>
								<td>
									<?php print $suggested_value["text"];?>
								</td>
								<td width="70">
									<a href="snmp.php?action=item_movedown_dssv&snmp_query_graph_id=<?php print $_GET["id"];?>&id=<?php print $suggested_value["id"];?>&snmp_query_id=<?php print $_GET["snmp_query_id"];?>&data_template_id=<?php print $data_template["id"];?>&field_name=<?php print $suggested_value["field_name"];?>"><img src="images/move_down.gif" border="0" alt="Move Down"></a>
									<a href="snmp.php?action=item_moveup_dssv&snmp_query_graph_id=<?php print $_GET["id"];?>&id=<?php print $suggested_value["id"];?>&snmp_query_id=<?php print $_GET["snmp_query_id"];?>&data_template_id=<?php print $data_template["id"];?>&field_name=<?php print $suggested_value["field_name"];?>"><img src="images/move_up.gif" border="0" alt="Move Up"></a>
								</td>
								<td width="1%" align="right">
									<a href="snmp.php?action=item_remove_dssv&snmp_query_graph_id=<?php print $_GET["id"];?>&id=<?php print $suggested_value["id"];?>&snmp_query_id=<?php print $_GET["snmp_query_id"];?>&data_template_id=<?php print $data_template["id"];?>"><img src="images/delete_icon.gif" width="10" height="10" border="0" alt="Delete"></a>&nbsp;
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<?php
			}
			}
			
			form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],$i);
			?>
				<td>
					<table cellspacing="0" cellpadding="0" border="0" width="100%">
						<tr>
							<td width="1">
								<input type="text" name="svds_<?php print $data_template["id"];?>_text" size="30">
							</td>
							<td width="200">
								&nbsp;Field Name: <input type="text" name="svds_<?php print $data_template["id"];?>_field" size="15">
							</td>
							<td>
								&nbsp;<input type="image" src="images/button_add.gif" name="svds_<?php print $data_template["id"];?>" alt="Add" align="absmiddle">
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<?php
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
		
		print "	<tr bgcolor='#" . $colors["header_panel"] . "'>
				<td><span style='color: white; font-weight: bold;'>Graph Template - " . db_fetch_cell("select name from graph_templates where id=" . $snmp_query_item["graph_template_id"]) . "</span></td>
			</tr>";
		
		$i = 0;
		if (sizeof($suggested_values) > 0) {
		foreach ($suggested_values as $suggested_value) {
			form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],$i); $i++;
			?>
				<td>
					<table cellspacing="0" cellpadding="0" border="0" width="100%">
						<tr>
							<td width="80">
								<strong><?php print $suggested_value["field_name"];?></strong>
							</td>
							<td>
								<?php print $suggested_value["text"];?>
							</td>
							<td width="70">
								<a href="snmp.php?action=item_movedown_gsv&snmp_query_graph_id=<?php print $_GET["id"];?>&id=<?php print $suggested_value["id"];?>&snmp_query_id=<?php print $_GET["snmp_query_id"];?>&field_name=<?php print $suggested_value["field_name"];?>"><img src="images/move_down.gif" border="0" alt="Move Down"></a>
								<a href="snmp.php?action=item_moveup_gsv&snmp_query_graph_id=<?php print $_GET["id"];?>&id=<?php print $suggested_value["id"];?>&snmp_query_id=<?php print $_GET["snmp_query_id"];?>&field_name=<?php print $suggested_value["field_name"];?>"><img src="images/move_up.gif" border="0" alt="Move Up"></a>
							</td>
							<td width="1%" align="right">
								<a href="snmp.php?action=item_remove_gsv&snmp_query_graph_id=<?php print $_GET["id"];?>&id=<?php print $suggested_value["id"];?>&snmp_query_id=<?php print $_GET["snmp_query_id"];?>"><img src="images/delete_icon.gif" width="10" height="10" border="0" alt="Delete"></a>&nbsp;
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<?php
		}
		}
		
		form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],$i);
		?>
			<td>
				<table cellspacing="0" cellpadding="0" border="0" width="100%">
					<tr>
						<td width="1">
							<input type="text" name="svg_text" size="30">
						</td>
						<td width="200">
							&nbsp;Field Name: <input type="text" name="svg_field" size="15">
						</td>
						<td>
							&nbsp;<input type="image" src="images/button_add.gif" name="svg" alt="Add" align="absmiddle">
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
		
		end_box();
	}
	
	form_hidden_id("id",(isset($snmp_query_item) ? $snmp_query_item["id"] : "0"));
	form_hidden_id("snmp_query_id",$_GET["snmp_query_id"]);
	form_hidden_box("save_component_snmp_query_item","1","");
	
	form_save_button("snmp.php?action=edit&id=" . $_GET["snmp_query_id"]);
}

/* ---------------------
    SNMP Query Functions
   --------------------- */

function snmp_remove() {
	if ((read_config_option("remove_verification") == "on") && (!isset($_GET["confirm"]))) {
		include ('include/top_header.php');
		form_confirm("Are You Sure?", "Are you sure you want to delete the SNMP Query <strong>'" . db_fetch_cell("select name from snmp_query where id=" . $_GET["id"]) . "'</strong>?", $_SERVER["HTTP_REFERER"], "snmp.php?action=remove&id=" . $_GET["id"]);
		include ('include/bottom_footer.php');
		exit;
	}
	
	if ((read_config_option("remove_verification") == "") || (isset($_GET["confirm"]))) {
		$snmp_query_graph = db_fetch_assoc("select id from snmp_query_graph where snmp_query_id=" . $_GET["id"]);
		
		if (sizeof($snmp_query_graph) > 0) {
		foreach ($snmp_query_graph as $item) {
			db_execute("delete from snmp_query_graph_rrd where snmp_query_graph_id=" . $item["id"]);
		}
		}
		
		db_execute("delete from snmp_query where id=" . $_GET["id"]);
		db_execute("delete from snmp_query_field where snmp_query_id=" . $_GET["id"]);
		db_execute("delete from snmp_query_graph where snmp_query_id=" . $_GET["id"]);
		db_execute("delete from host_template_snmp_query where snmp_query_id=" . $_GET["id"]);
		db_execute("delete from host_snmp_query where snmp_query_id=" . $_GET["id"]);
		db_execute("delete from host_snmp_cache where snmp_query_id=" . $_GET["id"]);
	}
}

function snmp_edit() {
	include_once ("include/xml_functions.php");
	
	global $colors, $paths, $snmp_query_field_actions;
	
	if (!empty($_GET["id"])) {
		$snmp_query = db_fetch_row("select * from snmp_query where id=" . $_GET["id"]);
		$header_label = "[edit: " . $snmp_query["name"] . "]";
	}else{
		$header_label = "[new]";
	}
	
	start_box("<strong>SNMP Queries</strong> $header_label", "98%", $colors["header"], "3", "center", "");
	
	?>
	<form method="post" action="snmp.php">
	
	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">Name</font><br>
			A name for this SNMP query.
		</td>
		<?php form_text_box("name",(isset($snmp_query) ? $snmp_query["name"] : ""),"","100", "40");?>
	</tr>
	
	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Description</font><br>
			A description for this SNMP query.
		</td>
		<?php form_text_box("description",(isset($snmp_query) ? $snmp_query["description"] : ""),"","255", "40");?>
	</tr>
	
	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],0); ?>
		<td width="50%">
			<font class="textEditTitle">XML Path</font><br>
			The full path to the XML file containing definitions for this snmp query.
		</td>
		<?php form_text_box("xml_path",(isset($snmp_query) ? $snmp_query["xml_path"] : ""),"<path_cacti>/resource/","255", "40");?>
	</tr>
	
	<?php form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],1); ?>
		<td width="50%">
			<font class="textEditTitle">Data Input Method</font><br>
			Select the data input method that will store/execute the data for this query.
		</td>
		<?php form_dropdown("data_input_id",db_fetch_assoc("select id,name from data_input order by name"),"name","id",(isset($snmp_query) ? $snmp_query["data_input_id"] : "0"),"","");?>
	</tr>
	
	<?php
	form_hidden_id("id",(isset($snmp_query) ? $snmp_query["id"] : "0"));
	end_box();
	
	if (!empty($snmp_query["id"])) {
		start_box("", "98%", "aaaaaa", "3", "center", "");
		print "<tr bgcolor='#f5f5f5'><td>" . (file_exists(str_replace("<path_cacti>", $paths["cacti"], $snmp_query["xml_path"])) ? "<font color='#0d7c09'><strong>XML File Exists</strong></font>" : "<font color='#ff0000'><strong>XML File Does Not Exist</strong></font>") . "</td></tr>";
		end_box();
		
		start_box("<strong>Data Input Method</strong> [" . db_fetch_cell("select name from data_input where id=" . $snmp_query["data_input_id"]) . "]", "98%", $colors["header"], "3", "center", "");
		
		print "	<tr bgcolor='#" . $colors["header_panel"] . "'>
				<td><span style='color: white; font-weight: bold;'>Data Input Field -> SNMP Field Action Mappings</span></td>
			</tr>";
		
		$fields = db_fetch_assoc("select
			data_input_fields.id,
			data_input_fields.name,
			snmp_query_field.action_id,
			snmp_query_field.snmp_query_id
			from data_input_fields
			left join snmp_query_field on (snmp_query_field.data_input_field_id=data_input_fields.id and snmp_query_field.snmp_query_id=" . $_GET["id"] . ")
			where data_input_fields.data_input_id=" . $snmp_query["data_input_id"] . "
			order by data_input_fields.name");
		
		$i = 0;
		if (sizeof($fields) > 0) {
		foreach ($fields as $field) {
			if (empty($field["snmp_query_id"])) {
				$old_value = "";
			}else{
				$old_value = "on";
			}
			
			form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],$i); $i++;
			?>
				<td colspan="3">
					<table cellspacing="0" cellpadding="0" border="0" width="100%">
						<tr>
							<td width="200">
								<strong>Data Input Field:</strong>
							</td>
							<td width="200">
								<?php print $field["name"];?>
							</td>
							<td width="1">
								<?php form_base_dropdown("mdt_" . $field["id"] . "_action_id",$snmp_query_field_actions,"","",$field["action_id"],"","");?>
							</td>
							<td align="right">
								<?php form_base_checkbox("mdt_" . $field["id"] . "_check", $old_value, "", "",$_GET["id"],true);?>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<?php
		}
		}
		
		end_box();
		
		start_box("<strong>Associated Graph/Data Templates</strong>", "98%", $colors["header"], "3", "center", "snmp.php?action=item_edit&snmp_query_id=" . $snmp_query["id"]);
		
		print "	<tr bgcolor='#" . $colors["header_panel"] . "'>
				<td><span style='color: white; font-weight: bold;'>Name</span></td>
				<td><span style='color: white; font-weight: bold;'>Graph Template Name</span></td>
				<td></td>
			</tr>";
		
		$snmp_query_graphs = db_fetch_assoc("select
			snmp_query_graph.id,
			graph_templates.name as graph_template_name,
			snmp_query_graph.name
			from snmp_query_graph
			left join graph_templates on snmp_query_graph.graph_template_id=graph_templates.id
			where snmp_query_graph.snmp_query_id=" . $snmp_query["id"]);
		
		$i = 0;
		if (sizeof($snmp_query_graphs) > 0) {
		foreach ($snmp_query_graphs as $snmp_query_graph) {
			form_alternate_row_color($colors["form_alternate1"],$colors["form_alternate2"],$i); $i++;
			?>
				<td>
					<strong><a href="snmp.php?action=item_edit&id=<?php print $snmp_query_graph["id"];?>&snmp_query_id=<?php print $snmp_query["id"];?>"><?php print $snmp_query_graph["name"];?></a></strong>
				</td>
				<td>
					<?php print $snmp_query_graph["graph_template_name"];?>
				</td>
				<td width="1%" align="right">
					<a href="snmp.php?action=item_remove&id=<?php print $snmp_query_graph["id"];?>&snmp_query_id=<?php print $snmp_query["id"];?>"><img src="images/delete_icon.gif" width="10" height="10" border="0" alt="Delete"></a>&nbsp;
				</td>
			</tr>
			<?php
		}
		}else{
			print "<tr><td><em>No Graph Templates Defined.</em></td></tr>";
		}
		
		end_box();
	}
	
	form_hidden_box("save_component_snmp_query","1","");
	
	form_save_button("snmp.php");
}

function snmp() {
	global $colors;
	
	start_box("<strong>SNMP Queries</strong>", "98%", $colors["header"], "3", "center", "snmp.php?action=edit");
	
	print "<tr bgcolor='#" . $colors["header_panel"] . "'>";
		DrawMatrixHeaderItem("Name",$colors["header_text"],1);
		DrawMatrixHeaderItem("&nbsp;",$colors["header_text"],1);
	print "</tr>";
    	
	$snmp_queries = db_fetch_assoc("select id,name from snmp_query order by name");
	
	$i = 0;
	if (sizeof($snmp_queries) > 0) {
	foreach ($snmp_queries as $snmp_query) {
		form_alternate_row_color($colors["alternate"],$colors["light"],$i); $i++;
			?>
			<td>
				<a class="linkEditMain" href="snmp.php?action=edit&id=<?php print $snmp_query["id"];?>"><?php print $snmp_query["name"];?></a>
			</td>
			<td width="1%" align="right">
				<a href="snmp.php?action=remove&id=<?php print $snmp_query["id"];?>"><img src="images/delete_icon.gif" width="10" height="10" border="0" alt="Delete"></a>&nbsp;
			</td>
		</tr>
	<?php
	}
	}
	end_box();	
}
?>
