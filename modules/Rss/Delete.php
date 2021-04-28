<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

global $adb, $table_prefix;

if(!isset($_REQUEST['record'])) {
	die($mod_strings['ERR_DELETE_RECORD']);
}

$del_query = "DELETE FROM ".$table_prefix."_rss WHERE rssid=?";
$adb->pquery($del_query, array($_REQUEST['record']));

header("Location: index.php?module=".vtlib_purify($_REQUEST['return_module'])."&action=RssAjax&file=ListView&directmode=ajax");
