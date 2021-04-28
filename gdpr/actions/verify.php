<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554 crmv@163697

defined('BASEPATH') OR exit('No direct script access allowed');

global $CFG, $GPDRManager;

$GPDRManager->clear();

$contactId = $GPDRManager->getContactId();

$authTokenResult = $GPDRManager->getAuthToken();
if (!$authTokenResult['success']) {
	if ($authTokenResult['error'] === 'OPERATION_DENIED') {
		$GPDRManager->showOperationDenied($authTokenResult, true);
	} else {
		$GPDRManager->showError(_T($authTokenResult['error']), '', true);
	}
}

$smarty = new GDPR\SmartyConfig();

$authToken = $authTokenResult['token'];

$contactEmail = $GPDRManager->getContactEmail();

$smarty->assign('BROWSER_TITLE', _T('browser_title_verify'));
$smarty->assign('CONTACT_ID', $contactId);
$smarty->assign('CONTACT_EMAIL', $contactEmail);
$smarty->assign('AUTH_TOKEN', $authToken);

$smarty->display('Verify.tpl');