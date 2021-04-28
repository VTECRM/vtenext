<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@95157 */

require_once('modules/Documents/Documents.php');
require_once('modules/Documents/storage/StorageBackendUtils.php');

if ($_REQUEST['act'] == 'updateDldCnt') {

	$crmid = intval($_REQUEST['file_id']);
	$SBU = StorageBackendUtils::getInstance();
	$SBU->incrementDownloadCount('Documents', $crmid);
	$SBU->saveDownloadChangelog('Documents', $crmid); // crmv@152087

} elseif ($_REQUEST['act'] == 'checkFileIntegrityDetailView') {	

	$crmid = intval($_REQUEST['noteid']);
	$SBU = StorageBackendUtils::getInstance();
	$integrity = $SBU->checkIntegrity('Documents', $crmid);
	
	switch ($integrity) {
		case 0:
			echo "file_available";
			break;
		case 1:
			echo "lost_integrity";
			break;
		case 2:
			echo "file_not_available";
			break;
		case 3:
			echo "internal_error";
			break;
		default:
			echo "unknown_error";
			break;
	}
	
}