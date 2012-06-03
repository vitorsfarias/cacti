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

/* compatibility mode for constants 
 * set to false to verify, that including constants here is no longer required */
define('CACTI_CONST_COMPAT', TRUE);

/* compatibility mode for Arrays 
 * set to false to verify, that including arrays here is no longer required */
define('CACTI_ARRAY_COMPAT', TRUE);

/* compatibility mode for Forms 
 * set to false to verify, that including forms here is no longer required */
define('CACTI_FORM_COMPAT', TRUE);

define('CHECKED', 'on');
define('ACTION_NONE', '-1');

define('CACTI_ESCAPE_CHARACTER', '"');
#define("CACTI_ESCAPE_CHARACTER", "\"");

define("HOST_GROUPING_GRAPH_TEMPLATE", 1);
define("HOST_GROUPING_DATA_QUERY_INDEX", 2);

define("TREE_ORDERING_NONE", 1);
define("TREE_ORDERING_ALPHABETIC", 2);
define("TREE_ORDERING_NUMERIC", 3);
define("TREE_ORDERING_NATURAL", 4);

define("TREE_ITEM_TYPE_HEADER", 1);
define("TREE_ITEM_TYPE_GRAPH", 2);
define("TREE_ITEM_TYPE_HOST", 3);

define("RRDTOOL_OUTPUT_NULL", 0);
define("RRDTOOL_OUTPUT_STDOUT", 1);
define("RRDTOOL_OUTPUT_STDERR", 2);
define("RRDTOOL_OUTPUT_GRAPH_DATA", 3);

define("DATA_INPUT_TYPE_SCRIPT", 1);
define("DATA_INPUT_TYPE_SNMP", 2);
define("DATA_INPUT_TYPE_SNMP_QUERY", 3);
define("DATA_INPUT_TYPE_SCRIPT_QUERY", 4);
define("DATA_INPUT_TYPE_PHP_SCRIPT_SERVER", 5);
define("DATA_INPUT_TYPE_QUERY_SCRIPT_SERVER", 6);

/* used both for polling and reindexing */
define("POLLER_ACTION_SNMP", 0);
define("POLLER_ACTION_SCRIPT", 1);
define("POLLER_ACTION_SCRIPT_PHP", 2);
/* used for reindexing only:
 * in case we do not have OID_NUM_INDEXES|ARG_NUM_INDEXES
 * we simply use the OID_INDEX|ARG_INDEX and count number of indexes found
 * so this is more of a REINDEX_ACTION_... thingy
 */
define("POLLER_ACTION_SNMP_COUNT", 10);
define("POLLER_ACTION_SCRIPT_COUNT", 11);
define("POLLER_ACTION_SCRIPT_PHP_COUNT", 12);

define("POLLER_COMMAND_REINDEX", 1);
define("POLLER_COMMAND_RRDPURGE", 2);

define("POLLER_VERBOSITY_NONE", 1);
define("POLLER_VERBOSITY_LOW", 2);
define("POLLER_VERBOSITY_MEDIUM", 3);
define("POLLER_VERBOSITY_HIGH", 4);
define("POLLER_VERBOSITY_DEBUG", 5);
define("POLLER_VERBOSITY_DEVDBG", 6);

define("HOST_UNKNOWN", 0);
define("HOST_DOWN", 1);
define("HOST_RECOVERING", 2);
define("HOST_UP", 3);
define("HOST_ERROR", 4);

define("SNMP_POLLER", 0);
define("SNMP_CMDPHP", 1);
define("SNMP_WEBUI", 2);

define('RRD_VERSION_1_0', 'rrd-1.0.x');
define('RRD_VERSION_1_2', 'rrd-1.2.x');
define('RRD_VERSION_1_3', 'rrd-1.3.x');
define('RRD_VERSION_1_4', 'rrd-1.4.x');

define('OPER_MODE_NATIVE', 0);
define('OPER_MODE_RESKIN', 1);
define('OPER_MODE_IFRAME_NONAV', 2);
define('OPER_MODE_NOTABS', 3);

/*
 * moved to include/graph/graph_constants.php
 * moved to include/presets/preset_rra__constants.php
 * moved to include/data_source/data_source_constants.php
 */
if (CACTI_CONST_COMPAT === TRUE) {
	require_once(CACTI_INCLUDE_PATH . '/auth/auth_constants.php');
	require_once(CACTI_INCLUDE_PATH . '/data_query/data_query_constants.php');
	require_once(CACTI_INCLUDE_PATH . '/graph/graph_constants.php');
	require_once(CACTI_INCLUDE_PATH . '/presets/preset_rra_constants.php');
	require_once(CACTI_INCLUDE_PATH . '/data_source/data_source_constants.php');
}

?>
