<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@150808 */

global $sdk_mode,$current_user;

$availableDays = array(0, 1);
$defaultFirstDay = 1; // default on monday

switch($sdk_mode) {
	case 'insert':
		break;
	case 'detail':
		$value = $col_fields[$fieldname];
		if ($value === null || $value === '') $value = $defaultFirstDay;
		
		$col_fields[$fieldname] = $value;
		
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = getTranslatedString('LBL_DAY'.intval($value),'Calendar');
		//crmv@167234 - removed code
		foreach ($availableDays as $day) {
			$langlabel = getTranslatedString('LBL_DAY'.$day, 'Calendar');
			$chk_val = ($value == $day ? "selected" : ""); 
			$options[] = array($langlabel,$day,$chk_val);	
		}

		$label_fld ["options"] = $options;
		break;
	case 'edit':
		if ($value === null || $value === '') $value = $defaultFirstDay;
		
		foreach ($availableDays as $day) {
			$langlabel = getTranslatedString('LBL_DAY'.$day, 'Calendar');
			$chk_val = ($value == $day ? "selected" : "");
			$options[] = array($langlabel,$day,$chk_val);	
		}

		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue [] = $options;
		break;
	case 'relatedlist':
	case 'list':
		$value = getTranslatedString('LBL_DAY'.intval($sdk_value),'Calendar');
		break;
}
?>