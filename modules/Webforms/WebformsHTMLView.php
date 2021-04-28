<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Webforms/Webforms.php');
require_once('modules/Webforms/model/WebformsModel.php');
require_once 'config.inc.php';

Webforms::checkAdminAccess($current_user);

$webformModel=Webforms_Model::retrieveWithId($_REQUEST['id']);
$webformFields=$webformModel->getFields();

$smarty = new VteSmarty();

$smarty->assign('ACTIONPATH',$site_URL);
$smarty->assign('WEBFORM',new Webforms());
$smarty->assign('WEBFORMMODEL',$webformModel);
$smarty->assign('WEBFORMFIELDS',$webformFields);
$smarty->assign('LANGUAGE',$current_language);
$smarty->display(vtlib_getModuleTemplate($currentModule,'HTMLView.tpl'));
?>