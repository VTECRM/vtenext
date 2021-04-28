<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function wsapp_getHandler($appType){
	global $table_prefix;
    $db = PearDatabase::getInstance();
    //crmv@392267	
    if ($appType == ''){
    	$appType = 'Outlook';
    }
    //crmv@392267e
    $result = $db->pquery("SELECT * FROM ".$table_prefix."_wsapp_handlerdetails WHERE type=?",array($appType));

    $handlerResult = array();

    if($db->num_rows($result)>0){
        $handlerResult['handlerclass'] = $db->query_result($result,0,'handlerclass');
        $handlerResult['handlerpath'] = $db->query_result($result,0,'handlerpath');
    }
    return $handlerResult;
}

function wsapp_getApplicationName($key){
	global $table_prefix;
    $db = PearDatabase::getInstance();

    $result = $db->pquery("SELECT name from ".$table_prefix."_wsapp WHERE appkey=?",array($key));
    $name = false;
    if($db->num_rows($result)){
        $name = $db->query_result($result,0,'name');
    }
    return $name;
}

function wsapp_getRecordEntityNameIds($entityNames,$modules,$user){
    $entityMetaList = array();
    $db = PearDatabase::getInstance();
    
    if(empty($entityNames)) return;

    if(!is_array($entityNames))
        $entityNames = array($entityNames);
    if(empty($modules))
        return array();
    if(!is_array($modules))
        $modules = array($modules);
    $entityNameIds = array();
    foreach($modules as $moduleName){
        if(empty($entityMetaList[$moduleName])){
            $handler = vtws_getModuleHandlerFromName($moduleName, $user);
            $meta = $handler->getMeta();
            $entityMetaList[$moduleName] = $meta;
        }
        $meta = $entityMetaList[$moduleName];
        $nameFieldsArray = explode(",",$meta->getNameFields());
        if(count($nameFieldsArray)>1){
            $nameFields = "concat(".implode(",' ',",$nameFieldsArray).")";
        }
        else
            $nameFields = $nameFieldsArray[0];

        $query = "SELECT ".$meta->getObectIndexColumn()." as id,$nameFields as entityname FROM ".$meta->getEntityBaseTable()." WHERE $nameFields IN(".generateQuestionMarks($entityNames).")";
        $result = $db->pquery($query,$entityNames);
        $num_rows = $db->num_rows($result);
        for($i=0;$i<$num_rows;$i++){
            $id = $db->query_result($result, $i,'id');
            $entityName = $entityNames[$i];
            $entityNameIds[$entityName] = vtws_getWebserviceEntityId($moduleName, $id);
        }
    }
    return $entityNameIds;
}
/***
 * Converts default time zone to specifiedTimeZone
 */

function wsapp_convertDateTimeToTimeZone($dateTime,$toTimeZone){
    global $log,$default_timezone;
    $time_zone = $default_timezone;
    $source_time = date_default_timezone_set($time_zone);
    $sourceDate = date("Y-m-d H:i:s");
    $dest_time = date_default_timezone_set($toTimeZone);
    $destinationDate = date("Y-m-d H:i:s");
    $diff = (strtotime($destinationDate)-strtotime($sourceDate));
    $givenTimeInSec = strtotime($dateTime);
    $modifiedTimeSec = $givenTimeInSec+$diff;
    $display_time = date("Y-m-d H:i:s",$modifiedTimeSec);
    return $display_time;
}

function wsapp_checkIfRecordsAssignToUser($recordsIds,$userid){
	global $table_prefix;
	$assignedRecordIds = array();
    if(!is_array($recordsIds))
        $recordsIds = array($recordsIds);
    if(count($recordsIds)<=0)
        return $assignedRecordIds;
    $db = PearDatabase::getInstance();
    $query = "SELECT * FROM ".$table_prefix."_crmentity where crmid IN (".generateQuestionMarks($recordsIds).") and smownerid=?";
    $params = array();
    foreach($recordsIds as $id){
        $params[] = $id;
    }
    $params[] = $userid;
    $queryResult = $db->pquery($query,$params);
    $num_rows = $db->num_rows($queryResult);
    
    for($i=0;$i<$num_rows;$i++){
        $assignedRecordIds[] = $db->query_result($queryResult,$i,"crmid");
    }
    return $assignedRecordIds;
}

function wsapp_getAppKey($appName){
	global $table_prefix;
    $db = PearDatabase::getInstance();
    $query = "SELECT * FROM ".$table_prefix."_wsapp WHERE name=?";
    $params = array($appName);
    $result = $db->pquery($query,$params);
    $appKey="";
    if($db->num_rows($result)){
        $appKey = $db->query_result($result,0,'appkey');
    }
    return $appKey;
}

function wsapp_getAppSyncType($appKey){
	global $table_prefix;
	$db = PearDatabase::getInstance();
    $query = "SELECT type FROM ".$table_prefix."_wsapp WHERE appkey=?";
    $params = array($appKey);
    $result = $db->pquery($query,$params);
    $syncType="";
    if($db->num_rows($result)>0){
        $syncType = $db->query_result($result,0,'type');
    }
    return $syncType;
}

function wsapp_RegisterHandler($type,$handlerClass,$handlerPath){
	global $table_prefix;
	$db = PearDatabase::getInstance();
	$query = "SELECT 1 FROM ".$table_prefix."_wsapp_handlerdetails where type=?";
	$result = $db->pquery($query,array($type));
	if($db->num_rows($result)>0){
		$saveQuery = "UPDATE ".$table_prefix."_wsapp_handlerdetails SET handlerclass=?,handlerpath=? WHERE type=?";
		$parameters = array($handlerClass,$handlerPath,$type);
	} else{
		$saveQuery = "INSERT INTO ".$table_prefix."_wsapp_handlerdetails VALUES(?,?,?)";
		$parameters = array($type,$handlerClass,$handlerPath);
	}
	$db->pquery($saveQuery,$parameters);}

?>