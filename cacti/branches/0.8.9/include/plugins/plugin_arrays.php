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

require_once(CACTI_INCLUDE_PATH . '/plugins/plugin_constants.php');

$plugin_actions = array(
	PLUGIN_ACTION_INSTALL 	=> 'Install',
	PLUGIN_ACTION_ENABLE 	=> 'Enable',
	PLUGIN_ACTION_DISABLE 	=> 'Disable',
	PLUGIN_ACTION_UNINSTALL => 'Uninstall',
	PLUGIN_ACTION_CHECK 	=> 'Check)'
);

$plugin_status_names = array(
	PLUGIN_STATUS_DISABLED 					=> 'Disabled',
	PLUGIN_STATUS_ACTIVE_OLD 				=> 'Active',
	PLUGIN_STATUS_NOT_INSTALLED 			=> 'Not Installed',
	PLUGIN_STATUS_ACTIVE_NEW 				=> 'Active',
	PLUGIN_STATUS_AWAITING_CONFIGURATION 	=> 'Awaiting Configuration',
	PLUGIN_STATUS_AWAITING_UPGRADE 			=> 'Awaiting Upgrade',
	PLUGIN_STATUS_INSTALLED 				=> 'Installed'
);
	
if (! isset($plugins) || ! is_array($plugins))
{
	$plugins = array();
}
$plugin_hooks = array();
$plugins_system = array('settings', 'boost', 'dsstats');
/* name all plugins, that have been included into core code
 * we use this to avoid any install action */
$plugins_deprecated = array('settings');

$plugin_architecture = array(
	'version' => '3.2'	# PIA compat mode still supports "old" functions
);
	
