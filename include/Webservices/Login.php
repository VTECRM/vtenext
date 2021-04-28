<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@26686 crmv@56023 */
	
function vtws_login($username,$pwd){
	
	$user = CRMEntity::getInstance('Users');
	$userId = $user->retrieve_user_id($username);
	$user->id = $userId;
	
	if ($user->checkBannedLogin()) {
		throw new WebServiceException(WebServiceErrorCode::$IPBANNED,"Ip banned because too many unsuccessfully logins");
	}

	$token = vtws_getActiveToken($userId);
	if($token == null || empty($token)){
		throw new WebServiceException(WebServiceErrorCode::$INVALIDTOKEN,"Specified token is invalid or expired");
	}
	
	$accessKey = vtws_getUserAccessKey($userId);
	if($accessKey == null){
		throw new WebServiceException(WebServiceErrorCode::$ACCESSKEYUNDEFINED,"Access key for the user is undefined");
	}
	
	$check_access_key = false;
	foreach($token as $t) {
		$accessCrypt = md5($t.$accessKey);
		if(strcmp($accessCrypt,$pwd) === 0) {
			$check_access_key = true;
			break;
		}
	}
	if(!$check_access_key){
		$user->trackErrorLogin();
		throw new WebServiceException(WebServiceErrorCode::$INVALIDUSERPWD,"Invalid username or password");
	} else {
		$user->trackSuccessLogin();
	}
	
	$success = true;
	$user->checkTrackingLogin($success);
	if (!$success) {
		throw new WebServiceException(WebServiceErrorCode::$LOGINLOCKED,"Login failed because too many unsuccessfully logins");
	}
	
	$user = $user->retrieveCurrentUserInfoFromFile($userId);
	if($user->status != 'Inactive'){
		
		// crmv@91082 - login history
		$loghistory = LoginHistory::getInstance();
		$Signin = $loghistory->user_login($user->column_fields["user_name"]);
		// crmv@91082e

		return $user;
	}
	throw new WebServiceException(WebServiceErrorCode::$AUTHREQUIRED,'Given user is inactive');
}

function vtws_getActiveToken($userId){
	global $adb,$table_prefix;
	//crmv@26686
	$tmp = array();
	$sql = "delete from ".$table_prefix."_ws_userauthtoken where userid=? and expiretime < ?";
	$result = $adb->pquery($sql,array($userId,time()));
	$sql = "select token from ".$table_prefix."_ws_userauthtoken where userid=? and expiretime >= ? order by expiretime desc";
	$result = $adb->pquery($sql,array($userId,time()));
	if($result != null && isset($result) && $adb->num_rows($result)>0){		
		while($row=$adb->fetchByAssoc($result)) {
			$tmp[] = $row['token'];
		}
		return $tmp;
	}
	//crmv@26686e
	return null;
}

function vtws_getUserAccessKey($userId){
	global $adb,$table_prefix;
	
	$sql = "select accesskey from ".$table_prefix."_users where id=?";
	$result = $adb->pquery($sql,array($userId));
	if($result != null && isset($result)){
		if($adb->num_rows($result)>0){
			return $adb->query_result($result,0,"accesskey");
		}
	}
	return null;
}

//crmv@2390m
function vtws_login_pwd($username,$password){
	$user = CRMEntity::getInstance('Users');
	$user->column_fields['user_name'] = $username;
	if(!$user->doLogin($password)) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDUSERPWD,"Invalid username or password");
	}
	$userId = $user->retrieve_user_id($username);
	$user = $user->retrieveCurrentUserInfoFromFile($userId);
	if($user->status != 'Inactive'){
		return array($user->column_fields['accesskey']);
	}
	throw new WebServiceException(WebServiceErrorCode::$AUTHREQUIRED,'Given user is inactive');
}
//crmv@2390me
?>