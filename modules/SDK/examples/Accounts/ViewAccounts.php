<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $sdk_mode,$table_prefix;
switch($sdk_mode) {
	case '':
		$col_fields['description'] = 'Descrizione di default.';
		break;
	case 'edit':
	case 'detail':
		if ($col_fields['industry'] == 'Banking' && $fieldname == 'website') {
			$readonly = 100;
			$success = true;
		}
		break;
	case 'popup_query':
	case 'list_related_query':
		$sdk_columns = array($table_prefix.'_account.industry');
		include('modules/SDK/AddColumnsToQueryView.php');
		break;
	case 'popup':
	case 'related':
	case 'list':
		$sdk_columnnames = array('industry');
		include('modules/SDK/GetFieldsFromQueryView.php');
		if ($sdk_columnvalues['industry'] == 'Banking' && $fieldname == 'website') {
			$readonly = 100;
			$success = true;
		}
		break;
	case 'report':
		if (!isset($sdk_columnvalues['industry'])) {
			$sdk_result = $adb->pquery("select industry from {$table_prefix}_account where accountid = ?",array($sdk_columnvalues['crmid']));
			if ($sdk_result && $adb->num_rows($sdk_result) > 0) {
				$sdk_columnvalues['industry'] = $adb->query_result($sdk_result,0,'industry');
			}
		}
		if ($sdk_columnvalues['industry'] == 'Banking' && $fieldname == 'website') {
			$readonly = 100;
			$success = true;
		}
		break;
}
?>