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

function poller_cache_format_detail($action, $snmp_version, $snmp_community, $snmp_username, $arg1) {
	if ($action == 0) {
		if ($snmp_version != 3) {
			$details =
				__("SNMP Version:") . " " . $snmp_version . ", " .
				__("Community:") . " " . $snmp_community . ", " .
				__("OID:") . " " . $arg1;
		}else{
			$details =
				__("SNMP Version:") . " " . $snmp_version . ", " .
				__("User:") . " " . $snmp_username . ", " .
				__("OID:") . " " . $arg1;
		}
	}elseif ($action == 1) {
			$details = __("Script:") . " " .  $arg1;
	}else{
			$details = __("Script Server:") . " " .  $arg1;
	}

	return $details;
}


function poller_cache_filter() {
	global $item_rows;

	html_start_box("<strong>" . __("Poller Cache Items") . "</strong>", "100", "3", "center", "", true);
	?>
	<tr class='rowAlternate2'>
		<td>
			<form name="form_pollercache" action="utilities.php">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td class="nw50">
						&nbsp;<?php print __("Host:");?>&nbsp;
					</td>
					<td class="w1">
						<?php
						if (isset($_REQUEST["device_id"])) {
							$hostname = db_fetch_cell("SELECT description as name FROM device WHERE id=".html_get_page_variable("device_id")." ORDER BY description,hostname");
						} else {
							$hostname = "";
						}
						?>
						<input class="ac_field" type="text" id="device" size="30" value="<?php print $hostname; ?>">
						<input type="hidden" id="device_id">
					</td>
					<td class="nw50">
						&nbsp;<?php print __("Action:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="poller_action" onChange="applyPItemFilterChange(document.form_pollercache)">
							<option value="-1"<?php if (html_get_page_variable('poller_action') == '-1') {?> selected<?php }?>><?php print __("Any");?></option>
							<option value="0"<?php if (html_get_page_variable('poller_action') == '0') {?> selected<?php }?>><?php print __("SNMP");?></option>
							<option value="1"<?php if (html_get_page_variable('poller_action') == '1') {?> selected<?php }?>><?php print __("Script");?></option>
							<option value="2"<?php if (html_get_page_variable('poller_action') == '2') {?> selected<?php }?>><?php print __("Script Server");?></option>
						</select>
					</td>
					<td class="nw120">
						&nbsp;<input type="submit" Value="<?php print __("Go");?>" name="go" align="middle">
						<input type="button" Value="<?php print __("Clear");?>" name="clear" align="middle" onClick="clearPItemFilterChange(document.form_userlog)">
					</td>
				</tr>
			</table>
			<table cellpadding="0" cellspacing="0" border="0">
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
						<select name="rows" onChange="applyPItemFilterChange(document.form_pollercache)">
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
				</tr>
			</table>
			<div><input type='hidden' name='page' value='1'></div>
			<div><input type='hidden' name='action' value='view_poller_cache'></div>
			<div><input type='hidden' name='page_referrer' value='view_poller_cache'></div>
			</form>
		</td>
	</tr>
	<?php
	html_end_box(false);
	?>
	<script type="text/javascript">
	<!--
	$().ready(function() {
		$("#device").autocomplete("utilities.php?action=ajax_get_devices_brief", { max: 8, highlight: false, scroll: true, scrollHeight: 300 });
		$("#device").result(function(event, data, formatted) {
			if (data) {
				$(this).parent().find("#device_id").val(data[1]);
				applyPItemFilterChange(document.form_pollercache);
			}else{
				$(this).parent().find("#device_id").val(0);
			}
		});
	});

	function clearPItemFilterChange(objForm) {
		strURL = '?device_id=-1';
		strURL = strURL + '&filter=';
		strURL = strURL + '&poller_action=-1';
		strURL = strURL + '&rows=-1';
		strURL = strURL + '&action=view_poller_cache';
		strURL = strURL + '&page=1';
		document.location = strURL;
	}

	function applyPItemFilterChange(objForm) {
		if (objForm.device_id.value) {
			strURL = '?device_id=' + objForm.device_id.value;
			strURL = strURL + '&filter=' + objForm.filter.value;
		}else{
			strURL = '?filter=' + objForm.filter.value;
		}
		strURL = strURL + '&poller_action=' + objForm.poller_action.value;
		strURL = strURL + '&rows=' + objForm.rows.value;
		strURL = strURL + '&action=view_poller_cache';
		strURL = strURL + '&page=1';
		document.location = strURL;
	}

	-->
	</script>
	<?php
}


function poller_cache_get_records(&$total_rows, &$rowspp) {

	/* form the 'where' clause for our main sql query */
	$sql_where = "WHERE poller_item.local_data_id=data_template_data.local_data_id";

	if (html_get_page_variable("poller_action") == "-1") {
		/* Show all items */
	}else {
		$sql_where .= " AND poller_item.action='" . html_get_page_variable("poller_action") . "'";
	}

	if (html_get_page_variable("device_id") == "-1") {
		/* Show all items */
	}elseif (html_get_page_variable("device_id") == "0") {
		$sql_where .= " AND poller_item.device_id=0";
	}elseif (!empty($_REQUEST["device_id"])) {
		$sql_where .= " AND poller_item.device_id=" . html_get_page_variable("device_id");
	}

	if (strlen(html_get_page_variable("filter"))) {
		$sql_where .= " AND (data_template_data.name_cache LIKE '%%" . html_get_page_variable("filter") . "%%'
			OR device.description LIKE '%%" . html_get_page_variable("filter") . "%%'
			OR poller_item.arg1 LIKE '%%" . html_get_page_variable("filter") . "%%'
			OR poller_item.hostname LIKE '%%" . html_get_page_variable("filter") . "%%'
			OR poller_item.rrd_path  LIKE '%%" . html_get_page_variable("filter") . "%%')";
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_data_source");
	}else{
		$rowspp = html_get_page_variable("rows");
	}


	$total_rows = db_fetch_cell("SELECT
		COUNT(*)
		FROM data_template_data
		RIGHT JOIN (poller_item
		LEFT JOIN device
		ON poller_item.device_id=device.id)
		ON data_template_data.local_data_id=poller_item.local_data_id
		$sql_where");

	$poller_sql = "SELECT
		poller_item.*,
		data_template_data.name_cache,
		device.description
		FROM data_template_data
		RIGHT JOIN (poller_item
		LEFT JOIN device
		ON poller_item.device_id=device.id)
		ON data_template_data.local_data_id=poller_item.local_data_id
		$sql_where
		ORDER BY " . html_get_page_variable("sort_column") . " " . html_get_page_variable("sort_direction") . "
		LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp;

	//	print $poller_sql;

	return db_fetch_assoc($poller_sql);
}


function utilities_view_poller_cache($refresh=true) {
	global $item_rows, $colors;

	define("MAX_DISPLAY_PAGES", 21);

	$table = New html_table;

	$table->page_variables = array(
		"device_id"      => array("type" => "numeric",  "method" => "request", "default" => "-1"),
		"poller_action"  => array("type" => "string",  "method" => "request", "default" => "-1"),
		"page"           => array("type" => "numeric", "method" => "request", "default" => "1"),
		"rows"           => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"filter"         => array("type" => "string",  "method" => "request", "default" => ""),
		"sort_column"    => array("type" => "string",  "method" => "request", "default" => "name_cache"),
		"sort_direction" => array("type" => "string",  "method" => "request", "default" => "ASC")
	);


	$table->table_format = array(
		"name_cache" => array(
			"name" => __("Data Source Name"),
			"link" => true,
			"href" => "data_sources.php?action=edit",
			"filter" => true,
			"order" => "ASC"
		),
		"details" => array(
			"name" => __("Details"),
			"function" => "poller_cache_format_detail",
			"params" => array("action", "snmp_version", "snmp_community", "snmp_username", "arg1"),
			"filter" => true,
			"order" => "ASC"
		),
		"rrd_path" => array(
			"name" => __("RRD Path"),
			"filter" => true,
			"order" => "ASC"
		),
	);

	/* initialize page behavior */
	$table->key_field      = "local_data_id";
	$table->href           = "utilities.php";
	$table->session_prefix = "sess_poller_cache";
	$table->filter_func    = "poller_cache_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->sortable       = true;
	$table->table_id       = "poller_cache";
#	$table->actions        = $poller_cache_actions;

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = poller_cache_get_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();

return;











	if (sizeof($poller_cache) > 0) {
		foreach ($poller_cache as $item) {
			form_alternate_row_color();
				?>
				<td width="375">
					<a class="linkEditMain" href="<?php print htmlspecialchars("data_sources.php?action=edit&id=" . $item["local_data_id"]);?>"><?php print (strlen($_REQUEST["filter"]) ? preg_replace("/(" . preg_quote($_REQUEST["filter"]) . ")/i", "<span class=\"filter\">\\1</span>", $item["name_cache"]) : $item["name_cache"]);?></a>
				</td>

				<td>
				<?php
				if ($item["action"] == 0) {
					if ($item["snmp_version"] != 3) {
						$details =
							__("SNMP Version:") . " " . $item["snmp_version"] . ", " .
							__("Community:") . " " . $item["snmp_community"] . ", " .
							__("OID:") . " " . (strlen(get_request_var_request("filter")) ? (preg_replace("/(" . preg_quote(get_request_var_request("filter")) . ")/i", "<span class=\"filter\">\\1</span>", $item["arg1"])) : $item["arg1"]);
					}else{
						$details =
							__("SNMP Version:") . " " . $item["snmp_version"] . ", " .
							__("User:") . " " . $item["snmp_username"] . ", " .
							__("OID:") . " " . $item["arg1"];
					}
				}elseif ($item["action"] == 1) {
						$details = __("Script:") . " " . (strlen(get_request_var_request("filter")) ? (preg_replace("/(" . preg_quote(get_request_var_request("filter")) . ")/i", "<span class=\"filter\">\\1</span>", $item["arg1"])) : $item["arg1"]);
				}else{
						$details = __("Script Server:") . " " . (strlen(get_request_var_request("filter")) ? (preg_replace("/(" . preg_quote(get_request_var_request("filter")) . ")/i", "<span class=\"filter\">\\1</span>", $item["arg1"])) : $item["arg1"]);
				}

				print $details;
				?>
				</td>
			<?php
			form_end_row();
			form_alternate_row_color();
			?>
				<td>
				</td>
				<td>
					RRD: <?php print $item["rrd_path"];?>
				</td>
			<?php
			form_end_row();
		}

		form_end_table();

		print $nav;
	}else{
		print "<tr><td><em>" . __("No Records Found") . "</em></td></tr>\n";
	}

	print "</table>\n";
}
