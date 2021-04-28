<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $currentModule;

$ajaxaction = $_REQUEST["ajxaction"];
// crmv@164122
if ($ajaxaction == "GETNOTIFICATION") {
	$modObj = CRMEntity::getInstance($currentModule);
	
	$record = intval($_REQUEST['record']);
	$setSeen = $_REQUEST['seen'];

	if ($record > 0 && $modObj->canUserEditRecord($record)) { // crmv@164122
		if ($setSeen != '') {
			// crmv@164122
			if ($setSeen  == '1') {
				$modObj->setRecordSeen($record);
			} else {
				$modObj->setRecordUnseen($record);
			}
			// crmv@164122e
		}

		// crmv@164122 - removed line
		
		$widgetInstance = $modObj->getWidget('DetailViewBlockCommentWidget');
		$unseenCount = $modObj->getUnseenCount();	//crmv@64325
		echo $unseenCount.':#:SUCCESS'.$widgetInstance->processItem($widgetInstance->getModel($record));
	} else {
		echo ':#:FAILURE';
	}

// crmv@43194e
}