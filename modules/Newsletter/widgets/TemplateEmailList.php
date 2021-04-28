<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@80155 crmv@126696 */

require_once('include/utils/utils.php');

global $adb, $table_prefix, $current_user,$default_charset; //crmv@119012
global $mod_strings, $app_strings, $theme;

$record = intval($_REQUEST['record']);
$mode = $_REQUEST['mode'];

$small_page_title = getTranslatedString('LBL_EMAIL_TEMPLATES','Users');
include('themes/SmallHeader.php');

$smarty = new VteSmarty();
$smarty->assign("MOD",$mod_strings);
$smarty->assign("APP",$app_strings);
$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", "themes/$theme/images/");

$smarty->assign("RECORD", $record);
$smarty->assign("MODE", $mode);

$smarty->assign("ENABLE_PREVIEW", true);
$smarty->assign("PREVIEW_FUNCTION", 'previewTemplate');
$smarty->assign("SELECT_FUNCTION", 'submittemplate');
$smarty->assign("SHOW_CANCEL", false);

if($mode == 'processmaker') {
	$smarty->assign("PREVIEW_FUNCTION", 'void');
	$smarty->assign("SELECT_FUNCTION", 'ActionNewsletterScript.selectTemplate');
	$smarty->assign("SHOW_CANCEL", true);
}

$res = $adb->pquery("SELECT fieldid FROM ".$table_prefix."_field WHERE fieldname = ?", array('bu_mc'));
$bu_mc_enabled = ($res && $adb->num_rows($res) > 0);

$sql = "SELECT templateid, templatename, foldername, description FROM ".$table_prefix."_emailtemplates WHERE templatetype = ? AND parentid = 0"; // crmv@151466
$params = array('Newsletter');

if ($bu_mc_enabled) {
	$bu_mc = explode(' |##| ', $current_user->column_fields['bu_mc']);
	if (!empty($bu_mc)) {
		$cond = array();
		foreach($bu_mc as $b) {
			$cond[] = "bu_mc LIKE '%$b%'"; 
		}
		$sql .= " AND (".implode(' OR ',$cond).")"; 
	}
}
$sql .= " ORDER BY templateid DESC";

$templates = array();
$result = $adb->pquery($sql, $params);
while ($temprow = $adb->fetch_array($result)) {
	if (is_admin($current_user) || $temprow['foldername'] != 'Personal') {
		$templatename = popup_from_html($temprow["templatename"]);
		$templatename = htmlspecialchars($templatename,ENT_QUOTES,$default_charset);
		$temprow['templatename'] = $templatename;
		$templates[] = $temprow;
	}
}
$smarty->assign("TEMPLATES", $templates);

$smarty->display('modules/Newsletter/widgets/TemplateEmailList.tpl');

include('themes/SmallFooter.php');