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

include_once(CACTI_BASE_PATH . "/include/graph/graph_constants.php");
include_once(CACTI_BASE_PATH . "/lib/time.php");
include_once(CACTI_BASE_PATH . "/lib/graph_view.php");

global $lang2locale;

$using_guest_account = false;
$show_console_tab = true;

/* ================= input validation ================= */
input_validate_input_number(get_request_var_request("local_graph_id"));
/* ==================================================== */

if (read_config_option("auth_method") != AUTH_METHOD_NONE) {
	/* at this point this user is good to go... so get some setting about this
	user and put them into variables to save excess SQL in the future */
	$current_user = db_fetch_row("select * from user_auth where id=" . $_SESSION["sess_user_id"]);

	/* find out if we are logged in as a 'guest user' or not */
	if (db_fetch_cell("select id from user_auth where username='" . read_config_option("guest_user") . "'") == $_SESSION["sess_user_id"]) {
		$using_guest_account = true;
	}

	/* find out if we should show the "console" tab or not, based on this user's permissions */
	if (sizeof(db_fetch_assoc("select realm_id from user_auth_realm where realm_id=8 and user_id=" . $_SESSION["sess_user_id"])) == 0) {
		$show_console_tab = false;
	}
}

/* use cached url if available and applicable */
if ((isset($_SESSION["sess_graph_view_url_cache"])) &&
	(empty($_REQUEST["action"])) &&
	(basename($_SERVER["PHP_SELF"]) == "graph_view.php") &&
	(preg_match("/action=(tree|preview|list)/", $_SESSION["sess_graph_view_url_cache"]))) {

	header("Location: " . $_SESSION["sess_graph_view_url_cache"]);
}

/* store the current tab */
load_current_session_value("toptab", "sess_cacti_toptab", "graphs");
load_current_session_value("action", "sess_cacti_topaction", read_graph_config_option("default_view_mode"));

/* need to correct $_SESSION["sess_nav_level_cache"] in zoom view */
if ($_REQUEST["action"] == "zoom") {
	$_SESSION["sess_nav_level_cache"][2]["url"] = htmlspecialchars("graph.php?local_graph_id=" . $_REQUEST["local_graph_id"] . "&rra_id=all");
}

/* setup tree selection defaults if the user has not been here before */
if (($_REQUEST["action"] == "tree") &&
	(!isset($_GET["leaf_id"])) &&
	(!isset($_SESSION["sess_has_viewed_graphs"]))) {

	$_SESSION["sess_has_viewed_graphs"] = true;

	$first_branch = find_first_folder_url();

	if (!empty($first_branch)) {
		header("Location: $first_branch");
	}
}

$page_title = plugin_hook_function('page_title', draw_navigation_text("title"));

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title><?php echo $page_title; ?></title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<?php
	if (isset($_SESSION["custom"]) && $_SESSION["custom"] == true) {
		print "<meta http-equiv=refresh content='99999'>\r\n";
	}else if (isset($_SESSION["action"]) && $_SESSION["action"] == 'zoom') {
		print "<meta http-equiv=refresh content='99999'>\r\n";
	}else{
		$refresh = plugin_hook_function('top_graph_refresh', '0');

		if ($refresh > 0) {
			print "<meta http-equiv=refresh content='" . htmlspecialchars($refresh,ENT_QUOTES) . "'>\r\n";
		}
	}
	?>
	<meta http-equiv='Content-Type' content='text/html;charset=utf-8'>
	<meta http-equiv='Content-Script-Type' content='text/javascript'>
	<meta http-equiv='Content-Style-Type' content='text/css'>
	<link type='text/css' href='<?php echo CACTI_URL_PATH; ?>include/main.css' rel='stylesheet'>
	<link type='text/css' href='<?php echo CACTI_URL_PATH; ?>include/dd.css' rel='stylesheet'>
	<link type='text/css' href='<?php echo CACTI_URL_PATH; ?>include/jquery.autocomplete.css' rel='stylesheet'>
	<link type='text/css' media='screen' href='<?php echo CACTI_URL_PATH; ?>include/css/colorpicker.css' rel='stylesheet'>
	<link type='text/css' media='screen' href='<?php echo CACTI_URL_PATH; ?>include/css/cacti_dd_menu.css' rel='stylesheet'>
	<link type='text/css' media='screen' href='<?php echo CACTI_URL_PATH; ?>include/css/jquery-ui.css' rel='stylesheet'>
	<link type="text/css" media="screen" href="<?php echo CACTI_URL_PATH; ?>include/css/jquery-ui-timepicker.css" rel="stylesheet">
	<link href='<?php echo CACTI_URL_PATH; ?>images/favicon.ico' rel='shortcut icon'>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/js/jquery/jquery.js'></script>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/js/jquery/jquery-ui.js'></script>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/js/layout.js'></script>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/layout.php'></script>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/js/jquery/jquery.jstree.js'></script>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/js/jquery/jquery.cookie.js'></script>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/js/jquery/jquery.zoom.js'></script>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/js/jquery/jquery.autocomplete.js'></script>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/js/jquery/colorpicker.js'></script>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/js/jquery/jquery.dd.js'></script>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/js/jquery/jquery.dropdown.js'></script>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/js/jquery/jquery.tablednd.js'></script>
	<script type='text/javascript' src='<?php echo CACTI_URL_PATH; ?>include/js/jquery/jquery.timepicker.js'></script>
	<script type="text/javascript" src="<?php echo CACTI_URL_PATH; ?>include/js/jquery/locales/LC_MESSAGES/jquery.ui.datepicker-<?php print (read_config_option('i18n_language_support') != 0) ? CACTI_LANGUAGE_FILE : "english_usa";?>.js"></script>
	<?php initializeCookieVariable(); plugin_hook('page_head'); ?>
</head>
<body id='body'>
<div id='header'>
	<div id='logobar'></div>
	<div id='navbar'>
		<div id='navbar_l'>
			<ul>
				<?php echo draw_header_tab("console", __("Console"), CACTI_URL_PATH . "index.php");?>
				<?php echo draw_header_tab("graphs", __("Graphs"), CACTI_URL_PATH . "graph_view.php");?>
				<?php plugin_hook('top_graph_header_tabs');?>
			</ul>
		</div>
		<div id='navbar_r'>
			<ul>
				<?php if (preg_match("/(graphs|graph_settings|tree|preview|list)/", get_request_var_request("toptab"))) { ?>
				<?php echo draw_header_tab("graph_settings", __("Settings"), CACTI_URL_PATH . "graph_settings.php");?>
				<?php echo draw_header_tab("tree", __("Tree"), CACTI_URL_PATH . "graph_view.php?action=tree", CACTI_URL_PATH . "images/tab_mode_tree_new.gif");?>
				<?php echo draw_header_tab("list", __("List"), CACTI_URL_PATH . "graph_view.php?action=list", CACTI_URL_PATH . "images/tab_mode_list_new.gif");?>
				<?php echo draw_header_tab("preview", __("Preview"), CACTI_URL_PATH . "graph_view.php?action=preview", CACTI_URL_PATH . "images/tab_mode_preview_new.gif");?>
				<?php }else{ plugin_hook('top_graph_header_tabs_right'); }?>
			</ul>
		</div>
	</div>
	<div id='navbrcrumb'>
		<div id='brcrumb' style='float:left'>
			<?php echo draw_navigation_text();?>
		</div>
		<div style='float:right'>
			<a href='<?php echo cacti_wiki_url();?>' target='_blank'>
			<img src='<?php echo CACTI_URL_PATH; ?>images/help.gif' title='<?php print __("Help");?>' alt='<?php print __("Help");?>' align='top'>
			</a>
		</div>
		<div style='float:right'>
		<?php	if (read_config_option("auth_method") != AUTH_METHOD_NONE) {
					if(read_config_option('i18n_timezone_support') != 0) {
						?><span id='menu_timezones' class='cacti_dd_link'><span id='date_time_format'><strong><?php echo __date("D, " . date_time_format() . " T");?></strong></span></span><?php
					}else {
						?><span id='date_time_format'><strong><?php echo __date("D, " . date_time_format() . " T");?></strong></span><?php
					}
				?>
					&nbsp;&nbsp;&nbsp;<?php print __("Logged in as");?> <strong><?php print db_fetch_cell("select username from user_auth where id=" . $_SESSION["sess_user_id"]);?></strong> (<a href='<?php echo CACTI_URL_PATH; ?>logout.php'><?php print __("Logout");?></a>)
		<?php } ?>
		</div>
		<?php if(read_config_option('i18n_language_support') != 0) {?>
		<div style='float:right;'>
			<span id='menu_languages' class='cacti_dd_link'><img src='<?php echo CACTI_URL_PATH; ?>images/flag_icons/<?php print CACTI_COUNTRY;?>.gif' alt='<?php print CACTI_COUNTRY;?>' align='top'>&nbsp;<?php print $lang2locale[CACTI_LOCALE]["language"];?></span>
		</div>
		<div id='loading' style='display:none; float:right'><img src='<?php echo CACTI_URL_PATH; ?>images/load_small.gif' align='top' alt='<?php print __("loading");?>'>LOADING</div>
		<?php }?>
	</div>
</div>
<div id='wrapper' style='opacity:0;'>
	<?php if (($_REQUEST["action"] == "tree") || ((isset($_REQUEST["view_type"]) ? $_REQUEST["view_type"] : "") == "tree")) { ?>
	<div id='graph_tree'>
		<div id='tree_filter'>
			<?php graph_view_tree_filter();?>
		</div>
		<div class='tree'></div>
	</div>
	<div id='vsplitter' onMouseout='doneDivResize()' onMouseover='doDivResize(this,event)' onMousemove='doDivResize(this,event)'>
		<div id='vsplitter_toggle' onClick='vSplitterToggle()' onMouseover='vSplitterEm()' onMouseout='vSplitterUnEm()' title='<?php print __("Hide/Unhide Menu");?>'></div>
	</div>
	<div id='graph_tree_content'>
		<div id='graphs'>
		</div>
	<?php }else{ ?>
<div id='graph_content'>
	<?php }

