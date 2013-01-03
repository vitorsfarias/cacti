<?php
/*
   +-------------------------------------------------------------------------+
   | Copyright (C) 2004-2013 The Cacti Group                                 |
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

$guest_account = true;

include("./include/auth.php");
include_once(CACTI_INCLUDE_PATH . "/data_source/data_source_forms.php");

/* set default action */
if (!isset($_REQUEST["action"])) $_REQUEST["action"] = "";

switch (get_request_var_request("action")) {
	case 'ajax_get_languages':
		ajax_get_languages();

		break;
	case 'ajax_get_timezones':
		ajax_get_timezones();

		break;
	case 'ajax_get_data_dd_menus':
		ajax_get_data_dd_menus();

		break;
	case 'ajax_get_data_templates':
		ajax_get_data_templates();

		break;
	case 'ajax_get_devices_detailed':
		ajax_get_devices_detailed();

		break;
	case 'ajax_get_devices_brief':
		ajax_get_devices_brief();

		break;
	case 'ajax_get_messages':
		ajax_get_messages();

		break;
}
