<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@150024 */

global $php_max_execution_time;
set_time_limit($php_max_execution_time);

$targetid = intval($_REQUEST['return_id']);
$cvModule = vtlib_purify($_REQUEST["list_type"]);
$cvid = intval($_REQUEST["cvid"]);

if ($cvid > 0) {
	$focus = CRMEntity::getInstance('Targets');
	$focus->loadCVList($targetid, $cvModule, $cvid);
}

header("Location: index.php?module=Targets&action=TargetsAjax&file=CallRelatedList&ajax=true&record=".$targetid);