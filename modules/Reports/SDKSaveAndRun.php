<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@97862 crmv@98500 */

global $current_user, $app_strings, $mod_strings;

// get the SDK report info
$reportinfo = SDK::getReport($reportid, $folderid);

// import the file for the report
require_once($reportinfo['reportrun']);

$CU = CRMVUtils::getInstance();
$ogReport = Reports::getInstance();

$config = $ogReport->loadReport($reportid);
$oReportRun = new $reportinfo['runclass']($reportid);
if (empty($config['reportname'])) $config['reportname'] = $oReportRun->reportname; // crmv@190915

$list_report_form = new VteSmarty();

$list_report_form->assign("MOD", $mod_strings);
$list_report_form->assign("APP", $app_strings);

//crmv@29686+33991
$list_report_form->assign("THEME", $theme);
$list_report_form->assign("IMAGE_PATH", $image_path);
$list_report_form->assign("MODULE", 'Reports');
$list_report_form->assign("SINGLE_MOD", 'SINGLE_Reports');

$list_report_form->assign("DATEFORMAT",$current_user->date_format);
$list_report_form->assign("JS_DATEFORMAT",parse_calendardate($app_strings['NTC_DATE_FORMAT']));

$list_report_form->assign("DIRECT_OUTPUT", false);
$list_report_form->assignByRef("__REPORT_RUN_INSTANCE", $oReportRun); // crmv@181170

$list_report_form->assign("REPORT_HAS_SUMMARY",$oReportRun->hasSummary());
$list_report_form->assign("REPORT_HAS_TOTALS",$oReportRun->hasTotals());
//crmv@29686+33991e
$list_report_form->assign("REPORTID", $reportid);
$list_report_form->assign("FOLDERID", $folderid);
$list_report_form->assign("REPORT_TAB", $reportTab);

$list_report_form->assign("REPORTNAME", htmlspecialchars($config['reportname'],ENT_QUOTES,$default_charset));
$list_report_form->assign("IS_EDITABLE", false);
$list_report_form->assign("IS_DUPLICABLE", false); // crmv@38798

/* standard filter stuff */
$BLOCKJS = $ogReport->getCriteriaJS();
$list_report_form->assign("BLOCKJS",$BLOCKJS);

$list_report_form->assign("STDFILTERFIELDS",$oReportRun->getStdFilterFields($reportid)); // crmv@140813
$list_report_form->assign("STDFILTEROPTIONS",$ogReport->getStdFilterOptions($reportid));
if ($_REQUEST["startdate"] && $_REQUEST["enddate"]) {
	$oReportRun->setStdFilterFromRequest($_REQUEST);
}
// fake config block, just to parse the stdfilter
$stdfilter = array(
	'fields' => array(),
	'relations' => $config['relations'] ?: array(),
	'stdfilters' => array($oReportRun->getStdFilter(0))
);
$ogReport->prepareForEdit($stdfilter);
$list_report_form->assign("STDFILTER",$stdfilter['stdfilters'][0]);

$list_report_form->assign("REPORT_HAS_SUMMARY",$oReportRun->hasSummary());
$list_report_form->assign("REPORT_HAS_TOTALS",$oReportRun->hasTotals());
		
$list_report_form->assign("EXPORT_PERMITTED",'SELECT');
$list_report_form->assign("ENABLE_EXPORT_PDF", $oReportRun->enableExportPdf);
$list_report_form->assign("ENABLE_EXPORT_XLS", $oReportRun->enableExportXls);
$list_report_form->assign("ENABLE_PRINT", $oReportRun->enablePrint);

$list_report_form->assign("HIDE_PARAMS_BLOCK", $oReportRun->hideParamsBlock);
$list_report_form->assign("SDKJSFUNCTION", $reportinfo['jsfunction']);
$list_report_form->assign("SDKBLOCK", $oReportRun->getSDKBlock());

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
	// crmv@128369 crmv@172034
	// add the columnname
	foreach ($ordering as &$order) {
		$order['index'] = $order['column'];
		$order['column'] = $reqColumns[$order['index']]['name'];
	}
	// crmv@128369e crmv@172034e
	$oReportRun->setQueryOrdering($ordering);
}

$list_report_form->assign("PAGESIZE", $oReportRun->pageSize);

// execute the report!
$sshtml = $oReportRun->GenerateReport("HTML", null); // keep the 2nd parameter for compatibility
if(is_array($sshtml) && $oReportRun->hasTotals()) $totalhtml = $oReportRun->GenerateReport("TOTALHTML"); //crmv@73628

$list_report_form->assign("REPORTHTML", $sshtml);
$list_report_form->assign("REPORTTOTALHTML", $totalhtml); //crmv@73628

// crmv@172034 - support column operations
if ($oReportRun->columns) {
	$list_report_form->assign("TOTAL_RECORDS", $oReportRun->getTotalCount());
	$oclass = $oReportRun->getOutputClass("HTML", true);
	$headerData = $oclass->getHeader();
	$list_report_form->assign('COLUMNS_DEF', Zend_Json::encode($headerData));
}
// crmv@172034e

// get all reports in this folder
$rpts = SDK::getReports($folderid);
$reports_array = array();
foreach ($rpts as $repid=>$rep) {
	$reports_array[$repid] = $rep['reportname'];
}

if ($_REQUEST['mode'] != 'ajax') {
	$list_report_form->assign("REPINFOLDER", $reports_array);
	if ($_REQUEST['embedded'] != 1) {
		require('modules/VteCore/header.php');	//crmv@30447
	}
	$list_report_form->display('modules/Reports/ReportRun.tpl');
} else {

	if ($_REQUEST['format'] == 'json') {
		
		$oReportRun->setReportTab('MAIN');
		$oReportRun->setOutputFormat('JSON');
		
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