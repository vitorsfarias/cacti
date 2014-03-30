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

include_once(CACTI_LIBRARY_PATH . "/tree.php");

/** DEPRECATED!  */
function api_tree_item_save($id, $tree_id, $type, $parent_tree_item_id, $title, $local_graph_id, $rra_id,
	$host_id, $host_grouping_type, $sort_children_type, $propagate_changes) {
	cacti_log("function " . __FUNCTION__ . " called", false, "DEPRECATION WARNING");
	return tree_item_save($id, $tree_id, $type, $parent_tree_item_id, $title, $local_graph_id, $rra_id,
	$host_id, $host_grouping_type, $sort_children_type, $propagate_changes);
}


