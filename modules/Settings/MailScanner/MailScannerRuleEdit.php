<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Settings/MailScanner/core/MailScannerInfo.php');
require_once('modules/Settings/MailScanner/core/MailScannerRule.php');

global $app_strings, $mod_strings, $currentModule, $theme, $current_language;

$smarty = new VteSmarty();
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH","themes/$theme/images/");

$scannername = $_REQUEST['scannername'];
$scannerruleid= $_REQUEST['ruleid'];
$scannerinfo = new Vtenext_MailScannerInfo($scannername);//crmv@207843
$scannerrule = new Vtenext_MailScannerRule($scannerruleid);//crmv@207843

$smarty->assign("SCANNERINFO", $scannerinfo->getAsMap());
$smarty->assign("SCANNERRULE", $scannerrule);
$smarty->assign("DEFAULT_SUBJECT_REGEX", $scannerrule->default['subject']);	//crmv@78745

$smarty->display('MailScanner/MailScannerRuleEdit.tpl');

?>