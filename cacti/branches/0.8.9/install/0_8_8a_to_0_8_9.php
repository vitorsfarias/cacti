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

function upgrade_to_0_8_9() {
	require_once("../lib/import.php");
	require("../include/plugins/plugin_arrays.php");
	require_once("../lib/plugins.php");
	require_once("../lib/poller.php");
	require_once("../lib/utility.php");

	$show_output = true;
	$drop_items = true;
	$no_drop_items = false;
	$data = array();
	$columns = array();

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
	plugin_upgrade_table('0.8.9', 'sites', $data, $show_output, $no_drop_items);

	# create new table graph_templates_xaxis
	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true, 'comment' => 'Unique Table Id');
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '', 'comment' => 'Unique Hash');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(100)', 'NULL' => false, 'default' => '', 'comment' => 'Name of X-Axis Preset');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['type'] = 'MyISAM';
	$data['comment'] = 'X-Axis Presets';
	plugin_upgrade_table('0.8.9', 'graph_templates_xaxis', $data, $show_output, $no_drop_items);

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
	plugin_upgrade_table('0.8.9', 'graph_templates_xaxis_items', $data, $show_output, $no_drop_items);

	/* create new table VDEF */
	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'hash', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'name', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.9', 'vdef', $data, $show_output, $no_drop_items);

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
	plugin_upgrade_table('0.8.9', 'vdef_items', $data, $show_output, $no_drop_items);

	/* create new table fonts */
	unset($data);
	$data['columns'][] = array('name' => 'id', 'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'font', 'type' => 'varchar(255)', 'NULL' => false, 'default' => '');
	$data['keys'][] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$data['type'] = 'MyISAM';
	plugin_upgrade_table('0.8.9', 'fonts', $data, $show_output, $no_drop_items);



	/*
	 * add new columns to existing tables
	 */
	/* add new columns for plugin_config */
	unset($columns);
	$columns[] = array('name' => 'ptype', 'type' => 'int(11)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '1', 'after' => 'version');
	$columns[] = array('name' => 'sequence', 'type' => 'mediumint(8)','unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'ptype');
	plugin_upgrade_columns('0.8.9', 'plugin_config', $columns, $show_output, $no_drop_items);

	/* adjust size of column for plugin_config */
	unset($columns);
	$columns[] = array('name' => 'plugin', 'type' => 'varchar(32)', 'NULL' => false, 'default' => '');
	plugin_upgrade_columns('0.8.9', 'plugin_db_changes', $columns, $show_output, $no_drop_items);

	/* add description */
	unset($columns);
	$columns[] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'after' => 'name');
	plugin_upgrade_columns('0.8.9', 'data_template', $columns, $show_output, $no_drop_items);

	/* add rrd_compute_rpn for data source items */
	unset($columns);
	$columns[] = array('name' => 't_rrd_compute_rpn', 'type' => 'char(2)', 'default' => NULL, 'after' => 'rrd_minimum');
	$columns[] = array('name' => 'rrd_compute_rpn', 'type' => 'varchar(150)', 'default' => '', 'after' => 't_rrd_compute_rpn');
	plugin_upgrade_columns('0.8.9', 'data_template_rrd', $columns, $show_output, $no_drop_items);

	/* add a site column to the host table */
	unset($columns);
	$columns[] = array('name' => 'site_id', 'type' => 'int(10)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'id');
	/* add the poller id for hosts to allow for multiple pollers */
	$columns[] = array('name' => 'poller_id', 'type' => 'smallint(5)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'site_id');
	$columns[] = array('name' => 'template_enabled', 'type' => 'char(2)', 'NULL' => false, 'default' => '', 'after' => 'host_template_id');
	/* implement per device threads setting for spine */
	$columns[] = array('name' => 'polling_time', 'type' => 'decimal(10,5)', 'NULL' => false, 'default' => '0.00000', 'after' => 'avg_time');
	plugin_upgrade_columns('0.8.9', 'host', $columns, $show_output, $no_drop_items);


	/* add some fields required for devices to table host_template */
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
	plugin_upgrade_columns('0.8.9', 'host_template', $columns, $show_output, $no_drop_items);

	/* add reindexing to host_template_snmp_query */
	unset($columns);
	$columns[] = array('name' => 'reindex_method', 'type' => 'tinyint(3)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'snmp_query_id');
	plugin_upgrade_columns('0.8.9', 'host_template_snmp_query', $columns, $show_output, $no_drop_items);

	unset($columns);
	$columns[] = array('name' => 'description', 'type' => 'varchar(255)', 'NULL' => false, 'after' => 'name');
	$columns[] = array('name' => 'image', 'type' => 'varchar(64)', 'NULL' => false, 'after' => 'description');
	plugin_upgrade_columns('0.8.9', 'graph_templates', $columns, $show_output, $no_drop_items);

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
	plugin_upgrade_columns('0.8.9', 'graph_templates_graph', $columns, $show_output, $no_drop_items);

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
	plugin_upgrade_columns('0.8.9', 'graph_templates_item', $columns, $show_output, $no_drop_items);

	/* add the poller id for devices to allow for multiple pollers */
	unset($columns);
	$columns[] = array('name' => 'id', 'type' => 'smallint(5)', 'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$columns[] = array('name' => 'disabled', 'type' => 'char(2)', 'default' => '', 'after' => 'id');
	$columns[] = array('name' => 'description', 'type' => 'varchar(45)', 'collation' => 'utf8_general_ci', 'NULL' => false, 'default' => '', 'after' => 'disabled');
	$columns[] = array('name' => 'hostname', 'type' => 'varchar(250)', 'collation' => 'utf8_general_ci', 'NULL' => false, 'default' => '', 'after' => 'description');
	$columns[] = array('name' => 'total_time', 'type' => 'double', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'hostname');
	$columns[] = array('name' => 'snmp', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'total_time');
	$columns[] = array('name' => 'script', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'snmp');
	$columns[] = array('name' => 'server', 'type' => 'mediumint(8)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'script');
	$columns[] = array('name' => 'last_update', 'type' => 'datetime', 'NULL' => false, 'default' => '0000-00-00 00:00:00', 'after' => 'server');
	/* remove stale column "ip_address" */
	plugin_upgrade_columns('0.8.9', 'poller', $columns, $show_output, $drop_items);
	/* AUTO_INCREMENT=2 */
	plugin_upgrade_auto_increment('0.8.9', 'poller', 2);

	unset($columns);
	$columns[] = array('name' => 'present', 'type' => 'tinyint(4)', 'NULL' => false, 'default' => '1', 'after' => 'action');
	plugin_upgrade_columns('0.8.9', 'poller_item', $columns, $show_output, $no_drop_items);

	/* add the poller id for poller_output to allow for multiple pollers */
	unset($columns);
	$columns[] = array('name' => 'poller_id', 'type' => 'smallint(5)', 'unsigned' => 'unsigned', 'NULL' => false, 'default' => '0', 'after' => 'time');
	plugin_upgrade_columns('0.8.9', 'poller_output', $columns, $show_output, $no_drop_items);

	unset($columns);
	$columns[] = array('name' => 'image', 'type' => 'varchar(64)', 'NULL' => false, 'after' => 'description');
	plugin_upgrade_columns('0.8.9', 'snmp_query', $columns, $show_output, $no_drop_items);


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
	plugin_upgrade_keys('0.8.9', 'data_input_data', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'host_id', 'columns' => 'host_id');
	$key[] = array('name' => 'host_id_snmp_query_id_snmp_index', 'columns' => 'host_id,snmp_query_id,snmp_index');
	plugin_upgrade_keys('0.8.9', 'data_local', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'local_data_id', 'columns' => 'local_data_id');
	$key[] = array('name' => 'data_template_id', 'columns' => 'data_template_id');
	$key[] = array('name' => 'data_source_path', 'columns' => 'data_source_path');
	plugin_upgrade_keys('0.8.9', 'data_template_data', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'local_data_id', 'columns' => 'local_data_id');
	$key[] = array('name' => 'data_template_id', 'columns' => 'data_template_id');
	$key[] = array('name' => 'local_data_template_rrd_id', 'columns' => 'local_data_template_rrd_id');
	$key[] = array('name' => 'local_data_id_data_source_name', 'columns' => 'local_data_id,data_source_name');
	plugin_upgrade_keys('0.8.9', 'data_template_rrd', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'disabled', 'columns' => 'disabled');
	$key[] = array('name' => 'poller_id', 'columns' => 'poller_id');
	$key[] = array('name' => 'site_id', 'columns' => 'site_id');
	plugin_upgrade_keys('0.8.9', 'host', $key, true);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'host_id,snmp_query_id,field_name,snmp_index', 'primary' => true);
	$key[] = array('name' => 'host_id', 'columns' => 'host_id,field_name');
	$key[] = array('name' => 'snmp_index', 'columns' => 'snmp_index');
	$key[] = array('name' => 'field_name', 'columns' => 'field_name');
	$key[] = array('name' => 'field_value', 'columns' => 'field_value');
	$key[] = array('name' => 'snmp_query_id', 'columns' => 'snmp_query_id');
	$key[] = array('name' => 'host_id_snmp_query_id', 'columns' => 'host_id,snmp_query_id');
	$key[] = array('name' => 'host_id_snmp_query_id_snmp_index', 'columns' => 'host_id,snmp_query_id,snmp_index');
	$key[] = array('name' => 'present', 'columns' => 'present', 'type' => 'BTREE');
	plugin_upgrade_keys('0.8.9', 'host_snmp_cache', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'host_id,snmp_query_id', 'primary' => true);
	$key[] = array('name' => 'host_id', 'columns' => 'host_id');
	plugin_upgrade_keys('0.8.9', 'host_snmp_query', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'host_template_id,graph_template_id', 'primary' => true);
	$key[] = array('name' => 'host_template_id', 'columns' => 'host_template_id');
	plugin_upgrade_keys('0.8.9', 'host_template_graph', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'host_template_id,snmp_query_id', 'primary' => true);
	$key[] = array('name' => 'host_template_id', 'columns' => 'host_template_id');
	plugin_upgrade_keys('0.8.9', 'host_template_snmp_query', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'host_id', 'columns' => 'host_id');
	$key[] = array('name' => 'graph_template_id', 'columns' => 'graph_template_id');
	$key[] = array('name' => 'snmp_query_id', 'columns' => 'snmp_query_id');
	$key[] = array('name' => 'snmp_index', 'columns' => 'snmp_index');
	plugin_upgrade_keys('0.8.9', 'graph_local', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'graph_template_id', 'columns' => 'graph_template_id');
	$key[] = array('name' => 'local_graph_id', 'columns' => 'local_graph_id');
	$key[] = array('name' => 'task_item_id', 'columns' => 'task_item_id');
	$key[] = array('name' => 'graph_template_id_local_graph_id', 'columns' => 'graph_template_id,local_graph_id');
	$key[] = array('name' => 'local_graph_template_item_id', 'columns' => 'local_graph_template_item_id');
	$key[] = array('name' => 'local_graph_id_sequence', 'columns' => 'local_graph_id,sequence');
	plugin_upgrade_keys('0.8.9', 'graph_templates_item', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'local_data_id,rrd_name', 'primary' => true);
	$key[] = array('name' => 'local_data_id', 'columns' => 'local_data_id');
	$key[] = array('name' => 'host_id', 'columns' => 'host_id');
	$key[] = array('name' => 'rrd_next_step', 'columns' => 'rrd_next_step');
	$key[] = array('name' => 'action', 'columns' => 'action');
	$key[] = array('name' => 'local_data_id_rrd_path', 'columns' => 'local_data_id,rrd_path');
	$key[] = array('name' => 'host_id_rrd_next_step', 'columns' => 'host_id,rrd_next_step');
	$key[] = array('name' => 'host_id_snmp_port', 'columns' => 'host_id,snmp_port');
	plugin_upgrade_keys('0.8.9', 'poller_item', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'local_data_id,rrd_name,time', 'primary' => true);
	$key[] = array('name' => 'poller_id', 'columns' => 'poller_id');
	plugin_upgrade_keys('0.8.9', 'poller_output', $key, $show_output, $drop_items);

	unset($key);
	$key[] = array('name' => 'PRIMARY', 'columns' => 'id', 'primary' => true);
	$key[] = array('name' => 'directory', 'columns' => 'directory', 'unique' => true);
	$key[] = array('name' => 'status', 'columns' => 'status');
	plugin_upgrade_keys('0.8.9', 'plugin_config', $key, $show_output, $drop_items);



	/*
	 * now update current entries of table host_template
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

	db_install_execute("0.8.9", "UPDATE `host_template` " .
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

	db_install_execute("0.8.9", "UPDATE `host_template_snmp_query` SET `reindex_method` = '1'");

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
			db_install_execute("0.8.9", "UPDATE `plugin_config` SET sequence=" . $i++ . ", ptype= " . $ptype . " WHERE id=" . $item["id"]);
		}
	}

	/* we don't need the internal hooks anymore; they're now part of the code base */
	db_install_execute("0.8.9", "DELETE FROM `plugin_hooks` WHERE `plugin_hooks`.`name` = 'internal'");
	db_install_execute("0.8.9", "REPLACE INTO `plugin_realms` VALUES (1, 'internal', 'plugins.php', 'Plugin Management')");

	/* wrong lower limit for generic OID graph template */
	db_install_execute("0.8.9", "UPDATE graph_templates_graph SET lower_limit='0', vertical_label='' WHERE id=47");

	/* Add SNMPv3 Context to SNMP Input Methods */
	/* first we must see if the user was smart enough to add it themselves */
	$context1 = db_fetch_row("SELECT id FROM data_input_fields WHERE data_input_id=1 AND data_name='snmp_context' AND input_output='in' AND type_code='snmp_context'");
	if ($context1 > 0) {
		# nop
	} else {
		db_install_execute("0.8.9", "INSERT INTO data_input_fields VALUES (DEFAULT, '8e42450d52c46ebe76a57d7e51321d36',1,'SNMP Context (v3)','snmp_context','in','',0,'snmp_context','','')");
	}
	$context2 = db_fetch_row("SELECT id FROM data_input_fields WHERE data_input_id=2 AND data_name='snmp_context' AND input_output='in' AND type_code='snmp_context'");
	if ($context2 > 0) {
		# nop
	} else {
		db_install_execute("0.8.9", "INSERT INTO data_input_fields VALUES (DEFAULT, 'b5ce68ca4e9e36d221459758ede01484',2,'SNMP Context (v3)','snmp_context','in','',0,'snmp_context','','')");
	}

	db_install_execute("0.8.9", "UPDATE data_input_fields SET name='SNMP Authentication Protocol (v3)' WHERE name='SNMP Authenticaion Protocol (v3)'");

	db_install_execute("0.8.9", "REPLACE INTO `graph_templates_xaxis` VALUES(1, 'a09c5cab07a6e10face1710cec45e82f', 'Default')");

	db_install_execute("0.8.9", "REPLACE INTO `graph_templates_xaxis_items` VALUES(1, '60c2066a1c45fab021d32fe72cbf4f49', 'Day', 1, 86400, 'HOUR', 4, 'HOUR', 2, 'HOUR', 2, 23200, '%H')");
	db_install_execute("0.8.9", "REPLACE INTO `graph_templates_xaxis_items` VALUES(2, 'd867f8fc2730af212d0fd6708385cf89', 'Week', 1, 604800, 'DAY', 1, 'DAY', 1, 'DAY', 1, 259200, '%d')");
	db_install_execute("0.8.9", "REPLACE INTO `graph_templates_xaxis_items` VALUES(3, '06304a1840da88f3e0438ac147219003', 'Month', 1, 2678400, 'WEEK', 1, 'WEEK', 1, 'WEEK', 1, 1296000, '%W')");
	db_install_execute("0.8.9", "REPLACE INTO `graph_templates_xaxis_items` VALUES(4, '33ac10e60fd855e74736bee43bda4134', 'Year', 1, 31622400, 'MONTH', 2, 'MONTH', 1, 'MONTH', 2, 15811200, '%m')");




	/* insert the default poller into the database */
	db_install_execute("0.8.9", "REPLACE INTO `poller` VALUES (1,'','Main Poller','localhost',0,0,0,0,'0000-00-00 00:00:00');");

	/* update all devices to use poller 1, or the main poller */
	db_install_execute("0.8.9", "UPDATE host SET poller_id=1 WHERE poller_id=0");

	/* update the poller_items table to set the default poller_id */
	db_install_execute("0.8.9", "UPDATE poller_item SET poller_id=1 WHERE poller_id=0");

	/* fill table VDEF */
	db_install_execute("0.8.9", "REPLACE INTO `vdef` VALUES (1, 'e06ed529238448773038601afb3cf278', 'Maximum');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef` VALUES (2, 'e4872dda82092393d6459c831a50dc3b', 'Minimum');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef` VALUES (3, '5ce1061a46bb62f36840c80412d2e629', 'Average');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef` VALUES (4, '06bd3cbe802da6a0745ea5ba93af554a', 'Last (Current)');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef` VALUES (5, '631c1b9086f3979d6dcf5c7a6946f104', 'First');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef` VALUES (6, '6b5335843630b66f858ce6b7c61fc493', 'Total: Current Data Source');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef` VALUES (7, 'c80d12b0f030af3574da68b28826cd39', '95th Percentage: Current Data Source');");



	/* fill table VDEF */
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (1, '88d33bf9271ac2bdf490cf1784a342c1', 1, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (2, 'a307afab0c9b1779580039e3f7c4f6e5', 1, 2, 1, '1');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (3, '0945a96068bb57c80bfbd726cf1afa02', 2, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (4, '95a8df2eac60a89e8a8ca3ea3d019c44', 2, 2, 1, '2');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (5, 'cc2e1c47ec0b4f02eb13708cf6dac585', 3, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (6, 'a2fd796335b87d9ba54af6a855689507', 3, 2, 1, '3');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (7, 'a1d7974ee6018083a2053e0d0f7cb901', 4, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (8, '26fccba1c215439616bc1b83637ae7f3', 4, 2, 1, '5');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (9, 'a8993b265f4c5398f4a47c44b5b37a07', 5, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (10, '5a380d469d611719057c3695ce1e4eee', 5, 2, 1, '6');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (11, '65cfe546b17175fad41fcca98c057feb', 6, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (12, 'f330b5633c3517d7c62762cef091cc9e', 6, 2, 1, '7');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (13, 'f1bf2ecf54ca0565cf39c9c3f7e5394b', 7, 1, 4, 'CURRENT_DATA_SOURCE');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (14, '11a26f18feba3919be3af426670cba95', 7, 2, 6, '95');");
	db_install_execute("0.8.9", "REPLACE INTO `vdef_items` VALUES (15, 'e7ae90275bc1efada07c19ca3472d9db', 7, 3, 1, '8');");

	# graph_templates_items: set line_width of x
	db_install_execute("0.8.9", "UPDATE graph_templates_item SET `line_width`=1 WHERE `graph_type_id`=4"); # LINE1
	db_install_execute("0.8.9", "UPDATE graph_templates_item SET `line_width`=2 WHERE `graph_type_id`=5"); # LINE2
	db_install_execute("0.8.9", "UPDATE graph_templates_item SET `line_width`=3 WHERE `graph_type_id`=6"); # LINE3

	/* new cdef's for background colorization */
	$cdef_id = 	db_fetch_cell("SELECT id FROM cdef WHERE hash='2544acefc5fef30366c71336166ed141';");
	if ($cdef_id == 0) {
		db_install_execute("0.8.9", "REPLACE INTO `cdef` VALUES(DEFAULT, '2544acefc5fef30366c71336166ed141', 'Time: Daytime')");
		$cdef_id = 	db_fetch_cell("SELECT id FROM cdef WHERE hash='2544acefc5fef30366c71336166ed141';");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'ac0dea239ef3279c9b5ee04990fd4ec0', $cdef_id, 1, 1, '42')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '12f2bd71d5cbc078b9712c54d21c4f59', $cdef_id, 2, 6, '86400')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'bf35d7e5ae6df56398ea0f34a77311fc', $cdef_id, 3, 2, '5')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '31a9b3ff3b402f0446e6f6454b4d47c2', $cdef_id, 4, 4, 'TIME_SHIFT_START')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '996b718fc70353deb676e9037af9eadd', $cdef_id, 5, 1, '23')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '9c48bd2133670fd5158264ac25df6bb6', $cdef_id, 6, 1, '42')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '50c205e8bd5bb19b7fbee0ec2dee44cb', $cdef_id, 7, 6, '86400')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '14ee4ad2c7f91ab6406e1ecec6f4bcdc', $cdef_id, 8, 2, '5')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '38023f18060f2586e3504bbdd2634cc3', $cdef_id, 9, 4, 'TIME_SHIFT_END')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '1dbfee1b96a11492e58128ee8de93925', $cdef_id, 10, 1, '21')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '6979b0680858c8d153530d9390f6a4e9', $cdef_id, 11, 1, '37')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'f9d37c6480c3555c9d6d2d8910ef2da7', $cdef_id, 12, 1, '36')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '6c2604fd53780532c93c16d82c0337fd', $cdef_id, 13, 4, 'CURRENT_DATA_SOURCE')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'c2652379ba1c6523dc036e0a312536c4', $cdef_id, 14, 2, '3')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '63bf07a965b64fc41faa4bf01ae8a39d', $cdef_id, 15, 1, '29')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '2a9dea57a4f5d12cd0e2e66a31186a35', $cdef_id, 16, 1, '36')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '014839ebf8261c501d1da6c2c5217a0c', $cdef_id, 17, 4, 'CURRENT_DATA_SOURCE')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '01c946b79d68fad871e6e9437cba924f', $cdef_id, 18, 2, '3')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '4d0879e3c65c5af4e35d41a1631dcbe5', $cdef_id, 19, 1, '29')");
	}

	$cdef_id = 	db_fetch_cell("SELECT id FROM cdef WHERE hash='8bd388f585b624a7bbad97101a2b7ee9';");
	if ($cdef_id == 0) {
		db_install_execute("0.8.9", "REPLACE INTO `cdef` VALUES(DEFAULT, '8bd388f585b624a7bbad97101a2b7ee9', 'Time: Nighttime')");
		$cdef_id = 	db_fetch_cell("SELECT id FROM cdef WHERE hash='8bd388f585b624a7bbad97101a2b7ee9';");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '1c9452055499efaddded29c74ee21880', $cdef_id, 1, 1, '42')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '36af4d7c5a8acf09bda1a3a5f1409979', $cdef_id, 2, 6, '86400')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '74cf8897d5ada9da271c64e82a1384ac', $cdef_id, 3, 2, '5')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '053c5efacd6787b6e41ed109043ba256', $cdef_id, 4, 4, 'TIME_SHIFT_START')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'da39b6410ab37833842511f46182717d', $cdef_id, 5, 1, '21')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '652afbee7025a256b8dc3c49e75b27fc', $cdef_id, 6, 1, '37')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '26a63ba997e1f904c71bb7c9eb5e76e5', $cdef_id, 7, 1, '42')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '6f83ed61e0743176f03dd790f31521ea', $cdef_id, 8, 6, '86400')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '6b49d9dc72576a7ada160f0befc77c85', $cdef_id, 9, 2, '5')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '22f0dd9a5e0e189424ea29fe1383e29d', $cdef_id, 10, 4, 'TIME_SHIFT_END')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'd3f3a319e8fcfac10bd06fb247d236af', $cdef_id, 11, 1, '23')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '1cf7208bfa84c61f788f327500b712a6', $cdef_id, 12, 1, '37')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'c29025779a287d2f7b946e9ffbba3c24', $cdef_id, 13, 1, '36')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '690852ea78bf45796ef21947e27528be', $cdef_id, 14, 4, 'CURRENT_DATA_SOURCE')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '09061dcd9762280ffd3994c8274b19f8', $cdef_id, 15, 2, '3')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '60be0afe23bef9fdb7e6cabd9067eb32', $cdef_id, 16, 1, '29')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'f4a6609839d199ecb12c2f05b5d3a7b6', $cdef_id, 17, 1, '29')");
	}

	$cdef_id = 	db_fetch_cell("SELECT id FROM cdef WHERE hash='b4ef0a1c5e471dc6bae6a13ace5c57e7';");
	if ($cdef_id == 0) {
		db_install_execute("0.8.9", "REPLACE INTO `cdef` VALUES(DEFAULT, 'b4ef0a1c5e471dc6bae6a13ace5c57e7', 'Time: Weekend')");
		$cdef_id = 	db_fetch_cell("SELECT id FROM cdef WHERE hash='b4ef0a1c5e471dc6bae6a13ace5c57e7';");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'd4f93d57657e6c3ae2053a4a760a0c7b', $cdef_id, 1, 1, '42')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '00a793341980c41728c6ee665718001c', $cdef_id, 2, 6, '604800')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '0a7eaf7192e5e44a425f5e8986850190', $cdef_id, 3, 2, '5')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'ceb07e26bf15c561b12004c5e32d7f1f', $cdef_id, 4, 6, '172800')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '3a3bfafebd173fdbbd8c07d2e2dd661f', $cdef_id, 5, 1, '23')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '4c080ecaaa7260886ea148869d4d0456', $cdef_id, 6, 1, '42')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'bd57afcd9879e29e29bb796ba8d6188d', $cdef_id, 7, 6, '604800')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'cd14cd9adfbae04973a75b90880e7d64', $cdef_id, 8, 2, '5')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '3bed46dd43a64d54acc4f0723cff0bc7', $cdef_id, 9, 6, '345600')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '6fa62ee12bb8ba8936e39ea4303f92fd', $cdef_id, 10, 1, '21')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'f26848c08c2fb385126f90107494ce64', $cdef_id, 11, 1, '37')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'b8a5dde83327cac6705cdaa58300153b', $cdef_id, 12, 1, '36')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'f6aa118b35e269101ca3049cc4a323db', $cdef_id, 13, 4, 'CURRENT_DATA_SOURCE')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '967beb159b1ea744460ff3439ab205eb', $cdef_id, 14, 2, '3')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'f30028a71a1f4333703c70f8e499b03a', $cdef_id, 15, 1, '29')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '6888be191630a0964fdb9eaeb01cecaf', $cdef_id, 16, 1, '36')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '77c456204e43a9053c68b51750d5df75', $cdef_id, 17, 4, 'CURRENT_DATA_SOURCE')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, 'ce271b7a9809646a1fe4a7cd286fd98a', $cdef_id, 18, 2, '3')");
		db_install_execute("0.8.9", "REPLACE INTO `cdef_items` VALUES(DEFAULT, '8bcd193850b37953ffe940fdf2a26aa6', $cdef_id, 19, 1, '29')");
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
			update_pre_089_graph_items($graph_template_items);
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
			update_pre_089_graph_items($graph_items);
		}
	}

	/* change the name and description for data input method "Unix - Get Load Average" from 10min to 15min */
	$dim_id = db_fetch_cell("SELECT id FROM `data_input` WHERE input_string LIKE '%%scripts/loadavg_multi.pl%%' LIMIT 0,1");
	$field_id = db_fetch_cell("SELECT id FROM `data_input_fields` WHERE data_name LIKE '%%10min%%' AND data_input_id =" . $dim_id . " LIMIT 0,1");
	if ($field_id > 0) {
		db_install_execute("0.8.9", "UPDATE data_input_fields SET `name`='15 Minute Average', `data_name`='15min' WHERE `id`=" . $field_id);
	}

	/* custom font handling has changed
	 * all font options may stay unchanged, when the custom_fonts checkbox has been checked == custom_fonts='on'
	 * but if it is unchecked, the fonts and sizes have to been erased */
	$users = db_fetch_assoc("SELECT user_id FROM settings_graphs WHERE name='custom_fonts' AND value=''");
	if (sizeof($users)) {
		foreach ($users as $user) {
			db_install_execute("0.8.9", "UPDATE settings_graphs SET `value`='' WHERE  name IN ('title_size','title_font','legend_size','legend_font','axis_size','axis_font','unit_size','unit_font') AND `user_id`=" . $user['user_id']);
		}
	}

	/* drop deprecated plugins, if present */
	if (sizeof($plugins_deprecated)) {
		foreach($plugins_deprecated as $plugin) {
			plugin_uninstall($plugin);
			cacti_log(__FUNCTION__ . ": plugin '$plugin' was uninstalled", false);
		}
	}
	
	/* fill font cache */
	repopulate_font_cache();

}
?>
