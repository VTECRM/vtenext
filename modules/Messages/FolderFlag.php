<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $currentModule;
$account = vtlib_purify($_REQUEST['account']);
$folder = $_REQUEST['folder'];
$action = $_REQUEST['faction'];
$focus = CRMEntity::getInstance($currentModule);
$focus->flagFolder($account,$folder,$action);
exit;
?>