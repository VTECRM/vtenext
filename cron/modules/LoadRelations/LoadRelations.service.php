<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@200009 */

require_once('include/utils/LoadRelationsUtils.php');

// this is to run the process directly from the terminal
$importid = intval($_REQUEST['massid']);

$MUtils = LoadRelationsUtils::getInstance();

$r = true;
if ($importid > 0) {
	$r = $MUtils->processCron($importid);
} else {
	$r = $MUtils->processCron();
}

if (!$r) {
	echo "Error during the LoadRelations cron.\n";
}