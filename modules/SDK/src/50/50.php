<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@101683 */
if (!function_exists('getCustomUserList')) {
	function getCustomUserList($module, $fieldname) {
		global $adb, $table_prefix, $showfullusername;
		$user_array = array();
		$fieldinfo = $adb->pquery("select info from {$table_prefix}_field
			inner join {$table_prefix}_fieldinfo on {$table_prefix}_field.fieldid = {$table_prefix}_fieldinfo.fieldid
			where tabid = ? and fieldname = ?", array(getTabid($module),$fieldname));
		if ($fieldinfo && $adb->num_rows($fieldinfo) > 0) {
			$info = Zend_Json::decode($adb->query_result_no_html($fieldinfo,0,'info'));
			$info = $info['users'];	//crmv@106857
			if (!empty($info)) {
				foreach($info as $id) {
					$user_array[$id] = getUserName($id,$showfullusername);
				}
			}
		}
		return $user_array;
	}
}

global $sdk_mode, $current_user, $showfullusername;
switch($sdk_mode) {
	case 'detail':
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$user_id = $col_fields[$fieldname];
		$user_name = getUserName($user_id,$showfullusername);
		if(is_admin($current_user))
		{
			$label_fld[] = '<a href="index.php?module=Users&action=DetailView&record='.$user_id.'">'.$user_name.'</a>';
		}
		else
		{
			$label_fld[] = $user_name;
		}
		if (isset($dynaform_info['users'])) {
			$users_arr = array();
			$dynaform_info_users = explode(',',$dynaform_info['users']);
			foreach($dynaform_info_users as $id) {
				$users_arr[$id] = getUserName($id,$showfullusername);
			}
		} else {
			$users_arr = getCustomUserList($module,$fieldname);
		}
		$users_combo = get_select_options_array($users_arr, $user_id);
		$label_fld["options"] = $users_combo;
		break;
	case 'edit':
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		($mode == '' && empty($value)) ? $assigned_user_id = $current_user->id : $assigned_user_id = $value;
		$selected_value = false; //crmv@160843
		if (isset($dynaform_info['users'])) {
			$users_arr = array();
			$dynaform_info_users = explode(',',$dynaform_info['users']);
			foreach($dynaform_info_users as $id) {
				$users_arr[$id] = getUserName($id,$showfullusername);
			}
		//crmv@160837
		} elseif (isset($dynaform_info['default'])) {
			$users_arr = array($value => getUserName($value,$showfullusername));
		//crmv@160837e
		} else {
			$users_arr = getCustomUserList($module_name,$fieldname);
		}
		if (empty($value) && strpos($typeofdata,"M") !== false) {
			$assigned_user_id = '';
			//crmv@131239
			unset($users_arr['']);
			if (!in_array('',$users_arr)) $users_arr = array(''=>getTranslatedString("LBL_PLEASE_SELECT")) + $users_arr;
			//crmv@131239e
		}
		$users_combo = get_select_options_array($users_arr, $assigned_user_id, 'false', $selected_value); //crmv@160843
		$fieldvalue[] = $users_combo;
		//crmv@160843
		if ($_REQUEST['enable_editoptions'] == 'yes') {
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
		//crmv@160843e
		break;
	case 'relatedlist':
	case 'list':
		if (!empty($sdk_value)) $value = getUserName($sdk_value,$showfullusername);
		break;
}