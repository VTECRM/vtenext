<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $currentModule, $adb, $table_prefix, $current_account, $current_folder;
$idlist = getListViewCheck($currentModule);
$action = vtlib_purify($_REQUEST['massaction']);
$current_account = vtlib_purify($_REQUEST['account']);
$current_folder = vtlib_purify($_REQUEST['folder']);
$focus = CRMEntity::getInstance($currentModule);
if(!empty($idlist)) {
	switch ($action) {
		case 'Unseen':
			$focus->massSetFlag('seen',0,$idlist);
			break;
		case 'Seen':
			$focus->massSetFlag('seen',1,$idlist);
			break;
		case 'Flagged':
			$focus->massSetFlag('flagged',1,$idlist);
			break;
		case 'Unflagged':
			$focus->massSetFlag('flagged',0,$idlist);
			break;
	}
}
$rstart = "&start=".vtlib_purify($_REQUEST['start']).'&load_all=true';	//crmv@48307
$return_module = $currentModule;
$return_action = 'ListView';
$parenttab = getParentTab();
header("location: index.php?module=$return_module&action={$return_module}Ajax&file=$return_action&ajax=true&start=$rstart&parenttab=$parenttab&account=".$_REQUEST['account']."&folder=".$_REQUEST['folder']."&thread=".$_REQUEST['thread']);
?>