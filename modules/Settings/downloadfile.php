<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb, $table_prefix;
global $mod_strings, $default_charset;

$dbQuery = "SELECT logo, logoname FROM {$table_prefix}_organizationdetails";

$result = $adb->query($dbQuery) or die("Couldn't get file list");
if($adb->num_rows($result) == 1) {
	$name = $adb->query_result($result, 0, "logoname");
	$fileContent = $adb->query_result($result, 0, "logo");
	$name = html_entity_decode($name, ENT_QUOTES, $default_charset);
	header("Cache-Control: private");
	header("Content-Disposition: attachment; filename=$name");
	header("Content-Description: PHP Generated Data");
	echo base64_decode($fileContent);
} else {
	echo $mod_strings['LBL_RECORD_NOEXIST'];
}
