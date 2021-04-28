<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@62414
global $mod_strings, $app_strings, $currentModule, $current_user, $theme;
include_once('include/utils/utils.php');

$requestedfile = vtlib_purify($_REQUEST['requestedfile']);

if (is_numeric($requestedfile)) {
	$FS = FileStorage::getInstance();
	$attachment = $FS->getAttachmentId($requestedfile);
	if ($attachment !== null) {
		$requestedfile = "index.php?module=uploads&action=downloadfile&entityid={$requestedfile}&fileid={$attachment}";
	}
}

$smarty = new VteSmarty();
$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('MODULE', $currentModule);
$smarty->assign('REQUESTED_FILE', $requestedfile);

$smarty->display('modules/Messages/ViewerJS.tpl');