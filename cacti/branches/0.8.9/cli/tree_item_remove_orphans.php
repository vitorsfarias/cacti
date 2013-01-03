#!/usr/bin/php -q
<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2013 The Cacti Group                                 |
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

include(dirname(__FILE__)."/../include/global.php");
include_once(CACTI_LIBRARY_PATH . "/api_automation_tools.php");
include_once(CACTI_LIBRARY_PATH . '/tree.php');
include_once(CACTI_LIBRARY_PATH . '/api_tree.php');

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

$treeId     = 0;
$remove = FALSE;
$debug = FALSE;

if (sizeof($parms)) {
	foreach($parms as $parameter) {
		@list($arg, $value) = @explode("=", $parameter);
		
		switch ($arg) {
			case "--tree-id":
				$treeId = $value;
				
				break;
			case "--remove":
				$remove = TRUE;
				
				break;
			case "--debug":
				$debug = TRUE;
				
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
	
	/* get all tree header items
	 * order matters!
	 * fetch deepest level of headers
	 * in case you remove a deeper header, the higher level may become orphaned as well! */
	if (strtolower($treeId) === "all") {
		$sql = "SELECT * FROM graph_tree ORDER BY id";
	} else {
		$sql = "SELECT * FROM graph_tree WHERE id=$treeId ORDER BY id";
	}
	
	$tree_array = db_fetch_assoc($sql);
	if (sizeof($tree_array)) {
		foreach ($tree_array as $tree) {
			if ( $debug ) {
				print "Working on " . $tree['name'] . "[" . $tree['id'] . "]\n";
			}
			remove_orphans($tree['id'], $remove, $debug);
		}
	} else {
		print "No matching tree found\n";
		exit(1);
	}
} else {
	display_help();
}


function remove_orphans($treeId, $remove, $debug) {
	$sql = "SELECT id, title, order_key FROM graph_tree_items WHERE graph_tree_id=$treeId AND host_id=0 AND local_graph_id=0 ORDER BY order_key DESC";
	$header_array = db_fetch_assoc($sql);
	
	/* research headers */
	if (sizeof($header_array)) {
		foreach ($header_array as $header) {
		/* extract relevant part of order_key */
			$tier = tree_tier($header["order_key"]);
			$resulting_order_key = substr($header["order_key"], 0, ($tier * CHARS_PER_TIER));
			if ( $debug ) {
				print "TREE[" . $treeId . "] " . $header['title'] . "[" . $header['id'] . "]: " . $resulting_order_key;
			}
			
			/* find children of resulting order_key */
			$sql = "SELECT COUNT(*) FROM graph_tree_items WHERE graph_tree_id=$treeId AND order_key LIKE '" . $resulting_order_key . "%%'";
			$children = db_fetch_cell($sql);
			if ( $debug ) {
				print " - " . $children . "\n";
			}
			
			/* in case we only find the header itself, it's an orphaned header */
			if ($children <= 1) {
				print "TREE[" . $treeId . "] " . "Orphaned header: " . $header["title"];
				if ($remove) {
				/* remove stale header */
					db_execute("DELETE FROM graph_tree_items WHERE graph_tree_id=$treeId AND id = " . $header["id"]);
					print " deleted\n";
				}else{
					print "\n";
				}
			}else{
				if ( $debug ) {
					$sql = "SELECT * FROM graph_tree_items WHERE graph_tree_id=$treeId AND order_key LIKE '" . $resulting_order_key . "%%'";
					$child_array = db_fetch_assoc($sql);
					if (sizeof($child_array)) {
						foreach($child_array as $child) {
							print "TREE[" . $treeId . "] " . $header['title'] . "[" . $child['id'] . "]: " . $child['order_key'] . " title: " . $child['title'] . " graph: " . $child['local_graph_id'] . " host: " . $child['host_id'] . "\n";
							
						}
					}
				}
				/* NOP, non-orphaned header */
			}
		}
	}
}

function display_help() {
	echo "Remove Stale Tree Headers Script 1.1, Copyright 2004-2012 - The Cacti Group\n\n";
	echo "A simple command line utility to remove orphaned tree headers in Cacti\n\n";
	echo "usage: tree_item_remove_orphans.php.php  --tree-id=[tree id] [--remove] [--debug]\n\n";
	echo "    --tree-id=[ID]    id of tree or 'all' for all trees\n";
	echo "    --remove          remove orphaned header entries\n";
	echo "    --debug           print lots of messages\n";
}

?>