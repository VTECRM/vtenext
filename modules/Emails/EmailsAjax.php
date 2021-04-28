<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb;

$local_log =& LoggerManager::getLogger('EmailsAjax');
global $currentModule;
$modObj = CRMEntity::getInstance($currentModule);

$ajaxaction = $_REQUEST["ajxaction"];
if($ajaxaction == "DETAILVIEW") {
	// do nothing
} elseif($ajaxaction == "LOADRELATEDLIST" || $ajaxaction == "DISABLEMODULE") {
	require_once 'include/ListView/RelatedListViewContents.php';
} elseif($_REQUEST['ajaxmode'] == 'qcreate') {
	require_once('quickcreate.php');
} else {
	require_once('include/Ajax/CommonAjax.php');
}
?>