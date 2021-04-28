<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'include/Webservices/Utils.php';
require_once 'include/Webservices/ModuleTypes.php';
require_once 'include/utils/CommonUtils.php';

	function vtws_sync($mtime,$elementType,$syncType,$user){
		global $adb, $recordString,$modifiedTimeString,$table_prefix;
        
		$numRecordsLimit = 100;
		$ignoreModules = array("Users");
		$typed = true;
		$dformat = "Y-m-d H:i:s";
		$datetime = date($dformat, $mtime);
		$setypeArray = array();
		$setypeData = array();
		$setypeHandler = array();
		$setypeNoAccessArray = array();

		$output = array();
		$output["updated"] = array();
		$output["deleted"] = array();
		
		$applicationSync = false;
		if(is_object($syncType) && ($syncType instanceof Users)){
			$user = $syncType;
		} else if($syncType == 'application'){
			$applicationSync = true;
		}

		if($applicationSync && !is_admin($user)){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Only admin users can perform application sync");
		}
		
		$ownerIds = array($user->id);

		if(!isset($elementType) || $elementType=='' || $elementType==null){
			$typed=false;
		}


		
		$adb->startTransaction();

		$accessableModules = array();
		$entityModules = array();
		$modulesDetails = vtws_listtypes(null,$user);
		$moduleTypes = $modulesDetails['types'];
		$modulesInformation = $modulesDetails["information"];

		foreach($modulesInformation as $moduleName=>$entityInformation){
		 if($entityInformation["isEntity"])
				$entityModules[] = $moduleName;
		}
		if(!$typed){
			$accessableModules = $entityModules;
		}
		else{
				if(!in_array($elementType,$entityModules))
					throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
				$accessableModules[] = $elementType;
		}

		$accessableModules = array_diff($accessableModules,$ignoreModules);

		if(count($accessableModules)<=0)
		{
				$output['lastModifiedTime'] = $mtime;
				$output['more'] = false;
				return $output;
		}

		if($typed){
				$handler = vtws_getModuleHandlerFromName($elementType, $user);
				$moduleMeta = $handler->getMeta();
				$entityDefaultBaseTables = $moduleMeta->getEntityDefaultTableList();
				//since there will be only one base table for all entities
				$baseCRMTable = $entityDefaultBaseTables[0];
				if($elementType=="Calendar" || $elementType=="Events" ){
					$baseCRMTable = getSyncQueryBaseTable($elementType);
				}
		}
		else
		 $baseCRMTable = $table_prefix."_crmentity ";

		//modifiedtime - next token
		$q = "SELECT modifiedtime FROM $baseCRMTable WHERE  modifiedtime>? and setype IN(".generateQuestionMarks($accessableModules).") ";
		$params = array($datetime);
		foreach($accessableModules as $entityModule){
			if($entityModule == "Events")
				$entityModule = "Calendar";
			$params[] = $entityModule;
		}
		if(!$applicationSync){
			$q .= ' and smownerid IN('.generateQuestionMarks($ownerIds).')';
			$params = array_merge($params,$ownerIds);
		}
		
		//crmv@fix
		$q .=" order by modifiedtime"; // limit $numRecordsLimit";
		$result = $adb->limitpQuery($q,0,$numRecordsLimit,$params);
		//crmv@fix e
		
		$modTime = array();
		for($i=0;$i<$adb->num_rows($result);$i++){
			$modTime[] = $adb->query_result($result,$i,'modifiedtime');
		}
		if(!empty($modTime)){
			$maxModifiedTime = max($modTime);
		}
		if(!$maxModifiedTime){
			$maxModifiedTime = $datetime;
		}



		foreach($accessableModules as $elementType){
			$handler = vtws_getModuleHandlerFromName($elementType, $user);
			$moduleMeta = $handler->getMeta();
			$deletedQueryCondition = $moduleMeta->getEntityDeletedQuery();
			preg_match_all("/(?:\s+\w+[ \t\n\r]+)?([^=]+)\s*=([^\s]+|'[^']+')/",$deletedQueryCondition,$deletedFieldDetails);
			$fieldNameDetails = $deletedFieldDetails[1];
			$deleteFieldValues = $deletedFieldDetails[2];
			$deleteColumnNames = array();
			foreach($fieldNameDetails as $tableName_fieldName){
				$fieldComp = explode(".",$tableName_fieldName);
				$deleteColumnNames[$tableName_fieldName] = $fieldComp[1];
			}
			$params = array($moduleMeta->getTabName(),$datetime,$maxModifiedTime);
			

			$queryGenerator = QueryGenerator::getInstance($elementType, $user);
			$fields = array();
			$moduleFeilds = $moduleMeta->getModuleFields();
			$moduleFeildNames = array_keys($moduleFeilds);
			$moduleFeildNames[]='id';
			$queryGenerator->setFields($moduleFeildNames);
			$selectClause = "SELECT ".$queryGenerator->getSelectClauseColumnSQL();
			// adding the fieldnames that are present in the delete condition to the select clause
			// since not all fields present in delete condition will be present in the fieldnames of the module
			foreach($deleteColumnNames as $table_fieldName=>$columnName){
				if(!in_array($columnName,$moduleFeildNames)){
					$selectClause .=", ".$table_fieldName;
				}
			}
			if($elementType=="Emails")
				$fromClause = vtws_getEmailFromClause();
			else
				$fromClause = $queryGenerator->getFromClause();
			$fromClause .= " INNER JOIN (select modifiedtime, crmid,deleted,setype FROM $baseCRMTable WHERE setype=? and modifiedtime >? and modifiedtime<=?";
			if(!$applicationSync){
				$fromClause.= 'and smownerid IN('.generateQuestionMarks($ownerIds).')';
				$params = array_merge($params,$ownerIds);
			}
			$fromClause.= ' ) '.$table_prefix.'_ws_sync ON ('.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_ws_sync.crmid)';
			$q = $selectClause." ".$fromClause;
			$result = $adb->pquery($q, $params);
			$recordDetails = array();
			$deleteRecordDetails = array();
			while($arre = $adb->fetchByAssoc($result)){
				$key = $arre[$moduleMeta->getIdColumn()];
				if(vtws_isRecordDeleted($arre,$deleteColumnNames,$deleteFieldValues)){
					if(!$moduleMeta->hasAccess()){
						continue;
					}
					$output["deleted"][] = vtws_getId($moduleMeta->getEntityId(), $key);
				}
				else{
					if(!$moduleMeta->hasAccess() ||!$moduleMeta->hasPermission(EntityMeta::$RETRIEVE,$key)){
						continue;
					}
					try{
						$output["updated"][] = DataTransform::sanitizeDataWithColumn($arre,$moduleMeta);;
					}catch(WebServiceException $e){
						//ignore records the user doesn't have access to.
						continue;
					}catch(Exception $e){
						throw new WebServiceException(WebServiceErrorCode::$INTERNALERROR,"Unknown Error while processing request");
					}
				}
			}
		}

		$q = "SELECT crmid FROM $baseCRMTable WHERE modifiedtime>?  and setype IN(".generateQuestionMarks($accessableModules).")";
		$params = array($maxModifiedTime);
		
		foreach($accessableModules as $entityModule){
			if($entityModule == "Events")
				$entityModule = "Calendar";
			$params[] = $entityModule;
		}
		if(!$applicationSync){
			$q.='and smownerid IN('.generateQuestionMarks($ownerIds).')';
			$params = array_merge($params,$ownerIds);
		}
		
		$result = $adb->pquery($q,$params);
		if($adb->num_rows($result)>0){
			$output['more'] = true;
		}
		else{
			$output['more'] = false;
		}
		if(!$maxModifiedTime){
			$modifiedtime = $mtime;
		}else{
			$modifiedtime = vtws_getSeconds($maxModifiedTime);
		}
		if(is_string($modifiedtime)){
			$modifiedtime = intval($modifiedtime);
		}
		$output['lastModifiedTime'] = $modifiedtime;

		$error = $adb->hasFailedTransaction();
		$adb->completeTransaction();

		if($error){
			throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
					vtws_getWebserviceTranslatedString('LBL_'.
							WebServiceErrorCode::$DATABASEQUERYERROR));
		}

		VTWS_PreserveGlobal::flush();
		return $output;
	}
	
	function vtws_getSeconds($mtimeString){
		//TODO handle timezone and change time to gmt.
		return strtotime($mtimeString);
	}

	function vtws_isRecordDeleted($recordDetails,$deleteColumnDetails,$deletedValues){
		$deletedRecord = false;
		$i=0;
		foreach($deleteColumnDetails as $tableName_fieldName=>$columnName){
			if($recordDetails[$columnName]!=$deletedValues[$i++]){
				$deletedRecord = true;
				break;
			}
		}
		return $deletedRecord;
	}

	function vtws_getEmailFromClause(){
		global $table_prefix;
		$q = "FROM ".$table_prefix."_activity
				INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_activity.activityid = ".$table_prefix."_crmentity.crmid
				LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_crmentity.smownerid = ".$table_prefix."_users.id
				LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_crmentity.smownerid = ".$table_prefix."_groups.groupid
				LEFT JOIN ".$table_prefix."_seattachmentsrel ON ".$table_prefix."_activity.activityid = ".$table_prefix."_seattachmentsrel.crmid
				LEFT JOIN ".$table_prefix."_attachments ON ".$table_prefix."_seattachmentsrel.attachmentsid = ".$table_prefix."_attachments.attachmentsid
				LEFT JOIN ".$table_prefix."_email_track ON ".$table_prefix."_activity.activityid = ".$table_prefix."_email_track.mailid
				INNER JOIN ".$table_prefix."_emaildetails ON ".$table_prefix."_activity.activityid = ".$table_prefix."_emaildetails.emailid
				LEFT JOIN ".$table_prefix."_users ".$table_prefix."_users2 ON ".$table_prefix."_emaildetails.idlists = ".$table_prefix."_users2.id
				LEFT JOIN ".$table_prefix."_groups ".$table_prefix."_groups2 ON ".$table_prefix."_emaildetails.idlists = ".$table_prefix."_groups2.groupid";
		return $q;
	}

	function getSyncQueryBaseTable($elementType){
		global $table_prefix;
		if($elementType!="Calendar" && $elementType!="Events"){
			return $table_prefix."_crmentity";
		}
		else{
			$activityCondition = getCalendarTypeCondition($elementType);
			$query = $table_prefix."_crmentity INNER JOIN ".$table_prefix."_activity ON (".$table_prefix."_crmentity.crmid = ".$table_prefix."_activity.activityid and $activityCondition)";
			return $query;
		}
	}

	function getCalendarTypeCondition($elementType){
		global $table_prefix;
		if($elementType == "Events")
			$activityCondition = $table_prefix."_activity.activitytype !='Task' and ".$table_prefix."_activity.activitytype !='Emails'";
		else
			$activityCondition = $table_prefix."_activity.activitytype ='Task'";
		return $activityCondition;
	}

?>