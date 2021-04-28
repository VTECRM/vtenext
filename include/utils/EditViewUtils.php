<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/database/PearDatabase.php');
require_once('include/ComboUtil.php'); //new
require_once('include/utils/CommonUtils.php'); //new
require_once 'modules/PickList/PickListUtils.php';
/** This function returns the vte_field details for a given vte_fieldname.
  * Param $uitype - UI type of the vte_field
  * Param $fieldname - Form vte_field name
  * Param $fieldlabel - Form vte_field label name
  * Param $maxlength - maximum length of the vte_field
  * Param $col_fields - array contains the vte_fieldname and values
  * Param $generatedtype - Field generated type (default is 1)
  * Param $module_name - module name
  * Return type is an array
  */

function getOutputHtml($uitype, $fieldname, $fieldlabel, $maxlength, $col_fields,$generatedtype,$module_name,$mode='',$readonly='',$typeofdata='',$dynaform_info=array())	//crmv@96450
{
	global $log;
	$log->debug("Entering getOutputHtml(".$uitype.",". $fieldname.",". $fieldlabel.",". $maxlength.",". $col_fields.",".$generatedtype.",".$module_name.") method ...");
	global $adb,$log,$default_charset,$table_prefix;
	global $theme,$mod_strings,$app_strings,$current_user,$processMakerView; //crmv@161211

	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

	$InventoryUtils = InventoryUtils::getInstance(); // crmv@42024

	$theme_path="themes/".$theme."/";
	$image_path=$theme_path."images/";
	$fieldlabel = from_html($fieldlabel);
	$fieldvalue = Array();
	$final_arr = Array();
	$value = $col_fields[$fieldname];
	$custfld = '';
	$ui_type[]= $uitype;
	$editview_fldname[] = $fieldname;

	//ds@12
    if($uitype == 19 && $fieldname == 'salesorder_introduction') //salesorder einleitung field
    {
        if($value == '')
            $fieldvalue[] = $mod_strings['intro_text'];
    }
    if($uitype == 19 && $fieldname == 'invoice_introduction') //invoice einleitung field
    {
        if($value == '')
            $fieldvalue[] = $mod_strings['intro_text'];
    }
    if($fieldname == 'salesorder_mwst')
    {
        if($value == '')
            $fieldvalue[] = 1;
    }
    if($fieldname == 'invoice_mwst')
    {
        if($value == '')
            $fieldvalue[] = 1;
    }
	//ds@12e

	//crmv@sdk-18509
	if(SDK::isUitype($uitype))
	{
		$sdk_file = SDK::getUitypeFile('php','edit',$uitype);
		if ($sdk_file != '') {
			include($sdk_file);
		}
	}
	//crmv@sdk-18509 e
    // vtlib customization: Related type field
	elseif($uitype == '10') {
		global $adb, $table_prefix;
		//crmv@96450 crmv@160837
		if (!empty($dynaform_info['relatedmods'])) {
			$entityTypes = explode(",",$dynaform_info['relatedmods']);
			$parent_id = $value;
		} elseif (!empty($dynaform_info['relatedmods_selected'])) {
			$entityTypes = explode(",",$dynaform_info['relatedmods_selected']); // fix
			$parent_id = $value;
		} else {
		//crmv@96450e crmv@160837e
			// crmv@113611
			$fldmod_result = $adb->pquery(
				"SELECT relmodule, status FROM {$table_prefix}_fieldmodulerel
				INNER JOIN {$table_prefix}_field ON {$table_prefix}_field.fieldid = {$table_prefix}_fieldmodulerel.fieldid
				INNER JOIN {$table_prefix}_tab ON {$table_prefix}_tab.tabid = {$table_prefix}_field.tabid
				WHERE {$table_prefix}_field.fieldname = ? AND {$table_prefix}_tab.name = ? AND {$table_prefix}_field.presence in (0,2)
				ORDER BY {$table_prefix}_fieldmodulerel.sequence ASC",
				Array($fieldname, $module_name)
			);
			// crmv@113611e	
			$entityTypes = Array();
			$parent_id = $value;
			for($index = 0; $index < $adb->num_rows($fldmod_result); ++$index) {
				$relmodule = $adb->query_result_no_html($fldmod_result, $index, 'relmodule');
				$entityTypes[getTranslatedString($relmodule,$relmodule)] = $relmodule;
			}
			ksort($entityTypes);
			$entityTypes = array_values($entityTypes); // crmv@175478
		}	//crmv@96450
		//crmv@90385
		if (!empty($value) && in_array('Users',$entityTypes)) {
			$resUserField = $adb->pquery("SELECT user_name, first_name, last_name FROM {$table_prefix}_users WHERE id = ?", array($value));
			if ($resUserField && $adb->num_rows($resUserField) > 0) {
				$valueType = 'Users';
				$displayValue = getUserFullName($value, $resUserField);
				$parent_id = $value;
			}
		} else {
		//crmv@90385e
			//crmv@51071
			if ($_REQUEST['action'] == 'CustomViewAjax' ) {
				foreach ($entityTypes as $entitytype) {
					$entity_id = getEntityId($entitytype,$value);
					if ($entity_id != 0) {
						$parent_id = $entity_id;
						$value = $entity_id;
						break;
					}
				}
			}
			//crmv@51071e
			//crmv@54924
			$displayValue = '';
			if(!empty($value)) {
				$valueType = getSalesEntityType($value);
				if (in_array($valueType,$entityTypes)) {
					$displayValueArray = getEntityName($valueType, $value);
					if(!empty($displayValueArray)){
						foreach($displayValueArray as $key=>$value){
							$displayValue = $value;
						}
					}
				}
			}
		}	//crmv@90385
		//crmv@92272
		if ($_REQUEST['enable_editoptions'] == 'yes') {
			$entityTypes[] = 'Other';
			if ($parent_id != '' && !is_numeric($parent_id)) {
				$valueType = 'Other';
				$displayValue = $parent_id;
			}
		}
		//crmv@92272e
		if (empty($displayValue)) {
			$valueType='';
			$value='';
			$parent_id='';
		}		
		//crmv@54924e
		$editview_label[] = Array('options'=>$entityTypes, 'selected'=>$valueType, 'displaylabel'=>getTranslatedString($fieldlabel, $module_name));
		$fieldvalue[] = Array('displayvalue'=>$displayValue,'entityid'=>$parent_id);

	} // END
	elseif($uitype == 5 || $uitype == 6 || $uitype ==23)
	{
		$log->info("uitype is ".$uitype);
		if($value=='')
		{

			if($fieldname != 'birthday' && $generatedtype != 2 && getTabid($module_name) !=14)// && $fieldname != 'due_date')//due date is today's date by default
				$disp_value=getNewDisplayDate();

			//Added to display the Contact - Support End Date as one year future instead of today's date -- 30-11-2005
			if($fieldname == 'support_end_date' && $_REQUEST['module'] == 'Contacts')
			{
				$addyear = strtotime("+1 year");
				global $current_user;
				$dat_fmt = (($current_user->date_format == '')?('dd-mm-yyyy'):($current_user->date_format));


                //ds@30

				if($dat_fmt == 'dd-mm-yyyy')
				{
                    $disp_value = date('d-m-Y',$addyear);
                }
                elseif($dat_fmt == 'mm-dd-yyyy')
                {
                    $disp_value = date('m-d-Y',$addyear);
                }
                elseif($dat_fmt == 'yyyy-mm-dd')
                {
                    $disp_value = date('Y-m-d', $addyear);
                }


                elseif($dat_fmt == 'mm.dd.yyyy')
                {
                    $disp_value = date('m.d.Y',$addyear);
                }
                elseif($dat_fmt == 'dd.mm.yyyy')
                {
                    $disp_value = date('d.m.Y', $addyear);
                }
                elseif($dat_fmt == 'yyyy.mm.dd')
                {
                    $disp_value = date('Y.m.d', $addyear);
                }


                elseif($dat_fmt == 'mm/dd/yyyy')
                {
                    $disp_value = date('m/d/Y',$addyear);
                }
                elseif($dat_fmt == 'dd/mm/yyyy')
                {
                    $disp_value = date('d/m/Y', $addyear);
                }
                elseif($dat_fmt == 'yyyy/mm/dd')
                {
                    $disp_value = date('Y/m/d', $addyear);
                }
                else
                    $disp_value = '';


				#$disp_value = (($dat_fmt == 'dd-mm-yyyy')?(date('d-m-Y',$addyear)):(($dat_fmt == 'mm-dd-yyyy')?(date('m-d-Y',$addyear)):(($dat_fmt == 'yyyy-mm-dd')?(date('Y-m-d', $addyear)):(''))));
				//ds@30e
			}

			if($fieldname == 'validtill' && $_REQUEST['module'] == 'Quotes')
			{
				$disp_value = '';
			}

			if ($_REQUEST['enable_editoptions'] == 'yes') $disp_value = '';	//crmv@92272
		}
		//crmv@96450 crmv@116011 crmv120769 crmv@161211
		elseif (is_array($arr_value = Zend_Json::decode($value)) && !empty($arr_value['custom'])) {
			$arr_value['custom'] = getDisplayDate(substr($arr_value['custom'],0,10));
			$disp_value = Zend_Json::encode($arr_value);
		}
		elseif ($processMakerView && strpos($value,'$') !== false)	// old mode
			$disp_value = $value;
		//crmv@96450e crmv@116011e crmv@120769e crmv@161211e
		else
		{
			$disp_value = getDisplayDate(substr($value,0,10));
		}
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$date_format = parse_calendardate($app_strings['NTC_DATE_FORMAT']);
		if($uitype == 6)
		{
			if($col_fields['time_start']!='')
			{
				$curr_time = $col_fields['time_start'];
			}
			else
			{
				$curr_time = date('H:i',(time() + (5 * 60)));
			}
		}
		if($module_name == 'Events' && $uitype == 23)
		{
			if($col_fields['time_end']!='')
			{
				$curr_time = $col_fields['time_end'];
			}
			else
			{
				$endtime = time() + (10 * 60);
				$curr_time = date('H:i',$endtime);
			}
		}
		$fieldvalue[] = array($disp_value => $curr_time) ;
		if($uitype == 5 || $uitype == 23)
		{
			if($module_name == 'Events' && $uitype == 23)
			{
				$fieldvalue[] = array($date_format=>getTranslatedString($current_user->date_format,'Users').' '.$app_strings['YEAR_MONTH_DATE']);
			}
			else
				$fieldvalue[] = array($date_format=>getTranslatedString($current_user->date_format,'Users'));
		}
		else
		{
			$fieldvalue[] = array($date_format=>getTranslatedString($current_user->date_format,'Users').' '.$app_strings['YEAR_MONTH_DATE']);
		}
	}
	elseif($uitype == 15 || $uitype == 16)
	{
		//crmv@96450
		if (!empty($dynaform_info['picklistvalues'])) {
			$picklistvalues = explode("\n",$dynaform_info['picklistvalues']);
			if (!empty($picklistvalues)) {
				foreach($picklistvalues as $picklistvalue) {
					$picklistvalue = trim($picklistvalue);
					$values_arr[$picklistvalue] = getTranslatedString($picklistvalue,'Processes');	//crmv@112993
				}
			}
		} else {
			$roleid=$current_user->roleid;
			$values_arr = getAssignedPicklistValues($fieldname, $roleid, $adb,$module_name);
		}
		//crmv@96450e
		$value_decoded = decode_html($value);
		$pickcount = count($values_arr);
		$empty_value = false;	//crmv@114293
		$selected_value = false; //crmv@160843
		if ($pickcount > 0){
			//crmv@18024 crmv@114293
			if ($value_decoded == '' && strpos($typeofdata,"M") !== false && !in_array($module_name,array('Calendar','Events'))){
				$options[] = array(getTranslatedString("LBL_PLEASE_SELECT"),'','selected');
				$empty_value = true;
				$selected_value = true; //crmv@160843
			}
			//crmv@18024e crmv@114293e
			foreach ($values_arr as $pickListValue=>$translated_value){
				if ($empty_value && trim($pickListValue) == '') continue;		//crmv@114293
				if($value_decoded == trim($pickListValue)) {
					$chk_val = "selected";
					$selected_value = true; //crmv@160843
				} else
					$chk_val = '';
				$pickListValue = to_html($pickListValue);
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities($translated_value,ENT_QUOTES,$default_charset),$pickListValue,$chk_val );
				else
					$options[] = array($translated_value,$pickListValue,$chk_val );
			}
		}
		if($pickcount == 0 && $value != '') // crmv@172864
			$options[] =  array($app_strings['LBL_NOT_ACCESSIBLE'],$value,'selected');
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue [] = $options;
		//crmv@160843
		if ($_REQUEST['enable_editoptions'] == 'yes') {
			$fieldvalue[] = array(
				'enable_editoptions' => true,
				'picklist_display' => (!$selected_value)?'none':'block',
				'editoptions_div_display' => ($selected_value)?'none':'block',
				'other_value' => (!$selected_value)?$value_decoded:'',
				'type_options' => array(
					array('v',str_replace(':','',getTranslatedString('LBL_PICK_LIST_VALUES','Settings')),($selected_value)?'selected':''),
					array('o',getTranslatedString('LBL_OTHER','Users'),(!$selected_value)?'selected':''),
				),
			);
		}
		//crmv@160843e
	}
	//crmv@8982
	elseif($uitype == 1015)
	{
		$picklistvalues = Picklistmulti::getTranslatedPicklist(false,$fieldname);
		$value = decode_html($value);
		if (is_array($picklistvalues)){
			foreach ($picklistvalues as $picklistid=>$pickListValue){
				if ($value === trim($picklistid)){
					$chk_val = "selected";
					$pickcount++;
					$found = true;
				}
				else
					$chk_val = '';
				$pickListValue =to_html($pickListValue);
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities($pickListValue,ENT_QUOTES,$default_charset),$picklistid,$chk_val );
				else
					$options[] = array($pickListValue,$picklistid,$chk_val );
				}
		}
		if (!$found){
			$selected = "selected";
		}
		else
			$selected = "";
		//default value empty!
		if (is_array($options))
				array_unshift($options,array(getTranslatedString("LBL_PLEASE_SELECT"),"",$selected));
			else
				$options[] = array(getTranslatedString("LBL_PLEASE_SELECT"),"",$selected);
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue [] = $options;
	}
	//crmv@8982e
	elseif($uitype == 17)
	{
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue [] = $value;
	}
	elseif($uitype == 85) //added for Skype by Minnie
	{
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue [] = $value;
	}
	elseif($uitype == 33)
	{
		$roleid=$current_user->roleid;
		//crmv@96450
		if (!empty($dynaform_info['picklistvalues'])) {
			$picklistvalues = explode("\n",$dynaform_info['picklistvalues']);
			if (!empty($picklistvalues)) {
				foreach($picklistvalues as $picklistvalue) {
					$picklistvalue = trim($picklistvalue);
					$values_arr[$picklistvalue] = $picklistvalue;
				}
			}
		} else
		//crmv@96450e
			$values_arr = getAssignedPicklistValues($fieldname, $roleid, $adb,$module_name);
		//crmv@60862
		if ($_REQUEST['module'] == 'CustomView' && $fieldname == 'taskstatus') {
			$values_arr2 = getAssignedPicklistValues('eventstatus', $roleid, $adb,$module_name);
			$values_arr = array_merge($values_arr,$values_arr2);
		}
		//crmv@60862
		$valuearr = explode(' |##| ',$value);
		if (is_array($valuearr)){
			foreach ($valuearr as $k=>$v){
				$valuearr_decoded[$k] = decode_html($v);
			}
		}
		else {
			$valuearr_decoded = Array();
		}
		$selected_value = false; //crmv@160843
		$pickcount = count($values_arr);
		if ($pickcount > 0){
			foreach ($values_arr as $pickListValue=>$translated_value){
				if(in_array(trim($pickListValue),$valuearr_decoded)) {
					$chk_val = "selected";
					$selected_value = true; //crmv@160843
				} else
					$chk_val = '';
				$pickListValue = to_html($pickListValue);
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
					$options[] = array(htmlentities($translated_value,ENT_QUOTES,$default_charset),$pickListValue,$chk_val );
				else
					$options[] = array($translated_value,$pickListValue,$chk_val );
			}
			if ($value != ''){
				foreach (array_diff($valuearr_decoded,array_keys($values_arr)) as $value_not_accessible){ //crmv@42451
					$options[] =  array($app_strings['LBL_NOT_ACCESSIBLE'],$value_not_accessible,'selected');
				}
			}
		}
		elseif($value != '')
		{
			$options[] =  array($app_strings['LBL_NOT_ACCESSIBLE'],$value,'selected');
		}
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue [] = $options;
		//crmv@160843
		if ($_REQUEST['enable_editoptions'] == 'yes') {
			if ($value == '' && !$selected_value) $selected_value = true;
			$fieldvalue[] = array(
				'enable_editoptions' => true,
				'picklist_display' => (!$selected_value)?'none':'block',
				'editoptions_div_display' => ($selected_value)?'none':'block',
				'other_value' => (!$selected_value)?$value:'',
				'type_options' => array(
					array('v',str_replace(':','',getTranslatedString('LBL_PICK_LIST_VALUES','Settings')),($selected_value)?'selected':''),
					array('o',getTranslatedString('LBL_OTHER','Users'),(!$selected_value)?'selected':''),
				),
			);
		}
		//crmv@160843e
	}
	elseif($uitype == 19)
	{
		if(isset($_REQUEST['body']))
		{
			$value = ($_REQUEST['body']);
		}

		if($fieldname == 'terms_conditions')//for default Terms & Conditions
		{
			//Assign the value from focus->column_fields (if we create Invoice from SO the SO's terms and conditions will be loaded to Invoice's terms and conditions, etc.,)
			$value = $col_fields['terms_conditions'];

			//if the value is empty then only we should get the default Terms and Conditions
			if($value == '' && $mode != 'edit')
				$value=getTermsandConditions();
		}

		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue [] = $value;
	}
	elseif($uitype == 21)
	{
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue [] = $value;
	}
	//crmv@101683 crmv@160843
	elseif($uitype == 52 || $uitype == 77 || $uitype == 54)
	{
		global $current_user;
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$selected_value = false;
		($value != '') ? $assigned_user_id = $value : $assigned_user_id = $current_user->id;
		$add_blank = false;
		if ($mode == '' && strpos($typeofdata,"M") !== false) {
			$add_blank = true;
			$assigned_user_id = '';
		}
		//Control will come here only for Products - Handler and Quotes - Inventory Manager
		if($is_admin==false && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[getTabid($module_name)] == 3 or $defaultOrgSharingPermission[getTabid($module_name)] == 0))
		{
			if ($uitype == 54)
				$combo = get_select_options_array(get_group_array($add_blank, "Active", $assigned_user_id,'private'), $assigned_user_id, 'false', $selected_value);
			else
				$combo = get_select_options_array(get_user_array($add_blank, "Active", $assigned_user_id,'private'), $assigned_user_id, 'false', $selected_value);
		}
		else
		{
			if ($uitype == 54)
				$combo = get_select_options_array(get_group_array($add_blank, "Active", $assigned_user_id), $assigned_user_id, 'false', $selected_value);
			else
				$combo = get_select_options_array(get_user_array($add_blank, "Active", $assigned_user_id), $assigned_user_id, 'false', $selected_value);
		}
		$fieldvalue [] = $combo;
		if ($_REQUEST['enable_editoptions'] == 'yes' && ($uitype == 52 || $uitype == 77)) {
			if ($value == '' && !$selected_value) $selected_value = true;
			$fieldvalue[] = array(
				'enable_editoptions' => true,
				//'skip_advanced_type_option' => false,
				'picklist_display' => (!$selected_value)?'none':'block',
				'editoptions_div_display' => (!$selected_value && $value != 'advanced_field_assignment')?'block':'none',
				'advanced_field_assignment_display' => ($value == 'advanced_field_assignment')?'block':'none',
				'other_value' => (!$selected_value)?$value:'',
				'type_options' => array(
					array('v',str_replace(':','',getTranslatedString('LBL_USERS_LIST','Settings')),($selected_value)?'selected':''),
					array('o',getTranslatedString('LBL_OTHER','Users'),(!$selected_value && $value != 'advanced_field_assignment')?'selected':''),
					array('A',getTranslatedString('LBL_ADVANCED'),($value == 'advanced_field_assignment')?'selected':''),
				),
			);
			if ($_REQUEST['file'] = 'ProcessMaker/actions/CreateForm' && stripos($_REQUEST['module'],'ModLight') !== false) unset($fieldvalue[1]['type_options'][2]);
		}
	}
	//crmv@101683e crmv@160843e
	// crmv@112606 crmv@160843
	elseif($uitype == 53)
	{
		if (!empty($dynaform_info) && stripos($value,'x') !== false) list(,$value) = explode('x',$value); // fix
		
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		
		$selected_value = false;
		(!empty($value)) ? $assigned_user_id = $value : $assigned_user_id = $current_user->id;
		
		if($fieldlabel == 'Assigned To' && $is_admin==false && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[getTabid($module_name)] == 3 or $defaultOrgSharingPermission[getTabid($module_name)] == 0))
		{
			$users_combo = get_select_options_array(get_user_array(FALSE, "Active", $assigned_user_id,'private'), $assigned_user_id, 'false', $selected_value);
		}
		else
		{
			$users_combo = get_select_options_array(get_user_array(FALSE, "Active", $assigned_user_id), $assigned_user_id, 'false', $selected_value);
		}
		
		if($fieldlabel == 'Assigned To' && $is_admin==false && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[getTabid($module_name)] == 3 or $defaultOrgSharingPermission[getTabid($module_name)] == 0))
		{
			$groups_combo = get_select_options_array(get_group_array(FALSE, "Active", $assigned_user_id,'private'), $assigned_user_id, 'false', $selected_value);
		}
		else
		{
			$groups_combo = get_select_options_array(get_group_array(FALSE, "Active", $assigned_user_id), $assigned_user_id, 'false', $selected_value);
		}
		$fieldvalue[]= $users_combo;
		$fieldvalue[] = $groups_combo;
		if ($_REQUEST['enable_editoptions'] == 'yes') {
			if ($value == '' && !$selected_value) $selected_value = true;
			$fieldvalue[] = array(
				'enable_editoptions' => true,
				'skip_advanced_type_option' => false,
				'editoptions_div_display' => (!$selected_value && $value != 'advanced_field_assignment')?'block':'none',
				'advanced_field_assignment_display' => ($value == 'advanced_field_assignment')?'block':'none',
				'other_value' => (!$selected_value)?$value:'',
			);
		}
	}
	// crmv@112606e crmv@160843e
	elseif($uitype == 55 || $uitype == 255){
		if($uitype==255){
			$fieldpermission = getFieldVisibilityPermission($module_name, $current_user->id,'firstname');
		}
		if($uitype == 255 && $fieldpermission == '0'){
			$fieldvalue[] = '';
		}else{
			$roleid=$current_user->roleid;
			$picklistValues = getAssignedPicklistValues('salutationtype', $roleid, $adb, $module_name);	//crmv@49922
			$pickcount = 0;
			$salt_value = $col_fields["salutationtype"];
			foreach($picklistValues as $pickListValue=>$translated_value){
				if($salt_value == trim($pickListValue)){
					$chk_val = "selected";
					$pickcount++;
				}else{
					$chk_val = '';
				}
				if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate'){
					$options[] = array(htmlentities($translated_value,ENT_QUOTES,$default_charset),$pickListValue,$chk_val );
				}else{
					$options[] = array($translated_value,$pickListValue,$chk_val);
				}
			}
			if($pickcount == 0 && $salt_value != ''){
				$options[] =  array($app_strings['LBL_NOT_ACCESSIBLE'],$salt_value,'selected');
			}
			$fieldvalue [] = $options;
		}
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $value;
	}
	elseif($uitype == 63)
	{
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		if($value=='')
			$value=1;
		$options = array();
		$pick_query="select * from ".$table_prefix."_duration_minutes order by sortorderid";
		$pickListResult = $adb->pquery($pick_query, array());
		$noofpickrows = $adb->num_rows($pickListResult);
		$salt_value = $col_fields["duration_minutes"];
		for($j = 0; $j < $noofpickrows; $j++)
		{
			$pickListValue=$adb->query_result($pickListResult,$j,"duration_minutes");

			if($salt_value == $pickListValue)
			{
				$chk_val = "selected";
			}
			else
			{
				$chk_val = '';
			}
			$options[$pickListValue] = $chk_val;
		}
		$fieldvalue[]=$value;
		$fieldvalue[]=$options;
	}
	elseif($uitype == 64)
	{
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$date_format = parse_calendardate($app_strings['NTC_DATE_FORMAT']);
		$fieldvalue[] = $value;
	}
	elseif($uitype == 156)
	{
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $value;
		$fieldvalue[] = $is_admin;
	}
	elseif($uitype == 56)
	{
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $value;
		//crmv@55961 crmv@78396 crmv@181281
		if ($mode == '' && $fieldname == 'newsletter_unsubscrpt' && $_REQUEST['module'] != 'CustomView') {
			$focusNewsletter = CRMEntity::getInstance('Newsletter');
			if (array_key_exists($module_name,$focusNewsletter->email_fields)) $readonly = 100;
		}
		//crmv@55961e crmv@78396e crmv@181281e
		//crmv@96450 crmv@115268 crmv@161211
		if ($processMakerView) {
			$options = array();
			$options[] = array($app_strings['LBL_NO'],0,($value == 0)?'selected':'');
			$options[] = array($app_strings['LBL_YES'],1,($value == 1)?'selected':'');
			$fieldvalue[] = 'picklist';
			$fieldvalue[] = $options;
		}
		//crmv@96450e crmv@115268e crmv@161211
	}
	elseif($uitype == 61)
	{
		if($value != '')
		{
			$assigned_user_id = $value;
		}
		else
		{
			$assigned_user_id = $current_user->id;
		}
		//crmv@7216
		if(($module_name == 'Emails' || $module_name == 'Fax') && $col_fields['record_id'] != '')
		//crmv@7216e
		{
			$attach_result = $adb->pquery("select * from ".$table_prefix."_seattachmentsrel where crmid = ?", array($col_fields['record_id']));
			//to fix the issue in mail attachment on forwarding mails
			if(isset($_REQUEST['forward']) && $_REQUEST['forward'] != '')
				global $att_id_list;
			for($ii=0;$ii < $adb->num_rows($attach_result);$ii++)
			{
				$attachmentid = $adb->query_result($attach_result,$ii,'attachmentsid');
				if($attachmentid != '')
				{
					$attachquery = "select * from ".$table_prefix."_attachments where attachmentsid=?";
					$attachmentsname = $adb->query_result($adb->pquery($attachquery, array($attachmentid)),0,'name');
					if($attachmentsname != '')
						$fieldvalue[$attachmentid] = '[ '.$attachmentsname.' ]';
					if(isset($_REQUEST['forward']) && $_REQUEST['forward'] != '')
						$att_id_list .= $attachmentid.';';
				}

			}
		}else
		{
			if($col_fields['record_id'] != '')
			{
				$attachmentid=$adb->query_result($adb->pquery("select * from ".$table_prefix."_seattachmentsrel where crmid = ?", array($col_fields['record_id'])),0,'attachmentsid');
				if($col_fields[$fieldname] == '' && $attachmentid != '')
				{
					$attachquery = "select * from ".$table_prefix."_attachments where attachmentsid=?";
					$value = $adb->query_result($adb->pquery($attachquery, array($attachmentid)),0,'name');
				}
			}
			if($value!='')
				$filename=' [ '.$value. ' ]';
			if($filename != '')
				$fieldvalue[] = $filename;
			if($value != '')
				$fieldvalue[] = $value;
		}
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
	}
	elseif($uitype == 28){
		if($col_fields['record_id'] != '')
		{
			$attachmentid=$adb->query_result($adb->pquery("select * from ".$table_prefix."_seattachmentsrel where crmid = ?", array($col_fields['record_id'])),0,'attachmentsid');
			if($col_fields[$fieldname] == '' && $attachmentid != '')
			{
				$attachquery = "select * from ".$table_prefix."_attachments where attachmentsid=?";
				$value = $adb->query_result($adb->pquery($attachquery, array($attachmentid)),0,'name');
			}
		}
		if($value!='' && $module_name != 'Documents')
			$filename=' [ '.$value. ' ]';
		elseif($value != '' && $module_name == 'Documents')
			$filename= $value;
		$filename = html_entity_decode($filename,ENT_QUOTES,$default_charset); //crmv@131416
		//crmv@104365
		if($filename != '') {
			$fieldvalue[] = $filename;
			$link = $filename;
			if($col_fields['filelocationtype'] == 'E' ){
       			$link = '<a target="_blank" href ='.$col_fields['filename'].' onclick=\'javascript:dldCntIncrease('.$col_fields['record_id'].');\'>'.$filename.'</a>';
        	}elseif($col_fields['filelocationtype'] == 'I' || $col_fields['filelocationtype'] == 'B') { // crmv@95157
       			$link = '<a href = "index.php?module=uploads&action=downloadfile&return_module='.$col_fields['record_module'].'&fileid='.$attachmentid.'&entityid='.$col_fields['record_id'].'" onclick=\'javascript:dldCntIncrease('.$col_fields['record_id'].');\'>'.$filename.'</a>';
        	}
		}
		if($value != '')
			$fieldvalue[] = $value;
		if($link != '')
			$fieldvalue[] = $link;
		//crmv@104365e

		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
	}
	elseif($uitype == 69)
  	{
  		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
 		if( $col_fields['record_id'] != "")
  		{
 		    //This query is for Products only
 		    if($module_name == 'Products')
 		    {
			    $query = 'select '.$table_prefix.'_attachments.path, '.$table_prefix.'_attachments.attachmentsid, '.$table_prefix.'_attachments.name ,'.$table_prefix.'_crmentity.setype from '.$table_prefix.'_products left join '.$table_prefix.'_seattachmentsrel on '.$table_prefix.'_seattachmentsrel.crmid='.$table_prefix.'_products.productid inner join '.$table_prefix.'_attachments on '.$table_prefix.'_attachments.attachmentsid='.$table_prefix.'_seattachmentsrel.attachmentsid inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_attachments.attachmentsid where '.$table_prefix.'_crmentity.setype=? and productid=?';
			    $params = array("Products Image");
 		    }
 		    else
		    {
			    	$query="select ".$table_prefix."_attachments.*,".$table_prefix."_crmentity.setype from ".$table_prefix."_attachments inner join ".$table_prefix."_seattachmentsrel on ".$table_prefix."_seattachmentsrel.attachmentsid = ".$table_prefix."_attachments.attachmentsid inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_attachments.attachmentsid where ".$table_prefix."_crmentity.setype='Contacts Image' and ".$table_prefix."_seattachmentsrel.crmid=?";
 		    }
 		    $params[] = $col_fields['record_id'];
 		    $result_image = $adb->pquery($query,$params);
 		    for($image_iter=0;$image_iter < $adb->num_rows($result_image);$image_iter++)
 		    {
			    $image_id_array[] = $adb->query_result($result_image,$image_iter,'attachmentsid');

			    //decode_html  - added to handle UTF-8   characters in file names
			    //urlencode    - added to handle special characters like #, %, etc.,
 			    $image_array[] = urlencode(decode_html($adb->query_result($result_image,$image_iter,'name')));
			    $image_orgname_array[] = decode_html($adb->query_result($result_image,$image_iter,'name'));

 			    $image_path_array[] = $adb->query_result($result_image,$image_iter,'path');
 		    }
 		    if(is_array($image_array))
 			    for($img_itr=0;$img_itr<count($image_array);$img_itr++)
 			    {
 				    $fieldvalue[] = array('name'=>$image_array[$img_itr],'path'=>$image_path_array[$img_itr].$image_id_array[$img_itr]."_","orgname"=>$image_orgname_array[$img_itr]);
 			    }
 		    else
 			    $fieldvalue[] = '';
  		}
  		else
  			$fieldvalue[] = '';
  	}
	elseif($uitype == 62)
	{
		if(isset($_REQUEST['parent_id']) && $_REQUEST['parent_id'] != '')
			$value = $_REQUEST['parent_id'];
		if($value != '')
			$parent_module = getSalesEntityType($value);
		if(isset($_REQUEST['account_id']) && $_REQUEST['account_id'] != '')
		{
			$parent_module = "Accounts";
			$value = $_REQUEST['account_id'];
		}
		if($parent_module != 'Contacts')
		{
			if($parent_module == "Leads")
			{
				$parent_name = getLeadName($value);
				$lead_selected = "selected";

			}
			elseif($parent_module == "Accounts")
			{
				$sql = "select * from  ".$table_prefix."_account where accountid=?";
				$result = $adb->pquery($sql, array($value));
				$parent_name = $adb->query_result($result,0,"accountname");
				$account_selected = "selected";

			}
			elseif($parent_module == "Potentials")
			{
				$sql = "select * from  ".$table_prefix."_potential where potentialid=?";
				$result = $adb->pquery($sql, array($value));
				$parent_name = $adb->query_result($result,0,"potentialname");
				$potential_selected = "selected";

			}
			elseif($parent_module == "Products")
			{
				$sql = "select * from  ".$table_prefix."_products where productid=?";
				$result = $adb->pquery($sql, array($value));
				$parent_name= $adb->query_result($result,0,"productname");
				$product_selected = "selected";

			}
			elseif($parent_module == "PurchaseOrder")
			{
				$sql = "select * from  ".$table_prefix."_purchaseorder where purchaseorderid=?";
				$result = $adb->pquery($sql, array($value));
				$parent_name= $adb->query_result($result,0,"subject");
				$porder_selected = "selected";

			}
			elseif($parent_module == "SalesOrder")
			{
				$sql = "select * from  ".$table_prefix."_salesorder where salesorderid=?";
				$result = $adb->pquery($sql, array($value));
				$parent_name= $adb->query_result($result,0,"subject");
				$sorder_selected = "selected";

			}
			elseif($parent_module == "Invoice")
			{
				$sql = "select * from  ".$table_prefix."_invoice where invoiceid=?";
				$result = $adb->pquery($sql, array($value));
				$parent_name= $adb->query_result($result,0,"subject");
				$invoice_selected = "selected";
			}
			elseif($parent_module == "Quotes")
			{
				$sql = "select * from  ".$table_prefix."_quotes where quoteid=?";
				$result = $adb->pquery($sql, array($value));
				$parent_name= $adb->query_result($result,0,"subject");
				$quote_selected = "selected";
			}elseif($parent_module == "HelpDesk")
			{
				$sql = "select * from  ".$table_prefix."_troubletickets where ticketid=?";
				$result = $adb->pquery($sql, array($value));
				$parent_name= $adb->query_result($result,0,"title");
				$ticket_selected = "selected";
			}
		}

        //ds@33
		if(isPermitted('Leads','EditView',$_REQUEST['record']) == 'yes')
		{
		  $array1[] = $app_strings['COMBO_LEADS'];
		  $array2[] = $lead_selected;
		  $array3[] = "Leads&action=Popup";
		}
		if(isPermitted('Accounts','EditView',$_REQUEST['record']) == 'yes')
		{
		  $array1[] = $app_strings['COMBO_ACCOUNTS'];
		  $array2[] = $account_selected;
		  $array3[] = "Accounts&action=Popup";
		}
		if(isPermitted('Potentials','EditView',$_REQUEST['record']) == 'yes')
		{
		  $array1[] = $app_strings['COMBO_POTENTIALS'];
		  $array2[] = $potential_selected;
		  $array3[] = "Potentials&action=Popup";
		}
		if(isPermitted('Products','EditView',$_REQUEST['record']) == 'yes')
		{
		  $array1[] = $app_strings['COMBO_PRODUCTS'];
		  $array2[] = $product_selected;
		  $array3[] = "Products&action=Popup";
		}
		if(isPermitted('Invoice','EditView',$_REQUEST['record']) == 'yes')
		{
		  $array1[] = $app_strings['COMBO_INVOICES'];
		  $array2[] = $invoice_selected;
		  $array3[] = "Invoice&action=Popup";
		}
		if(isPermitted('PurchaseOrder','EditView',$_REQUEST['record']) == 'yes')
		{
		  $array1[] = $app_strings['COMBO_PORDER'];
		  $array2[] = $porder_selected;
		  $array3[] = "PurchaseOrder&action=Popup";
		}
		if(isPermitted('SalesOrder','EditView',$_REQUEST['record']) == 'yes')
		{
		  $array1[] = $app_strings['COMBO_SORDER'];
		  $array2[] = $sorder_selected;
		  $array3[] = "SalesOrder&action=Popup";
		}
		if(isPermitted('Quotes','EditView',$_REQUEST['record']) == 'yes')
		{
		  $array1[] = $app_strings['COMBO_QUOTES'];
		  $array2[] = $quote_selected;
		  $array3[] = "Quotes&action=Popup";
		}
		if(isPermitted('HelpDesk','EditView',$_REQUEST['record']) == 'yes')
		{
		  $array1[] = $app_strings['COMBO_HELPDESK'];
		  $array2[] = $ticket_selected;
		  $array3[] = "HelpDesk&action=Popup";
		}

		$editview_label[] = $array1;
		$editview_label[] = $array2;
		$editview_label[] = $array3;

        /*

		$editview_label[] = array($app_strings['COMBO_LEADS'],
                                          $app_strings['COMBO_ACCOUNTS'],
                                          $app_strings['COMBO_POTENTIALS'],
                                          $app_strings['COMBO_PRODUCTS'],
                                          $app_strings['COMBO_INVOICES'],
                                          $app_strings['COMBO_PORDER'],
                                          $app_strings['COMBO_SORDER'],
					  $app_strings['COMBO_QUOTES'],
					  $app_strings['COMBO_HELPDESK']
                                         );
                $editview_label[] = array($lead_selected,
                                          $account_selected,
					  $potential_selected,
                                          $product_selected,
                                          $invoice_selected,
                                          $porder_selected,
                                          $sorder_selected,
					  $quote_selected,
					  $ticket_selected
                                         );

        */
        //ds@33e
		$fieldvalue[] =$parent_name;
		$fieldvalue[] =$value;

	}
	//added by rdhital/Raju for better email support
	elseif($uitype == 357)
	{
		if($_REQUEST['pmodule'] == 'Contacts')
		{
			$contact_selected = 'selected';
		}
		elseif($_REQUEST['pmodule'] == 'Accounts')
		{
			$account_selected = 'selected';
		}
		elseif($_REQUEST['pmodule'] == 'Leads')
		{
			$lead_selected = 'selected';
		}
		if(isset($_REQUEST['emailids']) && $_REQUEST['emailids'] != '')
		{
			$parent_id = $_REQUEST['emailids'];
			$parent_name='';
			$pmodule=$_REQUEST['pmodule'];
			$myids=explode("|",$parent_id);
			for ($i=0;$i<(count($myids)-1);$i++)
			{
				$realid=explode("@",$myids[$i]);
				$entityid=$realid[0];
				$nemail=count($realid);

				if ($pmodule=='Accounts'){
					$myfocus = CRMEntity::getInstance('Accounts');
					$myfocus->retrieve_entity_info($entityid,"Accounts");
					$fullname=br2nl($myfocus->column_fields['accountname']);
					$account_selected = 'selected';
				}
				elseif ($pmodule=='Contacts'){
					$myfocus = CRMEntity::getInstance('Contacts');
					$myfocus->retrieve_entity_info($entityid,"Contacts");
					$fname=br2nl($myfocus->column_fields['firstname']);
					$lname=br2nl($myfocus->column_fields['lastname']);
					$fullname=$lname.' '.$fname;
					$contact_selected = 'selected';
				}
				elseif ($pmodule=='Leads'){
					$myfocus = CRMEntity::getInstance('Leads');
					$myfocus->retrieve_entity_info($entityid,"Leads");
					$fname=br2nl($myfocus->column_fields['firstname']);
					$lname=br2nl($myfocus->column_fields['lastname']);
					$fullname=$lname.' '.$fname;
					$lead_selected = 'selected';
				}
				for ($j=1;$j<$nemail;$j++){
					$querystr='select columnname from '.$table_prefix.'_field where fieldid=?';
					$result=$adb->pquery($querystr, array($realid[$j]));
					$temp=$adb->query_result($result,0,'columnname');
					$temp1=br2nl($myfocus->column_fields[$temp]);

					//Modified to display the entities in red which don't have email id
					if(!empty($temp_parent_name) && strlen($temp_parent_name) > 150)
					{
						$parent_name .= '<br>';
						$temp_parent_name = '';
					}

					if($temp1 != '')
					{
						$parent_name .= $fullname.'&lt;'.$temp1.'&gt;; ';
						$temp_parent_name .= $fullname.'&lt;'.$temp1.'&gt;; ';
					}
					else
					{
						$parent_name .= "<b style='color:red'>".$fullname.'&lt;'.$temp1.'&gt;; '."</b>";
						$temp_parent_name .= "<b style='color:red'>".$fullname.'&lt;'.$temp1.'&gt;; '."</b>";
					}

				}
			}
		}
		else
		{
			if($_REQUEST['record'] != '' && $_REQUEST['record'] != NULL)
			{
				$parent_name='';
				$parent_id='';
				$myemailid= $_REQUEST['record'];
				$mysql = "select crmid from ".$table_prefix."_seactivityrel where activityid=?";
				$myresult = $adb->pquery($mysql, array($myemailid));
				$mycount=$adb->num_rows($myresult);
				if($mycount >0)
				{
					for ($i=0;$i<$mycount;$i++)
					{
						$mycrmid=$adb->query_result($myresult,$i,'crmid');
						$parent_module = getSalesEntityType($mycrmid);
						if($parent_module == "Leads")
						{
							$sql = "select firstname,lastname,email from ".$table_prefix."_leaddetails where leadid=?";
							$result = $adb->pquery($sql, array($mycrmid));
							$full_name = getFullNameFromQResult($result,0,"Leads");
							$myemail=$adb->query_result($result,0,"email");
							$parent_id .=$mycrmid.'@0|' ; //make it such that the email adress sent is remebered and only that one is retrived
							$parent_name .= $full_name.'<'.$myemail.'>; ';
							$lead_selected = 'selected';
						}
						elseif($parent_module == "Contacts")
						{
							$sql = "select * from  ".$table_prefix."_contactdetails where contactid=?";
							$result = $adb->pquery($sql, array($mycrmid));
							$full_name = getFullNameFromQResult($result,0,"Contacts");
							$myemail=$adb->query_result($result,0,"email");
							$parent_id .=$mycrmid.'@0|'  ;//make it such that the email adress sent is remebered and only that one is retrived
							$parent_name .= $full_name.'<'.$myemail.'>; ';
							$contact_selected = 'selected';
						}
						elseif($parent_module == "Accounts")
						{
							$sql = "select * from  ".$table_prefix."_account where accountid=?";
							$result = $adb->pquery($sql, array($mycrmid));
							$account_name = $adb->query_result($result,0,"accountname");
							$myemail=$adb->query_result($result,0,"email1");
							$parent_id .=$mycrmid.'@0|'  ;//make it such that the email adress sent is remebered and only that one is retrived
							$parent_name .= $account_name.'<'.$myemail.'>; ';
							$account_selected = 'selected';
						}elseif($parent_module == "Users")
						{
							$sql = "select user_name,email1 from ".$table_prefix."_users where id=?";
							$result = $adb->pquery($sql, array($mycrmid));
							$account_name = $adb->query_result($result,0,"user_name");
							$myemail=$adb->query_result($result,0,"email1");
							$parent_id .=$mycrmid.'@0|'  ;//make it such that the email adress sent is remebered and only that one is retrived
							$parent_name .= $account_name.'<'.$myemail.'>; ';
							$user_selected = 'selected';
						}
						elseif($parent_module == "Vendors")
						{
							$sql = "select * from  ".$table_prefix."_vendor where vendorid=?";
							$result = $adb->pquery($sql, array($mycrmid));
							$vendor_name = $adb->query_result($result,0,"vendorname");
							$myemail=$adb->query_result($result,0,"email");
							$parent_id .=$mycrmid.'@0|'  ;//make it such that the email adress sent is remebered and only that one is retrived
							$parent_name .= $vendor_name.'<'.$myemail.'>; ';
							$vendor_selected = 'selected';
						}
					}
				}
			}
			$custfld .= '<td width="20%" class="dataLabel">'.$app_strings['To'].'&nbsp;</td>';
			$custfld .= '<td width="90%" colspan="3"><input name="parent_id" type="hidden" value="'.$parent_id.'"><textarea readonly name="parent_name" cols="70" rows="2">'.$parent_name.'</textarea>&nbsp;<select name="parent_type" >';
			$custfld .= '<OPTION value="Contacts" selected>'.$app_strings['COMBO_CONTACTS'].'</OPTION>';
			//crmv@7217
			if ($module_name != "Sms") {
				$custfld .= '<OPTION value="Accounts" >'.$app_strings['COMBO_ACCOUNTS'].'</OPTION>';
				$custfld .= '<OPTION value="Vendors" >'.$app_strings['COMBO_VENDORS'].'</OPTION>';
			}
			//crmv@7217e
			$custfld .= '<OPTION value="Leads" >'.$app_strings['COMBO_LEADS'].'</OPTION></select><img src="'.$image_path.'select.gif" alt="Select" title="Select" LANGUAGE=javascript onclick=\'$log->debug("Exiting getOutputHtml method ..."); openPopup("index.php?module="+ document.EditView.parent_type.value +"&action=Popup&popuptype=set_$log->debug("Exiting getOutputHtml method ..."); return_emails&form=EmailEditView&form_submit=false","test","width=600,height=400,resizable=1,scrollbars=1,top=150,left=200");\' align="absmiddle" style=\'cursor:hand;cursor:pointer\'>&nbsp;<input type="image" src="'.$image_path.'clear_field.gif" alt="Clear" title="Clear" LANGUAGE=javascript onClick="this.form.parent_id.value=\'\';this.form.parent_name.value=\'\';$log->debug("Exiting getOutputHtml method ..."); return false;" align="absmiddle" style=\'cursor:hand;cursor:pointer\'></td>';//crmv@21048m
			//crmv@7217
			if ($module_name != "Sms"){
				$editview_label[] = array(
					'Contacts'=>$contact_selected,
					'Accounts'=>$account_selected,
					'Vendors'=>$vendor_selected,
					'Leads'=>$lead_selected,
					'Users'=>$user_selected
					);
			}
			else{
				$editview_label[] = array(
					'Contacts'=>$contact_selected,
					'Leads'=>$lead_selected,
					'Users'=>$user_selected
					);
			}
			//crmv@7217e
			$fieldvalue[] =$parent_name;
			$fieldvalue[] = $parent_id;
		}
	}
	//end of rdhital/Raju
	elseif($uitype == 71 || $uitype == 72)
	{
		// crmv@92112
		if($col_fields['record_id'] != '' && $fieldname == 'unit_price') {
			$rate_symbol=getCurrencySymbolandCRate($InventoryUtils->getProductBaseCurrency($col_fields['record_id'],$module_name)); // crmv@42024				
		} else {
			$currency_id = fetchCurrency($current_user->id);
			$rate_symbol=getCurrencySymbolandCRate($currency_id);
			$rate = $rate_symbol['rate'];
			if ($value !== '' && $value !== null) {
				$value = convertFromMasterCurrency($value,$rate); //crmv@92519
			}
		}
        if ($value !== '' && $value !== null) {
			$fieldvalue[] = formatUserNumber(floatval($value)); //crmv@83877 crmv@92824
		} else {
			$fieldvalue[] = '';
		}
		// crmv@92112
        $currency = $rate_symbol['symbol'];
		$editview_label[]=getTranslatedString($fieldlabel, $module_name).': ('.$currency.')';
	// crmv@83877
	} elseif($uitype == 7 || $uitype == 9) { // crmv@92112
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = (!empty($value) ? formatUserNumber(floatval($value), true) : ''); //crmv@95900
	// crmv@83877e
	} elseif($uitype == 79)
	{
		if($value != '')
		{
			$purchaseorder_name = getPoName($value);
		}
		elseif(isset($_REQUEST['purchaseorder_id']) && $_REQUEST['purchaseorder_id'] != '')
		{
			$value = $_REQUEST['purchaseorder_id'];
			$purchaseorder_name = getPoName($value);
		}
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $purchaseorder_name;
		$fieldvalue[] = $value;
	}
	elseif($uitype == 30)
	{
		$rem_days = 0;
		$rem_hrs = 0;
		$rem_min = 0;
		if($value!='')
			$SET_REM = "CHECKED";
		if(!empty($col_fields[$fieldname])){ //crmv@167234
			$rem_days = floor($col_fields[$fieldname]/(24*60));
			$rem_hrs = floor(($col_fields[$fieldname]-$rem_days*24*60)/60);
			$rem_min = ($col_fields[$fieldname]-$rem_days*24*60)%60;
		}// crmv@167234
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$day_options = getReminderSelectOption(0,31,'remdays',$rem_days);
		$hr_options = getReminderSelectOption(0,23,'remhrs',$rem_hrs);
		$min_options = getReminderSelectOption(5,59,'remmin',$rem_min); // crmv@114646
		$fieldvalue[] = array(array(0,32,'remdays',getTranslatedString('LBL_DAYS'),$rem_days),array(0,24,'remhrs',getTranslatedString('LBL_HOURS'),$rem_hrs),array(5,60,'remmin',getTranslatedString('LBL_MINUTES'),$rem_min)); // crmv@98866 crmv@114646
		$fieldvalue[] = array($SET_REM,getTranslatedString('LBL_YES'),getTranslatedString('LBL_NO'));
		$SET_REM = '';
	}
	elseif($uitype == 115)
	{
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$pick_query="select * from ".$table_prefix."_$fieldname";
		$pickListResult = $adb->pquery($pick_query, array());
		$noofpickrows = $adb->num_rows($pickListResult);

		//Mikecrowe fix to correctly default for custom pick lists
		$options = array();
		$found = false;
		for($j = 0; $j < $noofpickrows; $j++)
		{
			$pickListValue=$adb->query_result($pickListResult,$j,strtolower($fieldname));

			if($value == $pickListValue)
			{
				$chk_val = "selected";
				$found = true;
			}
			else
			{
				$chk_val = '';
			}
			$options[] = array(getTranslatedString($pickListValue),$pickListValue,$chk_val );
		}
		$fieldvalue [] = $options;
		$fieldvalue [] = $is_admin;
	}
	elseif($uitype == 116 || $uitype == 117)
	{
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$pick_query="select * from ".$table_prefix."_currency_info where currency_status = 'Active' and deleted=0";
		$pickListResult = $adb->pquery($pick_query, array());
		$noofpickrows = $adb->num_rows($pickListResult);

		//Mikecrowe fix to correctly default for custom pick lists
		$options = array();
		$found = false;
		for($j = 0; $j < $noofpickrows; $j++)
		{
			$pickListValue=$adb->query_result($pickListResult,$j,'currency_name');
			$currency_id=$adb->query_result($pickListResult,$j,'id');
			if($value == $currency_id)
			{
				$chk_val = "selected";
				$found = true;
			}
			else
			{
				$chk_val = '';
			}
			$options[$currency_id] = array($pickListValue=>$chk_val );
		}
		$fieldvalue [] = $options;
		$fieldvalue [] = $is_admin;
	}
	elseif($uitype ==98)
	{
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[]=$value;
        	$fieldvalue[]=getRoleName($value);
		$fieldvalue[]=$is_admin;
	}
	elseif($uitype == 105)
	{
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		 if( isset( $col_fields['record_id']) && $col_fields['record_id'] != '') {
			$query = "select ".$table_prefix."_attachments.path, ".$table_prefix."_attachments.name from ".$table_prefix."_contactdetails left join ".$table_prefix."_seattachmentsrel on ".$table_prefix."_seattachmentsrel.crmid=".$table_prefix."_contactdetails.contactid inner join ".$table_prefix."_attachments on ".$table_prefix."_attachments.attachmentsid=".$table_prefix."_seattachmentsrel.attachmentsid where ".$table_prefix."_contactdetails.imagename=".$table_prefix."_attachments.name and contactid=?";
			$result_image = $adb->pquery($query, array($col_fields['record_id']));
			for($image_iter=0;$image_iter < $adb->num_rows($result_image);$image_iter++)
			{
				$image_array[] = $adb->query_result($result_image,$image_iter,'name');
				$image_path_array[] = $adb->query_result($result_image,$image_iter,'path');
			}
		}
		if(is_array($image_array))
			for($img_itr=0;$img_itr<count($image_array);$img_itr++)
			{
				$fieldvalue[] = array('name'=>$image_array[$img_itr],'path'=>$image_path_array[$img_itr]);
			}
		else
			$fieldvalue[] = '';
	}
	//vtc
	elseif($uitype == 26){
		$editview_label[]=getTranslatedString($fieldlabel);
		$folderid=$col_fields['folderid'];
		// crmv@30967
		$foldername_query = 'select foldername from '.$table_prefix.'_crmentityfolder where tabid = ? and folderid = ?';
		$res = $adb->pquery($foldername_query,array(getTabId($module_name), $folderid));
		// crmv@30967e
		$foldername = $adb->query_result($res,0,'foldername');
		if($foldername != '' && $folderid != ''){
			$fldr_name[$folderid]=$foldername;
		}
		// crmv@30967
		$sql="select foldername,folderid from ".$table_prefix."_crmentityfolder where tabid = ? order by foldername";
		$res=$adb->pquery($sql,array(getTabId($module_name)));
		// crmv@30967e
		for($i=0;$i<$adb->num_rows($res);$i++)
		{
			$fid=$adb->query_result($res,$i,"folderid");
			$fldr_name[$fid]=$adb->query_result($res,$i,"foldername");
		}
		$fieldvalue[] = $fldr_name;
		}
	elseif($uitype == 27){
		// crmv@95157
		if ($value == 'E'){
			$external_selected = "selected";
		} elseif ($value == 'I') {
			$internal_selected = "selected";
			$legacyInternal = true;
		} elseif ($value == 'B') {
			$internal_selected = "selected";
		}
		$editview_label[] = array(getTranslatedString('LBL_INTERNAL','Documents'),getTranslatedString('LBL_EXTERNAL','Documents'));
		$editview_label[] = array($internal_selected,$external_selected);
		$editview_label[] = array($legacyInternal ? "I" : "B", "E");
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $value;
		$fieldvalue[] = $col_fields['filename'];
		// crmv@95157e
	}
	//crmv@16265	crmv@43764
	elseif($uitype == 199){
		(!empty($col_fields[$fieldname])) ? $fakeValue = '********' : $fakeValue = '';
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $fakeValue;
		//$fieldvalue[] = $col_fields[$fieldname];		// real value
	}
	//crmv@16265e	crmv@43764e
	//crmv@18338
	elseif($uitype == 1020){
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $value;
		$temp_val = $value;
		//crmv@36509
		switch ($sla_config_global['time_measure']){ // crmv@172864
			case 'minutes':
				$temp_val = $temp_val*60;
				break;
			case 'hours':
				$temp_val = $temp_val*3600;
				break;
			case 'days':
				$temp_val = $temp_val*86400;
				break;
		}
		//crmv@36509 e
		$value=time_duration(abs($temp_val));
		if (strpos($fieldname,"remaining")!==false || strpos($fieldname,"_out_")!==false){
			if (strpos($fieldname,"remaining")!==false){
				if ($temp_val<=0)
					$color = "red";
				else
					$color = "green";
			}
			if (strpos($fieldname,"_out_")!==false){
				if ($temp_val>0)
					$color = "red";
				else
					$color = "green";
			}
			$value = "<font color=$color>$value</font>";
		}
		$fieldvalue[] = $value;
	}
	elseif($uitype == 1021){
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $value;
		$fieldvalue[] = getDisplayDate($value);
	}
	//crmv@18338 end
	//crmv@146461
	elseif($uitype == 70)
	{
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		if ($value == '') {
			$fieldvalue[] = '';
		} else {
			$fieldvalue[] = getDisplayDate($value);
		}
		$dformat = getTranslatedString($current_user->date_format,'Users');
		$hformat = 'HH:mm:ss';
		$fieldvalue[] = array(
			'date_format_string'=>$dformat." ".$hformat,
			'date_format'=>strtoupper($dformat)." ".$hformat,
		);
	}
	//crmv@146461e
	else
	{
		//Added condition to set the subject if click Reply All from web mail
		if($_REQUEST['module'] == 'Emails' && $_REQUEST['mg_subject'] != '')
		{
			$value = $_REQUEST['mg_subject'];
		}
		$editview_label[]=getTranslatedString($fieldlabel, $module_name);
		if($uitype == 1 && ($fieldname=='expectedrevenue' || $fieldname=='budgetcost' || $fieldname=='actualcost' || $fieldname=='expectedroi' || $fieldname=='actualroi' ) && ($module_name=='Campaigns'))
		{
			$rate_symbol = getCurrencySymbolandCRate($user_info['currency_id']);
			$fieldvalue[] = convertFromDollar($value,$rate_symbol['rate']);
		}
		elseif($fieldname == 'fileversion'){
			if(empty($value)){
				$value = '';
			}
			else{
				$fieldvalue[] = $value;
			}
		}
		else
			$fieldvalue[] = $value;
	}

	// Mike Crowe Mod --------------------------------------------------------force numerics right justified.
	if ( !preg_match("/id=/i",$custfld) )
		$custfld = preg_replace("/<input/iS","<input id='$fieldname' ",$custfld);

	if ( in_array($uitype,array(71,72,7,9,90)) )
	{
		$custfld = preg_replace("/<input/iS","<input align=right ",$custfld);
	}
	$final_arr[]=$ui_type;
	$final_arr[]=$editview_label;
	$final_arr[]=$editview_fldname;
	$final_arr[]=$fieldvalue;
	$final_arr[]=$readonly;
	$type_of_data  = explode('~',$typeofdata);
	$final_arr[]=$type_of_data[1];
 	if(is_admin($current_user))
	   $final_arr[] = 1;
	else
	   $final_arr[] = 0;
	$log->debug("Exiting getOutputHtml method ...");
	return $final_arr;
}

/** This function returns the vte_invoice object populated with the details from sales order object.
* Param $focus - Invoice object
* Param $so_focus - Sales order focus
* Param $soid - sales order id
* Return type is an object array
*/

function getConvertSoToInvoice($focus,$so_focus,$soid)
{
	global $log,$current_user;
	$log->debug("Entering getConvertSoToInvoice(focus,focus,".$soid.") method ...");
    $log->info("in getConvertSoToInvoice ".$soid);
    $xyz=array('bill_street','bill_city','bill_code','bill_pobox','bill_country','bill_state','ship_street','ship_city','ship_code','ship_pobox','ship_country','ship_state');
	for($i=0;$i<count($xyz);$i++){
		if (getFieldVisibilityPermission('SalesOrder', $current_user->id,$xyz[$i]) == '0'){
			$so_focus->column_fields[$xyz[$i]] = $so_focus->column_fields[$xyz[$i]];
		}
		else
			$so_focus->column_fields[$xyz[$i]] = '';
	}
	$focus->column_fields['salesorder_id'] = $soid;
	$focus->column_fields['subject'] = $so_focus->column_fields['subject'];
	$focus->column_fields['customerno'] = $so_focus->column_fields['customerno'];
	$focus->column_fields['duedate'] = $so_focus->column_fields['duedate'];
	$focus->column_fields['contact_id'] = $so_focus->column_fields['contact_id'];//to include contact name in Invoice
	$focus->column_fields['account_id'] = $so_focus->column_fields['account_id'];
	$focus->column_fields['exciseduty'] = $so_focus->column_fields['exciseduty'];
	$focus->column_fields['salescommission'] = $so_focus->column_fields['salescommission'];
	$focus->column_fields['purchaseorder'] = $so_focus->column_fields['purchaseorder'];
	$focus->column_fields['bill_street'] = $so_focus->column_fields['bill_street'];
	$focus->column_fields['ship_street'] = $so_focus->column_fields['ship_street'];
	$focus->column_fields['bill_city'] = $so_focus->column_fields['bill_city'];
	$focus->column_fields['ship_city'] = $so_focus->column_fields['ship_city'];
	$focus->column_fields['bill_state'] = $so_focus->column_fields['bill_state'];
	$focus->column_fields['ship_state'] = $so_focus->column_fields['ship_state'];
	$focus->column_fields['bill_code'] = $so_focus->column_fields['bill_code'];
	$focus->column_fields['ship_code'] = $so_focus->column_fields['ship_code'];
	$focus->column_fields['bill_country'] = $so_focus->column_fields['bill_country'];
	$focus->column_fields['ship_country'] = $so_focus->column_fields['ship_country'];
	$focus->column_fields['bill_pobox'] = $so_focus->column_fields['bill_pobox'];
	$focus->column_fields['ship_pobox'] = $so_focus->column_fields['ship_pobox'];
	$focus->column_fields['description'] = $so_focus->column_fields['description'];
	$focus->column_fields['terms_conditions'] = $so_focus->column_fields['terms_conditions'];
    $focus->column_fields['currency_id'] = $so_focus->column_fields['currency_id'];
    $focus->column_fields['conversion_rate'] = $so_focus->column_fields['conversion_rate'];

	$log->debug("Exiting getConvertSoToInvoice method ...");
	return $focus;

}

/** This function returns the vte_invoice object populated with the details from quote object.
* Param $focus - Invoice object
* Param $quote_focus - Quote order focus
* Param $quoteid - quote id
* Return type is an object array
*/


function getConvertQuoteToInvoice($focus,$quote_focus,$quoteid)
{
	global $log,$current_user;
	$log->debug("Entering getConvertQuoteToInvoice(focus,focus,".$quoteid.") method ...");
        $log->info("in getConvertQuoteToInvoice ".$quoteid);
    $xyz=array('bill_street','bill_city','bill_code','bill_pobox','bill_country','bill_state','ship_street','ship_city','ship_code','ship_pobox','ship_country','ship_state');
	for($i=0;$i<12;$i++){
		if (getFieldVisibilityPermission('Quotes', $current_user->id,$xyz[$i]) == '0'){
			$quote_focus->column_fields[$xyz[$i]] = $quote_focus->column_fields[$xyz[$i]];
		}
		else
			$quote_focus->column_fields[$xyz[$i]] = '';
	}
	$focus->column_fields['subject'] = $quote_focus->column_fields['subject'];
	$focus->column_fields['account_id'] = $quote_focus->column_fields['account_id'];
	$focus->column_fields['bill_street'] = $quote_focus->column_fields['bill_street'];
	$focus->column_fields['ship_street'] = $quote_focus->column_fields['ship_street'];
	$focus->column_fields['bill_city'] = $quote_focus->column_fields['bill_city'];
	$focus->column_fields['ship_city'] = $quote_focus->column_fields['ship_city'];
	$focus->column_fields['bill_state'] = $quote_focus->column_fields['bill_state'];
	$focus->column_fields['ship_state'] = $quote_focus->column_fields['ship_state'];
	$focus->column_fields['bill_code'] = $quote_focus->column_fields['bill_code'];
	$focus->column_fields['ship_code'] = $quote_focus->column_fields['ship_code'];
	$focus->column_fields['bill_country'] = $quote_focus->column_fields['bill_country'];
	$focus->column_fields['ship_country'] = $quote_focus->column_fields['ship_country'];
	$focus->column_fields['bill_pobox'] = $quote_focus->column_fields['bill_pobox'];
	$focus->column_fields['ship_pobox'] = $quote_focus->column_fields['ship_pobox'];
	$focus->column_fields['description'] = $quote_focus->column_fields['description'];
	$focus->column_fields['terms_conditions'] = $quote_focus->column_fields['terms_conditions'];
    $focus->column_fields['currency_id'] = $quote_focus->column_fields['currency_id'];
    $focus->column_fields['conversion_rate'] = $quote_focus->column_fields['conversion_rate'];
    if (isset($quote_focus->column_fields['bu_mc'])) $focus->column_fields['bu_mc'] = $quote_focus->column_fields['bu_mc'];	//crmv@78395

	$log->debug("Exiting getConvertQuoteToInvoice method ...");
	return $focus;
}

/** This function returns the sales order object populated with the details from quote object.
* Param $focus - Sales order object
* Param $quote_focus - Quote order focus
* Param $quoteid - quote id
* Return type is an object array
*/

function getConvertQuoteToSoObject($focus,$quote_focus,$quoteid)
{
	global $log,$current_user;
	$log->debug("Entering getConvertQuoteToSoObject(focus,focus,".$quoteid.") method ...");
	$log->info("in getConvertQuoteToSoObject ".$quoteid);
	$xyz=array('bill_street','bill_city','bill_code','bill_pobox','bill_country','bill_state','ship_street','ship_city','ship_code','ship_pobox','ship_country','ship_state');
	for($i=0;$i<12;$i++){
		if (getFieldVisibilityPermission('Quotes', $current_user->id,$xyz[$i]) == '0'){
			$quote_focus->column_fields[$xyz[$i]] = $quote_focus->column_fields[$xyz[$i]];
		}
		else
		$quote_focus->column_fields[$xyz[$i]] = '';
	}
	$focus->column_fields['quote_id'] = $quoteid;
	$focus->column_fields['subject'] = $quote_focus->column_fields['subject'];
	$focus->column_fields['contact_id'] = $quote_focus->column_fields['contact_id'];
	$focus->column_fields['potential_id'] = $quote_focus->column_fields['potential_id'];
	$focus->column_fields['account_id'] = $quote_focus->column_fields['account_id'];
	$focus->column_fields['carrier'] = $quote_focus->column_fields['carrier'];
	$focus->column_fields['bill_street'] = $quote_focus->column_fields['bill_street'];
	$focus->column_fields['ship_street'] = $quote_focus->column_fields['ship_street'];
	$focus->column_fields['bill_city'] = $quote_focus->column_fields['bill_city'];
	$focus->column_fields['ship_city'] = $quote_focus->column_fields['ship_city'];
	$focus->column_fields['bill_state'] = $quote_focus->column_fields['bill_state'];
	$focus->column_fields['ship_state'] = $quote_focus->column_fields['ship_state'];
	$focus->column_fields['bill_code'] = $quote_focus->column_fields['bill_code'];
	$focus->column_fields['ship_code'] = $quote_focus->column_fields['ship_code'];
	$focus->column_fields['bill_country'] = $quote_focus->column_fields['bill_country'];
	$focus->column_fields['ship_country'] = $quote_focus->column_fields['ship_country'];
	$focus->column_fields['bill_pobox'] = $quote_focus->column_fields['bill_pobox'];
	$focus->column_fields['ship_pobox'] = $quote_focus->column_fields['ship_pobox'];
	$focus->column_fields['description'] = $quote_focus->column_fields['description'];
	$focus->column_fields['terms_conditions'] = $quote_focus->column_fields['terms_conditions'];
	$focus->column_fields['currency_id'] = $quote_focus->column_fields['currency_id'];
	$focus->column_fields['conversion_rate'] = $quote_focus->column_fields['conversion_rate'];
	if (isset($quote_focus->column_fields['bu_mc'])) $focus->column_fields['bu_mc'] = $quote_focus->column_fields['bu_mc'];	//crmv@78395

	$log->debug("Exiting getConvertQuoteToSoObject method ...");
	return $focus;
}

/** This function returns the no of vte_products associated to the given entity or a record.
* Param $module - module name
* Param $focus - module object
* Param $seid - sales entity id
* Return type is an object array
*/

function getNoOfAssocProducts($module,$focus,$seid='')
{
	global $log,$table_prefix;
	$log->debug("Entering getNoOfAssocProducts(".$module.",focus,".$seid."='') method ...");
	global $adb;
	$output = '';
	if($module == 'Quotes')
	{
		$query="select ".$table_prefix."_products.productname, ".$table_prefix."_products.unit_price, ".$table_prefix."_inventoryproductrel.* from ".$table_prefix."_inventoryproductrel inner join ".$table_prefix."_products on ".$table_prefix."_products.productid=".$table_prefix."_inventoryproductrel.productid where id=?";
		$params = array($focus->id);
	}
	elseif($module == 'PurchaseOrder')
	{
		$query="select ".$table_prefix."_products.productname, ".$table_prefix."_products.unit_price, ".$table_prefix."_inventoryproductrel.* from ".$table_prefix."_inventoryproductrel inner join ".$table_prefix."_products on ".$table_prefix."_products.productid=".$table_prefix."_inventoryproductrel.productid where id=?";
		$params = array($focus->id);
	}
	elseif($module == 'SalesOrder')
	{
		$query="select ".$table_prefix."_products.productname, ".$table_prefix."_products.unit_price, ".$table_prefix."_inventoryproductrel.* from ".$table_prefix."_inventoryproductrel inner join ".$table_prefix."_products on ".$table_prefix."_products.productid=".$table_prefix."_inventoryproductrel.productid where id=?";
		$params = array($focus->id);
	}
	elseif($module == 'Invoice')
	{
		$query="select ".$table_prefix."_products.productname, ".$table_prefix."_products.unit_price, ".$table_prefix."_inventoryproductrel.* from ".$table_prefix."_inventoryproductrel inner join ".$table_prefix."_products on ".$table_prefix."_products.productid=".$table_prefix."_inventoryproductrel.productid where id=?";
		$params = array($focus->id);
	}
	elseif($module == 'Potentials')
	{
		$query="select ".$table_prefix."_products.productname,".$table_prefix."_products.unit_price,".$table_prefix."_seproductsrel.* from ".$table_prefix."_products inner join ".$table_prefix."_seproductsrel on ".$table_prefix."_seproductsrel.productid=".$table_prefix."_products.productid where crmid=?";
		$params = array($seid);
	}
	//crmv@7214
    elseif($module == 'HelpDesk')
    {
        $query="SELECT ".$table_prefix."_products.productname, ".$table_prefix."_products.product_description, ".$table_prefix."_products.unit_price," .
                " ".$table_prefix."_products.qtyinstock, ".$table_prefix."_inventoryproductrel.* " .
                " FROM ".$table_prefix."_inventoryproductrel " .
                " INNER JOIN ".$table_prefix."_products ON ".$table_prefix."_products.productid=".$table_prefix."_inventoryproductrel.productid " .
                " WHERE id=".$focus->id." ORDER BY sequence_no";
    }
    //crmv@7214e
	elseif($module == 'Products')
	{
		$query="select ".$table_prefix."_products.productname,".$table_prefix."_products.unit_price, ".$table_prefix."_crmentity.* from ".$table_prefix."_products inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_products.productid where ".$table_prefix."_crmentity.deleted=0 and productid=?";
		$params = array($seid);
	}

	$result = $adb->pquery($query, $params);
	$num_rows=$adb->num_rows($result);
	$log->debug("Exiting getNoOfAssocProducts method ...");
	return $num_rows;
}

/** This function returns the detail block information of a record for given block id.
* Param $module - module name
* Param $block - block name
* Param $mode - view type (detail/edit/create)
* Param $col_fields - vte_fields array
* Param $tabid - vte_tab id
* Param $info_type - information type (basic/advance) default ""
* Return type is an object array
*/

function getBlockInformation($module, $result, $col_fields,$tabid,$blockdata,$mode,&$aBlockStatus='',&$blockVisibility='')	//crmv@96450 crmv@99316 crmv@104568
{
	global $log;
	$log->debug("Entering getBlockInformation(".$module.",". $result.",". $col_fields.",".$tabid.",".$blockdata.") method ..."); // crmv@104568
	global $adb;
	$editview_arr = Array();

	global $current_user,$mod_strings,$processMakerView; //crmv@161211

	$noofrows = $adb->num_rows($result);
	if (($module == 'Accounts' || $module == 'Contacts' || $module == 'Quotes' || $module == 'PurchaseOrder' || $module == 'SalesOrder'|| $module == 'Invoice') && $block == 2)
	{
		 global $log;
                $log->info("module is ".$module);

			$mvAdd_flag = true;
			$moveAddress = "<td rowspan='6' valign='middle' align='center'><input title='Copy billing address to shipping address'  class='button' onclick='return copyAddressRight(EditView)'  type='button' name='copyright' value='&raquo;' style='padding:0px 2px 0px 2px;font-size:12px'><br><br>
				<input title='Copy shipping address to billing address'  class='button' onclick='return copyAddressLeft(EditView)'  type='button' name='copyleft' value='&laquo;' style='padding:0px 2px 0px 2px;font-size:12px'></td>";
	}

	//crmv@9434+31357
	if (vtlib_isModuleActive('Transitions')){
		$transitions_obj = CRMEntity::getInstance('Transitions');
		$transitions_obj->Initialize($module,$current_user->roleid);
	}
	//crmv@9434+31357 end
	//crmv@9433
	if (vtlib_isModuleActive('Conditionals')){
		//crmv@36505
	    $conditionals_obj = CRMEntity::getInstance('Conditionals');
	    $conditionals_obj->Initialize($module,$tabid,$col_fields);
	    //crmv@36505 e
	}
	//crmv@9433 end
	for($i=0; $i<$noofrows; $i++)
	{
		$fieldtablename = $adb->query_result($result,$i,"tablename");
		$fieldcolname = $adb->query_result($result,$i,"columnname");
		$uitype = $adb->query_result($result,$i,"uitype");
		$fieldname = $adb->query_result($result,$i,"fieldname");
		$fieldlabel = $adb->query_result($result,$i,"fieldlabel");
		$block = $adb->query_result($result,$i,"block");
		$maxlength = $adb->query_result($result,$i,"maximumlength");
		$generatedtype = $adb->query_result($result,$i,"generatedtype");
		$readonly = $adb->query_result($result,$i,"readonly");
		$fieldid = $adb->query_result($result,$i,"fieldid"); // crmv@37679
		$typeofdata = getFinalTypeOfData($adb->query_result($result,$i,"typeofdata"), $adb->query_result($result,$i,"mandatory"));	//crmv@49510
		//crmv@9434
		if (vtlib_isModuleActive('Transitions'))
			$transitions_obj->handle_managed_fields($fieldname,$fieldcolname,$readonly,$col_fields,$mode,'EditView');
		//crmv@9434 end
		//crmv@9433
		if (vtlib_isModuleActive('Conditionals')){
			$fieldid = $adb->query_result($result,$i,"fieldid");
			if (is_array($conditionals_obj->permissions[$fieldid])){
				if ($conditionals_obj->permissions[$fieldid]["f2fp_visible"] == 0)
					$readonly = 100;
				elseif ($conditionals_obj->permissions[$fieldid]["f2fp_editable"] == 0)
					$readonly = 99;
				if ($conditionals_obj->permissions[$fieldid]["f2fp_mandatory"] == 1) $typeofdata = getFinalTypeOfData($typeofdata, '0');	//crmv@114144
			}
		}
		//crmv@9433 e
		//crmv@sdk-18508
		$sdk_files = SDK::getViews($module,$mode);
		if (!empty($sdk_files)) {
			foreach($sdk_files as $sdk_file) {
				$success = false;
				$readonly_old = $readonly;
				include($sdk_file['src']);
				SDK::checkReadonly($readonly_old,$readonly,$sdk_file['mode']);
				if ($success && $sdk_file['on_success'] == 'stop') {
					break;
				}
			}
		}
		//crmv@sdk-18508 e
		//crmv@103373 crmv@106857 crmv@161211
		// NB. same code in InsertTableRow.php and in include/CustomFieldUtil.php
		if ($processMakerView) {
			$readonly = 1;
			// TODO creare un metodo o degli array per gestire questi casi
			if (in_array($uitype,array(69,220))) $readonly = 100;	// hide table fields
			if ($uitype == 300) $uitype = 15;	//crmv@111091
			if (in_array($uitype,array(7,9,71,72))) $uitype = 1; //crmv@96450
			if ($_REQUEST['tablerow_mode'] == '1' && in_array($fieldname,array('parent_id','seq','assigned_user_id'))) $readonly = 100;	//crmv@115268 hide some fields from table fields
		}
		//crmv@103373e crmv@106857e crmv@161211e
		$custfld = getOutputHtml($uitype, $fieldname, $fieldlabel, $maxlength, $col_fields,$generatedtype,$module,$mode,$readonly,$typeofdata);
		$custfld[] = $fieldid; // crmv@37679
		$editview_arr[$block][]=$custfld;
	}
	//crmv@96450
	if ($module == 'Processes' && !empty($col_fields['record_id'])) { //crmv@170707
		require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
		$processDynaFormObj = ProcessDynaForm::getInstance();
		$processDynaFormObj->addBlockInformation($col_fields,'edit',$editview_arr,$blockdata,$aBlockStatus); // crmv@104568
	}
	//crmv@96450e
	// crmv@198024
	if ($module == 'Products' && $view != 'summary') {
		$prodFocus = CRMEntity::getInstance('Products');
		$prodFocus->addAttributesBlock($col_fields, 'edit', $editview_arr, $blockdata, $aBlockStatus);
	}
	// crmv@198024e
	//crmv@99316
	foreach($editview_arr as $headerid=>$editview_value) {
		foreach($editview_value as $i => $arr) {
			if ($arr[4] == 100) unset($editview_value[$i]);	// skip field
		}
		if (empty($editview_value)) $blockVisibility[$headerid] = 0;	// skip block if empty // crmv@104568 crmv@112297
	}
	//crmv@99316e
	foreach($editview_arr as $headerid=>$editview_value)
	{
		$editview_data = Array();
		for ($i=0,$j=0;$i<count($editview_value);$j++)
		{
			$key1=$editview_value[$i];
			if(is_array($editview_value[$i+1]) && ($key1[0][0]!=19 && $key1[0][0]!=20))
			{
				$key2=$editview_value[$i+1];
			}
			else
			{
				$key2 =array();
			}
			if($key1[0][0]!=19 && $key1[0][0]!=20){
				$editview_data[$j]=array(0 => $key1,1 => $key2);
				$i+=2;
			}
			else{
				$editview_data[$j]=array(0 => $key1);
				$i++;
			}
		}
		$editview_arr[$headerid] = $editview_data;
	}
	// crmv@104568
	$returndata = array();
	foreach($blockdata as $blockid=>$blockinfo) {
		$label = $blockinfo['label'];
		if ($label != '') {
			$curBlock = $label;
		}
		$blocklabel = getTranslatedString($curBlock,$module);
		$key = $blocklabel;
		if(is_array($editview_arr[$blockid])) {
			if (!is_array($returndata[$key])) {
				$returndata[$key] = array(
					'blockid' => $blockid,
					'panelid' => $blockinfo['panelid'],
					'label' => $blocklabel,
					'fields' => array()
				);
			}
			$returndata[$key]['fields'] = array_merge((array)$returndata[$key]['fields'], (array)$editview_arr[$blockid]);
		}
	}
	// crmv@104568e
	$log->debug("Exiting getBlockInformation method ...");
	return $returndata;

}

/** This function returns the data type of the vte_fields, with vte_field label, which is used for javascript validation.
* Param $validationData - array of vte_fieldnames with datatype
* Return type array
*/
//crmv@112297
function split_validationdataArray($validationData, $otherInfo=array())
{
	global $log;
	$log->debug("Entering split_validationdataArray(".$validationData.") method ...");
	$fieldName = '';
	$fieldLabel = '';
	$fldDataType = '';
	$rows = count($validationData);
	foreach($validationData as $fldName => $fldLabel_array)
	{
		if($fieldName == '')
		{
			$fieldName="'".$fldName."'";
		}
		else
		{
			$fieldName .= ",'".$fldName ."'";
		}
		foreach($fldLabel_array as $fldLabel => $datatype)
		{
			if($fieldLabel == '')
			{
				$fieldLabel = "'".addslashes($fldLabel)."'";
			}
			else
			{
				$fieldLabel .= ",'".addslashes($fldLabel)."'";
			}
			if($fldDataType == '')
			{
				$fldDataType = "'".$datatype ."'";
			}
			else
			{
				$fldDataType .= ",'".$datatype ."'";
			}
		}
	}
	$data['fieldname'] = $fieldName;
	$data['fieldlabel'] = $fieldLabel;
	$data['datatype'] = $fldDataType;
	if (!empty($otherInfo)) {
		$data['fielduitype'] = implode(',', $otherInfo['fielduitype']);
		$data['fieldwstype'] = implode(',',array_map(function($v){ return "\"$v\""; }, $otherInfo['fieldwstype']));
	}
	$log->debug("Exiting split_validationdataArray method ...");
	return $data;
}

function getValidationdataArray($validationData, $otherInfo=array(), $preserveKeys=false) {
	$data['fieldname'] = array();
	$data['fieldlabel'] = array();
	$data['datatype'] = array();
	foreach($validationData as $fldName => $fldLabel_array) {
		foreach($fldLabel_array as $fldLabel => $datatype) {
			$data['fieldname'][$fldName] = $fldName;
			$data['fieldlabel'][$fldName] = $fldLabel;
			$data['datatype'][$fldName] = $datatype;
		}
	}
	if (!empty($otherInfo)) {
		$data['fielduitype'] = $otherInfo['fielduitype'];
		$data['fieldwstype'] = $otherInfo['fieldwstype'];
	}
	if (!$preserveKeys && !empty($data)) {
		foreach($data as $info => $arr) $data[$info] = array_values($arr);
	}
	return $data;
}