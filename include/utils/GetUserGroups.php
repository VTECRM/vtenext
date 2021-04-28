<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@193294 optimized calls with request cache */

/** Class to retreive all the Parent Groups of the specified Group
*
*/
require_once('include/utils/UserInfoUtil.php');
require_once('include/utils/GetParentGroups.php');

class GetUserGroups { 

	public $user_groups = Array();
	//var $userRole='';

	/** to get all the parent vte_groups of the specified group
	* @params $groupId --> Group Id :: Type Integer
		* @returns updates the parent group in the varibale $parent_groups of the class
		*/
	function getAllUserGroups($userid)
	{
		global $adb,$log, $table_prefix;
		$log->debug("Entering getAllUserGroups(".$userid.") method...");
		
		//Retrieving from the user2grouptable
		$groups = $this->getGroupsForUser($userid);
		foreach ($groups as $now_group_id)
		{
			if(! in_array($now_group_id,$this->user_groups))
			{
				$this->user_groups[]=$now_group_id;
			}
		}

		// Getting the User Role
		$userRole = fetchUserRole($userid);
		
		//Retreiving from the vte_user2role
		$groups = $this->getGroupsForRole($userRole);
		foreach ($groups as $now_group_id)
		{
			if(! in_array($now_group_id,$this->user_groups))
			{
				$this->user_groups[]=$now_group_id;
			}
		}

		//Retrieving from the user2rs
		$groups = $this->getGroupsForRoleAndSub($userRole);
		foreach ($groups as $now_group_id)
		{
			if(! in_array($now_group_id,$this->user_groups))
			{
				$this->user_groups[]=$now_group_id;					
			}
		}
		
		foreach($this->user_groups as $grp_id)
		{
			$focus = new GetParentGroups();
			$focus->getAllParentGroups($grp_id);
			
			foreach($focus->parent_groups as $par_grp_id)
			{
				if(! in_array($par_grp_id,$this->user_groups))
				{
					$this->user_groups[]=$par_grp_id;
				}	
			}
		} 
		
		$log->debug("Exiting getAllUserGroups method...");	
	}
	
	protected function getGroupsForUser($userid) {
		global $adb, $table_prefix;
		
		static $cache = array();
		if (!isset($cache[$userid])) {
			$list = array();
			$query="select groupid from ".$table_prefix."_users2group where userid=?";
			$result = $adb->pquery($query, array($userid));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result, -1, false)) {
					$list[] = $row['groupid'];
				}
			}
			$cache[$userid] = $list;
		}
		return $cache[$userid];
	}
	
	protected function getGroupsForRole($userRole) {
		global $adb, $table_prefix;
		
		static $cache = array();
		if (!isset($cache[$userRole])) {
			$list = array();
			$query="select groupid from ".$table_prefix."_group2role where roleid=?";
			$result = $adb->pquery($query, array($userRole));
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result, -1, false)) {
					$list[] = $row['groupid'];
				}
			}
			$cache[$userRole] = $list;
		}
		return $cache[$userRole];
	}
	
	protected function getGroupsForRoleAndSub($userRole) {
		global $adb, $table_prefix;
		
		static $cache = array();
		if (!isset($cache[$userRole])) {
			$list = array();
			
			$parentRoles = getParentRole($userRole);
			$parentRolelist = array();
			foreach($parentRoles as $par_rol_id) {
				array_push($parentRolelist, $par_rol_id);		
			}
			array_push($parentRolelist, $userRole);
		
			$query="select groupid from ".$table_prefix."_group2rs where roleandsubid in (". generateQuestionMarks($parentRolelist) .")";
			$result = $adb->pquery($query, $parentRolelist);
			if ($result && $adb->num_rows($result) > 0) {
				while($row=$adb->fetchByAssoc($result, -1, false)) {
					$list[] = $row['groupid'];
				}
			}
			$cache[$userRole] = $list;
		}
		return $cache[$userRole];
	}
	
}