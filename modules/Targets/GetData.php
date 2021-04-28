<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@200009 crmv@202577 */

global $table_prefix;

if (isset($_REQUEST["only_limit"])) {
	$VTEP = VTEProperties::getInstance();
	$limit = $VTEP->getProperty('loadrelations.limit');
	echo $limit;
	exit();
} elseif (isset($_REQUEST["get_tm_total_count"])) {
	$VTEP = VTEProperties::getInstance();
	$limit = $VTEP->getProperty('masscreate.limit');
	
	$count = 0;

	if (isModuleInstalled('Telemarketing')) {
		$parentId = intval($_REQUEST['parent_id']);
		$type = getSingleFieldValue($table_prefix . "_campaign", 'campaigntype', 'campaignid', $parentId);
		if ($type === 'Telemarketing') {
			$RM = RelationManager::getInstance();
			$ids = array_filter(array_map('intval', $_REQUEST['ids'] ?? []));
			foreach ($ids as $id) {
				$count += count($RM->getRelatedIds('Targets', $id, ['Leads', 'Accounts', 'Contacts']));
			}
		}
	}
} elseif (isset($_REQUEST["list_type"])) {
	$VTEP = VTEProperties::getInstance();
	$limit = $VTEP->getProperty('loadrelations.limit');

	$targetid = intval($_REQUEST['return_id']);
	$cvModule = vtlib_purify($_REQUEST["list_type"]);
	$cvid = intval($_REQUEST["cvid"]);

	if ($cvid > 0) {
		$focus = CRMEntity::getInstance('Targets');
		$count = $focus->getCountList($targetid, $cvModule, $cvid);
	}
} elseif (isset($_REQUEST["reportid"])) {
	$VTEP = VTEProperties::getInstance();
	$limit = $VTEP->getProperty('loadrelations.limit');

	$targetid = intval($_REQUEST['return_id']);
	$reportid = intval($_REQUEST['reportid']);
	$reportModule = $_REQUEST["relatedmodule"];

	if ($reportid > 0) {
		$focus = CRMEntity::getInstance('Targets');
		$count = $focus->getCountReport($targetid, $reportModule, $reportid);
	}
}

if ($count >= 0 && $limit >= 0)
	echo $count . '###' . $limit;