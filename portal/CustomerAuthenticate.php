<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
include("include.php");
include("version.php");
require_once("PortalConfig.php");
require_once("include/utils/utils.php");

global $version,$default_language,$result,$welcome_page;
$username = trim($_REQUEST['username']);
$password = trim($_REQUEST['pw']);

if($username == ''){
	$username = $_REQUEST['userid'];
}
if($password == ''){
	$password = $_REQUEST['token'];
}

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
$cookieurl = str_replace('CustomerAuthenticate.php', '', $_SERVER['SCRIPT_NAME']) ?: '/';

session_set_cookie_params(0, $cookieurl, null, $isHttps, true);
session_start();

SDK::loadGlobalPhp(); // crmv@168297

setPortalCurrentLanguage();
$default_language = getPortalCurrentLanguage();
loadTranslations(); // crmv@168297

$params = array('user_name' => "$username",
	'user_password'=>"$password",
	'version' => "$version");


$result = $client->call('authenticate_user', $params, $Server_Path, $Server_Path);
//The following are the debug informations
$err = $client->getError();

if ($err || $result[0] == 'INVALID_USERNAME_OR_PASSWORD' || $result['err1'] == 'INVALID_USERNAME_OR_PASSWORD') // crmv@167855
{
	sleep(1); // crmv@127527
	//Uncomment the following lines to get the error message in login screen itself.
	/*
	echo '<h2>Error Message</h2><pre>' . $err . '</pre>';
	echo '<h2>request</h2><pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';
	echo '<h2>response</h2><pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
	echo '<h2>debug</h2><pre>' . htmlspecialchars($client->debug_str, ENT_QUOTES) . '</pre>';
	exit;
	*/
	if(empty($result[0]) && empty($result['err1'])){ // crmv@167855
		$login_error_msg = getTranslatedString("LBL_CANNOT_CONNECT_SERVER");
	}else{
		$login_error_msg = getTranslatedString("INVALID_USERNAME_OR_PASSWORD");
	}
	$login_error_msg = base64_encode($login_error_msg);
	header("Location: login.php?login_error=$login_error_msg");
	exit;
}

if (strtolower($result[0]['user_name']) == strtolower($username) && strtolower(html_entity_decode($result[0]['user_password'],ENT_QUOTES,'UTF-8')) == strtolower($password)) { //crmv@144987
	
	//crmv@remember_me
	if ($_POST['savelogin'] == 'on' || $_GET['savelogin'] == 'on') {
	
		$VTEPORTALLOGINID = $result[0]['id'];
		
		$params = array('id' => "$VTEPORTALLOGINID","user_hash"=>'', "step" => "save");
		$result2 = $client->call('authenticate_user_cookie', $params, $Server_Path, $Server_Path);
		
		if ($result2 && $result2[0]) {
			$user_hash = $result2[0];
			$login_expire_time = 3600*24*30; // one month
			setcookie('VTEPORTALLOGINID', $VTEPORTALLOGINID, time()+$login_expire_time, "", null, $isHttps, true);
			setcookie('VTEPORTALLOGINHASH', $user_hash, time()+$login_expire_time, "", null, $isHttps, true);
		}
	
	} else {
		setcookie('VTEPORTALLOGINID', false);
		setcookie('VTEPORTALLOGINHASH', false);
	}
	//crmv@remember_me_e
	
	$_SESSION['customer_id'] = $result[0]['id'];
	$_SESSION['customer_sessionid'] = $result[0]['sessionid'];
	$_SESSION['customer_name'] = $result[0]['user_name'];
	$_SESSION['last_login'] = $result[0]['last_login_time'];
	$_SESSION['support_start_date'] = $result[0]['support_start_date'];
	$_SESSION['support_end_date'] = $result[0]['support_end_date'];
	$customerid = $_SESSION['customer_id'];
	$sessionid = $_SESSION['customer_sessionid'];

	$params1 = Array(Array('id' => "$customerid", 'sessionid'=>"$sessionid", 'flag'=>"login"));

	$result2 = $client->call('update_login_details', $params1, $Server_Path, $Server_Path);

	$params = array('customerid'=>$customerid);
	$permission = $client->call('get_modules',$params,$Server_path,$Server_path);
	// crmv@173271
	if ($default_module && is_array($permission) && in_array($default_module, $permission)) {
		$module = $default_module;
	} else {
		$module = $permission[0];
	}
	// crmv@173271e
	
	if($permission == '')
	{
		echo getTranslatedString('LBL_NO_PERMISSION_FOR_ANY_MODULE');
		exit;
	}
	
	// Store the permitted modules in session for re-use
	$_SESSION['__permitted_modules'] = $permission;
	
	/* crmv@57342 */
	if (!empty($welcome_page))
		header("Location: $welcome_page");
	else
		header("Location: index.php?action=index&module=$module");
	/* crmv@57342e */
}
else
{
	sleep(1); // crmv@127527
	if($result[0] == 'NOT COMPATIBLE'){
		$error_msg = getTranslatedString("LBL_VERSION_INCOMPATIBLE");
	}elseif($result[0] == 'INVALID_USERNAME_OR_PASSWORD') {
		$error_msg = getTranslatedString("LBL_ENTER_VALID_USER");	
	}elseif($result[0] == 'MORE_THAN_ONE_USER'){
		$error_msg = getTranslatedString("MORE_THAN_ONE_USER");
	}
	else
		$error_msg = getTranslatedString("LBL_CANNOT_CONNECT_SERVER");

	$login_error_msg = base64_encode($error_msg);
	//header("Location: login.php?login_error=$login_error_msg");
}
