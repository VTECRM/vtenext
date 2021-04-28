<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@75301 */

require_once("config.inc.php");
require_once("include/utils/utils.php");
require_once('include/utils/PageHeader.php');

// by using a class, I can extend it and provide customizations easily
$VPH = VTEPageHeader::getInstance();
$VPH->displayHeader();