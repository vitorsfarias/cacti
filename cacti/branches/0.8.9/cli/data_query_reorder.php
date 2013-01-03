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

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

/* We are not talking to the browser */
$no_http_headers = true;

include(dirname(__FILE__) . "/../include/global.php");
require(CACTI_INCLUDE_PATH . "/data_query/data_query_arrays.php");
require_once(CACTI_INCLUDE_PATH . "/device/device_constants.php");
include_once(CACTI_LIBRARY_PATH . "/automation_tools.php");
include_once(CACTI_LIBRARY_PATH . "/data_query.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
$me = array_shift($parms);
$debug		= FALSE;	# no debug mode
$delimiter 	= ':';		# default delimiter, if not given by user
$quietMode 	= FALSE;	# be verbose by default
$device 	= array();
$query_id	= '';
$error		= '';

if (sizeof($parms)) {
	foreach($parms as $parameter) {
		@list($arg, $value) = @explode("=", $parameter);
		
		switch ($arg) {
			case "-d":
			case "--debug":			$debug 							= TRUE; 		break;
			case "--device-id":		$device["id"] 					= trim($value);	break;
			case "--site-id":		$device["site_id"] 				= trim($value);	break;
			case "--poller-id":		$device["poller_id"]			= trim($value);	break;
			case "--description":	$device["description"] 			= trim($value);	break;
			case "--ip":			$device["hostname"] 			= trim($value);	break;
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
			case "--data-query-id":	$query_id						= trim($value);	break;
			case "-V":
			case "-H":
			case "--help":
			case "--version":		display_help($me);								exit(0);
			case "--quiet":			$quietMode = TRUE;								break;
			default:				echo __("ERROR: Invalid Argument: (%s)", $arg) . "\n\n"; display_help($me); exit(1);
		}
	}
	
	
	if (sizeof($device)) {
		# verify the parameters given
		$verify = verifyDevice($device, true);
		if (isset($verify["err_msg"])) {
			print $verify["err_msg"] . "\n\n";
			display_help($me);
			exit(1);
		}
	}
	
	/* determine data queries to rerun */
	$query["snmp_query_id"] = $query_id;
	$verify = verifyDataQuery($query, true);
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

	/* build sql where clause to restrict result set to wanted devices and data query */
	$sql_where = "WHERE data_input_fields.type_code='output_type'";
	$sql_where .= (strlen($sql_where) ? " AND " : " WHERE " ) . str_replace("id", "data_local.host_id", array_to_sql_or($devices, "id"));
	$sql_where .= (strlen($sql_where) ? " AND " : " WHERE " ) . "data_local.snmp_query_id='$query_id'";
	
	/* get all object that have to be scanned */
	$sql = "SELECT " .
		"`data_local`.`host_id`, " .
		"`data_local`.`snmp_query_id`, " .
		"`data_local`.`snmp_index`, " .
		"`data_template_data`.`local_data_id`, " .
		"`data_template_data`.`data_input_id`, " .
		"`data_input_data`.`data_template_data_id`, " .
		"`data_input_data`.`data_input_field_id`, " .
		"`data_input_data`.`value` " .
		"FROM data_local " .
		"LEFT JOIN data_template_data ON data_local.id=data_template_data.local_data_id " .
		"LEFT JOIN data_input_fields ON data_template_data.data_input_id = data_input_fields.data_input_id " .
		"LEFT JOIN data_input_data ON ( " .
		"data_template_data.id = data_input_data.data_template_data_id " .
		"AND data_input_fields.id = data_input_data.data_input_field_id " .
		") " .
		$sql_where;

	if ($debug) {
		print $sql . "\n";
	}
	$data_queries = db_fetch_assoc($sql);
	
	
	$i = 1;
	if (sizeof($data_queries)) {
		/* issue warnings and start message if applicable */
		echo __("WARNING: Do not interrupt this script.  Reindexing can take quite some time") . "\n";
		print_debug("There are '" . sizeof($data_queries) . "' data query index items to run");
		foreach ($data_queries as $data_query) {
			if (!$debug) print ".";
			/* fetch current index_order from data_query XML definition and put it into host_snmp_query */
			update_data_query_sort_cache($data_query["host_id"], $data_query["snmp_query_id"]);
			/* build array required for function call */
			$data_query["snmp_index_on"] = get_best_data_query_index_type($data_query["host_id"], $data_query["snmp_query_id"]);
			/* as we request the output_type, "value" gives the snmp_query_graph_id */
			$data_query["snmp_query_graph_id"] = $data_query["value"]; 
			print_debug("Data Query #'" . $i . "' host: '" . $data_query["host_id"] .
				"' SNMP Query Id: '" . $data_query["snmp_query_id"] .
				"' Index: " . $data_query["snmp_index"] .
				"' Index On: " . $data_query["snmp_index_on"]
			);
			update_snmp_index_order($data_query);
			$i++;
		}
	}
}

/**
 * perform sql updates for all required tables for new index_sort_order
 * @arg array $snmp_query_array
 * 				$host_id
 * 				snmp_query_id
 * 				snmp_index_on
 * 				snmp_query_graph_id
 * 				snmp_index
 * 				$data_template_data_id	
 * 				$local_data_id
 * 
 * this code stems from lib/template.php, function create_complete_graph_from_template
 */
function update_snmp_index_order($data_query) {
	if (is_array($data_query)) {
		$data_input_field = array_rekey(db_fetch_assoc("SELECT " .
			"data_input_fields.id, " .
			"data_input_fields.type_code " .
			"FROM (snmp_query,data_input,data_input_fields) " .
			"WHERE snmp_query.data_input_id=data_input.id " .
			"AND data_input.id=data_input_fields.data_input_id " .
			"AND (data_input_fields.type_code='index_type' " .
			"OR data_input_fields.type_code='index_value' " .
			"OR data_input_fields.type_code='output_type') " .
			"AND snmp_query.id=" . $data_query["snmp_query_id"]), "type_code", "id");
		
		$snmp_cache_value = db_fetch_cell("SELECT field_value " .
			"FROM host_snmp_cache " .
			"WHERE host_id='" . $data_query["host_id"] . "' " . 
			"AND snmp_query_id='" . $data_query["snmp_query_id"] . "' " .
			"AND field_name='" . $data_query["snmp_index_on"] . "' " .
			"AND snmp_index='" . $data_query["snmp_index"] . "'");
		
		/* save the value to index on (ie. ifindex, ifip, etc) */
		db_execute("REPLACE INTO data_input_data " .
			"(data_input_field_id, data_template_data_id, t_value, value) " .
			"VALUES (" . 
			$data_input_field["index_type"] . ", " . 
			$data_query["data_template_data_id"] . ", '', '" . 
			$data_query["snmp_index_on"] . "')");
		
		/* save the actual value (ie. 3, 192.168.1.101, etc) */
		db_execute("REPLACE INTO data_input_data " .
			"(data_input_field_id,data_template_data_id,t_value,value) " .
			"VALUES (" . 
			$data_input_field["index_value"] . "," . 
			$data_query["data_template_data_id"] . ",'','" . 
			addslashes($snmp_cache_value) . "')");
		
		/* set the expected output type (ie. bytes, errors, packets) */
		db_execute("REPLACE INTO data_input_data " .
			"(data_input_field_id,data_template_data_id,t_value,value) " .
			"VALUES (" . 
			$data_input_field["output_type"] . "," . 
			$data_query["data_template_data_id"] . ",'','" . 
			$data_query["snmp_query_graph_id"] . "')");
		
		/* now that we have put data into the 'data_input_data' table, update the snmp cache for ds's */
		update_data_source_data_query_cache($data_query["local_data_id"]);
	}
}
function display_help($me) {
	echo "Reorder Data Query Script 1.0" . ", " . __("Copyright 2004-2012 - The Cacti Group") . "\n";
	echo __("A simple command line utility to reorder data queries in Cacti") . "\n\n";
	echo __("usage: ") . $me . " [--data-query-id=] [--device-id=] [--site-id=] [--poller-id=]\n";
	echo "       [--description=] [--ip=] [--template=] [--notes=\"[]\"] [--disabled]\n";
	echo "       [--avail=[pingsnmp]] [--ping-method=[tcp] --ping-port=[N/A, 1-65534]] --ping-retries=[2] --ping-timeout=[500]\n";
	echo "       [--version=1] [--community=] [--port=161] [--timeout=500]\n";
	echo "       [--username= --password=] [--authproto=] [--privpass= --privproto=] [--context=]\n";
	echo "       [--quiet] [-d] [--delim]\n\n";
	echo __("Required:") . "\n";
	echo "   --data-query-id  " . __("the numerical ID of the data_query to be listed") . "\n";
	echo __("Optional:") . "\n";
	echo "   --device-id                 " . __("the numerical ID of the device") . "\n";
#	echo "   --site-id                   " . __("the numerical ID of the site") . "\n";
#	echo "   --poller-id                 " . __("the numerical ID of the poller") . "\n";
	echo "   --description               " . __("the name that will be displayed by Cacti in the graphs") . "\n";
	echo "   --ip                        " . __("self explanatory (can also be a FQDN)") . "\n";
	echo "   --template                  " . __("denotes the device template to be used") . "\n";
	echo "                               " . __("In case a device template is given, all values are fetched from this one.") . "\n";
	echo "                               " . __("For a device template=0 (NONE), Cacti default settings are used.") . "\n";
	echo "                               " . __("Optionally overwrite by any of the following:") . "\n";
	echo "   --notes                     " . __("General information about this device. Must be enclosed using double quotes.") . "\n";
	echo "   --disable                   " . __("to add this device but to disable checks and 0 to enable it") . " [0|1]\n";
	echo "   --avail                     " . __("device availability check") . " [ping][none, snmp, pingsnmp]\n";
	echo "     --ping-method             " . __("if ping selected") . " [icmp|tcp|udp]\n";
	echo "     --ping-port               " . __("port used for tcp|udp pings") . " [1-65534]\n";
	echo "     --ping-retries            " . __("the number of time to attempt to communicate with a device") . "\n";
	echo "     --ping-timeout            " . __("ping timeout") . "\n";
	echo "   --version                   " . __("snmp version") . " [1|2|3]\n";
	echo "   --community                 " . __("snmp community string for snmpv1 and snmpv2. Leave blank for no community") . "\n";
	echo "   --port                      " . __("snmp port") . "\n";
	echo "   --timeout                   " . __("snmp timeout") . "\n";
	echo "   --username                  " . __("snmp username for snmpv3") . "\n";
	echo "   --password                  " . __("snmp password for snmpv3") . "\n";
	echo "   --authproto                 " . __("snmp authentication protocol for snmpv3") . " [".SNMP_AUTH_PROTOCOL_MD5."|".SNMP_AUTH_PROTOCOL_SHA."]\n";
	echo "   --privpass                  " . __("snmp privacy passphrase for snmpv3") . "\n";
	echo "   --privproto                 " . __("snmp privacy protocol for snmpv3") . " [".SNMP_PRIV_PROTOCOL_DES."|".SNMP_PRIV_PROTOCOL_AES128."]\n";
	echo "   --context                   " . __("snmp context for snmpv3") . "\n";
	echo "   --max-oids                  " . __("the number of OID's that can be obtained in a single SNMP Get request") . " [1-60]\n";
	echo "   -d                          " . __("Debug Mode, no updates made, but printing the SQL for updates") . "\n";
	echo "   --quiet                     " . __("batch mode value return") . "\n\n";
	echo __("Examples:") . "\n";
	echo "   php -q " . $me . "  --data-query-id=3 --device-id=5\n";
	echo "   " . __("  performs reordering of data query id 3 on device id 5 to current <index_order>") . "\n";
	echo "   php -q " . $me . "  --data-query-id=3 --template=8\n";
	echo "   " . __("  same as above, working on all devices associated with template id of 8") . "\n";
}

