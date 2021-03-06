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

function grow_edit_graph_tree($tree_id, $user_id, $options) {
	require_once(CACTI_INCLUDE_PATH . "/data_query/data_query_constants.php");
	include_once(CACTI_LIBRARY_PATH . "/tree.php");

	$tree_sorting_type = db_fetch_cell("select sort_type from graph_tree where id='$tree_id'");

	$tree = db_fetch_assoc("select
		graph_tree_items.id,
		graph_tree_items.title,
		graph_tree_items.graph_tree_id,
		graph_tree_items.local_graph_id,
		graph_tree_items.device_id,
		graph_tree_items.order_key,
		graph_tree_items.sort_children_type,
		graph_templates_graph.title_cache as graph_title,
		CONCAT_WS('',description,' (',hostname,')') as hostname
		from graph_tree_items
		left join graph_templates_graph on (graph_tree_items.local_graph_id=graph_templates_graph.local_graph_id and graph_tree_items.local_graph_id>0)
		left join device on (device.id=graph_tree_items.device_id)
		where graph_tree_items.graph_tree_id=$tree_id
		order by graph_tree_id, graph_tree_items.order_key");

	print "<!-- <P>Building Hierarchy w/ " . sizeof($tree) . " leaves</P>  -->\n";

	##  Here we go.  Starting the main tree drawing loop.

	/* change the visibility session variable if applicable */
	set_tree_visibility_status();

	$i = 0;
	if (sizeof($tree) > 0) {
		foreach ($tree as $leaf) {
			$tier = tree_tier($leaf["order_key"]);
			$transparent_indent = "<img src='images/transparent_pixel.gif' width='" . (($tier-1) * 20) . "' height='1' align='middle' alt=''>&nbsp;";
			$sort_cache[$tier] = $leaf["sort_children_type"];

			$visible = get_visibility($leaf);

			if ($leaf["local_graph_id"] > 0) {
				if ($visible) {
					form_alternate_row_color();
					print "<td>$transparent_indent<a href='" . htmlspecialchars("tree.php?action=item_edit&tree_id=" . $_GET["id"] . "&id=" . $leaf["id"]) . "'>" . $leaf["graph_title"] . "</a></td>\n";
					print "<td>Graph</td>";
				}
			}elseif ($leaf["title"] != "") {
				$icon = get_icon($leaf["graph_tree_id"], $leaf["order_key"]);
				if ($visible) {
					form_alternate_row_color();
					print "<td>$transparent_indent<a href='" . htmlspecialchars("tree.php?action=edit&id=" . $_GET["id"] . "&leaf_id=" . $leaf["id"] . "&subaction=change") . "'><img src='" . $icon . "' alt=''></a><a href='" . htmlspecialchars("tree.php?action=item_edit&tree_id=" . $_GET["id"] . "&id=" . $leaf["id"]) . "'>&nbsp;" . $leaf["title"] . "</a> (<a href='" . htmlspecialchars("tree.php?action=item_edit&tree_id=" . $_GET["id"] . "&parent_id=" . $leaf["id"]) . "'>Add</a>)</td>\n";
					print "<td>Heading</td>";
				}
			}elseif ($leaf["device_id"] > 0) {
				if ($visible) {
					form_alternate_row_color();
					print "<td>$transparent_indent<a href='" . htmlspecialchars("tree.php?action=item_edit&tree_id=" . $_GET["id"] . "&id=" . $leaf["id"]) . "'>Host: " . $leaf["hostname"] . "</a>&nbsp;<a href='" . htmlspecialchars("devices.php?action=edit&id=" . $leaf["device_id"]) . "'>(Edit device)</a></td>\n";
					print "<td>Host</td>";
				}
			}

			if ($visible) {
				if ( ((isset($sort_cache{$tier-1})) && ($sort_cache{$tier-1} != DATA_QUERY_INDEX_SORT_TYPE_NONE)) || ($tree_sorting_type != DATA_QUERY_INDEX_SORT_TYPE_NONE) )  {
					print "<td width='80'></td>\n";
				}else{
					print "<td width='80' align='center'>\n
					<a href='" . htmlspecialchars("tree.php?action=item_movedown&id=" . $leaf["id"] . "&tree_id=" . $_GET["id"]) . "'><img class='buttonSmall' src='images/move_down.gif' alt='Move Down' align='middle'></a>\n
					<a href='" . htmlspecialchars("tree.php?action=item_moveup&id=" . $leaf["id"] . "&tree_id=" . $_GET["id"]) . "'><img class='buttonSmall' src='images/move_up.gif' alt='Move Up' align='middle'></a>\n
					</td>\n";
				}

				print 	"<td align='right'>\n
				<a href='". htmlspecialchars("tree.php?action=item_remove&id=" . $leaf["id"] . "&tree_id=$tree_id") . "'><img class='buttonSmall' src='images/delete_icon.gif' alt='Delete' align='middle'></a>\n
				</td></tr>\n";
			}
		}
	}else{
		print "<tr><td><em>No Graph Tree Items</em></td></tr>";
	}
}

function set_tree_visibility_status() {
	if (!isset($_REQUEST["subaction"])) {
		$headers = db_fetch_assoc("SELECT graph_tree_id, order_key FROM graph_tree_items WHERE device_id='0' AND local_graph_id='0' AND graph_tree_id='" . $_REQUEST["id"] . "'");

		foreach ($headers as $header) {
			$variable = "sess_tree_leaf_expand_" . $header["graph_tree_id"] . "_" . tree_tier_string($header["order_key"]);

			if (!isset($_SESSION[$variable])) {
				$_SESSION[$variable] = true;
			}
		}
	}else if ((get_request_var_request("subaction") == "expand_all") ||
	(get_request_var_request("subaction") == "colapse_all")) {

		$headers = db_fetch_assoc("SELECT graph_tree_id, order_key FROM graph_tree_items WHERE device_id='0' AND local_graph_id='0' AND graph_tree_id='" . $_REQUEST["id"] . "'");

		foreach ($headers as $header) {
			$variable = "sess_tree_leaf_expand_" . $header["graph_tree_id"] . "_" . tree_tier_string($header["order_key"]);

			if (get_request_var_request("subaction") == "expand_all") {
				$_SESSION[$variable] = true;
			}else{
				$_SESSION[$variable] = false;
			}
		}
	}else{
		$order_key = db_fetch_cell("SELECT order_key FROM graph_tree_items WHERE id=" . $_REQUEST["leaf_id"]);
		$variable = "sess_tree_leaf_expand_" . $_REQUEST["id"] . "_" . tree_tier_string($order_key);

		if (isset($_SESSION[$variable])) {
			if ($_SESSION[$variable]) {
				$_SESSION[$variable] = false;
			}else{
				$_SESSION[$variable] = true;
			}
		}else{
			$_SESSION[$variable] = true;
		}
	}
}

function get_visibility($leaf) {
	$tier = tree_tier($leaf["order_key"]);

	$tier_string = tree_tier_string($leaf["order_key"]);

	$variable = "sess_tree_leaf_expand_" . $leaf["graph_tree_id"] . "_" . $tier_string;

	/* you must always show the base tier */
	if ($tier <= 1) {
		return true;
	}

	/* get the default status */
	$default = true;
	if (isset($_SESSION[$variable])) {
		$default = $_SESSION[$variable];
	}

	/* now work backwards to get the current visibility stauts */
	$i = $tier;
	$effective = $default;
	while ($i > 1) {
		$i--;

		$parent_tier = tree_tier_string(substr($tier_string, 0, $i * CHARS_PER_TIER));
		$parent_variable = "sess_tree_leaf_expand_" . $leaf["graph_tree_id"] . "_" . $parent_tier;

		$effective = @$_SESSION[$parent_variable];

		if (!$effective) {
			return $effective;
		}
	}

	return $effective;
}

function get_icon($graph_tree_id, $order_key) {
	$variable = "sess_tree_leaf_expand_" . $graph_tree_id . "_" . tree_tier_string($order_key);

	if (isset($_SESSION[$variable])) {
		if ($_SESSION[$variable]) {
			$icon = "images/hide.gif";
		}else{
			$icon = "images/show.gif";
		}
	}else{
		$icon = "images/hide.gif";
	}

	return $icon;
}

/* tree_tier_string - returns the tier key information to be used to determine
 visibility status of the tree item.
 @param $order_key - the order key of the branch to fetch the depth for
 @param $chars_per_tier - the number of characters dedicated to each branch
 depth (tier). this is typically '3' in cacti.
 @returns - the string representing the leaf position
 */
function tree_tier_string($order_key, $chars_per_tier = CHARS_PER_TIER) {
	$new_string = preg_replace("/0+$/",'',$order_key);

	return $new_string;
}

function grow_dropdown_tree($tree_id, $form_name, $selected_tree_item_id) {
	include_once(CACTI_LIBRARY_PATH . "/tree.php");

	$tree = db_fetch_assoc("select
		graph_tree_items.id,
		graph_tree_items.title,
		graph_tree_items.order_key
		from graph_tree_items
		where graph_tree_items.graph_tree_id=$tree_id
		and graph_tree_items.title != ''
		order by graph_tree_items.order_key");

	print "<select name='$form_name'>\n";
	print "\t\t\t\t\t\t\t<option value='0'>[root]</option>\n";

	if (sizeof($tree) > 0) {
		foreach ($tree as $leaf) {
			$tier = tree_tier($leaf["order_key"]);
			$indent = str_repeat("---", ($tier));

			if ($selected_tree_item_id == $leaf["id"]) {
				$html_selected = " selected";
			}else{
				$html_selected = "";
			}

			print "\t\t\t\t\t\t\t<option value='" . $leaf["id"] . "'$html_selected>$indent " . $leaf["title"] . "</option>\n";
		}
	}

	print "</select>\n";
}

function grow_dhtml_trees() {
	include_once(CACTI_LIBRARY_PATH . "/tree.php");
	include_once(CACTI_LIBRARY_PATH . "/data_query.php");

	?>
<script type="text/javascript">
		<!--
			USETEXTLINKS = 1
			STARTALLOPEN = 0
			USEFRAMES = 0
			USEICONS = 0
			WRAPTEXT = 1
			PERSERVESTATE = 1
			HIGHLIGHT = 1
	<?php
	/* get current time */
	list($micro,$seconds) = explode(" ", microtime());
	$current_time = $seconds + $micro;
	$expand_devices = read_graph_config_option("expand_devices");

	if (!isset($_SESSION['dhtml_tree'])) {
		$dhtml_tree = create_dhtml_tree();
		$_SESSION['dhtml_tree'] = $dhtml_tree;
	}else{
		$dhtml_tree = $_SESSION['dhtml_tree'];
		if (($dhtml_tree[0] + read_graph_config_option("page_refresh") < $current_time) || ($expand_devices != $dhtml_tree[1])) {
			$dhtml_tree = create_dhtml_tree();
			$_SESSION['dhtml_tree'] = $dhtml_tree;
		}else{
			$dhtml_tree = $_SESSION['dhtml_tree'];
		}
	}

	$total_tree_items = sizeof($dhtml_tree) - 1;

	for ($i = 2; $i <= $total_tree_items; $i++) {
		print $dhtml_tree[$i];
	}
	?>
		//-->
		</script>
	<?php
}

function create_dhtml_tree() {
	require(CACTI_INCLUDE_PATH . "/graph_tree/graph_tree_arrays.php");

	/* Record Start Time */
	list($micro,$seconds) = explode(" ", microtime());
	$start = $seconds + $micro;

	$dhtml_tree = array();

	$dhtml_tree[0] = $start;
	$dhtml_tree[1] = read_graph_config_option("expand_devices");
	$dhtml_tree[2] = "\t\tfoldersTree = gFld(\"\", \"\")\n";
	$dhtml_tree[3] = "\t\t\tfoldersTree.xID = \"root\"\n";
	$i = 3;

	$tree_list = get_graph_tree_array();

	/* auth check for devices on the trees */
	if (read_config_option("auth_method") != AUTH_METHOD_NONE) {
		$current_user = db_fetch_row("select policy_devices from user_auth where id=" . $_SESSION["sess_user_id"]);

		$sql_join = "left join user_auth_perms on (device.id=user_auth_perms.item_id and user_auth_perms.type=3 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ")";

		if ($current_user["policy_devices"] == "1") {
			$sql_where = "and !(user_auth_perms.user_id is not null and graph_tree_items.device_id > 0)";
		}elseif ($current_user["policy_devices"] == "2") {
			$sql_where = "and !(user_auth_perms.user_id is null and graph_tree_items.device_id > 0)";
		}
	}else{
		$sql_join  = "";
		$sql_where = "";
	}

	if (sizeof($tree_list) > 0) {
		foreach ($tree_list as $tree) {
			$i++;
			$hierarchy = db_fetch_assoc("select
				graph_tree_items.id,
				graph_tree_items.title,
				graph_tree_items.order_key,
				graph_tree_items.device_id,
				graph_tree_items.device_grouping_type,
				device.description as hostname
				from graph_tree_items
				left join device on (device.id=graph_tree_items.device_id)
				$sql_join
				where graph_tree_items.graph_tree_id=" . $tree["id"] . "
				$sql_where
				and graph_tree_items.local_graph_id = 0
				order by graph_tree_items.order_key");

				$dhtml_tree[$i] = "\t\t\tou0 = insFld(foldersTree, gFld(\"" . $tree["name"] . "\", \"graph_view.php?action=tree&tree_id=" . $tree["id"] . "\"))\n";
				$i++;
				$dhtml_tree[$i] = "\t\t\tou0.xID = \"tree_" . $tree["id"] . "\"\n";

				if (sizeof($hierarchy) > 0) {
					foreach ($hierarchy as $leaf) {
						$i++;
						$tier = tree_tier($leaf["order_key"]);

						if ($leaf["device_id"] > 0) {
							$dhtml_tree[$i] = "\t\t\tou" . ($tier) . " = insFld(ou" . abs(($tier-1)) . ", gFld(\"Host: " . addslashes($leaf["hostname"]) . "\", \"graph_view.php?action=tree&tree_id=" . $tree["id"] . "&leaf_id=" . $leaf["id"] . "\"))\n";
							$i++;
							$dhtml_tree[$i] = "\t\t\tou" . ($tier) . ".xID = \"tree_" . $tree["id"] . "_leaf_" . $leaf["id"] . "\"\n";

							if (read_graph_config_option("expand_devices") == CHECKED) {
								if ($leaf["device_grouping_type"] == TREE_DEVICE_GROUPING_GRAPH_TEMPLATE) {
									$graph_templates = db_fetch_assoc("select
									graph_templates.id,
									graph_templates.name
									from (graph_local,graph_templates,graph_templates_graph)
									where graph_local.id=graph_templates_graph.local_graph_id
									and graph_templates_graph.graph_template_id=graph_templates.id
									and graph_local.device_id=" . $leaf["device_id"] . "
									group by graph_templates.id
									order by graph_templates.name");

									if (sizeof($graph_templates) > 0) {
										foreach ($graph_templates as $graph_template) {
											$i++;
											$dhtml_tree[$i] = "\t\t\tou" . ($tier+1) . " = insFld(ou" . ($tier) . ", gFld(\" " . addslashes($graph_template["name"]) . "\", \"graph_view.php?action=tree&tree_id=" . $tree["id"] . "&leaf_id=" . $leaf["id"] . "&device_group_data=graph_template:" . $graph_template["id"] . "\"))\n";
											$i++;
											$dhtml_tree[$i] = "\t\t\tou" . ($tier+1) . ".xID = \"tree_" . $tree["id"] . "_leaf_" . $leaf["id"] . "_hgd_gt_" . $graph_template["id"] . "\"\n";
										}
									}
								}else if ($leaf["device_grouping_type"] == TREE_DEVICE_GROUPING_DATA_QUERY_INDEX) {
									$data_queries = db_fetch_assoc("select
									snmp_query.id,
									snmp_query.name
									from (graph_local,snmp_query)
									where graph_local.snmp_query_id=snmp_query.id
									and graph_local.device_id=" . $leaf["device_id"] . "
									group by snmp_query.id
									order by snmp_query.name");

									array_push($data_queries, array(
									"id" => "0",
									"name" => "Non Query Based"
									));

									if (sizeof($data_queries) > 0) {
										foreach ($data_queries as $data_query) {
											/* fetch a list of field names that are sorted by the preferred sort field */
											$sort_field_data = get_formatted_data_query_indexes($leaf["device_id"], $data_query["id"]);
											if ($data_query["id"] == 0) {
												$non_template_graphs = db_fetch_cell("SELECT COUNT(*) FROM graph_local WHERE device_id='" . $leaf["device_id"] . "' AND snmp_query_id='0'");
											}else{
												$non_template_graphs = 0;
											}

											if ((($data_query["id"] == 0) && ($non_template_graphs > 0)) ||
											(($data_query["id"] > 0) && (sizeof($sort_field_data) > 0))) {
												$i++;
												$dhtml_tree[$i] = "\t\t\tou" . ($tier+1) . " = insFld(ou" . ($tier) . ", gFld(\" " . addslashes($data_query["name"]) . "\", \"graph_view.php?action=tree&tree_id=" . $tree["id"] . "&leaf_id=" . $leaf["id"] . "&device_group_data=data_query:" . $data_query["id"] . "\"))\n";
												$i++;
												$dhtml_tree[$i] = "\t\t\tou" . ($tier+1) . ".xID = \"tree_" . $tree["id"] . "_leaf_" . $leaf["id"] . "_hgd_dq_" . $data_query["id"] . "\"\n";

												if ($data_query["id"] > 0) {
													while (list($snmp_index, $sort_field_value) = each($sort_field_data)) {
														$i++;
														$dhtml_tree[$i] = "\t\t\tou" . ($tier+2) . " = insFld(ou" . ($tier+1) . ", gFld(\" " . addslashes($sort_field_value) . "\", \"graph_view.php?action=tree&tree_id=" . $tree["id"] . "&leaf_id=" . $leaf["id"] . "&device_group_data=data_query_index:" . $data_query["id"] . ":" . urlencode($snmp_index) . "\"))\n";
														$i++;
														$dhtml_tree[$i] = "\t\t\tou" . ($tier+2) . ".xID = \"tree_" . $tree["id"] . "_leaf_" . $leaf["id"] . "_hgd_dqi" . $data_query["id"] . "_" . urlencode($snmp_index) . "\"\n";
													}
												}
											}
										}
									}
								}
							}
						}else{
							$dhtml_tree[$i] = "\t\t\tou" . ($tier) . " = insFld(ou" . abs(($tier-1)) . ", gFld(\"" . addslashes($leaf["title"]) . "\", \"graph_view.php?action=tree&tree_id=" . $tree["id"] . "&leaf_id=" . $leaf["id"] . "\"))\n";
							$i++;
							$dhtml_tree[$i] = "\t\t\tou" . ($tier) . ".xID = \"tree_" . $tree["id"] . "_leaf_" . $leaf["id"] . "\"\n";
						}
					}
				}
		}
	}

	return $dhtml_tree;
}

function tree_authorized($tree_id) {
	global $current_user;

	if (read_config_option("auth_method") != AUTH_METHOD_NONE) {
		$tree_policy = db_fetch_cell("SELECT policy_trees FROM user_auth WHERE id=" . $_SESSION["sess_user_id"]);
		$user_trees  = db_fetch_assoc("SELECT item_id FROM user_auth_perms WHERE type=2 AND user_id=" . $_SESSION["sess_user_id"]);

		if ($tree_policy == 1) {
			$auth_sql = db_fetch_cell("SELECT count(*) FROM user_auth_perms AS ap
				WHERE ap.item_id=$tree_id AND type=2");

			if ($auth_sql) {
				return FALSE;
			}else{
				return TRUE;
			}
		}else{
			$auth_sql = db_fetch_cell("SELECT count(*) FROM user_auth_perms AS ap
				WHERE ap.item_id=$tree_id AND type=2");

			if ($auth_sql) {
				return TRUE;
			}else{
				return FALSE;
			}
		}
	}else{
		return TRUE;
	}
}

function device_authorized($device_id, $user) {
	$auth_sql = db_fetch_cell("SELECT item_id
		FROM user_auth_perms
		WHERE type=3
		AND user_id=" . $user["id"] . "
		AND item_id=$device_id");

	if ($user["policy_devices"] == 1) {
		if (empty($auth_sql)) {
			return TRUE;
		}else{
			return FALSE;
		}
	}else{
		if (($auth_sql)) {
			return FALSE;
		}else{
			return TRUE;
		}
	}
}

function graph_authorized($local_graph_id, $user) {
	$auth_sql = db_fetch_cell("SELECT item_id
		FROM user_auth_perms
		WHERE type=1
		AND user_id=" . $user["id"] . "
		AND item_id=$local_graph_id");

	if ($user["policy_graphs"] == 1) {
		if (empty($auth_sql)) {
			return TRUE;
		}else{
			return FALSE;
		}
	}else{
		if (($auth_sql)) {
			return FALSE;
		}else{
			return TRUE;
		}
	}
}

function template_authorized($graph_template_id, $user) {
	$auth_sql = db_fetch_cell("SELECT item_id
		FROM user_auth_perms
		WHERE type=4
		AND user_id=" . $user["id"] . "
		AND item_id=$graph_template_id");

	if ($user["policy_graph_templates"] == 1) {
		if (empty($auth_sql)) {
			return TRUE;
		}else{
			return FALSE;
		}
	}else{
		if (($auth_sql)) {
			return FALSE;
		}else{
			return TRUE;
		}
	}
}

function get_trees($tree_id) {
	global $current_user;

	$trees_where = "";
	$sql_where   = "";
	$items       = array();

	if ($tree_id == 0 || $tree_id == "-2") {
		/* all system trees */
		$sql_where .= "WHERE gt.user_id=0";
	}elseif ($tree_id == "-1") {
		/* all user trees */
		$sql_where .= "WHERE gt.user_id=" . $_SESSION["sess_user_id"];
	}else{
		/* specific tree */
		$sql_where .= "WHERE gt.id=" . $tree_id;
	}

	if (read_config_option("auth_method") != AUTH_METHOD_NONE) {
		$tree_policy = db_fetch_cell("SELECT policy_trees FROM user_auth WHERE id=" . $_SESSION["sess_user_id"]);
		$user_trees  = db_fetch_assoc("SELECT item_id FROM user_auth_perms WHERE type=2 AND user_id=" . $_SESSION["sess_user_id"]);

		if (sizeof($user_trees)) {
			foreach($user_trees AS $tree) {
				$trees_where .= (strlen($sql_where) ? ", " . $tree["item_id"] : "(");
			}
			$trees_where .= ")";

			if ($tree_policy == 1) {
				$sql_where = "gt.id NOT IN $trees_where";
			}else{
				$sql_where = "gt.id IN $trees_where";
			}
		}
	}

	$sql = "SELECT *
		FROM graph_tree AS gt
		$sql_where
		ORDER BY gt.name";

	$trees = db_fetch_assoc($sql);

	if (sizeof($trees)) {
	foreach($trees as $tree) {
		$items[] = array(
			"tree_id" => $tree["id"],
			"leaf_id" => 0,
			"type" => 'tree',
			"id" => $tree["id"],
			"name" => $tree["name"]
		);
	}
	}

	return $items;
}

function get_tree_leaf_items($tree_id, $leaf_id, $device_group_type, $include_parent = false) {
	global $current_user;
	require(CACTI_INCLUDE_PATH . "/graph_tree/graph_tree_arrays.php");

	// prototype
	// $items = array($tree_id, $leaf_id, $type, $id, $name);
	// $tree_id = 'Tree where item exists'
	// $leaf_id = 'Leaf where item exists'
	// $type = 'graph|device|site|leaf|dqn|dqi|gtn';
	// $id   = 'local_graph_id|device_id|site_id|leaf_id|dqn_id|dqi_id|gtn_id'

	// the following types are only valid if $device_group_data = true
	// dqn|dqi|gtn

	include(CACTI_INCLUDE_PATH . "/global_arrays.php");
	include_once(CACTI_LIBRARY_PATH . "/data_query.php");
	include_once(CACTI_LIBRARY_PATH . "/tree.php");
	include_once(CACTI_LIBRARY_PATH . "/html_utility.php");

	/* get the trees that the user has access to */
	$items = array();

	if ($tree_id <= 0) {
		return get_trees($tree_id);
	}elseif (tree_authorized($tree_id)) {
		if ($include_parent) {
			return get_trees($tree_id);
		}

		$search_key = "";

		if ($leaf_id > 0) {
			/* return leaf, site, device, graph template or data query items */
			$leaf = db_fetch_row("SELECT *
				FROM graph_tree_items
				WHERE id=$leaf_id");

			$leaf_type = get_tree_item_type($leaf_id);

			/* get the "starting leaf" if the user clicked on a specific branch */
			if (!empty($leaf_id)) {
				$search_key = substr($leaf["order_key"], 0, (tree_tier($leaf["order_key"]) * CHARS_PER_TIER));
			}
		}else{
			$leaf_type = "header";
		}

		$user = db_fetch_row("SELECT * FROM user_auth WHERE id=" . $_SESSION["sess_user_id"]);

		if ($leaf_type == "header") {
			if (read_config_option("auth_method") != AUTH_METHOD_NONE) {
				$tree_items = db_fetch_assoc("SELECT *
					FROM graph_tree_items
					WHERE graph_tree_id=$tree_id
					AND parent_id=$leaf_id");

				if (sizeof($tree_items)) {
					foreach($tree_items AS $item) {
						if ($item["local_graph_id"]) {
							if (graph_authorized($item["local_graph_id"], $user)) {
								$new_tree_items[] = $item;
							}
						}elseif ($item["device_id"]) {
							if (device_authorized($item["device_id"], $user)) {
								$new_tree_items[] = $item;
							}
						}else{
							$new_tree_items[] = $item;
						}
					}

					$tree_items = $new_tree_items;
				}
			}else{
				$tree_items = db_fetch_assoc("SELECT *
					FROM graph_tree_items
					WHERE graph_tree_id=$tree_id
					AND parent_id=$leaf_id");
			}

			if (sizeof($tree_items)) {
				foreach($tree_items as $item) {
					if ($item["local_graph_id"] > 0) {
						$items[] = array(
							"tree_id" => $tree_id,
							"leaf_id" => $item["id"],
							"type" => 'graph',
							"id" => $item["local_graph_id"],
							"name" => get_graph_title($item["local_graph_id"])
						);
					}elseif ($item["device_id"] > 0) {
						$items[] = array(
							"tree_id" => $tree_id,
							"leaf_id" => $item["id"],
							"type" => 'device',
							"id" => $item["device_id"],
							"name" => get_device_description($item["device_id"])
						);
					}else{
						$items[] = array(
							"tree_id" => $tree_id,
							"leaf_id" => $item["id"],
							"type" => 'header',
							"id" => $item["id"],
							"name" => $item["title"]
						);
					}
				}
			}

			return $items;
		}elseif ($leaf_type == "device") {
			if (read_graph_config_option("expand_devices") == CHECKED) {
				if ($leaf["device_grouping_type"] == TREE_DEVICE_GROUPING_GRAPH_TEMPLATE) {
					if ((isset($device_group_type)) && ($device_group_type[0] != 'gt')) {
						$items = get_device_grouping_graph_templates($leaf, $user);
					}
				}else{
					if (isset($device_group_type)) {
						if (($device_group_type[0] != 'dqi') && ($device_group_type[0] != 'dq')) {
							$items = get_device_grouping_data_queries($leaf);
						}elseif ($device_group_type[0] != 'dqi') {
							$items = get_device_grouping_data_query_items($leaf, $device_group_type);
						}
					}
				}
			}

			return $items;
		}else{
			return $items;
		}
	}else{
		return $items;
	}
}

function get_device_grouping_graph_templates($leaf, $user) {
	$graph_templates = db_fetch_assoc("SELECT
		graph_templates.id,
		graph_templates.name
		FROM (graph_local,graph_templates,graph_templates_graph)
		WHERE graph_local.id=graph_templates_graph.local_graph_id
		AND graph_templates_graph.graph_template_id=graph_templates.id
		AND graph_local.device_id=" . $leaf["device_id"] . "
		" . (empty($_REQUEST["graph_template_id"]) ? "" : "AND graph_templates.id=$graph_template_id") . "
		GROUP BY graph_templates.id
		ORDER BY graph_templates.name");

	/* for graphs without a template */
	$items[] = array(
		"tree_id" => $leaf["graph_tree_id"],
		"leaf_id" => $leaf["id"],
		"type" => 'gt',
		"id" => 0,
		"name" => '(No Graph Template)'
	);

	if (sizeof($graph_templates) > 0) {
	foreach ($graph_templates as $graph_template) {
		if (template_authorized($graph_template["id"], $user)) {
			$items[] = array(
				"tree_id" => $leaf["graph_tree_id"],
				"leaf_id" => $leaf["id"],
				"type" => 'gt',
				"id" => $graph_template["id"],
				"name" => $graph_template["name"]
			);
		}
	}
	}

	return $items;
}

function get_device_grouping_data_queries($leaf) {
	$data_queries = db_fetch_assoc("SELECT
		snmp_query.id,
		snmp_query.name
		FROM (graph_local,snmp_query)
		WHERE graph_local.snmp_query_id=snmp_query.id
		AND graph_local.device_id=" . $leaf["device_id"] . "
		" . (!isset($_REQUEST["data_query_id"]) ? "" : "and snmp_query.id=$data_query_id") . "
		GROUP BY snmp_query.id
		ORDER BY snmp_query.name");

	/* for graphs without a template */
	$items[] = array(
		"tree_id" => $leaf["graph_tree_id"],
		"leaf_id" => $leaf["id"],
		"type" => 'dq',
		"id" => 0,
		"name" => '(Non Query Based)'
	);

	if (sizeof($data_queries) > 0) {
	foreach ($data_queries as $data_query) {
		$items[] = array(
			"tree_id" => $leaf["graph_tree_id"],
			"leaf_id" => $leaf["id"],
			"type" => 'dq',
			"id" => $data_query["id"],
			"name" => $data_query["name"]);
	}
	}

	return $items;
}

function get_device_grouping_data_query_items($leaf, $device_group_data) {
	$data_query_id = $device_group_data[1];
	$items = array();

	if ($data_query_id > 0) {
		$data_queries = db_fetch_assoc("select
			snmp_query.id,
			snmp_query.name
			FROM (graph_local,snmp_query)
			WHERE graph_local.snmp_query_id=snmp_query.id
			AND graph_local.device_id=" . $leaf["device_id"] . "
			AND snmp_query.id=$data_query_id
			GROUP BY snmp_query.id
			ORDER BY snmp_query.name");
	}

	if (sizeof($data_queries) > 0) {
	foreach ($data_queries as $data_query) {
		/* fetch a list of field names that are sorted by the preferred sort field */
		$sort_field_data = get_formatted_data_query_indexes($leaf["device_id"], $data_query["id"]);

		if (($data_query["id"] > 0) && (sizeof($sort_field_data) > 0)) {
			while (list($snmp_index, $sort_field_value) = each($sort_field_data)) {
				$items[] = array("tree_id" => $leaf["graph_tree_id"],
					"leaf_id" => $leaf["id"],
					"type" => "graph",
					"id" => $snmp_index,
					"name" => $sort_field_value
				);
			}
		}
	}
	}

	return $items;
}

function get_graph_tree_content($tree_id, $leaf_id, $device_group_data) {
	global $current_user, $graphs_per_page;

	include(CACTI_INCLUDE_PATH . "/global_arrays.php");
	require(CACTI_INCLUDE_PATH . "/graph_tree/graph_tree_arrays.php");
	include_once(CACTI_LIBRARY_PATH . "/data_query.php");
	include_once(CACTI_LIBRARY_PATH . "/tree.php");
	include_once(CACTI_LIBRARY_PATH . "/html_utility.php");
	include_once(CACTI_LIBRARY_PATH . "/graph.php");
	define("MAX_DISPLAY_PAGES", 21);

	if (empty($tree_id)) { return; }

	$sql_where       = "";
	$sql_join        = "";
	$title           = "";
	$title_delimeter = "";
	$search_key      = "";

	$leaf      = db_fetch_row("SELECT title, device_id, device_grouping_type
					FROM graph_tree_items
					WHERE id=$leaf_id");

	$leaf_type = get_tree_item_type($leaf_id);

	/* get the "starting leaf" if the user clicked on a specific branch */
	//if (!empty($leaf_id)) {
	//	$search_key = substr($leaf["order_key"], 0, (tree_tier($leaf["order_key"]) * CHARS_PER_TIER));
	//}

	/* graph permissions */
	if (read_config_option("auth_method") != AUTH_METHOD_NONE) {
		/* get policy information for the sql where clause */
		$sql_where = get_graph_permissions_sql($current_user["policy_graphs"], $current_user["policy_devices"], $current_user["policy_graph_templates"]);
		$sql_where = (empty($sql_where) ? "" : "AND $sql_where");
		$sql_join = "
			LEFT JOIN device ON (device.id=graph_local.device_id)
			LEFT JOIN graph_templates ON (graph_templates.id=graph_local.graph_template_id)
			LEFT JOIN user_auth_perms ON ((graph_templates_graph.local_graph_id=user_auth_perms.item_id AND user_auth_perms.type=1 AND user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (device.id=user_auth_perms.item_id and user_auth_perms.type=3 and user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . ") OR (graph_templates.id=user_auth_perms.item_id AND user_auth_perms.type=4 AND user_auth_perms.user_id=" . $_SESSION["sess_user_id"] . "))";
	}

	/* get information for the headers */
	if (!empty($tree_id)) { $tree_name = db_fetch_cell("SELECT name FROM graph_tree WHERE id=$tree_id"); }
	if (!empty($leaf_id)) { $leaf_name = $leaf["title"]; }
	if (!empty($leaf_id)) { $device_name = db_fetch_cell("SELECT device.description FROM (graph_tree_items,device) WHERE graph_tree_items.device_id=device.id AND graph_tree_items.id=$leaf_id"); }

	$device_group_data_array = $device_group_data;

	if ($device_group_data_array[0] == "gt") {
		$device_group_data_name = "Graph Template: " . db_fetch_cell("select name from graph_templates where id=" . $device_group_data_array[1]);
		$graph_template_id = $device_group_data_array[1];
	}elseif ($device_group_data_array[0] == "dq") {
		$device_group_data_name = "Graph Template: " . (empty($device_group_data_array[1]) ? "Non Query Based" : db_fetch_cell("select name from snmp_query where id=" . $device_group_data_array[1]));
		$data_query_id = $device_group_data_array[1];
	}elseif ($device_group_data_array[0] == "dqi") {
		$device_group_data_name = "Graph Template: " . (empty($device_group_data_array[1]) ? "Non Query Based" : db_fetch_cell("select name from snmp_query where id=" . $device_group_data_array[1])) . "-> " . (empty($device_group_data_array[2]) ? "Template Based" : get_formatted_data_query_index($leaf["device_id"], $device_group_data_array[1], $device_group_data_array[2]));
		$data_query_id = $device_group_data_array[1];
		$data_query_index = $device_group_data_array[2];
	}

	if (!empty($tree_name)) { $title .= $title_delimeter . "Tree: $tree_name"; $title_delimeter = "-> "; }
	if (!empty($leaf_name)) { $title .= $title_delimeter . "Leaf: $leaf_name"; $title_delimeter = "-> "; }
	if (!empty($device_name)) { $title .= $title_delimeter . "Host: $device_name"; $title_delimeter = "-> "; }
	if (!empty($device_group_data_name)) { $title .= $title_delimeter . " $device_group_data_name"; $title_delimeter = "-> "; }

	/* ================= input validation ================= */
	input_validate_input_number(get_request_var_request("graphs"));
	input_validate_input_number(get_request_var_request("page"));
	/* ==================================================== */

	/* if the user pushed the 'clear' button */
	if (isset($_REQUEST["clear_filter"])) {
		kill_session_var("sess_graph_view_graphs");
		kill_session_var("sess_graph_view_filter");
		kill_session_var("sess_graph_view_page");

		unset($_REQUEST["graphs"]);
		unset($_REQUEST["filter"]);
		unset($_REQUEST["page"]);

		$changed = true;
	}else{
		/* if any of the settings changed, reset the page number */
		$changed = 0;
		$changed += check_changed("graphs",          "sess_graph_view_graphs");
		$changed += check_changed("filter",          "sess_graph_view_filter");
		$changed += check_changed("action",          "sess_graph_view_action");
	}

	if (isset($_SESSION["sess_graph_view_tree_id"])) {
		if ($_SESSION["sess_graph_view_tree_id"] != $tree_id) {
			$changed += 1;
		}
	}
	$_SESSION["sess_graph_view_tree_id"] = $tree_id;

	if (isset($_SESSION["sess_graph_view_leaf_id"])) {
		if ($_SESSION["sess_graph_view_leaf_id"] != $leaf_id) {
			$changed += 1;
		}
	}
	$_SESSION["sess_graph_view_leaf_id"] = $leaf_id;

	if (isset($_SESSION["sess_graph_view_device_group_data"])) {
		if ($_SESSION["sess_graph_view_device_group_data"] != $device_group_data) {
			$changed += 1;
		}
	}
	$_SESSION["sess_graph_view_device_group_data"] = $device_group_data;

	if ($changed) {
		$_REQUEST["page"] = 1;
	}

	load_current_session_value("page",   "sess_graph_view_page",   "1");
	load_current_session_value("graphs", "sess_graph_view_graphs", read_graph_config_option("treeview_graphs_per_page"));
	load_current_session_value("filter", "sess_graph_view_filter", "");
	load_current_session_value("thumbnails", "sess_graph_view_thumbnails", (read_graph_config_option("thumbnail_section_tree_2") == CHECKED ? "true":""));

	$graph_list = array();

	if (($leaf_type == "header") || (empty($leaf_id))) {
		if (strlen(get_request_var_request("filter"))) {
			$sql_where = "AND (title_cache LIKE '%" . $_REQUEST["filter"] . "%' OR graph_templates_graph.title LIKE '%" . $_REQUEST["filter"] . "%')";
		}

		$graph_list = db_fetch_assoc("SELECT
			graph_tree_items.id,
			graph_tree_items.title,
			graph_tree_items.local_graph_id,
			graph_tree_items.rra_id,
			graph_templates_graph.height,
			graph_templates_graph.width,
			graph_templates_graph.title_cache as title_cache,
			graph_templates_graph.image_format_id
			FROM (graph_tree_items,graph_local)
			LEFT JOIN graph_templates_graph ON (graph_tree_items.local_graph_id=graph_templates_graph.local_graph_id AND graph_tree_items.local_graph_id>0)
			$sql_join
			WHERE graph_tree_items.graph_tree_id=$tree_id
			AND graph_local.id=graph_templates_graph.local_graph_id
			AND graph_tree_items.local_graph_id>0
			$sql_where
			GROUP BY graph_tree_items.id");
	}elseif ($leaf_type == "device") {
		/* graph template grouping */
		if ($leaf["device_grouping_type"] == TREE_DEVICE_GROUPING_GRAPH_TEMPLATE) {
			$graph_templates = db_fetch_assoc("SELECT
				graph_templates.id,
				graph_templates.name
				FROM (graph_local,graph_templates,graph_templates_graph)
				WHERE graph_local.id=graph_templates_graph.local_graph_id
				AND graph_templates_graph.graph_template_id=graph_templates.id
				AND graph_local.device_id=" . $leaf["device_id"] . "
				" . (empty($graph_template_id) ? "" : "AND graph_templates.id=$graph_template_id") . "
				GROUP BY graph_templates.id
				ORDER BY graph_templates.name");

			/* for graphs without a template */
			array_push($graph_templates, array(
				"id" => "0",
				"name" => "(No Graph Template)"
				));

			if (sizeof($graph_templates) > 0) {
				foreach ($graph_templates as $graph_template) {
					if (strlen(get_request_var_request("filter"))) {
						$sql_where = "AND (title_cache LIKE '%" . $_REQUEST["filter"] . "%')";
					}

					$graphs = db_fetch_assoc("SELECT
						graph_templates_graph.title_cache,
						graph_templates_graph.local_graph_id,
						graph_templates_graph.height,
						graph_templates_graph.width,
						graph_templates_graph.image_format_id
						FROM (graph_local,graph_templates_graph)
						$sql_join
						WHERE graph_local.id=graph_templates_graph.local_graph_id
						AND graph_local.graph_template_id=" . $graph_template["id"] . "
						AND graph_local.device_id=" . $leaf["device_id"] . "
						$sql_where
						ORDER BY graph_templates_graph.title_cache");

					/* let's sort the graphs naturally */
					if (sizeof($graphs)) {
						usort($graphs, 'naturally_sort_graphs');

						foreach ($graphs as $graph) {
							$graph["graph_template_name"] = $graph_template["name"];
							array_push($graph_list, $graph);
						}
					}
				}
			}
			/* data query index grouping */
		}elseif ($leaf["device_grouping_type"] == TREE_DEVICE_GROUPING_DATA_QUERY_INDEX) {
			$data_queries = db_fetch_assoc("SELECT
				snmp_query.id,
				snmp_query.name
				FROM (graph_local,snmp_query)
				WHERE graph_local.snmp_query_id=snmp_query.id
				AND graph_local.device_id=" . $leaf["device_id"] . "
				" . (!isset($data_query_id) ? "" : "and snmp_query.id=$data_query_id") . "
				GROUP BY snmp_query.id
				ORDER BY snmp_query.name");

			/* for graphs without a data query */
			if (empty($data_query_id)) {
				array_push($data_queries, array(
					"id" => "0",
					"name" => "Non Query Based"
					));
			}

			if (sizeof($data_queries) > 0) {
				foreach ($data_queries as $data_query) {
					/* fetch a list of field names that are sorted by the preferred sort field */
					$sort_field_data = get_formatted_data_query_indexes($leaf["device_id"], $data_query["id"]);

					if (strlen(get_request_var_request("filter"))) {
						$sql_where = "AND (title_cache LIKE '%" . $_REQUEST["filter"] . "%')";
					}

					/* grab a list of all graphs for this device/data query combination */
					$graphs = db_fetch_assoc("SELECT
						graph_templates_graph.title_cache,
						graph_templates_graph.local_graph_id,
						graph_templates_graph.height,
						graph_templates_graph.width,
						graph_templates_graph.image_format_id,
						graph_local.snmp_index
						FROM (graph_local, graph_templates_graph)
						$sql_join
						WHERE graph_local.id=graph_templates_graph.local_graph_id
						AND graph_local.snmp_query_id=" . $data_query["id"] . "
						AND graph_local.device_id=" . $leaf["device_id"] . "
						" . (empty($data_query_index) ? "" : "and graph_local.snmp_index='$data_query_index'") . "
						$sql_where
						GROUP BY graph_templates_graph.local_graph_id
						ORDER BY graph_templates_graph.title_cache");

					/* re-key the results on data query index */
					$snmp_index_to_graph = array();
					if (sizeof($graphs)) {
						/* let's sort the graphs naturally */
						usort($graphs, 'naturally_sort_graphs');

						foreach ($graphs as $graph) {
							$snmp_index_to_graph{$graph["snmp_index"]} = array(
								"local_graph_id"	=> $graph["local_graph_id"],
								"title_cache"		=> $graph["title_cache"],
								"image_format_id"	=> $graph["image_format_id"],
							);
							$graphs_height[$graph["local_graph_id"]] = $graph["height"];
						}
					}

					/* using the sorted data as they key; grab each snmp index from the master list */
					while (list($snmp_index, $sort_field_value) = each($sort_field_data)) {
						/* render each graph for the current data query index */
						if (isset($snmp_index_to_graph[$snmp_index])) {
							#while (list($local_graph_id, $graph_title) = each($snmp_index_to_graph[$snmp_index])) {
							foreach ($snmp_index_to_graph as $graph) {
								/* reformat the array so it's compatable with the html_graph* area functions */
								array_push($graph_list, array(
									"data_query_name"   => $data_query["name"],
									"sort_field_value" 	=> $sort_field_value,
									"local_graph_id"    => $graph["local_graph_id"],
									"title_cache"       => $graph["title_cache"],
									"image_format_id"   => $graph["image_format_id"],
									"height"            => $graphs_height[$graph["local_graph_id"]]
								));
							}
						}
					}
				}
			}
		}
	}

	$total_rows = sizeof($graph_list);

	if (read_graph_config_option("timespan_sel") == CHECKED) {
		graph_view_timespan_selector();
	}

	graph_view_search_filter();

	?>
	<script type='text/javascript'>
	<!--
	$(".graphimage").ZoomGraph({ inputfieldStartTime : 'date1', inputfieldEndTime : 'date2'});
	function pageChange(page) {
		strURL = '?action=ajax_tree_graphs&page=' + page;
		$.get("graph_view.php" + strURL, function(data) {
			$("#graphs").html(data);
		});
	}
	-->
	</script>
	<?php

	print "<table cellpadding='0' cellspacing='0' style='width:100%;border:1px solid #BEBEBE;'>\n";
	/* generate page list */
	if ($total_rows > get_request_var_request("graphs")) {
		$url_page_select = get_page_list($_REQUEST["page"], MAX_DISPLAY_PAGES, $_REQUEST["graphs"], $total_rows, "pageChange");

		$nav = "\t\t\t<tr class='rowHeader'>
				<td colspan='11'>
					<table width='100%' cellspacing='0' cellpadding='0' border='0'>
						<tr>
							<td align='left' style='width:100px;' class='textHeaderDark'>";
		if ($_REQUEST["page"] > 1) { $nav .= "<a class='linkOverDark' href='#' onClick='pageChange(" . ($_REQUEST["page"]-1) . ")'>&lt;&lt;&nbsp;" . __("Previous") . "</a>"; }
		$nav .= "</td>\n
							<td align='center' class='textHeaderDark'>
								" . __("Showing Graphs") . " " . ((get_request_var_request("graphs")*(get_request_var_request("page")-1))+1) . " " . __("to") . " " . ((($total_rows < read_graph_config_option("treeview_graphs_per_page")) || ($total_rows < (get_request_var_request("graphs")*get_request_var_request("page")))) ? $total_rows : (get_request_var_request("graphs")*get_request_var_request("page"))) . " " . __("of") . " $total_rows [$url_page_select]
							</td>\n
							<td align='right' style='width:100px;' class='textHeaderDark'>";
		if (($_REQUEST["page"] * $_REQUEST["graphs"]) < $total_rows) { $nav .= "<a class='linkOverDark' href='#' onClick='pageChange(" . ($_REQUEST["page"]+1) . ")'>". __("Next") . " &gt;&gt;</a>"; }
		$nav .= "</td>\n
						</tr>
					</table>
				</td>
			</tr>\n";
	}else{
		$nav = "<tr class='rowHeader'>
				<td colspan='11'>
					<table width='100%' cellspacing='0' cellpadding='0' border='0'>
						<tr>
							<td align='center' class='textHeaderDark'>
								" . __("Showing All Graphs") . (strlen(get_request_var_request("filter")) ? " [ " . __("Filter") . " '" . get_request_var_request("filter") . "' ". __("Applied") . " ]" : "") . "
							</td>
						</tr>
					</table>
				</td>
			</tr>\n";
	}

	print $nav;

	/* start graph display */
	print "\t\t\t<tr class='rowSubHeaderAlt'><td width='390' colspan='10' class='textHeaderLight'>$title</td></tr>";

	$i = $_REQUEST["graphs"] * ($_REQUEST["page"] - 1);
	$last_graph = $i + $_REQUEST["graphs"];

	$new_graph_list = array();
	while ($i < $total_rows && $i < $last_graph) {
		$new_graph_list[] = $graph_list[$i];
		$i++;
	}

	if (get_request_var_request("thumbnails") == "true") {
		html_graph_thumbnail_area($new_graph_list, "", "view_type=tree&graph_start=" . get_current_graph_start() . "&graph_end=" . get_current_graph_end());
	}else{
		html_graph_area($new_graph_list, "", "view_type=tree&graph_start=" . get_current_graph_start() . "&graph_end=" . get_current_graph_end());
	}

	print $nav;

	print "</table>\n";
}

function find_first_folder_url() {
	$default_tree_id = read_graph_config_option("default_tree_id");

	/* see if the user selected a default graph tree */
	$use_tree_id = 0;
	if (empty($default_tree_id)) {
		$tree_list = get_graph_tree_array();

		if (sizeof($tree_list) > 0) {
			$use_tree_id = $tree_list[0]["id"];
		}
	}else{
		$use_tree_id = $default_tree_id;
	}

	if (!empty($use_tree_id)) {
		/* find the first clickable item in the tree */
		$hierarchy = db_fetch_assoc("select
			graph_tree_items.id,
			graph_tree_items.device_id
			from graph_tree_items
			where graph_tree_items.graph_tree_id=$use_tree_id
			and graph_tree_items.local_graph_id = 0
			order by graph_tree_items.order_key");

		if (sizeof($hierarchy) > 0) {
			return htmlspecialchars("graph_view.php?action=tree&tree_id=$use_tree_id&leaf_id=" . $hierarchy[0]["id"] . "&select_first=true");
		}else{
			return htmlspecialchars("graph_view.php?action=tree&tree_id=$use_tree_id&select_first=true");
		}
	}

	return;
}

function draw_tree_header_row($tree_id, $tree_item_id, $current_tier, $current_title, $use_expand_contract, $expand_contract_status, $show_url) {
	/* start the nested table for the heading */
	print "<tr><td colspan='2'><table width='100%' cellpadding='2' cellspacing='1' border='0'><tr>\n";

	/* draw one vbar for each tier */
	for ($j=0;($j<($current_tier-1));$j++) {
		print "<td width='10'></td>\n";
	}

	/* draw the '+' or '-' icons if configured to do so */
	if (($use_expand_contract) && (!empty($current_title))) {
		if ($expand_contract_status == "1") {
			$other_status = '0';
			$ec_icon = 'show';
		}else{
			$other_status = '1';
			$ec_icon =  'hide';
		}

		print "<td align='center' width='1%'><a
			href='" . htmlspecialchars("graph_view.php?action=tree&tree_id=$tree_id&hide=$other_status&branch_id=$tree_item_id") . "'>
			<img src='images/$ec_icon.gif' alt=''></a></td>\n";
	}elseif (!($use_expand_contract) && (!empty($current_title))) {
		print "<td width='10'></td>\n";
	}

	/* draw the actual cell containing the header */
	if (!empty($current_title)) {
		print "<td style='white-space:nowrap;'>
			" . (($show_url == true) ? "<a href='" . htmlspecialchars("graph_view.php?action=tree&tree_id=$tree_id&start_branch=$tree_item_id") . "'>" : "") . $current_title . (($show_url == true) ? "</a>" : "") . "&nbsp;</td>\n";
	}

	/* end the nested table for the heading */
	print "</tr></table></td></tr>\n";
}

function draw_tree_graph_row($already_open, $graph_counter, $next_leaf_type, $current_tier, $local_graph_id, $rra_id, $graph_title) {
	/* start the nested table for the graph group */
	if ($already_open == false) {
		print "<tr><td><table width='100%' cellpadding='2' cellspacing='1'><tr>\n";

		/* draw one vbar for each tier */
		for ($j=0;($j<($current_tier-1));$j++) {
			print "<td width='10'></td>\n";
		}

		print "<td><table width='100%' cellspacing='0' cellpadding='2'><tr>\n";

		$already_open = true;
	}

	/* print out the actual graph html */
	if (read_graph_config_option("thumbnail_section_tree_1") == CHECKED) {
		if (read_graph_config_option("timespan_sel") == CHECKED) {
			print "<td><a href='" . htmlspecialchars("graph.php?local_graph_id=$local_graph_id&rra_id=all") . "'><img align='middle' alt='$graph_title' class='graphimage' id='graph_$local_graph_id'
				src='" . htmlspecialchars("graph_image.php?action=view&local_graph_id=$local_graph_id&rra_id=0&graph_start=" . get_current_graph_start() . "&graph_end=" . get_current_graph_end() . '&graph_height=' .
			read_graph_config_option("default_height") . '&graph_width=' . read_graph_config_option("default_width") . "&graph_nolegend=true") . "' border='0'></a></td>\n";

			/* if we are at the end of a row, start a new one */
			if ($graph_counter % read_graph_config_option("num_columns") == 0) {
				print "</tr><tr>\n";
			}
		}else{
			print "<td><a href='" . htmlspecialchars("graph.php?local_graph_id=$local_graph_id&rra_id=all") . "'><img align='middle' alt='$graph_title' class='graphimage' id='graph_$local_graph_id'
				src='" . htmlspecialchars("graph_image.php?action=view&local_graph_id=$local_graph_id&rra_id=$rra_id&graph_start=" . -(db_fetch_cell("select timespan from rra where id=$rra_id")) . '&graph_height=' .
			read_graph_config_option("default_height") . '&graph_width=' . read_graph_config_option("default_width") . "&graph_nolegend=true") . "' border='0'></a></td>\n";

			/* if we are at the end of a row, start a new one */
			if ($graph_counter % read_graph_config_option("num_columns") == 0) {
				print "</tr><tr>\n";
			}
		}
	}else{
		if (read_graph_config_option("timespan_sel") == CHECKED) {
			print "<td><a href='" . htmlspecialchars("graph.php?local_graph_id=$local_graph_id&rra_id=all") . "'><img class='graphimage' id='graph_$local_graph_id' src='" . htmlspecialchars("graph_image.php?action=view&local_graph_id=$local_graph_id&rra_id=0&graph_start=" . get_current_graph_start() . "&graph_end=" . get_current_graph_end()) . "' border='0' alt='$graph_title'></a></td>";
			print "</tr><tr>\n";
		}else{
			print "<td><a href='" . htmlspecialchars("graph.php?local_graph_id=$local_graph_id&rra_id=all") . "'><img class='graphimage' id='graph_$local_graph_id' src='" . htmlspecialchars("graph_image.php?action=view&local_graph_id=$local_graph_id&rra_id=$rra_id") . "' border='0' alt='$graph_title'></a></td>";
			print "</tr><tr>\n";
		}
	}

	/* if we are at the end of the graph group, end the nested table */
	if ($next_leaf_type != "graph") {
		print "</tr></table></td>\n";
		print "</tr></table></td></tr>\n";

		$already_open = false;
	}

	return $already_open;
}

function draw_tree_dropdown($current_tree_id) {
	$html = "";

	$tree_list = get_graph_tree_array();

	if (isset($_GET["tree_id"])) {
		$_SESSION["sess_view_tree_id"] = $current_tree_id;
	}

	/* if there is a current tree, make sure it still exists before going on */
	if ((!empty($_SESSION["sess_view_tree_id"])) && (db_fetch_cell("select id from graph_tree where id=" . $_SESSION["sess_view_tree_id"]) == "")) {
		$_SESSION["sess_view_tree_id"] = 0;
	}

	/* set a default tree if none is already selected */
	if (empty($_SESSION["sess_view_tree_id"])) {
		if (db_fetch_cell("select id from graph_tree where id=" . read_graph_config_option("default_tree_id")) > 0) {
			$_SESSION["sess_view_tree_id"] = read_graph_config_option("default_tree_id");
		}else{
			if (sizeof($tree_list) > 0) {
				$_SESSION["sess_view_tree_id"] = $tree_list[0]["id"];
			}
		}
	}

	/* make the dropdown list of trees */
	if (sizeof($tree_list) > 1) {
		$html ="<form name='form_tree_id' action='graph_view.php'>
			<td valign='middle' height='30'>\n
				<table width='100%' cellspacing='0' cellpadding='0'>\n
					<tr>\n
						<td width='200' class='textHeader'>\n
							&nbsp;&nbsp;" . __("Select a Graph Hierarchy") . ":&nbsp;\n
						</td>\n
						<td>\n
							<select name='cbo_tree_id' onChange='window.location=document.form_tree_id.cbo_tree_id.options[document.form_tree_id.cbo_tree_id.selectedIndex].value'>\n";

		foreach ($tree_list as $tree) {
			$html .= "\t\t\t\t\t\t\t<option value='graph_view.php?action=tree&tree_id=" . $tree["id"] . "'";
			if ($_SESSION["sess_view_tree_id"] == $tree["id"]) { $html .= " selected"; }
			$html .= ">" . $tree["name"] . "</option>\n";
		}

		$html .= "</select>\n";
		$html .= "</td></tr></table></td></form>\n";
	}elseif (sizeof($tree_list) == 1) {
		/* there is only one tree; use it */
	}

	return $html;
}

function naturally_sort_graphs($a, $b) {
	return strnatcasecmp($a['title_cache'], $b['title_cache']);
}
