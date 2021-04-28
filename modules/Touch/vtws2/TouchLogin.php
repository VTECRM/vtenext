<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@31780 - parametri extra al login */
/* crmv@33097 - offline */
/* crmv@37463 */
/* crmv@91082 - LoginHistory support */

require_once('modules/Users/LoginHistory.php');
require_once('include/utils/SessionValidator.php'); 

class TouchLogin extends TouchWSClass {

	public $preLogin = true;

	function process(&$request) {
		global $touchInst, $touchUtils, $touchCache, $adb, $table_prefix, $current_user;
		global $enterprise_current_version, $enterprise_current_build, $application_unique_key;

		$username = $_POST['username'];
		$password = $_POST['password'];
		$app_version = $_REQUEST['app_version'];
		$cipher = $request['cipher'];

		if (!empty($cipher)) {
			// TODO: not supported yet
		}

		$user = $touchUtils->getModuleInstance('Users');
		$user->column_fields['user_name'] = $username;
		if (!$user->doLogin($password)) {
			return $this->error("Invalid username or password");
		}
		unset($password);

		$userId = $user->retrieve_user_id($username);

		if (empty($userId)) {
			return $this->error('Unable to find an ID for the specified user');
		}

		$user = $user->retrieveCurrentUserInfoFromFile($userId);
		if ($user->status != 'Inactive'){
			//crmv@37463
			$current_user = $user;
			//crmv@37463e
			$accesskey = $user->column_fields['accesskey'];
		} else {
			return $this->error('The specified user is inactive');
		}

		if (empty($accesskey)) {
			return $this->error('The accesskey for the user is empty');
		}

		if (isPermitted('Touch', 'DetailView') != 'yes') {
			return $this->error('Touch module is not active');
		}

		$lang = 'it_it';
		if (!empty($current_user->column_fields['default_language'])) {
			$lang = $current_user->column_fields['default_language'];
		}
		
		if ($request['login_data']) {
			$touchInst->processLoginData($request['login_data']);
		}
		
		// crmv@91082 - login history
		$loghistory = LoginHistory::getInstance();
		$Signin = $loghistory->user_login($current_user->column_fields["user_name"]);
		
		$SV = SessionValidator::getInstance();
		$SV->refresh();
		// crmv@91082e
		
		//auditing
		// crmv@202301
		require_once('modules/Settings/AuditTrail.php');
		$AuditTrail = new AuditTrail();
		$AuditTrail->processAuthenticate($user, 'app');
		// crmv@202301e

		// extra parameters
		$params = array(
			'version' => $touchInst->version,
			'legacy_version' => false,
			'vte_version' => $enterprise_current_version,
			'vte_revision' => $enterprise_current_build,
			'vte_appkey' => $application_unique_key,
			'key' => $accesskey,
			'user_language' => $lang,
			//crmv@42707
			'userid' => $userId,
			'user_name' => $current_user->column_fields['user_name'], // crmv@198350 - LDAP login can be case insensitive
			'is_admin' => is_admin($current_user),
			//crmv@42707e
			// crmv@48677
			'thousands_separator' => $current_user->column_fields['thousands_separator'],
			'decimal_separator' => $current_user->column_fields['decimal_separator'],
			'decimals_num' => $current_user->column_fields['decimals_num'],
			// crmv@48677e
			'list_page_limit' => $touchInst->listPageLimit,
			'enable_offline' => intval($touchInst->isOfflineEnabled()),
			// leave this, it's to trick a buggy version of the activator
			'use'.'_offline_cache' => $touchInst->getProperty('use'.'_offline_cache'),
			'use_geolocation' => $touchInst->getProperty('use_geolocation') ?: false, // crmv@59610
			// crmv@86915
			'has_geolocation_mod' => (isModuleInstalled('Geolocalization') && vtlib_isModuleActive('Geolocalization')),
			'app_theme' => $touchInst->getProperty('app_theme') ?: '',
			// crmv@86915e
		);
		// crmv@174250
		if ($params['has_geolocation_mod']) {
			require_once('modules/Geolocalization/Geolocalization.php');
			$params['gapi_key'] = Geolocalization::getApiKey();
		}
		// crmv@174250e

		$touchCache->clear();
		$touchInst->resetWSSession();

		return $this->success($params);
	}
}
