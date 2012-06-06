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

require(CACTI_INCLUDE_PATH . '/auth/auth_arrays.php');

/* file: user_admin.php, action: user_edit (device) */
$fields_user_user_edit_device = array(
	'username' => array(
		'method' => 'textbox',
		'friendly_name' => __('User Name'),
		'description' => __('The login name for this user.'),
		'value' => '|arg1:username|',
		'max_length' => '255',
		'size' => '70'
	),
	'full_name' => array(
		'method' => 'textbox',
		'friendly_name' => __('Full Name'),
		'description' => __('A more descriptive name for this user, that can include spaces or special characters.'),
		'value' => '|arg1:full_name|',
		'max_length' => '255',
		'size' => '70'
	),
	'password' => array(
		'method' => 'textbox_password',
		'friendly_name' => __('Password'),
		'description' => __('Enter the password for this user twice. Remember that passwords are case sensitive!'),
		'value' => '',
		'max_length' => '255',
		'size' => '70'
	),
	'enabled' => array(
		'method' => 'checkbox',
		'friendly_name' => __('Enabled'),
		'description' => __('Determines if user is able to login.'),
		'value' => '|arg1:enabled|',
		'default' => ''
	),
	'grp1' => array(
		'friendly_name' => __('Account Options'),
		'method' => 'checkbox_group',
		'description' => __('Set any user account-specific options here.'),
		'items' => array(
			'must_change_password' => array(
				'value' => '|arg1:must_change_password|',
				'friendly_name' => __('User Must Change Password at Next Login'),
				'form_id' => '|arg1:id|',
				'default' => ''
			),
			'graph_settings' => array(
				'value' => '|arg1:graph_settings|',
				'friendly_name' => __('Allow this User to Keep Custom Graph Settings'),
				'form_id' => '|arg1:id|',
				'default' => CHECKED
			)
		)
	),
	'grp2' => array(
		'friendly_name' => __('Graph Options'),
		'method' => 'checkbox_group',
		'description' => __('Set any graph-specific options here.'),
		'items' => array(
			'show_tree' => array(
				'value' => '|arg1:show_tree|',
				'friendly_name' => __('User Has Rights to Tree View'),
				'form_id' => '|arg1:id|',
				'default' => CHECKED
			),
			'show_list' => array(
				'value' => '|arg1:show_list|',
				'friendly_name' => __('User Has Rights to List View'),
				'form_id' => '|arg1:id|',
				'default' => CHECKED
			),
			'show_preview' => array(
				'value' => '|arg1:show_preview|',
				'friendly_name' => __('User Has Rights to Preview View'),
				'form_id' => '|arg1:id|',
				'default' => CHECKED
			)
		)
	),
	'login_opts' => array(
		'friendly_name' => __('Login Options'),
		'method' => 'radio',
		'default' => AUTH_LOGIN_OPT_REFER,
		'description' => __('What to do when this user logs in.'),
		'value' => '|arg1:login_opts|',
		'items' => array(
			0 => array(
				'radio_value' => AUTH_LOGIN_OPT_REFER,
				'radio_caption' => __('Show the page that user pointed their browser to.'),
			),
			1 => array(
				'radio_value' => AUTH_LOGIN_OPT_CONSOLE,
				'radio_caption' => __('Show the default console screen.'),
			),
			2 => array(
				'radio_value' => AUTH_LOGIN_OPT_GRAPH,
				'radio_caption' => __('Show the default graph screen.'),
			)
		)
	),
	'realm' => array(
		'method' => 'drop_array',
		'friendly_name' => __('Authentication Realm'),
		'description' => __('Only used if you have LDAP or Web Basic Authentication enabled. Changing this to an non-enabled realm will effectively disable the user.'),
		'value' => '|arg1:realm|',
		'default' => 0,
		'array' => $auth_realms,
	),
);
