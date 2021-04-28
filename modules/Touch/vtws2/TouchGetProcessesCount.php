<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@188277 */

class TouchGetProcessesCount extends TouchWSClass {

	function process(&$request) {
		global $adb, $table_prefix, $touchInst;

		if (in_array('Processes', $touchInst->excluded_modules)) return $this->error('Module not permitted');

		// very stupid!
		ob_start();
		include('modules/SDK/src/Notifications/plugins/ProcessesCheckChanges.php');
		$result = ob_get_clean();
		ob_end_clean();

		return $this->success(array('total'=>$result));
	}
}