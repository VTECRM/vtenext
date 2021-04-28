<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@47611 */

require_once('include/utils/utils.php');
require_once('include/logging.php');
require_once('modules/SLA/SLA.php');

// Get the list of Invoice for which Recurring is enabled.

global $adb, $table_prefix, $log, $current_user;
$log =& LoggerManager::getLogger('SLA');
$log->debug("invoked SLA");

//crmv@135193
if (SLA::$skip_notifications) {
	global $global_skip_notifications;
	$tmp_global_skip_notifications = $global_skip_notifications;
	$global_skip_notifications = true;
}
if (SLA::$hide_changelog) {
	global $global_hide_changelog;
	$tmp_global_hide_changelog = $global_hide_changelog;
	$global_hide_changelog = true;
}
//crmv@135193e

$sla_config_global = SLA::get_config();

$LVU = ListViewUtils::getInstance();

foreach ($sla_config_global as $module=>$sla_config){
	$fields = $fields_select = array();
	$query =  "SELECT tablename,fieldname,columnname FROM ".$table_prefix."_field WHERE tabid=? and presence in (0,2) and tablename <> ?";
	$params = Array(getTabId($module),$table_prefix.'_ticketcomments');
	$res = $adb->pquery($query,$params);
	if ($res){
		while ($row = $adb->fetchByAssoc($res,-1,false)){
			$fields[$row['fieldname']] = $row['tablename'].".".$row['columnname'];
			$fields_select[$row['fieldname']] = $row['columnname'];
		}
	}
	$current_user = CRMEntity::getInstance('Users');
	$current_user->id = Users::getActiveAdminId(); //crmv@142444
	$current_user->date_format = 'yyyy-mm-dd';
	$params = Array();
	$where = "and ({$fields['ended_sla']} = 0 OR {$fields['ended_sla']} IS NULL)"; // crmv@123658
	$list_query = replaceSelectQuery($LVU->getListQuery($module,$where),implode(",",$fields).",{$table_prefix}_crmentity.crmid")." order by {$table_prefix}_crmentity.modifiedtime asc"; //crmv@36796
	//echo $adb->convert2Sql($list_query,$adb->flatten_array($params));
	// retrieve the first 1000 oldest records
	$list_res = $adb->limitpQuery($list_query,0, 1000, $params);
	if ($list_res && $adb->num_rows($list_res) > 0){
		$startstlacalc = time(); //crmv@53467
		while ($row = $adb->fetchByAssoc($list_res,-1,false)){
			//crmv@53467 avoid overwrite
			$query_modtime = "SELECT modifiedtime FROM ".$table_prefix."_crmentity WHERE crmid = ".$row['crmid'];
			$res_modtime = $adb->query($query_modtime);
			$modifiedtime = $adb->query_result($res_modtime,0,'modifiedtime');
			$modtime = strtotime($modifiedtime);
			if ($modtime > $startstlacalc) {
				continue;
			}			
			//crmv@53467e avoid overwrite
			$focus = CRMEntity::getInstance($module);
			$focus->id = $row['crmid'];
			$focus->mode = 'edit';
			$focus->column_fields["record_id"] = $row['crmid'];
			$focus->column_fields["record_module"] = $module;
			foreach ($fields_select as $fieldname=>$columnname){
				$focus->column_fields[$fieldname] = $row[$columnname];
			}
			$focus->save($module);
		}
	}
}
$log->debug("end SLA procedure");

//crmv@135193
if (SLA::$skip_notifications) $global_skip_notifications = $tmp_global_skip_notifications;
if (SLA::$hide_changelog) $global_hide_changelog = $tmp_global_hide_changelog;
//crmv@135193e