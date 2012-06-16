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

require(CACTI_INCLUDE_PATH . '/graph/graph_arrays.php');
require(CACTI_INCLUDE_PATH . '/presets/preset_rra_arrays.php');

/* file: (graphs.php|graph_templates.php), action: (graph|template)_edit
 *
 * struct_graph was split into different parts to group options as man rrdgrapg suggests
 * by using array merge, all parts are added again at last
 * class options are used to allow display modification, e.g. inter-dependencies
 *
 * some options are available only with certain RRDTool Versions
 * most of them are upwards compatible (only know exception for now is GIF support)
 * TODO: Here's the deal:
 * To be prepared for complete database driven rrdtool option support,
 * we will handle RRDTool support data-centric, that is: here.
 * For ease of use with jQuery, we will use css class to tag version dependant options.
 * For upward compatibility, we will use negative tags, e.g.
 * class = not_RRD_1_0
 * This way, we are prepared for new RRDTool versions to show up.
 * //FIXME: Database driven RRDtool support?
 *
 * Current drawback:
 * If all options of a $struct_graph* are disabled, the user will see an empty table.
 * Due to html_start_box, we don't have an id (or better: a class) to catch those empty tables.
 * It is possible to solve this code-wise, but this would weaken the data-driven approach.
 *  * */
$struct_graph_labels = array(
	'title' => array(
		'friendly_name' => 'Title' . ' (--title &lt;' . 'string' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '255',
		'size' => '50',
		'default' => '',
		'description' => 'The name that is printed on the graph.',
	),
	'vertical_label' => array(
		'friendly_name' => 'Vertical Label' . ' (--vertical-label &lt;' . 'string' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '255',
		'default' => '',
		'size' => '30',
		'description' => 'The label vertically printed to the left of the graph.',
	),
	'image_format_id' => array(
		'friendly_name' => 'Image Format' . ' (--imgformat &lt;' . 'format' . '&gt;)',
		'method' => 'drop_array',
		'array' => $image_types,
		'default' => IMAGE_TYPE_PNG,
		'description' => 'The type of graph that is generated; PNG, GIF or SVG. The selection of graph image type is very RRDtool dependent.',
	),
);

$struct_graph_right_axis = array(
	'right_axis' => array(
		'friendly_name' => 'Right Axis' . ' (--right-axis &lt;' . 'scale:shift' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '20',
		'default' => '',
		'size' => '20',
		'description' => 'A second axis will be drawn to the right of the graph. It is tied to the left axis via the scale and shift parameters.',
		'class' => 'not_RRD_1_0_x not_RRD_1_2_x',
	),
	'right_axis_label' => array(
		'friendly_name' => 'Right Axis Label' . ' (--right-axis-label &lt;' . 'string' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '200',
		'default' => '',
		'size' => '30',
		'description' => 'The label for the right axis.',
		'class' => 'not_RRD_1_0_x not_RRD_1_2_x',
	),
	'right_axis_format' => array(
		'friendly_name' => 'Right Axis Format' . ' (--right-axis-format &lt;' . 'format' . '&gt;)',
		'method' => 'drop_sql',
		'sql' => 'select id,name from graph_templates_gprint order by name',
		'default' => '0',
		'none_value' => 'None',
		'description' => 'By default the format of the axis lables gets determined automatically. If you want to do this yourself, use this option with the same %lf arguments you know from the PRINT and GPRINT commands.',
		'class' => 'not_RRD_1_0_x not_RRD_1_2_x',
	),
);

$struct_graph_size = array(
	'height' => array(
		'friendly_name' => 'Height' . ' (--height) &lt;' . 'pixels' . '&gt;',
		'method' => 'textbox',
		'max_length' => '50',
		'default' => '120',
		'size' => '10',
		'description' => 'The height (in pixels) that the graph is.',
	),
	'width' => array(
		'friendly_name' => 'Width' . ' (--width) &lt;' . 'pixels' . '&gt;',
		'method' => 'textbox',
		'max_length' => '50',
		'default' => '500',
		'size' => '10',
		'description' => 'The width (in pixels) that the graph is.',
	),
	'only_graph' => array(
		'friendly_name' => 'Only Graph' . ' (--only-graph)',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'If you specify the --only-graph option and set the height &lt; 32 pixels you will get a tiny graph image (thumbnail) to use as an icon for use in an overview, for example. All labeling will be stripped off the graph.',
	),
	'full_size_mode' => array(
		'friendly_name' => 'Full Size Mode' . ' (--full-size-mode)',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'The width and height specify the final dimensions of the output image and the canvas is automatically resized to fit.',
		'class' => 'not_RRD_1_0_x not_RRD_1_2_x',
	),
);

$struct_graph_limits = array(
	'auto_scale' => array(
		'friendly_name' => 'Auto Scale',
		'method' => 'checkbox',
		'default' => CHECKED,
		'description' => 'Auto scale the y-axis instead of defining an upper and lower limit.' . '<br>' .
						'<strong>' . 'Note:' . ' </strong>' . 'if this is checked, both the Upper and Lower limit will be ignored.',
	),
	'auto_scale_opts' => array(
		'friendly_name' => 'Auto Scale Options',
		'method' => 'radio',
		'default' => GRAPH_ALT_AUTOSCALE_MIN,
		'description' => 'Use' . '<br>' .
			'--alt-autoscale to scale to the absolute minimum and maximum' . '<br>' .
		    '--alt-autoscale-max to scale to the maximum value, using a given lower limit' . '<br>' .
		    '--alt-autoscale-min to scale to the minimum value, using a given upper limit' . '<br>' .
			'--alt-autoscale (with limits) to scale using both lower and upper limits (rrdtool default)',
		'items' => array(
			GRAPH_ALT_AUTOSCALE => array(
				'radio_value' => GRAPH_ALT_AUTOSCALE,
				'radio_caption' => 'Use' . ' --alt-autoscale ' . '(ignoring given limits)',
			),
			GRAPH_ALT_AUTOSCALE_MAX => array(
				'radio_value' => GRAPH_ALT_AUTOSCALE_MAX,
				'radio_caption' => 'Use' . ' --alt-autoscale_max ' . '(accepting a lower limit)',
			),
			GRAPH_ALT_AUTOSCALE_MIN => array(
				'radio_value' => GRAPH_ALT_AUTOSCALE_MIN,
				'radio_caption' => 'Use' . ' --alt-autoscale_min ' . '(accepting an upper limit, requires rrdtool 1.2.x)',
				'class' => 'not_RRD_1_0_x not_RRD_1_2_x',
			),
			GRAPH_ALT_AUTOSCALE_LIMITS => array(
				'radio_value' => GRAPH_ALT_AUTOSCALE_LIMITS,
				'radio_caption' => 'Use' . ' --alt-autoscale ' . '(accepting both limits, rrdtool default)',
			)
		)
	),
	'auto_scale_rigid' => array(
		'friendly_name' => 'Rigid Boundaries Mode' . ' (--rigid)',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'Do not expand the lower and upper limit if the graph contains a value outside the valid range.',
	),
	'upper_limit' => array(
		'friendly_name' => 'Upper Limit' . ' (--upper-limit &lt;' . 'limit' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '50',
		'default' => '100',
		'size' => '10',
		'description' => 'The maximum vertical value for the rrd graph.',
	),
	'lower_limit' => array(
		'friendly_name' => 'Lower Limit' . ' (--lower-limit &lt;' . 'limit' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '255',
		'default' => '0',
		'size' => '10',
		'description' => 'The minimum vertical value for the rrd graph.',
	),
	'no_gridfit' => array(
		'friendly_name' => 'No Gridfit' . ' (--no-gridfit)',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'In order to avoid anti-aliasing blurring effects rrdtool snaps points to device resolution pixels, this results in a crisper appearance. If this is not to your liking, you can use this switch to turn this behaviour off.' . '<br>' .
						'<strong>' . 'Note:' . ' </strong>' . 'Gridfitting is turned off for PDF, EPS, SVG output by default.',
		'class' => 'not_RRD_1_0_x',
	),
);

$struct_graph_grid = array(
	'x_grid' => array(
		'friendly_name' => 'X Grid' . ' (--x-grid &lt;GTM:GST:MTM:MST:LTM:LST:LPR:LFM&gt;)',
		'description' => 'This parameter allows to specify a different grid layout (Global, Major, Label Grid). We refer to the X-Axis Presets here.',
		'method' => 'drop_sql',
		'sql' => 'select id,name from graph_templates_xaxis order by name',
		'default' => '0',
		'none_value' => 'None',
	),
	'unit_value' => array(		//FIXME: shall we rename to y_grid?
		'friendly_name' => 'Y Grid' . ' (--y-grid &lt;' . 'grid step:label factor' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '50',
		'default' => '',
		'size' => '30',
		'description' => 'Y-axis grid lines appear at each grid step interval. Labels are placed every label factor lines. You can specify \'none\' to suppress the grid and labels altogether.',
	),
	'alt_y_grid' => array(
		'friendly_name' => 'Alternative Y Grid' . ' (--alt-y-grid)',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'The algorithm ensures that you always have a grid, that there are enough but not too many grid lines, and that the grid is metric. This parameter will also ensure that you get enough decimals displayed even if your graph goes from 69.998 to 70.001.' . '<br>' .
						'<strong>' . 'Note:' . ' </strong>' . 'This parameter may interfere with --alt-autoscale options.',
	),
	'auto_scale_log' => array(
		'friendly_name' => 'Logarithmic Scaling' . ' (--logarithmic)',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'Use Logarithmic y-axis scaling',
		'class' => 'auto_scale_log',
	),
	'scale_log_units' => array(
		'friendly_name' => 'SI Units for Logarithmic Scaling' . ' (--units=si)',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'Use SI Units for Logarithmic Scaling instead of using exponential notation (not available for rrdtool-1.0.x).' . '<br>' .
						'<strong>' . 'Note:' . ' </strong>' . 'Linear graphs use SI notation by default.',
		'class' => 'scale_log_units not_RRD_1_0_x',
	),
	'unit_exponent_value' => array(
		'friendly_name' => 'Unit Exponent Value' . ' (--units-exponent &lt;' . 'exponent' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '50',
		'default' => '',
		'size' => '30',
		'description' => 'What unit cacti should use on the Y-axis. Use 3 to display everything in \'k\' or -6 to display everything in \'u\' (micro).',
	),
	'unit_length' => array(
		'friendly_name' => 'Unit Length' . ' (--units-length &lt;' . 'length' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '50',
		'default' => '',
		'size' => '30',
		'description' => 'How many digits should rrdtool assume the y-axis labels to be? You may have to use this option to make enough space once you start fiddeling with the y-axis labeling.',
	),
);

$struct_graph_color = array(
	'colortag_back' => array(
		'friendly_name' => 'Background' . ' (--color BACK &lt;rrggbb[aa]&gt;)',
		'method' => 'textbox',
		'max_length' => '6',
		'default' => '',
		'size' => '6',
		'description' => 'Color tag of the background.',
		'class' => 'colortags',
	),
	'colortag_canvas' => array(
		'friendly_name' => 'Canvas' . ' (--color CANVAS &lt;rrggbb[aa]&gt;)',
		'method' => 'textbox',
		'max_length' => '6',
		'default' => '',
		'size' => '6',
		'description' => 'Color tag of the background of the actual graph.',
		'class' => 'colortags',
	),
	'colortag_shadea' => array(
		'friendly_name' => 'ShadeA' . ' (--color SHADEA &lt;rrggbb[aa]&gt;)',
		'method' => 'textbox',
		'max_length' => '6',
		'default' => '',
		'size' => '6',
		'description' => 'Color tag of the left and top border.',
		'class' => 'colortags',
	),
	'colortag_shadeb' => array(
		'friendly_name' => 'ShadeB' . ' (--color SHADEB &lt;rrggbb[aa]&gt;)',
		'method' => 'textbox',
		'max_length' => '6',
		'default' => '',
		'size' => '6',
		'description' => 'Color tag of the right and bottom border.',
		'class' => 'colortags',
	),
	'colortag_grid' => array(
		'friendly_name' => 'Grid' . ' (--color GRID &lt;rrggbb[aa]&gt;)',
		'method' => 'textbox',
		'max_length' => '6',
		'default' => '',
		'size' => '6',
		'description' => 'Color tag of the grid.',
		'class' => 'colortags',
	),
	'colortag_mgrid' => array(
		'friendly_name' => 'Major Grid' . ' (--color MGRID &lt;rrggbb[aa]&gt;)',
		'method' => 'textbox',
		'max_length' => '6',
		'default' => '',
		'size' => '6',
		'description' => 'Color tag of the major grid.',
		'class' => 'colortags',
	),
	'colortag_font' => array(
		'friendly_name' => 'Font' . ' (--color FONT &lt;rrggbb[aa]&gt;)',
		'method' => 'textbox',
		'max_length' => '6',
		'default' => '',
		'size' => '6',
		'description' => 'Color tag of the font.',
		'class' => 'colortags',
	),
	'colortag_axis' => array(
		'friendly_name' => 'Axis' . ' (--color AXIS &lt;rrggbb[aa]&gt;)',
		'method' => 'textbox',
		'max_length' => '6',
		'default' => '',
		'size' => '6',
		'description' => 'Color tag of the axis.',
		'class' => 'colortags',
	),
	'colortag_frame' => array(
		'friendly_name' => 'Frame' . ' (--color FRAME &lt;rrggbb[aa]&gt;)',
		'method' => 'textbox',
		'max_length' => '6',
		'default' => '',
		'size' => '6',
		'description' => 'Color tag of the frame.',
		'class' => 'colortags',
	),
	'colortag_arrow' => array(
		'friendly_name' => 'Arrow' . ' (--color ARROW &lt;rrggbb[aa]&gt;)',
		'method' => 'textbox',
		'max_length' => '6',
		'default' => '',
		'size' => '6',
		'description' => 'Color tag of the arrow.',
		'class' => 'colortags',
	),
);

$struct_graph_legend = array(
	'dynamic_labels' => array(
		'friendly_name' => 'Dynamic Labels' . ' (--dynamic-labels)',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'Draw line markers as a line.',
	),
	'force_rules_legend' => array(
		'friendly_name' => 'Force Rules Legend' . ' (--force-rules-legend)',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'Force the generation of HRULE and VRULE legends.',
	),
	'legend_position' => array(
		'friendly_name' => 'Legend Position' . ' (--legend-position=&lt;' . 'position' . '&gt;)',
		'method' => 'drop_array',
		'array' => $rrd_legend_position,
		'none_value' => 'None',
		'description' => 'Place the legend at the given side of the graph.',
		'class' => 'not_RRD_1_0_x not_RRD_1_2_x not_RRD_1_3_x',
	),
	'legend_direction' => array(
		'friendly_name' => 'Legend Direction' . ' (--legend-direction=&lt;' . 'direction' . '&gt;)',
		'method' => 'drop_array',
		'array' => $rrd_legend_direction,
		'none_value' => 'None',
		'description' => 'Place the legend items in the given vertical order.',
		'class' => 'not_RRD_1_0_x not_RRD_1_2_x not_RRD_1_3_x',
	),
);

$struct_graph_misc = array(
	'grid_dash' => array(
		'friendly_name' => 'Grid Dash' . ' (--grid-dash &lt;' . 'on:off' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '10',
		'default' => '',
		'size' => '10',
		'description' => 'By default the grid is drawn in a 1 on, 1 off pattern.',
	),
	'border' => array(
		'friendly_name' => 'Border' . ' (--border &lt;' . 'width' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '10',
		'default' => '',
		'size' => '10',
		'description' => 'Width in pixels for the 3rd border drawn around the image. 0 disables the border.',
	),
	'font_render_mode' => array(
		'friendly_name' => 'Font Render Mode' . ' (--font-render-mode &lt;' . 'mode' . '&gt;)',
		'method' => 'drop_array',
		'array' => $rrd_font_render_modes,
		'none_value' => 'None',
		'description' => 'Mode for font rendering.',
		'class' => 'not_RRD_1_0_x',
	),
	'font_smoothing_threshold' => array(
		'friendly_name' => 'Font Smoothing Threshold' . ' (--font-smoothing-threshold &lt;' . 'threshold' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '8',
		'default' => '',
		'size' => '8',
		'description' => 'This specifies the largest font size which will be rendered bitmapped, that is, without any font smoothing. By default, no text is rendered bitmapped.',
		'class' => 'not_RRD_1_0_x',
	),
	'graph_render_mode' => array(
		'friendly_name' => 'Graph Render Mode' . ' (--graph-render-mode &lt;' . 'mode' . '&gt;)',
		'method' => 'drop_array',
		'array' => $rrd_graph_render_modes,
		'none_value' => 'None',
		'description' => 'Mode for graph rendering.',
		'class' => 'not_RRD_1_0_x not_RRD_1_2_x',
	),
	'pango_markup' => array(
		'friendly_name' => 'Pango Markup' . ' (--pango-markup)',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'With this option, all text will be processed by pango markup. This allows to embed some simple html like markup tags.',
		'class' => 'not_RRD_1_0_x not_RRD_1_2_x',
	),
	'slope_mode' => array(
		'friendly_name' => 'Slope Mode' . ' (--slope-mode)',
		'method' => 'checkbox',
		'default' => CHECKED,
		'description' => 'Using Slope Mode, in RRDtool 1.2.x and above, evens out the shape of the graphs at the expense of some on screen resolution.',
		'class' => 'not_RRD_1_0_x',
	),
	'interlaced' => array(
		'friendly_name' => 'Interlaced' . ' (--interlaced)',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'If images are interlaced they become visible on browsers more quickly (this gets ignored in 1.3 for now!).',
	),
	'tab_width' => array(
		'friendly_name' => 'Tabulator Width' . ' (--tabwidth &lt;' . 'pixels' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '50',
		'default' => '',
		'size' => '10',
		'description' => 'Width of a tabulator in pixels.',
		'class' => 'not_RRD_1_0_x',
	),
	'base_value' => array(
		'friendly_name' => 'Base Value' . ' (--base &lt;[1000|1024]&gt;)',
		'method' => 'textbox',
		'max_length' => '50',
		'default' => '1000',
		'size' => '10',
		'description' => 'Should be set to 1024 for memory and 1000 for traffic measurements.',
		'class' => 'not_RRD_1_0_x',
	),
	'watermark' => array(
		'friendly_name' => 'Watermark' . ' (--watermark &lt;' . 'string' . '&gt;)',
		'method' => 'textbox',
		'max_length' => '255',
		'default' => '',
		'size' => '30',
		'description' => 'Adds the given string as a watermark, horizontally centered, at the bottom of the graph.',
		'class' => 'not_RRD_1_0_x',
	),
);

$struct_graph_cacti = array(
	'auto_padding' => array(
		'friendly_name' => 'Auto Padding',
		'method' => 'checkbox',
		'default' => CHECKED,
		'description' => 'Pad text so that legend and graph data always line up. Note: this could cause graphs to take longer to render because of the larger overhead. Also Auto Padding may not be accurate on all types of graphs, consistant labeling usually helps.',
	),
	'export' => array(
		'friendly_name' => 'Allow Graph Export',
		'method' => 'checkbox',
		'default' => CHECKED,
		'description' => 'Choose whether this graph will be included in the static html/png export if you use the export feature.',
	),
);

/* for use with existing modules */
$struct_graph = array_merge($struct_graph_labels, $struct_graph_right_axis, $struct_graph_size, $struct_graph_limits, $struct_graph_grid, $struct_graph_color, $struct_graph_legend, $struct_graph_misc, $struct_graph_cacti);

/* file: (graphs.php|graph_templates.php), action: item_edit */
$struct_graph_item = array(
	'task_item_id' => array(
		'friendly_name' => 'Data Source',
		'method' => 'drop_sql',
		'sql' => "select
			CONCAT_WS('', CASE WHEN host.description IS NULL THEN 'No Host' WHEN host.description IS NOT NULL THEN host.description end,' - ',data_template_data.name,' (',data_template_rrd.data_source_name,')') AS name,
			data_template_rrd.id
			FROM (data_template_data,data_template_rrd,data_local)
			LEFT JOIN host ON (data_local.host_id=host.id)
			WHERE data_template_rrd.local_data_id=data_local.id
			AND data_template_data.local_data_id=data_local.id
			ORDER BY name",
		'default' => '0',
		'none_value' => 'None',
		'description' => 'The data source to use for this graph item.',
		'class' => 'not_RRD_TYPE_COMMENT not_RRD_TYPE_TEXTALIGN not_RRD_TYPE_HRULE not_RRD_TYPE_VRULE',
	),
	'consolidation_function_id' => array(
		'friendly_name' => 'Consolidation Function',
		'method' => 'drop_array',
		'array' => $consolidation_functions,
		'default' => '0',
		'description' => 'How data for this item is represented statistically on the graph.',
		'class' => 'not_RRD_TYPE_COMMENT not_RRD_TYPE_TEXTALIGN not_RRD_TYPE_HRULE not_RRD_TYPE_VRULE',
	),
	'graph_type_id' => array(
		'friendly_name' => 'Graph Item Type',
		'method' => 'drop_array',
		'array' => $graph_item_types,
		'default' => '0',
		'description' => 'How data for this item is represented visually on the graph.' . '<br>' .
						'<strong>' . 'Note: ' . '</strong>' .
						'To customize the \'Custom Legend\' shortcut, see \'Settings -> Legend\'',
	),
	'line_width' => array(
		'friendly_name' => 'Line Width (decimal)',
		'method' => 'textbox',
		'max_length' => '5',
		'default' => '',
		'size' => '5',
		'description' => 'In case LINE was chosen, specify width of line here.',
		'class' => 'not_RRD_1_0_x not_RRD_TYPE_COMMENT not_RRD_TYPE_TEXTALIGN not_RRD_TYPE_HRULE not_RRD_TYPE_VRULE not_RRD_TYPE_AREA not_RRD_TYPE_AREASTACK not_RRD_TYPE_GPRINT not_RRD_TYPE_TICK',
	),
	'dashes' => array(
		'friendly_name' => 'Dashes (dashes[=on_s[,off_s[,on_s,off_s]...]])',
		'method' => 'textbox',
		'max_length' => '20',
		'default' => '',
		'size' => '10',
		'description' => 'The dashes modifier enables dashed line style.',
		'class' => 'not_RRD_1_0_x not_RRD_1_2_x not_RRD_TYPE_COMMENT not_RRD_TYPE_TEXTALIGN not_RRD_TYPE_AREA not_RRD_TYPE_AREASTACK not_RRD_TYPE_GPRINT not_RRD_TYPE_TICK',
	),
	'dash_offset' => array(
		'friendly_name' => 'Dash Offset (dash-offset=offset)',
		'method' => 'textbox',
		'max_length' => '4',
		'default' => '',
		'size' => '4',
		'description' => 'The dash-offset parameter specifies an offset into the pattern at which the stroke begins.',
		'class' => 'not_RRD_1_0_x not_RRD_1_2_x not_RRD_TYPE_COMMENT not_RRD_TYPE_TEXTALIGN not_RRD_TYPE_AREA not_RRD_TYPE_AREASTACK not_RRD_TYPE_GPRINT not_RRD_TYPE_TICK',
	),
	'color_id' => array(
		'friendly_name' => 'Color',
		'method' => 'drop_color',
		'default' => '0',
		'on_change' => 'changeColorId()',
		'description' => 'The color to use for the legend.',
		'class' => 'not_RRD_TYPE_COMMENT not_RRD_TYPE_TEXTALIGN not_RRD_TYPE_GPRINT',
	),
	'alpha' => array(
		'friendly_name' => 'Opacity/Alpha Channel',
		'method' => 'drop_array',
		'default' => 'FF',
		'array' => $graph_color_alpha,
		'description' => 'The opacity/alpha channel of the color.',
		'class' => 'not_RRD_1_0_x not_RRD_TYPE_COMMENT not_RRD_TYPE_TEXTALIGN not_RRD_TYPE_GPRINT',
	),
	'cdef_id' => array(
		'friendly_name' => 'CDEF Function',
		'method' => 'drop_sql',
		'sql' => 'select id,name from cdef order by name',
		'default' => '0',
		'none_value' => 'None',
		'description' => 'A CDEF (math) function to apply to this item on the graph.',
		'class' => 'not_RRD_TYPE_COMMENT not_RRD_TYPE_TEXTALIGN',
	),
	'vdef_id' => array(
		'friendly_name' => 'VDEF Function',
		'method' => 'drop_sql',
		'sql' => 'select id,name from vdef order by name',
		'default' => '0',
		'none_value' => 'None',
		'description' => 'A VDEF (math) function to apply to this item on the legend.',
		'class' => 'not_RRD_1_0_x not_RRD_TYPE_COMMENT not_RRD_TYPE_TEXTALIGN',
	),
	'shift' => array(
		'friendly_name' => 'Shift Data',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'Offset your data on the time axis (x-axis) by the amount specified in the \'value\' field.',
		'class' => 'not_RRD_1_0_x not_RRD_TYPE_COMMENT not_RRD_TYPE_TEXTALIGN not_RRD_TYPE_GPRINT not_RRD_TYPE_HRULE not_RRD_TYPE_VRULE',
	),
	'value' => array(
		'friendly_name' => 'Value',
		'method' => 'textbox',
		'max_length' => '50',
		'default' => '',
		'size' => '10',
		'description' => '[HRULE|VRULE]: The value of the graph item' . '<br/>' .
						'[TICK]: The fraction for the tick line.' . '<br/>' .
						'[SHIFT]: The time offset in seconds.',
		'class' => 'not_RRD_TYPE_COMMENT not_RRD_TYPE_TEXTALIGN not_RRD_TYPE_LINE not_RRD_TYPE_LINESTACK not_RRD_TYPE_AREA not_RRD_TYPE_AREASTACK not_RRD_TYPE_GPRINT RRD_TYPE_SHIFT',
	),
	'gprint_id' => array(
		'friendly_name' => 'GPRINT Type',
		'method' => 'drop_sql',
		'sql' => 'select id,name from graph_templates_gprint order by name',
		'default' => '2',
		'description' => 'If this graph item is a GPRINT, you can optionally choose another format here. You can define additional types under \'GPRINT Presets\'.',
		'class' => 'not_RRD_TYPE_COMMENT not_RRD_TYPE_TEXTALIGN not_RRD_TYPE_HRULE not_RRD_TYPE_VRULE not_RRD_TYPE_LINE not_RRD_TYPE_LINESTACK not_RRD_TYPE_AREA not_RRD_TYPE_AREASTACK not_RRD_TYPE_TICK',
	),
	'textalign' => array(
		'friendly_name' => 'Text Alignment' . ' (TEXTALIGN)',
		'method' => 'drop_array',
		'value' => '|arg1:textalign|',
		'array' => $rrd_textalign,
		'none_value' => 'None',
		'description' => 'All subsequent legend line(s) will be aligned as given here.' .
						'You may use this command multiple times in a single graph.' .
						'This command does not produce tabular layout.' . '<br/>' .
						'<strong>' . 'Note: ' . '</strong>' .
						'You may want to insert a &lt;HR&gt; on the preceding graph item.' . '<br/>' .
						'<strong>' . 'Note: ' . '</strong>' .
						'A &lt;HR&gt; on this legend line will obsolete this setting!',
						'class' => 'not_RRD_1_0_x not_RRD_1_2_x not_RRD_TYPE_COMMENT not_RRD_TYPE_GPRINT not_RRD_TYPE_HRULE not_RRD_TYPE_VRULE not_RRD_TYPE_LINE not_RRD_TYPE_LINESTACK not_RRD_TYPE_AREA not_RRD_TYPE_AREASTACK not_RRD_TYPE_TICK',
	),
	'text_format' => array(
		'friendly_name' => 'Text Format',
		'method' => 'textbox',
		'max_length' => '255',
		'default' => '',
		'description' => 'Text that will be displayed on the legend for this graph item.',
		'class' => 'not_RRD_TYPE_TEXTALIGN',
	),
	'hard_return' => array(
		'friendly_name' => 'Insert Hard Return',
		'method' => 'checkbox',
		'default' => '',
		'description' => 'Forces the legend to the next line after this item.',
		'class' => 'not_RRD_TYPE_TEXTALIGN',
	),
	'sequence' => array(
		'friendly_name' => 'Sequence',
		'method' => 'view'
	)
);


/* file: (graphs.php -> lib/graph.php), action: edit */
$struct_graph_header = array(
		"graph_template_id" => array(
			"method" => "autocomplete",
			"callback_function" => "graphs.php?action=ajax_get_graph_templates",
			"friendly_name" => "Selected Graph Template",
			"description" => "Choose a graph template to apply to this graph.  Please note that graph data may be lost if you change the graph template after one is already applied.",
			"id" => "|arg1:graph_template_id|",
			"sql" => "SELECT name FROM graph_templates WHERE id=|arg1:graph_template_id|"
			),
		"host_id" => array(
			"method" => "autocomplete",
			"callback_function" => "graphs.php?action=ajax_get_devices_detailed",
			"friendly_name" => "Host",
			"description" => "Choose the device that this graph belongs to.",
			"id" => "|arg2:host_id|",
			"sql" => "SELECT CONCAT_WS('',description,' (',hostname,')') FROM host WHERE id=|arg2:host_id|"
			),
		"graph_template_graph_id" => array(
			"method" => "hidden",
			"value" => "|arg1:id|"
			),
		"local_graph_id" => array(
			"method" => "hidden",
			"value" => "|arg1:local_graph_id|"
			),
		"local_graph_template_graph_id" => array(
			"method" => "hidden",
			"value" => "|arg1:local_graph_template_graph_id|"
			),
		"hidden_graph_template_id" => array(
			"method" => "hidden",
			"value" => "|arg1:graph_template_id|"
			),
		"hidden_host_id" => array(
			"method" => "hidden",
			"value" => "|arg2:host_id|"
			)
		);
