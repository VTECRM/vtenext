<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@187823 */

require_once('OrganizerField.php');

global $sdk_mode;

switch($sdk_mode) {
	case 'insert':
		$sdk_skipfield = true;
		break;
	case 'insert.after':
		$recordid = $this->id;
		if ($fldvalue && is_array($fldvalue)) {
			$ofield = OrganizerField::getInstance($module, $fieldname);
			$ofield->setValue($this->id, $fldvalue);
		}
		break;
	case 'detail':
		$recordid = $col_fields['record_id'];
		
		$ofield = OrganizerField::getInstance($module, $fieldname);
		$val = $ofield->getValue($recordid);
		$display = $ofield->getDisplayValue($val);
		
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $display;
		$label_fld['options'] = array(
			'org_type' => $val['type'],
			'simulate_uitype' => $val['simulate_uitype'],
		);
		break;
	case 'edit':
		// edit not supported by this uitype
		break;
	case 'relatedlist':
	case 'list':
	case 'report':
		$ofield = OrganizerField::getInstance($module, $fieldname ?: $fieldName);
		$val = $ofield->getValue($recordid ?: $recordId);
		$fieldvalue = $value = $ofield->getDisplayValue($val);
		break;
	case 'querygeneratorsearch':
		// TODO
		break;
	case 'popupbasicsearch':
		// TODO
		break;
	case 'popupadvancedsearch':
		// TODO
		break;
}