<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
// crmv@67410

global $currentModule, $current_user;
$modObj = CRMEntity::getInstance($currentModule);

$ajaxaction = $_REQUEST["ajxaction"];
if($ajaxaction == "DETAILVIEW")
{
	$crmid = $_REQUEST["recordid"];
	$tablename = $_REQUEST["tableName"];
	$fieldname = $_REQUEST["fldName"];
	$fieldvalue = utf8RawUrlDecode($_REQUEST["fieldValue"]);
	if($crmid != "")
	{
		$permEdit = isPermitted($currentModule, 'DetailViewAjax', $crmid);
		$permField = getFieldVisibilityPermission($currentModule, $current_user->id, $fieldname);
		
		if ($permEdit == 'yes' && $permField == 0) {
			$modObj->retrieve_entity_info($crmid,$currentModule);
			$modObj->column_fields[$fieldname] = $fieldvalue;
			
			$modObj->id = $crmid;
			$modObj->mode = "edit";
			$modObj->save($currentModule);
			
			// crmv@137993 - portal code moved to main class
			
			if($modObj->id != "") {
				echo ":#:SUCCESS";
			} else {
				echo ":#:FAILURE";
			}
		} else {
			echo ":#:FAILURE";
		}
	} else {
		echo ":#:FAILURE";
	}
} elseif($ajaxaction == "LOADRELATEDLIST" || $ajaxaction == "DISABLEMODULE"){
	require_once 'include/ListView/RelatedListViewContents.php';
//crmv@157490
} elseif($ajaxaction == "CHECKPORTALDUPLICATES"){
	$check = $modObj->checkPortalDuplicates($_REQUEST['email'],$_REQUEST['record']);
	echo ($check) ? 'DUPLICATED' : 'NOT_DUPLICATED';
//crmv@157490e
//crmv@161554
} elseif($ajaxaction == "GDPRANONYMIZE"){
	$crmid = vtlib_purify($_REQUEST['record']);
	$gdprws = GDPRWS::getInstance();
	$email = getSingleFieldValue($gdprws->emailFields[$currentModule]['tablename'], $gdprws->emailFields[$currentModule]['columnname'], $modObj->tab_name_index[$gdprws->emailFields[$currentModule]['tablename']], $crmid);
	$gdprws->applyContactDelete(array('module'=>$currentModule,'contactid'=>$crmid,'email'=>$email),null,null);
	echo 'SUCCESS';
//crmv@161554e
}