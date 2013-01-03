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

define("DEVICE_ACTION_DELETE", "1");
define("DEVICE_ACTION_ENABLE", "2");
define("DEVICE_ACTION_DISABLE", "3");
define("DEVICE_ACTION_CHANGE_SNMP_OPTIONS", "4");
define("DEVICE_ACTION_CLEAR_STATISTICS", "5");
define("DEVICE_ACTION_CHANGE_AVAILABILITY_OPTIONS", "6");
define("DEVICE_ACTION_CHANGE_POLLER", "7");
define("DEVICE_ACTION_CHANGE_SITE", "8");

define("DEVICE_ACTION_DELETE_DS_KEEP", "1");
define("DEVICE_ACTION_DELETE_DS_DELETE", "2");

define("DEVICE_UNKNOWN", 0);
define("DEVICE_DOWN", 1);
define("DEVICE_RECOVERING", 2);
define("DEVICE_UP", 3);
define("DEVICE_ERROR", 4);

define("SNMP_VERSION_NONE", 0);
define("SNMP_VERSION_1", 1);
define("SNMP_VERSION_2", 2);
define("SNMP_VERSION_3", 3);

define("SNMP_AUTH_PROTOCOL_NONE", 	'');
define("SNMP_AUTH_PROTOCOL_MD5", 	'MD5');
define("SNMP_AUTH_PROTOCOL_SHA", 	'SHA');

define("SNMP_PRIV_PROTOCOL_NONE", 	'');
define("SNMP_PRIV_PROTOCOL_DES", 	'DES');
define("SNMP_PRIV_PROTOCOL_AES128", 'AES');

define("AVAIL_NONE", 0);
define("AVAIL_SNMP_AND_PING", 1);
define("AVAIL_SNMP", 2);
define("AVAIL_PING", 3);
define("AVAIL_SNMP_OR_PING", 4);
define("AVAIL_SNMP_GET_SYSDESC", 5);
define("AVAIL_SNMP_GET_NEXT", 6);

define("PING_NONE", 0);
define("PING_ICMP", 1);
define("PING_UDP", 2);
define("PING_TCP", 3);
