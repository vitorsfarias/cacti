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

$fields_template_import = array(
	"import_file" => array(
		"friendly_name" => __("Import Template from Local File"),
		"description" => __("If the XML file containing template data is located on your local machine, select it here."),
		"value" => "",
		"method" => "file"
		),
	"import_text" => array(
		"method" => "textarea",
		"friendly_name" => __("Import Template from Text"),
		"description" => __("If you have the XML file containing template data as text, you can paste it into this box to import it."),
		"value" => "",
		"default" => "",
		"textarea_rows" => "10",
		"textarea_cols" => "50",
		"class" => "textAreaNotes"
		),
	"import_rra" => array(
		"friendly_name" => __("Import RRA Settings"),
		"description" => __("Choose whether to allow Cacti to import custom RRA settings from imported templates or whether to use the defaults for this installation."),
		"method" => "radio",
		"value" => "",
		"default" => "1",
		"on_change" => "changeRRA()",
		"items" => array(
			0 => array(
				"radio_value" => "1",
				"radio_caption" => __("Select your RRA settings below (Recommended)")
				),
			1 => array(
				"radio_value" => "2",
				"radio_caption" => __("Use custom RRA settings from the template")
				),
			)
		),
	"rra_id" => array(
		"method" => "drop_multi_rra",
		"friendly_name" => __("Associated RRA's"),
		"description" => __("Which RRA's to use when entering data (It is recommended that you <strong>deselect unwanted values</strong>)."),
		"form_id" => "",
		"sql_all" => "select rra.id from rra where id in (1,2,3,4) order by id",
		),
	);
