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

require_once(CACTI_BASE_PATH . "/include/device/device_constants.php");

$device_actions = array(
	DEVICE_ACTION_DELETE => "Delete",
	DEVICE_ACTION_ENABLE => "Enable",
	DEVICE_ACTION_DISABLE => "Disable",
	DEVICE_ACTION_CHANGE_SNMP_OPTIONS => "Change SNMP Options",
	DEVICE_ACTION_CLEAR_STATISTICS => "Clear Statistics",
	DEVICE_ACTION_CHANGE_AVAILABILITY_OPTIONS => "Change Availability Options",
#	DEVICE_ACTION_CHANGE_POLLER => "Change Poller",
#	DEVICE_ACTION_CHANGE_SITE => "Change Site",
	);

$device_threads = array(
	1 => "1 Thread (default)",
	2 => "2 Threads",
	3 => "3 Threads",
	4 => "4 Threads",
	5 => "5 Threads",
	6 => "6 Threads"
	);

$host_struc = array(
	"host_template_id",
	"description",
	"hostname",
	"notes",
	"snmp_community",
	"snmp_version",
	"snmp_username",
	"snmp_password",
	"snmp_auth_protocol",
	"snmp_priv_passphrase",
	"snmp_priv_protocol",
	"snmp_context",
	"snmp_port",
	"snmp_timeout",
	"max_oids",
	"availability_method",
	"ping_method",
	"ping_port",
	"ping_timeout",
	"ping_retries",
	"disabled",
	"status",
	"status_event_count",
	"status_fail_date",
	"status_rec_date",
	"status_last_error",
	"min_time",
	"max_time",
	"cur_time",
	"avg_time",
	"total_polls",
	"failed_polls",
	"availability"
	);

$snmp_versions = array(
	SNMP_VERSION_NONE	=> "Not In Use",
	SNMP_VERSION_1		=> "Version 1",
	SNMP_VERSION_2		=> "Version 2",
	SNMP_VERSION_3		=> "Version 3",
	);

$snmp_auth_protocols = array(
	SNMP_AUTH_PROTOCOL_NONE 	=> "[NONE]",
	SNMP_AUTH_PROTOCOL_MD5 		=> "MD5 (default)",
	SNMP_AUTH_PROTOCOL_SHA 		=> "SHA",
	);

$snmp_priv_protocols = array(
	SNMP_PRIV_PROTOCOL_NONE 	=> "[None]",
	SNMP_PRIV_PROTOCOL_DES 		=> "DES (default)",
	SNMP_PRIV_PROTOCOL_AES128 	=> "AES",
	);

$availability_options = array(
	AVAIL_NONE => "None",
	AVAIL_SNMP_AND_PING => "Ping and SNMP Uptime",
	AVAIL_SNMP_OR_PING => "Ping or SNMP Uptime",
	AVAIL_SNMP => "SNMP Uptime",
	AVAIL_SNMP_GET_SYSDESC => "SNMP Desc",
	AVAIL_SNMP_GET_NEXT => "SNMP getNext",
	AVAIL_PING => "Ping",
	);

$ping_methods = array(
	PING_ICMP => "ICMP Ping",
	PING_TCP => "TCP Ping",
	PING_UDP => "UDP Ping",
	);

/* snmp_line_break knows all characters, where we want break SNMP sysDescr string; order matters! */
$snmp_line_break = array(" ","#","-",":");