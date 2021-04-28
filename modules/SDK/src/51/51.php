<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@101683 crmv@104988 crmv@131239 */
global $sdk_mode, $current_user, $showfullusername, $adb, $table_prefix;
switch($sdk_mode) {
	case 'detail':
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$user_id = $col_fields[$fieldname];
		$user_name = getUserName($user_id, $showfullusername);
		$assigned_user_id = $current_user->id;
		if(is_admin($current_user))
		{
			$label_fld[] = '<a href="index.php?module=Users&action=DetailView&record='.$user_id.'">'.$user_name.'</a>';
		}
		else
		{
			$label_fld[] = $user_name;
		}
		$users_combo = get_select_options_array(get_user_array(false, "Active", $user_id), $assigned_user_id);
		$label_fld["options"] = $users_combo;
		break;
	case 'edit':
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$selected_value = false; //crmv@160843
		($value != '') ? $assigned_user_id = $value : $assigned_user_id = $current_user->id;
		$add_blank = false;
		if ($mode == '' && strpos($typeofdata,"M") !== false) {
			$add_blank = true;
			$assigned_user_id = '';
		}
		$users_combo = get_select_options_array(get_user_array($add_blank, "Active", $assigned_user_id), $assigned_user_id, 'false', $selected_value); //crmv@160843
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
		(!empty($sdk_value)) ? $value = getUserName($sdk_value, $showfullusername) : $value = '';
		break;
	case 'popupbasicsearch':
		$alias = "u_{$column_name}";
		$join = "left join {$table_prefix}_users {$alias} on {$alias}.id = {$table_name}.{$column_name}";
		$where = $current_user->formatUserNameSql($adb, $alias, $showfullusername)." like '%{$search_string}%'";
		break;
	case 'popupadvancedsearch':
		// TODO
		break;
}