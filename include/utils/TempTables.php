<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@63349 - temporary tables replacements */


class TmpUserTables extends SDKExtendableUniqueClass {

	public $tmpTable = '_tmp_users';
	
	public function __construct() {
		global $table_prefix;
		
		$this->tmpTable = $table_prefix.'_tmp_users';
	}
	
	// Clean the whole table
	public function cleanTmp() {
		global $adb, $table_prefix;
		
		$adb->query("DELETE FROM {$this->tmpTable}");
		return true;
	}
	
	// Clean only a specific user from the table
	public function cleanTmpForUser($userid) {
		global $adb, $table_prefix;
		
		$adb->pquery("DELETE FROM {$this->tmpTable} WHERE userid = ?", array($userid));
		return true;
	}
	
	public function getAllUsers() {
		global $adb, $table_prefix;
		
		$list = array();
		$r = $adb->pquery("SELECT id FROM {$table_prefix}_users WHERE status = ?", array('Active'));
		if ($r && $adb->num_rows($r) > 0) {
			$list = array();
			while ($row = $adb->FetchByAssoc($r, -1, false)) {
				$list[] = $row['id'];
			}
		}
		
		return $list;
	}
	
	// Get a list of non-admin users which may use the table
	public function getNonAdminUsers() {
		global $adb, $table_prefix;
		
		$list = array();
		$r = $adb->pquery("SELECT id FROM {$table_prefix}_users WHERE is_admin = ? AND status = ?", array('off', 'Active'));
		if ($r && $adb->num_rows($r) > 0) {
			$list = array();
			while ($row = $adb->FetchByAssoc($r, -1, false)) {
				$list[] = $row['id'];
			}
		}
		
		return $list;
	}
	
	// Get a list of admin users
	public function getAdminUsers() {
		global $adb, $table_prefix;
		
		$list = array();
		$r = $adb->pquery("SELECT id FROM {$table_prefix}_users WHERE is_admin = ? AND status = ?", array('on', 'Active'));
		if ($r && $adb->num_rows($r) > 0) {
			$list = array();
			while ($row = $adb->FetchByAssoc($r, -1, false)) {
				$list[] = $row['id'];
			}
		}
		
		return $list;
	}
	
	// Generate all the rows for a specified module
	public function generateTmpForUser($userid, $moduleHint = 'Accounts') {
		global $adb, $table_prefix;
		
		//$this->cleanTmpForUser($userid);
		// insert the privileges
		
		$userid = intval($userid);
		
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		//crmv@69557
		if (is_readable('user_privileges/sharing_privileges_'.$userid.'.php')) {
			require('user_privileges/sharing_privileges_'.$userid.'.php');
		} else{
			return false;
		}
		//crmv@69557e
		
		$user = CRMEntity::getInstance('Users');
		$user->retrieveCurrentUserInfoFromFile($userid);
		$user->id = $userid;
		
		if (empty($current_user_parent_role_seq)) {
			$user_role = $user->column_fields['roleid'];
			$user_role_info = getRoleInformation($user_role);
			$current_user_parent_role_seq = $user_role_info[$user_role][1];
		}
		if (empty($current_user_groups)) {
			$userGroupFocus = new GetUserGroups();
			$userGroupFocus->getAllUserGroups($user->id);
			$current_user_groups = $userGroupFocus->user_groups;
		}
		
		$inst = CRMEntity::getInstance($moduleHint);
		
		if (method_exists($inst, 'getNonAdminAccessQuery')) {
			$query = $inst->getNonAdminAccessQuery(null, $user, $current_user_parent_role_seq, $current_user_groups);
			$query = preg_replace('/^select id/', "select $userid as id, id as subuserid", $query);
		
			$sql = "INSERT INTO {$this->tmpTable} (userid, subuserid) $query";
			$r = $adb->query($sql);
		}
		
		return (!!$r);
		
	}
	
	// Generate the whole table
	public function generateTmp()  {
		global $adb, $table_prefix;
		
		// clear the table
		$this->cleanTmp();
		
		// get all the users
		$users = $this->getAllUsers();
		
		if ($users) {
			foreach ($users as $userid) {
				// insert the privileges
				$this->generateTmpForUser($userid);
			}
		}
		
		return true;
	}
	
	public function hasSubUser($userid, $subuserid) {
		global $adb, $table_prefix;
		
		$has = false;
		$ret = $adb->pquery("SELECT COUNT(*) AS cnt FROM {$this->tmpTable} WHERE userid = ? AND subuserid = ?", array($userid, $subuserid));
		if ($ret) {
			$has = ($adb->query_result_no_html($ret, 0, 'cnt') > 0);
		}
		return $has;
	}
	
}

/**
 * This class handles the new table for user permissions
 */
class TmpUserModTables extends TmpUserTables {
	
	public $tmpTable = '_tmp_users_mod';
	
	public function __construct() {
		global $table_prefix;
	
		parent::__construct();
		$this->tmpTable = $table_prefix.'_tmp_users_mod';
	}
	
	// Clean only a specific module from the table
	public function cleanTmpForModule($module) {
		global $adb, $table_prefix;
		
		$tabid = getTabid2($module); // crmv@127944
		$adb->pquery("DELETE FROM {$this->tmpTable} WHERE tabid = ?", array($tabid));
		return true;
	}
	
	// Clean only a module and a user from the table
	public function cleanTmpForModuleUser($module, $userid) {
		global $adb, $table_prefix;
		
		$tabid = getTabid2($module); // crmv@127944
		$adb->pquery("DELETE FROM {$this->tmpTable} WHERE userid = ? AND tabid = ?", array($userid, $tabid));
		return true;
	}
	
	public function cleanTmpForUser($userid) {
		global $adb, $table_prefix;
		
		$adb->pquery("DELETE FROM {$this->tmpTable} WHERE userid = ?", array($userid));
		return true;
	}
	
	// Get a list of modules which require rows in the table
	public function getTmpModulesForUser($userid) {
		global $adb, $table_prefix, $current_user;
		
		// uses the $userid var
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		require('user_privileges/sharing_privileges_'.$userid.'.php');
	
		$list = false;
	
		if ($is_admin == false && 
			$profileGlobalPermission[1] == 1 && 
			$profileGlobalPermission[2]	== 1 
		) {
		
			// first get a list of active modules
			$r = $adb->query("SELECT tabid, name FROM {$table_prefix}_tab WHERE presence = 0");
			if ($r && $adb->num_rows($r) > 0) {
				$list = array();
				while ($row = $adb->FetchByAssoc($r, -1, false)) {
					$tabid = $row['tabid'];
					if ($defaultOrgSharingPermission[$tabid] == 3 || $defaultOrgSharingPermission[$tabid] == 8) { //crmv@160797
						$list[] = $row['name'];
					}
				}
			}
		}
				
		return $list;
	}
	
	// Generate the rows for the specified module and user
	public function generateTmpForModuleUser($module, $userid) {
		global $adb, $table_prefix;
		
		$userid = intval($userid);
		$tabid = getTabid2($module); // crmv@127944
		if (!$tabid) return false;
		
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		require('user_privileges/sharing_privileges_'.$userid.'.php');
		
		$user = CRMEntity::getInstance('Users');
		$user->retrieveCurrentUserInfoFromFile($userid);
		$user->id = $userid;
		
		if (empty($current_user_parent_role_seq)) {
			$user_role = $user->column_fields['roleid'];
			$user_role_info = getRoleInformation($user_role);
			$current_user_parent_role_seq = $user_role_info[$user_role][1];
		}
		if (empty($current_user_groups)) {
			$userGroupFocus = new GetUserGroups();
			$userGroupFocus->getAllUserGroups($user->id);
			$current_user_groups = $userGroupFocus->user_groups;
		}
		
		$inst = CRMEntity::getInstance($module);
		
		if (method_exists($inst, 'getNonAdminAccessQuery')) {
			$query = $inst->getNonAdminAccessQuery($module, $user, $current_user_parent_role_seq, $current_user_groups);
			$query = preg_replace('/^select id/', "select $userid as id, $tabid as tabid, id as subuserid", $query);
		
			$sql = "INSERT INTO {$this->tmpTable} (userid, tabid, subuserid) $query";
			$r = $adb->query($sql);
		}
		
		return (!!$r);
	}
	
	// Generate all the rows for a specified module
	public function generateTmpForUser($userid, $moduleHint = null) { // crmv@146653
		global $adb, $table_prefix;
		
		$this->cleanTmpForUser($userid);
		$mods = $this->getTmpModulesForUser($userid);
		if (is_array($mods)) {
			foreach ($mods as $module) {
				// insert the privileges
				$this->generateTmpForModuleUser($module, $userid);
			}
		}
		
		return true;
	}
	
	// Generate all the rows for a specified module
	public function generateTmpForModule($module) {
		global $adb, $table_prefix;
		
		$this->cleanTmpForModule($module);
		$users = $this->getNonAdminUsers();
		
		if ($users) {
			foreach ($users as $userid) {
				// insert the privileges
				$this->generateTmpForModuleUser($module, $userid);
			}
		}
		
		return true;
	}
	
	// Generate the whole table
	public function generateTmp()  {
		global $adb, $table_prefix;
		
		// clear the table
		$this->cleanTmp();
		
		// get the non admin users
		$users = $this->getNonAdminUsers();
		
		if ($users) {
			foreach ($users as $userid) {
				// get the modules for the user
				$mods = $this->getTmpModulesForUser($userid);
				if (is_array($mods)) {
					foreach ($mods as $module) {
						// insert the privileges
						$this->generateTmpForModuleUser($module, $userid);
					}
				}
			}
		}
		
		// and for admin users, generate the messages
		$users = $this->getAdminUsers();
		if ($users) {
			foreach ($users as $userid) {
				// insert the privileges
				$this->generateTmpForModuleUser('Messages', $userid);
			}
		}
		
		return true;
	}

	public function hasSubUser($userid, $subuserid, $module = null) { // crmv@146653
		global $adb, $table_prefix;
		
		$has = false;
		$tabid = getTabid2($module); // crmv@127944
		
		$ret = $adb->pquery("SELECT COUNT(*) AS cnt FROM {$this->tmpTable} WHERE userid = ? AND tabid = ? AND subuserid = ?", array($userid, $tabid, $subuserid));
		if ($ret) {
			$has = ($adb->query_result_no_html($ret, 0, 'cnt') > 0);
		}
		return $has;
	}
	
}

/**
 * This class handles the table for the calendar permissions
 */
class TmpUserCalTables extends TmpUserModTables {

	public $tmpTable = '_tmp_users_cal';
	
	public function __construct() {
		global $table_prefix;
		
		parent::__construct();
		$this->tmpTable = $table_prefix.'_tmp_users_cal';
	}
	
	// Retrieves only the Calendar and Events modules
	public function getTmpModulesForUser($userid) {
		global $adb, $table_prefix;
		
		// uses the $userid var
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		require('user_privileges/sharing_privileges_'.$userid.'.php');
	
		$list = false;
		$modNames = array('Calendar', 'Events');
	
		if ($is_admin == false && 
			$profileGlobalPermission[1] == 1 && 
			$profileGlobalPermission[2]	== 1 
		) {
		
			// first get a list of active modules
			$r = $adb->pquery("SELECT tabid, name FROM {$table_prefix}_tab WHERE presence = 0 AND name in (".generateQuestionMarks($modNames).")", $modNames);
			if ($r && $adb->num_rows($r) > 0) {
				$list = array();
				while ($row = $adb->FetchByAssoc($r, -1, false)) {
					$tabid = $row['tabid'];
					// crmv@99203 - remove condition
					//if ($defaultOrgSharingPermission[$tabid] == 3) {
						$list[] = $row['name'];
					//}
					// crmv@99203e
				}
			}
		}
		
		return $list;
	}
	
	// Generate the rows for the specified module and user
	public function generateTmpForModuleUser($module, $userid) {
		global $adb, $table_prefix;
		
		$userid = intval($userid);
		$tabid = getTabid2($module); // crmv@127944
		
		require('user_privileges/requireUserPrivileges.php'); // crmv@39110
		require('user_privileges/sharing_privileges_'.$userid.'.php');
		
		$user = CRMEntity::getInstance('Users');
		$user->retrieveCurrentUserInfoFromFile($userid);
		$user->id = $userid;
		
		if (empty($current_user_parent_role_seq)) {
			$user_role = $user->column_fields['roleid'];
			$user_role_info = getRoleInformation($user_role);
			$current_user_parent_role_seq = $user_role_info[$user_role][1];
		}
		if (empty($current_user_groups)) {
			$userGroupFocus = new GetUserGroups();
			$userGroupFocus->getAllUserGroups($user->id);
			$current_user_groups = $userGroupFocus->user_groups;
		}
		
		$sharedCol = 'SHARED';
		$adb->format_columns($sharedCol);
		
		$inst = CRMEntity::getInstance($module);
		
		$query = $inst->getNonAdminAccessQuery($module, $user, $current_user_parent_role_seq, $current_user_groups);
		
		// first populate it with all zeroes
		$query = preg_replace('/^select id/', "select $userid as id, $tabid as tabid, id as subuserid, 0 as $sharedCol", $query);
		$sql = "INSERT INTO {$this->tmpTable} (userid, tabid, subuserid, $sharedCol) $query";
		$r = $adb->query($sql);
		
		// now replace the 0s with 1s
		if ($adb->isMysql()) {
			// nothing for the moment
			/*$query = 
				"REPLACE INTO {$this->tmpTable} 
					SELECT $userid as userid, $tabid as tabid, userid as subuserid, 1 as $sharedCol 
					FROM {$table_prefix}_sharedcalendar 
					WHERE sharedid = $userid";*/
			//$result = $adb->query($query);
		} else {
			$query = 
				"INSERT INTO {$this->tmpTable} 
					SELECT $userid as userid, $tabid as tabid, userid as subuserid, 1 as $sharedCol 
					FROM {$table_prefix}_sharedcalendar 
					WHERE sharedid = $userid 
						AND not exists (select userid from {$this->tmpTable} tt where tt.userid = $userid AND tt.tabid = $tabid AND tt.subuserid = {$table_prefix}_sharedcalendar.userid)";
			$result = $adb->query($query);
		}
		
		//crmv@17001 - add missing users
		$res = $adb->query("SELECT id FROM {$table_prefix}_users WHERE id NOT IN (SELECT subuserid FROM {$this->tmpTable} WHERE userid = $userid AND tabid = $tabid)");
		if ($res && $adb->num_rows($res)>0) {
			while($row=$adb->fetchByAssoc($res, -1, false)) {
				$adb->pquery("insert into {$this->tmpTable} (userid, tabid, subuserid, $sharedCol) values ($userid, $tabid, ?,?)",array($row['id'], 2));
			}
		}
		//crmv@17001e
		
		//crmv@42775
		if ($adb->isMysql()) {
			$query = 
				"UPDATE {$this->tmpTable} t
				INNER JOIN {$table_prefix}_sharedcalendar s ON t.userid = $userid AND t.tabid = $tabid AND s.userid = t.subuserid AND s.sharedid = $userid
				SET t.$sharedCol = 1
				WHERE t.$sharedCol = 2";
		} elseif ($adb->isOracle()) {
			$query = 
				"UPDATE {$this->tmpTable} t
					SET t.$sharedCol = 1
					WHERE t.$sharedCol = 2
					AND EXISTS
					(SELECT s.userid
						FROM {$table_prefix}_sharedcalendar s WHERE t.userid = $userid AND t.tabid = $tabid AND s.userid = t.subuserid AND s.sharedid = $userid
					)";
		} elseif ($adb->isMssql()) {
			// TODO: Not tested!
			$query = 
				"UPDATE t
				SET t.$sharedCol = 1
				FROM {$this->tmpTable} t
				INNER JOIN {$table_prefix}_sharedcalendar s ON t.userid = $userid AND t.tabid = $tabid AND s.userid = t.subuserid AND s.sharedid = $userid
				WHERE t.$sharedCol = 2";
		}
		$result = $adb->query($query);
		//crmv@42775e
		
		//crmv@25593 - invitees
		$query = "select {$table_prefix}_crmentity.smownerid
				from {$table_prefix}_activity
				inner join {$table_prefix}_crmentity on {$table_prefix}_crmentity.crmid = {$table_prefix}_activity.activityid
				inner join {$table_prefix}_groups on {$table_prefix}_groups.groupid = {$table_prefix}_crmentity.smownerid
				inner join (SELECT activityid FROM {$table_prefix}_invitees WHERE inviteeid = ? AND activityid > 0) t on t.activityid = {$table_prefix}_activity.activityid
				WHERE deleted = 0
				GROUP BY {$table_prefix}_crmentity.smownerid";
		$res = $adb->pquery($query,array($userid));
		if ($res && $adb->num_rows($res)>0) {
			while($row=$adb->fetchByAssoc($res, -1, false)) {
				// crmv@28028 insert ignore for oracle
				if ($adb->isOracle()) {
					$par = array($row['smownerid'],3, $row['smownerid']);
					$adb->pquery(
						"insert into {$this->tmpTable} (userid, tabid, subuserid, $sharedCol) ($sharedCol,id) 
							select $userid as userid, $tabid as tabid, ?, ? from dual where not exists (select subuserid from {$this->tmpTable} where userid = $userid AND $tabid = $tabid AND {$this->tmpTable}.subuserid = ?)", $par);
				} elseif ($adb->isMysql()) {
					$adb->pquery("insert ignore into {$this->tmpTable} (userid, tabid, subuserid, $sharedCol) values ($userid, $tabid, ?,?)",array($row['smownerid'], 3));
				} else {
					$adb->pquery("insert into {$this->tmpTable} (userid, tabid, subuserid, $sharedCol) values ($userid, $tabid, ?,?)",array($row['smownerid'], 3));
				}
				// crmv@28028e
			}
		}
		//crmv@25593e
		
		return true;
	}
	
}


class TmpUserModRelTables extends TmpUserModTables {
	public $tmpTable = '_tmp_users_mod_rel';
	
	public function __construct() {
		global $table_prefix;
	
		parent::__construct();
		$this->tmpTable = $table_prefix.'_tmp_users_mod_rel';
	}
		
	public function cleanTmpForModuleUserId($module, $relmodule, $userid, $id) {
		global $adb, $table_prefix;
		
		$tabid = getTabid2($module); // crmv@127944
		$reltabid = getTabid2($relmodule); // crmv@127944
		$adb->pquery("DELETE FROM {$this->tmpTable} WHERE userid = ? AND tabid = ? AND reltabid = ? AND parentid = ?", array($userid, $tabid,$reltabid, $id));
		return true;
	}

	public function getJoinCondition($module, $relmodule, $userid, $id, $extColumn = null, $extColumn2 = null, $alias = '') {
		$tableName = $this->tmpTable;
		if (empty($alias)) $alias = $tableName;
		
		$id = intval($id);
		$userid = intval($userid);
		$tabid = intval(getTabid2($module)); // crmv@127944
		$reltabid = intval(getTabid2($relmodule)); // crmv@127944
		
		$sql = "$alias.userid = $userid AND $alias.tabid = $tabid AND $alias.reltabid = $reltabid AND $alias.parentid = $id";
		if ($extColumn) {
			$sql .= " AND $alias.crmid = $extColumn";
		}
		if ($extColumn2) {
			$sql .= " AND $alias.relcrmid = $extColumn2";
		}
		return $sql;
	}

}