<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@94525 */

require_once("data/Tracker.php");
require_once("include/utils/utils.php");

global $current_language;
global $currentModule;
global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

global $app_strings;

// crmv@101684
$login_ajax = ($_REQUEST['login_view'] == 'ajax');
// no need to load anything new, since all the needed files are on the page
if ($login_ajax) return; 
// crmv@101684e

$smarty=new VteSmarty();
$smarty->assign("APP", $app_strings);

if(isset($app_strings['LBL_CHARSET'])) {
	$smarty->assign("LBL_CHARSET", $app_strings['LBL_CHARSET']);
} else {
	$smarty->assign("LBL_CHARSET", $default_charset);
}

$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("CURRENT_LANGUAGE", $current_language);

$smarty->display('loginheader.tpl');
