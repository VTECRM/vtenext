<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@96742 */

require_once("modules/Reports/ReportRun.php");
require_once("modules/Reports/Reports.php");

global $app_strings, $mod_strings, $theme;

$reportid = vtlib_purify($_REQUEST["record"]);
$folderid = vtlib_purify($_REQUEST["folderid"]);
$filtercolumn = $_REQUEST["stdDateFilterField"];
$filter = $_REQUEST["stdDateFilter"];

$oReport = Reports::getInstance($reportid);

$oPrint_smarty=new VteSmarty();

//crmv@sdk-25785
$sdkrep = SDK::getReport($reportid, $folderid);
if (!is_null($sdkrep)) {
	require_once($sdkrep['reportrun']);
	$oReportRun = new $sdkrep['runclass']($reportid);
} else {
	$oReportRun = ReportRun::getInstance($reportid);
}
//crmv@sdk-25785e

// crmv@97862
if ($_REQUEST["startdate"] && $_REQUEST["enddate"]) {
	$oReportRun->setStdFilterFromRequest($_REQUEST);
}
// crmv@97862e

$_REQUEST['limit_string'] = 'ALL';

// crmv@169285
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
// crmv@169285e

// crmv@177381
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
// crmv@177381e

// crmv@29686
if ($_REQUEST['export_report_main'] == 1)
	$arr_values = $oReportRun->GenerateReport("PRINT",$filterlist);
	
// crmv@169285
// reset search for other report types
$oReportRun->setQuerySearch(null);
$oReportRun->setQuerySearchColumns(null);
$oReportRun->setQueryOrdering(null); // crmv@177381
// crmv@169285e

if ($_REQUEST['export_report_totals'] == 1)
	$total_report = $oReportRun->GenerateReport("PRINT_TOTAL",$filterlist);
if ($_REQUEST['export_report_summary'] == 1) {
	$count_total_report = $oReportRun->GenerateReport("COUNT",$filterlist);
	$oPrint_smarty->assign("COUNT_TOTAL_HTML", $count_total_report);
}
// crmv@29686e

$oPrint_smarty->assign("THEME",$theme);
$oPrint_smarty->assign("COUNT",$arr_values[1]);
$oPrint_smarty->assign("APP",$app_strings);
$oPrint_smarty->assign("MOD",$mod_strings);
$oPrint_smarty->assign("REPORT_NAME",$oReportRun->reportname); //crmv@150040
$oPrint_smarty->assign("PRINT_CONTENTS",$arr_values[0]);
$oPrint_smarty->assign("TOTAL_HTML",$total_report);
$oPrint_smarty->display("PrintReport.tpl");