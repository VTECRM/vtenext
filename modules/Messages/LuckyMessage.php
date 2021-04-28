<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $currentModule;
$record = vtlib_purify($_REQUEST['record']);
$folder = vtlib_purify($_REQUEST['folder']);
$account = vtlib_purify($_REQUEST['account']);
$focus = CRMEntity::getInstance($currentModule);
if (empty($record)) {
	$record = getListViewCheck($currentModule);
}
$record = $focus->getLuckyMessage($account,$folder,$record);
echo $record;
exit;
?>