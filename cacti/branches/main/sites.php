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

define("MAX_DISPLAY_PAGES", 21);

$site_actions = array(
	ACTION_NONE => __("None"),
	"1" => __("Delete")
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
		include_once("./include/top_header.php");

		site_edit();

		include_once("./include/bottom_footer.php");
		break;
	case 'ajax_view':
		site();

		break;
	default:
		if (isset($_REQUEST["export_sites_x"])) {
			site_export();
		}else{
			include_once("./include/top_header.php");
			site();
			include_once("./include/bottom_footer.php");
		}

		break;
}

/* --------------------------
    The Save Function
   -------------------------- */

function form_save() {
	if ((isset($_POST["save_component_site"])) && (empty($_POST["add_dq_y"]))) {
		$id = site_save($_POST["id"], $_POST["name"], $_POST["alternate_id"], $_POST["address1"],
		get_request_var_post("address2"), get_request_var_post("city"), get_request_var_post("state"), get_request_var_post("postal_code"),
		get_request_var_post("country"), get_request_var_post("notes"));

		if ((is_error_message()) || ($_POST["id"] != $_POST["hidden_id"])) {
			header("Location: sites.php?action=edit&id=" . (empty($id) ? $_POST["id"] : $id));
		}else{
			header("Location: sites.php");
		}
		exit;
	}
}

/* ------------------------
    The "actions" function
   ------------------------ */

function form_actions() {
	global $site_actions;

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		$selected_items = unserialize(stripslashes($_POST["selected_items"]));

		if (get_request_var_post("drp_action") === "1") { /* delete */
			/* do a referential integrity check */
			if (sizeof($selected_items)) {
			foreach($selected_items as $site_id) {
				/* ================= input validation ================= */
				input_validate_input_number($site_id);
				/* ==================================================== */

				if (sizeof(db_fetch_assoc("SELECT * FROM device WHERE site_id=$site_id LIMIT 1"))) {
					$bad_ids[] = $site_id;
				}else{
					$site_ids[] = $site_id;
				}
			}
			}

			if (isset($bad_ids)) {
				$message = "";
				foreach($bad_ids as $rra_id) {
					$message .= (strlen($message) ? "<br>":"") . "<i>Site " . $rra_id . " is in use and can not be removed</i>\n";
				}

				$_SESSION['sess_message_site_ref_int'] = array('message' => "<font size=-2>$message</font>", 'type' => 'info');

				raise_message('site_ref_int');
			}

			if (isset($site_ids)) {
			foreach($site_ids as $id) {
				site_remove($id);
			}
			}
		}

		exit;
	}

	/* setup some variables */
	$site_list = "";

	/* loop through each of the sites selected on the previous page and get more info about them */
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			$site_info = db_fetch_cell("SELECT name FROM sites WHERE id=" . $matches[1]);
			$site_list .= "<li>" . $site_info . "</li>";
			$site_array[] = $matches[1];
		}
	}

	print "<form id='sactions' name='sactions' action='sites.php' method='post'>\n";

	html_start_box("", "100", "3", "center", "");

	if (isset($site_array)) {
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
						<p>" . __("When you click 'Continue', the following Site(s) will be deleted.") . "</p>
						<div class='action_list'><ul>$site_list</ul></div>
					</td>
				</tr>\n";

			$title = __("Delete Site(s)");
		}
	}else{
		print "<tr><td class='textArea'>" . __("You must select at least one site.") . "</td></tr>\n";

		$title = __("Selection Error");
	}

	if (!isset($site_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button($title);
	}else{
		form_continue(serialize($site_array), get_request_var_post("drp_action"), $title, "sactions");
	}

	html_end_box();
}

function site_export() {
	global $site_actions;

	site_process_page_variables();

	$sql_where = "";

	$sites = site_get_site_records($sql_where, "", FALSE);

	if (get_request_var_request("detail") == "false") {
		$xport_array = array();
		array_push($xport_array, '"name","total_devices","total_device_errors",' .
			'"total_macs","total_ips","total_oper_ports",' .
			'"total_user_ports"');

		if (sizeof($sites)) {
			foreach($sites as $site) {
				array_push($xport_array,'"' . $site['name'] . '","' .
				$site['total_devices'] . '","' .
				$site['total_device_errors'] . '","' .
				$site['total_macs'] . '","' .
				$site['total_ips'] . '","' .
				$site['total_oper_ports'] . '","' .
				$site['total_user_ports'] . '"');
			}
		}
	}else{
		$xport_array = array();
		array_push($xport_array, '"name","address1","address2",' .
			'"city","state","postal_code",' .
			'"country"');

		if (sizeof($sites)) {
			foreach($sites as $site) {
				array_push($xport_array,'"' . $site['name'] . '","' .
				$site['address1'] . '","' .
				$site['address2'] . '","' .
				$site['city'] . '","' .
				$site['state'] . '","' .
				$site['postal_code'] . '","' .
				$site['country'] . '"');
			}
		}
	}

	header("Content-type: application/xml");
	header("Content-Disposition: attachment; filename=cacti_site_xport.csv");
	foreach($xport_array as $xport_line) {
		print $xport_line . "\n";
	}
}

function site_save($id, $name, $alternate_id, $address1, $address2, $city, $state, $postal_code, $country, $notes) {
	$save["id"]           = $id;
	$save["name"]         = form_input_validate($name,         $_POST["name"],         "", false, 3);
	$save["alternate_id"] = form_input_validate($alternate_id, $_POST["alternate_id"], "", true, 3);
	$save["address1"]     = form_input_validate($address1,     $_POST["address1"],     "", true, 3);
	$save["address2"]     = form_input_validate($address2,     $_POST["address2"],     "", true, 3);
	$save["city"]         = form_input_validate($city,         $_POST["city"],         "", true, 3);
	$save["state"]        = form_input_validate($state,        $_POST["state"],        "", true, 3);
	$save["postal_code"]  = form_input_validate($postal_code,  $_POST["postal_code"],  "", true, 3);
	$save["country"]      = form_input_validate($country,      $_POST["country"],      "", true, 3);
	$save["notes"]        = form_input_validate($notes,        $_POST["notes"],        "", true, 3);

	$id = 0;
	if (!is_error_message()) {
		$id = sql_save($save, "sites", "id");

		if ($id) {
			raise_message(1);
		}else{
			raise_message(2);
		}
	}

	return $id;
}

function site_remove($id) {
	$devices = db_fetch_cell("SELECT COUNT(*) FROM device WHERE site_id='" . $id . "'");

	if ($devices == 0) {
		db_execute("DELETE FROM sites WHERE id='" . $id . "'");
	}else{
		$_SESSION["sess_messages"] = __("Some sites not removed as they contain devices!");
	}
}

/* ---------------------
    Site Functions
   --------------------- */

function site_remove_confirm() {
	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	$devices = db_fetch_cell("SELECT COUNT(*) FROM device WHERE site_id='" . $_REQUEST["site_id"] . "'");

	if ($devices == 0) {
		if ((read_config_option("remove_verification") == CHECKED) && (!isset($_GET["confirm"]))) {
			include("./include/top_header.php");
			form_confirm(__("Are You Sure?"), __("Are you sure you want to delete the site") . " <strong>'" . db_fetch_cell("select description from device where id=" . get_request_var("device_id")) . "'</strong>?", "sites.php", "sites.php?action=remove&id=" . get_request_var("id"));
			include("./include/bottom_footer.php");
			exit;
		}

		if ((read_config_option("remove_verification") == "") || (isset($_GET["confirm"]))) {
			site_remove(get_request_var("id"));
		}
	}else{
		display_custom_error_message(__("You can not delete this site while there are devices associated with it."));
	}
}

function site_edit() {
	require_once(CACTI_BASE_PATH . "/lib/site.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$site = db_fetch_row("select * from sites where id=" . get_request_var("id"));
		$header_label = "[edit: " . $site["name"] . "]";
	}else{
		$header_label = "[new]";
	}

	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='site_edit'>\n";

	html_start_box(__("Site") . " $header_label", "100", 0, "center", "");

	draw_edit_form(array(
		"config" => array("form_name" => "chk", "no_form_tag" => true),
		"fields" => inject_form_variables(site_form_list(), (isset($site) ? $site : array()))
		));

	form_hidden_box("id", (isset($site["id"]) ? $site["id"] : "0"), "");
	form_hidden_box("hidden_id", (isset($site["hidden_id"]) ? $site["hidden_id"] : "0"), "");
	form_hidden_box("save_component_site", "1", "");

	html_end_box();

	form_save_button("sites.php", "return");
}

function sites_filter() {
	global $item_rows;

	?>
	<script type="text/javascript">
	<!--
	function applySiteFilterChange(objForm) {
		strURL = '?report=sites';
		if (objForm.detail.checked) {
			if (typeof objForm.device_template_id !== "undefined") strURL = strURL + '&device_template_id=' + objForm.device_template_id.value;
			if (typeof objForm.site_id !== "undefined") strURL = strURL + '&site_id=' + objForm.site_id.value;
			if (typeof objForm.filter !== "undefined") strURL = strURL + '&filter=' + objForm.filter.value;
		}else{
			strURL = strURL + '&device_template_id=-1';
			strURL = strURL + '&site_id=-1';
			strURL = strURL + '&filter=';
		}
		strURL = strURL + '&detail=' + objForm.detail.checked;
		strURL = strURL + '&rows=' + objForm.rows.value;
		document.location = strURL;
	}
	-->
	</script>
	<?php html_start_box(__("Site Filters"), "100", "3", "center", "sites.php?action=edit", true);?>
	<tr class='rowAlternate3'>
		<td>
			<form method='get' action='<?php print basename($_SERVER["PHP_SELF"]);?>' name='form_sites'>
			<table cellpadding="0" cellspacing="3">
				<tr>
					<td>
						&nbsp;<?php print __("Search:");?>&nbsp;
						<input type="text" name="filter" size="30" value="<?php print html_get_page_variable("filter");?>">
					</td>
					<td>
						&nbsp;<?php print __("Rows:");?>&nbsp;
					<select name="rows" onChange="applySiteFilterChange(document.form_sites)">
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
					<td>
						&nbsp;<input type="checkbox" id="detail" name="detail" <?php if ((html_get_page_variable("detail") == "true") || (html_get_page_variable("detail") == CHECKED)) print ' checked';?> onClick="applySiteFilterChange(document.form_sites)">
						<label for="detail"><?php print __("Details");?></label>
					</td>
					<td class="nw120">
						&nbsp;<input type="submit" Value="<?php print __("Go");?>" name="go" align="middle">
						<input type="submit" Value="<?php print __("Clear");?>" name="clear" align="middle">
					</td>
				</tr>
			<?php
			if (!(html_get_page_variable("detail") == "false")) { ?>
			</table>
			<table cellpadding="0" cellspacing="3">
				<tr>
					<td>
						&nbsp;<?php print __("Site:");?>
						<select id="site_id" name="site_id" onChange="applySiteFilterChange(document.form_sites)">
						<option value="-1"<?php if (html_get_page_variable("site_id") == "-1") {?> selected<?php }?>><?php print __("Any");?></option>
						<?php
						$sites = db_fetch_assoc("SELECT * FROM sites ORDER BY sites.name");
						if (sizeof($sites) > 0) {
						foreach ($sites as $site) {
							print '<option value="' . $site["id"] . '"'; if (html_get_page_variable("site_id") == $site["id"]) { print " selected"; } print ">" . $site["name"] . "</option>";
						}
						}
						?>
						</select>
					</td>
					<td>
						&nbsp;<?php print __("Device Template:");?>
						<select id="device_template_id" name="device_template_id" onChange="applySiteFilterChange(document.form_sites)">
						<option value="-1"<?php if (html_get_page_variable("device_template_id") == "-1") {?> selected<?php }?>><?php print __("Any");?></option>
						<?php
						$device_templates = db_fetch_assoc("SELECT DISTINCT device_template.id,
							device_template.name
							FROM device_template
							INNER JOIN device ON (device_template.id = device.device_template_id)
							ORDER BY device_template.name");
						if (sizeof($device_templates) > 0) {
						foreach ($device_templates as $device_template) {
							print '<option value="' . $device_template["id"] . '"'; if (html_get_page_variable("device_template_id") == $device_template["id"]) { print " selected"; } print ">" . $device_template["name"] . "</option>";
						}
						}
						?>
						</select>
					</td>
				</tr>
			<?php }?>
			</table>
			<div>
				<input type='hidden' name='page' value='1'>
				<input type='hidden' name='report' value='sites'>
			</div>
			</form>
		</td>
	</tr>
	<?php
	html_end_box(false);
}

function get_site_records(&$total_rows, &$rowspp, $apply_limits = TRUE) {
	/* create SQL where clause */
	$device_type_info = db_fetch_row("SELECT * FROM device_template WHERE id='" . html_get_page_variable("device_template_id") . "'");

	$sql_where = "";

	/* form the 'where' clause for our main sql query */
	if (strlen(html_get_page_variable("filter"))) {
		if (("detail") == "false") {
			$sql_where = "WHERE (sites.name LIKE '%%" . html_get_page_variable("filter") . "%%')";
		}else{
			$sql_where = "WHERE (device_template.name LIKE '%%" . html_get_page_variable("filter") . "%%' OR " .
				"sites.name LIKE '%%" . html_get_page_variable("filter") . "%%')";
		}
	}

	if (sizeof($device_type_info)) {
		if (!strlen($sql_where)) {
			$sql_where = "WHERE (device.device_template_id=" . $device_type_info["id"] . ")";
		}else{
			$sql_where .= " AND (device.device_template_id=" . $device_type_info["id"] . ")";
		}
	}

	if ((html_get_page_variable("site_id") != "-1") && (html_get_page_variable("detail"))){
		if (!strlen($sql_where)) {
			$sql_where = "WHERE (device.site_id='" . html_get_page_variable("site_id") . "')";
		}else{
			$sql_where .= " AND (device.site_id='" . html_get_page_variable("site_id") . "')";
		}
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	if (get_request_var_request("detail") == "false") {
		$query_string = "SELECT *
			FROM sites
			$sql_where
			ORDER BY " . html_get_page_variable("sort_column") . " " . html_get_page_variable("sort_direction");

		if ($apply_limits) {
			$query_string .= " LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp;
		}
	}else{
		$query_string ="SELECT sites.id,
			sites.name,
			sites.alternate_id,
			sites.address1,
			sites.address2,
			sites.city,
			sites.state,
			sites.country,
			Count(device_template.id) AS total_devices,
			device_template.name as device_template_name
			FROM (device_template
			RIGHT JOIN device ON (device_template.id=device.device_template_id))
			RIGHT JOIN sites ON (device.site_id=sites.id)
			$sql_where
			GROUP BY sites.name, device_template.name
			ORDER BY " . html_get_page_variable("sort_column") . " " . html_get_page_variable("sort_direction");

		if ($apply_limits) {
			$query_string .= " LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp;
		}
	}

	if (html_get_page_variable("detail") == "false") {
		$total_rows = db_fetch_cell("SELECT
			COUNT(sites.id)
			FROM sites
			$sql_where");
	}else{
		$total_rows = sizeof(db_fetch_assoc("SELECT
			device_template.id, sites.name
			FROM (device_template
			RIGHT JOIN device ON (device_template.id=device.device_template_id))
			RIGHT JOIN sites ON (device.site_id=sites.id)
			$sql_where
			GROUP BY sites.name, device_template.id"));
	}

	return db_fetch_assoc($query_string);
}

function site($refresh = true) {
	global $site_actions;

	$table = New html_table;

	$table->page_variables = array(
		"page" => array("type" => "numeric", "default" => "1"),
		"rows" => array("type" => "numeric", "default" => "-1"),
		"site_id" => array("type" => "numeric", "default" => "-1"),
		"device_template_id" => array("type" => "numeric", "default" => "-1"),
		"detail" => array("type" => "string", "default" => "false"),
		"filter" => array("type" => "string", "default" => ""),
		"sort_column" => array("type" => "string", "default" => "name"),
		"sort_direction" => array("type" => "string", "default" => "ASC"));

	/* initialize page behavior */
	$table->href           = "sites.php";
	$table->session_prefix = "sess_sites";
	$table->filter_func    = "sites_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->checkbox       = true;
	$table->sortable       = true;
	$table->actions        = $site_actions;
	$table->table_id       = "sites";

	/* we must validate table variables */
	$table->process_page_variables();

	/* need "process_page_variables" first before calling html_get_page_variable to access global $_pageVars */
	if (html_get_page_variable("detail") == "false") {
		$table->table_format = array(
			"name" => array(
				"name" => __("Site Name"),
				"filter" => true,
				"link" => true,
				"order" => "ASC"
			),
			"address1" => array(
				"name" => __("Address"),
				"order" => "ASC"
			),
			"city" => array(
				"name" => __("City"),
				"order" => "ASC"
			),
			"state" => array(
				"name" => __("State"),
				"order" => "DESC"
			),
			"country" => array(
				"name" => __("Country"),
				"order" => "DESC"
			)
		);
	}else{
		$table->table_format = array(
			"name" => array(
				"name" => __("Site Name"),
				"filter" => true,
				"link" => true,
				"order" => "ASC"
			),
			"device_template_name" => array(
				"name" => __("Device Type"),
				"order" => "ASC"
			),
			"total_devices" => array(
				"name" => __("Devices"),
				"order" => "DESC",
				"align" => "right"
			),
			"address1" => array(
				"name" => __("Address"),
				"order" => "ASC"
			),
			"city" => array(
				"name" => __("City"),
				"order" => "ASC"
			),
			"state" => array(
				"name" => __("State"),
				"order" => "DESC"
			),
			"country" => array(
				"name" => __("Country"),
				"order" => "DESC"
			)
		);
	}

	/* get the records */
	$table->rows = get_site_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}
