<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@27589 */

function getUserAuthtokenKey($type,$user_id,$seconds_to_expire, $securetoken = false) { //crmv@29377
	global $adb;
	emptyUserAuthtokenKey($type,$user_id);
	//crmv@29377
	//genera un token più sicuro
	if ($securetoken){
		$authToken = md5(crypt(strval(microtime(true)+(mt_rand(0, 10000) / 10.0)).strval($user_id).$type)); // crmv@179766
	} else {
		$authToken = uniqid();
	}
	//crmv@29377e
	$expireTime = time()+$seconds_to_expire; //seconds
	$sql = "insert into vte_userauthtoken(type,userid,token,expiretime) values (?,?,?,?)";
	$adb->pquery($sql,array($type,$user_id,$authToken,$expireTime));
	if ($user_id != '' && $authToken != '') {
		return base64_encode(Zend_Json::encode(array('userid'=>$user_id,'token'=>$authToken)));
	} else {
		return false;
	}
}

function validateUserAuthtokenKey($type,$key) {
	global $adb;
	$tmp = Zend_Json::decode(base64_decode($key));
	$user_id = (int)$tmp['userid'];
	$token = $tmp['token'];
	$sql_d = "delete from vte_userauthtoken where type=? and userid=? and expiretime < ?";
	$result_d = $adb->pquery($sql_d,array($type,$user_id,time()));
	$sql = "select * from vte_userauthtoken where type=? and userid=? and expiretime >= ?";
	$result = $adb->pquery($sql,array($type,$user_id,time()));
	if($result != null && isset($result) && $adb->num_rows($result)>0){
		$token_saved = $adb->query_result($result,0,'token');
		if ($token_saved == $token) {
			return $user_id;
		}
	}
	return false;
}

function emptyUserAuthtokenKey($type,$user_id) {
	global $adb;
	$sql = "delete from vte_userauthtoken where type=? and userid=?";
	$adb->pquery($sql,array($type,$user_id));
}