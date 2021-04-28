<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@202301 */

require_once('modules/Settings/AuditTrail.php');

$AuditTrail = new AuditTrail();

if ($AuditTrail->isEnabled()) {
	$AuditTrail->disable();
} else {
	$AuditTrail->enable();
}

echo Zend_Json::encode(['success' => true]);