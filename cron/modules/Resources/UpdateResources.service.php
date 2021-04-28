<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@144893 */

require('config.inc.php');
require_once('include/utils/ResourceVersion.php');

$RV = ResourceVersion::getInstance();

// if you want to force the update of a specific file, use the following statement
//$RV->createResource('path/to/file.js');

$RV->enableCacheWrite();
$RV->updateResources();