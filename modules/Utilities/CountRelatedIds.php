<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43611 */
/* TODO: use several modules from */
global $current_user;

// source module (only 1 for now)
$modules_from = array($_REQUEST['module_from']);

// source crmids
$crmid_from = array_filter(array_map('intval', explode(':', $_REQUEST['crmid_from'])));

// destination module
$modules_to = array_filter(explode(':', $_REQUEST['modules_to']));

$rm = RelationManager::getInstance();

$ret = array();
foreach ($modules_from as $mfrom) {
	foreach ($crmid_from as $crmid) {
		$relids = $rm->countRelatedIds($mfrom, $crmid, $modules_to);
		$ret[$crmid] = $relids;
	}
}

echo Zend_Json::encode($ret);