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

/* get_tree_item_type - gets the type of tree item
   @arg $tree_item_id - the id of the tree item to fetch the type for
   @returns - a string reprenting the type of the tree item. valid return
     values are 'header', 'graph', and 'host' */
function get_tree_item_type($tree_item_id) {
	$tree_item = db_fetch_row("select title,local_graph_id,host_id from graph_tree_items where id=$tree_item_id");

	if ($tree_item["local_graph_id"] > 0) {
		return "graph";
	}elseif ($tree_item["title"] != "") {
		return "header";
	}elseif ($tree_item["host_id"] > 0) {
		return "host";
	}

	return "";
}

/* tree_tier - gets the "depth" of a particular branch of the tree
   @arg $order_key - the order key of the branch to fetch the depth for
   @arg $chars_per_tier - the number of characters dedicated to each branch
     depth (tier). this is typically '3' in cacti.
   @returns - a number reprenting the depth of the branch, where '0' is the
     base of the tree and the maximum value is:
     length($order_key) / $chars_per_tier */
function tree_tier($order_key, $chars_per_tier = CHARS_PER_TIER) {
	$root_test = str_pad('', $chars_per_tier, '0');

	if (preg_match("/^$root_test/", $order_key)) {
		$tier = 0;
	}else{
		$tier = ceil(strlen(preg_replace("/0+$/",'',$order_key)) / $chars_per_tier);
	}

	return $tier;
}

/* get_parent_id - returns the tree item id of the parent of this tree item
   @arg $id - the tree item id to search for a parent
   @arg $table - the sql table to use when searching for a parent id
   @arg $where - extra sql WHERE queries that must be used to query $table
   @returns - the id of the parent tree item to $id, or '0' if $id is at the root
     of the tree */
function get_parent_id($id, $table, $where = "") {
	require_once(CACTI_INCLUDE_PATH . '/graph_tree/graph_tree_constants.php');
	
	$parent_root = 0;

	$order_key = db_fetch_cell("select order_key from $table where id=$id and $where");
	$tier = tree_tier($order_key);

	if ($tier > 1) {
		$parent_root = substr($order_key, 0, (($tier-1) * CHARS_PER_TIER));
	}

	$parent_id = db_fetch_cell("select id from $table where order_key='" . str_pad($parent_root, (MAX_TREE_DEPTH * CHARS_PER_TIER), '0') . "' and $where");

	if ((empty($parent_id)) || (empty($tier))) {
		return "0";
	}else{
		return $parent_id;
	}
}

/* get_next_tree_id - finds the next available order key on a particular branch
   @arg $order_key - the order key to use as a starting point for the available
     order key search. this order is used as the 'root' in the search
   @arg $table - the sql table to use when searching for a parent id
   @arg $field - the sql field name that contains the order key
   @arg $where - extra sql WHERE queries that must be used to query $table
   @returns - the next available order key in $order_key's branch */
function get_next_tree_id($order_key, $table, $field, $where) {
	require_once(CACTI_INCLUDE_PATH . '/graph_tree/graph_tree_constants.php');
	
	if (preg_match("/^" . str_repeat('0', CHARS_PER_TIER) . "/",$order_key)) {
		$tier = 0;
		$parent_root = '';
	}else{
		$tier = tree_tier($order_key);
		$parent_root = substr($order_key, 0, ($tier * CHARS_PER_TIER));
	}

	$order_key = db_fetch_cell("SELECT $field FROM $table WHERE $where AND $field LIKE '$parent_root%' ORDER BY $field DESC LIMIT 1");

	$complete_root = substr($order_key, 0, ($tier * CHARS_PER_TIER) + CHARS_PER_TIER);
	$order_key_suffix = (substr($complete_root, - CHARS_PER_TIER) + 1);
	$order_key_suffix = str_pad($order_key_suffix, CHARS_PER_TIER, '0', STR_PAD_LEFT);
	$order_key_suffix = str_pad($parent_root . $order_key_suffix, (MAX_TREE_DEPTH * CHARS_PER_TIER), '0', STR_PAD_RIGHT);

	return $order_key_suffix;
}

/* branch_up - moves a branch up in the tree
   @arg $order_key - the order key of the branch to move up
   @arg $table - the sql table to use when searching for a parent id
   @arg $field - the sql field name that contains the order key
   @arg $where - extra sql WHERE queries that must be used to query $table */
function branch_up($order_key, $table, $field, $where) {
	move_branch('up', $order_key, $table, $field, $where);
}

/* branch_down - moves a branch down in the tree
   @arg $order_key - the order key of the branch to move down
   @arg $table - the sql table to use when searching for a parent id
   @arg $field - the sql field name that contains the order key
   @arg $where - extra sql WHERE queries that must be used to query $table */
function branch_down($order_key, $table, $field, $where) {
	move_branch('down', $order_key, $table, $field, $where);
}

/* move_branch - moves a branch up or down in the tree
   @arg $dir - the direction of the move, either 'up' or 'down'
   @arg $order_key - the order key of the branch to move up or down
   @arg $table - the sql table to use when searching for a parent id
   @arg $field - the sql field name that contains the order key
   @arg $where - extra sql WHERE queries that must be used to query $table */
function move_branch($dir, $order_key, $table, $field, $where) {
	require_once(CACTI_INCLUDE_PATH . '/graph_tree/graph_tree_constants.php');
	
	$tier = tree_tier($order_key);

	db_execute("LOCK TABLES $table WRITE, graph_tree READ, graph_templates_graph READ, host READ");

	if ($where != '') { $where = " AND $where"; }

	$tree_order = $dir == 'up' ? 'ORDER BY order_key ASC' : 'ORDER BY order_key DESC';
	$arrow = $dir == 'up' ? '<' : '>';
	$order = $dir == 'up' ? 'DESC' : 'ASC';

	/* take a peek at the current tree structure */
	$pre_tree = db_fetch_assoc("SELECT * FROM graph_tree_items WHERE local_graph_id='0' AND host_id='0' $tree_order");

	$sql = "SELECT * FROM $table WHERE $field $arrow '$order_key' AND $field LIKE '%" . substr($order_key, ($tier * CHARS_PER_TIER))."'
		AND $field NOT LIKE '%" . str_repeat('0', CHARS_PER_TIER) . substr($order_key, ($tier * CHARS_PER_TIER)) . "' $where ORDER BY $field $order";

	$displaced_row = db_fetch_row($sql);

	if (sizeof($displaced_row) > 0) {
		$old_root = substr($order_key, 0, ($tier * CHARS_PER_TIER));
		$new_root = substr($displaced_row[$field], 0, ($tier * CHARS_PER_TIER));

		db_execute("UPDATE $table SET $field = CONCAT('" . str_pad('', ($tier * CHARS_PER_TIER), 'Z') . "',SUBSTRING($field," . (($tier * CHARS_PER_TIER) + 1).")) WHERE $field LIKE '$new_root%'$where");
		db_execute("UPDATE $table SET $field = CONCAT('$new_root',SUBSTRING($field," . (($tier * CHARS_PER_TIER) + 1) . ")) WHERE $field LIKE '$old_root%' $where");
		db_execute("UPDATE $table SET $field = CONCAT('$old_root',SUBSTRING($field," . (($tier * CHARS_PER_TIER) + 1) . ")) WHERE $field LIKE '".str_pad('', ($tier * CHARS_PER_TIER), 'Z') . "%' $where");
	}

	/* move session variables around */
	reset_session_variables($pre_tree);

	db_execute("UNLOCK TABLES");
}

function reset_session_variables($pre_tree) {
	/* get the current settings */
	$i = 0;
	if (sizeof($pre_tree)) {
	foreach($pre_tree as $leaf) {
		$tier = tree_tier($leaf["order_key"]);

		$tier_string = tree_tier_string($leaf["order_key"]);

		$variable = "sess_tree_leaf_expand_" . $leaf["graph_tree_id"] . "_" . $tier_string;

		if (isset($_SESSION[$variable])) {
			$pre_tree[$i]["visibility"] = $_SESSION[$variable];
			unset($_SESSION[$variable]);
		}else{
			$pre_tree[$i]["visibility"] = true;
		}

		$i++;
	}

	foreach($pre_tree as $leaf) {
		$new_leaf = db_fetch_row("SELECT * FROM graph_tree_items WHERE id='" . $leaf['id'] . "'");

		$tier = tree_tier($new_leaf["order_key"]);

		$tier_string = tree_tier_string($new_leaf["order_key"]);

		$variable = "sess_tree_leaf_expand_" . $new_leaf["graph_tree_id"] . "_" . $tier_string;

		$_SESSION[$variable] = $leaf["visibility"];
	}
	}
}

/* sort_branch - sorts the child items a branch using a specified sorting algorithm
   @arg $sort_type - the type of sorting to perform. available options are:
     SORT_TYPE_TREE (1) - sort the entire tree
     SORT_TYPE_TREE_ITEM (2) - sort a single tree branch
   @arg $item_id - the id tree or tree item to sort
   @arg $sort_style - the type of sorting to perform. available options are:
     TREE_ORDERING_NONE (1) - no sorting
     TREE_ORDERING_ALPHABETIC (2) - alphabetic sorting
     TREE_ORDERING_NUMERIC (3) - numeric sorting */
function sort_tree($sort_type, $item_id, $sort_style) {
	require_once(CACTI_INCLUDE_PATH . "/data_query/data_query_constants.php");
	require_once(CACTI_INCLUDE_PATH . "/graph_tree/graph_tree_constants.php");
	include_once(CACTI_LIBRARY_PATH . "/sort.php");

	if (empty($item_id)) { return 0; }
	if ($sort_style == TREE_ORDERING_NONE) { return 0; }

	$search_key = "";

	if ($sort_type == SORT_TYPE_TREE_ITEM) {
		$tree_item = db_fetch_row("select order_key,graph_tree_id from graph_tree_items where id=$item_id");
		$search_key = substr($tree_item["order_key"], 0, (tree_tier($tree_item["order_key"]) * CHARS_PER_TIER));

		$sql_where = "where graph_tree_items.graph_tree_id='" . $tree_item["graph_tree_id"] . "'
			and graph_tree_items.order_key like '$search_key" . str_repeat('_', CHARS_PER_TIER) . str_repeat('0', (MAX_TREE_DEPTH * CHARS_PER_TIER) - (strlen($search_key) + CHARS_PER_TIER)) . "'
			and graph_tree_items.id != '$item_id'";

		$tree_id = $tree_item["graph_tree_id"];
	}else if ($sort_type == SORT_TYPE_TREE) {
		$sql_where = "where graph_tree_items.graph_tree_id='$item_id'";

		$tree_id = $item_id;
	}else{
		return 0;
	}

	$hier_sql = "select
		graph_tree_items.id,
		graph_tree_items.title,
		graph_tree_items.local_graph_id,
		graph_tree_items.host_id,
		graph_tree_items.order_key,
		graph_templates_graph.title_cache as graph_title,
		CONCAT_WS('',description,' (',hostname,')') as hostname
		from graph_tree_items
		left join graph_templates_graph on (graph_tree_items.local_graph_id=graph_templates_graph.local_graph_id and graph_tree_items.local_graph_id>0)
		left join host on (host.id=graph_tree_items.host_id)
		$sql_where
		order by graph_tree_items.order_key";

	$hierarchy = db_fetch_assoc($hier_sql);

	$leaf_sort_array = array();
	if (sizeof($hierarchy) > 0) {
		foreach ($hierarchy as $leaf) {
			$_search_key = substr($leaf["order_key"], 0, ((tree_tier($leaf["order_key"]) - 1) * CHARS_PER_TIER));

			if ($leaf["local_graph_id"] > 0) {
				$leaf_sort_array{strlen($_search_key) / CHARS_PER_TIER}[$_search_key]{$leaf["order_key"]} = $leaf["graph_title"];
			}elseif ($leaf["title"] != "") {
				$leaf_sort_array{strlen($_search_key) / CHARS_PER_TIER}[$_search_key]{$leaf["order_key"]} = $leaf["title"];
			}elseif ($leaf["host_id"] > 0) {
				$leaf_sort_array{strlen($_search_key) / CHARS_PER_TIER}[$_search_key]{$leaf["order_key"]} = $leaf["hostname"];
			}
		}
	}

	/* do the actual sort */
	while (list($_tier_key, $tier_array) = each($leaf_sort_array)) {
		while (list($_search_key, $search_array) = each($tier_array)) {
			if ($sort_style == TREE_ORDERING_NUMERIC) {
				uasort($leaf_sort_array[$_tier_key][$_search_key], "usort_numeric");
			}elseif ($sort_style == TREE_ORDERING_ALPHABETIC) {
				uasort($leaf_sort_array[$_tier_key][$_search_key], "usort_alphabetic");
			}elseif ($sort_style == TREE_ORDERING_NATURAL) {
				uasort($leaf_sort_array[$_tier_key][$_search_key], "usort_natural");
			}
		}
	}

	/* sort from most specific to least specific */
	rsort($leaf_sort_array);

	reset($leaf_sort_array);
	while (list($_tier_key, $tier_array) = each($leaf_sort_array)) {
		while (list($_search_key, $search_array) = each($tier_array)) {
			/* prepend all order keys will 'x' so they don't collide during the REPLACE process */
			db_execute("update graph_tree_items set order_key = CONCAT('x',order_key) where order_key like '$_search_key%%' " . (($sort_type == SORT_TYPE_TREE_ITEM) ? "and id != $item_id" : "and order_key != '$_search_key" . str_repeat('0', (MAX_TREE_DEPTH * CHARS_PER_TIER) - strlen($_search_key)) . "'") . " and graph_tree_id = '$tree_id'");

			$i = 1;
			while (list($leaf_order_key, $leaf_title) = each($search_array)) {
				$starting_tier = tree_tier($leaf_order_key);

				$old_base_tier = substr($leaf_order_key, 0, ($starting_tier * CHARS_PER_TIER));
				$new_base_tier = $_search_key . str_pad(strval($i), CHARS_PER_TIER, '0', STR_PAD_LEFT);

				db_execute("update graph_tree_items set order_key = REPLACE(order_key, 'x$old_base_tier', '$new_base_tier') where order_key like 'x$old_base_tier%%' " . (($sort_type == SORT_TYPE_TREE_ITEM) ? "and id != $item_id" : "") . " and graph_tree_id = '$tree_id'");

				$i++;
			}
		}
	}
}

/* reparent_branch - places a branch and all of its children to a new root
     node
   @arg $new_parent_id - the target parent id for the target branch to move
   @arg $tree_item_id - the id of the branch to re-parent */
function reparent_branch($new_parent_id, $tree_item_id) {
	require_once(CACTI_INCLUDE_PATH . '/graph_tree/graph_tree_constants.php');
	
	if (empty($tree_item_id)) { return 0; }

	/* get the current tree_id */
	$graph_tree_id = db_fetch_cell("select graph_tree_id from graph_tree_items where id=$tree_item_id");

	/* make sure the parent id actually changed */
	if (get_parent_id($tree_item_id, "graph_tree_items", "graph_tree_id=$graph_tree_id") == $new_parent_id) {
		return 0;
	}

	/* get current key so we can do a sql select on it */
	$old_order_key = db_fetch_cell("select order_key from graph_tree_items where id=$tree_item_id");
	$new_order_key = get_next_tree_id(db_fetch_cell("select order_key from graph_tree_items where id=$new_parent_id"), "graph_tree_items", "order_key", "graph_tree_id=$graph_tree_id");

	/* yeah, this would be really bad */
	if (empty($old_order_key)) {
		return 0;
	}

	$old_starting_tier = tree_tier($old_order_key);
	$new_starting_tier = tree_tier($new_order_key);

	$new_base_tier = substr($new_order_key, 0, ($new_starting_tier * CHARS_PER_TIER));
	$old_base_tier = substr($old_order_key, 0, ($old_starting_tier * CHARS_PER_TIER));

	/* prevent possible collisions */
	db_execute("update graph_tree_items set order_key = CONCAT('x',order_key) where order_key like '$old_base_tier%%' and graph_tree_id=$graph_tree_id");

	/* truncate */
	if ($new_starting_tier >= $old_starting_tier) {
		db_execute("update graph_tree_items set order_key = SUBSTRING(REPLACE(order_key, 'x$old_base_tier', '$new_base_tier'), 1, " . (MAX_TREE_DEPTH * CHARS_PER_TIER) . ") where order_key like 'x$old_base_tier%%' and graph_tree_id=$graph_tree_id");
	/* append */
	}else{
		db_execute("update graph_tree_items set order_key = CONCAT(REPLACE(order_key, 'x$old_base_tier', '$new_base_tier'), '" . str_repeat('0', (strlen($old_base_tier) - strlen($new_base_tier))) . "') where order_key like 'x$old_base_tier%%' and graph_tree_id=$graph_tree_id");
	}
}

/* delete_branch - deletes a branch and all of its children
   @arg $tree_item_id - the id of the branch to remove */
function delete_branch($tree_item_id) {
	require_once(CACTI_INCLUDE_PATH . '/graph_tree/graph_tree_constants.php');
	
	if (empty($tree_item_id)) { return 0; }

	db_execute("LOCK TABLES graph_tree_items WRITE, graph_tree READ, graph_templates_graph READ, host READ");

	$tree_item = db_fetch_row("select order_key,local_graph_id,host_id,graph_tree_id from graph_tree_items where id=$tree_item_id");

	/* if this item is a graph/host, it will have NO children, so we can just delete the
	graph and exit. */
	if ((!empty($tree_item["local_graph_id"])) || (!empty($tree_item["host_id"]))) {
		db_execute("delete from graph_tree_items where id=$tree_item_id");
		db_execute("UNLOCK TABLES");
		return 0;
	}

	/* yeah, this would be really bad */
	if (empty($tree_item["order_key"])) {
		db_execute("UNLOCK TABLES");
		return 0;
	}

	$starting_tier = tree_tier($tree_item["order_key"]);
	$order_key = substr($tree_item["order_key"], 0, (CHARS_PER_TIER * $starting_tier));

	$tree = db_fetch_assoc("select
		graph_tree_items.id, graph_tree_items.order_key
		from graph_tree_items
		where graph_tree_items.order_key like '$order_key%%'
		and graph_tree_items.graph_tree_id='" . $tree_item["graph_tree_id"] . "'
		order by graph_tree_items.order_key");

	if (sizeof($tree) > 0) {
	foreach ($tree as $item) {
		/* delete the folder */
		db_execute("delete from graph_tree_items where id=" . $item["id"]);
	}
	}

	/* CLEANUP - reorder the tier that this branch lies in */
	$order_key = substr($order_key, 0, (CHARS_PER_TIER * ($starting_tier-1)));

	$tree = db_fetch_assoc("select
		graph_tree_items.id, graph_tree_items.order_key
		from graph_tree_items
		where graph_tree_items.order_key like '$order_key%%'
		and graph_tree_items.graph_tree_id='" . $tree_item["graph_tree_id"] . "'
		order by graph_tree_items.order_key");

	if (sizeof($tree) > 0) {
		$old_key_part = substr($tree[0]["order_key"], strlen($order_key), CHARS_PER_TIER);

		/* we key tier==0 off of '1' and tier>0 off of '0' */
		if (tree_tier($order_key) == 0) {
			$i = 1;
		}else{
			$i = 0;
		}

		foreach ($tree as $tree_item) {
			/* this is the key column we are going to 'rekey' */
			$new_key_part = substr($tree_item["order_key"], strlen($order_key), CHARS_PER_TIER);

			/* incriment a counter for the new key column */
			if ($old_key_part != $new_key_part) {
				$i++;
			}

			/* build the new order key string */
			$key = $order_key . str_pad(strval($i), CHARS_PER_TIER, '0', STR_PAD_LEFT) . substr($tree_item["order_key"], (strlen($order_key) + CHARS_PER_TIER));

			db_execute("update graph_tree_items set order_key='$key' where id=" . $tree_item["id"]);

			$old_key_part = $new_key_part;
		}
	}

	db_execute("UNLOCK TABLES");
}


/** tree_add_tree_names_to_actions_array - build an array to add tree names to the action dropdown list
 *
 * @return array - tree names as entries to an array
 */
function tree_add_tree_names_to_actions_array() {
	/* add a list of tree names to the actions dropdown */
	$actions = array();
	$trees = db_fetch_assoc("select id,name from graph_tree order by name");

	if (sizeof($trees) > 0) {
		foreach ($trees as $tree) {
			$actions{"tr_" . $tree["id"]} = __("Place on a Tree") . " (" . $tree["name"] . ")";
		}
	}

	return $actions;
}

/** tree_item_save - add a tree item to the database
 *
 * @param int $id
 * @param int $tree_id				- the tree, to which the item shall be added
 * @param string $type				- type of tree item, e.g. device, graph, ...
 * @param int $parent_tree_item_id	- parent tree item, below which the current item shall be added
 * @param string $title				- title, in case tree item type is a header
 * @param int $local_graph_id		- graph id, in case item type is a graph
 * @param int $rra_id				- rra to be used for display
 * @param int $device_id			- device id, in case item type is a device
 * @param int $device_grouping_type	- grouping type (e.g. alpha) for a device item
 * @param int $sort_children_type	- shall item children be sorted
 * @param string $propagate_changes - shall changes be propagated
 * @return unknown_type
 */
function tree_item_save($id, $tree_id, $type, $parent_tree_item_id, $title, $local_graph_id, $rra_id,
	$device_id, $device_grouping_type, $sort_children_type, $propagate_changes) {
	require(CACTI_INCLUDE_PATH . "/graph_tree/graph_tree_arrays.php");

	input_validate_input_number($tree_id);
	input_validate_input_number($parent_tree_item_id);

	require_once(CACTI_INCLUDE_PATH . "/data_query/data_query_constants.php");

	$parent_order_key = db_fetch_cell("select order_key from graph_tree_items where id=$parent_tree_item_id");

	/* fetch some cache variables */
	if (empty($id)) {
		/* new/save - generate new order key */
		$order_key = get_next_tree_id($parent_order_key, "graph_tree_items", "order_key", "graph_tree_id=$tree_id");
	}else{
		/* edit/save - use old order_key */
		$order_key = db_fetch_cell("select order_key from graph_tree_items where id=$id");
	}

	/* duplicate graph check */
	$search_key = substr($parent_order_key, 0, (tree_tier($parent_order_key) * CHARS_PER_TIER));
	if (($type == TREE_ITEM_TYPE_GRAPH) && (sizeof(db_fetch_assoc("select id from graph_tree_items where local_graph_id='$local_graph_id' and graph_tree_id='$tree_id' and order_key like '$search_key" . str_repeat('_', CHARS_PER_TIER) . str_repeat('0', (MAX_TREE_DEPTH * CHARS_PER_TIER) - (strlen($search_key) + CHARS_PER_TIER)) . "'")) > 0)) {
		return db_fetch_cell("select id from graph_tree_items where local_graph_id='$local_graph_id' and graph_tree_id='$tree_id' and order_key like '$search_key" . str_repeat('_', CHARS_PER_TIER) . str_repeat('0', (MAX_TREE_DEPTH * CHARS_PER_TIER) - (strlen($search_key) + CHARS_PER_TIER)) . "'");
	}

	/* Duplicate header check */
	if (($type == TREE_ITEM_TYPE_HEADER)) {
		if ((sizeof(db_fetch_assoc("select id from graph_tree_items where title='$title' and graph_tree_id='$tree_id' and order_key like '$search_key" . str_repeat('_', CHARS_PER_TIER) . str_repeat('0', (MAX_TREE_DEPTH * CHARS_PER_TIER) - (strlen($search_key) + CHARS_PER_TIER)) . "'")) > 0)) {
			return db_fetch_cell("select id from graph_tree_items where title='$title' and graph_tree_id='$tree_id' and order_key like '$search_key" . str_repeat('_', CHARS_PER_TIER) . str_repeat('0', (MAX_TREE_DEPTH * CHARS_PER_TIER) - (strlen($search_key) + CHARS_PER_TIER)) . "'");
		}
	}

	/* Duplicate device check */
	if (($type == TREE_ITEM_TYPE_HOST) && (sizeof(db_fetch_assoc("select id from graph_tree_items where host_id='$device_id' and local_graph_id='$local_graph_id' and graph_tree_id='$tree_id' and order_key like '$search_key" . str_repeat('_', CHARS_PER_TIER) . str_repeat('0', (MAX_TREE_DEPTH * CHARS_PER_TIER) - (strlen($search_key) + CHARS_PER_TIER)) . "'")) > 0)) {
			return db_fetch_cell("select id from graph_tree_items where host_id='$device_id' and local_graph_id='$local_graph_id' and graph_tree_id='$tree_id' and order_key like '$search_key" . str_repeat('_', CHARS_PER_TIER) . str_repeat('0', (MAX_TREE_DEPTH * CHARS_PER_TIER) - (strlen($search_key) + CHARS_PER_TIER)) . "'");
	}

	$save["id"] 				= $id;
	$save["parent_id"]			= $parent_tree_item_id;
	$save["graph_tree_id"] 		= $tree_id;
	$save["title"] 				= form_input_validate($title, "title", "", ($type == TREE_ITEM_TYPE_HEADER ? false : true), 3);
	$save["order_key"] 			= $order_key;
	$save["local_graph_id"] 	= form_input_validate($local_graph_id, "local_graph_id", "", true, 3);
	$save["rra_id"]				= form_input_validate($rra_id, "rra_id", "", true, 3);
	$save["host_id"] 			= form_input_validate($device_id, "host_id", "", true, 3);
	$save["host_grouping_type"] = form_input_validate($device_grouping_type, "host_grouping_type", "", true, 3);
	$save["sort_children_type"] = form_input_validate($sort_children_type, "sort_children_type", "", true, 3);

	$tree_item_id = 0;

	if (!is_error_message()) {
		$tree_item_id = sql_save($save, "graph_tree_items");

		if ($tree_item_id) {
			raise_message(1);

			/* re-parent the branch if the parent item has changed */
			if ($parent_tree_item_id != $tree_item_id) {
				reparent_branch($parent_tree_item_id, $tree_item_id);
			}

			$tree_sort_type = db_fetch_cell("select sort_type from graph_tree where id='$tree_id'");

			/* tree item ordering */
			if ($tree_sort_type == DATA_QUERY_INDEX_SORT_TYPE_NONE) {
				/* resort our parent */
				$parent_sorting_type = db_fetch_cell("select sort_children_type from graph_tree_items where id=$parent_tree_item_id");
				if ((!empty($parent_tree_item_id)) && ($parent_sorting_type != DATA_QUERY_INDEX_SORT_TYPE_NONE)) {
					sort_tree(SORT_TYPE_TREE_ITEM, $parent_tree_item_id, $parent_sorting_type);
				}

				/* if this is a header, sort direct children */
				if (($type == TREE_ITEM_TYPE_HEADER) && ($sort_children_type != DATA_QUERY_INDEX_SORT_TYPE_NONE)) {
					sort_tree(SORT_TYPE_TREE_ITEM, $tree_item_id, $sort_children_type);
				}
			/* tree ordering */
			}else{
				/* potential speed savings for large trees */
				if (tree_tier($save["order_key"]) == 1) {
					sort_tree(SORT_TYPE_TREE, $tree_id, $tree_sort_type);
				}else{
					sort_tree(SORT_TYPE_TREE_ITEM, $parent_tree_item_id, $tree_sort_type);
				}
			}

			/* if the user checked the 'Propagate Changes' box */
			if (($type == TREE_ITEM_TYPE_HEADER) && ($propagate_changes == true)) {
				$search_key = preg_replace("/0+$/", "", $order_key);

				$tree_items = db_fetch_assoc("select
					graph_tree_items.id
					from graph_tree_items
					where graph_tree_items.host_id = 0
					and graph_tree_items.local_graph_id = 0
					and graph_tree_items.title != ''
					and graph_tree_items.order_key like '$search_key%%'
					and graph_tree_items.graph_tree_id='$tree_id'");

				if (sizeof($tree_items) > 0) {
					foreach ($tree_items as $item) {
						db_execute("update graph_tree_items set sort_children_type = '$sort_children_type' where id = '" . $item["id"] . "'");

						if ($sort_children_type != DATA_QUERY_INDEX_SORT_TYPE_NONE) {
							sort_tree(SORT_TYPE_TREE_ITEM, $item["id"], $sort_children_type);
						}
					}
				}
			}
		}else{
			raise_message(2);
		}
	}

	return $tree_item_id;
}

function &graph_tree_form_list() {
	require(CACTI_INCLUDE_PATH . "/graph_tree/graph_tree_forms.php");

	return $fields_graph_tree_edit;
}
