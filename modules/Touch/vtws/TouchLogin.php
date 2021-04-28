<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 - parametri extra al login */
/* crmv@33097 - offline */
/* crmv@37463 */

global $adb, $table_prefix;

// require login through post request only
$accessKey = touch_ws_login($_POST['username'],$_POST['password']);
$lang = 'it_it';

if ($accessKey) {

	$res = $adb->pquery("select id from {$table_prefix}_users where user_name = ?", array($_POST['username']));
	if ($res && $adb->num_rows($res) > 0) {
		$userid = $adb->query_result($res, 0, 'id');
		$current_user = CRMEntity::getInstance('Users');
		$current_user->id = $userid;
		$current_user->retrieveCurrentUserInfoFromFile($userid);
		$lang = $current_user->column_fields['default_language'];
	}

	// extra parameters
	$params = array(
		'version' => $touchInst->legacyVersion,
		'legacy_version' => true,
		'vte_version' => $enterprise_current_version,
		'vte_revision' => $enterprise_current_build,
		'key' => $accessKey,
		'user_language' => $lang,
		//crmv@42707
		'userid' => $userid,
		'is_admin' => is_admin($current_user),
		//crmv@42707e
		// crmv@48677
		'thousands_separator' => $current_user->column_fields['thousands_separator'],
		'decimal_separator' => $current_user->column_fields['decimal_separator'],
		'decimals_num' => $current_user->column_fields['decimals_num'],
		// crmv@48677e
		'list_page_limit' => $touchInst->listPageLimit,
		'enable_offline' => intval($touchInst->isOfflineEnabled()),
		'use_geolocation' => $touchInst->getProperty('use_geolocation') ?: false, // crmv@59610
	);

} else {
	die();
}

echo Zend_Json::encode($params);
?>