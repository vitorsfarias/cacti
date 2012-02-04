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

/* allow the upgrade script to run for as long as it needs to */
ini_set('max_execution_time', '0');

/* let's not repopulate the poller cache unless we have to */
$repopulate = false;

require_once('../include/global.php');
require_once('../lib/html.php');
require_once('../lib/html_utility.php');

/* default value for this variable */
if (!isset($_REQUEST["install_type"])) {
	$_REQUEST["install_type"] = 0;
}


if (empty($_REQUEST["step"])) {
	$_REQUEST["step"] = 1;
}

// Do any necessary changes here from previous submits
switch (get_request_var_request("step")) {
	case "1":
		break;
	case "2":
		break;
	case "3":
		break;
	case "4":
		if (isset($_POST['database_default'])) {
			if (install_write_config ()) {
				include('../include/config.php');
				db_connect_real($database_hostname, $database_username, $database_password, $database_default, $database_type, $database_port, $database_ssl);
				if (!install_check_db_connection ()) {
					$_REQUEST["error"] = 'NoAccess';
					$_REQUEST["step"] = 3;
				}
			} else {
				$_REQUEST["error"] = 'NoWrite';
				$_REQUEST["step"] = 3;
			}
		}
		break;
	case "5":
		if (!install_check_db_connection ()) {
			$_REQUEST["step"] = 3;
		} else {

			if (get_request_var_request("install_type") == "1") {
				include_once('../lib/data_query.php');
				include_once("..//lib/utility.php");

				kill_session_var("sess_config_array");
				kill_session_var("sess_device_cache_array");
				/* just in case we have hard drive graphs to deal with */
				$device_id = db_fetch_cell("SELECT id FROM device WHERE hostname = '127.0.0.1'");
				if (!empty($device_id)) {
					run_data_query($device_id, 6);
				}
			}
			if (get_request_var_request("install_type") == "3") {
				$cacti_versions = array('0.8', '0.8.1', '0.8.2', '0.8.2a', '0.8.3', '0.8.3a', '0.8.4', '0.8.5', '0.8.5a',
					'0.8.6', '0.8.6a', '0.8.6b', '0.8.6c', '0.8.6d', '0.8.6e', '0.8.6f', '0.8.6g', '0.8.6h', '0.8.6i', '0.8.6j', '0.8.6k',
					'0.8.7', '0.8.7a', '0.8.7b', '0.8.7c', '0.8.7d', '0.8.7e', '0.8.7f', '0.8.7g', '0.8.7h', '0.8.7i',
					'1.0.0');
	

				if(!$database_empty) {
					$old_cacti_version = db_fetch_cell('SELECT cacti FROM version');
				} else {
					$old_cacti_version = '';
				}

				/* try to find current (old) version in the array */
				$old_version_index = array_search($old_cacti_version, $cacti_versions);

				/* if the version is not found, die */
				if (!is_int($old_version_index)) {
					print "	<p style='font-family: Verdana, Arial; font-size: 16px; font-weight: bold; color: red;'>" . __("Error") . "</p>
							<p style='font-family: Verdana, Arial; font-size: 12px;'>" . __("Invalid Cacti version %1\$s,  cannot upgrade to %2\$s", "<strong>$old_cacti_version</strong>", "<strong>" . CACTI_VERSION . "</strong>") . "</p>";
					exit;
				}

				/* loop from the old version to the current, performing updates for each version in between */
				for ($i = ($old_version_index+1); $i < count($cacti_versions); $i++) {
					if ($cacti_versions[$i] == "0.8.1") {
						include ("0_8_to_0_8_1.php");
						upgrade_to_0_8_1();
					} elseif ($cacti_versions[$i] == "0.8.2") {
						include ("0_8_1_to_0_8_2.php");
						upgrade_to_0_8_2();
					} elseif ($cacti_versions[$i] == "0.8.2a") {
						include ("0_8_2_to_0_8_2a.php");
						upgrade_to_0_8_2a();
					} elseif ($cacti_versions[$i] == "0.8.3") {
						include ("0_8_2a_to_0_8_3.php");
						include_once("../lib/utility.php");
						upgrade_to_0_8_3();
					} elseif ($cacti_versions[$i] == "0.8.4") {
						include ("0_8_3_to_0_8_4.php");
						upgrade_to_0_8_4();
					} elseif ($cacti_versions[$i] == "0.8.5") {
						include ("0_8_4_to_0_8_5.php");
						upgrade_to_0_8_5();
					} elseif ($cacti_versions[$i] == "0.8.6") {
						include ("0_8_5a_to_0_8_6.php");
						upgrade_to_0_8_6();
					} elseif ($cacti_versions[$i] == "0.8.6a") {
						include ("0_8_6_to_0_8_6a.php");
						upgrade_to_0_8_6a();
					} elseif ($cacti_versions[$i] == "0.8.6d") {
						include ("0_8_6c_to_0_8_6d.php");
						upgrade_to_0_8_6d();
					} elseif ($cacti_versions[$i] == "0.8.6e") {
						include ("0_8_6d_to_0_8_6e.php");
						upgrade_to_0_8_6e();
					} elseif ($cacti_versions[$i] == "0.8.6g") {
						include ("0_8_6f_to_0_8_6g.php");
						upgrade_to_0_8_6g();
					} elseif ($cacti_versions[$i] == "0.8.6h") {
						include ("0_8_6g_to_0_8_6h.php");
						upgrade_to_0_8_6h();
					} elseif ($cacti_versions[$i] == "0.8.6i") {
						include ("0_8_6h_to_0_8_6i.php");
						upgrade_to_0_8_6i();
					} elseif ($cacti_versions[$i] == "0.8.7") {
						include ("0_8_6j_to_0_8_7.php");
						upgrade_to_0_8_7();
					} elseif ($cacti_versions[$i] == "0.8.7a") {
						include ("0_8_7_to_0_8_7a.php");
						upgrade_to_0_8_7a();
					} elseif ($cacti_versions[$i] == "0.8.7b") {
						include ("0_8_7a_to_0_8_7b.php");
						upgrade_to_0_8_7b();
					} elseif ($cacti_versions[$i] == "0.8.7c") {
						include ("0_8_7b_to_0_8_7c.php");
						upgrade_to_0_8_7c();
					} elseif ($cacti_versions[$i] == "0.8.7d") {
						include ("0_8_7c_to_0_8_7d.php");
						upgrade_to_0_8_7d();
					} elseif ($cacti_versions[$i] == "0.8.7e") {
						include ("0_8_7d_to_0_8_7e.php");
						upgrade_to_0_8_7e();
					} elseif ($cacti_versions[$i] == "0.8.7f") {
						include ("0_8_7e_to_0_8_7f.php");
						upgrade_to_0_8_7f();
					} elseif ($cacti_versions[$i] == "0.8.7g") {
						include ("0_8_7f_to_0_8_7g.php");
						upgrade_to_0_8_7g();
					} elseif ($cacti_versions[$i] == "0.8.7h") {
						include ("0_8_7g_to_0_8_7h.php");
						upgrade_to_0_8_7h();
					} elseif ($cacti_versions[$i] == "0.8.7i") {
						include ("0_8_7h_to_0_8_7i.php");
						upgrade_to_0_8_7i();
					} elseif ($cacti_versions[$i] == "1.0.0") {
						include ("0_8_7i_to_1_0_0.php");
						upgrade_to_1_0_0();
					}
				}
			}
		}
		break;
	case "6":
		if (!install_check_db_connection ()) $_REQUEST["step"] = 3;
		include_once('../lib/data_query.php');
		include_once("..//lib/utility.php");
		$input = install_file_paths();
		/* get all items on the form and write values for them  */
		while (list($name, $array) = each($input)) {
			if (isset($_POST[$name])) {
				db_execute("REPLACE INTO settings (name, value) VALUES ('$name', '" . get_request_var_post($name) . "')");
			}
		}
		setcookie(session_name(),"",time() - 3600,"/");
		break;
	case "7":
		if (!install_check_db_connection ()) $_REQUEST["step"] = 3;

		break;
	case "8":
		if (!install_check_db_connection ()) $_REQUEST["step"] = 3;

		break;
	case "9":
		if (!install_check_db_connection ()) $_REQUEST["step"] = 3;
		db_execute("delete from version");
		db_execute("insert into version (cacti) values ('" . CACTI_VERSION . "')");
		break;
	case "10":
		header ("Location: ../index.php");
		exit;
		break;
}



// Now we display the next page
switch (get_request_var_request("step")) {
	case "1":
		install_page_header (get_request_var_request("step"));

		print '<p>' . __('Thanks for taking the time to download and install Cacti, the complete graphing solution for your network. Before you can start making cool graphs, there are a few pieces of data that cacti needs to know.') . '<p>';
		print '<p>' . __('Make sure you have read and followed the required steps needed to install cacti before continuing. Install information can be found for ');
		if (CACTI_SERVER_OS == "win32") {
			print '<a href="../docs/html/install_windows.html">Windows</a>';
		} else {
			print '<a href="../docs/html/install_unix.html">Unix / Linux</a>';
		}
		print '<p>';

		print '<p>' .  __('Cacti is licensed under the GNU General Public License v2, you must agree to its provisions before continuing:') . '<p>';
		print '<hr><div style="height:400px;width:100%;overflow-y:scroll;background-color:white;">';
		install_print_license();
		print '</div><hr>';
		print '<p>&nbsp;&nbsp;&nbsp;&nbsp;<input type=checkbox name=IAgree onclick="theChecker()">&nbsp;&nbsp;&nbsp;&nbsp;I agree to the above license agreement';
		print '<script type="text/javascript">
				function theChecker() { 
					if (document.installform.IAgree.checked==false) {
						document.installform.installButton.disabled=true;
					} else {
						document.installform.installButton.disabled=false;
					}
				}
			</script>';
		install_page_footer(false);

		break;
	case "2":		/* PHP Modules */

		install_page_header (get_request_var_request("step"));

		print '<h2>Pre-installation Check</h2><br>';
		print 'Cacti requies several PHP Modules to be installed to work properly.  If any of these are not installed, you will be unable to continue the installation until corrected.<br><br>';

		html_start_box("<strong> " . __("Required PHP Modules") . "</strong>", "30", 0, "", "", false);
		html_header(array(array('name' => 'Name'), array('name' => 'Required'), array('name' => 'Installed')));

		form_alternate_row_color();
		form_selectable_cell('PHP Version', '');
		form_selectable_cell('5.2.0+', '');
		form_selectable_cell((version_compare(PHP_VERSION, '5.2.0', '<') ? "<font color=red>" . PHP_VERSION . "</font>" : "<font color=green>" . PHP_VERSION . "</font>"), '');
		form_end_row();

		$extensions = array( array('name' => 'session', 'installed' => false),
					array('name' => 'sockets', 'installed' => false),
					array('name' => 'mysql', 'installed' => false),
					array('name' => 'xml', 'installed' => false),
					array('name' => 'pcre', 'installed' => false),
					array('name' => 'json', 'installed' => false),
				);

		$ext = verify_php_extensions($extensions);
		$i = 0;
		$enabled = true;
		foreach ($ext as $e) {
			form_alternate_row_color();
			form_selectable_cell($e['name'], '');
			form_selectable_cell('<font color=green>Yes</font>', '');
			form_selectable_cell(($e['installed'] ? '<font color=green>Yes</font>' : '<font color=red>NO</font>'), '');
			form_end_row();
			if (!$e['installed']) $enabled = false;
		}
		html_end_box(false);

		print '<br>' . __('<br>These extensions may increase the performance of your Cacti install but are not necessary.<br><br>');
		$extensions = array( array('name' => 'snmp', 'installed' => false),
					array('name' => 'mysqli', 'installed' => false),
					array('name' => 'gd', 'installed' => false),

				);

		$ext = verify_php_extensions($extensions);
		$i = 0;
		html_start_box("<strong> " . __("Other Modules") . "</strong>", "30", 0, "", "", false);
		html_header(array(array('name' => 'Name'), array('name' => 'Required'), array('name' => 'Installed')));
		foreach ($ext as $e) {
			form_alternate_row_color();
			//print '<td>' . $e['name'] . '</td><td><font color=green>Yes</font></td><td>' . ($e['installed'] ? '<font color=green>Yes</font>' : '<font color=red>NO</font>') . '</td>';
			form_selectable_cell($e['name'], '');
			form_selectable_cell('<font color=green>Yes</font>', '');
			form_selectable_cell(($e['installed'] ? '<font color=green>Yes</font>' : '<font color=red>NO</font>'), '');
			form_end_row();
		}
		html_end_box(false);

		install_page_footer($enabled);

		break;
	case "3":	/* Database Setup */
		install_page_header (get_request_var_request("step"));
		$error = get_request_var_request("error");
		if ($error == 'NoWrite') {
			print '<p><font color=red>' . __('ERROR: There was an issue writing the include/config.php file.') . '</font></p>';
		}
		if ($error == 'NoAccess') {
			print '<p><font color=red>' . __('ERROR: Could not connect to the database') . '</font></p>';
		}
		print '<p>' . __("The following information has been determined from Cacti's configuration file.") . '<p>';

		if (file_exists('../include/config.php')) {
			if (is_writable('../include/config.php')) {
				$writeaccess = "Writable";
			} else {
				$writeaccess = "Config file is not writable";
			}
		} else {
			$writeaccess = "Config file does not exist";
		}
		html_start_box('', '100%', '3', '', '');
		$form_array = array(
			"database_settings" => array(
				"friendly_name" => "Database Configuration Options",
				"method" => "spacer",
				),
			"writeaccess" => array(
				"friendly_name" => "Write Access to config file",
				"description" => "This will display whether the webserver has write access to the include/config.php file.  The installer will need access to this file in order to update the configuration settings.  After setup, you should remove these permissions.",
				"method" => "text",
				"default" => "",
				"value" => $writeaccess,
				),
			"database_type" => array(
				"friendly_name" => "Database Type",
				"description" => "This is the type of database that will be used for your cacti install.",
				"method" => "drop_array",
				"default" => "mysql",
				"value" => "mysql",
				"array" => array("mysql"),
				),
			"database_hostname" => array(
				"friendly_name" => "Database Hostname",
				"description" => "This is the hostname used to connect to your database.",
				"method" => "textbox",
				"default" => "127.0.0.1",
				"value" => $database_hostname,
				"max_length" => "60"
				),
			"database_port" => array(
				"friendly_name" => "Database Port",
				"description" => "This is the port used to connect to the database.",
				"method" => "textbox",
				"default" => "3306",
				"value" => $database_port,
				"max_length" => "6"
				),
			"database_default" => array(
				"friendly_name" => "Database Name",
				"description" => "This is the name of the database to connect to.",
				"method" => "textbox",
				"default" => "cacti",
				"value" => $database_default,
				"max_length" => "32"
				),
			"database_username" => array(
				"friendly_name" => "Database Username",
				"description" => "This is the username used to authenicate to the database",
				"method" => "textbox",
				"default" => "cactiuser",
				"value" => $database_username,
				"max_length" => "32"
				),
			"database_password" => array(
				"friendly_name" => "Database Password",
				"description" => "This is the password used to authenicate to the database.",
				"method" => "textbox_password",
				"default" => "",
				"value" => "",
				"max_length" => "32"
				),
			"database_ssl" => array(
				"friendly_name" => "Database SSL",
				"description" => "Whether the database connection utilizes SSL.",
				"method" => "checkbox",
				"default" => "off",
				"value" => ($database_ssl ? true : false),
				),
		);
		draw_edit_form(
			array(
				'config' => array(
					'no_form_tag' => true
					),
				'fields' => $form_array
			)
		);

		html_end_box();
		print '</p>';
		install_page_footer();
		break;
	case "4":		/* Type of Installation */
		install_page_header (get_request_var_request("step"));
		$old_cacti_version = db_fetch_cell('SELECT cacti FROM version');
		print '<p>' . __("Please select the type of installation") . '<p>';
		print '<p><select name="install_type">';
		if ($old_cacti_version == 'new_install' || $old_cacti_version == '') {
			print '<option value="1"' . ($default_install_type == "1" ? " selected" : "") . '>' . __("New Install") . '</option>';
		} else {
			print '<option value="3"' . ($default_install_type == "3" ? " selected" : "") . '>' . __("Upgrade from cacti 0.8.x") . '</option>';
		}
		print '</select></p><br><br>';
		install_page_footer();
		break;
	case "5":		/* File Paths */
		$i = 0;
		install_page_header (get_request_var_request("step"));
		$input = install_file_paths();
		print '<p>' . __("Make sure all of these values are correct before continuing.") . '<p>';
		$installsettings = array();
		$installsettings = $settings['path'];

		unset($installsettings['path_rrdtool_default_font']);
		$installsettings['versions_header'] = $settings['general']['versions_header'];
		$installsettings['versions_header'] = $settings['general']['versions_header'];
		$installsettings['snmp_version'] = $settings['general']['snmp_version'];
		$installsettings['rrdtool_version'] = $settings['general']['rrdtool_version'];
		$installsettings['extended_paths']['value'] = 'off';
		while (list($name, $array) = each($input)) {
			if (isset($input[$name])) {
				$installsettings[$name]['value'] = $array["default"];
			}
		}
		html_start_box('', '60', '3', '', '');
		draw_edit_form(
			array(
				'config' => array(
					'no_form_tag' => true
					),
				'fields' => $installsettings
			)
		);
		html_end_box(false);
		print '<p><br><br>';

		install_page_footer();
		break;
	case "6":		/* Plugin Setup */
		install_page_header (get_request_var_request("step"));
		print "<h1>Plugin Setup</h1>";
		print "Cacti has a plethora of plugins to enchance your monitoring capabilities.  Please select any from the list below that you would like to utilize.<br><br>";
		$pluginslist = retrieve_plugin_list();
		$i = 0;
		html_start_box('<strong>Plugins</strong>', '100', '3', 'center', "");
		html_header_checkbox(array(array('name' => 'Directory'), array('name' => 'Name'), array('name' => 'Description'), array('name' => 'Version'), array('name' => 'Author')));
		foreach ($plugins as $p) {
			form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $p['id']); $i++;
			form_selectable_cell($p['directory'], $p['id']);
			form_selectable_cell($p['name'], $p['id']);
			form_selectable_cell($p['desc'], $p['id']);
			form_selectable_cell($p['version'], $p['id']);
			form_selectable_cell($p['author'], $p['id']);
			form_checkbox_cell($p['name'], $p['id']);
			form_end_row();
		}
		html_end_box(false);
		install_page_footer();
		break;
	case "7":		/* Template Setup */
		install_page_header (get_request_var_request("step"));
		print "<h1>Template Setup</h1>";
		print "Templates allow you to monitor and graph a vast assortment of data within Cacti.  While the base Cacti install provides basic templates for most devices, you can select a few extra templates below to include in your install.<br><br>";
		print "<form name='chk' method='post' action='start.php'>";

		$templates = plugin_setup_get_templates();

		html_start_box('<strong>Templates</strong>', '100%', '3', 'center', "");
		html_header_checkbox(array(array('name' => 'Name'), array('name' => 'Description'), array('name' => 'Author'), array('name' => 'Homepage')));
		$i = 0;
		foreach ($templates as $id => $p) {
			form_alternate_row_color($colors["alternate"], $colors["light"], $i, 'line' . $id); $i++;
			form_selectable_cell($p['name'], $id);
			form_selectable_cell($p['description'], $id);
			form_selectable_cell($p['author'], $id);
			if ($p['homepage'] != '') {
				form_selectable_cell("<a href='". $p['homepage'] . "' target=_new>" . $p['homepage'] . "</a>", $id);
			} else {
				form_selectable_cell('', $id);
			}
			form_checkbox_cell($p['name'], $id);
			form_end_row();
		}
		html_end_box(false);

		install_page_footer();
		break;
	case "8":		/* Settings */
		install_page_header (get_request_var_request("step"));

		print "<h1>Settings Setup</h1>";
		print "To ensure proper functionality of your Cacti install, there are several settings that are required to be configured.<br><br>";

		html_start_box('', '60', '3', '', '');
		$form_array = array(
			"settings_dns_header" => array(
				"friendly_name" => "DNS Options",
				"method" => "spacer",
				),
			"settings_dns_primary" => array(
				"friendly_name" => "Primary DNS IP Address",
				"description" => "Enter the primary DNS IP Address to utilize for reverse lookups.",
				"method" => "textbox",
				"default" => "",
				"value" => "",
				"max_length" => "30"
				),
			"settings_dns_secondary" => array(
				"friendly_name" => "Secondary DNS IP Address",
				"description" => "Enter the secondary DNS IP Address to utilize for reverse lookups.",
				"method" => "textbox",
				"default" => "",
				"value" => "",
				"max_length" => "30"
				),
			"settings_dns_timeout" => array(
				"friendly_name" => "DNS Timeout",
				"description" => "Please enter the DNS timeout in milliseconds.  Cacti uses a PHP based DNS resolver.",
				"method" => "textbox",
				"default" => "500",
				"value" => "",
				"max_length" => "10"
				),
		);
		draw_edit_form(
			array(
				'config' => array(
					'no_form_tag' => true
					),
				'fields' => $form_array
			)
		);

		html_end_box();

		install_page_footer();
		break;
	case "9":
		install_page_header (get_request_var_request("step"));
		print '<h2>' . __("Complete") . '</h2>';
		print __('Your Cacti installation is now complete and ready to be utilized.  If you have any issues, please feel free to drop by our forums!');
		install_page_footer();

	case "88":
				?>

						<p><?php echo __("Upgrade results:"); ?></p>

						<?php
						$current_version  = "";
						$upgrade_results = "";
						$failed_sql_query = false;

						$fail_text    = "<span class=\"warning\">[" . __("Fail") ."]</span>&nbsp;";
						$success_text = "<span class=\"success\">[" . __("Success") . "]</span>&nbsp;";
						$fail_message = "<span class=\"warning\">[" . __("Message") . "]</span>&nbsp;";

						if (isset($_SESSION["sess_sql_install_cache"])) {
							while (list($index, $arr1) = each($_SESSION["sess_sql_install_cache"])) {
								while (list($version, $arr2) = each($arr1)) {
									while (list($status, $sql) = each($arr2)) {
										if ($current_version != $version) {
											$version_index = array_search($version, $cacti_versions);
											$upgrade_results .= "<p><strong>" . $cacti_versions{$version_index-1}  . " -> " . $cacti_versions{$version_index} . "</strong></p>\n";
										}

										$upgrade_results .= "<p class='code'>" . (($status == FALSE) ? $fail_text . $sql[0] . "<br>" . $fail_message . $sql[1] : $success_text . $sql) . "</p>\n";

										/* if there are one or more failures, make a note because we are going to print
										out a warning to the user later on */
										if ($status == 0) {
											$failed_sql_query = true;
										}

										$current_version = $version;
									}
								}
							}

							kill_session_var("sess_sql_install_cache");
						} else{
							print "<em>" . __("No SQL queries have been executed.") . "</em>";
						}

						if ($failed_sql_query == true) {
							print "<p><strong><font color='#FF0000'>" . __("WARNING:") . "</font></strong> " . __("One or more of the SQL queries needed to upgraded your Cacti installation has failed. Please see below for more details. Your Cacti MySQL user must have <strong>SELECT, INSERT, UPDATE, DELETE, ALTER, CREATE, and DROP</strong>permissions. For each query that failed, you should evaluate the error message returned and take appropriate action.") . "</p>\n";
						}

						print $upgrade_results;
						?>

				<?php
						break;
}

function verify_php_extensions($extensions) {
	for ($i = 0; $i < count($extensions); $i++) {
		if (extension_loaded($extensions[$i]['name'])){
			$extensions[$i]['installed'] = true;
		}
	}
	return $extensions;
}

function db_install_execute($cacti_version, $sql) {
	global $cnn_id;

	$sql_install_cache = (isset($_SESSION["sess_sql_install_cache"]) ? $_SESSION["sess_sql_install_cache"] : array());

	if (db_execute($sql)) {
		$sql_install_cache{sizeof($sql_install_cache)}[$cacti_version][TRUE] = $sql;
	} else{
		$sql_install_cache{sizeof($sql_install_cache)}[$cacti_version][FALSE] = array($sql, $cnn_id->ErrorMsg());
//		$sql_install_cache{sizeof($sql_install_cache)}[$cacti_version][2] = $cnn_id->ErrorMsg();
	}

	$_SESSION["sess_sql_install_cache"] = $sql_install_cache;
}

function find_best_path($binary_name) {
	if (CACTI_SERVER_OS == "win32") {
		$pgf = getenv("ProgramFiles");
		$pgf64 = getenv("ProgramW6432");

		if (strlen($pgf64)) {
			$search_paths[] = $pgf64 . "/php";
			$search_paths[] = $pgf64 . "/rrdtool";
			$search_paths[] = $pgf64 . "/net-snmp/bin";
		}
		$search_paths[] = $pgf . "/php";
		$search_paths[] = $pgf . "/rrdtool";
		$search_paths[] = $pgf . "/net-snmp/bin";
		$search_paths[] = "c:/php";
		$search_paths[] = "c:/cacti";
		$search_paths[] = "c:/spine";
		$search_paths[] = "c:/usr/bin";
		$search_paths[] = "c:/usr/net-snmp/bin";
		$search_paths[] = "c:/rrdtool";
		$search_paths[] = "d:/php";
		$search_paths[] = "d:/cacti";
		$search_paths[] = "d:/spine";
		$search_paths[] = "d:/usr/bin";
		$search_paths[] = "d:/usr/net-snmp/bin";
		$search_paths[] = "d:/rrdtool";
	} else{
		$search_paths = array("/bin", "/sbin", "/usr/bin", "/usr/sbin", "/usr/local/bin", "/usr/local/sbin");
	}

	foreach ($search_paths as $path) {
		if ((file_exists($path . "/" . $binary_name)) && (is_readable($path . "/" . $binary_name))) {
			return $path . "/" . $binary_name;
		}
	}
}


function plugin_setup_get_templates() {
	$templates = Array(
			'Linux - IO Wait.xml.gz',
			'Linux - Load Average.xml.gz',
			'Local - Poller Statistics.xml.gz',
			'Website - Connection Statistics.xml.gz',
			'Windows - Services.xml.gz',
			);

	$path = CACTI_BASE_PATH . '/install/templates';
	$info = Array();
	foreach ($templates as $xmlfile) {
		$filename = "compress.zlib:///$path/$xmlfile";
		$xml = file_get_contents($filename);;
		//Loading Template Information from package
		$xmlget = simplexml_load_string($xml); 
		$data = ToArray($xmlget);
		if (is_array($data['info']['author'])) $data['info']['author'] = '';
		if (is_array($data['info']['email'])) $data['info']['email'] = '';
		if (is_array($data['info']['description'])) $data['info']['description'] = '';
		if (is_array($data['info']['homepage'])) $data['info']['homepage'] = '';

		$data['info']['filename'] = $xmlfile;
		$info[] = $data['info'];
	}
	return $info;
}

function retrieve_plugin_list() {
	$plugins = Array(
			'Test Plugin.xml.gz',
			);

	$path = CACTI_BASE_PATH . '/install/plugins';
	$info = Array();
	foreach ($plugins as $xmlfile) {
		$filename = "compress.zlib:///$path/$xmlfile";
		$xml = @file_get_contents($filename);
		if ($xml) {
			$xmlget = simplexml_load_string($xml); 
			$data = ToArray($xmlget);
			if (is_array($data['info']['author'])) $data['info']['author'] = '';
			if (is_array($data['info']['email'])) $data['info']['email'] = '';
			if (is_array($data['info']['description'])) $data['info']['description'] = '';
			if (is_array($data['info']['homepage'])) $data['info']['homepage'] = '';
			$data['info']['desc'] = '';
			$data['info']['filename'] = $xmlfile;
			$info[] = $data['info'];
		}
	}
	return $info;
}

function ToArray ($data) {
	if (is_object($data)) {
		$data = get_object_vars($data);
	}
	return (is_array($data)) ? array_map(__FUNCTION__,$data) : $data;
}

function install_check_db_connection () {
	global $database_sessions, $database_default;
	if (isset($database_sessions[$database_default]) && $database_sessions[$database_default])
		return TRUE;
	return FALSE;
}

function install_check_db_old () {
	global $old_cacti_version;



	if(!$database_empty) {
		$old_cacti_version = db_fetch_cell('SELECT cacti FROM version');
	} else {
		$old_cacti_version = '';
	}

	/* try to find current (old) version in the array */
	$old_version_index = array_search($old_cacti_version, $cacti_versions);

	/* do a version check */
	if ($old_cacti_version == CACTI_VERSION) {
		print "	<p style='font-family: Verdana, Arial; font-size: 16px; font-weight: bold; color: red;'>" . __("Error") . "</p>
			<p style='font-family: Verdana, Arial; font-size: 12px;'>" . __("This installation is already up-to-date. Click <a href='../index.php'>here</a> to use cacti.") . "</p>";
		exit;
	} elseif (preg_match("/^0\.6/", $old_cacti_version)) {
		print "	<p style='font-family: Verdana, Arial; font-size: 16px; font-weight: bold; color: red;'>" . __("Error") . "</p>
			<p style='font-family: Verdana, Arial; font-size: 12px;'>" . __("You are attempting to install cacti %s	onto a 0.6.x database. To continue, you must create a new database, import 'cacti.sql' into it, and update 'include/config.php' to point to the new database.", CACTI_VERSION) . "</p>";
		exit;
	} elseif (empty($old_cacti_version)) {
		print "	<p style='font-family: Verdana, Arial; font-size: 16px; font-weight: bold; color: red;'>Error</p>
			<p style='font-family: Verdana, Arial; font-size: 12px;'>" . __("You have created a new database, but have not yet imported the 'cacti.sql' file. At the command line, execute the following to continue:") . "</p>
			<p><pre>mysql -u $database_username -p $database_default < cacti.sql</pre></p>
			<p>" . __("This error may also be generated if the cacti database user does not have correct permissions on the cacti database. Please ensure that the cacti database user has the ability to SELECT, INSERT, DELETE, UPDATE, CREATE, ALTER, DROP, INDEX on the cacti database.") . "</p>";
		exit;
	}
}

function install_file_paths () {
	global $settings;

	/* Here, we define each name, default value, type, and path check for each value
	we want the user to input. The "name" field must exist in the 'settings' table for
	this to work. Cacti also uses different default values depending on what OS it is
	running on. */

	/* RRDTool Binary Path */
	$input = array();
	$input["path_rrdtool"] = $settings["path"]["path_rrdtool"];

	if (CACTI_SERVER_OS == "unix") {
		$which_rrdtool = find_best_path("rrdtool");

		if (config_value_exists("path_rrdtool")) {
			$input["path_rrdtool"]["default"] = read_config_option("path_rrdtool");
		} else if (!empty($which_rrdtool)) {
			$input["path_rrdtool"]["default"] = $which_rrdtool;
		} else{
			$input["path_rrdtool"]["default"] = "/usr/local/bin/rrdtool";
		}
	} elseif (CACTI_SERVER_OS == "win32") {
		$which_rrdtool = find_best_path("rrdtool.exe");

		if (config_value_exists("path_rrdtool")) {
			$input["path_rrdtool"]["default"] = read_config_option("path_rrdtool");
		} else if (!empty($which_rrdtool)) {
			$input["path_rrdtool"]["default"] = $which_rrdtool;
		} else{
			$input["path_rrdtool"]["default"] = "c:/rrdtool/rrdtool.exe";
		}
	}

	/* PHP Binary Path */
	$input["path_php_binary"] = $settings["path"]["path_php_binary"];

	if (CACTI_SERVER_OS == "unix") {
		$which_php = find_best_path("php");

		if (config_value_exists("path_php_binary")) {
			$input["path_php_binary"]["default"] = read_config_option("path_php_binary");
		} else if (!empty($which_php)) {
			$input["path_php_binary"]["default"] = $which_php;
		} else{
			$input["path_php_binary"]["default"] = "/usr/bin/php";
		}
	} elseif (CACTI_SERVER_OS == "win32") {
		$which_php = find_best_path("php.exe");

		if (config_value_exists("path_php_binary")) {
			$input["path_php_binary"]["default"] = read_config_option("path_php_binary");
		} else if (!empty($which_php)) {
			$input["path_php_binary"]["default"] = $which_php;
		} else{
			$input["path_php_binary"]["default"] = "c:/php/php.exe";
		}
	}

	/* Perl Binary Path */
	$input["path_perl_binary"] = $settings["path"]["path_perl_binary"];

	if (CACTI_SERVER_OS == "unix") {
		$which_perl = find_best_path("perl");

		if (config_value_exists("path_perl_binary")) {
			$input["path_perl_binary"]["default"] = read_config_option("path_perl_binary");
		} else if (!empty($which_perl)) {
			$input["path_perl_binary"]["default"] = $which_perl;
		} else{
			$input["path_perl_binary"]["default"] = "/usr/bin/perl";
		}
	} elseif (CACTI_SERVER_OS == "win32") {
		$which_perl = find_best_path("perl.exe");

		if (config_value_exists("path_perl_binary")) {
			$input["path_perl_binary"]["default"] = read_config_option("path_perl_binary");
		} else if (!empty($which_perl)) {
			$input["path_perl_binary"]["default"] = $which_perl;
		} else{
			$input["path_perl_binary"]["default"] = "c:/perl/perl.exe";
		}
	}

	/* Shell Binary Path */
	$input["path_sh_binary"] = $settings["path"]["path_sh_binary"];

	if (CACTI_SERVER_OS == "unix") {
		$which_sh = find_best_path("sh");

		if (config_value_exists("path_sh_binary")) {
			$input["path_sh_binary"]["default"] = read_config_option("path_sh_binary");
		} else if (!empty($which_sh)) {
			$input["path_sh_binary"]["default"] = $which_sh;
		} else{
			$input["path_sh_binary"]["default"] = "/bin/sh";
		}
	} elseif (CACTI_SERVER_OS == "win32") {
		$which_sh = find_best_path("sh.exe");

		if (config_value_exists("path_sh_binary")) {
			$input["path_sh_binary"]["default"] = read_config_option("path_sh_binary");
		} else if (!empty($which_sh)) {
			$input["path_sh_binary"]["default"] = $which_sh;
		} else{
			$input["path_sh_binary"]["default"] = "c:/sh/sh.exe";
		}
	}

	/* fc_list Binary Path */
	$input["path_fc_list_binary"] = $settings["path"]["path_fc_list_binary"];

	if (CACTI_SERVER_OS == "unix") {
		$which_fc_list = find_best_path("fc-list");

		if (config_value_exists("path_fc_list_binary")) {
			$input["path_fc_list_binary"]["default"] = read_config_option("path_fc_list_binary");
		} else if (!empty($which_fc_list)) {
			$input["path_fc_list_binary"]["default"] = $which_fc_list;
		} else{
			$input["path_fc_list_binary"]["default"] = "/usr/bin/fc_list";
		}
	} elseif (CACTI_SERVER_OS == "win32") {
		$which_fc_list = find_best_path("fc_list.exe");

		if (config_value_exists("path_fc_list_binary")) {
			$input["path_fc_list_binary"]["default"] = read_config_option("path_fc_list_binary");
		} else if (!empty($which_fc_list)) {
			$input["path_fc_list_binary"]["default"] = $which_fc_list;
		} else{
			$input["path_fc_list_binary"]["default"] = "c:/rrdtool/fc-list.exe";
		}
	}

	/* Font Dir Path */
	$input["path_font_dir"] = $settings["path"]["path_font_dir"];

	if (CACTI_SERVER_OS == "unix") {

		if (config_value_exists("path_font_dir")) {
			$input["path_font_dir"]["default"] = read_config_option("path_font_dir");
		} else{
			$input["path_font_dir"]["default"] = "/usr/share/fonts";
		}
	} elseif (CACTI_SERVER_OS == "win32") {

		if (config_value_exists("path_font_dir")) {
			$input["path_font_dir"]["default"] = read_config_option("path_font_dir");
		} else{
			$input["path_font_dir"]["default"] = "%WINDIR%/Fonts";
		}
	}

	/* snmpwalk Binary Path */
	$input["path_snmpwalk"] = $settings["path"]["path_snmpwalk"];

	if (CACTI_SERVER_OS == "unix") {
		$which_snmpwalk = find_best_path("snmpwalk");

		if (config_value_exists("path_snmpwalk")) {
			$input["path_snmpwalk"]["default"] = read_config_option("path_snmpwalk");
		} else if (!empty($which_snmpwalk)) {
			$input["path_snmpwalk"]["default"] = $which_snmpwalk;
		} else{
			$input["path_snmpwalk"]["default"] = "/usr/local/bin/snmpwalk";
		}
	} elseif (CACTI_SERVER_OS == "win32") {
		$which_snmpwalk = find_best_path("snmpwalk.exe");

		if (config_value_exists("path_snmpwalk")) {
			$input["path_snmpwalk"]["default"] = read_config_option("path_snmpwalk");
		} else if (!empty($which_snmpwalk)) {
			$input["path_snmpwalk"]["default"] = $which_snmpwalk;
		} else{
			$input["path_snmpwalk"]["default"] = "c:/net-snmp/bin/snmpwalk.exe";
		}
	}

	/* snmpget Binary Path */
	$input["path_snmpget"] = $settings["path"]["path_snmpget"];

	if (CACTI_SERVER_OS == "unix") {
		$which_snmpget = find_best_path("snmpget");

		if (config_value_exists("path_snmpget")) {
			$input["path_snmpget"]["default"] = read_config_option("path_snmpget");
		} else if (!empty($which_snmpget)) {
			$input["path_snmpget"]["default"] = $which_snmpget;
		} else{
			$input["path_snmpget"]["default"] = "/usr/local/bin/snmpget";
		}
	} elseif (CACTI_SERVER_OS == "win32") {
		$which_snmpget = find_best_path("snmpget.exe");

		if (config_value_exists("path_snmpget")) {
			$input["path_snmpget"]["default"] = read_config_option("path_snmpget");
		} else if (!empty($which_snmpget)) {
			$input["path_snmpget"]["default"] = $which_snmpget;
		} else{
			$input["path_snmpget"]["default"] = "c:/net-snmp/bin/snmpget.exe";
		}
	}

	/* snmpbulkwalk Binary Path */
	$input["path_snmpbulkwalk"] = $settings["path"]["path_snmpbulkwalk"];

	if (CACTI_SERVER_OS == "unix") {
		$which_snmpbulkwalk = find_best_path("snmpbulkwalk");

		if (config_value_exists("path_snmpbulkwalk")) {
			$input["path_snmpbulkwalk"]["default"] = read_config_option("path_snmpbulkwalk");
		} else if (!empty($which_snmpbulkwalk)) {
			$input["path_snmpbulkwalk"]["default"] = $which_snmpbulkwalk;
		} else{
			$input["path_snmpbulkwalk"]["default"] = "/usr/local/bin/snmpbulkwalk";
		}
	} elseif (CACTI_SERVER_OS == "win32") {
		$which_snmpbulkwalk = find_best_path("snmpbulkwalk.exe");

		if (config_value_exists("path_snmpbulkwalk")) {
			$input["path_snmpbulkwalk"]["default"] = read_config_option("path_snmpbulkwalk");
		} else if (!empty($which_snmpbulkwalk)) {
			$input["path_snmpbulkwalk"]["default"] = $which_snmpbulkwalk;
		} else{
			$input["path_snmpbulkwalk"]["default"] = "c:/net-snmp/bin/snmpbulkwalk.exe";
		}
	}

	/* snmpgetnext Binary Path */
	$input["path_snmpgetnext"] = $settings["path"]["path_snmpgetnext"];

	if (CACTI_SERVER_OS == "unix") {
		$which_snmpgetnext = find_best_path("snmpgetnext");

		if (config_value_exists("path_snmpgetnext")) {
			$input["path_snmpgetnext"]["default"] = read_config_option("path_snmpgetnext");
		} else if (!empty($which_snmpgetnext)) {
			$input["path_snmpgetnext"]["default"] = $which_snmpgetnext;
		} else{
			$input["path_snmpgetnext"]["default"] = "/usr/local/bin/snmpgetnext";
		}
	} elseif (CACTI_SERVER_OS == "win32") {
		$which_snmpgetnext = find_best_path("snmpgetnext.exe");

		if (config_value_exists("path_snmpgetnext")) {
			$input["path_snmpgetnext"]["default"] = read_config_option("path_snmpgetnext");
		} else if (!empty($which_snmpgetnext)) {
			$input["path_snmpgetnext"]["default"] = $which_snmpgetnext;
		} else{
			$input["path_snmpgetnext"]["default"] = "c:/net-snmp/bin/snmpgetnext.exe";
		}
	}

	/* log file path */
	$input["path_cactilog"] = $settings["path"]["path_cactilog"];
	$input["path_cactilog"]["description"] = "The path to your Cacti log file.";
	if (config_value_exists("path_cactilog")) {
		$input["path_cactilog"]["default"] = read_config_option("path_cactilog");
	} else {
		$input["path_cactilog"]["default"] = CACTI_BASE_PATH . "/log/cacti.log";
	}

	/* spine Binary Path */
	$input["path_spine"] = $settings["path"]["path_spine"];

	if (CACTI_SERVER_OS == "unix") {
		$which_spine = find_best_path("spine");

		if (config_value_exists("path_spine")) {
			$input["path_spine"]["default"] = read_config_option("path_spine");
		} else if (!empty($which_spine)) {
			$input["path_spine"]["default"] = $which_spine;
		} else{
			$input["path_spine"]["default"] = "/usr/local/bin/spine";
		}
	} elseif (CACTI_SERVER_OS == "win32") {
		$which_spine = find_best_path("spine.exe");

		if (config_value_exists("path_spine")) {
			$input["path_spine"]["default"] = read_config_option("path_spine");
		} else if (!empty($which_spine)) {
			$input["path_spine"]["default"] = $which_spine;
		} else{
			$input["path_spine"]["default"] = "c:/spine/spine.exe";
		}
	}

	/* SNMP Version */
	if (CACTI_SERVER_OS == "unix") {
		$input["snmp_version"] = $settings["general"]["snmp_version"];
		$input["snmp_version"]["default"] = "net-snmp";
	}

	/* RRDTool Version */
	if ((file_exists($input["path_rrdtool"]["default"])) && ((CACTI_SERVER_OS == "win32") || (is_executable($input["path_rrdtool"]["default"]))) ) {
		$input["rrdtool_version"] = $settings["general"]["rrdtool_version"];

		$out_array = array();
		exec(cacti_escapeshellcmd($input["path_rrdtool"]["default"]), $out_array);

		if (sizeof($out_array) > 0) {
			if (preg_match("/^RRDtool 1\.4/", $out_array[0])) {
				$input["rrdtool_version"]["default"] = RRD_VERSION_1_4;
			} else if (preg_match("/^RRDtool 1\.3/", $out_array[0])) {
				$input["rrdtool_version"]["default"] = RRD_VERSION_1_3;
			} else if (preg_match("/^RRDtool 1\.2\./", $out_array[0])) {
				$input["rrdtool_version"]["default"] = RRD_VERSION_1_2;
			} else if (preg_match("/^RRDtool 1\.0\./", $out_array[0])) {
				$input["rrdtool_version"]["default"] = RRD_VERSION_1_0;
			}
		}
	}
	return $input;
}


function install_page_header($step) {
print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Cacti</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
	<meta http-equiv="Content-Script-Type" content="text/javascript" >
	<meta http-equiv="Content-Style-Type" content="text/css">
	<link type="text/css" href="../include/main.css" rel="stylesheet">
	<link type="text/css" href="../include/dd.css" rel="stylesheet">
	<link type="text/css" href="../include/jquery.autocomplete.css" rel="stylesheet">
	<link type="text/css" media="screen" href="../include/css/cacti_dd_menu.css" rel="stylesheet">
	<link type="text/css" media="screen" href="../include/css/jquery-ui.css" rel="stylesheet">
	<link href="../images/favicon.ico" rel="shortcut icon">
	<script type="text/javascript" src="../include/js/jquery/jquery.js"></script>
	<script type="text/javascript" src="../include/js/jquery/jquery-ui.js"></script>
	<script type="text/javascript" src="../include/js/layout.js"></script>
	<script type="text/javascript" src="../include/layout.php"></script>
	<script type="text/javascript" src="../include/js/jquery/jquery.autocomplete.js"></script>
	<script type="text/javascript" src="../include/js/jquery/jquery.bgiframe.js"></script>
	<script type="text/javascript" src="../include/js/jquery/jquery.ajaxQueue.js"></script>
	<script type="text/javascript" src="../include/js/jquery/jquery.tablednd.js"></script>
	<script type="text/javascript" src="../include/js/jquery/jquery.dropdown.js"></script>
	<script type="text/javascript" src="../include/js/jquery/jquery.dd.js"></script>
	<script type="text/javascript" src="../include/js/jquery/colorpicker.js"></script>
</head>
<body class=body>
<div id=header>
	<div id=logobar></div>
	<div id=navbar>
		<div id=navbar_l>
			<ul>';
			//	echo draw_header_tab("console", __("Installation"), "index.php");

print '		</ul>
		</div>
	</div>
	<div id=navbrcrumb>
		<div style=\'float:left\'>
			Cacti Installation
		</div>
	</div>
</div>

<div id=wrapper>
	<div id=menu>';
		$menu = Array(1 => 'License', 2 => 'Pre-Installation', 3 => 'Database Check', 4 => 'Install / Upgrade', 5 => 'File Paths', 6 => 'Plugin Install', 7 => 'Template Install', 8 => 'Settings', 9 => 'Complete');
		install_draw_menu ($menu, $step);
		print '		<div style=\'text-align:center; padding:20px\'><img src=\'../images/cacti_logo.gif\' alt=Cacti></div>
	</div>
	<div id="vsplitter" >
		<div id="vsplitter_toggle" ></div>
	</div>

	<div id=content style="font-size:15px;">';


	
	print '<form name="installform" method="post" action="index.php">';


}

function install_page_footer($enabled = true) {
	print 	'<p align="right"><input name="installButton" type="submit" value="' . (get_request_var_request("step") == "9" ? __("Finish") : __("Next")) . '" title="' . (get_request_var_request("step") == "9" ? __("Finish Install"): __("Proceed to Next Step")) . '"' . ($enabled == false ? ' disabled="disabled"' : '') . '></p>


	<input type="hidden" name="step" value="' . ($_REQUEST["step"]+1) . '">

	</form>

	</div> 
</div> 
<div id=footer><br> 
</div> 
</body> 
</html>';
}

function install_draw_menu ($menu, $step) {
	print "<div id=0_div class=menuMain>" . __("Installation") . "</div><div><ul id=0 class=menuSubMain>";
	$background = "../images/menu_line.gif";
	$i = 1;
	foreach ($menu as $id => $item_title) {
		print "<li " . ($i == $step ? "class='menuSubMainSelected'" : '') . "><a>$item_title</a></li>";
		$i++;
	}
	print "</ul></div>";
}


function install_print_license() {
	print ' <h2>GNU General Public License v2</h2>
<h3>Table of Contents</h3> 
<ul> 
 
  <li><a name="TOC1" href="#SEC1">GNU GENERAL PUBLIC
  LICENSE<!--TRANSLATORS: Don\'t translate the license; copy msgid\'s
  verbatim!--></a> 
    <ul> 
      <li><a name="TOC2" href="#SEC2">Preamble</a></li> 
      <li><a name="TOC3" href="#SEC3">TERMS AND CONDITIONS
      FOR COPYING, DISTRIBUTION AND MODIFICATION</a></li> 
      <li><a name="TOC4" href="#SEC4">How to Apply These
      Terms to Your New Programs</a></li> 
    </ul></li> 
</ul> 
 
<hr /> 
 
<h3><a name="SEC1" href="#TOC1">GNU GENERAL PUBLIC LICENSE</a></h3> 
<p> 
Version 2, June 1991
</p> 
 
<pre> 
Copyright (C) 1989, 1991 Free Software Foundation, Inc.  
51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 
Everyone is permitted to copy and distribute verbatim copies
of this license document, but changing it is not allowed.
</pre> 
 
<h3><a name="preamble"></a><a name="SEC2" href="#TOC2">Preamble</a></h3> 
 
<p> 
  The licenses for most software are designed to take away your
freedom to share and change it.  By contrast, the GNU General Public
License is intended to guarantee your freedom to share and change free
software--to make sure the software is free for all its users.  This
General Public License applies to most of the Free Software
Foundation\'s software and to any other program whose authors commit to
using it.  (Some other Free Software Foundation software is covered by
the GNU Lesser General Public License instead.)  You can apply it to
your programs, too.
</p> 
 
<p> 
  When we speak of free software, we are referring to freedom, not
price.  Our General Public Licenses are designed to make sure that you
have the freedom to distribute copies of free software (and charge for
this service if you wish), that you receive source code or can get it
if you want it, that you can change the software or use pieces of it
in new free programs; and that you know you can do these things.
</p> 
 
<p> 
  To protect your rights, we need to make restrictions that forbid
anyone to deny you these rights or to ask you to surrender the rights.
These restrictions translate to certain responsibilities for you if you
distribute copies of the software, or if you modify it.
</p> 
 
<p> 
  For example, if you distribute copies of such a program, whether
gratis or for a fee, you must give the recipients all the rights that
you have.  You must make sure that they, too, receive or can get the
source code.  And you must show them these terms so they know their
rights.
</p> 
 
<p> 
  We protect your rights with two steps: (1) copyright the software, and
(2) offer you this license which gives you legal permission to copy,
distribute and/or modify the software.
</p> 
 
<p> 
  Also, for each author\'s protection and ours, we want to make certain
that everyone understands that there is no warranty for this free
software.  If the software is modified by someone else and passed on, we
want its recipients to know that what they have is not the original, so
that any problems introduced by others will not reflect on the original
authors\' reputations.
</p> 
 
<p> 
  Finally, any free program is threatened constantly by software
patents.  We wish to avoid the danger that redistributors of a free
program will individually obtain patent licenses, in effect making the
program proprietary.  To prevent this, we have made it clear that any
patent must be licensed for everyone\'s free use or not licensed at all.
</p> 
 
<p> 
  The precise terms and conditions for copying, distribution and
modification follow.
</p> 
 
 
<h3><a name="terms"></a><a name="SEC3" href="#TOC3">TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION</a></h3> 
 
 
<a name="section0"></a><p> 
<strong>0.</strong> 
 This License applies to any program or other work which contains
a notice placed by the copyright holder saying it may be distributed
under the terms of this General Public License.  The "Program", below,
refers to any such program or work, and a "work based on the Program"
means either the Program or any derivative work under copyright law:
that is to say, a work containing the Program or a portion of it,
either verbatim or with modifications and/or translated into another
language.  (Hereinafter, translation is included without limitation in
the term "modification".)  Each licensee is addressed as "you".
</p> 
 
<p> 
Activities other than copying, distribution and modification are not
covered by this License; they are outside its scope.  The act of
running the Program is not restricted, and the output from the Program
is covered only if its contents constitute a work based on the
Program (independent of having been made by running the Program).
Whether that is true depends on what the Program does.
</p> 
 
<a name="section1"></a><p> 
<strong>1.</strong> 
 You may copy and distribute verbatim copies of the Program\'s
source code as you receive it, in any medium, provided that you
conspicuously and appropriately publish on each copy an appropriate
copyright notice and disclaimer of warranty; keep intact all the
notices that refer to this License and to the absence of any warranty;
and give any other recipients of the Program a copy of this License
along with the Program.
</p> 
 
<p> 
You may charge a fee for the physical act of transferring a copy, and
you may at your option offer warranty protection in exchange for a fee.
</p> 
 
<a name="section2"></a><p> 
<strong>2.</strong> 
 You may modify your copy or copies of the Program or any portion
of it, thus forming a work based on the Program, and copy and
distribute such modifications or work under the terms of Section 1
above, provided that you also meet all of these conditions:
</p> 
 
<dl> 
  <dt></dt> 
    <dd> 
      <strong>a)</strong> 
      You must cause the modified files to carry prominent notices
      stating that you changed the files and the date of any change.
    </dd> 
  <dt></dt> 
    <dd> 
      <strong>b)</strong> 
      You must cause any work that you distribute or publish, that in
      whole or in part contains or is derived from the Program or any
      part thereof, to be licensed as a whole at no charge to all third
      parties under the terms of this License.
    </dd> 
  <dt></dt> 
    <dd> 
      <strong>c)</strong> 
      If the modified program normally reads commands interactively
      when run, you must cause it, when started running for such
      interactive use in the most ordinary way, to print or display an
      announcement including an appropriate copyright notice and a
      notice that there is no warranty (or else, saying that you provide
      a warranty) and that users may redistribute the program under
      these conditions, and telling the user how to view a copy of this
      License.  (Exception: if the Program itself is interactive but
      does not normally print such an announcement, your work based on
      the Program is not required to print an announcement.)
    </dd> 
</dl> 
 
<p> 
These requirements apply to the modified work as a whole.  If
identifiable sections of that work are not derived from the Program,
and can be reasonably considered independent and separate works in
themselves, then this License, and its terms, do not apply to those
sections when you distribute them as separate works.  But when you
distribute the same sections as part of a whole which is a work based
on the Program, the distribution of the whole must be on the terms of
this License, whose permissions for other licensees extend to the
entire whole, and thus to each and every part regardless of who wrote it.
</p> 
 
<p> 
Thus, it is not the intent of this section to claim rights or contest
your rights to work written entirely by you; rather, the intent is to
exercise the right to control the distribution of derivative or
collective works based on the Program.
</p> 
 
<p> 
In addition, mere aggregation of another work not based on the Program
with the Program (or with a work based on the Program) on a volume of
a storage or distribution medium does not bring the other work under
the scope of this License.
</p> 
 
<a name="section3"></a><p> 
<strong>3.</strong> 
 You may copy and distribute the Program (or a work based on it,
under Section 2) in object code or executable form under the terms of
Sections 1 and 2 above provided that you also do one of the following:
</p> 
 
<!-- we use this doubled UL to get the sub-sections indented, --> 
<!-- while making the bullets as unobvious as possible. --> 
 
<dl> 
  <dt></dt> 
    <dd> 
      <strong>a)</strong> 
      Accompany it with the complete corresponding machine-readable
      source code, which must be distributed under the terms of Sections
      1 and 2 above on a medium customarily used for software interchange; or,
    </dd> 
  <dt></dt> 
    <dd> 
      <strong>b)</strong> 
      Accompany it with a written offer, valid for at least three
      years, to give any third party, for a charge no more than your
      cost of physically performing source distribution, a complete
      machine-readable copy of the corresponding source code, to be
      distributed under the terms of Sections 1 and 2 above on a medium
      customarily used for software interchange; or,
    </dd> 
  <dt></dt> 
    <dd> 
      <strong>c)</strong> 
      Accompany it with the information you received as to the offer
      to distribute corresponding source code.  (This alternative is
      allowed only for noncommercial distribution and only if you
      received the program in object code or executable form with such
      an offer, in accord with Subsection b above.)
    </dd> 
</dl> 
 
<p> 
The source code for a work means the preferred form of the work for
making modifications to it.  For an executable work, complete source
code means all the source code for all modules it contains, plus any
associated interface definition files, plus the scripts used to
control compilation and installation of the executable.  However, as a
special exception, the source code distributed need not include
anything that is normally distributed (in either source or binary
form) with the major components (compiler, kernel, and so on) of the
operating system on which the executable runs, unless that component
itself accompanies the executable.
</p> 
 
<p> 
If distribution of executable or object code is made by offering
access to copy from a designated place, then offering equivalent
access to copy the source code from the same place counts as
distribution of the source code, even though third parties are not
compelled to copy the source along with the object code.
</p> 
 
<a name="section4"></a><p> 
<strong>4.</strong> 
 You may not copy, modify, sublicense, or distribute the Program
except as expressly provided under this License.  Any attempt
otherwise to copy, modify, sublicense or distribute the Program is
void, and will automatically terminate your rights under this License.
However, parties who have received copies, or rights, from you under
this License will not have their licenses terminated so long as such
parties remain in full compliance.
</p> 
 
<a name="section5"></a><p> 
<strong>5.</strong> 
 You are not required to accept this License, since you have not
signed it.  However, nothing else grants you permission to modify or
distribute the Program or its derivative works.  These actions are
prohibited by law if you do not accept this License.  Therefore, by
modifying or distributing the Program (or any work based on the
Program), you indicate your acceptance of this License to do so, and
all its terms and conditions for copying, distributing or modifying
the Program or works based on it.
</p> 
 
<a name="section6"></a><p> 
<strong>6.</strong> 
 Each time you redistribute the Program (or any work based on the
Program), the recipient automatically receives a license from the
original licensor to copy, distribute or modify the Program subject to
these terms and conditions.  You may not impose any further
restrictions on the recipients\' exercise of the rights granted herein.
You are not responsible for enforcing compliance by third parties to
this License.
</p> 
 
<a name="section7"></a><p> 
<strong>7.</strong> 
 If, as a consequence of a court judgment or allegation of patent
infringement or for any other reason (not limited to patent issues),
conditions are imposed on you (whether by court order, agreement or
otherwise) that contradict the conditions of this License, they do not
excuse you from the conditions of this License.  If you cannot
distribute so as to satisfy simultaneously your obligations under this
License and any other pertinent obligations, then as a consequence you
may not distribute the Program at all.  For example, if a patent
license would not permit royalty-free redistribution of the Program by
all those who receive copies directly or indirectly through you, then
the only way you could satisfy both it and this License would be to
refrain entirely from distribution of the Program.
</p> 
 
<p> 
If any portion of this section is held invalid or unenforceable under
any particular circumstance, the balance of the section is intended to
apply and the section as a whole is intended to apply in other
circumstances.
</p> 
 
<p> 
It is not the purpose of this section to induce you to infringe any
patents or other property right claims or to contest validity of any
such claims; this section has the sole purpose of protecting the
integrity of the free software distribution system, which is
implemented by public license practices.  Many people have made
generous contributions to the wide range of software distributed
through that system in reliance on consistent application of that
system; it is up to the author/donor to decide if he or she is willing
to distribute software through any other system and a licensee cannot
impose that choice.
</p> 
 
<p> 
This section is intended to make thoroughly clear what is believed to
be a consequence of the rest of this License.
</p> 
 
<a name="section8"></a><p> 
<strong>8.</strong> 
 If the distribution and/or use of the Program is restricted in
certain countries either by patents or by copyrighted interfaces, the
original copyright holder who places the Program under this License
may add an explicit geographical distribution limitation excluding
those countries, so that distribution is permitted only in or among
countries not thus excluded.  In such case, this License incorporates
the limitation as if written in the body of this License.
</p> 
 
<a name="section9"></a><p> 
<strong>9.</strong> 
 The Free Software Foundation may publish revised and/or new versions
of the General Public License from time to time.  Such new versions will
be similar in spirit to the present version, but may differ in detail to
address new problems or concerns.
</p> 
 
<p> 
Each version is given a distinguishing version number.  If the Program
specifies a version number of this License which applies to it and "any
later version", you have the option of following the terms and conditions
either of that version or of any later version published by the Free
Software Foundation.  If the Program does not specify a version number of
this License, you may choose any version ever published by the Free Software
Foundation.
</p> 
 
<a name="section10"></a><p> 
<strong>10.</strong> 
 If you wish to incorporate parts of the Program into other free
programs whose distribution conditions are different, write to the author
to ask for permission.  For software which is copyrighted by the Free
Software Foundation, write to the Free Software Foundation; we sometimes
make exceptions for this.  Our decision will be guided by the two goals
of preserving the free status of all derivatives of our free software and
of promoting the sharing and reuse of software generally.
</p> 
 
<a name="section11"></a><p><strong>NO WARRANTY</strong></p> 
 
<p> 
<strong>11.</strong> 
 BECAUSE THE PROGRAM IS LICENSED FREE OF CHARGE, THERE IS NO WARRANTY
FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE LAW.  EXCEPT WHEN
OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR OTHER PARTIES
PROVIDE THE PROGRAM "AS IS" WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESSED
OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.  THE ENTIRE RISK AS
TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU.  SHOULD THE
PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY SERVICING,
REPAIR OR CORRECTION.
</p> 
 
<a name="section12"></a><p> 
<strong>12.</strong> 
 IN NO EVENT UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING
WILL ANY COPYRIGHT HOLDER, OR ANY OTHER PARTY WHO MAY MODIFY AND/OR
REDISTRIBUTE THE PROGRAM AS PERMITTED ABOVE, BE LIABLE TO YOU FOR DAMAGES,
INCLUDING ANY GENERAL, SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES ARISING
OUT OF THE USE OR INABILITY TO USE THE PROGRAM (INCLUDING BUT NOT LIMITED
TO LOSS OF DATA OR DATA BEING RENDERED INACCURATE OR LOSSES SUSTAINED BY
YOU OR THIRD PARTIES OR A FAILURE OF THE PROGRAM TO OPERATE WITH ANY OTHER
PROGRAMS), EVEN IF SUCH HOLDER OR OTHER PARTY HAS BEEN ADVISED OF THE
POSSIBILITY OF SUCH DAMAGES.
</p> 
 
<h3>END OF TERMS AND CONDITIONS</h3> 
 
Copyright (C) 2011  The Cacti Group
 
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.
 
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
</pre> 
 

';
}

function install_write_config () {
	if (!isset($_POST['database_default'])) {
		return FALSE;
	}

	$config = array();
	$config['url_path'] = "";
	$config['memory_limit'] = "";
	$cacti_session_name = "Cacti";
	$database_ssl = false;

	if (file_exists('../include/config.php')) {
		include('../include/config.php');
	} else {
		@file_put_contents('../include/config.php', '');
	}

	if (!is_writable('../include/config.php')) {
		return FALSE;
	}

	$file = fopen('../include/config.php', 'w');

	input_validate_input_number(get_request_var_post("database_type"));
	input_validate_input_number(get_request_var_post("database_port"));

	$types = array('mysql', 'mysqli');
	$database_type = $types[$_POST['database_type']];
	$database_default  = preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['database_default']);
	$database_hostname = preg_replace('/[^A-Za-z0-9_\.\-]/', '', $_POST['database_hostname']);
	$database_username = preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['database_username']);
	$database_password = preg_replace('/[^A-Za-z0-9_\-\'\!\@\#\%\^\&\*\(\)\{\}\[\]\:\?\<\>]/', '', $_POST['database_password']);
	$database_password_confirm = preg_replace('/[^A-Za-z0-9_\-\'\!\@\#\%\^\&\*\(\)\{\}\[\]\:\?\<\>]/', '', $_POST['database_password_confirm']);
	$database_port = $_POST['database_port'];
	$database_ssl = (isset($_POST['database_ssl']) ? $_POST['database_ssl'] : false);

	fwrite($file, '<?php
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

/* make sure these values refect your actual database/host/user/password */

');

	fwrite ($file, '$database_type = "' . $database_type . "\";\n");
	fwrite ($file, '$database_default = "' . $database_default . "\";\n");
	fwrite ($file, '$database_hostname = "' . $database_hostname . "\";\n");
	fwrite ($file, '$database_username = "' . $database_username . "\";\n");
	fwrite ($file, '$database_password = "' . $database_password . "\";\n");
	fwrite ($file, '$database_port = "' . ($database_port ? $database_port : '3306') . "\";\n");
	fwrite ($file, '$database_ssl = ' . ($database_ssl ? 'true' : 'false') . ";\n\n");
	fwrite ($file, "/* Default session name - Session name must contain alpha characters */\n");
	fwrite ($file, '$cacti_session_name = "' . $cacti_session_name . "\";\n\n");
	fwrite($file, '/*
 This is full URL Path to the Cacti installation.
 For example, if your cacti was accessible by http://server/cacti/
 you would use "/cacti/" as the url path.
 For just http://server/ use "/".  Use value of "" for relative path.
*/

');
	fwrite($file, "\$config['url_path'] = \""  . $config['url_path'] . "\";\n\n");
	fwrite($file, "/* for large site, you may need additional memory for PHP.  If so, set the default here */\n");
	fwrite($file, "\$config['memory_limit'] = \"" . $config['memory_limit'] . "\";\n\n\n");
	return TRUE;
}


