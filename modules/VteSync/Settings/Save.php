<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@176547 */

require_once('modules/VteSync/VteSync.php');

global $current_user;
if (!is_admin($current_user)) die('Not authorized');

$vsync = VteSync::getInstance();

$syncid = intval($_POST['syncid']);
$saveid = intval($_POST['oauth2_saveid']);

// TODO validate
$error = "";
$valid = $vsync->validateSave($_POST, $syncid > 0 ? 'update' : 'create', $error);

if ($valid) {
	if ($syncid > 0) {
		$r = $vsync->updateSync($syncid, $_POST, $saveid);
	} else {
		// insert
		$r = $vsync->insertSync($_POST['synctype'], $_POST, $saveid);
	}

	if ($r && $saveid > 0) {
		$vsync->clearOAuthData($saveid);
	} elseif (!$r) {
		$error = "Unable to save";
	}
}

if ($error) {
	// bad, but errors are nicely caught by ajax presave
	die($error);
} else {
	header('Location: index.php?module=Settings&action=VteSync&parenttab=Settings');
}

