<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Documents/Documents.php');

global $adb;
global $table_prefix;


$local_log =& LoggerManager::getLogger('index');
$folderid = $_REQUEST['record'];
$foldername = utf8RawUrlDecode($_REQUEST["foldername"]);
$folderdesc = utf8RawUrlDecode($_REQUEST["folderdesc"]);

if(isset($_REQUEST['savemode']) && $_REQUEST['savemode'] == 'Save')
{
	if($folderid == "")
	{
		// crmv@30967
		// check if it exists
		$folderinfo = getEntityFoldersByName($foldername, 'Documents');

		if (empty($folderinfo) && $foldername != '') {
			$sqlseq = "select max(sequence) as max from ".$table_prefix."_crmentityfolder where tabid = ?";
			$sequence = $adb->query_result($adb->pquery($sqlseq,array(getTabId('Documents'))),0,'max') + 1;
			$result = addEntityFolder('Documents', $foldername, $folderdesc, $current_user->id, '', $sequence);
			if (!$result) {
				echo "Failure";
			} else {
				header("Location: index.php?action=DocumentsAjax&file=ListView&mode=ajax&module=Documents");
			}
		} else {
			echo "DUPLICATE_FOLDERNAME";
		}
		// crmv@30967e

	} elseif($folderid != "") {

		// crmv@30967
		// check if creating a duplicate
		$folderinfo = getEntityFoldersByName($foldername, 'Documents');

		if (empty($folderinfo) || $folderinfo[0]['folderid'] == $folderid) {
			$result = editEntityFolder($folderid, $foldername);
			if(!$result) {
				echo "Failure";
			} else {
				echo 'Success';
			}
		} else {
			echo "DUPLICATE_FOLDERNAME";
		}
		// crmv@30967e

	}
}

?>