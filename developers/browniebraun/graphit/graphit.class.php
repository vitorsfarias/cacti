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
 * Chart engine
 */
class graphit{

    private     $chart_default_type             = 'combi';
    private     $image_default_width            = 180;
    private     $image_default_height           = 100;

	protected   $data_series                    = array();
    protected   $data_series_color              = array();
    protected	$data_series_names				= array();
	protected	$data_series_types				= array();
    protected   $data_series_maximum            = 0;
    protected   $data_series_minimum            = 0;
    protected	$data_series_counter			= 0;
	protected	$data_series_parallel_counter	= 0;
	protected	$data_series_stacked_counter	= 0;
    protected   $data_series_element_counter    = 1;
	protected	$label_series					= array();
	protected	$label_series_angle				= 0;
	protected	$label_series_maxLength			= 0;

    protected   $axis_title_x1                  = false;
    protected   $axis_title_y                   = false;
    protected   $axis_title_y2                  = false;

    protected   $axis_unit_x1                   = false;
   	protected   $axis_unit_y                    = false;
    protected   $axis_unit_y2                   = false;

    protected   $x_grid                         = false;
    protected   $y_grid                         = false;
    protected   $x_grid_style                   = 'dotted';
    protected   $y_grid_style                   = 'dotted';

    protected	$chart_legend					= false;
    protected	$chart_legend_lenght_maximum	= 0;
	protected	$chart_visualization_type		= '2D';

	protected	$valid_series_types				= array('BAR', 'BAR_STACKED', 'AREA', 'AREA_STACKED', 'LINE');

    protected   $valid_chart_types				= array('xy'		=> 'CHART_GROUP_I',
														'hbar' 		=> 'CHART_GROUP_I',
														'combi'		=> 'CHART_GROUP_I',
														'pie'		=> 'CHART_GROUP_II',
														'spider'	=> 'CHART_GROUP_II');

    protected   $valid_font_objects             = array('TITLE',
                                                        'AXIS',
                                                        'LEGEND' );

    protected   $valid_color_objects            = array('IMAGE_BORDER',
                                                        'IMAGE_BACKGROUND',
                                                        'CHART_BACKGROUND',
                                                        'X_AXIS',
                                                        'Y_AXIS',
                                                        'Y2_AXIS',
                                                        'GRID',
                                                        'AXIS_FONT',
                                                        'TITLE_FONT',
                                                        'LEGEND_FONT' );

    protected   $valid_grid_styles              = array('dotted',
														'dashed',
														'drawn_through');




    /**
     * Constructor
     * @title   = title of the chart
     * @width   = total width in px
     * @height  = total height in px
     * @access protected
     */
    function __construct($title = false, $type = 'vbar', $width = 180, $height = 100){

		/* setup default values, valid for all types */
        if (isset($this->valid_chart_types[$type])) {
			$this->chart_type	= $type;
			$this->chart_group	= $this->valid_chart_types[$type];
		}else {
			$this->chart_type	= $this->chart_default_type;
			$this->chart_group	= $this->valid_chart_types[$this->chart_default_type];
		}

		$this->canvas_width      = ( (int) $width < $this->image_default_width )
								? $this->image_default_width
								: (int) $width;

		$this->canvas_height		= ( (int) $height < $this->image_default_height )
								? $this->image_default_height
								: (int) $height;

		$this->chart_title		= ($title)
								? $title
								: false;

        /* setup default font sizes */
        $lowest_value           = ($this->canvas_width >= $this->canvas_height)
								? $this->canvas_height
								: $this->canvas_width;

        $base_value				= round($lowest_value/($lowest_value*0.05+24));

       	/* default chart settings */
        $this->axis_font_size				= $base_value;
        $this->title_font_size				= round($base_value*1.4);
        $this->legend_font_size				= $base_value;

        /* setup default colors */
        $this->image_border_color       	= '#FFFFFF';
        $this->image_background_color   	= '#A1A1A1';
        $this->chart_background_color   	= '#F5F5F5';
    	$this->chart_value_border			= '#995599';
        $this->x_axis_color             	= '#000000';
        $this->x_axis_color_small_tics  	= '';
        $this->y_axis_color             	= '#000000';
        $this->y_axis_color_small_tics  	= '';
        $this->y2_axis_color            	= '#000000';
        $this->x2_axis_color_small_tics 	= '';
        $this->grid_color               	= '#00FF00';
        $this->axis_font_color          	= '#000000';
        $this->title_font_color         	= '#FFFFFF';
        $this->legend_font_color        	= '#000000';

        /* transparency */
        $this->alpha_image_border_color		= false;
        $this->alpha_image_background_color = false;
        $this->alpha_chart_background_color = false;
        $this->alpha_x_axis_color           = false;
        $this->alpha_y_axis_color           = false;
        $this->alpha_y2_axis_color          = false;
        $this->alpha_grid_color             = false;
        $this->alpha_axis_font_color        = false;
        $this->alpha_title_font_color       = false;
        $this->alpha_legend_font_color      = false;


    	/* draw axis titles */
    	if($this->chart_type == 'pie' | $this->chart_type == 'spider') {
    		//include_once('./graphit_subclass.charts2.php');
    	}else {
    		include_once('graphit.subclass.xy.php');
    	}



	}




    public function create() {

    	/* create the image handle */
    	$this->img = imagecreatetruecolor($this->canvas_width, $this->canvas_height);

        /* setup image colors */
        $this->image_background_color   = $this->get_image_color($this->image_background_color, $this->alpha_image_background_color);
        $this->image_border_color       = $this->get_image_color($this->image_border_color, $this->alpha_image_border_color);
        $this->chart_background_color   = $this->get_image_color($this->chart_background_color, $this->alpha_chart_background_color);

        $this->x_axis_color_small_tics  = $this->get_image_color($this->x_axis_color, $this->alpha_x_axis_color+50);
		$this->x_axis_color             = $this->get_image_color($this->x_axis_color, $this->alpha_x_axis_color);

        $this->y_axis_color_small_tics	= $this->get_image_color($this->y_axis_color, $this->alpha_y_axis_color+50);
        $this->y_axis_color             = $this->get_image_color($this->y_axis_color, $this->alpha_y_axis_color);

        $this->y2_axis_color_small_tics	= $this->get_image_color($this->y2_axis_color, $this->alpha_y2_axis_color+20);
        $this->y2_axis_color            = $this->get_image_color($this->y2_axis_color, $this->alpha_y2_axis_color);
        $this->grid_color               = $this->get_image_color($this->grid_color, $this->alpha_grid_color);
		$this->chart_value_border		= $this->get_image_color($this->chart_value_border, 50);

        /* setup font colors */
        $this->axis_font_color          = $this->get_image_color($this->axis_font_color, $this->alpha_axis_font_color);
        $this->title_font_color         = $this->get_image_color($this->title_font_color, $this->alpha_title_font_color);
        $this->legend_font_color        = $this->get_image_color($this->legend_font_color, $this->alpha_title_font_color);

		/* setup series colors */
		foreach($this->data_series_color as &$color) { $color = $this->get_series_color($color["color"], $color["alpha"]);  }

       	/* setup grid style */
        $this->valid_grid_styles        = array('dotted' => array($this->grid_color, IMG_COLOR_TRANSPARENT),
                                                'dashed' => array(IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, IMG_COLOR_TRANSPARENT, $this->grid_color, $this->grid_color, $this->grid_color, $this->grid_color),
                                                'drawn_through' => array($this->grid_color));
        $this->x_grid_style             = $this->valid_grid_styles[$this->x_grid_style];
        $this->y_grid_style             = $this->valid_grid_styles[$this->y_grid_style];

        /* draw the background */
        imagefilltoborder($this->img, 0, 0, 1, $this->image_background_color);

        /* draw image border */
        imagerectangle($this->img, 1, 1, $this->canvas_width-2, $this->canvas_height-2, $this->image_border_color);

		/* execute the "constructor" of the sub class */
		chart::_construct();


        imagepng($this->img);
        imagedestroy($this->img);


    }


	/**
	 * graphit::set_label_series()
	 *
	 * @param array $labels -an array that contains all values for labeling
	 * @param int $angle - angled between 0 and 360 degree used to rotate the labels
	 */
	public function set_label_series($labels, $angle = 0) {
		if(is_array($labels) && sizeof($labels) > 0 ){

			$this->label_series = $labels;

			foreach($labels as $label){
				if(strlen($label) > $this->label_series_maxLength) {
					$this->label_series_maxLenght = $label;
				}
			}

			if(is_int($angle) && $angle > 0 && $angle < 360) {
				$this->label_series_angle = $angle;
			}
		}
	}



    public function set_data_series($name, $type, $values, $color = false, $alpha = 0) {

    	if(in_array((string)$type, $this->valid_series_types)) {
    		if(is_array($values) && sizeof($values) > 0 ) {

    			$this->data_series_counter++;

    			if($type == 'BAR') {
					$this->data_series_parallel_counter++;
    			}elseif ($type == 'BAR_STACKED'){
    				$this->data_series_stacked_counter = 1;
    			}

    			foreach($values as &$value) { $value = (float) $value; }

    			$maximum    = max($values);
    			$minimum    = min($values);
    			$elements   = count($values);

				$this->data_series[]       	= $values;
    			$data_series_index = array_pop(array_keys($this->data_series));

    			$this->data_series_color[]	= array("color" => $color, "alpha" => $alpha);
    			$this->data_series_names[]	= (string)$name;
    			$this->data_series_types[$type][] = $data_series_index;

    			if(strlen($name)>$this->chart_legend_lenght_maximum) {
    				$this->chart_legend_lenght_maximum = strlen($name);
    			}

    			if($elements > $this->data_series_element_counter) {
    				$this->data_series_element_counter = $elements;
    			}




    			if($maximum > $this->data_series_maximum) {
    				$this->data_series_maximum = $maximum;
    			}

    			if($minimum < $this->data_series_minimum) {
    				$this->data_series_minimum = $minimum;
    			}

    		}else {
    			//error handle
    		}

    	}
    }


	public function set_visualization_type($type) {
		if(in_array(strtoupper($type), array('2D', '3D'))) {
			$this->chart_visualization_type = strtoupper($type);
		}
	}


    /**
     * graphit::set_object_font()
     * setup the color of the available objects
     * @path   = path of the font file
     * @return
     */
    public function set_object_font($object, $path) {

        if(in_array($object, $this->valid_font_objects) && file_exists($path)) {
            $object = strtolower($object) . '_font';
            $this->$object = $path;
        }
    }


    /**
     * graphit::set_object_color()
     * setup the color of the available objects
     * @color   = hexadecimal color code
     * @return
     */
    public function set_object_color($object, $color, $alpha = 0) {
        if(in_array($object, $this->valid_color_objects)) {
            /* setup color */
			$object = strtolower($object) . '_color';
            $this->$object = $color;

            /* setup transparency */
            $object = 'alpha_' . strtolower($object);
            $this->$object = $alpha;
        }
    }


   /**
     * graphit::set_axis()
     * setup an axis
     * @type        = possible axis are 'x','y' and 'y2'
     * @title       = the title to be displayed
     * @unit        = the unit without brackets
     * @position    = the position in relation to the axis as base line
     * @return
     */
    public function set_axis($type, $title, $unit, $position) {

        $valid_axis      = array('x1', 'y', 'y2');
        $valid_positions = array('right', 'middle');

        if(in_array($type, $valid_axis)) {
            $var_title      = 'axis_title_' . $type;
            $var_unit       = 'axis_unit_' . $type;
            $var_position   = 'axis_position_' . $type;

            $this->$var_title    = $title;
            $this->$var_unit     = $unit;
            $this->$var_position = (in_array($position, $valid_positions)) ? $position : 'middle';
        }
    }





    public function set_grid($x_style, $y_style) {

    	if($x_style && in_array((string)$x_style, $this->valid_grid_styles)) {
    		$this->x_grid = true;
    		$this->x_grid_style = $x_style;
    	}

    	if($y_style && in_array((string)$y_style, $this->valid_grid_styles)) {
            $this->y_grid = true;
            $this->y_grid_style = $y_style;
    	}
    }


	/**
	 * graphit::set_legend()
	 * setup position of the legend
	 * @position = position relative to the chart. ("right", "left", "top" or "bottom")
	 * @return
	 */
	public function set_legend($position) {
		$valid_legend_positions = array("right", "left", "top", "bottom");
		if (in_array($position, $valid_legend_positions)) {
			$this->chart_legend = $position;
		}
	}










	/**
     * graphit::get_image_color()
     * returns the allocated image color
     * @param mixed $color
     * @return
     */
	private function get_image_color($color, $alpha = false) {

		$rgb	= graphit::trans_hex2RGB($color);
		$color	= is_integer($alpha) ? imagecolorallocatealpha($this->img, $rgb[0], $rgb[1], $rgb[2], $alpha)
									 : imagecolorallocate($this->img, $rgb[0], $rgb[1], $rgb[2]);

		return $color;
	}


	/**
	 * graphit::get_series_color()
	 * returns the allocated image color
	 * @param mixed $color
	 * @return
	 */
	private function get_series_color($color, $alpha = false) {

		$rgb	= graphit::trans_hex2RGB($color);

		$rgb[3] = ($rgb[0] >= 30) ? $rgb[0] - 30 : 0;
		$rgb[4] = ($rgb[1] >= 30) ? $rgb[1] - 30 : 0;
		$rgb[5] = ($rgb[2] >= 30) ? $rgb[2] - 30 : 0;

		$color			= is_integer($alpha) ? imagecolorallocatealpha($this->img, $rgb[0], $rgb[1], $rgb[2], $alpha)
									 		 : imagecolorallocate($this->img, $rgb[0], $rgb[1], $rgb[2]);
		$shadow			= is_integer($alpha) ? imagecolorallocatealpha($this->img, $rgb[3], $rgb[4], $rgb[5], $alpha)
											 : imagecolorallocate($this->img, $rgb[3], $rgb[4], $rgb[5]);
		return array('normal' => $color, 'shadow' => $shadow);
	}


    /**
     * graphit::trans_hex2RGB()
     * transforms hexadecimal color code to RGB code
     * @param hex color - hexadecimal color code
     * @param hex default_color - alternative color which should be used if prim. color is invalid
     * @return string - a string that contais the RGB code (red, green, blue)
     */
    protected function trans_hex2RGB($color, $default_color = array(255,255,255)) {
        $color = str_replace('#', '', $color);

        if(strlen($color) != 6) {
            return $default_color;
        }else {
            $red    = hexdec(substr($color, 0, 2));
            $green  = hexdec(substr($color, 2, 2));
            $blue   = hexdec(substr($color, 4, 2));
            return  array($red, $green, $blue);
        }
    }


	/**
	 * graphit::draw_chart_title()
	 * draws the chart title
	 */
	protected function draw_chart_title(){

		if($this->chart_title) {

			/* calculate the size of the title */
			$title_box = imageftbbox($this->title_font_size, 0, $this->title_font, $this->chart_title);
			$x = $title_box[0] + ($this->canvas_width / 2) - ($title_box[4] / 2);
			$y = 2*$this->title_font_size;

			/* draw the title */
			imagefttext($this->img, $this->title_font_size,0 ,$x ,$y , $this->title_font_color, $this->title_font, $this->chart_title);

		}
	}


	/**
	 * graphit::draw_chart_legend()
	 * draws the chart legend
	 */
	protected function draw_chart_legend() {

		if(sizeof($this->data_series_names) > 0) {

			if($this->chart_legend == "right") {
				$this->offset_right;

				$y1_legend		= 10;
				$x1_rectangle	= 10;
				$x2_rectangle	= $x1_rectangle + $this->legend_font_size;
				$y2_rectangle	= $y1_legend + $this->legend_font_size;

				$x1_legend		= $x2_rectangle + $this->legend_font_size;

				$i = 0;
				foreach($this->data_series_names as $key => $data_series_name) {

					$i++;
					imagefilledrectangle($this->img, $x1_rectangle, $y1_legend*($i-1), $x2_rectangle, $y2_rectangle, $this->data_series_color[$key]['normal']);
					imagerectangle($this->img, $x1_rectangle, $y1_legend*($i-1), $x2_rectangle, $y2_rectangle, $this->data_series_color[$key]['normal']);
					imagefttext($this->img, $this->legend_font_size, 0, $x1_legend, $y1_legend*$i , $this->title_font_color, $this->title_font, $data_series_name);
				}

			}
		}
	}


	/**
	 * graphit::draw_axis_name()
	 * labels the x and y axis
	 */
	protected function draw_axis_name(){

		$valid_axis = array('x1', 'y', 'y2');

		foreach($valid_axis as $axis) {

			$var_title      = 'axis_title_' . $axis;
			$var_unit       = 'axis_unit_' . $axis;
			$var_position   = 'axis_position_' . $axis;

			if($this->$var_title) {

				$title = $this->$var_title . (($this->$var_unit != '') ? ' [' . $this->$var_unit . ']' : '');
				if($axis == 'y') {
					$angle = 90;
				}elseif($axis == 'y2') {
					$angle = 270;
				}else {
					$angle = 0;
				}

				/* calculate the size of the title */
				$title_box = imageftbbox($this->axis_font_size, $angle, $this->axis_font, $title);

				if($axis == 'x1'){
					$y = $this->canvas_height - 2*$this->axis_font_size;
					$x = ($this->$var_position == 'middle') ? ($this->chart_width / 2) - ($title_box[4] / 2) + $this->offset_left
					                                        : $this->canvas_width - $this->offset_right - $title_box[4];
				}elseif($axis == 'y') {
					$x = 3*$this->axis_font_size;
					$y = ($this->$var_position == 'middle') ? ($this->chart_height / 2) - ($title_box[3] / 2) + $this->offset_top
					                                        : -$title_box[3] + $this->offset_top;
				}else{
					$x = $this->canvas_width - 3*$this->axis_font_size;;
					$y = ($this->$var_position == 'middle') ? ($this->chart_height / 2) - ($title_box[3] / 2) + $this->offset_top
					                                        : -$title_box[3] + $this->offset_top;
				}

				/* draw the y-title */
				imagefttext($this->img, $this->axis_font_size, $angle,$x ,$y , $this->axis_font_color, $this->axis_font, $title);
			}
		}
	}


	/**
	 * graphit::draw_line()
	 * draws a line with a minimum of two points
	 * @param array $coordinates - an array that contains cordinates by pairs (x1, y1, x2, y2, x3, y3 ...).
	 * @param int $data_series_index - the index of the data series to access the related parameters
	 */
	protected function draw_line($coordinates, $data_series_index) {

		if(is_array($coordinates) && sizeof($coordinates)>=4 && (sizeof($coordinates)% 2 == 0 )) {
			$counter = sizeof($coordinates)/2-1;
			for($n=0; $n < $counter; $n++) {
				$offset = 2*$n;
				imagefilledarc($this->img, $coordinates[0+$offset], $coordinates[1+$offset], 3, 3, 0, 360, $this->data_series_color[$data_series_index]['normal'], IMG_ARC_PIE);
				imageline($this->img, $coordinates[0+$offset], $coordinates[1+$offset], $coordinates[2+$offset], $coordinates[3+$offset],  $this->data_series_color[$data_series_index]['normal']);
			}
			imagefilledarc($this->img, $coordinates[2+$offset], $coordinates[3+$offset], 3, 3, 0, 360, $this->data_series_color[$data_series_index]['normal'], IMG_ARC_PIE);
		}
	}



	/**
	 * graphit::draw_bar()
	 * draws a 2D bar
	 * @param int $x1 x coordinate upper left corner
	 * @param int $y1 y coordinate upper left corner
	 * @param int $x2 x coordinate lower left corner
	 * @param int $y2 y coordinate lower left corner
	 * @param int $data_series_index the index of the data series to access the related parameters
	 */
	protected function draw_bar($x1, $y1, $x2, $y2, $data_series_index) {

		imagefilledrectangle($this->img, $x1, $y1, $x2, $y2,  $this->data_series_color[$data_series_index]['normal']);
		imagerectangle($this->img, $x1, $y1, $x2, $y2, $this->chart_value_border);
	}


	/**
	 * graphit::draw_multidimensional_bar()
	 * draws a 3D bar
	 * @param int $x1 x coordinate upper left corner
	 * @param int $y1 y coordinate upper left corner
	 * @param int $x2 x coordinate lower left corner
	 * @param int $y2 y coordinate lower left corner
	 * @param int $data_series_index the index of the data series to access the related parameters
	 */
	protected function draw_multidimensional_bar($x1, $y1, $x2, $y2, $data_series_index) {

		if($y1 > $y2) {
			$P1y = $y2;
			$y2 = $y1;
		}else {
			$P1y = $y1;
		}

		$P1x = $x1;
		$P2x = $P1x + $this->offset_multidimensional;
		$P2y = $P1y - $this->offset_multidimensional;
		$P3x = $x2 + $this->offset_multidimensional;
		$P3y = $P2y;
		$P4x = $P3x;
		$P4y = $y2 - $this->offset_multidimensional;
		$P5x = $x2;
		$P5y = $y2;
		$P6x = $x2;
		$P6y = $P1y;

		if(abs($P1y-$y2) != 1) {
			imagefilledrectangle($this->img, $P1x, $P1y, $x2, $y2, $this->data_series_color[$data_series_index]['normal']);
			imagefilledpolygon($this->img, array( $P1x, $P1y, $P2x, $P2y, $P3x, $P3y, $P4x, $P4y, $P5x, $P5y, $P6x, $P6y), 6, $this->data_series_color[$data_series_index]['shadow']);
			imagerectangle($this->img, $P1x, $P1y, $x2, $y2, $this->chart_value_border);
			imagepolygon($this->img, array( $P1x, $P1y, $P2x, $P2y, $P3x, $P3y, $P4x, $P4y, $P5x, $P5y, $P6x, $P6y), 6, $this->chart_value_border);
			imageline($this->img, $P6x, $P6y, $P3x, $P3y, $this->chart_value_border);
		}else {
			imagefilledpolygon($this->img, array( $P1x, $P1y-1, $P2x-1, $P2y, $P3x-1, $P3y,$P6x, $P6y-1), 4,  $this->data_series_color[$data_series_index]['normal']);
			imagepolygon($this->img, array( $P1x, $P1y-1, $P2x-1, $P2y, $P3x-1, $P3y,$P6x, $P6y-1), 4, $this->chart_value_border);
		}
	}
}
?>