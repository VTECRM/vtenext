<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@128159 */
require_once('modules/SDK/src/73/73Utils.php');
$uitypeTimeUtils = UitypeTimeUtils::getInstance();

global $sdk_mode;
switch($sdk_mode) {
	case 'insert':
		$fldvalue = $uitypeTimeUtils->time2Seconds($this->column_fields[$fieldname]);
		break;
	case 'formatvalue':
	case 'querygeneratorsearch':
	case 'customviewsearch':
	case 'reportsearch':
		if (is_array($value)) {
			foreach($value as $i => $v) {
				$value[$i] = $uitypeTimeUtils->time2Seconds($v);
			}
		} else {
			$value = $uitypeTimeUtils->time2Seconds($value);
		}
		if ($sdk_mode == 'reportsearch' && $comparator == 'bw') {
			$value2 = $uitypeTimeUtils->time2Seconds($value2);
		}
		break;
	case 'popupbasicsearch':
		$where = "$table_name.$column_name = '".$uitypeTimeUtils->time2Seconds($search_string)."'";
		break;
	case 'popupadvancedsearch':
		$srch_val = $uitypeTimeUtils->time2Seconds($srch_val);
		break;
	case 'detail':
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $uitypeTimeUtils->seconds2Time($col_fields[$fieldname]);
		break;
	case 'edit':
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $uitypeTimeUtils->seconds2Time($value);
		break;
	case 'relatedlist':
	case 'list':
	case 'pdfmaker':
		$value = $uitypeTimeUtils->seconds2Time($sdk_value);
		break;
	case 'report':
		$fieldvalue = $uitypeTimeUtils->seconds2Time($sdk_value);
		break;
}