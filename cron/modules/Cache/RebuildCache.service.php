<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@164465 */

// for now only vteprop is supported
$VP = VTEProperties::getInstance();
if (!$VP->checkCacheValidity()) {
	$VP->rebuildCache();
}

// you can add here other caches to update