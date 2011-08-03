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

function utilities_clear_user_log() {
		/* delete all entries */
		db_execute("DELETE FROM user_log");
}

function utilities_format_timestamp($date) {
	/* return the timestamp in the format defined by the user (if any) */
	return __date("D, " . date_time_format() . " T", strtotime($date));	
}

function utilities_format_authentication_result($type) {
#	require_once(CACTI_BASE_PATH . "/include/auth/auth_arrays.php");
	global $auth_log_messages;
	/* return the authentication type in a human readable format */
	return $auth_log_messages["$type"];	
}

function userlog_filter() {
	global $item_rows;

	html_start_box("<strong>" . __("User Login History") . "</strong>", "100", "3", "center", "", true);
	?>
	<tr class='rowAlternate2'>
		<td>
			<form name="form_userlog" action="utilities.php">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td class="nw50">
						&nbsp;<?php print __("Username:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="username" onChange="applyViewLogFilterChange(document.form_userlog)">
							<option value="-1"<?php if (html_get_page_variable("username") == "-1") {?> selected<?php }?>><?php print __("All");?></option>
							<option value="-2"<?php if (html_get_page_variable("username") == "-2") {?> selected<?php }?>><?php print __("Deleted/Invalid");?></option>
							<?php
							$users = db_fetch_assoc("SELECT DISTINCT username FROM user_auth ORDER BY username");

							if (sizeof($users) > 0) {
							foreach ($users as $user) {
								print "<option value='" . $user["username"] . "'"; if (html_get_page_variable("username") == $user["username"]) { print " selected"; } print ">" . $user["username"] . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td class="nw50">
						&nbsp;<?php print __("Result:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="result" onChange="applyViewLogFilterChange(document.form_userlog)">
							<option value="-1"<?php if (html_get_page_variable('result') == '-1') {?> selected<?php }?>><?php print __("Any");?></option>
							<option value="1"<?php if (html_get_page_variable('result') == '3') {?> selected<?php }?>><?php print __("Password Change");?></option>
							<option value="1"<?php if (html_get_page_variable('result') == '1') {?> selected<?php }?>><?php print __("Success");?></option>
							<option value="0"<?php if (html_get_page_variable('result') == '0') {?> selected<?php }?>><?php print __("Failed");?></option>
						</select>
					</td>
					<td class="nw50">
						&nbsp;<?php print __("Rows:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="rows" onChange="applyViewLogFilterChange(document.form_userlog)">
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
					<td class="nw50">
						&nbsp;<?php print __("Search:");?>&nbsp;
					</td>
					<td class="w1">
						<input type="text" name="filter" size="20" value="<?php print html_get_page_variable("filter");?>">
					</td>
					<td class="nw120">
						&nbsp;<input type="submit" Value="<?php print __("Go");?>" name="go" align="middle">
						<input type="button" Value="<?php print __("Clear");?>" name="clear" align="middle" onClick="clearViewLogFilterChange(document.form_userlog)">
						<input type="submit" Value="<?php print __("Purge All");?>" name="purge_x" align="middle">
					</td>
				</tr>
			</table>
			<div><input type='hidden' name='page' value='1'></div>
			<div><input type='hidden' name='action' value='view_user_log'></div>
			<div><input type='hidden' name='page_referrer' value='view_user_log'></div>
			</form>
		</td>
	</tr>
	<?php
	html_end_box(false);
	?>
	<script type="text/javascript">
	<!--
	function clearViewLogFilterChange(objForm) {
		strURL = '?username=-1';
		strURL = strURL + '&filter=';
		strURL = strURL + '&rows=-1';
		strURL = strURL + '&result=-1';
		strURL = strURL + '&action=view_user_log';
		strURL = strURL + '&page=1';
		document.location = strURL;
	}

	function applyViewLogFilterChange(objForm) {
		if (objForm.username.value) {
			strURL = '?username=' + objForm.username.value;
			strURL = strURL + '&filter=' + objForm.filter.value;
		}else{
			strURL = '?filter=' + objForm.filter.value;
		}
		strURL = strURL + '&rows=' + objForm.rows.value;
		strURL = strURL + '&result=' + objForm.result.value;
		strURL = strURL + '&action=view_user_log';
		strURL = strURL + '&page=1';
		document.location = strURL;
	}
	-->
	</script>
	<?php
}

function userlog_get_records(&$total_rows, &$rowspp) {

	$sql_where = "";

	/* filter by username */
	if (html_get_page_variable("username") == "-1") {
		/* Show all users */
	}elseif (html_get_page_variable("username") == "-2") {
		/* only show deleted users */
		$sql_where = "WHERE user_log.username NOT IN (SELECT DISTINCT username from user_auth)";
	}elseif (!empty($_REQUEST["username"])) {
		/* show specific user */
		$sql_where = "WHERE user_log.username='" . html_get_page_variable("username") . "'";
	}

	/* filter by result aka login type */
	if (html_get_page_variable("result") == "-1") {
		/* Show all items */
	}else{
		$sql_where .= (strlen($sql_where) ? " AND ":"WHERE ") . " user_log.result=" . html_get_page_variable("result");
	}

	/* filter by search string */
	if (html_get_page_variable("filter") <> "") {
		$sql_where .= (strlen($sql_where) ? " AND":"WHERE");
		$sql_where .= " (user_log.username LIKE '%%" . html_get_page_variable("filter") . "%%'" .
						" OR user_log.time LIKE '%%" . html_get_page_variable("filter") . "%%'" .
						" OR user_log.ip LIKE '%%" . html_get_page_variable("filter") . "%%')";
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$sortby = html_get_page_variable("sort_column");
	if ($sortby=="hostname") {
		$sortby = "INET_ATON(hostname)";
	}

	$total_rows = db_fetch_cell("SELECT
		COUNT(*)
		FROM user_auth
		RIGHT JOIN user_log
		ON user_auth.username = user_log.username
		$sql_where");

	$user_log_sql = "SELECT
		user_log.username,
		user_auth.full_name,
		user_auth.realm,
		user_log.user_id,
		user_log.time,
		user_log.result,
		user_log.ip
		FROM user_auth
		RIGHT JOIN user_log
		ON user_auth.username = user_log.username
		$sql_where
		ORDER BY " . $sortby . " " . html_get_page_variable("sort_direction") . "
		LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp;

	//	print $user_log_sql;

	return db_fetch_assoc($user_log_sql);
}

function utilities_view_user_log($refresh=true) {
	global $item_rows, $colors;
	require(CACTI_BASE_PATH . "/include/auth/auth_arrays.php");

	define("MAX_DISPLAY_PAGES", 21);

	$table = New html_table;

	$table->page_variables = array(
		"page"           => array("type" => "numeric", "method" => "request", "default" => "1"),
		"rows"           => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"filter"         => array("type" => "string",  "method" => "request", "default" => ""),
		"username"       => array("type" => "string",  "method" => "request", "default" => "-1"),
		"result"         => array("type" => "string",  "method" => "request", "default" => "-1"),
		"sort_column"    => array("type" => "string",  "method" => "request", "default" => "time"),
		"sort_direction" => array("type" => "string",  "method" => "request", "default" => "DESC")
	);


	$table->table_format = array(
		"username" => array(
			"name" => __("Username"),
			"filter" => true,
			"order" => "ASC"
		),
		"full_name" => array(
			"name" => __("Full Name"),
			"filter" => true,
			"order" => "ASC"
		),
		"realm" => array(
			"name" => __("Authentication Realm"),
			"function" => "display_auth_realms",
			"params" => array("realm"),
			"order" => "ASC"
		),
		"time" => array(
			"name" => __("Date"),
			"function" => "utilities_format_timestamp",
			"params" => array("time"),			
			"filter" => true,
			"order" => "ASC"
		),
		"result" => array(
			"name" => __("Authentication Type"),
			"function" => "utilities_format_authentication_result",
			"params" => array("result"),			
			"order" => "ASC"
		),
		"ip" => array(
			"name" => __("IP Address"),
			"filter" => true,
			"order" => "DESC"
		)
	);

	/* initialize page behavior */
	$table->key_field      = "user_id";
	$table->href           = "utilities.php";
	$table->session_prefix = "sess_userlog";
	$table->filter_func    = "userlog_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->sortable       = true;
	$table->table_id       = "userlog";
#	$table->actions        = $userlog_actions;

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = userlog_get_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();
}
