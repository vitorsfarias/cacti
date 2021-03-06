#!/usr/bin/php -q
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

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

/* We are not talking to the browser */
$no_http_headers = true;

include(dirname(__FILE__) . "/../include/global.php");
require_once(CACTI_INCLUDE_PATH . "/device/device_constants.php");
include_once(CACTI_LIBRARY_PATH . "/automation_tools.php");
include_once(CACTI_LIBRARY_PATH . "/data_source.php");
include_once(CACTI_LIBRARY_PATH . "/graph.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
$me = array_shift($parms);
$debug		= FALSE;	# no debug mode
$quietMode 	= FALSE;	# be verbose by default
$device 	= array();
$force		= FALSE;

if (sizeof($parms)) {

	foreach($parms as $parameter) {
		@list($arg, $value) = @explode("=", $parameter);

		switch($arg) {
			case "-d":
			case "--debug":			$debug 							= TRUE; 		break;

			# to select the devices to act on, at least one parameter must be given to specify device list
			case "--device-id":		$device["id"] 					= trim($value);	break;
			case "--site-id":		$device["site_id"] 				= trim($value);	break;
			case "--poller-id":		$device["poller_id"]			= trim($value);	break;
			case "--description":	$device["description"] 			= trim($value);	break;
			case "--ip":			$device["hostname"] 			= trim($value);	break;
			case "--host_template_id":
			case "--template":		$device["host_template_id"]	 	= trim($value);	break;
			case "--community":		$device["snmp_community"] 		= trim($value);	break;
			case "--version":		$device["snmp_version"] 		= trim($value);	break;
			case "--notes":			$device["notes"] 				= trim($value);	break;
			case "--disabled":		$device["disabled"] 			= trim($value);	break;
			case "--username":		$device["snmp_username"] 		= trim($value);	break;
			case "--password":		$device["snmp_password"] 		= trim($value);	break;
			case "--authproto":		$device["snmp_auth_protocol"]	= trim($value);	break;
			case "--privproto":		$device["snmp_priv_protocol"] 	= trim($value);	break;
			case "--privpass":		$device["snmp_priv_passphrase"] = trim($value);	break;
			case "--context":		$device["snmp_context"] 		= trim($value);	break;
			case "--port":			$device["snmp_port"] 			= trim($value);	break;
			case "--timeout":		$device["snmp_timeout"] 		= trim($value);	break;
			case "--avail":			$device["availability_method"] 	= trim($value);	break;
			case "--ping-method":	$device["ping_method"] 			= trim($value);	break;
			case "--ping-port":		$device["ping_port"] 			= trim($value);	break;
			case "--ping-retries":	$device["ping_retries"] 		= trim($value);	break;
			case "--ping-timeout":	$device["ping_timeout"] 		= trim($value);	break;
			case "--max-oids":		$device["max_oids"] 			= trim($value);	break;
			case "--device-threads":$device["device_threads"] 		= trim($value);	break;

			# various paramaters specifying graphs
			#case "--graph-type":	$graph_type 					= strtolower(trim($value));	break;
			case "--graph-template-id":	$graph_template_id 			= trim($value);	break;
			case "--graph-title":	$graph_title 					= trim($value);	break;
			case "--graph-id":		$graph_id 						= trim($value);	break;
			case "--force":			$force 							= TRUE;			break;

			case "--snmp-query-id":	$ds_graph["snmpQueryId"] 		= trim($value);	break;
			case "--snmp-query-type-id":$ds_graph["snmpQueryType"] 	= trim($value);	break;
			case "--snmp-field":	$ds_graph["snmpField"] 			= trim($value);	break;
			case "--snmp-field-spec":$ds_graph["snmpFieldSpec"] 	= trim($value);	break;
			case "--snmp-value":	$ds_graph["snmpValue"] 			= trim($value);	break;

			# miscellaneous
			case "-V":
			case "-H":
			case "--help":
			case "--version":		display_help($me);								exit(0);
			case "--quiet":			$quietMode = TRUE;								break;
			default:				echo __("ERROR: Invalid Argument: (%s)", $arg) . "\n\n"; display_help($me); exit(1);
		}
	}

	if (!isset($graph_title)) {
		echo __("ERROR: No graph title given") . "\n";
		exit(1);
	}

	if (isset($graph_id)) {	# update a single graph
		/* Verify the graph's existance */
		$graph_exists = db_fetch_cell("SELECT id FROM graph_local WHERE id=$graph_id");
		if (empty($graph_exists)) {
			echo __("ERROR: Unknown Graph ID (%d)", $graph_id) . "\n";
			exit(1);
		} else {
			graph_update($graph_id, $graph_title);
		}


	} else {				# delete graphs related to the given device and/or graph template
		$selection = "";
		if (sizeof($device)) {
			# verify the parameters given
			$verify = verifyDevice($device, true);
			if (isset($verify["err_msg"])) {
				print $verify["err_msg"] . "\n\n";
				display_help($me);
				exit(1);
			}
			/* get devices matching criteria */
			$devices = getDevices($device);
			if (!sizeof($devices)) {
				echo __("ERROR: No matching Devices found") . "\n";
				echo __("Try php -q device_list.php") . "\n";
				exit(1);
			}
			/* form a valid sql statement for host_id */
			$selection = "WHERE " . str_replace("id", "host_id", array_to_sql_or($devices, "id")) . " ";
		}

		if (!isset($graph_template_id) || ($graph_template_id === 0) || !(db_fetch_cell("SELECT id FROM graph_templates WHERE id=$graph_template_id"))) {
			echo __("ERROR: No valid graph template given") . "\n";
			echo __("Try php -q graph_template_list.php") . "\n";
			exit(1);
		} else {
			/* form a valid sql statement for host_id */
			$selection .= (strlen($selection) ? " AND " : " WHERE ") . " graph_templates.id=" . $graph_template_id;
		}

		$graphs = getGraphs($selection, $columns);
		foreach ($graphs as $graph) {
			graph_update($graph["local_graph_id"], $graph_title);
		}
	}


}else{
	display_help($me);
	exit(0);
}


function graph_update($id, $graphTitle) {
	if ($graphTitle != "") {
		db_execute("UPDATE graph_templates_graph
					SET title=\"$graphTitle\"
					WHERE local_graph_id=" . $id);

		update_graph_title_cache($id);
		echo __("Updated graph-id: (%d)", $id) . "\n";
	}
}



function display_help($me) {
	echo "Update Graph Script 1.0" . ", " . __("Copyright 2004-2014 - The Cacti Group") . "\n";
	echo __("A simple command line utility to update a graph from Cacti") . "\n\n";
	echo __("usage: ") . $me . " --graph-title=[title] [--graph-template-id=|--graph-id=]\n";
	echo "       [--device-id=] [--site-id=] [--poller-id=]\n";
	echo "       [--description=] [--ip=] [--template=] [--notes=\"[]\"] [--disabled]\n";
	echo "       [--avail=[pingsnmp]] [--ping-method=[tcp] --ping-port=[N/A, 1-65534]] --ping-retries=[2] --ping-timeout=[500]\n";
	echo "       [--version=1] [--community=] [--port=161] [--timeout=500]\n";
	echo "       [--username= --password=] [--authproto=] [--privpass= --privproto=] [--context=]\n";
	echo "       [--quiet] [-d]\n\n";
	echo __("Required:") . "\n";
	echo "   --graph-title       " . __("the new title for that graph") . "\n";
	echo "   --graph-template-id " . __("the numerical ID of the graph template") . "\n";
	echo __("Optional:") . "\n";
	echo "   --graph-id          " . __("the numerical ID of the graph") . "\n";
	echo "   --device-id         " . __("the numerical ID of the device") . "\n";
	echo "   --site-id           " . __("the numerical ID of the site") . "\n";
	echo "   --poller-id         " . __("the numerical ID of the poller") . "\n";
	echo "   --description       " . __("the name that will be displayed by Cacti in the graphs") . "\n";
	echo "   --ip                " . __("self explanatory (can also be a FQDN)") . "\n";
	echo "   --template          " . __("denotes the device template to be used") . "\n";
	echo "                       " . __("In case a device template is given, all values are fetched from this one.") . "\n";
	echo "                       " . __("For a device template=0 (NONE), Cacti default settings are used.") . "\n";
	echo "                       " . __("Optionally overwrite by any of the following:") . "\n";
	echo "   --notes             " . __("General information about this device. Must be enclosed using double quotes.") . "\n";
	echo "   --disable           " . __("to add this device but to disable checks and 0 to enable it") . " [0|1]\n";
	echo "   --avail             " . __("device availability check") . " [ping][none, snmp, pingsnmp]\n";
	echo "     --ping-method     " . __("if ping selected") . " [icmp|tcp|udp]\n";
	echo "     --ping-port       " . __("port used for tcp|udp pings") . " [1-65534]\n";
	echo "     --ping-retries    " . __("the number of time to attempt to communicate with a device") . "\n";
	echo "     --ping-timeout    " . __("ping timeout") . "\n";
	echo "   --version           " . __("snmp version") . " [1|2|3]\n";
	echo "   --community         " . __("snmp community string for snmpv1 and snmpv2. Leave blank for no community") . "\n";
	echo "   --port              " . __("snmp port") . "\n";
	echo "   --timeout           " . __("snmp timeout") . "\n";
	echo "   --username          " . __("snmp username for snmpv3") . "\n";
	echo "   --password          " . __("snmp password for snmpv3") . "\n";
	echo "   --authproto         " . __("snmp authentication protocol for snmpv3") . " [".SNMP_AUTH_PROTOCOL_MD5."|".SNMP_AUTH_PROTOCOL_SHA."]\n";
	echo "   --privpass          " . __("snmp privacy passphrase for snmpv3") . "\n";
	echo "   --privproto         " . __("snmp privacy protocol for snmpv3") . " [".SNMP_PRIV_PROTOCOL_DES."|".SNMP_PRIV_PROTOCOL_AES128."]\n";
	echo "   --context           " . __("snmp context for snmpv3") . "\n";
	echo "   --max-oids          " . __("the number of OID's that can be obtained in a single SNMP Get request") . " [1-60]\n";
	#echo "   -d                  " . __("Debug Mode, no updates made, but printing the SQL for updates") . "\n";
	echo "   --force             " . __("delete all related data sources") . "\n\n";
	echo __("Examples:") . "\n";
	echo "   php -q " . $me . " --graph-id=55 --graph-title='New Title'\n";
	echo "   " . __("  updates graph id 55 a new title") . "\n";
	echo "   php -q " . $me . " --graph-template-id=11 --graph-title='New Title'\n";
	echo "   " . __("  updates all graphs related to graph template id 11 with a new title") . "\n";
	echo "   php -q " . $me . " --device-id=1 --graph-template-id=11 --graph-title='New Title'\n";
	echo "   " . __("  same as above for device id 1") . "\n";
}
