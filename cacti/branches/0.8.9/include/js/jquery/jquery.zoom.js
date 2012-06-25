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

/* requirements:
	jQuery 1.7.x or above
	jQuery UI 1.8.x or above
*/

(function($){
	$.fn.zoom = function(options) {

		/* default values of the different options being offered */
		var defaults = {
			inputfieldStartTime	: '',					// ID of the input field that contains the start date
			inputfieldEndTime	: '',					// ID of the input field that contains the end date
			submitButton		: 'button_refresh_x',	// ID of the submit button
			cookieName			: 'cacti_zoom'			// default name required for session cookie
		};

		/* some global variables */
		var zoom			= new Array();
		zoom.marker			= new Array();
		zoom.marker[1]		= new Array();
		zoom.marker[2]		= new Array();



		var rrdgraph;										// reference to the rrdgraph itself
		var graphUrl		= '';
		var localGraphID	= 0;
		var imagePosTop		= 0;
		var imagePosLeft	= 0;

		var zoomBoxWidth	= 0;
		var zoomBoxHeight	= 0;
		var zoomBoxPosTop	= 0;
		var zoomBoxPosRight	= 0;
		var zoomBoxPosLeft	= 0;

		var zoomAreaHeight  = 0;


		var zoomStartPos	= 'none';
		var zoomEndPos		= 'none';
		var zoomAction		= 'left2right';

		var graphWidth		= 0;
		var graphTimespan	= 0;
		var secondsPerPixel = 0;
		var location		= window.location.href.split("?");
		var locationBase	= location[0];
		var graphParameters = '';

		var options 		= $.extend(defaults, options);

		/* use a cookie to support local settings */
		var cookie = $.cookie(options.cookieName);
		var zoomCustomSettings = cookie ?  unserialize(cookie) : new Array();

		if(zoomCustomSettings.zoomMode == undefined) zoomCustomSettings.zoomMode = 'quick';
		if(zoomCustomSettings.zoomOutPositioning == undefined) zoomCustomSettings.zoomOutPositioning = 'center';
		if(zoomCustomSettings.zoomOutFactor == undefined) zoomCustomSettings.zoomOutFactor = '2';
		if(zoomCustomSettings.zoomMarkers == undefined) zoomCustomSettings.zoomMarkers = true;
		if(zoomCustomSettings.zoomTimestamps == undefined) zoomCustomSettings.zoomTimestamps = true;
		if(zoomCustomSettings.zoom3rdMouseButton == undefined) zoomCustomSettings.zoom3rdMouseButton = false;

		/* create or update a session cookie */
		$.cookie( options.cookieName, serialize(zoomCustomSettings), {expires: null} );

		/* support jQuery's concatination */
		return this.each(function() {
			rrdgraph = $(this);
			zoom_init();
		});

		/* init zoom */
		function zoom_init() {
			var $this = rrdgraph;
			$this.mouseenter(
				function(){
					zoomFunction_init($this);
				}
			);
		}



		/* check if image has been already loaded or if broken */
		function isReady(image){
			if(typeof image[0].naturalWidth !== undefined && image[0].naturalWidth == 0) {
				return false;
			}
			// necessary to support older versions of IE(6-8)
			if(!image[0].complete) {
				return false;
			}
			return true;
		}


		function zoomFunction_init(image) {

			var $this = image;

			// exit if image has not been already loaded or if it is unavailable
			if(!isReady($this)) {
				return;
			}

			var image = rrdgraph;

			// get all graph parameters
			graphUrl 			= $this.attr("src");
			graphParameters		= getUrlVars(graphUrl);

			if(zoomStartPos != 'none' && localGraphID != graphParameters["local_graph_id"]) {
				return;
			}

			localGraphID		= graphParameters["local_graph_id"];
			graphStartTime		= graphParameters["graph_start"];
			graphEndTime		= graphParameters["graph_end"];
			graphTimespan		= graphParameters["graph_end"] - graphParameters["graph_start"];
			secondsPerPixel 	= graphTimespan/graphParameters["graph_width"];


			graphWidth			= graphParameters["graph_width"];
			graphHeight			= graphParameters["graph_height"];

			if((graphParameters["title_font_size"] <= 0) || (graphParameters["title_font_size"] == "")) {
				graphTitleFontSize = 12;
			}else {
				graphTitleFontSize = graphParameters["title_font_size"];
			}

			if("graph_nolegend" in graphParameters) {
				graphTitleFontSize	*= .70;
			}

			imageWidth			= $this.width();
			imageHeight			= $this.height();
			imagePosTop			= $this.offset().top;
			imagePosLeft		= $this.offset().left;

			// define the zoom box
			zoomBoxWidth		= graphWidth;
			zoomBoxHeight		= graphHeight;
			zoomAreaHeight		= graphHeight;

			if(graphTitleFontSize == null) {
				zoomBoxPosTop = 32 - 1;
			}else {
				//default multiplier
				var multiplier = 2.4;

				// array of "best fit" multipliers
				multipliers = new Array("-5", "-2", "0", "1.7", "1.6", "1.7", "1.8", "1.9", "2", "2", "2.1", "2.1", "2.2", "2.2", "2.3", "2.3", "2.3", "2.3", "2.3");

				if(multipliers[Math.round(graphTitleFontSize)] != null) {
					multiplier = multipliers[Math.round(graphTitleFontSize)];
				}

				zoomBoxPosTop = imagePosTop + parseInt(Math.abs(graphTitleFontSize) * multiplier) + 15;
			}

			zoomBoxPosBottom = parseInt(zoomBoxPosTop) + parseInt(zoomBoxHeight);
			zoomBoxPosRight	= parseInt(imagePosLeft) + parseInt(imageWidth) - 30;
			zoomBoxPosLeft	= parseInt(zoomBoxPosRight) - parseInt(graphWidth);

			// add the zoom box if it has not been created yet.
			if($("#zoomBox").length == 0) {
				/* IE does not fire hover or click behaviors on completely transparent elements.
					Use a background color and set opacity to 1% as a workaround.(see CSS file) */
				$("<div id='zoomBox'></div>").appendTo("body");
			}

			// reset the zoom box
			$("#zoomBox").off().css({ cursor:'crosshair', width:zoomBoxWidth + 'px', height:zoomBoxHeight + 'px', top:zoomBoxPosTop+'px', left:zoomBoxPosLeft+'px' });
			$("#zoomBox").bind('contextmenu', function(e) { zoomContextMenu_show(e); return false;} );

			// add the zoom area if it has not been created yet.
			if($("#zoomArea").length == 0) {
				$("<div id='zoomArea'></div>").appendTo("body");
			}

			// reset the area box
			$("#zoomArea").off().css({ top:zoomBoxPosTop+'px', height:zoomAreaHeight+'px' });
			init_ZoomAction(image);

			// add two markers if they has not been created yet.
			if($("#zoom-marker-1").length == 0) {
				$('<div id="zoom-excluded-area-1" class="zoomExcludedArea"></div>').appendTo("body");
				$('<div class="zoom-marker" id="zoom-marker-1"><div class="zoom-marker-arrow-down"></div><div class="zoom-marker-arrow-up"></div></div>').appendTo("body");
				$('<div id="zoom-marker-tooltip-1" class="zoom-marker-tooltip"><div id="zoom-marker-tooltip-1-arrow-left" class="test-arrow-left"></div><span id="zoom-marker-tooltip-value-1" class="zoom-marker-tooltip-value">-</span><div id="zoom-marker-tooltip-1-arrow-right" class="test-arrow-right"></div></div>').appendTo('body');
			}
			if($("#zoom-marker-2").length == 0) {
				$('<div id="zoom-excluded-area-2" class="zoomExcludedArea"></div>').appendTo("body");
				$('<div class="zoom-marker" id="zoom-marker-2"><div class="zoom-marker-arrow-down"></div><div class="zoom-marker-arrow-up"></div></div>').appendTo("body");
				$('<div id="zoom-marker-tooltip-2" class="zoom-marker-tooltip"><div id="zoom-marker-tooltip-2-arrow-left" class="test-arrow-left"></div><span id="zoom-marker-tooltip-value-2" class="zoom-marker-tooltip-value">-</span><div id="zoom-marker-tooltip-2-arrow-right" class="test-arrow-right"></div></div>').appendTo('body');
			}

			// basic setup for both markers
			$(".zoom-marker-arrow-up").css({ top:(zoomBoxHeight-6) + 'px' });

			// add right click menu if not being defined so far
			if($("#zoom-menu").length == 0) {
				$('<div id="zoom-menu" class="zoom-menu">'
					+ '<div class="first_li">'
					+ 		'<div class="ui-icon ui-icon-zoomin"></div><span>Zoom In</span>'
					+ '</div>'
					+ '<div class="first_li">'
					+ 		'<div class="ui-icon ui-icon-zoomout"></div>'
					+ 		'<span class="zoomContextMenuAction__zoom_out">Zoom Out (2x)</span>'
					+ 		'<div class="inner_li advanced_mode">'
					+ 			'<span class="zoomContextMenuAction__zoom_out__2">2x</span>'
					+ 			'<span class="zoomContextMenuAction__zoom_out__4">4x</span>'
					+ 			'<span class="zoomContextMenuAction__zoom_out__8">8x</span>'
					+ 			'<span class="zoomContextMenuAction__zoom_out__16">16x</span>'
					+ 			'<span class="zoomContextMenuAction__zoom_out__32">32x</span>'
					+ 		'</div>'
					+ '</div>'
					+ '<div class="sep_li"></div>'
					+ '<div class="first_li">'
					+ 		'<div class="ui-icon ui-icon-empty"></div><span>Zoom Mode</span>'
					+ 		'<div class="inner_li">'
					+ 			'<span class="zoomContextMenuAction__set_zoomMode__quick">Quick</span>'
					+ 			'<span class="zoomContextMenuAction__set_zoomMode__advanced">Advanced</span>'
					+ 		'</div>'
					+ '</div>'
					+ '<div class="first_li advanced_mode">'
					+ 		'<div class="ui-icon ui-icon-wrench"></div><span>Settings</span>'
					+ 			'<div class="inner_li">'
					+ 				'<div class="sec_li"><span>Markers</span>'
					+ 					'<div class="inner_li advanced_mode">'
					+ 						'<span class="zoomContextMenuAction__set_zoomMarkers__on">Enabled</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomMarkers__off">Disabled</span>'
					+ 					'</div>'
					+				'</div>'
					+ 				'<div class="sec_li"><span>Timestamps</span></span>'
					+ 					'<div class="inner_li advanced_mode">'
					+ 						'<span class="zoomContextMenuAction__set_zoomTimestamps__on">Enabled</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomTimestamps__off">Disabled</span>'
					+ 					'</div>'
					+				'</div>'
					+ 				'<div class="sep_li"></div>'
					+ 				'<div class="sec_li"><span>Zoom Out Factor</span>'
					+ 					'<div class="inner_li advanced_mode">'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutFactor__2">2x</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutFactor__4">4x</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutFactor__8">8x</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutFactor__16">16x</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutFactor__32">32x</span>'
					+ 					'</div>'
					+ 				'</div>'
					+ 				'<div class="sec_li"><span>Zoom Out Positioning</span>'
					+ 					'<div class="inner_li advanced_mode">'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutPositioning__begin">Begin with</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutPositioning__center">Center</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoomOutPositioning__end">End with</span>'
					+ 					'</div>'
					+				'</div>'
					+ 				'<div class="sec_li"><span>3rd Mouse Button</span>'
					+ 					'<div class="inner_li advanced_mode">'
					+ 						'<span class="zoomContextMenuAction__set_zoom3rdMouseButton__zoom_in">Zoom in</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoom3rdMouseButton__zoom_out">Zoom out</span>'
					+ 						'<span class="zoomContextMenuAction__set_zoom3rdMouseButton__off">Disabled</span>'
					+ 					'</div>'
					+				'</div>'
					+ 			'</div>'
					+ 		'</div>'
					+ '<div class="sep_li"></div>'
					+ '<div class="first_li">'
					+ 		'<div class="ui-icon ui-icon-close"></div><span class="zoomContextMenuAction__close">Close</span>'
					+ '</div>').appendTo('body');
			}
			zoomContextMenu_init();
		}


		/*
		* splits off the parameters of a given url
		*/
		function getUrlVars(url) {
			var parameters = [], name, value;

			urlBaseAndParameters = url.split("?");
			urlBase = urlBaseAndParameters[0];
			urlParameters = urlBaseAndParameters[1].split("&");
			parameters["urlBase"] = urlBase;

			for(var i=0; i<urlParameters.length; i++) {
				parameter = urlParameters[i].split("=");
				parameters[parameter[0]] = parameter[1];
			}
			return parameters;
		}

		/**
		 *
		 * @access public
		 * @return void
		 **/
		function serialize(object){
			var str = "";
			for(var key in object) { str += (key + '=' + object[key] + ','); }
			return str.slice(0, -1);
		}

		/**
		 *
		 * @access public
		 * @return void
		 **/
		function unserialize(string){
			var obj = new Array();
			pairs = string.split(',');

			for(var i=0; i<pairs.length; i++) {
				pair = pairs[i].split("=");
				if(pair[1] == "true") {
					pair[1] = true;
				}else if(pair[1] == "false") {
					pair[1] = false;
				}
				obj[pair[0]] = pair[1];
			}
			return obj;
		}

		/*
		* registers all the different mouse click event handler
		*/
		function init_ZoomAction(image) {

			if(zoomCustomSettings.zoomMode == 'quick') {

				$("#zoomBox").mousedown( function(e) {
					switch(e.which) {
						/* clicking the left mouse button will initiates a zoom-in */
						case 1:
							zoomContextMenu_hide();
							// reset the zoom area
							zoomStartPos = e.pageX;
							if(zoomCustomSettings.zoomMode != 'quick') {
								$("#zoom-marker-1").css({ height:zoomBoxHeight+'px', top:zoomBoxPosTop+'px', left:zoomStartPos+'px', display:'block' });
								$("#zoom-marker-tooltip-1").css({ top:zoomBoxPosTop+'px', left:zoomStartPos+'px'});
								//$(".zoom-marker-tooltip").css({ display:'block' });
							}
							$("#zoomBox").css({ cursor:'e-resize' });
							$("#zoomArea").css({ width:'0px', left:zoomStartPos+'px' });
						break;
					}
				});

			/* register all mouse up events */
			$("#zoomBox").mouseup(function(e) {
				switch(e.which) {
					case 3:
						//zoomAction_zoom_out()();
					break;
				}
			});

			/* register all mouse up events */
			$("body").mouseup( function(e) {
				switch(e.which) {

					/* leaving the left mouse button will execute a zoom in */
					case 1:
						/* execute a simple click if the parent node is an anchor */
					//	if(image.parent().attr("href") !== undefined && zoomStartPos == e.pageX) {
					//		open(image.parent().attr("href"), "_self");
					//		return false;
					//	}

						if(zoomCustomSettings.zoomMode == 'quick' && zoomStartPos != 'none') {
							dynamicZoom(image);
							//$("#zoom-marker-2").css({ height:zoomBoxHeight + 'px', top:zoomBoxPosTop+'px', left:(zoomBoxPosLeft+parseInt(zoomBoxWidth)-1)+'px' });
						}
					break;
				}
			});

			/* stretch the zoom area in that direction the user moved the mouse pointer */
			$("#zoomBox").mousemove( function(e) { drawZoomArea(e) } );

			/* stretch the zoom area in that direction the user moved the mouse pointer.
			   That is required to get it working faultlessly with Opera, IE and Chrome	*/
			$("#zoomArea").mousemove( function(e) { drawZoomArea(e); } );

			/* moving the mouse pointer quickly will avoid that the mousemove event has enough time to actualize the zoom area */
			$("#zoomBox").mouseout( function(e) { drawZoomArea(e) } );














			}else{

				$("#zoomBox").off("mousedown").on("mousedown", function(e) {
					switch(e.which) {
						case 1:
							/* hide context menu if open */
							zoomContextMenu_hide();

							/* find out which marker has to be added */
							if($("#zoom-marker-1").is(":visible") && $("#zoom-marker-2").is(":visible")) {
								/* both markers are in - do nothing */
								return;
							}else {
								var marker = $("#zoom-marker-1").is(":hidden") ? 1 : 2;
								var secondmarker = (marker == 1) ? 2 : 1;
							}


							/* select marker */
							var $this = $("#zoom-marker-" + marker);

							/* place the marker and make it visible */
							$this.css({ height:zoomBoxHeight+'px', top:zoomBoxPosTop+'px', left:e.pageX+'px', display:'block' });

							/* make the excluded areas visible directly in that moment both markers are set */
							if($("#zoom-marker-1").is(":visible") && $("#zoom-marker-2").is(":visible")) {
								zoom.marker[1].left		= $("#zoom-marker-1").position().left;
								zoom.marker[2].left		= $("#zoom-marker-2").position().left;
								zoom.marker.distance	= zoom.marker[1].left - zoom.marker[2].left;

								$("#zoom-excluded-area-1").css({
									height:zoomBoxHeight+'px',
									top:zoomBoxPosTop+'px',
									left: (zoom.marker.distance > 0) ? zoom.marker[1].left : zoomBoxPosLeft,
									width: (zoom.marker.distance > 0) ? zoomBoxPosRight - zoom.marker[1].left : zoom.marker[1].left - zoomBoxPosLeft,
									display:'block'
								});

								$("#zoom-excluded-area-2").css({
									height:zoomBoxHeight+'px',
									top:zoomBoxPosTop+'px',
									left: (zoom.marker.distance < 0) ? zoom.marker[2].left : zoomBoxPosLeft,
									width: (zoom.marker.distance < 0) ? zoomBoxPosRight - zoom.marker[2].left : zoom.marker[2].left - zoomBoxPosLeft,
									display:'block'
								});
							}


							/* make it draggable */
							$this.draggable({
								containment:[ zoomBoxPosLeft-1, 0 , zoomBoxPosLeft+parseInt(zoomBoxWidth), 0 ],
								axis: "x",
								start:
									function(event, ui) {
										$("#zoom-marker-tooltip-" + marker).css({ top: ( (marker == 1) ? zoomBoxPosTop+3 : zoomBoxPosBottom-30 )+'px', left:ui.position["left"]+'px'}).fadeIn(250);
									},
								drag:
									function(event, ui) {
										zoom.marker[marker].left = ui.position["left"];

										/* update the timestamp shown in tooltip */
										$("#zoom-marker-tooltip-value-" + marker).html(
											unixTime2Date(parseInt(parseInt(graphParameters["graph_start"]) + (zoom.marker[marker].left + 1 - zoomBoxPosLeft)*secondsPerPixel)).replace(" ", "<br>")
										);

										zoom.marker[marker].width = $("#zoom-marker-tooltip-" + marker).width();

										/* show the execludedArea if both markers are in */
										if($("#zoom-marker-" + marker).is(":visible") && $("#zoom-marker-" + secondmarker).is(":visible")) {
											zoom.marker.distance = $("#zoom-marker-" + marker).position().left - $("#zoom-marker-" + secondmarker).position().left;

											if( zoom.marker.distance > 0 ) {
												zoom.marker[marker].excludeArea = 'right';
												zoom.marker[secondmarker].excludeArea = 'left';
											}else {
												zoom.marker[marker].excludeArea = 'left';
												zoom.marker[secondmarker].excludeArea = 'right';
											}
										}

										/* let the tooltip follow its marker - this has to be done for both markers too */
										$("#zoom-marker-tooltip-" + marker).css({ left: zoom.marker[marker].left + ( (zoom.marker[marker].excludeArea == 'right') ? (2) : (-2-zoom.marker[marker].width) ) });
										$("#zoom-marker-tooltip-" + secondmarker ).css({ left: zoom.marker[secondmarker].left + ( (zoom.marker[secondmarker].excludeArea == 'right') ? (2) : (-2-zoom.marker[secondmarker].width) ) });

										//$("#zoom-marker-tooltip-1-arrow-left").css({display: (zoom.marker[marker].excludeArea == 'right') ?

										$("#zoom-excluded-area-" + marker).css({ left: (zoom.marker.distance > 0) ? zoom.marker[marker].left : zoomBoxPosLeft, width: (zoom.marker.distance > 0) ? zoomBoxPosRight - zoom.marker[marker].left : zoom.marker[marker].left - zoomBoxPosLeft});
										$("#zoom-excluded-area-" + secondmarker).css({ left: (zoom.marker.distance > 0) ? zoomBoxPosLeft : zoom.marker[secondmarker].left, width: (zoom.marker.distance > 0) ? zoom.marker[secondmarker].left - zoomBoxPosLeft : zoomBoxPosRight - zoom.marker[secondmarker].left});
									},
								stop:
									function(event,ui) {

									}

							});

							break;
						case 2:
							if(zoomCustomSettings.zoom3rdMouseButton != false) {
								zoomContextMenu_hide();
								alert("double");
							}
							break;
					}
					return false;

				});

			}
		}


		/*
		* executes a dynamic zoom in
		*/
		function dynamicZoom(image){

			var newGraphStartTime 	= (zoomAction == 'left2right')
									? parseInt(parseInt(graphParameters["graph_start"]) + (zoomStartPos - zoomBoxPosLeft)*secondsPerPixel)
									: parseInt(parseInt(graphParameters["graph_start"]) + (zoomEndPos - zoomBoxPosLeft)*secondsPerPixel);
			var newGraphEndTime 	= (zoomAction == 'left2right')
									? parseInt(newGraphStartTime + (zoomEndPos-zoomStartPos)*secondsPerPixel)
									: parseInt(newGraphStartTime + (zoomStartPos-zoomEndPos)*secondsPerPixel);

			if(options.inputfieldStartTime != '' & options.inputfieldEndTime != ''){
				$('#' + options.inputfieldStartTime).val(unixTime2Date(newGraphStartTime));
				$('#' + options.inputfieldEndTime).val(unixTime2Date(newGraphEndTime));

				image.unbind();
				$("#zoomBox").unbind();
				$("#zoomArea").unbind();
				$("#zoomBox").remove();
				$("#zoomArea").remove();
				graphUrl		= '';
				localGraphID	= 0;
				imagePosTop		= 0;
				imagePosLeft	= 0;

				zoomBoxPosTop	= 0;
				zoomBoxPosRight	= 0;
				zoomBoxPosLeft	= 0;

				zoomStartPos	= 'none';
				zoomEndPos		= 'none';
				zoomAction		= 'left2right';

				graphWidth		= 0;
				graphParameters = '';

				$("input[name='" + options.submitButton + "']").trigger('click');

				return false;
			}else {
				open(locationBase + "?action=" + graphParameters["action"] + "&local_graph_id=" + graphParameters["local_graph_id"] + "&rra_id=" + graphParameters["rra_id"] + "&view_type=" + graphParameters["view_type"] + "&graph_start=" + newGraphStartTime + "&graph_end=" + newGraphEndTime + "&graph_height=" + graphParameters["graph_height"] + "&graph_width=" + graphParameters["graph_width"] + "&title_font_size=" + graphParameters["title_font_size"], "_self");
			}
		}


		/*
		* converts a Unix time stamp to a formatted date string
		*/
		function unixTime2Date(unixTime){
			var date	= new Date(unixTime*1000);
			var year	= date.getFullYear();
			var month	= ((date.getMonth()+1) < 9 ) ? '0' + (date.getMonth()+1) : date.getMonth()+1;
			var day		= (date.getDate() > 9) ? date.getDate() : '0' + date.getDate();
			var hours	= (date.getHours() > 9) ? date.getHours() : '0' + date.getHours();
			var minutes	= (date.getMinutes() > 9) ? date.getMinutes() : '0' + date.getMinutes();
			var seconds	= (date.getSeconds() > 9) ? date.getSeconds() : '0' + date.getSeconds();

			var formattedTime = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds;
			return formattedTime;
		}


		/*
		* executes a static zoom out (as right click event)
		*/
		function zoomAction_zoom_out(multiplier){

			var timeSpan = (graphParameters["graph_end"] - graphParameters["graph_start"]);
			var secondsPerPixel = timeSpan/graphParameters["graph_width"];

			multiplier--;
			if(zoomCustomSettings.zoomOutPositioning == 'begin') {
				var newGraphStartTime = parseInt(graphParameters["graph_start"]);
				var newGraphEndTime = parseInt(parseInt(graphParameters["graph_end"]) + (multiplier * timeSpan));
			}else if(zoomCustomSettings.zoomOutPositioning == 'end') {
				var newGraphStartTime = parseInt(parseInt(graphParameters["graph_start"]) - (multiplier * timeSpan));
				var newGraphEndTime = parseInt(graphParameters["graph_end"]);
			}else {
				// define the new start and end time, so that the selected area will be centered per default
				var newGraphStartTime = parseInt(parseInt(graphParameters["graph_start"]) - (0.5 * multiplier * timeSpan));
				var newGraphEndTime = parseInt(parseInt(graphParameters["graph_end"]) + (0.5 * multiplier * timeSpan));
			}

			if(options.inputfieldStartTime != '' & options.inputfieldEndTime != ''){
				$('#' + options.inputfieldStartTime).val(unixTime2Date(newGraphStartTime));
				$('#' + options.inputfieldEndTime).val(unixTime2Date(newGraphEndTime));
				$('#' + options.inputfieldStartTime).closest("form").submit();
			}else {
				open(locationBase + "?action=" + graphParameters["action"] + "&local_graph_id=" + graphParameters["local_graph_id"] + "&rra_id=" + graphParameters["rra_id"] + "&view_type=" + graphParameters["view_type"] + "&graph_start=" + newGraphStartTime + "&graph_end=" + newGraphEndTime + "&graph_height=" + graphParameters["graph_height"] + "&graph_width=" + graphParameters["graph_width"] + "&title_font_size=" + graphParameters["title_font_size"], "_self");
			}
		}


		/*
		* updates the css parameters of the zoom area to reflect user's interaction
		*/
		function drawZoomArea(event) {

			if(zoomStartPos == 'none') { return; }

			/* mouse has been moved from right to left */
			if((event.pageX-zoomStartPos)<0) {
				zoomAction = 'right2left';
				zoomEndPos = (event.pageX < zoomBoxPosLeft) ? zoomBoxPosLeft : event.pageX;
				$("#zoomArea").css({ background:'red', left:(zoomEndPos+1)+'px', width:Math.abs(zoomStartPos-zoomEndPos-1)+'px' });
			/* mouse has been moved from left to right*/
			}else {
				zoomAction = 'left2right';
				zoomEndPos = (event.pageX > zoomBoxPosRight) ? zoomBoxPosRight : event.pageX;
				$("#zoomArea").css({ background:'red', left:zoomStartPos+'px', width:Math.abs(zoomEndPos-zoomStartPos-1)+'px' });
			}
			/* move second marker if necessary */
			if(zoomCustomSettings.zoomMode != 'quick') {
				$("#zoom-marker-2").css({ left:(zoomEndPos+1)+'px' });
				$("#zoom-marker-tooltip-2").css({ top:zoomBoxPosTop+'px', left:(zoomEndPos-5)+'px' });
			}
		}

		/**
		 *
		 * @access public
		 * @return void
		 **/
		function zoomContextMenu_init(){

			/* sync menu with cookie parameters */
			$(".zoomContextMenuAction__set_zoomMode__" + zoomCustomSettings.zoomMode).addClass("ui-state-highlight");
			$(".zoomContextMenuAction__set_zoomMarkers__" + ((zoomCustomSettings.zoomMarkers === true) ? "on" : "off") ).addClass("ui-state-highlight");
			$(".zoomContextMenuAction__set_zoomTimestamps__" + ((zoomCustomSettings.zoomTimestamps) ? "on" : "off") ).addClass("ui-state-highlight");
			$(".zoomContextMenuAction__set_zoomOutFactor__" + zoomCustomSettings.zoomOutFactor).addClass("ui-state-highlight");
			$(".zoomContextMenuAction__set_zoomOutPositioning__" + zoomCustomSettings.zoomOutPositioning).addClass("ui-state-highlight");
			$(".zoomContextMenuAction__set_zoom3rdMouseButton__" + ((zoomCustomSettings.zoom3rdMouseButton === false) ? "off" : zoomCustomSettings.zoom3rdMouseButton) ).addClass("ui-state-highlight");

			if(zoomCustomSettings.zoomMode == "quick") {
				$("#zoom-menu > .advanced_mode").hide();
			}else {
				$(".zoomContextMenuAction__zoom_out").text("Zoom Out (" + zoomCustomSettings.zoomOutFactor + "x)");
			}

			/* init click on events */
			$('[class*=zoomContextMenuAction__]').off().on('click', function() {
				var zoomContextMenuAction = false;
				var zoomContextMenuActionValue = false;
				var classList = $(this).attr('class').trim().split(/\s+/);

				$.each( classList, function(index, item){
					if( item.search("zoomContextMenuAction__") != -1) {
						zoomContextMenuActionList = item.replace("zoomContextMenuAction__", "").split("__");
						zoomContextMenuAction = zoomContextMenuActionList[0];
						if(zoomContextMenuActionList[1] == 'undefined' || zoomContextMenuActionList[1] == 'off') {
							zoomContextMenuActionValue = false;
						}else if(zoomContextMenuActionList[1] == 'on') {
							zoomContextMenuActionValue = true;
						}else {
							zoomContextMenuActionValue = zoomContextMenuActionList[1];
						}
						return( false );
					}
				});

				if( zoomContextMenuAction ) {
					if( zoomContextMenuAction.substring(0,8) == "set_zoom") {
						zoomContextMenuAction_set( zoomContextMenuAction.replace("set_zoom", "").toLowerCase(), zoomContextMenuActionValue);
					}else {
						zoomContextMenuAction_do( zoomContextMenuAction, zoomContextMenuActionValue);
					}
				}
			});

			/* init hover events */
			$(".first_li , .sec_li, .inner_li span").hover(
				function () {
					$(this).css({backgroundColor : '#E0EDFE' , cursor : 'pointer'});
					if ( $(this).children().size() >0 )
						if(zoomCustomSettings.zoomMode == "quick") {
							$(this).children('.inner_li:not(.advanced_mode)').show();
						}else {
							$(this).children('.inner_li').show();
						}
					},
				function () {
					$(this).css('background-color' , '#fff' );
					$(this).children('.inner_li').hide();
				}
			);
		};

		/**
		 *
		 * @access public
		 * @return void
		 **/
		function zoomContextMenuAction_set(object, value){
			switch(object) {
				case "mode":
					if( zoomCustomSettings.zoomMode != value) {
						zoomCustomSettings.zoomMode = value;
						$('[class*=zoomContextMenuAction__set_zoomMode__]').toggleClass("ui-state-highlight");

						if(value == "quick") {
							// reset menu
							$("#zoom-menu > .advanced_mode").hide();
							$(".zoomContextMenuAction__zoom_out").text("Zoom Out (2x)");

							zoomCustomSettings.zoomMode			= 'quick';
							$.cookie( options.cookieName, serialize(zoomCustomSettings));
						}else {
							// switch to advanced mode
							$("#zoom-menu > .advanced_mode").show();
							$(".zoomContextMenuAction__zoom_out").text("Zoom Out (" +  + zoomCustomSettings.zoomOutFactor + "x)");

							zoomCustomSettings.zoomMode			= 'advanced';
							$.cookie( options.cookieName, serialize(zoomCustomSettings));
						}
						init_ZoomAction(rrdgraph);
					}
					break;
				case "markers":
					if( zoomCustomSettings.zoomMarkers != value) {
						zoomCustomSettings.zoomMarkers = value;
						$.cookie( options.cookieName, serialize(zoomCustomSettings));
						$('[class*=zoomContextMenuAction__set_zoomMarkers__]').toggleClass('ui-state-highlight');
					}
					break;
				case "timestamps":
					if( zoomCustomSettings.zoomTimestamps != value) {
						zoomCustomSettings.zoomTimestamps = value;
						$.cookie( options.cookieName, serialize(zoomCustomSettings));
						$('[class*=zoomContextMenuAction__set_zoomTimestamps__]').toggleClass('ui-state-highlight');
					}
					break;
				case "outfactor":
					if( zoomCustomSettings.zoomOutFactor != value) {
						zoomCustomSettings.zoomOutFactor = value;
						$.cookie( options.cookieName, serialize(zoomCustomSettings));
						$('[class*=zoomContextMenuAction__set_zoomOutFactor__]').removeClass('ui-state-highlight');
						$('.zoomContextMenuAction__set_zoomOutFactor__' + value).addClass('ui-state-highlight');
						$('.zoomContextMenuAction__zoom_out').text('Zoom Out (' + value + 'x)');
					}
					break;
				case "outpositioning":
					if( zoomCustomSettings.zoomOutPositioning != value) {
						zoomCustomSettings.zoomOutPositioning = value;
						$.cookie( options.cookieName, serialize(zoomCustomSettings));
						$('[class*=zoomContextMenuAction__set_zoomOutPositioning__]').removeClass('ui-state-highlight');
						$('.zoomContextMenuAction__set_zoomOutPositioning__' + value).addClass('ui-state-highlight');
					}
					break;
				case "3rdmousebutton":
					if( zoomCustomSettings.zoom3rdMouseButton != value) {
						zoomCustomSettings.zoom3rdMouseButton = value;
						$.cookie( options.cookieName, serialize(zoomCustomSettings));
						$('[class*=zoomContextMenuAction__set_zoom3rdMouseButton__]').removeClass('ui-state-highlight');
						$('.zoomContextMenuAction__set_zoom3rdMouseButton__' + ((value === false) ? "off" : value)).addClass('ui-state-highlight');
					}
					break;
			}
		}

		function zoomContextMenuAction_do(action, value){
			switch(action) {
				case "close":
					zoomContextMenu_hide();
					break;
				case "zoom_out":
					if(value == undefined) {
						value = (zoomCustomSettings.zoomMode != "quick") ? zoomCustomSettings.zoomOutFactor : 2;
					}
					zoomAction_zoom_out(value);
					break;
			}
		}

		function zoomContextMenu_show(e){
			$("#zoom-menu").css({ left: e.pageX, top: e.pageY, zIndex: '101' }).show();
		};

		function zoomContextMenu_hide(){
			$('#zoom-menu').hide();
		}

	};

})(jQuery);