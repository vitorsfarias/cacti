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

/*
   !!! IMPORTANT !!!

   The following defaults are not to be altered.  Please refer to
   include/config.php for user configurable database settings.

*/

/* Default database settings*/
$database_type = 'mysql';
$database_default = 'cacti';
$database_hostname = 'localhost';
$database_username = 'cactiuser';
$database_password = 'cactiuser';
$database_port = '3306';
$database_ssl = false;

/* Default session name - Session name must contain alpha characters */
$cacti_session_name = 'Cacti';

/* Initialize config array and include the configuration */
$config = array();
if (file_exists(dirname(__FILE__) . '/config.php'))
{
	include(dirname(__FILE__) . '/config.php');
}

/* Default url path if not set */
if (! isset($config['url_path']))
{
	$config['url_path'] = '';
}

/* detect old configuration files */
if (isset($config['cacti_version']))
{
	die('Invalid include/config.php file detected.');
	exit;
}

/* set script memory limits */
ini_set('memory_limit', '512M');
if (isset($config['memory_limit']) && $config['memory_limit'] != '')
{
	ini_set('memory_limit', $config['memory_limit']);
}

/* setup proper enviroment */
if (strstr(PHP_OS, 'WIN'))
{
	define('CACTI_SERVER_OS', 'win32');
	define('CACTI_BASE_PATH', str_replace('\\', '/', substr(dirname(__FILE__), 0, -8)));

	/* suppress cygwin warnings */
	putenv('cygwin=nodosfilewarning');
}
else
{
	define('CACTI_SERVER_OS', 'unix');
	define('CACTI_BASE_PATH', preg_replace('/(.*)[\/]include/', '\\1', dirname(__FILE__)));
}

/* setup paths */
/* CACTI_BASE_PATH is platform dependant and has been defined above */
/* this is were distro package maintainers should look at */
define('CACTI_URL_PATH', $config['url_path']);
define('CACTI_RRA_PATH', CACTI_BASE_PATH . '/rra');
define('CACTI_LIBRARY_PATH', CACTI_BASE_PATH . '/lib');
define('CACTI_INCLUDE_PATH', CACTI_BASE_PATH . '/include');
define('CACTI_PLUGIN_PATH', CACTI_BASE_PATH . '/plugins');
define('CACTI_CACHE_PATH', CACTI_BASE_PATH . '/cache');		# no compat required
define('CACTI_CACHE_URL_PATH', CACTI_URL_PATH . '/cache');	# no compat required

#$config = array();									# compat
#$config['url_path'] = $url_path;					# compat
#$config['base_path']    = CACTI_BASE_PATH;			# compat
#$config['library_path'] = CACTI_BASE_PATH . '/lib';	# compat
#$config['include_path'] = CACTI_BASE_PATH . '/include';	# compat
#$config['rra_path'] 	= CACTI_BASE_PATH . '/rra';	# compat
#$config['plugin_path'] 	= CACTI_BASE_PATH . '/plugins';	# compat

#$config['cacti_server_os'] = CACTI_SERVER_OS;		# compat

/* Files that do not need http header information - Command line scripts */
$no_http_header_files = array(
	'add_device.php',
	'add_graphs.php',
	'add_perms.php',
	'add_tree.php',
	'cmd.php',
	'copy_user.php',
	'data_query_add.php',
	'data_query_list.php',
	'data_source_remove.php',
	'device_add.php',
	'device_list.php',
	'device_template_list.php',
	'device_update_template.php',
	'graph_add.php',
	'graph_list.php',
	'perms_add.php',
	'poller_commands.php',
	'poller_export.php',
	'poller_graphs_reapply_names.php',
	'poller_output_empty.php',
	'poller.php',
	'poller_reindex_devices.php',
	'query_host_cpu.php',
	'query_host_partitions.php',
	'rrd_datasource_add.php',
	'rebuild_poller_cache.php',
	'repair_database.php',
	'script_server.php',
	'sql.php',
	'ss_host_cpu.php',
	'ss_host_disk.php',
	'ss_sql.php',
	'structure_rra_paths.php',
	'tree_add.php',
	'user_copy.php',
);

/* built-in snmp support */
define('PHP_SNMP_SUPPORT', function_exists('snmpget'));

/* display ALL errors */
error_reporting(E_ALL);

/* current cacti version */
define('CACTI_VERSION', '1.0.0');
define('CACTI_WIKI_URL', 'http://docs.cacti.net/reference:100:');

/* include base modules */
//include(CACTI_LIBRARY_PATH . '/adodb/adodb.inc.php');
include(CACTI_LIBRARY_PATH . '/database.php');

/* check that the absolute necessary mysql PHP module is loaded  (install checks the rest), and report back if not */
if (! function_exists('mysql_data_seek'))
{
	die ("\n\nRequired 'mysql' PHP extension not loaded. Check your php.ini file.\n");
}

/* connect to the database server */
db_connect_real($database_hostname, $database_username, $database_password, $database_default, $database_type, $database_port, $database_ssl);

/* Check that the database has tables in it - can't use db_fetch_assoc because that uses read_config_option! */
$result = @mysql_query('show tables from ' . $database_default);
$database_empty = false;
if(! $result || mysql_num_rows($result) == 0)
{
	$database_empty = true;
}

/* initilize php session */
session_name($cacti_session_name);
session_start();

/* include additional modules */
include_once(CACTI_LIBRARY_PATH . '/functions.php');
include_once(CACTI_INCLUDE_PATH . '/global_constants.php');
include_once(CACTI_INCLUDE_PATH . '/global_language.php');
include_once(CACTI_INCLUDE_PATH . '/global_timezones.php');
include_once(CACTI_LIBRARY_PATH . '/log.php');
include_once(CACTI_INCLUDE_PATH . '/global_arrays.php');
include_once(CACTI_INCLUDE_PATH . '/global_settings.php');
include_once(CACTI_INCLUDE_PATH . '/plugins/plugin_arrays.php');
include_once(CACTI_LIBRARY_PATH . '/plugins.php');
include_once(CACTI_LIBRARY_PATH . '/html.php');
include_once(CACTI_LIBRARY_PATH . '/html_form.php');
include_once(CACTI_LIBRARY_PATH . '/html_utility.php');
include_once(CACTI_LIBRARY_PATH . '/html_validate.php');
include_once(CACTI_LIBRARY_PATH . '/variables.php');
include_once(CACTI_LIBRARY_PATH . '/auth.php');
include_once(CACTI_LIBRARY_PATH . '/ajax.php');

if(! $database_empty)
{
	// avoid running read_config_option against an empty DB - this isn't needed during the install process anyway
	include_once(CACTI_INCLUDE_PATH . '/global_form.php');
}

plugin_load_realms();
plugin_hook('config_arrays');
plugin_hook('config_settings');
plugin_hook('config_form');

if (read_config_option('require_ssl') == 'on')
{
	if (!isset($_SERVER['HTTPS']) && isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI']))
	{
		Header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '\n\n');
		exit;
	}
}

if ((! in_array(basename($_SERVER['PHP_SELF']), $no_http_header_files, true)) && ($_SERVER['PHP_SELF'] != ''))
{
	/* Sanity Check on 'Corrupt' PHP_SELF */
	if ($_SERVER['SCRIPT_NAME'] != $_SERVER['PHP_SELF'])
	{
		echo '\nInvalid PHP_SELF Path \n';
		exit;
	}

	/* we don't want these pages cached */
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: post-check=0, pre-check=0', false);

	/* IE has a problem with caching and https */
	if (isset($_SERVER['HTTP_USER_AGENT']) && !substr_count($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
	{
		header('Pragma: no-cache');
	}

	/* prevent IE from silently rejects cookies sent from third party sites. */
	header('P3P: CP=\'CAO PSA OUR\'');

	/* detect and handle get_magic_quotes */
	if (! get_magic_quotes_gpc())
	{
		function addslashes_deep($value)
		{
			if (is_array($value))
			{
				return array_map('addslashes_deep', $value);
			}
			return addslashes($value);
		}

		$_POST   = array_map('addslashes_deep', $_POST);
		$_GET    = array_map('addslashes_deep', $_GET);
		$_COOKIE = array_map('addslashes_deep', $_COOKIE);
	}

	/* make sure to start only only Cacti session at a time */
	if (! isset($_SESSION['cacti_cwd']))
	{
		$_SESSION['cacti_cwd'] = CACTI_BASE_PATH;
	}
	else
	{
		if ($_SESSION['cacti_cwd'] != CACTI_BASE_PATH)
		{
			session_unset();
			session_destroy();
		}
	}

	updateCookieChanges();
}

/* emulate 'register_globals' = 'off' if turned on */
if ((bool)ini_get('register_globals'))
{
	$not_unset = array('_GET', '_POST', '_COOKIE', '_SERVER', '_SESSION', '_ENV', '_FILES', 'database_type', 'database_default', 'database_hostname', 'database_username', 'database_password', 'config', 'colors');

	/* Not only will array_merge give a warning if a parameter is not an array, it will
	* actually fail. So we check if HTTP_SESSION_VARS has been initialised. */
	if (! isset($_SESSION)) $_SESSION = array();

	/* Merge all into one extremely huge array; unset this later */
	$input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_SESSION, $_ENV, $_FILES);

	unset($input['input']);
	unset($input['not_unset']);

	while (list($var,) = @each($input))
	{
		if (!in_array($var, $not_unset)) unset($$var);
	}

	unset($input);
}

