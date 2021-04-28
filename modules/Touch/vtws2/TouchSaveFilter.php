<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@34559 */

class TouchSaveFilter extends TouchWSClass {

	public $validateModule = true;

	function process(&$request) {
	
		$module = $request['module'];
		$viewid = intval($request['viewid']);
		$recordid = intval($request['record']);
		$relRecordid = intval($request['relrecord']);
		
		// TODO:
		
		return $this->success();
		
	}
	
}
