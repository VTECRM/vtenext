<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include('Contacts_config.php');
set_time_limit(0);
if (function_exists('proc_nice')) @proc_nice(10);

global $module, $success_import, $log_script_state, $log_script_content;
//create log object (total and partial)
$global_log=new log("\n");
//start the total logger
$global_log->start();
//scrivo sul db lo stato dello script
$skip = false;
$time_start = time();
$res = $adb->pquery("select count(*) as count from $log_script_state where type = ?",Array($module));
if ($res){
	if ($adb->query_result($res,0,'count') == 0){
		$adb->pquery("insert into $log_script_state (type,state,working_id) values (?,?,?)",Array($module,0,NULL));
	}
}
$res = $adb->pquery("select enabled from $log_script_state where type = ?",Array($module));
if ($res && $adb->num_rows($res) > 0){
	$enabled = (int)$adb->query_result($res,0,'enabled');
	if ($enabled === 0){
		$skip = true;
	}
}
if (!$skip){
	$working_id = $adb->getUniqueID($log_script_content);
	$adb->pquery("update $log_script_state set state = 1,working_id = ? where type = ?",Array($working_id,$module));
	$adb->startTransaction();
	$records=do_import_contacts(date("Y-m-d H:i:s",$time_start),$working_id,$adbext);
	$adb->completeTransaction();
	$time_end = time();
	if (crmv_is_empty_import($records)){ // se non ho importato niente, aggiorno solamente la data di ultimo avvio e basta...
		$adb->pquery("update $log_script_state set state = 0 , working_id = NULL,lastrun = ? where type = ?",Array(date('Y-m-d H:i:s',$time_end),$module));
	}
	else{
		//scrivo sul db la data di partenza dello script
		$res = Array($working_id,$module,date("Y-m-d H:i:s",$time_start));
		$adb->pquery('insert into '.$log_script_content.' (id,type,date_start) values ('.generateQuestionMarks($res).')',$res);		
		//scrivo sul db i dati sull'esecuzione dello script
		$duration = $time_end - $time_start;
		$duration_min = intval ($duration / 60);
		$duration_sec = $duration - $duration_min * 60;
		$duration_string = $duration_min."m:".$duration_sec."s";
		$res = Array(date("Y-m-d H:i:s",$time_end),$records['records_created'],$records['records_updated'],$records['records_deleted'],($records['records_created']+$records['records_updated']),$duration_string,$working_id);
		$adb->pquery('update '.$log_script_content.' 
			set date_end = ?,
			records_created = ?,
			records_updated = ?,
			records_deleted = ?,
			total_records = ?,
			duration = ? where id = ?'
			,$res);
		//scrivo sul db lo stato dello script
		$adb->pquery("update $log_script_state set state = 0 , working_id = NULL, lastrun = ? where type = ?",Array(date('Y-m-d H:i:s',$time_end),$module));
	}
	$global_log->stop('import');
	if ($log_active){
		echo "\n----------------------TOTAL TIME------------------------\n";
		echo $global_log->get_content();
		echo "----------------------TOTAL TIME------------------------\n";
	}
	$success_import = true;
}
?>