<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $sdk_mode;
switch($sdk_mode) {
	case 'detail':
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $col_fields[$fieldname];
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
		$roleid=$current_user->roleid;
		$values_arr = getAssignedPicklistValues($fieldname, $roleid, $adb,$module_name);
		$value_decoded = decode_html($value);
		$pickcount = count($values_arr);
		if ($pickcount > 0){
			//crmv@20094
			if ($uitype != 1115) {
			//crmv@20094e
				//crmv@18024
				if ($mode == '' && strpos($typeofdata,"M") !== false && !in_array($module_name,array('Calendar','Events'))){
					$options[] = array(getTranslatedString("LBL_PLEASE_SELECT"),'','selected');
				}
				//crmv@18024 end
			//crmv@20094
			}
			//crmv@20094e
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
		$fieldvalue [] = $options;
		break;
	case 'relatedlist':
		global $current_user,$adb;
		$roleid=$current_user->roleid;
		$values_arr = getAssignedPicklistValues($fieldname, $roleid, $adb,$module);
		//crmv@18228
		if ($module == 'Calendar' && $colname == 'activitystatus'){
			$values_arr = array_merge($values_arr,getAssignedPicklistValues('eventstatus', $roleid, $adb,$module));
		}
		//crmv@18228 end
		$value = $adb->query_result($list_result,$list_result_count,$colname);
		$value_decoded = decode_html($value);
		$pickcount = count($values_arr);
		//crmv@fix activitytype
		if (!($module == 'Calendar' && $fieldname == 'activitytype' && $value == 'Task')){
			if ($pickcount > 0){
				if (!in_array($value_decoded,array_keys($values_arr)) && $value_decoded != '')
					$value = "<font color='red'>".$app_strings['LBL_NOT_ACCESSIBLE']."</font>";
				else
					$value = textlength_check($values_arr[$value_decoded]);	
			}
			elseif($pickcount == 0 && count($value))
			{
				$value = "<font color='red'>".$app_strings['LBL_NOT_ACCESSIBLE']."</font>";
			}
		}
		else{
			$value = getTranslatedString($value,$module);
		}
		//crmv@fix activitytype end
		if($fieldname == 'ticket_title'){
			$value = '<a href="index.php?module=HelpDesk&action=DetailView&record='.$entity_id.'">'.$value.'</a>';
		}
		break;
	case 'list':
		if (!$is_admin && $this->picklistRoleMap[$fieldName] && !in_array($value, $this->picklistValueMap[$fieldName])) {
			$value = "<font color='red'>".getTranslatedString('LBL_NOT_ACCESSIBLE',$module)."</font>";
		} else {
			//crmv@fix translate
			$value = textlength_check(getTranslatedString($value,$module));
			//crmv@fix translate end
		}
		break;
}
?>