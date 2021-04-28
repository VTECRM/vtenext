<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $sdk_mode,$table_prefix;
switch($sdk_mode) {
	case 'detail':
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$imgpath = $col_fields[$fieldname];
		if($imgpath != '') {
			$label_fld[] = getUserAvatarImg($col_fields['record_id'],'id="'.$fieldname.'"');
		} else {
			$sql = "select ".$table_prefix."_attachments.* from ".$table_prefix."_attachments left join ".$table_prefix."_salesmanattachmentsrel on ".$table_prefix."_salesmanattachmentsrel.attachmentsid = ".$table_prefix."_attachments.attachmentsid where ".$table_prefix."_salesmanattachmentsrel.smid=?";
			$image_res = $adb->pquery($sql, array($col_fields['record_id']));
			if ($image_res && $adb->num_rows($image_res) > 0) {
				$label_fld[] = '';
			} else {			
				$label_fld[] = 'NO_USER_IMAGE';
			}
		}
		break;
		break;
	case 'edit':
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		if ($mode != 'edit') {
			$value = '';
		}
		$fieldvalue[] = $value;
		break;
	case 'relatedlist':
	case 'list':
		$value = $sdk_value;
		break;
}
?>