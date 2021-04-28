<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@63475 */

global $currentModule;
$record = vtlib_purify($_REQUEST['record']);	// message record
$contentid = vtlib_purify($_REQUEST['contentid']);	// attachment id
$linkto = vtlib_purify($_REQUEST['linkto']);
$linkto_module = vtlib_purify($_REQUEST['linkto_module']);

$focus = CRMEntity::getInstance($currentModule);
$focus->saveDocument($record,$contentid,$linkto,$linkto_module);

die('SUCCESS');
?>