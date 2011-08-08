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
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

include("./include/auth.php");

define("MAX_DISPLAY_PAGES", 21);

$user_actions = array(
	"1" => __("Delete"),
	"2" => __("Copy"),
	"3" => __("Enable"),
	"4" => __("Disable"),
	"5" => __("Batch Copy")
	);

switch (get_request_var_request("action")) {
	case 'actions':
		form_actions();

		break;
	case 'save':
		form_save();

		break;
	case 'perm_remove':
		perm_remove();

		break;
	case 'edit':
		include_once(CACTI_BASE_PATH . "/include/top_header.php");
		user_edit(true);
		include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");

		break;
	case 'user_edit':
	case 'user_realms_edit':
	case 'graph_settings_edit':
	case 'graph_perms_edit':
		user_edit(false);

		break;
	case 'ajax_get_devices_detailed':
		ajax_get_devices_detailed();

		break;
	case 'ajax_get_graphs_brief':
		ajax_get_graphs_brief();

		break;
	case 'ajax_get_graph_templates':
		ajax_get_graph_templates();

		break;
	case 'ajax_view':
		user();

		break;
	default:
		if (!plugin_hook_function('user_admin_action', get_request_var_request("action"))) {
			include_once(CACTI_BASE_PATH . "/include/top_header.php");
			user();
			include_once(CACTI_BASE_PATH . "/include/bottom_footer.php");
		}
		break;
}

/* --------------------------
    Actions Function
   -------------------------- */
/**
 * perform different actions
 */
function form_actions() {
	global $user_actions;
	require(CACTI_BASE_PATH . "/include/auth/auth_arrays.php");

	/* if we are to save this form, instead of display it */
	if (isset($_POST["selected_items"])) {
		if (get_request_var_post("drp_action") != "2") {
			$selected_items = unserialize(stripslashes(get_request_var_post("selected_items")));
		}

		if (get_request_var_post("drp_action") === "1") { /* delete */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				user_remove($selected_items[$i]);
			}
		}

		if (get_request_var_post("drp_action") === "2") { /* copy */
			/* ================= input validation ================= */
			input_validate_input_number(get_request_var_post("selected_items"));
			input_validate_input_number(get_request_var_post("new_realm"));
			/* ==================================================== */

			$new_username = get_request_var_post("new_username");
			$new_realm = get_request_var_post("new_realm", 0);
			$template_user = db_fetch_row("SELECT username, realm FROM user_auth WHERE id = " . get_request_var_post("selected_items"));
			$overwrite = array( "full_name" => get_request_var_post("new_fullname") );

			if (strlen($new_username)) {
				if (sizeof(db_fetch_assoc("SELECT username FROM user_auth WHERE username = '" . $new_username . "' AND realm = " . $new_realm))) {
					raise_message(19);
				} else {
					if (user_copy($template_user["username"], $new_username, $template_user["realm"], $new_realm, false, $overwrite) === false) {
						raise_message(2);
					} else {
						raise_message(1);
					}
				}
			}
		}

		if (get_request_var_post("drp_action") === "3") { /* enable */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				user_enable($selected_items[$i]);
			}
		}

		if (get_request_var_post("drp_action") === "4") { /* disable */
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				user_disable($selected_items[$i]);
			}
		}

		if (get_request_var_post("drp_action") === "5") { /* batch copy */
			/* ================= input validation ================= */
			input_validate_input_number(get_request_var_post("template_user"));
			/* ==================================================== */

			$copy_error = false;
			$template = db_fetch_row("SELECT username, realm FROM user_auth WHERE id = " . get_request_var_post("template_user"));
			for ($i=0;($i<count($selected_items));$i++) {
				/* ================= input validation ================= */
				input_validate_input_number($selected_items[$i]);
				/* ==================================================== */

				$user = db_fetch_row("SELECT username, realm FROM user_auth WHERE id = " . $selected_items[$i]);
				if ((isset($user)) && (isset($template))) {
					if (user_copy($template["username"], $user["username"], $template["realm"], $user["realm"], true) === false) {
						$copy_error = true;
					}
				}
			}
			if ($copy_error) {
				raise_message(2);
			} else {
				raise_message(1);
			}
		}

		exit;
	}

	/* loop through each of the users and process them */
	$user_list = "";
	$user_array = array();
	while (list($var,$val) = each($_POST)) {
		if (preg_match("/^chk_([0-9]+)$/", $var, $matches)) {
			/* ================= input validation ================= */
			input_validate_input_number($matches[1]);
			/* ==================================================== */

			if (get_request_var_post("drp_action") != "2") {
				$user_list .= "<li>" . db_fetch_cell("SELECT username FROM user_auth WHERE id=" . $matches[1]) . "</li>";
			}
			$user_array[] = $matches[1];
		}
	}

	print "<form id='uactions' name='uactions' action='user_admin.php' method='post'>\n";

	html_start_box("", "100", "3", "center", "");

	$user_id = "";

	/* Check for deleting of Graph Export User */
	if (get_request_var_post("drp_action") === ACTION_NONE) { /* NONE */
		print "	<tr>
					<td class='textArea'>
						<p>" . __("You did not select a valid action. Please select 'Return' to return to the previous menu.") . "</p>
					</td>
				</tr>\n";

		$title = __("Selection Error");
	}elseif ((get_request_var_post("drp_action") === "1") && (sizeof($user_array))) { /* delete */
		$exportuser = read_config_option('export_user_id');
		if (in_array($exportuser, $user_array)) {
			print "	<tr>
				<td class='textArea'>
					<p>" . __("You can not delete the Export User '") . db_fetch_cell("SELECT username FROM user_auth WHERE id=$exportuser") . __("'.  Please select 'Return' to return to the previous menu.") . "</p>
				</td>
			</tr>\n";

			unset($user_array);

			$title = __("Export User Error");
		}else{
			print "
				<tr>
					<td class='textArea'>
						<p>" . __("When you click 'Continue', the following User(s) will be deleted.") . "</p>
						<div class='action_list'><ul>$user_list</ul></div>
					</td>
				</tr>\n";

			$title = __("Delete Selected User(s)");
		}
	}elseif ((get_request_var_post("drp_action") === "2") && (sizeof($user_array))) { /* copy */
		$user_id = $user_array[0];
		$user_realm = db_fetch_cell("SELECT realm FROM user_auth WHERE id = " . $user_id);

		print "
			<tr>
				<td class='textArea'>
					<p>" . __("When you click 'Continue', the selected Users settings will be copied to the new User.") . "</p>
				</td>
			</tr><tr>
				<td class='textArea'>\n" .
					__("Template Username:") . " <i>" . db_fetch_cell("SELECT username FROM user_auth WHERE id=" . $user_id) . "</i>
				</td>
			</tr><tr>
				<td class='textArea'>
				" . __("New Username:");
		print form_text_box("new_username", "", "", 25);
		print "				</td>
			</tr><tr>
				<td class='textArea'>
					" . __("New Full Name:");
		print form_text_box("new_fullname", "", "", 35);
		print "				</td>
			</tr><tr>
				<td class='textArea'>
					" . __("New Realm:") . " \n";
		print form_dropdown("new_realm", $auth_realms, "", "", $user_realm, "", 0);
		print "				</td>
			</tr>\n";

		$title = __("Copy User");
	}elseif ((get_request_var_post("drp_action") === "3") && (sizeof($user_array))) { /* enable */
		print "
			<tr>
				<td class='textArea'>
					<p>" . __("When you click 'Continue', the following User(s) will be enabled.") . "</p>
					<div class='action_list'><ul>$user_list</ul></div>
				</td>
			</tr>\n";

		$title = __("Enable User(s)");
	}elseif ((get_request_var_post("drp_action") === "4") && (sizeof($user_array))) { /* disable */
		print "
			<tr>
				<td class='textArea'>
					<p>" . __("When you click 'Continue', the following User(s) will be disabled.") . "</p>
					<div class='action_list'><ul>$user_list</ul></div>
				</td>
			</tr>\n";

		$title = __("Disable User(s)");
	}elseif ((get_request_var_post("drp_action") === "5") && (sizeof($user_array))) { /* batch copy */
		$usernames = db_fetch_assoc("SELECT id,username FROM user_auth WHERE realm = 0 ORDER BY username");
		print "
			<tr>
				<td class='textArea'>
					<p>" . __("When you click 'Continue',  the following User(s) will have their settings reinitialized with the selected User.  The original user Full Name, Password, Realm and Enable status will be retained, all other fields will be overwritten from template User.") . "</p>
				</td>
			</tr>
			<tr>
				<td class='textArea'>
					" . __("Template User:") . " \n";
		print form_dropdown("template_user", $usernames, "username", "id", "", "", 0);
		print "		</td>
			</tr>
			<tr>
				<td class='textArea'>
					<p>" . __("Users to update:") . "</p>
					<div class='action_list'><ul>$user_list</ul></div>
				</td>
			</tr>\n";

		$title = __("Re-Template User(s)");
	}

	if (!isset($user_array) || get_request_var_post("drp_action") === ACTION_NONE) {
		form_return_button();
	}else{
		form_continue(serialize($user_array), get_request_var_post("drp_action"), $title, "uactions");
	}

	html_end_box();
}

/* --------------------------
    Save Function
   -------------------------- */
/**
 * save user attributes
 */
function form_save() {
	global $settings_graphs;
	require_once(CACTI_BASE_PATH . "/include/auth/auth_constants.php");

	/* graph permissions */
	if ((isset($_POST["save_component_graph_perms"])) && (!is_error_message())) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("id"));
		input_validate_input_number(get_request_var_post("perm_graphs"));
		input_validate_input_number(get_request_var_post("perm_trees"));
		input_validate_input_number(get_request_var_post("perm_devices"));
		input_validate_input_number(get_request_var_post("perm_graph_templates"));
		input_validate_input_number(get_request_var_post("policy_graphs"));
		input_validate_input_number(get_request_var_post("policy_trees"));
		input_validate_input_number(get_request_var_post("policy_devices"));
		input_validate_input_number(get_request_var_post("policy_graph_templates"));
		/* ==================================================== */

		$add_button_clicked = false;

		if (isset($_POST["add_graph_y"])) {
			db_execute("REPLACE INTO user_auth_perms (user_id,item_id,type) VALUES (" . get_request_var_post("id") . "," . get_request_var_post("perm_graphs") . "," . PERM_GRAPHS . ")");
			$add_button_clicked = true;
		}elseif (isset($_POST["add_tree_y"])) {
			db_execute("REPLACE INTO user_auth_perms (user_id,item_id,type) VALUES (" . get_request_var_post("id") . "," . get_request_var_post("perm_trees") . "," . PERM_TREES . ")");
			$add_button_clicked = true;
		}elseif (isset($_POST["add_device_y"])) {
			db_execute("REPLACE INTO user_auth_perms (user_id,item_id,type) VALUES (" . get_request_var_post("id") . "," . get_request_var_post("perm_devices") . "," . PERM_DEVICES . ")");
			$add_button_clicked = true;
		}elseif (isset($_POST["add_graph_template_y"])) {
			db_execute("REPLACE INTO user_auth_perms (user_id,item_id,type) VALUES (" . get_request_var_post("id") . "," . get_request_var_post("perm_graph_templates") . "," . PERM_GRAPH_TEMPLATES . ")");
			$add_button_clicked = true;
		}

		db_execute("UPDATE user_auth
			SET
				policy_graphs=" . get_request_var_post("policy_graphs") . ",
				policy_trees="  . get_request_var_post("policy_trees"). ",
				policy_devices="  . get_request_var_post("policy_devices"). ",
				policy_graph_templates=" . get_request_var_post("policy_graph_templates") . "
			WHERE id=" . get_request_var_post("id"));

		if ($add_button_clicked == true) {
			header("Location: user_admin.php?action=graph_perms_edit&id=" . get_request_var_post("id"));
			exit;
		}
	}

	/* user management save */
	if (isset($_POST["save_component_user"])) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var_post("id"));
		input_validate_input_number(get_request_var_post("realm"));
		/* ==================================================== */

		if ((get_request_var_post("password") == "") && (get_request_var_post("password_confirm") == "")) {
			$password = db_fetch_cell("SELECT password FROM user_auth WHERE id = " . get_request_var_post("id"));
		}else{
			$password = md5(get_request_var_post("password"));
		}

		/* check duplicate username */
		if (sizeof(db_fetch_row("select * from user_auth where realm = " . get_request_var_post("realm") . " and username = '" . get_request_var_post("username") . "' and id != " . get_request_var_post("id")))) {
			raise_message(12);
		}

		/* check for guest or template user */
		$username = db_fetch_cell("select username from user_auth where id = " . get_request_var_post("id"));
		if ($username != get_request_var_post("username")) {
			if ($username == read_config_option("user_template")) {
				raise_message(20);
			}
			if ($username == read_config_option("guest_user")) {
				raise_message(20);
			}
		}

		/* check to make sure the passwords match; if not error */
		if (get_request_var_post("password") != get_request_var_post("password_confirm")) {
			raise_message(4);
		}

		form_input_validate(get_request_var_post("password"), "password", "" . preg_quote(get_request_var_post("password_confirm")) . "", true, 4);
		form_input_validate(get_request_var_post("password_confirm"), "password_confirm", "" . preg_quote(get_request_var_post("password")) . "", true, 4);

		$save["id"] = get_request_var_post("id");
		$save["username"] = form_input_validate(get_request_var_post("username"), "username", "^[A-Za-z0-9\._\\\@\ -]+$", false, 3);
		$save["full_name"] = form_input_validate(get_request_var_post("full_name"), "full_name", "", true, 3);
		$save["password"] = $password;
		$save["must_change_password"] = form_input_validate(get_request_var_post("must_change_password", ""), "must_change_password", "", true, 3);
		$save["show_tree"] = form_input_validate(get_request_var_post("show_tree", ""), "show_tree", "", true, 3);
		$save["show_list"] = form_input_validate(get_request_var_post("show_list", ""), "show_list", "", true, 3);
		$save["show_preview"] = form_input_validate(get_request_var_post("show_preview", ""), "show_preview", "", true, 3);
		$save["graph_settings"] = form_input_validate(get_request_var_post("graph_settings", ""), "graph_settings", "", true, 3);
		$save["login_opts"] = form_input_validate(get_request_var_post("login_opts"), "login_opts", "", true, 3);
		$save["policy_graphs"] = form_input_validate(get_request_var_post("policy_graphs", get_request_var_post("hidden_policy_graphs")), "policy_graphs", "", true, 3);
		$save["policy_trees"] = form_input_validate(get_request_var_post("policy_trees", get_request_var_post("hidden_policy_trees")), "policy_trees", "", true, 3);
		$save["policy_devices"] = form_input_validate(get_request_var_post("policy_devices", get_request_var_post("hidden_policy_devices")), "policy_devices", "", true, 3);
		$save["policy_graph_templates"] = form_input_validate(get_request_var_post("policy_graph_templates", get_request_var_post("hidden_policy_graph_templates")), "policy_graph_templates", "", true, 3);
		$save["realm"] = get_request_var_post("realm", 0);
		$save["enabled"] = form_input_validate(get_request_var_post("enabled", ""), "enabled", "", true, 3);
		$save = plugin_hook_function('user_admin_setup_sql_save', $save);

		if (!is_error_message()) {
			$user_id = sql_save($save, "user_auth");

			if ($user_id) {
				raise_message(1);
			}else{
				raise_message(2);
			}
		}
	}else if (isset($_POST["save_component_realm_perms"])) {
		$user_id = get_request_var_post("id");
		db_execute("DELETE FROM user_auth_realm WHERE user_id = " . $user_id);

		while (list($var, $val) = each($_POST)) {
			if (substr($var, 0, 7) == "section") {
			    db_execute("REPLACE INTO user_auth_realm (user_id,realm_id) VALUES (" . $user_id . "," . substr($var, 7) . ")");
			}
		}
	}elseif (isset($_POST["save_component_graph_settings"])) {
		$user_id = get_request_var_post("id");
		while (list($tab_short_name, $tab_fields) = each($settings_graphs)) {
			while (list($field_name, $field_array) = each($tab_fields)) {
				if ((isset($field_array["items"])) && (is_array($field_array["items"]))) {
					while (list($sub_field_name, $sub_field_array) = each($field_array["items"])) {
						db_execute("REPLACE INTO settings_graphs (user_id,name,value) VALUES (" . (!empty($user_id) ? $user_id : get_request_var_post("id")) . ",'$sub_field_name', '" . get_request_var_post($sub_field_name, "") . "')");
					}
				}else{
					db_execute("REPLACE INTO settings_graphs (user_id,name,value) VALUES (" . (!empty($user_id) ? $user_id : $_POST["id"]) . ",'$field_name', '" . get_request_var_post($field_name) . "')");
				}
			}
		}

		/* reset local settings cache so the user sees the new settings */
		kill_session_var("sess_graph_config_array");
	}elseif (isset($_POST["save_component_graph_perms"])) {
		db_execute("UPDATE user_auth SET
			policy_graphs = " . get_request_var_post("policy_graphs") . ",
			policy_trees = " . get_request_var_post("policy_trees") . ",
			policy_devices = " . get_request_var_post("policy_devices") . ",
			policy_graph_templates = " . get_request_var_post("policy_graph_templates") . "
			WHERE id = " . get_request_var_post("id"));
	} else {
		plugin_hook('user_admin_user_save');
	}

	/* redirect to the appropriate page */
	if (isset($_POST["save_component_realm_perms"])) {
		header("Location: user_admin.php?action=user_realms_edit&id=" . (empty($user_id) ? $_POST["id"] : $user_id));
	}elseif (isset($_POST["save_component_graph_perms"])) {
		header("Location: user_admin.php?action=graph_perms_edit&id=" . (empty($user_id) ? $_POST["id"] : $user_id));
	}elseif (isset($_POST["save_component_graph_settings"])) {
		header("Location: user_admin.php?action=graph_settings_edit&id=" . (empty($user_id) ? $_POST["id"] : $user_id));
	}elseif (isset($_POST["save_component_user"])) {
		header("Location: user_admin.php?action=edit&id=" . (empty($user_id) ? $_POST["id"] : $user_id));
	}else{
		header(plugin_hook_function('user_admin_save_location', "Location: user_admin.php?action=edit&id=" . (empty($user_id) ? $_POST["id"] : $user_id)));
	}
	exit;
}

/* --------------------------
    Graph Permissions
   -------------------------- */
/**
 * remove permissions for a user id
 * parms passed as request vars
 */
function perm_remove() {
	require_once(CACTI_BASE_PATH . "/include/auth/auth_constants.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	input_validate_input_number(get_request_var("user_id"));
	/* ==================================================== */

	if (get_request_var("type") == "graph") {
		db_execute("DELETE FROM user_auth_perms WHERE type = " . PERM_GRAPHS . " AND user_id = " . get_request_var("user_id") . " AND item_id = " . get_request_var("id"));
	}elseif (get_request_var("type") == "tree") {
		db_execute("DELETE FROM user_auth_perms WHERE type = " . PERM_TREES . " AND user_id = " . get_request_var("user_id") . " AND item_id = " . get_request_var("id"));
	}elseif (get_request_var("type") == "device") {
		db_execute("DELETE FROM user_auth_perms WHERE type = " . PERM_DEVICES . " AND user_id = " . get_request_var("user_id") . " AND item_id = " . get_request_var("id"));
	}elseif (get_request_var("type") == "graph_template") {
		db_execute("DELETE FROM user_auth_perms WHERE type = " . PERM_GRAPH_TEMPLATES . " AND user_id=" . get_request_var("user_id") . " and item_id = " . get_request_var("id"));
	}

	header("Location: user_admin.php?action=graph_perms_edit&id=" . get_request_var("user_id"));
	exit;
}

/**
 * edit global user attributes
 * @param array $user	- user data
 */
function user_global_edit($user){

	html_start_box(__("General Settings"), "100", 0, "center");

	draw_edit_form(array(
		"config" => array("no_form_tag" => true),
		"fields" => inject_form_variables(user_auth_form_list(), (isset($user) ? $user : array()))
	));

	html_end_box();

	form_hidden_box("id", (isset($_GET["id"]) ? get_request_var("id") : "0"), "");
	form_hidden_box("hidden_policy_graphs", (isset($_GET["policy_graphs"]) ? get_request_var("policy_graphs") : "2"), "");
	form_hidden_box("hidden_policy_trees", (isset($_GET["policy_trees"]) ? get_request_var("policy_trees") : "2"), "");
	form_hidden_box("hidden_policy_devices", (isset($_GET["policy_devices"]) ? get_request_var("policy_devices") : "2"), "");
	form_hidden_box("hidden_policy_graph_templates", (isset($_GET["policy_graph_templates"]) ? get_request_var("policy_graph_templates") : "2"), "");
	form_hidden_box("save_component_user", "1", "");

}

/**
 * edit user permissions for graphs
 */

function graph_perms_edit() {
	require(CACTI_BASE_PATH . "/include/auth/auth_arrays.php");

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	if (!empty($_GET["id"])) {
		$policy = db_fetch_row("SELECT policy_graphs,policy_trees,policy_devices,policy_graph_templates FROM user_auth WHERE id = " . get_request_var("id"));

		$header_label = __("[edit: ") . db_fetch_cell("SELECT username FROM user_auth WHERE id = " . get_request_var("id")) . "]";
	}else{
		$header_label = __("[new]");
	}

	?>
	<script type="text/javascript">
	<!--
	$().ready(function() {
		$("#device").autocomplete("user_admin.php?action=ajax_get_devices_detailed", { max: 8, highlight: false, scroll: true, scrollHeight: 300 });
		$("#device").result(function(event, data, formatted) {
			if (data) {
				$(this).parent().find("#perm_devices").val(data[1]);
			}else{
				$(this).parent().find("#perm_devices").val(0);
			}
		});
		$("#graph").autocomplete("user_admin.php?action=ajax_get_graphs_brief&id=<?php print get_request_var("id", 0);?>", { max: 8, highlight: false, scroll: true, scrollHeight: 300 });
		$("#graph").result(function(event, data, formatted) {
			if (data) {
				$(this).parent().find("#perm_graphs").val(data[1]);
			}else{
				$(this).parent().find("#perm_graphs").val(0);
			}
		});
		$("#graph_templates").autocomplete("user_admin.php?action=ajax_get_graph_templates", { max: 8, highlight: false, scroll: true, scrollHeight: 300 });
		$("#graph_templates").result(function(event, data, formatted) {
			if (data) {
				$(this).parent().find("#perm_graph_templates").val(data[1]);
			}else{
				$(this).parent().find("#perm_graph_templates").val(0);
			}
		});
	});
	//-->
	</script>
	<?php

	#print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='user_admin'>\n";
	/* box: graph permissions */
	html_start_box(__("Graph Permissions (By Graph)"), "100", "3", "center", "");

	$graphs = db_fetch_assoc("SELECT
		graph_templates_graph.local_graph_id AS id,
		graph_templates_graph.title_cache AS name
		FROM graph_templates_graph
		LEFT JOIN user_auth_perms ON (graph_templates_graph.local_graph_id=user_auth_perms.item_id AND user_auth_perms.type=" . PERM_GRAPHS . ")
		WHERE graph_templates_graph.local_graph_id > 0
		AND user_auth_perms.user_id = " . get_request_var("id", 0) . "
		ORDER BY graph_templates_graph.title_cache");

	?>

	<tr>
		<td class="nw100">
			<font class="textEditTitle" title="The default allow/deny graph policy for this user"><?php print __("Default Policy");?></font>
		</td>
		<td align="left">
			<?php form_dropdown("policy_graphs",$graph_policy_array,"","",$policy["policy_graphs"],"",""); ?>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<table width="100%" cellpadding="1" cellspacing="0">
				<?php
				if (sizeof($graphs) > 0) {
					foreach ($graphs as $item) {
						form_alternate_row_color("graph" . $item["id"], true);
						print "<td><strong>" . $item["name"] . "</strong>" . __(" - ") . (($policy["policy_graphs"] == AUTH_CONTROL_DATA_POLICY_ALLOW) ? __("No Access") : __("Accessible")) . "</td>
								<td align='right'><a href='" . htmlspecialchars("user_admin.php?action=perm_remove&type=graph&id=" . $item["id"] . "&user_id=" . $_GET["id"]) . "'><img class='buttonSmall' src='images/delete_icon.gif' alt='" . __("Delete") . "' align='absmiddle'></a>&nbsp;</td>\n";
						form_end_row();
					}
				}else{
					print "<tr><td><em>" . __("No Graphs") . "</em></td></tr>";
				}
				?>
			</table>
		</td>
	</tr>
	<?php

	html_end_box(false);

	?>
	<table align='left'>
		<tr>
			<td width='40'>
				<input type="submit" value="<?php print __("Add");?>" name="add_graph_y">
			</td>
			<td align='left' width='1'>
				<input class="ac_field" type="text" id="graph" size="70" value="">
				<input type="hidden" id="perm_graphs" name="perm_graphs">
			</td>
		</tr>
	</table>
	<?php

	/* box: device permissions */
	html_start_box(__("Graph Permissions (By Device)"), "100", "3", "center", "");

	$devices = db_fetch_assoc("SELECT
		device.id,
		CONCAT('',device.description,' (',device.hostname,')') as name
		FROM device
		LEFT JOIN user_auth_perms ON (device.id = user_auth_perms.item_id AND user_auth_perms.type = " . PERM_DEVICES . ")
		WHERE user_auth_perms.user_id = " . get_request_var("id", 0) . "
		ORDER BY device.description,device.hostname");

	?>
	<tr>
		<td class="nw100">
			<font class="textEditTitle" title="<?php print __("The default allow/deny graph policy for this user");?>"><?php print __("Default Policy");?></font>
		</td>
		<td align="left">
			<?php form_dropdown("policy_devices",$graph_policy_array,"","",$policy["policy_devices"],"",""); ?>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<table width="100%" cellpadding="1" cellspacing="0">
				<?php
				if (sizeof($devices)) {
					foreach ($devices as $item) {
						form_alternate_row_color("device" . $item["id"], true);
						print "<td><strong>" . $item["name"] . "</strong>" . __(" - ") . (($policy["policy_devices"] == AUTH_CONTROL_DATA_POLICY_ALLOW) ? __("No Access") : __("Accessible")) . "</td>
								<td align='right'><a href='" . htmlspecialchars("user_admin.php?action=perm_remove&type=device&id=" . $item["id"] . "&user_id=" . $_GET["id"]) . "'><img class='buttonSmall' src='images/delete_icon.gif' alt='" . __("Delete") . "' align='absmiddle'></a>&nbsp;</td>\n";
						form_end_row();
					}
				}else{
					print "<tr><td><em>" . __("No Devices") . "</em></td></tr>";
				}
				?>
			</table>
		</td>
	</tr>
	<?php

	html_end_box(false);

	?>
	<table align='left'>
		<tr>
			<td width='40'>
				&nbsp;<input type="submit" value="<?php print __("Add");?>" name="add_device_y">
			</td>
			<td align='left' width='1'>
				<input class="ac_field" type="text" id="device" size="70" value="">
				<input type="hidden" id="perm_devices" name="perm_devices">
			</td>
		</tr>
	</table>
	<?php

	/* box: graph template permissions */
	html_start_box(__("Graph Permissions (By Graph Template)"), "100", "3", "center", "");

	$graph_templates = db_fetch_assoc("SELECT
		graph_templates.id,
		graph_templates.name
		from graph_templates
		LEFT JOIN user_auth_perms ON (graph_templates.id = user_auth_perms.item_id AND user_auth_perms.type = " . PERM_GRAPH_TEMPLATES . ")
		WHERE user_auth_perms.user_id = " . get_request_var("id", 0) . "
		ORDER BY graph_templates.name");

	?>
	<tr>
		<td class="nw100">
			<font class="textEditTitle" title="<?php print __("The default allow/deny graph policy for this user");?>"><?php print __("Default Policy");?></font>
		</td>
		<td align="left">
			<?php form_dropdown("policy_graph_templates",$graph_policy_array,"","",$policy["policy_graph_templates"],"",""); ?>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<table width="100%" cellpadding="1" cellspacing="0">
				<?php
				if (sizeof($graph_templates)) {
					foreach ($graph_templates as $item) {
						form_alternate_row_color("templates" . $item["id"], true);
						print "<td><strong>" . $item["name"] . "</strong>" . __(" - ") . (($policy["policy_graph_templates"] == AUTH_CONTROL_DATA_POLICY_ALLOW) ? __("No Access") : __("Accessible")) . "</td>
								<td align='right'><a href='" . htmlspecialchars("user_admin.php?action=perm_remove&type=graph_template&id=" . $item["id"] . "&user_id=" . $_GET["id"]) . "'><img class='buttonSmall' src='images/delete_icon.gif' alt='" . __("Delete") . "' align='absmiddle'></a>&nbsp;</td>\n";
						form_end_row();
					}
				}else{
					print "<tr><td><em>" . __("No Graph Templates") . "</em></td></tr>";
				}
				?>
			</table>
		</td>
	</tr>
	<?php

	html_end_box(false);

	?>
	<table align='left'>
		<tr>
			<td width='40'>
				&nbsp;<input type="submit" value="<?php print __("Add");?>" name="add_graph_template_y">
			</td>
			<td align='left' width='1'>
				<input class="ac_field" type="text" id="graph_templates" size="70" value="">
				<input type="hidden" id="perm_graph_templates" name="perm_graph_templates">
			</td>
		</tr>
	</table>
	<?php

	/* box: tree permissions */
	html_start_box(__("Tree Permissions"), "100", "3", "center", "");

	$trees = db_fetch_assoc("SELECT
		graph_tree.id,
		graph_tree.name
		from graph_tree
		LEFT JOIN user_auth_perms ON (graph_tree.id = user_auth_perms.item_id AND user_auth_perms.type = " . PERM_TREES . ")
		WHERE user_auth_perms.user_id = " . get_request_var("id", 0) . "
		ORDER BY graph_tree.name");

	?>
	<tr>
		<td class="nw100">
			<font class="textEditTitle" title="<?php print __("The default allow/deny graph policy for this user");?>"><?php print __("Default Policy");?></font>
		</td>
		<td align="left">
			<?php form_dropdown("policy_trees",$graph_policy_array,"","",$policy["policy_trees"],"",""); ?>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<table width="100%" cellpadding="1" cellspacing="0">
				<?php
				if (sizeof($trees)) {
					foreach ($trees as $item) {
						form_alternate_row_color("tree" . $item["id"], true);
						print "<td><strong>" . $item["name"] . "</strong>" . __(" - ") . (($policy["policy_trees"] == AUTH_CONTROL_DATA_POLICY_ALLOW) ? __("No Access") : __("Accessible")) . "</td>
							<td align='right'><a href='" . htmlspecialchars("user_admin.php?action=perm_remove&type=tree&id=" . $item["id"] . "&user_id=" . $_GET["id"]) . "'><img class='buttonSmall' src='images/delete_icon.gif' alt='Delete' align='absmiddle'></a>&nbsp;</td>\n";
						form_end_row();
					}
				}else{
					print "<tr><td><em>" . __("No Trees") . "</em></td></tr>";
				}
				?>
			</table>
		</td>
	</tr>
	<?php

	html_end_box(false);

	?>
	<table align='left'>
		<tr>
			<td width='40'>
				&nbsp;<input type="submit" value="<?php print __("Add");?>" name="add_tree_y">
			</td>
			<td align='left' width='1'>
				<?php form_dropdown("perm_trees",db_fetch_assoc("SELECT id, name FROM graph_tree WHERE id NOT IN (SELECT item_id FROM user_auth_perms WHERE user_auth_perms.type=2 AND user_auth_perms.user_id=".get_request_var("id",0)." ) ORDER BY name"),"name","id","","","");?>
			</td>
		</tr>
	</table>
	<?php

	form_hidden_box("id", get_request_var_request("id"), "");
	form_hidden_box("save_component_graph_perms","1","");
}

/**
 * edit user realm associations
 */

function user_realms_edit() {
	global $user_auth_realms;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	html_start_box("", "100", "0", "center", "");

	print "	<tr class='rowHeader'>
			<td class='textHeaderDark'><strong>" . __("Realm Permissions") . "</strong></td>
			<td class='textHeaderDark' width='1%' align='center'><input type='checkbox' style='margin: 0px;' name='all' title='" . __("Select All") . "' onClick='selectAll(\"chk_\",this.checked)'></td>\n
		</tr>\n";

	?>
	<tr>
		<td colspan="2" width="100%">
			<table width="100%">
				<tr>
					<td valign="top" width="50%">
						<?php
						$i = 0;
						while (list($realm_id, $realm_name) = each($user_auth_realms)) {
							if (sizeof(db_fetch_assoc("SELECT realm_id FROM user_auth_realm WHERE user_id = " . get_request_var("id", 0) . " AND realm_id = " . $realm_id)) > 0) {
								$old_value = CHECKED;
							}else{
								$old_value = "";
							}

							$column1 = floor((sizeof($user_auth_realms) / 2) + (sizeof($user_auth_realms) % 2));

							if ($i == $column1) {
								print "</td><td valign='top' width='50%'>";
							}

							form_checkbox("section" . $realm_id, $old_value, $realm_name, "", "", "", (!empty($_GET["id"]) ? 1 : 0)); print "<br>";

							$i++;
						}
						?>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<?php
	html_end_box();

	form_hidden_box("id", get_request_var_request("id"), "");
	form_hidden_box("save_component_realm_perms","1","");
}

/**
 * edit user specific graph settings
 */

function graph_settings_edit() {
	global $settings_graphs, $tabs_graphs;

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var("id"));
	/* ==================================================== */

	html_start_box(__("Graph Settings"), "100", 0, "center", "");

	while (list($tab_short_name, $tab_fields) = each($settings_graphs)) {
		$header_items = array(
			array("name" => $tabs_graphs[$tab_short_name], "align" => 'left'),
			array("name" => "&nbsp;")
		);
		print "<tr><td>";
		html_header($header_items, 1, false, $tab_short_name);

		$form_array = array();

		while (list($field_name, $field_array) = each($tab_fields)) {
			$form_array += array($field_name => $tab_fields[$field_name]);

			if ((isset($field_array["items"])) && (is_array($field_array["items"]))) {
				while (list($sub_field_name, $sub_field_array) = each($field_array["items"])) {
					if (graph_config_value_exists($sub_field_name, get_request_var("id"))) {
						$form_array[$field_name]["items"][$sub_field_name]["form_id"] = 1;
					}

					$form_array[$field_name]["items"][$sub_field_name]["value"] =  db_fetch_cell("SELECT value FROM settings_graphs WHERE name = '" . $sub_field_name . "' AND user_id = " . get_request_var("id"));
				}
			}else{
				if (graph_config_value_exists($field_name, get_request_var("id"))) {
					$form_array[$field_name]["form_id"] = 1;
				}

				$form_array[$field_name]["value"] = db_fetch_cell("select value from settings_graphs where name='$field_name' and user_id=" . $_GET["id"]);
			}
		}

		draw_edit_form(
			array(
				"config" => array(
					"no_form_tag" => true
					),
				"fields" => $form_array
				)
			);
		print "</table></td></tr>";		/* end of html_header */
	}

	# the id tag is required for our js code!
	form_hidden_box("hidden_rrdtool_version", read_config_option("rrdtool_version"), "");
	form_hidden_box("id", get_request_var_request("id"), "");
	form_hidden_box("save_component_graph_settings","1","");

	html_end_box();

	include_once(CACTI_BASE_PATH . "/access/js/colorpicker.js");
	include_once(CACTI_BASE_PATH . "/access/js/graph_template_options.js");
}

/* --------------------------
    User Administration
   -------------------------- */
/**
 * global control function for user edit
 */
function user_edit($tabs = false) {

	if (!$tabs) {
		/* ================= input validation ================= */
		input_validate_input_number(get_request_var("id"));
		/* ==================================================== */

		if (!empty($_GET["id"])) {
			$user = db_fetch_row("SELECT * FROM user_auth WHERE id = " . get_request_var("id"));
			$header_label = __("[edit: ") . $user["username"] . "]";
		}else{
			$user = array();
			$header_label = __("[new]");
		}
	
		plugin_hook_function('user_admin_edit', (isset($user) ? get_request_var("id") : 0));

		print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='user_edit'>\n";

		switch ($_REQUEST["tab"]) {
			case "user_edit":
				user_global_edit($user);
				break;
			case "graph_settings_edit":
				graph_settings_edit();
				break;
			case "user_realms_edit":
				user_realms_edit();
				break;
			case "graph_perms_edit":
				graph_perms_edit();
				break;
			default:
				if (!plugin_hook_function('user_admin_run_action', get_request_var_request("action"))) {
					user_realms_edit();
				}

		}

		form_save_button_alt("return!user_admin.php");
	}else{
		$user_tabs = array(
			"user_edit" => array("name" => __("General Settings"), "title" => __("General Settings are common settings for all users.")),
			"user_realms_edit" => array("name" => __("Realm Permissions"), "title" => __("Realm permissions control which sections of Cacti this user will have access to.")),
			"graph_perms_edit" => array("name" => __("Graph Permissions"), "title" => __("Graph policies will be evaluated in the order shown until a match is found.")),
			"graph_settings_edit" => array("name" => __("Graph Settings"), "title" => __("Graph settings control how graphs are displayed for this user.")));

		print "<table width='100%' cellspacing='0' cellpadding='0' align='center'><tr>";
		print "<td><div id='tabs_user'>";

		$i = 1;
		if (sizeof($user_tabs)) {
			print "<ul>";
			foreach (array_keys($user_tabs) as $tab_short_name) {
				print "<li><a id='tabs-$i' href='" . htmlspecialchars("user_admin.php?action=$tab_short_name&tab=" . $tab_short_name . "&id=" . get_request_var("id")) . "'>" . $user_tabs[$tab_short_name]["name"] . "</a></li>";
				$i++;
	
				if (empty($_GET["id"])) break;
			}
			print "</ul>";
		}

		print "</div></td></tr></table>\n";

		print "<script type='text/javascript'>
			$().ready(function() {
				$('#tabs_user').tabs({ cookie: { expires: 30 } });
			});
		</script>\n";
	}

}

/**
 * process page variables to govern table display
 * e.g. page number we're on, total number of rows ...
 */
function user_process_page_variables() {
	$page_variables = array(
		"page" => array("type" => "numeric", "method" => "request", "default" => "1"),
		"rows" => array("type" => "numeric", "method" => "request", "default" => "-1"),
		"filter" => array("type" => "string", "method" => "request", "default" => ""),
		"sort_column" => array("type" => "string", "method" => "request", "default" => "username"),
		"sort_direction" => array("type" => "string", "method" => "request", "default" => "ASC"));

	if (isset($_REQUEST["clear"])) {
		$clear = true;
	}else{
		$clear = false;
	}

	html_verify_request_variables($page_variables, "sess_user_admin", $clear);
}

/**
 * draw the filter(s) that are displayed on the page
 */
function user_filter() {
	global $item_rows;

	html_start_box(__('User Management'), "100", "3", "center", "user_admin.php?action=edit", true);
	?>
	<tr class='rowAlternate3'>
		<td>
			<form name="form_user_admin" action="user_admin.php">
			<table cellpadding="0" cellspacing="0">
				<tr>
					<td class="nw50">
						<?php print __("Search:");?>&nbsp;
					</td>
					<td class="w1">
						<input type="text" name="filter" size="40" value="<?php print html_get_page_variable("filter");?>">
					</td>
					<td class="nw50">
						&nbsp;<?php print __("Rows:");?>&nbsp;
					</td>
					<td class="w1">
						<select name="rows" onChange="applyFilterChange(document.form_user_admin)">
							<option value="-1"<?php if (html_get_page_variable("rows") == "-1") {?> selected<?php }?>>Default</option>
							<?php
							if (sizeof($item_rows) > 0) {
							foreach ($item_rows as $key => $value) {
								print "<option value='" . $key . "'"; if (html_get_page_variable("rows") == $key) { print " selected"; } print ">" . $value . "</option>\n";
							}
							}
							?>
						</select>
					</td>
					<td class="nw120">
						&nbsp;<input type="button" onClick="applyFilterChange(document.form_user_admin)" value="<?php echo __('Go');?>" name="go" align="middle">
						<input type="submit" value="<?php echo __('Clear');?>" name="clear" align="middle">
					</td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
	<?php
	html_end_box(false);
	?>
	<script type="text/javascript">
	<!--
	function applyFilterChange(objForm) {
		strURL = '?rows=' + objForm.rows.value;
		strURL = strURL + '&filter=' + objForm.filter.value;
		document.location = strURL;
	}
	-->
	</script>
	<?php
}

/**
 * fetch all records that are required for displaying
 * @param int $total_rows	number of rows available in the table, accepting the filter that was set
 * 							this number is returned by the function
 * @param int $rowspp		set the number of rows that shall be fetched and displayed
 */
function user_get_records(&$total_rows, &$rowspp) {
	/* form the 'where' clause for our main sql query */
	if (strlen(html_get_page_variable("filter"))) {
		$sql_where = "WHERE (user_auth.username LIKE '%" . html_get_page_variable("filter") . "%' OR user_auth.full_name LIKE '%" . html_get_page_variable("filter") . "%')";
	}else{
		$sql_where = "";
	}

	/* initial value for rows per page */
	if (html_get_page_variable("rows") == "-1") {
		$rowspp = read_config_option("num_rows_device");
	}else{
		$rowspp = html_get_page_variable("rows");
	}

	/* fetch number of total rows available, honor the filter defined */
	$total_rows = db_fetch_cell("SELECT
		COUNT(user_auth.id)
		FROM user_auth
		$sql_where");

	/* return the rows, honoring
	 *    the filter
	 *    the number of rows for display
	 */
	return db_fetch_assoc("SELECT
		id,
		user_auth.username,
		full_name,
		realm,
		enabled,
		policy_graphs,
		time,
		max(time) as dtime
		FROM user_auth
		LEFT JOIN user_log ON (user_auth.id = user_log.user_id)
		$sql_where
		GROUP BY id
		ORDER BY " . html_get_page_variable("sort_column") . " " . html_get_page_variable("sort_direction") .
		" LIMIT " . ($rowspp * (html_get_page_variable("page") - 1)) . "," . $rowspp);
}

/**
 * define the table layout
 * e.g. columns, specific column formatting via callback functions, filtering and the like
 */
function user_get_table_format() {
	return array(
		"username" => array(
			"name" => __("User Name"),
			"link" => true,
			"filter" => true,
			"order" => "ASC"
		),
		"full_name" => array(
			"name" => __("Full Name"),
			"filter" => true,
			"order" => "ASC"
		),
		"enabled" => array(
			"name" => __("Enabled"),
			"function" => "display_user_status",
			"params" => array("enabled"),
			"order" => "ASC"
		),
		"realm" => array(
			"name" => __("Realm"),
			"function" => "display_auth_realms",
			"params" => array("realm"),
			"order" => "ASC"
		),
		"policy_graphs" => array(
			"name" => __("Default Graph Policy"),
			"function" => "display_policy_graphs",
			"params" => array("policy_graphs"),
			"order" => "ASC"
		),
		"dtime" => array(
			"name" => __("Last Login"),
			"function" => "display_last_login",
			"params" => array("dtime"),
			"order" => "DESC",
			"align" => "right"
		)
	);
}

/**
 * display the user list
 */
function user() {
	global $user_actions;
	require(CACTI_BASE_PATH . "/include/auth/auth_arrays.php");

	$total_rows = 0; $rowspp = 0;

	user_process_page_variables();
	user_filter();

	$rows = user_get_records($total_rows, $rowspp);

	html_draw_table(user_get_table_format(), $rows, $total_rows, $rowspp, html_get_page_variable("page"), "id", "user_admin.php",
		$user_actions, html_get_page_variable("filter"), true, true, true,
		html_get_page_variable("sort_column"), html_get_page_variable("sort_direction"));
}

