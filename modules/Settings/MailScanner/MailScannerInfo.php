<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Settings/MailScanner/core/MailScannerInfo.php');

global $app_strings, $mod_strings, $currentModule, $theme, $current_language;
global $application_unique_key; // defined in config.inc.php

$smarty = new VteSmarty();
$smarty->assign("MOD", return_module_language($current_language,'Settings'));
$smarty->assign("CMOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH","themes/$theme/images/");

$scanners = Vtenext_MailScannerInfo::listAll();//crmv@207843

$smarty->assign("SCANNERS", $scanners);
$smarty->assign("APP_KEY", $application_unique_key);

$smarty->display('MailScanner/MailScannerInfo.tpl');

?>