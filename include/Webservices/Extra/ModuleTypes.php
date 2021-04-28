<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/Webservices/Extra/WebserviceExtra.php');

function vtws_listtypesExtra($fieldTypeList, $user=false){
	$extramodules = WebserviceExtra::getAllExtraModules();
	$return_modules = Array();
	foreach ($extramodules as $module){
		$module_obj = WebserviceExtra::getInstance($module);
		if ($module_obj){
			$return_modules['types'][] = $module;
			$return_modules['information'][$module] = $module_obj->get_listtype($module);
			$return_modules['information'][$module]['isEntity'] = 2;
		}
	}
	return $return_modules;
}
?>