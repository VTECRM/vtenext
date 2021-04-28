<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@90004 */

global $currentModule;

if (file_exists("modules/$currentModule/ListViewFolder.php")) {
	checkFileAccess("modules/$currentModule/ListViewFolder.php");
	include("modules/$currentModule/ListViewFolder.php");
} else {
	include("modules/VteCore/ListViewFolder.php");	//crmv@30967
}