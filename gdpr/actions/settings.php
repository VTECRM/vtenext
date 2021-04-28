<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554

defined('BASEPATH') OR exit('No direct script access allowed');

global $CFG, $GPDRManager;

if (!$GPDRManager->isValidSession()) {
	GDPR\Redirect::to('verify');
}

$smarty = new GDPR\SmartyConfig();

$contactId = $GPDRManager->getContactId();
$accessToken = $GPDRManager->getAccessToken();

$privacyPolicyConfirmed = $GPDRManager->isPrivacyPolicyConfirmed();

if ($privacyPolicyConfirmed && $GPDRManager->hasDuplicates()) {
	$GPDRManager->processAction('merge');
	exit();
}

$contactData = $GPDRManager->getContactData();
$contactEmail = $GPDRManager->getContactEmail();

$smarty->assign('BROWSER_TITLE', _T('browser_title_settings'));
$smarty->assign('CONTACT_ID', $contactId);
$smarty->assign('CONTACT_EMAIL', $contactEmail);
$smarty->assign('ACCESS_TOKEN', $accessToken);
$smarty->assign('PRIVACY_POLICY_CONFIRMED', $privacyPolicyConfirmed);

$settingsData = array(
	'gdpr_privacypolicy' => $privacyPolicyConfirmed ? 'checked' : '',
	'gdpr_personal_data' => $contactData['gdpr_personal_data'] ? 'checked' : '',
	'gdpr_marketing' => $contactData['gdpr_marketing'] ? 'checked' : '',
	'gdpr_thirdparties' => $contactData['gdpr_thirdparties'] ? 'checked' : '',
	'gdpr_profiling' => $contactData['gdpr_profiling'] ? 'checked' : '',
	'gdpr_restricted' => $contactData['gdpr_restricted'] ? 'checked' : '',
	'gdpr_notifychange' => $contactData['gdpr_notifychange'] ? 'checked' : '',
);

$smarty->assign('SETTINGS_DATA', $settingsData);

$smarty->display('Settings.tpl');