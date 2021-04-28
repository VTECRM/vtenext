<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@27020 crmv@38592 */
require_once("data/Tracker.php");
require_once("include/utils/utils.php");

insert_charset_header();

global $app_strings, $currentModule, $default_theme, $moduleList, $theme;
if (empty($theme)) $theme = $default_theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$smarty = new VteSmarty();
$smarty->assign("APP", $app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);

global $small_page_title, $small_page_path, $small_page_title_link, $small_page_subtitle, $small_page_subtitle_link;
if (empty($small_page_title)) {
	$smarty->assign("BROWSER_TITLE", getTranslatedString($currentModule,$currentModule).' - '.getTranslatedString('LBL_BROWSER_TITLE'));
	$smarty->assign("PAGE_TITLE", '');
} else {
	if ($small_page_title != 'SKIP_TITLE') {
		$smarty->assign("BROWSER_TITLE", $small_page_title);
	}
	$smarty->assign("PAGE_TITLE", $small_page_title);
}
if (!empty($small_browser_title)) {
	$smarty->assign("BROWSER_TITLE", $small_browser_title);
}

if (empty($small_page_title_link)) {
	$small_page_title_link = 'location.reload();';
}
$smarty->assign("PAGE_TITLE_LINK", $small_page_title_link);

$smarty->assign("PAGE_SUB_TITLE", $small_page_subtitle);
if (empty($small_page_subtitle_link)) {
	$small_page_subtitle_link = 'location.reload();';
}
$smarty->assign("PAGE_SUB_TITLE_LINK", $small_page_subtitle_link);

$smarty->assign("BUTTON_LIST", $small_page_buttons);
$smarty->assign("PATH", $small_page_path);

// crmv@114260
if (isset($header_z_index) && $header_z_index != '') {
	$smarty->assign("HEADER_Z_INDEX", $header_z_index);
} else {
	$smarty->assign("HEADER_Z_INDEX", 10);
}
// crmv@114260e

$smarty->display('SmallHeader.tpl');