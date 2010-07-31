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

/**
 * GraphIt 0.1 - Codename Phoenix
 * Universal sub class for creating line / bar charts
 */

class chart extends graphit {

	protected $y_pos_x1_axis 			= false;
	protected $x_pos_data_elements 		= array();
	protected $x_pixel_block			= false;
	protected $bar_width				= false;
	protected $y_pos_zero_point			= false;
	protected $y_pixel_block			= false;
	protected $block_size				= false;

	protected function _construct(){

		chart::calculate_chart_frame();
		chart::generate_chart_blank();

		graphit::draw_chart_title();
		graphit::draw_axis_name();
	}


	protected function calculate_chart_frame(){
		$chart_width	= $this->canvas_width;
		$chart_height	= $this->canvas_height;

		/* at first only for bar charts */
		$this->offset_left		= ($this->axis_title_y)		?	4 * $this->axis_font_size	: 2 * $this->axis_font_size;
		$this->offset_right		= ($this->axis_title_y2)	?	4 * $this->axis_font_size	: 2 * $this->axis_font_size;
		$this->offset_top		= ($this->chart_title)		?	3 * $this->title_font_size	: 1 * $this->title_font_size;
		$this->offset_bottom	= ($this->axis_title_x1)	?	4 * $this->axis_font_size	: 2 * $this->axis_font_size;

		$this->chart_width   = $this->canvas_width - $this->offset_left - $this->offset_right;
		$this->chart_height  = $this->canvas_height - $this->offset_top - $this->offset_bottom;

		/* draw image border */
		//imagerectangle($this->img, $this->offset_left, $this->offset_top, $this->canvas_width-$this->offset_right, $this->canvas_height-$this->offset_bottom, $this->image_border_color);
	}



	protected function generate_chart_blank() {

		$maximum = $this->data_series_maximum;
		$minimum = $this->data_series_minimum;
		$counter = $this->data_series_element_counter;

		if($maximum == 0 & $minimum == 0) {
			$maximum = 0.5;
			$minimum = -0.5;
		}

		$highest_value = ($maximum > abs($minimum)) ? $maximum : abs($minimum);

		switch($highest_value){
			case ($highest_value >= 1000):
				$factor_rounding = -3;
				$this->block_size = 1000;
				break;
			case ($highest_value >= 100):
				$factor_rounding = -2;
				$this->block_size = 100;
				break;
			case ($highest_value >= 10):
				$factor_rounding = -1;
				$this->block_size = 10;
				break;
			case ($highest_value > 0 && $highest_value < 0.001):
				$factor_rounding = 4;
				$this->block_size = 0.0001;
				break;
			case ($highest_value > 0 && $highest_value < 0.01):
				$factor_rounding = 3;
				$this->block_size = 0.001;
				break;
			case ($highest_value > 0 && $highest_value < 0.1):
				$factor_rounding = 2;
				$this->block_size = 0.01;
				break;
			case ($highest_value > 0 && $highest_value < 1):
				$factor_rounding = 1;
				$this->block_size = 0.1;
				break;
			default:
				$factor_rounding = 0;
				$this->block_size = 1;
		}


		if($maximum != 0) {
			$upper_limit = round($maximum, $factor_rounding);
			if($upper_limit <= $maximum) $upper_limit += $this->block_size;
		}else {
			$upper_limit = 0;
		}

		if($minimum != 0) {
			$lower_limit = round($minimum, $factor_rounding);
			if($lower_limit >= $minimum) $lower_limit -= $this->block_size;
		}else {
			$lower_limit = 0;
		}

		$range = $upper_limit + abs($lower_limit);



		/* setup numeration offset */
		$numeration_box = (strlen($upper_limit)>strlen($lower_limit))
						? imageftbbox($this->axis_font_size, 0, $this->axis_font, "$upper_limit.'-'")
						: imageftbbox($this->axis_font_size, 0, $this->axis_font, "$lower_limit.'-'");

		/* distance between left chart border and Y1 axis */
		$offset_y1_numeration = $numeration_box[2] - $numeration_box[0];



		/* X-Axis settings */
		$maxLength_x_axis = ($this->canvas_width-$this->offset_right) - ($this->offset_left+$offset_y1_numeration);
		/* correct width if 3D is requested */
		if($this->chart_visualization_type == '3D') {
			$this->x_pixel_block = floor($maxLength_x_axis/$this->data_series_element_counter);
			$offset = ($this->x_pixel_block) % $this->data_series_counter;
			$this->x_pixel_block -= $offset;
			$this->bar_width = floor(($this->x_pixel_block / $this->data_series_counter)*0.8);
			$this->offset_multidimensional = floor($this->bar_width*0.3);
			$maxLength_x_axis -= $this->offset_multidimensional;
		}

		$this->x_pixel_block = floor($maxLength_x_axis/$this->data_series_element_counter);
		$offset = ($this->x_pixel_block) % $this->data_series_counter;
		$this->x_pixel_block -= $offset;
		$this->bar_width = floor(($this->x_pixel_block / $this->data_series_counter)*0.8);
		$this->x_pixel_block_inner_offset = floor(($this->x_pixel_block - ($this->bar_width * $this->data_series_counter))/2);

		/* calculate maximum height for labeling of Y1-Axis */
		$offset_y1_labeling = 3*$this->axis_font_size;

		/* every block has to be divided into 5 parts */
		$this->y_pixel_block	  = ($this->chart_visualization_type == '3D')	? ceil((($this->chart_height-$offset_y1_labeling-$this->offset_multidimensional)/$range)*$this->block_size)
																		: ceil((($this->chart_height-$offset_y1_labeling)/$range)*$this->block_size);
		$this->y_pixel_block	 -= $this->y_pixel_block % 5;
		$this->y_pixel_block_div  = $this->y_pixel_block /5;


		/* we have to equal the overhead for both sides */
		$overhead = ($this->chart_visualization_type == '3D')	? (($this->chart_height-$offset_y1_labeling-$this->offset_multidimensional) - ($range/$this->block_size*$this->y_pixel_block))/2
																: (($this->chart_height-$offset_y1_labeling) - ($range/$this->block_size*$this->y_pixel_block))/2;
		/* calculate y position of the zero point */
		$this->y_pos_zero_point = ($this->chart_visualization_type == '3D')	? ceil($this->offset_top+$overhead+$this->offset_multidimensional+($upper_limit/$this->block_size*$this->y_pixel_block))
																		: ceil($this->offset_top+$overhead+($upper_limit/$this->block_size*$this->y_pixel_block));

		/* define y position of the X1-Axis */
		$this->y_pos_x1_axis = $this->y_pos_zero_point-($lower_limit/$this->block_size*$this->y_pixel_block);

		/* define y and x position of the y1 axis */
		$y1_pos_y_axis = $this->y_pos_zero_point - ($upper_limit/$this->block_size*$this->y_pixel_block);
		$y2_pos_y_axis = $this->y_pos_x1_axis;
		$x_pos_y_axis = $this->offset_left+$offset_y1_numeration;


		/* draw background of the chart area */
		if($this->chart_visualization_type == '3D') {
			imagefilledpolygon($this->img, array(	$x_pos_y_axis, $y1_pos_y_axis,
													$x_pos_y_axis+$this->offset_multidimensional, $y1_pos_y_axis-$this->offset_multidimensional,
													$x_pos_y_axis+$maxLength_x_axis+$this->offset_multidimensional, $y1_pos_y_axis-$this->offset_multidimensional,
													$x_pos_y_axis+$maxLength_x_axis+$this->offset_multidimensional, $this->y_pos_x1_axis-$this->offset_multidimensional,
													$x_pos_y_axis+$maxLength_x_axis, $this->y_pos_x1_axis,
													$x_pos_y_axis, $this->y_pos_x1_axis),
													6, $this->chart_background_color);
		}else {
			imagefilledrectangle($this->img, $x_pos_y_axis, $y1_pos_y_axis,  $x_pos_y_axis+$maxLength_x_axis, $this->y_pos_x1_axis, $this->chart_background_color);
		}

		/* numeration initial value */
		$numeration_value = $upper_limit + $this->block_size;

		/* draw the Y-Axis */
		imageline($this->img, $x_pos_y_axis, $y1_pos_y_axis, $x_pos_y_axis, $y2_pos_y_axis, $this->y_axis_color);

		/* define the lenght of the  in relation to their distance */
		$lenght_y_div_big = ($this->y_pixel_block_div > 1) ? 5 : 3;
		$lenght_y_div_small = ($this->y_pixel_block_div > 1) ? 2 : 0;

		/* define whether every block can be labeled or only every second unit */
		$numeration_div	= ($this->y_pixel_block > $this->axis_font_size*2) ? 1 : 3;

		for($rel_y_position=0, $y_position=$y1_pos_y_axis; $y_position <= $y2_pos_y_axis; $rel_y_position += $this->y_pixel_block_div, $y_position += $this->y_pixel_block_div) {
			if($rel_y_position % $this->y_pixel_block == 0) {
				$lenght_y_div = $lenght_y_div_big;
				$color_y_div = $this->y_axis_color;

				/* numeration */
				$numeration_value -= $this->block_size;

			}else {
				$lenght_y_div = $lenght_y_div_small;
				$color_y_div = $this->y_axis_color_small_tics;
			}
			/* draw the divisor */
			imageline($this->img, $x_pos_y_axis, $y1_pos_y_axis+$rel_y_position, $x_pos_y_axis-$lenght_y_div, $y1_pos_y_axis+$rel_y_position, $color_y_div);

			/* draw the y grid and perform the numeration */
			if($rel_y_position % $this->y_pixel_block == 0) {
				/* draw the y grid if configured */
				if($this->y_grid) {
					imagesetstyle($this->img, $this->y_grid_style);
					if($this->chart_visualization_type == '3D') {

						/* draw a base plate if the lower border (x labeling) does not pass throught the zero point and cover the x-Axis */
						if($y_position == $this->y_pos_zero_point) {
							imageline($this->img, $x_pos_y_axis, $y_position, $x_pos_y_axis+$maxLength_x_axis, $y_position, $this->grid_color);
							imageline($this->img, $x_pos_y_axis+$maxLength_x_axis, $y_position, $x_pos_y_axis+$maxLength_x_axis+$this->offset_multidimensional, $y_position-$this->offset_multidimensional, ($rel_y_position==0) ? $this->grid_color : IMG_COLOR_STYLED);
						}

						/* only the border should not be drawn by using the y_grid_style */
						imageline($this->img, $x_pos_y_axis, $y_position, $x_pos_y_axis+$this->offset_multidimensional, $y_position-$this->offset_multidimensional, ($rel_y_position==0) ? $this->grid_color : IMG_COLOR_STYLED);
						imageline($this->img, $x_pos_y_axis+$this->offset_multidimensional, $y_position-$this->offset_multidimensional, $x_pos_y_axis+$maxLength_x_axis+$this->offset_multidimensional, $y_position-$this->offset_multidimensional, ($rel_y_position==0) ? $this->grid_color : IMG_COLOR_STYLED);


					}else {
						imageline($this->img, $x_pos_y_axis, $y_position, $x_pos_y_axis+$maxLength_x_axis, $y_position, IMG_COLOR_STYLED);
					}
				}

				/* create the numeration */
				$numeration_box = imageftbbox($this->axis_font_size, 0, $this->axis_font, $numeration_value);
				$offset_numeration = $numeration_box[2] - $numeration_box[0] + $this->axis_font_size;
				$numeration_diff = $offset_y1_numeration - $offset_numeration;
				imagefttext($this->img, $this->axis_font_size, 0 ,$this->offset_left+$numeration_diff, $y_position+0.5*$this->axis_font_size, $this->axis_font_color, $this->axis_font, $numeration_value);
			}

		}


		/* draw the X1-Axis */
		imageline($this->img, $x_pos_y_axis, $this->y_pos_x1_axis, $x_pos_y_axis+$maxLength_x_axis, $this->y_pos_x1_axis, $this->x_axis_color);

		/* use the same lenght for the x divisors like used for the y ones */
		$lenght_x_div_big = $lenght_y_div_big;

		$this->x_pos_data_elements = array();

		/* draw the x Grid  and calculate the different start positions for the data elements */
		for($rel_x_position=0, $data_element=0; $data_element <= $this->data_series_element_counter; $rel_x_position += $this->x_pixel_block, $data_element++ ) {

			$x_position =  $x_pos_y_axis + $rel_x_position;

			/* cache the different start positions */
			$this->x_pos_data_elements[] = $x_position + $this->x_pixel_block_inner_offset;;

			imageline($this->img, $x_position, $this->y_pos_x1_axis, $x_position, $this->y_pos_x1_axis+$lenght_x_div_big, $this->x_axis_color);

			if($this->x_grid) {
				imagesetstyle($this->img, $this->x_grid_style);
				if($this->chart_visualization_type == '3D') {
					/* avoid that the first offset will be drawn twice */
					if($rel_x_position != 0) {
						imageline($this->img, $x_position, $this->y_pos_x1_axis, $x_position+$this->offset_multidimensional, $this->y_pos_x1_axis-$this->offset_multidimensional, IMG_COLOR_STYLED);
					}
					imageline($this->img, $x_position+$this->offset_multidimensional, $this->y_pos_x1_axis-$this->offset_multidimensional, $x_position+$this->offset_multidimensional, $y1_pos_y_axis-$this->offset_multidimensional, IMG_COLOR_STYLED);
				}else {
					imageline($this->img, $x_position, $this->y_pos_x1_axis, $x_position, $y1_pos_y_axis, IMG_COLOR_STYLED);
				}
			}

			/* perform the labeling of the x-Axis */
			if($this->data_series_element_counter > $data_element) {
				$label = isset($this->label_series[$data_element])	? $this->label_series[$data_element]
																	: $data_element+1;
				$label_box = imageftbbox($this->axis_font_size, 0, $this->axis_font, $label);
				$x_pos_x_label = $x_position + $label_box[0] + ($this->x_pixel_block / 2) - ($label_box[4] / 2);
				$y_pos_x_label = $this->y_pos_x1_axis+2*$this->axis_font_size;
				imagefttext($this->img, $this->axis_font_size, 0 , $x_pos_x_label, $y_pos_x_label, $this->axis_font_color, $this->axis_font, $label);
			}
		}

		/* draw all data elements */
		chart::draw_data_series();

		/* draw the x-axis */
		imageline($this->img, $x_pos_y_axis, $this->y_pos_zero_point, $x_pos_y_axis+$maxLength_x_axis, $this->y_pos_zero_point, $this->x_axis_color);
	}


	protected function draw_data_series() {

//print_r($this->data_series_types);
//print_r($this->data_series);

		if($this->data_series_element_counter) {



			/* bar type: stacked, depth, multi */
			foreach($this->x_pos_data_elements as $data_element_index => $x_position) {

				foreach($this->data_series as $data_series_index => $data_series) {

					if(isset($data_series[$data_element_index]) && in_array($data_series_index, $this->data_series_types['BAR'])) {


						$y_offset_x_axis = ($data_series[$data_element_index]>0) ? -1 : +1;
						$y_position = $this->y_pos_zero_point - ($data_series[$data_element_index]/$this->block_size*$this->y_pixel_block);

						$x1 = $x_position+$data_series_index*$this->bar_width;
						$y1 = $y_position;
						$x2 = $x_position+($data_series_index+1)*$this->bar_width;
						$y2 = $this->y_pos_zero_point+$y_offset_x_axis;

						if($this->chart_visualization_type == '3D') {
							if($data_series[$data_element_index] !== NULL) {
								graphit::draw_multidimensional_bar($x1, $y1, $x2, $y2, $data_series_index);
							}

						}else {
							graphit::draw_bar($x1, $y1, $x2, $y2, $data_series_index);
						}
					}
				}
			}

			/* draw lines first */
			if(isset($this->data_series_types['LINE'])) {
				foreach($this->data_series_types['LINE'] as $data_series_index) {
					$coordinates = array();
					foreach($this->data_series[$data_series_index] as $data_element_index => $data_value) {
						/* x coordinate */
						$coordinates[] = $this->x_pos_data_elements[$data_element_index];
						/* y coordinate */
						$coordinates[] = $this->y_pos_zero_point - ($data_value/$this->block_size*$this->y_pixel_block);
					}
					graphit::draw_line($coordinates, $data_series_index);
				}
			}


		}
	}

}