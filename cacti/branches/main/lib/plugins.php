<?php

function do_hook($name) {
	$data = func_get_args();
	$data = api_plugin_hook($name, $data);
	return $data;
}

function do_hook_function($name,$parm=NULL) {
	return api_plugin_hook_function($name, $parm);
}

function api_user_realm_auth($filename = '') {
	return api_plugin_user_realm_auth ($filename);
}

/**
 * This function executes a hook.
 * @param string $name Name of hook to fire
 * @return mixed $data
 */
function api_plugin_hook($name) {
	global $config, $plugin_hooks;
	$data = func_get_args();
	$ret = '';
	$p = array();

	/* order the plugin functions by system first, then followed by order */
	$result = db_fetch_assoc("SELECT 1 AS id, pc.ptype, ph.name, ph.file, ph.function, pc.sequence
		FROM plugin_hooks AS ph
		LEFT JOIN plugin_config AS pc
		ON pc.directory=ph.name
		WHERE ph.status = " . PLUGIN_STATUS_ACTIVE_NEW . " AND hook = '$name'
		AND pc.ptype = " . PLUGIN_TYPE_SYSTEM . "
		UNION
		SELECT pc.id, pc.ptype, ph.name, ph.file, ph.function, pc.sequence
		FROM plugin_hooks AS ph
		LEFT JOIN plugin_config AS pc
		ON pc.directory=ph.name
		WHERE ph.status = " . PLUGIN_STATUS_ACTIVE_NEW . " AND hook = '$name'
		AND pc.ptype <> " . PLUGIN_TYPE_SYSTEM . "
		ORDER BY ptype ASC, sequence ASC", true);

	if (count($result)) {
		foreach ($result as $hdata) {
			$p[] = $hdata['name'];
			if (file_exists(CACTI_BASE_PATH . '/plugins/' . $hdata['name'] . '/' . $hdata['file'])) {
				include_once(CACTI_BASE_PATH . '/plugins/' . $hdata['name'] . '/' . $hdata['file']);
			}
			$function = $hdata['function'];
			if (function_exists($function)) {
				$function($data);
			}
		}
	}

	/* Variable-length argument lists have a slight problem when */
	/* passing values by reference. Pity. This is a workaround.  */
	return $data;
}

function api_plugin_hook_function($name, $parm=NULL) {
	global $config, $plugin_hooks;

	$data = func_get_args();
	$ret    = $parm;
	$p = array();

	/* order the plugin functions by system first, then followed by order */
	$result = db_fetch_assoc("SELECT 1 AS id, pc.ptype, ph.name, ph.file, ph.function, pc.sequence
		FROM plugin_hooks AS ph
		LEFT JOIN plugin_config AS pc
		ON pc.directory=ph.name
		WHERE ph.status = " . PLUGIN_STATUS_ACTIVE_NEW . " AND hook = '$name'
		AND pc.ptype = " . PLUGIN_TYPE_SYSTEM . "
		UNION
		SELECT pc.id, pc.ptype, ph.name, ph.file, ph.function, pc.sequence
		FROM plugin_hooks AS ph
		LEFT JOIN plugin_config AS pc
		ON pc.directory=ph.name
		WHERE ph.status = " . PLUGIN_STATUS_ACTIVE_NEW . " AND hook = '$name'
		AND pc.ptype <> " . PLUGIN_TYPE_SYSTEM . "
		ORDER BY ptype ASC, sequence ASC", true);

	if (count($result)) {
		foreach ($result as $hdata) {
			$p[] = $hdata['name'];
			if (file_exists(CACTI_BASE_PATH . '/plugins/' . $hdata['name'] . '/' . $hdata['file'])) {
				include_once(CACTI_BASE_PATH . '/plugins/' . $hdata['name'] . '/' . $hdata['file']);
			}
			$function = $hdata['function'];
			if (function_exists($function)) {
				$ret = $function($ret);
			}
		}
	}

	/* Variable-length argument lists have a slight problem when */
	/* passing values by reference. Pity. This is a workaround.  */
	return $ret;
}

function api_plugin_db_table_create($plugin, $table, $data, $sql_install_cache=false) {
	global $config, $database_default;
	include_once(CACTI_BASE_PATH . "/lib/database.php");

	$result = db_fetch_assoc("show tables from `" . $database_default . "`") or die (mysql_error());
	$tables = array();
	foreach($result as $index => $arr) {
		foreach ($arr as $t) {
			$tables[] = $t;
		}
	}
	if (!in_array($table, $tables)) {
		$c = 0;
		$sql = 'CREATE TABLE `' . $table . "` (\n";
		foreach ($data['columns'] as $column) {
			if (isset($column['name'])) {
				if ($c > 0)
					$sql .= ",\n";
				$sql .= plugin_db_format_column_sql($column);
				$c++;
			}
		}

		/* primary keys, multi-key columns are allowed, deprecated, so we convert to new format */
		if (isset($data['primary'])) {
			$data['keys'][] = array('columns' => $data["primary"], 'primary' => true, 'name' => 'PRIMARY');
		}

		/* "normal" and "unique" keys, multi-key columns are allowed, multiple keys per run are allowed as well */
		if (isset($data['keys'])) {
			foreach ($data['keys'] as $key) {
				$sql .= ",\n " . plugin_db_format_key_sql($key);
			}
		}
		
		# close parenthesis for column and index specification
		$sql .= ') ';
		
		if (isset($data['engine'])) { # accept "engine"
			$sql .= plugin_db_format_engine_sql($data['engine']);
		}
		if (isset($data['type'])) { # ... and deprecated "type"
			$sql .= plugin_db_format_engine_sql($data['type']);
		}
		
		if (isset($data['comment'])) {
			$sql .= plugin_db_format_comment_sql($data['comment']);
		}

		if (plugin_db_execute($sql, $plugin, $sql_install_cache)) {
			db_execute("INSERT INTO `plugin_db_changes` (`plugin`, `table`, `method`) VALUES ('$plugin', '$table', 'create')");
		}
	} else {
		db_execute("INSERT INTO `plugin_db_changes` (`plugin`, `table`, `method`) VALUES ('$plugin', '$table', 'create')");
	}
}

function api_plugin_db_changes_remove($plugin) {
	// Example: api_plugin_db_changes_remove ('thold');

	$tables = db_fetch_assoc("SELECT `table` FROM plugin_db_changes WHERE plugin = '$plugin' AND method ='create'", false);
	if (count($tables)) {
		foreach ($tables as $table) {
			db_execute("DROP TABLE `" . $table['table'] . "`;");
		}
		db_execute("DELETE FROM plugin_db_changes where plugin = '$plugin' AND method ='create'", false);
	}
	$columns = db_fetch_assoc("SELECT `table`, `column` FROM plugin_db_changes WHERE plugin = '$plugin' AND method ='addcolumn'", false);
	if (count($columns)) {
		foreach ($columns as $column) {
			db_execute('ALTER TABLE `' . $column['table'] . '` DROP `' . $column['column'] . '`');
		}
		db_execute("DELETE FROM plugin_db_changes where plugin = '$plugin' AND method = 'addcolumn'", false);
	}
}

function api_plugin_db_add_column($plugin, $table, $column, $sql_install_cache=false) {
	// Example: api_plugin_db_add_column ('thold', 'plugin_config', array('name' => 'test' . rand(1, 200), 'type' => 'varchar (255)', 'NULL' => false));

	global $config, $database_default;
	include_once(CACTI_BASE_PATH . '/lib/database.php');

	$result = db_fetch_assoc('show columns from `' . $table . '`') or die (mysql_error());
	$columns = array();
	foreach($result as $index => $arr) {
		foreach ($arr as $t) {
			$columns[] = $t;
		}
	}
	if (isset($column['name']) && !in_array($column['name'], $columns)) {
		$sql = 'ALTER TABLE `' . $table . '` ADD ';
		$sql .= plugin_db_format_column_sql($column);

		if (plugin_db_execute($sql, $plugin, $sql_install_cache)) {
			db_execute("INSERT INTO plugin_db_changes (plugin, `table`, `column`, `method`) VALUES ('$plugin', '$table', '" . $column['name'] . "', 'addcolumn')");
		}
	}
}

function api_plugin_install($plugin) {
	global $config;
	include_once(CACTI_BASE_PATH . "/plugins/$plugin/setup.php");

	$exists = db_fetch_assoc("SELECT id FROM plugin_config WHERE directory = '$plugin'", false);
	if (!count($exists)) {
		db_execute("DELETE FROM plugin_config WHERE directory = '$plugin'");
	}

	$name = $author = $webpage = $version = '';
	$function = 'plugin_' . $plugin . '_version';
	if (function_exists($function)){
		$info = $function();
		$name = $info['longname'];
		$webpage = $info['homepage'];
		$author = $info['author'];
		$version = $info['version'];
	}

	# compute sequence for next item to be installed
	$sequence = db_fetch_cell("SELECT MAX(sequence) FROM plugin_config");
	$sequence++;

	# plugin type
	$ptype = plugin_is_system_plugin($plugin);

	db_execute("INSERT INTO plugin_config " .
				"(directory, name, author, webpage, version, ptype, sequence) " . 
				"VALUES " . 
				"('$plugin', '$name', '$author', '$webpage', '$version', '$ptype', '$sequence')");

	$function = 'plugin_' . $plugin . '_install';
	if (function_exists($function)){
		$function();
		$ready = api_plugin_check_config ($plugin);
		if ($ready) {
			// Set the plugin as "disabled" so it can go live
			db_execute("UPDATE plugin_config SET status = " . PLUGIN_STATUS_INSTALLED . " WHERE directory = '$plugin'");
		} else {
			// Set the plugin as "needs configuration"
			db_execute("UPDATE plugin_config SET status = " . PLUGIN_STATUS_AWAITING_CONFIGURATION . " WHERE directory = '$plugin'");
		}
	}
}

function api_plugin_uninstall($plugin) {
	global $config;
	include_once(CACTI_BASE_PATH . "/plugins/$plugin/setup.php");
	// Run the Plugin's Uninstall Function first
	$function = 'plugin_' . $plugin . '_uninstall';
	if (function_exists($function)) {
		$function();
	}
	api_plugin_remove_hooks ($plugin);
	api_plugin_remove_realms ($plugin);
	db_execute("DELETE FROM plugin_config WHERE directory = '$plugin'");
	api_plugin_db_changes_remove ($plugin);
}

function api_plugin_check_config($plugin) {
	global $config;
	include_once(CACTI_BASE_PATH . "/plugins/$plugin/setup.php");
	$function = 'plugin_' . $plugin . '_check_config';
	if (function_exists($function)) {
		return $function();
	}
	return TRUE;
}

function api_plugin_enable($plugin) {
	$ready = api_plugin_check_config ($plugin);
	if ($ready) {
		api_plugin_enable_hooks ($plugin);
		db_execute("UPDATE plugin_config SET status = " . PLUGIN_STATUS_ACTIVE_NEW . " WHERE directory = '$plugin'");
	}
}

function api_plugin_is_enabled($plugin) {
	$status = db_fetch_cell("SELECT status FROM plugin_config WHERE directory = '$plugin'", false);
	if ($status == '1')
		return true;
	return false;
}

function api_plugin_disable($plugin) {
	api_plugin_disable_hooks ($plugin);
	db_execute("UPDATE plugin_config SET status = " . PLUGIN_STATUS_INSTALLED . " WHERE directory = '$plugin'");
}

function api_plugin_register_hook($plugin, $hook, $function, $file) {
	$exists = db_fetch_assoc("SELECT id FROM plugin_hooks WHERE name = '$plugin' AND hook = '$hook'", false);
	if (!count($exists)) {
		$settings = array('config_settings', 'config_arrays', 'config_form');
		if (!in_array($hook, $settings)) {
			db_execute("INSERT INTO plugin_hooks (name, hook, function, file) VALUES ('$plugin', '$hook', '$function', '$file')");
		} else {
			db_execute("INSERT INTO plugin_hooks (name, hook, function, file, status) VALUES ('$plugin', '$hook', '$function', '$file', 1)");
		}
	}
}

function api_plugin_remove_hooks($plugin) {
	db_execute("DELETE FROM plugin_hooks WHERE name = '$plugin'");
}

function api_plugin_enable_hooks($plugin) {
	db_execute("UPDATE plugin_hooks SET status = " . PLUGIN_STATUS_ACTIVE_NEW . " WHERE name = '$plugin'");
}

function api_plugin_disable_hooks($plugin) {
	db_execute("UPDATE plugin_hooks SET status = " . PLUGIN_STATUS_NOT_INSTALLED . " WHERE name = '$plugin' AND hook != 'config_settings' AND hook != 'config_arrays' AND hook != 'config_form'");
}

function api_plugin_register_realm($plugin, $file, $display, $admin = false) {
	$exists = db_fetch_assoc("SELECT id FROM plugin_realms WHERE plugin = '$plugin' AND file = '$file'", false);
	if (!count($exists)) {
		db_execute("INSERT INTO plugin_realms (plugin, file, display) VALUES ('$plugin', '$file', '$display')");
		if ($admin) {
			$realm_id = db_fetch_assoc("SELECT id FROM plugin_realms WHERE plugin = '$plugin' AND file = '$file'", false);
			$realm_id = $realm_id[0]['id'] + 100;
			$user_id = db_fetch_assoc("SELECT id FROM user_auth WHERE username = 'admin'", false);
			if (count($user_id)) {
				$user_id = $user_id[0]['id'];
				$exists = db_fetch_assoc("SELECT realm_id FROM user_auth_realm WHERE user_id = $user_id and realm_id = $realm_id", false);
				if (!count($exists)) {
					db_execute("INSERT INTO user_auth_realm (user_id, realm_id) VALUES ($user_id, $realm_id)");
				}
			}
		}
	}
}

function api_plugin_remove_realms($plugin) {
	$realms = db_fetch_assoc("SELECT id FROM plugin_realms WHERE plugin = '$plugin'", false);
	foreach ($realms as $realm) {
		$id = $realm['id'] + 100;
		db_execute("DELETE FROM user_auth_realm WHERE realm_id = '$id'");
	}
	db_execute("DELETE FROM plugin_realms WHERE plugin = '$plugin'");
}

function api_plugin_load_realms() {
	global $user_auth_realms, $user_auth_realm_filenames;
	$plugin_realms = db_fetch_assoc("SELECT * FROM plugin_realms ORDER BY plugin, display", false);
	if (count($plugin_realms)) {
		foreach ($plugin_realms as $plugin_realm) {
			$plugin_files = explode(',', $plugin_realm['file']);
			foreach($plugin_files as $plugin_file) {
				$user_auth_realm_filenames[$plugin_file] = $plugin_realm['id'] + 100;
			}
			$user_auth_realms[$plugin_realm['id'] + 100] = $plugin_realm['display'];
		}
	}
}

function api_plugin_user_realm_auth($filename = '') {
	global $user_realms, $user_auth_realms, $user_auth_realm_filenames;
	/* list all realms that this user has access to */
	if (!isset($user_realms)) {
		if (read_config_option('global_auth') == 'on' || read_config_option('auth_method') != AUTH_METHOD_NONE) {
			$user_realms = db_fetch_assoc("select realm_id from user_auth_realm where user_id=" . $_SESSION["sess_user_id"], false);
			$user_realms = array_rekey($user_realms, "realm_id", "realm_id");
		}else{
			$user_realms = $user_auth_realms;
		}
	}
	if ($filename != '') {
		if (isset($user_realms[$user_auth_realm_filenames{basename($filename)}]))
			return TRUE;
	}
	return FALSE;
}

function plugin_config_arrays() {
	/* empty, cause PIA is now part of core code */
}

/**
 * add a new menue header, changes the global $menu
 * @param string $menu_id		- id for the div of the menu entry	
 * @param string $menue_header	- translated header description
 */
function plugin_menu_add($menu_id, $menu_header) {
	global $menu;
	$menu[$menu_id]["name"] = $menu_header;	
}

/**
 * add an array of menu items to an existing menu specified by the id
 * @param string $menu_id
 * @param array $menue_items	- array of menu items
 * 									array(
 * 										<php module> => __("<Item Description>")
 * 										<php module> => __("<Item Description>")
 * 										...
 * 									)
 */
function plugin_menu_item_add($menu_id, $menu_items) {
	global $menu;
	$menu[$menu_id]["items"] += $menu_items;	
}

function plugin_draw_navigation_text($nav) {
	/* nav text moved to functions.php */
	return $nav;
}

function plugin_is_system_plugin($plugin) {
	require(CACTI_BASE_PATH . "/include/plugins/plugin_arrays.php");

	$system_plugin = (in_array($plugin, $plugins_system));
cacti_log(__FUNCTION__ . " plugin: $plugin result: $system_plugin", false, "TEST");	
	switch ($system_plugin) {
		case true:
			$plugin_type = PLUGIN_TYPE_SYSTEM;
			break;
		default:
			$plugin_type = PLUGIN_TYPE_GENERAL;
	}
cacti_log(__FUNCTION__ . " result: $plugin_type", false, "TEST");	
	return $plugin_type;
}

/**
 * upgrades or creates a table
 * @param string $plugin			- name of the plugin
 * @param string $table				- new/existing table name
 * @param array $data				- description of the new table
 * @param bool $sql_install_cache	- when using install_cache, the results will be presented to the user
 * @param bool $drop_items			- do you want to drop "superfluous" keys and columns?
 * 	$data['columns'][] = array('name' => 'id', 				'type' => 'mediumint(8)',	'unsigned' => 'unsigned', 'NULL' => false, 'auto_increment' => true);
	$data['columns'][] = array('name' => 'name',		 	'type' => 'varchar(100)'							, 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'description',		'type' => 'varchar(255)'							, 'NULL' => true , 'default' => 'NULL');
	$data['columns'][] = array('name' => 'object_type',		'type' => 'int(8)',			'unsigned' => 'unsigned', 'NULL' => false, 'default' => 0);
	$data['columns'][] = array('name' => 'enabled', 		'type' => 'int(1)',  		'unsigned' => 'unsigned', 'NULL' => false, 'default' => 1);
	$data['columns'][] = array('name' => 'updated_when',	'type' => 'datetime'								, 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'updated_by', 		'type' => 'varchar(100)'							, 'NULL' => false, 'default' => '');
	$data['columns'][] = array('name' => 'created_when', 	'type' => 'datetime'								, 'NULL' => false, 'default' => '0000-00-00 00:00:00');
	$data['columns'][] = array('name' => 'created_by', 		'type' => 'varchar(100)'							, 'NULL' => false, 'default' => '');
	$data['primary'] = 'id';	// deprecated
	$data['keys'][] = array('columns' => 'id', 'primary' => true, 'type' => 'BTREE', 'constraint' => 'symbol');	// PRIMARY INDEX USING BTREE
	$data['keys'][] = array('name' => 'constraint_index', 'columns' => 'name, object_type', 'unique' => true);	// multi-level UNIQUE index
	$data['keys'][] = array('name' => 'name', 'columns' => 'name');												// plain INDEX
	$data['keys'][] = array('name' => 'object_type', 'columns' => 'object_type');
	$data['engine'] = 'MyISAM';																					// ENGINE
	$data['comment'] = 'Authorization Control';

 */
function api_plugin_upgrade_table($plugin, $table, $data, $sql_install_cache=false, $drop_items=false) {
	global $database_default;
	include_once(CACTI_BASE_PATH . '/lib/database.php');
	
	/* which tables are defined right now? */
	$result = db_fetch_assoc('SHOW TABLES FROM `' . $database_default . '`') or die (mysql_error());
	$tables = array();
	foreach($result as $index => $arr) {
		foreach ($arr as $t) {
			$tables[] = $t;
		}
	}
	
	/* is the table already defined ... */
	if (in_array($table, $tables)) {
		/* ... then upgrade columns */
		if (isset($data['columns']) && sizeof($data['columns'])) { 
			api_plugin_upgrade_columns($plugin, $table, $data['columns'], $sql_install_cache, $drop_items);
		}

		/* convert old format for PRIMARY INDEX to new one */
		if (isset($data['primary'])) {
			/* add the primary key to the array of keys
			 * execute the sql below together with other keys */
			$data['keys'][] = array('columns' => $data["primary"], 'primary' => true, 'name' => 'PRIMARY');
		}

		// check indexes ---------------------------------------------------------------------------------
		if (isset($data['keys']) && sizeof($data['keys'])) { 
			api_plugin_upgrade_keys($plugin, $table, $data['keys'], $sql_install_cache, $drop_items);
		}
		
		// Check Engine ---------------------------------------------------------------------------------
		if (isset($data['engine'])) { # accept "engine"
			api_plugin_upgrade_engine($plugin, $table, $data['engine'], $sql_install_cache);
		}
		if (isset($data['type'])) { # ... as well as "type" (deprecated)
			api_plugin_upgrade_engine($plugin, $table, $data['type'], $sql_install_cache);
		}
		
		// Check Comment ---------------------------------------------------------------------------------
		if (isset($data['comment'])) { 
			api_plugin_upgrade_comment($plugin, $table, $data['comment'], $sql_install_cache);
		}
		
		
	} else {
		// Table does not exist, so create it
		api_plugin_db_table_create($plugin, $table, $data, $sql_install_cache);
	}
}


/**
 * add, change, drop columns of a given table
 * @param string $plugin			- name of the plugin/cacti version
 * @param string $table				- name of the table
 * @param array $columns			- column array
 * @param bool $sql_install_cache	- when using install_cache, the results will be presented to the user
 * @param bool $drop_columns		- do you want to drop "superfluous" columns?
 */
function api_plugin_upgrade_columns($plugin, $table, $columns, $sql_install_cache=false, $drop_columns=false) {
	global $database_default;
	
	/* we will create ALTER statements for the given table only */
	$table_sql = 'ALTER TABLE `' . $table . '` ';

	/* get the columns of that table */
	$result = db_fetch_assoc('SHOW COLUMNS FROM `' . $table . '` FROM `' . $database_default . '` ') or die (mysql_error());

	/* work on new/changed columns */
	$sql_array = plugin_db_check_columns($result, $columns, $drop_columns);
	if (sizeof($sql_array)) {
		foreach($sql_array as $sql) {
			plugin_db_execute($table_sql . $sql, $plugin, $sql_install_cache);
		}
	}
}


/**
 * add, change, drop keys of a given table
 * @param string $plugin			- name of the plugin/cacti version
 * @param string $table				- name of the table
 * @param array $keys				- key array
 * @param bool $sql_install_cache	- when using install_cache, the results will be presented to the user
 * @param bool $drop_keys			- do you want to drop "superfluous" keys?
 */
function api_plugin_upgrade_keys($plugin, $table, $keys, $sql_install_cache=false, $drop_keys=false) {
	global $database_default;
	
	/* we will create ALTER statements for the given table only */
	$table_sql = 'ALTER TABLE `' . $table . '` ';
	
	$result = db_fetch_assoc('SHOW INDEX FROM `' . $table . '` FROM `' . $database_default . '`') or die (mysql_error());
	/* work on new/changed indexes */
	if (isset($keys) && sizeof($keys)) {
		$sql_array = plugin_db_check_keys($result, $keys, $drop_keys);
		if (sizeof($sql_array)) {
			/* temporarily disable KEYS to improve speed for new indexes */
			plugin_db_execute($table_sql . ' DISABLE KEYS', $plugin, $sql_install_cache);
			foreach($sql_array as $sql) {
				plugin_db_execute($table_sql . $sql, $plugin, $sql_install_cache);
			}
			/* now enable KEYS again */
			plugin_db_execute($table_sql . ' ENABLE KEYS', $plugin, $sql_install_cache);
		}
	}
}


/**
 * upgrade type/engine of a given table
 * @param string $plugin			- name of the plugin/cacti version
 * @param string $table				- name of the table
 * @param array $engine				- requested type/engine of table
 * @param bool $sql_install_cache	- when using install_cache, the results will be presented to the user
 */
function api_plugin_upgrade_engine($plugin, $table, $engine, $sql_install_cache=false) {
	global $database_default;
	
	/* we will create ALTER statements for the given table only */
	$table_sql = 'ALTER TABLE `' . $table . '` ';
	
	/* check current TABLE STATUS to fetch existing type/engine */
	$result = db_fetch_row('SHOW TABLE STATUS FROM `' . $database_default . '` WHERE Name LIKE "' . $table . '"') or die (mysql_error());
	
	/* upgrade in case of mismatch */
	if (isset($result['Engine']) && strtolower($engine) != strtolower($result['Engine'])) {
		plugin_db_execute($table_sql . plugin_db_format_engine_sql($engine), $plugin, $sql_install_cache);
	}

}


/**
 * upgrade comment of a given table
 * @param string $plugin			- name of the plugin/cacti version
 * @param string $table				- name of the table
 * @param array $comment			- requested comment for table
 * @param bool $sql_install_cache	- when using install_cache, the results will be presented to the user
 */
function api_plugin_upgrade_comment($plugin, $table, $comment, $sql_install_cache=false) {
	global $database_default;
	
	/* we will create ALTER statements for the given table only */
	$table_sql = 'ALTER TABLE `' . $table . '` ';
	
	/* check current TABLE STATUS to fetch existing type/engine */
	$result = db_fetch_row('SHOW TABLE STATUS FROM `' . $database_default . '` WHERE Name LIKE "' . $table . '"') or die (mysql_error());
	
	/* upgrade in case of mismatch */
	if (isset($result['Comment']) && strtolower($comment) != strtolower($result['Comment'])) {
		plugin_db_execute($table_sql . plugin_db_format_comment_sql($comment), $plugin, $sql_install_cache);
	}

}

/**
 * rename a given table, if exists
 * @param string $plugin			- name of the plugin/cacti version
 * @param string $old_table			- name of the old table
 * @param string $new_table			- requested new name for table
 * @param bool $sql_install_cache	- when using install_cache, the results will be presented to the user
 */
function api_plugin_rename_table($plugin, $old_table, $new_table, $sql_install_cache=false) {
	global $database_default;
	include_once(CACTI_BASE_PATH . '/lib/database.php');
	
	/* which tables are defined right now? */
	$result = db_fetch_assoc('SHOW TABLES FROM `' . $database_default . '`') or die (mysql_error());
	$tables = array();
	foreach($result as $index => $arr) {
		foreach ($arr as $t) {
			$tables[] = $t;
		}
	}
	
	/* is the table already defined ... */
	if (in_array($old_table, $tables)) {
		plugin_db_execute('RENAME TABLE `' . $old_table . '` TO `' . $new_table . '`', $plugin, $sql_install_cache);
	}
}

/**
 * rename a given column, if exists
 * @param string $plugin			- name of the plugin/cacti version
 * @param string $old_column		- name of the old column
 * @param array $new_column			- requested data for new column
 * @param bool $sql_install_cache	- when using install_cache, the results will be presented to the user
 */
function api_plugin_rename_column($plugin, $table, $old_column, $new_column, $sql_install_cache=false) {
	global $database_default;
	include_once(CACTI_BASE_PATH . '/lib/database.php');

	/* get the columns of that table */
	$result = db_fetch_assoc('SHOW COLUMNS FROM `' . $table . '` FROM `' . $database_default . '` ') or die (mysql_error());
	$cols = array();
	foreach($result as $index => $t) {
		$cols[$t['Field']] = $t;
	}
	
	/* is the column already defined ... */
	if (array_key_exists($old_column, $cols)) {
		plugin_db_execute('ALTER TABLE `' . $table . '` CHANGE `' . $old_column . '` ' . plugin_db_format_column_sql($new_column), $plugin, $sql_install_cache);
	}
}

/**
 * compares existing columns to new ones and creates required SQL statements
 * @param array $result		- result of SHOW COLUMNS FROM for given table
 * @param array $columns	- new columns
 * @param bool $drop_columns- do you want to drop "superfluous" columns?
 * @return array			- resulting SQL, one statement per array index
 */
function plugin_db_check_columns($result, $columns, $drop_columns=false) {

	$sql_array = array();
	
	$cols = array();
	foreach($result as $index => $t) {
		$cols[$t['Field']] = $t;
	}

	/* loop through all new columns/fields and check attributes */
	foreach ($columns as $column) {
		if (isset($column['name'])) {
			/* is this column already present? */
			if (isset($cols[$column['name']])) {
				$c = $cols[$column['name']];
				/* remember if anything has to be changed in $ok */
				$ok = true;
				
				/* reformatting of certain attributes */
				if (strstr($c['Type'], 'unsigned')) {
					$c['unsigned'] = true;
					$c['Type'] = trim(str_replace('unsigned', '', $c['Type']));
				}
				$c['Type'] = str_replace(' ', '', $c['Type']);
				$column['type'] = str_replace(' ', '', $column['type']);
				/* various checks for column attributes */
				if (strtolower($column['type']) != strtolower($c['Type'])) {
					$ok = FALSE;
				}
				if (isset($column['NULL']) && (($column['NULL'] == FALSE && $c['Null'] != 'NO') || ($column['NULL'] == TRUE && $c['Null'] != 'YES'))) {
					$ok = FALSE;
				}
				if (isset($column['auto_increment']) && ($column['auto_increment'] == 1 && isset($c['Extra']) && $c['Extra'] != 'auto_increment')) {
					$ok = FALSE;
				} else if (isset($c['Extra']) && $c['Extra'] == 'auto_increment' && !isset($column['auto_increment'])) {
					$ok = FALSE;
				}
				if (isset($column['unsigned']) && $column['unsigned'] != $c['unsigned']) {   #todo: undefined index: unsigned
					$ok = FALSE;
				}
				if (isset($column['default']) && $column['default'] != $c['Default']) {
					$ok = FALSE;
				}
				
				/* any change required? */
				if (!$ok) {
					$sql_array[] = ' CHANGE `' . $column['name'] . '` ' . plugin_db_format_column_sql($column);
				}
			} else {
				// Column does not yet exist
				$sql_array[] = ' ADD ' . plugin_db_format_column_sql($column);
			}
		}
	}

	if ($drop_columns) {
		// Find extra columns in the Database ------------------------------------------------------------
		$cols = array();
		foreach($result as $index => $t) {
			$cols[$t['Field']] = $t;
		}
		foreach ($cols as $c) {
			$found = FALSE;
			foreach ($columns as $d) {
				if ($c['Field'] == $d['name']) {
					$found = true;
				}
			}
			if ($found == FALSE) {
				// Extra Column in the Table has to be deleted
				$sql_array[] = ' DROP `' . $c['Field'] . '`';
			}
		}
	}
	
	return $sql_array;
}


/**
 * compares existing keys to new ones and creates required SQL statements
 * @param array $result		- result of SHOW INDEXES for given table
 * @param array $keys		- new keys
 * @param bool $drop_keys	- do you want to drop "superfluous" keys?
 * @return array			- resulting SQL, one statement per array index
 */
function plugin_db_check_keys($result, $keys, $drop_keys=false) {

	$sql_array = array();

	if (isset($keys)) {
		foreach ($keys as $key) {
			/* always make the 'name' = 'PRIMARY' for a primary key
			 * in case the user has chosen to provide his own name
			 */
			if (isset($key['primary']) && $key['primary']) {
				$key['name'] = 'PRIMARY';
			}

			/* key column may hold more than one table column, e.g. "INDEX 'f1' ('f1', 'f2', 'f3')"
			 * so we have to explode them to compare against "SHOW INDEX FROM table"
			 */
			$key_cols = explode(',', str_replace(' ', '', $key['columns']));

			/* now try to find the "new index column" among the already defined indexes */
			foreach($key_cols as $key_col) {
				$found = false;
				reset($result);
				foreach($result as $idx => $value) {
					if ($value['Key_name'] == $key['name'] &&		// name of the index matches
						$value['Column_name'] == $key_col) {		// column of index matches

						/* INDEX already exists
						 * add all columns from the user definition
						 */
						$result[$idx]['name'] 		= $key['name'];
						$result[$idx]['columns'] 	= $key['columns'];	// save ALL columns!
						$result[$idx]['primary'] 	= (isset($key['primary']) ? $key['primary'] : false);
						$result[$idx]['unique'] 	= (isset($key['unique']) ? $key['unique'] : false);
						$result[$idx]['type'] 		= (isset($key['type']) ? $key['type'] : '');
						/* if any of the supported attributes deviate from current setting, mark entry as 'update required'
						 * this statement will have to be extended if some new attribute (collation, comment, ...)
						 * has to be supported */
						$result[$idx]['update']		= ($result[$idx]['Non_unique'] != $result[$idx]['unique']) &&
														(($result[$idx]['Key_name'] == 'PRIMARY') != $result[$idx]['primary']) &&
														(strtolower($result[$idx]['Index_type']) != strtolower($result[$idx]['type']));
						$found = true;
						continue;
					}
				}
				/* if the requested index is not yet available, add a new record */
				if (!$found) {
					/* For new entries, use the name of the new index $index['name'] as the array index.
					 * For new multi-level indexes, this will result in overwriting the
					 * same array index for each index column.
					 * Only 'Column_name' varies through all index columns which is quite ok
					 * for our purpose
					 */
					$new 							= $key['name'];
					$result[$new]['Key_name'] 		= $key['name'];
					$result[$new]['Column_name']	= $key_col;
					$result[$new]['Seq_in_index']	= 1;
					$result[$new]['name'] 			= $key['name'];
					$result[$new]['columns'] 		= $key['columns'];		// save ALL columns!
					$result[$new]['primary'] 		= (isset($key['primary']) ? $key['primary'] : false);
					$result[$new]['unique'] 		= (isset($key['unique']) ? $key['unique'] : false);
					$result[$new]['type'] 			= (isset($key['type']) ? $key['type'] : '');
					$result[$new]['new']			= true;
				}
			}
		}
		
		/* now scan through the extended index array
		 * update:  all items marked this way require an update (DROP/ADD)
		 * new:     all those items are new
		 * nothing: item exists already and requires no change
		 * There's something special with multi-column indexes
		 * As we will have to add a multi-column index using a single ADD INDEX statement,
		 * all columns were saved to $result[]['columns'] to provide the format we require
		 * for the sql command.
		 * To avoid ADDing this index more than once, we will check for 'Seq_in_index === 1'
		 * and only act in this case
		 */
		reset($result);
		foreach($result as $index) {
			if ($index['Seq_in_index'] == 1) {
				/* act only on these items as explained above */
				if (isset($index['update'])) {
					if ($index['update']) {
						/* there is no CHANGE INDEX, so we have to DROP INDEX ... */
						$sql_array[] = ' DROP KEY `' . $index['Key_name'] . '`';
						/* ... and ADD INDEX again */
						$sql_array[] = ' ADD' . plugin_db_format_key_sql($index);
					} else {
						/* this is an index that already exists and does NOT require an update */
					}
				} elseif (isset($index['new']) && $index['new']) {
					$sql_array[] = ' ADD' . plugin_db_format_key_sql($index);
				} elseif ($drop_keys) {
					/* neither
					 * - an existing index without update
					 * - an existing index with update
					 * - a new index
					 * so apparently this is a "superfluous" index
					 * and we'll have to drop it
					 */
					$sql_array[] = ' DROP KEY `' . $index['Key_name'] . '`';
				}
			} else {
				/* do nothing, because this is a follow-up entry of a 
				 * multi-level index
				 */
			}
		}
	}
	return $sql_array;
}


/**
 * create sql for a table column
 * @param array $column	 - column attributes for a given sql column
 * @return string 		 - sql derived from data structure
 */
function plugin_db_format_column_sql($column) {
	
	/* we require at least a name ... */
	if (isset($column['name'])) {
		$sql = '`' . $column['name'] . '`';
	} else return '';
		
	/* ... and some more data */
	if (isset($column['type']))
		$sql .= ' ' . $column['type'];
	if (isset($column['unsigned']))
		$sql .= ' unsigned';
	if (isset($column['NULL']) && $column['NULL'] == false)
		$sql .= ' NOT NULL';
	if (isset($column['NULL']) && $column['NULL'] == true && !isset($column['default']))
		$sql .= ' default NULL';
	if (isset($column['default']))
		$sql .= ' default ' . (is_numeric($column['default']) ? $column['default'] : "'" . $column['default'] . "'");
	if (isset($column['auto_increment']))
		$sql .= ' auto_increment';
	if (isset($column['after']))
		$sql .= ' AFTER ' . $column['after'];

	return $sql;
}


/**
 * create sql for adding a [PRIMARY|UNIQUE] KEY/INDEX
 * @param array $key		- attributes for the given key
 * @return string			- sql
 */
function plugin_db_format_key_sql($key) {

	/* take care of special "switches" 
	 * a KEY may either be a 
	 * - PRIMARY KEY (no UNIQUE allowed)
	 * - UNIQUE KEY (no PRIMARY allowed)
	 * - "normal" KEY */
	if (isset($key['unique']) && $key['unique']) {
		$sql = " UNIQUE KEY";
	} elseif (isset($key['primary']) && $key['primary']) {
		$sql = " PRIMARY KEY";
	} else {
		$sql = " KEY";
	}
	
	/* key name given, not required for a PRIMARY KEY */
	if (isset($key['name']) && !(isset($key['primary']) && $key['primary'])) {
		$sql .= " `" . $key['name'] . '` ';
	}			
	
	/* key type given */
	if (isset($key['type']) && strlen($key['type'])) {
		$sql .= " USING " . $key['type'];
	}			
	
	/* column specification requires parenthesis */
	$sql .= ' (';
	
	if (isset($key['columns'])) {
		/* remove blanks */
		$no_blanks = str_replace(" ", "", $key['columns']);
		/* add tics to columns names */
		$sql .= "`" . str_replace(",", "`, `", $no_blanks) . '`';
	}
	
	$sql .= ')';
	
	return $sql;
}


/**
 * create sql to define the table type/engine, e.g. MEMORY, MYISAM, ...
 * @param string $engine 	- the storage type of the table
 * @return string		- sql
 */
function plugin_db_format_engine_sql($engine) {
	
	$sql = '';
	/* use ENGINE instead of TYPE as the latter is deprecated since MySQL 5.1 */
	if (isset($engine)) {
		$sql .= ' ENGINE=' . $engine;
	}			
	return $sql;
}

/**
 * create sql to define the table comment
 * @param string $comment 	- the comment for the table
 * @return string			- sql
 */
function plugin_db_format_comment_sql($comment) {
	
	$sql = '';

	if (isset($comment)) {
		$sql .= " COMMENT='" . $comment . "'";
	}
		
	return $sql;
}

/**
 * execute the SQL; if required, cache the SQL for showing it to the user
 * (in case of cacti upgrade)
 * @param string $sql				- the sql to be executed
 * @param string $plugin			- the plugin/version
 * @param bool $sql_install_cache	- true, when caching is required
 */
function plugin_db_execute($sql, $plugin='', $sql_install_cache=false) {
	
	if ($sql_install_cache) {
		return db_install_execute($plugin, $sql);
	} else {
		return db_execute($sql);
	}	
}
