<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@163697

require_once('modules/Settings/GDPRConfig/GDPRUtils.php');

$mode = 'ajax';
$action = $_REQUEST['subaction'];
$json = null;

$GDPRU = new GDPRUtils();

if ($action == 'save_general_settings') {
	$ok = $GDPRU->saveGeneralSettings($_REQUEST);
	$json = array('success' => $ok, 'error' => ($ok ? '' : 'Unable to save'));
} else {
	$json = array('success' => false, 'error' => "Unknwon action");
}

echo Zend_Json::encode($json);
exit();