<?php
/*
   +-------------------------------------------------------------------------+
   | Copyright (C) 2004-2010 The Cacti Group                                 |
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
$no_http_headers = true;
require(dirname(__FILE__) . "/../../include/global.php");
require_once(dirname(__FILE__) . "/../../lib/functions.php");

if (!isset($_REQUEST['cacti_dd_menu'])) {
	$_REQUEST['cacti_dd_menu'] = '';
}
print_r($_GET);
switch ($_REQUEST['cacti_dd_menu']) {
	case 'graph_options':

		$output	= "<h6><a id='changeGraphState' onClick='changeGraphState()' href='#'>Unlock/Lock</a></h6>"
				. "<h6><a href='" . htmlspecialchars('graphs.php?action=graph_edit&id=' . $_GET["graph_id"] . "&debug=" . (isset($_SESSION["graph_debug_mode"]) ? "0" : "1")) . "'>" . __("Turn") . " <strong>" . (isset($_SESSION["graph_debug_mode"]) ? __("Off") : __(CHECKED)) . "</strong> " . __("Debug Mode") . "</a></h6>"
				. "<h6><a href='" . htmlspecialchars('graph_templates.php?action=template_edit&id=' . $_GET["graph_template_id"] ) . "'>" . __("Edit Template") . "</a></h6>"
				. "<h6><a href='" . htmlspecialchars('devices.php?action=edit&id=' . $_GET["device_id"] ) . "'>" . __("Edit Host") . "</a></h6>";
		break;

	default:
		$output = "<h6>Invalid</h6>";
		break;
}

print $output;