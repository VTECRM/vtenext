<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@31775
global $adb, $table_prefix;
$reportid = vtlib_purify($_REQUEST['report']);
if ($reportid != '' && $reportid != 0) {
	$result = $adb->pquery("SELECT {$table_prefix}_report.reportid FROM {$table_prefix}_report WHERE {$table_prefix}_report.reportid = ? AND {$table_prefix}_report.sharingtype = ?",array($reportid,'Public'));
	if (!$result || $adb->num_rows($result) == 0) {
		echo getTranslatedString('LBL_ERROR_PUBLIC_REPORT','Reports');
		exit;
	}
}
echo 'SUCCESS';
exit;
//crmv@31775e
?>