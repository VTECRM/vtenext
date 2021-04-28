<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require 'include.inc';
class VTFieldFormulasEventHandler extends VTEventHandler{
	function handleEvent($name, $data){
		global $adb;
		$moduleName = $data->getModuleName();
		$mem = new VTModuleExpressionsManager($adb);
		$me = $mem->retrieve($moduleName);
		$me->update($data);
	}
}
?>