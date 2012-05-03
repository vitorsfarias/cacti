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

include('include/global.php');

/* check to see if this is a new installation */
if ($database_empty)
{
	?>
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	<html>
	<head>
		<title>Cacti</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<link href="<?php echo CACTI_URL_PATH; ?>include/main.css" rel="stylesheet">
		<link rel="shortcut icon" href="<?php echo CACTI_URL_PATH; ?>images/favicon.ico">
	</head>
	<body>
	<br><br>
	
	<table width="450" align='center'>
		<tr>
			<td colspan='2'><img src='<?php echo CACTI_URL_PATH; ?>images/auth_deny.gif' alt='<?php echo 'Access Denied'; ?>'></td>
		</tr>
		<tr><td></td></tr>
		<tr>
			<td class='textArea' colspan='2'>You've configured a database for the CACTI system in <em>include/config.php</em></br>
			But this database seems to be empty or we lack access to that database.</br></br>
			Current settings for the database are as follows:</br></td>
		</tr>
		<tr>
			<td class='textArea'>hostname: </td>
			<td class='textArea'><em><?php print $database_hostname; ?></em>, </br></td>
		</tr>
		<tr>
			<td class='textArea'>user: </td>
			<td class='textArea'><em><?php print $database_username; ?></em>, </br>
		</tr>
		<tr>
			<td class='textArea'>password: </td>
			<td class='textArea'><em><?php print $database_password; ?></em>, </br>
		</tr>
		<tr>
			<td class='textArea'>name: </td>
			<td class='textArea'><em><?php print $database_default; ?></em>, </br>
		</tr>
		<tr>
			<td class='textArea'>type: </td>
			<td class='textArea'><em><?php print $database_type; ?></em>, </br>
		</tr>
		<tr>
			<td class='textArea'>port: </td>
			<td class='textArea'><em><?php print $database_port; ?></em>,</br>
		</tr>
		<tr>
			<td class='textArea'>ssl mode: </td>
			<td class='textArea'><em><?php print $database_ssl; ?></em></td>
		</tr>
	</table>
	
	</body>
	</html>
	<?php
	exit;
}
elseif (db_fetch_cell("select cacti from version") != CACTI_VERSION)
{
	/* we can access the database,
	 * but we have a version mismatch
	 * ==> perform a database upgrade
	 */
	header ('Location: install/');
	exit;
}


if (read_config_option("auth_method") != 0) {
	/* handle alternate authentication realms */
	api_plugin_hook_function('auth_alternate_realms');

	/* handle change password dialog */
	if ((isset($_SESSION['sess_change_password'])) && (read_config_option('webbasic_enabled') != CHECKED))
	{
		header ('Location: auth_changepassword.php?ref=' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php'));
		exit;
	}

	/* don't even bother with the guest code if we're already logged in */
	if ( (isset($guest_account)) && (empty($_SESSION['sess_user_id'])) )
	{
		$guest_user_id = db_fetch_cell("select id from user_auth where username='" . read_config_option('guest_user') . "' and realm = 0 and enabled = 'on'"); //FIXME: Input Quoting

		/* cannot find guest user */
		if (! empty($guest_user_id))
		{
			$_SESSION['sess_user_id'] = $guest_user_id;
		}
	}

	/* if we are a guest user in a non-guest area, wipe credentials */
	if (! empty($_SESSION['sess_user_id']))
	{
		if ( (! isset($guest_account)) && (db_fetch_cell("select id from user_auth where username='" . read_config_option('guest_user') . "'") == $_SESSION['sess_user_id']))
		{
			kill_session_var('sess_user_id');
		}
	}

	if (empty($_SESSION['sess_user_id']))
	{
		/* no logged in user */
		include('auth_login.php');
		exit;
	}
	else
	{
		$realm_id = 0;


		/* get realm for called file */
		if (isset($user_auth_realm_filenames[ basename($_SERVER['PHP_SELF']) ]))
		{
			$realm_id = $user_auth_realm_filenames[ basename($_SERVER['PHP_SELF']) ];
		}

		/* determine if user is allowed to access this realm */	
		$user_realm_check = db_fetch_assoc("select user_auth_realm.realm_id from user_auth_realm where user_auth_realm.user_id='" . $_SESSION['sess_user_id'] . "' and user_auth_realm.realm_id='" . $realm_id . "'");
		if ( empty($user_realm_check) || empty($realm_id) )
		{
			if (isset($_SERVER["HTTP_REFERER"]))
			{
				$referer = "<td class='textArea' colspan='2' align='center'>( <a href='" . $_SERVER["HTTP_REFERER"] . "'>" . __('Return') . "</a> | <a href='" . CACTI_URL_PATH . "logout.php'>" . __("Login Again") . "</a> )</td>";
			}
			else
			{
				$referer = "<td class='textArea' colspan='2' align='center'>( <a href='" . CACTI_URL_PATH . "index.php'>" . __('Login Again') . "</a> )</td>";
			}

			?>
			<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
			<html>
			<head>
				<title>Cacti</title>
				<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
				<link href="<?php echo CACTI_URL_PATH; ?>include/main.css" rel="stylesheet">
				<link rel="shortcut icon" href="<?php echo CACTI_URL_PATH; ?>images/favicon.ico">
			</head>
			<body>
			<br><br>

			<table width="450" align='center'>
				<tr>
					<td colspan='2'><img src='<?php echo CACTI_URL_PATH; ?>images/auth_deny.gif' alt='<?php echo __('Access Denied'); ?>'></td>
				</tr>
				<tr><td></td></tr>
				<tr>
					<td class='textArea' colspan='2'>You are not permitted to access this section of Cacti. If you feel that you
					need access to this particular section, please contact the Cacti administrator.</td>
				</tr>
				<tr>
					<?php print $referer; ?>
				</tr>
			</table>

			</body>
			</html>
			<?php
			exit;
		}
	}
}
