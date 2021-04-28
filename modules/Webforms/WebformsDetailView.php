<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $app_strings, $mod_strings, $current_language, $currentModule, $theme,$current_user,$adb,$log;

require_once('modules/Webforms/Webforms.php');
require_once('modules/Webforms/model/WebformsModel.php');
require_once('modules/Webforms/model/WebformsFieldModel.php');

Webforms::checkAdminAccess($current_user);

if(isset($_REQUEST['id'])){

	$webformModel=Webforms_Model::retrieveWithId($_REQUEST['id']);
	$webform=new Webforms();
	$smarty = new VteSmarty();

	$category = getParentTab();
	$username = getUserFullName($webformModel->getOwnerId());


	$smarty->assign('WEBFORMMODEL',$webformModel);
	$smarty->assign('WEBFORM',$webform);
	$smarty->assign('OWNER',$username);
	$smarty->assign('THEME', $theme);
	$smarty->assign('MOD', $mod_strings);
	$smarty->assign('APP', $app_strings);
	$smarty->assign('MODULE', $currentModule);
	$smarty->assign('CATEGORY', $category);
	$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
	$smarty->assign('WEBFORMFIELDS', Webforms::getFieldInfos($webformModel->getTargetModule()));
	$smarty->assign('ACTIONPATH',$site_URL.'/modules/Webforms/capture.php');
	$smarty->assign('LANGUAGE',$current_language);
	
	$smarty->assign("SINGLE_MOD",'Webform');
	$smarty->assign("ID", $_REQUEST['id']);
	if (isset($focus->name)) $smarty->assign("NAME", $focus->name);
	else $smarty->assign("NAME", $webformModel->getName());
	
	$smarty->display(vtlib_getModuleTemplate($currentModule,'DetailView.tpl'));
}

?>