<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Settings/MailScanner/core/MailScannerRule.php');

$mode = $_REQUEST['mode'];
$targetruleid = $_REQUEST['targetruleid'];
$ruleid = $_REQUEST['ruleid'];
	
if($mode == 'rulemove_up') {
	Vtenext_MailScannerRule::resetSequence($ruleid, $targetruleid);//crmv@207843
} else if($mode == 'rulemove_down') {
	Vtenext_MailScannerRule::resetSequence($ruleid, $targetruleid);//crmv@207843
}

include('modules/Settings/MailScanner/MailScannerRule.php');

?>