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

/* used both for polling and reindexing */
define('POLLER_ACTION_SNMP', 0);
define('POLLER_ACTION_SCRIPT', 1);
define('POLLER_ACTION_SCRIPT_PHP', 2);
/* used for reindexing only: 
 * in case we do not have OID_NUM_INDEXES|ARG_NUM_INDEXES
 * we simply use the OID_INDEX|ARG_INDEX and count number of indexes found
 * so this is more of a REINDEX_ACTION_... thingy
 */
define('POLLER_ACTION_SNMP_COUNT', 10);
define('POLLER_ACTION_SCRIPT_COUNT', 11);
define('POLLER_ACTION_SCRIPT_PHP_COUNT', 12);

define('POLLER_COMMAND_REINDEX', 1);
define('POLLER_COMMAND_RRDPURGE', 2);

define('POLLER_CMD', 1);
define('POLLER_SPINE', 2);

