<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $currentModule, $current_user;

// the first time load still the fist page
$lvs = getLVS($currentModule);
if (!isset($_REQUEST['calc_nav']) && !empty($lvs)) {
	setLVSDetails($currentModule,$lvs['viewname'],1,'start');
}
	
checkFileAccess("modules/$currentModule/ListView.php");
include_once("modules/$currentModule/ListView.php");
?>