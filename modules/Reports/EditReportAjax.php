<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@97862 */

require_once('modules/Reports/Reports.php');


$mode = 'ajax';
$reportid = intval($_REQUEST['reportid']);
$action = $_REQUEST['subaction'];
$raw = null;
$tpl = '';
$json = null;

$reports = Reports::getInstance();

if ($action == 'GetModulesList') {
	$chain = Zend_Json::decode($_REQUEST['chain']);
	$getFields = ($_REQUEST['getfields'] == 1);
	$modules = $reports->getModulesListForChain($reportid, $chain);
	if ($getFields) {
		$ftype = $_REQUEST['fieldstype'];
		if ($ftype == 'total') {
			$fields = $reports->getTotalsFieldsListForChain($reportid, $chain);
		} elseif ($ftype == 'stdfilter') {
			$fields = $reports->getStdFiltersFieldsListForChain($reportid, $chain);
		} elseif ($ftype == 'advfilter') {
			$fields = $reports->getAdvFiltersFieldsListForChain($reportid, $chain);
		} else {
			$fields = $reports->getFieldsListForChain($reportid, $chain);
		}
	}
	$result = array('modules' => $modules, 'fields' => $fields);
	$json = array('success' => true, 'error' => null, 'result' => $result);

} elseif ($action == 'GetFieldsList') {
	$chain = Zend_Json::decode($_REQUEST['chain']);
	$fields = $reports->getFieldsListForChain($reportid, $chain);
	$json = array('success' => true, 'error' => null, 'result' => $fields);

} elseif ($action == 'GetStdFiltersFieldsList') {
	$chain = Zend_Json::decode($_REQUEST['chain']);
	$fields = $reports->getStdFiltersFieldsListForChain($reportid, $chain);
	$json = array('success' => true, 'error' => null, 'result' => $fields);

} elseif ($action == 'GetAdvFiltersFieldsList') {
	$chain = Zend_Json::decode($_REQUEST['chain']);
	$fields = $reports->getAdvFiltersFieldsListForChain($reportid, $chain);
	$json = array('success' => true, 'error' => null, 'result' => $fields);
	
} elseif ($action == 'GetTotalsFieldsList') {
	$chain = Zend_Json::decode($_REQUEST['chain']);
	$fields = $reports->getTotalsFieldsListForChain($reportid, $chain);
	$json = array('success' => true, 'error' => null, 'result' => $fields);

// crmv@128369
} elseif ($action == 'LoadClustersList') {
	global $app_strings, $mod_strings;
	
	$smarty = new VteSmarty();
	$smarty->assign("MOD", $mod_strings);
	$smarty->assign("APP", $app_strings);
	$smarty->assign("REPORTID", $reportid);
	
	$clusterData = Zend_Json::decode($_REQUEST['clusterdata']);
	$smarty->assign("CLUSTERS", $clusterData);
	
	$html = $smarty->fetch('modules/Reports/ClustersList.tpl');
	
	$json = array('success' => true, 'result' => $html);

} elseif ($action == 'LoadClusterFilters') {

	global $app_strings, $mod_strings;
	
	$smarty = new VteSmarty();
	$smarty->assign("MOD", $mod_strings);
	$smarty->assign("APP", $app_strings);
	$smarty->assign("REPORTID", $reportid);
	$smarty->assign("CLUSTERIDX", $_REQUEST['clusteridx']);
	
	$smarty->assign("COMPARATORS",$reports->getAdvFilterOptions());
	$smarty->assign("FILTERDESC", getTranslatedString('LBL_CLUSTER_FILTER_DESC'));
	
	$cluster = Zend_Json::decode($_REQUEST['cluster']);
	if ($reportid > 0) {
		$config = $reports->loadReport($reportid);
	} else {
		$primodule = $_REQUEST['primodule']; // crmv@181858
		$config = array(
			// crmv@181858
			'relations' => array(
				array(
					'name' => $primodule,
					'module' => $primodule,
				)
			)
			// crmv@181858e
		);
	}
	$advfilters = $reports->prepareForSaveAdvFilt($cluster['conditions'], $config['relations']);
	$reports->prepareForEditAdvFilt($advfilters, $config);
	$smarty->assign("ADVFILTERS", $advfilters);
	
	$html = $smarty->fetch('modules/Reports/EditStepAdvFilters.tpl');
	
	$json = array('success' => true, 'result' => $html);
	
// crmv@128369e

} elseif ($action == 'SaveReport') {
	$success = true;
	$error = '';
	try {
		$config = $reports->prepareForSave($reportid, $_REQUEST);
	} catch (Exception $e) {
		$success = false;
		$error = $e->getMessage();
	}
	if ($success) {
		$newReportid = $reports->saveReport($reportid, $config);
		$result = array(
			'reportid' => $newReportid,
			'folderid' => $config['folderid'],
		);
		$chartinfo = Zend_Json::decode($_REQUEST['chartinfo']);
		if ($newReportid && !empty($chartinfo)) {
			$chartid = $reports->createChart($newReportid, $chartinfo);
			if (!$chartid) {
				// delete the report
				$reports->deleteReport($newReportid);
				$success = false;
				$error = 'Unable to create che chart';
			}
		}
		// crmv@172355
		if ($reportid > 0 && $newReportid && $_REQUEST['remove_charts'] === '1') {
			$chartFocus = CRMEntity::getInstance('Charts');
			$charts = $chartFocus->getChartsForReport($newReportid);
			foreach ($charts as $chart) {
				$chart->trash('Charts', $chart->id);
			}
		}
		// crmv@172355e
	}	
	$json = array('success' => $success, 'error' => $error, 'result' => $result);
	
} else {
	$json = array('success' => false, 'error' => "Unknown subaction");
}

// output
if (!is_null($raw)) {
	echo $raw;
	exit(); // sorry, I have to do this, some html shit is spitted out at the end of the page
} elseif (!empty($tpl) && isset($smarty)) {
	$smarty->display('modules/Reports/'.$tpl);
} elseif (!empty($json)) {
	echo Zend_Json::encode($json);
	exit(); // idem
} else {
	echo "No data returned";
}