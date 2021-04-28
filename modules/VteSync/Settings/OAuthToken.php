<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@176547 */

require_once('modules/VteSync/VteSync.php');

global $current_user;
if (!is_admin($current_user)) die('Not authorized');

$code = $_REQUEST['code'];

$vsync = VteSync::getInstance();

if ($code) {
	$state = $_GET['state'];
	$saveid = $vsync->searchOAuthData($state);
	$data = $vsync->loadOAuthData($saveid);
	if (!$saveid || !$vsync->checkOAuthState($state, $data)) {
		if ($saveid > 0) $vsync->clearOAuthData($saveid);
		die('Invalid state');
	}
	
	if (!$vsync->getAccessToken($code, $saveid)) {
		die();
	}
	
	// ok, we have the token!
	echo '<html><body><script type="text/javascript">window.opener.VteSyncConfig.setAuthorizeStatus(true, "'.$saveid.'"); window.close()</script></body></html>';
	
} else {
	die('Authorization code not provided');
}
//$authUrl = $vsync->getOAuthAuthUrl($typeid, $client_id);