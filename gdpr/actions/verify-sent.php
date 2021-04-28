<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@161554

defined('BASEPATH') OR exit('No direct script access allowed');

global $CFG, $GPDRManager;

$smarty = new GDPR\SmartyConfig();

$contactId = $GPDRManager->getContactId();

$smarty->assign('BROWSER_TITLE', _T('browser_title_verify_sent'));
$smarty->assign('CONTACT_ID', $contactId);

$smarty->display('VerifySent.tpl');