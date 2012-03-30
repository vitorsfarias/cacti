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

include("./include/auth.php");

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = ""; }

switch (get_request_var_request("action")) {
case 'save':
	while (list($field_name, $field_array) = each($settings{$_POST["tab"]})) {
		if (($field_array["method"] == "header") || ($field_array["method"] == "spacer" )){
			/* do nothing */
		}elseif ($field_array["method"] == "textbox_password") {
			if ($_POST[$field_name] != $_POST[$field_name."_confirm"]) {
				raise_message(4);
				break;
			}elseif (isset($_POST[$field_name])) {
				$value = qstr(get_request_var_post($field_name));
				db_execute("replace into settings (name,value) values ('$field_name', $value)");
			}
		}elseif ((isset($field_array["items"])) && (is_array($field_array["items"]))) {
			while (list($sub_field_name, $sub_field_array) = each($field_array["items"])) {
				if (isset($_POST[$sub_field_name])) {
					$value = qstr(get_request_var_post($sub_field_name));
					db_execute("replace into settings (name,value) values ('$sub_field_name', $value)");
				}
			}
		}else{
			$value = qstr(get_request_var_post($field_name));
			db_execute("replace into settings (name,value) values ('$field_name', $value)");
		}
	}

	raise_message(1);

	/* reset local settings cache so the user sees the new settings */
	kill_session_var("sess_config_array");

	header("Location: settings.php?tab=" . $_POST["tab"]);
	break;
case 'emailtest':
	include(CACTI_BASE_PATH . "/lib/mail.php");
	test_mail();
	break;
case 'ajax_view':
	print "<form method='post' action='" .  basename($_SERVER["PHP_SELF"]) . "' name='settings'>\n";
	html_start_box(__("Cacti Settings") . " (" . $tabs[$_REQUEST["tab"]] . ")", "100", 0, "center", "");

	$form_array = array();

	while (list($field_name, $field_array) = each($settings[$_REQUEST["tab"]])) {
		$form_array += array($field_name => $field_array);

		if ((isset($field_array["items"])) && (is_array($field_array["items"]))) {
			while (list($sub_field_name, $sub_field_array) = each($field_array["items"])) {
				if (config_value_exists($sub_field_name)) {
					$form_array[$field_name]["items"][$sub_field_name]["form_id"] = 1;
				}

				$form_array[$field_name]["items"][$sub_field_name]["value"] = db_fetch_cell("select value from settings where name='$sub_field_name'");
				/* for autocomplete fields, we need the "name" as well */
				$form_array[$field_name]["items"][$sub_field_name]["name"] = $form_array[$field_name]["items"][$sub_field_name]["value"];
			}
		}else{
			if (config_value_exists($field_name)) {
				$form_array[$field_name]["form_id"] = 1;
			}

			$form_array[$field_name]["value"] = db_fetch_cell("select value from settings where name='$field_name'");
		}
	}

	draw_edit_form(array(
			"config" => array("no_form_tag" => true, "left_column_width" => "60%"),
			"fields" => $form_array
	));

	form_hidden_box("tab", $_REQUEST["tab"], "");
	# the id tag is required for our js code!
	form_hidden_box("hidden_rrdtool_version", read_config_option("rrdtool_version"), "");

	html_end_box();

	include_once(CACTI_BASE_PATH . "/access/js/colorpicker.js");
	include_once(CACTI_BASE_PATH . "/access/js/graph_template_options.js");

	?>
	<script type="text/javascript">
	<!--
	$().ready(function(){
			$('#i18n_language_support').bind('change', function() {
				$('#i18n_default_language').attr('disabled', ($('#i18n_language_support').val() == '0') ? true : false );
				$('#i18n_auto_detection').attr('disabled', ($('#i18n_language_support').val() == '0') ? true : false );
			});
			$('#i18n_timezone_support').bind('change', function() {
				$('#i18n_default_timezone').attr('disabled', ($('#i18n_timezone_support').val() == '0') ? true : false );
			});
		$('#i18n_timezone_support').trigger('change');
		$('#i18n_language_support').trigger('change');

		// trigger on autocomplete select operation
		// and on change (e.g. in case you type everything by keybord)
		// to change the font of any input textbox that shall define a font-family to that very font
		// and at last trigger that event to cover the ready() event
		// we do NOT care in case weird data was entered, browser will silently fall back to a valid font	
		$(".font_family").bind( "autocompleteselect change", function(event) {
			//alert('Font Value: ' + $(this).val());
			$(this).css({
				"font-family": function(index) {
					return $(this).val();
				}
			});		
		})
		.trigger('autocompleteselect');
	});
	
	//-->
	</script>
	<?php

	form_save_button("", "save");

	break;
default:
	include(CACTI_BASE_PATH . "/include/top_header.php");

	/* draw the categories tabs on the top of the page */
	print "<table width='100%' cellspacing='0' cellpadding='0' align='center'><tr>";
	print "<td><div id='tabs_settings'>";

	$i=1;
	if (sizeof($tabs) > 0) {
		print "<ul>";
		foreach (array_keys($tabs) as $tab_short_name) {
			print "<li><a id='tabs-$i' href='" . htmlspecialchars("settings.php?action=ajax_view&tab=" . $tab_short_name) . "'>$tabs[$tab_short_name]</a></li>";
			$i++;
		}
		print "</ul>";
	}

	print "</div></td></tr></table>\n";

	print "<script type='text/javascript'>
		$().ready(function() {
			$('#tabs_settings').tabs({ cookie: { expires: 30 } });
		});
	</script>\n";

	include(CACTI_BASE_PATH . "/include/bottom_footer.php");
	break;
}
