<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

	function vtws_extendSession(){
		global $adb,$API_VERSION,$application_unique_key;
		if(VteSession::hasKey("authenticated_user_id") && VteSession::get("app_unique_key") == $application_unique_key){
			$userId = VteSession::get("authenticated_user_id");
			$sessionManager = new SessionManager();
			$sessionManager->set("authenticatedUserId", $userId);
			$crmObject = VtenextWebserviceObject::fromName($adb,"Users");//crmv@207871
			$userId = vtws_getId($crmObject->getEntityId(),$userId);
			$vteVersion = vtws_getVteVersion();
			$resp = array("sessionName"=>$sessionManager->getSessionId(),"userId"=>$userId,"version"=>$API_VERSION,"vteVersion"=>$vteVersion);
			return $resp;
		}else{
			throw new WebServiceException(WebServiceErrorCode::$AUTHFAILURE,"Authencation Failed");
		}
	}
?>