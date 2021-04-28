<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $adb, $table_prefix;
global $currentModule, $current_user;

$stuffid = intval($_REQUEST['stuffid']);
$chartsize = intval($_REQUEST['size']);

if($stuffid > 0) {
	$res = $adb->pquery("select chartid from {$table_prefix}_homecharts where stuffid = ?", array($stuffid));
	if ($res) {
		$chartid = $adb->query_result($res, 0, 'chartid');
	}

	$res = $adb->pquery("update {$table_prefix}_homestuff set size = ? where stuffid = ?", array($chartsize, $stuffid));

	// invalidate cache
	if ($chartid) {
		$res = $adb->pquery("update {$table_prefix}_chartscache set chart_file_home = '' where chartid = ?", array($chartid));
	}
}


?>