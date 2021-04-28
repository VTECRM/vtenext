<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@215354 crmv@215597 */

require_once('modules/Settings/KlondikeAI/KlondikeConfig.php');

global $app_strings, $mod_strings;
global $theme;

$smarty = new VteSmarty();
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME",$theme);

$KC = new KlondikeConfig();

if ($_REQUEST['remove_link'] === 'true') {
	$KC->removeConfig();
	header('Location: index.php?module=Settings&action=KlondikeAI&parenttab=Settings');
	exit;
}

$showConfig = false;

$cfg = $KC->getconfig();
$url = $cfg['klondike_url'] ?: $KC->getKlondikeUrl();

$smarty->assign("KLONDIKE_URL",$url);

if ($cfg['access_token']) {
	// already linked, check validity
	$smarty->assign("HAS_TOKEN",true);
	$smarty->assign("VALID_TOKEN",true);
	
	$token = $KC->getValidAccessToken($cfg);
	if ($token) {
		$smarty->assign("ATOKEN",$token);
	} else {
		$smarty->assign("VALID_TOKEN",false);
		$smarty->assign("ERRORMSG",getTranslatedString('LBL_KLONDIKE_EXPIRED_TOKEN', 'Settings'));
		$showConfig = true;
	}
	
} else {
	$smarty->assign("HAS_TOKEN",false);
	$smarty->assign("VALID_TOKEN",false);
	$showConfig = true;
}

if ($showConfig) {
	$smarty->display('Settings/KlondikeAI/Config.tpl');
} else {
	$smarty->display('Settings/KlondikeAI/ButtonsPanel.tpl');
}


