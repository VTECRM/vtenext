<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@29686 crmv@96742 */

ini_set('max_execution_time','1800');
ini_set('memory_limit','1024M');
require_once("modules/Reports/ReportRun.php");
require_once("modules/Reports/Reports.php");

require_once("include/mpdf/mpdf.php"); // crmv@30066

global $current_user, $theme, $mod_strings, $app_strings;

$reportid = vtlib_purify($_REQUEST["record"]);
$folderid = vtlib_purify($_REQUEST["folderid"]);
$filtercolumn = vtlib_purify($_REQUEST["stdDateFilterField"]);
$filter = vtlib_purify($_REQUEST["stdDateFilter"]);

$oReport = Reports::getInstance($reportid);

//crmv@sdk-25785
$sdkrep = SDK::getReport($reportid, $folderid);
if (!is_null($sdkrep)) {
	require_once($sdkrep['reportrun']);
	$oReportRun = new $sdkrep['runclass']($reportid);
	$oReport->reportname = $oReportRun->reportname;
} else {
	$oReportRun = ReportRun::getInstance($reportid);
}
//crmv@sdk-25785e

// crmv@97862
if ($_REQUEST["startdate"] && $_REQUEST["enddate"]) {
	$oReportRun->setStdFilterFromRequest($_REQUEST);
}
// crmv@97862e

$_REQUEST['limit_string'] = 'ALL'; // avoid any limit
if ($_REQUEST['export_report_summary'] == 1)
	$reportcount = $oReportRun->GenerateReport("COUNT",$filterlist);
if ($_REQUEST['export_report_totals'] == 1)
	$reporttotal = $oReportRun->GenerateReport("TOTALHTML",$filterlist);
$oReportRun->_columnslist = false; // force reload of columns

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

if ($_REQUEST['export_report_main'] == 1)
	$reportdata = $oReportRun->GenerateReport("PDF",$filterlist); // TODO: sistema per report con tante righe
$repcolumns = $reportdata[3]; // numero di colonne

// gestione pagina in base alle colonne del report (-L for landscape)
if ($repcolumns <= 5) {
	$format = 'A4';
} elseif ($repcolumns <= 10) {
	$format = 'A4-L';
} elseif ($repcolumns <= 15) {
	$format = 'A3';
} elseif ($repcolumns <= 20) {
	$format = 'A3-L';
} elseif ($repcolumns <= 25) {
	$format = 'A2';
} elseif ($repcolumns <= 30) {
	$format = 'A2-L';
} elseif ($repcolumns <= 35) {
	$format = 'A1';
} else {
	$format = 'A1-L'; // maximum size allowed
}


$mpdf = new mPDF(
	'', 		// mode
	$format, 	// page size/orientation
	'', 		// default font size
	'Arial',	// default font
	10,			// margin left (mm)
	10,			// margin right
	10,			// margin top
	10,			// margin bottom
	10,			// margin header
	10			// margin footer
);

// performance tips
//$mpdf->shrink_tables_to_fit = 0; // disable font shrinking
// $mpdf->debug = true; // DEBUG INFO

$mpdf->SetAuthor('VTE CRM');
$mpdf->SetAutoFont();
$mpdf->setFooter('{PAGENO}'); // page number
$csspath = "themes/$theme/reportpdf.css";
//$mpdf->WriteHTML(file_get_contents($csspath), 1);
//$mpdf->SetHTMLHeader($header_html);
//$mpdf->SetHTMLFooter($footer_html);


$top_html = "
<html>\n
<head>
<title>Report: {$oReport->reportname}</title>\n
<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"$csspath\" />\n
</head>\n
<body class=\"pdfbody\">\n
<h2 style=\"text-align: center\">{$oReport->reportname}</h2><br /><br />
";
$bottom_html = "
</body>\n
</html>\n
";

/*
$report_html = $top_html . $reportcount . $reportdata[0] . $reporttotal . $bottom_html;
die($report_html);
*/

$mpdf->WriteHTML($top_html);

if (!empty($reportcount)) {
	$mpdf->WriteHTML('<bookmark content="'.$mod_strings['LBL_REPORT_SUMMARY'].'" /><h3>'.$mod_strings['LBL_REPORT_SUMMARY'].'</h3><br />');
	$mpdf->WriteHTML($reportcount);
	$mpdf->WriteHTML('<pagebreak />');
	$mpdf->WriteHTML('<bookmark content="'.$app_strings['Report'].'" /><h3>'.$app_strings['Report'].'</h3><br />');
}
if (is_array($reportdata) && !empty($reportdata[0])) {
	$mpdf->WriteHTML($reportdata[0]);
}
if (!empty($reporttotal)) {
	$mpdf->WriteHTML('<pagebreak />');
	$mpdf->WriteHTML('<bookmark content="'.$mod_strings['LBL_REPORT_TOTALS'].'" /><h3>'.$mod_strings['LBL_REPORT_TOTALS'].'</h3><br />');
	$mpdf->WriteHTML($reporttotal);
}

$mpdf->WriteHTML($bottom_html);

// crmv@139057
if ($_REQUEST['batch_export'] == 1 && !empty($filePath)) {
	$fname = $filePath;
} else {
	$reportname = 'Report.pdf';
	$fname = 'cache/'.$reportname;
}

$mpdf->Output($fname);

$filename = $oReportRun->generateExportFileName('pdf'); // crmv@192411

if ($_REQUEST['batch_export'] != 1) {

	$filesize = filesize("./cache/$reportname");
	@ob_clean();
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); // fix for IE6
	header('Content-Type: application/pdf');
	header("Content-length: ".$filesize);
	//header("Cache-Control: private");
	header('Content-disposition: attachment; filename="'.$filename.'"');
	header("Content-Description: PHP Generated Data");

	$file = @fopen('cache/'.$reportname,"rb");
	$chunksize = 1024*1024; // reads 1M every time
	while(!feof($file)) {
		echo @fread($file, $chunksize);
		ob_flush();
		//flush();
	}

	@unlink("cache/$reportname");
	die();
}
// crmv@139057e