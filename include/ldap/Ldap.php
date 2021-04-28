<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

function ldapConnectServer()
{
	log_ldap('entering connect to LDAP server',Array());
	$AUTHCFG = get_config_ldap();
	$conn = ldap_connect($AUTHCFG['ldap_host'],$AUTHCFG['ldap_port']);
	log_ldap('connect',Array($AUTHCFG['ldap_host'],$AUTHCFG['ldap_port']),ldap_errno($conn));
	@ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3); // Try version 3.  Will fail and default to v2.
	@ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT,1);
	@ldap_set_option($conn, LDAP_OPT_REFERRALS, 0); // required for AD
	if (!empty($AUTHCFG['ldap_username'])) 
	{
		if (!@ldap_bind($conn, $AUTHCFG['ldap_username'], $AUTHCFG['ldap_pass'])){
			log_ldap('bind',Array($AUTHCFG['ldap_username'], $AUTHCFG['ldap_pass']),ldap_errno($conn));
			log_ldap('exiting connect to LDAP server',Array());
			return NULL;
		}	
	} 
	else 
	{
		if (!@ldap_bind($conn)){ //attempt an anonymous bind if no user/pass specified in config.php
			log_ldap('anonymous bind',Array(),ldap_errno($conn));
			log_ldap('exiting connect to LDAP server',Array());
			return NULL;
		}	
	}
	return $conn;
}

function log_ldap($action,$params,$error=false){
	global $log;
	$params_string = implode(",",$params);
	if (!empty($error)){
		$reason = ldap_err2str($error);
		$log->fatal("LDAP $action with params: $params_string, reason: $reason");
	}	
	else
		$log->debug("LDAP $action success with params: $params_string");
}

/**
 * Function to authenticate users via LDAP
 *
 * @param string $authUser -  Username to authenticate
 * @param string $authPW - Cleartext password
 * @return NULL on failure, user's info (in an array) on bind
 */
function ldapAuthenticate($authUser, $authPW) 
{
	log_ldap('entering autenticate to LDAP server',Array());
	$AUTHCFG = get_config_ldap();
	
	if (empty($authUser) || empty($authPW)) 
		return false;
	
	$conn = ldapConnectServer();
	if ($conn == NULL)
		return false;
	
	$retval = false;
	$filter = $AUTHCFG['ldap_account'] . '=' . $authUser;
	$ident  = @ldap_search($conn, $AUTHCFG['ldap_basedn'], $filter);
	log_ldap('autenticate:user search',Array($AUTHCFG['ldap_basedn'],$filter),ldap_errno($conn));
	if ($ident) 
	{
		$result = @ldap_get_entries($conn, $ident);
		log_ldap('autenticate:get entries',Array($ident),ldap_errno($conn));
		if ($result[0]) 
		{
			// dn is the LDAP path where the user was fond. This attribute is always returned.
			if (@ldap_bind( $conn, $result[0]["dn"], $authPW) ) {
				log_ldap('autenticate:bind',Array($result[0]["dn"],$authPW),ldap_errno($conn));
				$retval = true;
			}	
		}
		@ldap_free_result($ident);
	}
	
	@ldap_unbind($conn);
	log_ldap('exiting autenticate to LDAP server',Array());
	return $retval;
}

// Search a user by the given filter and returns the attributes defined in the array $required
function ldapSearchUser($filter, $required)
{
	log_ldap('entering search user',Array());
	$AUTHCFG = get_config_ldap();
	
	$conn = ldapConnectServer();
	if ($conn == NULL)
		return NULL;
	$ident = @ldap_search($conn, $AUTHCFG['ldap_basedn'], $filter, $required);
	log_ldap('search:user search',Array($AUTHCFG['ldap_basedn'],$filter,$required),ldap_errno($conn));
	if ($ident) 
	{
		$result = @ldap_get_entries($conn, $ident);
		log_ldap('search:get entries',Array($ident),ldap_errno($conn));
		@ldap_free_result($ident);
	}
	@ldap_unbind($conn);
	log_ldap('exiting search user',Array());
	return $result;
	
}

// Searches for a user's fullname
// returns a hashtable with Account => FullName of all matching users
function ldapSearchUserAccountAndName($user)
{
	log_ldap('entering search user account and name',Array());
	$AUTHCFG = get_config_ldap();
	$fldaccount = strtolower($AUTHCFG['ldap_account']);
	$fldname    = strtolower($AUTHCFG['ldap_fullname']);
	$fldclass   = strtolower($AUTHCFG['ldap_objclass']);

	$usrfilter  = explode("|", $AUTHCFG['ldap_userfilter']);

	$required   = array($fldaccount,$fldname,$fldclass);	
	$ldapArray  = ldapSearchUser("$fldaccount=*$user*", $required);

	// copy from LDAP specific array to a standardized hashtable
	// Skip Groups and Organizational Units. Copy only users.
	for ($i=0; $i<$ldapArray["count"]; $i++)
	{
		$isuser = false;
		foreach($usrfilter as $filt)
		{
			if (in_array($filt, $ldapArray[$i][$fldclass]))
		    {
		    	$isuser = true;
		    	break;
		    }
		}
		if ($isuser)
		{
			$account = $ldapArray[$i][$fldaccount][0];
			$name    = $ldapArray[$i][$fldaccount][0];
	
			$userArray[$account] = $name;
		}
	}
	log_ldap('exiting search user account and name',Array());
	return $userArray;
}

// retrieve all requested LDAP values for the given user account
// $fields = array("ldap_forename", "ldap_email",...)
// returns a hashtable with "ldap_forename" => "John"
function ldapGetUserValues($account, $fields)
{
	log_ldap('entering get user values',Array());
	$AUTHCFG = get_config_ldap();
	//crmv@20049
	foreach ($AUTHCFG['fields'] as $key=>$value){
		$required[] = $key;
	}
	//crmv@20049e
	$filter = $AUTHCFG['ldap_account'] . "=" .$account;
	$ldapArray = ldapSearchUser($filter, $required);
	// copy from LDAP specific array to a standardized hashtable
	foreach ($fields as $key)
	{
		//crmv@20049
		$attr  = strtolower($key);
		//crmv@20049e
		$value = $ldapArray[0][$attr][0];
		$valueArray[$key] = $value;
	}
	log_ldap('exiting get user values',Array());
	return $valueArray;
}

//crmv@9010e
?>