<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $sdk_mode,$adb;
switch($sdk_mode) {
	case 'detail':
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = getTranslatedString($col_fields[$fieldname]);
		$values = getModuleList201();
		$value = $col_fields[$fieldname];
		$value_decoded = decode_html($value);
		$pickcount = $adb->num_rows($values);
		if ($values && $pickcount > 0) {
			while($row=$adb->fetchByAssoc($values)) {
				if($value_decoded == trim($row['name']))
					$chk_val = "selected";
				else
					$chk_val = '';
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities(getTranslatedString($row['name'],$row['name']),ENT_QUOTES,$default_charset),to_html($row['name']),$chk_val);	
				else
					$options[] = array(getTranslatedString($row['name'],$row['name']),to_html($row['name']),$chk_val);	
			}
		} elseif($pickcount == 0 && count($value)) {
			$options[] =  array("<font color='red'>".$app_strings['LBL_NOT_ACCESSIBLE']."</font>",$value,'selected');
		}
		$label_fld ["options"] = $options;
		break;
	case 'edit':
		$values = getModuleList201();
		$value_decoded = decode_html($value);
		$pickcount = $adb->num_rows($values);
		if ($values && $pickcount > 0) {
			if ($mode == 'create' && strpos($typeofdata,"M") !== false && !in_array($module_name,array('Calendar','Events'))){
				$options[] = array(getTranslatedString("LBL_PLEASE_SELECT"),'','selected');
			}
			while($row=$adb->fetchByAssoc($values)) {
				if($value_decoded == trim($row['name']))
					$chk_val = "selected";
				elseif(($mode == 'create'  && trim($row['name']) == 'Home') || ($mode == 'edit' && trim($row['name']) == 'Home' && $value_decoded == ''))
					$chk_val = "selected";
				else
					$chk_val = '';
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities(getTranslatedString($row['name'],$row['name']),ENT_QUOTES,$default_charset),to_html($row['name']),$chk_val );	
				else
					$options[] = array(getTranslatedString($row['name'],$row['name']),to_html($row['name']),$chk_val );	
			}
		}
		if($pickcount == 0 && count($value))
			$options[] =  array($app_strings['LBL_NOT_ACCESSIBLE'],$value,'selected');
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue [] = $options;
		break;
	case 'relatedlist':
	case 'list':
		$value = getTranslatedString($sdk_value,$sdk_value);
		break;
}
?>