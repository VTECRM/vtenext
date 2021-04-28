<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Webforms/Webforms.php');
require_once('modules/Webforms/model/WebformsModel.php');

global $current_user,$log;
Webforms::checkAdminAccess($current_user);

$webform=Webforms_Model::retrieveWithId(vtlib_purify($_REQUEST['id']));
$webform->delete();

$listURL='index.php?module=Webforms&action=WebformsListView&parenttab=Settings';
header(sprintf("Location: %s",$listURL));
?>