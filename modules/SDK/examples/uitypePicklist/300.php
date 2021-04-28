<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('300Utils.php');

global $sdk_mode;
switch($sdk_mode) {
	case 'detail':
		// code snippet from DeteailViewUtils.php
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = getTranslatedString($col_fields[$fieldname]);
		$roleid=$current_user->roleid;
		$values_arr = getAssignedPicklistValues($fieldname, $roleid, $adb,$module);
		$value = $col_fields[$fieldname];
		$value_decoded = decode_html($value);
		$pickcount = count($values_arr);
		if ($pickcount > 0){
			foreach ($values_arr as $pickListValue=>$translated_value){
				if($value_decoded == trim($pickListValue))
					$chk_val = "selected";
				else
					$chk_val = '';
				$pickListValue = to_html($pickListValue);
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities($translated_value,ENT_QUOTES,$default_charset),$pickListValue,$chk_val );	
				else
					$options[] = array($translated_value,$pickListValue,$chk_val );	
			}
		}
		elseif($pickcount == 0 && count($value))
		{
			$options[] =  array("<font color='red'>".$app_strings['LBL_NOT_ACCESSIBLE']."</font>",$value,'selected');
		}
		$label_fld ["options"] = $options;
		break;
	case 'edit':
		// code snippet from EditViewUtils.php
		$roleid=$current_user->roleid;
		$values_arr = getAssignedPicklistValues($fieldname, $roleid, $adb,$module_name);
		$value_decoded = decode_html($value);
		$pickcount = count($values_arr);
		if ($pickcount > 0){
			//crmv@18024	crmv@131239
			if ($value_decoded == '' && strpos($typeofdata,"M") !== false && !in_array($module_name,array('Calendar','Events'))){
				$options[] = array(getTranslatedString("LBL_PLEASE_SELECT"),'','selected');
			}
			//crmv@18024e	crmv@131239e
			foreach ($values_arr as $pickListValue=>$translated_value){
				if($value_decoded == trim($pickListValue))
					$chk_val = "selected";
				else
					$chk_val = '';
				$pickListValue = to_html($pickListValue);
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities($translated_value,ENT_QUOTES,$default_charset),$pickListValue,$chk_val );	
				else
					$options[] = array($translated_value,$pickListValue,$chk_val );	
			}
		}
		if($pickcount == 0 && count($value))
			$options[] =  array($app_strings['LBL_NOT_ACCESSIBLE'],$value,'selected');
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $options;
		$fieldvalue[] = $module_name;	//crmv@131239
		break;	
	case 'relatedlist':
	case 'list':
		$value = textlength_check(getTranslatedString($sdk_value,$module));
		break;
}
?>