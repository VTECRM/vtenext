<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@95157 */

global $sdk_mode,$current_user;

switch($sdk_mode) {
	case 'insert':
		break;
	case 'detail':
		$SBU = StorageBackendUtils::getInstance();
		
		$value = $col_fields[$fieldname];
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $SBU->getBackendLabel($value);
		
		break;
	case 'edit':
		
		$SBU = StorageBackendUtils::getInstance();
		$list = $SBU->getAvailableBackends($module_name);
		
		$value_decoded = decode_html($value);
		$pickcount = count($list);
		if ($list && $pickcount > 0) {
			foreach ($list as $prefix=>$themelabel) {
				$chk_val = ($value_decoded == trim($prefix)) ? "selected" : "";
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities($themelabel,ENT_QUOTES,$default_charset),to_html($prefix),$chk_val );	
				else
					$options[] = array($themelabel ,to_html($prefix),$chk_val );	
			}
		}
		if($pickcount == 0 && count($value))
			$options[] =  array($app_strings['LBL_NOT_ACCESSIBLE'],$value,'selected');
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue [] = $options;
		break;
	case 'relatedlist':
	case 'list':
		$SBU = StorageBackendUtils::getInstance();
		$value = $SBU->getBackendLabel($sdk_value);
		break;
}
?>