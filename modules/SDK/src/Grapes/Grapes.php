<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@197575 */

global $mod_strings, $app_strings, $theme;
global $adb, $table_prefix, $current_user, $current_language, $site_URL;

error_reporting(E_ERROR);

$templateid = intval($_REQUEST['templateid']);
$mode = vtlib_purify($_REQUEST['mode']);
$tpl_content = vtlib_purify($_REQUEST['content']);
$selected_path = vtlib_purify($_REQUEST['path']);

$entitytype = getSalesEntityType($templateid);
if($entitytype == 'Newsletter'){
	$newsletterid = $templateid;
	$templateid = getSingleFieldValue($table_prefix.'_newsletter', 'templateemailid', 'newsletterid', $newsletterid);
}


//LOAD STORED IMAGES
require_once('include/ckeditor/filemanager/connectors/php/filemanager.config.php');
require_once('include/ckeditor/filemanager/connectors/php/filemanager.class.php');

$fm = new Filemanager($config);

if(!empty($selected_path)){
	$selected_path .= '/';
}

$main_img_folder = 'storage/images_uploaded/';
$_GET['path'] = $main_img_folder.$selected_path;
$_GET['path'] = str_replace('..', '', $_GET['path']);	//crmv@fix
$fm->params['type'] = 'Images';

//exploring folder and images crmv@201352
if(isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'explore'){
	if($fm->getvar('path')) {
		$response = $fm->getfolder();
	}
	//preprint($response);
	$arr_content = array();
	$arr_content['directories'] = array();
	$arr_content['files'] = array();
	foreach($response as $path => $info){
		if($info['File Type'] == 'dir'){
			$arr_content['directories'][] = str_replace($main_img_folder, '', $path);
		}else{
			$arr_content['files'][] = $path;
		}
	}
	echo Zend_Json::encode($arr_content);
	exit();
}

//get stored images
if($fm->getvar('path')) {
    $response = $fm->getfolder();
}
//preprint($response);
$images_uploaded = array();
//$images_uploaded = array_keys($response);
foreach($response as $file => $info){
	if($info['File Type'] == 'dir') continue;
	$images_uploaded[] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].str_replace('index.php','',$_SERVER['SCRIPT_NAME']).$file;	//$info['Preview'];
}
//crmv@201352e
$images_uploaded = Zend_Json::encode($images_uploaded);

$smarty = new VteSmarty();
$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign('THEME', $theme);
$smarty->assign('MODE', $focus->mode);
$smarty->assign('NAME', 'Grapes');	//crmv@104310
$smarty->assign('UPDATEINFO', 'Grapes');
$smarty->assign('CURRENT_LANGUAGE', $current_language);

$smarty->assign('IMAGES_UPLOADED', $images_uploaded);
$smarty->assign('UP_ENDPOINT', $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']);//$site_URL.'/include/ckeditor/filemanager/connectors/php/filemanager.php');
$smarty->assign('IMAGES_FOLDER', $main_img_folder);

if(!empty($mode) && $mode == 'save_draft'){
	
	$ins = "INSERT INTO tbl_s_nl_tpl_draft (templateid, draft_date, draft_content, compiled_by) VALUES (?,?,?,?) ";
	$params = array($templateid, date('Y-m-d H:i:s'), $tpl_content, $current_user->id);
	$adb->pquery($ins, $params);
	
	$q = "SELECT draft_id, templateid FROM tbl_s_nl_tpl_draft WHERE templateid = ? ORDER BY draft_date ";
	$res = $adb->pquery($q, array($templateid));
	if($adb->num_rows($res) > 10){
		//cancello la bozza più vecchia
		
		$draft_id = $adb->query_result($res, 0, 'draft_id');
		$templateid_old = $adb->query_result($res, 0, 'templateid');	//penso sia uguale a quello dove sono
		
		$del = "DELETE FROM tbl_s_nl_tpl_draft WHERE templateid = ? AND draft_id = ? ";
		$adb->pquery($del, array($templateid_old, $draft_id));
	}
	
	echo "OK";
	exit;
}

$template_content = '';
$template_name = $template_sub = $template_desc = '';
if(!empty($templateid)){
	$q = "SELECT templatename, subject, description, foldername, body FROM {$table_prefix}_emailtemplates WHERE templateid = ? ";
	$res = $adb->pquery($q, array($templateid));
	if($adb->num_rows($res) > 0){
		$template_content = $adb->query_result_no_html($res, 0, 'body');

		$template_name = $adb->query_result_no_html($res, 0, 'templatename');
		$template_sub = $adb->query_result_no_html($res, 0, 'subject');
		$template_desc = $adb->query_result_no_html($res, 0, 'description');
	}
}

$smarty->assign('CONTENT', $template_content);

$allOptions = [];
$emailTemplateVariables = getEmailTemplateVariables();

foreach ($emailTemplateVariables as $mod => $fields) {
	foreach ($fields as &$f) {
		$f[0] = addslashes($f[0]);
		$f[1] = addslashes($f[1]);
	}
	$allOptions[getTranslatedString($mod, $mod)] = $fields;
}

$smarty->assign('TPL_ID', $templateid);
$smarty->assign('ALL_VARIABLES', Zend_Json::encode($allOptions));

if(!empty($mode) && $mode == 'load_body'){

	if(!isset($_REQUEST['is_wizard']) || $_REQUEST['is_wizard'] != '1'){
		$smarty->assign('CAN_EDIT_TEMPLATES', true);
		$smarty->assign('load_header', true);
	}
	
	$smarty->assign('TPL_NAME', $template_name);
	$smarty->assign('TPL_DESC', $template_desc);
	$smarty->assign('TPL_SUBJECT', $template_sub);

	$smarty->display('modules/SDK/src/Grapes/GrapesPage.tpl');
	exit;
}

require_once('include/ListView/SimpleListView.php');

$Slv = SimpleListView::getInstance('EmailTemplates'); // fake module, but works as well
$Slv->listid = 200;
$Slv->maxFields = 2;
$Slv->entriesPerPage = 5;
$Slv->showCreate = true;	//crmv@55230	is_admin($current_user);
$Slv->selectFunction = 'nlwTemplateSelect';
$Slv->createFunction = 'nlwTemplateEdit';
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