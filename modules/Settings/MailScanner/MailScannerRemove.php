<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once('modules/Settings/MailScanner/core/MailScannerInfo.php');
require_once('modules/Settings/MailScanner/core/MailBox.php');

$scannerinfo = new Vtenext_MailScannerInfo(trim($_REQUEST['scannername']));//crmv@207843

$scannerinfo->delete();

header('Location: index.php?module=Settings&action=MailScanner&parenttab=Settings');

?>