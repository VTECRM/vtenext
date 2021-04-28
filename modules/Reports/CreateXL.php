<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $php_max_execution_time;
set_time_limit($php_max_execution_time);

// crmv@30385 - cambio classe php per scrivere i xls - tutto il file
/* crmv@101168 */


require_once("modules/Reports/ReportRun.php");
require_once("modules/Reports/Reports.php");

global $tmp_dir, $root_directory, $mod_strings, $app_strings; // crmv@29686

// crmv@139057
if ($_REQUEST['batch_export'] == 1 && !empty($filePath)) {
	$fname = $filePath;
} else {
	$fname = tempnam($root_directory.$tmp_dir, "merge2.xls");
}
// crmv@139057e

# Write out the data
$reportid = intval($_REQUEST["record"]);
$folderid = intval($_REQUEST["folderid"]);

$oReport = Reports::getInstance($reportid);

//crmv@sdk-25785

$sdkrep = SDK::getReport($reportid, $folderid);
if (!is_null($sdkrep)) {
	require_once($sdkrep['reportrun']);
	$oReportRun = new $sdkrep['runclass']($reportid);
	$oReportRun->setOutputFormat('XLS');
} else {
	$oReportRun = ReportRun::getInstance($reportid);
}

$_REQUEST['limit_string'] = 'ALL'; // crmv@96742

//crmv@sdk-25785e

// crmv@97862
if ($_REQUEST["startdate"] && $_REQUEST["enddate"]) {
	$oReportRun->setStdFilterFromRequest($_REQUEST);
}
// crmv@97862e

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
$temp_xls_report = $oReportRun->GenerateReport("XLS"); // to initialize stuff in reports object
$mainOutClass = $oReportRun->getOutputClass(); // crmv@133409
$numrows = count($temp_xls_report); //crmv@139048
if ($_REQUEST['export_report_main'] == 1)
	$arr_val = $temp_xls_report;
	
// crmv@169285
// reset search for other report types
$oReportRun->setQuerySearch(null);
$oReportRun->setQuerySearchColumns(null);
$oReportRun->setQueryOrdering(null); // crmv@177381
// crmv@169285e

if ($_REQUEST['export_report_totals'] == 1)
	$totalxls = $oReportRun->GenerateReport("TOTALXLS");
if ($_REQUEST['export_report_summary'] == 1)
	$counttotalxls = $oReportRun->GenerateReport("COUNTXLS");
// crmv@29686e


// crmv@180826

$objPHPExcel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$objPHPExcel->removeSheetByIndex(0); // remove default sheet

$objPHPExcel->getProperties()
	->setCreator("VTE CRM")
	->setLastModifiedBy("VTE CRM")
	->setTitle("Report"); // TODO: report title

$objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
$objPHPExcel->getDefaultStyle()->getFont()->setSize(10);

$xlsStyle1 = new PhpOffice\PhpSpreadsheet\Style\Style();
$xlsStyle2 = new PhpOffice\PhpSpreadsheet\Style\Style();

$xlsStyle1->applyFromArray(
	array('font' => array(
		'name' => 'Arial',
		'bold' => true,
		'size' => 12,
		'color' => array( 'rgb' => '0000FF' )
	),
));

$xlsStyle2->applyFromArray(
	array('font' => array(
		'name' => 'Arial',
		'bold' => true,
		'size' => 11,
	),
));

// crmv@157509
// only the number of decimal digits and the presence of thousand separator
// can be decided, the character is in the Excel general options
$baseNumberFormat = 
	($current_user->thousands_separator != '' ? "#,##" : "")
	."0"
	.($current_user->decimals_num > 0 ? '.'.str_repeat('0', $current_user->decimals_num) : '');
// crmv@157509e

// crmv@139057
if (!function_exists('addXlsHeader')) {
	function addXlsHeader($sheet, $oReportRun, $headerStyle) { // crmv@178606
		$output = $oReportRun->getOutputClass();
		$head = $output->getSimpleHeaderArray();
		if ($head && count($head) > 0) {
			$count = 1; // crmv@198780
			$sheet->duplicateStyle($headerStyle, 'A1:'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($head)).'1'); // crmv@178606
			foreach($head as $key) {
				$sheet->setCellValueByColumnAndRow($count, 1, $key);
				//$sheet->getColumnDimensionByColumn($count)->setAutoSize(true); // crmv@97862 crmv@139048
				$count = $count + 1;
			}
		}
	}
}
// crmv@139057e

// crmv@29686 - riepilogo
if (is_array($counttotalxls) && count($counttotalxls) > 0) {
	$sheet0 = new PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($objPHPExcel, $mod_strings['LBL_REPORT_SUMMARY']);
	$objPHPExcel->addSheet($sheet0);

	// header
	$colcount = 1; // crmv@198780
	$rowcount = 1;
	$sheet0->duplicateStyle($xlsStyle1, 'A1:'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($counttotalxls[0])).'1');
	foreach ($counttotalxls[0] as $key=>$v) {
		$sheet0->setCellValueByColumnAndRow($colcount++, $rowcount, $key);
	}

	foreach ($counttotalxls as $xlsrow) {
		++$rowcount;
		$colcount = 1; // crmv@198780
		foreach ($xlsrow as $k=>$xlsval) {
			$sheet0->setCellValueByColumnAndRow($colcount++, $rowcount, $xlsval);
		}
	}
} elseif ($_REQUEST['export_report_summary'] == 1 && $oReportRun->hasSummary()) {
	// add an empty sheet with the column names
	$sheet0 = new PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($objPHPExcel, $mod_strings['LBL_REPORT_SUMMARY']);
	$objPHPExcel->addSheet($sheet0);
	$oReportRun->setReportTab('COUNT');
	addXlsHeader($sheet0, $oReportRun, $xlsStyle1); // crmv@178606
}

if (is_array($arr_val) && is_array($arr_val[0])) {
	$count = 1; // crmv@198780
	$sheet1 = new PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($objPHPExcel, $app_strings['Report']);
	$objPHPExcel->addSheet($sheet1);
	// crmv@29686e

	$sheet1->duplicateStyle($xlsStyle1, 'A1:'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($arr_val[0])).'1');
	foreach($arr_val[0] as $key=>$value) {
		$label = preg_replace('/ ##[0-9]+##$/', '', $key); // crmv@171787
		$sheet1->setCellValueByColumnAndRow($count, 1, $label); // crmv@171787
		//$sheet1->getColumnDimensionByColumn($count)->setAutoSize(true); // crmv@97862 crmv@139048
		$count = $count + 1;
	}

	$rowcount=2;
	foreach($arr_val as $key=>$array_value)
	{
		$dcount = 1; // crmv@198780
		foreach($array_value as $hdr=>$value)
		{
			$value = decode_html($value);
			if (strpos($value,'=') === 0) $value = "'".$value;	//crmv@52501
			
			$hcell = $mainOutClass->getHeaderByIndex($dcount -1 ); // crmv@133409 crmv@198780
			$cell = $mainOutClass->getCellByIndex($rowcount-2, $dcount -1 ); // crmv@157509 crmv@198780

			//crmv@29016
			//check for strings that looks like numbers (starting with 0 or with a text column)
			if (is_numeric($value) && ($value !== '0' && substr(strval($value), 0, 1) == '0' && !preg_match('/[,.]/', $value)) || in_array($hcell['wstype'], ['text', 'string'])) { // crmv@30385 crmv@98764 crmv@192463 crmv@198827
				$sheet1->setCellValueExplicitByColumnAndRow($dcount, $rowcount, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			// crmv@133409
			} elseif (($hcell['wstype'] == 'date' || $hcell['wstype'] == 'datetime') && strlen($value)> 7) { // crmv@187716
				// set the date format (the value is in db format)
				$value = \PhpOffice\PhpSpreadsheet\Shared\Date::stringToExcel($value);
				$dateFormat = ($hcell['wstype'] == 'datetime' ? 'yyyy-mm-dd h:mm:ss' : \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDD2);
				$sheet1->setCellValueByColumnAndRow($dcount, $rowcount, $value);
				$sheet1->getStyleBycolumnAndRow($dcount, $rowcount)->getNumberFormat()->setFormatCode($dateFormat);
			// crmv@133409e
			// crmv@38798 crmv@157509 - currency fields
			} elseif ($cell['currency_symbol']) {
				$cell['currency_symbol'] = html_entity_decode($cell['currency_symbol']); // crmv@167567
				$value = floatval(trim(str_replace($cell['currency_symbol'], '', $value)));
				$sheet1->setCellValueExplicitByColumnAndRow($dcount, $rowcount, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
				$numberFormat = "\"{$cell['currency_symbol']}\" ".$baseNumberFormat;
				$sheet1->getStyleBycolumnAndRow($dcount, $rowcount)->getNumberFormat()->setFormatCode($numberFormat);
			} elseif (is_numeric($value)) {
				$sheet1->setCellValueExplicitByColumnAndRow($dcount, $rowcount, $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
				$sheet1->getStyleBycolumnAndRow($dcount, $rowcount)->getNumberFormat()->setFormatCode($baseNumberFormat);
			// crmv@38798e crmv@157509e
			} else {
				$sheet1->setCellValueByColumnAndRow($dcount, $rowcount, $value);
			}
			//crmv@29016e
			$dcount = $dcount + 1;
		}
		$rowcount++;
	}
} elseif ($_REQUEST['export_report_main'] == 1) {
	$sheet1 = new PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($objPHPExcel, $app_strings['Report']);
	$objPHPExcel->addSheet($sheet1);
	$oReportRun->setReportTab('MAIN');
	addXlsHeader($sheet1, $oReportRun, $xlsStyle1); // crmv@178606
}


$rowcount = 1; // crmv@29686
$count=2; // crmv@198780
if (is_array($totalxls)) {
	if(is_array($totalxls[0])) {
		$sheet2 = new PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($objPHPExcel, $mod_strings['LBL_REPORT_TOTALS']);
		$objPHPExcel->addSheet($sheet2);

		$sheet2->duplicateStyle($xlsStyle1, 'A1:'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($totalxls[0])+1).'1'); // crmv@198780
		foreach($totalxls[0] as $key=>$value) {
			$chdr=substr($key,-3,3);
			$sheet2->setCellValueByColumnAndRow($count++, $rowcount, $mod_strings[$chdr]);
		}
	}
	$rowcount++;
	foreach($totalxls as $key=>$array_value) {
		$dcount = 2;
		foreach($array_value as $hdr=>$value) {
			if ($dcount==2)	{ // crmv@198780
				$sheet2->setCellValueByColumnAndRow(1, $rowcount, substr($hdr,0,strlen($hdr)-4)); // crmv@198780
			}
			$value = decode_html($value);
			$sheet2->setCellValueByColumnAndRow($dcount++, $rowcount, $value);
		}
		$rowcount++; //crmv@36517
	}
} elseif ($_REQUEST['export_report_totals'] == 1 && $oReportRun->hasTotals()) {
	// add an empty sheet with the column names
	$sheet2 = new PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($objPHPExcel, $mod_strings['LBL_REPORT_TOTALS']);
	$objPHPExcel->addSheet($sheet2);
	$oReportRun->setReportTab('TOTAL');
	addXlsHeader($sheet2, $oReportRun, $xlsStyle1); // crmv@178606
}


// add an empty sheet if none inserted, otherwise MS Excel won't open the file
if ($objPHPExcel->getSheetCount() == 0) {
	$sheet = new PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($objPHPExcel, $app_strings['Report']);
	$objPHPExcel->addSheet($sheet);
}

$objPHPExcel->setActiveSheetIndex(0);	// crmv@112208

//crmv@139057 crmv@139048
$excel_type = 'Xls';
$excel_ext = 'xls';
$app_type = 'application/vnd.ms-excel';
if ($numrows > 80000){
	$excel_type = 'Csv';
	$excel_ext = 'csv';
	$app_type = 'text/csv';
} elseif ($numrows > 65000){	
	$excel_type = 'Xlsx';
	$excel_ext = 'xlsx';
	$app_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
}

$filename = $oReportRun->generateExportFileName($excel_ext); // crmv@192411

$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, $excel_type); // replace with Excel2007 and change extension to xlsx for the new format
$objWriter->setPreCalculateFormulas(false);
$objWriter->save($fname);
 
if ($_REQUEST['batch_export'] != 1) {

	if(isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'],'MSIE'))
	{
		header("Pragma: public");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	}
	header("Content-Type: {$app_type}");
	header("Content-Length: ".@filesize($fname));
	header('Content-disposition: attachment; filename="'.$filename.'"');
	$fh=fopen($fname, "rb");
	fpassthru($fh);
	//unlink($fname);
	exit;
}
//crmv@139057e crmv@139048e crmv@180826e