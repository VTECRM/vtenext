<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
global $currentModule;

// crmv@30967
checkFileAccess("modules/$currentModule/ListViewFolder.php");
include_once("modules/$currentModule/ListViewFolder.php");
// crmv@30967e
?>