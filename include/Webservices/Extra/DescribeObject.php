<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/Webservices/Extra/ModuleTypes.php');

function vtws_describeExtra($elementType,$user){
	$module_obj = WebserviceExtra::getInstance($elementType);
	if (!$module_obj){
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
	}
	$entity = $module_obj->describe($elementType);
	return $entity;
}
?>