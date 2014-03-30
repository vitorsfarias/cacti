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

/* used for plugin actions */
define('PLUGIN_ACTION_INSTALL', 'Install');
define('PLUGIN_ACTION_ENABLE', 'Enable');
define('PLUGIN_ACTION_DISABLE', 'Disable');
define('PLUGIN_ACTION_UNINSTALL', 'Uninstall');
define('PLUGIN_ACTION_CHECK', 'Check');

/* plugin status names */
define('PLUGIN_STATUS_DISABLED', '-2');
define('PLUGIN_STATUS_ACTIVE_OLD', '-1');
define('PLUGIN_STATUS_NOT_INSTALLED', '0');
define('PLUGIN_STATUS_ACTIVE_NEW', '1');
define('PLUGIN_STATUS_AWAITING_CONFIGURATION', '2');
define('PLUGIN_STATUS_AWAITING_UPGRADE', '3');
define('PLUGIN_STATUS_INSTALLED', '4');

/* plugin types */
define('PLUGIN_TYPE_SYSTEM', '0');
define('PLUGIN_TYPE_GENERAL', '1');

