<?/* 
+-------------------------------------------------------------------------+
| Copyright (C) 2002 Ian Berry                                            |
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
| cacti: the rrdtool frontend [php-auth, php-tree, php-form]              |
+-------------------------------------------------------------------------+
| This code is currently maintained and debugged by Ian Berry, any        |
| questions or comments regarding this code should be directed to:        |
| - iberry@raxnet.net                                                     |
+-------------------------------------------------------------------------+
| - raXnet - http://www.raxnet.net/                                       |
+-------------------------------------------------------------------------+
*/?>
<?

$messages = array(
	1  => array(
		"message" => 'Save Successful.',
		"type" => "info"),
	2  => array(
		"message" => 'Save Failed',
		"type" => "error"),
		);

$cdef_operators = array(1 => "+",
			     "-",
			     "*",
			     "/",
			     "%");

$cdef_functions = array(1 => "SIN",
			     "COS",
			     "LOG",
			     "EXP",
			     "FLOOR",
			     "CEIL",
			     "LT",
			     "LE",
			     "GT",
			     "GE",
			     "EQ",
			     "IF",
			     "MIN",
			     "MAX",
			     "LIMIT",
			     "DUP",
			     "EXC",
			     "POP",
			     "UN",
			     "UNKN",
			     "PREV",
			     "INF",
			     "NEGINF",
			     "NOW",
			     "TIME",
			     "LTIME");

				
$consolidation_functions = array(1 => "AVERAGE",
				      "MIN",
				      "MAX",
				      "LAST");
					
$data_source_types = array(1 => "GAUGE",
				"COUNTER",
				"DERIVE",
				"ABSOLUTE");
				
$graph_item_types = array(1 => "COMMENT",
			       "HRULE",
			       "VRULE",
			       "LINE1",
			       "LINE2",
			       "LINE3",
			       "AREA",
			       "STACK",
			       "GPRINT");

$image_types = array(1 => "PNG",
			  "GIF");

$struct_graph = array("image_format_id", "title", "height", "width", "upper_limit",
		      "lower_limit", "vertical_label", "auto_scale", "auto_scale_opts",
		      "auto_scale_log", "auto_scale_rigid", "auto_padding", "base_value",
		      "grouping", "export", "unit_value", "unit_exponent_value");

$struct_graph_item = array("task_item_id", "color_id", "graph_type_id", "cdef_id", "consolidation_function_id",
			    "text_format", "value", "hard_return", "gprint_id", "sequence");

$struct_data_source = array("name", "active", "rrd_step", "FORCE:data_input_id");

$struct_data_source_item = array("rrd_maximum", "rrd_minimum", "rrd_heartbeat", "data_source_type_id", "data_source_name", "FORCE:data_input_field_id");

$snmp_versions = array(1 => "Version 1",
			    "Version 2",
			    "Version 3");

$registered_cacti_names = array("path_cacti");
?>
