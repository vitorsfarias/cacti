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

require_once(CACTI_INCLUDE_PATH . '/graph/graph_constants.php');

$graph_actions = array(
	GRAPH_ACTION_DELETE 					=> 'Delete',
	GRAPH_ACTION_CHANGE_TEMPLATE 			=> 'Change Graph Template',
	GRAPH_ACTION_DUPLICATE 					=> 'Duplicate',
	GRAPH_ACTION_CONVERT_TO_TEMPLATE 		=> 'Convert to Graph Template',
	GRAPH_ACTION_CHANGE_HOST 				=> 'Change Host',
	GRAPH_ACTION_REAPPLY_SUGGESTED_NAMES 	=> 'Reapply Suggested Names',
	GRAPH_ACTION_RESIZE 					=> 'Resize Graphs',
	GRAPH_ACTION_ENABLE_EXPORT 				=> 'Enable Graph Export',
	GRAPH_ACTION_DISABLE_EXPORT 			=> 'Disable Graph Export',
);

$rrd_font_render_modes = array(
	RRD_FONT_RENDER_NORMAL	=> 'Normal',
	RRD_FONT_RENDER_LIGHT	=> 'Light',
	RRD_FONT_RENDER_MONO	=> 'Mono',
);

$rrd_graph_render_modes = array(
	RRD_GRAPH_RENDER_NORMAL	=> 'Normal',
	RRD_GRAPH_RENDER_MONO	=> 'Mono',
);

$rrd_legend_position = array(
	RRD_LEGEND_POS_NORTH	=> 'North',
	RRD_LEGEND_POS_SOUTH	=> 'South',
	RRD_LEGEND_POS_WEST		=> 'West',
	RRD_LEGEND_POS_EAST		=> 'East',
);

$rrd_textalign = array(
	RRD_ALIGN_LEFT			=> 'Left',
	RRD_ALIGN_RIGHT			=> 'Right',
	RRD_ALIGN_JUSTIFIED		=> 'Justified',
	RRD_ALIGN_CENTER		=> 'Center',
);

$rrd_legend_direction = array(
	RRD_LEGEND_DIR_TOPDOWN	=> 'Top -> Down',
	RRD_LEGEND_DIR_BOTTOMUP	=> 'Bottom -> Up',
);

$graph_item_gprint_types = array(
	GRAPH_ITEM_TYPE_GPRINT			=> 'GPRINT:deprecated',
	GRAPH_ITEM_TYPE_GPRINT_AVERAGE	=> 'GPRINT:AVERAGE',
	GRAPH_ITEM_TYPE_GPRINT_LAST		=> 'GPRINT:LAST',
	GRAPH_ITEM_TYPE_GPRINT_MAX		=> 'GPRINT:MAX',
	GRAPH_ITEM_TYPE_GPRINT_MIN		=> 'GPRINT:MIN',
);

$graph_item_types1 = array(
	GRAPH_ITEM_TYPE_COMMENT			=> 'COMMENT',
	GRAPH_ITEM_TYPE_HRULE			=> 'HRULE',
	GRAPH_ITEM_TYPE_VRULE			=> 'VRULE',
	GRAPH_ITEM_TYPE_LINE1			=> 'LINE1',
	GRAPH_ITEM_TYPE_LINE2			=> 'LINE2',
	GRAPH_ITEM_TYPE_LINE3			=> 'LINE3',
	GRAPH_ITEM_TYPE_AREA			=> 'AREA',
	GRAPH_ITEM_TYPE_STACK		=> 'AREA:STACK',
);

$graph_item_types2 = array(
	GRAPH_ITEM_TYPE_LINESTACK		=> 'LINE:STACK',
	GRAPH_ITEM_TYPE_TICK			=> 'TICK',
	GRAPH_ITEM_TYPE_TEXTALIGN		=> 'TEXTALIGN',
	GRAPH_ITEM_TYPE_LEGEND			=> 'Legend',
	GRAPH_ITEM_TYPE_CUSTOM_LEGEND	=> 'Custom Legend',
);

$graph_item_types = $graph_item_types1 + $graph_item_gprint_types + $graph_item_types2;

$image_types = array(
	IMAGE_TYPE_PNG 	=> 'PNG',
	IMAGE_TYPE_GIF	=> 'GIF',
	IMAGE_TYPE_SVG	=> 'SVG',
);

$graph_color_alpha = array(
		'00' => '  0%',
		'19' => ' 10%',
		'33' => ' 20%',
		'4C' => ' 30%',
		'66' => ' 40%',
		'7F' => ' 50%',
		'99' => ' 60%',
		'B2' => ' 70%',
		'CC' => ' 80%',
		'E5' => ' 90%',
		'FF' => '100%'
);

$colortag_sequence = array(
	COLORTAGS_GLOBAL 	=> 'Accept global colortags only, if any',
	COLORTAGS_USER	 	=> 'Accept user colortags only, if any',
	COLORTAGS_TEMPLATE 	=> 'Accept graph template colortags only, if any',
	COLORTAGS_UTG	 	=> 'Accept user colortags, template next, global last',
	COLORTAGS_TUG	 	=> 'Accept template colortags, user next, global last',
);

$graph_views = array(
	GRAPH_TREE_VIEW 	=> 'Tree View',
	GRAPH_LIST_VIEW 	=> 'List View',
	GRAPH_PREVIEW_VIEW 	=> 'Preview View',
);

$graph_timespans = array(
	GT_LAST_HALF_HOUR 	=> 'Last Half Hour',
	GT_LAST_HOUR 		=> 'Last Hour',
	GT_LAST_2_HOURS 	=> 'Last %d Hours', 2,
	GT_LAST_4_HOURS 	=> 'Last %d Hours', 4,
	GT_LAST_6_HOURS 	=> 'Last %d Hours', 6,
	GT_LAST_12_HOURS 	=> 'Last %d Hours', 12,
	GT_LAST_DAY 		=> 'Last Day',
	GT_LAST_2_DAYS 		=> 'Last %d Days', 2,
	GT_LAST_3_DAYS 		=> 'Last %d Days', 3,
	GT_LAST_4_DAYS 		=> 'Last %d Days', 4,
	GT_LAST_WEEK 		=> 'Last Week',
	GT_LAST_2_WEEKS 	=> 'Last %d Weeks', 2,
	GT_LAST_MONTH 		=> 'Last Month',
	GT_LAST_2_MONTHS 	=> 'Last %d Months', 2,
	GT_LAST_3_MONTHS 	=> 'Last %d Months', 3,
	GT_LAST_4_MONTHS 	=> 'Last %d Months', 4,
	GT_LAST_6_MONTHS 	=> 'Last %d Months', 6,
	GT_LAST_YEAR 		=> 'Last Year',
	GT_LAST_2_YEARS 	=> 'Last %d Years', 2,
	GT_DAY_SHIFT 		=> 'Day Shift',
	GT_THIS_DAY 		=> 'This Day',
	GT_THIS_WEEK 		=> 'This Week',
	GT_THIS_MONTH 		=> 'This Month',
	GT_THIS_YEAR 		=> 'This Year',
	GT_PREV_DAY 		=> 'Previous Day',
	GT_PREV_WEEK 		=> 'Previous Week',
	GT_PREV_MONTH 		=> 'Previous Month',
	GT_PREV_YEAR 		=> 'Previous Year',
);

/* never translate this array */
$graph_timeshifts = array(
	GTS_HALF_HOUR 	=> '30 Min',
	GTS_1_HOUR 		=> '1 Hour',
	GTS_2_HOURS 	=> '2 Hours',
	GTS_4_HOURS 	=> '4 Hours',
	GTS_6_HOURS 	=> '6 Hours',
	GTS_12_HOURS 	=> '12 Hours',
	GTS_1_DAY 		=> '1 Day',
	GTS_2_DAYS 		=> '2 Days',
	GTS_3_DAYS 		=> '3 Days',
	GTS_4_DAYS 		=> '4 Days',
	GTS_1_WEEK 		=> '1 Week',
	GTS_2_WEEKS 	=> '2 Weeks',
	GTS_1_MONTH 	=> '1 Month',
	GTS_2_MONTHS 	=> '2 Months',
	GTS_3_MONTHS 	=> '3 Months',
	GTS_4_MONTHS 	=> '4 Months',
	GTS_6_MONTHS 	=> '6 Months',
	GTS_1_YEAR 		=> '1 Year',
	GTS_2_YEARS 	=> '2 Years',
);

$graph_timeshifts_localized = array(
	GTS_HALF_HOUR 	=> '30 Min',
	GTS_1_HOUR 		=> '1 Hour',
	GTS_2_HOURS 	=> '%d Hours', 2,
	GTS_4_HOURS 	=> '%d Hours', 4,
	GTS_6_HOURS 	=> '%d Hours', 6,
	GTS_12_HOURS 	=> '%d Hours', 12,
	GTS_1_DAY 		=> '1 Day',
	GTS_2_DAYS 		=> '%d Days', 2,
	GTS_3_DAYS 		=> '%d Days', 3,
	GTS_4_DAYS 		=> '%d Days', 4,
	GTS_1_WEEK 		=> '1 Week',
	GTS_2_WEEKS 	=> '%d Weeks', 2,
	GTS_1_MONTH 	=> '1 Month',
	GTS_2_MONTHS 	=> '%d Months', 2,
	GTS_3_MONTHS 	=> '%d Months', 3,
	GTS_4_MONTHS 	=> '%d Months', 4,
	GTS_6_MONTHS 	=> '%d Months', 6,
	GTS_1_YEAR 		=> '1 Year',
	GTS_2_YEARS 	=> '%d Years', 2,
);

$graph_weekdays = array(
	WD_SUNDAY	 	=> date('l', strtotime('Sunday')),
	WD_MONDAY 		=> date('l', strtotime('Monday')),
	WD_TUESDAY	 	=> date('l', strtotime('Tuesday')),
	WD_WEDNESDAY 	=> date('l', strtotime('Wednesday')),
	WD_THURSDAY 	=> date('l', strtotime('Thursday')),
	WD_FRIDAY	 	=> date('l', strtotime('Friday')),
	WD_SATURDAY		=> date('l', strtotime('Saturday'))
);

$graph_dateformats = array(
	GD_MO_D_Y =>'Month Number, Day, Year',
	GD_MN_D_Y =>'Month Name, Day, Year',
	GD_D_MO_Y =>'Day, Month Number, Year',
	GD_D_MN_Y =>'Day, Month Name, Year',
	GD_Y_MO_D =>'Year, Month Number, Day',
	GD_Y_MN_D =>'Year, Month Name, Day'
);

$graph_datechar = array(
	GDC_HYPHEN => '-',
	GDC_SLASH => '/',
	GDC_DOT => '.'
);
