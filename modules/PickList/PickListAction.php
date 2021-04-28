<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'include/utils/utils.php';
require_once 'modules/PickList/PickListUtils.php';
require_once "include/Zend/Json.php";
//crmv@16312
global $adb, $metaLogs, $current_user, $default_charset,$table_prefix; // crmv@49398
//crmv@16312 end
$moduleName = $_REQUEST['fld_module'];
$tableName = $_REQUEST['fieldname'];
$tableName = $adb->sql_escape_string($tableName);
$mode = trim($_REQUEST['mode']);
if(empty($mode)){
	echo "action mode is empty";
	exit;
}

if($mode == 'add'){
	$newValues = $_REQUEST['newValues'];
	$selectedRoles = $_REQUEST['selectedRoles'];
	
	$arr = Zend_Json::decode($newValues);
	$roles = Zend_Json::decode($selectedRoles);
	$count = count($arr);
	
	$sql = "select picklistid from ".$table_prefix."_picklist where name=?";
	$result = $adb->pquery($sql, array($tableName));
	$picklistid = $adb->query_result($result,0,"picklistid");
	
	for($i=0; $i<$count;$i++){
		//crmv@16312+24353
		$val = html_entity_decode(trim($arr[$i]), ENT_QUOTES, $default_charset);
		//crmv@16312+24353e
		//if(!empty($val)){	//crmv@114293
			$id = $adb->getUniqueID($table_prefix."_$tableName");
			$picklist_valueid = getUniquePicklistID();
			//crmv@23791
			$params = array($id, $val, 1, $picklist_valueid);
			if (in_array($tableName,array('eventstatus','taskstatus'))) {
				$params[] = 0;
			}
			$sql = "insert into ".$table_prefix."_$tableName values (".generateQuestionMarks($params).")";
			$adb->pquery($sql,$params);
			//crmv@23791e
			
			// crmv@174894
			if ($moduleName == 'Events' && $tableName == 'activitytype') {
				$tmplanguages = vtlib_getToggleLanguageInfo();
				$languages = array_keys($tmplanguages);
				foreach($languages as $lang){
					$value2translate = preg_replace('/[\W_]+/','_',strtolower($val)); // crmv@187112
					SDK::setLanguageEntry('ALERT_ARR', $lang, $value2translate, $val);
				}
				SDK::clearSessionValues();
			}
			// crmv@174894e
			
			//add the picklist values to the selected roles
			for($j=0;$j<count($roles);$j++){
				$roleid = $roles[$j];
				
				$sql ="select max(sortid)+1 as sortid from ".$table_prefix."_role2picklist left join ".$table_prefix."_$tableName on ".$table_prefix."_$tableName.picklist_valueid=".$table_prefix."_role2picklist.picklistvalueid where roleid=? and picklistid=?";
				$sortid = $adb->query_result($adb->pquery($sql, array($roleid, $picklistid)),0,'sortid');
				
				$sql = "insert into ".$table_prefix."_role2picklist values(?,?,?,?)";
				$adb->pquery($sql, array($roleid, $picklist_valueid, $picklistid, $sortid));
			}
		//}
	}
	//crmv@163337
	if ($metaLogs) $metaLogId = $metaLogs->log($metaLogs::OPERATION_EDITFIELD, getFieldid(getTabid($moduleName), $tableName), array('picklist_action'=>'add')); // crmv@49398
	if (!empty($metaLogId)) {
		require_once('modules/Settings/LayoutBlockListUtils.php');
		$layoutBlockListUtils = LayoutBlockListUtils::getInstance();
		$layoutBlockListUtils->versionOperation(getTabid($moduleName),$metaLogId);
	}
	//crmv@163337e
	echo "SUCCESS";
}elseif($mode == 'edit'){
	$newValues = Zend_Json::decode($_REQUEST['newValues']);
	$oldValues = Zend_Json::decode($_REQUEST['oldValues']);
	if(count($newValues) != count($oldValues)){
		echo "Some error occured";
		exit;
	}
	
	$qry="select tablename,columnname,uitype from ".$table_prefix."_field where fieldname=? and presence in (0,2)"; // crmv@85491
	$result = $adb->pquery($qry, array($tableName));
	$num = $adb->num_rows($result);

	for($i=0; $i<count($newValues);$i++){
		//crmv@16312+24353
		$newVal = html_entity_decode($newValues[$i], ENT_QUOTES, $default_charset);
		//crmv@16312+24353 end
		$oldVal = $oldValues[$i];
		
		if($newVal != $oldVal){
			//crmv@40527
			if(Vtecrm_Utils::CheckTable($table_prefix.'_linkedlist')) {
				include_once('modules/SDK/examples/uitypePicklist/300Utils.php');
				//crmv@57238
				if($adb->isMssql()){
					$qry_is_linked = "SELECT pickdest,picksrc FROM {$table_prefix}_linkedlist WHERE picksrc = ? GROUP BY pickdest,picksrc"; 
				}
				else{ //crmv@57238e
					$qry_is_linked = "SELECT * FROM {$table_prefix}_linkedlist WHERE picksrc = ? GROUP BY pickdest";
				} //crmv@57238
				$res_is_linked = $adb->pquery($qry_is_linked,Array($tableName));
				
				while($row = $adb->fetch_array($res_is_linked)){
					$update = false;
					$picklist1 = $row['picksrc'];
					$picklist2 = $row['pickdest'];				
					$matr_upd = linkedListGetAllOptions($picklist1, $picklist2, $moduleName);				
					unset($matr_upd['values1'][$oldVal]);
					$matr_upd['values1'][$newVal] = getTranslatedString($newVal,$moduleName);
					foreach ($matr_upd['matrix'] AS $keys => $picklistvals) {					
						if ($keys == $oldVal) {						
							$matr_upd['matrix'][$newVal] = $matr_upd['matrix'][$oldVal];
							unset($matr_upd['matrix'][$oldVal]);
							$update = true;
						}
					}
					if ($update) {
						linkedListDeleteLink($picklist1, $moduleName, $picklist2); // reset connections					
						foreach ($matr_upd['matrix'] as $src=>$destarray) {
							$destlinks = array();
							
							foreach ($destarray as $dest=>$destval) {							
								if ($destval == 1) $destlinks[$src][] = $dest;
							}
							if (count($destlinks) > 0) {
								linkedListAddLink($picklist1, $picklist2, $moduleName, $src, $destlinks[$src]);
							}
						}
					}
					
				}
				unset($matr_upd);
				//crmv@57238
				if($adb->isMssql()){
					$qry_is_linked_dest = "SELECT pickdest,picksrc FROM {$table_prefix}_linkedlist WHERE pickdest = ? GROUP BY pickdest,picksrc";
				}
				else{ //crmv@57238e
					$qry_is_linked_dest = "SELECT * FROM {$table_prefix}_linkedlist WHERE pickdest = ? GROUP BY pickdest";
				} //crmv@57238
				$res_is_linked_dest = $adb->pquery($qry_is_linked_dest,Array($tableName));
				
				while($row = $adb->fetch_array($res_is_linked_dest)){
					$update = false;
					$picklist1 = $row['picksrc'];
					$picklist2 = $row['pickdest'];				
					$matr_upd = linkedListGetAllOptions($picklist1, $picklist2, $moduleName);				
					unset($matr_upd['values2'][$oldVal]);
					$matr_upd['values2'][$newVal] = getTranslatedString($newVal,$moduleName);
					foreach ($matr_upd['matrix'] AS $keys => $picklistvals) {					
						if (in_array($oldVal,$picklistvals)) {
							$matr_upd['matrix'][$keys][$newVal] = $matr_upd['matrix'][$keys][$oldVal];
							unset($matr_upd['matrix'][$keys][$oldVal]);
							$update = true;
						}
					}
					if ($update) {
						linkedListDeleteLink($picklist1, $moduleName, $picklist2); // reset connections					
						foreach ($matr_upd['matrix'] as $src=>$destarray) {
							$destlinks = array();
							
							foreach ($destarray as $dest=>$destval) {							
								if ($destval == 1) $destlinks[$src][] = $dest;
							}
							if (count($destlinks) > 0) {
								linkedListAddLink($picklist1, $picklist2, $moduleName, $src, $destlinks[$src]);
							}
						}
					}
					
				}
			}
			//crmv@40527e
			
			$sql = "UPDATE ".$table_prefix."_$tableName SET $tableName=? WHERE $tableName=?";
			$adb->pquery($sql, array($newVal, $oldVal));
			
			// crmv@174894
			if ($moduleName == 'Events' && $tableName == 'activitytype') {
				$tmplanguages = vtlib_getToggleLanguageInfo();
				$languages = array_keys($tmplanguages);
				foreach($languages as $lang){
					$value2translate = preg_replace('/[\W_]+/','_',strtolower($newVal)); // crmv@187112
					SDK::setLanguageEntry('ALERT_ARR', $lang, $value2translate, $newVal);
				}
				SDK::clearSessionValues();
			}
			// crmv@174894e
			
			//replace the value of this piclist with new one in all records
			if($num > 0){
				for($n=0;$n<$num;$n++){
					$table_name = $adb->query_result_no_html($result,$n,'tablename');
					$columnName = $adb->query_result_no_html($result,$n,'columnname');
					$uitype = $adb->query_result_no_html($result,$n,'uitype'); // crmv@85491
					
					$sql = "update $table_name set $columnName=? where $columnName=?";
					$adb->pquery($sql, array($newVal, $oldVal));

					// crmv@85491
					if($uitype == 33){
						$sql = "SELECT $tableName FROM $table_name WHERE $tableName LIKE ?";
						$like_val = "%".$oldVal."%"; // fuzzy search, later I'll match precisely
						$res = $adb->pquery($sql,array($like_val));
						if($res){
							while($row = $adb->fetchByAssoc($res)){
								$pick_values = explode(' |##| ',$row[$tableName]);
								$tmp_key = array_search($oldVal,$pick_values);
								if($tmp_key !== false){
									$pick_values[$tmp_key] = $newVal;
									$string_values = implode(' |##| ',$pick_values);
									
									$update_sql = "UPDATE $table_name SET $columnName=? where $columnName=?";
									$adb->pquery($update_sql, array($string_values, $row[$tableName]));
								}
							}
						}
					}
					// crmv@85491e
				}
			}
		}
	}
	//crmv@163337
	if ($metaLogs) $metaLogId = $metaLogs->log($metaLogs::OPERATION_EDITFIELD, getFieldid(getTabid($moduleName), $tableName), array('picklist_action'=>'edit')); // crmv@49398
	if (!empty($metaLogId)) {
		require_once('modules/Settings/LayoutBlockListUtils.php');
		$layoutBlockListUtils = LayoutBlockListUtils::getInstance();
		$layoutBlockListUtils->versionOperation(getTabid($moduleName),$metaLogId);
	}
	//crmv@163337e
	echo "SUCCESS";
}elseif($mode == 'delete'){
	$values = Zend_Json::decode($_REQUEST['values']);
	$replaceVal = $_REQUEST['replaceVal'];
	if(!empty($replaceVal)){
		$sql = "select * from ".$table_prefix."_$tableName where $tableName=?";
		$result = $adb->pquery($sql, array($replaceVal));
		$replacePicklistID = $adb->query_result($result, 0, "picklist_valueid");
	}
	
	for($i=0;$i<count($values);$i++){
		$sql = "select * from ".$table_prefix."_$tableName where $tableName=?";
		$result = $adb->pquery($sql, array($values[$i]));
		$origPicklistID = $adb->query_result($result, 0, "picklist_valueid");
			
		//give permissions for the new picklist
		if(!empty($replaceVal)){
			$sql = "select * from ".$table_prefix."_role2picklist where picklistvalueid=?";
			$result = $adb->pquery($sql, array($replacePicklistID));
			$count = $adb->num_rows($result);
			
			if($count == 0){
				$sql = "update ".$table_prefix."_role2picklist set picklistvalueid=? where picklistvalueid=?";
				$adb->pquery($sql, array($replacePicklistID, $origPicklistID));
			}
		}
		
		//crmv@40527
		if(Vtecrm_Utils::CheckTable($table_prefix.'_linkedlist')) {
			$delVal = $values[$i];
			include_once('modules/SDK/examples/uitypePicklist/300Utils.php');
			//crmv@57238
			if($adb->isMssql()){
				$qry_is_linked = "SELECT pickdest,picksrc FROM {$table_prefix}_linkedlist WHERE pickdest = ? GROUP BY pickdest,picksrc";
			}
			else{ //crmv@57238e
				$qry_is_linked = "SELECT * FROM {$table_prefix}_linkedlist WHERE picksrc = ? GROUP BY pickdest";
			} //crmv@57238
			$res_is_linked = $adb->pquery($qry_is_linked,Array($tableName));
			
			while($row = $adb->fetch_array($res_is_linked)){
				$update = false;
				$picklist1 = $row['picksrc'];
				$picklist2 = $row['pickdest'];				
				$matr_upd = linkedListGetAllOptions($picklist1, $picklist2, $moduleName);				
				unset($matr_upd['values1'][$delVal]);				
				foreach ($matr_upd['matrix'] AS $keys => $picklistsvals) {					
					if ($keys == $delVal) {
						unset($matr_upd['matrix'][$delVal]);
						$update = true;
					}
				}
				if ($update) {
					linkedListDeleteLink($picklist1, $moduleName, $picklist2); // reset connections					
					foreach ($matr_upd['matrix'] as $src=>$destarray) {
						$destlinks = array();
						
						foreach ($destarray as $dest=>$destval) {							
							if ($destval == 1) $destlinks[$src][] = $dest;
						}
						if (count($destlinks) > 0) {
							linkedListAddLink($picklist1, $picklist2, $moduleName, $src, $destlinks[$src]);
						}
					}
				}
				
			}
			unset($matr_upd);
			$qry_is_linked_dest = "SELECT * FROM {$table_prefix}_linkedlist WHERE pickdest = ? GROUP BY pickdest";
			$res_is_linked_dest = $adb->pquery($qry_is_linked_dest,Array($tableName));
			
			while($row = $adb->fetch_array($res_is_linked_dest)){
				$update = false;
				$picklist1 = $row['picksrc'];
				$picklist2 = $row['pickdest'];				
				$matr_upd = linkedListGetAllOptions($picklist1, $picklist2, $moduleName);				
				unset($matr_upd['values2'][$delVal]);				
				foreach ($matr_upd['matrix'] AS $keys => $picklistvals) {					
					if (in_array($oldVal,$picklistvals)) {						
						unset($matr_upd['matrix'][$keys][$delVal]);
						$update = true;
					}
				}
				if ($update) {
					linkedListDeleteLink($picklist1, $moduleName, $picklist2); // reset connections					
					foreach ($matr_upd['matrix'] as $src=>$destarray) {
						$destlinks = array();
						
						foreach ($destarray as $dest=>$destval) {							
							if ($destval == 1) $destlinks[$src][] = $dest;
						}
						if (count($destlinks) > 0) {
							linkedListAddLink($picklist1, $picklist2, $moduleName, $src, $destlinks[$src]);
						}
					}
				}
				
			}
		}
		//crmv@40527e
		
		$sql = "delete from ".$table_prefix."_$tableName where $tableName=?";
		$adb->pquery($sql, array($values[$i]));
		
		$sql = "delete from ".$table_prefix."_role2picklist where picklistvalueid=?";
		$adb->pquery($sql, array($origPicklistID));
		
		//replace the value of this piclist with new one in all records
		$qry="select tablename,columnname from ".$table_prefix."_field where fieldname=? and presence in (0,2)";
		$result = $adb->pquery($qry, array($tableName));
		$num = $adb->num_rows($result);
		if($num > 0){
			for($n=0;$n<$num;$n++){
				$table_name = $adb->query_result($result,$n,'tablename');
				$columnName = $adb->query_result($result,$n,'columnname');
				
				$sql = "update $table_name set $columnName=? where $columnName=?";
				$adb->pquery($sql, array($replaceVal, $values[$i]));
			}
		}
	}
	//crmv@163337
	if ($metaLogs) $metaLogId = $metaLogs->log($metaLogs::OPERATION_EDITFIELD, getFieldid(getTabid($moduleName), $tableName), array('picklist_action'=>'delete')); // crmv@49398
	if (!empty($metaLogId)) {
		require_once('modules/Settings/LayoutBlockListUtils.php');
		$layoutBlockListUtils = LayoutBlockListUtils::getInstance();
		$layoutBlockListUtils->versionOperation(getTabid($moduleName),$metaLogId);
	}
	//crmv@163337e
	echo "SUCCESS";
}

?>