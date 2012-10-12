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

require_once(CACTI_INCLUDE_PATH . "/presets/preset_cdef_constants.php");

$cdef_operators = array(1 =>
	'+',
	'-',
	'*',
	'/',
	'%'
);

$cdef_functions = array(1 =>
	'ADDNAN',
	'SIN',
	'COS',
	'LOG',
	'EXP',
	'SQRT',
	'ATAN',
	'ATAN2',
	'FLOOR',
	'CEIL',
	'DEG2RAD',
	'RAD2DEG',
	'ABS',
	'SORT',
	'REV',
	'AVG',
	'TREND',
	'TRENDNAN',
	'PREDICT',
	'PREDICTSIGMA',
	'LT',
	'LE',
	'GT',
	'GE',
	'EQ',
	'NE',
	'UN',
	'ISINF',
	'IF',
	'MIN',
	'MAX',
	'LIMIT',
	'DUP',
	'POP',
	'EXC',
	'UNKN',
	'INF',
	'NEGINF',
	'PREV',
	'NOW',
	'TIME',
	'LTIME'
);

$cdef_item_types = array(
	CVDEF_ITEM_TYPE_FUNCTION	=> 'Function',
	CVDEF_ITEM_TYPE_OPERATOR	=> 'Operator',
	CVDEF_ITEM_TYPE_SPEC_DS		=> 'Special Data Source',
	CVDEF_ITEM_TYPE_CDEF		=> 'Another CDEF',
	CVDEF_ITEM_TYPE_STRING		=> 'Custom String',
);

$custom_data_source_types = array(
	'CURRENT_DATA_SOURCE'				=> 'Current Graph Item Data Source',
	'ALL_DATA_SOURCES_NODUPS'			=> 'All Data Sources (Do not Include Duplicates)',
	'ALL_DATA_SOURCES_DUPS'				=> 'All Data Sources (Include Duplicates)',
	'SIMILAR_DATA_SOURCES_NODUPS'		=> 'All Similar Data Sources (Do not Include Duplicates)',
	'SIMILAR_DATA_SOURCES_DUPS'			=> 'All Similar Data Sources (Include Duplicates)',
	'CURRENT_DS_MINIMUM_VALUE'			=> 'Current Data Source Item: Minimum Value',
	'CURRENT_DS_MAXIMUM_VALUE'			=> 'Current Data Source Item: Maximum Value',
	'CURRENT_GRAPH_MINIMUM_VALUE'		=> 'Graph: Lower Limit',
	'CURRENT_GRAPH_MAXIMUM_VALUE'		=> 'Graph: Upper Limit',
	'COUNT_ALL_DS_NODUPS'				=> 'Count of All Data Sources (Do not Include Duplicates)',
	'COUNT_ALL_DS_DUPS'					=> 'Count of All Data Sources (Include Duplicates)',
	'COUNT_SIMILAR_DS_NODUPS'			=> 'Count of All Similar Data Sources (Do not Include Duplicates)',
	'COUNT_SIMILAR_DS_DUPS'		 		=> 'Count of All Similar Data Sources (Include Duplicates)',
	'TIME_SHIFT_START'					=> 'Graph: Shift Start Time',
	'TIME_SHIFT_END'					=> 'Graph: Shift End Time',
	'GRAPH_START'						=> 'Graph: Start Time',
	'GRAPH_END'							=> 'Graph: End Time',
);
