<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@30014 - mostra il nome del report per id*/
global $sdk_mode, $adb, $table_prefix;

if (!function_exists('getReportName')) {
	function getReportName($reportid) {
		global $adb, $table_prefix;
	
		$rname = '';
		$res = $adb->pquery("select reportname, folderid from {$table_prefix}_report where reportid = ?", array($reportid));
		if ($res) {
			$rname = $adb->query_result_no_html($res, 0, 'reportname'); // crmv@183235
		}
		return $rname;
	}
}

switch($sdk_mode) {
	case 'insert':
		break;
	case 'edit':
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $value;
		$fieldvalue[] = decode_html("<a href='index.php?module=Reports&action=SaveAndRun&tab=Charts&record=".$value."'>".getReportName($value).'</a>');
		break;
	case 'detail':
		$label_fld[] = getTranslatedString($fieldlabel, $module);

		$label_fld[] = "<a href='index.php?module=Reports&action=SaveAndRun&tab=Charts&record=".$col_fields[$fieldname]."'>".getReportName($col_fields[$fieldname]).'</a>';
		break;
	case 'relatedlist':
	case 'list':
		$value = "<a href='index.php?module=Reports&action=SaveAndRun&tab=Charts&record=".$sdk_value."'>".getReportName($sdk_value).'</a>';
		break;

}

?>