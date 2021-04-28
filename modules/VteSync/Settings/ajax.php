<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@176547 */

global $current_user;
if (!is_admin($current_user)) die('Not authorized');

require_once('modules/VteSync/VteSync.php');

$action = $_REQUEST['subaction'];
$success = false;
$error = null;

if ($action == 'save_oauth_data') {
	$vsync = VteSync::getInstance();
	$saveid = $vsync->insertOAuthData($_POST);
	if ($saveid > 0) {
		$success = true;
		$result = array('saveid' => $saveid);
	}
} elseif ($action == 'authorize') {
	require('OAuthAuthorize.php');
} elseif ($action == 'token') {
	require('OAuthToken.php');
} elseif ($action == 'toggle_status') {
	$syncid = intval($_REQUEST['syncid']);
	$active = ($_REQUEST['active'] == '1');
	
	$vsync = VteSync::getInstance();
	$vsync->setSyncActive($syncid, $active);
	$success = true;
} elseif ($action == 'delete_sync') {
	$syncid = intval($_REQUEST['syncid']);
	$vsync = VteSync::getInstance();
	$vsync->deleteSync($syncid);
	$success = true;
} elseif ($action == 'get_service_info') {
	$typeid = intval($_REQUEST['typeid']);
	$vsync = VteSync::getInstance();
	
	// crmv@190016
	$modules = $vsync->getTypeModules($typeid);
	$auths = $vsync->getAuthTypes($typeid);
	$oauth2Flows = $vsync->getOAuth2Flows($typeid); // crmv@196666
	$hasurl = $vsync->hasSystemUrl($typeid); 
	$result = array(
		'has_system_url' => $hasurl,
		'system_url_example' => $hasurl ? $vsync->getSystemUrlExample($typeid)  : '',
		'modules' => $modules,
		'auths' => $auths,
		'oauth2_flows' => $oauth2Flows, // crmv@196666
	);
	// crmv@190016e
	
	$success = true;
} elseif ($action == 'validate_save') {
	$syncid = intval($_REQUEST['syncid']);
	$vsync = VteSync::getInstance();
	
	$error = "";
	$success = $vsync->validateSave($_POST, $syncid > 0 ? 'update' : 'create', $error);
} else {
	die('Unknown action');
}

if ($success) {
	$output = array('success' => $success, 'result' => $result);
} else {
	$output = array('success' => $success, 'error' => $error);
}

echo Zend_Json::encode($output);
die();