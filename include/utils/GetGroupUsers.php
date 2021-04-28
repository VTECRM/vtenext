<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* Class to retreive all the vte_users present in a group */

/* crmv@46552 crmv@158559 crmv@193294 */

require_once('include/utils/UserInfoUtil.php');
require_once('include/utils/GetParentGroups.php');

class GetGroupUsers { 

	public $group_users = array();
	public $group_subgroups = array();

	/** to get all the vte_users and vte_groups of the specified group
	 * @params $groupId --> Group Id :: Type Integer
	 * @params $only_active --> Filter only active users :: Type Boolean
	 * @params $recursively --> Search users in roles, roles and subordinates and in subgroups recursively :: Type Boolean (if false it returns only users directly included in the group)
	 * @returns the vte_users present in the group in the variable $parent_groups of the class
	 * @returns the sub vte_groups present in the group in the variable $group_subgroups of the class
	 */
	function getAllUsersInGroup($groupid, $only_active=false, $recursively=true)
	{
		global $adb,$log, $table_prefix;
		$log->debug("Entering getAllUsersInGroup(".$groupid.") method...");
		
		if ($only_active) {
			$inactive_users = $this->getInactiveUsers();
		} else {
			$inactive_users = array();
		}

		//Retreiving from the user2grouptable
		$subusers = $this->getSubUsers($groupid);
		foreach($subusers as $now_user_id)
		{
			if(!in_array($now_user_id,$inactive_users) && !in_array($now_user_id,$this->group_users))
			{
				$this->group_users[]=$now_user_id;
			}
		}

		if (!$recursively) return;

		//Retreiving from the vte_group2role
		$subroles = $this->getSubRoles($groupid);
		foreach($subroles as $now_role_id)
		{
			$now_role_users=array();
			$now_role_users=getRoleUsers($now_role_id);
				
			foreach($now_role_users as $now_role_userid => $now_role_username)
			{
				if(!in_array($now_role_userid,$inactive_users) && !in_array($now_role_userid,$this->group_users)) //crmv@66487
				{
					$this->group_users[]=$now_role_userid;
				}
			}
		}

		//Retreiving from the vte_group2rs
		$subrs = $this->getSubRolesAndSub($groupid);
		foreach($subrs as $now_rs_id)
		{
			$now_rs_users=getRoleAndSubordinateUsers($now_rs_id);
			foreach($now_rs_users as $now_rs_userid => $now_rs_username)
			{
				if(!in_array($now_rs_userid,$inactive_users) && !in_array($now_rs_userid,$this->group_users)) //crmv@66487
				{
					$this->group_users[]=$now_rs_userid;
				}
			}
		}

		//Retreving from group2group
		$subgroups = $this->getSubGroups($groupid);
		foreach($subgroups as $now_grp_id)
		{
			$focus = new GetGroupUsers();
			$focus->getAllUsersInGroup($now_grp_id,$only_active);
			$now_grp_users=$focus->group_users;
			$now_grp_grps=$focus->group_subgroups;
			if(! array_key_exists($now_grp_id,$this->group_subgroups))
			{
				$this->group_subgroups[$now_grp_id]=$now_grp_users;
			}
				
			foreach($focus->group_users as $temp_user_id)
			{
				if(!in_array($temp_user_id,$inactive_users) && !in_array($temp_user_id,$this->group_users))	//crmv@66487
				{
					$this->group_users[]=$temp_user_id;
				}
			}

			foreach($focus->group_subgroups as $temp_grp_id => $users_array)
			{
				if(! array_key_exists($temp_grp_id,$this->group_subgroups))
				{
					$this->group_subgroups[$temp_grp_id]=$focus->group_users;
				}
			}
		}

		$log->debug("Exiting getAllUsersInGroup method...");
	}
	
	
	protected function getInactiveUsers() {
		global $adb, $table_prefix;
		
		static $inactive_users = null;

		if (is_null($inactive_users)) {
			$inactive_users = array();
			$result = $adb->pquery("SELECT id FROM {$table_prefix}_users WHERE status = ?",array('Inactive'));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result, -1, false)) {
					$inactive_users[] = $row['id'];
				}
			}
		}
		return $inactive_users;
	}
	
	protected function getSubUsers($groupid) {
		global $adb, $table_prefix;
		
		static $cache = array();
		if (!isset($cache[$groupid])) {
			$list = array();
			$query="select userid from ".$table_prefix."_users2group where groupid=?";
			$result = $adb->pquery($query, array($groupid));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result, -1, false)) {
					$list[] = $row['userid'];
				}
			}
			$cache[$groupid] = $list;
		}
		return $cache[$groupid];
	}
	
	protected function getSubRoles($groupid) {
		global $adb, $table_prefix;
		
		static $cache = array();
		if (!isset($cache[$groupid])) {
			$list = array();
			$query="select roleid from ".$table_prefix."_group2role where groupid=?";
			$result = $adb->pquery($query, array($groupid));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result, -1, false)) {
					$list[] = $row['roleid'];
				}
			}
			$cache[$groupid] = $list;
		}
		return $cache[$groupid];
	}
	
	
	protected function getSubRolesAndSub($groupid) {
		global $adb, $table_prefix;
		
		static $cache = array();
		if (!isset($cache[$groupid])) {
			$list = array();
			$query="select roleandsubid from ".$table_prefix."_group2rs where groupid=?";
			$result = $adb->pquery($query, array($groupid));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result, -1, false)) {
					$list[] = $row['roleandsubid'];
				}
			}
			$cache[$groupid] = $list;
		}
		return $cache[$groupid];
	}
	
	protected function getSubGroups($groupid) {
		global $adb, $table_prefix;
		
		static $cache = array();
		if (!isset($cache[$groupid])) {
			$list = array();
			$query="select containsgroupid from ".$table_prefix."_group2grouprel where groupid=?";
			$result = $adb->pquery($query, array($groupid));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result, -1, false)) {
					$list[] = $row['containsgroupid'];
				}
			}
			$cache[$groupid] = $list;
		}
		return $cache[$groupid];
	}
	
}