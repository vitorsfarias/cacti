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

(function($){
	$.fn.ZoomGraph = function(options) {

		/* default values of the different options being offered */
		var defaults = {
			inputfieldStartTime	: '',	// ID of the input field that contains the start date
			inputfieldEndTime	: ''	// ID of the input field that contains the end date
		};

		/* some global variables */
		var graphUrl		= '';
		var localGraphID	= 0;
		var imagePosTop		= 0;
		var imagePosLeft	= 0;

		var zoomBoxPosTop	= 0;
		var zoomBoxPosRight	= 0;
		var zoomBoxPosLeft	= 0;

		var zoomStartPos	= 'none';
		var zoomEndPos		= 'none';
		var zoomAction		= 'zoom-in';

		var graphWidth		= 0;
		var location		= window.location.href.split("?");
		var locationBase	= location[0];
		var graphParameters = '';

		var options 		= $.extend(defaults, options);

		/* support jQuery's concatination */
		return this.each(function() {
			obj = $(this);
			initZoomGraph(obj);
		});

		function initZoomGraph(image) {
			var $this = image;

			$this.mouseenter(
				function(){
					initZoomFunction($this);
				}
			);
		}


		function initZoomFunction(image) {
			var $this = image;

			// get all graph parameters
			graphUrl 			= $this.attr("src");
			graphParameters		= getUrlVars(graphUrl);

			if(zoomStartPos != 'none' && localGraphID != graphParameters["local_graph_id"]) {
				return;
			}

			localGraphID		= graphParameters["local_graph_id"];
			graphStartTime		= graphParameters["graph_start"];
			graphEndTime		= graphParameters["graph_end"];


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
			imagePosTop 		= $this.offset().top;
			imagePosLeft 		= $this.offset().left;

			// define the zoom box
			var zoomBoxWidth	= graphWidth;
			var zoomBoxHeight	= graphHeight;
			var zoomAreaHeight  = graphHeight;

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

			zoomBoxPosRight	= parseInt(imagePosLeft) + parseInt(imageWidth) - 30;
			zoomBoxPosLeft	= parseInt(zoomBoxPosRight) - parseInt(graphWidth);

			// add the zoom box if it has not been created yet.
			if($("#zoomBox").length == 0) {
				/* IE does not fire hover or click behaviors on completely transparent elements.
					Use a background color and set opacity to 1% as a workaround */
				$("<div id='zoomBox' style=' background:blue; z-index:899; filter:alpha(opacity=1); -moz-opacity:0.01; -khtml-opacity:0.01; opacity:0.01; width:0px; height:0px; top:0px; left:0px; position:absolute; overflow:hidden; border:0; padding:0; margin:0'></div>").appendTo("body");
			}

			// reset the zoom box
			$("#zoomBox").css({ cursor:'crosshair', width:zoomBoxWidth + 'px', height:zoomBoxHeight + 'px', top:zoomBoxPosTop+'px', left:zoomBoxPosLeft+'px' });

			// add the zoom area if it has not been created yet.
			if($("#zoomArea").length == 0) {
				$("<div id='zoomArea' style=' cursor:e-resize; background-color:red; height:0px; top:"+zoomBoxPosTop+"px; position:absolute; z-index:900; filter:alpha(opacity=40); -moz-opacity:0.4; -khtml-opacity:0.4; opacity:0.4; overflow:hidden; border:0; padding:0; margin:0'>").appendTo("body");
			}

			// reset the area box
			$("#zoomArea").css({ top:zoomBoxPosTop+'px', height:zoomAreaHeight+'px' });
			initZoomAction(image);
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
				name = parameter[0];
				value = parameter[1];
				parameters.push(name);
				parameters[name] = value;
			}
			return parameters;
		}


		/*
		* registers all the different mouse click event handler
		*/
		function initZoomAction(image) {

			/* register all mouse down events */
			$("#zoomBox").mousedown(function(e) {
				switch(e.which) {

					/* clicking the left mouse button will initiate a dynamic zoom-in / out */
					case 1:
						// reset the zoom area
						zoomStartPos = e.pageX;

						$("#zoomBox").css({ cursor:'e-resize' });
						$("#zoomArea").css({ width:'0px', left:zoomStartPos+'px' });
					break;

					/* middle mouse button does nothing so far*/
					case 2:
					break;

					case 3:
						/* disable the contextmenu dynamically for a right click on that
						 element to avoid that other elements will be affected too.
						 Works faultlessly with IE9 and Chrome. Some issues with FF and unsupported by Opera.
						 Opera users have to press the "ALT" and the right mouse button. */
						$(document).bind("contextmenu",function(e){ e.preventDefault(); });
					break;
				}
			});

			/* register all mouse up events */
			$("#zoomBox").mouseup(function(e) {
				switch(e.which) {
					case 3:
						zoomOut();
					break;
				}
			});

			/* register all mouse up events */
			$("body").mouseup(function(e) {
				switch(e.which) {

					/* leaving the left mouse button will execute a zoom in */
					case 1:
						/* execute a simple click if the parent node is an anchor */
						if(image.parent().attr("href") !== undefined && zoomStartPos == e.pageX) {
							open(image.parent().attr("href"), "_self");
							return false;
						}

						if(zoomStartPos != 'none') { dynamicZoom(image); }
					break;
				}
			});

			/* stretch the zoom area in that direction the user moved the mouse pointer */
			$("#zoomBox").mousemove( function(e) { drawZoomArea(e) } );

			/* stretch the zoom area in that direction the user moved the mouse pointer.
			   That is required to get it working faultlessly with Opera, IE and Chrome	*/
			$("#zoomArea").mousemove( function(e) { drawZoomArea(e) } );

			/* moving the mouse pointer quickly will avoid that the mousemove event has enough time to actualize the zoom area */
			$("#zoomBox").mouseout(function(e) { drawZoomArea(e) } );


		}


		/*
		* executes a dynamic zoom in / out
		*/
		function dynamicZoom(image){

			var timeSpan = graphParameters["graph_end"] - graphParameters["graph_start"];
			var secondsPerPixel = timeSpan/graphParameters["graph_width"];

			var newGraphStartTime 	= (zoomAction == 'zoom-in')
									? parseInt(parseInt(graphParameters["graph_start"]) + (zoomStartPos - zoomBoxPosLeft)*secondsPerPixel)
									: parseInt(parseInt(graphParameters["graph_start"]) - timeSpan);
			var newGraphEndTime 	= (zoomAction == 'zoom-in')
									? parseInt(newGraphStartTime + (zoomEndPos-zoomStartPos)*secondsPerPixel)
									: parseInt(parseInt(graphParameters["graph_end"]) + timeSpan);

			if(options.inputfieldStartTime != '' & options.inputfieldEndTime != ''){
				$('#' + options.inputfieldStartTime).val(unixTime2Date(newGraphStartTime));
				$('#' + options.inputfieldEndTime).val(unixTime2Date(newGraphEndTime));
				$('#' + options.inputfieldStartTime).closest("form").submit();
			}else {
				open(locationBase + "?action=" + graphParameters["action"] + "&local_graph_id=" + graphParameters["local_graph_id"] + "&rra_id=" + graphParameters["rra_id"] + "&view_type=" + graphParameters["view_type"] + "&graph_start=" + newGraphStartTime + "&graph_end=" + newGraphEndTime + "&graph_height=" + graphParameters["graph_height"] + "&graph_width=" + graphParameters["graph_width"] + "&title_font_size=" + graphParameters["title_font_size"] + '#test', "_self");
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
		function zoomOut(){
			var timeSpan = graphParameters["graph_end"] - graphParameters["graph_start"];
			var secondsPerPixel = timeSpan/graphParameters["graph_width"];

			// define the new start and end time, so that the selected area will be centered
			var newGraphStartTime = parseInt(parseInt(graphParameters["graph_start"]) - timeSpan);
			var newGraphEndTime = parseInt(parseInt(graphParameters["graph_end"]) + timeSpan);

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

			/* moving the mouse to the left means zoom out and will be colored blue */
			if((event.pageX-zoomStartPos)<0) {

				zoomAction = 'zoom-out';
				zoomEndPos = (event.pageX < zoomBoxPosLeft) ? zoomBoxPosLeft : event.pageX;

				$("#zoomArea").css({ background:'blue', left:(zoomEndPos+1)+'px', width:Math.abs(zoomStartPos-zoomEndPos-1)+'px' });

			/* moving the mouse to the right means zoom in and will be colored red */
			}else {

				zoomAction = 'zoom-in';
				zoomEndPos = (event.pageX > zoomBoxPosRight) ? zoomBoxPosRight : event.pageX;

				$("#zoomArea").css({ background:'red', left:zoomStartPos+'px', width:Math.abs(zoomEndPos-zoomStartPos-1)+'px' });
			}

		}

	};

})(jQuery);