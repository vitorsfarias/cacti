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

/* graph template stuff */
function toggleGraph(graph_template_id, onCheck) {
	if (isGraphCreated(graph_template_id)) { return; }

	msgid  = document.getElementById('cg_'     + graph_template_id);
	lineid = document.getElementById('gt_line' + graph_template_id);

	if (!onCheck) {
		msgid.checked = !msgid.checked;
	}

	if (msgid.checked) {
		lineid.style.backgroundColor = 'khaki';
	}else{
		lineid.style.backgroundColor = '';
	}
}

function isGraphCreated(graph_template_id) {
	for (var i = 0; i < gt_created_graphs.length; i++) {
		if (gt_created_graphs[i] == graph_template_id) {
			return true;
		}
	}

	return false;
}

function setGraphStatus() {
	for (var i = 0; i < gt_created_graphs.length; i++) {
		$("#gt_line"+gt_created_graphs[i]).attr('name', 'graphing').css('background-color', '#FFFFFF').children().css('color', '#999999');
		$("#cg_"+gt_created_graphs[i]).hide().attr('checked', false);
	}
}

function selectAllGraphs(prefix, checkbox_state) {
	if (checkbox_state) {
		$("input[id^="+prefix+"]").attr('checked', true);
		$("tr[id^=gt_line]").css('background-color', 'khaki');
	}else{
		$("input[id^="+prefix+"]").attr('checked', false);
		$("tr[id^=gt_line]").css('background-color', '');
	}
	setGraphStatus();
}

/* general id based selects */
function selectLine(id, update) {
	msgid  = document.getElementById('chk_' + id);
	lineid = document.getElementById('line'+ id);

	if (!update) msgid.checked = !msgid.checked;

	if (msgid.checked) {
		lineid.style.backgroundColor = 'khaki';
	}else{
		lineid.style.backgroundColor = '';
	}
}

function selectAll(prefix, checkbox_state) {
	if (checkbox_state) {
		$("input[id^="+prefix+"]").attr('checked', true);
		$("tr[id^=line]").css('background-color', 'khaki');
		$("tr[name^=disabled]").css('background-color', '');
	}else{
		$("input[id^="+prefix+"]").attr('checked', false);
		$("tr[id^=line]").css('background-color', '');
	}
}

/* data query stuff */
function toggleDataQueryGraph(snmp_query_id, snmp_index, onCheck) {
	if (isDataQueryTemplateGraphed(snmp_query_id, snmp_index)) { return; }

	msgid  = document.getElementById('sg_' + snmp_query_id + '_' + snmp_index);
	lineid = document.getElementById('line'+ snmp_query_id + '_' + snmp_index);

	if (!onCheck) {
		msgid.checked = !msgid.checked;
	}

	if (msgid.checked) {
		lineid.style.backgroundColor = 'khaki';
	}else{
		lineid.style.backgroundColor = '';
	}
}

function isDataQueryTemplateGraphed(snmp_query_id, snmp_index) {
	var snmp_query_graph_id = document.getElementById('sgg_' + snmp_query_id).value;

	for (var i = 0; i < created_graphs[snmp_query_graph_id].length; i++) {
		if (created_graphs[snmp_query_graph_id][i] == snmp_index) {
			return true;
		}
	}

	return false;
}

function setDataQueryGraphStatus(snmp_query_id) {
	resetDataQueryGraphStatus(snmp_query_id);

	var snmp_query_graph_id = document.getElementById('sgg_' + snmp_query_id).value;

	for (var i = 0; i < created_graphs[snmp_query_graph_id].length; i++) {
		$("#line"+snmp_query_id+"_"+created_graphs[snmp_query_graph_id][i]).css('background-color', '').attr('name', 'disabled_line').children().css('color', '#999999');
		$("#sg_"+snmp_query_id+"_"+created_graphs[snmp_query_graph_id][i]).hide().attr('checked', false);
	}
}

function resetDataQueryGraphStatus(snmp_query_id) {
	$("tr[id^=line"+snmp_query_id+"]").attr('name', 'enabled').css('background-color', '').children().css('color', '');
	$("input[id^=sg_"+snmp_query_id+"]").show().attr('checked', false);
	$("input[name^=all_"+snmp_query_id+"]").attr('checked', false);
}

function selectAllDataQueries(data_query_id, checkbox_state) {
	if (checkbox_state) {
		$("input[id^=sg_"+data_query_id+"]").attr('checked', true);
		$("tr[id^=line"+data_query_id+"]").css('background-color', 'khaki');
		$("tr[name^=disabled]").css('background-color', '');
	}else{
		$("input[id^=sg_"+data_query_id+"]").attr('checked', false);
		$("tr[id^=line"+data_query_id+"]").css('background-color', '');
	}
}

function setSelections() {
	for (var i = 0; i < document.chk.elements.length; i++) {
		lineid = document.getElementById('line'+ document.chk.elements[i].name.substr(4));

		if (document.chk.elements[i].checked) {
			if ( lineid ) { lineid.style.backgroundColor = 'khaki'; }
		}else{
			if ( lineid ) { lineid.style.backgroundColor = ''; }
		}
	}
}

function navigation_select(name, location) {
	createCookie("navbar_id", name);

	document.location = location;
}

function htmlStartBoxFilterChange(id, initialize) {
	filter = readCookieElement("formvis", id);

	if (filter == "o") {
		if (initialize != null) {
			/* do nothing we want to stay the same */
		}else{
			createCookieElement("formvis", id, "c");
			filter = "c";
		}
	}else{
		if (initialize != null) {
			if (filter == "c") {
				/* do nothing we want to stay the same */
			}else{
				createCookieElement("formvis", id, "o");
				filter = "o";
			}
		}else{
			createCookieElement("formvis", id, "o");
			filter = "o";
		}
	}

	if (filter == "c") {
		document.getElementById(id).style.display  = "none";
		document.getElementById(id+'_twisty').src = "images/tw_close.gif";
	}else{
		document.getElementById(id).style.display  = "";
		document.getElementById(id+'_twisty').src = "images/tw_open.gif";
	}
}

function changeMenuState(id, initialize) {
	var filter = readCookieElement("menu", id);
	var object = document.getElementById("ul_"+id);

	if (filter == "o") {
		if (initialize != null) {
			createCookieElement("menu", id, "o");
		}else{
			createCookieElement("menu", id, "c");
			closeMenu(id);
		}
	}else{
		if (initialize != null) {
			if (filter == "c") {
				createCookieElement("menu", id, "c");
				hideMenu(id);
			}else{
				createCookieElement("menu", id, "o");
			}
		}else{
			createCookieElement("menu", id, "o");
			openMenu(id);
		}
	}
}

function closeMenu(id) {
	$("#ul_"+id).slideUp("fast");
}

function hideMenu(id) {
	$("#ul_"+id).hide();
}

function openMenu(id) {
	$("#ul_"+id).slideDown("fast");
}

var objTh           = null;
var objDiv          = null;
var overColumn      = false;
var overVSplit      = false;
var iEdgeThreshold  = 10;
var isMouseDown     = false;
var vSplitterClosed = false;
var creatingCookie  = false;
var browser         = "Unknown";
var browserVersion  = 0;

/* tells if on the right border or not */
function isOnBorderRight(type, object, event) {
	var width    = object.offsetWidth;
	var pos      = findPos(object);
	var absRight = pos[0] + width;

	if (object.id != 'checkbox' && $('#'+object.id).next("th").attr('id') != 'checkbox') {
		if (event.clientX > (absRight - iEdgeThreshold)) {
			if (type == "column") {
				objTh = object;
				objThWidth = width - iEdgeThreshold;
			}
			return true;
		}
	}

	return false;
}

function findPos(obj) {
	var curleft = curtop = 0;

	if (obj.offsetParent) {
		curleft = obj.offsetLeft;
		curtop  = obj.offsetTop;

		while (obj = obj.offsetParent) {
			curleft += obj.offsetLeft;
			curtop  += obj.offsetTop;
		}
	}

	return [curleft,curtop];
}

/* tells if on the bottom border or not */
function isOnBorderBottom(object, event) {
	var height = object.offsetHeight;
	var pos    = findPos(object);
	var absTop = pos[1];

	if (event.clientY > (absTop + object.offsetHeight - iEdgeThreshold)) {
		return true;
	}

	return false;
}

function getParentNode(objReference, nodeName, className) {
	var oElement = objReference;
	while (oElement != null && oElement.tagName != null && oElement.tagName != "BODY") {
		if (oElement.tagName.toUpperCase() == nodeName &&
			(className == null || oElement.className.search("\b"+className+"\b") != 1)) {
			return oElement;
		}

		oElement = oElement.parentNode;
	}

	return null;
}

function doColResize(object, event){
	if(!event) event = window.event;

	if ($("#"+object.id).parents("table").hasClass("resizable")) {
		if (!isMouseDown) {
			if (isOnBorderRight("column", object, event)) {
				overColumn          = true;
				$("th").css("cursor", "e-resize");
			}else{
				overColumn          = false;
				$("th").css("cursor", "");
				$("th > a").css("cursor", "");
			}
		} else {
			$("th > a").css("cursor", "e-resize");
		}
	}else{
		overColumn = false;
	}

	return overColumn;
}

function doneColResize() {
	overColumn = false;
	$("th > a").css("cursor", "");
	saveColumnWidths();
}

function initResizableColumns() {
	columns = document.getElementsByTagName("th");

	pathname = getBaseName();

	$('th').mousemove(function(e) {
		doColResize(this,e);
	});

	$('th > a').mousedown(function(e) {
		return false;
	});

	if (columns.length > 0) {
		for (i = 0; i < columns.length; i++) {
			cur_value = readCookieElement(pathname, columns[i].id);

			if (cur_value) {
				if (cur_value != "NaN") {
					columns[i].style.width = cur_value - 8 + "px";
				}
			} else {
				try { eval("value=sess_cacti_ui_"+pathname); }
				catch(err) { value = null }
				if (value) {
					cur_value = readVariableAsCookie(value, columns[i].id);

					if (cur_value && cur_value != "NaN") {
						columns[i].style.width = cur_value - 8 + "px";
					}
				}
			}
		}

		saveColumnWidths();
	}
}

function getBaseName() {
	var pathname = location.pathname;

	while (pathname.indexOf("/") >= 0) {
		pathname = "ui_" + pathname.substring(pathname.indexOf("/")+1);
	}

	return pathname.replace(".php", "");
}

function saveColumnWidths() {
	columns = document.getElementsByTagName("th");
	pathname = getBaseName();
	for (i = 0; i < columns.length; i++) {
		width1 = columns[i].clientWidth;
		width2 = parseInt(columns[i].style.width);
		width3 = columns[i].scrollWidth;
		if (width2 < width3) {
			width = width3;
		}else{
			width = width2;
		}
		//alert("clientWidth:"+width1+", width:"+width2+", scroll:"+width3);
		createCookieElement(pathname, columns[i].id, width);
	}
}

function doDivResize(object, event){
	if (!event) event = window.event;

	if (!isMouseDown) {
		if (isOnBorderRight("div", object, event)) {
			overVSplit          = true;
			object.style.cursor = "e-resize";
		}else{
			overVSplit          = false;
			object.style.cursor = "";
		}
	}

	return overColumn;
}

function doneDivResize(){
	overVSplit = false;

	if (document.getElementById("vsplitter")) {
		createCookieElement("menu", "vsplitter_last", parseInt(document.getElementById("vsplitter").style.marginLeft));
	}
}

function vSplitterToggle() {
	if (vSplitterClosed) {
		vSplitterClosed = false;
		createCookieElement("menu", "vsplitter_status", "0");
	}else{
		vSplitterClosed = true;
		createCookieElement("menu", "vsplitter_status", "1");
	}

	vSplitterPos();
}

function mouseDown(event) {
	if (!event) event = window.event;

	startX = event.clientX;
	startY = event.clientY;

	if (overColumn) {
		isMouseDown   = true;
	} else if (overVSplit) {
		if (event.srcElement) {
			objDiv = event.srcElement;
		} else if (event.target) {
			objDiv = event.target;
		} else {
			return;
		}

		objDiv = getParentNode(objDiv,"DIV");

		if (objDiv == null) return;

		objDivWidth   = objDiv.offsetLeft;
		isMouseDown   = true;
	}
}

function mouseMove(event) {
	if (!event) event = window.event;

	/* let's see how wide the page is */
	var clWidth = document.getElementById("wrapper").clientWidth;

	if (isMouseDown) {
		if (objTh) {
			minX    = $("#"+objTh.id+" > a").width();
			thSt    = parseInt(event.clientX - startX + objThWidth);

			/* check for minimum width */
			if (thSt >= 10 && objTh) {
				objTh.style.width    = thSt + "px";
				//$('#debug').text("Name:"+objTh.id+", MinX:"+minX+", ThWidth:"+objThWidth+", New Width:"+thSt);
			}

			if ((browser == 'IE') && (document.selection)) {
				document.selection.empty();
			}else if (window.getSelection()) {
				window.getSelection().removeAllRanges();
			}
		}else if (objDiv) {
			divSt = event.clientX - startX + objDivWidth;
			if (divSt >=30 ) {
				vSplitterClosed = false;
				$("#vsplitter").css("marginLeft", divSt + "px");
				$("#menu").css("width", parseInt(divSt - 5) + "px").css("marginLeft", "0px");
				$("#content").css("width", parseInt(clWidth - divSt - 20) + "px").css("left", parseInt(divSt + 2) + "px");
				$("#graph_tree").css("width", parseInt(divSt - 5) + "px").css("marginLeft", "0px");
				$("#graph_tree_content").css("width", parseInt(clWidth - divSt - 20) + "px").css("left", parseInt(divSt + 2) + "px");

			}else{
				vSplitterClosed = true;
				$("#vsplitter").css("marginLeft", "0px");
				$("#menu").css("width", "0px").css("marginLeft", "-200px");
				$("#content").css("left", "2px").css("width", parseInt(clWidth + 200) + "px");
				$("#graph_tree").css("width", "0px").css("marginLeft", "-200px");
				$("#graph_tree_content").css("left", "2px").css("width", parseInt(clWidth + 200) + "px");
			}

			if ((browser == 'IE') && (document.selection)) {
				document.selection.empty();
			}else if (window.getSelection) {
				window.getSelection().removeAllRanges();
			}
		}
	}
}

function mouseUp(event) {
	if (!event) event = window.event;

	if (objTh) {
		if ((browser == 'IE') && (document.selection)) {
			document.selection.empty();
		} else if (window.getSelection) {
			window.getSelection().removeAllRanges();
		}

		objTh = null;
		doneColResize();
	} else if (objDiv) {
		if ((browser == 'IE') && (document.selection)) {
			document.selection.empty();
		}else if (window.getSelection) {
			window.getSelection().removeAllRanges();
		}

		objDiv = null;
		doneDivResize();
	}

	isMouseDown = false;
}

/* page load functions */
function setFocus() {
	var inputs = document.getElementsByTagName("input");
	var found  = false;
	var hfound = false;
	var x      = 0;

	while (true) {
		if (x == 0) {
			for (var i=0; i < inputs.length; i++) {
				switch (inputs[i].type) {
				case "text":
					inputs[i].focus();
					found = true;

					break;
				}

				if (found) {
					break;
				}
			}
		}else{
			for (var i=0; i < inputs.length; i++) {
				switch (inputs[i].type) {
				case "image":
				case "password":
				case "file":
					inputs[i].focus();
					found = true;

					break;
				case "hidden":
					hid_count = i;
					hfound    = true;

					break;
				default:
				}

				if (found) {
					break;
				}
			}
		}

		x++;
		if (x > 1 || found) {
			break;
		}
	}
}

function vSplitterEm() {
	document.getElementById("vsplitter_toggle").style.backgroundColor = 'yellow';
}

function vSplitterUnEm() {
	document.getElementById("vsplitter_toggle").style.backgroundColor = 'white';
}

function vSplitterPos() {
	var divSt        = parseInt(readCookieElement("menu", "vsplitter_last"), 10);
	var vertical_pos = 0;
	var marginLeft;
	var menuWidth;

	/* let's see how wide the page is */
	var clWidth = document.getElementById("wrapper").clientWidth;

	vSplitterClosed = parseInt(readCookieElement("menu", "vsplitter_status"), 10);

	if (!divSt) {
		divSt = 165;
	}

	menuWidth  = divSt - 5;
	marginLeft = divSt + 2;

	if (document.getElementById("vsplitter")) {
		if (vSplitterClosed == 1) {
			if (document.getElementById("menu") != null) {
				document.getElementById("vsplitter").style.marginLeft = "0px";
				document.getElementById("menu").style.width           = "0px";
				document.getElementById("menu").style.marginLeft      = "-200px";
				document.getElementById("content").style.left         = "2px";
				document.getElementById("content").style.width        = parseInt(clWidth - 20) + "px";
			}else{
				document.getElementById("vsplitter").style.marginLeft     = "0px";
				document.getElementById("graph_tree").style.width         = "0px";
				document.getElementById("graph_tree").style.marginLeft    = "-200px";
				document.getElementById("graph_tree_content").style.left  = "2px";
				document.getElementById("graph_tree_content").style.width = parseInt(clWidth - 20) + "px";
			}
		}else{
			if (document.getElementById("menu") != null) {
				document.getElementById("vsplitter").style.marginLeft = divSt      + "px";
				document.getElementById("menu").style.width           = menuWidth  + "px";
				document.getElementById("menu").style.marginLeft      = "0px";
				document.getElementById("content").style.left         = marginLeft + "px";
				document.getElementById("content").style.width        = parseInt(clWidth - divSt - 20) + "px";
			}else{
				document.getElementById("vsplitter").style.marginLeft     = divSt      + "px";
				document.getElementById("graph_tree").style.width         = menuWidth  + "px";
				document.getElementById("graph_tree").style.marginLeft    = "0px";
				document.getElementById("graph_tree_content").style.left  = marginLeft + "px";
				document.getElementById("graph_tree_content").style.width = parseInt(clWidth - divSt - 20) + "px";
			}
		}
	}

	if (document.getElementById('vsplitter_toggle')) {
		if (document.getElementById('content')) {
			vertical_pos = parseInt(document.getElementById('content').clientHeight) / 2;

			document.getElementById('vsplitter_toggle').style.marginTop = vertical_pos + "px";
		}else if (document.getElementById('graph_tree_content')) {
			vertical_pos = parseInt(document.getElementById('graph_tree_content').clientHeight) / 2;

			document.getElementById('vsplitter_toggle').style.marginTop = vertical_pos + "px";
		}
	}
}

$(window).resize(function() {
	/* initialize the page splitter as required */
	vSplitterPos();

	/* fix browser quirks */
	fixBrowserQuirks();

	/* size the content divs */
	sizeContentDivs();
});

$().ready(function() {
	/* detect the browser type */
	detectBrowser();

	/* initialize mouse functions */
	document.onmousedown = mouseDown;
	document.onmousemove = mouseMove;
	document.onmouseup   = mouseUp;

	/* set document focus */
	setFocus();

	/* fix browser quirks */
	fixBrowserQuirks();

	/* size the content divs */
	sizeContentDivs();

	/* initialize the page splitter as required */
	vSplitterPos();

	/* restore column widths */
	initResizableColumns();

	/* restore the page visibility */
	transitionPage();
});

function sizeContentDivs() {
	var top    = document.getElementById("wrapper").offsetTop;
	var bottom = document.getElementById("wrapper").clientHeight;

	/* IE6 will enter infinite loop here */
	if (browser != "IE" || (browser == "IE" && browserVersion != 6)) {
		/* size the menu first */
		if (document.getElementById("menu")) {
			document.getElementById("menu").style.height = parseInt(bottom-top) + "px";
		}

		/* size the remaining content */
		if (document.getElementById("content")) {
			document.getElementById("content").style.height = parseInt(bottom-top) + "px";
		}else if(document.getElementById("graph_tree_content")) {
			document.getElementById("graph_tree_content").style.height = parseInt(bottom-top) + "px";
		}else{
			document.getElementById("graph_content").style.height = parseInt(bottom-top) + "px";
		}
	}
}

function transitionPage() {
	if (browser != "IE") {
		if (document.getElementById("graph_tree")) {
			document.getElementById("graph_tree").style.opacity         = 1;
			document.getElementById("graph_tree_content").style.opacity = 1;
			document.getElementById("wrapper").style.opacity            = 1;
		}else if (document.getElementById("graph_content")) {
			document.getElementById("graph_content").style.opacity      = 1;
			document.getElementById("wrapper").style.opacity            = 1;
		}else {
			document.getElementById("menu").style.opacity    = 1;
			document.getElementById("content").style.opacity = 1;
			document.getElementById("wrapper").style.opacity = 1;
		}
	}
}

function fixBrowserQuirks() {
	var window_height = document.getElementById("wrapper").clientHeight;
	var myDiv;

	if (browser == "IE") {
		if (document.getElementById("content") != null) {
			myDiv = document.getElementById("content");
		}else if (document.getElementById("graph_tree")) {
			myDiv = document.getElementById("graph_tree_content");
		}else if (document.getElementById("graph_content")) {
			myDiv = document.getElementById("graph_content");
		}

		if (myDiv.scrollHeight > window_height) {
			myDiv.style.paddingRight = "30px";
			myDiv.style.overflowX   = "hidden";
		}
	}else if (browser == "FF") {
		if (document.getElementById("content") != null) {
			myDiv = document.getElementById("content");
		}else if (document.getElementById("graph_tree")) {
			myDiv = document.getElementById("graph_tree_content");
		}else if (document.getElementById("graph_content")) {
			myDiv = document.getElementById("graph_content");
		}

		if (myDiv.scrollHeight <= window_height) {
			myDiv.style.paddingRight = "10px";
			myDiv.style.overflowX   = "hidden";
		}
	}
}

function detectBrowser() {
	if (navigator.userAgent.indexOf('MSIE 6.0') >= 0) {
		browser = "IE";
		browserVersion = 6;
	}else if (navigator.userAgent.indexOf('MSIE 7.0') >= 0) {
		browser = "IE";
		browserVersion = 7;
	}else if (navigator.userAgent.indexOf('MSIE 8.0') >= 0) {
		browser = "IE";
		browserVersion = 8;
	}else if (navigator.userAgent.indexOf('Mozilla') >= 0) {
		browser = "FF";
	}else if (navigator.userAgent.indexOf('Opera') >= 0) {
		browser = "Opera";
	}else{
		browser = "Other";
	}
}

/* Cookie Functions */
function createCookie(name, value, days) {
	if (!creatingCookie) {
		creatingCookie = true;

		if (days) {
			var date    = new Date();
			date.setTime(date.getTime() + (days*24*60*60*1000));
			var expires = "; expires=" + date.toGMTString();
		} else {
			var expires = "";
		}

		document.cookie  = name + "=" + value + expires + "; path=/";

		creatingCookie = false;
	}
}

function readCookie(name) {
	var nameEQ = name + "=";

	var ca     = document.cookie.split(';');

	for (var i=0; i < ca.length; i++) {
		var c = ca[i];

		while (c.charAt(0) == ' ') {
			c = c.substring(1, c.length);
		}

		if (c.indexOf(nameEQ) == 0) {
			return c.substring(nameEQ.length, c.length);
		}
	}

	return null;
}

function eraseCookie(name) {
	createCookie(name, "", -1);
}

/* cookie container functions */
function readVariableAsCookie(elements, element) {
	var search_for     = element + "@@";
	var return_value   = null;
	var end_location   = -1;

	if (elements) {
		var start_location = elements.indexOf(search_for);

		if (start_location >= 0) {
			end_location = elements.indexOf("!", start_location);

			if (end_location >= 0) {
				return_value = elements.substring(start_location + search_for.length, end_location);
			}else{
				return_value = elements.substring(start_location + search_for.length);
			}
		}
	}

	return return_value;
}

function readCookieElement(name, element) {
	elements           = readCookie(name);
	var search_for     = element + "@@";
	var return_value   = null;
	var end_location   = -1;

	if (elements) {
		var start_location = elements.indexOf(search_for);

		if (start_location >= 0) {
			end_location = elements.indexOf("!", start_location);

			if (end_location >= 0) {
				return_value = elements.substring(start_location + search_for.length, end_location);
			}else{
				return_value = elements.substring(start_location + search_for.length);
			}
		}
	}

	return return_value;
}

function createCookieElement(name, element, value) {
	if (readCookieElement(name, element)) {
		updateCookieElement(name, element, value);
	}else{
		appendCookieElement(name, element, value);
	}
}

function appendCookieElement(name, element, value) {
	elements = readCookie(name);

	if (elements) {
		elements = elements + "!" + element + "@@" + value;
	}else{
		elements = element + "@@" + value;
	}

	createCookie(name, elements);
}

function updateCookieElement(name, element, value) {
	var elements       = readCookie(name);
	var new_elements   = "";
	var remainder      = "";
	var start_location = 0;

	if (elements) {
		start_location = elements.indexOf(element + "@@");

		if (start_location >= 0) {
			new_elements = elements.substring(0, start_location);

			if (new_elements.substring(new_elements.length - 1) == "!") {
				new_elements = new_elements.substring(0, new_elements.length - 1);
			}

			remainder = elements.indexOf("!", start_location);

			if (remainder > 0) {
				new_elements = new_elements + elements.substring(remainder);
			}

			if (new_elements.substring(new_elements.length, 1) == "!") {
				new_elements = new_elements + element + "@@" + value;
			}else{
				new_elements = new_elements + "!" + element + "@@" + value;
			}
		}else{
			new_elements = elements + "!" + element + "@@" + value;
		}
	}else{
		new_elements = element + "@@" + value;
	}

	createCookie(name, new_elements);
}
