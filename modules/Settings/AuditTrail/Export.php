<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@164355 crmv@202301 - moved here */

require_once('modules/Settings/AuditTrail.php');
require_once('modules/Settings/AuditTrail/Extractor.php');

global $current_user;

if (!is_admin($current_user)) die('Not authorized');

$userid = intval($_REQUEST['userid']);
$interval = $_REQUEST['interval'];

// get valid dates for the time interval
if ($interval == 'custom') {
	$dateStart = getValidDBInsertDateValue($_REQUEST['date_start']);
	$dateEnd = getValidDBInsertDateValue($_REQUEST['date_end']);
} else {
	$CU = CRMVUtils::getInstance();
	$int = $CU->getTimeIntervals(['labels' => false, 'display_dates' => false, 'dates' => ['past', 'until_today', 'around_today']]);
	
	if (array_key_exists($interval, $int)) {
		$dateStart = $int[$interval]['from'];
		$dateEnd = $int[$interval]['to'];
	} else {
		$dateStart = $dateEnd = '';
	}
}


$config = array(
	'userid' => $userid,
	'from' => $dateStart,
	'to' => $dateEnd,
);

$ATE = new AuditTrailExtractor($config);

$history = $ATE->extract();

$ATE->exportXls($history);