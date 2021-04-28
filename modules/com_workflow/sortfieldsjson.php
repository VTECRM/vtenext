<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/


function vtSortFieldsJson($request){
	$moduleName = $request['module_name'];
	$focus = CRMEntity::getInstance($moduleName); // crmv@26897
	echo Zend_Json::encode($focus->sortby_fields);
}
vtSortFieldsJson($_REQUEST);
