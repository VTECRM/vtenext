<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@170283 */
function vtws_check_exists($id,$user) {
	global $adb, $table_prefix;
	list($prefix,$record) = explode('x',$id);
	$result = $adb->pquery("select deleted from {$table_prefix}_crmentity where crmid = ?", array(intval($record)));
	if ($result && $adb->num_rows($result) > 0) {
		$deleted = $adb->query_result($result,0,'deleted');
		if ($deleted == 0) return true;
	}
	return false;
}