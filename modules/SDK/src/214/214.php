<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@101930 */

global $sdk_mode,$current_user, $table_prefix;
global $showfullusername;

switch($sdk_mode) {
	case 'detail':
		
		$recordid = intval($_REQUEST['record']);	// BAD, BAD, BAD!!!
		$value = $col_fields[$fieldname];
		$owner = intval($col_fields['modifiedby']) ?: getSingleFieldValue($table_prefix.'_crmentity','modifiedby','crmid', $recordid);
		
		if (empty($owner)) {
			// try with the creator
			$owner = intval($col_fields['creator']) ?: getSingleFieldValue($table_prefix.'_crmentity','smcreatorid','crmid', $recordid);
		}
		
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $value;
		
		$label_fld['options'] = array(
			'ownerid' => $owner,
			'ownername' => getOwnerName($owner, $showfullusername),
			'avatar' => getUserAvatar($owner),
			'friendlytime' => getFriendlyDate($value),
		);

		break;
	default:
		// not visible
		break;
}