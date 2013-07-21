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

global $menu;

$actions_none = array(
	ACTION_NONE => 'None',
);

$messages = array(
	1  => array(
		"message" => 'Save Successful.',
		"type" => "info"),
	2  => array(
		"message" => 'Save Failed.',
		"type" => "error"),
	3  => array(
		"message" => 'Save Failed: Field Input Error (Check Red Fields).',
		"type" => "error"),
	4  => array(
		"message" => 'Passwords do not match, please retype.',
		"type" => "error"),
	5  => array(
		"message" => 'You must select at least one field.',
		"type" => "error"),
	6  => array(
		"message" => 'You must have built in user authentication turned on to use this feature.',
		"type" => "error"),
	7  => array(
		"message" => 'XML parse error.',
		"type" => "error"),
	12 => array(
		"message" => 'Username already in use.',
		"type" => "error"),
	15 => array(
		"message" => 'XML: Cacti version does not exist.',
		"type" => "error"),
	16 => array(
		"message" => 'XML: Hash version does not exist.',
		"type" => "error"),
	17 => array(
		"message" => 'XML: Generated with a newer version of Cacti.',
		"type" => "error"),
	18 => array(
		"message" => 'XML: Cannot locate type code.',
		"type" => "error"),
	19 => array(
		"message" => 'Username already exists.',
		"type" => "error"),
	20 => array(
		"message" => 'Username change not permitted for designated template or guest user.',
		"type" => "error"),
	21 => array(
		"message" => 'User delete not permitted for designated template or guest user.',
		"type" => "error"),
	22 => array(
		"message" => 'User delete not permitted for designated graph export user.',
		"type" => "error"),
	23 => array(
		"message" => 'Data Template Includes Deleted Round Robin Archive.  Please run Database Repair Script to Identify and/or Correct.',
		"type" => "error"),
	24 => array(
		"message" => 'Graph Template Includes Deleted GPrint Prefix.  Please run Database Repair Script to Identify and/or Correct.',
		"type" => "error"),
	25 => array(
		"message" => 'Graph Template Includes Deleted CDEFs.  Please run Database Repair Script to Identify and/or Correct.',
		"type" => "error"),
	26 => array(
		"message" => 'Graph Template Includes Deleted Data Input Method.  Please run Database Repair Script to Identify.',
		"type" => "error"),
	27 => array(
		"message" => 'Data Template Not Found during Export.  Please run Database Repair Script to Identify.',
		"type" => "error"),
	28 => array(
		"message" => 'Host Template Not Found during Export.  Please run Database Repair Script to Identify.',
		"type" => "error"),
	29 => array(
		"message" => 'Data Query Not Found during Export.  Please run Database Repair Script to Identify.',
		"type" => "error"),
	30 => array(
		"message" => 'Graph Template Not Found during Export.  Please run Database Repair Script to Identify.',
		"type" => "error"),
	);

$snmp_query_field_actions = array(1 =>
	"SNMP Field Name (Dropdown)",
	"SNMP Field Value (From User)",
	"SNMP Output Type (Dropdown)");

$banned_snmp_strings = array(
	"End of MIB",
	"No Such");

$logfile_options = array(1 =>
	"Logfile Only",
	"Logfile and Syslog/Eventlog",
	"Syslog/Eventlog Only");

$logfile_verbosity = array(
	POLLER_VERBOSITY_NONE => "NONE - Syslog Only if Selected",
	POLLER_VERBOSITY_LOW => "LOW - Statistics and Errors",
	POLLER_VERBOSITY_MEDIUM => "MEDIUM - Statistics, Errors and Results",
	POLLER_VERBOSITY_HIGH => "HIGH - Statistics, Errors, Results and Major I/O Events",
	POLLER_VERBOSITY_DEBUG => "DEBUG - Statistics, Errors, Results, I/O and Program Flow",
	POLLER_VERBOSITY_DEVDBG => "DEVEL - Developer DEBUG Level");

$poller_intervals = array(
	10 => "Every 10 Seconds",
	15 => "Every 15 Seconds",
	20 => "Every 20 Seconds",
	30 => "Every 30 Seconds",
	60 => "Every Minute",
	300 => "Every 5 Minutes");

$cron_intervals = array(
	60 => "Every Minute",
	300 => "Every 5 Minutes");

$registered_cacti_names = array(
	"path_cacti");

$graph_tree_views = array(1 =>
	"Single Pane",
	"Dual Pane");

$auth_methods = array(
	0 => "None",
	1 => "Builtin Authentication",
	2 => "Web Basic Authentication");
if (function_exists("ldap_connect")) {
	$auth_methods[3] = "LDAP Authentication";
}

$auth_realms = array(0 =>
	"Local",
	"LDAP",
	"Web Basic");

$ldap_versions = array(
	2 => "Version 2",
	3 => "Version 3"
	);

$ldap_encryption = array(
	0 => "None",
	1 => "SSL",
	2 => "TLS");

$ldap_modes = array(
	0 => "No Searching",
	1 => "Anonymous Searching",
	2 => "Specific Searching");

$snmp_implimentations = array(
	"ucd-snmp" => "UCD-SNMP 4.x",
	"net-snmp" => "NET-SNMP 5.x");

$rrdtool_versions = array(
	"Unknown"	=> "Unknown",
	"rrd-1.0.x" => "RRDTool 1.0.x",
	"rrd-1.2.x" => "RRDTool 1.2.x",
	"rrd-1.3.x" => "RRDTool 1.3.x",
	"rrd-1.4.x" => "RRDTool 1.4.x");

$menu = array(
	"Create" => array(
		"graphs_new.php" => "New Graphs"
		),
	"Management" => array(
		"graphs.php" => array(
			"graphs.php" => "Graph Management",
			"cdef.php" => "CDEFs",
			"vdef.php" => "VDEFs",
			"xaxis.php" => "X-Axis",
			"color.php" => "Colors",
			"gprint_presets.php" => "GPRINT Presets"
			),
		"tree.php" => "Graph Trees",
		"data_sources.php" => array(
			"data_sources.php" => "Data Sources",
			"rra.php" => "RRAs"
			),
		"host.php" => 'Devices',
#		"sites.php" => 'Sites',
#		"pollers.php" => 'Pollers'
		),
	"Collection Methods" => array(
		"data_queries.php" => "Data Queries",
		"data_input.php" => "Data Input Methods"
		),
	"Templates" => array(
		"graph_templates.php" => "Graph Templates",
		"host_templates.php" => "Host Templates",
		"data_templates.php" => "Data Templates"
		),
	"Import/Export" => array(
		"templates_import.php" => "Import Templates",
		"templates_export.php" => "Export Templates"
		),
	"Configuration"  => array(
		"settings.php" => "Settings",
		"plugins.php" => "Plugin Management"
		),
	"Utilities" => array(
		"utilities.php" => "System Utilities",
		"user_admin.php" => "User Management",
		"logout.php" => "Logout User"
	));

$log_tail_lines = array(
	-1 => "All Lines",
	10 => "10 Lines",
	15 => "15 Lines",
	20 => "20 Lines",
	50 => "50 Lines",
	100 => "100 Lines",
	200 => "200 Lines",
	500 => "500 Lines",
	1000 => "1000 Lines",
	2000 => "2000 Lines",
	3000 => "3000 Lines",
	5000 => "5000 Lines",
	10000 => "10000 Lines"
	);

$item_rows = array(
	10 => "10",
	15 => "15",
	20 => "20",
	25 => "25",
	30 => "30",
	40 => "40",
	50 => "50",
	100 => "100",
	250 => "250",
	500 => "500",
	1000 => "1000",
	2000 => "2000",
	5000 => "5000"
	);

$graphs_per_page = array(
	4 => "4",
	6 => "6",
	8 => "8",
	10 => "10",
	14 => "14",
	20 => "20",
	24 => "24",
	30 => "30",
	40 => "40",
	50 => "50",
	100 => "100"
	);

$page_refresh_interval = array(
	5 => "5 Seconds",
	10 => "10 Seconds",
	20 => "20 Seconds",
	30 => "30 Seconds",
	60 => "1 Minute",
	300 => "5 Minutes",
	600 => "10 Minutes",
	9999999 => "Never");

$user_auth_realms = array(
	1 => "User Administration",
	2 => "Data Input",
	3 => "Update Data Sources",
	4 => "Update Graph Trees",
	5 => "Update Graphs",
	7 => "View Graphs",
	8 => "Console Access",
	9 => "Update Round Robin Archives",
	10 => "Update Graph Templates",
	11 => "Update Data Templates",
	12 => "Update Host Templates",
	13 => "Data Queries",
	14 => "Update CDEF's",
	15 => "Global Settings",
	16 => "Export Data",
	17 => "Import Data"
	);

$user_auth_realm_filenames = array(
	'about.php'						=> 8,
	'cdef.php'						=> 14,
	'color.php'						=> 5,
	'data_input.php'				=> 2,
	'data_queries.php'				=> 13,
	'data_sources_items.php'		=> 3,
	'data_sources.php'				=> 3,
	'data_templates.php'			=> 11,
	'data_templates_items.php'		=> 11,
	'host.php'						=> 3,
	'host_templates.php'			=> 12,
	'email_templates.php'			=> 8,
	'event_queue.php'				=> 8,
	'gprint_presets.php'			=> 5,
	'graph.php'						=> 7,
	'graph_image.php'				=> 7,
	'graph_xport.php'				=> 7,
	'graph_settings.php'			=> 7,
	'graph_templates.php'			=> 10,
	'graph_templates_inputs.php'	=> 10,
	'graph_templates_items.php'		=> 10,
	'graph_view.php'				=> 7,
	'graphs.php'					=> 5,
	'graphs_items.php'				=> 5,
	'graphs_new.php'				=> 5,
	'index.php'						=> 8,
	'logout.php'					=> 7,
	'plugins.php'					=> 101,
	'pollers.php'					=> 3,
	'rra.php'						=> 9,
	'settings.php'					=> 15,
	'sites.php'						=> 3,
	'smtp_servers.php'				=> 8,
	'smtp_queue.php'				=> 8,
	'templates_export.php'			=> 16,
	'templates_import.php'			=> 17,
	'tree.php'						=> 4,
	'user_admin.php'				=> 1,
	'utilities.php'					=> 15,
	'vdef.php'						=> 14,
	'xaxis.php'						=> 5,
	'layout.php'					=> 7
);

/* sequence of hash_type_codes defines the sequence of import ations
 * on template import */
$hash_type_codes = array(
	'round_robin_archive'		=> '15',
	'cdef'						=> '05',
	'cdef_item'					=> '14',
	'vdef'						=> '18',
	'vdef_item'					=> '19',
	'gprint_preset'				=> '06',
	'xaxis'						=> '16',
	'xaxis_item'				=> '17',
	'data_input_method'			=> '03',
	'data_input_field'			=> '07',
	'data_template'				=> '01',
	'data_template_item'		=> '08',
	'graph_template'			=> '00',
	'graph_template_item'		=> '10',
	'graph_template_input'		=> '09',
	'data_query'				=> '04',
	'data_query_graph'			=> '11',
	'data_query_sv_graph'		=> '12',
	'data_query_sv_data_source'	=> '13',
	'host_template'				=> '02',
);

$hash_version_codes = array(
	"0.8.4"  => "0000",
	"0.8.5"  => "0001",
	"0.8.5a" => "0002",
	"0.8.6"  => "0003",
	"0.8.6a" => "0004",
	"0.8.6b" => "0005",
	"0.8.6c" => "0006",
	"0.8.6d" => "0007",
	"0.8.6e" => "0008",
	"0.8.6f" => "0009",
	"0.8.6g" => "0010",
	"0.8.6h" => "0011",
	"0.8.6i" => "0012",
	"0.8.6j" => "0013",
	"0.8.7"  => "0014",
	"0.8.7a" => "0015",
	"0.8.7b" => "0016",
	"0.8.7c" => "0017",
	"0.8.7d" => "0018",
	"0.8.7e" => "0019",
	"0.8.7f" => "0020",
	"0.8.7g" => "0021",
	"0.8.7h" => "0022",
	"0.8.7i" => "0023",
	"0.8.8"  => "0024",
	"0.8.8a" => "0024",
	"0.8.8b" => "0024",
	"0.8.9"  => "0024"
	);

$hash_type_names = array(
	"cdef" => "CDEF",
	"cdef_item" => "CDEF Item",
	"gprint_preset" => "GPRINT Preset",
	"data_input_method" => "Data Input Method",
	"data_input_field" => "Data Input Field",
	"data_template" => "Data Template",
	"data_template_item" => "Data Template Item",
	"graph_template" => "Graph Template",
	"graph_template_item" => "Graph Template Item",
	"graph_template_input" => "Graph Template Input",
	"data_query" => "Data Query",
	"host_template" => "Host Template",
	"round_robin_archive" => "Round Robin Archive"
	);

/*
 * moved to specific files
 */
if (CACTI_ARRAY_COMPAT === TRUE) {
	require(CACTI_INCLUDE_PATH . '/auth/auth_arrays.php');
	require(CACTI_INCLUDE_PATH . '/data_query/data_query_arrays.php');
	require(CACTI_INCLUDE_PATH . '/device/device_arrays.php');
	require(CACTI_INCLUDE_PATH . '/graph/graph_arrays.php');
	require(CACTI_INCLUDE_PATH . '/graph_tree/graph_tree_arrays.php');
	require(CACTI_INCLUDE_PATH . '/poller/poller_arrays.php');
	require(CACTI_INCLUDE_PATH . '/presets/preset_cdef_arrays.php');
	require(CACTI_INCLUDE_PATH . '/presets/preset_rra_arrays.php');
	require(CACTI_INCLUDE_PATH . '/data_source/data_source_arrays.php');
}

?>
