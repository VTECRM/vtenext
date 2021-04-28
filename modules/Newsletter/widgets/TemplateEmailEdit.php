<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@80155 */

require_once('include/utils/utils.php');
require_once('data/Tracker.php');
require_once('include/utils/UserInfoUtil.php');
require_once('include/CustomFieldUtil.php');

global $app_strings,$mod_strings,$current_language,$default_charset,$theme,$currentModule,$adb,$table_prefix,$current_user;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

//To get Email Template variables -- Pavani
$allOptions=getEmailTemplateVariables();
$smarty = new VteSmarty();

$mode = $_REQUEST['mode'];
if($mode == 'edit')
{
	$focus = CRMEntity::getInstance($currentModule);
	$focus->id = $_REQUEST['record'];
	$focus->retrieve_entity_info($_REQUEST['record'], $currentModule);
	$templateid = $focus->column_fields['templateemailid'];
	$sql = "select * from {$table_prefix}_emailtemplates where templateid=?";
	$result = $adb->pquery($sql, array($templateid));
	
	//crmv@116110
	if($result && $adb->num_rows($result) > 0){
		$emailtemplateResult = str_replace('"','&quot;',$adb->fetch_array($result));
		$saved_bu_mc = explode(' |##| ', $emailtemplateResult["bu_mc"]);
	
		$smarty->assign("FOLDERNAME", $emailtemplateResult["foldername"]);
		$smarty->assign("TEMPLATENAME", $emailtemplateResult["templatename"]);
		$smarty->assign("TEMPLATEID", $emailtemplateResult["templateid"]);
		$smarty->assign("DESCRIPTION", $emailtemplateResult["description"]);
		$smarty->assign("SUBJECT", $emailtemplateResult["subject"]);
		$smarty->assign("BODY", $emailtemplateResult["body"]);
		$smarty->assign("PAGE_TITLE", getTranslatedString('LBL_EDIT_EMAIL_TEMPLATES','Settings'));
		$smarty->assign("TEMPLATETYPE", getTemplateTypeValues($emailtemplateResult["templatetype"]));
	} else {
		// crmv@159056
		//fallback to create mode
		$mode = '';
		$saved_bu_mc = array();
		// crmv@159056e
		$smarty->assign("TEMPLATETYPE", getTemplateTypeValues('Newsletter'));
		$smarty->assign("PAGE_TITLE", getTranslatedString('LBL_CREATE_EMAIL_TEMPLATES','Settings'));
	}
	//crmv@116110e
} else {
	$saved_bu_mc = array();
	$smarty->assign("TEMPLATETYPE", getTemplateTypeValues('Newsletter')); //crmv@159056
	$smarty->assign("PAGE_TITLE", getTranslatedString('LBL_CREATE_EMAIL_TEMPLATES','Settings'));
}

$smarty->assign("APP", $app_strings);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("THEME", $theme);
$smarty->assign("THEME_PATH", $theme_path);
$smarty->assign("PARENTTAB", getParentTab());
$smarty->assign("ALL_VARIABLES", $allOptions);
$smarty->assign("RECORD", $_REQUEST['record']);
$smarty->assign("EMODE", $mode);

$res = $adb->query("select * from {$table_prefix}_field where fieldname = 'bu_mc'");
if ($res && $adb->num_rows($res) > 0) {
	$pick_bu_mc = array();
	$bu_mc = explode(' |##| ', $current_user->column_fields['bu_mc']);
	foreach($bu_mc as $b) {
		(in_array($b, $saved_bu_mc)) ? $selected = 'selected' : $selected = '';
		$pick_bu_mc[] = array('value'=>$b,'label'=>getTranslatedString($b, 'Users'),'selected'=>$selected);
	}
	$smarty->assign("BU_MC_ENABLED", true);
	$smarty->assign("BU_MC", $pick_bu_mc);
}

//crmv@197575
$CRMVUtils = CRMVUtils::getInstance();
$template_editor = $CRMVUtils->getConfigurationLayout('template_editor');
$smarty->assign('TEMPLATE_EDITOR', $template_editor);
//crmv@197575e

$smarty->display("modules/Newsletter/widgets/TemplateEmailEdit.tpl");
?>