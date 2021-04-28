<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchSetRecent extends TouchWSClass {

	public $validateModule = true;

	function process(&$request) {
		global $log, $adb, $table_prefix, $touchInst, $touchUtils, $current_user, $currentModule;
		
		$module = $request['module'];
		$recordid = intval($request['record']);
		
		$trackMod = $module;
		if ($module == 'Events') $trackMod = 'Calendar';

		require_once('data/Tracker.php');

		try {
			$focus = $touchUtils->getModuleInstance($trackMod);
			$focus->track_view($current_user->id, $trackMod, $recordid);
		} catch (Exception $e) {
			return $this->error($e->getMessage());
		}
		
		return $this->success();
	}

}
