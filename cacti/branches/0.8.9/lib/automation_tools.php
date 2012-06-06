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

function getHostTemplates() {
	$tmpArray = db_fetch_assoc("select id, name from host_template order by id");

	$host_templates[0] = "None";

	if (sizeof($tmpArray)) {
		foreach ($tmpArray as $template) {
			$host_templates[$template["id"]] = $template["name"];
		}
	}

	return $host_templates;
}

function getHostsByDescription() {
	$hosts = array();
	$tmpArray = db_fetch_assoc("select id, description from host order by description");

	if (sizeof($tmpArray)) {
		foreach ($tmpArray as $tmp) {
			$hosts[$tmp["description"]] = $tmp["id"];
		}
	}

	return $hosts;
}

function getHosts() {
	$hosts    = array();
	$tmpArray = db_fetch_assoc("select * from host order by id");

	if (sizeof($tmpArray)) {
		foreach ($tmpArray as $host) {
			$hosts[$host["id"]] = $host;
		}
	}

	return $hosts;
}

/** getDevices					get all matching devices for given selection criteria
 * @param string $input_parms	array of selection criteria
 * @return						array of devices, indexed by host_id
 */
function getDevices($input_parms) {
	$devices    = array();

	$sql_where = "";

	if (isset($input_parms["id"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'id = ' . $input_parms["id"] . ' ';
	}

	if (isset($input_parms["site_id"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'site_id = ' . $input_parms["site_id"] . ' ';
	}

	if (isset($input_parms["poller_id"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'poller_id = ' . $input_parms["poller_id"] . ' ';
	}

	if (isset($input_parms["description"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'description like "%%' . $input_parms["description"] . '%%" ';
	}

	if (isset($input_parms["hostname"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'hostname like "%%' . $input_parms["hostname"] . '%%" ';
	}

	if (isset($input_parms["host_template_id"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'host_template_id = ' . $input_parms["host_template_id"] . ' ';
	}

	if (isset($input_parms["notes"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'notes like "%%' . $input_parms["notes"] . '%%" ';
	}

	if (isset($input_parms["snmp_community"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'snmp_community like "%%' . $input_parms["snmp_community"] . '%%" ';
	}

	if (isset($input_parms["snmp_version"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'snmp_version = ' . $input_parms["snmp_version"] . ' ';
	}

	if (isset($input_parms["snmp_username"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'snmp_username like "%%' . $input_parms["snmp_username"] . '%%" ';
	}

	if (isset($input_parms["snmp_password"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'snmp_password like "%%' . $input_parms["snmp_password"] . '%%" ';
	}

	if (isset($input_parms["snmp_auth_protocol"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'snmp_auth_protocol = "' . $input_parms["snmp_auth_protocol"] . '" ';
	}

	if (isset($input_parms["snmp_priv_passphrase"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'snmp_priv_passphrase like "%%' . $input_parms["snmp_priv_passphrase"] . '%%" ';
	}

	if (isset($input_parms["snmp_priv_protocol"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'snmp_priv_protocol = "' . $input_parms["snmp_priv_protocol"] . '" ';
	}

	if (isset($input_parms["snmp_context"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'snmp_context like "%%' . $input_parms["snmp_context"] . '%%" ';
	}

	if (isset($input_parms["snmp_port"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'snmp_port = ' . $input_parms["snmp_port"] . ' ';
	}

	if (isset($input_parms["snmp_timeout"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'snmp_timeout = ' . $input_parms["snmp_timeout"] . ' ';
	}

	if (isset($input_parms["availability_method"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'availability_method = ' . $input_parms["availability_method"] . ' ';
	}

	if (isset($input_parms["ping_method"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'ping_method = ' . $input_parms["ping_method"] . ' ';
	}

	if (isset($input_parms["ping_port"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'ping_port = ' . $input_parms["ping_port"] . ' ';
	}

	if (isset($input_parms["ping_timeout"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'ping_timeout = ' . $input_parms["ping_timeout"] . ' ';
	}

	if (isset($input_parms["ping_retries"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'ping_retries = ' . $input_parms["ping_retries"] . ' ';
	}

	if (isset($input_parms["max_oids"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'max_oids = ' . $input_parms["max_oids"] . ' ';
	}

	if (isset($input_parms["device_threads"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'device_threads = ' . $input_parms["device_threads"] . ' ';
	}

	if (isset($input_parms["disabled"])) {
		strlen($sql_where) ? ($sql_where .= ' AND ') : ($sql_where .= ' WHERE ');
		$sql_where .= 'disabled = ' . $input_parms["disabled"] . ' ';
	}

	$sql_stmt = ("SELECT " .
					"* " .
				"FROM " .
					"host " .
	$sql_where .
				"ORDER BY id");
	#print $sql_stmt ."\n";

	return db_fetch_assoc($sql_stmt);
}

/** getGraphs						- get all Graphs related to a given device selection
 * @param string $device_selection	- sql selection of device(s), empty for all devices
 * 									  e.g. WHERE device_id = <id>
 * 								       WHERE (device_id IN (...))
 * @param array $header
 * @return array
 */
function getGraphs($device_selection, &$header) {
	$sql = "SELECT " .
			"graph_local.id as local_graph_id, " .
			"graph_local.host_id, " .
			"host.hostname, " .
			"graph_templates.id as gt_id, " .
			"graph_templates.name, " .
			"graph_templates_graph.title_cache  " .
			"FROM graph_local " .
			"LEFT JOIN graph_templates_graph ON (graph_local.id=graph_templates_graph.local_graph_id) " .
			"LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id) " .
			"LEFT JOIN host ON (graph_local.host_id=host.id) " .
	$device_selection .
			" ORDER BY host_id ASC, gt_id ASC";

	$tmpArray = db_fetch_assoc($sql);

	if (sizeof($tmpArray)) {
		# provide human readable column headers
		$header["local_graph_id"]["desc"]		= __("Local Graph Id");
		$header["host_id"]["desc"] 				= __("Host Id");
		$header["hostname"]["desc"] 			= __("Hostname");
		$header["gt_id"]["desc"]		 		= __("Graph Template Id");
		$header["name"]["desc"]			 		= __("Graph Template Name");
		$header["title_cache"]["desc"] 			= __("Graph Title");
	}

	return $tmpArray;
}


/** getHostGraphs
 *
 * @param array $devices
 * @param array $header
 * @return array
 */
function getHostGraphs($devices, &$header) {

	$header = array();	# provides header info for printout
	$sql_where = "";

	if (sizeof($devices)) {
		$sql_where .= ((strlen($sql_where) === 0) ? "WHERE " : "AND ") . str_replace("id", "graph_local.host_id", array_to_sql_or($devices, "id")) . " ";
	}

	$sql = "SELECT " .
		"graph_templates_graph.local_graph_id as id, " .
		"graph_templates_graph.title_cache as name, " .
		"graph_templates.name as template_name, " .
		"graph_local.host_id as host_id, " .
		"host.hostname as hostname " .
		"FROM graph_local " .
		"LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id) " .
		"LEFT JOIN graph_templates_graph ON (graph_local.id=graph_templates_graph.local_graph_id) " .
		"LEFT JOIN host ON (graph_local.host_id=host.id) " .
	$sql_where .
		" ORDER BY graph_templates_graph.local_graph_id";
	#print $sql . "\n";

	$tmpArray = db_fetch_assoc($sql);
	if (sizeof($tmpArray)) {
		# provide human readable column headers
		$header["id"]["desc"] 				= __("Graph Id");
		$header["name"]["desc"] 			= __("Graph Title");
		$header["template_name"]["desc"] 	= __("Graph Template Name");
		$header["host_id"]["desc"] 			= __("Host Id");
		$header["hostname"]["desc"] 		= __("Hostname");
	}

	return $tmpArray;
}

function getInputFields($templateId) {
	$fields    = array();

	$tmpArray = db_fetch_assoc("SELECT DISTINCT
		data_input_fields.data_name AS `name`,
		data_input_fields.name AS `description`,
		data_input_data.value AS `default`,
		data_template_data.data_template_id,
		data_input_fields.id AS `data_input_field_id`
		FROM data_input_data
		INNER JOIN (((data_template_rrd
		INNER JOIN (graph_templates
		INNER JOIN graph_templates_item
		ON graph_templates.id=graph_templates_item.graph_template_id)
		ON data_template_rrd.id=graph_templates_item.task_item_id)
		INNER JOIN data_template_data
		ON data_template_rrd.data_template_id=data_template_data.data_template_id)
		INNER JOIN data_input_fields
		ON data_template_data.data_input_id=data_input_fields.data_input_id)
		ON (data_input_data.data_template_data_id=data_template_data.id)
		AND (data_input_data.data_input_field_id=data_input_fields.id)
		WHERE (((graph_templates.id)=$templateId)
		AND (data_template_rrd.local_data_id=0)
		AND (data_template_data.local_data_id=0)
		AND ((data_input_data.t_value)='on')
		AND ((data_input_fields.input_output)='in'))");

	if (sizeof($tmpArray)) {
		foreach ($tmpArray as $row) {
			$fields[$row["data_template_id"] . ":" . $row["name"]] = $row;
		}
	}

	return $fields;
}

function getAddresses() {
	$addresses = array();
	$tmpArray  = db_fetch_assoc("SELECT id, hostname FROM host ORDER BY hostname");

	if (sizeof($tmpArray)) {
		foreach ($tmpArray as $tmp) {
			$addresses[$tmp["hostname"]] = $tmp["id"];
		}
	}

	return $addresses;
}

function getSNMPFields($hostId, $snmp_query_id = "") {
	$fieldNames = array();

	if ($snmp_query_id != "") {
		$sql_where = " AND snmp_query_id=$snmp_query_id";
	}else{
		$sql_where = "";
	}

	$tmpArray   = db_fetch_assoc("SELECT DISTINCT field_name
		FROM host_snmp_cache
		WHERE host_id = " . $hostId . "
		$sql_where
		ORDER BY field_name");

	if (sizeof($tmpArray)) {
		foreach ($tmpArray as $f) {
			$fieldNames[$f["field_name"]] = 1;
		}
	}

	return $fieldNames;
}

function getSNMPValues($hostId, $field, $snmp_query_id = "") {
	$values   = array();

	if ($snmp_query_id != "") {
		$sql_where = " AND snmp_query_id=$snmp_query_id";
	}else{
		$sql_where = "";
	}

	$tmpArray = db_fetch_assoc("SELECT field_value
		FROM host_snmp_cache
		WHERE host_id=" . $hostId . "
		AND field_name='" . $field . "'
		$sql_where
		ORDER BY field_value");

	if (sizeof($tmpArray)) {
		foreach ($tmpArray as $v) {
			$values[$v["field_value"]] = 1;
		}
	}

	return $values;
}

function getSNMPQueries() {
	$queries  = array();
	$tmpArray = db_fetch_assoc("SELECT id, name
		FROM snmp_query
		ORDER by id");

	if (sizeof($tmpArray)) {
		foreach ($tmpArray as $q) {
			$queries[$q["id"]] = $q["name"];
		}
	}

	return $queries;
}

function getSNMPQueryTypes($snmpQueryId) {
	$types    = array();
	$tmpArray = db_fetch_assoc("SELECT id, name
		FROM snmp_query_graph
		WHERE snmp_query_id = " . $snmpQueryId . "
		ORDER BY id");

	if (sizeof($tmpArray)) {
		foreach ($tmpArray as $type) {
			$types[$type["id"]] = $type["name"];
		}
	}

	return $types;
}

function getGraphTemplates() {
	$graph_templates = array();
	$tmpArray        = db_fetch_assoc("SELECT id, name
		FROM graph_templates
		ORDER BY id");

	if (sizeof($tmpArray)) {
		foreach ($tmpArray as $t) {
			$graph_templates[$t["id"]] = $t["name"];
		}
	}

	return $graph_templates;
}

function getGraphTemplatesByHostTemplate($host_template_id) {
	$graph_templates = array();
	$tmpArray 		 = db_fetch_assoc("SELECT " .
										"host_template_graph.graph_template_id AS id, " .
										"graph_templates.name AS name " .
									"FROM host_template_graph " .
									"LEFT JOIN graph_templates " .
										"ON (host_template_graph.graph_template_id = graph_templates.id) " .
									"WHERE host_template_id = $host_template_id");

	if (sizeof($tmpArray)) {
		foreach ($tmpArray as $t) {
			$graph_templates[$t["id"]] = $t["name"];
		}
	}

	return $graph_templates;
}

/** getSNMPQueriesByDevices
 *
 * @param array $devices
 * @param int $snmp_query_id
 * @param string $header
 * @return array
 */
function getSNMPQueriesByDevices($devices, $snmp_query_id='', &$header) {
	require(CACTI_INCLUDE_PATH . "/data_query/data_query_arrays.php");

	$header = array();	# provides header info for printout
	$sql_where = "";

	if (sizeof($devices)) {
		$sql_where .= ((strlen($sql_where) === 0) ? "WHERE " : "AND ") . str_replace("id", "host_id", array_to_sql_or($devices, "id")) . " ";
	}
	if ($snmp_query_id != '') {
		$sql_where .= ((strlen($sql_where) === 0) ? "WHERE " : "AND ") . " snmp_query.id =" . $snmp_query_id . " ";
	}
	$sql = "SELECT " .
				"host.id as host_id, " .
				"host.hostname as hostname, " .
				"snmp_query.id as snmp_query_id, " .
				"snmp_query.name as snmp_query_name, " .
				"host_snmp_query.sort_field, " .
				"host_snmp_query.title_format, " .
				"host_snmp_query.reindex_method " .
				"FROM host " .
				"LEFT JOIN host_snmp_query ON (host.id = host_snmp_query.host_id) " .
				"LEFT JOIN snmp_query ON (host_snmp_query.snmp_query_id = snmp_query.id) " .
	$sql_where .
				"ORDER by host.id, snmp_query.id";
	#print $sql . "\n";

	$tmpArray = db_fetch_assoc($sql);
	if (sizeof($tmpArray)) {
		foreach ($tmpArray as $key => $value) {	# recode reindex type in a human readable fashion
			$tmpArray{$key}["human_reindex_method"] = $reindex_types[$tmpArray{$key}["reindex_method"]];
		}
		# provide human readable column headers
		$header["host_id"]["desc"] 				= "Host Id";
		$header["hostname"]["desc"] 			= "Hostname";
		$header["snmp_query_id"]["desc"] 		= "Query Id";
		$header["snmp_query_name"]["desc"] 		= "Query Name";
		$header["sort_field"]["desc"] 			= "Sort Field";
		$header["title_format"]["desc"] 		= "Title Format";
		$header["reindex_method"]["desc"] 		= "#";
		$header["human_reindex_method"]["desc"] = "Reindex Method";
	}

	return $tmpArray;
}

function displayQueryTypes($types, $quietMode = FALSE) {
	if (!$quietMode) {
		echo "Known SNMP Query Types: (id, name)\n";
	}

	while (list($id, $name) = each ($types)) {
		echo $id . "\t" . $name . "\n";
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displayHostTemplates($host_templates, $quietMode = FALSE) {
	if (!$quietMode) {
		echo "Valid Host Templates: (id, name)\n";
	}

	if (sizeof($host_templates)) {
		foreach ($host_templates as $id => $name) {
			echo "$id\t$name\n";
		}
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displayCommunities($quietMode = FALSE) {
	if (!$quietMode) {
		echo "Known communities are: (community)\n";
	}

	$communities = db_fetch_assoc("SELECT DISTINCT
		snmp_community
		FROM host
		ORDER BY snmp_community");

	if (sizeof($communities)) {
		foreach ($communities as $community) {
			echo $community["snmp_community"]."\n";
		}
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displaySNMPFields($fields, $hostId, $quietMode = FALSE) {
	if (!$quietMode) {
		echo "Known SNMP Fields for host-id $hostId: (name)\n";
	}

	while (list($field, $values) = each ($fields)) {
		echo $field . "\n";
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displaySNMPValues($values, $hostId, $field, $quietMode = FALSE) {
	if (!$quietMode) {
		echo "Known values for $field for host $hostId: (name)\n";
	}

	while (list($value, $foo) = each($values)) {
		echo "$value\n";
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displaySNMPQueries($queries, $quietMode = FALSE) {
	if (!$quietMode) {
		echo "Known SNMP Queries:(id, name)\n";
	}

	while (list($id, $name) = each ($queries)) {
		echo $id . "\t" . $name . "\n";
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displayInputFields($input_fields, $quietMode = FALSE) {
	if (!$quietMode) {
		echo "Known Input Fields:(name, default, description)\n";
	}

	if (sizeof($input_fields)) {
	foreach ($input_fields as $row) {
		echo $row["data_template_id"] . ":" . $row["name"] . "\t" . $row["default"] . "\t" . $row["description"] . "\n";
	}
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displayGraphTemplates($templates, $quietMode = FALSE) {
	if (!$quietMode) {
		echo "Known Graph Templates:(id, name)\n";
	}

	while (list($id, $name) = each ($templates)) {
		echo $id . "\t" . $name . "\n";
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displayHosts($hosts, $quietMode = FALSE) {
	if (!$quietMode) {
		echo "Known Hosts: (id, hostname, template, description)\n";
	}

	if (sizeof($hosts)) {
		foreach($hosts as $host) {
			echo $host["id"] . "\t" . $host["hostname"] . "\t" . $host["host_template_id"] . "\t" . $host["description"] . "\n";
		}
	}

	if (!$quietMode) {
		echo "\n";
	}
}

/** displayRealms
 * @param array $perm
 * @param bool $quietMode
 */
function displayRealms($perm, $quietMode = FALSE) {
	global $user_auth_realms;

	if (!$quietMode) {
		echo __("Realms: (userid, name, realm id, name)") . "\n";
	}

	$sql_where = "";
	if (isset($perm["realm_id"])) {
		if (!(((string) $perm["realm_id"]) === ((string)(int) $perm["realm_id"]))) {
			echo __("ERROR: Invalid realm id (%s)", $perm["realm_id"]) . "\n";
			return (1);
		} elseif ($perm["realm_id"] > 0) {
			$sql_where .= (strlen($sql_where) ? " AND " : " WHERE ") . " realm_id=" . $perm["realm_id"];
		}
	}

	if (isset($perm["user_id"])) {
		if (!(((string) $perm["user_id"]) === ((string)(int) $perm["user_id"]))) {
			echo __("ERROR: Invalid user id (%s)", $perm["user_id"]) . "\n";
			return (1);
		} elseif ($perm["user_id"] > 0) {
			$sql_where .= (strlen($sql_where) ? " AND " : " WHERE ") . " user_id=" . $perm["user_id"];
		}
	}


	$sql = "SELECT realm_id, user_id, user_auth.username " .
		"FROM user_auth_realm " .
		"LEFT JOIN user_auth ON (user_auth_realm.user_id = user_auth.id) " . $sql_where .
		" ORDER BY user_id, realm_id";

	$realms = db_fetch_assoc($sql);

	if (sizeof($realms)) {
		foreach ($realms as $realm) {
			echo $realm["user_id"]."\t" . $realm["username"]."\t";
			echo $realm["realm_id"]."\t";
			echo $user_auth_realms{$realm["realm_id"]}."\n";
		}
	}

	if (!$quietMode) {
		echo "\n";
	}
}

/** displayPerms
 *
 * @param array $perm
 * @param bool $quietMode
 */
function displayPerms($perm, $quietMode = FALSE) {
	require(CACTI_INCLUDE_PATH . "/auth/auth_arrays.php");

	if (!$quietMode) {
		echo __("Permissions: (userid, username, item type, default policy, item id, item policy, item name)") . "\n";
	}

	$sql_where = "";
	if (isset($perm["user_id"])) {
		if (!(((string) $perm["user_id"]) === ((string)(int) $perm["user_id"]))) {
			echo __("ERROR: Invalid user id (%s)", $perm["user_id"]) . "\n";
			return (1);
		} elseif ($perm["user_id"] > 0) {
			$sql_where .= (strlen($sql_where) ? " AND " : " WHERE ") . " user_id=" . $perm["user_id"];
		}
	}

	if (isset($perm["item_type_id"])) {
		if (!(((string) $perm["item_type_id"]) === ((string)(int) $perm["item_type_id"]))) {
			# print human readable text instead of id
			echo __("ERROR: Invalid item type (%s)", $perm["item_type"]) . "\n";
			return (1);
		} elseif ($perm["item_type_id"] > 0) {
			$sql_where .= (strlen($sql_where) ? " AND " : " WHERE ") . " `type`=" . $perm["item_type_id"];
		}
	}

	if (isset($perm["item_id"])) {
		if (!(((string) $perm["item_id"]) === ((string)(int) $perm["item_id"]))) {
			echo __("ERROR: Invalid item id (%s)", $perm["item_id"]) . "\n";
			return (1);
		} elseif ($perm["item_id"] > 0) {
			$sql_where .= (strlen($sql_where) ? " AND " : " WHERE ") . " item_id=" . $perm["item_id"];
		}
	}

	$sql = "SELECT user_id, user_auth.username, " .
		"policy_graphs, policy_trees, policy_hosts, policy_graph_templates, " .
		"`type`, item_id " .
		"FROM user_auth_perms " .
		"LEFT JOIN user_auth ON (user_auth_perms.user_id = user_auth.id) " . $sql_where .
		" ORDER BY user_id, `type`";

	$perms = db_fetch_assoc($sql);

	if (sizeof($perms)) {
		foreach ($perms as $item) {
			switch ($item["type"]) {
				case PERM_GRAPHS:
					$item["default_policy"] = (($item["policy_graphs"] == AUTH_CONTROL_DATA_POLICY_ALLOW) ? __("Accessible") : __("No Access"));
					$item["item_policy"] = (($item["policy_graphs"] == AUTH_CONTROL_DATA_POLICY_ALLOW) ? __("No Access") : __("Accessible"));
					$item["name"] = db_fetch_cell("SELECT title_cache FROM graph_templates_graph WHERE local_graph_id=" . $item["item_id"]);
					break;
				case PERM_TREES:
					$item["default_policy"] = (($item["policy_trees"] == AUTH_CONTROL_DATA_POLICY_ALLOW) ? __("Accessible") : __("No Access"));
					$item["item_policy"] = (($item["policy_trees"] == AUTH_CONTROL_DATA_POLICY_ALLOW) ? __("No Access") : __("Accessible"));
					$item["name"] = db_fetch_cell("SELECT name FROM graph_tree WHERE id=" . $item["item_id"]);
					break;
				case PERM_DEVICES:
					$item["default_policy"] = (($item["policy_hosts"] == AUTH_CONTROL_DATA_POLICY_ALLOW) ? __("Accessible") : __("No Access"));
					$item["item_policy"] = (($item["policy_hosts"] == AUTH_CONTROL_DATA_POLICY_ALLOW) ? __("No Access") : __("Accessible"));
					$item["name"] = db_fetch_cell("SELECT hostname FROM device WHERE id=" . $item["item_id"]);
					break;
				case PERM_GRAPH_TEMPLATES:
					$item["default_policy"] = (($item["policy_graph_templates"] == AUTH_CONTROL_DATA_POLICY_ALLOW) ? __("Accessible") : __("No Access"));
					$item["item_policy"] = (($item["policy_graph_templates"] == AUTH_CONTROL_DATA_POLICY_ALLOW) ? __("No Access") : __("Accessible"));
					$item["name"] = db_fetch_cell("SELECT name FROM graph_templates WHERE id=" . $item["item_id"]);
					break;
			}
			echo $item["user_id"]."\t" . $item["username"]."\t";
			echo $perm_item_types[$item["type"]]."\t";
			echo $item["default_policy"]."\t";
			echo $item["item_id"]."\t";
			echo $item["item_policy"]."\t";
			echo $item["name"]."\n";
		}
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displayTrees($quietMode = FALSE) {
	global $tree_sort_types;

	if (!$quietMode) {
		echo "Known Trees:\nid\tsort method\t\t\tname\n";
	}

	$trees = db_fetch_assoc("SELECT
		id,
		sort_type,
		name
		FROM graph_tree
		ORDER BY id");

	if (sizeof($trees)) {
		foreach ($trees as $tree) {
			echo $tree["id"]."\t";
			echo $tree_sort_types[$tree["sort_type"]]."\t";
			echo $tree["name"]."\n";
		}
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displayTreeNodes($tree_id, $nodeType = "", $parentNode = "", $quietMode = FALSE) {
	global $tree_sort_types, $tree_item_types, $tree_device_group_types;

	if (!$quietMode) {
		echo "Known Tree Nodes:\n";
		echo "type\tid\tparentid\ttitle\tattribs\n";
	}

	$parentID = 0;

	$nodes = db_fetch_assoc("SELECT
		id,
		local_graph_id,
		rra_id,
		title,
		host_id,
		host_grouping_type,
		order_key,
		sort_children_type
		FROM graph_tree_items
		WHERE graph_tree_id=$tree_id
		ORDER BY order_key");

	if (sizeof($nodes)) {
		foreach ($nodes as $node) {
			/* taken from tree.php, funtion item_edit() */
			$current_type = TREE_ITEM_TYPE_HEADER;
			if ($node["local_graph_id"] > 0) { $current_type = TREE_ITEM_TYPE_GRAPH; }
			if ($node["title"] != "") { $current_type = TREE_ITEM_TYPE_HEADER; }
			if ($node["host_id"] > 0) { $current_type = TREE_ITEM_TYPE_HOST; }

			switch ($current_type) {
				case TREE_ITEM_TYPE_HEADER:
					$starting_tier = tree_tier($node["order_key"]);
					if ($starting_tier == 1) {
						$parentID = 0;
					}else{
						$parent_tier = substr($node["order_key"], 0, (($starting_tier - 1) * CHARS_PER_TIER));
						$parentID = db_fetch_cell("SELECT id FROM graph_tree_items WHERE order_key LIKE '$parent_tier%%' AND graph_tree_id=$tree_id ORDER BY order_key LIMIT 1");
					}

					if ($nodeType == '' || $nodeType == 'header') {
						if ($parentNode == '' || $parentNode == $parentID) {
							echo $tree_item_types[$current_type]."\t";
							echo $node["id"]."\t";
							if ($parentID == 0) {
								echo "N/A\t";
							}else{
								echo $parentID."\t";
							}

							echo $node["title"]."\t";
							echo $tree_sort_types[$node["sort_children_type"]]."\t";
							echo "\n";
						}
					}
					$parentID = $node["id"];

					break;

				case TREE_ITEM_TYPE_GRAPH:
					if ($nodeType == '' || $nodeType == 'graph') {
						if ($parentNode == '' || $parentNode == $parentID) {
							echo $tree_item_types[$current_type]."\t";
							echo $node["id"]."\t";
							if ($parentID == 0) {
								echo "N/A\t";
							}else{
								echo $parentID."\t";
							}

							/* fetch the title for that graph */
							$graph_title = db_fetch_cell("SELECT
							graph_templates_graph.title_cache as name
							FROM (
								graph_templates_graph,
								graph_local)
							WHERE
								graph_local.id=graph_templates_graph.local_graph_id and
								local_graph_id = " . $node["local_graph_id"]);

							$rra = db_fetch_cell("SELECT
								name
								FROM rra
								WHERE id =" . $node["rra_id"]);

							echo $graph_title ."\t";
							echo $rra . "\t";
							echo "\n";
						}
					}
					break;

				case TREE_ITEM_TYPE_HOST:
					if ($nodeType == '' || $nodeType == 'host') {
						if ($parentNode == '' || $parentNode == $parentID) {
							echo $tree_item_types[$current_type]."\t";
							echo $node["id"]."\t";
							if ($parentID == 0) {
								echo "N/A\t";
							}else{
								echo $parentID."\t";
							}

							$name = db_fetch_cell("SELECT
							hostname
							FROM host
							WHERE id = " . $node["host_id"]);
							echo $name . "\t";
							echo $tree_device_group_types[$node["host_grouping_type"]]."\t";
							echo "\n";
						}
					}
				break;
			}
		}
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displayRRAs($quietMode = FALSE) {
	if (!$quietMode) {
		echo "Known RRAs:\nid\tsteps\trows\ttimespan\tname\n";
	}

	$rras = db_fetch_assoc("SELECT
		id,
		name,
		steps,
		rows,
		timespan
		FROM rra
		ORDER BY id");

	if (sizeof($rras)) {
		foreach ($rras as $rra) {
			echo $rra["id"]."\t";
			echo $rra["steps"]."\t";
			echo $rra["rows"]."\t";
			echo $rra["timespan"]."\t\t";
			echo $rra["name"]."\n";
		}
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displayHostGraphs($host_id, $quietMode = FALSE) {

	if (!$quietMode) {
		echo "Known Host Graphs: (id, name, template)\n";
	}

	$graphs = db_fetch_assoc("SELECT
		graph_templates_graph.local_graph_id as id,
		graph_templates_graph.title_cache as name,
		graph_templates.name as template_name
		FROM (graph_local,graph_templates_graph)
		LEFT JOIN graph_templates ON (graph_local.graph_template_id=graph_templates.id)
		WHERE graph_local.id=graph_templates_graph.local_graph_id
		AND graph_local.host_id=" . $host_id . "
		ORDER BY graph_templates_graph.local_graph_id");

	if (sizeof($graphs)) {
		foreach ($graphs as $graph) {
			echo $graph["id"] . "\t";
			echo $graph["name"] . "\t";
			echo $graph["template_name"] . "\t";
			echo "\n";
		}
	}

	if (!$quietMode) {
		echo "\n";
	}
}

function displayUsers($quietMode = FALSE) {
	if (!$quietMode) {
		echo "Known Users:\nid\tusername\tfull_name\n";
	}

	$groups = db_fetch_assoc("SELECT
				id,
				username,
				full_name
				FROM user_auth
				ORDER BY id");

	if (sizeof($groups)) {
		foreach ($groups as $group) {
			echo $group["id"]."\t";
			echo $group["username"]."\t";
			echo $group["full_name"]."\n";
		}
	}

	if (!$quietMode) {
		echo "\n";
	}
}

/** displayGroups
 *
 * @param bool $quietMode
 */
function displayGroups($quietMode = FALSE) {
	if (!$quietMode) {
		echo __("Known Groups: (tbd...)") . "\n";
	}

	$groups = db_fetch_assoc("");

	if (sizeof($groups)) {
		foreach ($groups as $group) {
			echo $group["id"]."\t";
			echo $group["username"]."\t";
			echo $group["full_name"]."\n";
		}
	}

	if (!$quietMode) {
		echo "\n";
	}
}


/** displayGenericArray	- column-save printout of arrays
 * @param array $data			- the array to be printed; fields of each array item must relate to $req_fields
 * @param array $req_fields		- an array of fields to be printed;
 * 									index = field name;
 * 									"desc" = human readable description
 * @param string $title			- optional title of printout; skipped in quietMode
 * @param bool $quietMode		- optionally suppress title printout
 * @return bool					- true, if anything has been printed
 */
function displayGenericArray($data, $req_fields=array(), $title="", $quietMode=FALSE) {
	$exit_code = false; # assume an error until we've printed sth
	$pad = 2;		# default padding size

	if (sizeof($data) && sizeof($req_fields)) {
		# determine length of each data field
		reset($req_fields);
		foreach($req_fields as $key => $value) {
			if(!isset($req_fields{$key}["desc"])) {	# if no explicit field description given, use field name as description
				$req_fields{$key}["desc"] = $key;
			}
			# default field length equals length of header description
			$req_fields{$key}["length"] = strlen($req_fields{$key}["desc"]);

			foreach ($data as $row) {				# see, whether any data field is longer than the corresponding header
				if (isset($row{$key}))
				$req_fields{$key}["length"] = max($req_fields{$key}["length"],strlen($row{$key}));
			}
		}

		if (!$quietMode) {
			if ($title === "") {
				#
			} else {
				echo $title . "\n";
			}
			# now print headers: field identifier and field names
			reset($req_fields);
			foreach($req_fields as $item) {
				print(str_pad($item["desc"], $item["length"]+$pad));
			}
			print "\n";
		}

		# and data, finally
		if (sizeof($data) > 0) {
			foreach ($data as $row) {
				reset($req_fields);
				while (list ($field_name, $field_array) = each($req_fields)) {
					if (isset($row[$field_name])) {
						print(str_pad($row[$field_name], $req_fields[$field_name]["length"]+$pad));
						$exit_code = true;
					}
				}
				print "\n";
			}
		}
	}
	return $exit_code;
}


/**
 * verifyDevice		- verifies all array items for a device array
 * 					  recodes the device array, if necessary
 * @param $device		- device array (part of device table)
 * @param $ri_check	- request a referential integrity test
 * returns			- if ok, returns true with array recoded; otherwise array containg error message
 */
function verifyDevice(&$device, $ri_check=false) {
	require_once(CACTI_INCLUDE_PATH . "/device/device_constants.php");

	foreach($device as $key => $value) {

		switch ($key) {
			case "id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*) FROM host WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This device id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "site_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Site Id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*) FROM sites WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This site id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "poller_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Poller Id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*) FROM poller WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This poller id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "host_template_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Host Template Id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*) FROM host_template WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This Host template id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "description":
				break;
			case "hostname":
				break;
			case "notes":
				break;
			case "snmp_community":
				break;
			case "snmp_version":
				if (($value == 1 || $value == 2 || $value == 3)) {
					#
				}else{
					$check["err_msg"] = "ERROR: Invalid SNMP Version: ($value)";
					return $check;
				}
				break;
			case "snmp_username":
				break;
			case "snmp_password":
				break;
			case "snmp_auth_protocol":
				if (strtoupper($value) == SNMP_AUTH_PROTOCOL_MD5) {
					$device{$key} = SNMP_AUTH_PROTOCOL_MD5;
				} elseif (strtoupper($value) == SNMP_AUTH_PROTOCOL_SHA) {
					$device{$key} = SNMP_AUTH_PROTOCOL_SHA;
				} elseif (strtoupper($value) == SNMP_AUTH_PROTOCOL_NONE) {
					$device{$key} = SNMP_AUTH_PROTOCOL_NONE;
				} else {
					$check["err_msg"] = "ERROR: Invalid SNMP Authentication Protocol: ($value)";
					return $check;
				}

				break;
			case "snmp_priv_passphrase":
				break;
			case "snmp_priv_protocol":
				if (strtoupper($value) == SNMP_PRIV_PROTOCOL_DES) {
					$device{$key} = SNMP_PRIV_PROTOCOL_DES;
				} elseif (strtoupper($value) == SNMP_PRIV_PROTOCOL_AES128) {
					$device{$key} = SNMP_PRIV_PROTOCOL_AES128;
				} elseif (strtoupper($value) == SNMP_PRIV_PROTOCOL_NONE) {
					$device{$key} = SNMP_PRIV_PROTOCOL_NONE;
				} else {
					$check["err_msg"] = "ERROR: Invalid SNMP Privacy Protocol: ($value)";
					return $check;
				}

				break;
			case "snmp_context":
				break;
			case "snmp_port":
				if ($value > 0) {
					# fine
				}else{
					$check["err_msg"] = "ERROR: Invalid SNMP Port: ($value)";
					return $check;
				}
				break;
			case "snmp_timeout":
				if (($value > 0) && ($value <= 20000)) {
					# fine
				}else{
					$check["err_msg"] = "ERROR: Invalid SNMP Timeout: ($value). Valid values are from 1 to 20000";
					return $check;
				}
				break;
			case "availability_method":
				switch(strtolower($value)) {
					case "none":
						$availability_method = '0'; /* tried to use AVAIL_NONE, but then ereg failes on validation, sigh */
						break;
					case "ping":
						$availability_method = AVAIL_PING;
						break;
					case "snmp":
						$availability_method = AVAIL_SNMP;
						break;
					case "pingsnmp":
						$availability_method = AVAIL_SNMP_AND_PING;
						break;
					default:
						$check["err_msg"] = "ERROR: Invalid Availability Parameter: ($value)";
						return $check;
				}
				$device{$key} = $availability_method;
				break;
			case "ping_method":
				switch(strtolower($value)) {
					case "icmp":
						$ping_method = PING_ICMP;
						break;
					case "tcp":
						$ping_method = PING_TCP;
						break;
					case "udp":
						$ping_method = PING_UDP;
						break;
					default:
						$check["err_msg"] = "ERROR: Invalid Ping Method: ($value)";
						return $check;
				}
				$device{$key} = $ping_method;
				break;
			case "ping_port":
				if ($value > 0) {
					# fine
				}else{
					$check["err_msg"] = "ERROR: Invalid Ping Port: ($value)";
					return $check;
				}
				break;
			case "ping_timeout":
				if ($value > 0) {
					# fine
				}else{
					$check["err_msg"] = "ERROR: Invalid Ping Timeout: ($value)";
					return $check;
				}
				break;
			case "ping_retries":
				if ($value > 0) {
					# fine
				}else{
					$check["err_msg"] = "ERROR: Invalid Ping Retries: ($value)";
					return $check;
				}
				break;
			case "max_oids":
				if ($value > 0) {
					# fine
				}else{
					$check["err_msg"] = "ERROR: Invalid Max OIDs: ($value)";
					return $check;
				}
				break;
			case "device_threads":
				if ($value > 0) {
					# fine
				}else{
					$check["err_msg"] = "ERROR: Invalid Device Threads: ($value)";
					return $check;
				}
				break;
			case "disabled":
				switch ($value) {
					case 1:
					case 'on':
					case "'on'":
						$device["disabled"]  = CHECKED;
						break;
					case 0:
					case '':
					case "''":
					case 'off':
					case "'off'":
						$device["disabled"]  = '""';
						break;
					default:
						$check["err_msg"] = "ERROR: Invalid disabled flag ($value)";
						return $check;
				}
				break;
			default:
				# device array may contain "unknown" columns due to extensions made by any plugin
				# in future, a validation hook may be implemented here
				/* TODO: validation hook */
		}
	}

	# everything's fine
	return true;
}

/**
 * verifyDataQuery	- verifies all array items for a data query array
 * 					  recodes the array, if necessary
 * @param array $data_query	- data query array (part of host_snmp_query)
 * @param bool  $ri_check	- request a referential integrity test
 * returns			- if ok, returns true with array recoded; otherwise array containg error message
 */
function verifyDataQuery(&$data_query, $ri_check=false) {
	require_once(CACTI_INCLUDE_PATH . "/data_query/data_query_constants.php");

	foreach($data_query as $key => $value) {

		switch ($key) {
			case "host_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*) FROM host WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This device id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "snmp_query_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: SNMP query id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*) FROM snmp_query WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This SNMP query id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "sort_field":
				break;
			case "title_format":
				break;
			case "reindex_method":
				if ((((string) $value) === ((string)(int) $value)) &&
				($value >= DATA_QUERY_AUTOINDEX_NONE) &&
				($value <= DATA_QUERY_AUTOINDEX_VALUE_CHANGE)) {
					$data_query["reindex_method"] = $value;
				} else {
					switch (strtolower($value)) {
						case "none":
							$data_query["reindex_method"] = DATA_QUERY_AUTOINDEX_NONE;
							break;
						case "uptime":
							$data_query["reindex_method"] = DATA_QUERY_AUTOINDEX_BACKWARDS_UPTIME;
							break;
						case "index":
							$data_query["reindex_method"] = DATA_QUERY_AUTOINDEX_INDEX_COUNT_CHANGE;
							break;
						case "fields":
							$data_query["reindex_method"] = DATA_QUERY_AUTOINDEX_FIELD_VERIFICATION;
							break;
						case "value":
							$data_query["reindex_method"] = DATA_QUERY_AUTOINDEX_VALUE_CHANGE;
							break;
						default:
							$check["err_msg"] = "ERROR: You must supply a valid reindex method for all devices!" . "\n";
							return $check;
					}
				}
				break;
			default:
				# device array may contain "unknown" columns due to extensions made by any plugin
				# in future, a validation hook may be implemented here
				/* TODO: validation hook */
		}
	}

	# everything's fine
	return true;
}

/**
 * verifyDQGraph	- verifies all array items for a graph array to create a Data Query based Graph
 * 					  recodes the dqGraph array, if necessary
 * @param $dqGraph	- dqGraph array (part of  table)
 * @param $ri_check	- request a referential integrity test
 * returns			- if ok, returns true with array recoded; otherwise array containg error message
 */
function verifyDQGraph(&$dqGraph, $ri_check=false) {
	require_once(CACTI_INCLUDE_PATH . "/data_query/data_query_constants.php");

	if (($dqGraph["snmp_query_id"] == "") ||
	($dqGraph["snmp_query_graph_id"] == "") ||
	($dqGraph["snmp_field"] == "") ||
	($dqGraph["snmp_value"] == "") ||
	($dqGraph["host_id"] == "") ||
	($dqGraph["graph_template_id"] == "")) {
		$check["err_msg"] = "ERROR: For graph type of 'ds' you must supply more options" . "\n";
		return $check;
	}

	foreach($dqGraph as $key => $value) {

		switch ($key) {
			case "host_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Device id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*) FROM host WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This Host id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "graph_template_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Graph template id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*) FROM graph_templates WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This Graph template id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "snmp_query_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: SNMP query id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*) FROM snmp_query WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This SNMP query id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "snmp_query_graph_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: SNMP query type id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*)	FROM snmp_query_graph WHERE id=" . $value .
											" AND snmp_query_id=" . $dqGraph["snmp_query_id"] .
											" AND graph_template_id=" . $dqGraph["graph_template_id"]);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This SNMP query type id does not exist ($value) for SNMP query "  . $dqGraph["snmp_query_id"] . ", graph template id " . $dqGraph["graph_template_id"];
						return $check;
					}
				}
				break;
			case "snmp-field":
				if ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*)	FROM host_snmp_cache WHERE host_id=" . $dqGraph["host_id"] .
											" AND snmp_query_id=" . $dqGraph["snmp_query_id"] .
											" AND field_name='" . $value . "'");
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This SNMP field name does not exist ($value) for SNMP query " . $dqGraph["snmp_query_id"] . ", device id " . $dqGraph["host_id"];
						return $check;
					}
				}
				break;
			case "snmp-value":
				if ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*)	FROM host_snmp_cache WHERE host_id=" . $dqGraph["host_id"] .
											" AND snmp_query_id=" . $dqGraph["snmp_query_id"] .
											" AND field_name='" . $dqGraph["snmp-field"] . "'" .
											" AND field_value='" . $value . "'");
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This SNMP field value does not exist ($value) for SNMP query " . $dqGraph["snmp_query_id"] . ", device id " . $dqGraph["host_id"] . ", SNMP field " . $dqGraph["snmp-field"];
						return $check;
					}
				}
				break;
			case "reindex-method":
				if ((((string) $value) === ((string)(int) $value)) &&
				($value >= DATA_QUERY_AUTOINDEX_NONE) &&
				($value <= DATA_QUERY_AUTOINDEX_VALUE_CHANGE)) {
					$dqGraph["reindex_method"] = $value;
				} else {
					switch (strtolower($value)) {
						case "none":
							$dqGraph["reindex_method"] = DATA_QUERY_AUTOINDEX_NONE;
							break;
						case "uptime":
							$dqGraph["reindex_method"] = DATA_QUERY_AUTOINDEX_BACKWARDS_UPTIME;
							break;
						case "index":
							$dqGraph["reindex_method"] = DATA_QUERY_AUTOINDEX_INDEX_COUNT_CHANGE;
							break;
						case "fields":
							$dqGraph["reindex_method"] = DATA_QUERY_AUTOINDEX_FIELD_VERIFICATION;
							break;
						case "value":
							$dqGraph["reindex_method"] = DATA_QUERY_AUTOINDEX_VALUE_CHANGE;
							break;
						default:
							$check["err_msg"] = "ERROR: You must supply a valid reindex method for all devices!" . "\n";
							return $check;
					}
				}
				break;
			default:
				# device array may contain "unknown" columns due to extensions made by any plugin
				# in future, a validation hook may be implemented here
				/* TODO: validation hook */
		}

	}
}

/** verifyGraphInputFields	- verifies Graph Input Fields
 * @param $templateId		- graph template id 
 * @param $cgInputFields	- input fields as given by user
 * @param $input_fields		- input fields related to the specific graph template
 * returns					- value array as needed by graph creation function
 */
function verifyGraphInputFields($templateId, $cgInputFields, $input_fields) {

	$values = array();

	# input fields given?
	if (strlen($cgInputFields)) {
		$fields = explode(" ", $cgInputFields);

		if (sizeof($fields)) {
			foreach ($fields as $option) {
				$data_template_id = 0;
				$option_value = explode("=", $option);

				if (substr_count($option_value[0], ":")) {
					$compound 			= explode(":", $option_value[0]);
					$data_template_id 	= $compound[0];
					$field_name       	= $compound[1];
				}else{
					$field_name       	= $option_value[0];
				}

				/* check for the input fields existance */
				$field_found = FALSE;
				if (sizeof($input_fields)) {
					foreach ($input_fields as $key => $row) {
						if (substr_count($key, $field_name)) {
							if ($data_template_id == 0) {
								$data_template_id = $row["data_template_id"];
							}
							$field_found = TRUE;
							break;
						}
					}
				}

				if (!$field_found) {
					echo "ERROR: Unknown input-field ($field_name)" . "\n";
					echo "Try php -q graph_list.php --list-input-fields" . "\n";
					exit(1);
				}

				$value = $option_value[1];
				$values["cg"][$templateId]["custom_data"][$data_template_id][$input_fields[$data_template_id . ":" . $field_name]["data_input_field_id"]] = $value;
			}
		}

		return $values;
	}
}

/** verifyPermissions
 *
 * @param array $perm
 * @param string $delim
 * @param bool $ri_check
 */
function verifyPermissions(&$perm, $delim, $ri_check=false) {
	require(CACTI_INCLUDE_PATH . "/auth/auth_arrays.php");

	foreach($perm as $key => $value) {

		switch ($key) {
			case "user_id":
				# non-null userids given?
				if (strlen($value)) {
					$userids = explode($delim, $value);
					if (sizeof($userids)) {
						foreach ($userids as $id) {
							if (!(((string) $id) === ((string)(int) $id))) {
								$check["err_msg"] = "ERROR: User id must be integer ($id)";
								return $check;
							} elseif ($ri_check) {
								$match = db_fetch_cell("SELECT COUNT(*)	FROM user_auth WHERE id=" . $id);
								if ($match == 0) {
									$check["err_msg"] = "ERROR: This user id does not exist ($id)";
									return $check;
								}
							}
							# if we arrive here, everything has been verified
							$perm["userids"][$id] = $id;
						}
					}
				}
				break;
			case "item_type":
				if ($value == $perm_item_types[PERM_GRAPHS]) {
					$perm["item_type_id"] = PERM_GRAPHS;
				} elseif ($value == $perm_item_types[PERM_TREES]) {
					$perm["item_type_id"] = PERM_TREES;
				} elseif ($value == $perm_item_types[PERM_DEVICES]) {
					$perm["item_type_id"] = PERM_DEVICES;
				} elseif ($value == $perm_item_types[PERM_GRAPH_TEMPLATES]) {
					$perm["item_type_id"] = PERM_GRAPH_TEMPLATES;
				} else {
					$check["err_msg"] = "ERROR: Invalid Item Type: (" . $perm["item_type"] . ")";
					return $check;
				}
				break;
			case "item_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Item id must be integer ($value)";
					return $check;
				} elseif ($ri_check && isset($perm["item_type"])) {

					switch ($perm["item_type"]) {
						case "graph":
						case PERM_GRAPHS: /* graph */
							$match = db_fetch_cell("SELECT local_graph_id FROM graph_templates_graph WHERE local_graph_id=" . $value);
							if ($match == 0) {
								$check["err_msg"] = "ERROR: Invalid Graph item id ($value)";
								return $check;
							}
							break;
						case "tree":
						case PERM_TREES: /* tree */
							$match = db_fetch_cell("SELECT id FROM graph_tree WHERE id=" . $value);
							if ($match == 0) {
								$check["err_msg"] = "ERROR: Invalid Tree item id ($value)";
								return $check;
							}
							break;
						case "device":
						case PERM_DEVICES: /* device */
							$match = db_fetch_cell("SELECT id FROM host WHERE id=" . $value);
							if ($match == 0) {
								$check["err_msg"] = "ERROR: Invalid device item id ($value)";
								return $check;
							}
							break;
						case "graph_template":
						case PERM_GRAPH_TEMPLATES: /* graph_template */
							$match = db_fetch_cell("SELECT id FROM graph_templates WHERE id=" . $value);
							if ($match == 0) {
								$check["err_msg"] = "ERROR: Invalid Graph Template item id ($value)";
								return $check;
							}
							break;
					}
				}
				break;
			case "devices":
				# has to be verified by verifyDevices()
				break;
			default:
				# nothing
		}
	}
}

/** verifyTree
 *
 * @param array $tree
 * @param bool $ri_check
 */
function verifyTree(&$tree, $ri_check=false) {
	require(CACTI_INCLUDE_PATH . "/data_query/data_query_arrays.php");

	foreach($tree as $key => $value) {

		switch ($key) {
			case "id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Tree id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*)	FROM graph_tree WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This tree id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "name":
				break;
			case "sort_type_cli":
				if ($value == $tree_sort_types_cli[DATA_QUERY_INDEX_SORT_TYPE_NONE]) {
					$tree["sort_type"] = DATA_QUERY_INDEX_SORT_TYPE_NONE;
				} elseif ($value == $tree_sort_types_cli[DATA_QUERY_INDEX_SORT_TYPE_ALPHABETIC]) {
					$tree["sort_type"] = DATA_QUERY_INDEX_SORT_TYPE_ALPHABETIC;
				} elseif ($value == $tree_sort_types_cli[DATA_QUERY_INDEX_SORT_TYPE_NATURAL]) {
					$tree["sort_type"] = DATA_QUERY_INDEX_SORT_TYPE_NATURAL;
				} elseif ($value == $tree_sort_types_cli[DATA_QUERY_INDEX_SORT_TYPE_NUMERIC]) {
					$tree["sort_type"] = DATA_QUERY_INDEX_SORT_TYPE_NUMERIC;
				} else {
					$check["err_msg"] = "ERROR: Invalid Sort Type: (" . $tree["sort_type_cli"] . ")";
					return $check;
				}
				break;
			default:
				# nothing
		}
	}
}

/** verifyTreeItem
 *
 * @param array $tree_item
 * @param bool $ri_check
 */
function verifyTreeItem(&$tree_item, $ri_check=false) {
	require(CACTI_INCLUDE_PATH . "/data_query/data_query_arrays.php");
	require(CACTI_INCLUDE_PATH . "/graph_tree/graph_tree_arrays.php");

	foreach($tree_item as $key => $value) {

		switch ($key) {
			case "id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Tree item id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*)	FROM graph_tree_items WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This tree item id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "graph_tree_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Tree id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*)	FROM graph_tree WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This tree id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "local_graph_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Graph id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*)	FROM graph_local WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This graph id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "rra_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: RRA id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*)	FROM rra WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This rra id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "title":
				break;
			case "host_id":
				if (!(((string) $value) === ((string)(int) $value))) {
					$check["err_msg"] = "ERROR: Id must be integer ($value)";
					return $check;
				} elseif ($ri_check) {
					$match = db_fetch_cell("SELECT COUNT(*) FROM host WHERE id=" . $value);
					if ($match == 0) {
						$check["err_msg"] = "ERROR: This device id does not exist ($value)";
						return $check;
					}
				}
				break;
			case "parent_node":
				if (isset($tree_item["graph_tree_id"])) {
					if (!(((string) $value) === ((string)(int) $value))) {
						$check["err_msg"] = "ERROR: Parent node must be integer ($value)";
						return $check;
					} elseif ($ri_check) {
						$match = db_fetch_cell("SELECT COUNT(*)	" .
											"FROM graph_tree_items " .
											"WHERE graph_tree_id=" . $tree_item["graph_tree_id"] . " " .
											"AND id=" . $value);
						if ($match == 0) {
							$check["err_msg"] = "ERROR: This parent node does not exist ($value)";
							return $check;
						}
					}
				} else {
					$check["err_msg"] = "ERROR: Tree id must be given if parent node is specified";
					return $check;
				}
				break;
			case "host_grouping_type":
				if ($value != TREE_DEVICE_GROUPING_GRAPH_TEMPLATE && $value != TREE_DEVICE_GROUPING_DATA_QUERY_INDEX) {
					$check["err_msg"] = "ERROR: Host Group Type must be " . TREE_DEVICE_GROUPING_GRAPH_TEMPLATE . " or " . TREE_DEVICE_GROUPING_DATA_QUERY_INDEX . " (Graph Template or Data Query Index)" . "\n";
					return $check;
				}
				break;
			case "sort_children_type":
				if ($value != DATA_QUERY_INDEX_SORT_TYPE_NONE && $value != DATA_QUERY_INDEX_SORT_TYPE_ALPHABETIC && $value != DATA_QUERY_INDEX_SORT_TYPE_NUMERIC && $value != DATA_QUERY_INDEX_SORT_TYPE_NATURAL) {
					$check["err_msg"] = "ERROR: Sort Children Type must be one of (" . DATA_QUERY_INDEX_SORT_TYPE_NONE . ", " . DATA_QUERY_INDEX_SORT_TYPE_ALPHABETIC . ", " . DATA_QUERY_INDEX_SORT_TYPE_NUMERIC . ", " . DATA_QUERY_INDEX_SORT_TYPE_NATURAL . " (Manual, Alphabetic, Numeric, Natural)" . "\n";
					return $check;
				}
				break;
			case "sort_type_cli":
				if ($value == $tree_sort_types_cli[DATA_QUERY_INDEX_SORT_TYPE_NONE]) {
					$tree_item["sort_children_type"] = DATA_QUERY_INDEX_SORT_TYPE_NONE;
				} elseif ($value == $tree_sort_types_cli[DATA_QUERY_INDEX_SORT_TYPE_ALPHABETIC]) {
					$tree_item["sort_children_type"] = DATA_QUERY_INDEX_SORT_TYPE_ALPHABETIC;
				} elseif ($value == $tree_sort_types_cli[DATA_QUERY_INDEX_SORT_TYPE_NATURAL]) {
					$tree_item["sort_children_type"] = DATA_QUERY_INDEX_SORT_TYPE_NATURAL;
				} elseif ($value == $tree_sort_types_cli[DATA_QUERY_INDEX_SORT_TYPE_NUMERIC]) {
					$tree_item["sort_children_type"] = DATA_QUERY_INDEX_SORT_TYPE_NUMERIC;
				} else {
					$check["err_msg"] = "ERROR: Invalid Sort Type: (" . $tree_item["sort_type_cli"] . ")";
					return $check;
				}
				break;
			default:
				# nothing
		}
	}
}
