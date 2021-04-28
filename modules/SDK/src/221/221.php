<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/SDK/src/221/221Utils.php');
global $sdk_mode, $current_user;

switch($sdk_mode) {
	case 'insert':
		break;
	case 'detail':
		$value = $col_fields[$fieldname];

		$uitype221 = new UitypeRoleUtils();
		$roles = $uitype221->getAllRoles();
		$display_value = '';
		foreach($roles as $role) {
			$chk_val = '';
			if ($value == $role['roleid']) {
				$display_value = $role['rolename'];
				$chk_val = 'selected';
			}
			if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
				$options[] = array(htmlentities($role['rolename'],ENT_QUOTES,$default_charset),to_html($role['roleid']),$chk_val);	
			else
				$options[] = array($role['rolename'],to_html($role['roleid']),$chk_val);
		}
		$link = '';
		if (!empty($display_value)) {
			if (is_admin($current_user))
				$link = '<a href="index.php?module=Settings&action=RoleDetailView&parenttab=Settings&roleid='.$value.'">'.$display_value.'</a>';
			else
				$link = $display_value;
		}
		$label_fld[] = getTranslatedString($fieldlabel, $module);
		$label_fld[] = $link;
		$label_fld ["options"] = $options;
		break;
	case 'edit':
		$uitype221 = new UitypeRoleUtils();
		$roles = $uitype221->getAllRoles();
		$selected_value = ''; //crmv@174986
		foreach($roles as $role) {
			$chk_val = '';
			if ($value == $role['roleid']) {
				$chk_val = 'selected';
				$selected_value = $role['roleid']; //crmv@174986
			}
			if(isset($_REQUEST['file']) && $_REQUEST['file'] == 'QuickCreate')
				$options[] = array(htmlentities($role['rolename'],ENT_QUOTES,$default_charset),to_html($role['roleid']),$chk_val );	
			else
				$options[] = array($role['rolename'] ,to_html($role['roleid']),$chk_val );
		}
		$editview_label[] = getTranslatedString($fieldlabel, $module_name);
		$fieldvalue[] = $options;
		//crmv@174986
		if ($_REQUEST['enable_editoptions'] == 'yes') {
			if ($value == '' && empty($chk_val)) $selected_value = true;
			$fieldvalue[] = array(
				'enable_editoptions' => true,
				'picklist_display' => (!$selected_value)?'none':'block',
				'editoptions_div_display' => (!$selected_value)?'block':'none',
				'advanced_field_assignment_display' => 'none',
				'other_value' => (!$selected_value)?$value:'',
				'type_options' => array(
					array('v',str_replace(':','',getTranslatedString('LBL_ROLES','Settings')),($selected_value)?'selected':''),
					array('o',getTranslatedString('LBL_OTHER','Users'),(!$selected_value)?'selected':''),
				),
			);
			if ($_REQUEST['file'] = 'ProcessMaker/actions/CreateForm' && stripos($_REQUEST['module'],'ModLight') !== false) unset($fieldvalue[1]['type_options'][2]);
		}
		//crmv@174986e
		break;
	case 'relatedlist':
	case 'list':
		$uitype221 = new UitypeRoleUtils();
		$value = $uitype221->getRoleName($value);
		break;
}
?>