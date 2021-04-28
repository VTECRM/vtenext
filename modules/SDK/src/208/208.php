<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * crmv@37679
 */

require_once('modules/SDK/src/208/208Utils.php');
global $sdk_mode;
global $iAmAProcess; // crmv@183699

$uitype208 = new EncryptedUitype();
if (empty($fieldname)) $fieldname = $fieldName; // for list
if (empty($module)) $module = $module_name; // for editview
$fieldid = $uitype208->getFieldIdFromName($module, $fieldname);

$permitted = $uitype208->isPermitted($fieldid);
if ($permitted) {
	$pwd = $uitype208->getCachedPassword($fieldid);
	VteSession::setArray(array('uitype208', $fieldid, 'permitted'), true);
} else {
	$pwd = false;
	$uitype208->setCachedPassword($fieldid, '', false);
}

$str_not_accessible = '<font color="red">'.getTranslatedString('LBL_NOT_ACCESSIBLE').'</font>';
$str_encoded = '<font color="green">'.getTranslatedString('LBL_CIPHERED').'</font>';
switch($sdk_mode) {
	// crmv@183699
	case 'export':
		if ($pwd != false) {
			$value = $uitype208->decryptData($value, $pwd);
		} else {
			$value = ($permitted ? getTranslatedString('LBL_CIPHERED') : getTranslatedString('LBL_NOT_ACCESSIBLE'));
		}
		break;
	// crmv@183699e
	case 'insert':
		$fldvalue = $this->column_fields[$fieldname];

		$isAjax = ($_REQUEST['file'] == 'DetailViewAjax' && $_REQUEST['ajxaction'] == 'DETAILVIEW');
		$isValidAjax = (!$isAjax || ($isAjax && $_REQUEST['fldName'] == $fieldname));

		if ($pwd != false && $readonly != 100 && $isValidAjax && !$iAmAProcess) { // crmv@183699
			$fldvalue = $uitype208->encryptData($fldvalue, $pwd);
			$maxlen = $uitype208->getColumnLength($table_name, $columname);
			
			if ($maxlen < strlen($fldvalue)) {
				$sdk_skipfield = true;
				// inglorious death, but avoid data corruption
				die(':#:FAILURE : Column is too short to hold crypted data.');
			}
		// crmv@182306
		} elseif ($pwd === false && $readonly != 100 && $isAjax && $_REQUEST['fldName'] == $fieldname && !$iAmAProcess) { // crmv@183699
			die(':#:FAILURE : Password expired, refresh the page and try again.');
		// crmv@182306e
		} else {
			// prevent db update
			$sdk_skipfield = true;
		}
		
		// crmv@183699
		// Bad way to get the list of edited fields! Please find a better way!
		global $massedit_fields;
		if ($_REQUEST["action"] == "MassEditSave" && is_array($massedit_fields) && !in_array($fieldname, $massedit_fields)) {
			$sdk_skipfield = true;
		}
		// crmv@183699e
		
		break;
	case 'detail':
		$val = $col_fields[$fieldname];

		if ($pwd != false) {
			$val = $uitype208->decryptData($val, $pwd);
		} else {
			$val = ($permitted ? $str_encoded : $str_not_accessible);
		}
		$label_fld[] = getTranslatedString($fieldlabel,$module);
		$label_fld[] = $val;
		break;
	case 'edit':
		$encodedValue = $value;

		if ($pwd != false) {
			$value = $uitype208->decryptData($encodedValue, $pwd);
		} else {
			$value = ($permitted ? $str_encoded : $str_not_accessible);
		}

		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $value;
		break;
	case 'relatedlist':
	case 'list':
		if ($sdk_mode == 'list')
			$encodedValue = $rawValue;
		else
			$encodedValue = $field_val;

		if ($pwd != false) {
			$value = $uitype208->decryptData($encodedValue, $pwd);
		} else {
			$value = ($permitted ? $str_encoded : $str_not_accessible);
		}

		break;
}