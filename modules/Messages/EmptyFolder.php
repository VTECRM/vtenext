<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@48159 crmv@205127 */

global $current_user, $currentModule, $adb, $table_prefix;
$account = vtlib_purify($_REQUEST['account']);
$folder = $_REQUEST['folder'];

$focus = CRMEntity::getInstance($currentModule);
$folderSeparator = $focus->getFolderSeparator($account);

$focus->addToPropagationCron('empty', array('userid'=>$current_user->id,'account'=>$account,'folder'=>$folder), 5);

$adb->pquery("UPDATE {$table_prefix}_messages
	SET deleted = ?
	WHERE deleted = ? AND smownerid = ? AND account = ? AND (folder = ? OR folder like ?)",
array(1,0,$current_user->id,$account,$folder,$folder.$folderSeparator.'%'));

$focus->reloadCacheFolderCount($current_user->id,$account,$folder);

$adb->pquery("delete from {$table_prefix}_messages_folders where userid = ? and accountid = ? and globalname like ?", array($current_user->id,$account,$folder.$folderSeparator.'%'));

exit;