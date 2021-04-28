<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

if ($_REQUEST['file'] == 'Folders') {
	global $currentModule, $app_strings, $mod_strings;
	$focus = CRMEntity::getInstance($currentModule);
	$smarty = new VteSmarty();
	$smarty->assign('MOD', $mod_strings);
	$smarty->assign('APP', $app_strings);
	$smarty->assign('FOCUS', $focus);
	$focus->setAccount($_REQUEST['account']);
	$current_account = $_REQUEST['account'];
}

$smarty->assign('DIV_DIMENSION', array('Folders'=>'0%','ListViewContents'=>'24%','PreDetailViewContents'=>'60%','DetailViewContents'=>'61%','TurboliftContents'=>'15%'));
$smarty->assign('VIEW', 'list');

try {
	($focus->force_check_imap_connection) ? $check = $focus->getZendMailStorageImap() : $check = true;	//crmv@125629
	if ($current_account != 'all' && $check) {	//crmv@125629
		$smarty->assign('FOLDERS', $focus->getFoldersList());
	}
} catch (Exception $e) {}

if ($_REQUEST['file'] == 'Folders') {
	$smarty->display("modules/Messages/Folders.tpl");
}
?>