<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
	
	function vtws_getchallenge($username){
		
		global $adb,$table_prefix;
		
		$user = CRMEntity::getInstance('Users');
		$userid = $user->retrieve_user_id($username);
		$authToken = uniqid();
		
		$servertime = time();
		$expireTime = time()+(60*5);
		
		//crmv@26686
//		$sql = "delete from vte_ws_userauthtoken where userid=?";
//		$adb->pquery($sql,array($userid));
		//crmv@26686e
		
		$sql = "insert into ".$table_prefix."_ws_userauthtoken(userid,token,expireTime) values (?,?,?)";
		$adb->pquery($sql,array($userid,$authToken,$expireTime));
		
		return array("token"=>$authToken,"serverTime"=>$servertime,"expireTime"=>$expireTime);
	}

?>