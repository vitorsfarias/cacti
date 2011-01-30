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

/* We are not talking to the browser */
$no_http_headers = true;

include(dirname(__FILE__)."/../include/global.php");
include_once($config["base_path"]."/lib/api_device.php");
include_once($config["base_path"]."/lib/snmp.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

if (sizeof($parms)) {
	$update_descr = false;
	$debug = false;
	unset($host_id);
	unset($host_template_id);
	unset($require_sysdescr);

	foreach($parms as $parameter) {
		@list($arg, $value) = @explode("=", $parameter);

		switch ($arg) {
		case "-d":
			$debug = TRUE;
			print "DEBUG: Running in debug mode\n";

			break;
		case "--host-id":
			$host_id = trim($value);
			if (!is_numeric($host_id)) {
				echo "ERROR: You must supply a valid host-id to run this script!\n";
				exit(1);
			}

			break;
		case "--host-template":
			$host_template_id = $value;
			if (!is_numeric($host_template_id)) {
				echo "ERROR: You must supply a numeric host-template for all hosts!\n";
				exit(1);
			}

			break;
		case "--require-sysdescr":
			$require_sysdescr = trim($value);

			break;
		case "--update-description":
			$update_descr = true;

			break;
		case "--version":
		case "-V":
		case "-H":
		case "--help":
			display_help();
			exit(0);
		default:
			echo "ERROR: Invalid Argument: ($arg)\n\n";
			display_help();
			exit(1);
		}
	}



	/*
	 * verify valid host id and get a name for it
	 */
	if (isset($host_id)) {
		$sql = "SELECT * FROM host WHERE id = " . $host_id;
	} else {
		$sql = "SELECT * FROM host";
	}
	$hosts = db_fetch_assoc($sql);

	/*
	 * verify valid host template and get a name for it
	 */
	if (isset($host_template_id)) {
		$host_template_name = db_fetch_cell("SELECT name FROM snmp_query WHERE id = " . $host_template_id);
		if (!isset($host_template_name)) {
			echo "ERROR: Unknown Host Template Id ($host_template_id)\n";
			exit(1);
		}
	}

	/*
	 * Now, scan all hosts and fetch data for update
	 */
	if (sizeof($hosts)) {
		foreach ($hosts as $host) {
			
			if ($update_descr && 
				(($host["availability_method"] == AVAIL_SNMP) ||
				($host["availability_method"] == AVAIL_SNMP_AND_PING) ||
				($host["availability_method"] == AVAIL_SNMP_OR_PING))) {
				/* get system name */
				$snmp_sysName = cacti_snmp_get($host["hostname"], $host["snmp_community"],
							".1.3.6.1.2.1.1.5.0", $host["snmp_version"],
							$host["snmp_username"], $host["snmp_password"],
							$host["snmp_auth_protocol"], $host["snmp_priv_passphrase"],
							$host["snmp_priv_protocol"], $host["snmp_context"], 
							$host["snmp_port"], $host["snmp_timeout"], $host["ping_retries"]);
				if (strlen($snmp_sysName) > 0) {
					/* translate all blanks and " to underscores */
					$snmp_sysName = trim(strtr($snmp_sysName," \"","__"));
					$host["description"] = sql_sanitize($snmp_sysName);
				}
			}

			/* if we require a specific sysName, check it */
			if (isset($require_sysdescr) && 
				(($host["availability_method"] == AVAIL_SNMP) ||
				($host["availability_method"] == AVAIL_SNMP_AND_PING) ||
				($host["availability_method"] == AVAIL_SNMP_OR_PING))) {
				/* get system name */
				$snmp_sysDescr = cacti_snmp_get($host["hostname"], $host["snmp_community"],
							".1.3.6.1.2.1.1.1.0", $host["snmp_version"],
							$host["snmp_username"], $host["snmp_password"],
							$host["snmp_auth_protocol"], $host["snmp_priv_passphrase"],
							$host["snmp_priv_protocol"], $host["snmp_context"], 
							$host["snmp_port"], $host["snmp_timeout"], $host["ping_retries"]);
				if (strlen($snmp_sysDescr) > 0) {
					/* wanted string given? */
					if (preg_match("/" . $require_sysdescr . "/", $snmp_sysDescr)) {
						$host["host_template_id"] = $host_template_id;
					} else {
						print "Skipping " . $host["id"] . ":" . $host["description"] . ":" . $snmp_sysDescr . "\n";
						continue;
					}
				} else {
					print "Skipping " . $host["id"] . ":" . $host["description"] . ":" . $snmp_sysDescr . "\n";
					continue;
				}
			} elseif (isset($host_template_id)) { /* else use given host template id, if any */
				$host["host_template_id"] = $host_template_id;
			}
			
			
			if ($debug) {
				print "DEBUG: update " . $host["id"] . ":" . $host["description"] . ":" . $host["host_template_id"] . "\n";
			} else {
				$host_id = api_device_save($host["id"], $host["host_template_id"], $host["description"],
					trim($host["hostname"]), $host["snmp_community"], $host["snmp_version"],
					$host["snmp_username"], $host["snmp_password"],
					$host["snmp_port"], $host["snmp_timeout"],
					(isset($host["disabled"]) ? $host["disabled"] : ""),
					$host["availability_method"], $host["ping_method"],
					$host["ping_port"], $host["ping_timeout"],
					$host["ping_retries"], $host["notes"],
					$host["snmp_auth_protocol"], $host["snmp_priv_passphrase"],
					$host["snmp_priv_protocol"], $host["snmp_context"], $host["max_oids"]);
				print "Update " . $host["id"] . ":" . $host["description"] . ":" . $host["host_template_id"] . "\n";
			}
		}
	} 
}else{
	display_help();
	exit(0);
}

function display_help() {
	echo "Update Host Script 1.0, Copyright 2008 - The Cacti Group\n\n";
	echo "A simple command line utility to update an existing device in Cacti\n\n";
	echo "usage: update_host.php [--host-id=[ID]] [--host-template=[dq_id]] [--update-description]\n\n";
	echo "    --host-id             the numerical ID of the host\n";
	echo "    --host-template       the numerical ID of the data_query to be added\n";
	echo "    --require-sysdescr    update host template only if this string is part of sysDescr\n";
	echo "    --update-description  update host description from SNMP sysName\n";
}

?>
