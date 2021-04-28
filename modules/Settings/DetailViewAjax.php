<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@92272 */

global $currentModule, $current_user;

$ajaxaction = $_REQUEST["ajxaction"];
if($ajaxaction == "DETAILVIEW")
{
	$crmid = $_REQUEST["recordid"];
	$tablename = $_REQUEST["tableName"];
	$fieldname = $_REQUEST["fldName"];
	$fieldvalue = utf8RawUrlDecode($_REQUEST["fieldValue"]); 
	
	if($crmid != "" && is_admin($current_user) && strpos($fieldname,'pm_') === 0)
	{
		require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
		$PMUtils = ProcessMakerUtils::getInstance();
		$PMUtils->edit($crmid,array(str_replace('pm_','',$fieldname)=>$fieldvalue));
		echo ":#:SUCCESS";
	} else {
		echo ":#:FAILURE";
	}
}