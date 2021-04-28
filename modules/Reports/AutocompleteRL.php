<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31775 crmv@98500 */

require_once('modules/Reports/Reports.php');
$module = $_REQUEST['cvmodule'];
$field = $_REQUEST['field'];
if ($module != '') {
	$reports = Reports::getInstance();
	$report_list = $reports->sgetRptsforFldr(false,$module);
	$autocomplete_return = array();
	foreach($report_list as $folder) {
		foreach($folder as $report) {
			$reportname = getTranslatedString($report['reportname'],'Reports');
			if (stripos($reportname,$_REQUEST['autocomplete_where']) !== false || $_REQUEST['autocomplete_where'] == 'ALL') {
				$autocomplete_return[] = array(
					'id'=>$report['reportid'],
					'label'=>$reportname,
					'return_function'=>"return_report_to_rl(".$report['reportid'].",'".$reportname."','".$field."')",
					'return_function_file'=>'modules/Targets/Targets.js',
				);
			}
		}
	}
	die(Zend_Json::encode($autocomplete_return));
}