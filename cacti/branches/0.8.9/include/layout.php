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

$no_http_headers = true;
require(dirname(__FILE__) . "/global.php");
?>
$(document).ready(function(){

	// Ajax request for language menu
	$('#menu_languages').click(
	function() {
		$.ajax({
			method: "get",url: "<?php print CACTI_URL_PATH;?>layout.php?action=ajax_get_languages",
			beforeSend: function(){$("#loading").fadeIn(0);},
			complete: function(){$("#loading").fadeOut(1000); },
			success: function(html){$('#menu_languages').DropDownMenu({
				timeout: 500,
				name: 'dd_languages',
				html: html,
				title: '<?php print __('Languages');?>',
				offsetY: 5
			});}
		});
	}
	);

	// Ajax request for timezone menu
	$('#menu_timezones').click(
	function() {
		$.ajax({
			method: "get",url: "<?php print CACTI_URL_PATH;?>layout.php?action=ajax_get_timezones",
			beforeSend: function(){$("#loading").fadeIn(0);},
			complete: function(){$("#loading").fadeOut(1000);},
			success: function(html){$('#menu_timezones').DropDownMenu({
				timeout: 500,
				name: 'dd_timezones',
				html: html,
				width: 150,
				title: '<?php print __('Time zones');?>',
				offsetY: 5
			});}
		});
	}
	);

	// Ajax request for generic menus used in combination with a html_start_box
	$('.html_start_box').click(
		function() {
			var menu_id		= '#' + $(this).attr('id');
			var menu_title	= $(this).attr('name');
			var menu_rel	= $(this).attr('rel');
			$.ajax({
					method: "get",url: "<?php print CACTI_URL_PATH; ?>layout.php?action=ajax_get_data_dd_menus&" + menu_rel,
					success: function(html){
						$(menu_id).DropDownMenu({
						timeout: 500,
						name: 'dd_menu_' + this.id,
						title: menu_title,
						textAlign: 'right',
						html: html
					});}
			 });
		}
	);
});
