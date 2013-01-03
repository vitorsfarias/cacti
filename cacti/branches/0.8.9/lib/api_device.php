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

include_once(CACTI_LIBRARY_PATH . "/device.php");

/** DEPRECATED!  */
function api_device_remove($host_id) {
	cacti_log("function " . __FUNCTION__ . " called using $host_id", false, "DEPRECATION WARNING");
	device_remove($host_id);
}

/** DEPRECATED!  */
function api_device_remove_multi($host_ids) {
	cacti_log("function " . __FUNCTION__ . " called", false, "DEPRECATION WARNING");
	device_remove_multi($host_ids);
}

/** DEPRECATED!  */
function api_device_dq_remove($host_id, $data_query_id) {
	cacti_log("function " . __FUNCTION__ . " called", false, "DEPRECATION WARNING");
	device_dq_remove($host_id, $data_query_id);
}

/** DEPRECATED!  */
function api_device_gt_remove($host_id, $graph_template_id) {
	cacti_log("function " . __FUNCTION__ . " called", false, "DEPRECATION WARNING");
	device_gt_remove($host_id, $graph_template_id);
}

/** DEPRECATED!  */
function api_device_save($id, $host_template_id, $description, $hostname, $snmp_community, $snmp_version,
	$snmp_username, $snmp_password, $snmp_port, $snmp_timeout, $disabled, $availability_method, $ping_method, $ping_port,
	$ping_timeout, $ping_retries, $notes, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context,
	$max_oids, $device_threads, $site_id=0, $poller_id=0, $template_enabled='') {
	cacti_log("function " . __FUNCTION__ . " called", false, "DEPRECATION WARNING");
	device_save($id, $host_template_id, $description, $hostname, $snmp_community, $snmp_version,
	$snmp_username, $snmp_password, $snmp_port, $snmp_timeout, $disabled, $availability_method, $ping_method, $ping_port,
	$ping_timeout, $ping_retries, $notes, $snmp_auth_protocol, $snmp_priv_passphrase, $snmp_priv_protocol, $snmp_context,
	$max_oids, $device_threads, $site_id, $poller_id, $template_enabled);
}

?>

























