<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@44609 */

global $app_strings;

$from_module = vtlib_purify($_REQUEST['from_module']);
$from_crmid = intval($_REQUEST['from_crmid']);
$to_module = vtlib_purify($_REQUEST['to_module']);
$to_crmid = intval($_REQUEST['to_crmid']);

if (isPermitted($from_module, 'Delete', $from_crmid) != 'yes') die($app_strings['LBL_PERMISSION']);;
if (isPermitted($to_module, 'EditView', $to_crmid) != 'yes') die($app_strings['LBL_PERMISSION']);;

$rm = RelationManager::getInstance();
$relatedIds = $rm->getRelatedIds($from_module, $from_crmid, array(), array(), false, true); // crmv@164120
if (!empty($relatedIds)) {
	foreach ($relatedIds as $mod => $ids) {
		$rm->relate($to_module, $to_crmid, $mod, $ids);
	}
}

$from_focus = CRMEntity::getInstance($from_module);
$from_focus->trash($from_module, $from_crmid);

die('SUCCESS');
?>