#!/usr/bin/php -q
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

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;

include(dirname(__FILE__) . "/../include/global.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
$me = array_shift($parms);

global $debug;

$debug = FALSE;
$font_table  = "fonts";
$force = FALSE;

foreach($parms as $parameter) {
	@list($arg, $value) = @explode("=", $parameter);

	switch ($arg) {
	case "-d":
	case "--debug":
		$debug = TRUE;
		break;
	case "-v":
	case "--version":
	case "-h":
	case "--help":
		display_help($me);
		exit;
	default:
		echo __("ERROR: Invalid Parameter %s", $parameter) . "\n\n";
		display_help($me);
		exit;
	}
}



if ((file_exists(read_config_option("path_fc_list_binary"))) && ((function_exists('is_executable')) && (is_executable(read_config_option("path_fc_list_binary"))))) {
	
	echo __("Updating Cacti Font Table, using fc-list") . "\n";
	
	/* get a list of all fonts found on this system
	 * output looks like
		DejaVu Serif:fullname=DejaVu Serif
		DejaVu Serif:fullname=DejaVu Serif Bold
		DejaVu Serif:fullname=DejaVu Serif Bold Italic
		DejaVu Serif:fullname=DejaVu Serif Italic
		Dingbats
		FreeMono:fullname=Free Mono Cursiva,Free Mono kurzíva,Free Mono kursiv,Free Mono Πλάγια,Free Monospaced Oblique,Free Mono Kursivoitu,Free Mono Italique,Free Mono Dőlt,
		Free Mono Corsivo,Free Mono Cursief,Free Mono Kursywa,Free Mono Itálico,Free Mono oblic,Free Mono Курсив,Free Mono İtalik,Free Mono huruf miring,Free Mono похилий,Free
		 Mono slīpraksts,Free Mono pasvirasis,Free Mono nghiêng,Free Mono Etzana	but initially is unsorted
	 */
	$fontlist = explode("\n", shell_exec(cacti_escapeshellcmd(read_config_option("path_fc_list_binary")) . " : family fullname"));
	
	$size = sizeof($fontlist);
	if ($size) {
		/* empty the font table before inserting to start fresh */
		db_execute("TRUNCATE TABLE $font_table");

		/* sort the table for a proper display */
		sort($fontlist, SORT_LOCALE_STRING);
		
		$success = 0;
		/* scan through all fonts found */
		foreach ($fontlist as $font) {
			/* get the fullnames out; this is what we require to name a font */
			$font = preg_replace("/.*fullname=/", "", $font);
			/* skip "empty" fonts */
			if ($font == "") continue;
			/* a single font may contain several "fullname"s, so explode them */
			$fontarray = explode(",", $font);
			
			/* scan through all fullnames found */
			foreach($fontarray as $item) {
				/* escape the fullnames properly */
				$item = cacti_escapeshellarg($item, true);
				if (db_execute("INSERT INTO $font_table SET font=$item")) {
					print __("Font successfully inserted: %s\n", $item);
					$success++;
				} else {
					print __("Error while inserting font: %s\n", $item);
				}
			}
		}
		
		print __("%d font items inserted into font table" . "\n", $success);
	} else {
		print __("No fonts found, existing" . "\n");		
	}
} else {
	print __("Not able to execute the fc-list command. Either fc-list is not available, fc-list path not set or not executable." . "\n");
}





/*	display_help - displays the usage of the function */
function display_help($me) {
	echo "Cacti Update Font List Tool v1.0" . ", " . __("Copyright 2004-2011 - The Cacti Group") . "\n";
	echo __("usage: ") . $me . " [-d] [--debug] [-h]  [--help] [-v] [--version]\n\n";
	echo "   -d --debug    " . __("Display verbose output during execution") . "\n";
	echo "   -v --version  " . __("Display the version") . "\n";
	echo "   -h --help     " . __("Display this help message") . "\n";
}
