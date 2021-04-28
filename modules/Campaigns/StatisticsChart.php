<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@38600 crmv@101506 crmv@172994 */

class StatisticsChart extends SDKExtendableClass {
	
	const CHARTJS_PLUGIN = 1;
	const PCHART_PLUGIN = 2;
	
	/* Array const is available from PHP 5.6. */
	public static $PLUGIN_MAPPING = array(
		StatisticsChart::CHARTJS_PLUGIN => 'ChartJS',
		StatisticsChart::PCHART_PLUGIN => 'pChart',
	);
	
	protected $focus = null;
	protected $datasets = null;
	protected $chartPlugin = null;
	
	public function __construct($focus, $datasets, $chartPlugin = StatisticsChart::CHARTJS_PLUGIN) {
		$this->setFocus($focus);
		$this->setDatasets($datasets);
		$this->setChartPlugin($chartPlugin);
	}
	
	public function generateChart($opts = array()) {
		$this->reloadRelated();
		
		$serieValues = $this->generateSerieValues();
		
		$chartData = array();
		
		if ($this->chartPlugin === StatisticsChart::CHARTJS_PLUGIN) {
			$chartData = $this->generateChartJS($serieValues);
		} elseif ($this->chartPlugin === StatisticsChart::PCHART_PLUGIN) {
			$chartData = $this->generatePChart($serieValues);
		}
		
		return $chartData;
	}
	
	protected function reloadRelated() {
		//crmv@25083 - reload related
		$focus = $this->focus;
		if (is_object($focus) && $focus instanceof Campaigns) {
			// crmv@176702
			$tabid = getTabid('Campaigns');
			$focus->get_statistics_message_queue($focus->id, $tabid, 0, false, true, true);
			$focus->get_statistics_sent_messages($focus->id, $tabid, 0, false, true, true);
			$focus->get_statistics_viewed_messages($focus->id, $tabid, 0, false, true, true);
			$focus->get_statistics_tracked_link($focus->id, $tabid, 0, false, true, true);
			$focus->get_statistics_unsubscriptions($focus->id, $tabid, 0, false, true, true);
			$focus->get_statistics_bounced_messages($focus->id, $tabid, 0, false, true, true);
			$focus->get_statistics_suppression_list($focus->id, $tabid, 0, false, true, true);
			$focus->get_statistics_failed_messages($focus->id, $tabid, 0, false, true, true);
			// crmv@176702e
		}
		//crmv@25083e
	}
	
	// crmv@176702 crmv@181281
	protected function generateSerieValues() {
		$datasets = array_keys($this->datasets);
		
		$serie_vals = array();
		$noModId = -1;
		foreach ($datasets as $id => $label) {
			$serie_vals[$id] = $this->getSerieValues($label, $id, false);
			if ($label == 'Suppression list') $noModId = $id;
		}
		
		$focusNewsletter = CRMEntity::getInstance('Newsletter');
		$modules = array_reverse($focusNewsletter->module_email_fields_priority);
		foreach($modules as $module) {
			$serie[getTranslatedString($module)] = array();
		}
		$serie[getTranslatedString('LBL_ALL')] = array();
		
		for ($i=0; $i<8; ++$i) {
			if ($i == $noModId) {
				foreach($modules as $module) {
					$serie[getTranslatedString($module)][] = 0;
				}
				$serie[getTranslatedString('LBL_ALL')][] = @count($serie_vals[$i]);
			} else {
				$all = 0;
				foreach($focusNewsletter->email_fields as $module => $email_field) {
					$serie[getTranslatedString($module)][] = @count($serie_vals[$i][$module]);
					$all = $all + @count($serie_vals[$i][$module]);
				}
				$serie[getTranslatedString('LBL_ALL')][] = $all;
			}
		}
		
		return $serie;
	}
	// crmv@176702e crmv@181281e
	
	protected function getSerieValues($label, $id, $group_by_crmid) {
		global $adb;
		
		$serie_vals = array();
		
		// se $group_by_crmid = true :
		// non ho lo stesso conteggio delle related perchè conto solamente i crmid/email coinvolti (distinct crmid/email per capirci)
		
		if (VteSession::get(strtolower($label).'_listquery') != '') {
			$result = $adb->query(VteSession::get(strtolower($label).'_listquery'));
			if ($result && $adb->num_rows($result) > 0) {
				while ($row = $adb->fetchByAssoc($result)) {
					if ($label == 'Suppression list') {
						if ($group_by_crmid) {
							$serie_vals[$row['email']] = '';
						} else {
							$serie_vals[] = $row['email'];
						}
					} else {
						// crmv@25083
						$module = $row['setype'];
						if ($group_by_crmid) {
							$serie_vals[$module][$row['crmid']] = '';
						} else {
							$serie_vals[$module][] = $row['crmid'];
						}
						// crmv@25083e
					}
				}
			}
		}
		
		return $serie_vals;
	}
	
	protected function generateChartJS($serieValues) {
		$chartData = array();
		$chartData['datasets'] = array();
		$chartData['labels'] = array();
		
		$chartFocus = CRMEntity::getInstance('Charts');
		$mainPalette = $chartFocus->parsePaletteFile("modules/Charts/palettes/pastels.color");
		$palette = $chartFocus->paletteRGB2Css($mainPalette);
		$paletteCount = count($mainPalette);
		
		foreach ((array) $serieValues as $label => $values) {
			if (!isset($k)) $k = 0;
			$chartData['datasets'][] = array(
				'label' => $label,
				'data' => $values,
				'fillColor' => $palette[$k % $paletteCount],
			);
			$k++;
		}
		
		$datasetsLabels = array_keys($this->datasets);
		array_walk($datasetsLabels, function (&$label, $id, $module) {
			$label = getTranslatedString($label, $module);
		}, 'Campaigns');
		
		$chartData['labels'] = $datasetsLabels;

		$anchors = array_map(function ($element) {
			return $element['relationId'];
		}, $this->datasets);
		$anchors = array_values($anchors);
		
		$chartData['anchors'] = $anchors;
		
		return $chartData;
	}
	
	protected function generatePChart($serieValues) {
		require_once("include/pChart/class/pData.class.php");
		require_once("include/pChart/class/pDraw.class.php");
		require_once("include/pChart/class/pImage.class.php");
		
		$chartData = array();
		
		$DataSet = new pData();
		
		$datasetsLabels = array_keys($this->datasets);
		array_walk($datasetsLabels, function(&$label, $id, $module) {
			$label = getTranslatedString($label, $module);
		}, 'Campaigns');
		
		$DataSet->AddPoints($datasetsLabels, "Label");
		$DataSet->setAbscissa("Label");
		$DataSet->setSerieDescription("Label", "Label");
		
		// crmv@146616
		$maxValue = 10;
		foreach ($serieValues as $label => $info) {
			$DataSet->AddPoints($info, $label);
			$maxValue = max($maxValue, max($info));
		}
		
		// crmv@146616e
		
		// crmv@6974
		if (!function_exists('chartAxisFormat')) {
			function chartAxisFormat($v) {
				if (is_float($v)) $v = round($v, 1);
				return $v;
			}
		}
		$DataSet->setAxisDisplay(0, AXIS_FORMAT_CUSTOM, 'chartAxisFormat');
		// crmv@69743e
		
		// Initialise the graph
		$pImage = new pImage(1200, 350, $DataSet); // crmv@59091
		$pImage->setFontProperties(array('FontName' => "include/pChart/fonts/MYRIADPRO-REGULAR.OTF", 'FontSize' => 10));
		$pImage->setGraphArea(260, 30, 1180, 340); // crmv@59091
		
		$pImage->drawScale(array(
			'Mode' => SCALE_MODE_START0, 
			'Factors' => array(round($maxValue / 10)), 
			'GridR' => 50, 
			'GridG' => 50, 
			'GridB' => 50, 
			'CycleBackground' => true, 
			'Pos' => SCALE_POS_TOPBOTTOM)
		); // crmv@59091 crmv@146616
		                                                                                                                                                                                                       
		// Draw the bar graph
		$pImage->drawBarChart();
		
		// Finish the graph
		$pImage->setFontProperties(array('FontName' => "include/pChart/fonts/MYRIADPRO-REGULAR.OTF", 'Fontsize' => 12));
		$pImage->drawLegend(5, 40, array()); // crmv@59091
		if (!is_dir('cache/charts/')) @mkdir('cache/charts');
		$pImage->Render("cache/charts/StatisticsChart.png");
		
		return $chartData;
	}
	
	public function setFocus($focus) {
		$this->focus = $focus;
	}
	
	public function getFocus() {
		return $this->focus;
	}
	
	public function setDatasets($datasets) {
		$this->datasets = $datasets;
	}
	
	public function getDatasets() {
		return $this->datasets;
	}
	
	public function setChartPlugin($chartPlugin) {
		$this->chartPlugin = $chartPlugin;
	}
	
	public function getChartPlugin() {
		return $this->chartPlugin;
	}
	
	public function getChartPluginName() {
		if (isset(StatisticsChart::$PLUGIN_MAPPING[$this->chartPlugin])) {
			return StatisticsChart::$PLUGIN_MAPPING[$this->chartPlugin];
		} else {
			return 'Unknown plugin name';
		}
	}
	
}