<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

 /**
  *		Plugin for search_engine for field assigned_user_id
  *
  */
if ( ! SEARCHENGINE_LOADED ) return false;
global $is_admin,$current_user,$default_charset,$adb;
include_once('include/utils/utils.php');
require_once ('user_privileges/sharing_privileges_' . $current_user->id . '.php');
require('user_privileges/requireUserPrivileges.php'); // crmv@39110
$srch_val = function_exists('iconv') ? @iconv("UTF-8",$default_charset,$search_engine['options']['input']) : $search_engine['options']['input']; // crmv@167702
$srch_val = mysql_real_escape_string($srch_val);
$mode =$_REQUEST['mode'];
$module =$_REQUEST['modulename'];
if ($is_admin == false && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[getTabid($module)] == 3 || $defaultOrgSharingPermission[getTabid($module)] == 0))
	$private = true;
switch($mode){
	case "T":
		$result = ($private)?get_current_user_access_groups($module,$srch_val):get_group_options($srch_val);
		if ($result && $adb->num_rows($result)>0){
			$cnt = 0;
			while($row = $adb->fetchByAssoc($result)){
				$res[$row['groupid']]=$row['groupname'];
				if ($cnt>20)
					break;
				$cnt++;
			}
		}
		break;
	case "U":
	default:
		$res = get_user_array(false,'Active','',($private)?'private':'',$srch_val);
		break;
}
if ($res){
	$search_engine['results'] = $res;
}
?>