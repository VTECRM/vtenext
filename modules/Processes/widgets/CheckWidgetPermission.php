<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@169362 */
function checkProcessesWidgetPermission($row) {
	require_once('modules/Settings/ProcessMaker/ProcessMakerUtils.php');
	$PMUtils = ProcessMakerUtils::getInstance();
	$resources = $PMUtils->getAdvancedPermissionsResources($_REQUEST['record']);
	return (!empty($resources));
}