<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@96742 crmv@97237 crmv@98764 */
 
require("config.php");
require_once("modules/Reports/ReportRun.php");
require_once 'modules/Reports/Reports.php';
require_once('modules/CustomView/CustomView.php');

global $adb,$table_prefix, $current_user;
global $theme, $mod_strings,$app_strings,$current_language; // crmv@30014

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$reportid = intval($_REQUEST["record"]);
$folderid = intval($_REQUEST["folderid"]);
$format = $_REQUEST['format'];
$reportTab = vtlib_purify($_REQUEST['tab']);

//crmv@sdk-25785
if ($report = SDK::getReport($reportid, $folderid)) {
	require_once('modules/Reports/SDKSaveAndRun.php');
	die();
}
//crmv@sdk-25785e

$CU = CRMVUtils::getInstance();
$ogReport = Reports::getInstance();

if ($ogReport->reportExists($reportid)) {

	if ($ogReport->isViewable($reportid)) {
	
		require('user_privileges/requireUserPrivileges.php');
	
		$config = $ogReport->loadReport($reportid);
		$oReportRun = ReportRun::getInstance($reportid);
		
		$list_report_form = new VteSmarty();

		$list_report_form->assign("MOD", $mod_strings);
		$list_report_form->assign("APP", $app_strings);

		$list_report_form->assign("THEME", $theme);
		$list_report_form->assign("IMAGE_PATH", $image_path);
		$list_report_form->assign("MODULE", 'Reports');
		$list_report_form->assign("SINGLE_MOD", 'SINGLE_Reports');
		
		$list_report_form->assign("DATEFORMAT",$current_user->date_format);
		$list_report_form->assign("JS_DATEFORMAT",parse_calendardate($app_strings['NTC_DATE_FORMAT']));
		
		$list_report_form->assign("DIRECT_OUTPUT", true);
		$list_report_form->assignByRef("__REPORT_RUN_INSTANCE", $oReportRun); // crmv@181170
		
		$list_report_form->assign("REPORTID", $reportid);
		$list_report_form->assign("FOLDERID", $folderid);
		$list_report_form->assign("REPORT_TAB", $reportTab);
		
		$list_report_form->assign("REPORTNAME", htmlspecialchars($config['reportname'],ENT_QUOTES,$default_charsetm, false));
		$list_report_form->assign("IS_EDITABLE", $ogReport->isEditable($reportid));
		$list_report_form->assign("IS_DUPLICABLE", true); // crmv@38798
		$list_report_form->assign("ENABLE_PRINT", true);	//crmv@sdk-25785

		/* standard filter stuff */
		$BLOCKJS = $ogReport->getCriteriaJS();
		$list_report_form->assign("BLOCKJS",$BLOCKJS);

		$list_report_form->assign("STDFILTERFIELDS",$ogReport->getStdFilterFields($reportid));
		$list_report_form->assign("STDFILTEROPTIONS",$ogReport->getStdFilterOptions($reportid));
		if ($_REQUEST['stdDateFilter'] == 'custom' || ($_REQUEST["startdate"] && $_REQUEST["enddate"])) { // crmv@148966
			$oReportRun->setStdFilterFromRequest($_REQUEST);
			$changedStdFilter = true; // crmv@101474
		}
		// fake config block, just to parse the stdfilter
		$stdfilter = array(
			'fields' => array(),
			'relations' => $config['relations'],
			'stdfilters' => array($oReportRun->getStdFilter(0))
		);
		$ogReport->prepareForEdit($stdfilter);
		$list_report_form->assign("STDFILTER",$stdfilter['stdfilters'][0]);

		
		$list_report_form->assign("REPORT_HAS_SUMMARY",$oReportRun->hasSummary());
		$list_report_form->assign("REPORT_HAS_TOTALS",$oReportRun->hasTotals());

		// crmv@30014
		if (Vtlib_isModuleActive('Charts') && isPermitted('Charts', 'EditView')) {
			$list_report_form->assign("CHARTS_LANG", return_module_language($current_language, 'Charts'));
			$list_report_form->assign("SHOW_CHART_CREATE", true);
			$chartInst = CRMEntity::getInstance('Charts');
			$list_report_form->assign("CHART_TYPES", $chartInst->getChartTypes());
			$charts = $chartInst->getChartsForReport($reportid, $changedStdFilter); // crmv@31209 crmv@186088
			if (count($charts) > 0) {
				$list_report_form->assign("REPORT_HAS_CHARTS", true);
				$list_report_form->assign("CHART_LIST", $charts);
			}
		} else {
			$list_report_form->assign("SHOW_CHART_CREATE", false);
			$list_report_form->assign("REPORT_HAS_CHARTS", false);
		}		
		// crmv@30014e
		
		if ($ogReport->isExportable($reportid)) {
			$list_report_form->assign("EXPORT_PERMITTED","YES");
		} else {
			$list_report_form->assign("EXPORT_PERMITTED","NO");
		}
		
		// set parameters for query
		$pageLength = intval($_REQUEST['length']);
		if ($pageLength > 0) {
			$oReportRun->pageSize = $pageLength;
		}
		$limitStart = intval($_REQUEST['start']);
		if ($limitStart > 0) {
			$end = $limitStart + $oReportRun->pageSize;
			$oReportRun->setQueryLimit($limitStart, $end);
		} else {
			// use a default
			$oReportRun->setDefaultQueryLimit();
		}
		
		$search = $_REQUEST['search'];
		if (is_array($search) && $search['value']) {
			$oReportRun->setQuerySearch($search['value']);
		}
		
		$reqColumns = $_REQUEST['columns'];
		if (is_array($reqColumns) && count($reqColumns) > 0) {
			// extract the search info
			$searchCols = array();
			foreach ($reqColumns as $idx => $colinfo) {
				$name = $colinfo['name'];
				if ($name && $colinfo['search']['value']) {
					$searchCols[$name] = array(
						'index' => $idx,
						'column' => $name,
						'search' => $colinfo['search']['value'],
					);
				}
			}
			if (count($searchCols) > 0) {
				$oReportRun->setQuerySearchColumns($searchCols);
			}
		}
		
		$ordering = $_REQUEST['order'];
		if (is_array($ordering) && count($ordering) > 0) {
			// crmv@128369
			// add the columnname
			foreach ($ordering as &$order) {
				$order['index'] = $order['column'];
				$order['column'] = $reqColumns[$order['index']]['name'];
			}
			// crmv@128369e
			$oReportRun->setQueryOrdering($ordering);
		}
		
		$list_report_form->assign("PAGESIZE", $oReportRun->pageSize);

		if (trim($folderid) == '') $folderid = false; //crmv@fix reports
		
		if($_REQUEST['mode'] != 'ajax') {
			if ($_REQUEST['embedded'] != 1) {
				require('modules/VteCore/header.php');	//crmv@30447
			}
			$list_report_form->display('modules/Reports/ReportRun.tpl');
		} else {
		
			if ($_REQUEST['format'] == 'json') {
				
				$oReportRun->setReportTab('MAIN');
				$oReportRun->setOutputFormat('JSON');
				// crmv@101474
				if (!$changedStdFilter) {
					// don't generate again sub queries, unless I changed the std filter
					$oReportRun->reuseSubqueries();
				}
				// crmv@101474e
				
				$return_data = $oReportRun->GenerateReport();
				if (!$return_data) {
					$output = array('success' => false, 'error' => 'Unable to generate the report');
				} else {
					$output = $return_data[0];
				}
				echo Zend_Json::encode($output);
				die();
			} else {
				$list_report_form->display('modules/Reports/ReportRunContents.tpl');
			}
		}
	
	} else {
	
		$CU->showAccessDenied(getTranslatedString('LBL_NO_ACCESS'), ($_REQUEST['mode'] != 'ajax'));
	}
	
} else {
	// crmv@172898
	$showHeader = $_REQUEST['action'] === 'SaveAndRun' ? true : false;
	$CU->showAccessDenied(getTranslatedString('LBL_REPORT_DELETED'), $showHeader);
	// crmv@172898e
}