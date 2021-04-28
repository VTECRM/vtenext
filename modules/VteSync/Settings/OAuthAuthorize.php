<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@176547 */

require_once('modules/VteSync/VteSync.php');

global $current_user;
if (!is_admin($current_user)) die('Not authorized');

$saveid = intval($_REQUEST['saveid']);

$vsync = VteSync::getInstance();

// load data from session
$data = $vsync->loadOAuthData($saveid);
$typeid = $data['typeid'];
$client_id = $data['client_id'];
$state = '';

$authUrl = $vsync->getOAuthAuthUrl($typeid, $client_id, $state);

if ($authUrl) {
	// save the state
	$data['state'] = $state;
	$vsync->replaceOAuthData($saveid, $data);

	header("Location: $authUrl");
	die();
} else {
	die('Unable to prepare the authorization url');
}