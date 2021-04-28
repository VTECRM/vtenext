<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

$folderId = vtlib_purify($_REQUEST['folderid']);

if(isset($_REQUEST['idlist']) && $_REQUEST['idlist']!= '')
{
	$idArray = Array();
	$idArray = explode(':',$_REQUEST['idlist']);
	for($i = 0;$i < count($idArray);$i++) // crmv@30967
	{
		ChangeFolder($idArray[$i],$folderId);
	}
	die('SUCCESS'); // crmv@30967
}elseif(isset($_REQUEST['record']) && $_REQUEST['record']!= '')
{
	$id = vtlib_purify($_REQUEST["record"]);
	ChangeFolder($id,$folderId);
	die('SUCCESS'); // crmv@30967
}


/** To Change the Report to another folder
  * @param $reportId -- The report id
  * @param $folderId -- The folderid the which the report to be moved
  * @returns nothing
 */
function ChangeFolder($reportId,$folderId)
{
	global $adb,$table_prefix;
	$imovereportsql = "update {$table_prefix}_report set folderid=? where reportid=?";
	$adb->pquery($imovereportsql, array($folderId, $reportId));
}
