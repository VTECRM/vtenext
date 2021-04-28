<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554 crmv@163697

global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $current_user, $currentModule, $current_language, $default_language;

require_once('modules/Settings/GDPRConfig/GDPRUtils.php');

if ($_REQUEST['ajax'] == 1) {
	require('modules/Settings/GDPRConfig/GDPRAjax.php');
	return;
}

$mode = $_REQUEST['mode'] ?: '';

$GDPRU = new GDPRUtils();

$smarty = new VteSmarty();
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");

$BU = BusinessUnit::getInstance();
$businessUnitEnabled = BusinessUnit::isEnabled();

$businessList = $BU->getBusinessList();
$businessId = isset($_REQUEST['business_id']) ? intval($_REQUEST['business_id']) : $businessList[0]['organizationid'];

$business = $BU->getBusinessInfo($businessId);
if (!$business) {
	die("Business not found!");
}

if ($mode == 'save') {
	$data = $GDPRU->prepareDataFromRequest();
	
	$r = $GDPRU->updateGDPR($businessId, $data);
	
	header('Location: index.php?module=Settings&action=GDPRConfig&parentTab=Settings&business_id='.$businessId);
	exit();
}

$smarty->assign("GDPR_INFO", $GDPRU->getGDPRInfo($businessId));
$smarty->assign("BUSINESS_ID", $businessId);
$smarty->assign("BUSINESS_UNIT", $businessList);
$smarty->assign("BUSINESS_UNIT_ENABLED", $businessUnitEnabled);
$smarty->assign("EMAIL_TEMPLATES", $GDPRU->getEmailTemplates());

$smarty->assign("MODE", $mode);

$smarty->display('Settings/GDPRConfig/GDPR.tpl');