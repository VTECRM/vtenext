<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@44179 */

global $currentModule;
$status = 'SUCCESS';
$record = vtlib_purify($_REQUEST['record']);
$flag = vtlib_purify($_REQUEST['flag']);
$value = vtlib_purify($_REQUEST['value']);
$focus = CRMEntity::getInstance($currentModule);
$focus->id = $record;
$focus->retrieve_entity_info($record, $currentModule);
if ($flag == 'delete') {
	$focus->trash($currentModule,$record);
} else {
	$focus->setFlag($flag,$value) ? $status = 'SUCCESS' : $status = 'ERROR';
}
echo $status;
exit;
?>