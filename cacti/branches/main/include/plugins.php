<?php

/*
 * Copyright (c) 1999-2005 The SquirrelMail Project Team (http://squirrelmail.org)
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 */

function use_plugin ($name) {
	global $config;
	if (file_exists(CACTI_BASE_PATH . "/plugins/$name/setup.php")) {
		include_once(CACTI_BASE_PATH . "/plugins/$name/setup.php");
		$function = "plugin_init_$name";
		if (function_exists($function)) {
			$function();
		}
	}
}

require(CACTI_BASE_PATH . "/include/plugins/plugin_arrays.php");

$oldplugins = read_config_option('oldplugins');
if (strlen(trim($oldplugins))) {
	$oldplugins = explode(',', $oldplugins);
	$plugins    = array_merge($plugins, $oldplugins);
}

/* On startup, register all plugins configured for use. */
if (isset($plugins) && is_array($plugins)) {
	foreach ($plugins as $name) {
		use_plugin($name);
	}
}
