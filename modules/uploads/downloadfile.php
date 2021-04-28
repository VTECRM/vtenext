<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@95157 */

require_once('modules/Documents/storage/StorageBackendUtils.php');

$attachmentsid = intval($_REQUEST['fileid']);
$entityid = intval($_REQUEST['entityid']);
$returnmodule = $_REQUEST['return_module'];
if ($entityid > 0 && empty($returnmodule)) $returnmodule = getSalesEntityType($entityid); //crmv@181250

if (isPermitted($returnmodule, 'DetailView', $entityid) != 'yes') {
	die('Not permitted');
}

$SBU = StorageBackendUtils::getInstance();
$SBU->downloadFile($returnmodule, $entityid, $attachmentsid);
