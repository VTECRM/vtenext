<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Users/LoginHistory.php');
require_once('modules/Users/Users.php');
require_once('config.php');

global $adb ,$current_user;

//crmv@208466 deleted part of backup

// crmv@91082 - Recording Logout Info
$loghistory = LoginHistory::getInstance();
$loghistory->user_logout($current_user->user_name);
// crmv@91082e


$local_log =& LoggerManager::getLogger('Logout');

if ($current_user) $current_user->deleteCookieSaveLogin();	//crmv@29377

VteSession::destroy(); // crmv@128133

// unset savelogin cookie
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
setcookie('savelogindata', false, 0, "", "", $isHttps, true); //crmv@29377 crmv@80972

define("IN_LOGIN", true);

// go to the login screen.
header("Location: index.php?action=Login&module=Users");
?>