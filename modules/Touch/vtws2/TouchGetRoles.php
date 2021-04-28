<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* retrieves roles  */

class TouchGetRoles extends TouchWSClass {

	function process(&$request) {
		global $adb, $table_prefix;
		
		$roles = array();
		$res = $adb->query(
			"select *
			from {$table_prefix}_role r
			inner join {$table_prefix}_role2profile rp on rp.roleid = r.roleid"
		);

		if ($res) {
			while ($row = $adb->fetchByAssoc($res, -1, false)) {
				$roles[] = $row;
			}
		}

		return $this->success(array('roles' => $roles, 'total'=>count($roles)));
	}
}
