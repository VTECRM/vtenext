<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $currentModule, $current_user;
$modObj = CRMEntity::getInstance($currentModule);

$ajaxaction = $_REQUEST["ajxaction"];
if($ajaxaction == 'DETAILVIEW')
{
	// crmv@67410
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
	// crmv@67410e
} elseif($ajaxaction == "LOADRELATEDLIST" || $ajaxaction == "DISABLEMODULE"){
	require_once 'include/ListView/RelatedListViewContents.php';
//crmv@55961
} elseif($ajaxaction == "LOCKRECEIVINGNEWSLETTER"){
	$record = $_REQUEST['record'];
	$module = getSalesEntityType($record);
	$mode = $_REQUEST['mode'];
	
	$focus = CRMEntity::getInstance($module);
	$focus->retrieve_entity_info($record,$module);
	$email = $focus->column_fields[$modObj->email_fields[$module]['fieldname']];
	
	$modObj->lockReceivingNewsletter($email,$mode);
	$modObj->saveUnsubscribeChangelog($record, 'all', $mode == 'lock'); // crmv@151474
	exit;
}
//crmv@55961e
// crmv@195115
elseif ($ajaxaction == "STOPSENDING") {
	global $adb;
	$record = intval($_REQUEST['record']);
	
	$adb->pquery("delete from tbl_s_newsletter_queue where newsletterid = ? and status = ?",Array($record,'Scheduled'));
	
	$modObj->retrieve_entity_info_no_html($record,$currentModule);
	$modObj->column_fields['scheduled'] = '0';
	$modObj->mode = 'edit';
	$modObj->save($currentModule);
	
	echo 'SUCCESS';
	exit;
}
// crmv@195115e