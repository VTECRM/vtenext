<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchGetFavorites extends TouchWSClass {
	
	public function process(&$request) {
		global $touchInst, $current_user, $table_prefix;
		
		require_once('modules/SDK/src/Favorites/Utils.php');

		$favs = getFavoritesList($current_user->id);
		$favout = array();

		foreach ($favs as $k=>$v) {
			if (in_array($v['module'], $touchInst->excluded_modules)) continue;
			if (!isModuleInstalled($v['module']) || !vtlib_isModuleActive($v['module'])) continue; // crmv@113940
			if (isPermitted($v['module'], 'DetailView', $v['crmid']) != 'yes') continue; // crmv@113638
			// fix for Calendar (Events/Tasks)
			if ($v['module'] == 'Calendar') {
				$activityType = getSingleFieldValue($table_prefix.'_activity', 'activitytype', 'activityid', $v['crmid']);
				if ($activityType != 'Task') {
					// Events uses the "Events" module
					$v['module'] = 'Events';
				}
			}
			$newfav = $v;
			$newfav['entityname'] = $v['name'];
			$newfav['favourite'] = 1;
			unset($newfav['name']);
			$favout[] = $newfav;
		}

		return $this->success(array('list'=>$favout, 'total'=>count($favout)));
	}
	
}