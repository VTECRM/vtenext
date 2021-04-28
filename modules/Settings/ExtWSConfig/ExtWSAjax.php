<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@146670 crmv@146671 */

require_once('modules/Settings/ExtWSConfig/ExtWSUtils.php');
require_once('modules/Settings/ExtWSConfig/ExtWS.php');

global $mod_strings, $app_strings, $theme;

$mode = 'ajax';
$extwsid = intval($_REQUEST['extwsid']);
$action = $_REQUEST['subaction'];
$raw = null;
$json = null;

$EWSU = new ExtWSUtils();
$EWS = new ExtWS();

if ($action == 'delete_ws') {
	$ok = $EWSU->deleteWS($extwsid);
	$json = array('success' => $ok, 'error' => ($ok ? '' : 'Unable to delete'));
} elseif ($action == 'test_ws') {
	$data = $EWSU->prepareDataFromRequest();
	$result = $EWS->call($data);
	$json = array('success' => true, 'error' => '', 'result' => $result);
} elseif ($action == 'automap_fields') {
	$error = '';
	$fields = $EWSU->automapFields($_REQUEST['data'], $error);
	$json = array('success' => !empty($fields), 'fields' => $fields, 'error' => $error);
} else {
	$json = array('success' => false, 'error' => "Unknwon action");
}

echo Zend_Json::encode($json);
exit();