<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

class TouchSetFlag extends TouchWSClass {
	
	public function process(&$request) {
		global $touchInst, $touchUtils, $currentModule;
		
		$module = $request['module'];
		$recordid = intval($request['recordid']);
		$flag = vtlib_purify($request['flag']);
		$value = vtlib_purify($request['value']);
		
		if ($module != 'ALL' && in_array($module, $touchInst->excluded_modules)) return $this->error('Module not permitted');
		
		$currentModule = $module;

		$focus = $touchUtils->getModuleInstance($currentModule);
		$focus->id = $recordid;
		$focus->retrieve_entity_info($recordid, $currentModule);

		if (method_exists($focus, 'setFlag')) {
			try {
				$focus->setFlag($flag,$value);
				$success = true;
			} catch (Exception $e) {
				$success = false;
				$message = $e->getMessage();
			}

		} else {
			$success = false;
			$message = 'The module does not support flags';
		}

		return $touchInst->createOutput(array(), $message, $success);
	}
}
