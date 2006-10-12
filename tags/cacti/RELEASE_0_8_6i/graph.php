<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004 Ian Berry                                            |
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
 | cacti: a php-based graphing solution                                    |
 +-------------------------------------------------------------------------+
 | Most of this code has been designed, written and is maintained by       |
 | Ian Berry. See about.php for specific developer credit. Any questions   |
 | or comments regarding this code should be directed to:                  |
 | - iberry@raxnet.net                                                     |
 +-------------------------------------------------------------------------+
 | - raXnet - http://www.raxnet.net/                                       |
 +-------------------------------------------------------------------------+
*/

/* set default action */
if (!isset($_REQUEST["action"])) { $_REQUEST["action"] = "view"; }
if (!isset($_REQUEST["view_type"])) { $_REQUEST["view_type"] = ""; }

$guest_account = true;
include("./include/auth.php");
include("./lib/rrd.php");
include("./lib/html_tree.php");
include("./include/top_graph_header.php");

/* ================= input validation ================= */
input_validate_input_regex(get_request_var("rra_id"), "^([0-9]+|all)$");
input_validate_input_number(get_request_var("local_graph_id"));
/* ==================================================== */

if ($_GET["rra_id"] == "all") {
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
if (read_config_option("global_auth") == "on") {
	$access_denied = !(is_graph_allowed($_GET["local_graph_id"]));

	if ($access_denied == true) {
		print "<strong><font size='+1' color='FF0000'>ACCESS DENIED</font></strong>"; exit;
	}
}

$graph_title = get_graph_title($_GET["local_graph_id"]);

if ($_REQUEST["view_type"] == "tree") {
	print "<table width='98%' style='background-color: #ffffff; border: 1px solid #ffffff;' align='center' cellpadding='3'>";
}else{
	print "<br><table width='98%' style='background-color: #f5f5f5; border: 1px solid #bbbbbb;' align='center' cellpadding='3'>";
}

$rras = get_associated_rras($_GET["local_graph_id"]);

switch ($_REQUEST["action"]) {
case 'view':
	?>
	<tr bgcolor='#<?php print $colors["header_panel"];?>'>
		<td colspan='3' class='textHeaderDark'>
			<strong>Viewing Graph</strong> '<?php print $graph_title;?>'
		</td>
	</tr>
	<?php

	$i = 0;
	if (sizeof($rras) > 0) {
	foreach ($rras as $rra) {
		?>
		<tr>
			<td align='center'>
				<table width='1' cellpadding='0'>
					<tr>
						<td>
							<img src='graph_image.php?local_graph_id=<?php print $_GET["local_graph_id"];?>&rra_id=<?php print $rra["id"];?>' border='0' alt='<?php print $graph_title;?>'>
						</td>
						<td valign='top' style='padding: 3px;' class='noprint'>
							<a href='graph.php?action=zoom&local_graph_id=<?php print $_GET["local_graph_id"];?>&rra_id=<?php print $rra["id"];?>&view_type=<?php print $_REQUEST["view_type"];?>'><img src='images/graph_zoom.gif' border='0' alt='Zoom Graph' title='Zoom Graph' style='padding: 3px;'></a><br>
							<a href='graph.php?action=properties&local_graph_id=<?php print $_GET["local_graph_id"];?>&rra_id=<?php print $rra["id"];?>&view_type=<?php print $_REQUEST["view_type"];?>'><img src='images/graph_properties.gif' border='0' alt='Graph Source/Properties' title='Graph Source/Properties' style='padding: 3px;'></a>
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
	$rra = db_fetch_row("select timespan,steps,name from rra where id=" . $_GET["rra_id"]);

	/* define the time span, which decides which rra to use */
	$timespan = -($rra["timespan"]);

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
		if (($graph_end - $_GET["graph_start"])>$max_timespan) {
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
	if ((read_config_option("rrdtool_version")) == "rrd-1.2.x") {
		if (read_graph_config_option("title_font") == "") {
			if (read_config_option("title_font") == "") {
                $title_font_size = 10;
			}else {
				$title_font_size = read_config_option("title_size");
			}
		}else {
			$title_font_size = read_graph_config_option("title_size");
		}
	}else {
		$title_font_size = 0;
	}

	?>
	<tr bgcolor='#<?php print $colors["header_panel"];?>'>
		<td colspan='3' class='textHeaderDark'>
			<strong>Zooming Graph</strong> '<?php print $graph_title;?>'
		</td>
	</tr>
	<div id='zoomBox' style='position:absolute; overflow:none; left:0px; top:0px; width:0px; height:0px; visibility:visible; background:red; filter:alpha(opacity=50); -moz-opacity:0.5; -khtml-opacity:0.5; opacity:0.5'></div>
	<div id='zoomSensitiveZone' style='position:absolute; overflow:none; left:0px; top:0px; width:0px; height:0px; visibility:visible; cursor:crosshair; background:blue; filter:alpha(opacity=0); -moz-opacity:0; -khtml-opacity:0; opacity:0' oncontextmenu='return false'></div>
	<STYLE MEDIA="print">
	/*Turn off the zoomBox*/
	div#zoomBox, div#zoomSensitiveZone {display: none}
	/*This keeps IE from cutting things off*/
	#why {position: static; width: auto}
	</STYLE>
	<tr>
		<td align='center'>
			<table width='1' cellpadding='0'>
				<tr>
					<td>
						<img id='zoomGraphImage' src='graph_image.php?local_graph_id=<?php print $_GET["local_graph_id"];?>&rra_id=<?php print $_GET["rra_id"];?>&view_type=<?php print $_REQUEST["view_type"];?>&graph_start=<?php print $graph_start;?>&graph_end=<?php print $graph_end;?>&graph_height=<?php print $graph_height;?>&graph_width=<?php print $graph_width;?>&title_font_size=<?php print $title_font_size ?>' border='0' alt='<?php print $graph_title;?>'>
					</td>
					<td valign='top' style='padding: 3px;' class='noprint'>
						<a href='graph.php?action=properties&local_graph_id=<?php print $_GET["local_graph_id"];?>&rra_id=<?php print $_GET["rra_id"];?>&view_type=<?php print $_REQUEST["view_type"];?>&graph_start=<?php print $graph_start;?>&graph_end=<?php print $graph_end;?>'><img src='images/graph_properties.gif' border='0' alt='Graph Source/Properties' title='Graph Source/Properties' style='padding: 3px;'></a>
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

	include("./include/zoom.js");

	break;
case 'properties':
	?>
	<tr bgcolor='#<?php print $colors["header_panel"];?>'>
		<td colspan='3' class='textHeaderDark'>
			<strong>Viewing Graph Properties </strong> '<?php print $graph_title;?>'
		</td>
	</tr>
	<tr>
		<td align='center'>
			<table width='1' cellpadding='0'>
				<tr>
					<td>
						<img src='graph_image.php?local_graph_id=<?php print $_GET["local_graph_id"];?>&rra_id=<?php print $_GET["rra_id"];?>&graph_start=<?php print (isset($_GET["graph_start"]) ? $_GET["graph_start"] : 0);?>&graph_end=<?php print (isset($_GET["graph_end"]) ? $_GET["graph_end"] : 0);?>' border='0' alt='<?php print $graph_title;?>'>
					</td>
					<td valign='top' style='padding: 3px;'>
						<a href='graph.php?action=zoom&local_graph_id=<?php print $_GET["local_graph_id"];?>&rra_id=<?php print $_GET["rra_id"];?>&view_type=<?php print $_REQUEST["view_type"];?>'><img src='images/graph_zoom.gif' border='0' alt='Zoom Graph' title='Zoom Graph' style='padding: 3px;'></a><br>
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
print "<br><br>";

include_once("./include/bottom_footer.php");

?>