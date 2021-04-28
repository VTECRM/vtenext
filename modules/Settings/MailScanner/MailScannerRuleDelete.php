<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('modules/Settings/MailScanner/core/MailScannerRule.php');

$scannername = $_REQUEST['scannername'];
$scannerruleid= $_REQUEST['ruleid'];
$scannerrule = new Vtenext_MailScannerRule($scannerruleid);//crmv@207843
$scannerrule->delete();

include('modules/Settings/MailScanner/MailScannerRule.php');

?>