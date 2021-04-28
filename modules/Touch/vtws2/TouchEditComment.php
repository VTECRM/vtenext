<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@93148 */

require_once('modules/ModComments/ModComments.php');


class TouchEditComment extends TouchWSClass {

	function process(&$request) {
		global $touchInst, $touchUtils, $currentModule;

		$module = 'ModComments';
		if (in_array($module, $touchInst->excluded_modules)) return $this->error('Module not permitted');

		$currentModule = $module;

		$subaction = $request['action'];
		
		if ($subaction == 'addusers') {
			$record = intval($request['record']);
			$userids = Zend_Json::decode($request['users']) ?: array();
			$userids = array_filter(array_map('intval', $userids));
			
			if (count($userids) == 0) return $this->error('Invalid users specified');
			
			if (isPermitted($module, 'EditView', $record) != 'yes') {
				return $this->error('Operation not permitted');
			}
			$r = $this->addUsers($record, $userids);
			if ($r == false) return $this->error('Operation failed');
			
			return $this->success();
		} else {
			return $this->error('Unknown action');
		}
		
	}
	
	public function addUsers($crmid, $users) {
		global $touchUtils;
	
		$modObj = $touchUtils->getModuleInstance('ModComments');
		$modObj->retrieve_entity_info($crmid, 'ModComments');
		$modObj->id = $crmid;
		$modObj->column_fields['crmid'] = $crmid;
			
		if(empty($modObj->column_fields['smcreatorid'])) {
			$modObj->column_fields['smcreatorid'] = $modObj->column_fields['assigned_user_id'];
		}
			
		$r = $modObj->addUsers($users);
		return $r;
	}

}
