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

if (!defined('VALID_HOST_FIELDS')) {
	$string = plugin_hook_function('valid_host_fields', '(hostname|host_id|snmp_community|snmp_username|snmp_password|snmp_auth_protocol|snmp_priv_passphrase|snmp_priv_protocol|snmp_context|snmp_version|snmp_port|snmp_timeout)');
	define('VALID_HOST_FIELDS', $string);
}

/* file: data_input.php, action: edit */
$fields_data_input_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "Enter a meaningful name for this data input method.",
		"value" => "|arg1:name|",
		"max_length" => "255",
		),
	"type_id" => array(
		"method" => "drop_array",
		"friendly_name" => "Input Type",
		"description" => "Choose the method you wish to use to collect data for this Data Input method.",
		"value" => "|arg1:type_id|",
		"array" => $input_types,
		),
	"input_string" => array(
		"method" => "textarea",
		"friendly_name" => "Input String",
		"description" => "The data that is sent to the script, which includes the complete path to the script and input sources in &lt;&gt; brackets.",
		"value" => "|arg1:input_string|",
		"textarea_rows" => "4",
		"textarea_cols" => "60",
		"class" => "textAreaNotes",
		"max_length" => "255",
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"save_component_data_input" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: data_input.php, action: field_edit (dropdown) */
$fields_data_input_field_edit_1 = array(
	"data_name" => array(
		"method" => "drop_array",
		"friendly_name" => "Field [|arg1:|]",
		"description" => "Choose the associated field from the |arg1:| field.",
		"value" => "|arg3:data_name|",
		"array" => "|arg2:|",
		)
	);

/* file: data_input.php, action: field_edit (textbox) */
$fields_data_input_field_edit_2 = array(
	"data_name" => array(
		"method" => "textbox",
		"friendly_name" => "Field [|arg1:|]",
		"description" => "Enter a name for this |arg1:| field.",
		"value" => "|arg2:data_name|",
		"max_length" => "50",
		)
	);

/* file: data_input.php, action: field_edit */
$fields_data_input_field_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Friendly Name",
		"description" => "Enter a meaningful name for this data input method.",
		"value" => "|arg1:name|",
		"max_length" => "200",
		),
	"update_rra" => array(
		"method" => "checkbox",
		"friendly_name" => "Update RRD File",
		"description" => "Whether data from this output field is to be entered into the rrd file.",
		"value" => "|arg1:update_rra|",
		"default" => "on",
		"form_id" => "|arg1:id|"
		),
	"regexp_match" => array(
		"method" => "textbox",
		"friendly_name" => "Regular Expression Match",
		"description" => "If you want to require a certain regular expression to be matched against input data, enter it here (ereg format).",
		"value" => "|arg1:regexp_match|",
		"max_length" => "200",
		"size" => "70"
		),
	"allow_nulls" => array(
		"method" => "checkbox",
		"friendly_name" => "Allow Empty Input",
		"description" => "Check here if you want to allow NULL input in this field from the user.",
		"value" => "|arg1:allow_nulls|",
		"default" => "",
		"form_id" => false
		),
	"type_code" => array(
		"method" => "textbox",
		"friendly_name" => "Special Type Code",
		"description" => "If this field should be treated specially by host templates, indicate so here. Valid keywords for this field are " . (str_replace(")", "'", str_replace("(", "'", str_replace("|", "', '", VALID_HOST_FIELDS)))),
		"value" => "|arg1:type_code|",
		"max_length" => "40"
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"input_output" => array(
		"method" => "hidden",
		"value" => "|arg2:|"
		),
	"sequence" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:sequence|"
		),
	"data_input_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg3:data_input_id|"
		),
	"save_component_field" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: data_templates.php, action: template_edit */
$fields_data_template_template_edit = array(
	"template_name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "The name given to this data template.",
		"value" => "|arg1:name|",
		"max_length" => "150",
		),
	"data_template_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg2:data_template_id|"
		),
	"data_template_data_id" => array(
		"method" => "hidden_zero",
		"value" => "|arg2:id|"
		),
	"current_rrd" => array(
		"method" => "hidden_zero",
		"value" => "|arg3:view_rrd|"
		),
	"save_component_template" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: grprint_presets.php, action: edit */
$fields_grprint_presets_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "Enter a name for this GPRINT preset, make sure it is something you recognize.",
		"value" => "|arg1:name|",
		"max_length" => "50",
		),
	"gprint_text" => array(
		"method" => "textbox",
		"friendly_name" => "GPRINT Text",
		"description" => "Enter the custom GPRINT string here.",
		"value" => "|arg1:gprint_text|",
		"max_length" => "50",
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"save_component_gprint_presets" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);


/* file: host_templates.php, action: edit */
$fields_host_template_edit = array(
	"name" => array(
		"method" => "textbox",
		"friendly_name" => "Name",
		"description" => "A useful name for this host template.",
		"value" => "|arg1:name|",
		"max_length" => "255",
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"save_component_template" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

/* file: user_admin.php, action: user_edit (host) */
$fields_user_user_edit_host = array(
	"username" => array(
		"method" => "textbox",
		"friendly_name" => "User Name",
		"description" => "The login name for this user.",
		"value" => "|arg1:username|",
		"max_length" => "255"
		),
	"full_name" => array(
		"method" => "textbox",
		"friendly_name" => "Full Name",
		"description" => "A more descriptive name for this user, that can include spaces or special characters.",
		"value" => "|arg1:full_name|",
		"max_length" => "255"
		),
	"password" => array(
		"method" => "textbox_password",
		"friendly_name" => "Password",
		"description" => "Enter the password for this user twice. Remember that passwords are case sensitive!",
		"value" => "",
		"max_length" => "255"
		),
	"enabled" => array(
		"method" => "checkbox",
		"friendly_name" => "Enabled",
		"description" => "Determines if user is able to login.",
		"value" => "|arg1:enabled|",
		"default" => ""
		),
	"grp1" => array(
		"friendly_name" => "Account Options",
		"method" => "checkbox_group",
		"description" => "Set any user account-specific options here.",
		"items" => array(
			"must_change_password" => array(
				"value" => "|arg1:must_change_password|",
				"friendly_name" => "User Must Change Password at Next Login",
				"form_id" => "|arg1:id|",
				"default" => ""
				),
			"graph_settings" => array(
				"value" => "|arg1:graph_settings|",
				"friendly_name" => "Allow this User to Keep Custom Graph Settings",
				"form_id" => "|arg1:id|",
				"default" => "on"
				)
			)
		),
	"grp2" => array(
		"friendly_name" => "Graph Options",
		"method" => "checkbox_group",
		"description" => "Set any graph-specific options here.",
		"items" => array(
			"show_tree" => array(
				"value" => "|arg1:show_tree|",
				"friendly_name" => "User Has Rights to Tree View",
				"form_id" => "|arg1:id|",
				"default" => "on"
				),
			"show_list" => array(
				"value" => "|arg1:show_list|",
				"friendly_name" => "User Has Rights to List View",
				"form_id" => "|arg1:id|",
				"default" => "on"
				),
			"show_preview" => array(
				"value" => "|arg1:show_preview|",
				"friendly_name" => "User Has Rights to Preview View",
				"form_id" => "|arg1:id|",
				"default" => "on"
				)
			)
		),
	"login_opts" => array(
		"friendly_name" => "Login Options",
		"method" => "radio",
		"default" => "1",
		"description" => "What to do when this user logs in.",
		"value" => "|arg1:login_opts|",
		"items" => array(
			0 => array(
				"radio_value" => "1",
				"radio_caption" => "Show the page that user pointed their browser to."
				),
			1 => array(
				"radio_value" => "2",
				"radio_caption" => "Show the default console screen."
				),
			2 => array(
				"radio_value" => "3",
				"radio_caption" => "Show the default graph screen."
				)
			)
		),
	"realm" => array(
		"method" => "drop_array",
		"friendly_name" => "Authentication Realm",
		"description" => "Only used if you have LDAP or Web Basic Authentication enabled.  Changing this to an non-enabled realm will effectively disable the user.",
		"value" => "|arg1:realm|",
		"default" => 0,
		"array" => $auth_realms,
		),
	"id" => array(
		"method" => "hidden_zero",
		"value" => "|arg1:id|"
		),
	"_policy_graphs" => array(
		"method" => "hidden",
		"default" => "2",
		"value" => "|arg1:policy_graphs|"
		),
	"_policy_trees" => array(
		"method" => "hidden",
		"default" => "2",
		"value" => "|arg1:policy_trees|"
		),
	"_policy_hosts" => array(
		"method" => "hidden",
		"default" => "2",
		"value" => "|arg1:policy_hosts|"
		),
	"_policy_graph_templates" => array(
		"method" => "hidden",
		"default" => "2",
		"value" => "|arg1:policy_graph_templates|"
		),
	"save_component_user" => array(
		"method" => "hidden",
		"value" => "1"
		)
	);

$export_types = array(
	"graph_template" => array(
		"name" => "Graph Template",
		"title_sql" => "select name from graph_templates where id=|id|",
		"dropdown_sql" => "select id,name from graph_templates order by name"
		),
	"data_template" => array(
		"name" => "Data Template",
		"title_sql" => "select name from data_template where id=|id|",
		"dropdown_sql" => "select id,name from data_template order by name"
		),
	"host_template" => array(
		"name" => "Host Template",
		"title_sql" => "select name from host_template where id=|id|",
		"dropdown_sql" => "select id,name from host_template order by name"
		),
	"data_query" => array(
		"name" => "Data Query",
		"title_sql" => "select name from snmp_query where id=|id|",
		"dropdown_sql" => "select id,name from snmp_query order by name"
		)
	);

$fields_template_import = array(
	"import_file" => array(
		"friendly_name" => "Import Template from Local File",
		"description" => "If the XML file containing template data is located on your local machine, select it here.",
		"method" => "file"
		),
	"import_text" => array(
		"method" => "textarea",
		"friendly_name" => "Import Template from Text",
		"description" => "If you have the XML file containing template data as text, you can paste it into this box to import it.",
		"value" => "",
		"default" => "",
		"textarea_rows" => "10",
		"textarea_cols" => "50",
		"class" => "textAreaNotes"
		),
	"import_rra" => array(
		"friendly_name" => "Import RRA Settings",
		"description" => "Choose whether to allow Cacti to import custom RRA settings from imported templates or whether to use the defaults for this installation.",
		"method" => "radio",
		"value" => "",
		"default" => "1",
		"on_change" => "changeRRA()",
		"items" => array(
			0 => array(
				"radio_value" => "1",
				"radio_caption" => "Select your RRA settings below (Recommended)"
				),
			1 => array(
				"radio_value" => "2",
				"radio_caption" => "Use custom RRA settings from the template"
				),
			)
		),
	"rra_id" => array(
		"method" => "drop_multi_rra",
		"friendly_name" => "Associated RRA's",
		"description" => "Which RRA's to use when entering data (It is recommended that you <strong>deselect unwanted values</strong>).",
		"form_id" => "",
		"sql_all" => "select rra.id from rra where id in (1,2,3,4) order by id",
		),
	);


/*
 * moved to specific files
 */
if (CACTI_FORM_COMPAT === TRUE) {
	require(CACTI_INCLUDE_PATH . '/auth/auth_forms.php');
	require(CACTI_INCLUDE_PATH . '/data_query/data_query_forms.php');
	require(CACTI_INCLUDE_PATH . '/device/device_forms.php');
	require(CACTI_INCLUDE_PATH . '/graph/graph_forms.php');
	require(CACTI_INCLUDE_PATH . '/graph_template/graph_template_forms.php');
	require(CACTI_INCLUDE_PATH . '/graph_tree/graph_tree_forms.php');
	require(CACTI_INCLUDE_PATH . '/poller/poller_forms.php');
	require(CACTI_INCLUDE_PATH . '/presets/preset_cdef_forms.php');
	require(CACTI_INCLUDE_PATH . '/presets/preset_color_forms.php');
	require(CACTI_INCLUDE_PATH . '/presets/preset_rra_forms.php');
	require(CACTI_INCLUDE_PATH . '/data_source/data_source_forms.php');
}
