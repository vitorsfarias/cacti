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
 * Standard HTML form elements
 */

/** draw_edit_form - draws an html edit form
   @param array $array - an array that contains all of the information needed to draw
     the html form. see the arrays contained in include/global_settings.php
     for the extact syntax of this array */
function draw_edit_form($array) {
	if (sizeof($array) > 0) {
		while (list($top_branch, $top_children) = each($array)) {
			if ($top_branch == "config") {
				$config_array = $top_children;
			}elseif ($top_branch == "fields") {
				$fields_array = $top_children;
			}
		}
	}

	$i = 0;
	if (sizeof($fields_array) > 0) {
		while (list($field_name, $field_array) = each($fields_array)) {
			if ($i == 0) {
				if (!isset($config_array["no_form_tag"])) {
					print "<form method='post' action='" . ((isset($config_array["post_to"])) ? $config_array["post_to"] : basename($_SERVER["PHP_SELF"])) . "'" . ((isset($config_array["form_name"])) ? " name='" . $config_array["form_name"] . "'" : "") . ">\n";
				}
			}

			if ($i == 0 && $field_array["method"] != "spacer") {
			//	print "<thead><tr id='row_generic'><th colspan='2' class='left textRowSubHeaderDark'>General</th></tr></thead><tbody>\n";
			}

			if ($field_array["method"] == "hidden") { /* TODO: input type=hidden is not allowed inside a <table> but outside e.g. a <td> */
				form_hidden_box($field_name, $field_array["value"], ((isset($field_array["default"])) ? $field_array["default"] : ""));
			}elseif ($field_array["method"] == "hidden_zero") {
				form_hidden_box($field_name, $field_array["value"], "0");
			}elseif ($field_array["method"] == "spacer") {
				print ($i > 0 ? "</tbody></table>":"<tr><td>");
				print "<table class='striped wp100' cellpadding='0' cellspacing='0' border='0'>";
				print "<thead><tr id='row_$field_name'><th colspan='2' class='left textRowSubHeaderDark'>" . $field_array["friendly_name"] . "</th></tr></thead><tbody>\n";
			}else{
				if (isset($config_array["force_row_color"])) {
					print "<tr id='row_$field_name' bgcolor='#" . $config_array["force_row_color"] . "'>";
				}else{
					form_alternate_row_color('row_' . $field_name);
				}

				if (isset($field_array["sub_checkbox"])) {
					/* print description as a hover */
					$width = ((isset($config_array["left_column_width"])) ? (" width='" . $config_array["left_column_width"] . "'") : " width='50%'");
					print "<td" . $width . " class='template_checkbox'>\n";
					print "<font class='textEditTitle'>" . $field_array["friendly_name"] . "</font><br>\n";

					if (isset($field_array["description"])) {
						if (strlen($field_array["description"])) {
							print "<div>" . $field_array["description"] . "</div>";
						}
					}

					form_checkbox($field_array["sub_checkbox"]["name"],
						$field_array["sub_checkbox"]["value"],
						$field_array["sub_checkbox"]["friendly_name"],
						((isset($field_array["sub_checkbox"]["default"])) 	? $field_array["sub_checkbox"]["default"] : ""),
						((isset($field_array["sub_checkbox"]["form_id"])) 	? $field_array["sub_checkbox"]["form_id"] : ""),
						((isset($field_array["sub_checkbox"]["class"])) 	? $field_array["sub_checkbox"]["class"] : ""),
						((isset($field_array["sub_checkbox"]["on_change"])) ? $field_array["sub_checkbox"]["on_change"] : ""));
					print "</td>\n";
				} else {
					$width = ((isset($config_array["left_column_width"])) ? (" width='" . $config_array["left_column_width"] . "'") : " width='50%'");
					print "<td" . $width . ">\n";
					print "<font class='textEditTitle'>" . $field_array["friendly_name"] . "</font><br>\n";
					print ((isset($field_array["description"])) ? $field_array["description"] : "");
					print "</td>\n";
				}

				print "<td>";

				draw_edit_control($field_name, $field_array);

				print "</td>\n</tr>\n";
			}

			$i++;
		}
	}

	echo "</tbody></table></td></tr>";

}

/** draw_edit_control - draws a single control to be used on an html edit form
   @param string $field_name - the name of the control
   @param array $field_array - an array containing data for this control. see include/global_form.php
     for more specific syntax */
function draw_edit_control($field_name, &$field_array) {
	switch ($field_array["method"]) {
	case 'textbox':
		form_text_box($field_name, $field_array["value"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			$field_array["max_length"],
			((isset($field_array["size"])) ? $field_array["size"] : "40"), "text",
			((isset($field_array["form_id"])) ? $field_array["form_id"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'filepath':
		form_filepath_box($field_name, $field_array["value"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			$field_array["max_length"],
			((isset($field_array["size"])) ? $field_array["size"] : "40"), "text",
			((isset($field_array["form_id"])) ? $field_array["form_id"] : ""));

		break;
	case 'dirpath':
		form_dirpath_box($field_name, $field_array["value"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			$field_array["max_length"],
			((isset($field_array["size"])) ? $field_array["size"] : "40"), "text",
			((isset($field_array["form_id"])) ? $field_array["form_id"] : ""));

		break;
	case 'multiple_dirpath':
		form_multiple_dirpath_box($field_name, $field_array["value"], $field_array["textarea_rows"],
			$field_array["textarea_cols"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'textbox_password':
		form_text_box($field_name, $field_array["value"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			$field_array["max_length"],
			((isset($field_array["size"])) ? $field_array["size"] : "40"), "password");

		print "<br>";

		form_text_box($field_name . "_confirm", $field_array["value"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			$field_array["max_length"],
			((isset($field_array["size"])) ? $field_array["size"] : "40"), "password");

		break;
	case 'textarea':
		form_text_area($field_name, $field_array["value"], $field_array["textarea_rows"],
			$field_array["textarea_cols"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'drop_array':
		form_dropdown($field_name, $field_array["array"], "", "", $field_array["value"],
			((isset($field_array["none_value"])) ? $field_array["none_value"] : ""),
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'drop_sql':
		form_dropdown($field_name,
			db_fetch_assoc($field_array["sql"]), "name", "id", $field_array["value"],
			((isset($field_array["none_value"])) ? $field_array["none_value"] : ""),
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'drop_image':
		form_dropdown_image($field_name,
			$field_array["path"], $field_array["value"], $field_array["default"], (isset($field_array["width"]) ? $field_array["width"]: ""));

		break;
	case 'drop_multi':
		form_multi_dropdown($field_name, $field_array["array"], db_fetch_assoc($field_array["sql"]), "id",
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'drop_multi_rra':
		form_multi_dropdown($field_name, array_rekey(db_fetch_assoc("select id,name from rra order by timespan"), "id", "name"),
			(empty($field_array["form_id"]) ? db_fetch_assoc($field_array["sql_all"]) : db_fetch_assoc($field_array["sql"])), "id",
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'drop_tree':
		grow_dropdown_tree($field_array["tree_id"], $field_name, $field_array["value"]);

		break;
	case 'drop_color':
		form_color_dropdown($field_name, $field_array["value"], "None",
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'checkbox':
		form_checkbox($field_name,
			$field_array["value"],
			$field_array["friendly_name"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			((isset($field_array["form_id"])) ? $field_array["form_id"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'checkbox_group':
		while (list($check_name, $check_array) = each($field_array["items"])) {
			form_checkbox($check_name,
				$check_array["value"],
				$check_array["friendly_name"],
				((isset($check_array["default"])) ? $check_array["default"] : ""),
				((isset($check_array["form_id"])) ? $check_array["form_id"] : ""),
				((isset($field_array["class"])) ? $field_array["class"] : ""),
				((isset($check_array["on_change"])) ? $check_array["on_change"] : (((isset($field_array["on_change"])) ? $field_array["on_change"] : ""))));

			print "<br>";
		}

		break;
	case 'radio':
		while (list($radio_index, $radio_array) = each($field_array["items"])) {
			form_radio_button($field_name, $field_array["value"], $radio_array["radio_value"], $radio_array["radio_caption"],
				((isset($field_array["default"])) ? $field_array["default"] : ""),
				((isset($field_array["class"])) ? $field_array["class"] : ""),
				((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

			print "<br>";
		}

		break;
	case 'custom':
		print $field_array["value"];

		break;
	case 'template_checkbox':
		print "<em>" . html_boolean_friendly($field_array["value"]) . "</em>";

		form_hidden_box($field_name, $field_array["value"], "");

		break;
	case 'template_drop_array':
		print "<em>" . $field_array["array"]{$field_array["value"]} . "</em>";

		form_hidden_box($field_name, $field_array["value"], "");

		break;
	case 'template_drop_multi_rra':
		$items = db_fetch_assoc($field_array["sql_print"]);

		if (sizeof($items) > 0) {
		foreach ($items as $item) {
			print htmlspecialchars($item["name"],ENT_QUOTES) . "<br>";
		}
		}

		break;
	case 'autocomplete':
		/* we may need to evaluate a given SQL
		 * this should yield a single value */
		if (isset($field_array["sql"])) {
			$field_array["value"] = db_fetch_cell($field_array["sql"]);
		}
		form_autocomplete_box($field_name,
			$field_array["callback_function"],
			((isset($field_array["id"])) ? $field_array["id"] : ""),
			((isset($field_array["value"])) ? $field_array["value"] : ""),
			((isset($field_array["size"])) ? $field_array["size"] : "40"),
			((isset($field_array["max_length"])) ? $field_array["max_length"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));
		break;
	case 'font':
		form_font_box($field_name, $field_array["value"],
			((isset($field_array["default"])) ? $field_array["default"] : ""),
			$field_array["max_length"],
			((isset($field_array["size"])) ? $field_array["size"] : "40"), "text",
			((isset($field_array["form_id"])) ? $field_array["form_id"] : ""),
			((isset($field_array["class"])) ? $field_array["class"] : ""),
			((isset($field_array["on_change"])) ? $field_array["on_change"] : ""));

		break;
	case 'file':
		form_file($field_name,
			((isset($field_array["size"])) ? $field_array["size"] : "40"));

		break;
	default:
		print "<em>" . htmlspecialchars($field_array["value"],ENT_QUOTES) . "</em>";

		form_hidden_box($field_name, $field_array["value"], "");

		break;
	}
}

/** form_file - draws a standard html file input element
   @param string $form_name - the name of this form element
   @param int $form_size - the size (width) of the textbox */
function form_file($form_name, $form_size = 30) {

	print "<input type='file'";

	if (isset($_SESSION["sess_error_fields"])) {
		if (!empty($_SESSION["sess_error_fields"][$form_name])) {
			print "class='txtErrorTextBox'";
			unset($_SESSION["sess_error_fields"][$form_name]);
		}
	}

	print " id='$form_name' name='$form_name' size='$form_size'>";
}

/** form_filepath_box - draws a standard html textbox and provides status of a files existence
   @param string $form_name - the name of this form element
   @param string $form_previous_value - the current value of this form element
   @param string $form_default_value - the value of this form element to use if there is
     no current value available
   @param int $form_max_length - the maximum number of characters that can be entered
     into this textbox
   @param int $form_size - the size (width) of the textbox
   @param string $type - the type of textbox, either 'text' or 'password'
   @param int $current_id - used to determine if a current value for this form element
     exists or not. a $current_id of '0' indicates that no current value exists,
     a non-zero value indicates that a current value does exist */
function form_filepath_box($form_name, $form_previous_value, $form_default_value, $form_max_length, $form_size = 30, $type = "text", $current_id = 0) {
	if (($form_previous_value == "") && (empty($current_id))) {
		$form_previous_value = $form_default_value;
	}

	print "<input type='$type'";

	if (isset($_SESSION["sess_error_fields"])) {
		if (!empty($_SESSION["sess_error_fields"][$form_name])) {
			print "class='txtErrorTextBox'";
			unset($_SESSION["sess_error_fields"][$form_name]);
		}
	}

	if (isset($_SESSION["sess_field_values"])) {
		if (!empty($_SESSION["sess_field_values"][$form_name])) {
			$form_previous_value = $_SESSION["sess_field_values"][$form_name];
		}
	}

	if (is_file($form_previous_value)) {
		$extra_data = "<span class=\"success\"><br>[" . __("OK: FILE FOUND") . "]</span>";
	}else if (is_dir($form_previous_value)) {
		$extra_data = "<span class=\"warning\"><br>[" . __("ERROR: IS DIR") . "]</span>";
	}else if (strlen($form_previous_value) == 0) {
		$extra_data = "";
	}else{
		$extra_data = "<span class=\"warning\"><br>[" . __("ERROR: FILE NOT FOUND") . "]</span>";
	}

	print " id='$form_name' name='$form_name' size='$form_size'" . (!empty($form_max_length) ? " maxlength='$form_max_length'" : "") . " value='" . htmlspecialchars($form_previous_value, ENT_QUOTES) . "'>" . $extra_data;
}

/** form_dirpath_box - draws a standard html textbox and provides status of a directories existence
   @param string $form_name - the name of this form element
   @param string $form_previous_value - the current value of this form element
   @param string $form_default_value - the value of this form element to use if there is
     no current value available
   @param int $form_max_length - the maximum number of characters that can be entered
     into this textbox
   @param int $form_size - the size (width) of the textbox
   @param string $type - the type of textbox, either 'text' or 'password'
   @param int $current_id - used to determine if a current value for this form element
     exists or not. a $current_id of '0' indicates that no current value exists,
     a non-zero value indicates that a current value does exist */
function form_dirpath_box($form_name, $form_previous_value, $form_default_value, $form_max_length, $form_size = 30, $type = "text", $current_id = 0) {
	if (($form_previous_value == "") && (empty($current_id))) {
		$form_previous_value = $form_default_value;
	}

	print "<input type='$type'";

	if (isset($_SESSION["sess_error_fields"])) {
		if (!empty($_SESSION["sess_error_fields"][$form_name])) {
			print "class='txtErrorTextBox'";
			unset($_SESSION["sess_error_fields"][$form_name]);
		}
	}

	if (isset($_SESSION["sess_field_values"])) {
		if (!empty($_SESSION["sess_field_values"][$form_name])) {
			$form_previous_value = $_SESSION["sess_field_values"][$form_name];
		}
	}

	if (is_dir($form_previous_value)) {
		$extra_data = "<span class=\"success\"><br>[" . __("OK: DIR FOUND") . "]</span>";
	}else if (is_file($form_previous_value)) {
		$extra_data = "<span class=\"warning\"><br>[" . __("ERROR: IS FILE") . "]</span>";
	}else if (strlen($form_previous_value) == 0) {
		$extra_data = "";
	}else{
		$extra_data = "<span class=\"warning\"><br>[" . __("ERROR: DIR NOT FOUND") . "]</span>";
	}

	print " id='$form_name' name='$form_name' size='$form_size'" . (!empty($form_max_length) ? " maxlength='$form_max_length'" : "") . " value='" . htmlspecialchars($form_previous_value, ENT_QUOTES) . "'>" . $extra_data;
}

/** form_multiple_dirpath_box - draws a standard html textbox area and provides status of multiple directories existence
   @param string $form_name - the name of this form element
   @param string $form_previous_value - the current value of this form element (selected or not)
   @param string $form_rows - the number of rows in the text area box
   @param string $form_columns - the number of columns in the text area box
   @param string $form_default_value - the value of this form element to use if there is
     no current value available
   @param string $class - specify a css class
   @param string $on_change - specify a javascript onchange action */
function form_multiple_dirpath_box($form_name, $form_previous_value, $form_rows, $form_columns, $form_default_value, $class = "", $on_change = "") {
	if ($form_previous_value == "") {
		$form_previous_value = $form_default_value;
	}

	if (isset($_SESSION["sess_error_fields"])) {
		if (!empty($_SESSION["sess_error_fields"][$form_name])) {
			$class .= (strlen($class) ? " ":"") . "txtErrorTextBox";
			unset($_SESSION["sess_error_fields"][$form_name]);
		}
	}

	if (isset($_SESSION["sess_field_values"])) {
		if (!empty($_SESSION["sess_field_values"][$form_name])) {
			$form_previous_value = $_SESSION["sess_field_values"][$form_name];
		}
	}

	$dirs = explode(":", $form_previous_value);
	if (sizeof($dirs)) {
		foreach ($dirs as $dir) {
			if (is_dir($dir)) {
				$extra_data = "<span class=\"success\"><br>[" . __("OK: DIR FOUND") . "]</span>";
			}else if (is_file($dir)) {
				$extra_data = "<span class=\"warning\"><br>[" . __("ERROR: IS FILE") . ": " .$dir . "]</span>";
			}else{
				$extra_data = "<span class=\"warning\"><br>[" . __("ERROR: DIR NOT FOUND") . ": " . $dir . "]</span>";
			}
		}		
	}

	if (strlen($class)) {
		$class = " class='$class' ";
	}

	if (strlen($on_change)) {
		$on_change = " onChange='$on_change' ";
	}

	print "<textarea cols='$form_columns' rows='$form_rows' id='$form_name' name='$form_name'" . $class . $on_change . ">" . htmlspecialchars($form_previous_value, ENT_QUOTES) . "</textarea>$extra_data\n";
}

/** form_text_box - draws a standard html textbox
   @param string $form_name - the name of this form element
   @param string $form_previous_value - the current value of this form element
   @param string $form_default_value - the value of this form element to use if there is
     no current value available
   @param int $form_max_length - the maximum number of characters that can be entered
     into this textbox
   @param int $form_size - the size (width) of the textbox
   @param string $type - the type of textbox, either 'text' or 'password'
   @param int $current_id - used to determine if a current value for this form element
     exists or not. a $current_id of '0' indicates that no current value exists,
     a non-zero value indicates that a current value does exist
   @param string $class - specify a css class
   @param string $on_change - specify a javascript onchange action */
   function form_text_box($form_name, $form_previous_value, $form_default_value, $form_max_length, $form_size = 30, $type = "text", $current_id = 0, $class = "", $on_change = "") {
   	if (($form_previous_value == "") && (empty($current_id))) {
		$form_previous_value = $form_default_value;
	}

	print "<input type='$type'";

	if (isset($_SESSION["sess_error_fields"])) {
		if (!empty($_SESSION["sess_error_fields"][$form_name])) {
			print " class='txtErrorTextBox'";
			unset($_SESSION["sess_error_fields"][$form_name]);
		}
	}

	if (isset($_SESSION["sess_field_values"])) {
		if (!empty($_SESSION["sess_field_values"][$form_name])) {
			$form_previous_value = $_SESSION["sess_field_values"][$form_name];
		}
	}

	if (strlen($class)) {
		$class = " class='$class' ";
	}

	if (strlen($on_change)) {
		$on_change = " onChange='$on_change' ";
	}

	print " id='$form_name' name='$form_name' " . $on_change . $class ." size='$form_size'" . (!empty($form_max_length) ? " maxlength='$form_max_length'" : "") . " value='" . htmlspecialchars($form_previous_value, ENT_QUOTES) . "'>\n";
}

/** form_autocomplete_box - draws a standard html textbox as an autocomplete type
 * there are multiple use cases:
 * sql given: the value to be displayed will be derived from the SQL and passed to this function (see above)
 * id given: in case we refer to a unique id of a table, we have to pass this id to the save function along with the value displayed
 * value given: this is the value we have to display
 *              in case e.g. of "Settings", we do NOT have an id but this value only, so don't pass any id around
 *              in case of id+value present, the value has to be shown along with a hidden id
   @param string $form_name  - the name of this form element
   @param string $callback_function - the function that primes the field
   @param int $id - an id that serves as an index to a table and shall be passed to the save function
   @param string $value - what should be displayed to the user
   @param int $form_size - the size of the text box
   @param int $form_max_length - the maximum number of text to allow
   @param string $class - specify a css class
   @param string $on_change - specify a javascript onchange action */
function form_autocomplete_box($form_name, $callback_function, $id="", $value="", $form_size = "40", $form_max_length = "", $class = "", $on_change = "") {

	/* in case we have a valid id, we require a
	 * form_name for the value and a
	 * form_name for the id (hidden)
	 * else we only require the form_name for the value
	 */
	if ($id == '') {
		$form_value = $form_name;
		/* we only have to fill a single $form_value,
		 * so initialize the rest as empty string */
		$form_id = '';
		$jq_form_id1 = '';
		$jq_form_id2 = '';
	}else{
		$form_value = $form_name . "_display";
		$form_id = $form_name;
		/* make the jQuery code dynamic
		 * and fill the id field as well
		 */
		$jq_form_id1 = '$(this).parent().find("#' . $form_id . '").val(ui.item.id);';
		$jq_form_id2 = '$(this).parent().find("#' . $form_id . '").val(-1);';
	}

	print '<script  type="text/javascript">
	$().ready(function() {
		$("#' . $form_value . '").autocomplete({
			// provide data via callback
			source: "' . $callback_function . '",
			// start selecting, even if no letter typed
			minLength: 0,
			// what to do with data returned
			select: function(event, ui) {
				if (ui.item) {
					// provide the value
					$(this).parent().find("#' . $form_value . '").val(ui.item.value);
					' . $jq_form_id1 . '
				}else{
					// in case we didnt find anything
					$(this).parent().find("#' . $form_value . '").val("");
					' . $jq_form_id2 . '
				}
			}			
		});
	});
	</script>';


	if (isset($_SESSION["sess_error_fields"])) {
		if (!empty($_SESSION["sess_error_fields"][$form_name])) {
			print "class='txtErrorTextBox'";
			unset($_SESSION["sess_error_fields"][$form_name]);
		}
	}

	if (isset($_SESSION["sess_field_values"])) {
		if (!empty($_SESSION["sess_field_values"][$form_name])) {
			$form_previous_value = $_SESSION["sess_field_values"][$form_name];
		}
	}

	if (strlen($class)) {
		$class = " class='$class' ";
	}

	if (strlen($on_change)) {
		$on_change = " onChange='$on_change' ";
	}

	print "<input type='text' id='$form_value' name='$form_value'  $on_change  $class size='$form_size'" . (!empty($form_max_length) ? " maxlength='$form_max_length'" : "") . " value='" . htmlspecialchars($value, ENT_QUOTES) . "'>\n";
	if ($id !== '') {
		print "<div><input type='hidden' id='$form_id' name='$form_id' value='" . htmlspecialchars($id, ENT_QUOTES) . "'></div>";
	}
}

/** form_hidden_box - draws a standard html hidden element
   @param string $form_name - the name of this form element
   @param string $form_previous_value - the current value of this form element
   @param string $form_default_value - the value of this form element to use if there is no current value available */
function form_hidden_box($form_name, $form_previous_value, $form_default_value, $echo = false) {
	if ($form_previous_value == "") {
		$form_previous_value = $form_default_value;
	}

	if ($echo) { /* TODO: both times same action??? */
		print "<tr style='display:none;'><td><input type='hidden' id='$form_name' name='$form_name' value='" . htmlspecialchars($form_previous_value, ENT_QUOTES) . "'></td></tr>\n";
	}else{
		print "<tr style='display:none;'><td><input type='hidden' id='$form_name' name='$form_name' value='" . htmlspecialchars($form_previous_value, ENT_QUOTES) . "'></td></tr>\n";
	}
}

/** form_dropdown - draws a standard html dropdown box
   @param string $form_name - the name of this form element
   @param array $form_data - an array containing data for this dropdown. it can be formatted
     in one of two ways:
     $array["id"] = "value";
     -- or --
     $array[0]["id"] = 43;
     $array[0]["name"] = "Red";
   @param string $column_display - used to indentify the key to be used for display data. this
     is only applicable if the array is formatted using the second method above
   @param int $column_id - used to indentify the key to be used for id data. this
     is only applicable if the array is formatted using the second method above
   @param string $form_previous_value - the current value of this form element
   @param string $form_none_entry - the name to use for a default 'none' element in the dropdown
   @param string $form_default_value - the value of this form element to use if there is
     no current value available
   @param string $class - any css that needs to be applied to this form element
   @param string $on_change - onChange modifier */
function form_dropdown($form_name, $form_data, $column_display, $column_id, $form_previous_value, $form_none_entry, $form_default_value, $class = "", $on_change = "") {
	if ($form_previous_value == "") {
		$form_previous_value = $form_default_value;
	}

	if (isset($_SESSION["sess_error_fields"])) {
		if (!empty($_SESSION["sess_error_fields"][$form_name])) {
			$class .= (strlen($class) ? " ":"") . "txtErrorTextBox";
			unset($_SESSION["sess_error_fields"][$form_name]);
		}
	}

	if (isset($_SESSION["sess_field_values"])) {
		if (!empty($_SESSION["sess_field_values"][$form_name])) {
			$form_previous_value = $_SESSION["sess_field_values"][$form_name];
		}
	}

	if (strlen($class)) {
		$class = " class='$class' ";
	}

	if (strlen($on_change)) {
		$on_change = " onChange='$on_change' ";
	}

	print "<select id='$form_name' name='$form_name'" . $class . $on_change . ">";

	if (!empty($form_none_entry)) {
		print "<option value='0'" . (empty($form_previous_value) ? " selected" : "") . ">$form_none_entry</option>\n";
	}

	html_create_list($form_data, $column_display, $column_id, htmlspecialchars($form_previous_value, ENT_QUOTES));

	print "</select>\n";
}

/** form_dropdown_image
 *
 * @param string $form_name
 * @param string $form_path
 * @param string $form_previous_value
 * @param string $form_default_value
 * @param int $form_width
 */
function form_dropdown_image($form_name, $form_path, $form_previous_value, $form_default_value = "", $form_width = "120") {
	if ($form_previous_value == "") {
		$form_previous_value = $form_default_value;
	}
	if (isset($_SESSION["sess_field_values"])) {
		if (!empty($_SESSION["sess_field_values"][$form_name])) {
			$form_previous_value = $_SESSION["sess_field_values"][$form_name];
		}
	}

	print "<select id='$form_name' style='width:" . $form_width . "px;' name='$form_name'>";

	$form_none_entry = ucwords(str_replace("_", " ", str_replace(".gif", "", str_replace(".jpg", "", str_replace(".png", "", $form_default_value)))));

	$path       = CACTI_CACHE_PATH . "/". $form_path;
	$imgpath    = CACTI_CACHE_URL_PATH . $form_path;

	if (!empty($form_none_entry)) {
		print "<option>&nbsp;Default Image&nbsp;</option>\n";
		print "<option title='" . CACTI_URL_PATH . "images/icons/tree/" . $form_default_value . "' value='" . CACTI_URL_PATH . "images/icons/tree/" . $form_default_value . "'" . (empty($form_default_value) || basename($form_previous_value) == $form_default_value ? " selected" : "") . ">&nbsp;$form_none_entry&nbsp;</option>\n";
	}

	/* get the images in use first */
	$dh = opendir($path);
	$found = array();
	$array = array();
	/* validate contents of the plugin directory */
	if (is_resource($dh)) {
		while (($file = readdir($dh)) !== false) {
			if ($file != "." && $file != ".." && !is_dir("$path/$file") && preg_match("/(\.png|\.jpg|\.gif)/", $file)) {
				if (sizeof(getimagesize($path . "/" . $file))) {
					$title = ucwords(str_replace("_", " ", str_replace(".gif", "", str_replace(".jpg", "", str_replace(".png", "", $file)))));
					$found[] = basename($file);
					$array[$title] = $file;
				}
			}
		}
		closedir($dh);

		if (sizeof($array)) {
			asort($array);
			
			print "<option>&nbsp;In Use Images&nbsp;</option>\n";
			foreach($array as $t => $f) {
				print "<option title='" . $imgpath . "/" . $f . "' value='" . $imgpath . "/" . $f . "'" . (($form_previous_value == ($imgpath . "/" . $f)) ? " selected" : "") . ">&nbsp;" . $t . "&nbsp;</option>\n";
			}
		}
	}

	/* get the remaining images next first */
	$path       = CACTI_BASE_PATH . "/images/icons/tree/";
	$imgpath    = CACTI_URL_PATH . "/images/tree_icons";
	$array      = array();
	$dh = opendir($path);
	/* validate contents of the plugin directory */
	if (is_resource($dh)) {
		while (($file = readdir($dh)) !== false) {
			if ($file != "." && $file != ".." && !is_dir("$path/$file") && preg_match("/(\.png|\.jpg|\.gif)/", $file)) {
				if (!in_array(basename($file), $found) && sizeof(getimagesize($path . "/" . $file))) {
					$title = ucwords(str_replace("_", " ", str_replace(".gif", "", str_replace(".jpg", "", str_replace(".png", "", $file)))));
					if ($title != $form_none_entry) {
						$array[$title] = $file;
					}
				}
			}
		}
		closedir($dh);

		if (sizeof($array)) {
			asort($array);
			
			print "<option>&nbsp;Available Images&nbsp;</option>\n";
			foreach($array as $t => $f) {
				print "<option title='" . $imgpath . "/" . $f . "' value='" . $imgpath . "/" . $f . "'" . (($form_previous_value == ($imgpath . "/" . $f)) ? " selected" : "") . ">&nbsp;" . $t . "&nbsp;</option>\n";
			}
		}
	}

	print "</select>\n";

	?>
	<script type="text/javascript">
	<!--
	$().ready(function(arg) {
		$("#<?php print $form_name;?>").msDropDown();
		$("#designhtml select").msDropDown();
		$("#dynamic").msDropDown();
	});
	//-->
	</script><?php
}

/** form_checkbox - draws a standard html checkbox
   @param string $form_name - the name of this form element
   @param string $form_previous_value - the current value of this form element
   @param string $form_caption - the text to display to the right of the checkbox
   @param string $form_default_value - the value of this form element to use if there is
     no current value available
   @param int $current_id - used to determine if a current value for this form element
     exists or not. a $current_id of '0' indicates that no current value exists,
     a non-zero value indicates that a current value does exist
   @param string $class - specify a css class
   @param string $on_change - specify a javascript onchange action */
function form_checkbox($form_name, $form_previous_value, $form_caption, $form_default_value, $current_id = 0, $class = "", $on_change = "") {
	if (($form_previous_value == "") && (empty($current_id))) {
		$form_previous_value = $form_default_value;
	}

	if (isset($_SESSION["sess_field_values"])) {
		if (!empty($_SESSION["sess_field_values"][$form_name])) {
			$form_previous_value = $_SESSION["sess_field_values"][$form_name];
		}
	}

	if (strlen($class)) {
		$class = " class='$class'";
	}

	if (strlen($on_change)) {
		$on_change = " onChange='$on_change'";
	}

	if ($form_previous_value == CHECKED) {
		$checked = " checked";
	}else{
		$checked = "";
	}

	print "<input type='checkbox' id='$form_name' name='$form_name'" . $on_change . $class . $checked . "> <label for='$form_name'>$form_caption</label>\n";
}

/** form_radio_button - draws a standard html radio button
   @param string $form_name - the name of this form element
   @param string $form_previous_value - the current value of this form element (selected or not)
   @param string $form_current_value - the current value of this form element (element id)
   @param string $form_caption - the text to display to the right of the checkbox
   @param string $form_default_value - the value of this form element to use if there is
     no current value available
   @param string $class - specify a css class
   @param string $on_change - specify a javascript onchange action */
function form_radio_button($form_name, $form_previous_value, $form_current_value, $form_caption, $form_default_value, $class = "", $on_change = "") {
	if ($form_previous_value == "") {
		$form_previous_value = $form_default_value;
	}

	if (isset($_SESSION["sess_field_values"])) {
		if (!empty($_SESSION["sess_field_values"][$form_name])) {
			$form_previous_value = $_SESSION["sess_field_values"][$form_name];
		}
	}

	if (strlen($class)) {
		$class = " class='$class' ";
	}

	if (strlen($on_change)) {
		$on_change = " onChange='$on_change' ";
	}

	if ($form_previous_value == $form_current_value) {
		$checked = " checked";
	}else{
		$checked = "";
	}

	$css_id = $form_name . "_" . $form_current_value;

	print "<input type='radio' id='$css_id' name='$form_name' value='$form_current_value'" . $class . $on_change . $checked . "><label for='$css_id'>$form_caption</label>\n";
}

/** form_text_area - draws a standard html text area box
   @param string $form_name - the name of this form element
   @param string $form_previous_value - the current value of this form element (selected or not)
   @param string $form_rows - the number of rows in the text area box
   @param string $form_columns - the number of columns in the text area box
   @param string $form_default_value - the value of this form element to use if there is
     no current value available
   @param string $class - specify a css class
   @param string $on_change - specify a javascript onchange action */
function form_text_area($form_name, $form_previous_value, $form_rows, $form_columns, $form_default_value, $class = "", $on_change = "") {
	if ($form_previous_value == "") {
		$form_previous_value = $form_default_value;
	}

	if (isset($_SESSION["sess_error_fields"])) {
		if (!empty($_SESSION["sess_error_fields"][$form_name])) {
			$class .= (strlen($class) ? " ":"") . "txtErrorTextBox";
			unset($_SESSION["sess_error_fields"][$form_name]);
		}
	}

	if (isset($_SESSION["sess_field_values"])) {
		if (!empty($_SESSION["sess_field_values"][$form_name])) {
			$form_previous_value = $_SESSION["sess_field_values"][$form_name];
		}
	}

	if (strlen($class)) {
		$class = " class='$class' ";
	}

	if (strlen($on_change)) {
		$on_change = " onChange='$on_change' ";
	}

	print "<textarea cols='$form_columns' rows='$form_rows' id='$form_name' name='$form_name'" . $class . $on_change . ">" . htmlspecialchars($form_previous_value, ENT_QUOTES) . "</textarea>\n";
}

/** form_multi_dropdown - draws a standard html multiple select dropdown
   @param string $form_name - the name of this form element
   @param array $array_display - an array containing display values for this dropdown. it must
     be formatted like:
     $array[id] = display;
   @param array $sql_previous_values - an array containing keys that should be marked as selected.
     it must be formatted like:
     $array[0][$column_id] = key
   @param int $column_id - the name of the key used to reference the keys above
   @param string $class - specify a css class
   @param string $on_change - specify a javascript onchange action */
function form_multi_dropdown($form_name, $array_display, $sql_previous_values, $column_id, $class = "", $on_change = "") {

	if (isset($_SESSION["sess_error_fields"])) {
		if (!empty($_SESSION["sess_error_fields"][$form_name])) {
			$class .= (strlen($class) ? " ":"") . "txtErrorTextBox";
			unset($_SESSION["sess_error_fields"][$form_name]);
		}
	}

	if (strlen($class)) {
		$class = " class='$class' ";
	}

	if (strlen($on_change)) {
		$on_change = " onChange='$on_change' ";
	}

	print "<select id='$form_name' name='$form_name" . "[]'" . $class . " multiple>\n";

	foreach (array_keys($array_display) as $id) {
		print "<option value='" . $id . "'";

		for ($i=0; ($i < count($sql_previous_values)); $i++) {
			if ($sql_previous_values[$i][$column_id] == $id) {
				print " selected";
			}
		}

		print ">". htmlspecialchars($array_display[$id],ENT_QUOTES);
		print "</option>\n";
	}

	print "</select>\n";
}

/*
 * Second level form elements
 */

/** form_color_dropdown - draws a dropdown containing a list of colors that uses a bit
     of css magic to make the dropdown item background color represent each color in
     the list
   @param string $form_name - the name of this form element
   @param string $form_previous_value - the current value of this form element
   @param string $form_none_entry - the name to use for a default 'none' element in the dropdown
   @param string $form_default_value - the value of this form element to use if there is
     no current value available
   @param string $class - specify a css class
   @param string $on_change - specify a javascript onchange action */
function form_color_dropdown($form_name, $form_previous_value, $form_none_entry, $form_default_value, $class = "", $on_change = "") {
	if ($form_previous_value == "") {
		$form_previous_value = $form_default_value;
	}

	if (isset($_SESSION["sess_error_fields"])) {
		if (!empty($_SESSION["sess_error_fields"][$form_name])) {
			$class .= " txtErrorTextBox";
			unset($_SESSION["sess_error_fields"][$form_name]);
		}
	}

	if (strlen($class)) {
		$class = " class='$class' ";
	}

	$current_color = db_fetch_cell("SELECT hex FROM colors WHERE id=$form_previous_value");

	if (strlen($on_change)) {
		$on_change = " " . $on_change . ";";
	}

	$on_change = " onChange='this.style.backgroundColor=this.options[this.selectedIndex].style.backgroundColor;$on_change'";

	$colors_list = db_fetch_assoc("select id,hex from colors order by hex desc");

	print "<select style='background-color: #$current_color;' id='$form_name' name='$form_name'" . $class . $on_change . ">\n";

	if ($form_none_entry != "") {
		print "<option value='0'>$form_none_entry</option>\n";
	}

	if (sizeof($colors_list) > 0) {
		foreach ($colors_list as $color) {
			print "<option style='background-color: #" . $color["hex"] . ";' value='" . $color["id"] . "'";

			if ($form_previous_value == $color["id"]) {
				print " selected";
			}

			print ">" . $color["hex"] . "</option>\n";
		}
	}

	print "</select>\n";
}

/** form_font_box - draws a standard html textbox and provides status of a fonts existence
   @param string $form_name - the name of this form element
   @param string $form_previous_value - the current value of this form element
   @param string $form_default_value - the value of this form element to use if there is
     no current value available
   @param int $form_max_length - the maximum number of characters that can be entered
     into this textbox
   @param int $form_size - the size (width) of the textbox
   @param string $type - the type of textbox, either 'text' or 'password'
   @param int $current_id - used to determine if a current value for this form element
     exists or not. a $current_id of '0' indicates that no current value exists,
     a non-zero value indicates that a current value does exist
   @param string $class - specify a css class
   @param string $on_change - specify a javascript onchange action */
function form_font_box($form_name, $form_previous_value, $form_default_value, $form_max_length, $form_size = 30, $type = "text", $current_id = 0, $class = "", $on_change = "") {

	if (($form_previous_value == "") && (empty($current_id))) {
		$form_previous_value = $form_default_value;
	}

	print "<input type='$type'";

	if (isset($_SESSION["sess_error_fields"])) {
		if (!empty($_SESSION["sess_error_fields"][$form_name])) {
			print "class='txtErrorTextBox'";
			unset($_SESSION["sess_error_fields"][$form_name]);
		}
	}

	if (isset($_SESSION["sess_field_values"])) {
		if (!empty($_SESSION["sess_field_values"][$form_name])) {
			$form_previous_value = $_SESSION["sess_field_values"][$form_name];
		}
	}

	if (strlen($form_previous_value) == 0) { # no data: defaults are used; everythings fine
			$extra_data = "";
	} else {

		/* do some simple checks */
		if (read_config_option("rrdtool_version") == "rrd-1.0.x" ||
			read_config_option("rrdtool_version") == "rrd-1.2.x") { # rrdtool 1.0 and 1.2 use font files
			if (is_file($form_previous_value)) {
				$extra_data = "<span style='color:green'><br>[" . "OK: FILE FOUND" . "]</span>";
			}else if (is_dir($form_previous_value)) {
				$extra_data = "<span style='color:red'><br>[" . "ERROR: IS DIR" . "]</span>";
			}else{
				$extra_data = "<span style='color:red'><br>[" . "ERROR: FILE NOT FOUND" . "]</span>";
			}
		} else {	# rrdtool 1.3+ use fontconfig
			/* verifying all possible pango font params is too complex to be tested here
			 * so we only escape the font
			 */
			$extra_data = "<span style='color:green'><br>[" . "NO FONT VERIFICATION POSSIBLE" . "]</span>";
		}
	}

	if (strlen($class)) {
		$class = " class='$class' ";
	}

	if (strlen($on_change)) {
		$on_change = " onChange='$on_change' ";
	}

	print " id='$form_name' name='$form_name'" . $on_change . $class ." size='$form_size'" . (!empty($form_max_length) ? " maxlength='$form_max_length'" : "") . " value='" . htmlspecialchars($form_previous_value, ENT_QUOTES) . "'>" . $extra_data;
}

/** form_confirm - draws a table presenting the user with some choice and allowing
     them to either proceed (delete) or cancel
   @param string $body_text - the text to prompt the user with on this form
   @param string $cancel_url - the url to go to when the user clicks 'cancel'
   @param string $action_url - the url to go to when the user clicks 'delete' */
function form_confirm($title_text, $body_text, $cancel_url, $action_url) { ?>
	<br>
	<table align="center" cellpadding="1" cellspacing="0" border="0" width="60%">
		<tr>
			<td colspan="10">
				<table width="100%" cellpadding="3" cellspacing="0">
					<tr>
						<td class="textHeaderDark"><?php print $title_text;?></td>
					</tr>
					<?php
					form_area($body_text);
					form_confirm_buttons($action_url, $cancel_url);
					?>
				</table>
			</td>
		</tr>
	</table>
<?php }

/** form_confirm_buttons - draws a cancel and delete button suitable for display
     on a confirmation form
   @param string $cancel_url - the url to go to when the user clicks 'cancel'
   @param string $action_url - the url to go to when the user clicks 'delete' */
function form_confirm_buttons($action_url, $cancel_url) {
	?>
	<tr>
		<td>
			<a href="<?php print $cancel_url;?>"><img src="<?php print CACTI_URL_PATH; ?>images/button_cancel.gif" alt="<?php print __("Cancel");?>" align="middle"></a>
			<a href="<?php print $action_url . "&confirm=yes";?>"><img src="<?php print CACTI_URL_PATH ?>images/button_delete.gif" alt="<?php print __("Delete");?>" align="middle"></a>
		</td>
	</tr>
	<?php 
}

/** form_save_button - draws a (save|create) and cancel button at the bottom of
     an html edit form
   @param string $cancel_url - the url to go to when the user clicks 'cancel'
   @param string $force_type - if specified, will force the 'action' button to be either
     'save' or 'create'. otherwise this field should be properly auto-detected
   @param string $key_field  - required to dinstinguish between SAVE and CREATE */
function form_save_button($cancel_url, $force_type = "", $key_field = "id") {
	$calt = "Cancel";

	if (empty($force_type) || $force_type == "return") {
		if (empty($_GET[$key_field])) {
			$alt = "Create";
		}else{
			$alt = "Save";

			if (strlen($force_type)) {
				$calt   = "Return";
			}else{
				$calt   = "Cancel";
			}
		}

	}elseif ($force_type == "save") {
		$alt = "Save";
	}elseif ($force_type == "create") {
		$alt = "Create";
	}elseif ($force_type == "import") {
		$alt = "Import";
	}elseif ($force_type == "export") {
		$alt = "Export";
	}

	if ($force_type != "import" && $force_type != "export" && $force_type != "save") {
		$cancel_action = "<input type='button' onClick='returnTo(\"" . $cancel_url . "\")' value='" . $calt . "'>";
	}else{
		$cancel_action = "";
	}

	?>

	<script type="text/javascript">
	<!--
	function returnTo(location) {
		if (location != "") {
			document.location = location;
		}else{
			document.history.back();
		}
	}
	-->
	</script>
	<table align='center' width='100%' style='background-color: #ffffff; border: 1px solid #bbbbbb;'>
		<tr>
			<td align="right" style="text-align:right;">
				<input id='action' name='action' type='hidden' value='save'>
				<?php print $cancel_action;?>
				<input type='submit' value='<?php print $alt;?>'>
			</td>
		</tr>
	</table>
	</form>
	<?php
}

/** form_confirm_alt - draws a table presenting the user with some choice and allowing
     them to either proceed (delete) or cancel
   @param string $body_text - the text to prompt the user with on this form
   @param string $body_text - the text to prompt the user with on this form
   @param string $cancel_url - the url to go to when the user clicks 'cancel'
   @param string $action_url - the url to go to when the user clicks 'delete' */
function form_confirm_alt($title_text, $body_text, $cancel_url, $action_url) { ?>
		<br>
		<table align="center" cellpadding="1" cellspacing="0" border="0" width="60%">
			<tr>
				<td colspan="10">
					<table width="100%" cellpadding="3" cellspacing="0">
						<tr>
							<td class="textHeaderDark"><?php print $title_text;?></td>
						</tr>
						<?php
						form_area($body_text);
						form_confirm_buttons_alt();
						?>
					</table>
				</td>
			</tr>
		</table>

<?php }

/** form_save_button_alt - draws a (save|create) and cancel button at the bottom of
     an html edit form
   @param string $cancel_action - determines CANCEL action, e.g. URL to go to
   @param string $action 		- determines SAVE action
   @param string $force_type 	- if specified, will force the 'action' button to be either
     'save' or 'create'. otherwise this field should be properly auto-detected
   @param string $key_field  	- required to dinstinguish between SAVE and CREATE */
function form_save_button_alt($cancel_action = "", $action = "save", $force_type = "", $key_field = "id") {
	$calt = __("Cancel");

	if ((empty($force_type)) || (substr_count($cancel_action,"return"))) {
		if (empty($_GET[$key_field])) {
			$sname = "save";
			$salt  = __("Create");
		}else{
			$sname = "save";
			$salt  = __("Save");
		}

		if (substr_count($cancel_action,"return")) {
			$calt   = __("Return");
			$action = "save";
		}else{
			$calt   = __("Cancel");
		}
	}elseif ($force_type == "save") {
		$sname = "save";
		$salt  = __("Save");
	}elseif ($force_type == "create") {
		$sname = "save";
		$salt  = __("Create");
	}elseif ($force_type == "import") {
		$sname = "create";
		$salt  = __("Import");
	}elseif ($force_type == "export") {
		$sname = "create";
		$salt  = __("Export");
	}

	if ($force_type != "import" && $force_type != "export") {
		if (substr_count($cancel_action, "!")) {
			$url = form_cancel_action_compose($cancel_action);
			$action = "window.location.assign(\"" . htmlspecialchars($url) . "\")";
		}elseif (isset($_SERVER['HTTP_REFERER'])) {
			$url = $_SERVER['HTTP_REFERER'];
			$action = "window.location.assign(\"" . htmlspecialchars($url) . "\")";
		}else{
			$action = "history.back()";
		}
	}

	?>
	<table class='saveBox'>
		<tr>
			<td>
				<input type='hidden' name='action' value='<?php print $sname;?>'>
				<?php if ($force_type != "import" && $force_type != "export") { ?><input id='cancel' type='button' value='<?php print $calt;?>' onClick='<?php print $action;?>' name='cancel'><?php } ?>
				<input id='<?php print $sname;?>' type='submit' value='<?php print $salt;?>' name='<?php print $sname;?>'>
			</td>
		</tr>
	</table>
	</form>
	<?php
}


/** form_cancel_action_compose - determine if the user has chosen to cancel, and if the user
   has selected "cancel", where to goto.  the default will be to goto the current
   page with no action (aka continue)
  @param string $cancel_action -
  		'url' 	- defines the URL to go to
  		'path'	-
  		'return'-
  		else	- composes the URL parameters from REQUEST_URI
  @return string - URL to go to
*/
function form_cancel_action_compose($cancel_action) {
	global $url_path;

	$vars        = explode("|", $cancel_action);
	$uri         = $_SERVER["REQUEST_URI"];
	$uri_request = "";
	$url         = "";

	if (sizeof($vars)) {
	foreach($vars as $var) {
		$request = explode("!", $var);

		if ($request[0] == "url") {
			$url = $request[1];
		}elseif ($request[0] == "path" || $request[0] == "return") {
			$url = $request[1];
		}elseif (strlen($uri_request)) {
			$uri_request .= "&" . $request[0] . "=" . $request[1];
		}else{
			$uri_request .= "?" . $request[0] . "=" . $request[1];
		}
	}
	}

	if ((isset($url)) && (strlen($url))) {
		return html_simple_decode($url_path . $url . $uri_request);
	}elseif ((isset($uri)) && (strlen($uri))) {
		return $url_path . $uri . $uri_request;
	}
}

/** form_return_button - draws a return button at the bottom of
     an html edit form
*/
function form_return_button($title = "") {
	?>
	<tr id='title' style='display:none;'><td><?php print $title;?></td></tr>
	<tr>
		<td align="right" style="text-align:right;">
			<input type='button' value='<?php print __("Return");?>' onClick='javascript:$("#cdialog").dialog("close")' name='cancel'>
		</td>
	</tr>
	</form>
	<?php
}

/** form_cancel_button - draws a cancel button at the bottom of
     an html edit form
*/
function form_cancel_button() {
	?>
	<tr>
		<td align="right" style="text-align:right">
			<input type='button' value='<?php print __("Cancel");?>' onClick='window.history.back()' name='cancel'>
		</td>
	</tr>
	</form>
	<?php
}

/** form_ajax_save - draws a simple Save/Cancel button at the bottom of an html edit form
   @param string $title 	- the title for the form
   @param string $form_id 	- the id of the form to post prior to returning control
 */
function form_ajax_save($title, $form_id, $text = "") {

	if ($text == "") {
		$text = __("Save");
	}

	html_start_box("", "100", "3", "center", "");

	?>
	<br>
	<tr id='title' style='display:none;'><td><?php print $title;?></td></tr>
	<tr>
		<td align="right" style="text-align:right">
			<input id='cancel' type='button' value='<?php print __("Cancel");?>' onClick='$("#cdialog").dialog("close");$("#cdialog").empty();' name='cancel'>
			<input id='save' type='button' value='<?php print $text;?>' name='save' title='<?php print $text . " [ " . $title . "]";?>'>
		</td>
	</tr>
	</form>
        <script type='text/javascript'>
        $('#save').click(function(data) {
		$var = $('#<?php print $form_id;?>');
		if ($var.attr('method') == 'post') {
			$.post($var.attr('action'), $var.serialize(), function(data) {
				$('#cdialog').dialog('close');
				$('#cdialog').empty();
				$.get($var.attr('action')+'?action=ajax_view', function(data) {
					$('#content').html(data);
				});
			});
		}
        });
        </script>
	<?php

	html_end_box();
}

/** form_continue - draws a Continue/Cancel button at the bottom of an html edit form
 * requires, that a REQUEST variable of 'table_id' denotes the place where to return
 * TODO: we should pass this variable via parameter list!
   @param string $item_list 	- serialized device array
   @param string $drp_action 	- if specified, will direct the system what to do if "No" is selected
   @param string $title			- title of the popup displayed for confirmation
   @param string $form_id		- id of the form which we try to reload using call to function "ajax_view"
 */
function form_continue($item_list, $drp_action = "none", $title = "", $form_id = "") {
	input_validate_input_regex($drp_action, "^([a-zA-Z0-9_]+)$");

	?>
	<tr id='title' style='display:none;'><td><?php print $title;?></td></tr>
	<tr>
		<td align="right" style="text-align:right">
			<div><input type='hidden' name='action' value='actions'></div>
			<div><input type='hidden' name='selected_items' value='<?php print $item_list;?>'></div>
			<div><input type='hidden' name='drp_action' value='<?php print $drp_action;?>'></div>
			<?php print (isset($_REQUEST["parent"]) ? "<div><input type='hidden' name='parent' value='" . get_request_var_post("parent") . "'></div>":"");?>
			<?php print (isset($_REQUEST["parent_id"]) ? "<div><input type='hidden' name='parent_id' value='" . get_request_var_post("parent_id") . "'></div>":"");?>
			<input id='cancel' type='button' value='<?php print __("Cancel");?>' onClick='$("#cdialog").dialog("close");' name='cancel'>
			<input id='continue' type='button' value='<?php print __("Continue");?>' name='continue' title='<?php print $title;?>'>
		</td>
	</tr>
	</form>
        <script type='text/javascript'>
        $('#continue').click(function(data) {
		// attempt to calculate the form id if one is not given
		if ("<?php print $form_id;?>" == "") {
			$var = $(this).parents('form:last');
		}else{
			$var = $('#<?php print $form_id;?>');
		}

		if ($var.attr('method') == 'post') {
			$myReturn = $('#<?php print get_request_var_request('table_id');?>').closest('.ui-tabs-panel');

			action = $var.attr('action')+'?action=ajax_view';
			<?php print (isset($_REQUEST["parent"]) ? "action += '&parent=" . get_request_var_post("parent") . "'":"");?>;
			<?php print (isset($_REQUEST["parent_id"]) ? "action += '&parent_id=" . get_request_var_post("parent_id") . "'":"");?>;
			$.post($var.attr('action'), $var.serialize(), function(data) {
				$('#cdialog').dialog('close');
				$('#cdialog').empty();
				$.get(action, function(data) {
					if ($myReturn.attr('id')) {
						$myReturn.html(data);
					}else{ 
						$('#content').html(data);
					}
				});
			});
		}else{
			alert("Unable to determine form");
		}
        });
        </script>
	<?php
}

/** form_continue2 		- draws a Continue/Cancel button at the bottom of an html edit form
   @param string $device_list 	- serialized item array
   @param string $action 		- specifies the action code, e.g. "save_gt" for the main procedure select directive
 */
function form_continue2($item_list, $action = "none", $title = "") {
	?>
	<tr>
		<td align="right" style="text-align:right">
			<div><input type='hidden' name='action' value='<?php print $action;?>'></div>
			<div><input type='hidden' name='selected_items' value='<?php print $item_list;?>'></div>
			<input type='submit' value='<?php print __("Continue");?>' name='yes' title='<?php print $title;?>'>
			<input type='button' value='<?php print __("Cancel");?>' onClick='window.history.back()' name='cancel'>
		</td>
	</tr>
	</form>
	<?php
}

/** form_confirm_buttons_alt - draws a cancel and delete button suitable for display
     on a confirmation form */
function form_confirm_buttons_alt() {
	?>
	<tr>
		<td>
			<input type='submit' value='<?php print __("Cancel");?>' name='cancel'>
			<input type='submit' value='<?php print __("Delete");?>' name='delete'>
		</td>
	</tr>
<?php }

/** html_simple_decode
 *
 * @param string $string - string to decode
 * @return string
 */
function html_simple_decode($string) {
	if (function_exists("html_entity_decode")) {
		return html_entity_decode($string);
	}else{
		return str_replace("&amp;", "&", str_replace("&quot;", "\"", str_replace("&#039;", "'", $string)));
	}
}

/** draw_template_edit_form
 *
 * @param string $table_id
 * @param string $edit_struct
 * @param string $edit_data
 * @param string $use_template
 */
function draw_template_edit_form($table_id, $edit_struct, $edit_data, $use_template=false) {

	$form_array = array();

	while (list($field_name, $field_array) = each($edit_struct)) {
		if (isset($field_array["flags"]) && $field_array["flags"] == "NOTEMPLATE" && !$use_template) {
			CONTINUE;
		}
		$form_array += array($field_name => $edit_struct[$field_name]);

		$form_array[$field_name]["value"] = (isset($edit_data[$field_name]) ? $edit_data[$field_name] : "");
		$form_array[$field_name]["form_id"] = (isset($edit_data["id"]) ? $edit_data["id"] : "0");

		if (!(($use_template === false) || ($edit_data{"t_" . $field_name} == CHECKED))) {
			$form_array[$field_name]["method"] = "template_" . $form_array[$field_name]["method"];
		}


		$form_array[$field_name]["sub_checkbox"] = array(
			"name" => "t_" . $field_name,
			"friendly_name" => "<em>" . __("Use Per-Graph Value (Ignore this Value)") . "</em>",
			"value" => (isset($edit_data{"t_" . $field_name}) ? $edit_data{"t_" . $field_name} : ""),
			"class" => (isset($form_array[$field_name]["class"]) ? $form_array[$field_name]["class"] : "")
		);
	}

	draw_edit_form(
		array(
			"config" => array("no_form_tag" => true),
			"fields" => inject_form_variables($form_array, $edit_data)
			)
		);

}
