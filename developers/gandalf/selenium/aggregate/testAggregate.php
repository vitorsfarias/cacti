<?php
# ----------------------------------------------------------------------------------
# before running this script, you will have to start the
# selenium server by executing
#    /usr/bin/selenium-server &
#
# this script does not authenticate against cacti
# so it requires to deactivate Settings -> Authenticaction -> Login
# 
# without specifiying the /path/to/firefox-bin, selenium will call a shell script only.
# this will result in browser sessions not being terminated
# remember to check your /path/to/firefox-bin in case test fails
# ----------------------------------------------------------------------------------
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

# unless a hook for 'global_constants' is available, all DEFINEs go here
define("AGGREGATE_GRAPH_TYPE_KEEP", 0);
define("GRAPH_ITEM_TYPE_COMMENT", 1);
define("GRAPH_ITEM_TYPE_HRULE",   2);
define("GRAPH_ITEM_TYPE_VRULE",   3);
define("GRAPH_ITEM_TYPE_LINE1",   4);
define("GRAPH_ITEM_TYPE_LINE2",   5);
define("GRAPH_ITEM_TYPE_LINE3",   6);
define("GRAPH_ITEM_TYPE_AREA",    7);
define("GRAPH_ITEM_TYPE_STACK",   8);
define("GRAPH_ITEM_TYPE_GPRINT",  9);
define("GRAPH_ITEM_TYPE_LEGEND", 10);

define("AGGREGATE_TOTAL_NONE", 1);
define("AGGREGATE_TOTAL_ALL", 2);
define("AGGREGATE_TOTAL_ONLY", 3);

define("AGGREGATE_TOTAL_TYPE_SIMILAR", 1);
define("AGGREGATE_TOTAL_TYPE_ALL", 2);

define("AGGREGATE_ORDER_NONE", 1);
define("AGGREGATE_ORDER_DS_GRAPH", 2);
define("AGGREGATE_ORDER_GRAPH_DS", 3);

$agg_graph_types = array(
AGGREGATE_GRAPH_TYPE_KEEP 	=> "Keep Graph Types",
GRAPH_ITEM_TYPE_STACK		=> "Convert to AREA/STACK Graph",
GRAPH_ITEM_TYPE_LINE1 		=> "Convert to LINE1 Graph",
#		GRAPH_ITEM_TYPE_LINE2 		=> "Convert to LINE2 Graph",
#		GRAPH_ITEM_TYPE_LINE3 		=> "Convert to LINE3 Graph",
);

$agg_totals = array(
AGGREGATE_TOTAL_NONE 		=> "No Totals",
AGGREGATE_TOTAL_ALL	 		=> "Print all Legend Items",
AGGREGATE_TOTAL_ONLY 		=> "Print totaling Legend Items Only",
);

$agg_totals_type = array(
AGGREGATE_TOTAL_TYPE_SIMILAR=> "Total Similar Data Sources",
AGGREGATE_TOTAL_TYPE_ALL 	=> "Total All Data Sources",
);

$agg_order_types = array(
AGGREGATE_ORDER_NONE => "No Reordering",
AGGREGATE_ORDER_DS_GRAPH => "Data Source, Graph",
#AGGREGATE_ORDER_GRAPH_DS => "Graph, Data Source",
);

class testAggregate extends PHPUnit_Extensions_SeleniumTestCase
{

	protected function setUp()
	{
		$this->setBrowser('*firefox /usr/lib64/firefox-3.5/firefox');
		$this->setBrowserUrl('http://localhost/');
	}

	private function _run_test($title, $agt, $at, $att, $aot)
	{
		$this->click("//input[@value='Go']");
		$this->waitForPageToLoad("30000");
		$this->click("all");
		$this->select("drp_action", "label=Create Aggregate Graph");
		$this->click("//div[@id='main']/form/table[2]/tbody/tr/td[3]/input");
		$this->waitForPageToLoad("30000");
		$this->select("aggregate_graph_type", "label=$agt");
		$this->select("aggregate_total", "label=$at");
		$this->select("aggregate_total_type", "label=$att");
		$this->type("aggregate_total_prefix", "$att");
		$this->select("aggregate_order_type", "label=$aot");
		$this->type("title_format", "Aggregate - Traffic ($title, $agt, $at, $att, $aot)");
		if ($this->isElementPresent("agg_color_1") &&
			$this->isElementPresent("agg_color_5") &&
			$this->isElementPresent("agg_color_6") &&
			$this->isElementPresent("agg_color_10")) 
		{
			$this->select("agg_color_1", "label=Green: dark-light, 16");
			$this->select("agg_color_5", "label=Red: light yellow-dark red, 8");
			$this->select("agg_color_6", "label=Yellow: light-dark, 4");
			$this->select("agg_color_10", "label=Red: light-dark, 16");
			$this->click("agg_total_1");
			$this->click("agg_total_2");
			$this->click("agg_total_3");
			$this->click("agg_total_4");
			$this->click("agg_total_5");
			$this->click("agg_total_6");
			$this->click("agg_total_7");
			$this->click("agg_total_8");
			$this->click("agg_total_9");
			$this->click("agg_total_10");
		} else {
			$this->select("agg_color_1", "label=Green: dark-light, 16");
			$this->select("agg_color_5", "label=Yellow: light-dark, 4");
			$this->click("agg_total_1");
			$this->click("agg_total_2");
			$this->click("agg_total_3");
			$this->click("agg_total_4");
			$this->click("agg_total_5");
			$this->click("agg_total_6");
			$this->click("agg_total_7");
			$this->click("agg_total_8");
		}
		$this->click("//input[@value='Continue']");
		$this->waitForPageToLoad("30000");
	}

	private function _iterate_tests($title)
	{
		global $agg_graph_types, $agg_totals, $agg_order_types, $agg_totals_type;
		foreach ($agg_graph_types as $k1 => $agt) {
			foreach ($agg_totals as $k2 => $at) {
				switch ($k2) {
					case AGGREGATE_TOTAL_NONE:
						foreach ($agg_order_types as $k4 => $aot) {
							$att = $agg_totals_type[AGGREGATE_TOTAL_TYPE_SIMILAR];
							print ("Title:$title AGT:$agt AT:$at ATT:$att AOT:$aot\n");
							$this->_run_test($title, $agt, $at, $att, $aot);
							$this->click("link=Graph Management");
							$this->waitForPageToLoad("30000");
						}
						break;
					case AGGREGATE_TOTAL_ALL:
						foreach ($agg_totals_type as $k3 => $att) {
							foreach ($agg_order_types as $k4 => $aot) {
								print ("Title:$title AGT:$agt AT:$at ATT:$att AOT:$aot\n");
								$this->_run_test($title, $agt, $at, $att, $aot);
								$this->click("link=Graph Management");
								$this->waitForPageToLoad("30000");
							}
						}
						break;
					case AGGREGATE_TOTAL_ONLY:
						foreach ($agg_totals_type as $k3 => $att) {
							$aot = $agg_order_types[AGGREGATE_ORDER_NONE];
							print ("Title:$title AGT:$agt AT:$at ATT:$att AOT:$aot\n");
							$this->_run_test($title, $agt, $at, $att, $aot);
							$this->click("link=Graph Management");
							$this->waitForPageToLoad("30000");
						}
						break;
				}
			}
		}
	}

	public function testGTdefault()
	{
		$this->open("/workspace/cacti087g/host.php");
		$this->click("link=Graph Management");
		$this->waitForPageToLoad("30000");
		$this->select("template_id", "label=Interface - Traffic (bits/sec) ( de...");
		$this->waitForPageToLoad("30000");
		$this->type("filter", "traffic");

		$this->_iterate_tests("default");
	}

	public function testGTareastack()
	{
		$this->open("/workspace/cacti087g/host.php");
		$this->click("link=Graph Management");
		$this->waitForPageToLoad("30000");
		$this->select("template_id", "label=Interface - Traffic (bits/sec) (ARE...");
		$this->waitForPageToLoad("30000");
		$this->type("filter", "traffic");

		$this->_iterate_tests("Area/Stack");
	}

	public function testGTpeak()
	{
		$this->open("/workspace/cacti087g/host.php");
		$this->click("link=Graph Management");
		$this->waitForPageToLoad("30000");
		$this->select("template_id", "label=Interface - Traffic (bits/sec) (pea...");
		$this->waitForPageToLoad("30000");
		$this->type("filter", "traffic");

		$this->_iterate_tests("Peak");
	}

	public function testGTposneg()
	{
		$this->open("/workspace/cacti087g/host.php");
		$this->click("link=Graph Management");
		$this->waitForPageToLoad("30000");
		$this->select("template_id", "label=Interface - Traffic (bits/sec) (pos...");
		$this->waitForPageToLoad("30000");
		$this->type("filter", "traffic");

		$this->_iterate_tests("Pos/Neg");
	}

	public function testGTline()
	{
		$this->open("/workspace/cacti087g/host.php");
		$this->click("link=Graph Management");
		$this->waitForPageToLoad("30000");
		$this->select("template_id", "label=Interface - Traffic (bits/sec) (pur...");
		$this->waitForPageToLoad("30000");
		$this->type("filter", "traffic");

		$this->_iterate_tests("Line");
	}
}
?>