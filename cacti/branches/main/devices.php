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
require_once(CACTI_BASE_PATH . "/include/device/device_arrays.php");
include_once(CACTI_BASE_PATH . "/lib/device.php");
include_once(CACTI_BASE_PATH . "/lib/graph.php");
include_once(CACTI_BASE_PATH . "/lib/utility.php");
include_once(CACTI_BASE_PATH . "/lib/tree.php");
include_once(CACTI_BASE_PATH . "/lib/snmp.php");
include_once(CACTI_BASE_PATH . "/lib/ping.php");
include_once(CACTI_BASE_PATH . "/lib/html_tree.php");
include_once(CACTI_BASE_PATH . "/lib/data_query.php");
include_once(CACTI_BASE_PATH . "/lib/sort.php");
include_once(CACTI_BASE_PATH . "/lib/html_form_template.php");
include_once(CACTI_BASE_PATH . "/lib/template.php");
include_once(CACTI_BASE_PATH . "/lib/data_source.php");
include_once(CACTI_BASE_PATH . "/lib/rrd.php");

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
		device_edit(false);

		break;
	case 'edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		device_edit(true);
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
	case 'graphs':
		graph();

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
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		device();
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
}
