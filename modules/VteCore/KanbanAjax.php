<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@OPER6288 crmv@121672 */

global $adb, $table_prefix, $currentModule, $current_user, $app_strings;
$modObj = CRMEntity::getInstance($currentModule);

$ajxaction = $_REQUEST['ajxaction'];
if ($ajxaction == 'SAVE' || $ajxaction == 'SAVE_WITHOUT_PRESAVE') {
	$viewid = vtlib_purify($_REQUEST['viewid']);
	$column = vtlib_purify($_REQUEST['column']);
	$record = vtlib_purify($_REQUEST['record']);
	$ajax_result = false;
	$ajax_result_message = getTranslatedString('ERROR');
	$type = 'Kanban'; $message = ''; $confirm = false; $status = true;	// init sdk presave parameters
	
	$kanbanView = KanbanView::getInstance($viewid);
	$actions = $kanbanView->getActions($column);
	if (empty($actions['conditions'])) {
		$ajax_result_message = getTranslatedString('LBL_KANBAN_DRAG_DISABLED');
	} else {
		$isPermitted = (isPermitted($currentModule, 'DetailViewAjax', $record) == 'yes');
		if ($isPermitted) {
			foreach($actions['conditions'] as $action) {
				$permField = getFieldVisibilityPermission($currentModule, $current_user->id, $action['fieldname']);
				if ($permField != 0) {
					$isPermitted = false;
					break;
				}
			}
		}
		if ($isPermitted) {
			if ($ajxaction == 'SAVE') {
				$sdk_file = SDK::getPreSave($currentModule);
				if ($sdk_file != '' && Vtecrm_Utils::checkFileAccess($sdk_file)) {
					
					$modObj->retrieve_entity_info_no_html($record, $currentModule);
					foreach($actions['conditions'] as $action) {
						$modObj->column_fields[$action['fieldname']] = $action['value'];
					}
					$values = $modObj->column_fields;
					
					include($sdk_file);
				}
			}
			if ($confirm || !$status) {
				($message != '') ? $ajax_result_message = $message : $ajax_result_message = getTranslatedString('ERROR');
			} else {
				if (empty($modObj->id)) {
					$modObj->retrieve_entity_info_no_html($record, $currentModule);
					foreach($actions['conditions'] as $action) {
						$modObj->column_fields[$action['fieldname']] = $action['value'];
					}
				}
				$modObj->mode = 'edit';
				$modObj->save($currentModule);
				if($modObj->id != '') {
					$ajax_result = true;
					$ajax_result_message = '';
				}
			}
		} else {
			$ajax_result_message = getTranslatedString('LBL_PERMISSION');
		}
	}
	if ($ajax_result) {
		echo ":#:SUCCESS:$ajax_result_message";
	} else {
		echo ($confirm) ? ":#:CONFIRM:$ajax_result_message" : ":#:FAILURE:$ajax_result_message";
	}
	exit;
} elseif ($ajxaction == 'LOADCOLUMN') {
	$viewid = vtlib_purify($_REQUEST['viewid']);
	$column = vtlib_purify($_REQUEST['column']);
	$page = vtlib_purify($_REQUEST['page']);
	
	$kanbanView = KanbanView::getInstance($viewid);
	$column = $kanbanView->getList($column,VteSession::get('lv_user_id_'.$currentModule),$page); // crmv@107328
	
	$smarty = new VteSmarty();
	$smarty->assign('APP',$app_strings);
	$smarty->assign('MODULE',$currentModule);
	$smarty->assign('KANBAN_COL',$column);
	$smarty->display('KanbanColumn.tpl');
}