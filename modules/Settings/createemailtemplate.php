<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('include/CustomFieldUtil.php');

global $app_strings;
global $mod_strings;
global $current_language,$default_charset;

global $theme, $adb, $table_prefix;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";
$smod_strings = return_module_language($current_language,'Settings');

//To get Email Template variables -- Pavani
$allOptions=getEmailTemplateVariables();
$smarty = new VteSmarty();

$smarty->assign("APP", $app_strings);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("THEME", $theme);
$smarty->assign("THEME_PATH", $theme_path);
$smarty->assign("UMOD", $mod_strings);
$smarty->assign("PARENTTAB", getParentTab());
$smarty->assign("ALL_VARIABLES", $allOptions);

$smarty->assign("MOD", $smod_strings);
$smarty->assign("MODULE", 'Settings');

$smarty->assign("TEMPLATETYPE", getTemplateTypeValues($emailtemplateResult["templatetype"]));	//crmv@22700
//crmv@80155
$smarty->assign("USE_SIGNATURE", 0);
$smarty->assign("OVERWRITE_MESSAGE", 1);

$res = $adb->query("select * from ".$table_prefix."_field where fieldname = 'bu_mc'");
if ($res && $adb->num_rows($res) > 0) {
	$pick_bu_mc = array();
	$bumc_res = $adb->query("SELECT bu_mc FROM {$table_prefix}_bu_mc GROUP BY bu_mc");
	while($row_bumc = $adb->fetchByAssoc($bumc_res)){
		$pick_bu_mc[] = array('value'=>$row_bumc['bu_mc'],'label'=>getTranslatedString($row_bumc['bu_mc'], 'Users'),'selected'=>'');
	}
	$smarty->assign("BU_MC_ENABLED", true);
	$smarty->assign("BU_MC", $pick_bu_mc);
}
//crmv@80155e

//crmv@197575
$CRMVUtils = CRMVUtils::getInstance();
$template_editor = $CRMVUtils->getConfigurationLayout('template_editor');
$smarty->assign('TEMPLATE_EDITOR', $template_editor);
//crmv@197575e

$smarty->display("CreateEmailTemplate.tpl");

?>