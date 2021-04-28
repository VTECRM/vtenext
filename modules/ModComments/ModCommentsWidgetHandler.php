<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $currentModule;

$widgetName = vtlib_purify($_REQUEST['widget']);
$criteria = vtlib_purify($_REQUEST['criteria']);
//crmv@31301
$searchkey = vtlib_purify($_REQUEST['searchkey']);
if ($searchkey == getTranslatedString('LBL_SEARCH_TITLE').getTranslatedString('ModComments','ModComments')) {
	$searchkey = '';
}
//crmv@31301e

$widgetController = CRMEntity::getInstance($currentModule);
$widgetInstance = $widgetController->getWidget($widgetName);
$widgetInstance->setCriteria($criteria);
$widgetInstance->setSearchKey($searchkey); //crmv@31301

echo $widgetInstance->process( array('ID' => vtlib_purify($_REQUEST['parentid'])) );
?>