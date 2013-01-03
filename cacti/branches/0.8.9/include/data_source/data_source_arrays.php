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

require_once(CACTI_INCLUDE_PATH . "/data_source/data_source_constants.php");

$ds_actions = array(
	DS_ACTION_DELETE => "Delete",
	DS_ACTION_CHANGE_TEMPLATE => "Change Data Source Template",
	DS_ACTION_DUPLICATE => "Duplicate",
	DS_ACTION_CONVERT_TO_TEMPLATE => "Convert to Data Source Template",
	DS_ACTION_CHANGE_HOST => "Change Host",
	DS_ACTION_REAPPLY_SUGGESTED_NAMES => "Reapply Suggested Names",
	DS_ACTION_ENABLE => "Enable",
	DS_ACTION_DISABLE => "Disable",
	);

$data_source_types = array(
	DATA_SOURCE_TYPE_GAUGE		=> "GAUGE",
	DATA_SOURCE_TYPE_COUNTER	=> "COUNTER",
	DATA_SOURCE_TYPE_DERIVE		=> "DERIVE",
	DATA_SOURCE_TYPE_ABSOLUTE	=> "ABSOLUTE",
	DATA_SOURCE_TYPE_COMPUTE	=> "COMPUTE"
	);
