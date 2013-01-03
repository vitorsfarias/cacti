<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2013 The Cacti Group                                 |
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

require_once(CACTI_INCLUDE_PATH . '/graph_tree/graph_tree_constants.php');

$tree_types = array(
	TREE_TYPE_TREE => __('Tree'),
	TREE_TYPE_NODE => __('Node'),
);

$tree_item_types = array(
	TREE_ITEM_TYPE_HEADER => __('Header'),
	TREE_ITEM_TYPE_GRAPH => __('Graph'),
	TREE_ITEM_TYPE_HOST => __('Device'),
);

$tree_device_group_types = array(
	HOST_GROUPING_GRAPH_TEMPLATE => __('Graph Template'),
	HOST_GROUPING_DATA_QUERY_INDEX => __('Data Query Index'),
);
