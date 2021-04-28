<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@9010
global $mod_strings;
global $app_strings;
global $app_list_strings;
$smarty = new VteSmarty();
if($_REQUEST['error'] != '')
{
        $error_msg =$_REQUEST['error'];
	$smarty->assign("ERROR_MSG",' <b><font class="warning">'.$error_msg.'</font></b>');
}

global $adb;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$sql="SELECT * FROM tbl_s_ldap_config";
$result = $adb->limitQuery($sql,0,1);
if ($result){
	$ldap_active = $adb->query_result($result,0,'ldap_active');
	$server = $adb->query_result($result,0,'ldap_host');
	$server_port = $adb->query_result($result,0,'ldap_port');
	$ldap_basedn = $adb->query_result_no_html($result,0,'ldap_basedn');
	$ldap_username = $adb->query_result_no_html($result,0,'ldap_username');
	$ldap_password = $adb->query_result_no_html($result,0,'ldap_pass');
	$objclass = $adb->query_result($result,0,'ldap_objclass');
	$account = $adb->query_result_no_html($result,0,'ldap_account');
	$fullname = $adb->query_result_no_html($result,0,'ldap_fullname');
	$userfilter = $adb->query_result_no_html($result,0,'ldap_userfilter');
	$role = $adb->query_result_no_html($result,0,'user_role');
}
if(isset($_REQUEST['ldap_server_mode']) && $_REQUEST['ldap_server_mode'] != '')
	$smarty->assign("LDAP_SERVER_MODE",$_REQUEST['ldap_server_mode']);
else
	$smarty->assign("LDAP_SERVER_MODE",'view');

if(isset($_REQUEST['ldap_active']))
	$smarty->assign("LDAPACTIVE",$_REQUEST['ldap_active']);
elseif (isset($server))
	$smarty->assign("LDAPACTIVE",$ldap_active);
else 
	$smarty->assign("LDAPACTIVE",1); 	
if(isset($_REQUEST['ldap_host']))
	$smarty->assign("LDAPHOST",$_REQUEST['ldap_host']);
elseif (isset($server))
	$smarty->assign("LDAPHOST",$server);

if (isset($_REQUEST['ldap_port']))
        $smarty->assign("LDAPPORT",$_REQUEST['ldap_port']);      
elseif (isset($server_port))
        $smarty->assign("LDAPPORT",$server_port);
else $smarty->assign("LDAPPORT",'389'); 

if (isset($_REQUEST['ldap_basedn']))
        $smarty->assign("LDAPBSEDN",$_REQUEST['ldap_basedn']);      
elseif (isset($ldap_basedn))
        $smarty->assign("LDAPBSEDN",$ldap_basedn);

if (isset($_REQUEST['ldap_username']))
	$smarty->assign("LDAPSUSER",$_REQUEST['ldap_username']);
elseif (isset($ldap_username))
        $smarty->assign("LDAPSUSER",$ldap_username);

if (isset($_REQUEST['ldap_password']))
	$smarty->assign("LDAPSPASSWORD",$_REQUEST['ldap_password']);
elseif (isset($ldap_password))
	$smarty->assign("LDAPSPASSWORD",$ldap_password);

if (isset($_REQUEST['ldap_objclass']))
	$smarty->assign("LDAPOBJCLASS",$_REQUEST['ldap_objclass']);
elseif (isset($objclass))
	$smarty->assign("LDAPOBJCLASS",$objclass);
else 
	$smarty->assign("LDAPOBJCLASS",'objectClass'); 	
if (isset($_REQUEST['ldap_account']))
	$smarty->assign("LDAPACCOUNT",$_REQUEST['ldap_account']);
elseif (isset($account))
	$smarty->assign("LDAPACCOUNT",$account);
else 
	$smarty->assign("LDAPACCOUNT",'sAMAccountName'); 
if (isset($_REQUEST['ldap_fullname']))
	$smarty->assign("LDAPFULLNAME",$_REQUEST['ldap_fullname']);
elseif (isset($fullname))
	$smarty->assign("LDAPFULLNAME",$fullname);
else 
	$smarty->assign("LDAPFULLNAME",'cn'); 
if (isset($_REQUEST['ldap_userfilter']))
	$smarty->assign("LDAPFILTER",$_REQUEST['ldap_userfilter']);
elseif (isset($userfilter))
	$smarty->assign("LDAPFILTER",$userfilter);
else 
	$smarty->assign("LDAPFILTER",'user|person|organizationalPerson ');
if (isset($_REQUEST['user_role'])){
	$smarty->assign("secondvalue",getRoleName($_REQUEST['user_role']));
	$smarty->assign("roleid",$_REQUEST['user_role']);
}	
elseif (isset($role)){
	$smarty->assign("secondvalue",getRoleName($role));
	$smarty->assign("roleid",$role);
}
$smarty->assign("THEME", $theme);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("APP", $app_strings);
$smarty->assign("CMOD", $mod_strings);

$smarty->display("Settings/LdapServer.tpl");
//crmv@9010e
?>