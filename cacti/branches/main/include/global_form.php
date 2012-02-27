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

require(CACTI_BASE_PATH . '/include/auth/auth_arrays.php');

if (! defined('VALID_HOST_FIELDS'))
{
	$string = plugin_hook_function('valid_device_fields', '(hostname|device_id|snmp_community|snmp_username|snmp_password|snmp_auth_protocol|snmp_priv_passphrase|snmp_priv_protocol|snmp_context|snmp_version|snmp_port|snmp_timeout)');
	define('VALID_HOST_FIELDS', $string);
}

$export_types = array(
	'graph_template' => array(
		'name' => __('Graph Template'),
		'title_sql' => 'select name from graph_templates where id=|id|',
		'dropdown_sql' => 'select id,name from graph_templates order by name'
	),
	'data_template' => array(
		'name' => __('Data Source Template'),
		'title_sql' => 'select name from data_template where id=|id|',
		'dropdown_sql' => 'select id,name from data_template order by name'
	),
	'device_template' => array(
		'name' => __('Device Template'),
		'title_sql' => 'select name from device_template where id=|id|',
		'dropdown_sql' => 'select id,name from device_template order by name'
	),
	'data_query' => array(
		'name' => __('Data Query'),
		'title_sql' => 'select name from snmp_query where id=|id|',
		'dropdown_sql' => 'select id,name from snmp_query order by name'
	)
);
	
