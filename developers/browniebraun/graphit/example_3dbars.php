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

include_once('./graphit.class.php');
$chart = new graphit('Example XY Chart', 'xy', 600, 400);
$chart->set_axis('x1', 'Position' ,'', 'middle' );
$chart->set_axis('y', 'AVG', 'Bits/s', 'right');

$chart->set_visualization_type('3D');

$chart->set_object_font('TITLE', './DejaVuSansMono.ttf');
$chart->set_object_font('AXIS', './DejaVuSansMono.ttf');

/* optional: play a little bit with colors */
$chart->set_object_color('Y_AXIS','#c5c5c5');
$chart->set_object_color('X_AXIS','#c5c5c5');
$chart->set_object_color('GRID','#ffffff', 90);
$chart->set_object_color('AXIS_FONT','#c5c5c5');
$chart->set_object_color('TITLE_FONT', '#c5c5c5');
$chart->set_object_color('IMAGE_BORDER','#0000ff');
$chart->set_object_color('IMAGE_BACKGROUND', '000000');
$chart->set_object_color('CHART_BACKGROUND','#0000ff' , 90);

/* show Y- and X grid */
$chart->set_grid('dotted', 'dotted');

/* add some data series */
$chart->set_data_series('Serie 1', 'BAR', array(20, 4, 6, 50, -51), '#ff0000', 15);
$chart->set_data_series('Serie 2', 'BAR', array(5, 50,  25, 35, 0), '#ffff00', 15);
$chart->set_data_series('Serie 3', 'BAR', array(5, -16, 42, 30), '#556699', 15);

/* use A, B and C to label the first 3 Elements of the X-Axis. */
$chart->set_label_series(array('A', 'B', 'C'));

/* create the chart */
header ("Content-type: image/png");
$chart->create();
?>