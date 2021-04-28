<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@167019
// crmv@176893

require_once('modules/Documents/DropArea.php');

$parentModule = vtlib_purify($_REQUEST['pmodule']);
$parentRecord = intval($_REQUEST['precord']);

$DA = DropArea::getInstance();
$html = $DA->fetchDropAreaForm($parentModule, $parentRecord);

$json = array('success' => true, 'title' => getTranslatedString('LBL_SAVE_LABEL') . ' ' . getTranslatedString('Document', 'Documents'), 'html' => $html);

echo Zend_Json::encode($json);
exit();