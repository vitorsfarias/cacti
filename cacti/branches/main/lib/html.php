<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2011 The Cacti Group                                 |
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

/* html_start_box - draws the start of an HTML box with an optional title
   @param $title - the title of this box ("" for no title)
   @param $width - the width of the box in pixels or percent
   @param $cell_padding - the amount of cell padding to use inside of the box
   @param $align - the HTML alignment to use for the box (center, left, or right)
   @param $add_text - the url to use when the user clicks 'Add' in the upper-right
     corner of the box ("" for no 'Add' link) or use "menu::menu_title:menu_id:menu_class:ajax_parameters"
     to show a drop down menu instead
   @param $collapsing - tells wether or not the table collapses
   @param $table_id - the table id to make the table addressable by jQuery's table DND plugin */
function html_start_box($title, $width, $cell_padding, $align, $add_text = "", $collapsing = false, $table_id = "") {
	static $form_number = 0;
	$form_number++;

	$function_name = "addObject" . $form_number . "()";

	$temp_string = str_replace("strong", "", $title);
	if (strpos($temp_string, "[")) {
		$temp_string = substr($temp_string, 0, strpos($temp_string, "[")-1);
	}

	if ($title != "") {
		$item_id = clean_up_name($temp_string);
	}else{
		$item_id = "item_" . rand(255, 65535);
	}

	if ($collapsing) {
		$ani  = "style=\"cursor:pointer;\" onClick=\"htmlStartBoxFilterChange('" . $item_id . "')\"";
		$ani3 = "onClick=\"htmlStartBoxFilterChange('" . $item_id . "')\"";
	}else{
		$ani  = "";
		$ani3 = "";
	}

	$table_id = ($table_id != '') ? "id=\"$table_id\"" : "";

	/* we need the addObject function definition in case we have an "$add_text" */
	if ($collapsing || (strlen($add_text))) { ?>
		<script type="text/javascript">
		<!--
		function <?php print $function_name;?> {
			document.location = '<?php echo $add_text;?>';
			return false;
		}
		$().ready(function() {
			htmlStartBoxFilterChange('<?php print $item_id;?>', true);
		});
		-->
		</script>
	<?php } ?>
		<table cellpadding='0' cellspacing='0' align='<?php print $align;?>' class='startBoxHeader <?php print 'wp' . $width;?> startBox0'>
			<?php if ($title != '') {?><tr class='rowHeader'>
				<td colspan='100'>
					<table cellpadding='0' cellspacing='0' class='startBox0'>
						<tr>
							<td>
								<table cellpadding='0' cellspacing='0' class='startBox0' <?php print $ani;?>>
									<tr>
										<?php if ($collapsing) {?><td class='textHeaderDark nw9'>
											<img id='<?php print $item_id . '_twisty';?>' src='<?php print CACTI_URL_PATH; ?>images/tw_open.gif' alt='<?php print __('Filter');?>' align='middle'>
										</td><?php } ?>
										<td onMouseDown='return false' class='textHeaderDark'><?php print $title;?>
										</td>
									</tr>
								</table>
							</td>
							<?php
							if ($add_text != '') {
								if (strpos($add_text, 'menu::') !== false) {
										list($menu_title, $menu_id, $menu_class, $ajax_parameters) = explode(':', str_replace('menu::', '', $add_text));?>
							<td class='textHeaderDark w1 right'>
								<span name='<?php print $menu_title;?>' id='<?php print $menu_id;?>' class='<?php print $menu_class;?> cacti_dd_link' rel='<?php print htmlspecialchars($ajax_parameters);?>'><img src='<?php print CACTI_URL_PATH; ?>images/cog.png' id='cog' width='16' height='16' alt='cog'></span>
							</td><?php
								}else {	?>
							<td class='textHeaderDark w1 right'>
								<input type='button' onClick='<?php print $function_name;?>' style='font-size:10px;' value='Add'>
							</td><?php
								}
							}?>
						</tr>
					</table>
				</td>
			</tr>
			<?php }?>
			<tr style='border: 0px;' id='<?php print $item_id;?>'>
				<td>
					<table cellpadding='0' cellspacing='1' <?php print $table_id;?> class='startBox<?php print $cell_padding;?>'><?php
}

function html_start_box_dq($query_name, $query_id, $device_id, $colspan, $width, $cell_padding, $align) {
	$temp_string = str_replace('strong', '', $query_name);
	if (strpos($temp_string, '[')) {
		$temp_string = substr($temp_string, 0, strpos($temp_string, '[')-1);
	}

	if ($query_name != '') {
		$item_id = clean_up_name($temp_string);
	}else{
		$item_id = 'item_' . rand(255, 65535);
	}

	?>
		<table cellpadding='0' cellspacing='0' align='<?php print $align;?>' class='startBoxHeader startBox0'>
			<tr class='rowHeader'>
				<td style='padding:0px 5px 0px 5px;' colspan='<?php print $colspan+1;?>'>
					<table cellpadding='0' cellspacing='1' class='startBox0' >
						<tr>
							<td class='textHeaderDark'>
								<?php print __('Data Query');?> [<?php print $query_name; ?>]
							</td>
							<td class='right nw'>
								<a href='graphs_new.php?action=query_reload&amp;id=<?php print $query_id;?>&amp;device_id=<?php print $device_id;?>'><img class='buttonSmall' src='images/reload_icon_small.gif' alt='<?php print __('Reload');?>' title='<?php print __('Reload Associated Query');?>' align='middle'></a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr style='border: 0px;' id='<?php print $item_id;?>'>
				<td colspan='<?php print $colspan+1;?>'>
					<table cellpadding='0' cellspacing='1' class='startBox<?php print $cell_padding;?>'><?php
}

/* html_end_box - draws the end of an HTML box
   @param $trailing_br (bool) - whether to draw a trailing <br> tag after ending
     the box */
function html_end_box($trailing_br = true) { ?>
					</table>
				</td>
			</tr>
		</table>
		<?php if ($trailing_br == true) { print '<br>'; } ?>
<?php }

/* html_graph_start_box - draws the start of an HTML graph view box
   @param $cellpadding - the table cell padding for the box
   @param $leading_br (bool) - whether to draw a leader <br> tag before the start of the table */
function html_graph_start_box($cellpadding = 3, $leading_br = true) {
	if ($leading_br == true) {
		print "<br>\n";
	}

	print "\t<table class='startBox1' cellpadding='$cellpadding'>\n";
}

/* html_graph_end_box - draws the end of an HTML graph view box */
function html_graph_end_box() {
	print "</table>\n";
}

/* html_graph_area - draws an area the contains full sized graphs
   @param $graph_array - the array to contains graph information. for each graph in the
     array, the following two keys must exist
     $arr[0]["local_graph_id"] // graph id
     $arr[0]["title_cache"] // graph title
   @param $no_graphs_message - display this message if no graphs are found in $graph_array
   @param $extra_url_args - extra arguments to append to the url
   @param $header - html to use as a header */
function html_graph_area(&$graph_array, $no_graphs_message = '', $extra_url_args = '', $header = '') {
	$i = 0;
	if (sizeof($graph_array) > 0) {
		if ($header != '') {
			print $header;
		}

		foreach ($graph_array as $graph) {
			if (isset($graph['graph_template_name'])) {
				if (isset($prev_graph_template_name)) {
					if ($prev_graph_template_name != $graph['graph_template_name']) {
						$print  = true;
						$prev_graph_template_name = $graph['graph_template_name'];
					}else{
						$print = false;
					}
				}else{
					$print  = true;
					$prev_graph_template_name = $graph['graph_template_name'];
				}

				if ($print) {
					print "\t\t<tr class='rowSubHeader'>
						<td colspan='3' class='textHeaderDark'>
							" . __('Graph Template:') . " " . $graph['graph_template_name'] . "
						</td>
					</tr>";
				}
			}elseif (isset($graph['data_query_name'])) {
				if (isset($prev_data_query_name)) {
					if ($prev_data_query_name != $graph['data_query_name']) {
						$print  = true;
						$prev_data_query_name = $graph['data_query_name'];
					}else{
						$print = false;
					}
				}else{
					$print  = true;
					$prev_data_query_name = $graph['data_query_name'];
				}

				if ($print) {
					print "\t\t\t<tr class='rowSubHeaderAlt'><td colspan='3' class='textHeaderLight'>" . __('Data Query:') . " " . $graph['data_query_name'] . "</td></tr>";
				}
				print "<tr class='rowSubHeader'>
					<td colspan='3' class='textHeaderDark'>
						" . $graph['sort_field_value']. "
					</td>
				</tr>";
			}

			?>
			<tr align='center' style='background-color: #<?php print ($i % 2 == 0 ? 'f9f9f9' : 'ffffff');?>;'>
				<td>
					<table cellpadding='0'>
						<tr>
							<td>
								<?php
								if ($graph['image_format_id'] == IMAGE_TYPE_PNG || $graph['image_format_id'] == IMAGE_TYPE_GIF) {
									?>
									<div style='min-height: <?php echo (1.6 * $graph['height']) . 'px'?>;'>
									<a href='<?php print htmlspecialchars('graph.php?action=view&local_graph_id=' . $graph['local_graph_id'] . '&rra_id=all');?>'>
										<img class='graphimage' id='graph_<?php print $graph['local_graph_id'] ?>'
											src='<?php print htmlspecialchars('graph_image.php?local_graph_id=' . $graph['local_graph_id'] . '&rra_id=0&graph_height=' . $graph['height'] . '&graph_width=' . $graph['width'] . '&title_font_size=' . ((read_graph_config_option('custom_fonts') == 'on') ? read_graph_config_option('title_size') : read_config_option('title_size')) . (($extra_url_args == '') ? '' : "&$extra_url_args"));?>'
											border='0' alt='<?php print $graph['title_cache'];?>'>
									</a></div>
									<?php
								} elseif ($graph['image_format_id'] == IMAGE_TYPE_SVG) {
									?>
									<div style='min-height: <?php echo (1.6 * $graph['height']) . 'px'?>;'>
									<a href='<?php print htmlspecialchars('graph.php?action=view&local_graph_id=' . $graph['local_graph_id'] . '&rra_id=all');?>'>
										<object class='graphimage' id='graph_<?php print $graph['local_graph_id'] ?>'
											type='svg+xml'
											data='<?php print htmlspecialchars('graph_image.php?local_graph_id=' . $graph['local_graph_id'] . '&rra_id=0' . (($extra_url_args == '') ? '' : "&$extra_url_args"));?>'
											border='0'>
											Can't display SVG
										</object>;
									</a></div>
									<?php
									#print "<object class='graphimage' id='graph_" . $graph["local_graph_id"] . "' type='svg+xml' data='" . htmlspecialchars("graph_image.php?action=view&local_graph_id=" . $graph["local_graph_id"] . "&rra_id=" . $rra["id"]) . "' border='0'>Can't display SVG</object>";
								}
								print (read_graph_config_option('show_graph_title') == CHECKED ? "<p style='font-size: 10;' align='center'>" . $graph['title_cache'] . "</p>" : "");
								?>
							</td>
							<td valign='top' style='align: left; padding: 3px;' class='noprint'>
								<a href='<?php print htmlspecialchars("graph.php?action=zoom&local_graph_id=" . $graph["local_graph_id"] . "&rra_id=0&" . $extra_url_args);?>'><img src='images/graph_zoom.gif' alt='<?php print __("Zoom Graph");?>' title='<?php print __("Zoom Graph");?>' class='img_info'></a><br>
								<a href='<?php print htmlspecialchars("graph_xport.php?local_graph_id=" . $graph["local_graph_id"] . "&rra_id=0&" . $extra_url_args);?>'><img src='images/graph_query.png' alt='<?php print __("CSV Export");?>' title='<?php print __("CSV Export");?>' class='img_info'></a><br>
								<a href='<?php print htmlspecialchars("graph.php?action=properties&local_graph_id=" . $graph["local_graph_id"] . "&rra_id=0&" . $extra_url_args);?>'><img src='images/graph_properties.gif' alt='<?php print __("Properties");?>' title='<?php print __("Graph Source/Properties");?>' class='img_info'></a><br>
								<a href=<?php print htmlspecialchars(get_browser_query_string() . "#page_top");?>><img src='images/graph_page_top.gif' alt='<?php print __("Page Top");?>' title='<?php print __("Page Top");?>' class='img_info'></a><br>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<?php

			$i++;
		}
	}else{
		if ($no_graphs_message != "") {
			print "<td><em>$no_graphs_message</em></td>";
		}
	}
}

/* html_graph_thumbnail_area - draws an area the contains thumbnail sized graphs
   @param $graph_array - the array to contains graph information. for each graph in the
     array, the following two keys must exist
     $arr[0]["local_graph_id"] // graph id
     $arr[0]["title_cache"] // graph title
   @param $no_graphs_message - display this message if no graphs are found in $graph_array
   @param $extra_url_args - extra arguments to append to the url
   @param $header - html to use as a header */
function html_graph_thumbnail_area(&$graph_array, $no_graphs_message = "", $extra_url_args = "", $header = "") {
	$i = 0; $k = 0; $j = 0;

	$num_graphs = sizeof($graph_array);

	if ($num_graphs > 0) {
		if ($header != "") {
			print $header;
		}

		$start = true;
		foreach ($graph_array as $graph) {
			if (isset($graph["graph_template_name"])) {
				if (isset($prev_graph_template_name)) {
					if ($prev_graph_template_name != $graph["graph_template_name"]) {
						$print  = true;
						$prev_graph_template_name = $graph["graph_template_name"];
					}else{
						$print = false;
					}
				}else{
					$print  = true;
					$prev_graph_template_name = $graph["graph_template_name"];
				}

				if ($print) {
					if (!$start) {
						while($i % read_graph_config_option("num_columns") != 0) {
							print "\t\t\t<td align='center' width='" . ceil(100 / read_graph_config_option("num_columns")) . "%'></td>";
							$i++;
						}
						print "\t\t</tr>\t";
					}

					print "\t\t<tr class='rowSubHeader'>
						<td colspan='" . read_graph_config_option("num_columns") . "' class='textHeaderDark'>
							" . __("Graph Template:") . " " . $graph["graph_template_name"] . "
						</td>
					</tr>";
					$i = 0;
				}
			}elseif (isset($graph["data_query_name"])) {
				if (isset($prev_data_query_name)) {
					if ($prev_data_query_name != $graph["data_query_name"]) {
						$print  = true;
						$prev_data_query_name = $graph["data_query_name"];
					}else{
						$print = false;
					}
				}else{
					$print  = true;
					$prev_data_query_name = $graph["data_query_name"];
				}

				if ($print) {
					if (!$start) {
						while($i % read_graph_config_option("num_columns") != 0) {
							print "<td align='center' width='" . ceil(100 / read_graph_config_option("num_columns")) . "%'></td>";
							$i++;
						}

						print "</tr>";
					}

					print "\t\t\t<tr class='rowSubHeaderAlt'>
							<td colspan='" . read_graph_config_option("num_columns") . "' class='textHeaderLight'>" . __("Data Query:") . " " . $graph["data_query_name"] . "</td>
						</tr>";
					$i = 0;
				}

				if (!isset($prev_sort_field_value) || $prev_sort_field_value != $graph["sort_field_value"]){
					$prev_sort_field_value = $graph["sort_field_value"];
					print "<tr class='rowSubHeader'>
						<td colspan='" . read_graph_config_option("num_columns") . "' class='textHeaderDark'>
							" . $graph["sort_field_value"] . "
						</td>
					</tr>";
					$i = 0;
					$j = 0;
				}
			}

			if ($i == 0) {
				print "<tr style='background-color: #" . ($j % 2 == 0 ? "F2F2F2" : "FFFFFF") . ";'>";
				$start = false;
			}

			?>
			<td align='center' width='<?php print ceil(100 / read_graph_config_option("num_columns"));?>%'>
				<table align='center' cellpadding='0'>
					<tr>
						<td class='center'>
							<?php
							if ($graph["image_format_id"] == IMAGE_TYPE_PNG || $graph["image_format_id"] == IMAGE_TYPE_GIF) {
								?>
								<div style="min-height: <?php echo (1.6 * read_graph_config_option("default_height")) . "px"?>;"><a href='<?php print htmlspecialchars("graph.php?action=view&local_graph_id=" . $graph["local_graph_id"] . "&rra_id=all");?>'>
									<img class='graphimage' id='graph_<?php print $graph["local_graph_id"] ?>'
										src='<?php print htmlspecialchars("graph_image.php?local_graph_id=" . $graph["local_graph_id"] . "&rra_id=0&graph_height=" . read_graph_config_option("default_height") . "&graph_width=" . read_graph_config_option("default_width") . "&graph_nolegend=true&title_font_size=" . ((read_graph_config_option("custom_fonts") == "on") ? read_graph_config_option("title_size") : read_config_option("title_size")) . (($extra_url_args == "") ? "" : "&$extra_url_args"));?>'
										border='0' alt='<?php print $graph["title_cache"];?>'>
								</a></div>
								<?php
							} else if ($graph["image_format_id"] == IMAGE_TYPE_SVG) {
								?>
								<div style="min-height: <?php echo (1.6 * read_graph_config_option("default_height")) . "px"?>;"><a href='<?php print htmlspecialchars("graph.php?action=view&local_graph_id=" . $graph["local_graph_id"] . "&rra_id=all");?>'>
									<object class='graphimage' id='graph_<?php print $graph["local_graph_id"] ?>'
										type='svg+xml'
										data='<?php print htmlspecialchars("graph_image.php?local_graph_id=" . $graph["local_graph_id"] . "&rra_id=0&graph_height=" . read_graph_config_option("default_height") . "&graph_width=" . read_graph_config_option("default_width") . "&graph_nolegend=true" . (($extra_url_args == "") ? "" : "&$extra_url_args"));?>'
										border='0'>
										Can't display SVG
									</object>;
								</a></div>
								<?php
								#print "<object class='graphimage' id='graph_" . $graph["local_graph_id"] . "' type='svg+xml' data='" . htmlspecialchars("graph_image.php?action=view&local_graph_id=" . $graph["local_graph_id"] . "&rra_id=" . $rra["id"]) . "' border='0'>Can't display SVG</object>";
							}
							print (read_graph_config_option("show_graph_title") == CHECKED ? "<p style='font-size: 10;' align='center'>" . $graph["title_cache"] . "</p>" : "");
							?>
						</td>
						<td valign='top' style='align: left; padding: 3px;'>
							<a href='<?php print htmlspecialchars("graph.php?action=zoom&local_graph_id=" . $graph["local_graph_id"] . "&rra_id=0&" . $extra_url_args);?>'><img src='images/graph_zoom.gif' alt='<?php print __("Zoom Graph");?>' title='<?php print __("Zoom Graph");?>' class='img_info'></a><br>
							<a href='<?php print htmlspecialchars("graph_xport.php?local_graph_id=" . $graph["local_graph_id"] . "&rra_id=0&" . $extra_url_args);?>'><img src='images/graph_query.png' alt='<?php print __("CSV Export");?>' title='<?php print __("CSV Export");?>' class='img_info'></a><br>
							<a href='<?php print htmlspecialchars("graph.php?action=properties&local_graph_id=" . $graph["local_graph_id"] . "&rra_id=0&" . $extra_url_args);?>'><img src='images/graph_properties.gif' alt='<?php print __("Graph Source/Properties");?>' title='<?php print __("Graph Source/Properties");?>' class='img_info'></a><br>
							<a href='#page_top'><img src='images/graph_page_top.gif' alt='<?php print __("Page Top");?>' title='<?php print __("Page Top");?>' class='img_info'></a><br>
						</td>
					</tr>
				</table>
			</td>
			<?php

			$i++;
			$k++;

			if (($i % read_graph_config_option("num_columns") == 0) && ($k < $num_graphs)) {
				$i=0;
				$j++;
				print "</tr>\n";
				$start = true;
			}
		}

		if (!$start) {
			while($i % read_graph_config_option("num_columns") != 0) {
				print "<td align='center' width='" . ceil(100 / read_graph_config_option("num_columns")) . "%'></td>";
				$i++;
			}

			print "</tr>\n";
		}
	}else{
		if ($no_graphs_message != "") {
			print "<td><em>$no_graphs_message</em></td>";
		}
	}
}

/* html_header_sort - draws a header row suitable for display inside of a box element.  When
     a user selects a column header, the callback function "filename" will be called to handle
     the sort the column and display the altered results.
   @param $header_items - an array containing a list of column items to display.  The
        format is similar to the html_header, with the exception that it has three
        dimensions associated with each element (id => column_id, name => display_text, order => default_sort_direction, align => alignment)
   @param $sort_column - the value of current sort column.
   @param $sort_direction - the value the current sort direction.  The actual sort direction
        will be opposite this direction if the user selects the same named column.
   @param $last_item_colspan - the TD 'colspan' to apply to the last cell in the row 
   @param $table_id - table id
   */
function html_header_sort($header_items, $sort_column, $sort_direction, $last_item_colspan = 1, $table_id = "") {
	static $rand_id = 0;

	$table_id = ($table_id != '') ? "id=\"$table_id\"" : "";

	/* reverse the sort direction */
	if ($sort_direction == "ASC") {
		$new_sort_direction = "DESC";
		$selected_sort_class = "sort_asc";
	}else{
		$new_sort_direction = "ASC";
		$selected_sort_class = "sort_desc";
	}

	print "\t\t<table cellpadding='0' cellspacing='0' $table_id class='hover striped resizable startBoxHeader startBox3'><thead><tr class='rowSubHeader nodrag nodrop'>\n";

	$pathname = html_get_php_pathname();
	foreach($header_items as $column => $item) {
		$align = "text-align:left;";
		/* by default, you will always sort ascending, with the exception of an already sorted column */
		if ($sort_column == $column) {
			$direction    = $new_sort_direction;
			$display_text = $item["name"];
			if (isset($item["align"])) {
				$align = "text-align:" . $item["align"] . ";";
			}
			$sort_class   = $selected_sort_class;
		}else{
			$display_text = $item["name"];
			if (isset($item["order"])) {
				$direction = $item["order"];
			}else{
				$direction = "ASC";
			}
			if (isset($item["align"])) {
				$align = "text-align:" . $item["align"] . ";";
			}
			$sort_class   = "";
		}

		if (($column == "") || (isset($item["sort"]) && $item["sort"] == false)) {
			$width = html_get_column_width($pathname, "hhs_$rand_id");

			print "\t\t\t<th style='display:block;$align' id='hhs_$rand_id'" . ((($rand_id+1) == count($header_items)) ? "colspan='$last_item_colspan' class='textSubHeaderDark nodrag nodrop lastColumn'" : "class='textSubHeaderDark nodrag nodrop'") . ">" . $display_text . "</th>\n";

			$rand_id++;
		}else{
			$width = html_get_column_width($pathname, $column);

			print "\t\t\t<th nowrap style='width:$width;white-space:nowrap;$align' id='" . $column . "'" . ((($rand_id+1) == count($header_items)) ? "colspan='$last_item_colspan' class='textSubHeaderDark nodrag nodrop lastColumn'" : "class='textSubHeaderDark nodrag nodrop'") . ">";
			print "\n\t\t\t\t<a class='$sort_class' style='display:block;' href='" . htmlspecialchars(basename($_SERVER["PHP_SELF"]) . "?sort_column=" . $column . "&sort_direction=" . $direction) . "'>" . $display_text . "</a>";
			print "\n\t\t\t</th>\n";
		}
	}

	print "\t\t</tr></thead><tbody>\n";
}

/* html_header_sort_checkbox - draws a header row with a 'select all' checkbox in the last cell
     suitable for display inside of a box element.  When a user selects a column header,
     the collback function "filename" will be called to handle the sort the column and display
     the altered results.
   @param $header_items - an array containing a list of column items to display.  The
        format is similar to the html_header, with the exception that it has three
        dimensions associated with each element (id => column_id, name => display_text, order => default sort order, align => text aligment)
   @param $sort_column - the value of current sort column.
   @param $sort_direction - the value the current sort direction.  The actual sort direction
        will be opposite this direction if the user selects the same named column.
   @param $form_action - the url to post the 'select all' form to 
   @param $table_id - table_id
*/
function html_header_sort_checkbox($header_items, $sort_column, $sort_direction, $form_action = "", $table_id = "") {
	static $rand_id = 0;

	/* reverse the sort direction */
	if ($sort_direction == "ASC") {
		$new_sort_direction = "DESC";
		$selected_sort_class = "sort_asc";
	}else{
		$new_sort_direction = "ASC";
		$selected_sort_class = "sort_desc";
	}

	$table_id = ($table_id != '') ? "id=\"$table_id\"" : "";

	/* default to the 'current' file */
	if ($form_action == "") { $form_action = basename($_SERVER["PHP_SELF"]); }

	print "<form name='chk' method='post' action='$form_action'>\n";	# properly place form outside table
	print "\t<table $table_id class='hover striped resizable startBoxHeader startBox3'>\n";
	print "\t\t<thead><tr class='rowSubHeader nodrag nodrop'>\n";

	$pathname = html_get_php_pathname();
	if (sizeof($header_items)) {

	foreach($header_items as $column => $item) {
		/* by default, you will always sort ascending, with the exception of an already sorted column */
		$align = "text-align:left;";
		if ($sort_column == $column) {
			$direction    = $new_sort_direction;
			$display_text = $item["name"];
			if (isset($item["align"])) {
				$align = "text-align:" . $item["align"] . ";";
			}
			$sort_class   = $selected_sort_class;
		}else{
			$display_text = $item["name"];
			if (isset($item["order"])) {
				$direction    = $item["order"];
			}
			if (isset($item["align"])) {
				$align = "text-align:" . $item["align"] . ";";
			}
			$sort_class   = "";
		}


		if (($column == "") || (isset($item["sort"]) && $item["sort"] == false)) {
			$width = html_get_column_width($pathname, "hhscrand_$rand_id");

			print "\t\t\t<th id='hhsc_$rand_id' class='textSubHeaderDark wp" . $width . " nodrag nodrop' style='$align'><a style='display:block;' href='#'>" . $display_text . "</a></th>\n";

			$rand_id++;
		}else{
			$width = html_get_column_width($pathname, $column);

			print "\t\t\t<th id='" . $column . "' class='textSubHeaderDark wp" . $width . " nodrag nodrop' style='$align'>";
			print "\n\t\t\t\t<a class='$sort_class' style='display:block;' href='" . htmlspecialchars(basename($_SERVER["PHP_SELF"]) . "?sort_column=" . $column . "&sort_direction=" . $direction) . "'>" . $display_text . "</a>";
			print "\n\t\t\t</th>\n";
		}
	}
	}

	print "\t\t\t<th id='checkbox' class='textSubHeaderDark nw14 nodrag nodrop lastColumn'><input type='checkbox' style='margin: 0px;' name='all' title='Select All' onClick='selectAll(\"chk_\",this.checked)'></th>\n";
	print "\t\t</tr></thead><tbody>\n";
}

/* html_header - draws a header row suitable for display inside of a box element
   @param $header_items - an array containing a list of items to be included in the header
   @param $last_item_colspan - the TD 'colspan' to apply to the last cell in the row
   @param $resizable - allow for the table to be resized via javascript
   @param $table_id - table_id
   @param $tclass - optional class extension for table
   @param $trclass - optional class extension for table row
   @param $thclass - optional class extension for table header cell
 */
function html_header($header_items, $last_item_colspan = 1, $resizable = false, $table_id = '', $tclass = '', $trclass = '', $thclass = '') {
	static $rand_id = 0;

	$table_id = ($table_id != '') ? "id=\"$table_id\"" : "";

	if ($resizable) {
		$pathname = html_get_php_pathname();

		print "\t\t<table cellpadding='0' cellspacing='0' $table_id class='hover striped resizable startBoxHeader startBox3 $tclass'><thead><tr class='rowSubHeader nodrag nodrop $trclass'>\n";
	}else{
		print "\t\t<table cellpadding='0' cellspacing='0' $table_id class='hover striped startBoxHeader startBox3 $tclass'><thead><tr class='rowSubHeader nodrag nodrop $trclass'>\n";
	}

	$i = 0;
	$align = "text-align:left;";
	foreach($header_items as $item) {
		if (isset($item["align"])) {
			$align = "text-align:" . $item["align"] . ";";
		}else{
			$align = "";
		}

		if ($resizable) {
			$width = html_get_column_width($pathname, "hh_$rand_id");

			print "\t\t\t<th id='hh_$rand_id' style='width: $width;$align' " . ((($i+1) == count($header_items)) ? "colspan='$last_item_colspan' class='textSubHeaderDark $thclass lastColumn'" : "class='textSubHeaderDark $thclass'") . ">" . $item["name"] . "</th>\n";
		}else{
			print "\t\t\t<th id='hh_$rand_id' style='$align' " . ((($i+1) == count($header_items)) ? "colspan='$last_item_colspan' class='textSubHeaderDark $thclass lastColumn'" : "class='textSubHeaderDark $thclass'") . ">" . $item["name"] . "</th>\n";
		}
		$rand_id++;
		$i++;
	}

	print "\t\t</tr></thead><tbody>\n";
}

/* html_header_checkbox - draws a header row with a 'select all' checkbox in the last cell
     suitable for display inside of a box element
   @param $header_items - an array containing a list of items to be included in the header
   @param $form_action - the url to post the 'select all' form to
   @param $resizable - allow for the table to be resized via javascript
   @param $tclass - optional class extension for table
   @param $trclass - optional class extension for table row
   @param $thclass - optional class extension for table header cell
 */
function html_header_checkbox($header_items, $form_action = "", $resizable = false, $table_id = '', $tclass = '', $trclass= '', $thclass = '') {
	static $rand_id = 0;

	$table_id = ($table_id != '') ? "id=\"$table_id\"" : "";

	/* default to the 'current' file */
	if ($form_action == "") { $form_action = basename($_SERVER["PHP_SELF"]); }
           
	print "<form name='chk' method='post' action='$form_action'>\n";	# properly place form outside table
	if ($resizable) {
		$pathname = html_get_php_pathname();
		print "\t\t<table cellpadding='0' cellspacing='1' $table_id class='hover striped resizable startBox0 $tclass'><thead><tr class='rowSubHeader $trclass'>\n";
	}else{
		print "\t\t<table cellpadding='0' cellspacing='1' class='hover striped startBox0 $tclass'><thead><tr class='rowSubHeader $trclass'>\n";
	}

	$i = 0;
	foreach($header_items as $item) {
		if (isset($item["align"])) {
			$align = "text-align:" . $item["align"] . ";";
		}else{
			$align = "";
		}

		if ($resizable) {
			$width = html_get_column_width($pathname, "hhc_$rand_id");
			print "\t\t\t<th id='hhc_$rand_id' style='width: $width;$align' class='textSubHeaderDark $thclass'>" . $item["name"] . "</th>\n";
		}else{
			print "\t\t\t<th id='hhc_$rand_id' style='$align' class='textSubHeaderDark $thclass'>" . $item["name"] . "</th>\n";
		}
		$rand_id++;
	}

	print "\t\t\t<th id='checkbox' class='textSubHeaderDark nw14 lastColumn'><input type='checkbox' style='margin: 0px;' name='all' title='Select All' onClick='selectAll(\"chk_\",this.checked)'></th>\n";
	print "\t\t</tr></thead><tbody>\n";
}

class html_table {
	public function html_table() {
		$this->resizable      = true;
		$this->checkbox       = false;
		$this->sortable       = true;
		$this->refresh        = true;
		$this->rows           = array();
		$this->table_format   = array();
		$this->total_rows     = 0;
		$this->rows_per_page  = -1;
		$this->key_field      = "id";
		$this->href           = "";
		$this->actions        = array();
		$this->page_vars      = array();
		$this->filter_func    = "";
		$this->filter_html    = "";
		$this->session_prefix = "";
		$this->table_id       = "";
	}

	function process_page_variables() {
		if ((sizeof($this->page_variables)) && (strlen($this->session_prefix))) {
			if (isset($_REQUEST["clear"])) {
				$clear = true;
			}else{
				$clear = false;
			}

			html_verify_request_variables($this->page_variables, $this->session_prefix, $clear);
		}else{
			echo "ERROR: You must initialize both the page_variables and session_prefix variables\n";
			return false;
		}

		return true;
	}

	function draw_table() {
		/* process page variables first */
		if (!$this->process_page_variables()) return;

		/* draw the filter */
		if ($this->refresh) {
			if (function_exists($this->filter_func)) {
				call_user_func($this->filter_func);
			}else if (strlen($this->filter_html)) {
				echo $this->filter_html;
			}
		}

		/* generate page list navigation */
		if ($this->checkbox) {
			$columns = sizeof($this->table_format)+1;
		}else{
			$columns = sizeof($this->table_format);
		}

		html_start_box("", "100", "0", "center", "");

		/* calculate the navagation bar */
		$nav = html_create_nav(html_get_page_variable("page"), MAX_DISPLAY_PAGES, $this->rows_per_page, $this->total_rows, $columns, $this->href . (strlen(html_get_page_variable("filter")) ? "?filter=" . html_get_page_variable("filter"):""));

		/* display the navigation bar */
		print $nav;

		html_end_box(false);

		/* draw the header */
		if ($this->checkbox) {
			if ($this->sortable) {
				html_header_sort_checkbox($this->table_format, html_get_page_variable("sort_column"), html_get_page_variable("sort_direction"), "", $this->table_id);
			}else{
				html_header_checkbox($this->table_format, "", false, $this->table_id);
			}
		}elseif ($this->sortable) {
			/* html_header_sort does not define a form but we need one in case of a filtered table */
			print "<form name='chk' method='post' action='" . basename($_SERVER["PHP_SELF"]) . "'>\n";	# properly place form outside table
			html_header_sort($this->table_format, html_get_page_variable("sort_column"), html_get_page_variable("sort_direction"), 1, $this->table_id);
		}else{
			/* html_header does not define a form but we need one in case of a filtered table */
			print "<form name='chk' method='post' action='" . basename($_SERVER["PHP_SELF"]) . "'>\n";	# properly place form outside table
			html_header($this->table_format, 1, false, $this->table_id);
		}

		/* draw the rows */
		if (sizeof($this->rows)) {
			foreach ($this->rows as $row) {
				$row = plugin_hook_function(str_replace(".php", "", $this->href) . '_table', $row);

				/* check to see if this row requires special treatment via a row-level callback function */
				if (!isset($this->row_function) || $this->row_function == '') {
					$row_classes = "";		# don't pass any additional class
				}elseif (!isset($this->row_params)) {
					/* call a row-level function that returns additional row level classes */
					$row_classes = call_user_func($this->row_function, $row[$this->key_field]);
				}else{
					/* call a row-level function like above, but provide parameters to it */
					$passarray = array();
					if (sizeof($this->row_params)) {
					foreach($this->row_params as $param) {
						if (isset($row[$param])) {
							$passarray[] = $row[$param];
						}
					}
					}
					$row_classes = call_user_func_array($this->row_function, $passarray);
				}
				form_alternate_row_color('line' . $row[$this->key_field], true, $row_classes);

				$checkbox_title = __("Check to select Row");
				foreach($this->table_format as $column => $data) {
					$text  = "";
					$class = "";
					$width = "";

					/* remove any '.' from the column name, they are not permitted */
					if (substr_count($column, ".")) {
						$nc = explode(".", $column);
						$column = $nc[sizeof($nc)-1];
					}

					/* check to see if this is a link column */
					if (isset($data["link"])) {
						if (!strlen($checkbox_title)) {
							$checkbox_title = $row[$column];
						}
						if ( isset( $data["href"] ) ) {
							$text = "<a class='linkEditMain' href='" . htmlspecialchars($data["href"] . "&id=" . $row[$this->key_field]) . "'>";
						}else{
							$text = "<a class='linkEditMain' href='" . htmlspecialchars($this->href . "?action=edit&id=" . $row[$this->key_field]) . "'>";
						}
					}

					/* check to see if this is a filterable column */
					if (!isset($data["function"])) {
						$value = $row[$column];
					}elseif (!isset($data["params"])) {
						$value = call_user_func($data["function"], $row[$this->key_field]);
					}else{
						$passarray = array();
						if (sizeof($data["params"])) {
						foreach($data["params"] as $param) {
							if (isset($row[$param])) {
								$passarray[] = $row[$param];
							}
						}
						}

						$value = call_user_func_array($data["function"], $passarray);
					}

					if (isset($data["format"])) {
						$format_array = explode(",", $data["format"]);
						switch($format_array[0]) {
							case "round":
								$value = round($value, $format_array[1]);
								break;
							default:
								break;
						}
					}

					if (isset($data["filter"]) && strlen(html_get_page_variable("filter"))) {
						$text .= preg_replace("/(" . preg_quote(html_get_page_variable("filter")) . ")/i", "<span class=\"filter\">\\1</span>", $value);
					}else{
						$text .= $value;
					}

					if (!strlen($text) && isset($data["noneval"])) {
						$text = $data["noneval"];
					}

					/* does the column have alignment */
					if (isset($data["align"])) {
						$align = $data["align"];
					}else{
						$align = "";
					}

					/* check to see if this is a link column */
					if (isset($data["link"])) {
						$text .= "</a>";
					}

					form_selectable_cell($text, $row[$this->key_field], $width, $class, $align);
				}

				if ($this->checkbox) {
					form_checkbox_cell($checkbox_title, $row[$this->key_field]);
				}
				form_end_row();
			}
			form_end_table();

			print $nav;
		}else{
			print "<tr><td colspan='$columns'><em>" . __("No Rows Found") . "</em></td></tr>\n";
		}

		print "</table>\n";	# end table of html_header_sort_checkbox

		/* draw the dropdown containing a list of available actions for this form */
		if (is_array($this->actions) && sizeof($this->actions)) {
			draw_actions_dropdown($this->actions);
		}

		print "</form>\n";	# end form of html_header*
	}
}

/** html_draw_table - draws a full html table based upon specification
   @param $table_format - an array that contains all of the columns to draw.  Attributes of the array include:
     id => The column name,
     name => The display name for the column
     align => The text alignment for the column
     order => The default sort order of the column
     filter => Is the column filterable
     link => The link status of the column 'true'/'false'
     href => (Optional) The link href for the column.  Otherwise contructed from input options
   @param $rows - The database rows sent to the function
   @param $total_rows - The total rows that are to be displayed, if -1 then don't display page X of Y
   @param $rows_per_page - The number of rows per page
   @param $key_field - The primary key field for the row's
   @param $href - The base href for linkable columns
   @param $actions - The list of dropdown actions to present to the user
   @param $filter - The current filter for this table
   @param $resizeable - Is the table resizable
   @param $checkbox - Either true or false if this is to be a checkbox table
   @param $sortable - Is the table sortable
   @param $sort_columns - The current sort column array
   @param $sort_directions - The current sort order array */
function html_draw_table(&$table_format, &$rows, $total_rows, $rows_per_page, $page, $key_field = "id", $href = "",
	$actions = "", $filter = "", $resizable = true, $checkbox = false, $sortable = true, $sort_columns = "", $sort_directions = "", $table_id = "") {

	/* generate page list navigation */
	if ($checkbox) {
		$columns = sizeof($table_format)+1;
	}else{
		$columns = sizeof($table_format);
	}

	html_start_box("", "100", "0", "center", "");

	/* calculate the navagation bar */
	$nav = html_create_nav($page, MAX_DISPLAY_PAGES, $rows_per_page, $total_rows, $columns, $href . "?filter=" . $filter);

	/* display the navigation bar */
	print $nav;

	html_end_box(false);

	/* draw the header */
	if ($checkbox) {
		if ($sortable) {
			html_header_sort_checkbox($table_format, $sort_columns, $sort_directions, "", $table_id);
		}else{
			html_header_checkbox($table_format, "", false, $table_id);
		}
	}else{
		if ($sortable) {
			html_header_sort($table_format, $sort_columns, $sort_directions, 1, $table_id);
		}else{
			html_header($table_format, 1, false, $table_id);
		}
	}

	/* draw the rows */
	if (sizeof($rows)) {
		foreach ($rows as $row) {
			$row = plugin_hook_function(str_replace(".php", "", $href) . '_table', $row);

			form_alternate_row_color('line' . $row[$key_field], true);

			$checkbox_title = "";
			foreach($table_format as $column => $data) {
				$text  = "";
				$class = "";
				$width = "";

				/* remove any '.' from the column name, they are not permitted */
				if (substr_count($column, ".")) {
					$nc = explode(".", $column);
					$column = $nc[sizeof($nc)-1];
				}

				/* check to see if this is a link column */
				if (isset($data["link"])) {
					if (!strlen($checkbox_title)) {
						$checkbox_title = $row[$column];
					}
					if ( isset( $data["href"] ) ) {
						$text = "<a class='linkEditMain' href='" . htmlspecialchars($data["href"] . "&id=" . $row[$key_field]) . "'>";
					}else{
						$text = "<a class='linkEditMain' href='" . htmlspecialchars($href . "?action=edit&id=" . $row[$key_field]) . "'>";
					}
				}

				/* check to see if this is a filterable column */
				if (!isset($data["function"])) {
					$value = $row[$column];
				}elseif (!isset($data["params"])) {
					$value = call_user_func($data["function"], $row[$key_field]);
				}else{
					$passarray = array();
					if (sizeof($data["params"])) {
					foreach($data["params"] as $param) {
						if (isset($row[$param])) {
							$passarray[] = $row[$param];
						} else {
							/* an argument is expected but not provided by the row that was passed (uninitialized data)
							 * so let's provide an empty array */
							 $passarray[] = '';
						}
					}
					}
					$value = call_user_func_array($data["function"], $passarray);
				}

				if (isset($data["format"])) {
					$format_array = explode(",", $data["format"]);
					switch($format_array[0]) {
						case "round":
							$value = round($value, $format_array[1]);
							break;
						default:
							break;
					}
				}

				if (isset($data["filter"]) && strlen($filter)) {
					$text .= preg_replace("/(" . preg_quote($filter) . ")/i", "<span class=\"filter\">\\1</span>", $value);
				}else{
					$text .= $value;
				}

				if (!strlen($text) && isset($data["noneval"])) {
					$text = $data["noneval"];
				}

				/* does the column have alignment */
				if (isset($data["align"])) {
					$align = $data["align"];
				}else{
					$align = "";
				}

				/* check to see if this is a link column */
				if (isset($data["link"])) {
					$text .= "</a>";
				}

				form_selectable_cell($text, $row[$key_field], $width, $class, $align);
			}

			if ($checkbox) {
				form_checkbox_cell($checkbox_title, $row[$key_field]);
			}
			form_end_row();
		}
		form_end_table();

		print $nav;
	}else{
		print "<tr><td><em>" . __("No Rows Found") . "</em></td></tr>\n";
	}

	print "</table>\n";	# end table of html_header_sort_checkbox

	/* draw the dropdown containing a list of available actions for this form */
	if (is_array($actions)) {
		draw_actions_dropdown($actions);
	}
	print "</form>\n";	# end form of html_header_sort_checkbox
}

/* html_create_list - draws the items for an html dropdown given an array of data
   @param $form_data - an array containing data for this dropdown. it can be formatted
     in one of two ways:
     $array["id"] = "value";
     -- or --
     $array[0]["id"] = 43;
     $array[0]["name"] = "Red";
   @param $column_display - used to indentify the key to be used for display data. this
     is only applicable if the array is formatted using the second method above
   @param $column_id - used to indentify the key to be used for id data. this
     is only applicable if the array is formatted using the second method above
   @param $form_previous_value - the current value of this form element */
function html_create_list($form_data, $column_display, $column_id, $form_previous_value) {
	if (empty($column_display)) {
		foreach (array_keys($form_data) as $id) {
			print "\t\t\t\t\t\t\t<option value='" . $id . "'";

			if ($form_previous_value == $id) {
			print " selected";
			}

			print ">" . title_trim(null_out_substitutions($form_data[$id]), 75) . "</option>\n";
		}
	}else{
		if (sizeof($form_data) > 0) {
			foreach ($form_data as $row) {
				print "\t\t\t\t\t\t\t<option value='$row[$column_id]'";

				if ($form_previous_value == $row[$column_id]) {
					print " selected";
				}

				if (isset($row["device_id"])) {
					print ">" . title_trim($row[$column_display], 75) . "</option>\n";
				}else{
					print ">" . title_trim(null_out_substitutions($row[$column_display]), 75) . "</option>\n";
				}
			}
		}
	}
}

/* html_split_string - takes a string and breaks it into a number of <br> separated segments
   @param $string - string to be modified and returned
   @param $length - the maximal string length to split to
   @param $forgiveness - the maximum number of characters to walk back from to determine
         the correct break location.
   @returns $new_string - the modified string to be returned. */
function html_split_string($string, $length = 70, $forgiveness = 10) {
	$new_string = "";
	$j    = 0;
	$done = false;

	while (!$done) {
		if (strlen($string) > $length) {
			for($i = 0; $i < $forgiveness; $i++) {
				if (substr($string, $length-$i, 1) == " ") {
					$new_string .= substr($string, 0, $length-$i) . "<br>";

					break;
				}
			}

			$string = substr($string, $length-$i);
		}else{
			$new_string .= $string;
			$done        = true;
		}

		$j++;
		if ($j > 4) break;
	}

	return $new_string;
}

/* html_create_nav - creates page select navigation html
 * 					creates a table inside of a row
   @param $current_page - the current page displayed
   @param $max_pages - the maxium number of pages to show on a page
   @param $rows_per_page - the number of rows to display per page
   @param $total_rows - the total number of rows that can be displayed
   @param $columns - the total number of columns on this page
   @param $base_url - the url to navigate to
   @param $page_var - the request variable to look for the page number
   @param $url_page_select - the page list to display */
function html_create_nav($current_page, $max_pages, $rows_per_page, $total_rows, $columns, $base_url, $page_var = "page") {
	if (substr_count($base_url, "?")) {
		$base_url .= "&";
	}else{
		$base_url .= "?";
	}

	if ($total_rows > 0) {
		$url_page_select = get_page_list($current_page, $max_pages, $rows_per_page, $total_rows, $base_url, $page_var);

		$nav = "
			<tr class='rowHeader'>
				<td colspan='$columns'>
					<table cellpadding='0' cellspacing='1' class='startBox0'>
						<tr>
							<td class='textHeaderDark wp15 left'>";
								if ($current_page > 1) {
									$nav .= "<a class='linkOverDark' href='" . htmlspecialchars($base_url . $page_var . "=" . ($current_page-1)) . "'>";
									$nav .= "&lt;&lt;&nbsp;" . __("Previous");
									$nav .= "</a>\n";
								}
								$nav .= "
							</td>\n
							<td class='textHeaderDark wp70 center'>
								" . __("Showing Rows") . " " . (($rows_per_page*($current_page-1))+1) . " " . __("to") . " " . ((($total_rows < $rows_per_page) || ($total_rows < ($rows_per_page*$current_page))) ? $total_rows : ($rows_per_page*$current_page)) . " " . __("of") . " $total_rows " . (strlen($url_page_select) ? "[$url_page_select]":"") . "
							</td>\n
							<td class='textHeaderDark wp15 right'>";
								if (($current_page * $rows_per_page) < $total_rows) {
									$nav .= "<a class='linkOverDark' href='" . htmlspecialchars($base_url . $page_var . "=" . ($current_page+1)) . "'>";
									$nav .= __("Next") . " &gt;&gt;";
									$nav .= "</a>\n";
								}
								$nav .= "
							</td>\n
						</tr>
					</table>
				</td>
			</tr>\n";
	}else{
		$nav = "
			<tr class='rowHeader'>
				<td colspan='$columns'>
					<table cellpadding='0' cellspacing='1' class='startBox0'>
						<tr>
							<td class='textHeaderDark wp15 center'>No Rows Found</td>
						</tr>
					</table>
				</td>
			</tr>\n";
	}

	return $nav;
}

/* draw_graph_items_list - draws a nicely formatted list of graph items for display
     on an edit form
   @param $item_list - an array representing the list of graph items. this array should
     come directly from the output of db_fetch_assoc()
   @param $filename - the filename to use when referencing any external url
   @param $url_data - any extra GET url information to pass on when referencing any
     external url
   @param $disable_controls - whether to hide all edit/delete functionality on this form */
function draw_graph_items_list($item_list, $filename, $url_data, $disable_controls) {
	require(CACTI_BASE_PATH . "/include/presets/preset_rra_arrays.php");
	require(CACTI_BASE_PATH . "/include/graph/graph_arrays.php");
	include(CACTI_BASE_PATH . "/include/global_arrays.php");

	$header_items = array(
		array("name" => __("Graph Item"), "align" => "left"),
		array("name" => __("Data Source"), "align" => "left"),
		array("name" => __("Graph Item Type"), "align" => "left"),
		array("name" => __("CF Type"), "align" => "left"),
		array("name" => __("CDEF"), "align" => "left"),
		array("name" => __("GPRINT Type"), "align" => "left"),
		array("name" => __("Item Color"), "align" => "center"),
		array("name" => __("Action"), "align" => "center")
	);
	$last_item_colspan = 3;

	print "<tr><td>";
	html_header($header_items, $last_item_colspan, false, 'graph_item');

	$group_counter = 0; $_graph_type_name = ""; $i = 0;

	$i = 0;
	if (sizeof($item_list) > 0) {
	foreach ($item_list as $item) {
		/* graph grouping display logic */
		$this_row_style = ""; $hard_return = "";

		if ($item["graph_type_id"] != GRAPH_ITEM_TYPE_GPRINT &&
			$item["graph_type_id"] != GRAPH_ITEM_TYPE_GPRINT_AVERAGE &&
			$item["graph_type_id"] != GRAPH_ITEM_TYPE_GPRINT_LAST &&
			$item["graph_type_id"] != GRAPH_ITEM_TYPE_GPRINT_MAX &&
			$item["graph_type_id"] != GRAPH_ITEM_TYPE_GPRINT_MIN) {
			$this_row_style = "font-weight: bold;";

			$group_counter++;
		}

		$_graph_type_name = $graph_item_types{$item["graph_type_id"]};

		form_alternate_row_color($item["id"], true);

		print "<td>";
		if ($disable_controls == false) { print "<a href='" . htmlspecialchars("$filename?action=item_edit&id=" . $item["id"] . "&$url_data") ."'>"; }
		print "Item # " . ($i+1);
		if ($disable_controls == false) { print "</a>"; }
		print "</td>\n";

		if (empty($item["data_source_name"])) { $item["data_source_name"] = __("No Task"); }

		switch ($item["graph_type_id"]) {
			case GRAPH_ITEM_TYPE_AREA:
			case GRAPH_ITEM_TYPE_AREASTACK:
			case GRAPH_ITEM_TYPE_GPRINT:
			case GRAPH_ITEM_TYPE_GPRINT_AVERAGE:
			case GRAPH_ITEM_TYPE_GPRINT_LAST:
			case GRAPH_ITEM_TYPE_GPRINT_MAX:
			case GRAPH_ITEM_TYPE_GPRINT_MIN:
			case GRAPH_ITEM_TYPE_LINE1:
			case GRAPH_ITEM_TYPE_LINE2:
			case GRAPH_ITEM_TYPE_LINE3:
			case GRAPH_ITEM_TYPE_LINESTACK:
			case GRAPH_ITEM_TYPE_TICK:
				$matrix_title = "(" . $item["data_source_name"] . "): " . $item["text_format"];
				break;
			case GRAPH_ITEM_TYPE_HRULE:
				$matrix_title = "HRULE: " . $item["value"];
				break;
			case GRAPH_ITEM_TYPE_VRULE:
				$matrix_title = "VRULE: " . $item["value"];
				break;
			case GRAPH_ITEM_TYPE_COMMENT:
				$matrix_title = "COMMENT: " . $item["text_format"];
				break;
			case GRAPH_ITEM_TYPE_TEXTALIGN:
				$matrix_title = "TEXTALIGN: " . $rrd_textalign{$item["textalign"]};
				break;
		}

		if ($item["hard_return"] == CHECKED) {
			$hard_return = "<font color=\"#FF0000\">&lt;HR&gt;</font>";
		}

		print "<td style='$this_row_style'>" . htmlspecialchars($matrix_title) . $hard_return . "</td>\n";
		print "<td style='$this_row_style'>" . $graph_item_types{$item["graph_type_id"]} . "</td>\n";
		print "<td style='$this_row_style'>" . ((!empty($item["consolidation_function_id"])) ? $consolidation_functions{$item["consolidation_function_id"]} : __("None")) . "</td>\n";
		print "<td style='$this_row_style'>" . ((strlen($item["cdef_name"]) > 0) ? substr($item["cdef_name"],0,30) : __("None")) . "</td>\n";
		print "<td style='$this_row_style'>" . ((strlen($item["gprint_name"]) > 0) ? substr($item["gprint_name"],0,30) : __("None")) . "</td>\n";
		print "<td style='$this_row_style'"  . ((!empty($item["hex"])) ? " bgcolor='#" . $item["hex"] . "'" : "") . ">&nbsp;</td>\n";

		if ($disable_controls == false) {
			print "<td align='center'><a href='" . htmlspecialchars("$filename?action=item_remove&id=" . $item["id"] . "&$url_data") . "'><img id='buttonSmall" . $item["id"] . "' class='buttonSmall' src='images/delete_icon.gif' title='Delete this Item' alt='Delete' align='middle'></a></td>\n";
		}

		print "</tr>";

		$i++;
	}
	}else{
		print "<tr class='topBoxAlt'><td colspan='" . (sizeof($header_items)+$last_item_colspan-1) . "'><em>" . __("No Items") . "</em></td></tr>";
	}

	print "</table></td></tr>";
}

function draw_data_template_items_list($item_list, $filename, $url_data, $disable_controls) {
	require(CACTI_BASE_PATH . "/include/data_source/data_source_arrays.php");

	$header_items = array(
		array("name" => __("Item")),
		array("name" => __("Data Source Name")),
		array("name" => __("Data Source Item Type")),
		array("name" => __("Minimum"), "align" => "right"),
		array("name" => __("Maximum"), "align" => "right"),
		array("name" => __("Heartbeat"), "align" => "right"),
		array("name" => __("Action"), "align" => "center")
	);
	$last_item_colspan = 3;

	print "<tr><td>";
	html_header($header_items, $last_item_colspan, false, 'data_source_item','wp100');

	$i = 0;

	if (sizeof($item_list) > 0) {
		$i = 0;
		foreach ($item_list as $item) {
			$href_item = "";
			if ($disable_controls == false) { $href_item .= "<a style='white-space:nowrap;' class='linkEditMain' href='" . htmlspecialchars("$filename?action=item_edit&id=" . $item["id"] . "&$url_data") ."'>"; }
			$href_item .= "Item # " . ($i+1);
			if ($disable_controls == false) { $href_item .= "</a>"; }

			form_alternate_row_color('line' . $item["id"], true);
			form_selectable_cell($href_item, $item["id"]);
			form_selectable_cell((isset($item["data_source_name"]) ? $item["data_source_name"] : ''), $item["id"]);
			form_selectable_cell((isset($data_source_types[$item["data_source_type_id"]]) ? $data_source_types[$item["data_source_type_id"]] : __("None")), $item["id"]);
			form_selectable_cell((isset($item["rrd_minimum"]) ? $item["rrd_minimum"] : 0), $item["id"], "", "", "right");
			form_selectable_cell((isset($item["rrd_maximum"]) ? $item["rrd_maximum"] : 0), $item["id"], "", "", "right");
			form_selectable_cell((isset($item["rrd_heartbeat"]) ? $item["rrd_heartbeat"] : 0), $item["id"], "", "", "right");
			if ($disable_controls == false) {
				print "<td align='center'><a href='" . htmlspecialchars("$filename?action=item_remove&id=" . $item["id"] . "&$url_data") . "'>" .
						"<img id='buttonSmall" . $item["id"] . "' class='buttonSmall' src='images/delete_icon.gif' " .
						"title='" . __("Delete this Item") . "' alt='" . __("Delete") . "' align='middle'></a></td>\n";
			}
			$i++;
			form_end_row();
		}
	}else{
		print "<tr class='topBoxAlt'><td colspan='" . (sizeof($header_items)+$last_item_colspan-1) . "'><em>" . __("No Items") . "</em></td></tr>";
	}
	print "</table></td></tr>";		/* end of html_header */
}


function draw_header_tab($name, $title, $location, $image = "") {
	if ($image == "") {
		return "<li id=\"tab_" . html_escape($name) . "\"" . (html_selected_tab($name, $location) ? " class=\"selected\"" : " class=\"notselected\"") . "><a href=\"javascript:navigation_select('" . html_escape($name) . "','" . htmlspecialchars($location) . "')\" title=\"" . html_escape($title) . "\">" . html_escape($title) . "</a></li>\n";
	}else{
		return "<li id=\"tab_" . html_escape($name) . "\"" . (html_selected_tab($name, $location) ? " class=\"selected\"" : " class=\"notselected\"") . "><a href=\"javascript:navigation_select('" . html_escape($name) . "','" . htmlspecialchars($location) . "')\" title=\"" . html_escape($title) . "\"><img src='$image' alt='$title' align='middle'></a></li>\n";
	}
}

function html_selected_tab($name, $location) {
	if (get_request_var_request('toptab') == $name) {
		return true;
	}elseif ($name == 'graphs' && preg_match('/(graph_settings|tree|preview|list)/', get_request_var_request('toptab'))) {
		return true;
	}elseif (get_request_var_request('toptab') == 'graphs' && get_request_var_request('action') == $name) {
		return true;
	}elseif (!plugin_hook_function('top_tab_selected', array($name, $location))) {
		return true;
	}

	return false;
}

function html_escape($html) {
	return htmlentities($html, ENT_QUOTES, 'UTF-8');
}

/* html_get_php_pathname() - extracts the name of the php file without the
   extention.  This value is used to store and retriev cookie values */
function html_get_php_pathname() {
	$path = $_SERVER['PHP_SELF'];

	while (($location = strpos($path, '/')) !== FALSE) {
		$path = substr($path, $location + 1);
	}

	return str_replace('.php', '', $path);
}

function html_get_column_width($name, $element) {
	$width = html_read_cookie_element($name, $element);

	if (!strlen($width)) {
		return 'auto';
	}else{
		return $width . 'px';
	}
}

/* html_read_cookie_element - extracts an element from the specified cookie array
   @param $name - the cookie name that contains the cookie elements
   @param $element - the name of the cookie element to be searched for. */
function html_read_cookie_element($name, $element) {
	if (isset($_COOKIE[$name])) {
		$parts = explode('!', $_COOKIE[$name]);

		foreach ($parts as $part) {
			$name_value = explode('@@', $part);

			if ($name_value[0] == $element) {
				if ($name_value[1] == 'NaN') {
					return '';
				}else{
					return $name_value[1];
				}
			}
		}
	}

	return '';
}

/* draw_menu - draws the cacti menu for display in the console */
function draw_menu($user_menu = '') {
	global $user_auth_realms, $user_auth_realm_filenames, $menu;

	if (strlen($user_menu == 0)) {
		$user_menu = $menu;
	}

	/* list all realms that this user has access to */
	if (read_config_option('auth_method') != AUTH_METHOD_NONE) {
		$user_realms = db_fetch_assoc('select realm_id from user_auth_realm where user_id=' . $_SESSION['sess_user_id']);
		$user_realms = array_rekey($user_realms, 'realm_id', 'realm_id');
	}else{
		$user_realms = $user_auth_realms;
	}

	$first_ul = true;

	/* loop through each header */
	while (list($header_id, $header_array) = each($user_menu)) {
		/* pass 1: see if we are allowed to view any children */
		$show_header_items = false;
		while (list($item_url, $item_title) = each($header_array['items'])) {
			$current_realm_id = (isset($user_auth_realm_filenames{basename($item_url)}) ? $user_auth_realm_filenames{basename($item_url)} : 0);

			if ((isset($user_realms[$current_realm_id])) || (!isset($user_auth_realm_filenames{basename($item_url)}))) {
				$show_header_items = true;
			}
		}

		reset($header_array['items']);

		if ($show_header_items == true) {
			if (!$first_ul) {
				print '</ul></div>';
			}else{
				$first_ul = false;
			}

			$id = clean_up_name(strtolower($header_id));
			print "<div id='$id" . "_div" . "' class='menuMain'>" . $header_array['description'] . "</div><div>
				<ul id='$id' class='menuSubMain'>";
		}

		/* pass 2: loop through each top level item and render it */
		while (list($item_url, $item_title) = each($header_array['items'])) {
			$current_realm_id = (isset($user_auth_realm_filenames{basename($item_url)}) ? $user_auth_realm_filenames{basename($item_url)} : 0);

			/* if this item is an array, then it contains sub-items. if not, is just
			the title string and needs to be displayed */
			if (is_array($item_title)) {
				$i = 0;

				if ((isset($user_realms[$current_realm_id])) || (!isset($user_auth_realm_filenames{basename($item_url)}))) {
					/* if the current page exists in the sub-items array, draw each sub-item */
					if (array_key_exists(basename($_SERVER['PHP_SELF']), $item_title) == true) {
						$draw_sub_items = true;
					}else{
						$draw_sub_items = false;
					}

					while (list($item_sub_url, $item_sub_title) = each($item_title)) {
						$item_sub_url = CACTI_URL_PATH . $item_sub_url;

						/* indent sub-items */
						if ($i > 0) {
							$prepend_string = '--- ';
						}else{
							$prepend_string = '';
						}

						/* do not put a line between each sub-item */
						if (($i == 0) || ($draw_sub_items == false)) {
							$background = CACTI_URL_PATH . 'images/menu_line.gif';
						}else{
							$background = '';
						}

						/* draw all of the sub-items as selected for ui grouping reasons. we can use the 'bold'
						or 'not bold' to distinguish which sub-item is actually selected */
						if ((basename($_SERVER['PHP_SELF']) == basename($item_sub_url)) || ($draw_sub_items)) {
							$td_class = 'textMenuItemSelected';
						}else{
							$td_class = 'textMenuItem';
						}

						/* always draw the first item (parent), only draw the children if we are viewing a page
						that is contained in the sub-items array */
						if (($i == 0) || ($draw_sub_items)) {
							if (basename($_SERVER['PHP_SELF']) == basename($item_sub_url)) {
								print "<li class='menuSubMainSelected'><a href='$item_sub_url'>$prepend_string$item_sub_title</a></li>";
							}else{
								print "<li><a href='$item_sub_url'>$prepend_string$item_sub_title</a></li>";
							}
						}

						$i++;
					}
				}
			}else{
				if ((isset($user_realms[$current_realm_id])) || (!isset($user_auth_realm_filenames{basename($item_url)}))) {
					/* draw normal (non sub-item) menu item */
					$item_url = CACTI_URL_PATH . $item_url;
					if (basename($_SERVER['PHP_SELF']) == basename($item_url)) {
						print "<li class='menuSubMainSelected'><a href='$item_url'>$item_title</a></li>";
					}else{
						print "<li><a href='$item_url'>$item_title</a></li>";
					}
				}
			}
		}
	}
	print '</ul></div>';
	?>
	<script type='text/javascript'>
	<!--
	$('.menuMain').each(function(index) {
		changeMenuState($(this),true);
		$(this).click(function() {
			changeMenuState($(this));
		});
		$(this).disableSelection();
	});
	-->
	</script>
	<?php
}

/* draw_actions_dropdown - draws a table the allows the user to select an action to perform
     on one or more data elements
   @param $actions_array - an array that contains a list of possible actions. this array should
     be compatible with the form_dropdown() function */
function draw_actions_dropdown($actions_array) {
	global $actions_none;
	?>
	<table class='saveBoxAction'>
		<tr>
			<td class='w1 left' valign='top'>
				<img src='<?php echo CACTI_URL_PATH; ?>images/arrow.gif' alt='' align='middle'>&nbsp;
			</td>
			<td class='right'>
				<?php print __('Choose an action:');?>
				<?php form_dropdown('drp_action',$actions_none+$actions_array,'','',ACTION_NONE,'','');?>
			</td>
			<td class='w1 right'>
				<input id='go' type='button' value='<?php print __('Go');?>' name='go'>
			</td>
		</tr>
	</table>

	<input type='hidden' name='action' value='actions'>
	<script type='text/javascript'>
	$('#go').click(function(data) {
		$var = $(this).parents('form');
		$.post($var.attr('action'), $var.serialize(), function(data) {
			$('#cdialog').html(data);
			title=$('#title td').text();
			$('#title').empty().remove();
			$('#cdialog').dialog({ title: title, minHeight: 80, minWidth: 500 });
		});
	});
	</script>
	<?php
}

/*
 * Deprecated functions
 */

function DrawMatrixHeaderItem($matrix_name, $matrix_text_color, $column_span = 1, $align = 'left') { ?>
		<td height='1' style='text-align:<?php print $align;?>;' colspan='<?php print $column_span;?>'>
			<font color='#<?php print $matrix_text_color;?>'><?php print $matrix_name;?></font>
		</td>
<?php
}

function form_area($text) { ?>
	<tr>
		<td bgcolor='#E1E1E1' class='textArea'>
			<?php print $text;?>
		</td>
	</tr>
<?php
}
