<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@150024 */

global $php_max_execution_time;
set_time_limit($php_max_execution_time);

$targetid = intval($_REQUEST['return_id']);
$reportid = intval($_REQUEST['reportid']);
$reportModule = $_REQUEST["relatedmodule"];

if ($reportid > 0) {
	$focus = CRMEntity::getInstance('Targets');
	$focus->loadReportList($targetid, $reportModule, $reportid);
}

header("Location: index.php?module=Targets&action=TargetsAjax&file=CallRelatedList&ajax=true&record=".$targetid);