<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@33448 crmv@55708 */
require_once('modules/SDK/src/CalendarTracking/CalendarTrackingUtils.php');

$record = vtlib_purify($_REQUEST['record']);
$type = $_REQUEST['type'];
global $currentModule;
$currentModule = getSalesEntityType($record);

/* if ($currentModule == 'ProjectTask') {
	if ($type == 'eject') {
		deleteTracking($record);
		echo 'FAILED';
	} else {
		echo 'SUCCESS';
	}
	exit;
} else */
if ($currentModule == 'HelpDesk') {
	switch ($type) {
		case 'start':
			if (getActiveTracked() !== false) {
				echo 'FAILED';
				exit;
			}
			$message = getTranslatedString('LBL_TRACKING_MSG_START', 'APP_STRINGS');
			break;
		case 'pause':
			$message = getTranslatedString('LBL_TRACKING_MSG_PAUSE', 'APP_STRINGS');
			break;
		case 'stop':
			$message = getTranslatedString('LBL_TRACKING_MSG_STOP', 'APP_STRINGS');
			break;
		case 'eject':
			deleteTracking($record);
			echo 'FAILED';
			exit;
			break;
	}
	if ($message == '') {
		echo 'SUCCESS';
		exit;
	} else {
		echo 'SUCCESSMESSAGE::'.$message;
		exit;
	}
} else {
	if ($type == 'eject') {
		deleteTracking($record);
		echo 'FAILED';
	} elseif (in_array($type,array('pause','stop'))) {
		echo 'SUCCESSMESSAGE::';
	} else {
		echo 'SUCCESS';
	}
	exit;
}
?>