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

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = "view"; }
if (!isset($_REQUEST["view_type"])) { $_REQUEST["view_type"] = ""; }

$guest_account = true;
include("./include/auth.php");
include_once(CACTI_LIBRARY_PATH . "/rrd.php");

plugin_hook_function('graph');

include_once(CACTI_LIBRARY_PATH . "/html_tree.php");
include_once(CACTI_INCLUDE_PATH . "/top_graph_header.php");

/* ================= input validation ================= */
input_validate_input_regex(get_request_var_request("rra_id"), "/^([0-9]+|all)$/");
input_validate_input_number(get_request_var("local_graph_id"));
input_validate_input_number(get_request_var("graph_end"));
input_validate_input_number(get_request_var("graph_start"));
input_validate_input_regex(get_request_var_request("view_type"), "/^([a-zA-Z0-9]+)$/");
/* ==================================================== */

if (!isset($_GET['rra_id'])) {
	$_GET['rra_id'] = 'all';
}

if (get_request_var("rra_id") == "all") {
	$sql_where = " where id is not null";
}else{
	$sql_where = " where id=" . $_GET["rra_id"];
}

/* make sure the graph requested exists (sanity) */
if (!(db_fetch_cell("select local_graph_id from graph_templates_graph where local_graph_id=" . $_GET["local_graph_id"]))) {
	print "<strong><font size='+1' color='FF0000'>GRAPH DOES NOT EXIST</font></strong>"; exit;
}

/* take graph permissions into account here, if the user does not have permission
give an "access denied" message */
if (read_config_option("auth_method") != AUTH_METHOD_NONE) {
	$access_denied = !(is_graph_allowed($_GET["local_graph_id"]));

	if ($access_denied == true) {
		print "<strong><font size='+1' color='FF0000'>" . __("ACCESS DENIED") . "</font></strong>"; exit;
	}
}

$graph_title = get_graph_title($_GET["local_graph_id"]);

if (get_request_var_request("view_type") == "tree") {
	print "<table class='topBox'>";
}else{
	print "<table class='topBoxAlt'>";
}

$rras = get_associated_rras($_GET["local_graph_id"]);

switch (get_request_var_request("action")) {
case 'view':
	?>
	<tr class='rowSubHeader'>
		<td colspan='3' class='textHeaderDark'>
			<strong><?php print __("Viewing Graph");?></strong> '<?php print $graph_title;?>'
		</td>
	</tr>
	<?php

	$i = 0;
	if (sizeof($rras) > 0) {
	foreach ($rras as $rra) {
		?>
		<tr>
			<td align='center'>
				<table cellpadding='0'>
					<tr>
						<td>
							<?php
							$image_format_id = db_fetch_cell("SELECT
										graph_templates_graph.image_format_id
										FROM graph_templates_graph
										WHERE graph_templates_graph.local_graph_id=" . $_GET["local_graph_id"]);
							if ($image_format_id == IMAGE_TYPE_PNG || $image_format_id == IMAGE_TYPE_GIF) {
								print "<img class='graphimage' id='graph_" . $_GET["local_graph_id"] ."' src='" . htmlspecialchars("graph_image.php?action=view&local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $rra["id"]) . "' border='0' alt='" . $graph_title . "'>";
							} else if ($image_format_id == IMAGE_TYPE_SVG) {
								print "<object class='graphimage' id='graph_" . $_GET["local_graph_id"] . "' type='svg+xml' data='" . htmlspecialchars("graph_image.php?action=view&local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $rra["id"]) . "' border='0'>Can't display SVG</object>";
							}
							?>
						</td>
						<td valign='top' style='padding: 3px;' class='noprint'>
							<a href='<?php print htmlspecialchars("graph.php?action=zoom&local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $rra["id"] . "&view_type=" . $_REQUEST["view_type"]);?>'><img src='images/graph_zoom.gif' alt='<?php print __("Zoom Graph");?>' title='<?php print __("Zoom Graph");?>' class='img_info'></a><br>
							<a href='<?php print htmlspecialchars("graph_xport.php?local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $rra["id"] . "&view_type=" . $_REQUEST["view_type"]);?>'><img src='images/graph_query.png' alt='<?php print __("CSV Export");?>' title='<?php print __("CSV Export");?>' class='img_info'></a><br>
							<a href='<?php print htmlspecialchars("graph.php?action=properties&local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $rra["id"] . "&view_type=" . $_REQUEST["view_type"]);?>'><img src='images/graph_properties.gif' alt='<?php print __("Graph Source/Properties");?>' title='<?php print __("Graph Source/Properties");?>' class='img_info'></a>
							<?php plugin_hook('graph_buttons', array('hook' => 'view', 'local_graph_id' => $_GET['local_graph_id'], 'rra' => $rra['id'], 'view_type' => $_REQUEST['view_type'])); ?>
						</td>
					</tr>
					<tr>
						<td colspan='2' align='center'>
							<strong><?php print $rra["name"];?></strong>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?php
		$i++;
	}
	}

	break;

case 'zoom':
	/* find the maximum time span a graph can show */
	$max_timespan=1;
	if (sizeof($rras) > 0) {
		foreach ($rras as $rra) {
			if ($rra["steps"] * $rra["rows"] * $rra["rrd_step"] > $max_timespan) {
				$max_timespan = $rra["steps"] * $rra["rows"] * $rra["rrd_step"];
			}
		}
	}

	/* fetch information for the current RRA */
	if ($_GET["rra_id"] > 0) {
		$rra = db_fetch_row("select id,timespan,steps,name from rra where id=" . $_GET["rra_id"]);

		/* define the time span, which decides which rra to use */
		$timespan = -($rra["timespan"]);
	}else{
		$timespan = -300;
	}

	/* find the step and how often this graph is updated with new data */
	$ds_step = db_fetch_cell("SELECT
		data_template_data.rrd_step
		FROM (data_template_data,data_template_rrd,graph_templates_item)
		WHERE graph_templates_item.task_item_id=data_template_rrd.id
		AND data_template_rrd.local_data_id=data_template_data.local_data_id
		AND graph_templates_item.local_graph_id=" . $_GET["local_graph_id"] .
		" LIMIT 0,1");
	$ds_step = empty($ds_step) ? 300 : $ds_step;
	$seconds_between_graph_updates = ($ds_step * $rra["steps"]);

	$now = time();

	if (isset($_GET["graph_end"]) && ($_GET["graph_end"] <= $now - $seconds_between_graph_updates)) {
		$graph_end = $_GET["graph_end"];
	}else{
		$graph_end = $now - $seconds_between_graph_updates;
	}

	if (isset($_GET["graph_start"])) {
		if (($graph_end - get_request_var("graph_start"))>$max_timespan) {
			$graph_start = $now - $max_timespan;
		}else {
			$graph_start = $_GET["graph_start"];
		}
	}else{
		$graph_start = $now + $timespan;
	}

	/* required for zoom out function */
	if ($graph_start == $graph_end) {
		$graph_start--;
	}

	$graph = db_fetch_row("select
		graph_templates_graph.height,
		graph_templates_graph.width
		from graph_templates_graph
		where graph_templates_graph.local_graph_id=" . $_GET["local_graph_id"]);

	$graph_height = $graph["height"];
	$graph_width = $graph["width"];
	if ((read_config_option("rrdtool_version")) != "rrd-1.0.x") {
		if (read_graph_config_option("custom_fonts") == "on" & read_graph_config_option("title_size") != "") {
			$title_font_size = read_graph_config_option("title_size");
		}elseif (read_config_option("title_size") != "") {
			$title_font_size = read_config_option("title_size");
		}else {
			$title_font_size = 10;
		}
	}else {
		$title_font_size = 0;
	}

	?>
	<tr class='rowSubHeader'>
		<td colspan='3' class='textHeaderDark'>
			<strong><?php print __("Zooming Graph");?></strong> '<?php print $graph_title;?>'
		</td>
	</tr>
	<tr>
		<td align='center'>
			<table cellpadding='0'>
				<tr>
					<td>
						<?php
						$image_format_id = db_fetch_cell("SELECT
									graph_templates_graph.image_format_id
									FROM graph_templates_graph
									WHERE graph_templates_graph.local_graph_id=" . $_GET["local_graph_id"]);
						if ($image_format_id == IMAGE_TYPE_PNG || $image_format_id == IMAGE_TYPE_GIF) {
							print "<img class='graphimage' id='zoomGraphImage' src='" . htmlspecialchars("graph_image.php?action=zoom&local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $rra["id"] . "&view_type=" . $_REQUEST["view_type"] . "&graph_start=" . $graph_start . "&graph_end=" . $graph_end . "&graph_height=" . $graph_height . "&graph_width=" . $graph_width . "&title_font_size=" . $title_font_size) . "' border='0' alt='" . $graph_title . "'>";
						} else if ($image_format_id == IMAGE_TYPE_SVG) {
							print "<object class='graphimage' id='zoomGraphImage' type='svg+xml' data='" . htmlspecialchars("graph_image.php?action=zoom&local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $rra["id"] . "&view_type=" . $_REQUEST["view_type"] . "&graph_start=" . $graph_start . "&graph_end=" . $graph_end . "&graph_height=" . $graph_height . "&graph_width=" . $graph_width . "&title_font_size=" . $title_font_size) . "' border='0'>Can't display SVG</object>";
						}
						?>
					</td>
					<td valign='top' style='padding: 3px;' class='noprint'>
						<a href='<?php print htmlspecialchars("graph.php?action=properties&local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $_GET["rra_id"] . "&view_type=" . $_REQUEST["view_type"] . "&graph_start=" . $graph_start . "&graph_end=" . $graph_end);?>'><img src='images/graph_properties.gif' alt='<?php print __("Properties");?>' title='<?php print __("Graph Source/Properties");?>' class='img_info'></a>
						<a href='<?php print htmlspecialchars("graph_xport.php?local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $_GET["rra_id"] . "&view_type=" . $_REQUEST["view_type"] . "&graph_start=" . $graph_start . "&graph_end=" . $graph_end);?>'><img src='images/graph_query.png' alt='<?php print __("CSV Export");?>' title='<?php print __("CSV Export");?>' class='img_info'></a><br>
						<?php plugin_hook('graph_buttons', array('hook' => 'zoom', 'local_graph_id' => $_GET['local_graph_id'], 'rra' =>  $_GET['rra_id'], 'view_type' => $_REQUEST['view_type'])); ?>
					</td>
				</tr>
				<tr>
					<td colspan='2' align='center'>
						<strong><?php print $rra["name"];?></strong>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<script type="text/javascript" >
		$(document).ready(function() { $(".graphimage").ZoomGraph(); });
	</script>
	<?php

	break;
case 'properties':
	?>
	<tr class='rowSubHeader'>
		<td colspan='3' class='textHeaderDark'>
			<strong><?php print __("Viewing Graph Properties");?></strong> '<?php print $graph_title;?>'
		</td>
	</tr>
	<tr>
		<td align='center'>
			<table cellpadding='0'>
				<tr>
					<td>
						<?php
						$image_format_id = db_fetch_cell("SELECT
									graph_templates_graph.image_format_id
									FROM graph_templates_graph
									WHERE graph_templates_graph.local_graph_id=" . $_GET["local_graph_id"]);
						if ($image_format_id == IMAGE_TYPE_PNG || $image_format_id == IMAGE_TYPE_GIF) {
							print "<img class='graphimage' id='graph_" . $_GET["local_graph_id"] ."' src='" . htmlspecialchars("graph_image.php?action=properties&local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $_GET["rra_id"] . "&graph_start=" . (isset($_GET["graph_start"]) ? $_GET["graph_start"] : 0) . "&graph_end=" . (isset($_GET["graph_end"]) ? $_GET["graph_end"] : 0)) . "' border='0' alt='" . $graph_title . "'>";
						} else if ($image_format_id == IMAGE_TYPE_SVG) {
							print "<object class='graphimage' id='graph_" . $_GET["local_graph_id"] . "' type='svg+xml' data='" . htmlspecialchars("graph_image.php?action=view&local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $_GET["rra_id"] . "&graph_start=" . (isset($_GET["graph_start"]) ? $_GET["graph_start"] : 0) . "&graph_end=" . (isset($_GET["graph_end"]) ? $_GET["graph_end"] : 0)) . "' border='0'>Can't display SVG</object>";
						}
						?>
					</td>
					<td valign='top' style='padding: 3px;'>
						<a href='<?php print htmlspecialchars("graph.php?action=zoom&local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $_GET["rra_id"] . "&view_type=" . $_REQUEST["view_type"]);?>'><img src='images/graph_zoom.gif' alt='<?php print __("Zoom Graph");?>' title='<?php print __("Zoom Graph");?>' class='img_info'></a><br>
						<a href='<?php print htmlspecialchars("graph_xport.php?local_graph_id=" . $_GET["local_graph_id"] . "&rra_id=" . $_GET["rra_id"] . "&view_type=" . $_REQUEST["view_type"]);?>'><img src='images/graph_query.png' alt='<?php print __("CSV Export");?>' title='<?php print __("CSV Export");?>' class='img_info'></a><br>
						<?php plugin_hook('graph_buttons', array('hook' => 'properties', 'local_graph_id' => $_GET['local_graph_id'], 'rra' =>  $_GET['rra_id'], 'view_type' => $_REQUEST['view_type'])); ?>
					</td>
				</tr>
				<tr>
					<td colspan='2' align='center'>
						<strong><?php print db_fetch_cell("select name from rra where id=" . $_GET["rra_id"]);?></strong>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<?php

	break;
}

print "</table>";

include_once(CACTI_INCLUDE_PATH . "/bottom_footer.php");
