<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2014 The Cacti Group                                 |
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

global $config, $menu, $refresh;

$oper_mode = api_plugin_hook_function('top_header', OPER_MODE_NATIVE);
if ($oper_mode == OPER_MODE_RESKIN) {
	return;
}

$page_title = api_plugin_hook_function('page_title', draw_navigation_text("title"));

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="X-UA-Compatible" content="edge">
	<title><?php echo $page_title; ?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
	<link href="<?php echo $config['url_path']; ?>include/themes/<?php print read_config_option('selected_theme');?>/main.css" type="text/css" rel="stylesheet">
	<link href="<?php echo $config['url_path']; ?>include/themes/<?php print read_config_option('selected_theme');?>/jquery.zoom.css" type="text/css" rel="stylesheet">
	<link href="<?php echo $config['url_path']; ?>include/themes/<?php print read_config_option('selected_theme');?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<link href="<?php echo $config['url_path']; ?>include/themes/<?php print read_config_option('selected_theme');?>/default/style.css" type="text/css" rel="stylesheet">
	<link href="<?php echo $config['url_path']; ?>images/favicon.ico" rel="shortcut icon">
	<?php api_plugin_hook('page_head'); ?>
	<script type="text/javascript" src="<?php echo $config['url_path']; ?>include/js/jquery.js" language="javascript"></script>
	<script type="text/javascript" src="<?php echo $config['url_path']; ?>include/js/jquery-ui.js" language="javascript"></script>
	<script type="text/javascript" src="<?php echo $config['url_path']; ?>include/js/jquery.cookie.js" language="javascript"></script>
	<script type="text/javascript" src="<?php echo $config['url_path']; ?>include/js/jstree.js"></script>
	<script type="text/javascript" src="<?php echo $config['url_path']; ?>include/js/jquery.easytabs.js"></script>
	<script type="text/javascript" src="<?php echo $config['url_path']; ?>include/jscalendar/calendar.js"></script>
	<script type="text/javascript" src="<?php echo $config['url_path']; ?>include/jscalendar/lang/calendar-en.js"></script>
	<script type="text/javascript" src="<?php echo $config['url_path']; ?>include/jscalendar/calendar-setup.js"></script>
	<script type="text/javascript" src="<?php echo $config['url_path']; ?>include/layout.js"></script>
	<?php
	if (isset($refresh)) {
		if (is_array($refresh)) {
			print "<meta http-equiv=refresh content='" . htmlspecialchars($refresh["seconds"],ENT_QUOTES) . "'; url='" . htmlspecialchars($refresh["page"],ENT_QUOTES) . "'>\r\n";
		}else{
			print "<meta http-equiv=refresh content='" . htmlspecialchars($refresh,ENT_QUOTES) . "'>\r\n";
		}
	}
	?>
</head>

<?php if ($oper_mode == OPER_MODE_NATIVE) {?>
<body class='cactiConsoleBody' <?php print api_plugin_hook_function("body_style", "");?>>
<?php }else{?>
<body class='cactiConsoleBody' <?php print api_plugin_hook_function("body_style", "");?>>
<?php }?>

<table style="width:100%" cellspacing="0" cellpadding="0">
<?php if ($oper_mode == OPER_MODE_NATIVE) { ;?>
	<tr class='cactiPageHead noprint'>
		<td class='cactiConsolePageHeadBackdrop' valign="bottom" colspan="3">
			<table width="100%" cellspacing="0" cellpadding="0">
				<tr>
					<td id="tabs" valign="bottom">
						<?php html_show_tabs_left(true);?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr class='breadCrumbBar noprint'>
		<td colspan="3">
			<table width="100%">
				<tr>
					<td width='30%'>
						<?php echo draw_navigation_text();?>
					</td>
					<td width='40%'><div class='scrollBar'></div>
					<td width='30%' align="right"><div class='infoBar'>
						<?php if (read_config_option("auth_method") != 0) { api_plugin_hook('nav_login_before'); ?>
							<?php api_plugin_hook('nav_login_before'); ?>
							Logged in as <strong><?php print db_fetch_cell("select username from user_auth where id=" . $_SESSION["sess_user_id"]);?></strong> (<a href="<?php echo $config['url_path']; ?>logout.php">Logout</a>)&nbsp;
							<?php api_plugin_hook('nav_login_after'); } ?>
					</div></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td id='navigation' class='cactiConsoleNavigationArea' valign='top'>
			<table>
				<?php draw_menu();?>
				<tr>
					<td class='cactiLogo' align='center'>
						<a href='<?php echo $config['url_path']; ?>about.php'><img src="<?php echo $config['url_path']; ?>images/cacti_logo.gif" border="0"></a>
					</td>
				</tr>
			</table>
		</td>
		<td class='cactiConsoleContentArea' width="100%" valign="top"><?php display_output_messages();?><div style='position:relative;' id='main'>
<?php }else{ ?>
	<tr>
		<td class='cactiConsoleContentArea' width="100%" valign="top"><?php display_output_messages();?><div style='position:relative;' id='main'>
<?php } ?>
