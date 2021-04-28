<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@83340 crmv@102334 */

global $currentModule;

$indexFile = 'HomeView';
if (file_exists("modules/$currentModule/$indexFile.php")) {
	checkFileAccess("modules/$currentModule/$indexFile.php");
	require("modules/$currentModule/$indexFile.php");
} else {
	require("modules/VteCore/$indexFile.php");
}