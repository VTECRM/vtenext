<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 */

class TouchGetLinkedRecords extends TouchWSClass {
	
	public function process(&$request) {
		global $touchInst, $touchUtils, $currentModule;
		
		$module = vtlib_purify($request['module']);
		$recordid = intval($request['recordid']);
		
		$currentModule = $module;

		$rm = RelationManager::getInstance();
		$excludedMods = array('ModComments');
		//if ($currentModule == 'Calendar') $excludedMods[] = 'Contacts'; // otherwise if more than 1 contact there's no related list to show

		$relIds = $rm->getRelatedIds($module, $recordid, array(), $excludedMods, false, true);

		$records = array();
		foreach ($relIds as $mod=>$ids) {
			foreach ($ids as $rid) {
				$records[] = array(
					'crmid' => $rid,
					'module' => $mod,
					'entityname' => $touchUtils->getEntityNameFromFields($mod, $rid, null),
				);
			}
		}

		return $this->success(array('entries'=>$records, 'total'=>count($records)));
	}
	
}
