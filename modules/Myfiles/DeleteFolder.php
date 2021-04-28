<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Myfiles/Myfiles.php');
require_once('include/logging.php');
require_once('include/database/PearDatabase.php');

global $adb;
global $current_user;
global $table_prefix;
if($current_user->is_admin != 'on')
{
	echo 'NOT_PERMITTED';
	die;
}
else
{
	$local_log =& LoggerManager::getLogger('index');
	if(isset($_REQUEST['folderid']) && $_REQUEST['folderid'] != '')
		$folderId = $_REQUEST['folderid'];
	else
	{
		echo 'FAILURE';
		die;
	}
	if(isset($_REQUEST['deletechk']) && $_REQUEST['deletechk'] == 'true')
	{
		$query = "select myfilesid from ".$table_prefix."_myfiles INNER JOIN ".$table_prefix."_crmentity ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_myfiles.myfilesid WHERE ".$table_prefix."_myfiles.folderid = ? and ".$table_prefix."_crmentity.deleted = 0";
		$result = $adb->pquery($query,array($folderId));
		if($adb->num_rows($result) > 0)
		{
			echo 'FAILURE';
		}
		else
		{
			header("Location: index.php?action=MyfilesAjax&file=ListView&mode=ajax&module=Myfiles");
			exit;
		}
	}
	else
	{
		if ($folderId != 1)	deleteEntityFolder($folderId); //crmv@30967
		header("Location: index.php?action=MyfilesAjax&file=ListView&mode=ajax&module=Myfiles");
		exit;
	}
}
?>