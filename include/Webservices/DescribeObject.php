<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
	
function vtws_describe($elementType,$user,$show_hidden_fields=false){	//crmv@120039
	
	global $log,$adb;
	$webserviceObject = VtenextWebserviceObject::fromName($adb,$elementType);//crmv@207871
	$webserviceObject->show_hidden_fields = $show_hidden_fields;	//crmv@120039
	$handlerPath = $webserviceObject->getHandlerPath();
	$handlerClass = $webserviceObject->getHandlerClass();

	require_once $handlerPath;
	
	$handler = new $handlerClass($webserviceObject,$user,$adb,$log);
	$meta = $handler->getMeta();
	
	$types = vtws_listtypes(null, $user);
	$types['types'][] = 'ProductsBlock'; // crmv@195745 - allow describe of productsblock
	if(!in_array($elementType,$types['types'])){
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
	}
	
	$entity = $handler->describe($elementType);
	VTWS_PreserveGlobal::flush();
	return $entity;
}

//crmv@120039
function vtws_describe_all($elementType,$user){
	return vtws_describe($elementType,$user,true);
}	
//crmv@120039e