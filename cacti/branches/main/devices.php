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
require_once(CACTI_INCLUDE_PATH . "/device/device_arrays.php");
include_once(CACTI_LIBRARY_PATH . "/device.php");
include_once(CACTI_LIBRARY_PATH . "/graph.php");
include_once(CACTI_LIBRARY_PATH . "/utility.php");
include_once(CACTI_LIBRARY_PATH . "/tree.php");
include_once(CACTI_LIBRARY_PATH . "/snmp.php");
include_once(CACTI_LIBRARY_PATH . "/ping.php");
include_once(CACTI_LIBRARY_PATH . "/html_tree.php");
include_once(CACTI_LIBRARY_PATH . "/data_query.php");
include_once(CACTI_LIBRARY_PATH . "/sort.php");
include_once(CACTI_LIBRARY_PATH . "/html_form_template.php");
include_once(CACTI_LIBRARY_PATH . "/template.php");
include_once(CACTI_LIBRARY_PATH . "/data_source.php");
include_once(CACTI_LIBRARY_PATH . "/rrd.php");

define("MAX_DISPLAY_PAGES", 21);

$device_actions = plugin_hook_function('device_action_array', $device_actions);

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch (get_request_var_request('action')) {
	case 'save':
		device_form_save();

		break;
	case 'actions':
		device_form_actions();

		break;
	case 'add_gt':
		device_add_gt();

		break;
	case 'gt_remove':
		device_remove_gt();

		break;
	case 'add_dq':
		device_add_dq();

		break;
	case 'query_remove':
		device_remove_query();

		break;
	case 'query_reload':
		device_reload_query();

		break;
	case 'query_verbose':
		device_reload_query();

		break;
	case 'ajax':
		device_ajax_actions();

		break;
	case 'ajax_edit':
	case 'ajax_edit_graph_template':
	case 'ajax_edit_data_query':
		device_edit(false);

		break;
	case 'edit':
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		device_edit(true);
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
	case 'graphs':
		cacti_graph();

		break;
	case 'data_sources':
		data_source();

		break;
	case 'graphs_new':
		graphs_new();

		break;
	case 'ajax_view':
		device();

		break;
	default:
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		device();
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
}
