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
include_once(CACTI_BASE_PATH . "/lib/time.php");

load_current_session_value("page_referrer", "page_referrer", "");

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

if (isset($_REQUEST["sort_direction"])) {
	if (get_request_var_request('page_referrer') == "view_snmp_cache") {
		$_REQUEST["action"] = "view_snmp_cache";
	}else if (get_request_var_request('page_referrer') == "view_poller_cache") {
		$_REQUEST["action"] = "view_poller_cache";
	}else if (get_request_var_request('page_referrer') == "view_font_cache") {
		$_REQUEST["action"] = "view_font_cache";
	}else{
		$_REQUEST["action"] = "view_user_log";
	}
}

if ((isset($_REQUEST["clear_x"])) || (isset($_REQUEST["go_x"]))) {
	if (get_request_var_request('page_referrer') == "view_snmp_cache") {
		$_REQUEST["action"] = "view_snmp_cache";
	}else if (get_request_var_request('page_referrer') == "view_poller_cache") {
		$_REQUEST["action"] = "view_poller_cache";
	}else if (get_request_var_request('page_referrer') == "view_font_cache") {
		$_REQUEST["action"] = "view_font_cache";
	}else if (get_request_var_request('page_referrer') == "view_user_log") {
		$_REQUEST["action"] = "view_user_log";
	}else{
		$_REQUEST["action"] = "view_logfile";
	}
}

if (isset($_REQUEST["purge_x"])) {
	if (get_request_var_request('page_referrer') == "view_user_log") {
		$_REQUEST["action"] = "clear_user_log";
	}else{
		$_REQUEST["action"] = "clear_logfile";
	}
}

switch (get_request_var_request("action")) {
	case 'rebuild_font_cache':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		/* obtain timeout settings */
		$max_execution = ini_get("max_execution_time");

		ini_set("max_execution_time", "0");
		repopulate_font_cache();
		ini_set("max_execution_time", $max_execution);

		utilities_view_font_cache();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
	case 'view_font_cache':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		utilities_view_font_cache();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
	case 'view_snmp_cache':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		utilities_view_snmp_cache();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
	case 'view_poller_cache':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		utilities_view_poller_cache();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
	case 'view_logfile':
		utilities_view_logfile();

		break;
	case 'clear_logfile':
		utilities_clear_logfile();
		utilities_view_logfile();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
	case 'view_user_log':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		utilities_view_user_log();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		break;
	case 'clear_user_log':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");

		utilities_clear_user_log();
		utilities_view_user_log();

		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
	case 'ajax_view':
		utilities_view_tech(false);

		break;
	case 'ajax_get_fonts':
		utilities_ajax_get_fonts();
		
		break;
	case 'view_tech':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		utilities_view_tech(true);
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
	case 'ajax_get_devices_brief':
		ajax_get_devices_brief();
		break;
	default:

		if (!plugin_hook_function('utilities_action', get_request_var_request('action'))) {
			include_once(CACTI_BASE_PATH . "/include/top_header.php");
			utilities();
			include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		}
		break;
}

/* -----------------------
    Utilities Functions
   ----------------------- */

function utilities_php_modules() {
	/*
	   Gather phpinfo into a string variable - This has to be done before
	   any headers are sent to the browser, as we are going to do some
	   output buffering fun
	*/

	ob_start();
	phpinfo(INFO_MODULES);
	$php_info = ob_get_contents();
	ob_end_clean();

	/* Remove nasty style sheets, links and other junk */
	$php_info = str_replace("\n", "", $php_info);
	$php_info = preg_replace('/^.*\<body\>/', '', $php_info);
	$php_info = preg_replace('/\<\/body\>.*$/', '', $php_info);
	$php_info = preg_replace('/\<a.*\>/U', '', $php_info);
	$php_info = preg_replace('/\<\/a\>/', '<hr>', $php_info);
	$php_info = preg_replace('/\<img.*\>/U', '', $php_info);
	$php_info = preg_replace('/\<\/?address\>/', '', $php_info);
	$php_info = str_replace("<hr>", "", $php_info);
	$php_info = str_replace("<br />", "", $php_info);
	$php_info = str_replace("<h2>", "<h2><strong>" . __("Module Name:") . " </strong>", $php_info);
	$php_info = str_replace("cellpadding=\"3\"", "cellspacing=\"0\" cellpadding=\"3\"", $php_info);
	$php_info = str_replace("width=\"600\"","", $php_info);

	return $php_info;
}

function memory_bytes($val) {
	$val  = trim($val);
	$last = strtolower($val{strlen($val)-1});
	switch($last) {
	// The 'G' modifier is available since PHP 5.1.0
	case 'g':
		$val *= 1024;
	case 'm':
		$val *= 1024;
	case 'k':
		$val *= 1024;
	}

	return $val;
}


function memory_readable($val) {
	if ($val < 1024) {
		$val_label = "bytes";
	}elseif ($val < 1048576) {
		$val_label = "K";
		$val /= 1024;
	}elseif ($val < 1073741824) {
		$val_label = "M";
		$val /= 1048576;
	}else{
		$val_label = "G";
		$val /= 1073741824;
	}

	return $val . $val_label;
}


function utilities_view_tech($tabs = false) {
	global $rrdtool_versions;

	if ($tabs) {
		$tabs = array(
			"general" => __("General"),
			"database" => __("DB Info"),
			"process" => __("DB Processes"),
			"php" => __("PHP Info"),
			"i18n" => __("Languages")
		);

		/* draw the categories tabs on the top of the page */
		print "<div id='tabs_util'>\n";
		print "<ul>\n";

		$i = 1;
		if (sizeof($tabs) > 0) {
		foreach (array_keys($tabs) as $tab_short_name) {
			print "<li><a id='tabs-$i' href='" . htmlspecialchars("utilities.php?action=ajax_view&tab=$tab_short_name") . "'>$tabs[$tab_short_name]</a></li>";
			$i++;
		}
		}
		print "</ul>\n";
		print "</div>\n";

		print "<script type='text/javascript'>
			$().ready(function() {
				$('#tabs_util').tabs({ cookie: { expires: 30 } });
			});
		</script>\n";
	}else{
		/* Remove all cached settings, cause read of database */
		kill_session_var("sess_config_array");

		switch (get_request_var_request("tab")) {
		case "general":
			display_general();

			break;
		case "database":
			display_database();

			break;
		case "process":
			display_database_processes();

			break;
		case "php":
			display_php();

			break;
		case "i18n":
			display_languages();
		default:

			break;
		}
	}
}

function utilities_ajax_get_fonts() {
	/* input validation */
	if (isset($_REQUEST["q"])) {
		$q = strtolower(sanitize_search_string(get_request_var("q")));
	} else return;

	$sql = "SELECT id, font as name
		FROM fonts
		WHERE font LIKE '%$q%'
		ORDER BY font";

	$fonts = db_fetch_assoc($sql);

	if (sizeof($fonts) > 0) {
		foreach ($fonts as $font) {
			print $font["name"] . "|" . $font["name"] . "\n";
		}
	}
}

function display_php() {
	$php_info = utilities_php_modules();

	html_start_box(__("PHP Module Information"), "100", "3", "center", "");
	print "<tr>\n";
	print "<td class='left'>" . $php_info . "</td>\n";
	print "</tr>\n";

	html_end_box();
}

function display_general() {
	global $rrdtool_versions;
	require(CACTI_BASE_PATH . "/include/poller/poller_arrays.php");
	require(CACTI_BASE_PATH . "/include/data_input/data_input_arrays.php");

	/* Get poller stats */
	$poller_item = db_fetch_assoc("SELECT action, count(action) as total FROM poller_item GROUP BY action");

	/* Get system stats */
	$device_count  = db_fetch_cell("SELECT COUNT(*) FROM device");
	$graph_count = db_fetch_cell("SELECT COUNT(*) FROM graph_local");
	$data_count  = db_fetch_assoc("SELECT i.type_id, COUNT(i.type_id) AS total FROM data_template_data AS d, data_input AS i WHERE d.data_input_id = i.id AND local_data_id <> 0 GROUP BY i.type_id");

	/* Get RRDtool version */
	$rrdtool_version = __("Unknown");
	if ((file_exists(read_config_option("path_rrdtool"))) && ((function_exists('is_executable')) && (is_executable(read_config_option("path_rrdtool"))))) {

		$out_array = array();
		exec(cacti_escapeshellcmd(read_config_option("path_rrdtool")), $out_array);

		if (sizeof($out_array) > 0) {
			if (preg_match("/^RRDtool 1\.4/", $out_array[0])) {
				$rrdtool_version = RRD_VERSION_1_4;
			}else if (preg_match("/^RRDtool 1\.3/", $out_array[0])) {
				$rrdtool_version = RRD_VERSION_1_3;
			}else if (preg_match("/^RRDtool 1\.2\./", $out_array[0])) {
				$rrdtool_version = RRD_VERSION_1_2;
			}else if (preg_match("/^RRDtool 1\.0\./", $out_array[0])) {
				$rrdtool_version = RRD_VERSION_1_0;
			}
		}
	}

	/* Get SNMP cli version */
	$snmp_version = read_config_option("snmp_version");
	if ((file_exists(read_config_option("path_snmpget"))) && ((function_exists('is_executable')) && (is_executable(read_config_option("path_snmpget"))))) {
		$snmp_version = trim(shell_exec(cacti_escapeshellcmd(read_config_option("path_snmpget")) . " -V 2>&1"));
	}

	/* Check RRDTool issues */
	$rrdtool_error = "";
	if ($rrdtool_version != read_config_option("rrdtool_version")) {
		$rrdtool_error .= "<br><span class='warning'>" . __("ERROR: Installed RRDTool version does not match configured version.") . "<br>" . __("Please visit the") . " <a class='linkEditMain' href='" . htmlspecialchars("settings.php?tab=general") . "'> " . __("Configuration Settings") . "</a>" . __("and select the correct RRDTool Utility Version.") . "</span><br>";
	}
	$graph_gif_count = db_fetch_cell("SELECT COUNT(*) FROM graph_templates_graph WHERE image_format_id = 2");
	if (($graph_gif_count > 0) && (read_config_option("rrdtool_version") != RRD_VERSION_1_0)) {
		$rrdtool_error .= "<br><span class='warning'>" . sprintf(__("ERROR: RRDTool 1.2.x does not support the GIF images format, but %s graph(s) and/or templates have GIF set as the image format."), $graph_gif_count) . "</span><br>";
	}

	/* Display tech information */
	html_start_box(__("General Technical Support Information"), "100", "3", "center", "");
	print "<tr><td>";
	html_header(array(array("name" => __("General Information"))), 2, '', '', 'left wp100');
	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("System Date") . "</td>\n";
	disable_tmz_support();
	print "		<td class='textAreaNotes v'>" . __date("D, " . date_time_format() . " T") . "</td>\n";
	enable_tmz_support();
	print "</tr>\n";
	print "<tr class='rowAlternate3'>\n";
	print "		<td class='textAreaNotes e'>" . __("User Date") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . __date("D, " . date_time_format() . " T") . "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("Cacti Version") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . CACTI_VERSION . "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate3'>\n";
	print "		<td class='textAreaNotes e'>" . __("Cacti OS") . "</td>\n";
	print "		<td>" . CACTI_SERVER_OS . "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("SNMP Version") . "</td>\n";
	print "		<td>" . $snmp_version . "</td>\n";
	print "</tr>\n";

	print "<tr class='rowAlternate3'>\n";
	print "		<td class='textAreaNotes e'>" . __("RRDTool Version") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . (isset($rrdtool_versions[$rrdtool_version]) ? $rrdtool_versions[$rrdtool_version]: "Unknown") . " " . $rrdtool_error . "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("Hosts") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . $device_count . "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate3'>\n";
	print "		<td class='textAreaNotes e'>" . __("Graphs") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . $graph_count . "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("Data Sources") . "</td>\n";
	print "		<td class='textAreaNotes v'>";
	$data_total = 0;
	if (sizeof($data_count)) {
		foreach ($data_count as $item) {
			print $input_types[$item["type_id"]] . ": " . $item["total"] . "<br>";
			$data_total += $item["total"];
		}
		print __("Total:") . " " . $data_total;
	}else{
		print "<span class='warning'>0</span>";
	}

	$spine_version = "";
	if ($poller_options[read_config_option("poller_type")] == "spine") {
		$spine_output = shell_exec(cacti_escapeshellcmd(read_config_option("path_spine")) . " -v");
		$spine_version = substr($spine_output, 6, 6);
	}

	print "</tr></table></td></tr>";		/* end of html_header */
	print "<tr><td>";
	html_header(array(array("name" => __("Poller Information"))), 2, '', '', 'left wp100');
	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("Interval") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . read_config_option("poller_interval") . "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate3'>\n";
	print "		<td class='textAreaNotes e'>" . __("Type"). "</td>\n";
	print "		<td class='textAreaNotes v'>" . $poller_options[read_config_option("poller_type")] . " " . $spine_version . "</td>\n";
	print "</tr>\n";

	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("Items") . "</td>\n";
	print "		<td class='textAreaNotes v'>";
	$total = 0;
	if (sizeof($poller_item)) {
		foreach ($poller_item as $item) {
			print "Action[" . $item["action"] . "]: " . $item["total"] . "<br>";
			$total += $item["total"];
		}
		print __("Total:") . " " . $total;
	}else{
		print "<span class='warning'>" . __("No items to poll") . "</span>";
	}
	print "</td>\n";
	print "</tr>\n";

	print "<tr class='rowAlternate3'>\n";
	print "		<td class='textAreaNotes e'>" . __("Concurrent Processes") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . read_config_option("concurrent_processes") . "</td>\n";
	print "</tr>\n";

	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("Max Threads") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . read_config_option("max_threads") . "</td>\n";
	print "</tr>\n";

	print "<tr class='rowAlternate3'>\n";
	print "		<td class='textAreaNotes e'>" . __("PHP Servers") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . read_config_option("php_servers") . "</td>\n";
	print "</tr>\n";

	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("Script Timeout") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . read_config_option("script_timeout") . "</td>\n";
	print "</tr>\n";

	print "<tr class='rowAlternate3'>\n";
	print "		<td class='textAreaNotes e'>" . __("Max OID") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . read_config_option("max_get_size") . "</td>\n";
	print "</tr>\n";

	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("Last Run Statistics") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . read_config_option("stats_poller") . "</td>\n";
	print "</tr>\n";

	print "</table></td></tr>";		/* end of html_header */
	print "<tr><td>";
	html_header(array(array("name" => __("PHP Information"))), 2, '', '', 'left wp100');
	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("PHP Version") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . phpversion() . "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate3'>\n";
	print "		<td class='textAreaNotes e'>" . __("PHP OS") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . PHP_OS . "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("PHP uname") . "</td>\n";
	print "		<td class='textAreaNotes v'>";
	if (function_exists("php_uname")) {
		print php_uname();
	}else{
		print __("N/A");
	}
	print "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate3'>\n";
	print "		<td class='textAreaNotes e'>" . __("PHP SNMP") . "</td>\n";
	print "		<td class='textAreaNotes v'>";
	if (function_exists("snmpget")) {
		print __("Installed");
	} else {
		print __("Not Installed");
	}
	print "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>max_execution_time</td>\n";
	print "		<td class='textAreaNotes v'>" . ini_get("max_execution_time") . "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate3'>\n";
	print "		<td class='textAreaNotes e'>memory_limit</td>\n";
	print "		<td class='textAreaNotes v'>" . ini_get("memory_limit");

	/* Calculate memory suggestion based off of data source count */
	$memory_suggestion = $data_total * 32768;
	/* Set minimum - 16M */
	if ($memory_suggestion < 16777216) {
		$memory_suggestion = 16777216;
	}
	/* Set maximum - 512M */
	if ($memory_suggestion > 536870912) {
		$memory_suggestion = 536870912;
	}
	/* Suggest values in 8M increments */
	$memory_suggestion = round($memory_suggestion / 8388608) * 8388608;
	if (memory_bytes(ini_get('memory_limit')) < $memory_suggestion) {
		print "<br><span class='warning'>";
		if ((ini_get('memory_limit') == -1)) {
			print __("You've set memory limit to 'unlimited'.") . "<br/>";
		}
		print __("It is highly suggested that you either alter you php.ini memory_limit to %s or higher, or set a value for \$config['memory_limit'] in include/config.php. <br/>This suggested memory value is calculated based on the number of data source present and is only to be used as a suggestion, actual values may vary system to system based on requirements.", memory_readable($memory_suggestion));
		print "</span><br>";
	}
	print "</table></td></tr>";		/* end of html_header */

	html_end_box();
}

function display_database() {
	global $rrdtool_versions;

	/* Get table status */
	$table_status = db_fetch_assoc("SHOW TABLE STATUS");

	$display_array = array(
		array("name" => __("Name"), "align" => "left"),
		array("name" => __("Engine"), "align" => "left"),
		array("name" => __("Version"), "align" => "left"),
		array("name" => __("Row Format"), "align" => "left"),
		array("name" => __("Rows"), "align" => "right"),
		array("name" => __("Average Length"), "align" => "right"),
		array("name" => __("Data Length"), "align" => "right"),
		array("name" => __("Index Length"), "align" => "right"),
		array("name" => __("Auto Increment"), "align" => "left"),
		array("name" => __("Collation"), "align" => "left"));

	html_start_box(__("MySQL Table Information"), "100", "3", "center", "");
	print "<tr><td>";
	html_header($display_array);
	if (sizeof($table_status) > 0) {
		foreach ($table_status as $item) { #print "<pre>"; print_r($item); print "</pre>";
			form_alternate_row_color("row_" . $item["Name"]);
			print "<td>" . $item["Name"] . "</td>\n";
			if (isset($item["Engine"])) {
				print "  <td>" . $item["Engine"] . "</td>\n";
			}else{
				print "  <td>" . __("Unknown") . "</td>\n";
			}
			print "<td>" . $item["Version"] . "</td>\n";
			print "<td>" . $item["Row_format"] . "</td>\n";
			print "<td style='text-align:right;'>" . number_format($item["Rows"]) . "</td>\n";
			print "<td style='text-align:right;'>" . number_format($item["Avg_row_length"]) . "</td>\n";
			print "<td style='text-align:right;'>" . number_format($item["Data_length"]) . "</td>\n";
			print "<td style='text-align:right;'>" . number_format($item["Index_length"]) . "</td>\n";
			print "<td style='text-align:right;'>" . number_format($item["Auto_increment"]) . "</td>\n";
			if (isset($item["Collation"])) {
				print "  <td>" . $item["Collation"] . "</td>\n";
			} else {
				print "  <td>". __("Unknown") . "</td>\n";
			}
			print "</tr>\n";
		}
	}else{
		print __("Unable to retrieve table status");
	}
	print "</table></td></tr>";		/* end of html_header */
	html_end_box();
}

function display_database_processes() {
	global $rrdtool_versions;

	/* Get table status */
	$db_processes = db_fetch_assoc("SHOW PROCESSLIST");

	$display_array = array(
		array("name" => __("ID")),
		array("name" => __("User")),
		array("name" => __("Host")),
		array("name" => __("Database")),
		array("name" => __("Command")),
		array("name" => __("Time"), "align" => "right"),
		array("name" => __("State")),
		array("name" => __("Info"))
	);

	html_start_box(__("MySQL Process Information"), "100", "3", "center", "");
	print "<tr><td>";
	html_header($display_array);
	if (sizeof($db_processes) > 0) {
		foreach ($db_processes as $item) {
			form_alternate_row_color("row_" . $item["Id"]);
			print "<td>" . $item["Id"] . "</td>\n";
			print "<td>" . $item["User"] . "</td>\n";
			print "<td>" . $item["Host"] . "</td>\n";
			print "<td>" . $item["db"] . "</td>\n";
			print "<td>" . $item["Command"] . "</td>\n";
			print "<td style='text-align:right'>" . number_format($item["Time"]) . "</td>\n";
			print "<td>" . $item["State"] . "</td>\n";
			print "<td>" . $item["Info"] . "</td>\n";
			print "</tr>\n";
		}
	}else{
		print __("Unable to retrieve process status");
	}
	print "</table></td></tr>";		/* end of html_header */
	html_end_box();
}

function display_languages() {
	global $cacti_textdomains, $lang2locale, $i18n_modes, $cacti_locale;

	$loaded_extensions = get_loaded_extensions();

	$language = $lang2locale[$cacti_locale]["language"];

	/* rebuild $lang2locale array to find country and language codes easier */
	$locations = array();
	foreach($lang2locale as $locale => $properties) {
		$locations[$properties['filename'] . ".mo"] = $properties["language"];
	}

	/* create a list of all languages this Cacti system supports ... */
	$dhandle = opendir(CACTI_BASE_PATH . "/locales/LC_MESSAGES");
	$supported_languages["cacti"] = __("English") . ", ";
	while (false !== ($filename = readdir($dhandle))) {
		if(isset($locations[$filename])) {
			$supported_languages["cacti"] .= $locations[$filename] . ", ";
		}
	}
	$supported_languages["cacti"] = substr($supported_languages["cacti"], 0, -2);

	/* ... and do the same for all installed plugins */
	$plugins = db_fetch_assoc("SELECT `directory` FROM `plugin_config` ORDER BY sequence ASC");

	if(sizeof($plugins)>0) {
		foreach($plugins as $plugin) {

			$plugin = $plugin["directory"];
			$dhandle = @opendir(CACTI_BASE_PATH . "/plugins/" . $plugin . "/locales/LC_MESSAGES");
			$supported_languages[$plugin] = __("English") . ", ";
			if($dhandle) {
				while (false !== ($filename = readdir($dhandle))) {
					if(isset($locations[$filename])) {
						$supported_languages[$plugin] .= $locations[$filename] . ", ";
					}
				}
			}
			$supported_languages[$plugin] = substr($supported_languages[$plugin], 0, -2);
		}
	}


	html_start_box(__("Language Information"), "100", "3", "center", "");
	html_header(array(array("name" => __("General Information"))), 2,'','','left wp100','');
	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("Current Language") . "</td>\n";
	print "		<td class='textAreaNotes v'>". $language . "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate2'>\n";
	print "		<td class='textAreaNotes e'>" . __("Language Mode") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . $i18n_modes[read_config_option('i18n_language_support')] . "</td>\n";
	print "</tr>\n";
	print "<tr class='rowAlternate1'>\n";
	print "		<td class='textAreaNotes e'>" . __("Default Language") . "</td>\n";
	print "		<td class='textAreaNotes v'>" . __("English") . "</td>\n";
	print "</tr>\n";
	/* html_header is resizable by default, need to pass 'false' */
	html_header(array(array("name" => __("Supported Languages"))), 2, '', '', 'left wp100');
	$i = 0;
	if(sizeof($supported_languages)>0) {
		foreach($supported_languages as $domain => $languages) {
			$class_int = $i % 2 +1;
			print "<tr class='rowAlternate" . $class_int . "'>\n";
			print "		<td class='textAreaNotes e'>" . ucfirst($domain) . "</td>\n";
			print "		<td class='textAreaNotes v'>". $languages . "</td>\n";
			print "</tr>\n";
			$i++;
		}
	}else {
			print "<tr class='rowAlternate1'>\n";
			print "		<td class='textAreaNotes v'><i>" . __("no languages supported."). "</i></td>\n";
			print "</tr>\n";
	}
	/* html_header is resizable by default, need to pass 'false' */
	html_header(array(array("name" => __("Loaded Language Files"))), 2, '', '', 'left wp100');
	$i = 0;
	if(sizeof($cacti_textdomains)>0) {
		foreach($cacti_textdomains as $domain => $paths) {
			$class_int = $i % 2 +1;
			print "<tr class='rowAlternate" . $class_int . "'>\n";
			print "		<td class='textAreaNotes e'>" . ucfirst($domain) . "</td>\n";
			print "		<td class='textAreaNotes v'>". $paths['path2catalogue'] . "</td>\n";
			print "</tr>\n";
			$i++;
		}
	}else {
			print "<tr class='rowAlternate1'>\n";
			print "		<td class='textAreaNotes v'><i>" . __("No Language Files Loaded.") . "</i></td>\n";
			print "</tr>\n";
	}
	html_end_box();
}

function utilities_view_logfile() {
	global $log_tail_lines, $page_refresh_interval;

	$logfile = read_config_option("path_cactilog");

	if ($logfile == "") {
		$logfile = "./log/rrd.log";
	}

	/* helps determine output color */
	$linecolor = True;

	input_validate_input_number(get_request_var_request("tail_files"));
	input_validate_input_number(get_request_var_request("message_type"));
	input_validate_input_number(get_request_var_request("refresh"));
	input_validate_input_number(get_request_var_request("reverse"));

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST["clear_x"])) {
		kill_session_var("sess_logfile_tail_lines");
		kill_session_var("sess_logfile_message_type");
		kill_session_var("sess_logfile_filter");
		kill_session_var("sess_logfile_refresh");
		kill_session_var("sess_logfile_reverse");

		unset($_REQUEST["tail_lines"]);
		unset($_REQUEST["message_type"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["refresh"]);
		unset($_REQUEST["reverse"]);
	}

	load_current_session_value("tail_lines", "sess_logfile_tail_lines", read_config_option("num_rows_log"));
	load_current_session_value("message_type", "sess_logfile_message_type", "-1");
	load_current_session_value("filter", "sess_logfile_filter", "");
	load_current_session_value("refresh", "sess_logfile_refresh", read_config_option("log_refresh_interval"));
	load_current_session_value("reverse", "sess_logfile_reverse", 1);

	$_REQUEST['page_referrer'] = 'view_logfile';
	load_current_session_value('page_referrer', 'page_referrer', 'view_logfile');

	$refresh["seconds"] = $_REQUEST["refresh"];
	$refresh["page"] = "utilities.php?action=view_logfile";

	include_once(CACTI_BASE_PATH . "/include/top_header.php");

	?>
	<script type="text/javascript">
	<!--

	function applyViewLogFilterChange(objForm) {
		strURL = '?tail_lines=' + objForm.tail_lines.value;
		strURL = strURL + '&message_type=' + objForm.message_type.value;
		strURL = strURL + '&refresh=' + objForm.refresh.value;
		strURL = strURL + '&reverse=' + objForm.reverse.value;
		strURL = strURL + '&filter=' + objForm.filter.value;
		strURL = strURL + '&action=view_logfile';
		strURL = strURL + '&page=1';
		document.location = strURL;
	}

	-->
	</script>
	<?php

	html_start_box(__("Log File Filters"), "100", "3", "center", "", true);
	?>
	<tr class='rowAlternate3'>
		<td>
			<form name="form_logfile" action="utilities.php">
			<table cellpadding="1" cellspacing="0">
				<tr>
					<td class="nw80">
						&nbsp;<?php print __("Tail Lines:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="tail_lines" onChange="applyViewLogFilterChange(document.form_logfile)">
							<?php
							foreach($log_tail_lines AS $tail_lines => $display_text) {
								print "<option value='" . $tail_lines . "'"; if (get_request_var_request("tail_lines") == $tail_lines) { print " selected"; } print ">" . $display_text . "</option>\n";
							}
							?>
						</select>
					</td>
					<td class="nw100">
						&nbsp;<?php print __("Message Type:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="message_type" onChange="applyViewLogFilterChange(document.form_logfile)">
							<option value="-1"<?php if (get_request_var_request('message_type') == '-1') {?> selected<?php }?>><?php print __("All");?></option>
							<option value="1"<?php if (get_request_var_request('message_type') == '1') {?> selected<?php }?>><?php print __("Stats");?></option>
							<option value="2"<?php if (get_request_var_request('message_type') == '2') {?> selected<?php }?>><?php print __("Warnings");?></option>
							<option value="3"<?php if (get_request_var_request('message_type') == '3') {?> selected<?php }?>><?php print __("Errors");?></option>
							<option value="4"<?php if (get_request_var_request('message_type') == '4') {?> selected<?php }?>><?php print __("Debug");?></option>
							<option value="5"<?php if (get_request_var_request('message_type') == '5') {?> selected<?php }?>><?php print __("SQL Calls");?></option>
						</select>
					</td>
					<td class="nw200">
						&nbsp;<input type="submit" Value="<?php print __("Go");?>" name="go" align="middle">
						<input type="submit" Value="<?php print __("Clear");?>" name="clear_x" align="middle">
						<input type="submit" Value="<?php print __("Purge");?>" name="purge_x" align="middle">
					</td>
				</tr>
				<tr>
					<td class="nw80">
						&nbsp;<?php print __("Refresh:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="refresh" onChange="applyViewLogFilterChange(document.form_logfile)">
							<?php
							foreach($page_refresh_interval AS $seconds => $display_text) {
								print "<option value='" . $seconds . "'"; if (get_request_var_request("refresh") == $seconds) { print " selected"; } print ">" . $display_text . "</option>\n";
							}
							?>
						</select>
					</td>
					<td class="nw100">

						&nbsp;<?php print __("Display Order:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="reverse" onChange="applyViewLogFilterChange(document.form_logfile)">
							<option value="1"<?php if (get_request_var_request('reverse') == '1') {?> selected<?php }?>><?php print __("Newest First");?></option>
							<option value="2"<?php if (get_request_var_request('reverse') == '2') {?> selected<?php }?>><?php print __("Oldest First");?></option>
						</select>
					</td>
				</tr>
			</table>
			<table cellpadding="1" cellspacing="0">
				<tr>
					<td class="nw80">
						&nbsp;<?php print __("Search:");?>&nbsp;
					</td>
					<td class="w1">
						<input type="text" name="filter" size="75" value="<?php print $_REQUEST["filter"];?>">
					</td>
				</tr>
			</table>
			<div><input type='hidden' name='page' value='1'></div>
			<div><input type='hidden' name='action' value='view_logfile'></div>
			</form>
		</td>
	</tr>
	<?php
	html_end_box(false);

	/* read logfile into an array and display */
	$logcontents = tail_file($logfile, $_REQUEST["tail_lines"], $_REQUEST["message_type"], $_REQUEST["filter"]);

	if (get_request_var_request("reverse") == 1) {
		$logcontents = array_reverse($logcontents);
	}

	if (get_request_var_request("message_type") > 0) {
		$start_string = "<strong>" . __("Log File") . "</strong> [" . __("Total Lines:") . " " . sizeof($logcontents) . " - " . __("Non-Matching Items Hidden") . "]";
	}else{
		$start_string = "<strong>" . __("Log File") . "</strong> [" . __("Total Lines:") . " " . sizeof($logcontents) . " - " . __("All Items Shown") . "]";
	}

	html_start_box($start_string, "100", "0", "center", "");

	$i = 0;
	$j = 0;
	$linecolor = false;
	foreach ($logcontents as $item) {
		$new_item = create_object_link("Host[", "]", $item, "devices.php?action=edit&id=");
		$new_item = create_object_link("DS[", "]", $new_item, "data_sources.php?action=edit&id=");
		$new_item = create_object_link("Graph[", "]", $new_item, "graphs.php?action=graph_edit&id=");

		/* TODO: allow for more objects here? Tree Items require parent tree id! Plugin Hook? */
		/* e.g. Poller, Site, data Query, Data Input Method, ... */

		if ((substr_count($new_item, "ERROR")) || (substr_count($new_item, "FATAL"))) {
			$log_class = "log_error_fatal";
		}elseif (substr_count($new_item, "WARN")) {
			$log_class = "log_warn";
		}elseif (substr_count($new_item, " SQL ")) {
			$log_class = "log_sql";
		}elseif (substr_count($new_item, "DEBUG")) {
			$log_class = "log_debug";
		}elseif (substr_count($new_item, "STATS")) {
			$log_class = "log_stats";
		}else{
			if ($linecolor) {
				$log_class = "log_default1";
			}else{
				$log_class = "log_default2";
			}
			$linecolor = !$linecolor;
		}

		?>
		<tr class="<?php print $log_class ?>">
			<td>
				<?php print $new_item;?>
			</td>
		</tr>
		<?php
		$j++;
		$i++;

		if ($j > 1000) {
			?>
			<tr class="log_warn">
				<td>
					<?php print ">>>>  " . __("LINE LIMIT OF 1000 LINES REACHED!!") . "  <<<<";?>
				</td>
			</tr>
			<?php

			break;
		}
	}

	html_end_box();

	include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
}

function utilities_clear_logfile() {
	load_current_session_value("refresh", "sess_logfile_refresh", read_config_option("log_refresh_interval"));

	$refresh["seconds"] = $_REQUEST["refresh"];
	$refresh["page"] = "utilities.php?action=view_logfile";

	include_once(CACTI_BASE_PATH . "/include/top_header.php");

	$logfile = read_config_option("path_cactilog");

	if ($logfile == "") {
		$logfile = "./log/cacti.log";
	}

	html_start_box(__("Clear Cacti Log File"), "100", "3", "center", "");
	if (file_exists($logfile)) {
		if (is_writable($logfile)) {
			$timestamp = format_date();
			$log_fh = fopen($logfile, "w");
			fwrite($log_fh, $timestamp . " - WEBUI: Cacti Log Cleared from Web Management Interface\n");
			fclose($log_fh);
			print "<tr><td>" . __("Cacti Log File Cleared") . "</td></tr>";
		}else{
			print "<tr><td><span class='warning'><b>" . __("Error: Unable to clear log, ") . __("no write permissions.") . "<b></span></td></tr>";		}
	}else{
		print "<tr><td><span class='warning'><b>" . __("Error: Unable to clear log, ") . __("file does not exist.") . "</b></span></td></tr>";
	}
	html_end_box();
}

function utilities() {
	html_start_box(__("Cacti System Utilities"), "100", "3", "center", "");

	print "<tr><td>";
	html_header(array(array("name" => __("Technical Support"))), 2, '', '', 'left wp100'); ?>

	<tr class="rowAlternate1">
		<td class="textAreaNotes e">
			<a class='linkEditMain' href='<?php print htmlspecialchars("utilities.php?action=view_tech#ui-tabs-1");?>'><?php print __("Technical Support");?></a>
		</td>
		<td class="textAreaNotes v">
			<?php print __("Cacti technical support page.  Used by developers and technical support persons to assist with issues in Cacti.  Includes checks for common configuration issues.");?>
		</td>
	</tr>

	<?php
	print "</table></td></tr>";		/* end of html_header */
	print "<tr><td>";
	html_header(array(array("name" => __("Log Administration"))), 2,'','','left wp100');?>

	<tr class="rowAlternate1">
		<td class="textAreaNotes e">
			<a class='linkEditMain' href='<?php print htmlspecialchars("utilities.php?action=view_logfile");?>'><?php print __("View Cacti Log File");?></a>
		</td>
		<td class="textAreaNotes v">
			<?php print __("The Cacti Log File stores statistic, error and other message depending on system settings.  This information can be used to identify problems with the poller and application.");?>
		</td>
	</tr>
	<tr class="rowAlternate3">
		<td class="textAreaNotes e">
			<a class='linkEditMain' href='<?php print htmlspecialchars("utilities.php?action=view_user_log");?>'><?php print __("View User Log");?></a>
		</td>
		<td class="textAreaNotes v">
			<?php print __("Allows Administrators to browse the user log.  Administrators can filter and export the log as well.");?>
		</td>
	</tr>

	<?php
	print "</table></td></tr>";		/* end of html_header */
	print "<tr><td>";
	html_header(array(array("name" => __("Poller Cache Administration"))), 2,'','','left wp100'); ?>

	<tr class="rowAlternate1">
		<td class="textAreaNotes e">
			<a class='linkEditMain' href='<?php print htmlspecialchars("utilities.php?action=view_poller_cache");?>'><?php print __("View Poller Cache");?></a>
		</td>
		<td class="textAreaNotes v">
			<?php print __("This is the data that is being passed to the poller each time it runs. This data is then in turn executed/interpreted and the results are fed into the rrd files for graphing or the database for display.");?>
		</td>
	</tr>
	<tr class="rowAlternate3">
		<td class="textAreaNotes e">
			<a class='linkEditMain' href='<?php print htmlspecialchars("utilities.php?action=view_snmp_cache");?>'><?php print __("View SNMP Cache");?></a>
		</td>
		<td class="textAreaNotes v">
			<?php print __("The SNMP cache stores information gathered from SNMP queries. It is used by cacti to determine the OID to use when gathering information from an SNMP-enabled device.");?>
		</td>
	</tr>

	<?php
	print "</table></td></tr>";		/* end of html_header */
	print "<tr><td>";
	html_header(array(array("name" => __("Font Cache Administration"))), 2,'','','left wp100'); ?>

	<tr class="rowAlternate1">
		<td class="textAreaNotes e">
			<a class='linkEditMain' href='<?php print htmlspecialchars("utilities.php?action=rebuild_font_cache");?>'><?php print __("Rebuild Font Cache");?></a>
		</td>
		<td class="textAreaNotes v">
			<?php print __("For Pango based RRDTool Versions, fonts are accessed via fc-list and cached by Cacti for easy selection via dropdown. This option rebuilds the Cacti-maintained font table.");?>
		</td>
	</tr>

	<?php

	print "</table></td></tr>";		/* end of html_header */

	plugin_hook('utilities_list');

	html_end_box();
}
