<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554

defined('BASEPATH') OR exit('No direct script access allowed');

global $CFG, $GPDRManager;

if (!$GPDRManager->isPrivacyPolicyConfirmed()) {
	GDPR\Redirect::to('settings');
}

if (!$GPDRManager->hasDuplicates()) {
	GDPR\Redirect::to('detailview');
}

$smarty = new GDPR\SmartyConfig();

$contactId = $GPDRManager->getContactId();
$accessToken = $GPDRManager->getAccessToken();
$contactEmail = $GPDRManager->getContactEmail();

$smarty->assign('BROWSER_TITLE', _T('browser_title_merge'));
$smarty->assign('CONTACT_ID', $contactId);
$smarty->assign('ACCESS_TOKEN', $accessToken);
$smarty->assign('CONTACT_EMAIL', $contactEmail);

$duplicates = $GPDRManager->getContactDuplicates();
$smarty->assign('CONTACT_DUPLICATES', $duplicates);

$smarty->display('Merge.tpl');