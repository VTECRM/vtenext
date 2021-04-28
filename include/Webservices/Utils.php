<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/database/PearDatabase.php');
require_once("modules/Users/Users.php");
require_once 'include/Webservices/WebserviceField.php';
require_once 'include/Webservices/EntityMeta.php';
require_once 'include/Webservices/VtenextWebserviceObject.php';//crmv@207871
require_once("include/Webservices/VtenextCRMObject.php");//crmv@207871
require_once("include/Webservices/VtenextCRMObjectMeta.php");//crmv@207871
require_once("include/Webservices/DataTransform.php");
require_once("include/Webservices/WebServiceError.php");
require_once 'include/utils/utils.php';
require_once 'include/utils/UserInfoUtil.php';
require_once 'include/Webservices/ModuleTypes.php';
require_once 'include/utils/VtlibUtils.php';
require_once 'include/Webservices/WebserviceEntityOperation.php';
require_once 'include/Webservices/PreserveGlobal.php';

/* Function to return all the users in the groups that this user is part of.
 * @param $id - id of the user
 * returns Array:UserIds userid of all the users in the groups that this user is part of.
 */
function vtws_getUsersInTheSameGroup($id){
	require_once('include/utils/GetGroupUsers.php');
	require_once('include/utils/GetUserGroups.php');

	$groupUsers = new GetGroupUsers();
	$userGroups = new GetUserGroups();
	$allUsers = Array();
	$userGroups->getAllUserGroups($id);
	$groups = $userGroups->user_groups;

	foreach ($groups as $group) {
		$groupUsers->getAllUsersInGroup($group);
		$usersInGroup = $groupUsers->group_users;
		foreach ($usersInGroup as $user) {
		if($user != $id){
				$allUsers[$user] = getUserFullName($user);
			}
		}
	}
	return $allUsers;
}

function vtws_generateRandomAccessKey($length=10){
	$source = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	$accesskey = "";
	$maxIndex = strlen($source);
	for($i=0;$i<$length;++$i){
		$accesskey = $accesskey.substr($source,rand(null,$maxIndex),1);
	}
	return $accesskey;
}

/**
 * get current vte version from the database.
 */
function vtws_getVteVersion(){
	global $adb,$table_prefix;
	$query = 'select * from '.$table_prefix.'_version';
	$result = $adb->pquery($query, array());
	$version = '';
	while($row = $adb->fetch_array($result))
	{
		$version = $row['current_version'];
	}
	return $version;
}

function vtws_getUserAccessibleGroups($moduleId, $user){
	global $adb;
	// crmv@39110
	$userid = $user->id;
	require('user_privileges/requireUserPrivileges.php');
	// crmv@39110e
	require('user_privileges/sharing_privileges_'.$user->id.'.php');
	$tabName = getTabname($moduleId);
	if($is_admin==false && $profileGlobalPermission[2] == 1 &&
			($defaultOrgSharingPermission[$moduleId] == 3 or $defaultOrgSharingPermission[$moduleId] == 0)){
		$result=get_current_user_access_groups($tabName);
	}else{
		$result = get_group_options();
	}

	$groups = array();
	if($result != null && $result != '' && is_object($result)){
		$rowCount = $adb->num_rows($result);
		for ($i = 0; $i < $rowCount; $i++) {
			$nameArray = $adb->query_result_rowdata($result,$i);
			$groupId=$nameArray["groupid"];
			$groupName=$nameArray["groupname"];
			$groups[] = array('id'=>$groupId,'name'=>$groupName);
		}
	}
	return $groups;
}

function vtws_getWebserviceGroupFromGroups($groups){
	global $adb;
	$webserviceObject = VtenextWebserviceObject::fromName($adb,'Groups');//crmv@207871
	foreach($groups as $index=>$group){
		$groups[$index]['id'] = vtws_getId($webserviceObject->getEntityId(),$group['id']);
	}
	return $groups;
}

function vtws_getUserWebservicesGroups($tabId,$user){
	$groups = vtws_getUserAccessibleGroups($tabId,$user);
	return vtws_getWebserviceGroupFromGroups($groups);
}

function vtws_getIdComponents($elementid){
	return explode("x",$elementid);
}

function vtws_getId($objId, $elemId){
	return $objId."x".$elemId;
}

function getEmailFieldId($meta, $entityId){
	global $adb,$table_prefix;
	//no email field accessible in the module. since its only association pick up the field any way.
	$query="SELECT fieldid,fieldlabel,columnname FROM ".$table_prefix."_field WHERE tabid=?
		and uitype=13 and presence in (0,2)";
	$result = $adb->pquery($query, array($meta->getTabId()));

	//pick up the first field.
	$fieldId = $adb->query_result($result,0,'fieldid');
	return $fieldId;
}

function vtws_getParameter($parameterArray, $paramName,$default=null){

	if(is_array($parameterArray[$paramName])) {
		$param = array_map('addslashes', $parameterArray[$paramName]);
	} else {
		$param = addslashes($parameterArray[$paramName]);
	}
	if(!$param){
		$param = $default;
	}
	return $param;
}

// crmv@146076
function vtws_getEntityNameFields($moduleName) {
	global $adb,$table_prefix, $current_user;
	
	if ($current_user && $current_user->id > 0) {
		// get profile (only the first one)
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		$userProfile = $current_user_profiles[0];
		// override default field with the profile one
		$query = "SELECT COALESCE(p2en.fieldname, en.fieldname) AS fieldname
			FROM {$table_prefix}_entityname en
			LEFT JOIN {$table_prefix}_profile2entityname p2en ON p2en.profileid = ? AND p2en.tabid = en.tabid
			WHERE en.modulename = ?";
		$params = array($userProfile, $moduleName); // crmv@157032
	} else {
		$query = "SELECT fieldname FROM {$table_prefix}_entityname WHERE modulename = ?";
		$params = array($moduleName);
	}
	$result = $adb->pquery($query, $params);
	$nameFields = array();
	if ($adb->num_rows($result) > 0) {
		$fieldsname = $adb->query_result_no_html($result,0,'fieldname');
		if (strpos($fieldsname,',') !== false) {
			$nameFields = explode(',',$fieldsname);
		} else {
			array_push($nameFields,$fieldsname);
		}
	}
	return $nameFields;
}
// crmv@146076e

/** function to get the module List to which are crm entities.
 *  @return Array modules list as array
 */
function vtws_getModuleNameList(){
	global $adb,$table_prefix;

	$sql = "select name from ".$table_prefix."_tab where isentitytype=1 and name not in ('Rss','Recyclebin','Events') order by tabsequence";
	$res = $adb->pquery($sql, array());
	$mod_array = Array();
	while($row = $adb->fetchByAssoc($res)){
		array_push($mod_array,$row['name']);
	}
	return $mod_array;
}

function vtws_getWebserviceEntities(){
	global $adb,$table_prefix;

	$sql = "select name,id,ismodule from ".$table_prefix."_ws_entity";
	$res = $adb->pquery($sql, array());
	$moduleArray = Array();
	$entityArray = Array();
	while($row = $adb->fetchByAssoc($res)){
		if($row['ismodule'] == '1'){
			array_push($moduleArray,$row['name']);
		}else{
			array_push($entityArray,$row['name']);
		}
	}
	return array('module'=>$moduleArray,'entity'=>$entityArray);
}

/**
 *
 * @param VtenextWebserviceObject $webserviceObject
 * @return CRMEntity
 */
function vtws_getModuleInstance($webserviceObject){
	$moduleName = $webserviceObject->getEntityName();
	return CRMEntity::getInstance($moduleName);
}

function vtws_isRecordOwnerUser($ownerId){
	global $adb,$table_prefix;
	$result = $adb->pquery("select first_name from ".$table_prefix."_users where id = ?",array($ownerId));
	$rowCount = $adb->num_rows($result);
	$ownedByUser = ($rowCount > 0);
	return $ownedByUser;
}

function vtws_isRecordOwnerGroup($ownerId){
	global $adb,$table_prefix;
	$result = $adb->pquery("select groupname from ".$table_prefix."_groups where groupid = ?",array($ownerId));
	$rowCount = $adb->num_rows($result);
	$ownedByGroup = ($rowCount > 0);
	return $ownedByGroup;
}

function vtws_getOwnerType($ownerId){
	if(vtws_isRecordOwnerGroup($ownerId) == true){
		return 'Groups';
	}
	if(vtws_isRecordOwnerUser($ownerId) == true){
		return 'Users';
	}
	throw new WebServiceException(WebServiceErrorCode::$INVALIDID,"Invalid owner of the record");
}

function vtws_runQueryAsTransaction($query,$params,&$result){
	global $adb;

	$adb->startTransaction();
	$result = $adb->pquery($query,$params);
	$error = $adb->hasFailedTransaction();
	$adb->completeTransaction();
	return !$error;
}

function vtws_getCalendarEntityType($id){
	global $adb,$table_prefix;

	$sql = "select activitytype from ".$table_prefix."_activity where activityid=?";
	$result = $adb->pquery($sql,array($id));
	$seType = 'Calendar';
	if($result != null && isset($result)){
		if($adb->num_rows($result)>0){
			$activityType = $adb->query_result($result,0,"activitytype");
			if($activityType !== "Task"){
				$seType = "Events";
			}
		}
	}
	return $seType;
}

/***
 * Get the webservice reference Id given the entity's id and it's type name
 */
function vtws_getWebserviceEntityId($entityName, $id){
	global $adb;
	$webserviceObject = VtenextWebserviceObject::fromName($adb,$entityName);//crmv@207871
	return $webserviceObject->getEntityId().'x'.$id;
}

function vtws_addDefaultModuleTypeEntity($moduleName){
	global $adb;
	$isModule = 1;
	$moduleHandler = array('file'=>'include/Webservices/VtenextModuleOperation.php',
		'class'=>'VtenextModuleOperation');//crmv@207871
	return vtws_addModuleTypeWebserviceEntity($moduleName,$moduleHandler['file'],$moduleHandler['class'],$isModule);
}

function vtws_addModuleTypeWebserviceEntity($moduleName,$filePath,$className){
	global $adb,$table_prefix;
	$checkres = $adb->pquery('SELECT id FROM '.$table_prefix.'_ws_entity WHERE name=? AND handler_path=? AND handler_class=?',
		array($moduleName, $filePath, $className));
	if($checkres && $adb->num_rows($checkres) == 0) {
		$isModule=1;
		$entityId = $adb->getUniqueID($table_prefix."_ws_entity");
		$adb->pquery('insert into '.$table_prefix.'_ws_entity(id,name,handler_path,handler_class,ismodule) values (?,?,?,?,?)',
			array($entityId,$moduleName,$filePath,$className,$isModule));
	}
}

function vtws_deleteWebserviceEntity($moduleName) {
	global $adb,$table_prefix;
	$adb->pquery('DELETE FROM '.$table_prefix.'_ws_entity WHERE name=?',array($moduleName));
}

function vtws_addDefaultActorTypeEntity($actorName,$actorNameDetails,$withName = true){
	$actorHandler = array('file'=>'include/Webservices/VtenextActorOperation.php',
		'class'=>'VtenextActorOperation');//crmv@207871
	if($withName == true){
		vtws_addActorTypeWebserviceEntityWithName($actorName,$actorHandler['file'],$actorHandler['class'],
			$actorNameDetails);
	}else{
		vtws_addActorTypeWebserviceEntityWithoutName($actorName,$actorHandler['file'],$actorHandler['class'],
			$actorNameDetails);
	}
}

function vtws_addActorTypeWebserviceEntityWithName($moduleName,$filePath,$className,$actorNameDetails){
	global $adb,$table_prefix;
	$isModule=0;
	$entityId = $adb->getUniqueID($table_prefix."_ws_entity");
	$adb->pquery('insert into '.$table_prefix.'_ws_entity(id,name,handler_path,handler_class,ismodule) values (?,?,?,?,?)',
		array($entityId,$moduleName,$filePath,$className,$isModule));
	vtws_addActorTypeName($entityId,$actorNameDetails['fieldNames'],$actorNameDetails['indexField'],
		$actorNameDetails['tableName']);
}

function vtws_addActorTypeWebserviceEntityWithoutName($moduleName,$filePath,$className,$actorNameDetails){
	global $adb,$table_prefix;
	$isModule=0;
	$entityId = $adb->getUniqueID($table_prefix."_ws_entity");
	$adb->pquery('insert into '.$table_prefix.'_ws_entity(id,name,handler_path,handler_class,ismodule) values (?,?,?,?,?)',
		array($entityId,$moduleName,$filePath,$className,$isModule));
}

function vtws_addActorTypeName($entityId,$fieldNames,$indexColumn,$tableName){
	global $adb,$table_prefix;
	$adb->pquery('insert into '.$table_prefix.'_ws_entity_name(entity_id,name_fields,index_field,table_name) values (?,?,?,?)',
		array($entityId,$fieldNames,$indexColumn,$tableName));
}

function vtws_getName($id,$user){
	global $log,$adb;

	$webserviceObject = VtenextWebserviceObject::fromId($adb,$id);//crmv@207871
	$handlerPath = $webserviceObject->getHandlerPath();
	$handlerClass = $webserviceObject->getHandlerClass();

	require_once $handlerPath;

	$handler = new $handlerClass($webserviceObject,$user,$adb,$log);
	$meta = $handler->getMeta();
	return $meta->getName($id);
}

function vtws_preserveGlobal($name,$value){
	return VTWS_PreserveGlobal::preserveGlobal($name,$value);
}

/**
 * Takes the details of a webservices and exposes it over http.
 * @param $name name of the webservice to be added with namespace.
 * @param $handlerFilePath file to be include which provides the handler method for the given webservice.
 * @param $handlerMethodName name of the function to the called when this webservice is invoked.
 * @param $requestType type of request that this operation should be, if in doubt give it as GET,
 * 	general rule of thumb is that, if the operation is adding/updating data on server then it must be POST
 * 	otherwise it should be GET.
 * @param $preLogin 0 if the operation need the user to authorised to access the webservice and
 * 	1 if the operation is called before login operation hence the there will be no user authorisation happening
 * 	for the operation.
 * @return Integer operationId of successful or null upon failure.
 */
//crmv@170283
function vtws_addWebserviceOperation($name,$handlerFilePath,$handlerMethodName,$requestType,$preLogin = 0,$rest_name=null){
	global $adb,$table_prefix;
	if(strtolower($requestType) != 'get' && strtolower($requestType) != 'post'){
		return null;
	}
	$requestType = strtoupper($requestType);
	if(empty($preLogin)){
		$preLogin = 0;
	}else{
		$preLogin = 1;
	}
	$operationId = $adb->getUniqueID($table_prefix."_ws_operation");
	$params = array('operationid'=>$operationId,'name'=>$name,'handler_path'=>$handlerFilePath,'handler_method'=>$handlerMethodName,'type'=>$requestType,'prelogin'=>$preLogin);
	if (!empty($rest_name)) {
		$params['rest_name'] = $rest_name;
	}
	$createOperationQuery = "insert into {$table_prefix}_ws_operation(".implode(',',array_keys($params)).") values (".generateQuestionMarks($params).")";
	$result = $adb->pquery($createOperationQuery,$params);
	if($result !== false){
		return $operationId;
	}
	return null;
}
//crmv@170283e

/**
 * Add a parameter to a webservice.
 * @param $operationId Id of the operation for which a webservice needs to be added.
 * @param $paramName name of the parameter used to pickup value from request(POST/GET) object.
 * @param $paramType type of the parameter, it can either 'string','datetime' or 'encoded'
 * 	encoded type is used for input which will be encoded in JSON or XML(NOT SUPPORTED).
 * @param $sequence sequence of the parameter in the definition in the handler method.
 * @return Boolean true if the parameter was added successfully, false otherwise
 */
function vtws_addWebserviceOperationParam($operationId,$paramName,$paramType,$sequence){
	global $adb,$table_prefix;
	$supportedTypes = array('string','encoded','datetime','double','boolean');
	if(!is_numeric($sequence)){
		$sequence = 1;
	}if($sequence <=1){
		$sequence = 1;
	}
	if(!in_array(strtolower($paramType),$supportedTypes)){
		return false;
	}
	$createOperationParamsQuery = "insert into ".$table_prefix."_ws_operation_parameters(operationid,name,type,sequence)
		values (?,?,?,?)";
	$result = $adb->pquery($createOperationParamsQuery,array($operationId,$paramName,$paramType,$sequence));
	return ($result !== false);
}

/**
 *
 * @global PearDatabase $adb
 * @global <type> $log
 * @param <type> $name
 * @param <type> $user
 * @return WebserviceEntityOperation
 */
function vtws_getModuleHandlerFromName($name,$user){
	global $adb, $log;
	$webserviceObject = VtenextWebserviceObject::fromName($adb,$name);//crmv@207871
	$handlerPath = $webserviceObject->getHandlerPath();
	$handlerClass = $webserviceObject->getHandlerClass();

	require_once $handlerPath;

	$handler = new $handlerClass($webserviceObject,$user,$adb,$log);
	return $handler;
}

function vtws_getModuleHandlerFromId($id,$user){
	global $adb, $log;
	$webserviceObject = VtenextWebserviceObject::fromId($adb,$id);//crmv@207871
	$handlerPath = $webserviceObject->getHandlerPath();
	$handlerClass = $webserviceObject->getHandlerClass();

	require_once $handlerPath;

	$handler = new $handlerClass($webserviceObject,$user,$adb,$log);
	return $handler;
}

function vtws_CreateCompanyLogoFile($fieldname) {
	global $root_directory;
	$uploaddir = $root_directory ."/storage/logo/";
	$allowedFileTypes = array("jpeg", "png", "jpg", "pjpeg" ,"x-png");
	$binFile = $_FILES[$fieldname]['name'];
	$fileType = $_FILES[$fieldname]['type'];
	$fileSize = $_FILES[$fieldname]['size'];
	$fileTypeArray = explode("/",$fileType);
	$fileTypeValue = strtolower($fileTypeArray[1]);
	if($fileTypeValue == '') {
		$fileTypeValue = substr($binFile,strrpos($binFile, '.')+1);
	}
	if($fileSize != 0) {
		if(in_array($fileTypeValue, $allowedFileTypes)) {
			move_uploaded_file($_FILES[$fieldname]["tmp_name"],
					$uploaddir.$_FILES[$fieldname]["name"]);
			return $binFile;
		}
		throw new WebServiceException(WebServiceErrorCode::$INVALIDTOKEN,
			"$fieldname wrong file type given for upload");
	}
	throw new WebServiceException(WebServiceErrorCode::$INVALIDTOKEN,
			"$fieldname file upload failed");
}

function vtws_getActorEntityName ($name, $idList) {
	$db = PearDatabase::getInstance();
	if (!is_array($idList) && count($idList) == 0) {
		return array();
	}
	$entity = VtenextWebserviceObject::fromName($db, $name);//crmv@207871
	return vtws_getActorEntityNameById($entity->getEntityId(), $idList);
}

function vtws_getActorEntityNameById ($entityId, $idList) {
	global $table_prefix;
	$db = PearDatabase::getInstance();
	if (!is_array($idList) && count($idList) == 0) {
		return array();
	}
	$nameList = array();
	$webserviceObject = VtenextWebserviceObject::fromId($db, $entityId);//crmv@207871
	$query = "select * from ".$table_prefix."_ws_entity_name where entity_id = ?";
	$result = $db->pquery($query, array($entityId));
	if (is_object($result)) {
		$rowCount = $db->num_rows($result);
		if ($rowCount > 0) {
			$nameFields = $db->query_result($result,0,'name_fields');
			$tableName = $db->query_result($result,0,'table_name');
			$indexField = $db->query_result($result,0,'index_field');
			if (!(strpos($nameFields,',') === false)) {
				$fieldList = explode(',',$nameFields);
				$nameFields = "concat(";
				$nameFields = $nameFields.implode(",' ',",$fieldList);
				$nameFields = $nameFields.")";
			}

			$query1 = "select $nameFields as entityname, $indexField from $tableName where ".
				"$indexField in (".generateQuestionMarks($idList).")";
			$params1 = array($idList);
			$result = $db->pquery($query1, $params1);
			if (is_object($result)) {
				$rowCount = $db->num_rows($result);
				for ($i = 0; $i < $rowCount; $i++) {
					$id = $db->query_result($result,$i, $indexField);
					$nameList[$id] = $db->query_result($result,$i,'entityname');
				}
				return $nameList;
			}
		}
	}
	return array();
}

function vtws_isRoleBasedPicklist($name) {
	global $table_prefix;
	$db = PearDatabase::getInstance();
	$sql = "select picklistid from ".$table_prefix."_picklist where name = ?";
	$result = $db->pquery($sql, array($name));
	return ($db->num_rows($result) > 0);
}

function vtws_getConvertLeadFieldMapping(){
	global $adb,$table_prefix;
	$sql = "select * from ".$table_prefix."_convertleadmapping";
	$result = $adb->pquery($sql,array());
	if($result === false){
		return null;
	}
	$mapping = array();
	$rowCount = $adb->num_rows($result);
	for($i=0;$i<$rowCount;++$i){
		$row = $adb->query_result_rowdata($result,$i);
		$mapping[$row['leadfid']] = array('Accounts'=>$row['accountfid'],
			'Potentials'=>$row['potentialfid'],'Contacts'=>$row['contactfid']);
	}
	return $mapping;
}

/**	Function used to get the lead related Notes and Attachments with other entities Account, Contact and Potential
 *	@param integer $id - leadid
 *	@param integer $relatedId -  related entity id (accountid / contactid)
 */
function vtws_getRelatedNotesAttachments($id,$relatedId) {
	global $adb,$log,$table_prefix;

	$sql = "select * from ".$table_prefix."_senotesrel where crmid=?";
	$result = $adb->pquery($sql, array($id));
	if($result === false){
		return false;
	}
	$rowCount = $adb->num_rows($result);

	//crmv@38798
	$relatedModule = getSalesEntityType($relatedId);
	$sql="insert into ".$table_prefix."_senotesrel(crmid,notesid,relmodule) values (?,?,?)";
	for($i=0; $i<$rowCount;++$i ) {
		$noteId=$adb->query_result($result,$i,"notesid");
		$resultNew = $adb->pquery($sql, array($relatedId, $noteId, $relatedModule));
		if($resultNew === false){
			return false;
		}
	}
	//crmv@38798e

	$sql = "select * from ".$table_prefix."_seattachmentsrel where crmid=?";
	$result = $adb->pquery($sql, array($id));
	if($result === false){
		return false;
	}
	$rowCount = $adb->num_rows($result);

	$sql = "insert into ".$table_prefix."_seattachmentsrel(crmid,attachmentsid) values (?,?)";
	for($i=0;$i<$rowCount;++$i) {
		$attachmentId=$adb->query_result($result,$i,"attachmentsid");
		$resultNew = $adb->pquery($sql, array($relatedId, $attachmentId));
		if($resultNew === false){
			return false;
		}
	}
	return true;
}

/**	Function used to save the lead related products with other entities Account, Contact and Potential
 *	$leadid - leadid
 *	$relatedid - related entity id (accountid/contactid/potentialid)
 *	$setype - related module(Accounts/Contacts/Potentials)
 */
function vtws_saveLeadRelatedProducts($leadId, $relatedId, $setype) {
	global $adb,$table_prefix;

	$result = $adb->pquery("select * from ".$table_prefix."_seproductsrel where crmid=?", array($leadId));
	if($result === false){
		return false;
	}
	$rowCount = $adb->num_rows($result);
	for($i = 0; $i < $rowCount; ++$i) {
		$productId = $adb->query_result($result,$i,'productid');
		$resultNew = $adb->pquery("insert into ".$table_prefix."_seproductsrel values(?,?,?)", array($relatedId, $productId, $setype));
		if($resultNew === false){
			return false;
		}
	}
	return true;
}

//crmv@56031
function vtws_saveLeadRelatedMessages($leadId, $relatedId, $setype) {
	global $adb,$table_prefix;

	$result = $adb->pquery("select * from ".$table_prefix."_messagesrel where crmid=?", array($leadId));
	if($result === false){
		return false;
	}
	$rowCount = $adb->num_rows($result);
	for($i = 0; $i < $rowCount; ++$i) {
		$messagehash = $adb->query_result($result,$i,'messagehash');
		$resultNew = $adb->pquery("insert into ".$table_prefix."_messagesrel values(?,?,?)", array($messagehash, $relatedId, $setype));
		if($resultNew === false){
			return false;
		}
	}
	return true;
}
//crmv@56031e

/**	Function used to save the lead related services with other entities Account, Contact and Potential
 *	$leadid - leadid
 *	$relatedid - related entity id (accountid/contactid/potentialid)
 *	$setype - related module(Accounts/Contacts/Potentials)
 */
function vtws_saveLeadRelations($leadId, $relatedId, $setype) {
	global $adb,$table_prefix;

	$result = $adb->pquery("select * from ".$table_prefix."_crmentityrel where crmid=?", array($leadId));
	if($result === false){
		return false;
	}
	$rowCount = $adb->num_rows($result);
	for($i = 0; $i < $rowCount; ++$i) {
		$recordId = $adb->query_result($result,$i,'relcrmid');
		$recordModule = $adb->query_result($result,$i,'relmodule');
		$adb->pquery("insert into ".$table_prefix."_crmentityrel values(?,?,?,?)",
		array($relatedId, $setype, $recordId, $recordModule));
		if($resultNew === false){
			return false;
		}
	}
	$result = $adb->pquery("select * from ".$table_prefix."_crmentityrel where relcrmid=?", array($leadId));
	if($result === false){
		return false;
	}
	$rowCount = $adb->num_rows($result);
	for($i = 0; $i < $rowCount; ++$i) {
		$recordId = $adb->query_result($result,$i,'crmid');
		$recordModule = $adb->query_result($result,$i,'module');
		// crmv@114347
		if ($adb->isMysql()) {
			$adb->pquery("insert ignore into ".$table_prefix."_crmentityrel values (?,?,?,?)",
				array($recordId, $recordModule, $relatedId, $setype) //crmv@35141
			);
		} else {
			$res2 = $adb->pquery("SELECT crmid FROM {$table_prefix}_crmentityrel WHERE crmid = ? AND relcrmid = ?", array($recordId, $relatedId));
			if ($res2 && $adb->num_rows($res2) == 0) {
				$adb->pquery("insert into ".$table_prefix."_crmentityrel values(?,?,?,?)",
					array($recordId, $recordModule, $relatedId, $setype) //crmv@35141
				);
			}
		}
		// crmv@114347e
		if($resultNew === false){
			return false;
		}
	}
	//crmv@35141
	$skip_reference_modules = array('Calendar','Events');	//crmv@54739 crmv@164120 crmv@164122
	$result = $adb->pquery("select * from {$table_prefix}_fieldmodulerel where relmodule = ? and module not in (".generateQuestionMarks($skip_reference_modules).")",array('Leads',$skip_reference_modules));
	if ($result && $adb->num_rows($result) > 0) {
		while($row=$adb->fetchByAssoc($result)) {
			$fieldid = $row['fieldid'];
			$result1 = $adb->pquery("select * from {$table_prefix}_fieldmodulerel where module = ? and relmodule = ?",array($row['module'],$setype));
			if ($result1 && $adb->num_rows($result1) > 0) {
				$new_fieldid = $adb->query_result($result1,0,'fieldid');
				$new_field_result = $adb->pquery("select columnname, tablename from {$table_prefix}_field where fieldid = ?",array($new_fieldid));
				if ($new_field_result && $adb->num_rows($new_field_result) > 0) {
					$new_row_field = $adb->fetch_array($new_field_result);
				}
			}
			if ($fieldid == $new_fieldid) {
				$adb->pquery("update {$new_row_field['tablename']} set {$new_row_field['columnname']} = ? where {$new_row_field['columnname']} = ?", array($relatedId, $leadId));
			} else {
				$field_result = $adb->pquery("select columnname, tablename from {$table_prefix}_field where fieldid = ?",array($fieldid));
				if ($field_result && $adb->num_rows($field_result) > 0) {
					$row_field = $adb->fetch_array($field_result);
					$focus = CRMEntity::getInstance($row['module']);
					$result2 = $adb->pquery("select ".$focus->tab_name_index[$row_field['tablename']]." from {$row_field['tablename']} where {$row_field['columnname']} = ?", array($leadId));
					if ($result2 && $adb->num_rows($result2) > 0) {
						$related_ids = array();
						while($row2=$adb->fetchByAssoc($result2)) {
							$related_ids = $row2[$focus->tab_name_index[$row_field['tablename']]];
						}
						if (!empty($related_ids)) {
							$adb->pquery("update {$new_row_field['tablename']} set {$new_row_field['columnname']} = ? where ".$focus->tab_name_index[$new_row_field['tablename']]." in (".generateQuestionMarks($related_ids).") and ({$new_row_field['columnname']} = '' or {$new_row_field['columnname']} is null or {$new_row_field['columnname']} = 0)",array($relatedId,$related_ids));
							$adb->pquery("update {$row_field['tablename']} set {$row_field['columnname']} = ? where ".$focus->tab_name_index[$row_field['tablename']]." in (".generateQuestionMarks($related_ids).")",array('',$related_ids));
						}
					}
				}
			}
		}
	}
	//crmv@35141e
	return true;
}

function vtws_getFieldfromFieldId($fieldId, $fieldObjectList){
	foreach ($fieldObjectList as $field) {
		if($fieldId == $field->getFieldId()){
			return $field;
		}
	}
	return null;
}

/**	Function used to get the lead related activities with other entities Account and Contact
 *	@param integer $leadId - lead entity id
 *	@param integer $accountId - related account id
 *	@param integer $contactId -  related contact id
 *	@param integer $relatedId - related entity id to which the records need to be transferred
 */
function vtws_getRelatedActivities($leadId,$accountId,$contactId,$relatedId) {

	if(empty($leadId) || empty($relatedId) || (empty($accountId) && empty($contactId))){
		throw new WebServiceException(WebServiceErrorCode::$LEAD_RELATED_UPDATE_FAILED,
			"Failed to move related Activities/Emails");
	}
	global $adb,$table_prefix;
	$sql = "select * from ".$table_prefix."_seactivityrel where crmid=?";
	$result = $adb->pquery($sql, array($leadId));
	if($result === false){
		return false;
	}
	$rowCount = $adb->num_rows($result);
	for($i=0;$i<$rowCount;++$i) {
		$activityId=$adb->query_result($result,$i,"activityid");
		//crmv@171021
		$type =  getSalesEntityType($activityId);
		if (empty($type)) return false;
		//crmv@171021e
		$sql="delete from ".$table_prefix."_seactivityrel where crmid=?";
		$resultNew = $adb->pquery($sql, array($leadId));
		if($resultNew === false){
			return false;
		}
		if (!in_array($type,array('Emails','Fax','Sms'))) {	//crmv@54900
			if(!empty($accountId)){
				$sql = "insert into ".$table_prefix."_seactivityrel(crmid,activityid) values (?,?)";
				$resultNew = $adb->pquery($sql, array($accountId, $activityId));
				if($resultNew === false){
					return false;
				}
			}
			if(!empty($contactId)){
				$sql="insert into ".$table_prefix."_cntactivityrel(contactid,activityid) values (?,?)";
				$resultNew = $adb->pquery($sql, array($contactId, $activityId));
				if($resultNew === false){
					return false;
				}
			}
		} else {
			$sql = "insert into ".$table_prefix."_seactivityrel(crmid,activityid) values (?,?)";
			$resultNew = $adb->pquery($sql, array($relatedId, $activityId));
			if($resultNew === false){
				return false;
			}
		}
	}
	return true;
}

/**
 * Function used to transfer all the lead related records to given Entity(Contact/Account) record
 * @param $leadid - leadid
 * @param $relatedid - related entity id (contactid/accountid)
 * @param $setype - related module(Accounts/Contacts)
 */
function vtws_transferLeadRelatedRecords($leadId, $relatedId, $seType) {

	if(empty($leadId) || empty($relatedId) || empty($seType)){
		throw new WebServiceException(WebServiceErrorCode::$LEAD_RELATED_UPDATE_FAILED,
			"Failed to move related Records");
	}
	$status = vtws_getRelatedNotesAttachments($leadId, $relatedId);
	if($status === false){
		throw new WebServiceException(WebServiceErrorCode::$LEAD_RELATED_UPDATE_FAILED,
			"Failed to move related Documents to the ".$seType);
	}
	//Retrieve the lead related products and relate them with this new account
	$status = vtws_saveLeadRelatedProducts($leadId, $relatedId, $seType);
	if($status === false){
		throw new WebServiceException(WebServiceErrorCode::$LEAD_RELATED_UPDATE_FAILED,
			"Failed to move related Products to the ".$seType);
	}
	//crmv@56031
	$status = vtws_saveLeadRelatedMessages($leadId, $relatedId, $seType);
	if($status === false){
		throw new WebServiceException(WebServiceErrorCode::$LEAD_RELATED_UPDATE_FAILED,
			"Failed to move related Messages to the ".$seType);
	}
	//crmv@56031e
	$status = vtws_saveLeadRelations($leadId, $relatedId, $seType);
	if($status === false){
		throw new WebServiceException(WebServiceErrorCode::$LEAD_RELATED_UPDATE_FAILED,
			"Failed to move Records to the ".$seType);
	}
	/* crmv@35141 */
}

// crmv@177071
function vtws_transferOwnership($ownerId, $newOwnerId, $delete=true) {
	$user = CRMEntity::getInstance('Users');
	if ($delete) {
		$user->deleteUser($ownerId, $newOwnerId);
	} else {
		$user->transferOwnership($ownerId, $newOwnerId);
	}
}
// crmv@177071e

function vtws_getWebserviceTranslatedStringForLanguage($label, $currentLanguage) {
	static $translations = array();
	$currentLanguage = vtws_getWebserviceCurrentLanguage();
	if(empty($translations[$currentLanguage])) {
		include 'include/Webservices/language/'.$currentLanguage.'.lang.php';
		$translations[$currentLanguage] = $webservice_strings;
	}
	if(isset($translations[$currentLanguage][$label])) {
		return $translations[$currentLanguage][$label];
	}
	return null;
}

function vtws_getWebserviceTranslatedString($label) {
	$currentLanguage = vtws_getWebserviceCurrentLanguage();
	$translation = vtws_getWebserviceTranslatedStringForLanguage($label, $currentLanguage);
	if(!empty($translation)) {
		return $translation;
	}

	//current language doesn't have translation, return translation in default language
	//if default language is english then LBL_ will not shown to the user.
	$defaultLanguage = vtws_getWebserviceDefaultLanguage();
	$translation = vtws_getWebserviceTranslatedStringForLanguage($label, $defaultLanguage);
	if(!empty($translation)) {
		return $translation;
	}

	//if default language is not en_us then do the translation in en_us to eliminate the LBL_ bit
	//of label.
	if('en_us' != $defaultLanguage) {
		$translation = vtws_getWebserviceTranslatedStringForLanguage($label, 'en_us');
		if(!empty($translation)) {
			return $translation;
		}
	}
	return $label;
}

function vtws_getWebserviceCurrentLanguage() {
	global $default_language, $current_language;
	if(empty($current_language)) {
		return $default_language;
	}
	return $current_language;
}

function vtws_getWebserviceDefaultLanguage() {
	global $default_language;
	return $default_language;
}

//crmv@OPER4380
function vtws_addExtraTypeWebserviceEntity($moduleName,$filePath,$className){
	global $adb,$table_prefix;
	$checkres = $adb->pquery('SELECT id FROM '.$table_prefix.'_ws_entity_extra WHERE name=? AND handler_path=? AND handler_class=?',
		array($moduleName, $filePath, $className));
	if($checkres && $adb->num_rows($checkres) == 0) {
		$isModule=1;
		$entityId = $adb->getUniqueID($table_prefix."_ws_entity_extra");
		$adb->pquery('insert into '.$table_prefix.'_ws_entity_extra(id,name,handler_path,handler_class,ismodule) values (?,?,?,?,?)',
			array($entityId,$moduleName,$filePath,$className,$isModule));
	}
}
//crmv@OPER4380 e
?>