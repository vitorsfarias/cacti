#!/usr/bin/php -q
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

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die("<br><strong>This script is only meant to run at the command line.</strong>");
}

/* We are not talking to the browser */
$no_http_headers = true;

include(dirname(__FILE__) . "/../include/global.php");
include_once("../lib/import.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
$me = array_shift($parms);

if (sizeof($parms)) {
	$filename = "";
	$import_custom_rra_settings = false;
	$rra_set = "";

	foreach($parms as $parameter) {
		@list($arg, $value) = @explode("=", $parameter);

		switch ($arg) {
			case "--filename":
				$filename = trim($value);

				break;
			case "--with-template-rras":
				$import_custom_rra_settings = true;

				break;
			case "--with-user-rras":
				$rra_set = trim($value);

				break;
			case "--version":
			case "-V":
			case "-H":
			case "--help":
				display_help($me);
				exit(0);
			default:
				printf(__("ERROR: Invalid Argument: (%s)\n\n"), $arg);
				exit(1);
		}
	}
	
	if($rra_set != "") {
		if ($import_custom_rra_settings) {
			echo "ERROR: '--with-template-rras' given and '--with-user-rras' given. Ignoring '--with-user-rras'\n";
		} else {
			$rra_array = explode(':', $rra_set);
			if (sizeof($rra_array)) {
				foreach ($rra_array as $key => $value) {
					$name = db_fetch_cell("SELECT name FROM rra WHERE id=" . intval($value));
					if (strlen($name)) {
						print (__("using RRA %s\n", $name));
					} else {
						print (__("RRA id %s not found\n", $value));
						unset($rra_array[$key]);
					}
				}
			}
		}
	}else{
		$rra_array = array();
		if (!$import_custom_rra_settings) {
			print (__("ERROR: neither '--with-template-rras' given nor '--with-user-rras' given. Exiting'\n"));
			return false;
		}
	}

	if($filename != "") {
		if(file_exists($filename) && is_readable($filename)) {
			$fp = fopen($filename,"r");
			$xml_data = fread($fp,filesize($filename));
			fclose($fp);

			printf(__("Read %d bytes of XML data\n"), strlen($xml_data));

			$debug_data = import_xml_data($xml_data, $import_custom_rra_settings, $rra_array);

			while (list($type, $type_array) = each($debug_data)) {
				print "** " . $hash_type_names[$type] . "\n";

				while (list($index, $vals) = each($type_array)) {
					if ($vals["result"] == "success") {
						$result_text = __(" [success]");
					}else{
						$result_text = __(" [fail]");
					}

					if ($vals["type"] == "update") {
						$type_text = __(" [update]");
					}else{
						$type_text = __(" [new]");
					}
					echo "   $result_text " . $vals["title"] . " $type_text" . "\n";

					$dep_text = ""; $errors = false;
					if ((isset($vals["dep"])) && (sizeof($vals["dep"]) > 0)) {
						while (list($dep_hash, $dep_status) = each($vals["dep"])) {
							if ($dep_status == "met") {
								$dep_status_text = __("Found Dependency: ");
							} else {
								$dep_status_text = __("Unmet Dependency: ");
								$errors = true;
							}

							$dep_text .= "    + $dep_status_text " . hash_to_friendly_name($dep_hash, true) . "\n";
						}
					}

					/* dependency errors need to be reported */
					if ($errors) {
						echo $dep_text;
						exit(-1);
					}else{
						exit(0);
					}
				}
			}
		} else {
			printf(__("ERROR: file %s is not readable, or does not exist\n\n"), $filename);
			exit(1);
		}
	} else {
		echo __("ERROR: no filename specified") . "\n\n";
		display_help($me);
		exit(1);
	}
} else {
	echo __("ERROR: no parameters given") . "\n\n";
	display_help($me);
	exit(1);
}

function display_help($me) {
	echo "Template Import Script 1.1" . ", " . __("Copyright 2004-2012 - The Cacti Group") . "\n";
	echo __("A simple command line utility to import a Template into Cacti") . "\n\n";
	echo __("usage: ") . $me . " --filename=[filename] [--with-template-rras] [--with-user-rras=[n[:m]...]] [-h] [--help] [-v] [--version]\n";
	echo __("Required:") . "\n";
	echo "   --filename     " . __("the name of the XML file to import") . "\n";
	echo __("Optional:") . "\n";
	echo "   --with-template-rras " . __("also import custom RRA definitions from the template\n");
	echo "   --with-user-rras     " . __("use your own set of RRA like '1:2:3:4'\n");
	echo "   -v --version         " . __("Display this help message") . "\n";
	echo "   -h --help            " . __("Display this help message") . "\n";
}
