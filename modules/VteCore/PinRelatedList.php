<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

$mode = vtlib_purify($_REQUEST['mode']);
$module = vtlib_purify($_REQUEST['module']);
$relmodule = vtlib_purify($_REQUEST['relmodule']);
$related = vtlib_purify($_REQUEST['related']);
$related = explode('_',$related);
$label = $related[1];

global $adb, $table_prefix, $current_user;
$result = $adb->pquery("select relation_id from {$table_prefix}_relatedlists where tabid = ? and related_tabid = ?",array(getTabid($module),getTabid($relmodule)));
if ($result) {
	$relation_id = $adb->query_result($result,0,'relation_id');
	if ($mode == 'pin') {
		$adb->pquery("insert into {$table_prefix}_relatedlists_pin (userid,relation_id) values (?,?)",array($current_user->id,$relation_id));
	} elseif ($mode == 'unPin') {
		$adb->pquery("delete from {$table_prefix}_relatedlists_pin where userid = ? and relation_id = ?",array($current_user->id,$relation_id));
	}
}
exit;