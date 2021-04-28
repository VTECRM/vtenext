<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@27618
$mode = $_REQUEST['mode'];
if ($mode == 'spamedit') {
	include('modules/Settings/MailScanner/MailScannerSpamRuleEdit.php');
} elseif ($mode == 'spamsave') {
	include('modules/Settings/MailScanner/MailScannerSpamRuleSave.php');
}
//crmv@27618e
?>