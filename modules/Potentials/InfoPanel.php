<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@44187 crmv@44323 */

global $adb, $table_prefix;
global $currentModule, $current_user;

$focus = CRMEntity::getInstance($currentModule);
$record = intval($_REQUEST['record']);

if ($record > 0) {
	$focus->retrieve_entity_info($record, $currentModule);
	$focus->id = $record;
}

echo $focus->getExtraDetailBlock();
?>