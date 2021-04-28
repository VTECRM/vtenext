<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@43611 */

// webservices
require_once 'include/Webservices/Utils.php';
require_once("include/Webservices/VtenextCRMObject.php");//crmv@207871
require_once("include/Webservices/VtenextCRMObjectMeta.php");//crmv@207871
require_once("include/Webservices/DataTransform.php");
require_once("include/Webservices/WebServiceError.php");
require_once('include/Webservices/ModuleTypes.php');
require_once("include/Webservices/Retrieve.php");
require_once("include/Webservices/DescribeObject.php");

global $adb, $table_prefix;
global $mod_strings, $app_strings, $theme;
global $currentModule, $current_user;

$smarty = new VteSmarty();
$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', return_module_language($current_language, 'Newsletter'));
$smarty->assign('MODULE', $currentModule);
$smarty->assign('THEME', $theme);
$smarty->assign("CALENDAR_LANG", $app_strings['LBL_JSCALENDAR_LANG']);
$smarty->assign("DATEFORMAT", $current_user->date_format); // crmv@190519

$pageTitle = getTranslatedString('NewsletterWizard', 'Campaigns');

$smarty->assign('BROWSER_TITLE', $pageTitle);
$smarty->assign('PAGE_TITLE', 'SKIP_TITLE');	//$pageTitle


$campaignid = intval($_REQUEST['from_record']);
$newsletterid = intval($_REQUEST['newsletterid']);

if ($campaignid > 0) {
	// retrieve targets
	$RM = RelationManager::getInstance();
	$targets = $RM->getRelatedIds('Campaigns', $campaignid, 'Targets');
	$tlist = array();
	foreach ($targets as $tid) {
		$tname = getEntityName('Targets', $tid);
		$tlist[] = array('crmid'=> $tid, 'entityname'=>$tname[$tid]);
	}
	$smarty->assign('SEL_TARGETS', $tlist);
}

//crmv@197575
$CRMVUtils = CRMVUtils::getInstance();
$template_editor = $CRMVUtils->getConfigurationLayout('template_editor');
//crmv@197575e

$smarty->assign('CAMPAIGNID', $campaignid);
$smarty->assign('NEWSLETTERID', $newsletterid);

$smarty->assign('TESTEMAILADDRESS', getUserEmail($current_user->id));

require_once('include/ListView/SimpleListView.php');

$Slv = SimpleListView::getInstance('EmailTemplates'); // fake module, but works as well
$Slv->listid = 200;
$Slv->maxFields = 2;
$Slv->entriesPerPage = 5;
$Slv->showCreate = true;	//crmv@55230	is_admin($current_user);
$Slv->selectFunction = 'nlwTemplateSelect';
//crmv@197575
if($template_editor == 'grapesjs'){
	$Slv->createFunction = 'VTE.GrapesEditor.showGrapesDiv';
}else{
	$Slv->createFunction = 'nlwTemplateEdit';
}
$smarty->assign('TEMPLATE_EDITOR', $template_editor);
//crmv@197575e
$Slv->showCheckboxes = false;

$lv = $Slv->render();
$smarty->assign('TPLLIST', $lv);
$smarty->assign('CAN_EDIT_TEMPLATES', $Slv->showCreate);

$allOptions = getEmailTemplateVariables();
$smarty->assign('TPLVARIABLES', $allOptions);

// get newsletter fields
$nlfields = array('newslettername', 'from_name', 'from_address', 'replyto_address', 'description'); // crmv@151474
// retrieve them with webservices
$wsmodule = vtws_describe('Newsletter', $current_user);
// crmv@151474
// sort by block and sequence
$wsfields = $wsmodule['fields'];
usort($wsfields, function($f1, $f2) {
	if ($f1['blockid'] < $f2['blockid']) {
		return -1;
	} elseif ($f1['blockid'] > $f2['blockid']) {
		return +1;
	}
	return ($f1['sequence'] < $f2['sequence'] ? -1 : ($f1['sequence'] > $f2['sequence'] ? +1 : 0));
});
// crmv@151474e
$fields = array();
foreach ($wsfields as $f) { // crmv@151474
	if (in_array($f['name'], $nlfields)) {
		if ($f['name'] == 'from_name') {
			$f['value'] = trim(getUserFullName($current_user->id));
		} elseif ($f['name'] == 'from_address') {
			$f['value'] = getUserEmail($current_user->id);
		}
		$fields[] = $f;
	}
}
$smarty->assign('NLFIELDS', $fields);

//crmv@181281
$focusNewsletter = CRMEntity::getInstance('Newsletter');
$target_mods = $focusNewsletter->target_modules;
//crmv@181281e
$target_modinfo = array();
foreach ($target_mods as $tmod) {
	if (!vtlib_isModuleActive($tmod)) continue; //crmv@48990
	if (isPermitted($tmod, 'index') != 'yes') continue;
	
	// crmv@151905
	if ($campaignid > 0 && $tmod == 'Targets') {
		$cv = CRMEntity::getInstance('CustomView', $tmod); // crmv@115329
		$filterlist = $cv->getCustomViewCombo();

		$Slv = SimpleListView::getInstance($tmod);
		$Slv->entriesPerPage = 10;
		$Slv->showCreate = false;
		$Slv->showSuggested = false;
		$Slv->showCheckboxes = false;

		if ($tmod != 'Targets') {
			$Slv->extraButtonsHTML = '<input type="button" class="crmbutton" value="'.getTranslatedString('LBL_ADD_ALL').'" onclick="nlwFilterSelect(\''.$Slv->listid.'\', \''.$tmod.'\', jQuery(\'#SLVContainer_'.$Slv->listid.'\').find(\'#viewname\').val())" >';
		}

		$Slv->selectFunction = 'nlwRecordSelect';

		$list = $Slv->render();

		$modinfo = array(
			'filters' => $filterlist,
			'list' => $list,
			'listid' => $Slv->listid
		);
	} else {
		$modinfo = array(); // populated dynamically
	}
	// crmv@151905
	
	$target_modinfo[$tmod] = $modinfo;
}

$smarty->assign('TARGET_MODS', $target_modinfo);

$smarty->assign('HEADER_Z_INDEX', 100);

//crmv@168109
$res = $adb->pquery("select fieldid from ".$table_prefix."_field where fieldname = ?", array('bu_mc'));
if ($res && $adb->num_rows($res) > 0) {
	$pick_bu_mc = array();
	$bumc_res = $adb->query("SELECT bu_mc FROM {$table_prefix}_bu_mc GROUP BY bu_mc");
	while($row_bumc = $adb->fetchByAssoc($bumc_res)){
		$pick_bu_mc[] = array('value'=>$row_bumc['bu_mc'],'label'=>getTranslatedString($row_bumc['bu_mc'], 'Users'),'selected'=>'');
	}
	$smarty->assign("BU_MC_ENABLED", true);
	$smarty->assign("BU_MC", $pick_bu_mc);
}
//crmv@168109e

// crmv@190519
$now = date('Y-m-d H:i');
list($defaultSendDate, $defaultSendHour) = explode(' ', $now, 2);
$smarty->assign("DEFAULT_SENDDATE", getDisplayDate($defaultSendDate));
$smarty->assign("DEFAULT_SENDHOUR", $defaultSendHour);
// crmv@190519e

$smarty->display('modules/Campaigns/NewsletterWizard.tpl');