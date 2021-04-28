<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@59094 */

global $currentModule;

$focus = CRMEntity::getInstance('Messages');
$focus->id = vtlib_purify($_REQUEST['record']);
$focus->retrieve_entity_info(vtlib_purify($_REQUEST['record']), $currentModule);

$data = $focus->fetchBody(); // crmv@166575

echo 'SUCCESS::';
if (!empty($data['other'])) {
	echo 'ATTACHMENTS';
}
exit();
?>