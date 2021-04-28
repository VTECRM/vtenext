<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@99316 */
function vte_sum() {
	$arguments = func_get_args();
	$value = 0;
	if (!empty($arguments)) {
		foreach($arguments as $argument) {
			$argument = trim($argument,' \'"');
			$value = $value + floatval($argument);
		}
	}
	return $value;
}
function vte_calculate_percentage($percentage, $total) {
	/*
	 // if you do not want to use parameters you can read the records in the process with the object $engine
	 global $engine;
	 $metaid = 338;	// entity reference in the process manager ex. [$338] Accounts (BPMN-Task: start)
	 $crmid = $engine->getCrmid($metaid);
	 // now you can use the crmid and read the columns of the record with the function getSingleFieldValue(), a query or with the method retrieve_entity_info_no_html()
	 */
	$value = ($percentage / 100) * $total;
	return $value;
}
function vte_calculate_table_total($table_fieldname, $price_fieldname, $quantity_fieldname, $metaid='', $elementid='') {
	/*
	 * if metaid is not defined, it uses the current element you are saving
	 * ex. modlight:		$sdk:vte_calculate_table_total(ml44,f388,f389)
	 * ex. dynaform table:	$sdk:vte_calculate_table_total(vcf_2,vcf_4,vcf_5,DF148)
	 */
	global $engine;
	if (empty($metaid)) $metaid = $engine->metaid;
	if (empty($elementid)) $elementid = $engine->elementid;
	
	if (strpos($metaid,'DF') !== false) {
		$table_rows = get_field_table_rows($table_fieldname, str_replace('DF','',$metaid), 'Dynaform', $engine->running_process);
	} else {
		$record = $engine->getCrmid($metaid);
		$table_rows = get_field_table_rows($table_fieldname, $record);
	}
	$total = 0;
	if (!empty($table_rows)) {
		foreach($table_rows as $row) {
			$total += $row[$price_fieldname] * $row[$quantity_fieldname];
		}
	}
	return $total;
}
function get_field_table_rows($tablename, $record, $type='ModLight', $running_process='') {
	$rows = array();
	if ($type == 'ModLight') {
		$module = getSalesEntityType($record);
		require_once('include/utils/ModLightUtils.php');
		$MLUtils = ModLightUtils::getInstance();
		$columns = $MLUtils->getColumns($module,$tablename);
		$values = $MLUtils->getValues($module,$record,$tablename,$columns);
		if (!empty($values)) {
			foreach($values as $tmp) {
				$rows[] = $tmp['row'];
			}
		}
	} elseif ($type == 'Dynaform') {
		$metaid = $record;
		require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');
		$processDynaFormObj = ProcessDynaForm::getInstance();
		$values = $processDynaFormObj->getValues($running_process,$metaid);
		$rows = $values[$tablename];
	}
	return $rows;
}
function vte_compare_account_bill_ship_street($module, $id, $data) {
	list($wsModId,$id) = explode('x',$id);	// do it every time
	
	if ($data['bill_street'] == $data['ship_street']) {
		return 'e';
	} else {
		return 'n';
	}
}
function close_tickets($engine){
	global $adb, $table_prefix;
	
	$q = "SELECT id, module from {$table_prefix}_processmaker_metarec where processid = ? and start = ?";
	$result = $adb->pquery($q, array($engine->processid,1));
	
	if ($result && $adb->num_rows($result) > 0) {
		$metaid = $adb->query_result($result,0,'id');
		$module = $adb->query_result($result,0,'module');
		$crmid = $engine->getCrmid($metaid);
		
		$q = "SELECT * FROM {$table_prefix}_troubletickets AS T
		INNER JOIN {$table_prefix}_crmentity AS C
		ON T.ticketid = C.crmid
		WHERE C.deleted = 0 AND T.parent_id = ? and C.setype = ?";
		$res = $adb->pquery($q, array($crmid, 'HelpDesk'));
		if ($res && $adb->num_rows($res) > 0) {
			$num_rows = $adb->num_rows($res);
			
			for($ct = 0; $ct < $num_rows; $ct++){
				$ticket[$ct] = $adb->query_result($res,$ct,"crmid");
				$ticket_focus = CRMEntity::getInstance('HelpDesk'); //get record focus
				$ticket_focus->retrieve_entity_info_no_html($ticket[$ct],'HelpDesk'); //get record fields
				$ticket_focus->mode = 'edit';
				$ticket_focus->column_fields['ticketstatus'] = 'Closed';
				$ticket_focus->save('HelpDesk');
			}
		}
	}
}
//crmv@169519
function get_running_process_current_user($module, $id, $data) {
	global $current_user;
	return getUserName($current_user->id);
}
//crmv@169519e
//crmv@180645
function formatDate($date=null, $format='') {
	if (empty($date)) $date = date('Y-m-d H:i:s');
	if (empty($format)) $format = 'Y-m-d H:i:s';
	
	if (is_numeric($date)) return gmdate($format, $date); // for uitype 73
	else return date($format, strtotime($date));
}
//crmv@180645e
if (!function_exists('date_now')) {
	function date_now($format='') {
		if (empty($format)) $format = 'Y-m-d H:i:s';
		return date($format);
	}
}
if (!function_exists('diffDate')) {
	function diffDate($start, $end=null, $type=null) {
		if(empty($end) || $end == ''){
			$end = date("Y-m-d H:i:s");
		}
		
		$end = strtotime($end);
		$start = strtotime($start);
		$diff = ($end - $start);
		$diff = (int)$diff;
		
		if(isset($type)){
			if(!empty($type)){
				if($type == 'days'){
					$diff = $diff/86400;
					if($diff < 0){
						$diff = $diff*(-1);
						$diff = (int)$diff;
					}
				}
			}
		}
		return $diff; //return diff days
	}
}
// crmv@189362
function vte_get_projecttask_usage_percent($module, $id, $data) {
	list($wsModId,$id) = explode('x',$id);	// do it every time
	
	$tot = 0;
	$used = $data['used_hours'];
	if ($data['servicetype'] == 'Package') {
		$tot = $data['package_hours'];
	} elseif ($data['servicetype'] == 'Project') {
		$tot = $data['expected_hours'];
	}
	if ($tot > 0)
		return ($used * 100) / $tot;
	else
		return 0;
}
// crmv@189362e
// crmv@195748
function vte_json_column_fields() {
	$arguments = func_get_args();
	$column_fields = array();
	if (!empty($arguments)) {
		$id = $arguments[0];
		unset($arguments[0]);
		if (!empty($id) && !empty($arguments)) {
			if (stripos($id,"x") !== false) list(,$id) = explode('x',$id);
			$module = getSalesEntityType($id);
			$focus = CRMEntity::getInstance($module);
			$focus->retrieve_entity_info_no_html($id,$module);
			foreach ($arguments as $argument) {
				$column_fields[$argument] = $focus->column_fields[$argument];
			}
		}
	}
	return Zend_Json::encode($column_fields);
}
// crmv@195748e