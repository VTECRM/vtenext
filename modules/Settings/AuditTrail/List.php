<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@202301 */

require_once('modules/Settings/AuditTrail.php');

global $app_strings, $mod_strings;
global $current_language;
global $theme;

$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();
$smarty->assign("THEME", $theme);
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("IMAGE_PATH",$image_path);
$smarty->assign("USERLIST",getUserslist(false));
$smarty->assign("UID", vtlib_purify($_REQUEST['uid']) ?: $current_user->id); //crmv@204903 crmv@205568

$smarty->assign("DATEFORMAT",$current_user->date_format);

$CU = CRMVUtils::getInstance();
$int = $CU->getTimeIntervals(['labels' => true, 'display_dates' => true, 'dates' => ['past', 'until_today', 'around_today']]);
$smarty->assign("TIME_INTERVALS_JS",Zend_Json::encode($int));

$AuditTrail = new AuditTrail();

$smarty->assign("AuditStatus", $AuditTrail->isEnabled() ? 'enabled' : 'disabled');

//crmv@203590
$VP = VTEProperties::getInstance();
$interval = intval($VP->getProperty('security.audit.log_retention_time'));
$smarty->assign("AUDIT_LOG_INTERVAL", $interval);
//crmv@203590e

$smarty->display('Settings/AuditTrail/List.tpl');
