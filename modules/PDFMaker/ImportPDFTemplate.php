<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/utils/CommonUtils.php');

global $mod_strings;
global $app_strings;
global $app_list_strings;
global $current_user,$default_charset;

global $import_mod_strings;

$focus = 0;

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$log->info($mod_strings['LBL_MODULE_NAME'] . " Upload Step 1");

$smarty = new VteSmarty();

$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign("IMP", $import_mod_strings);

$smarty->assign("CATEGORY", htmlspecialchars($_REQUEST['parenttab'],ENT_QUOTES,$default_charset));

$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);

$smarty->assign("MODULE", $_REQUEST['module']);
$smarty->assign("MODULELABEL", getTranslatedString($_REQUEST['module'],$_REQUEST['module']));

$smarty->display("modules/PDFMaker/ImportPDFTemplate.tpl");

?>