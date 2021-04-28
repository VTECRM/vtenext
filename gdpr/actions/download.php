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

if (!$GPDRManager->isPrivacyPolicyConfirmed()) {
	GDPR\Redirect::to('settings');
}

if ($GPDRManager->hasDuplicates()) {
	$GPDRManager->processAction('merge');
	exit();
}

$GPDRManager->downloadContactData();