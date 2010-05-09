<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2010 The Cacti Group                                 |
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

include('./include/auth.php');

/* tab information */
$ptabs = array(
	'all'       => __('Plugins'),
);

$plugins = plugins_get_plugins_list();

$ptabs = api_plugin_hook_function ('plugin_management_tabs', $ptabs);

/* set the default settings category */
load_current_session_value('tab', 'sess_plugins_tab', 'all');
$current_tab = $_REQUEST['tab'];

$modes = array('all', 'disable', 'enable', 'check');

if (isset($_GET['mode']) && in_array($_GET['mode'], $modes)  && isset($_GET['id'])) {
	input_validate_input_regex(get_request_var('id'), '/^([a-zA-Z0-9]+)$/');

	$mode = $_GET['mode'];
	$id = sanitize_search_string($_GET['id']);

	switch ($mode) {
		case 'disable':
			if (!isset($plugins[$id]))
				break;
			api_plugin_disable($id);
			Header("Location: " . CACTI_URL_PATH . "plugins.php\n\n");
			exit;
		case 'enable':
			if (!isset($plugins[$id]))
				break;
			api_plugin_enable($id);
			Header("Location: " . CACTI_URL_PATH . "plugins.php\n\n");
			exit;
	}
}

include(CACTI_BASE_PATH . "/include/top_header.php");

plugins_draw_tabs($ptabs, $current_tab);

html_start_box('<strong>' . __('Plugins') . ' (' . $ptabs[$current_tab] . ')</strong>', '100', '3', 'center', '');

print "<tr><td><table width='100%'>";

switch ($current_tab) {
	case 'all':
		plugins_show();
		break;
	default:
		api_plugin_hook_function('plugin_management_tab_content', $current_tab);
}

html_end_box();

include(CACTI_BASE_PATH . "/include/bottom_footer.php");

function plugins_get_plugins_list () {
	$info  = db_fetch_assoc('SELECT * FROM plugin_config ORDER BY directory');
	$plugins = array();
	if (!empty($info)) {
		foreach ($info as $p) {
			$plugins[$p['directory']] = $p;
		}
	}
	return $plugins;
}

function plugins_draw_tabs ($tabs, $current_tab) {
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

function plugins_show($status = 'all') {
	global $plugins, $colors, $config, $status_names;

	print "<table width='100%' cellspacing=0 cellpadding=3>";

	$display_text = array(
		array("id" => "", "name" => __(" "), "order" => "ASC"),
		array("id" => "directory", "name" => __("Plugin"), "order" => "ASC"),
		array("id" => "longname", "name" => __("Name"), "order" => "ASC"),
		array("id" => "version", "name" => __("Version"), "order" => "ASC"),
		array("id" => "author", "name" => __("Author"), "order" => "ASC"),
		array("id" => "webpage", "name" => __("Webpage"), "order" => "ASC")
	);

	html_header_sort($display_text, get_request_var_request("sort_column"), get_request_var_request("sort_direction"));

	if (count($plugins)) {
		foreach ($plugins as $plugin) {
				form_alternate_row_color('line' . $plugin['id'], true);
				$link = '';
				switch ($plugin['status']) {
					case 1:	// Currently Active
						$link = "<a href='" . htmlspecialchars("plugins.php?mode=disable&id=" . $plugin['directory']) . "' class='linkEditMain'><img border=0 src=images/disable_icon.png></a>";
						break;
					case 4:	// Installed but not active
						$link = "<a href='" . htmlspecialchars("plugins.php?mode=enable&id=" . $plugin['directory']) . "' class='linkEditMain'><img border=0 src=images/enable_icon.png></a>";
						break;
				}
				form_selectable_cell($link, $plugin['directory']);
				form_selectable_cell($plugin['directory'], $plugin['directory']);
				form_selectable_cell((isset($plugin['name']) ? $plugin['name'] : $plugin['directory']), $plugin['directory']);
				form_selectable_cell((isset($plugin['version']) ? $plugin['version'] : ''), $plugin['directory']);
				form_selectable_cell((isset($plugin['author']) ? $plugin['author'] : ''), $plugin['directory']);
				form_selectable_cell((isset($plugin['webpage']) ? $plugin['webpage'] : ''), $plugin['directory']);
				form_end_row();
		}
		form_end_table();
	} else {
		form_alternate_row_color('line0', true);
		print '<td colspan=6><center>' . __("There are no installed Plugins") . '</center></td>';
		form_end_row();
	}

	print '</table>';
	html_end_box(FALSE);
}

