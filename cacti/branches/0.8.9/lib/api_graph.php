<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2014 The Cacti Group                                 |
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

include_once(CACTI_LIBRARY_PATH . "/graph.php");

/** DEPRECATED!  */
function api_graph_remove($local_graph_id) {
	cacti_log("function " . __FUNCTION__ . " called using $local_graph_id", false, "DEPRECATION WARNING");
	graph_remove($local_graph_id, false);
}

/** DEPRECATED!  */
function api_graph_remove_multi($local_graph_ids) {
	cacti_log("function " . __FUNCTION__ . " called", false, "DEPRECATION WARNING");
	graph_remove_multi($local_graph_ids);
}

/** DEPRECATED!  */
function api_resize_graphs($local_graph_id, $graph_width, $graph_height) {
	cacti_log("function " . __FUNCTION__ . " called using $local_graph_id", false, "DEPRECATION WARNING");
	resize_graphs($local_graph_id, $graph_width, $graph_height);
}

/** DEPRECATED!  */
function api_reapply_suggested_graph_title($local_graph_id) {
	cacti_log("function " . __FUNCTION__ . " called using $local_graph_id", false, "DEPRECATION WARNING");
	reapply_suggested_graph_title($local_graph_id);
}

/** DEPRECATED!  */
function api_get_graphs_from_datasource($local_data_id) {
	cacti_log("function " . __FUNCTION__ . " called using $local_graph_id", false, "DEPRECATION WARNING");
	return get_graphs_from_datasource($local_data_id);
}

?>
