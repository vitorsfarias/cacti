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

include_once(CACTI_LIBRARY_PATH . "/device.php");

/* api_device_remove - removes a device
   @arg $device_id - the id of the device to remove */
function api_device_remove($device_id) {
	cacti_log("function " . __FUNCTION__ . " called using $device_id", false, "DEPRECATION WARNING");
	device_remove($device_id);
}

/* api_device_remove_multi - removes multiple devices in one call
   @arg $device_ids - an array of device id's to remove */
function api_device_remove_multi($device_ids) {
	cacti_log("function " . __FUNCTION__ . " called", false, "DEPRECATION WARNING");
	device_remove_multi($device_ids);
}

/* api_device_dq_remove - removes a device->data query mapping
   @arg $device_id - the id of the device which contains the mapping
   @arg $data_query_id - the id of the data query to remove the mapping for */
function api_device_dq_remove($device_id, $data_query_id) {
	cacti_log("function " . __FUNCTION__ . " called", false, "DEPRECATION WARNING");
	device_dq_remove($device_id, $data_query_id);
}

/* api_device_gt_remove - removes a device->graph template mapping
   @arg $device_id - the id of the device which contains the mapping
   @arg $graph_template_id - the id of the graph template to remove the mapping for */
function api_device_gt_remove($device_id, $graph_template_id) {
	cacti_log("function " . __FUNCTION__ . " called", false, "DEPRECATION WARNING");
	device_gt_remove($device_id, $graph_template_id);
}

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

























