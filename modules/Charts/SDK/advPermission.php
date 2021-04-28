<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function chartsPermission($module, $actionname, $recordid = '') {
	global $adb, $table_prefix, $current_user;

	if ($module != 'Charts') return '';

	// editview e delete
	if (in_array($actionname, array('EditView', 'Delete', 'DetailViewAjax')) && !empty($recordid) && $current_user->is_admin == 'off') {

		require('user_privileges/user_privileges_'.$current_user->id.'.php');

		$q = $adb->pquery("select userid from {$table_prefix}_user2role inner join {$table_prefix}_users on {$table_prefix}_users.id = {$table_prefix}_user2role.userid inner join {$table_prefix}_role on {$table_prefix}_role.roleid = {$table_prefix}_user2role.roleid where {$table_prefix}_role.parentrole like '".$current_user_parent_role_seq."::%'",array());
		$subordinate_users = Array();
		for($i=0;$i<$adb->num_rows($query);$i++){
			$subordinate_users[] = $adb->query_result($query,$i,'userid');
		}
		if (count($subordinate_users) > 0) {
			$subusers_query = " OR {$table_prefix}_report.owner IN (".implode(',',$subordinate_users).")";
		}

		$q = "
			SELECT chartid
			FROM {$table_prefix}_charts
				INNER JOIN {$table_prefix}_report ON {$table_prefix}_report.reportid = {$table_prefix}_charts.reportid
				WHERE {$table_prefix}_charts.chartid = $recordid AND ({$table_prefix}_report.owner = {$current_user->id} $subusers_query)";
		$res = $adb->query($q);

		if ($res) {
			//crmv@94718
			$recordOwnerArr=getRecordOwnerId($recordid);
			foreach($recordOwnerArr as $type=>$owner_id) {
				$recOwnType=$type;
				$recOwnId=$owner_id;
			}
			
			if ($adb->num_rows($res) > 0) {
				return 'yes';
			} elseif (($recOwnType == 'Users' && $current_user->id == $recOwnId) || ($recOwnType == 'Groups' && in_array($recOwnId,$current_user_groups))) {
				return 'yes';
			} else {
				return 'no';
			}
			//crmv@94718e
		}
	}

	return '';
}

?>