<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function do_import_users($time_start,$import_id=false,$dbobject = null) {
	global $log,$adb,$seq_log,$current_user,$mapping,$root_directory;
	global $external_code,$module,$table,$fields_auto_create,$fields_auto_update;
	global $where,$update_field,$additional_fields,$mapping_entity,$internal_code,$time_field;
	global $order_by,$fields_jump_update;
	global $override_query;
	if (is_null($dbobject) || empty($dbobject)) $dbobject = $adb;

	if (count($additional_fields)> 1) {
		$add_f = ",".implode(",",$additional_fields);
	} elseif(count($additional_fields)>0) {
		$add_f = ",".$additional_fields[0];
	}
	$res_tot = Array();
	$process = false;

	$sql="select ".implode(",",array_keys($mapping))." $add_f from $table $where $order_by";
	// override query
	if (!empty($override_query)) $sql = $override_query;

	$res = $dbobject->limitQuery($sql,0,1);
	if ($res && $dbobject->num_rows($res) == 1){
		$process = true;
	}
	if ($process){
		$import = new importer($module,$mapping,$external_code,$time_start,$fields_auto_create,$fields_auto_update,$table,$import_id,$fields_jump_update); // crmv@186024
		$res_tot = $import->go($dbobject->query($sql));
	}
	else{
		$res_tot = Array(
		   'records_created'=>0,
		   'records_updated'=>0,
		   'records_deleted'=>0,
		);
	}
	return $res_tot;
}
?>