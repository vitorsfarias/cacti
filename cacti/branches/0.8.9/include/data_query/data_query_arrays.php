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

require_once(CACTI_BASE_PATH . "/include/data_query/data_query_constants.php");

$reindex_types = array(
	DATA_QUERY_AUTOINDEX_NONE => "None",
	DATA_QUERY_AUTOINDEX_BACKWARDS_UPTIME => "Uptime Goes Backwards",
	DATA_QUERY_AUTOINDEX_INDEX_COUNT_CHANGE => "Index Count Changed",
	DATA_QUERY_AUTOINDEX_VALUE_CHANGE => "Index Value Changed",
	DATA_QUERY_AUTOINDEX_FIELD_VERIFICATION => "Verify All Fields",
	);

$tree_sort_types = array(
	DATA_QUERY_INDEX_SORT_TYPE_NONE => "Manual Ordering (No Sorting)",
	DATA_QUERY_INDEX_SORT_TYPE_ALPHABETIC => "Alphabetic Ordering",
	DATA_QUERY_INDEX_SORT_TYPE_NATURAL => "Natural Ordering",
	DATA_QUERY_INDEX_SORT_TYPE_NUMERIC => "Numeric Ordering",
	);

$tree_sort_types_cli = array(
	DATA_QUERY_INDEX_SORT_TYPE_NONE => "manual",
	DATA_QUERY_INDEX_SORT_TYPE_ALPHABETIC => "alpha",
	DATA_QUERY_INDEX_SORT_TYPE_NATURAL => "natural",
	DATA_QUERY_INDEX_SORT_TYPE_NUMERIC => "numeric",
	);

$data_query_nullx_options = array(
	DATA_QUERY_INDEX_IGNORE_NOTHING => "Disable",
	DATA_QUERY_INDEX_IGNORE_NULLS => "Ignore All Nulls",
	DATA_QUERY_INDEX_IGNORE_DUP_NULLS => "Ignore Only Duplicate Nulls",
	);
	