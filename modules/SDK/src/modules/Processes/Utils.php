<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@103534 crmv@105685 crmv@126096 */
function advQueryProcesses($module = '') {
	global $current_user, $table_prefix;
	static $advQueryProcesses = array();
	
	if (!isset($advQueryProcesses[$module])) {
		$user_role = $current_user->column_fields['roleid'];
		$user_role_info = getRoleInformation($user_role);
		$current_user_parent_role_seq = $user_role_info[$user_role][1];
		
		$inst = CRMEntity::getInstance($module);
		$query = $inst->getNonAdminAccessQuery($module, $current_user, $current_user_parent_role_seq, array());
		
		$filter = "or exists (select running_process from {$table_prefix}_actor_running_processes where running_process = {$table_prefix}_processes.running_process and userid in (".$query."))";
		
		$assigned_array = get_group_array(false, 'Active', $current_user->id, 'private');
		if (empty($assigned_array)) {
			$assigned_array = array($current_user->id);
		} else {
			$assigned_array = array_keys($assigned_array);
			$assigned_array[] = $current_user->id;
		}
		$assigned_str = implode(',',$assigned_array);
		$filter .= " or exists (select running_process from {$table_prefix}_assigned_running_processes where running_process = {$table_prefix}_processes.running_process and assigned in (".$assigned_str."))";
		
		$advQueryProcesses[$module] = $filter;
	}
	return $advQueryProcesses[$module];
}
function advPermProcesses($module, $actionname, $record_id='') {
	if (!empty($record_id)) {
		global $current_user, $adb, $table_prefix;
		if ($actionname == 'DetailViewAjax' && $_REQUEST['ajxaction'] == 'SHOWGRAPH') $actionname = 'DetailView';	//crmv@109685 the same permissions of the DetailView
		$user_role = $current_user->column_fields['roleid'];
		$user_role_info = getRoleInformation($user_role);
		$current_user_parent_role_seq = $user_role_info[$user_role][1];
		
		$processMakerUtils = ProcessMakerUtils::getInstance();
		$inst = CRMEntity::getInstance($module);
		$query = $inst->getNonAdminAccessQuery($module, $current_user, $current_user_parent_role_seq, array());
		$query = "select running_process from {$table_prefix}_actor_running_processes where running_process = ? and userid in (".$query.")";
		$result = $adb->pquery($query, array(getSingleFieldValue($inst->table_name, 'running_process', $inst->table_index, $record_id)));
		if ($result && $adb->num_rows($result) > 0) {
			if ($processMakerUtils->edit_permission_mode == 'all') {
				$action_permission = isPermitted($module,$actionname,'',false);
				if ($action_permission == 'yes') return 'yes';
			} elseif ($processMakerUtils->edit_permission_mode == 'assigned') {
				$actionid = getActionid($actionname);
				if (in_array($actionid,array(0,1,2)) && $current_user->id != getSingleFieldValue($inst->entity_table, 'smownerid', $inst->tab_name_index[$inst->entity_table], $record_id)) {
					return 'no';
				} else {
					$action_permission = isPermitted($module,$actionname,'',false);
					if ($action_permission == 'yes') return 'yes';
				}
			}
		}
	}
	return '';
}