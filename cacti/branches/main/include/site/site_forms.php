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

/* file: sites.php, action: edit */
$fields_site_edit = array(
	"spacer0" => array(
		"method" => "spacer",
		"friendly_name" => __("Site Information"),
		),
	"name" => array(
		"method" => "textbox",
		"friendly_name" => __("Name"),
		"description" => __("The primary name for the site."),
		"value" => "|arg1:name|",
		"size" => "50",
		"max_length" => "100"
		),
	"address1" => array(
		"method" => "textbox",
		"friendly_name" => __("Address1"),
		"description" => __("The primary address for the site."),
		"value" => "|arg1:address1|",
		"size" => "70",
		"max_length" => "100"
		),
	"address2" => array(
		"method" => "textbox",
		"friendly_name" => __("Address2"),
		"description" => __("Additional address information for the site."),
		"value" => "|arg1:address2|",
		"size" => "70",
		"max_length" => "100"
		),
	"city" => array(
		"method" => "textbox",
		"friendly_name" => __("City"),
		"value" => "|arg1:city|",
		"description" => __("The city or locality for the site."),
		"size" => "20",
		"max_length" => "30"
		),
	"state" => array(
		"method" => "textbox",
		"friendly_name" => __("State"),
		"description" => __("The state for the site."),
		"value" => "|arg1:state|",
		"size" => "10",
		"max_length" => "20"
		),
	"postal_code" => array(
		"method" => "textbox",
		"friendly_name" => __("Postal/Zip Code"),
		"description" => __("The postal or zip code for the site."),
		"value" => "|arg1:postal_code|",
		"size" => "10",
		"max_length" => "20"
		),
	"country" => array(
		"method" => "textbox",
		"friendly_name" => __("Country"),
		"description" => __("The country for the site."),
		"value" => "|arg1:country|",
		"size" => "20",
		"max_length" => "30"
		),
	"notes" => array(
		"method" => "textarea",
		"friendly_name" => __("Site Notes"),
		"textarea_rows" => "3",
		"textarea_cols" => "70",
		"description" => __("Additional area use for random notes related to this site."),
		"value" => "|arg1:notes|",
		"max_length" => "255",
		"class" => "textAreaNotes"
		),
	"alternate_id" => array(
		"method" => "textbox",
		"friendly_name" => __("Alternate Name"),
		"description" => __("Used for cases where a site has a an alternate named used to describe it"),
		"value" => "|arg1:alternate_id|",
		"size" => "50",
		"max_length" => "30"
		),
	);
