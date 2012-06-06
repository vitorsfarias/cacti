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
include_once(CACTI_LIBRARY_PATH . "/utility.php");
include_once(CACTI_LIBRARY_PATH . "/tree.php");
include_once(CACTI_LIBRARY_PATH . "/data_source.php");
include_once(CACTI_LIBRARY_PATH . "/template.php");
include_once(CACTI_LIBRARY_PATH . "/html_tree.php");
include_once(CACTI_LIBRARY_PATH . "/html_form_template.php");
include_once(CACTI_LIBRARY_PATH . "/rrd.php");
include_once(CACTI_LIBRARY_PATH . "/data_query.php");
include_once(CACTI_LIBRARY_PATH . "/graph.php");

define("MAX_DISPLAY_PAGES", 21);

$graph_actions = plugin_hook_function('graphs_action_array', graph_actions_list());

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch (get_request_var_request("action")) {
	case 'save':
		graph_form_save();

		break;
	case 'actions':
		graph_form_actions();

		break;
	case 'graph_diff':
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		graph_diff();
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
	case 'item':
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		graph_item();
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
	case 'graph_remove':
		graph_remove();

		header("Location: graphs.php");

		break;
	case 'edit':
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		graph_edit();
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
	case 'ajax_get_graph_templates':
		ajax_get_graph_templates();

		break;
	case 'ajax_get_devices_detailed':
		ajax_get_devices_detailed();

		break;
	case 'ajax_get_devices_brief':
		ajax_get_devices_brief();

		break;
	case 'ajax_graph_item_dnd':
		graph_item_dnd();

		break;
	case 'ajax_view':
		cacti_graph();

		break;
	default:
		include_once(CACTI_INCLUDE_PATH . "/top_header.php");
		cacti_graph();
		include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");

		break;
}
