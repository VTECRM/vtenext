<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@29190
require_once('include/Zend/Json.php');
global $adb, $table_prefix, $current_user;

$search = $_REQUEST['term'];
$params = urldecode($_REQUEST['params']);
if ($params != '') {
	$tmp = explode('&',$params);
	$params = array();
	if (!empty($tmp)) {
		foreach ($tmp as $t) {
			$t = explode('=',$t);
			$params[$t[0]] = $t[1];
		}
	}
}
$module = $params['module'];
$return = array();
if (!empty($params) && vtlib_isModuleActive($module) && $moduleInstance = Vtecrm_Module::getInstance($module)) {

	$search_fields = array();
	$fl = array();

	$query = "select * from ".$table_prefix."_entityname where modulename = ?";
	$result = $adb->pquery($query, array($module));
	$fieldsname = $adb->query_result($result,0,'fieldname');
	$tablename = $adb->query_result($result,0,'tablename');
	$entityidfield = $adb->query_result($result,0,'entityidfield');
	if(!(strpos($fieldsname,',') === false))
	{
		$fieldlists = explode(',',$fieldsname);
		foreach($fieldlists as $w => $c)
		{
			if (count($fl)) {
				$fl[] = "' '";
			}
			$wsfield = WebserviceField::fromQueryResult($adb,$adb->pquery('select * from '.$table_prefix.'_field where tabid = ? and fieldname = ?',array($moduleInstance->id,$c)),0);
			$fl[] = "COALESCE(".$wsfield->getTableName().'.'.$wsfield->getColumnName().", '')"; // crmv@176257
			$search_fields[] = $wsfield->getTableName().'.'.$wsfield->getColumnName();
		}
		$fieldsname = $adb->sql_concat($fl);
	} else {
		$wsfield = WebserviceField::fromQueryResult($adb,$adb->pquery('select * from '.$table_prefix.'_field where tabid = ? and fieldname = ?',array($moduleInstance->id,$fieldsname)),0);
		$fieldsname = $wsfield->getTableName().'.'.$wsfield->getColumnName();
		$search_fields[] = $wsfield->getTableName().'.'.$wsfield->getColumnName();
	}
	if ($module == 'Users') {
		$search_fields[] = $table_prefix.'_users.user_name';
	}

	if($module == 'Calendar') {
		$entity_id = 'activityid';
	} elseif ($module == 'Users') {
		$entity_id = 'id';
	} else {
		$entity_id = 'crmid';
	}
	$select_fields = $fieldsname.' as displayname';
	$where = array();
	// crmv@37463
	foreach ($search_fields as $search_field) {
		$where[] = $search_field."###".$search;
	}
	// crmv@37463e
	//crmv@31775
	if($module == 'Reports') {
		$where = $search;
	}
	//crmv@31775e
	$return = array($select_fields,$where);
}
echo Zend_Json::encode($return);
exit;
//crmv@29190e
?>