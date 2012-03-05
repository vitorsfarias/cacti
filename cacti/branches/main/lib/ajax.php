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

function ajax_get_data_dd_menus() {
	if (!isset($_GET['cacti_dd_menu'])) {
		$_GET['cacti_dd_menu'] = '';
	}

	switch (get_request_var('cacti_dd_menu')) {
		case 'graph_options':

			$output	= "<h6><a id='changeGraphState' onClick='changeGraphState()' href='#'>Unlock/Lock</a></h6>";
			$output .= "<h6><a href='" . htmlspecialchars('graphs.php?action=edit&id=' . $_GET["graph_id"] . "&debug=" . (isset($_SESSION["graph_debug_mode"]) ? "0" : "1")) . "'>" . __("Turn") . " <strong>" . (isset($_SESSION["graph_debug_mode"]) ? __("Off") : __(CHECKED)) . "</strong> " . __("Debug Mode") . "</a></h6>";

			if (!empty($_GET["graph_template_id"])) {
				$output .= "<h6><a href='" . htmlspecialchars('graph_templates.php?action=edit&id=' . $_GET["graph_template_id"] ) . "'>" . __("Edit Template") . "</a></h6>";
			}
			if (!empty($_GET["device_id"])) {
				$output .= "<h6><a href='" . htmlspecialchars('devices.php?action=edit&id=' . $_GET["device_id"] ) . "'>" . __("Edit Host") . "</a></h6>";
			}
			break;

		case 'data_source_options':

			$output = "<h6><a id='changeDSState' onClick='changeDSState()' href='#'>Unlock/Lock</a></h6>";
			$output .= "<h6><a href='" . htmlspecialchars('data_sources.php?action=data_source_toggle_status&id=' . $_GET["data_source_id"] . '&newstate=' . $_GET["newstate"] ) . "'>" . (($_GET["newstate"]) ? __("Disable") : __("Enable")) . "</a></h6>";
			$output .= "<h6><a href='" . htmlspecialchars('data_sources.php?action=edit&id=' . $_GET["data_source_id"] . '&debug=' . (isset($_SESSION["ds_debug_mode"]) ? "0" : "1")) . "'>" . __("Turn") . " <strong>" . (isset($_SESSION["ds_debug_mode"]) ? __("Off") : __(CHECKED)) . "</strong> " . __("Debug Mode") . "</a></h6>";
			$output .= "<h6><a href='" . htmlspecialchars('data_sources.php?action=edit&id=' . $_GET["data_source_id"] . '&info=' . (isset($_SESSION["ds_info_mode"]) ? "0" : "1")) . "'>" . __("Turn") . " <strong>" . (isset($_SESSION["ds_info_mode"]) ? __("Off") : __(CHECKED)) . "</strong> " . __("RRD Info Mode") . "</a></h6>";

			if (!empty($_GET["data_template_id"])) {
				$output .= "<h6><a href='" . htmlspecialchars('data_templates.php?action=edit&id=' . $_GET["data_template_id"]) . "'>" . __("Edit Data Source Template") . "</a></h6>";
			}
			if (!empty($_GET["device_id"])) {
				$output .= "<h6><a href='" . htmlspecialchars('devices.php?action=edit&id=' . $_GET["device_id"]) . "'>" . __("Edit Host") . "</a></h6>";
			}
			break;

		case 'device_options':

			if (!empty($_GET["device_id"])) {
				$output =  "<h6><a href='" . htmlspecialchars('graphs.php?device_id=' . $_GET["device_id"] . '&template_id=-1&rows=-1&filter=') . "'>" . __("Graph Management") . "</a></h6>";
				$output .= "<h6><a href='" . htmlspecialchars('data_sources.php?device_id=' . $_GET["device_id"] . '&template_id=-1&rows=-1&method_id=-1&filter=') . "'>" . __("Data Source Management") . "</a></h6>";
				$output .= "<h6><a href='" . htmlspecialchars('graph_view.php?action=preview&device_id=' . $_GET["device_id"] . '&graph_template_id=0&filter=') . "'>" . __("Graph Preview") . "</a></h6>";
				$output .= "<h6><a href='" . htmlspecialchars('graph_view.php?action=list&device_id=' . $_GET["device_id"] . '&graph_template_id=0&filter=') . "'>" . __("Graph List View") . "</a></h6>";
			}
			break;

		default:
			$output = "";
			break;
	}
	print $output;

	plugin_hook_function('start_box_menu', $_GET['cacti_dd_menu']);
}

function ajax_get_data_templates() {
	/* input validation */
	if (isset($_REQUEST["q"])) {
		$q = strtolower(sanitize_search_string(get_request_var("q")));
	} else {
		return;
	}

	$sql = "SELECT
		id,
		name
		FROM data_template
		WHERE LOWER(name) LIKE '%$q%'
		ORDER BY name";

	$templates = db_fetch_assoc($sql);

	if (sizeof($templates) > 0) {
		foreach ($templates as $template) {
			print $template["name"] . "|" . $template["id"] . "\n";
		}
	}
}

function ajax_get_devices_brief() {
	/* input validation */
	if (isset($_REQUEST["term"])) {
		/* jQuery UI autocomplete passes filter string as "term" */
		$q = strtolower(sanitize_search_string(get_request_var("term")));
	} else return;

	/* first, get the device policy for current user */
	$device_perms = db_fetch_cell("SELECT policy_devices FROM user_auth WHERE id=" . $_SESSION["sess_user_id"]);

	/* build sql for fetching devices
	 * take into account, whether we have an ALLOW rule == list includes all allowed devices
	 * or a DENY rule == list includes all denied devices; if empty == all devices are allowed 
	 */
	if ($device_perms == AUTH_CONTROL_DATA_POLICY_ALLOW) {
		/* this is a ALLOW SELECTED DEVICES permission type */
		$sql = "SELECT id, description as value" .
				"FROM device " .
				"WHERE (hostname LIKE '%$q%' " .
				"OR description LIKE '%$q%') " .
				"AND id IN (SELECT item_id FROM user_auth_perms WHERE user_auth_perms.type=3 AND user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") " .
				"ORDER BY description,hostname";
	}else{
		/* this is a DENY SELECTED DEVICES permission type */
		$sql = "SELECT id, description as value " .
				"FROM device " .
				"WHERE (hostname LIKE '%$q%' " .
				"OR description LIKE '%$q%') " .
				"AND id NOT IN (SELECT item_id FROM user_auth_perms WHERE user_auth_perms.type=3 AND user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") " .
				"ORDER BY description,hostname";
	}

	/* fetch all matching devices */
	$devices = db_fetch_assoc($sql);
	/* we need an explicit list entry to list any device == effetively removing the device filter 
	 * this has to match the id required by the SQL to display any device 
	 * see $table->page_variables for the device_id "default" value */
	array_unshift($devices, array("id" => "-1", "value" => __("Any")));

	if (sizeof($devices) > 0) {
		/* pay attention to what fields are expected by the autocomplete select function!
		 * we now provide "id" and "description as value" */
		print json_encode($devices);
	}
}

function ajax_get_devices_detailed() {
	/* input validation */
	if (isset($_REQUEST["term"])) {
		/* jQuery UI autocomplete passes filter string as "term" */
		$q = strtolower(sanitize_search_string(get_request_var("term")));
	} else return;

	/* first, get the device policy for current user */
	$device_perms = db_fetch_cell("SELECT policy_devices FROM user_auth WHERE id=" . $_SESSION["sess_user_id"]);

	/* build sql for fetching devices
	 * take into account, whether we have an ALLOW rule == list includes all allowed devices
	 * or a DENY rule == list includes all denied devices; if empty == all devices are allowed 
	 */
	if ($device_perms == AUTH_CONTROL_DATA_POLICY_ALLOW) {
		$sql = "SELECT id, CONCAT_WS('',description,' (',hostname,')') as value
			FROM device
			WHERE (hostname LIKE '%$q%'
			OR description LIKE '%$q%')
			AND id IN (SELECT item_id FROM user_auth_perms WHERE user_auth_perms.type=3 AND user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ")
			ORDER BY description,hostname";
	}else{
		$sql = "SELECT id, CONCAT_WS('',description,' (',hostname,')') as value
			FROM device
			WHERE (hostname LIKE '%$q%'
			OR description LIKE '%$q%')
			AND id NOT IN (SELECT item_id FROM user_auth_perms WHERE user_auth_perms.type=3 AND user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ")
			ORDER BY description,hostname";
	}

	/* fetch all matching devices */
	$devices = db_fetch_assoc($sql);
	/* we need an explicit list entry to list any device == effetively removing the device filter 
	 * this has to match the id required by the SQL to display any device 
	 * see $table->page_variables for the device_id "default" value */
	array_unshift($devices, array("id" => "-1", "value" => __("Any")));

	if (sizeof($devices) > 0) {
		/* pay attention to what fields are expected by the autocomplete select function!
		 * we now provide "id" and "description as value" */
		print json_encode($devices);
	}
}

function ajax_get_form_dropdown() {
	/* input validation */
	if (isset($_REQUEST["q"])) {
		$q = sanitize_search_string(get_request_var("q"));
	} else return;

	if (isset($_REQUEST["sql"])) {
		$sql = base64_decode(get_request_var("sql"));
	} else return;

	if ($asname_pos = strpos(strtoupper($sql), "AS NAME")) {
		$name_qry = substr($sql, 6, $asname_pos-6);
		cacti_log($name_qry);
	}else{
		$name_qry = "name";
	}

	if ($where_pos = strpos(strtoupper($sql), "WHERE")) {
		$sql = substr($sql, 0, $where_pos+5) . " LOWER($name_qry) LIKE '%$q%' AND " . substr($sql, $where_pos+5);
	}elseif ($orderby_pos = strpos(strtoupper($form_data), "ORDER BY")) { # TODO $form_data is not defined
		$sql = substr($sql, 0, $orderby_pos) . " AND LOWER($name_qry) LIKE '%$q%' " . substr($sql, $orderby_pos);
	}else{
		$sql = $sql . " AND LOWER($name_qry) LIKE '%$s%'";
	}

	$entries = db_fetch_assoc($sql);

	if (sizeof($entries) > 0) {
		foreach ($entries as $entry) {
			print $entry["name"] . "|" . $entry["id"] . "\n";
		}
	}
}

function ajax_get_graph_templates()  {
	/* input validation */
	if (isset($_REQUEST["term"])) {
		/* jQuery UI autocomplete passes filter string as "term" */
		$q = strtolower(sanitize_search_string(get_request_var("term")));
	} else return;

	/* first, get the template policy for current user */
	$template_perms = db_fetch_cell("SELECT policy_graph_templates FROM user_auth WHERE id=" . $_SESSION["sess_user_id"]);

	/* build sql for fetching templates
	 * take into account, whether we have an ALLOW rule == list includes all allowed templates
	 * or a DENY rule == list includes all denied templates; if empty == all templates are allowed 
	 */
	if ($template_perms == AUTH_CONTROL_DATA_POLICY_ALLOW) {
		$sql = "SELECT
			id,
			name as value
			FROM graph_templates
			WHERE id IN (SELECT item_id FROM user_auth_perms WHERE user_auth_perms.type=4 AND user_auth_perms.user_id=". $_SESSION["sess_user_id"] . ")
			AND (name LIKE '%$q%')
			ORDER BY name";
	}else{
		$sql = "SELECT
			id,
			name as value
			FROM graph_templates
			WHERE id NOT IN (SELECT item_id FROM user_auth_perms WHERE user_auth_perms.type=4 AND user_auth_perms.user_id=". $_SESSION["sess_user_id"] . ")
			AND (name LIKE '%$q%')
			ORDER BY name";
	}

	/* fetch all matching templates */
	$templates = db_fetch_assoc($sql);
	/* we need an explicit list entry to list any template == effetively removing the template filter 
	 * this has to match the id required by the SQL to display any template 
	 * see $table->page_variables for the template_id "default" value */
	array_unshift($templates, array("id" => "-1", "value" => __("Any")));

	if (sizeof($templates) > 0) {
		/* pay attention to what fields are expected by the autocomplete select function!
		 * we now provide "id" and "description as value" */
		print json_encode($templates);
	}
}

function ajax_get_graph_tree_content() {
	include_once(CACTI_BASE_PATH . "/include/global.php");
	include_once(CACTI_BASE_PATH . "/lib/functions.php");
	include_once(CACTI_BASE_PATH . "/lib/html_tree.php");
	include_once(CACTI_BASE_PATH . "/lib/timespan_settings.php");

	/* Make sure nothing is cached */
	header("Cache-Control: must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header("Expires: ". gmdate("D, d M Y H:i:s", mktime(date("H"), date("i"), date("s"), date("m")-1, date("d"), date("Y")))." GMT");
	header("Last-Modified: ". gmdate("D, d M Y H:i:s")." GMT");

	/* parse the id string
	 * prototypes:
	 * tree_id, tree_id_leaf_id, tree_id_leaf_id_hgd_dq
	 * tree_id_leaf_id_hgd_dqi, tree_id_leaf_id_hgd_gt
	 */
	$tree_id         = 0;
	$leaf_id         = 0;
	$device_group_type = array('na', 0);

	if (!isset($_REQUEST["id"])) {
		if (isset($_SESSION["sess_graph_navigation"])) {
			$_REQUEST["id"] = $_SESSION["sess_graph_navigation"];
		}
	}

	if (isset($_REQUEST["id"])) {
		$_SESSION["sess_graph_navigation"] = $_REQUEST["id"];
		$id_array = explode("_", $_REQUEST["id"]);
		$type     = "";

		if (sizeof($id_array)) {
			foreach($id_array as $part) {
				if (is_numeric($part)) {
					switch($type) {
						case "tree":
							$tree_id = $part;
							break;
						case "leaf":
							$leaf_id = $part;
							break;
						case "dqi":
							$device_group_type = array("dqi", $part);
							break;
						case "dq":
							$device_group_type = array("dq", $part);
							break;
						case "gt":
							$device_group_type = array("gt", $part);
							break;
						default:
							break;
					}
				}else{
					$type = trim($part);
				}
			}
		}
	}

	get_graph_tree_content($tree_id, $leaf_id, $device_group_type);

	exit();
}

function ajax_get_graphs_brief() {
	/* input validation */
	if (isset($_REQUEST["term"])) {
		/* jQuery UI autocomplete passes filter string as "term" */
		$q = strtolower(sanitize_search_string(get_request_var("term")));
	} else return;

	/* first, get the graph policy for current user */
	$graph_perms = db_fetch_cell("SELECT policy_graphs FROM user_auth WHERE id=" . $_SESSION["sess_user_id"]);

	/* build sql for fetching graphs
	 * take into account, whether we have an ALLOW rule == list includes all allowed graphs
	 * or a DENY rule == list includes all denied graphs; if empty == all graphs are allowed 
	 */
	if ($graph_perms == AUTH_CONTROL_DATA_POLICY_ALLOW) {
		$sql = "SELECT
			local_graph_id AS id,
			title_cache AS value
			FROM graph_templates_graph
			WHERE local_graph_id > 0
			AND LOWER(title_cache) LIKE '%$q%'
			AND local_graph_id IN (SELECT item_id FROM user_auth_perms WHERE user_auth_perms.type=1 AND user_auth_perms.user_id=". $_SESSION["sess_user_id"] . ")
			ORDER BY title_cache";
	}else{
		$sql = "SELECT
			local_graph_id AS id,
			title_cache AS value
			FROM graph_templates_graph
			WHERE local_graph_id > 0
			AND LOWER(title_cache) LIKE '%$q%'
			AND local_graph_id NOT IN (SELECT item_id FROM user_auth_perms WHERE user_auth_perms.type=1 AND user_auth_perms.user_id=". $_SESSION["sess_user_id"] . ")
			ORDER BY title_cache";
	}

	/* fetch all matching graphs */
	$graphs = db_fetch_assoc($sql);
	/* we need an explicit list entry to list any graph == effetively removing the graph filter 
	 * this has to match the id required by the SQL to display any graph 
	 * see $table->page_variables for the graph_id "default" value */
	array_unshift($graphs, array("id" => "-1", "value" => __("Any")));

	if (sizeof($graphs) > 0) {
		/* pay attention to what fields are expected by the autocomplete select function!
		 * we now provide "id" and "description as value" */
		print json_encode($graphs);
	}
}

function ajax_get_languages() {
	global $lang2locale, $cacti_locale, $supported_languages;

	/* rebuild $lang2locale array to find country and language codes easier */
	$locations = array();
	foreach($lang2locale as $locale => $properties) {
		$locations[$properties['filename'] . ".mo"] = array("flag" => $properties["country"], "language" => $properties["language"], "locale" => $locale);
	}

	/* create a list of all languages this Cacti system supports ... */
	$dhandle = opendir(CACTI_BASE_PATH . "/locales/LC_MESSAGES");
	$supported_languages["cacti"][] = "english_usa.mo";
	while (false !== ($filename = readdir($dhandle))) {
		/* language file for the DHTML calendar has to be available too */
		$path2calendar = "./include/js/jquery/locales/LC_MESSAGES/jquery.ui.datepicker-" . str_replace(".mo", ".js", $filename);
		if(isset($locations[$filename]) & file_exists($path2calendar)) {
			$supported_languages["cacti"][] = $filename;
		}
	}

	/* in strict mode we have display languages only supported by Cacti and all installed plugins */
	if (read_config_option('i18n_language_support') == 2) {
		$plugins = db_fetch_assoc("SELECT `directory` FROM `plugin_config` ORDER BY sequence ASC");

		if(sizeof($plugins)>0) {
			foreach($plugins as $plugin) {
				$plugin = $plugin["directory"];
				$dhandle = @opendir(CACTI_BASE_PATH . "/plugins/" . $plugin . "/locales/LC_MESSAGES");
				$supported_languages[$plugin][] = "english_usa.mo";
				if($dhandle) {
					while (false !== ($filename = readdir($dhandle))) {
						if(isset($locations[$filename])) {
							$supported_languages[$plugin][]= $filename;
						}
					}
					/* remove all languages which will not be supported by the plugin */
					$intersect = array_intersect($supported_languages["cacti"], $supported_languages[$plugin]);
					if(sizeof($intersect)>0) {
						$supported_languages["cacti"] = $intersect;
					}
					if (sizeof($supported_languages["cacti"]) == 1) {
						break;
					}
				}else {
					/* no language support */
					$supported_languages["cacti"] = array();
					$supported_languages["cacti"][] = "english_usa.mo";
					break;
				}
			}
		}
	}

	$location = $_SERVER['HTTP_REFERER'];

	/* clean up from an existing language parameter */
	$search    = "language=" . $cacti_locale;
	$location  = str_replace(array( "?" . $search . "&", "?" . $search, "&" . $search), array( "?", "", ""), $location);
	$location .= (strpos($location, '?')) ? '&' : '?';

	if(sizeof($supported_languages["cacti"])) {
		/* sort list translated names alphabetically */
		sort($supported_languages["cacti"]);
		foreach($supported_languages["cacti"] as $lang) {
			?><h6><a href="<?php print $location . "language=" . $locations[$lang]["locale"]; ?>"><img src="<?php echo CACTI_URL_PATH; ?>images/icons/flags/<?php print $locations[$lang]["flag"];?>.gif" align="bottom" width="16" height="11">&nbsp;<?php print $locations[$lang]["language"];?></a></h6><?php
		}
	}
}

function ajax_get_timezones() {
	$location = $_SERVER['HTTP_REFERER'];

	/* define the full array of timezones */
	$timezones = array(
		__("Africa") => array(
			__("Abidjan") 		=> 'Africa/Abidjan',
			__("Accra") 		=> 'Africa/Accra',
			__("Addis Ababa")	=> 'Africa/Addis_Ababa',
			__("Algiers") 		=> 'Africa/Algiers',
			__("Asmara") 		=> 'Africa/Asmara',
			__("Bamako") 		=> 'Africa/Bamako',
			__("Bangui") 		=> 'Africa/Bangui',
			__("Banjul") 		=> 'Africa/Banjul',
			__("Bissau") 		=> 'Africa/Bissau',
			__("Blantyre") 		=> 'Africa/Blantyre',
			__("Brazzaville")	=> 'Africa/Brazzaville',
			__("Bujumbura")		=> 'Africa/Bujumbura',
			__("Cairo") 		=> 'Africa/Cairo',
			__("Casablanca") 	=> 'Africa/Casablanca',
			__("Ceuta") 		=> 'Africa/Ceuta',
			__("Conakry") 		=> 'Africa/Conakry',
			__("Dakar") 		=> 'Africa/Dakar',
			__("Dar es Salaam") => 'Africa/Dar_es_Salaam',
			__("Djibouti") 		=> 'Africa/Djibouti',
			__("Douala") 		=> 'Africa/Douala',
			__("El Aaiun") 		=> 'Africa/El_Aaiun',
			__("Freetown") 		=> 'Africa/Freetown',
			__("Gaborone") 		=> 'Africa/Gaborone',
			__("Harare") 		=> 'Africa/Harare',
			__("Johannesburg") 	=> 'Africa/Johannesburg',
			__("Kampala") 		=> 'Africa/Kampala',
			__("Khartoum") 		=> 'Africa/Khartoum',
			__("Kigali") 		=> 'Africa/Kigali',
			__("Kinshasa") 		=> 'Africa/Kinshasa',
			__("Lagos") 		=> 'Africa/Lagos',
			__("Libreville") 	=> 'Africa/Libreville',
			__("Lome") 			=> 'Africa/Lome',
			__("Luanda") 		=> 'Africa/Luanda',
			__("Lubumbashi") 	=> 'Africa/Lubumbashi',
			__("Lusaka") 		=> 'Africa/Lusaka',
			__("Malabo") 		=> 'Africa/Malabo',
			__("Maputo") 		=> 'Africa/Maputo',
			__("Maseru") 		=> 'Africa/Maseru',
			__("Mbabane") 		=> 'Africa/Mbabane',
			__("Mogadishu") 	=> 'Africa/Mogadishu',
			__("Monrovia") 		=> 'Africa/Monrovia',
			__("Nairobi") 		=> 'Africa/Nairobi',
			__("Ndjamena") 		=> 'Africa/Ndjamena',
			__("Niamey") 		=> 'Africa/Niamey',
			__("Nouakchott") 	=> 'Africa/Nouakchott',
			__("Ouagadougou") 	=> 'Africa/Ouagadougou',
			__("Porto-Novo") 	=> 'Africa/Porto-Novo',
			__("Sao Tome") 		=> 'Africa/Sao_Tome',
			__("Tripoli") 		=> 'Africa/Tripoli',
			__("Tunis") 		=> 'Africa/Tunis',
			__("Windhoek")		=> 'Africa/Windhoek'
		),
		__("America") => array(
			__("Adak")			=> 'America/Adak',
			__("Anchorage") 	=> 'America/Anchorage',
			__("Anguilla") 		=> 'America/Anguilla',
			__("Antigua") 		=> 'America/Antigua',
			__("Araguaina") 	=> 'America/Araguaina',
			__("Argentina") => array(
				__("Buenos Aires") 	=> 'America/Argentina/Buenos_Aires',
				__("Catamarca") 	=> 'America/Argentina/Catamarca',
				__("Cordoba") 		=> 'America/Argentina/Cordoba',
				__("Jujuy") 		=> 'America/Argentina/Jujuy',
				__("La Rioja") 		=> 'America/Argentina/La_Rioja',
				__("Mendoza") 		=> 'America/Argentina/Mendoza',
				__("Rio Gallegos") 	=> 'America/Argentina/Rio_Gallegos',
				__("Salta") 		=> 'America/Argentina/Salta',
				__("San Juan") 		=> 'America/Argentina/San_Juan',
				__("San Luis") 		=> 'America/Argentina/San_Luis',
				__("Tucuman") 		=> 'America/Argentina/Tucuman',
				__("Ushuaia") 		=> 'America/Argentina/Ushuaia'
			),
			__("Aruba") 		=> 'America/Aruba',
			__("Asuncion") 		=> 'America/Asuncion',
			__("Atikokan") 		=> 'America/Atikokan',
			__("Bahia") 		=> 'America/Bahia',
			__("Barbados") 		=> 'America/Barbados',
			__("Belem") 		=> 'America/Belem',
			__("Belize") 		=> 'America/Belize',
			__("Blanc-Sablon") 	=> 'America/Blanc-Sablon',
			__("Boa Vista") 	=> 'America/Boa_Vista',
			__("Bogota") 		=> 'America/Bogota',
			__("Boise") 		=> 'America/Boise',
			__("Cambridge Bay")	=> 'America/Cambridge_Bay',
			__("Campo Grande") 	=> 'America/Campo_Grande',
			__("Cancun")		=> 'America/Cancun',
			__("Caracas") 		=> 'America/Caracas',
			__("Cayenne") 		=> 'America/Cayenne',
			__("Cayman") 		=> 'America/Cayman',
			__("Chicago")		=> 'America/Chicago',
			__("Chihuahua") 	=> 'America/Chihuahua',
			__("Costa Rica") 	=> 'America/Costa_Rica',
			__("Cuiaba") 		=> 'America/Cuiaba',
			__("Curacao") 		=> 'America/Curacao',
			__("Danmarkshavn") 	=> 'America/Danmarkshavn',
			__("Dawson") 		=> 'America/Dawson',
			__("Dawson Creek") 	=> 'America/Dawson_Creek',
			__("Denver") 		=> 'America/Denver',
			__("Detroit") 		=> 'America/Detroit',
			__("Dominica") 		=> 'America/Dominica',
			__("Edmonton") 		=> 'America/Edmonton',
			__("Eirunepe") 		=> 'America/Eirunepe',
			__("El Salvador") 	=> 'America/El_Salvador',
			__("Fortaleza") 	=> 'America/Fortaleza',
			__("Glace Bay") 	=> 'America/Glace_Bay',
			__("Godthab") 		=> 'America/Godthab',
			__("Goose Bay") 	=> 'America/Goose_Bay',
			__("Grand Turk") 	=> 'America/Grand_Turk',
			__("Grenada") 		=> 'America/Grenada',
			__("Guadeloupe") 	=> 'America/Guadeloupe',
			__("Guatemala") 	=> 'America/Guatemala',
			__("Guayaquil") 	=> 'America/Guayaquil',
			__("Guyana") 		=> 'America/Guyana',
			__("Halifax") 		=> 'America/Halifax',
			__("Havana") 		=> 'America/Havana',
			__("Hermosillo") 	=> 'America/Hermosillo',
			__("Indiana") => array(
				__("Indianapolis") 		=> 'America/Indiana/Indianapolis',
				__("Knox") 				=> 'America/Indiana/Knox',
				__("Marengo") 			=> 'America/Indiana/Marengo',
				__("Petersburg") 		=> 'America/Indiana/Petersburg',
				__("Tell City") 		=> 'America/Indiana/Tell_City',
				__("Vevay") 			=> 'America/Indiana/Vevay',
				__("Vincennes") 		=> 'America/Indiana/Vincennes',
				__("Winamac") 			=> 'America/Indiana/Winamac'
			),
			__("Inuvik") 		=> 'America/Inuvik',
			__("Iqaluit") 		=> 'America/Iqaluit',
			__("Jamaica") 		=> 'America/Jamaica',
			__("Juneau") 		=> 'America/Juneau',
			__("Kentucky") => array(
				__("Louisville") 		=> 'America/Kentucky/Louisville',
				__("Monticello") 		=> 'America/Kentucky/Monticello'
			),
			__("La Paz") 		=> 'America/La_Paz',
			__("Lima") 			=> 'America/Lima',
			__("Los Angeles") 	=> 'America/Los_Angeles',
			__("Maceio") 		=> 'America/Maceio',
			__("Managua") 		=> 'America/Managua',
			__("Manaus") 		=> 'America/Manaus',
			__("Marigot") 		=> 'America/Marigot',
			__("Martinique") 	=> 'America/Martinique',
			__("Mazatlan") 		=> 'America/Mazatlan',
			__("Menominee") 	=> 'America/Menominee',
			__("Merida") 		=> 'America/Merida',
			__("Mexico City") 	=> 'America/Mexico_City',
			__("Miquelon") 		=> 'America/Miquelon',
			__("Moncton") 		=> 'America/Moncton',
			__("Monterrey") 	=> 'America/Monterrey',
			__("Montevideo") 	=> 'America/Montevideo',
			__("Montreal") 		=> 'America/Montreal',
			__("Montserrat") 	=> 'America/Montserrat',
			__("Nassau") 		=> 'America/Nassau',
			__("New York") 		=> 'America/New_York',
			__("Nipigon") 		=> 'America/Nipigon',
			__("Nome") 			=> 'America/Nome',
			__("Noronha") 		=> 'America/Noronha',
			__("North Dakota") => array(
				__("Center") 		=> 'America/North_Dakota/Center',
				__("New Salem") 	=> 'America/North_Dakota/New_Salem'
			),
			__("Panama") 		=> 'America/Panama',
			__("Pangnirtung") 	=> 'America/Pangnirtung',
			__("Paramaribo") 	=> 'America/Paramaribo',
			__("Phoenix") 		=> 'America/Phoenix',
			__("Port-au-Prince")=> 'America/Port-au-Prince',
			__("Port of Spain") => 'America/Port_of_Spain',
			__("Porto Velho") 	=> 'America/Porto_Velho',
			__("Puerto Rico") 	=> 'America/Puerto_Rico',
			__("Rainy River") 	=> 'America/Rainy_River',
			__("Rankin Inlet") 	=> 'America/Rankin_Inlet',
			__("Recife") 		=> 'America/Recife',
			__("Regina") 		=> 'America/Regina',
			__("Resolute") 		=> 'America/Resolute',
			__("Rio Branco") 	=> 'America/Rio_Branco',
			__("Santarem")		=> 'America/Santarem',
			__("Santiago") 		=> 'America/Santiago',
			__("Santo Domingo") => 'America/Santo_Domingo',
			__("Sao Paulo")		=> 'America/Sao_Paulo',
			__("Scoresbysund") 	=> 'America/Scoresbysund',
			__("Shiprock") 		=> 'America/Shiprock',
			__("St Barthelemy") => 'America/St_Barthelemy',
			__("St Johns") 		=> 'America/St_Johns',
			__("St Kitts") 		=> 'America/St_Kitts',
			__("St Lucia") 		=> 'America/St_Lucia',
			__("St Thomas") 	=> 'America/St_Thomas',
			__("St Vincent") 	=> 'America/St_Vincent',
			__("Swift Current") => 'America/Swift_Current',
			__("Tegucigalpa") 	=> 'America/Tegucigalpa',
			__("Thule") 		=> 'America/Thule',
			__("Thunder Bay") 	=> 'America/Thunder_Bay',
			__("Tijuana") 		=> 'America/Tijuana',
			__("Toronto") 		=> 'America/Toronto',
			__("Tortola") 		=> 'America/Tortola',
			__("Vancouver") 	=> 'America/Vancouver',
			__("Whitehorse") 	=> 'America/Whitehorse',
			__("Winnipeg") 		=> 'America/Winnipeg',
			__("Yakutat") 		=> 'America/Yakutat',
			__("Yellowknife") 	=> 'America/Yellowknife'
		),
		__("Antarctica") => array (
			__("Casey") 		=> 'Antarctica/Casey',
			__("Davis") 		=> 'Antarctica/Davis',
			__("DumontDUrville")=> 'Antarctica/DumontDUrville',
			__("Mawson") 		=> 'Antarctica/Mawson',
			__("McMurdo") 		=> 'Antarctica/McMurdo',
			__("Palmer") 		=> 'Antarctica/Palmer',
			__("Rothera") 		=> 'Antarctica/Rothera',
			__("South Pole") 	=> 'Antarctica/South_Pole',
			__("Syowa") 		=> 'Antarctica/Syowa',
			__("Vostok") 		=> 'Antarctica/Vostok'
		),
		__("Arctic") => array(
			__("Longyearbyen") 	=> 'Arctic/Longyearbyen'
		),
		__("Asia") => array(
			__("Aden") 			=> 'Asia/Aden',
			__("Almaty") 		=> 'Asia/Almaty',
			__("Amman") 		=> 'Asia/Amman',
			__("Anadyr") 		=> 'Asia/Anadyr',
			__("Aqtau") 		=> 'Asia/Aqtau',
			__("Aqtobe") 		=> 'Asia/Aqtobe',
			__("Ashgabat") 		=> 'Asia/Ashgabat',
			__("Baghdad") 		=> 'Asia/Baghdad',
			__("Bahrain") 		=> 'Asia/Bahrain',
			__("Baku") 			=> 'Asia/Baku',
			__("Bangkok") 		=> 'Asia/Bangkok',
			__("Beirut") 		=> 'Asia/Beirut',
			__("Bishkek") 		=> 'Asia/Bishkek',
			__("Brunei") 		=> 'Asia/Brunei',
			__("Choibalsan") 	=> 'Asia/Choibalsan',
			__("Chongqing") 	=> 'Asia/Chongqing',
			__("Colombo") 		=> 'Asia/Colombo',
			__("Damascus") 		=> 'Asia/Damascus',
			__("Dhaka") 		=> 'Asia/Dhaka',
			__("Dili") 			=> 'Asia/Dili',
			__("Dubai") 		=> 'Asia/Dubai',
			__("Dushanbe") 		=> 'Asia/Dushanbe',
			__("Gaza") 			=> 'Asia/Gaza',
			__("Harbin") 		=> 'Asia/Harbin',
			__("Ho Chi Minh") 	=> 'Asia/Ho_Chi_Minh',
			__("Hong Kong") 	=> 'Asia/Hong_Kong',
			__("Hovd") 			=> 'Asia/Hovd',
			__("Irkutsk") 		=> 'Asia/Irkutsk',
			__("Jakarta") 		=> 'Asia/Jakarta',
			__("Jayapura") 		=> 'Asia/Jayapura',
			__("Jerusalem") 	=> 'Asia/Jerusalem',
			__("Kabul") 		=> 'Asia/Kabul',
			__("Kamchatka") 	=> 'Asia/Kamchatka',
			__("Karachi") 		=> 'Asia/Karachi',
			__("Kashgar") 		=> 'Asia/Kashgar',
			__("Katmandu") 		=> 'Asia/Katmandu',
			__("Kolkata") 		=> 'Asia/Kolkata',
			__("Krasnoyarsk") 	=> 'Asia/Krasnoyarsk',
			__("Kuala Lumpur") 	=> 'Asia/Kuala_Lumpur',
			__("Kuching") 		=> 'Asia/Kuching',
			__("Kuwait") 		=> 'Asia/Kuwait',
			__("Macau") 		=> 'Asia/Macau',
			__("Magadan") 		=> 'Asia/Magadan',
			__("Makassar") 		=> 'Asia/Makassar',
			__("Manila") 		=> 'Asia/Manila',
			__("Muscat") 		=> 'Asia/Muscat',
			__("Nicosia") 		=> 'Asia/Nicosia',
			__("Novosibirsk") 	=> 'Asia/Novosibirsk',
			__("Omsk") 			=> 'Asia/Omsk',
			__("Oral") 			=> 'Asia/Oral',
			__("Phnom Penh") 	=> 'Asia/Phnom_Penh',
			__("Pontianak") 	=> 'Asia/Pontianak',
			__("Pyongyang") 	=> 'Asia/Pyongyang',
			__("Qatar") 		=> 'Asia/Qatar',
			__("Qyzylorda") 	=> 'Asia/Qyzylorda',
			__("Rangoon") 		=> 'Asia/Rangoon',
			__("Riyadh") 		=> 'Asia/Riyadh',
			__("Sakhalin") 		=> 'Asia/Sakhalin',
			__("Samarkand") 	=> 'Asia/Samarkand',
			__("Seoul") 		=> 'Asia/Seoul',
			__("Shanghai") 		=> 'Asia/Shanghai',
			__("Singapore") 	=> 'Asia/Singapore',
			__("Taipei") 		=> 'Asia/Taipei',
			__("Tashkent") 		=> 'Asia/Tashkent',
			__("Tbilisi") 		=> 'Asia/Tbilisi',
			__("Tehran") 		=> 'Asia/Tehran',
			__("Thimphu") 		=> 'Asia/Thimphu',
			__("Tokyo") 		=> 'Asia/Tokyo',
			__("Ulaanbaatar") 	=> 'Asia/Ulaanbaatar',
			__("Urumqi") 		=> 'Asia/Urumqi',
			__("Vientiane") 	=> 'Asia/Vientiane',
			__("Vladivostok") 	=> 'Asia/Vladivostok',
			__("Yakutsk") 		=> 'Asia/Yakutsk',
			__("Yekaterinburg") => 'Asia/Yekaterinburg',
			__("Yerevan") 		=> 'Asia/Yerevan'
		),
		__("Atlantic") => array(
			__("Azores") 		=> 'Atlantic/Azores',
			__("Bermuda") 		=> 'Atlantic/Bermuda',
			__("Canary") 		=> 'Atlantic/Canary',
			__("Cape Verde") 	=> 'Atlantic/Cape_Verde',
			__("Faroe") 		=> 'Atlantic/Faroe',
			__("Madeira") 		=> 'Atlantic/Madeira',
			__("Reykjavik") 	=> 'Atlantic/Reykjavik',
			__("South Georgia") => 'Atlantic/South_Georgia',
			__("St Helena") 	=> 'Atlantic/St_Helena',
			__("Stanley") 		=> 'Atlantic/Stanley'
		),
		__("Australia") => array(
			__("Adelaide") 		=> 'Australia/Adelaide',
			__("Brisbane") 		=> 'Australia/Brisbane',
			__("Broken Hill") 	=> 'Australia/Broken_Hill',
			__("Currie") 		=> 'Australia/Currie',
			__("Darwin") 		=> 'Australia/Darwin',
			__("Eucla") 		=> 'Australia/Eucla',
			__("Hobart") 		=> 'Australia/Hobart',
			__("Lindeman") 		=> 'Australia/Lindeman',
			__("Lord Howe") 	=> 'Australia/Lord_Howe',
			__("Melbourne") 	=> 'Australia/Melbourne',
			__("Perth") 		=> 'Australia/Perth',
			__("Sydney") 		=> 'Australia/Sydney'
		),
		__("Europe") => array(
			__("Amsterdam") 	=> 'Europe/Amsterdam',
			__("Andorra") 		=> 'Europe/Andorra',
			__("Athens") 		=> 'Europe/Athens',
			__("Belgrade") 		=> 'Europe/Belgrade',
			__("Berlin") 		=> 'Europe/Berlin',
			__("Bratislava") 	=> 'Europe/Bratislava',
			__("Brussels") 		=> 'Europe/Brussels',
			__("Bucharest") 	=> 'Europe/Bucharest',
			__("Budapest") 		=> 'Europe/Budapest',
			__("Chisinau") 		=> 'Europe/Chisinau',
			__("Copenhagen") 	=> 'Europe/Copenhagen',
			__("Dublin") 		=> 'Europe/Dublin',
			__("Gibraltar") 	=> 'Europe/Gibraltar',
			__("Guernsey") 		=> 'Europe/Guernsey',
			__("Helsinki") 		=> 'Europe/Helsinki',
			__("Isle of Man") 	=> 'Europe/Isle_of_Man',
			__("Istanbul") 		=> 'Europe/Istanbul',
			__("Jersey") 		=> 'Europe/Jersey',
			__("Kaliningrad") 	=> 'Europe/Kaliningrad',
			__("Kiev") 			=> 'Europe/Kiev',
			__("Lisbon") 		=> 'Europe/Lisbon',
			__("Ljubljana") 	=> 'Europe/Ljubljana',
			__("London") 		=> 'Europe/London',
			__("Luxembourg") 	=> 'Europe/Luxembourg',
			__("Madrid") 		=> 'Europe/Madrid',
			__("Malta") 		=> 'Europe/Malta',
			__("Mariehamn") 	=> 'Europe/Mariehamn',
			__("Minsk") 		=> 'Europe/Minsk',
			__("Monaco") 		=> 'Europe/Monaco',
			__("Moscow") 		=> 'Europe/Moscow',
			__("Oslo") 			=> 'Europe/Oslo',
			__("Paris") 		=> 'Europe/Paris',
			__("Podgorica") 	=> 'Europe/Podgorica',
			__("Prague") 		=> 'Europe/Prague',
			__("Riga") 			=> 'Europe/Riga',
			__("Rome") 			=> 'Europe/Rome',
			__("Samara") 		=> 'Europe/Samara',
			__("San Marino") 	=> 'Europe/San_Marino',
			__("Sarajevo") 		=> 'Europe/Sarajevo',
			__("Simferopol") 	=> 'Europe/Simferopol',
			__("Skopje") 		=> 'Europe/Skopje',
			__("Sofia") 		=> 'Europe/Sofia',
			__("Stockholm") 	=> 'Europe/Stockholm',
			__("Tallinn") 		=> 'Europe/Tallinn',
			__("Tirane") 		=> 'Europe/Tirane',
			__("Uzhgorod") 		=> 'Europe/Uzhgorod',
			__("Vaduz") 		=> 'Europe/Vaduz',
			__("Vatican") 		=> 'Europe/Vatican',
			__("Vienna") 		=> 'Europe/Vienna',
			__("Vilnius") 		=> 'Europe/Vilnius',
			__("Volgograd") 	=> 'Europe/Volgograd',
			__("Warsaw") 		=> 'Europe/Warsaw',
			__("Zagreb") 		=> 'Europe/Zagreb',
			__("Zaporozhye") 	=> 'Europe/Zaporozhye',
			__("Zurich") 		=> 'Europe/Zurich'
		),
		__("Indian") => array(
			__("Antananarivo") 	=> 'Indian/Antananarivo',
			__("Chagos") 		=> 'Indian/Chagos',
			__("Christmas") 	=> 'Indian/Christmas',
			__("Cocos") 		=> 'Indian/Cocos',
			__("Comoro") 		=> 'Indian/Comoro',
			__("Kerguelen") 	=> 'Indian/Kerguelen',
			__("Mahe") 			=> 'Indian/Mahe',
			__("Maldives") 		=> 'Indian/Maldives',
			__("Mauritius") 	=> 'Indian/Mauritius',
			__("Mayotte") 		=> 'Indian/Mayotte',
			__("Reunion") 		=> 'Indian/Reunion'
		),
		__("Pacific") => array(
			__("Apia") 			=> 'Pacific/Apia',
			__("Auckland") 		=> 'Pacific/Auckland',
			__("Chatham") 		=> 'Pacific/Chatham',
			__("Easter") 		=> 'Pacific/Easter',
			__("Efate") 		=> 'Pacific/Efate',
			__("Enderbury") 	=> 'Pacific/Enderbury',
			__("Fakaofo") 		=> 'Pacific/Fakaofo',
			__("Fiji") 			=> 'Pacific/Fiji',
			__("Funafuti") 		=> 'Pacific/Funafuti',
			__("Galapagos") 	=> 'Pacific/Galapagos',
			__("Gambier") 		=> 'Pacific/Gambier',
			__("Guadalcanal") 	=> 'Pacific/Guadalcanal',
			__("Guam") 			=> 'Pacific/Guam',
			__("Honolulu") 		=> 'Pacific/Honolulu',
			__("Johnston") 		=> 'Pacific/Johnston',
			__("Kiritimati") 	=> 'Pacific/Kiritimati',
			__("Kosrae") 		=> 'Pacific/Kosrae',
			__("Kwajalein") 	=> 'Pacific/Kwajalein',
			__("Majuro") 		=> 'Pacific/Majuro',
			__("Marquesas") 	=> 'Pacific/Marquesas',
			__("Midway") 		=> 'Pacific/Midway',
			__("Nauru") 		=> 'Pacific/Nauru',
			__("Niue") 			=> 'Pacific/Niue',
			__("Norfolk") 		=> 'Pacific/Norfolk',
			__("Noumea") 		=> 'Pacific/Noumea',
			__("Pago Pago") 	=> 'Pacific/Pago_Pago',
			__("Palau") 		=> 'Pacific/Palau',
			__("Pitcairn") 		=> 'Pacific/Pitcairn',
			__("Ponape") 		=> 'Pacific/Ponape',
			__("Port Moresby") 	=> 'Pacific/Port_Moresby',
			__("Rarotonga") 	=> 'Pacific/Rarotonga',
			__("Saipan") 		=> 'Pacific/Saipan',
			__("Tahiti") 		=> 'Pacific/Tahiti',
			__("Tarawa") 		=> 'Pacific/Tarawa',
			__("Tongatapu") 	=> 'Pacific/Tongatapu',
			__("Truk") 			=> 'Pacific/Truk',
			__("Wake") 			=> 'Pacific/Wake',
			__("Wallis") 		=> 'Pacific/Wallis'
		),
		__("Others") => array(
			"UTC"				=> 'UTC'
		)
	);

	/* clean up from an existing time zone parameter */
	$search = "time_zone=" . urlencode(CACTI_CUSTOM_TIME_ZONE);
	$location = str_replace(array( "?" . $search . "&", "?" . $search, "&" . $search), array( "?", "", ""), $location);
	$location .= (strpos($location, '?')) ? '&' : '?';

	foreach($timezones as $continent => $countries) {
		print "<h6><a href=\"#\">$continent</a><div>";
		/* sort the translated names alphabetically */
		ksort($countries);
		foreach($countries as $region => $time_zone) {
			if (is_array($time_zone)) {
				/* sort the translated names alphabetically */
				ksort($time_zone);
				print "<h6><a href=\"#\">$region</a><div>";
				foreach($time_zone as $city => $time_zone2) {
					print "<h6><a href=\"" . $location . "time_zone=" . urlencode($time_zone2) . "\">$city</a></h6>";
				}
				print "</div></h6>";
			}else {
				print "<h6><a href=\"" . $location . "time_zone=" . urlencode($time_zone) . "\">$region</a></h6>";
			}
		}
		print "</div></h6>";
	}
}

?>
