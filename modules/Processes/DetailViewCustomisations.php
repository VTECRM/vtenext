<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@99316 crmv@198388 */

require_once('modules/Settings/ProcessMaker/ProcessDynaForm.php');

// crmv@105933 crmv@181170
// remove some tools for the module
if ($smarty && is_array($smarty->getTemplateVars('CHECK'))) {
	$tool_buttons = $smarty->getTemplateVars('CHECK');
	unset($tool_buttons['EditView']);
	unset($tool_buttons['Import']);
	unset($tool_buttons['Merge']);
	unset($tool_buttons['DuplicatesHandling']);
	$smarty->assign('CHECK', $tool_buttons);
}
// crmv@105933e crmv@181170e

$condFields = array();
$processDynaFormObj = ProcessDynaForm::getInstance();
$enable = $processDynaFormObj->existsConditionalFpovValueActive($focus, $condFields);
if ($enable) {
	$smarty->assign('AJAXONCLICKFUNCT', 'ProcessMakerScript.checkAjaxSave');
	$smarty->assign('CONDITIONAL_FIELDS', $condFields);
}
$smarty->assign('AJAXSAVEFUNCTION', 'DynaFormScript.dtlViewAjaxSave');
$smarty->assign('TEMPLATE', $smarty_template);
$smarty_template = 'modules/Processes/DetailView.tpl';