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

require(CACTI_INCLUDE_PATH . "/presets/preset_rra_arrays.php");

/* file: rra.php, action: edit */
$fields_rra_edit = array(
	'name' => array(
		'method' => 'textbox',
		'friendly_name' => 'Name',
		'description' => 'How data is to be entered in RRAs.',
		'value' => '|arg1:name|',
		'max_length' => '100',
		'size' => '50'
	),
	'consolidation_function_id' => array(
		'method' => 'drop_multi',
		'friendly_name' => 'Consolidation Functions',
		'description' => 'How data is to be entered in RRAs.',
		'array' => $consolidation_functions,
		'sql' => 'select consolidation_function_id as id,rra_id from rra_cf where rra_id=|arg1:id|',
	),
	'x_files_factor' => array(
		'method' => 'textbox',
		'friendly_name' => 'X-Files Factor',
		'description' => 'The amount of unknown data that can still be regarded as known.',
		'value' => '|arg1:x_files_factor|',
		'max_length' => '10',
		'size' => '10'
	),
	'steps' => array(
		'method' => 'textbox',
		'friendly_name' => 'Steps',
		'description' => 'How many data points are needed to put data into the RRA.',
		'value' => '|arg1:steps|',
		'max_length' => '8',
		'size' => '10'
	),
	'rows' => array(
		'method' => 'textbox',
		'friendly_name' => 'Rows',
		'description' => 'How many generations data is kept in the RRA.',
		'value' => '|arg1:rows|',
		'max_length' => '12',
		'size' => '10'
	),
	'timespan' => array(
		'method' => 'textbox',
		'friendly_name' => 'Timespan',
		'description' => 'How many seconds to display in graph for this RRA.',
		'value' => '|arg1:timespan|',
		'max_length' => '12',
		'size' => '10'
	),
	'id' => array(
		'method' => 'hidden_zero',
		'value' => '|arg1:id|'
	),
	'save_component_rra' => array(
		'method' => 'hidden',
		'value' => '1'
	)
);
