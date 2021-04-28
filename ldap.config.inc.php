<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@9010
// ################################# LDAP #####################################
// Configuration for Login password check and, user search and auto complete

function get_config_ldap(){
	global $adb;
	$sql="SELECT * FROM tbl_s_ldap_config";
	$result = $adb->limitQuery($sql,0,1);
	if ($result){
		// crmv@197351 - no_html
		$ldap_active = $adb->query_result_no_html($result,0,'ldap_active');
		$server = $adb->query_result_no_html($result,0,'ldap_host');
		$server_port = $adb->query_result_no_html($result,0,'ldap_port');
		$ldap_basedn = $adb->query_result_no_html($result,0,'ldap_basedn');
		$server_username = $adb->query_result_no_html($result,0,'ldap_username');
		$server_password = $adb->query_result_no_html($result,0,'ldap_pass');
		$objclass = $adb->query_result_no_html($result,0,'ldap_objclass');
		$account = $adb->query_result_no_html($result,0,'ldap_account');
		$fullname = $adb->query_result_no_html($result,0,'ldap_fullname');
		$userfilter = $adb->query_result_no_html($result,0,'ldap_userfilter');
		$role = $adb->query_result_no_html($result,0,'user_role');
		// crmv@197351e
		$AUTHCFG['active'] = $ldap_active;
		$AUTHCFG['role']          = $role;
		$AUTHCFG['ldap_host']     = $server;
		$AUTHCFG['ldap_port']     = $server_port;
		if (isset($server_username) && $server_username!='')	
			$user=$server_username;
		else
			$user = NULL;	
		$AUTHCFG['ldap_username'] = $user;   // set = NULL if not required
		if (isset($server_password) && $server_password!='')	
			$AUTHCFG['ldap_pass']     = $server_password; // set = NULL if not required
		else 
			$AUTHCFG['ldap_pass']     =NULL;	
		$AUTHCFG['ldap_basedn']      = $ldap_basedn;	
		$AUTHCFG['ldap_objclass']    = $objclass; //objectClass (openldap,ad) used to filter users on search
		$AUTHCFG['ldap_account']     = $account; //sAMAccountName used to get the username for search/login user
		$AUTHCFG['ldap_fullname']    = $fullname; // "cn" or "name" or "displayName"
		// Required to search users: the array defined in ldap_objclass must contain at least one of the following values
		$AUTHCFG['ldap_userfilter']  = $userfilter; // user|person|organizationalPerson
// ################################# MAPPING LDAP USER #####################################		
		// LDAP attributes --> mapping ldap fields -> user fields
		//crmv@20049
		$fields[$AUTHCFG['ldap_account']]     = "user_name";
		//crmv@20049e
		$fields["ldap_forename"]    = "first_name";
		$fields["ldap_lastname"]    = "last_name";
		$fields["ldap_email"]       = "email1";
		$fields["ldap_tel_work"]    = "phone_work";
		$fields["ldap_department"]  = "department";
		$fields["ldap_description"] = "description";
// ################################# MAPPING LDAP USER END ##################################		
		$AUTHCFG['fields'] = $fields;
		return $AUTHCFG;
	}	
}
//crmv@9010e
 ?>