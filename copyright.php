<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require('config.inc.php');
require_once('include/utils/utils.php');
require_once('modules/Morphsuit/utils/MorphsuitUtils.php');

global $default_theme, $theme;
global $default_language, $current_language, $app_strings;

VteSession::start(); // crmv@171581 

if (empty($theme)) {
	$theme = $default_theme;
}
if (empty($current_language)) {
	$current_language = $default_language;
}

include('themes/LoginHeader.php');

$smarty = new VteSmarty();
$smarty->assign("APP", $app_strings);
$smarty->assign("LICENSE_FILE", 'LICENSE.txt');

$canUpdate = ($_REQUEST['use_current_login'] == 'yes' && vtlib_isModuleActive('Morphsuit')); //crmv@182677
$smarty->assign("CAN_UPDATE", $canUpdate);
if ($canUpdate) {
	$smarty->assign("MORPHSUIT", getMorphsuitInfo()); //crmv@182677
}

$freeVersion = isFreeVersion();
$smarty->assign("FREE_VERSION", $freeVersion);

$smarty->display('Copyright.tpl');
