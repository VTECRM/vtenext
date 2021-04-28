<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

	function vtModuleTypeInfoJson($adb, $request){
		$moduleName = $request['module_name'];
		$et = VTWSEntityType::usingGlobalCurrentUser($moduleName);
		echo Zend_Json::encode($et->getFieldLabels());
	}
	vtModuleTypeInfoJson($adb, $_REQUEST);
