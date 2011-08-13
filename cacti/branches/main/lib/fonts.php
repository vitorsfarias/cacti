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

function create_filebased_fontlist($debug=false) {
	if ($debug) cacti_log(__FUNCTION__ . " called");
	
	$success = 0;
	$file_types = array("font");
	$file_extensions = array("ttf", "otf", "afm", "pfa", "pfb");
	$file_array = array();
	
	/* fetch user input; may contain multiple dirs, ":" separated */
	$dirarray = explode(":", trim(read_config_option("path_font_dir")));
	
	/* scan all dirs given */
	if (sizeof($dirarray)) {
		foreach ($dirarray as $dir) {
			/* and sample files found in a single array */
			$file_array += search_dir_recursive(trim($dir), $debug);
		}
	}
	
	/* are those files matching given filetypes? */
	$matching = search_filetype($file_array, $file_types, $debug);
	/* are those files matching given file extensions? */
	$matching += search_fileext($file_array, $file_extensions, $debug);
	/* sort the table for a proper display */
	sort($matching, SORT_LOCALE_STRING);
	

	if (sizeof($matching)) {
		/* empty the font table before inserting to start fresh */
		db_execute("TRUNCATE TABLE fonts");

		/* scan through all fullnames found */
		foreach($matching as $item) {
			/* escape the fullnames properly, this depends on locale
			 * so it may erase some items */
			$item = trim(cacti_escapeshellarg($item, false));
			if ($item == "") continue;
			$item = "'" . $item . "'";
			if ($item == "''") continue;
			if (db_execute("INSERT INTO fonts SET font=$item")) {
				if ($debug) cacti_log("Font successfully inserted: " . $item);
				$success++;
			} else {
				if ($debug) cacti_log("Error while inserting font: " . $item);
			}
		}	
	}
	return $success;
}


function create_pango_fontlist($debug=false) {
	if ($debug) cacti_log(__FUNCTION__ . " called");

	$success = 0;
	if ((file_exists(read_config_option("path_fc_list_binary"))) && ((function_exists('is_executable')) && (is_executable(read_config_option("path_fc_list_binary"))))) {
		
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
			db_execute("TRUNCATE TABLE fonts");
	
			/* sort the table for a proper display */
			sort($fontlist, SORT_LOCALE_STRING);
			
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
					/* escape the fullnames properly, this depends on locale
					 * so it may erase some items */
					$item = trim(cacti_escapeshellarg($item, false));
					if ($item == "") continue;
					$item = "'" . $item . "'";
					if ($item == "''") continue;
					if (db_execute("INSERT INTO fonts SET font=$item")) {
						if ($debug) cacti_log("Font successfully inserted: " . $item);
						$success++;
					} else {
						if ($debug) cacti_log("Error while inserting font: " . $item);
					}
				}
			}
		}			
	}
	return $success;
}
