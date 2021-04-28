<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once("include/Webservices/Extra/ModuleTypes.php");
require_once("include/Webservices/Extra/DescribeObject.php");
	
function vtws_retrieveExtra($id, $user){
	//@TODO: permissions/query_check
	global $log,$adb,$table_prefix;
	list($extramodule_id,$record) = explode("x",$id);
	if ($extramodule_id == ''){
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
	}
	$module_obj = WebserviceExtra::getInstanceFromID($extramodule_id);
	if (!$module_obj){
		throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
	}		
	$extramodule = $module_obj->name;
	$obj = $module_obj->describe();
	$fields = Array();
	if (!empty($obj['fields'])){
		foreach ($obj['fields'] as $field_arr){
			$fields[$field_arr['name']] = '';
		}
	}
	$modules_ids = Array();
	$sql = "select id,name from ".$table_prefix."_ws_entity";
	$res = $adb->query($sql);
	if ($res){
		while($row = $adb->fetchByAssoc($res,-1,false)){
			$module_ids[$row['name']] = $row['id'];
		}
	}
	$params = Array();
	$q = '';
	$function = Array();
	$module_obj->retrieve_parameters($record,$module_ids,$params,$q,$fields,$function);
	try{
		$res = $adb->pquery($q,$params);
		if ($res){
			while($row = $adb->fetchByAssoc($res)){
				$row_change = Array();
				foreach ($fields as $field=>$prefix){
					if ($prefix != ''){
						if (isset($function[$field])){
							$row_change[$field] = $module_ids[$row[$function[$field]]]."x".$row[$field];
						}
						else{
							$row_change[$field] = $prefix."x".$row[$field];
						}
					}
					else{
						$row_change[$field] = $row[$field];
					}
				}
				$row_change['id'] = $extramodule_id."x".$row['historyid'];
			}
		}
		
	}
	catch(Exception $e) {
		throw new WebServiceException(WebServiceErrorCode::$QUERYSYNTAX, "Error running query");
	}
	return $row_change;
}
?>