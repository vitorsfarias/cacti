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

define("MAX_DISPLAY_PAGES", 21);

include('./include/auth.php');

/* tab information */
$ptabs = array(
	'all'       => __('Plugins'),
);

/* set the default settings category */
load_current_session_value('tab', 'sess_plugin_tab', 'all');
$current_tab = $_REQUEST['tab'];
$pluginslist = plugin_get_plugins_list();

$ptabs = api_plugin_hook_function('plugin_management_tabs', $ptabs);

/* Check to see if we are installing, etc... */
$modes = array('plugin_dnd', 'installold', 'uninstallold', 'install', 'uninstall', 'disable', 'enable', 'check', 'moveup', 'movedown');

if (isset($_GET['mode']) && in_array($_GET['mode'], $modes)  && isset($_GET['id'])) {
	input_validate_input_regex(get_request_var("id"), "/^([a-zA-Z0-9]+)$/");

	$mode = $_GET['mode'];
	$id   = sanitize_search_string($_GET['id']);

	switch ($mode) {
		case 'plugin_dnd':
			plugin_dnd();
			break;
		case 'installold':
			api_plugin_install_old($id);
			header("Location: plugins.php");
			exit;
			break;
		case 'uninstallold':
			api_plugin_uninstall_old($id);
			header("Location: plugins.php");
			exit;
			break;
		case 'install':
			api_plugin_install($id);
			header("Location: plugins.php");
			exit;
			break;
		case 'uninstall':
			if (!in_array($id, $pluginslist)) break;
			api_plugin_uninstall($id);
			header("Location: plugins.php");
			exit;
			break;
		case 'disable':
			if (!in_array($id, $pluginslist)) break;
			api_plugin_disable($id);
			header("Location: plugins.php");
			exit;
			break;
		case 'enable':
			if (!in_array($id, $pluginslist)) break;
			api_plugin_enable($id);
			header("Location: plugins.php");
			exit;
			break;
		case 'check':
			if (!in_array($id, $pluginslist)) break;
			break;
	}
}
include(CACTI_BASE_PATH . "/include/top_header.php");

#plugin_draw_tabs($ptabs, $current_tab);
#html_start_box('<strong>' . __('Plugins') . ' (' . $ptabs[$current_tab] . ')</strong>', '100', '3', 'center', '');
#print "<tr><td>";

switch ($current_tab) {
	case 'all':
		plugin_show();
		break;
	default:
		api_plugin_hook_function('plugin_management_tab_content', $current_tab);
}

#print "</td></tr>";
#html_end_box();

include(CACTI_BASE_PATH . "/include/bottom_footer.php");




function plugin_dnd(){
	#todo: exclude system plugins from being sorted

	if(!isset($_REQUEST['plugin_list']) || !is_array($_REQUEST['plugin_list'])) exit;
	/* plugins table contains one row defined as "nodrag&nodrop" */
	unset($_REQUEST['plugin_list'][0]);

	/* delivered plugin ids are in new order */
	$old_order = array();
	$new_order = array();

	# id's are passed in new sequence order, but each item is preceeded by 'line'
	if(sizeof($_REQUEST['plugin_list'])>0) {
		foreach($_REQUEST['plugin_list'] as $item) {
			$new_order[] = str_replace('line', '', $item);
		}
	}else {
		exit;
	}

	$sql = "SELECT id, sequence FROM plugin_config ORDER BY sequence ASC";
	$plugins = db_fetch_assoc($sql);

	if(sizeof($plugins)>0) {
		foreach($plugins as $item) {
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

	foreach($diff as $sequence => $plugin_id) {
		$sql = "UPDATE plugin_config SET sequence = $sequence WHERE id = $plugin_id";
		db_execute($sql);
	}
}

function plugin_get_plugins_list() {

	$pluginslist = array();
	$temp = db_fetch_assoc('SELECT directory FROM plugin_config ORDER BY sequence ASC');
	foreach ($temp as $t) {
		$pluginslist[] = $t['directory'];
	}
	return $pluginslist;
}

function plugin_draw_tabs($tabs, $current_tab) {
	/* draw the categories tabs on the top of the page */
	print "<table width='100%' cellspacing='0' cellpadding='0' align='center'><tr>\n";
	print "<td><div class='tabs'>";
	if (sizeof($tabs) > 0) {
		foreach (array_keys($tabs) as $tab_short_name) {
			print "<div class='tabDefault'><a " . (($tab_short_name == $current_tab) ? "class='tabSelected'" : "class='tabDefault'") . " href='plugins.php?tab=$tab_short_name'>$tabs[$tab_short_name]</a></div>";
		}
	}
	print "</div></td></tr></table>\n";
}

function plugin_temp_table_exists($table) {
	return sizeof(db_fetch_row("SHOW TABLES LIKE '$table'"));
}

function plugin_load_temp_table() {
	global $config, $plugins;

	$pluginslist = plugin_get_plugins_list();

	if (isset($_SESSION["plugin_temp_table"])) {
		$table = $_SESSION["plugin_temp_table"];
	}else{
		$table = "plugin_temp_table_" . rand();
	}
	$x = 0;
	while ($x < 30) {
		if (!plugin_temp_table_exists($table)) {
			$_SESSION["plugin_temp_table"] = $table;
			db_execute("CREATE TEMPORARY TABLE IF NOT EXISTS $table LIKE plugin_config");
			db_execute("TRUNCATE $table");
			db_execute("INSERT INTO $table SELECT * FROM plugin_config");
			break;
		}else{
			$table = "plugin_temp_table_" . rand();
		}
		$x++;
	}
	$sequence = db_fetch_cell("SELECT MAX(sequence) FROM plugin_config");
	$sequence++;

	$path = CACTI_BASE_PATH . '/plugins/';

	$dh = opendir($path);
	while (($file = readdir($dh)) !== false) {
		if ((is_dir("$path/$file")) && (file_exists("$path/$file/setup.php")) && (!in_array($file, $pluginslist))) {
			# a setup file exists and this is a new plugin (not known in pluginlist)
			include_once("$path/$file/setup.php");
			if (!function_exists('plugin_' . $file . '_install') && function_exists($file . '_version')) {
				# version function exists but install function does not ==> this is an old plugin
				# get version info
				$function = $file . '_version';
				$cinfo[$file] = $function();
				if (!isset($cinfo[$file]['author']))   $cinfo[$file]['author']   = 'Unknown';
				if (!isset($cinfo[$file]['homepage'])) $cinfo[$file]['homepage'] = 'Not Stated';
				if (isset($cinfo[$file]['webpage']))   $cinfo[$file]['homepage'] = $cinfo[$file]['webpage'];
				if (!isset($cinfo[$file]['longname'])) $cinfo[$file]['longname'] = ucfirst($file);
				
				# compute status
				$cinfo[$file]['status'] = -2;
				if (in_array($file, $plugins)) {
					$cinfo[$file]['status'] = -1;
				}

				# plugin type
				$ptype = plugin_is_system_plugin($file);
				
				# register new plugin into temp table for display
				db_execute("REPLACE INTO $table (directory, name, status, author, webpage, version, ptype, sequence)
					VALUES ('" .
						$file . "', '" .
						$cinfo[$file]['longname'] . "', '" .
						$cinfo[$file]['status']   . "', '" .
						$cinfo[$file]['author']   . "', '" .
						$cinfo[$file]['homepage'] . "', '" .
						$cinfo[$file]['version']  . "', '" .
						$ptype					  . "', '" .
						$sequence				  . "')");
				
				# add this plugin to pluginlist
				$pluginslist[] = $file;
				$sequence++;
				
				
			} elseif (function_exists('plugin_' . $file . '_install') && function_exists('plugin_' . $file . '_version')) {
				# version function exists AND install function exists ==> this is a new plugin
				# get version info
				$function               = $file . '_version';
				$cinfo[$file]           = $function();
				
				# status for new plugin is 
				$cinfo[$file]['status'] = 0;
				if (!isset($cinfo[$file]['author']))   $cinfo[$file]['author']   = 'Unknown';
				if (!isset($cinfo[$file]['homepage'])) $cinfo[$file]['homepage'] = 'Not Stated';
				if (isset($cinfo[$file]['webpage']))   $cinfo[$file]['homepage'] = $cinfo[$file]['webpage'];
				if (!isset($cinfo[$file]['longname'])) $cinfo[$file]['homepage'] = ucfirst($file);

				# plugin type
				$ptype = plugin_is_system_plugin($file);

				/* see if it's been installed as old, if so, remove from oldplugins array and session */
				$oldplugins = read_config_option("oldplugins");
				if (substr_count($oldplugins, $file)) {
					$oldplugins = str_replace($file, "", $oldplugins);
					$oldplugins = str_replace(",,", ",", $oldplugins);
					$oldplugins = trim($oldplugins, ",");
					set_config_option('oldplugins', $oldplugins);
					$_SESSION['sess_config_array']['oldplugins'] = $oldplugins;
				}

				# register new plugin into temp table for display
				db_execute("REPLACE INTO $table (directory, name, status, author, webpage, version, ptype, sequence)
					VALUES ('" .
						$file . "', '" .
						$cinfo[$file]['longname'] . "', '" .
						$cinfo[$file]['status']   . "', '" .
						$cinfo[$file]['author']   . "', '" .
						$cinfo[$file]['homepage'] . "', '" .
						$cinfo[$file]['version']  . "', '" .
						$ptype					  . "', '" .
						$sequence				  . "')");

				# add this plugin to pluginlist
				$pluginslist[] = $file;
				$sequence++;
			}
		}
	}
	closedir($dh);

	return $table;
}

function get_plugin_records(&$total_rows, &$rowspp) {

	/* get all currently known plugins by reading the plugins directory */
	$table = plugin_load_temp_table();

	/* form the 'where' clause for our main sql query */
	if (strlen(html_get_page_variable("filter"))) {
		$sql_where = "WHERE ($table.name LIKE '%%" . get_request_var_request("filter") . "%%')";
	}else{
		$sql_where = "";
	}

	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	$sortby = html_get_page_variable("sort_column");
	if ($sortby == "version") {
		$sortby = "version+0";
	}

	$sort_direction = html_get_page_variable("sort_direction");
	if ($sortby == "sequence") {
		$sort_direction = "ASC";
	}

	$total_rows = db_fetch_cell("select
		COUNT(id)
		from $table
		$sql_where");

	$sql_query = "SELECT * " .
		"FROM $table " . 
		"$sql_where " . 
		"ORDER BY " . $sortby . " " . $sort_direction . " " .
		"LIMIT " . ($rowspp*(html_get_page_variable("page")-1)) . "," . $rowspp;

	$plugins = db_fetch_assoc($sql_query);

	db_execute("DROP TABLE $table");
	
#	if (sizeof($plugins)) {
#		foreach ($plugins as $key => $value) {
#			# provide actions available
#			$plugins[$key]['actions'] = '';
#			# provide type
#			$plugins[$key]['type'] = '';
#		}
#	}

	return $plugins;
}

function plugin_filter() {
	global $item_rows, $config, $colors;
	require(CACTI_BASE_PATH . "/include/plugins/plugin_arrays.php");

	html_start_box("<strong>Plugin Management</strong> (Cacti Version: " . CACTI_VERSION .
		(isset($plugin_architecture['version']) ? ", Plugin Architecture Version: " . $plugin_architecture['version']:"") .
		")", "100", "3", "center", "", true);
	?>
	<tr class='rowAlternate2'>
		<td>
			<form action="plugins.php" name="form_plugins" method="post">
			<table cellpadding="0" cellspacing="3">
				<tr>
					<td class="nw50">
						&nbsp;<?php print __("Search:");?>&nbsp;
					</td>
					<td class="w1">
						<input type="text" name="filter" size="40" value="<?php print html_get_page_variable("filter");?>">
					</td>
					<td class="nw120">
						&nbsp;<input type="submit" Value="<?php print __("Go");?>" name="go" align="middle">
						<input type="button" Value="<?php print __("Clear");?>" name="clear" align="middle" onClick="clearPluginFilterChange(document.form_plugins)">
					</td>
					<td class="nw50">
						&nbsp;<?php print __("Rows:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="rows" onChange="applyPluginFilterChange(document.form_plugins)">
							<option value="-1"<?php if (html_get_page_variable("rows") == "-1") {?> selected<?php }?>><?php print __("Default");?></option>
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
			</form>
		</td>
	</tr>
	<?php
	html_end_box(false);
	?>
	<script type="text/javascript">
	<!--
	function clearPluginFilterChange(objForm) {
		strURL = strURL + '?filter=';
		strURL = strURL + '&rows=-1';
		document.location = strURL;
	}

	function applyPluginFilterChange(objForm) {
		strURL = '?filter=' + objForm.filter.value;
		strURL = strURL + '&rows=' + objForm.rows.value;
		document.location = strURL;
	}
	-->
	</script>
	<?php
}

function plugin_show($status = 'all', $refresh = true) {
	global $item_rows, $colors;

	$table = New html_table;

	$table->page_variables = array(
		"page"           => array("type" => "numeric", "method" => "request", "default" => "1"),
		"rows"           => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"filter"         => array("type" => "string",  "method" => "request", "default" => ""),
#		"tab"            => array("type" => "string",  "method" => "request", "default" => "", "nosession" => true),
		"sort_column"    => array("type" => "string",  "method" => "request", "default" => "sequence"),
		"sort_direction" => array("type" => "string",  "method" => "request", "default" => "ASC")
	);

	$table->table_format = array(
		"actions" => array(
			"name" => __("Actions"),
			"function" => "display_plugin_actions",
			"params" => array("directory", "status"),
			"order" => "nosort"
		),
		"directory" => array(
			"name" => __("Name"),
			"function" => "display_plugin_directory",
			"params" => array("directory", "webpage"),
			"filter" => true,
			"order" => "ASC"
		),
		"version" => array(
			"name" => __("Version"),
			"order" => "ASC"
		),
		"sequence" => array(
			"name" => __("Load Order"),
#			"function" => "display_plugin_ordering",
#			"params" => array("directory", "sequence"),
			"order" => "ASC"
		),
		"name" => array(
			"name" => __("Description"),
			"filter" => true,
			"order" => "ASC"
		),
		"ptype" => array(
			"name" => __("Type"),
			"function" => "display_plugin_type",
			"params" => array("directory", "status"),
			"order" => "ASC"
		),
		"status" => array(
			"name" => __("Status"),
			"function" => "display_plugin_status",
			"params" => array("status"),
			"order" => "ASC"
		),
		"author" => array(
			"name" => __("Author"),
			"order" => "ASC"
		)
	);

	/* initialize page behavior */
	$table->href           = "plugins.php";
	$table->session_prefix = "sess_plugins";
	$table->filter_func    = "plugin_filter";
	$table->refresh        = $refresh;
	$table->resizable      = true;
	$table->sortable       = true;
	$table->table_id       = "plugin_list";
	$table->row_function   = "row_plugin_class";
	$table->row_params     = array("ptype");
#	$table->actions        = $plugin_actions;

	/* we must validate table variables */
	$table->process_page_variables();

	/* get the records */
	$table->rows = get_plugin_records($table->total_rows, $table->rows_per_page);

	/* display the table */
	$table->draw_table();

	html_start_box("", "100%", $colors["header"], "3", "center", "");
	echo "<tr><td colspan=8><strong>" . __('NOTE:') . 
		"</strong> " . __("Change 'Load Order' by dragging and dropping.") . 
		"<br><strong>" . __('NOTE for SYSTEM plugins:') . 
		"</strong> " . __("SYSTEM plugins can not be reordered. Load order for them is determined by order of installation.") . "</td></tr>";
	html_end_box();

	?>
	<script type="text/javascript">
		$('#plugin_list').tableDnD({
			onDrop: function(table, row) {
				//alert($.tableDnD.serialize());
				$.get("plugins.php?mode=plugin_dnd&id=0&"+$.tableDnD.serialize());
			}
		});
	</script>
	<?php
}

