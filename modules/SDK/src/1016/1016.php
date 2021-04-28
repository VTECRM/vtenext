<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@104567

global $sdk_mode, $table_prefix;

switch ($sdk_mode) {
	case 'detail':
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$signaturePath = $col_fields[$fieldname];
		if (!empty($signaturePath) && file_exists($signaturePath)) {
			$value = $signaturePath;
		} else {
			$value = null;
		}
		$label_fld[] = $value;
		break;
	case 'edit':
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$signaturePath = $col_fields[$fieldname];
		if (!empty($signaturePath) && file_exists($signaturePath)) {
			$value = $signaturePath;
		} else {
			$value = null;
		}
		$fieldvalue[] = $value;
		break;
	case 'relatedlist':
	case 'list':
		$value = $sdk_value;
		break;
	case 'pdfmaker':
		if (!empty($value) && file_exists($value)) {
			$value = '<img src="' . $value . '" style="height:2.5cm;" />';
		} else {
			$value = '';
		}
		break;
}