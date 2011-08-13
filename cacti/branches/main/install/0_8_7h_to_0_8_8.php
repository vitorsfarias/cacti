<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2011 The Cacti Group                                 |
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
 | This code is designed, written, and maINTained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

function upgrade_to_0_8_8() {
	require_once("../lib/import.php");
	require("../include/plugins/plugin_arrays.php");
	require_once("../lib/plugins.php");
	require_once("../lib/poller.php");
	require_once("../lib/utility.php");

	$show_output = true;
	$drop_items = true;
	$no_drop_items = false;
	/*
	 * Create new tables
	 */

	/* Authenication System upgrade */
	$data = array();
	$data['columns'][] = array('name' => 'id', 				'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'name',		 	'type' => 'varchar(100)'							, 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'description',		'type' => 'varchar(255)'							, 'NULL' => true, 'default' => 'NULL');
	$data['columns'][] = array('name' => 'object_type',		'type' => 'int(8)',			'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'enabled', 		'type' => 'int(1)',  		'unsigned' => 'unsigned', 'NULL' => false, 'default' => 1);
	$data['columns'][] = array('name' => 'updated_when',	'type' => 'datetime'								, 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'updated_by', 		'type' => 'varchar(100)'							, 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'created_when', 	'type' => 'datetime'								, 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'created_by', 		'type' => 'varchar(100)'							, 'NULL' => false, 'default' => '');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'constraint_index', 'columns' => 'name, object_type', 'unique' => true);
	$data['keys'][] = array('name' => 'name', 'columns' => 'name');
	$data['keys'][] = array('name' => 'object_type', 'columns' => 'object_type');
	$data['type'] = 'MyISAM';
	$data['comment'] = 'Authorization Control';
	plugin_upgrade_table('0.8.8', 'auth_control', $data, $show_output, $no_drop_items);

	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'control_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'plugin_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'category', 'type' => 'varchar(25)', 'NULL' => false, 'default' => 'SYSTEM');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'value', 'type' => 'varchar(1000)', 'default' => NULL);
	$data['columns'][] = array('name' => 'enable_user_edit', 'type' => 'int(1)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'updated_when', 'type' => 'datetime', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'updated_by', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'constraint_index', 'columns' => 'control_id, plugin_id, category, name', 'unique' => true);
	$data['keys'][] = array('name' => 'control_id', 'columns' => 'control_id');
	$data['keys'][] = array('name' => 'name', 'columns' => 'name');
	$data['keys'][] = array('name' => 'plugin_id', 'columns' => 'plugin_id');
	$data['keys'][] = array('name' => 'category', 'columns' => 'category');
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'auth_data', $data, $show_output, $no_drop_items);

	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'item_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'type', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'control_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'item_id', 'columns' => 'item_id');
	$data['keys'][] = array('name' => 'type', 'columns' => 'type');
	$data['keys'][] = array('name' => 'control_id', 'columns' => 'control_id');
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'auth_graph_perms', $data, $show_output, $no_drop_items);

	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'control_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'parent_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'control_id', 'columns' => 'control_id');
	$data['keys'][] = array('name' => 'parent_id', 'columns' => 'parent_id');
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'auth_link', $data, $show_output, $no_drop_items);

	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'description', 'type' => 'text', 'NULL' => false);
	$data['columns'][] = array('name' => 'category', 'type' => 'varchar(100)', 'default' => NULL);
	$data['columns'][] = array('name' => 'plugin_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'name', 'columns' => 'name');
	$data['keys'][] = array('name' => 'plugin_id', 'columns' => 'plugin_id');
	$data['keys'][] = array('name' => 'category', 'columns' => 'category');
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'auth_perm', $data, $show_output, $no_drop_items);

	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'control_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'perm_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'control_id', 'columns' => 'control_id');
	$data['keys'][] = array('name' => 'perm_id', 'columns' => 'perm_id');
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'auth_perm_link', $data, $show_output, $no_drop_items);

	/* create a sites table */
	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'int(10)', 'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'address1', 'type' => 'varchar(100)', 'default' => '');
	$data['columns'][] = array('name' => 'address2', 'type' => 'varchar(100)', 'default' => '');
	$data['columns'][] = array('name' => 'city', 'type' => 'varchar(50)', 'default' => '');
	$data['columns'][] = array('name' => 'state', 'type' => 'varchar(20)', 'default' => '');
	$data['columns'][] = array('name' => 'postal_code', 'type' => 'varchar(20)', 'default' => '');
	$data['columns'][] = array('name' => 'country', 'type' => 'varchar(30)', 'default' => '');
	$data['columns'][] = array('name' => 'timezone', 'type' => 'varchar(40)', 'default' => '');
	$data['columns'][] = array('name' => 'alternate_id', 'type' => 'varchar(30)', 'default' => '');
	$data['columns'][] = array('name' => 'notes', 'type' => 'text');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'name', 'columns' => 'name');
	$data['keys'][] = array('name' => 'city', 'columns' => 'city');
	$data['keys'][] = array('name' => 'state', 'columns' => 'state');
	$data['keys'][] = array('name' => 'postal_code', 'columns' => 'postal_code');
	$data['keys'][] = array('name' => 'country', 'columns' => 'country');
	$data['keys'][] = array('name' => 'alternate_id', 'columns' => 'alternate_id');
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'sites', $data, $show_output, $no_drop_items);
	/* Plugin Architecture */
	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'int(8)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'directory', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'status', 'type' => 'tinyint(2)', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'author', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'webpage', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'version', 'type' => 'varchar(8)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'ptype', 'type' => 'tinyint(2)', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'sequence', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'status', 'columns' => 'status');
	$data['keys'][] = array('name' => 'directory', 'columns' => 'directory');
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'plugin_config', $data, $show_output, $no_drop_items);

	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'int(10)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'plugin', 'type' => 'varchar(16)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'table', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'column', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'method', 'type' => 'varchar(16)', 'NULL' => false, 'default' => '');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'plugin', 'columns' => 'plugin');
	$data['keys'][] = array('name' => 'method', 'columns' => 'method');
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'plugin_db_changes', $data, $show_output, $no_drop_items);

	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'int(8)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'hook', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'file', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'function', 'type' => 'varchar(128)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'status', 'type' => 'int(8)', 'NULL' => false, 'default' => 0);
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'hook', 'columns' => 'hook');
	$data['keys'][] = array('name' => 'status', 'columns' => 'status');
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'plugin_hooks', $data, $show_output, $no_drop_items);

	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'int(8)', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'plugin', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'file', 'type' => 'text', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'display', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'plugin', 'columns' => 'plugin');
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'plugin_realms', $data, $show_output, $no_drop_items);

	# create new table graph_templates_xaxis
	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true, 'comment' => 'Unique Table Id');
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '', 'comment' => 'Unique Hash');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '', 'comment' => 'Name of X-Axis Preset');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['type'] = 'MyISAM';
	$data['comment'] = 'X-Axis Presets';
	plugin_upgrade_table('0.8.8', 'graph_templates_xaxis', $data, $show_output, $no_drop_items);

	# create new table graph_templates_xaxis_items
	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true, 'comment' => 'Row Id');
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '', 'comment' => 'Unique Hash');
	$data['columns'][] = array('name' => 'item_name', 'type' => 'varchar(100)', 'NULL' => false, 'comment' => 'Name of this Item');
	$data['columns'][] = array('name' => 'xaxis_id', 'type' => 'int(12)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0, 'comment' => 'Id of related X-Axis Preset');
	$data['columns'][] = array('name' => 'timespan', 'type' => 'int(12)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0, 'comment' => 'Graph Timespan that shall match this Item');
	$data['columns'][] = array('name' => 'gtm', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '', 'comment' => 'Global Grid Timespan');
	$data['columns'][] = array('name' => 'gst', 'type' => 'smallint(4)', 'unsigned' => 'unsigned', 'NULL' => false, 'comment' => 'Global Grid Timespan Steps');
	$data['columns'][] = array('name' => 'mtm', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '', 'comment' => 'Major Grid Timespan');
	$data['columns'][] = array('name' => 'mst', 'type' => 'smallint(4)', 'unsigned' => 'unsigned', 'NULL' => false, 'comment' => 'Major Grid Timespan Steps');
	$data['columns'][] = array('name' => 'ltm', 'type' => 'varchar(10)', 'NULL' => false, 'default' => '', 'comment' => 'Label Grid Timespan');
	$data['columns'][] = array('name' => 'lst', 'type' => 'smallint(4)', 'unsigned' => 'unsigned', 'NULL' => false, 'comment' => 'Label Grid Timespan Steps');
	$data['columns'][] = array('name' => 'lpr', 'type' => 'int(12)', 'unsigned' => 'unsigned', 'NULL' => false, 'comment' => 'Label Placement Relative');
	$data['columns'][] = array('name' => 'lfm', 'type' => 'varchar(100)', 'NULL' => false, 'comment' => 'Label Format');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['type'] = 'MyISAM';
	$data['comment'] = 'Items for X-Axis Presets';
	plugin_upgrade_table('0.8.8', 'graph_templates_xaxis_items', $data, $show_output, $no_drop_items);

	/* logging system */
	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'bigint(20)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'timestamp', 'type' => 'datetime', 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'facility', 'type' => 'tinyint(1)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'severity', 'type' => 'int(1)', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'poller_id', 'type' => 'smallint(5)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'device_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'data_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'username', 'type' => 'varchar(100)', 'NULL' => false, 'default' => 'system');
	$data['columns'][] = array('name' => 'source', 'type' => 'varchar(50)', 'NULL' => false, 'default' => 'localhost');
	$data['columns'][] = array('name' => 'plugin_name', 'type' => 'varchar(64)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'message', 'type' => 'text', 'NULL' => false);
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'facility', 'columns' => 'facility');
	$data['keys'][] = array('name' => 'severity', 'columns' => 'severity');
	$data['keys'][] = array('name' => 'device_id', 'columns' => 'device_id');
	$data['keys'][] = array('name' => 'data_id', 'columns' => 'data_id');
	$data['keys'][] = array('name' => 'poller_id', 'columns' => 'poller_id');
	$data['keys'][] = array('name' => 'username', 'columns' => 'username');
	$data['keys'][] = array('name' => 'timestamp', 'columns' => 'timestamp');
	$data['keys'][] = array('name' => 'plugin_name', 'columns' => 'plugin_name');
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'log', $data, $show_output, $no_drop_items);

	/* create new table VDEF */
	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'vdef', $data, $show_output, $no_drop_items);

	/* create new table VDEF_ITEMS */
	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'vdef_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'sequence', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'type', 'type' => 'tinyint(2)', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'value', 'type' => 'varchar(150)', 'NULL' => false, 'default' => '');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['keys'][] = array('name' => 'vdef_id', 'columns' => 'vdef_id');
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'vdef_items', $data, $show_output, $no_drop_items);

	/* create new table I18N_TIME_ZONES */
	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'olson_tz_string', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'posix_tz_string', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'i18n_time_zones', $data, $show_output, $no_drop_items);

	/* create new table fonts */
	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'font', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.8', 'fonts', $data, $show_output, $no_drop_items);

	/*
	 * rename host -> device for tables and columns
	 * we have some updates to those tables in this file already
	 * so please take care not to change sequence
	 */
	plugin_rename_table('0.8.8', 'host', 						'device', $show_output);
	plugin_rename_table('0.8.8', 'host_graph', 					'device_graph', $show_output);
	plugin_rename_table('0.8.8', 'host_snmp_cache', 			'device_snmp_cache', $show_output);
	plugin_rename_table('0.8.8', 'host_snmp_query', 			'device_snmp_query', $show_output);
	plugin_rename_table('0.8.8', 'host_template', 				'device_template', $show_output);
	plugin_rename_table('0.8.8', 'host_template_graph', 		'device_template_graph', $show_output);
	plugin_rename_table('0.8.8', 'host_template_snmp_query', 	'device_template_snmp_query', $show_output);

	/* change column names */
	$column = array('name' => 'device_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false);
	plugin_rename_column('0.8.8', 'device_graph', 'host_id', $column, $show_output);
	plugin_rename_column('0.8.8', 'data_local', 'host_id', $column, $show_output);
	plugin_rename_column('0.8.8', 'graph_local', 'host_id', $column, $show_output);
	plugin_rename_column('0.8.8', 'graph_tree_items', 'host_id', $column, $show_output);
	plugin_rename_column('0.8.8', 'device_snmp_cache', 'host_id', $column, $show_output);
	plugin_rename_column('0.8.8', 'device_snmp_query', 'host_id', $column, $show_output);
	plugin_rename_column('0.8.8', 'poller_item', 'host_id', $column, $show_output);
	plugin_rename_column('0.8.8', 'poller_reindex', 'host_id', $column, $show_output);

	$column = array('name' => 'device_grouping_type', 'type' => 'tinyint(3)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 1);
	plugin_rename_column('0.8.8', 'graph_tree_items', 'host_grouping_type', $column, $show_output);

	$column = array('name' => 'device_template_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false);
	plugin_rename_column('0.8.8', 'device', 'host_template_id', $column, $show_output);
	plugin_rename_column('0.8.8', 'device_template_graph', 'host_template_id', $column, $show_output);
	plugin_rename_column('0.8.8', 'device_template_snmp_query', 'host_template_id', $column, $show_output);

	$column = array('name' => 'policy_devices', 'type' => 'tinyint(1)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 1);
	plugin_rename_column('0.8.8', 'user_auth', 'policy_hosts', $column, $show_output);

	$column = array('name' => 'total_time', 'type' => 'double', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	plugin_rename_column('0.8.8', 'poller', 'ip_address', $column, $show_output);

	/*
	 * add new columns to existing tables
	 */

	/* add image storage to graph templates, data queries, and device templates */
	$columns = array();
	$columns[] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'after' => 'name');
	plugin_upgrade_columns('0.8.8', 'data_template', $columns, $show_output, $no_drop_items);

	/* add rrd_compute_rpn for data source items */
	unset($columns);
	$columns[] = array('name' => 't_rrd_compute_rpn', 'type' => 'char(2)', 'default' => NULL, 'after' => 'rrd_minimum');
	$columns[] = array('name' => 'rrd_compute_rpn', 'type' => 'varchar(150)', 'default' => '', 'after' => 't_rrd_compute_rpn');
	plugin_upgrade_columns('0.8.8', 'data_template_rrd', $columns, $show_output, $no_drop_items);

	/* add a site column to the device table */
	unset($columns);
	$columns[] = array('name' => 'site_id', 'type' => 'int(10)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'id');
	/* add the poller id for hosts to allow for multiple pollers */
	$columns[] = array('name' => 'poller_id', 'type' => 'smallint(5)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'site_id');
	$columns[] = array('name' => 'template_enabled', 'type' => 'char(2)', 'NULL' => false, 'default' => '', 'after' => 'device_template_id');
	/* implement per device threads setting for spine */
	$columns[] = array('name' => 'device_threads', 'type' => 'tinyint(2)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '1', 'after' => 'max_oids');
	$columns[] = array('name' => 'polling_time', 'type' => 'decimal(10,5)', 'NULL' => false, 'default' => '0.00000', 'after' => 'avg_time');
	plugin_upgrade_columns('0.8.8', 'device', $columns, $show_output, $no_drop_items);

	/* enable lossless reindexing in Cacti */
	unset($columns);
	$columns[] = array('name' => 'present', 'type' => 'tinyint(4)', 'NULL' => false, 'default' => '1', 'after' => 'oid');
	#, ADD INDEX present USING BTREE (present));
	plugin_upgrade_columns('0.8.8', 'device_snmp_cache', $columns, $show_output, $no_drop_items);

	/* add some fields required for devices to table device_template */
	unset($columns);
	$columns[] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'after' => 'name');
	$columns[] = array('name' => 'image', 'type' => 'varchar(64)', 'NULL' => false, 'after' => 'description');
	/* changes for template propagation */
	$columns[] = array('name' => 'override_defaults', 'type' => 'char(2)', 'NULL' => false, 'default' => '', 'after' => 'image');
	$columns[] = array('name' => 'override_permitted', 'type' => 'char(2)', 'NULL' => false, 'default' => 'on', 'after' => 'override_defaults');
	/* implement per device threads setting for spine */
	$columns[] = array('name' => 'snmp_community', 'type' => 'varchar(100)', 'default' => NULL, 'after' => 'override_permitted');
	$columns[] = array('name' => 'snmp_version', 'type' => 'tinyint(1)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '1', 'after' => 'snmp_community');
	$columns[] = array('name' => 'snmp_username', 'type' => 'varchar(50)', 'default' => NULL, 'after' => 'snmp_version');
	$columns[] = array('name' => 'snmp_password', 'type' => 'varchar(50)', 'default' => NULL, 'after' => 'snmp_username');
	$columns[] = array('name' => 'snmp_auth_protocol', 'type' => 'char(5)', 'default' => '', 'after' => 'snmp_password');
	$columns[] = array('name' => 'snmp_priv_passphrase', 'type' => 'varchar(200)', 'default' => '', 'after' => 'snmp_auth_protocol');
	$columns[] = array('name' => 'snmp_priv_protocol', 'type' => 'char(6)', 'default' => '', 'after' => 'snmp_priv_passphrase');
	$columns[] = array('name' => 'snmp_context', 'type' => 'varchar(64)', 'default' => '', 'after' => 'snmp_priv_protocol');
	$columns[] = array('name' => 'snmp_port', 'type' => 'mediumint(5)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '161', 'after' => 'snmp_context');
	$columns[] = array('name' => 'snmp_timeout', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '500', 'after' => 'snmp_port');
	$columns[] = array('name' => 'availability_method', 'type' => 'smallint(5)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '1', 'after' => 'snmp_timeout');
	$columns[] = array('name' => 'ping_method', 'type' => 'smallint(5)', 'unsigned' => 'unsigned', 'default' => '0', 'after' => 'availability_method');
	$columns[] = array('name' => 'ping_port', 'type' => 'int(12)', 'unsigned' => 'unsigned', 'default' => '0', 'after' => 'ping_method');
	$columns[] = array('name' => 'ping_timeout', 'type' => 'int(12)', 'unsigned' => 'unsigned', 'default' => '500', 'after' => 'ping_port');
	$columns[] = array('name' => 'ping_retries', 'type' => 'int(12)', 'unsigned' => 'unsigned', 'default' => '2', 'after' => 'ping_timeout');
	$columns[] = array('name' => 'max_oids', 'type' => 'int(12)', 'unsigned' => 'unsigned', 'default' => '10', 'after' => 'ping_retries');
	$columns[] = array('name' => 'device_threads', 'type' => 'tinyint(2)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '1', 'after' => 'max_oids');
	plugin_upgrade_columns('0.8.8', 'device_template', $columns, $show_output, $no_drop_items);

	/* add reindexing to device_template_snmp_query */
	unset($columns);
	$columns[] = array('name' => 'reindex_method', 'type' => 'tinyint(3)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'snmp_query_id');
	plugin_upgrade_columns('0.8.8', 'device_template_snmp_query', $columns, $show_output, $no_drop_items);

	unset($columns);
	$columns[] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'after' => 'name');
	$columns[] = array('name' => 'image', 'type' => 'varchar(64)', 'NULL' => false, 'after' => 'description');
	plugin_upgrade_columns('0.8.8', 'graph_templates', $columns, $show_output, $no_drop_items);

	/* new columns for graph_templates_graph */
	unset($columns);
	$columns[] = array('name' => 't_right_axis', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'right_axis', 'type' => 'varchar(20)', 'default' => NULL);
	$columns[] = array('name' => 't_right_axis_label', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'right_axis_label', 'type' => 'varchar(200)', 'default' => NULL);
	$columns[] = array('name' => 't_right_axis_format', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'right_axis_format', 'type' => 'mediumint(8)', 'default' => NULL);
	$columns[] = array('name' => 't_only_graph', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'only_graph', 'type' => 'char(2)', 'default' => NULL);
	$columns[] = array('name' => 't_full_size_mode', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'full_size_mode', 'type' => 'char(2)', 'default' => NULL);
	$columns[] = array('name' => 't_no_gridfit', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'no_gridfit', 'type' => 'char(2)', 'default' => NULL);
	$columns[] = array('name' => 't_x_grid', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'x_grid', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0');
	$columns[] = array('name' => 't_unit_length', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'unit_length', 'type' => 'varchar(10)', 'default' => NULL);
	$columns[] = array('name' => 't_colortag_back', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'colortag_back', 'type' => 'char(8)', 'default' => NULL);
	$columns[] = array('name' => 't_colortag_canvas', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'colortag_canvas', 'type' => 'char(8)', 'default' => NULL);
	$columns[] = array('name' => 't_colortag_shadea', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'colortag_shadea', 'type' => 'char(8)', 'default' => NULL);
	$columns[] = array('name' => 't_colortag_shadeb', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'colortag_shadeb', 'type' => 'char(8)', 'default' => NULL);
	$columns[] = array('name' => 't_colortag_grid', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'colortag_grid', 'type' => 'char(8)', 'default' => NULL);
	$columns[] = array('name' => 't_colortag_mgrid', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'colortag_mgrid', 'type' => 'char(8)', 'default' => NULL);
	$columns[] = array('name' => 't_colortag_font', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'colortag_font', 'type' => 'char(8)', 'default' => NULL);
	$columns[] = array('name' => 't_colortag_axis', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'colortag_axis', 'type' => 'char(8)', 'default' => NULL);
	$columns[] = array('name' => 't_colortag_frame', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'colortag_frame', 'type' => 'char(8)', 'default' => NULL);
	$columns[] = array('name' => 't_colortag_arrow', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'colortag_arrow', 'type' => 'char(8)', 'default' => NULL);
	$columns[] = array('name' => 't_font_render_mode', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'font_render_mode', 'type' => 'varchar(10)', 'default' => NULL);
	$columns[] = array('name' => 't_font_smoothing_threshold', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'font_smoothing_threshold', 'type' => 'int(8)', 'default' => NULL);
	$columns[] = array('name' => 't_graph_render_mode', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'graph_render_mode', 'type' => 'varchar(10)', 'default' => NULL);
	$columns[] = array('name' => 't_pango_markup', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'pango_markup', 'type' => 'char(2)', 'default' => NULL);
	$columns[] = array('name' => 't_interlaced', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'interlaced', 'type' => 'char(2)', 'default' => NULL);
	$columns[] = array('name' => 't_tab_width', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'tab_width', 'type' => 'mediumint(4)', 'default' => NULL);
	$columns[] = array('name' => 't_watermark', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'watermark', 'type' => 'varchar(255)', 'default' => NULL);
	$columns[] = array('name' => 't_dynamic_labels', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'dynamic_labels', 'type' => 'char(2)', 'default' => NULL);
	$columns[] = array('name' => 't_force_rules_legend', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'force_rules_legend', 'type' => 'char(2)', 'default' => NULL);
	$columns[] = array('name' => 't_legend_position', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'legend_position', 'type' => 'varchar(10)', 'default' => NULL);
	$columns[] = array('name' => 't_legend_direction', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'legend_direction', 'type' => 'varchar(10)', 'default' => NULL);
	$columns[] = array('name' => 't_grid_dash', 'type' => 'char(2)', 'default' => '0');
	$columns[] = array('name' => 'grid_dash', 'type' => 'varchar(10)', 'default' => NULL);
	$columns[] = array('name' => 't_border', 'type' => 'char(2)', 'default' => '0');
	/* add --alt-y-grid as an option */
	$columns[] = array('name' => 't_alt_y_grid', 'type' => 'char(2)', 'default' => '0', 'after' => 'auto_scale_rigid');
	$columns[] = array('name' => 'alt_y_grid', 'type' => 'char(2)', 'default' => '', 'after' => 't_alt_y_grid');
	/* increase size for upper/lower limit for use with |query_*| variables */
	$columns[] = array('name' => 'upper_limit', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '0');
	$columns[] = array('name' => 'lower_limit', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '0');
	$columns[] = array('name' => 'border', 'type' => 'char(2)', 'default' => NULL);
	plugin_upgrade_columns('0.8.8', 'graph_templates_graph', $columns, $show_output, $no_drop_items);

	/* changes to insert VDEF into table graph_templates_item just behind CDEF */
	unset($columns);
	$columns[] = array('name' => 'vdef_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0, 'after' => 'cdef_id');
	# split LINEx into LINE and a line_width of x
	$columns[] = array('name' => 'line_width', 'type' => 'decimal(4,2)', 'default' => 0, 'after' => 'graph_type_id');
	# add DASHES and DASH-OFFSET
	$columns[] = array('name' => 'dashes', 'type' => 'varchar(20)', 'default' => NULL, 'after' => 'line_width');
	$columns[] = array('name' => 'dash_offset', 'type' => 'mediumint(4)', 'default' => NULL, 'after' => 'dashes');
	# add TEXTALIGN
	$columns[] = array('name' => 'textalign', 'type' => 'varchar(10)', 'default' => NULL, 'after' => 'consolidation_function_id');
	# add SHIFT
	$columns[] = array('name' => 'shift', 'type' => 'char(2)', 'default' => NULL, 'after' => 'vdef_id');
	plugin_upgrade_columns('0.8.8', 'graph_templates_item', $columns, $show_output, $no_drop_items);

	/* make tree's a per user object.  System tree's have a user_id of 0 */
	unset($columns);
	$columns[] = array('name' => 'user_id', 'type' => 'int(10)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'id');
	plugin_upgrade_columns('0.8.8', 'graph_tree', $columns, $show_output, $no_drop_items);

	/* upgrade to the graph tree items */
	unset($columns);
	$columns[] = array('name' => 'parent_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'id');
	$columns[] = array('name' => 'site_id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'title');
	plugin_upgrade_columns('0.8.8', 'graph_tree_items', $columns, $show_output, $no_drop_items);

	/* add the poller id for devices to allow for multiple pollers */
	unset($columns);
	$columns[] = array('name' => 'disabled', 'type' => 'char(2)', 'default' => '', 'after' => 'id');
	$columns[] = array('name' => 'description', 'type' => 'varchar(45)', 'NULL' => false, 'default' => '', 'after' => 'disabled');
	$columns[] = array('name' => 'snmp', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'total_time');
	$columns[] = array('name' => 'script', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'snmp');
	$columns[] = array('name' => 'server', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'script');
	plugin_upgrade_columns('0.8.8', 'poller', $columns, $show_output, $no_drop_items);

	unset($columns);
	$columns[] = array('name' => 'present', 'type' => 'tinyint(4)', 'NULL' => false, 'default' => '1', 'after' => 'action');
	plugin_upgrade_columns('0.8.8', 'poller_item', $columns, $show_output, $no_drop_items);

	/* add the poller id for poller_output to allow for multiple pollers */
	unset($columns);
	$columns[] = array('name' => 'poller_id', 'type' => 'smallint(5)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'time');
	plugin_upgrade_columns('0.8.8', 'poller_output', $columns, $show_output, $no_drop_items);

	unset($columns);
	$columns[] = array('name' => 'present', 'type' => 'tinyint(4)', 'NULL' => false, 'default' => '1', 'after' => 'action');
	#, ADD INDEX present USING BTREE (present));
	plugin_upgrade_columns('0.8.8', 'poller_reindex', $columns, $show_output, $no_drop_items);

	unset($columns);
	$columns[] = array('name' => 'image', 'type' => 'varchar(64)', 'NULL' => false, 'after' => 'description');
	plugin_upgrade_columns('0.8.8', 'snmp_query', $columns, $show_output, $no_drop_items);


	/*
	 * install ALL required keys ($drop_items=true)
	 * Take ALL keys from cacti.sql and feed them to the plugin_upgrade_keys
	 * This procedure will take care of
	 * - new keys
	 * - keys that require a change (drop,add sequence)
	 * - keys to be removed
	 * This way, we avoid a difference between cacti.sql and upgraded keys
	 */
	$key = array();
	$key[] = array('name' => 'PRIMARY', 'columns' => 'data_input_field_id,data_template_data_id', 'primary' => true);
	$key[] = array('name' => 't_value', 'columns' => 't_value');
	$key[] = array('name' => 'data_template_data_id', 'columns' => 'data_template_data_id');
	plugin_upgrade_keys('0.8.8', 'data_input_data', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'device_id', 'columns' => 'device_id');
	$key[] = array('name' => 'device_id_snmp_query_id_snmp_index', 'columns' => 'device_id,snmp_query_id,snmp_index');
	plugin_upgrade_keys('0.8.8', 'data_local', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'local_data_id', 'columns' => 'local_data_id');
	$key[] = array('name' => 'data_template_id', 'columns' => 'data_template_id');
	$key[] = array('name' => 'data_source_path', 'columns' => 'data_source_path');
	plugin_upgrade_keys('0.8.8', 'data_template_data', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'local_data_id', 'columns' => 'local_data_id');
	$key[] = array('name' => 'data_template_id', 'columns' => 'data_template_id');
	$key[] = array('name' => 'local_data_template_rrd_id', 'columns' => 'local_data_template_rrd_id');
	$key[] = array('name' => 'local_data_id_data_source_name', 'columns' => 'local_data_id,data_source_name');
	plugin_upgrade_keys('0.8.8', 'data_template_rrd', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'disabled', 'columns' => 'disabled');
	$key[] = array('name' => 'poller_id', 'columns' => 'poller_id');
	$key[] = array('name' => 'site_id', 'columns' => 'site_id');
	plugin_upgrade_keys('0.8.8', 'device', $key, true);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'device_id,snmp_query_id,field_name,snmp_index', 'primary' => true);
	$key[] = array('name' => 'device_id', 'columns' => 'device_id,field_name');
	$key[] = array('name' => 'snmp_index', 'columns' => 'snmp_index');
	$key[] = array('name' => 'field_name', 'columns' => 'field_name');
	$key[] = array('name' => 'field_value', 'columns' => 'field_value');
	$key[] = array('name' => 'snmp_query_id', 'columns' => 'snmp_query_id');
	$key[] = array('name' => 'device_id_snmp_query_id', 'columns' => 'device_id,snmp_query_id');
	$key[] = array('name' => 'device_id_snmp_query_id_snmp_index', 'columns' => 'device_id,snmp_query_id,snmp_index');
	$key[] = array('name' => 'present', 'columns' => 'present', 'type' => 'BTREE');
	plugin_upgrade_keys('0.8.8', 'device_snmp_cache', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'device_id,snmp_query_id', 'primary' => true);
	$key[] = array('name' => 'device_id', 'columns' => 'device_id');
	plugin_upgrade_keys('0.8.8', 'device_snmp_query', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'device_template_id,graph_template_id', 'primary' => true);
	$key[] = array('name' => 'device_template_id', 'columns' => 'device_template_id');
	plugin_upgrade_keys('0.8.8', 'device_template_graph', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'device_template_id,snmp_query_id', 'primary' => true);
	$key[] = array('name' => 'device_template_id', 'columns' => 'device_template_id');
	plugin_upgrade_keys('0.8.8', 'device_template_snmp_query', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'device_id', 'columns' => 'device_id');
	$key[] = array('name' => 'graph_template_id', 'columns' => 'graph_template_id');
	$key[] = array('name' => 'snmp_query_id', 'columns' => 'snmp_query_id');
	$key[] = array('name' => 'snmp_index', 'columns' => 'snmp_index');
	plugin_upgrade_keys('0.8.8', 'graph_local', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'graph_template_id', 'columns' => 'graph_template_id');
	$key[] = array('name' => 'local_graph_id', 'columns' => 'local_graph_id');
	$key[] = array('name' => 'task_item_id', 'columns' => 'task_item_id');
	$key[] = array('name' => 'graph_template_id_local_graph_id', 'columns' => 'graph_template_id,local_graph_id');
	$key[] = array('name' => 'local_graph_template_item_id', 'columns' => 'local_graph_template_item_id');
	$key[] = array('name' => 'local_graph_id_sequence', 'columns' => 'local_graph_id,sequence');
	plugin_upgrade_keys('0.8.8', 'graph_templates_item', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'user_id', 'columns' => 'user_id');
	plugin_upgrade_keys('0.8.8', 'graph_tree', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'graph_tree_id', 'columns' => 'graph_tree_id');
	$key[] = array('name' => 'device_id', 'columns' => 'device_id');
	$key[] = array('name' => 'local_graph_id', 'columns' => 'local_graph_id');
	$key[] = array('name' => 'order_key', 'columns' => 'order_key');
	plugin_upgrade_keys('0.8.8', 'graph_tree_items', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'local_data_id,rrd_name', 'primary' => true);
	$key[] = array('name' => 'local_data_id', 'columns' => 'local_data_id');
	$key[] = array('name' => 'device_id', 'columns' => 'device_id');
	$key[] = array('name' => 'rrd_next_step', 'columns' => 'rrd_next_step');
	$key[] = array('name' => 'action', 'columns' => 'action');
	$key[] = array('name' => 'local_data_id_rrd_path', 'columns' => 'local_data_id,rrd_path');
	$key[] = array('name' => 'device_id_rrd_next_step', 'columns' => 'device_id,rrd_next_step');
	$key[] = array('name' => 'device_id_snmp_port', 'columns' => 'device_id,snmp_port');
	plugin_upgrade_keys('0.8.8', 'poller_item', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'local_data_id,rrd_name,time', 'primary' => true);
	$key[] = array('name' => 'poller_id', 'columns' => 'poller_id');
	plugin_upgrade_keys('0.8.8', 'poller_output', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'username,user_id,time', 'primary' => true);
	$key[] = array('name' => 'username', 'columns' => 'username');
	$key[] = array('name' => 'user_id', 'columns' => 'user_id');
	plugin_upgrade_keys('0.8.8', 'user_log', $key, $show_output, $drop_items);

	/*
	 * now it's time to change values
	 *
	 * first, tackle rename for host -> device
	 * table value updates using REPLACE
	 */
	db_install_execute("0.8.8", "UPDATE data_template_data SET name=REPLACE(name,'|host_','|device_') WHERE name like '%%|host_%%'");
	db_install_execute("0.8.8", "UPDATE graph_templates_graph SET title=REPLACE(title,'|host_','|device_') WHERE title like '%%|host_%%'");
	db_install_execute("0.8.8", "UPDATE graph_templates_graph SET upper_limit=REPLACE(upper_limit,'|host_','|device_') WHERE upper_limit like '%%|host_%%'");
	db_install_execute("0.8.8", "UPDATE graph_templates_graph SET lower_limit=REPLACE(lower_limit,'|host_','|device_') WHERE lower_limit like '%%|host_%%'");
	db_install_execute("0.8.8", "UPDATE graph_templates_graph SET vertical_label=REPLACE(vertical_label,'|host_','|device_') WHERE vertical_label like '%%|host_%%'");
	db_install_execute("0.8.8", "UPDATE snmp_query_graph_rrd_sv SET `text`=REPLACE(`text`,'|host_','|device_') WHERE `text` like '%%|host_%%'");
	db_install_execute("0.8.8", "UPDATE snmp_query_graph_sv SET `text`=REPLACE(`text`,'|host_','|device_') WHERE `text` like '%%|host_%%'");
	db_install_execute("0.8.8", "UPDATE device SET poller_id=1 WHERE poller_id=0");

	/*
	 * now update current entries of table device_template
	 * make sure to use current global default settings in order not to change
	 * current behaviour when creating new devices from those templates
	 */
	$snmp_community	= read_config_option("snmp_community", true);
	$snmp_version = read_config_option("snmp_ver", true);
	$snmp_username = read_config_option("snmp_username", true);
	$snmp_password = read_config_option("snmp_password", true);
	$snmp_auth_protocol = read_config_option("snmp_auth_protocol", true);
	$snmp_priv_passphrase = read_config_option("snmp_priv_passphrase", true);
	$snmp_priv_protocol = read_config_option("snmp_priv_protocol", true);
	$snmp_context = read_config_option("snmp_context", true);
	$snmp_port = read_config_option("snmp_port", true);
	$snmp_timeout = read_config_option("snmp_timeout", true);
	$availability_method = read_config_option("availability_method", true);
	$ping_method = read_config_option("ping_method", true);
	$ping_port = read_config_option("ping_port", true);
	$ping_timeout = read_config_option("ping_timeout", true);
	$ping_retries = read_config_option("ping_retries", true);
	$max_oids = read_config_option("max_get_size", true);

	db_install_execute("0.8.8", "UPDATE `device_template` " .
			"SET  `snmp_community` = '" . $snmp_community . "' ," .
				" `snmp_version` = $snmp_version," .
				" `snmp_username` = '" . $snmp_username . "' ," .
				" `snmp_password` = '" . $snmp_password . "' ," .
				" `snmp_auth_protocol` = '" . $snmp_auth_protocol . "' ," .
				" `snmp_priv_passphrase` = '" . $snmp_priv_passphrase . "' ," .
				" `snmp_priv_protocol` = '" . $snmp_priv_protocol . "' ," .
				" `snmp_context` = '" . $snmp_context . "' ," .
				" `snmp_port` = $snmp_port," .
				" `snmp_timeout` = $snmp_timeout," .
				" `availability_method` = $availability_method," .
				" `ping_method` = $ping_method," .
				" `ping_port` = $ping_port," .
				" `ping_timeout` = $ping_timeout," .
				" `ping_retries` = $ping_retries," .
				" `max_oids` = $max_oids");

	db_install_execute("0.8.8", "UPDATE `device_template_snmp_query` SET `reindex_method` = '1'");

	/* plugins */
	/* get all plugins, pre088 code guarantees that SYSTEM plugins come first */
	$plugins = db_fetch_assoc("SELECT * FROM plugin_config ORDER BY id ASC");
	if (sizeof($plugins)) {
		$i = 0;
		foreach($plugins AS $item) {
			if (in_array($item["directory"], $plugins_system)) {
				$ptype = PLUGIN_TYPE_SYSTEM;
			} else {
				$ptype = PLUGIN_TYPE_GENERAL;
			}
			db_install_execute("0.8.8", "UPDATE `plugin_config` SET sequence=" . $i++ . ", ptype= " . $ptype . " WHERE id=" . $item["id"]);
		}
	}

	/* we don't need the internal hooks anymore; they're now part of the code base */
	db_install_execute("0.8.8", "DELETE FROM `plugin_hooks` WHERE `plugin_hooks`.`name` = 'internal'");
	db_install_execute("0.8.8", "REPLACE INTO `plugin_realms` VALUES (1, 'internal', 'plugins.php', 'Plugin Management')");

	/* wrong lower limit for generic OID graph template */
	db_install_execute("0.8.8", "UPDATE graph_templates_graph SET lower_limit='0', vertical_label='' WHERE id=47");

	/* Add SNMPv3 Context to SNMP Input Methods */
	/* first we must see if the user was smart enough to add it themselves */
	$context1 = db_fetch_row("SELECT id FROM data_input_fields WHERE data_input_id=1 AND data_name='snmp_context' AND input_output='in' AND type_code='snmp_context'");
	if ($context1 > 0) {
		# nop
	} else {
		db_install_execute("0.8.8", "INSERT INTO data_input_fields VALUES (DEFAULT, '8e42450d52c46ebe76a57d7e51321d36',1,'SNMP Context (v3)','snmp_context','in','',0,'snmp_context','','')");
	}
	$context2 = db_fetch_row("SELECT id FROM data_input_fields WHERE data_input_id=2 AND data_name='snmp_context' AND input_output='in' AND type_code='snmp_context'");
	if ($context2 > 0) {
		# nop
	} else {
		db_install_execute("0.8.8", "INSERT INTO data_input_fields VALUES (DEFAULT, 'b5ce68ca4e9e36d221459758ede01484',2,'SNMP Context (v3)','snmp_context','in','',0,'snmp_context','','')");
	}

	db_install_execute("0.8.8", "UPDATE data_input_fields SET name='SNMP Authentication Protocol (v3)' WHERE name='SNMP Authenticaion Protocol (v3)'");

	db_install_execute("0.8.8", "REPLACE INTO `graph_templates_xaxis` VALUES(1, 'a09c5cab07a6e10face1710cec45e82f', 'Default')");

	db_install_execute("0.8.8", "REPLACE INTO `graph_templates_xaxis_items` VALUES(1, '60c2066a1c45fab021d32fe72cbf4f49', 'Day', 1, 86400, 'HOUR', 4, 'HOUR', 2, 'HOUR', 2, 23200, '%H')");
	db_install_execute("0.8.8", "REPLACE INTO `graph_templates_xaxis_items` VALUES(2, 'd867f8fc2730af212d0fd6708385cf89', 'Week', 1, 604800, 'DAY', 1, 'DAY', 1, 'DAY', 1, 259200, '%d')");
	db_install_execute("0.8.8", "REPLACE INTO `graph_templates_xaxis_items` VALUES(3, '06304a1840da88f3e0438ac147219003', 'Month', 1, 2678400, 'WEEK', 1, 'WEEK', 1, 'WEEK', 1, 1296000, '%W')");
	db_install_execute("0.8.8", "REPLACE INTO `graph_templates_xaxis_items` VALUES(4, '33ac10e60fd855e74736bee43bda4134', 'Year', 1, 31622400, 'MONTH', 2, 'MONTH', 1, 'MONTH', 2, 15811200, '%m')");

	/* get all nodes whose parent_id is not 0 */
	$tree_items = db_fetch_assoc("SELECT * FROM graph_tree_items WHERE order_key NOT LIKE '___000%'");
	if (sizeof($tree_items)) {
	foreach($tree_items AS $item) {
		$translated_key = rtrim($item["order_key"], "0\r\n");
		$missing_len    = strlen($translated_key) % CHARS_PER_TIER;
		if ($missing_len > 0) {
			$translated_key .= substr("000", 0, $missing_len);
		}
		$parent_key_len = strlen($translated_key) - CHARS_PER_TIER;
		$parent_key     = substr($translated_key, 0, $parent_key_len);
		$parent_id      = db_fetch_cell("SELECT id FROM graph_tree_items WHERE graph_tree_id=" . $item["graph_tree_id"] . " AND order_key LIKE '" . $parent_key . "000%'");
		if ($parent_id != "") {
			db_execute("UPDATE graph_tree_items SET parent_id=$parent_id WHERE id=" . $item["id"]);
		}else{
			cacti_log("Some error occurred processing children", false);
		}
	}
	}

	/* insert the default poller into the database */
	db_install_execute("0.8.8", "REPLACE INTO `poller` VALUES (1,'','Main Poller','localhost',0,0,0,0,'0000-00-00 00:00:00');");

	/* update all devices to use poller 1, or the main poller */
	db_install_execute("0.8.8", "UPDATE device SET poller_id=1 WHERE poller_id=0");

	/* update the poller_items table to set the default poller_id */
	db_install_execute("0.8.8", "UPDATE poller_item SET poller_id=1 WHERE poller_id=0");

	/* fill table VDEF */
	db_install_execute("0.8.8", "REPLACE INTO `vdef` VALUES (1, 'e06ed529238448773038601afb3cf278', 'Maximum');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef` VALUES (2, 'e4872dda82092393d6459c831a50dc3b', 'Minimum');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef` VALUES (3, '5ce1061a46bb62f36840c80412d2e629', 'Average');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef` VALUES (4, '06bd3cbe802da6a0745ea5ba93af554a', 'Last (Current)');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef` VALUES (5, '631c1b9086f3979d6dcf5c7a6946f104', 'First');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef` VALUES (6, '6b5335843630b66f858ce6b7c61fc493', 'Total: Current Data Source');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef` VALUES (7, 'c80d12b0f030af3574da68b28826cd39', '95th Percentage: Current Data Source');");



	/* fill table VDEF */
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (1, '88d33bf9271ac2bdf490cf1784a342c1', 1, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (2, 'a307afab0c9b1779580039e3f7c4f6e5', 1, 2, 1, '1');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (3, '0945a96068bb57c80bfbd726cf1afa02', 2, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (4, '95a8df2eac60a89e8a8ca3ea3d019c44', 2, 2, 1, '2');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (5, 'cc2e1c47ec0b4f02eb13708cf6dac585', 3, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (6, 'a2fd796335b87d9ba54af6a855689507', 3, 2, 1, '3');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (7, 'a1d7974ee6018083a2053e0d0f7cb901', 4, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (8, '26fccba1c215439616bc1b83637ae7f3', 4, 2, 1, '5');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (9, 'a8993b265f4c5398f4a47c44b5b37a07', 5, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (10, '5a380d469d611719057c3695ce1e4eee', 5, 2, 1, '6');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (11, '65cfe546b17175fad41fcca98c057feb', 6, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (12, 'f330b5633c3517d7c62762cef091cc9e', 6, 2, 1, '7');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (13, 'f1bf2ecf54ca0565cf39c9c3f7e5394b', 7, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (14, '11a26f18feba3919be3af426670cba95', 7, 2, 6, '95');");
	db_install_execute("0.8.8", "REPLACE INTO `vdef_items` VALUES (15, 'e7ae90275bc1efada07c19ca3472d9db', 7, 3, 1, '8');");

	# graph_templates_items: set line_width of x
	db_install_execute("0.8.8", "UPDATE graph_templates_item SET `line_width`=1 WHERE `graph_type_id`=4"); # LINE1
	db_install_execute("0.8.8", "UPDATE graph_templates_item SET `line_width`=2 WHERE `graph_type_id`=5"); # LINE2
	db_install_execute("0.8.8", "UPDATE graph_templates_item SET `line_width`=3 WHERE `graph_type_id`=6"); # LINE3

	/* new cdef's for background colorization */
	$cdef_id = 	db_fetch_cell("SELECT id FROM cdef WHERE hash='2544acefc5fef30366c71336166ed141';");
	if ($cdef_id == 0) {
		db_install_execute("0.8.8", "REPLACE INTO `cdef` VALUES(DEFAULT, '2544acefc5fef30366c71336166ed141', 'Time: Daytime')");
		$cdef_id = 	db_fetch_cell("SELECT id FROM cdef WHERE hash='2544acefc5fef30366c71336166ed141';");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'ac0dea239ef3279c9b5ee04990fd4ec0', $cdef_id, 1, 1, '42')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '12f2bd71d5cbc078b9712c54d21c4f59', $cdef_id, 2, 6, '86400')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'bf35d7e5ae6df56398ea0f34a77311fc', $cdef_id, 3, 2, '5')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '31a9b3ff3b402f0446e6f6454b4d47c2', $cdef_id, 4, 4, 'TIME_SHIFT_START')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '996b718fc70353deb676e9037af9eadd', $cdef_id, 5, 1, '23')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '9c48bd2133670fd5158264ac25df6bb6', $cdef_id, 6, 1, '42')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '50c205e8bd5bb19b7fbee0ec2dee44cb', $cdef_id, 7, 6, '86400')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '14ee4ad2c7f91ab6406e1ecec6f4bcdc', $cdef_id, 8, 2, '5')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '38023f18060f2586e3504bbdd2634cc3', $cdef_id, 9, 4, 'TIME_SHIFT_END')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '1dbfee1b96a11492e58128ee8de93925', $cdef_id, 10, 1, '21')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '6979b0680858c8d153530d9390f6a4e9', $cdef_id, 11, 1, '37')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'f9d37c6480c3555c9d6d2d8910ef2da7', $cdef_id, 12, 1, '36')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '6c2604fd53780532c93c16d82c0337fd', $cdef_id, 13, 4, 'CURRENT_DATA_SOURCE')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'c2652379ba1c6523dc036e0a312536c4', $cdef_id, 14, 2, '3')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '63bf07a965b64fc41faa4bf01ae8a39d', $cdef_id, 15, 1, '29')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '2a9dea57a4f5d12cd0e2e66a31186a35', $cdef_id, 16, 1, '36')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '014839ebf8261c501d1da6c2c5217a0c', $cdef_id, 17, 4, 'CURRENT_DATA_SOURCE')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '01c946b79d68fad871e6e9437cba924f', $cdef_id, 18, 2, '3')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '4d0879e3c65c5af4e35d41a1631dcbe5', $cdef_id, 19, 1, '29')");
	}

	$cdef_id = 	db_fetch_cell("SELECT id FROM cdef WHERE hash='8bd388f585b624a7bbad97101a2b7ee9';");
	if ($cdef_id == 0) {
		db_install_execute("0.8.8", "REPLACE INTO `cdef` VALUES(DEFAULT, '8bd388f585b624a7bbad97101a2b7ee9', 'Time: Nighttime')");
		$cdef_id = 	db_fetch_cell("SELECT id FROM cdef WHERE hash='8bd388f585b624a7bbad97101a2b7ee9';");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '1c9452055499efaddded29c74ee21880', $cdef_id, 1, 1, '42')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '36af4d7c5a8acf09bda1a3a5f1409979', $cdef_id, 2, 6, '86400')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '74cf8897d5ada9da271c64e82a1384ac', $cdef_id, 3, 2, '5')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '053c5efacd6787b6e41ed109043ba256', $cdef_id, 4, 4, 'TIME_SHIFT_START')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'da39b6410ab37833842511f46182717d', $cdef_id, 5, 1, '21')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '652afbee7025a256b8dc3c49e75b27fc', $cdef_id, 6, 1, '37')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '26a63ba997e1f904c71bb7c9eb5e76e5', $cdef_id, 7, 1, '42')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '6f83ed61e0743176f03dd790f31521ea', $cdef_id, 8, 6, '86400')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '6b49d9dc72576a7ada160f0befc77c85', $cdef_id, 9, 2, '5')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '22f0dd9a5e0e189424ea29fe1383e29d', $cdef_id, 10, 4, 'TIME_SHIFT_END')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'd3f3a319e8fcfac10bd06fb247d236af', $cdef_id, 11, 1, '23')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '1cf7208bfa84c61f788f327500b712a6', $cdef_id, 12, 1, '37')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'c29025779a287d2f7b946e9ffbba3c24', $cdef_id, 13, 1, '36')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '690852ea78bf45796ef21947e27528be', $cdef_id, 14, 4, 'CURRENT_DATA_SOURCE')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '09061dcd9762280ffd3994c8274b19f8', $cdef_id, 15, 2, '3')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '60be0afe23bef9fdb7e6cabd9067eb32', $cdef_id, 16, 1, '29')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'f4a6609839d199ecb12c2f05b5d3a7b6', $cdef_id, 17, 1, '29')");
	}

	$cdef_id = 	db_fetch_cell("SELECT id FROM cdef WHERE hash='b4ef0a1c5e471dc6bae6a13ace5c57e7';");
	if ($cdef_id == 0) {
		db_install_execute("0.8.8", "REPLACE INTO `cdef` VALUES(DEFAULT, 'b4ef0a1c5e471dc6bae6a13ace5c57e7', 'Time: Weekend')");
		$cdef_id = 	db_fetch_cell("SELECT id FROM cdef WHERE hash='b4ef0a1c5e471dc6bae6a13ace5c57e7';");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'd4f93d57657e6c3ae2053a4a760a0c7b', $cdef_id, 1, 1, '42')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '00a793341980c41728c6ee665718001c', $cdef_id, 2, 6, '604800')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '0a7eaf7192e5e44a425f5e8986850190', $cdef_id, 3, 2, '5')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'ceb07e26bf15c561b12004c5e32d7f1f', $cdef_id, 4, 6, '172800')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '3a3bfafebd173fdbbd8c07d2e2dd661f', $cdef_id, 5, 1, '23')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '4c080ecaaa7260886ea148869d4d0456', $cdef_id, 6, 1, '42')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'bd57afcd9879e29e29bb796ba8d6188d', $cdef_id, 7, 6, '604800')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'cd14cd9adfbae04973a75b90880e7d64', $cdef_id, 8, 2, '5')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '3bed46dd43a64d54acc4f0723cff0bc7', $cdef_id, 9, 6, '345600')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '6fa62ee12bb8ba8936e39ea4303f92fd', $cdef_id, 10, 1, '21')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'f26848c08c2fb385126f90107494ce64', $cdef_id, 11, 1, '37')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'b8a5dde83327cac6705cdaa58300153b', $cdef_id, 12, 1, '36')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'f6aa118b35e269101ca3049cc4a323db', $cdef_id, 13, 4, 'CURRENT_DATA_SOURCE')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '967beb159b1ea744460ff3439ab205eb', $cdef_id, 14, 2, '3')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'f30028a71a1f4333703c70f8e499b03a', $cdef_id, 15, 1, '29')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '6888be191630a0964fdb9eaeb01cecaf', $cdef_id, 16, 1, '36')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '77c456204e43a9053c68b51750d5df75', $cdef_id, 17, 4, 'CURRENT_DATA_SOURCE')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'ce271b7a9809646a1fe4a7cd286fd98a', $cdef_id, 18, 2, '3')");
		db_install_execute("0.8.8", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '8bcd193850b37953ffe940fdf2a26aa6', $cdef_id, 19, 1, '29')");
	}

	/* adjust GPRINTs
	 * here's the deal
	 * All GPRINTs following an CF=AVERAGE/MAX/MIN/LAST non-GPRINT have to be changed to CF=AVERAGE/MAX/MIN/LAST
	 * (this is called the "parent" CF)
	 * the graph_type_id has to be changed as well according to:
	 * old CF | old GPRINT | new CF | new GPRINT
	 *   AVG  |   GPRINT   | parent | GRPINT_AVG
	 *   MAX  |   GPRINT   | parent | GRPINT_MAX
	 *   MIN  |   GPRINT   | parent | GRPINT_MIN
	 *   LAST |   GPRINT   | parent | GRPINT_LAST
	 */
	# first, handle templated graphs
	$graph_templates = db_fetch_assoc("SELECT id FROM graph_templates ORDER BY id ASC");
	if (sizeof($graph_templates)) {
		foreach ($graph_templates as $template) {
			$graph_template_items = db_fetch_assoc("SELECT * " .
								"FROM graph_templates_item " .
								"WHERE local_graph_id = 0 " .
								"AND graph_template_id = " . $template["id"] .  " " .
								"ORDER BY graph_template_id ASC, sequence ASC");
			update_pre_088_graph_items($graph_template_items);
		}
	}
	# now handle non-templated graphs
	$graphs = db_fetch_assoc("SELECT id FROM graph_local WHERE graph_template_id = 0 ORDER BY id ASC");
	if (sizeof($graphs)) {
		foreach ($graphs as $graph) {
			$graph_items = db_fetch_assoc("SELECT * " .
								"FROM graph_templates_item " .
								"WHERE local_graph_id = " . $graph["id"] . " " .
								"AND graph_template_id = 0 " .
								"ORDER BY local_graph_id ASC, sequence ASC");
			update_pre_088_graph_items($graph_items);
		}
	}

	/* change the name and description for data input method "Unix - Get Load Average" from 10min to 15min */
	$dim_id = db_fetch_cell("SELECT id FROM `data_input` WHERE input_string LIKE '%%scripts/loadavg_multi.pl%%' LIMIT 0,1");
	$field_id = db_fetch_cell("SELECT id FROM `data_input_fields` WHERE data_name LIKE '%%10min%%' AND data_input_id =" . $dim_id . " LIMIT 0,1");
	if ($field_id > 0) {
		db_install_execute("0.8.8", "UPDATE data_input_fields SET `name`='15 Minute Average', `data_name`='15min' WHERE `id`=" . $field_id);
	}

	/* custom font handling has changed
	 * all font options may stay unchanged, when the custom_fonts checkbox has been checked == custom_fonts='on'
	 * but if it is unchecked, the fonts and sizes have to been erased */
	$users = db_fetch_assoc("SELECT user_id FROM settings_graphs WHERE name='custom_fonts' AND value=''");
	if (sizeof($users)) {
		foreach ($users as $user) {
			db_install_execute("0.8.8", "UPDATE settings_graphs SET `value`='' WHERE  name IN ('title_size','title_font','legend_size','legend_font','axis_size','axis_font','unit_size','unit_font') AND `user_id`=" . $user['user_id']);
		}
	}


	/* update the reindex cache, as we now introduced more options for "index count changed" */
	$device_snmp_query = db_fetch_assoc("select device_id,snmp_query_id from device_snmp_query");
	if (sizeof($device_snmp_query) > 0) {
		foreach ($device_snmp_query as $item) {
			update_reindex_cache($item["device_id"], $item["snmp_query_id"]);
		}
	}

	/* fill table I18N_TIME_ZONES */
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('UTC', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('GMT', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('UCT', 'EAT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Universal', 'CET-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Abidjan', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Accra', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Addis_Ababa', 'EAT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Algiers', 'CET-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Asmara', 'EAT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Bamako', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Bangui', 'WAT-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Banjul', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Bissau', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Blantyre', 'CAT-2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Brazzaville', 'WAT-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Bujumbura', 'CAT-2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Cairo', 'EET-2EEST,M4.5.5/01:00,M9.5.5/03:00');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Casablanca', 'WET0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Ceuta', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Conakry', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Dakar', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Dar_es_Salaam', 'EAT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Djibouti', 'EAT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Douala', 'WAT-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/El_Aaiun', 'WET0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Freetown', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Gaborone', 'CAT-2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Harare', 'CAT-2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Johannesburg', 'SAST-2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Kampala', 'EAT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Khartoum', 'EAT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Kigali', 'CAT-2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Kinshasa', 'WAT-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Lagos', 'WAT-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Libreville', 'WAT-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Lome', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Luanda', 'WAT-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Lubumbashi', 'CAT-2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Lusaka', 'CAT-2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Malabo', 'WAT-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Maputo', 'CAT-2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Maseru', 'SAST-2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Mbabane', 'SAST-2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Mogadishu', 'EAT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Monrovia', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Nairobi', 'EAT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Ndjamena', 'WAT-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Niamey', 'WAT-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Nouakchott', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Ouagadougou', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Porto-Novo', 'WAT-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Sao_Tome', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Tripoli', 'EET-2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Tunis', 'CET-1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Africa/Windhoek', 'WAT-1WAST,M9.1.0,M4.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Adak', 'HAST10HADT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Anchorage', 'AKST9AKDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Anguilla', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Antigua', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Araguaina', 'BRT3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Aruba', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Asuncion', 'PYT4PYST,M10.1.0/0,M4.2.0/0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Atikokan', 'EST5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Bahia', 'BRT3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Barbados', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Belem', 'BRT3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Belize', 'CST6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Blanc-Sablon', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Boa_Vista', 'AMT4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Bogota', 'COT5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Boise', 'MST7MDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Cambridge_Bay', 'MST7MDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Campo_Grande', 'AMT4AMST,M10.3.0/0,M2.3.0/0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Cancun', 'CST6CDT,M4.1.0,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Caracas', 'VET4:30');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Cayenne', 'GFT3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Cayman', 'EST5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Chicago', 'CST6CDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Chihuahua', 'MST7MDT,M4.1.0,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Costa_Rica', 'CST6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Cuiaba', 'AMT4AMST,M10.3.0/0,M2.3.0/0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Curacao', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Danmarkshavn', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Dawson', 'PST8PDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Dawson_Creek', 'MST7');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Denver', 'MST7MDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Detroit', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Dominica', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Edmonton', 'MST7MDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Eirunepe', 'AMT4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/El_Salvador', 'CST6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Fortaleza', 'BRT3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Glace_Bay', 'AST4ADT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Godthab', 'WGT-3WGST,M3.5.0/1,M10.5.0/1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Goose_Bay', 'AST4ADT,M3.2.0/0:01,M11.1.0/0:01');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Grand_Turk', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Grenada', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Guadeloupe', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Guatemala', 'CST6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Guayaquil', 'ECT5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Guyana', 'GYT4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Halifax', 'AST4ADT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Havana', 'CST5CDT,M3.2.0/0,M10.5.0/1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Hermosillo', 'MST7');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Inuvik', 'MST7MDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Iqaluit', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Jamaica', 'EST5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Juneau', 'AKST9AKDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/La_Paz', 'BOT4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Lima', 'PET5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Los_Angeles', 'PST8PDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Maceio', 'BRT3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Managua', 'CST6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Manaus', 'AMT4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Marigot', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Martinique', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Mazatlan', 'MST7MDT,M4.1.0,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Menominee', 'CST6CDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Merida', 'CST6CDT,M4.1.0,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Mexico_City', 'CST6CDT,M4.1.0,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Miquelon', 'PMST3PMDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Moncton', 'AST4ADT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Monterrey', 'CST6CDT,M4.1.0,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Montevideo', 'UYT3UYST,M10.1.0,M3.2.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Montreal', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Montserrat', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Nassau', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/New_York', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Nipigon', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Nome', 'AKST9AKDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Noronha', 'FNT2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Panama', 'EST5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Pangnirtung', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Paramaribo', 'SRT3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Phoenix', 'MST7');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Port-au-Prince', 'EST5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Porto_Velho', 'AMT4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Port_of_Spain', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Puerto_Rico', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Rainy_River', 'CST6CDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Rankin_Inlet', 'CST6CDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Recife', 'BRT3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Regina', 'CST6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Resolute', 'CST5CDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Rio_Branco', 'AMT4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Santarem', 'BRT3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Santiago', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Santo_Domingo', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Sao_Paulo', 'BRT3BRST,M10.3.0/0,M2.3.0/0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Scoresbysund', 'EGT1EGST,M3.5.0/0,M10.5.0/1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Shiprock', 'MST7MDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/St_Barthelemy', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/St_Johns', 'NST3:30NDT,M3.2.0/0:01,M11.1.0/0:01');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/St_Kitts', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/St_Lucia', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/St_Thomas', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/St_Vincent', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Swift_Current', 'CST6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Tegucigalpa', 'CST6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Thule', 'AST4ADT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Thunder_Bay', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Tijuana', 'PST8PDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Toronto', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Tortola', 'AST4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Vancouver', 'PST8PDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Whitehorse', 'PST8PDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Winnipeg', 'CST6CDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Yakutat', 'AKST9AKDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Yellowknife', 'MST7MDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Argentina/Buenos_Aires', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Argentina/Catamarca', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Argentina/Cordoba', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Argentina/Jujuy', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Argentina/La_Rioja', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Argentina/Mendoza', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Argentina/Rio_Gallegos', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Argentina/Salta', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Argentina/San_Juan', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Argentina/San_Luis', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Argentina/Tucuman', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Argentina/Ushuaia', 'ART3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Indiana/Indianapolis', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Indiana/Knox', 'CST6CDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Indiana/Marengo', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Indiana/Petersburg', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Indiana/Tell_City', 'CST6CDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Indiana/Vevay', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Indiana/Vincennes', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Indiana/Winamac', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Kentucky/Louisville', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/Kentucky/Monticello', 'EST5EDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/North_Dakota/Center', 'CST6CDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('America/North_Dakota/New_Salem', 'CST6CDT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Antarctica/Casey', 'WST-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Antarctica/Davis', 'DAVT-7');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Antarctica/DumontDUrville', 'DDUT-10');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Antarctica/Mawson', 'MAWT-5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Antarctica/McMurdo', 'NZST-12NZDT,M9.5.0,M4.1.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Antarctica/Palmer', 'GMT4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Antarctica/Rothera', 'ROTT3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Antarctica/South_Pole', 'NZST-12NZDT,M9.5.0,M4.1.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Antarctica/Syowa', 'SYOT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Antarctica/Vostok', 'VOST-6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Arctic/Longyearbyen', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Aden', 'AST-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Almaty', 'ALMT-6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Amman', 'EET-2EEST,M3.5.4/0,M10.5.5/1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Anadyr', 'ANAT-11ANAST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Aqtau', 'AQTT-5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Aqtobe', 'AQTT-5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Ashgabat', 'TMT-5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Baghdad', 'AST-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Bahrain', 'AST-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Baku', 'AZT-4AZST,M3.5.0/4,M10.5.0/5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Bangkok', 'ICT-7');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Beirut', 'EET-2EEST,M3.5.0/0,M10.5.0/0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Bishkek', 'KGT-6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Brunei', 'BNT-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Choibalsan', 'CHOT-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Chongqing', 'CST-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Colombo', 'IST-5:30');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Damascus', 'EET-2EEST,M4.1.5/0,M10.5.5/0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Dhaka', 'BDT-6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Dili', 'TLT-9');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Dubai', 'GST-4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Dushanbe', 'TJT-5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Gaza', 'EET-2EEST,M3.5.6/0:01,M9.1.5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Harbin', 'CST-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Hong_Kong', 'HKT-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Hovd', 'HOVT-7');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Ho_Chi_Minh', 'ICT-7');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Irkutsk', 'IRKT-8IRKST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Jakarta', 'WIT-7');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Jayapura', 'EIT-9');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Jerusalem', 'IST-2IDT,M4.1.5,M10.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Kabul', 'AFT-4:30');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Kamchatka', 'PETT-11PETST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Karachi', 'PKT-5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Kashgar', 'CST-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Katmandu', 'NPT-5:45');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Kolkata', 'IST-5:30');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Krasnoyarsk', 'KRAT-7KRAST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Kuala_Lumpur', 'MYT-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Kuching', 'MYT-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Kuwait', 'AST-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Macau', 'CST-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Magadan', 'MAGT-11MAGST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Makassar', 'CIT-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Manila', 'PHT-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Muscat', 'GST-4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Nicosia', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Novosibirsk', 'NOVT-6NOVST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Omsk', 'OMST-6OMSST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Oral', 'ORAT-5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Phnom_Penh', 'ICT-7');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Pontianak', 'WIT-7');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Pyongyang', 'KST-9');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Qatar', 'AST-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Qyzylorda', 'QYZT-6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Rangoon', 'MMT-6:30');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Riyadh', 'AST-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Sakhalin', 'SAKT-10SAKST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Samarkand', 'UZT-5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Seoul', 'KST-9');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Shanghai', 'CST-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Singapore', 'SGT-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Taipei', 'CST-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Tashkent', 'UZT-5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Tbilisi', 'GET-4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Tehran', 'IRST-3:30IRDT,M3.4.2,M9.5.4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Thimphu', 'BTT-6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Tokyo', 'JST-9');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Ulaanbaatar', 'ULAT-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Urumqi', 'CST-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Vientiane', 'ICT-7');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Vladivostok', 'VLAT-10VLAST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Yakutsk', 'YAKT-9YAKST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Yekaterinburg', 'YEKT-5YEKST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Asia/Yerevan', 'AMT-4AMST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Atlantic/Azores', 'AZOT1AZOST,M3.5.0/0,M10.5.0/1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Atlantic/Bermuda', 'AST4ADT,M3.2.0,M11.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Atlantic/Canary', 'WET0WEST,M3.5.0/1,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Atlantic/Cape_Verde', 'CVT1');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Atlantic/Faroe', 'WET0WEST,M3.5.0/1,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Atlantic/Madeira', 'WET0WEST,M3.5.0/1,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Atlantic/Reykjavik', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Atlantic/South_Georgia', 'GST2');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Atlantic/Stanley', 'FKT4FKST,M9.1.0,M4.3.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Atlantic/St_Helena', 'GMT0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Amsterdam', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Andorra', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Athens', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Belgrade', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Berlin', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Bratislava', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Brussels', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Bucharest', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Budapest', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Chisinau', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Copenhagen', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Dublin', 'GMT0IST,M3.5.0/1,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Gibraltar', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Guernsey', 'GMT0BST,M3.5.0/1,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Helsinki', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Isle_of_Man', 'GMT0BST,M3.5.0/1,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Istanbul', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Jersey', 'GMT0BST,M3.5.0/1,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Kaliningrad', 'EET-2EEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Kiev', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Lisbon', 'WET0WEST,M3.5.0/1,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Ljubljana', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/London', 'GMT0BST,M3.5.0/1,M10.5.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Luxembourg', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Madrid', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Malta', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Mariehamn', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Minsk', 'EET-2EEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Monaco', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Moscow', 'MSK-3MSD,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Oslo', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Paris', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Podgorica', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Prague', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Riga', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Rome', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Samara', 'SAMT-3SAMST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/San_Marino', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Sarajevo', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Simferopol', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Skopje', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Sofia', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Stockholm', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Tallinn', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Tirane', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Uzhgorod', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Vaduz', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Vatican', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Vienna', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Vilnius', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Volgograd', 'VOLT-3VOLST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Warsaw', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Zagreb', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Zaporozhye', 'EET-2EEST,M3.5.0/3,M10.5.0/4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Europe/Zurich', 'CET-1CEST,M3.5.0,M10.5.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Indian/Antananarivo', 'EAT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Indian/Chagos', 'IOT-6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Indian/Christmas', 'CXT-7');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Indian/Cocos', 'CCT-6:30');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Indian/Comoro', 'EAT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Indian/Kerguelen', 'TFT-5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Indian/Mahe', 'SCT-4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Indian/Maldives', 'MVT-5');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Indian/Mauritius', 'MUT-4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Indian/Mayotte', 'EAT-3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Indian/Reunion', 'RET-4');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Apia', 'WST11');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Auckland', 'NZST-12NZDT,M9.5.0,M4.1.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Chatham', 'CHAST-12:45CHADT,M9.5.0/2:45,M4.1.0/3:45');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Easter', 'EAST6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Efate', 'VUT-11');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Enderbury', 'PHOT-13');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Fakaofo', 'TKT10');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Fiji', 'FJT-12');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Funafuti', 'TVT-12');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Galapagos', 'GALT6');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Gambier', 'GAMT9');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Guadalcanal', 'SBT-11');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Guam', 'ChST-10');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Honolulu', 'HST10');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Johnston', 'HST10');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Kiritimati', 'LINT-14');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Kosrae', 'KOST-11');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Kwajalein', 'MHT-12');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Majuro', 'MHT-12');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Marquesas', 'MART9:30');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Midway', 'SST11');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Nauru', 'NRT-12');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Niue', 'NUT11');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Norfolk', 'NFT-11:30');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Noumea', 'NCT-11');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Pago_Pago', 'SST11');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Palau', 'PWT-9');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Pitcairn', 'PST8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Ponape', 'PONT-11');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Port_Moresby', 'PGT-10');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Rarotonga', 'CKT10');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Saipan', 'ChST-10');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Tahiti', 'TAHT10');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Tarawa', 'GILT-12');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Tongatapu', 'TOT-13');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Truk', 'TRUT-10');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Wake', 'WAKT-12');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Pacific/Wallis', 'WFT-12');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Australia/Adelaide', 'CST-9:30CST,M10.1.0,M4.1.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Australia/Brisbane', 'EST-10');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Australia/Broken_Hill', 'CST-9:30CST,M10.1.0,M4.1.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Australia/Currie', 'EST-10EST,M10.1.0,M4.1.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Australia/Darwin', 'CST-9:30');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Australia/Eucla', 'CWST-8:45');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Australia/Hobart', 'EST-10EST,M10.1.0,M4.1.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Australia/Lindeman', 'EST-10');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Australia/Lord_Howe', 'LHST-10:30LHST-11,M10.1.0,M4.1.0');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Australia/Melbourne', 'EST-10EST,M10.1.0,M4.1.0/3');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Australia/Perth', 'WST-8');");
	db_install_execute("0.8.8", "REPLACE INTO `i18n_time_zones` (`olson_tz_string`, `posix_tz_string`) VALUES('Australia/Sydney', 'EST-10EST,M10.1.0,M4.1.0/3');");

	/* drop deprecated plugins */
	if (sizeof($plugins_deprecated)) {
		foreach($plugins_deprecated as $plugin) {
			plugin_uninstall($plugin);
			cacti_log(__FUNCTION__ . " plugin '$plugin' was uninstalled", false);
		}
	}
	
	/* fill font cache */
	repopulate_font_cache();

	/* TODO: Upgrade current users and permissions */
}
